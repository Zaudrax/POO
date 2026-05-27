<?php

declare(strict_types=1);

class CsvExporter implements ExporterInterface
{
    public function export(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $headers = [];

        foreach ($items as $item) {
            foreach (array_keys($item) as $key) {
                if (!in_array($key, $headers, true)) {
                    $headers[] = $key;
                }
            }
        }

        $lines = [];
        $lines[] = $this->buildCsvLine($headers);

        foreach ($items as $item) {
            $row = [];

            foreach ($headers as $header) {
                $row[] = array_key_exists($header, $item) ? $this->normalizeValue($item[$header]) : '';
            }

            $lines[] = $this->buildCsvLine($row);
        }

        return implode(PHP_EOL, $lines);
    }

    private function buildCsvLine(array $values): string
    {
        $escapedValues = [];

        foreach ($values as $value) {
            $escapedValues[] = '"' . str_replace('"', '""', (string) $value) . '"';
        }

        return implode(';', $escapedValues);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value;
    }
}
