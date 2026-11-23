<?php

namespace Devome\Grr\Service\Report;

use SecuAccess;

/**
 * L'utilisateur qui consulte le rapport et ce qu'il a le droit de voir.
 */
final class ReportViewer
{
    private const ADMINISTRATOR_LEVEL = 6;
    private const MANAGER_LEVEL = 3;

    /** @var array<int, int> niveau d'accès par ressource, calculé à la demande */
    private array $roomLevels = [];

    /**
     * @param list<int> $hiddenRoomIds ressources invisibles pour l'utilisateur
     */
    public function __construct(
        public readonly string $login,
        public readonly int $level,
        public readonly array $hiddenRoomIds,
    ) {
    }

    public static function current(): self
    {
        $login = (string) getUserName();

        return new self(
            $login,
            (int) SecuAccess::UserLevel($login, -1),
            array_map('intval', SecuAccess::UserResource($login, 'all')),
        );
    }

    public function isAdministrator(): bool
    {
        return $this->level >= self::ADMINISTRATOR_LEVEL;
    }

    /** Les champs additionnels non publics ne sont visibles que des administrateurs. */
    public function seesHiddenAdditionalFields(): bool
    {
        return $this->isAdministrator();
    }

    /** Une réservation confidentielle n'est visible que de son bénéficiaire et des gestionnaires de la ressource. */
    public function canSeeConfidentialEntry(string $beneficiary, int $roomId): bool
    {
        if ($beneficiary === $this->login) {
            return true;
        }
        $this->roomLevels[$roomId] ??= (int) SecuAccess::UserLevel($this->login, $roomId);

        return $this->roomLevels[$roomId] >= self::MANAGER_LEVEL;
    }
}
