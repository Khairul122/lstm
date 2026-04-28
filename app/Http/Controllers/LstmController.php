<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LstmBatchRun;
use App\Services\ExportResponse;
use App\Services\LstmExportService;
use Core\Controller;
use Core\Session;

final class LstmController extends Controller
{
    public function index(): void
    {
        LstmBatchRun::ensureTables();
        $latestBatch = LstmBatchRun::latest();
        $bestRun = $latestBatch !== null ? LstmBatchRun::bestRunForBatch((int) $latestBatch['id']) : null;
        $stats = LstmBatchRun::summaryStats();

        $this->view('pages.lstm.index', [
            'title' => 'LSTM',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'lstm',
            'form' => $this->defaultForm(),
            'latestBatch' => $latestBatch,
            'bestRun' => $bestRun,
            'stats' => $stats,
            'flashPopup' => Session::pull('flash_popup'),
        ]);
    }

    public function evaluationIndex(): void
    {
        LstmBatchRun::ensureTables();
        $search = trim((string) ($_GET['search'] ?? ''));
        $page = (int) ($_GET['page'] ?? 1);
        $result = LstmBatchRun::paginate($search, $page, 10);
        $latestBatch = LstmBatchRun::latest();
        $bestRun = $latestBatch !== null ? LstmBatchRun::bestRunForBatch((int) $latestBatch['id']) : null;
        $stats = LstmBatchRun::summaryStats();
        $overallRecap = $latestBatch !== null ? LstmBatchRun::evaluationRecap((int) $latestBatch['id']) : [];

        $this->view('pages.lstm.evaluasi', [
            'title' => 'Evaluasi',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'evaluasi',
            'items' => $result['items'],
            'search' => $result['search'],
            'currentPage' => $result['currentPage'],
            'totalPages' => $result['totalPages'],
            'totalItems' => $result['totalItems'],
            'perPage' => $result['perPage'],
            'latestBatch' => $latestBatch,
            'bestRun' => $bestRun,
            'stats' => $stats,
            'overallRecap' => $overallRecap,
            'flashPopup' => Session::pull('flash_popup'),
        ]);
    }

    public function train(): void
    {
        LstmBatchRun::ensureTables();
        $form = $this->sanitize($_POST);
        $error = $this->validate($form);

        if ($error !== null) {
            Session::flash('flash_popup', [
                'type' => 'error',
                'title' => 'Training Gagal',
                'message' => $error,
            ]);
            $this->redirect('/lstm');
        }

        $batchCode = 'BATCH-' . date('Ymd-His');
        $batchId = LstmBatchRun::createBatch([
            'batch_code' => $batchCode,
            'status' => 'queued',
            'sequence_length' => (int) $form['sequence_length'],
            'train_ratio' => (float) $form['train_ratio'],
            'epochs' => (int) $form['epochs'],
            'batch_size' => (int) $form['batch_size'],
            'lstm_units' => (int) $form['lstm_units'],
            'dropout_rate' => (float) $form['dropout_rate'],
            'optimizer' => $form['optimizer'],
            'learning_rate' => (float) $form['learning_rate'],
            'notes' => 'Batch training semua komoditas dimulai dari panel aplikasi.',
        ]);

        $scriptPath = realpath('database/train_lstm_batch.py');
        $command = sprintf('start /B python "%s" %d > NUL 2>&1', $scriptPath, $batchId);
        pclose(popen($command, "r"));

        Session::flash('flash_popup', [
            'type' => 'success',
            'title' => 'Training Dimulai',
            'message' => 'Proses batch training sedang berjalan di latar belakang. Silakan periksa halaman ini secara berkala.',
            'redirect' => base_url('/lstm/batch/' . $batchId),
        ]);

        $this->redirect('/lstm/batch/' . $batchId);
    }

    public function resetAll(): void
    {
        LstmBatchRun::resetAll();
        Session::flash('flash_popup', [
            'type' => 'success',
            'title' => 'Reset Berhasil',
            'message' => 'Semua batch, metrik, prediksi, residual, forecast, dan file model LSTM berhasil dihapus.',
        ]);
        $this->redirect('/lstm');
    }

    public function batch(string $id): void
    {
        LstmBatchRun::ensureTables();
        $batchId = (int) $id;
        $batch = LstmBatchRun::find($batchId);

        if ($batch === null) {
            Session::flash('flash_popup', [
                'type' => 'error',
                'title' => 'Batch Tidak Ditemukan',
                'message' => 'Batch LSTM yang diminta tidak tersedia.',
            ]);
            $this->redirect('/evaluasi');
        }

        $search = trim((string) ($_GET['search'] ?? ''));
        $page = (int) ($_GET['page'] ?? 1);
        $runs = LstmBatchRun::commodityRuns($batchId, $search, $page, 10);
        $recap = LstmBatchRun::evaluationRecap($batchId);
        $bestRun = LstmBatchRun::bestRunForBatch($batchId);

        $this->view('pages.lstm.batch', [
            'title' => 'Detail Batch LSTM',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'evaluasi',
            'batch' => $batch,
            'items' => $runs['items'],
            'search' => $runs['search'],
            'currentPage' => $runs['currentPage'],
            'totalPages' => $runs['totalPages'],
            'totalItems' => $runs['totalItems'],
            'perPage' => $runs['perPage'],
            'recap' => $recap,
            'bestRun' => $bestRun,
            'flashPopup' => Session::pull('flash_popup'),
        ]);
    }

