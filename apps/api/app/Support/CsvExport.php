<?php

namespace App\Support;

use Generator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plain fputcsv streaming — no maatwebsite/excel or league/csv dependency
 * needed for a flat one-sheet export. Streaming (not building the whole
 * string in memory) keeps this safe for large tables.
 */
class CsvExport
{
    /**
     * @param  string[]  $headers
     * @param  Generator<array<int, string|int|null>>  $rows
     */
    public static function stream(string $filename, array $headers, Generator $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel (the realistic destination for this file)
            // renders Arabic/French text correctly instead of mojibake.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }
}
