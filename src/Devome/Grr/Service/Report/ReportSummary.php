<?php

namespace Devome\Grr\Service\Report;

/**
 * Tableau croisé « valeur résumée × ressource » : nombre de réservations et volume réservé.
 * Le volume est en heures, ou en nombre de créneaux pour les domaines gérés par créneaux.
 */
final class ReportSummary
{
    /** @var array<string, array{area: string, room: string}> */
    private array $columns = [];
    /** @var array<string, true> */
    private array $lines = [];
    /** @var array<string, array<string, int>> */
    private array $counts = [];
    /** @var array<string, array<string, float>> */
    private array $amounts = [];

    public function __construct(public readonly bool $periods)
    {
    }

    public function add(string $areaName, string $roomName, string $label, float $amount): void
    {
        $column = $areaName."\0".$roomName;
        $this->columns[$column] ??= ['area' => $areaName, 'room' => $roomName];
        $this->lines[$label] = true;
        $this->counts[$label][$column] = ($this->counts[$label][$column] ?? 0) + 1;
        $this->amounts[$label][$column] = ($this->amounts[$label][$column] ?? 0.0) + $amount;
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /**
     * Données prêtes pour le template : lignes et colonnes dans l'ordre de première apparition.
     *
     * @return array{periods: bool, columns: list<array{area: string, room: string}>, lines: list<array{label: string, cells: list<?array{count: int, amount: string}>, total: array{count: int, amount: string}}>, totals: list<array{count: int, amount: string}>, grandTotal: array{count: int, amount: string}}
     */
    public function toArray(): array
    {
        $columnTotals = array_fill_keys(array_keys($this->columns), ['count' => 0, 'amount' => 0.0]);
        $grandCount = 0;
        $grandAmount = 0.0;
        $lines = [];

        foreach (array_keys($this->lines) as $label) {
            $label = (string) $label;
            $cells = [];
            $lineCount = 0;
            $lineAmount = 0.0;
            foreach (array_keys($this->columns) as $column) {
                if (!isset($this->counts[$label][$column])) {
                    $cells[] = null;
                    continue;
                }
                $count = $this->counts[$label][$column];
                $amount = $this->amounts[$label][$column];
                $cells[] = self::cell($count, $amount);
                $lineCount += $count;
                $lineAmount += $amount;
                $columnTotals[$column]['count'] += $count;
                $columnTotals[$column]['amount'] += $amount;
            }
            $lines[] = ['label' => $label, 'cells' => $cells, 'total' => self::cell($lineCount, $lineAmount)];
            $grandCount += $lineCount;
            $grandAmount += $lineAmount;
        }

        return [
            'periods' => $this->periods,
            'columns' => array_values($this->columns),
            'lines' => $lines,
            'totals' => array_map(static fn (array $total): array => self::cell($total['count'], $total['amount']), array_values($columnTotals)),
            'grandTotal' => self::cell($grandCount, $grandAmount),
        ];
    }

    /** @return array{count: int, amount: string} */
    private static function cell(int $count, float $amount): array
    {
        // %f respecte la locale (virgule décimale en français), comme l'ancien affichage
        return ['count' => $count, 'amount' => sprintf('%.2f', $amount)];
    }
}
