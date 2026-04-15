<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LstmBatchRun;

final class LstmExportService
{
    public static function batchSummary(int $batchId): array
    {
        $batch = LstmBatchRun::find($batchId);

        if ($batch === null) {
            return [];
        }

        return [[
            'kode_batch' => $batch['batch_code'],
            'status' => $batch['status'],
            'total_komoditas' => $batch['total_komoditas'],
            'komoditas_selesai' => $batch['completed_komoditas'],
            'komoditas_gagal' => $batch['failed_komoditas'],
            'sequence_length' => $batch['sequence_length'],
            'train_ratio' => $batch['train_ratio'],
            'epochs' => $batch['epochs'],
            'batch_size' => $batch['batch_size'],
            'lstm_units' => $batch['lstm_units'],
            'dropout_rate' => $batch['dropout_rate'],
            'optimizer' => $batch['optimizer'],
            'learning_rate' => $batch['learning_rate'],
            'notes' => $batch['notes'],
            'train_started_at' => $batch['train_started_at'],
            'train_finished_at' => $batch['train_finished_at'],
            'duration_seconds' => $batch['duration_seconds'],
            'created_at' => $batch['created_at'],
        ]];
    }

    public static function commodityRecap(int $batchId): array
    {
        return LstmBatchRun::evaluationRecap($batchId);
    }

    public static function batchComplete(int $batchId): array
    {
        $summaryRows = self::batchSummary($batchId);
        $summary = $summaryRows[0] ?? [];
        $recap = self::commodityRecap($batchId);
        $runs = LstmBatchRun::commodityRuns($batchId, '', 1, 1000)['items'];

        $rows = [];
        foreach ($runs as $run) {
            $runId = (int) $run['id'];
            $predictionCount = LstmBatchRun::predictionsPaginate($runId, '', 1, 1)['totalItems'];
            $residualCount = LstmBatchRun::residualsPaginate($runId, '', 1, 1)['totalItems'];
            $forecastCount = LstmBatchRun::forecastsPaginate($runId, '', 1, 1)['totalItems'];
            $rows[] = [
                'kode_batch' => $summary['kode_batch'] ?? '',
                'komoditas' => $run['komoditas'],
                'status' => $run['status'],
                'train_samples' => $run['train_samples'],
                'test_samples' => $run['test_samples'],
                'rmse' => $run['rmse'],
                'mae' => $run['mae'],
                'mape' => $run['mape'],
                'train_loss_final' => $run['train_loss_final'],
                'val_loss_final' => $run['val_loss_final'],
                'prediction_rows' => $predictionCount,
                'residual_rows' => $residualCount,
                'forecast_rows' => $forecastCount,
                'duration_seconds' => $run['duration_seconds'],
                'model_path' => $run['model_path'],
            ];
        }

        return $rows !== [] ? $rows : $recap;
    }

    public static function predictionRows(int $runId): array
    {
        return LstmBatchRun::predictionRows($runId);
    }

    public static function residualRows(int $runId): array
    {
        return LstmBatchRun::residualRows($runId);
    }

    public static function forecastRows(int $runId): array
    {
        return LstmBatchRun::forecastRows($runId);
    }
}
