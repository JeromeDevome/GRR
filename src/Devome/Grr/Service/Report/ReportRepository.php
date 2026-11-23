<?php

namespace Devome\Grr\Service\Report;

use Settings;

/**
 * Lecture des réservations correspondant aux critères du rapport.
 * Toutes les valeurs saisies par l'utilisateur passent par des paramètres de requête préparée.
 */
final class ReportRepository
{
    /** Colonnes texte interrogeables par un critère simple. */
    private const SEARCHABLE_COLUMNS = [
        'site' => 's.sitename',
        'area' => 'a.area_name',
        'room' => 'r.room_name',
        'type' => 't.type_name',
        'name' => 'e.name',
        'descr' => 'e.description',
    ];

    /**
     * @param list<AdditionalField> $additionalFields champs additionnels visibles (les autres sont ignorés comme critère)
     *
     * @return list<array<string, mixed>> une ligne par réservation, triée par domaine, ressource, type puis début
     */
    public function findEntries(ReportCriteria $criteria, ReportViewer $viewer, array $additionalFields): array
    {
        $prefix = TABLE_PREFIX;
        $multisite = Settings::get('module_multisite') == 1;
        $types = '';
        $params = [];

        $sql = 'SELECT DISTINCT e.id, e.start_time, e.end_time, e.name, e.description, e.type, e.beneficiaire, '
            .grr_sql_syntax_timestamp_to_unix('e.timestamp').' AS updated_at, '
            .'a.area_name, a.id AS area_id, r.room_name, r.description AS room_description, r.order_display, '
            .'r.confidentiel_resa, e.room_id, e.overload_desc, t.type_name, e.beneficiaire_ext, e.moderate, '
            .'u.prenom AS beneficiary_first_name, u.nom AS beneficiary_last_name'
            .($multisite ? ', s.sitename' : '')
            ." FROM {$prefix}_entry e"
            ." JOIN {$prefix}_room r ON r.id = e.room_id"
            ." JOIN {$prefix}_area a ON a.id = r.area_id"
            ." JOIN {$prefix}_type_area t ON t.type_letter = e.type"
            ." LEFT JOIN {$prefix}_utilisateurs u ON u.login = e.beneficiaire"
            .($multisite ? " JOIN {$prefix}_j_site_area sa ON sa.id_area = a.id JOIN {$prefix}_site s ON s.id = sa.id_site" : '')
            .' WHERE e.supprimer = 0 AND e.start_time < ? AND e.end_time > ?';
        $types .= 'ii';
        $params[] = $criteria->endsAt();
        $params[] = $criteria->startsAt();

        if ($viewer->hiddenRoomIds !== []) {
            $sql .= ' AND r.id NOT IN ('.implode(',', array_map('intval', $viewer->hiddenRoomIds)).')';
        }

        // Hors administrateurs : domaines publics, ou restreints auxquels l'utilisateur a accès
        if (!$viewer->isAdministrator()) {
            $sql .= " AND (a.access = 'a' OR (a.access = 'r' AND EXISTS ("
                ."SELECT 1 FROM {$prefix}_j_user_area j WHERE j.id_area = a.id AND j.login = ?)))";
            $types .= 's';
            $params[] = $viewer->login;
        }

        $clauses = [];
        foreach ($criteria->conditions as $condition) {
            $clause = $this->conditionClause($condition, $multisite, $additionalFields, $types, $params);
            if ($clause !== null) {
                $clauses[] = $clause;
            }
        }
        if ($clauses !== []) {
            $sql .= ' AND ('.implode($criteria->matchAll ? ' AND ' : ' OR ', $clauses).')';
        }

        $sql .= ' ORDER BY a.area_name, r.order_display, r.room_name, t.type_name, e.start_time';

        $result = grr_sql_query($sql, $types, $params);
        if (!$result) {
            fatal_error(0, grr_sql_error());
        }
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $rows;
    }

    /**
     * @param list<AdditionalField> $additionalFields
     * @param list<int|string> $params
     */
    private function conditionClause(ReportCondition $condition, bool $multisite, array $additionalFields, string &$types, array &$params): ?string
    {
        $like = $condition->contains ? 'LIKE' : 'NOT LIKE';
        $pattern = '%'.self::escapeLike($condition->text).'%';

        if ($condition->field === 'login') {
            // Bénéficiaire interne ou externe
            $types .= 'ss';
            array_push($params, $pattern, $pattern);

            return $condition->contains
                ? "(e.beneficiaire LIKE ? OR e.beneficiaire_ext LIKE ?)"
                : "(e.beneficiaire NOT LIKE ? AND e.beneficiaire_ext NOT LIKE ?)";
        }

        $fieldId = ReportCondition::additionalFieldId($condition->field);
        if ($fieldId !== null) {
            $visibleIds = array_map(static fn (AdditionalField $field): int => $field->id, $additionalFields);
            if (!in_array($fieldId, $visibleIds, true)) {
                return null;
            }
            // Format de stockage de overload_desc : « @id@valeur urlencodée@/id@ »
            $types .= 's';
            $params[] = '%@'.$fieldId.'@%'.self::escapeLike(urlencode($condition->text)).'%@/'.$fieldId.'@%';

            return "e.overload_desc $like ?";
        }

        if ($condition->field === 'site' && !$multisite) {
            return null;
        }
        $types .= 's';
        $params[] = $pattern;

        return self::SEARCHABLE_COLUMNS[$condition->field]." $like ?";
    }

    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
