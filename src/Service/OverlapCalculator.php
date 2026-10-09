<?php

declare(strict_types=1);

final class OverlapCalculator
{
    public function calculateDays(
        WorkRecord $first,
        WorkRecord $second,
    ): int {
        if (
            $first->projectId !== $second->projectId ||
            $first->employeeId === $second->employeeId
        ) {
            return 0;
        }

        $start = max($first->dateFrom, $second->dateFrom);
        $end = min($first->dateTo, $second->dateTo);

        if ($start > $end) {
            return 0;
        }

        return (int) $start->diff($end)->days + 1;
    }
}