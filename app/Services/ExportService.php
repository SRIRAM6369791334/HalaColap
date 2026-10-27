<?php

namespace App\Services;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Stream a CSV download response.
     *
     * @param  string   $filename  Filename without extension
     * @param  array    $headers   Column header labels
     * @param  iterable $rows      Each row is an array of scalar values
     */
    public function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn($v) => is_null($v) ? '' : $v, $row));
            }
            fclose($out);
        }, $filename . '.csv', [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ]);
    }
}