    public function run(string $id): void
    {
        LstmBatchRun::ensureTables();
        $runId = (int) $id;
        $run = LstmBatchRun::findRun($runId);

        if ($run === null) {
            Session::flash('flash_popup', [
                'type' => 'error',
                'title' => 'Run Tidak Ditemukan',
                'message' => 'Detail run LSTM tidak tersedia.',
            ]);
            $this->redirect('/evaluasi');
        }

        $predictionSearch = trim((string) ($_GET['prediction_search'] ?? ''));
        $predictionPage = (int) ($_GET['prediction_page'] ?? 1);
        $residualSearch = trim((string) ($_GET['residual_search'] ?? ''));
        $residualPage = (int) ($_GET['residual_page'] ?? 1);
        $forecastSearch = trim((string) ($_GET['forecast_search'] ?? ''));
        $forecastPage = (int) ($_GET['forecast_page'] ?? 1);

        $predictions = LstmBatchRun::predictionsPaginate($runId, $predictionSearch, $predictionPage, 15);
        $residuals = LstmBatchRun::residualsPaginate($runId, $residualSearch, $residualPage, 15);
        $forecasts = LstmBatchRun::forecastsPaginate($runId, $forecastSearch, $forecastPage, 15);
        $predictionSeries = LstmBatchRun::predictionSeries($runId);
        $residualSeries = LstmBatchRun::residualSeries($runId);
        $forecastSeries = LstmBatchRun::forecastSeries($runId);
        $historicalSeries = LstmBatchRun::historicalOverview((string) $run['komoditas'], 365);

        $this->view('pages.lstm.run', [
            'title' => 'Detail Run LSTM',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'evaluasi',
            'run' => $run,
            'predictionResult' => $predictions,
            'residualResult' => $residuals,
            'forecastResult' => $forecasts,
            'predictionSeries' => $predictionSeries,
            'residualSeries' => $residualSeries,
            'forecastSeries' => $forecastSeries,
            'historicalSeries' => $historicalSeries,
            'flashPopup' => Session::pull('flash_popup'),
        ]);
    }

    public function deleteBatch(string $id): void
    {
        LstmBatchRun::ensureTables();
        $batchId = (int) $id;
        $batch = LstmBatchRun::find($batchId);

        if ($batch === null) {
            Session::flash('flash_popup', [
                'type' => 'error',
                'title' => 'Batch Tidak Ditemukan',
                'message' => 'Batch LSTM yang ingin dihapus tidak tersedia.',
            ]);
            $this->redirect('/evaluasi');
        }

        if (($batch['status'] ?? '') === 'running') {
            Session::flash('flash_popup', [
                'type' => 'error',
                'title' => 'Batch Sedang Berjalan',
                'message' => 'Tidak dapat menghapus batch yang sedang berjalan.',
            ]);
            $this->redirect('/evaluasi');
        }

        try {
            LstmBatchRun::deleteBatch($batchId);
            Session::flash('flash_popup', [
                'type' => 'success',
                'title' => 'Hapus Berhasil',
                'message' => 'Batch dan data terkait berhasil dihapus.',
            ]);
        } catch (\Throwable $exception) {
            Session::flash('flash_popup', [
                'type' => 'error',
                'title' => 'Hapus Gagal',
                'message' => $exception->getMessage(),
            ]);
        }

        $this->redirect('/evaluasi');
    }

    public function exportBatchSummary(string $id, string $format): void
    {
        $batchId = (int) $id;
        $rows = LstmExportService::batchSummary($batchId);
        $batch = LstmBatchRun::find($batchId);
        $this->exportRows($rows, $format, 'batch-summary-' . ($batch['batch_code'] ?? $batchId), 'Rekap Batch LSTM');
    }

    public function exportBatchComplete(string $id, string $format): void
    {
        $batchId = (int) $id;
        $rows = LstmExportService::batchComplete($batchId);
        $batch = LstmBatchRun::find($batchId);
        $this->exportRows($rows, $format, 'batch-lengkap-' . ($batch['batch_code'] ?? $batchId), 'Batch Lengkap LSTM');
    }

    public function exportCommodityRecap(string $id, string $format): void
    {
        $batchId = (int) $id;
        $rows = LstmExportService::commodityRecap($batchId);
        $batch = LstmBatchRun::find($batchId);
        $this->exportRows($rows, $format, 'rekap-komoditas-' . ($batch['batch_code'] ?? $batchId), 'Rekap Komoditas LSTM');
    }

