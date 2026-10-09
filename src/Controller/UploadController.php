<?php

declare(strict_types=1);

final class UploadController
{
    public function __construct(
        private readonly CsvReader $reader,
        private readonly TeamAnalyzer $analyzer,
    ) {
    }

    public function handle(array $files): ?array
    {
        $file = $files['csv'] ?? null;

        if (!is_array($file)) {
            throw new InvalidArgumentException('Please select a CSV file.');
        }

        $error = $file['error'] ?? null;

        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(
                'File upload failed. Please select a file within the upload limit.',
            );
        }

        $filename = $file['tmp_name'] ?? null;

        if (!is_string($filename) || !is_uploaded_file($filename)) {
            throw new InvalidArgumentException('Invalid uploaded file.');
        }

        $records = $this->reader->read($filename);

        return $this->analyzer->analyze($records);
    }
}