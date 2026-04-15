<?php

declare(strict_types=1);

use Core\CSRF;
use Core\Session;

if (!function_exists('app_config')) {
    function app_config(string $key, mixed $default = null): mixed
    {
        static $config;
        $config ??= require __DIR__ . '/../config/app.php';
        return $config[$key] ?? $default;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = '/'): string
    {
        $base = rtrim((string) app_config('base_url', ''), '/');
        $path = '/' . ltrim($path, '/');
        return $base . ($path === '/' ? '' : $path);
    }
}

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . CSRF::token() . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $old = Session::get('_old_input', []);
        return (string) ($old[$key] ?? $default);
    }
}

if (!function_exists('set_old_input')) {
    function set_old_input(array $input): void
    {
        unset($input['password'], $input['_token']);
        Session::set('_old_input', $input);
    }
}

if (!function_exists('clear_old_input')) {
    function clear_old_input(): void
    {
        Session::forget('_old_input');
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $default = null): mixed
    {
        return Session::pull($key, $default);
    }
}

if (!function_exists('query_string')) {
    function query_string(array $overrides = [], array $remove = []): string
    {
        $query = $_GET;

        foreach ($remove as $key) {
            unset($query[$key]);
        }

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
                continue;
            }

            $query[$key] = (string) $value;
        }

        $built = http_build_query($query);

        return $built === '' ? '' : '?' . $built;
    }
}

if (!function_exists('landing_page_theme_assets')) {
    function landing_page_theme_assets(): string
    {
        return <<<'HTML'
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    'on-tertiary-container': '#a0c655',
                    'error-container': '#ffdad6',
                    'on-secondary-fixed-variant': '#0e5138',
                    'surface-container': '#eeeeec',
                    'on-primary-container': '#97bbf6',
                    tertiary: '#263900',
                    'tertiary-fixed': '#c8f17a',
                    outline: '#727781',
                    'on-primary-fixed-variant': '#1f477b',
                    'on-secondary-container': '#316e52',
                    'inverse-on-surface': '#f0f1ef',
                    'on-error': '#ffffff',
                    'on-primary': '#ffffff',
                    'on-tertiary-fixed': '#131f00',
                    'on-error-container': '#93000a',
                    'surface-container-lowest': '#ffffff',
                    'surface-tint': '#3a5f94',
                    background: '#f9f9f8',
                    'secondary-fixed-dim': '#95d4b3',
                    'tertiary-container': '#395100',
                    surface: '#f9f9f8',
                    'primary-fixed': '#d5e3ff',
                    error: '#ba1a1a',
                    'on-surface': '#1a1c1b',
                    'surface-container-high': '#e8e8e6',
                    'inverse-primary': '#a7c8ff',
                    'primary-container': '#234a7e',
                    'on-secondary-fixed': '#002114',
                    'on-surface-variant': '#424750',
                    'on-primary-fixed': '#001b3c',
                    'outline-variant': '#c2c6d1',
                    'surface-bright': '#f9f9f8',
                    'tertiary-fixed-dim': '#add461',
                    'on-tertiary-fixed-variant': '#364e00',
                    'surface-container-highest': '#e2e3e1',
                    'on-tertiary': '#ffffff',
                    'on-background': '#1a1c1b',
                    'inverse-surface': '#2f3130',
                    'primary-fixed-dim': '#a7c8ff',
                    'surface-variant': '#e2e3e1',
                    primary: '#003366',
                    'secondary-container': '#aeeecb',
                    secondary: '#2c694e',
                    'surface-container-low': '#f3f4f2',
                    'on-secondary': '#ffffff',
                    'secondary-fixed': '#b1f0ce',
                    'surface-dim': '#dadad8'
                },
                borderRadius: {
                    DEFAULT: '0.125rem',
                    lg: '0.25rem',
                    xl: '0.5rem',
                    full: '9999px'
                },
                boxShadow: {
                    panel: '0 24px 60px rgba(15, 23, 42, 0.08)',
                    glow: '0 18px 45px rgba(0, 51, 102, 0.18)'
                },
                animation: {
                    reveal: 'reveal .8s ease forwards',
                    floatSlow: 'floatSlow 8s ease-in-out infinite',
                    pulseRing: 'pulseRing 3.5s ease-out infinite',
                    shimmer: 'shimmer 2.8s linear infinite'
                },
                keyframes: {
                    reveal: {
                        '0%': { opacity: 0, transform: 'translateY(28px)' },
                        '100%': { opacity: 1, transform: 'translateY(0)' }
                    },
                    floatSlow: {
                        '0%, 100%': { transform: 'translateY(0px)' },
                        '50%': { transform: 'translateY(-12px)' }
                    },
                    pulseRing: {
                        '0%': { transform: 'scale(.95)', opacity: '.45' },
                        '70%': { transform: 'scale(1.08)', opacity: '0' },
                        '100%': { transform: 'scale(1.08)', opacity: '0' }
                    },
                    shimmer: {
                        '0%': { backgroundPosition: '-200% 0' },
                        '100%': { backgroundPosition: '200% 0' }
                    }
                }
            }
        }
    };
