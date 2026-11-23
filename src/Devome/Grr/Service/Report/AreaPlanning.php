<?php

namespace Devome\Grr\Service\Report;

/**
 * Adaptateur vers get_planning_area_values(), qui charge dans des variables globales
 * les réglages d'affichage d'un domaine (créneaux, format horaire…) lus ensuite par
 * describe_span() / describe_period_span(). Le domaine n'est rechargé que s'il change.
 *
 * C'est le seul point du rapport qui manipule encore ces globales.
 */
final class AreaPlanning
{
    private ?int $currentAreaId = null;

    public function activate(int $areaId): void
    {
        if ($areaId !== $this->currentAreaId) {
            get_planning_area_values($areaId);
            $this->currentAreaId = $areaId;
        }
    }

    public function usesPeriods(): bool
    {
        return ($GLOBALS['enable_periods'] ?? 'n') === 'y';
    }

    public function periodCount(): int
    {
        return max(1, count($GLOBALS['periods_name'] ?? []));
    }
}
