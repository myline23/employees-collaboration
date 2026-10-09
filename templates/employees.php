<?php

declare(strict_types=1);

$escape = static fn(string $value): string => htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8',
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employees — Longest Collaboration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f5f8;
            color: #172033;
            margin: 0;
            padding: 40px 20px;
        }

        main {
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
        }

        form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            margin: 24px 0;
        }

        button {
            background: #2457d6;
            color: white;
            border: 0;
            border-radius: 6px;
            padding: 12px 20px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #dde2eb;
        }

        th {
            background: #edf2fc;
        }

        .error {
            background: #fff0f0;
            color: #9b2020;
            padding: 16px;
            white-space: pre-wrap;
        }

        .table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body>
<main>
    <h1>Longest Collaboration</h1>
    <p>Find the employee pair with the most shared days across projects.</p>

    <form method="post" enctype="multipart/form-data" action="/">
        <label for="csv">CSV file</label>
        <input
            id="csv"
            name="csv"
            type="file"
            accept=".csv"
            required
        >
        <button type="submit">Analyze</button>
    </form>

    <p>Columns: EmpID, ProjectID, DateFrom, DateTo.</p>
    <p>End date NULL means today. Both boundary dates are included.</p>

    <?php if ($error !== null): ?>
        <div class="error" role="alert"><?= $escape($error) ?></div>

    <?php elseif ($submitted && $result === null): ?>
        <p>No employees worked together during the supplied periods.</p>

    <?php elseif ($result !== null): ?>
        <h2>
            Employees <?= $escape((string) $result['employeeId1']) ?>
            &amp; <?= $escape((string) $result['employeeId2']) ?>
        </h2>

        <p>
            Total shared project days:
            <strong><?= $escape((string) $result['totalDays']) ?></strong>
        </p>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th scope="col">Employee ID #1</th>
                    <th scope="col">Employee ID #2</th>
                    <th scope="col">Project ID</th>
                    <th scope="col">Days worked</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($result['projects'] as $projectId => $days): ?>
                    <tr>
                        <td><?= $escape((string) $result['employeeId1']) ?></td>
                        <td><?= $escape((string) $result['employeeId2']) ?></td>
                        <td><?= $escape((string) $projectId) ?></td>
                        <td><?= $escape((string) $days) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>