</script>
<style>
    body { font-family: 'Inter', sans-serif; }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .glass-panel {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(24px);
    }
    .reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity .8s ease, transform .8s ease;
    }
    .reveal.visible {
        opacity: 1;
        transform: translateY(0);
    }
    .nav-link {
        position: relative;
    }
    .nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -6px;
        width: 100%;
        height: 2px;
        background: #003366;
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s ease;
    }
    .nav-link.is-active::after,
    .nav-link:hover::after {
        transform: scaleX(1);
    }
    .metric-card,
    .insight-card,
    .overview-card,
    .snapshot-row,
    .forecast-row,
    .tilt-card {
        transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease, background-color .28s ease;
        will-change: transform;
    }
    .metric-card:hover,
    .insight-card:hover,
    .overview-card:hover,
    .tilt-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
    }
    .interactive-button {
        transition: transform .22s ease, box-shadow .22s ease, opacity .22s ease;
    }
    .interactive-button:hover {
        box-shadow: 0 14px 32px rgba(0, 51, 102, 0.16);
    }
    .hero-parallax {
        transition: transform .5s ease-out;
        transform-origin: center center;
    }
    .glass-orb {
        position: absolute;
        border-radius: 9999px;
        background: radial-gradient(circle, rgba(255,255,255,.55), rgba(255,255,255,0));
        filter: blur(2px);
        pointer-events: none;
    }
    .stat-number[data-count] {
        font-variant-numeric: tabular-nums;
    }
    .chart-shell {
        position: relative;
        overflow: hidden;
    }
    .chart-shell::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.45) 50%, rgba(255,255,255,0) 80%);
        background-size: 200% 100%;
        animation: shimmer 4.2s linear infinite;
        pointer-events: none;
        opacity: .45;
    }
    .progress-bar {
        transform-origin: left center;
        transform: scaleX(0);
        transition: transform 1s cubic-bezier(.2,.8,.2,1);
    }
    .progress-bar.is-visible {
        transform: scaleX(1);
    }
    .table-wrap {
        overflow-x: auto;
    }
    .skeleton-block {
        position: relative;
        overflow: hidden;
        background: #e8e8e6;
    }
    .skeleton-block::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.65), rgba(255,255,255,0));
        transform: translateX(-100%);
        animation: shimmer 1.6s linear infinite;
    }
    .skeleton-shell {
        transition: opacity .35s ease, visibility .35s ease;
    }
    .skeleton-shell.is-hidden {
        opacity: 0;
        visibility: hidden;
    }
    .mobile-nav-panel {
        transition: transform .28s ease, opacity .28s ease, visibility .28s ease;
    }
    .mobile-nav-panel.is-hidden {
        opacity: 0;
        visibility: hidden;
        transform: translateY(-12px);
        pointer-events: none;
    }
    .mascot-shell {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 70;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 12px;
    }
    .mascot-card {
        width: min(360px, calc(100vw - 32px));
        border-radius: 24px;
        border: 1px solid rgba(255,255,255,.7);
        background: rgba(255,255,255,.92);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
        backdrop-filter: blur(18px);
        overflow: hidden;
        transform-origin: bottom right;
        transition: transform .28s ease, opacity .28s ease, visibility .28s ease;
    }
    .mascot-card.is-hidden {
        opacity: 0;
        visibility: hidden;
        transform: translateY(16px) scale(.94);
        pointer-events: none;
    }
    .mascot-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px 10px;
    }
    .mascot-title-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .mascot-avatar {
        position: relative;
        width: 54px;
        height: 54px;
        border-radius: 18px;
        background: linear-gradient(135deg, #003366, #2c694e);
        display: grid;
        place-items: center;
        color: #fff;
        box-shadow: 0 14px 30px rgba(0, 51, 102, .22);
        overflow: hidden;
    }
    .mascot-avatar::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,.24), rgba(255,255,255,0));
    }
    .mascot-avatar svg {
        position: relative;
        z-index: 1;
        width: 28px;
        height: 28px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.9;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .mascot-avatar img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 2;
        object-fit: cover;
    }
    .mascot-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #2c694e;
    }
    .mascot-status::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #2c694e;
        box-shadow: 0 0 0 6px rgba(44,105,78,.12);
    }
    .mascot-body {
        padding: 0 18px 18px;
    }
    .mascot-body p {
        margin: 0;
        font-size: .92rem;
        line-height: 1.65;
        color: #424750;
    }
    .mascot-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }
    .mascot-chip {
        border: 0;
        border-radius: 999px;
        background: #f3f4f2;
        color: #003366;
        padding: 9px 12px;
        font-size: .77rem;
        font-weight: 700;
        cursor: pointer;
        transition: transform .2s ease, background-color .2s ease, color .2s ease;
    }
    .mascot-chip:hover {
        transform: translateY(-2px);
        background: #003366;
        color: #fff;
    }
    .mascot-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .mascot-icon-btn,
    .mascot-toggle {
        border: 0;
        cursor: pointer;
    }
    .mascot-icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: #f3f4f2;
        color: #003366;
        display: grid;
        place-items: center;
    }
    .mascot-icon-btn.is-speaking {
        background: #003366;
        color: #ffffff;
        box-shadow: 0 10px 24px rgba(0,51,102,.18);
    }
    .mascot-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 999px;
        background: linear-gradient(135deg, #003366, #2c694e);
        color: #fff;
        box-shadow: 0 18px 36px rgba(0,51,102,.26);
        padding: 12px 16px 12px 12px;
    }
    .mascot-toggle .mascot-avatar {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        box-shadow: none;
    }
    .mascot-toggle .mascot-avatar svg {
        width: 22px;
        height: 22px;
    }
    .mascot-toggle span:last-child {
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .03em;
    }
    .mascot-tip {
        margin-top: 12px;
        padding: 12px 14px;
        border-radius: 16px;
        background: #f7faf8;
        border: 1px solid rgba(0,51,102,.08);
        font-size: .82rem;
        color: #424750;
    }
    .mascot-bubble {
        max-width: 280px;
        padding: 12px 14px;
        border-radius: 18px;
        background: #ffffff;
        border: 1px solid rgba(0,51,102,.08);
        box-shadow: 0 16px 32px rgba(15,23,42,.12);
        font-size: .8rem;
        line-height: 1.55;
        color: #424750;
        transition: opacity .25s ease, transform .25s ease, visibility .25s ease;
    }
    .mascot-bubble.is-hidden {
        opacity: 0;
        visibility: hidden;
        transform: translateY(8px);
        pointer-events: none;
    }
    .mascot-avatar.is-curious {
        box-shadow: 0 16px 34px rgba(0, 51, 102, .28);
        transform: scale(1.04);
    }
    .mascot-avatar.is-excited {
        background: linear-gradient(135deg, #2c694e, #003366);
        box-shadow: 0 18px 36px rgba(44, 105, 78, .28);
        transform: scale(1.06);
    }
    .mascot-avatar.is-alert {
        background: linear-gradient(135deg, #8a5a00, #003366);
        box-shadow: 0 18px 36px rgba(138, 90, 0, .24);
    }
    .mascot-faq {
        margin-top: 14px;
        display: grid;
        gap: 8px;
    }
    .mascot-faq button {
        width: 100%;
        text-align: left;
        border: 0;
        border-radius: 14px;
        background: #f3f4f2;
        color: #003366;
        padding: 10px 12px;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform .2s ease, background-color .2s ease, color .2s ease;
    }
    .mascot-faq button:hover {
        transform: translateY(-2px);
        background: #dfe8e3;
    }
    @media (max-width: 640px) {
        .mascot-shell {
            right: 12px;
            left: 12px;
            bottom: 12px;
            align-items: stretch;
        }
        .mascot-card {
            width: 100%;
        }
        .mascot-toggle {
            justify-content: center;
        }
    }
</style>
HTML;
    }
}
