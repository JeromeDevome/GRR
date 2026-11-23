<?php

namespace Devome\Grr\Service\Report;

/**
 * Champ additionnel (table _overload) utilisable comme critère, colonne ou axe de résumé.
 */
final class AdditionalField
{
    public function __construct(
        public readonly int $id,
        /** Libellé « nom (domaine) » affiché dans les listes */
        public readonly string $label,
    ) {
    }

    /**
     * @return list<self> les champs visibles par l'utilisateur, dans l'ordre de mrbsOverloadGetFieldslist()
     */
    public static function visibleTo(ReportViewer $viewer): array
    {
        $fields = [];
        foreach (mrbsOverloadGetFieldslist('') as $label => $field) {
            if ($viewer->seesHiddenAdditionalFields() || $field['affichage'] === 'y') {
                $fields[] = new self((int) $field['id'], (string) $label);
            }
        }

        return $fields;
    }

    public function key(): string
    {
        return 'addon_'.$this->id;
    }

    /** Valeur du champ dans la colonne overload_desc d'une réservation (« @id@valeur@/id@ »). */
    public function valueIn(string $overloadDescription): string
    {
        return grrExtractValueFromOverloadDesc($overloadDescription, $this->id);
    }
}
