<?php

declare(strict_types=1);

final class WorkRecord
{
    public function __construct(
        public readonly int $employeeId,
        public readonly int $projectId,
        public readonly DateTimeImmutable $dateFrom,
        public readonly DateTimeImmutable $dateTo,
    ) {
    }
}