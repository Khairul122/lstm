<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DataPreprocessingLstm;
use Core\Controller;
use Core\Session;

final class PreprocessingController extends Controller
{
    public function index(): void
    {
        $selectedCommodity = trim((string) ($_GET['komoditas'] ?? ''));
        $tableSearch = trim((string) ($_GET['search'] ?? ''));
        $summaryPage = (int) ($_GET['summary_page'] ?? 1);
        $previewPage = (int) ($_GET['preview_page'] ?? 1);

        $this->renderPage([
            'form' => [
                'komoditas' => $selectedCommodity,
                'sequence_length' => '7',
                'train_ratio' => '0.8',
            ],
            'logs' => [],
            'runResult' => null,
            'tableSearch' => $tableSearch,
            'summaryPage' => $summaryPage,
            'previewPage' => $previewPage,
        ]);
    }

    public function process(): void
    {
        $form = [
            'komoditas' => trim((string) ($_POST['komoditas'] ?? '')),
            'sequence_length' => trim((string) ($_POST['sequence_length'] ?? '7')),
            'train_ratio' => trim((string) ($_POST['train_ratio'] ?? '0.8')),
        ];

        $validationError = $this->validate($form);

        if ($validationError !== null) {
            Session::flash('error', $validationError);
            $this->renderPage([
                'form' => $form,
                'logs' => [],
                'runResult' => null,
                'tableSearch' => trim((string) ($_GET['search'] ?? '')),
                'summaryPage' => 1,
                'previewPage' => 1,
            ]);
            return;
        }

        $runResult = DataPreprocessingLstm::process([
            'komoditas' => $form['komoditas'],
            'sequence_length' => (int) $form['sequence_length'],
            'train_ratio' => (float) $form['train_ratio'],
        ]);

        Session::flash('success', 'Preprocessing data LSTM selesai dan data berhasil disimpan ke database.');

        $this->renderPage([
            'form' => $form,
            'logs' => $runResult['logs'],
            'runResult' => $runResult,
            'tableSearch' => '',
            'summaryPage' => 1,
            'previewPage' => 1,
        ]);
    }

    private function renderPage(array $data): void
    {
        $tableSearch = trim((string) ($data['tableSearch'] ?? ''));
        $summaryPage = max(1, (int) ($data['summaryPage'] ?? 1));
        $previewPage = max(1, (int) ($data['previewPage'] ?? 1));
        $summaryResult = DataPreprocessingLstm::summaryPaginate($tableSearch, $summaryPage, 10);
        $previewResult = DataPreprocessingLstm::previewPaginate($tableSearch, $previewPage, 15);

        $this->view('pages.preprocessing.index', [
            'title' => 'Preprocessing LSTM',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'preprocessing',
            'commodityOptions' => DataPreprocessingLstm::commodityOptions(),
            'form' => $data['form'],
            'logs' => $data['logs'],
            'runResult' => $data['runResult'],
            'summaryRows' => $summaryResult['items'],
            'previewRows' => $previewResult['items'],
            'tableSearch' => $summaryResult['search'],
            'summaryCurrentPage' => $summaryResult['currentPage'],
            'summaryTotalPages' => $summaryResult['totalPages'],
            'summaryTotalItems' => $summaryResult['totalItems'],
            'summaryPerPage' => $summaryResult['perPage'],
            'previewCurrentPage' => $previewResult['currentPage'],
            'previewTotalPages' => $previewResult['totalPages'],
            'previewTotalItems' => $previewResult['totalItems'],
            'previewPerPage' => $previewResult['perPage'],
        ]);
    }

    private function validate(array $form): ?string
    {
        if (!ctype_digit($form['sequence_length'])) {
            return 'Panjang sekuens harus berupa angka bulat.';
        }

        $sequenceLength = (int) $form['sequence_length'];
        if ($sequenceLength < 1 || $sequenceLength > 60) {
            return 'Panjang sekuens harus berada pada rentang 1 sampai 60 hari.';
        }

        if (!is_numeric($form['train_ratio'])) {
            return 'Rasio data latih harus berupa angka.';
        }

        $trainRatio = (float) $form['train_ratio'];
        if ($trainRatio < 0.5 || $trainRatio > 0.95) {
            return 'Rasio data latih harus berada pada rentang 0.50 sampai 0.95.';
        }

        if ($form['komoditas'] !== '' && !in_array($form['komoditas'], DataPreprocessingLstm::commodityOptions(), true)) {
            return 'Komoditas yang dipilih tidak tersedia pada data stok historis.';
        }

        return null;
    }
}
