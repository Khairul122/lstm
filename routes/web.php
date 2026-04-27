<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KomoditasController;
use App\Http\Controllers\LstmController;
use App\Http\Controllers\PreprocessingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StokHistorisController;
use Core\Middleware\AuthMiddleware;
use Core\Middleware\CSRFCheckMiddleware;
use Core\Middleware\GuestMiddleware;

$router->get('/', [HomeController::class, 'index']);

$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class, CSRFCheckMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);

$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/profile', [ProfileController::class, 'index'], [AuthMiddleware::class]);
$router->get('/komoditas', [KomoditasController::class, 'index'], [AuthMiddleware::class]);
$router->get('/komoditas/create', [KomoditasController::class, 'create'], [AuthMiddleware::class]);
$router->post('/komoditas/store', [KomoditasController::class, 'store'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
$router->get('/komoditas/edit/{id}', [KomoditasController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/komoditas/update/{id}', [KomoditasController::class, 'update'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
$router->post('/komoditas/delete/{id}', [KomoditasController::class, 'delete'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
$router->get('/stok-historis', [StokHistorisController::class, 'index'], [AuthMiddleware::class]);
$router->get('/stok-historis/create', [StokHistorisController::class, 'create'], [AuthMiddleware::class]);
$router->post('/stok-historis/store', [StokHistorisController::class, 'store'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
$router->get('/stok-historis/edit/{id}', [StokHistorisController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/stok-historis/update/{id}', [StokHistorisController::class, 'update'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
$router->post('/stok-historis/delete/{id}', [StokHistorisController::class, 'delete'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
$router->get('/preprocessing', [PreprocessingController::class, 'index'], [AuthMiddleware::class]);
$router->post('/preprocessing/process', [PreprocessingController::class, 'process'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
$router->get('/lstm', [LstmController::class, 'index'], [AuthMiddleware::class]);
$router->post('/lstm/train', [LstmController::class, 'train'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
$router->post('/lstm/reset-all', [LstmController::class, 'resetAll'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
$router->get('/lstm/batch/{id}', [LstmController::class, 'batch'], [AuthMiddleware::class]);
$router->get('/lstm/run/{id}', [LstmController::class, 'run'], [AuthMiddleware::class]);
$router->get('/evaluasi', [LstmController::class, 'evaluationIndex'], [AuthMiddleware::class]);
$router->get('/evaluasi/batch/{id}', [LstmController::class, 'batch'], [AuthMiddleware::class]);
$router->get('/evaluasi/run/{id}', [LstmController::class, 'run'], [AuthMiddleware::class]);
$router->get('/evaluasi/batch/{id}/export/batch-summary/{format}', [LstmController::class, 'exportBatchSummary'], [AuthMiddleware::class]);
$router->get('/evaluasi/batch/{id}/export/batch-lengkap/{format}', [LstmController::class, 'exportBatchComplete'], [AuthMiddleware::class]);
$router->get('/evaluasi/batch/{id}/export/rekap-komoditas/{format}', [LstmController::class, 'exportCommodityRecap'], [AuthMiddleware::class]);
$router->get('/evaluasi/run/{id}/export/prediksi/{format}', [LstmController::class, 'exportPredictions'], [AuthMiddleware::class]);
$router->get('/evaluasi/run/{id}/export/residual/{format}', [LstmController::class, 'exportResiduals'], [AuthMiddleware::class]);
$router->get('/evaluasi/run/{id}/export/forecast/{format}', [LstmController::class, 'exportForecasts'], [AuthMiddleware::class]);
$router->post('/evaluasi/batch/{id}/delete', [LstmController::class, 'deleteBatch'], [AuthMiddleware::class, CSRFCheckMiddleware::class]);
