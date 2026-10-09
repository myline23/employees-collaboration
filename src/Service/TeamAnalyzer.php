<?php

declare(strict_types=1);

final class TeamAnalyzer
{
    public function __construct(
        private readonly OverlapCalculator $calculator,
    ) {
    }

    /**
     * @param WorkRecord[] $records
     * @return array{
     *     employeeId1: int,
     *     employeeId2: int,
     *     totalDays: int,
     *     projects: array<int, int>
     * }|null
     */
    public function analyze(array $records): ?array
    {
        $recordsByProject = [];

        foreach ($records as $record) {
            $recordsByProject[$record->projectId][] = $record;
        }

        $pairs = [];

        foreach ($recordsByProject as $projectId => $projectRecords) {
            $count = count($projectRecords);

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $first = $projectRecords[$i];
                    $second = $projectRecords[$j];

                    if ($first->employeeId === $second->employeeId) {
                        $start = max($first->dateFrom, $second->dateFrom);
                        $end = min($first->dateTo, $second->dateTo);

                        if ($start <= $end) {
                            throw new InvalidArgumentException(
                                "Overlapping periods for employee "
                                . "{$first->employeeId} in project {$projectId}.",
                            );
                        }

                        continue;
                    }

                    $days = $this->calculator->calculateDays(
                        $first,
                        $second,
                    );

                    if ($days === 0) {
                        continue;
                    }

                    $employeeId1 = min(
                        $first->employeeId,
                        $second->employeeId,
                    );

                    $employeeId2 = max(
                        $first->employeeId,
                        $second->employeeId,
                    );

                    $key = "{$employeeId1}:{$employeeId2}";

                    if (!isset($pairs[$key])) {
                        $pairs[$key] = [
                            'employeeId1' => $employeeId1,
                            'employeeId2' => $employeeId2,
                            'totalDays' => 0,
                            'projects' => [],
                        ];
                    }

                    $pairs[$key]['totalDays'] += $days;

                    $pairs[$key]['projects'][$projectId] =
                        ($pairs[$key]['projects'][$projectId] ?? 0) + $days;
                }
            }
        }

        $winner = null;

        foreach ($pairs as $pair) {
            if (
                $winner === null ||
                $pair['totalDays'] > $winner['totalDays'] ||
                (
                    $pair['totalDays'] === $winner['totalDays'] &&
                    (
                        $pair['employeeId1'] < $winner['employeeId1'] ||
                        (
                            $pair['employeeId1'] === $winner['employeeId1'] &&
                            $pair['employeeId2'] < $winner['employeeId2']
                        )
                    )
                )
            ) {
                $winner = $pair;
            }
        }

        if ($winner !== null) {
            ksort($winner['projects'], SORT_NUMERIC);
        }

        return $winner;
    }
}