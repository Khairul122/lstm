<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\LstmBatchRun;
use App\Models\StokHistoris;
use Core\Controller;
use Core\Session;

final class HomeController extends Controller
{
    public function index(): void
    {
        $forecastSummary = LstmBatchRun::landingForecastSummary();
        $forecastTable = LstmBatchRun::landingForecastPaginate(
            (string) ($_GET['search'] ?? ''),
            (string) ($_GET['commodity'] ?? ''),
            (string) ($_GET['status'] ?? ''),
            max(1, (int) ($_GET['page'] ?? 1)),
            10
        );

        $this->view('pages.home.index', [
            'title' => 'Landing Page',
            'username' => (string) Session::get('username', 'Guest'),
            'role' => (string) Session::get('role', '-'),
            'komoditasTotal' => Komoditas::totalCount(),
            'stokSummary' => StokHistoris::dashboardSummary(),
            'forecastSummary' => $forecastSummary,
            'forecastTable' => $forecastTable,
        ]);
    }
}