    public function exportPredictions(string $id, string $format): void
    {
        $runId = (int) $id;
        $run = LstmBatchRun::findRun($runId);
        $rows = LstmExportService::predictionRows($runId);
        $this->exportRows($rows, $format, 'prediksi-' . ($run['komoditas'] ?? $runId), 'Prediksi Data Uji');
    }

    public function exportResiduals(string $id, string $format): void
    {
        $runId = (int) $id;
        $run = LstmBatchRun::findRun($runId);
        $rows = LstmExportService::residualRows($runId);
        $this->exportRows($rows, $format, 'residual-' . ($run['komoditas'] ?? $runId), 'Residual Model');
    }

    public function exportForecasts(string $id, string $format): void
    {
        $runId = (int) $id;
        $run = LstmBatchRun::findRun($runId);
        $rows = LstmExportService::forecastRows($runId);
        $this->exportRows($rows, $format, 'forecast-' . ($run['komoditas'] ?? $runId), 'Forecast 1 Tahun');
    }

    private function sanitize(array $source): array
    {
        return [
            'sequence_length' => trim((string) ($source['sequence_length'] ?? '7')),
            'train_ratio' => trim((string) ($source['train_ratio'] ?? '0.8')),
            'epochs' => trim((string) ($source['epochs'] ?? '30')),
            'batch_size' => trim((string) ($source['batch_size'] ?? '16')),
            'lstm_units' => trim((string) ($source['lstm_units'] ?? '64')),
            'dropout_rate' => trim((string) ($source['dropout_rate'] ?? '0.2')),
            'optimizer' => trim((string) ($source['optimizer'] ?? 'adam')),
            'learning_rate' => trim((string) ($source['learning_rate'] ?? '0.001')),
        ];
    }

    private function validate(array $form): ?string
    {
        foreach (['sequence_length', 'epochs', 'batch_size', 'lstm_units'] as $field) {
            if (!ctype_digit($form[$field])) {
                return 'Parameter utama model harus berupa angka bulat.';
            }
        }

        if (!is_numeric($form['train_ratio']) || !is_numeric($form['dropout_rate']) || !is_numeric($form['learning_rate'])) {
            return 'Rasio data, dropout, dan learning rate harus berupa angka.';
        }

        if ((int) $form['sequence_length'] < 1 || (int) $form['sequence_length'] > 60) {
            return 'Sequence length harus berada pada rentang 1 sampai 60.';
        }

        if ((float) $form['train_ratio'] < 0.5 || (float) $form['train_ratio'] > 0.95) {
            return 'Train ratio harus berada pada rentang 0.50 sampai 0.95.';
        }

        if ((int) $form['epochs'] < 1 || (int) $form['epochs'] > 300) {
            return 'Epoch harus berada pada rentang 1 sampai 300.';
        }

        if ((int) $form['batch_size'] < 1 || (int) $form['batch_size'] > 256) {
            return 'Batch size harus berada pada rentang 1 sampai 256.';
        }

        if ((int) $form['lstm_units'] < 4 || (int) $form['lstm_units'] > 256) {
            return 'Jumlah unit LSTM harus berada pada rentang 4 sampai 256.';
        }

        if ((float) $form['dropout_rate'] < 0 || (float) $form['dropout_rate'] > 0.8) {
            return 'Dropout rate harus berada pada rentang 0.0 sampai 0.8.';
        }

        if ((float) $form['learning_rate'] <= 0 || (float) $form['learning_rate'] > 1) {
            return 'Learning rate harus lebih dari 0 dan tidak lebih dari 1.';
        }

        if (!in_array($form['optimizer'], ['adam', 'rmsprop'], true)) {
            return 'Optimizer yang dipilih tidak didukung.';
        }

        return null;
    }

    private function defaultForm(): array
    {
        return [
            'sequence_length' => '7',
            'train_ratio' => '0.8',
            'epochs' => '30',
            'batch_size' => '16',
            'lstm_units' => '64',
            'dropout_rate' => '0.2',
            'optimizer' => 'adam',
            'learning_rate' => '0.001',
        ];
    }

    private function exportRows(array $rows, string $format, string $baseFilename, string $title): void
    {
        $safeFilename = preg_replace('/[^A-Za-z0-9\-_]+/', '-', strtolower($baseFilename)) ?: 'export';

        if ($format === 'csv') {
            ExportResponse::downloadCsv($safeFilename . '.csv', $rows);
        }

        if ($format === 'excel') {
            ExportResponse::downloadExcel($safeFilename . '.xls', $rows);
        }

        if ($format === 'pdf') {
            ExportResponse::downloadPdf($safeFilename . '.pdf', $title, $rows);
        }

        Session::flash('flash_popup', [
            'type' => 'error',
            'title' => 'Format Tidak Didukung',
            'message' => 'Format export yang diminta tidak tersedia.',
        ]);
        $this->redirectBack();
    }
}
