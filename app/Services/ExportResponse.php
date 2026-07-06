<?php

declare(strict_types=1);

namespace App\Services;

final class ExportResponse
{
    public static function downloadCsv(string $filename, array $rows): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'wb');
        fwrite($output, self::csvContent($rows));
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
        $tempPath = tempnam(sys_get_temp_dir(), 'lstm_export_');
        if ($tempPath === false) {
            http_response_code(500);
            exit('Gagal membuat file sementara untuk export.');
        }

        $zip = new \ZipArchive();
        $zip->open($tempPath, \ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            $zip->addFromString($file['filename'], self::csvContent($file['rows']));
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . (string) filesize($tempPath));

        readfile($tempPath);
        unlink($tempPath);
        exit;
    }

    public static function downloadExcel(string $filename, array $rows): void
    {
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
                    echo '<td>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</td>';
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
        $pdf = new \TCPDF();
        $pdf->SetCreator('OpenCode');
        $pdf->SetAuthor('OpenCode');
        $pdf->SetTitle($title);
        $pdf->SetMargins(10, 12, 10);
        $pdf->AddPage('L', 'A4');
        $pdf->SetFont('helvetica', '', 9);

        $html = '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';

        if ($rows === []) {
            $html .= '<p>Tidak ada data untuk diekspor.</p>';
        } else {
            $html .= '<table border="1" cellpadding="4">';
            $html .= '<thead><tr style="background-color:#eef2ff;">';
            foreach (array_keys($rows[0]) as $header) {
                $html .= '<th><b>' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</b></th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</td>';
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
