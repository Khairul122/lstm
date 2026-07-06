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
        $envUrl = getenv('APP_URL');
        if ($envUrl) {
            $base = rtrim($envUrl, '/');
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $subFolder = '';
            if ($scriptName) {
                $dir = dirname($scriptName);
                if ($dir !== '/' && $dir !== '\\' && $dir !== '.') {
                    $subFolder = '/' . trim(str_replace('\\', '/', $dir), '/');
                }
            }
            $base = $protocol . '://' . $host . $subFolder;
        } else {
            $base = rtrim((string) app_config('base_url', ''), '/');
        }
        
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
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Plus Jakarta Sans', 'sans-serif'],
                    outfit: ['Outfit', 'sans-serif'],
                },
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
                    background: '#fafbfc',
                    'secondary-fixed-dim': '#95d4b3',
                    'tertiary-container': '#395100',
                    surface: '#fafbfc',
                    'primary-fixed': '#d5e3ff',
                    error: '#e11d48',
                    'on-surface': '#0f172a',
                    'surface-container-high': '#e2e8f0',
                    'inverse-primary': '#a7c8ff',
                    'primary-container': '#1e3a8a',
                    'on-secondary-fixed': '#002114',
                    'on-surface-variant': '#475569',
                    'on-primary-fixed': '#001b3c',
                    'outline-variant': '#cbd5e1',
                    'surface-bright': '#fafbfc',
                    'tertiary-fixed-dim': '#add461',
                    'on-tertiary-fixed-variant': '#364e00',
                    'surface-container-highest': '#cbd5e1',
                    'on-tertiary': '#ffffff',
                    'on-background': '#0f172a',
                    'inverse-surface': '#0f172a',
                    'primary-fixed-dim': '#a7c8ff',
                    'surface-variant': '#f1f5f9',
                    primary: '#0f3b75',
                    'secondary-container': '#ccfbf1',
                    secondary: '#0d9488',
                    'surface-container-low': '#f8fafc',
                    'on-secondary': '#ffffff',
                    'secondary-fixed': '#99f6e4',
                    'surface-dim': '#e2e8f0'
                },
                borderRadius: {
                    DEFAULT: '0.375rem',
                    lg: '0.5rem',
                    xl: '0.75rem',
                    '2xl': '1rem',
                    '3xl': '1.5rem',
                    full: '9999px'
                },
                boxShadow: {
                    panel: '0 20px 50px rgba(15, 23, 42, 0.05)',
                    glow: '0 12px 36px rgba(15, 59, 117, 0.15)',
                    'glow-sec': '0 12px 36px rgba(13, 148, 136, 0.15)',
                    glass: '0 8px 32px 0 rgba(15, 23, 42, 0.03)',
                },
                animation: {
                    reveal: 'reveal .8s ease forwards',
                    floatSlow: 'floatSlow 6s ease-in-out infinite',
                    pulseRing: 'pulseRing 3s ease-out infinite',
                    shimmer: 'shimmer 2.5s linear infinite'
                },
                keyframes: {
                    reveal: {
                        '0%': { opacity: 0, transform: 'translateY(24px)' },
                        '100%': { opacity: 1, transform: 'translateY(0)' }
                    },
                    floatSlow: {
                        '0%, 100%': { transform: 'translateY(0px)' },
                        '50%': { transform: 'translateY(-10px)' }
                    },
                    pulseRing: {
                        '0%': { transform: 'scale(.92)', opacity: '.4' },
                        '70%': { transform: 'scale(1.06)', opacity: '0' },
                        '100%': { transform: 'scale(1.06)', opacity: '0' }
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
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .font-outfit { font-family: 'Outfit', sans-serif; }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    
    /* Premium Glassmorphism */
    .glass-panel-premium {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.45);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }
    
    /* Ambient Blur Blobs */
    .bg-blob-indigo {
        position: absolute;
        width: min(500px, 80vw);
        height: min(500px, 80vw);
        border-radius: 50%;
        background: radial-gradient(circle, rgba(15, 59, 117, 0.15) 0%, rgba(224, 242, 254, 0.05) 70%, transparent 100%);
        filter: blur(40px);
        animation: floatSlow 10s ease-in-out infinite alternate;
        pointer-events: none;
        z-index: 0;
    }
    .bg-blob-teal {
        position: absolute;
        width: min(450px, 75vw);
        height: min(450px, 75vw);
        border-radius: 50%;
        background: radial-gradient(circle, rgba(13, 148, 136, 0.12) 0%, rgba(204, 251, 241, 0.05) 70%, transparent 100%);
        filter: blur(40px);
        animation: floatSlow 12s ease-in-out infinite alternate-reverse;
        pointer-events: none;
        z-index: 0;
    }

    /* Core Reveals */
    .reveal {
        opacity: 0;
        transform: translateY(24px);
        transition: opacity .8s cubic-bezier(0.16, 1, 0.3, 1), transform .8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .reveal.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    .is-hidden {
        display: none !important;
    }

    /* Premium Navigation */
    .nav-link {
        position: relative;
        font-weight: 600;
        letter-spacing: -0.01em;
    }
    .nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -4px;
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, #0f3b75, #0d9488);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .nav-link.is-active::after,
    .nav-link:hover::after {
        transform: scaleX(1);
    }

    /* Card Lift & Glows */
    .hover-lift {
        transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow .3s cubic-bezier(0.16, 1, 0.3, 1), border-color .3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .hover-lift:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        border-color: rgba(15, 59, 117, 0.15);
    }
    .card-glow-primary:hover {
        box-shadow: 0 24px 50px rgba(15, 59, 117, 0.1), 0 0 0 1px rgba(15, 59, 117, 0.1);
    }
    .card-glow-secondary:hover {
        box-shadow: 0 24px 50px rgba(13, 148, 136, 0.1), 0 0 0 1px rgba(13, 148, 136, 0.1);
    }

    /* Timeline step-connector with flow animation */
    .step-connector-animated {
        position: absolute;
        left: 50%;
        top: 48px;
        width: 100%;
        height: 2px;
        background-image: linear-gradient(to right, rgba(13, 148, 136, 0.25) 50%, transparent 50%);
        background-size: 16px 2px;
        background-repeat: repeat-x;
        animation: flowDash 0.8s linear infinite;
        z-index: 0;
    }
    @keyframes flowDash {
        0% { background-position: 0 0; }
        100% { background-position: 16px 0; }
    }

    /* Chart Layout Shimmer */
    .chart-shell::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.6) 50%, rgba(255,255,255,0) 80%);
        background-size: 200% 100%;
        animation: shimmer 3s linear infinite;
        pointer-events: none;
        opacity: .35;
    }

    /* Progress bars */
    .progress-bar {
        transform-origin: left center;
        transform: scaleX(0);
        transition: transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .progress-bar.is-visible {
        transform: scaleX(1);
    }

    /* Skeleton Loading UI */
    .skeleton-block {
        position: relative;
        overflow: hidden;
        background: #f1f5f9;
    }
    .skeleton-block::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.7), rgba(255,255,255,0));
        transform: translateX(-100%);
        animation: shimmer 1.5s linear infinite;
    }

    /* Custom Scrollbar for list-panel & chat */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 99px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* ===================== AI MASCOT UI ===================== */
    .mascot-shell {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 70;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 12px;
    }
    .mascot-card {
        width: min(380px, calc(100vw - 32px));
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.7);
        background: rgba(255, 255, 255, 0.88);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(15, 59, 117, 0.04);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        overflow: hidden;
        transform-origin: bottom right;
        transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1), opacity .3s ease, visibility .3s ease;
        display: flex;
        flex-direction: column;
        max-height: min(580px, 80vh);
    }
    .mascot-card.is-hidden {
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(.92);
        pointer-events: none;
    }
    .mascot-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px;
        background: linear-gradient(135deg, #0f3b75 0%, #0d9488 100%);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    }
    .mascot-title-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .mascot-avatar {
        position: relative;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.25);
        display: grid;
        place-items: center;
        color: #fff;
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    .mascot-avatar img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 4px;
    }
    .mascot-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .65rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #aeeecb;
    }
    .mascot-status::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 55px;
        background: #aeeecb;
        box-shadow: 0 0 0 4px rgba(174,238,203,.28);
        animation: pulseStatus 2s infinite;
    }
    @keyframes pulseStatus {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.6; }
    }
    .mascot-body {
        padding: 16px 20px;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    
    /* Modern AI Chat bubbles */
    .mascot-message-bot {
        align-self: flex-start;
        max-width: 88%;
        background: #f1f5f9;
        border-radius: 16px 16px 16px 4px;
        padding: 12px 14px;
        font-size: 0.85rem;
        line-height: 1.55;
        color: #0f172a;
        box-shadow: 0 2px 4px rgba(15,23,42,0.02);
    }
    .mascot-message-user {
        align-self: flex-end;
        max-width: 88%;
        background: linear-gradient(135deg, #0f3b75 0%, #1e40af 100%);
        border-radius: 16px 16px 4px 16px;
        padding: 12px 14px;
        font-size: 0.85rem;
        line-height: 1.55;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(15,59,117,0.15);
    }

    .mascot-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px;
    }
    .mascot-chip {
        border: 1px solid rgba(15, 59, 117, 0.08);
        border-radius: 99px;
        background: #ffffff;
        color: #0f3b75;
        padding: 8px 12px;
        font-size: .75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s ease;
        box-shadow: 0 2px 4px rgba(15,23,42,0.02);
    }
    .mascot-chip:hover {
        transform: translateY(-1.5px);
        background: #0f3b75;
        color: #fff;
        border-color: #0f3b75;
        box-shadow: 0 4px 10px rgba(15,59,117,0.15);
    }
    .mascot-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .mascot-icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        display: grid;
        place-items: center;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }
    .mascot-icon-btn:hover {
        background: rgba(255, 255, 255, 0.28);
        transform: scale(1.05);
    }
    .mascot-icon-btn.is-speaking {
        background: #aeeecb;
        color: #0f3b75;
        box-shadow: 0 4px 12px rgba(174,238,203,0.3);
    }
    .mascot-toggle {
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 999px;
        background: linear-gradient(135deg, #0f3b75 0%, #0d9488 100%);
        color: #fff;
        box-shadow: 0 12px 30px rgba(15,59,117,0.22);
        padding: 8px 16px 8px 8px;
        transition: transform .2s ease, box-shadow .2s ease;
        border: none;
        cursor: pointer;
        position: relative;
    }
    .mascot-toggle::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #0d9488;
        opacity: .35;
        animation: rippleOut 2.5s ease-out infinite;
        z-index: -1;
    }
    @keyframes rippleOut {
        0% { transform: scale(0.9); opacity: .6; }
        100% { transform: scale(1.6); opacity: 0; }
    }
    .mascot-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 36px rgba(15,59,117,0.28);
    }
    .mascot-toggle .mascot-avatar {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: none;
    }
    .mascot-toggle span:last-child {
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .mascot-tip {
        padding: 10px 12px;
        border-radius: 12px;
        background: #f0fdfa;
        border-left: 3px solid #0d9488;
        font-size: .75rem;
        color: #0f766e;
        line-height: 1.45;
    }
    .mascot-bubble {
        max-width: 260px;
        padding: 10px 14px;
        border-radius: 16px 16px 4px 16px;
        background: #ffffff;
        border: 1px solid rgba(15,59,117,0.06);
        box-shadow: 0 12px 28px rgba(15,23,42,.1);
        font-size: .78rem;
        line-height: 1.5;
        color: #334155;
        transition: all .25s ease;
        animation: bounceIn .4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes bounceIn {
        0% { transform: scale(0.85); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
    .mascot-bubble.is-hidden {
        opacity: 0;
        visibility: hidden;
        transform: translateY(8px);
        pointer-events: none;
    }
    .mascot-input-wrap {
        display: flex;
        gap: 6px;
        padding: 12px 16px;
        border-t: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .mascot-input {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 13px;
        outline: none;
        transition: all .2s ease;
        background: #ffffff;
    }
    .mascot-input:focus {
        border-color: #0f3b75;
        box-shadow: 0 0 0 3px rgba(15,59,117,0.08);
    }
    .mascot-send {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: #0f3b75;
        color: #ffffff;
        display: grid;
        place-items: center;
        cursor: pointer;
        border: none;
        transition: all .2s ease;
    }
    .mascot-send:hover {
        background: #1d4ed8;
        transform: scale(1.05);
    }
    
    .mascot-avatar.is-curious {
        animation: breathe 3s ease-in-out infinite;
    }
    .mascot-avatar.is-excited {
        animation: wiggle 0.6s ease-in-out 2;
    }
    .mascot-avatar.is-alert {
        animation: breathe 1.5s ease-in-out infinite;
    }
    
    @keyframes breathe {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    @keyframes wiggle {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(-5deg); }
        75% { transform: rotate(5deg); }
    }

    @media (max-width: 640px) {
        .mascot-shell {
            right: 16px;
            left: 16px;
            bottom: 16px;
            align-items: stretch;
        }
        .mascot-card {
            width: 100%;
        }
            justify-content: center;
        }
    }
</style>
HTML;
    }
}
