<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$passed = 0;
$failed = 0;

function checkSame(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            'Expected: ' . var_export($expected, true)
            . '; actual: ' . var_export($actual, true),
        );
    }
}

function expectInvalid(callable $action): void
{
    try {
        $action();
    } catch (InvalidArgumentException) {
        return;
    }

    throw new RuntimeException('Expected invalid data to be rejected.');
}

function record(
    int $employee,
    int $project,
    string $from,
    string $to,
): WorkRecord {
    return new WorkRecord(
        $employee,
        $project,
        new DateTimeImmutable($from),
        new DateTimeImmutable($to),
    );
}

function readCsv(string $content): array
{
    $filename = tempnam(sys_get_temp_dir(), 'employees-test-');

    if ($filename === false) {
        throw new RuntimeException('Cannot create temporary file.');
    }

    try {
        if (file_put_contents($filename, $content) === false) {
            throw new RuntimeException('Cannot write temporary file.');
        }

        return (new CsvReader(new DateParser()))->read($filename);
    } finally {
        unlink($filename);
    }
}

function test(string $name, callable $action): void
{
    global $passed, $failed;

    try {
        $action();
        $passed++;
        echo "PASS: {$name}" . PHP_EOL;
    } catch (Throwable $exception) {
        $failed++;
        echo "FAIL: {$name} — {$exception->getMessage()}" . PHP_EOL;
    }
}

$calculator = new OverlapCalculator();
$analyzer = new TeamAnalyzer($calculator);

test('One shared boundary day counts as one', function () use ($calculator) {
    $days = $calculator->calculateDays(
        record(143, 10, '2026-01-01', '2026-01-05'),
        record(218, 10, '2026-01-05', '2026-01-09'),
    );

    checkSame(1, $days);
});

test('No overlap returns zero', function () use ($calculator) {
    $days = $calculator->calculateDays(
        record(143, 10, '2026-01-01', '2026-01-05'),
        record(218, 10, '2026-01-06', '2026-01-09'),
    );

    checkSame(0, $days);
});

test('Different projects do not count', function () use ($calculator) {
    $days = $calculator->calculateDays(
        record(143, 10, '2026-01-01', '2026-01-05'),
        record(218, 20, '2026-01-01', '2026-01-05'),
    );

    checkSame(0, $days);
});

test('Days accumulate across projects and separate periods', function () use ($analyzer) {
    $result = $analyzer->analyze([
        record(143, 10, '2026-01-01', '2026-01-05'),
        record(218, 10, '2026-01-03', '2026-01-09'),
        record(300, 10, '2026-01-06', '2026-01-08'),
        record(143, 20, '2026-02-01', '2026-02-05'),
        record(143, 20, '2026-02-10', '2026-02-15'),
        record(218, 20, '2026-02-03', '2026-02-12'),
    ]);

    checkSame([
        'employeeId1' => 143,
        'employeeId2' => 218,
        'totalDays' => 9,
        'projects' => [10 => 3, 20 => 6],
    ], $result);
});

test('No winning pair when periods do not overlap', function () use ($analyzer) {
    checkSame(null, $analyzer->analyze([
        record(143, 10, '2026-01-01', '2026-01-05'),
        record(218, 10, '2026-01-06', '2026-01-09'),
    ]));
});

test('A single employee cannot form a pair', function () use ($analyzer) {
    checkSame(null, $analyzer->analyze([
        record(143, 10, '2026-01-01', '2026-01-05'),
        record(143, 10, '2026-01-10', '2026-01-15'),
    ]));
});

test('Ties use employee IDs regardless of input order', function () use ($analyzer) {
    $records = [
        record(300, 20, '2026-01-01', '2026-01-05'),
        record(150, 20, '2026-01-01', '2026-01-05'),
        record(218, 10, '2026-01-01', '2026-01-05'),
        record(143, 10, '2026-01-01', '2026-01-05'),
    ];

    $result = $analyzer->analyze($records);

    checkSame(143, $result['employeeId1']);
    checkSame(218, $result['employeeId2']);
    checkSame($result, $analyzer->analyze(array_reverse($records)));
});

test('Overlapping periods for the same employee are rejected', function () use ($analyzer) {
    expectInvalid(fn() => $analyzer->analyze([
        record(143, 10, '2026-01-01', '2026-01-10'),
        record(143, 10, '2026-01-05', '2026-01-15'),
    ]));
});

test('Supported date formats normalize consistently', function () {
    $parser = new DateParser();

    foreach ([
        '2026-10-03',
        '03/10/2026',
        '3/10/2026',
        '03.10.2026',
        '3-10-2026',
        '2026/10/03',
        '3 Oct 2026',
        '3 October 2026',
        'October 3, 2026',
    ] as $value) {
        checkSame('2026-10-03', $parser->parse($value)->format('Y-m-d'));
    }
});

test('Impossible dates are rejected and leap dates accepted', function () {
    $parser = new DateParser();

    expectInvalid(fn() => $parser->parse('2026-02-30'));
    expectInvalid(fn() => $parser->parse('2025-02-29'));

    checkSame('2024-02-29', $parser->parse('2024-02-29')->format('Y-m-d'));
});

test('CSV supports BOM, header, blank lines and NULL', function () {
    $before = new DateTimeImmutable('today');

    $records = readCsv(
        "\xEF\xBB\xBFEmpID,ProjectID,DateFrom,DateTo\n"
        . "\n"
        . "143,10,2020-01-01,NULL\n"
        . "218,10,03/01/2020,05/01/2020\n",
    );

    $after = new DateTimeImmutable('today');

    checkSame(2, count($records));
    checkSame('2020-01-03', $records[1]->dateFrom->format('Y-m-d'));

    if ($records[0]->dateTo < $before || $records[0]->dateTo > $after) {
        throw new RuntimeException('NULL was not converted to today.');
    }
});

test('CSV without a header is accepted', function () {
    $records = readCsv("143,10,2026-01-01,2026-01-05\n");

    checkSame(1, count($records));
    checkSame(143, $records[0]->employeeId);
});

test('Invalid CSV values are rejected', function () {
    foreach ([
        "abc,10,2026-01-01,2026-01-05\n",
        "143,10,2026-01-01\n",
        "143,10,2026-01-10,2026-01-05\n",
        "143,10,,2026-01-05\n",
        "143,10,2026-01-01,\n",
    ] as $content) {
        expectInvalid(fn() => readCsv($content));
    }
});

test('Errors from multiple CSV rows are collected', function () {
    try {
        readCsv(
            "abc,10,2026-01-01,2026-01-05\n"
            . "143,10,2026-02-30,2026-03-05\n",
        );
    } catch (InvalidArgumentException $exception) {
        $message = $exception->getMessage();

        if (!str_contains($message, 'Row 1:') ||
            !str_contains($message, 'Row 2:')) {
            throw new RuntimeException('Not all row errors were reported.');
        }

        return;
    }

    throw new RuntimeException('Expected CSV errors.');
});

test('Empty CSV is rejected', function () {
    expectInvalid(fn() => readCsv(''));
});

echo PHP_EOL . "Passed: {$passed}; Failed: {$failed}" . PHP_EOL;

exit($failed === 0 ? 0 : 1);