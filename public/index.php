<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$result = null;
$error = null;
$submitted = false;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $submitted = true;

    try {
        $controller = new UploadController(
            new CsvReader(new DateParser()),
            new TeamAnalyzer(new OverlapCalculator()),
        );

        $result = $controller->handle($_FILES);
    } catch (InvalidArgumentException $exception) {
        http_response_code(400);
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        http_response_code(500);
        error_log((string) $exception);
        $error = 'Unable to process the file. Please try again.';
    }
} elseif ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Method not allowed.');
}

require __DIR__ . '/../templates/employees.php';