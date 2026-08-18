<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DataPreprocessingLstm;
use App\Models\Komoditas;
use App\Models\LstmBatchRun;
use App\Models\StokHistoris;
use Core\Controller;
use Core\Session;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $komoditasTotal = Komoditas::totalCount();
        $stokSummary = StokHistoris::dashboardSummary();
        $preprocessingSummary = DataPreprocessingLstm::dashboardSummary();
        $latestBatch = LstmBatchRun::latest();
        $bestRun = $latestBatch !== null ? LstmBatchRun::bestRunForBatch((int) $latestBatch['id']) : null;

        $this->view('pages.dashboard.index', [
            'title' => 'Dashboard',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'dashboard',
            'komoditasTotal' => $komoditasTotal,
            'stokSummary' => $stokSummary,
            'preprocessingSummary' => $preprocessingSummary,
            'latestBatch' => $latestBatch,
            'bestRun' => $bestRun,
        ]);
    }

    public function saveScreenshot(): void
    {
        $name = (string) ($_POST['name'] ?? '');
        $imgData = (string) ($_POST['image'] ?? '');
        
        if ($name === '' || $imgData === '') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            $name = (string) ($data['name'] ?? '');
            $imgData = (string) ($data['image'] ?? '');
        }
        
        if ($name !== '' && $imgData !== '') {
            $name = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $name);
            $dir = __DIR__ . '/../../screenshoot';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            if (str_starts_with($imgData, 'data:image')) {
                $parts = explode(',', $imgData);
                $imgData = $parts[1] ?? '';
            }
            $decoded = base64_decode($imgData);
            file_put_contents($dir . '/' . $name, $decoded);
            echo "SAVED_SUCCESS_" . e($name);
            exit;
        }
        echo "ERROR_MISSING_DATA";
        exit;
    }
}
