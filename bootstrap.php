<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Sofia');

require_once __DIR__ . '/src/Model/WorkRecord.php';

require_once __DIR__ . '/src/Input/DateParser.php';
require_once __DIR__ . '/src/Input/CsvReader.php';

require_once __DIR__ . '/src/Service/OverlapCalculator.php';
require_once __DIR__ . '/src/Service/TeamAnalyzer.php';

require_once __DIR__ . '/src/Controller/UploadController.php';