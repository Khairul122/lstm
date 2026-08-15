<?php

declare(strict_types=1);

namespace App\Services;

final class ExportResponse
{
    public static function downloadCsv(string $filename, array $rows): void
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");

        if ($rows !== []) {
            fputcsv($output, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($output, array_values($row));
            }
        }

        fclose($output);
        exit;
    }

    public static function csvContent(array $rows): string
    {
        $output = fopen('php://memory', 'wb');
        fwrite($output, "\xEF\xBB\xBF");

        if ($rows !== []) {
            fputcsv($output, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($output, array_values($row));
            }
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * @param array<int, array{filename: string, rows: array}> $files
     */
    public static function downloadCsvZip(string $zipFilename, array $files): void
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        if (\class_exists(\ZipArchive::class)) {
            $tempPath = tempnam(sys_get_temp_dir(), 'lstm_export_');
            if ($tempPath !== false) {
                $zip = new \ZipArchive();
                if ($zip->open($tempPath, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) === true) {
                    foreach ($files as $file) {
                        $zip->addFromString($file['filename'], self::csvContent($file['rows']));
                    }
                    $zip->close();

                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
                    header('Content-Length: ' . (string) filesize($tempPath));

                    readfile($tempPath);
                    @unlink($tempPath);
                    exit;
                }
            }
        }

        // Fallback: Pure PHP ZIP generation (works on any PHP server without ext-zip)
        $formattedFiles = [];
        foreach ($files as $file) {
            $formattedFiles[] = [
                'filename' => $file['filename'],
                'content'  => self::csvContent($file['rows']),
            ];
        }

        $zipData = self::buildPurePhpZip($formattedFiles);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . (string) strlen($zipData));

        echo $zipData;
        exit;
    }

    /**
     * Build a zip file binary in pure PHP without relying on ext-zip / ZipArchive.
     *
     * @param array<int, array{filename: string, content: string}> $files
     */
    private static function buildPurePhpZip(array $files): string
    {
        $zipData = '';
        $cdRecords = '';
        $offset = 0;

        $d = getdate();
        $dosTime = ($d['hours'] << 11) | ($d['minutes'] << 5) | (int) ($d['seconds'] / 2);
        $dosDate = (($d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday'];

        foreach ($files as $file) {
            $filename = $file['filename'];
            $content = $file['content'];
            $uncompressedSize = strlen($content);
            $crc32 = crc32($content);

            if (\function_exists('gzdeflate')) {
                $compressedData = gzdeflate($content);
                $compressionMethod = 8;
            } else {
                $compressedData = $content;
                $compressionMethod = 0;
            }
            $compressedSize = strlen($compressedData);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                $compressionMethod,
                $dosTime,
                $dosDate,
                $crc32,
                $compressedSize,
                $uncompressedSize,
                strlen($filename),
                0
            ) . $filename;

            $zipData .= $localHeader . $compressedData;

            $cdRecords .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                $compressionMethod,
                $dosTime,
                $dosDate,
                $crc32,
                $compressedSize,
                $uncompressedSize,
                strlen($filename),
                0,
                0,
                0,
                0,
                32,
                $offset
            ) . $filename;

            $offset += strlen($localHeader) + $compressedSize;
        }

        $cdSize = strlen($cdRecords);
        $cdOffset = $offset;

        $eocd = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            count($files),
            count($files),
            $cdSize,
            $cdOffset,
            0
        );

        return $zipData . $cdRecords . $eocd;
    }

    public static function downloadExcel(string $filename, array $rows): void
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "\xEF\xBB\xBF";
        echo '<table border="1">';

        if ($rows !== []) {
            echo '<thead><tr>';
            foreach (array_keys($rows[0]) as $header) {
                echo '<th>' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $value) {
                    echo '<td>' . htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>';
                }
                echo '</tr>';
            }

            echo '</tbody>';
        }

        echo '</table>';
        exit;
    }

    public static function downloadPdf(string $filename, string $title, array $rows): void
    {
        if (!class_exists(\TCPDF::class)) {
            http_response_code(500);
            exit('Gagal membuat PDF: Library TCPDF belum terinstall. Silakan jalankan "composer install" di folder proyek.');
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $totalRows = count($rows);
        $maxPdfRows = 1000;
        $sliced = false;

        if ($totalRows > $maxPdfRows) {
            $rows = array_slice($rows, 0, $maxPdfRows);
            $sliced = true;
        }

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('OpenCode');
        $pdf->SetAuthor('OpenCode');
        $pdf->SetTitle($title);
        $pdf->SetMargins(8, 10, 8);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage('L', 'A4');
        $pdf->SetFont('helvetica', '', 8);

        $html = '<h2 style="margin-bottom:4px;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';

        if ($sliced) {
            $html .= '<p style="color:#666;font-style:italic;margin-top:0;">Menampilkan ' . number_format($maxPdfRows) . ' baris terbaru dari total ' . number_format($totalRows) . ' baris. Untuk seluruh data tanpa batasan, silakan gunakan format Excel atau CSV.</p>';
        }

        if ($rows === []) {
            $html .= '<p>Tidak ada data untuk diekspor.</p>';
        } else {
            $html .= '<table border="1" cellpadding="3" style="border-collapse:collapse;width:100%;">';
            $html .= '<thead><tr style="background-color:#eef2ff;font-weight:bold;">';
            foreach (array_keys($rows[0]) as $header) {
                $html .= '<th>' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filename, 'D');
        exit;
    }
}
