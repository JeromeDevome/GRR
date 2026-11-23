<?php

namespace Devome\Grr\Service\Report;

/**
 * Un critère de recherche du rapport : « <champ> contient / ne contient pas <texte> ».
 */
final class ReportCondition
{
    public const FIELDS = ['site', 'area', 'room', 'type', 'name', 'descr', 'login'];

    public function __construct(
        public readonly string $field,
        public readonly bool $contains,
        public readonly string $text,
    ) {
    }

    public static function isValidField(string $field): bool
    {
        return in_array($field, self::FIELDS, true) || self::additionalFieldId($field) !== null;
    }

    /** Identifiant du champ additionnel pour un champ « addon_<id> », null sinon. */
    public static function additionalFieldId(string $field): ?int
    {
        return preg_match('/^addon_([0-9]+)$/D', $field, $matches) === 1 ? (int) $matches[1] : null;
    }
}
