<?php

namespace Devome\Grr\Service\Report;

/**
 * Produit le contenu du rapport (détail des réservations et résumés) à partir des critères.
 */
final class ReportGenerator
{
    private const MINUTES_PER_DAY = 24 * 60;
    private const HIDDEN = '*****';
    private const EMPTY_LABEL = '(Autres)';

    public function __construct(private readonly ReportRepository $repository)
    {
    }

    /**
     * @param list<AdditionalField> $additionalFields
     *
     * @return array{count: int, entries: list<array<string, mixed>>, summaries: list<array<string, mixed>>}
     */
    public function generate(ReportCriteria $criteria, ReportViewer $viewer, array $additionalFields, string $dateFormat): array
    {
        $rows = $this->repository->findEntries($criteria, $viewer, $additionalFields);
        $planning = new AreaPlanning();
        $summaries = ['hours' => new ReportSummary(false), 'periods' => new ReportSummary(true)];
        $entries = [];

        foreach ($rows as $row) {
            $planning->activate((int) $row['area_id']);
            if ($criteria->includesDetails()) {
                $entries[] = $this->entry($row, $viewer, $additionalFields, $planning, $dateFormat);
            }
            if ($criteria->includesSummary()) {
                $summary = $planning->usesPeriods() ? $summaries['periods'] : $summaries['hours'];
                $summary->add(
                    (string) $row['area_name'],
                    (string) $row['room_name'],
                    $this->summaryLabel($row, $criteria->sumBy, $additionalFields),
                    $this->bookedAmount($row, $criteria, $planning),
                );
            }
        }

        return [
            'count' => count($rows),
            'entries' => $entries,
            'summaries' => array_values(array_map(
                static fn (ReportSummary $summary): array => $summary->toArray(),
                array_filter($summaries, static fn (ReportSummary $summary): bool => !$summary->isEmpty()),
            )),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param list<AdditionalField> $additionalFields
     *
     * @return array<string, mixed>
     */
    private function entry(array $row, ReportViewer $viewer, array $additionalFields, AreaPlanning $planning, string $dateFormat): array
    {
        $start = (int) $row['start_time'];
        $end = (int) $row['end_time'];
        [$startDate, $startTime, $duration, $durationUnit, $endDate, $endTime] = $planning->usesPeriods()
            ? describe_period_span($start, $end)
            : describe_span($start, $end, $dateFormat);

        $visible = (int) $row['confidentiel_resa'] === 0
            || $viewer->canSeeConfidentialEntry((string) $row['beneficiaire'], (int) $row['room_id']);

        $additionalValues = [];
        foreach ($additionalFields as $field) {
            $value = $field->valueIn((string) $row['overload_desc']);
            $additionalValues[] = $value === '' ? '-' : $value;
        }

        return [
            'id' => (int) $row['id'],
            'site' => (string) ($row['sitename'] ?? ''),
            'area' => (string) $row['area_name'],
            'roomDescription' => (string) $row['room_description'],
            'room' => (string) $row['room_name'],
            'title' => $visible ? affichage_lien_resa_planning($row['name'], $row['id']) : self::HIDDEN,
            'descriptionHtml' => !$visible ? self::HIDDEN : ($row['description'] !== '' ? nl2br(htmlspecialchars((string) $row['description'])) : ''),
            'beneficiary' => $visible ? $this->beneficiaryName($row) : self::HIDDEN,
            'startTimestamp' => $start,
            'startDate' => $startDate,
            'startTime' => $startTime,
            'duration' => trim($duration.' '.$durationUnit),
            'endTimestamp' => $end,
            'endDate' => $endDate,
            'endTime' => $endTime,
            'type' => (string) $row['type_name'],
            'updatedTimestamp' => (int) $row['updated_at'],
            'updatedAt' => date_time_string((int) $row['updated_at'], $dateFormat),
            'moderation' => (int) $row['moderate'],
            'additionalValues' => $additionalValues,
        ];
    }

    /** @param array<string, mixed> $row */
    private function beneficiaryName(array $row): string
    {
        if ($row['beneficiary_last_name'] !== null) {
            return $row['beneficiary_first_name'].' '.$row['beneficiary_last_name'];
        }

        // Bénéficiaire extérieur : « nom|courriel »
        return explode('|', (string) $row['beneficiaire_ext'])[0];
    }

    /**
     * @param array<string, mixed> $row
     * @param list<AdditionalField> $additionalFields
     */
    private function summaryLabel(array $row, string $sumBy, array $additionalFields): string
    {
        $label = match ($sumBy) {
            ReportCriteria::SUM_BY_BENEFICIARY => (string) $row['beneficiaire'],
            ReportCriteria::SUM_BY_DESCRIPTION => (string) $row['name'],
            ReportCriteria::SUM_BY_TYPE => (string) $row['type_name'],
            default => $this->additionalField($sumBy, $additionalFields)?->valueIn((string) $row['overload_desc']) ?? '',
        };

        return $label === '' ? self::EMPTY_LABEL : $label;
    }

    /**
     * Volume réservé dans la période du rapport : en heures, ou en créneaux pour un domaine à créneaux
     * (une réservation par créneaux stocke le numéro de créneau dans les minutes de l'horodatage).
     *
     * @param array<string, mixed> $row
     */
    private function bookedAmount(array $row, ReportCriteria $criteria, AreaPlanning $planning): float
    {
        $seconds = min((int) $row['end_time'], $criteria->endsAt()) - max((int) $row['start_time'], $criteria->startsAt());
        if (!$planning->usesPeriods()) {
            return $seconds / 3600.0;
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < self::MINUTES_PER_DAY) {
            return (float) $minutes;
        }
        $periods = $planning->periodCount();

        return (float) (($minutes % $periods) + intdiv($minutes, self::MINUTES_PER_DAY) * $periods);
    }

    /** @param list<AdditionalField> $additionalFields */
    private function additionalField(string $key, array $additionalFields): ?AdditionalField
    {
        foreach ($additionalFields as $field) {
            if ($field->key() === $key) {
                return $field;
            }
        }

        return null;
    }
}
