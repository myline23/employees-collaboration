<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

try {
    if ($argc !== 2) {
        throw new InvalidArgumentException(
            'Usage: php index.php <csv-file>',
        );
    }

    $reader = new CsvReader(new DateParser());
    $records = $reader->read($argv[1]);

    $analyzer = new TeamAnalyzer(new OverlapCalculator());
    $result = $analyzer->analyze($records);

    if ($result === null) {
        echo 'No pair found.' . PHP_EOL;
        exit(0);
    }

    echo 'Employees: '
        . $result['employeeId1'] . ', '
        . $result['employeeId2'] . PHP_EOL;

    echo 'Total days: ' . $result['totalDays'] . PHP_EOL;

    foreach ($result['projects'] as $projectId => $days) {
        echo "Project {$projectId}: {$days} days" . PHP_EOL;
    }
} catch (Exception $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}