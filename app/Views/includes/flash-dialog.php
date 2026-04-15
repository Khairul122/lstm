<?php

declare(strict_types=1);

$flashSuccess = flash('success');
$flashError = flash('error');
$dialogConfig = null;

if ($flashSuccess !== null) {
    $dialogConfig = [
        'type' => 'success',
        'title' => 'Aksi Berhasil',
        'message' => (string) $flashSuccess,
        'badge' => 'Success Message',
        'action_label' => 'Siap',
    ];
}

if ($flashError !== null) {
    $dialogConfig = [
        'type' => 'error',
        'title' => 'Terjadi Kendala',
        'message' => (string) $flashError,
        'badge' => 'Error Message',
        'action_label' => 'Mengerti',
    ];
}

if ($dialogConfig === null) {
    return;
}

$flashPopup = $dialogConfig;
require __DIR__ . '/flash-popup.php';
