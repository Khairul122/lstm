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
}
