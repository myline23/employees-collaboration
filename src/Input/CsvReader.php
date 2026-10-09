<?php

declare(strict_types=1);

final class CsvReader
{
    public function __construct(
        private readonly DateParser $dateParser,
    ) {
    }

    /** @return WorkRecord[] */
    public function read(string $filename): array
    {
        $handle = fopen($filename, 'r');

        if ($handle === false) {
            throw new RuntimeException('Cannot open CSV file.');
        }

        $records = [];
        $errors = [];
        $line = 0;
        $headerAllowed = true;
        $today = new DateTimeImmutable('today');

        try {
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;

                $row = array_map(
                    static fn($value): string => trim((string) $value),
                    $row,
                );

                if ($line === 1) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                }

                if (count($row) === 1 && $row[0] === '') {
                    continue;
                }

                if (
                    $headerAllowed &&
                    $row === ['EmpID', 'ProjectID', 'DateFrom', 'DateTo']
                ) {
                    $headerAllowed = false;
                    continue;
                }

                $headerAllowed = false;

                try {
                    if (count($row) !== 4) {
                        throw new InvalidArgumentException(
                            'Expected exactly four columns.',
                        );
                    }

                    $employeeId = $this->parseId($row[0]);
                    $projectId = $this->parseId($row[1]);

                    $dateFrom = $this->dateParser->parse($row[2]);

                    $dateTo = strtoupper($row[3]) === 'NULL'
                        ? $today
                        : $this->dateParser->parse($row[3]);

                    if ($dateFrom > $dateTo) {
                        throw new InvalidArgumentException(
                            'DateFrom must not be after DateTo.',
                        );
                    }

                    $records[] = new WorkRecord(
                        $employeeId,
                        $projectId,
                        $dateFrom,
                        $dateTo,
                    );
                } catch (InvalidArgumentException $exception) {
                    $errors[] = "Row {$line}: {$exception->getMessage()}";
                }
            }
        } finally {
            fclose($handle);
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(
                implode(PHP_EOL, $errors),
            );
        }

        if ($records === []) {
            throw new InvalidArgumentException(
                'CSV file contains no data records.',
            );
        }

        return $records;
    }

    private function parseId(string $value): int
    {
        if (!preg_match('/^[0-9]+$/', $value)) {
            throw new InvalidArgumentException(
                "Invalid ID: '{$value}'.",
            );
        }

        $id = filter_var($value, FILTER_VALIDATE_INT);

        if ($id === false || $id < 0) {
            throw new InvalidArgumentException(
                "ID is outside the supported integer range: '{$value}'.",
            );
        }

        return $id;
    }
}