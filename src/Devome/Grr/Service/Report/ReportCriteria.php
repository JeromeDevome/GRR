<?php

namespace Devome\Grr\Service\Report;

use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Paramètres du formulaire « Recherche & Stats », validés une fois pour toutes.
 */
final class ReportCriteria
{
    public const MODE_DETAILS = 1;
    public const MODE_SUMMARY = 2;
    public const MODE_BOTH = 3;

    public const SUM_BY_BENEFICIARY = '6';
    public const SUM_BY_DESCRIPTION = '3';
    public const SUM_BY_TYPE = '5';

    private const MIN_FORM_ROWS = 5;

    /**
     * @param list<ReportCondition> $conditions
     */
    public function __construct(
        public readonly bool $submitted,
        public readonly int $fromDay,
        public readonly int $fromMonth,
        public readonly int $fromYear,
        public readonly int $toDay,
        public readonly int $toMonth,
        public readonly int $toYear,
        public readonly array $conditions,
        public readonly bool $matchAll,
        public readonly int $mode,
        public readonly string $sumBy,
    ) {
    }

    /**
     * @param int $defaultReportDays réglage « default_report_days » : la date de fin par défaut vaut « date de début - N jours »
     */
    public static function fromRequest(Request $request, int $day, int $month, int $year, int $defaultReportDays): self
    {
        $query = $request->query;
        $defaultEnd = mktime(0, 0, 0, $month, $day - $defaultReportDays, $year);

        $parameters = $query->all();
        $fields = (array) ($parameters['champ'] ?? []);
        $texts = (array) ($parameters['texte'] ?? []);
        $types = (array) ($parameters['type_recherche'] ?? []);
        $conditions = [];
        foreach ($fields as $index => $field) {
            $field = is_string($field) ? $field : '';
            $text = is_string($texts[$index] ?? null) ? $texts[$index] : '';
            if ($field === '' || $text === '' || !ReportCondition::isValidField($field)) {
                continue;
            }
            $conditions[] = new ReportCondition($field, ($types[$index] ?? '1') !== '0', $text);
        }

        $mode = self::integer($query, 'summarize', self::MODE_DETAILS);
        $sumBy = (string) $query->get('sumby', self::SUM_BY_BENEFICIARY);
        $validSumBy = in_array($sumBy, [self::SUM_BY_BENEFICIARY, self::SUM_BY_DESCRIPTION, self::SUM_BY_TYPE], true)
            || ReportCondition::additionalFieldId($sumBy) !== null;

        return new self(
            submitted: $query->has('is_posted'),
            fromDay: self::integer($query, 'From_day', $day),
            fromMonth: self::integer($query, 'From_month', $month),
            fromYear: self::integer($query, 'From_year', $year),
            toDay: self::integer($query, 'To_day', (int) date('d', $defaultEnd)),
            toMonth: self::integer($query, 'To_month', (int) date('m', $defaultEnd)),
            toYear: self::integer($query, 'To_year', (int) date('Y', $defaultEnd)),
            conditions: $conditions,
            matchAll: $query->get('condition_et_ou') !== 'OR',
            mode: in_array($mode, [self::MODE_DETAILS, self::MODE_SUMMARY, self::MODE_BOTH], true) ? $mode : self::MODE_DETAILS,
            sumBy: $validSumBy ? $sumBy : self::SUM_BY_BENEFICIARY,
        );
    }

    /** Entier du paramètre, « 01 » compris (les sélecteurs de date envoient des zéros de tête). */
    private static function integer(InputBag $query, string $key, int $default): int
    {
        $value = $query->get($key);

        return is_string($value) && preg_match('/^-?[0-9]+$/D', $value) === 1 ? (int) $value : $default;
    }

    /** Début de la période, à 00:00 du jour de début. */
    public function startsAt(): int
    {
        return mktime(0, 0, 0, $this->fromMonth, $this->fromDay, $this->fromYear);
    }

    /** Fin de la période (exclue), à 00:00 du lendemain du jour de fin. */
    public function endsAt(): int
    {
        return mktime(0, 0, 0, $this->toMonth, $this->toDay + 1, $this->toYear);
    }

    public function includesDetails(): bool
    {
        return $this->mode !== self::MODE_SUMMARY;
    }

    public function includesSummary(): bool
    {
        return $this->mode !== self::MODE_DETAILS;
    }

    /**
     * Lignes de critères affichées dans le formulaire : celles saisies, plus deux vides (au moins cinq au total).
     *
     * @return list<array{field: string, contains: bool, text: string}>
     */
    public function formRows(): array
    {
        $rows = array_map(
            static fn (ReportCondition $condition): array => [
                'field' => $condition->field,
                'contains' => $condition->contains,
                'text' => $condition->text,
            ],
            $this->conditions,
        );
        $total = max(count($rows) + 2, self::MIN_FORM_ROWS);
        while (count($rows) < $total) {
            $rows[] = ['field' => '', 'contains' => true, 'text' => ''];
        }

        return $rows;
    }
}
