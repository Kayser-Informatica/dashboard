<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Painel operacional de monitoramento de clientes, serviços, heartbeats e logs.">
    <title>Systems Control | Monitoramento de Clientes e Serviços</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0a0f16;
            --bg-surface: #111822;
            --bg-surface-elevated: #16202c;
            --bg-card-head: #141c27;
            --bg-meta: #0d131b;
            
            --ink: #f1f5f9;
            --ink-muted: #94a3b8;
            --ink-subtle: #64748b;
            --line: #1e293b;
            --line-light: #334155;

            --emerald: #10b981;
            --emerald-glow: rgba(16, 185, 129, 0.25);
            --emerald-bg: rgba(16, 185, 129, 0.12);

            --amber: #f59e0b;
            --amber-glow: rgba(245, 158, 11, 0.25);
            --amber-bg: rgba(245, 158, 11, 0.12);

            --rose: #f43f5e;
            --rose-glow: rgba(244, 63, 94, 0.3);
            --rose-bg: rgba(244, 63, 94, 0.15);

            --cyan: #06b6d4;
            --cyan-glow: rgba(6, 182, 212, 0.25);
            --cyan-bg: rgba(6, 182, 212, 0.12);

            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-card: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 0 1px 1px rgba(255, 255, 255, 0.05);
        }

        * { box-sizing: border-box; }

        html, body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            background-color: var(--bg-base);
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .app-shell {
            display: flex;
            flex-direction: column;
            padding: 20px 28px 24px;
            gap: 18px;
            max-width: 1920px;
            margin: 0 auto;
            min-height: 100vh;
        }

        /* Top Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--line);
        }

        .brand-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #0284c7 0%, #6366f1 100%);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 17px;
            color: #ffffff;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
            letter-spacing: -0.05em;
        }

        .brand-title {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #ffffff;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-badge {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(14, 165, 233, 0.18);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .brand-sub {
            font-size: 12px;
            color: var(--ink-muted);
            margin-top: 2px;
        }

        .clock-widget {
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--bg-surface);
            padding: 6px 18px;
            border-radius: 999px;
            border: 1px solid var(--line);
        }

        .clock-time {
            font-family: 'JetBrains Mono', monospace;
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.05em;
        }

        .clock-date {
            font-size: 12px;
            color: var(--ink-muted);
            font-weight: 500;
            border-left: 1px solid var(--line-light);
            padding-left: 14px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .live-badge {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: var(--bg-surface);
            border: 1px solid var(--line);
            color: #34d399;
            border-color: rgba(52, 211, 153, 0.3);
            background: rgba(16, 185, 129, 0.08);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            position: relative;
        }

        .pulse-dot::after {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: currentColor;
            opacity: 0.4;
            animation: pulse-ring 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
        }

        .countdown-widget {
            display: flex;
            align-items: center;
            gap: 7px;
            background: var(--bg-surface);
            border: 1px solid var(--line);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            color: var(--ink-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .countdown-label {
            font-size: 11px;
            color: var(--ink-subtle);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .countdown-seconds {
            color: #38bdf8;
            font-weight: 700;
            font-size: 13px;
            min-width: 24px;
            text-align: right;
        }

        .countdown-icon {
            font-size: 13px;
            color: #38bdf8;
            display: inline-block;
            transition: transform 0.5s ease;
        }

        .countdown-icon.spinning {
            transform: rotate(360deg);
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.6); opacity: 0.8; }
            70% { transform: scale(1.8); opacity: 0; }
            100% { transform: scale(1.8); opacity: 0; }
        }

        /* Metrics Row */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
        }

        .metric-card {
            background: var(--bg-surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-card);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: transparent;
        }

        .metric-card--clients::before { background: linear-gradient(90deg, #8b5cf6, #d946ef); }
        .metric-card--total::before { background: linear-gradient(90deg, #3b82f6, #6366f1); }
        .metric-card--online::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .metric-card--attention::before { background: linear-gradient(90deg, #f59e0b, #ef4444); }
        .metric-card--backups::before { background: linear-gradient(90deg, #06b6d4, #3b82f6); }

        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .metric-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ink-muted);
        }

        .metric-value-row {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-top: 8px;
        }

        .metric-value {
            font-size: 34px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #ffffff;
            font-variant-numeric: tabular-nums;
        }

        .metric-card--clients .metric-value { color: #c084fc; }
        .metric-card--online .metric-value { color: #34d399; }
        .metric-card--attention .metric-value { color: #fbbf24; }
        .metric-card--attention.has-attention .metric-value { color: #f87171; }
        .metric-card--backups .metric-value { color: #38bdf8; }

        .metric-note {
            font-size: 12px;
            color: var(--ink-muted);
            margin-top: 6px;
        }

        /* Controls & Filter Bar */
        .filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: var(--bg-surface);
            padding: 12px 18px;
            border-radius: var(--radius-md);
            border: 1px solid var(--line);
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 420px;
        }

        .search-input {
            width: 100%;
            background: var(--bg-meta);
            border: 1px solid var(--line);
            color: #fff;
            padding: 8px 14px 8px 36px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            border-color: #38bdf8;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ink-subtle);
            font-size: 13px;
        }

        .filter-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn {
            background: var(--bg-meta);
            border: 1px solid var(--line);
            color: var(--ink-muted);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            color: #fff;
            border-color: var(--line-light);
        }

        .filter-btn.active {
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
            border-color: rgba(56, 189, 248, 0.4);
        }

        /* Clients & Services Container */
        .clients-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .client-section {
            background: var(--bg-surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-card);
        }

        .client-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 22px;
            background: var(--bg-card-head);
            border-bottom: 1px solid var(--line);
        }

        .client-title-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .client-avatar {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 14px;
            color: #fff;
        }

        .client-name {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
        }

        .client-slug {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--ink-subtle);
            background: var(--bg-base);
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid var(--line);
        }

        .client-services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
            gap: 16px;
            padding: 20px;
        }

        /* Service Card */
        .service-card {
            background: var(--bg-surface-elevated);
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.25s ease;
        }

        .service-card:hover {
            border-color: var(--line-light);
        }

        /* Highlighting for failed / alert cards */
        .service-card[data-status="failed"],
        .service-card.service-card--failed {
            border: 1.5px solid rgba(244, 63, 94, 0.7);
            border-left: 5px solid #f43f5e;
            background: linear-gradient(180deg, rgba(244, 63, 94, 0.16) 0%, rgba(20, 10, 15, 0.95) 100%) !important;
            box-shadow: 0 0 24px -2px rgba(244, 63, 94, 0.28), 0 8px 24px rgba(0, 0, 0, 0.5);
            animation: card-alert-pulse 3s infinite ease-in-out;
        }

        .service-card[data-status="failed"] .service-card-head,
        .service-card.service-card--failed .service-card-head {
            background: rgba(244, 63, 94, 0.22) !important;
            border-bottom: 1px solid rgba(244, 63, 94, 0.4);
        }

        .service-card[data-status="failed"] .service-name,
        .service-card.service-card--failed .service-name {
            color: #ffe4e6;
            text-shadow: 0 0 10px rgba(244, 63, 94, 0.4);
        }

        .service-card[data-status="failed"] .service-meta-grid,
        .service-card.service-card--failed .service-meta-grid {
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(244, 63, 94, 0.25);
        }

        .service-card[data-status="failed"] .service-alert-banner,
        .service-card.service-card--failed .service-alert-banner {
            background: rgba(244, 63, 94, 0.28);
            border-bottom: 1px solid rgba(244, 63, 94, 0.4);
            color: #fff1f2;
            font-weight: 600;
        }

        .service-card[data-status="failed"] .log-row,
        .service-card.service-card--failed .log-row {
            background: rgba(0, 0, 0, 0.2);
            border-color: rgba(244, 63, 94, 0.2);
        }

        @keyframes card-alert-pulse {
            0%, 100% {
                border-color: rgba(244, 63, 94, 0.6);
                box-shadow: 0 0 20px -2px rgba(244, 63, 94, 0.25), 0 6px 20px rgba(0, 0, 0, 0.4);
            }
            50% {
                border-color: rgba(244, 63, 94, 1);
                box-shadow: 0 0 32px 3px rgba(244, 63, 94, 0.45), 0 8px 24px rgba(0, 0, 0, 0.55);
            }
        }

        .service-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            background: var(--bg-meta);
            border-bottom: 1px solid var(--line);
            gap: 12px;
        }

        .service-name {
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Badges */
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .status-chip::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: currentColor;
        }

        .status--ok {
            color: #34d399;
            background: var(--emerald-bg);
            border: 1px solid rgba(52, 211, 153, 0.25);
        }

        .status--overdue {
            color: #fb923c;
            background: rgba(251, 146, 60, 0.15);
            border: 1px solid rgba(251, 146, 60, 0.35);
            animation: pulse-border 1.8s infinite;
        }

        .status--failed {
            color: #f87171;
            background: var(--rose-bg);
            border: 1px solid rgba(248, 113, 113, 0.35);
            animation: pulse-border 1.8s infinite;
        }

        .status--unknown {
            color: #fbbf24;
            background: var(--amber-bg);
            border: 1px solid rgba(251, 191, 36, 0.25);
        }

        @keyframes pulse-border {
            0%, 100% { box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.4); }
            50% { box-shadow: 0 0 0 4px rgba(244, 63, 94, 0); }
        }

        /* Meta Grid */
        .service-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 12px 18px;
            background: rgba(0, 0, 0, 0.15);
            border-bottom: 1px solid var(--line);
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--ink-subtle);
        }

        .meta-value {
            font-size: 12px;
            font-weight: 600;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .meta-value--highlight {
            color: #38bdf8;
            font-family: 'JetBrains Mono', monospace;
        }

        .meta-value--alert {
            color: #f87171;
            font-weight: 700;
        }

        /* Alert Banner */
        .service-alert-banner {
            padding: 8px 18px;
            font-size: 11px;
            background: rgba(244, 63, 94, 0.12);
            border-bottom: 1px solid rgba(244, 63, 94, 0.25);
            color: #fca5a5;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Logs Area */
        .service-logs-area {
            padding: 12px 18px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .logs-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ink-muted);
        }

        .log-row {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            align-items: center;
            gap: 10px;
            padding: 6px 10px;
            background: var(--bg-meta);
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            font-size: 11px;
        }

        .log-file {
            font-family: 'JetBrains Mono', monospace;
            color: #e2e8f0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .log-date {
            color: var(--ink-muted);
            white-space: nowrap;
        }

        .log-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-log-action {
            background: var(--bg-surface);
            border: 1px solid var(--line);
            color: var(--ink-muted);
            border-radius: 4px;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-log-action:hover {
            color: #fff;
            border-color: #38bdf8;
            background: rgba(14, 165, 233, 0.15);
        }

        .empty-logs {
            padding: 10px;
            text-align: center;
            color: var(--ink-subtle);
            font-size: 11px;
            background: var(--bg-meta);
            border: 1px dashed var(--line);
            border-radius: var(--radius-sm);
        }

        /* Modal Log Viewer */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            display: none;
            place-items: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: grid;
        }

        .modal-content {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 800px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }

        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #1e293b;
        }

        .modal-title {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            font-family: 'JetBrains Mono', monospace;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--ink-muted);
            font-size: 20px;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        .log-terminal {
            background: #090d16;
            color: #38bdf8;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            line-height: 1.6;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #1e293b;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 50vh;
            overflow-y: auto;
        }

        .modal-foot {
            padding: 14px 20px;
            border-top: 1px solid #1e293b;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Empty State */
        .empty-dashboard {
            padding: 60px 20px;
            text-align: center;
            background: var(--bg-surface);
            border: 1px dashed var(--line);
            border-radius: var(--radius-lg);
        }

        .empty-dashboard h3 { margin: 0 0 8px; color: #fff; }
        .empty-dashboard p { color: var(--ink-muted); font-size: 13px; max-width: 480px; margin: 0 auto; line-height: 1.5; }

        @media (max-width: 1200px) {
            .metrics-grid { grid-template-columns: repeat(3, 1fr); }
            .client-services-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .metrics-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: flex-start; }
            .filter-bar { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <!-- Top Header -->
        <header class="header">
            <div class="brand-group">
                <div class="brand-logo">SC</div>
                <div>
                    <div class="brand-title">
                        Systems Control
                        <span class="brand-badge">HEARTBEAT NOC</span>
                    </div>
                    <div class="brand-sub">Monitoramento de Clientes, Serviços & Periodicidade</div>
                </div>
            </div>

            <div class="clock-widget">
                <div class="clock-time" id="clock-time">--:--:--</div>
                <div class="clock-date" id="clock-date">Carregando data...</div>
            </div>

            <div class="header-actions">
                <div class="countdown-widget" id="countdown-widget" title="Tempo restante até a próxima atualização automática">
                    <span class="countdown-icon" id="countdown-icon">↻</span>
                    <span class="countdown-label">Refresh em</span>
                    <span class="countdown-seconds" id="countdown-seconds">{{ $refreshInterval ?? 30 }}s</span>
                </div>

                <div class="live-badge" id="connection-status-badge">
                    <span class="pulse-dot"></span>
                    <span id="connection-status-text">Monitoramento ativo</span>
                </div>
            </div>
        </header>

        <!-- KPI Metrics Row -->
        <section class="metrics-grid" aria-label="Indicadores operacionais">
            <article class="metric-card metric-card--clients">
                <div class="metric-header">
                    <span class="metric-label">Clientes</span>
                    <span>🏢</span>
                </div>
                <div class="metric-value-row">
                    <span class="metric-value" id="metric-clients">{{ $metrics['clients_count'] }}</span>
                </div>
                <div class="metric-note">Empresas monitoradas</div>
            </article>

            <article class="metric-card metric-card--total">
                <div class="metric-header">
                    <span class="metric-label">Total Serviços</span>
                    <span>▣</span>
                </div>
                <div class="metric-value-row">
                    <span class="metric-value" id="metric-total">{{ $metrics['total'] }}</span>
                </div>
                <div class="metric-note">Rotinas & Heartbeats</div>
            </article>

            <article class="metric-card metric-card--online">
                <div class="metric-header">
                    <span class="metric-label">Operacionais</span>
                    <span>●</span>
                </div>
                <div class="metric-value-row">
                    <span class="metric-value" id="metric-online">{{ $metrics['online'] }}</span>
                </div>
                <div class="metric-note">Dentro do prazo combinado</div>
            </article>

            <article class="metric-card metric-card--attention {{ $metrics['attention'] > 0 ? 'has-attention' : '' }}">
                <div class="metric-header">
                    <span class="metric-label">Atrasados / Falhas</span>
                    <span>▲</span>
                </div>
                <div class="metric-value-row">
                    <span class="metric-value" id="metric-attention">{{ $metrics['attention'] }}</span>
                </div>
                <div class="metric-note">Requerem ação imediata</div>
            </article>

            <article class="metric-card metric-card--backups">
                <div class="metric-header">
                    <span class="metric-label">Logs Recebidos</span>
                    <span>▤</span>
                </div>
                <div class="metric-value-row">
                    <span class="metric-value" id="metric-logs-today">{{ $metrics['logs_today'] }}</span>
                </div>
                <div class="metric-note">Arquivos gravados hoje</div>
            </article>
        </section>

        <!-- Search and Filter Bar -->
        <div class="filter-bar">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="search-input" class="search-input" placeholder="Filtrar por cliente ou serviço..." onkeyup="filterServices()">
            </div>
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all" onclick="setFilter('all')">Todos</button>
                <button class="filter-btn" data-filter="alerts" onclick="setFilter('alerts')">🔴 Com Alertas</button>
                <button class="filter-btn" data-filter="ok" onclick="setFilter('ok')">🟢 Saudáveis</button>
            </div>
        </div>

        <!-- Clients & Services Area -->
        <main class="clients-container" id="clients-container">
            @forelse($clients as $client)
                <section class="client-section" data-client-id="{{ $client->id }}" data-client-name="{{ strtolower($client->name) }}">
                    <div class="client-header">
                        <div class="client-title-row">
                            <div class="client-avatar">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
                            <div>
                                <h2 class="client-name">{{ $client->name }}</h2>
                                <span class="client-slug">{{ $client->slug }}</span>
                            </div>
                        </div>
                        <div class="client-badge-group">
                            <span class="brand-badge">{{ $client->monitoredServices->count() }} serviço(s)</span>
                        </div>
                    </div>

                    <div class="client-services-grid">
                        @forelse($client->monitoredServices as $service)
                            @php
                                $status = $service->computed_status;
                                $statusClass = match($status) {
                                    'ok' => 'status--ok',
                                    'failed' => 'status--failed',
                                    'overdue' => 'status--overdue',
                                    default => 'status--unknown',
                                };
                                $statusLabel = match($status) {
                                    'ok' => 'Operacional',
                                    'failed' => 'Falha',
                                    'overdue' => 'Atrasado',
                                    default => 'Aguardando',
                                };
                            @endphp

                            <article class="service-card {{ $status === 'failed' ? 'service-card--failed' : '' }}" data-service-name="{{ strtolower($service->name) }}" data-status="{{ $status }}">
                                <div class="service-card-head">
                                    <h3 class="service-name" title="{{ $service->name }}">{{ $service->name }}</h3>
                                    <span class="status-chip {{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>

                                <div class="service-meta-grid">
                                    <div class="meta-item">
                                        <span class="meta-label">Periodicidade</span>
                                        <span class="meta-value">{{ $service->expected_interval_minutes }} min (tol: {{ $service->grace_period_minutes }}m)</span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Último Ping</span>
                                        <span class="meta-value">{{ $service->last_ping_at ? $service->last_ping_at->format('d/m/Y H:i:s') . ' (' . $service->last_ping_at->diffForHumans() . ')' : 'Nunca' }}</span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Previsão / Prazo</span>
                                        @if($service->is_overdue)
                                            <span class="meta-value meta-value--alert">Atrasado há {{ $service->next_expected_at?->diffForHumans(null, true) }}</span>
                                        @elseif($service->next_expected_at)
                                            <span class="meta-value meta-value--highlight">{{ $service->next_expected_at->diffForHumans() }}</span>
                                        @else
                                            <span class="meta-value">-</span>
                                        @endif
                                    </div>
                                </div>

                                @if($service->last_message || $status === 'failed' || $status === 'overdue')
                                    <div class="service-alert-banner">
                                        <span>⚠️</span>
                                        <span>{{ $service->last_message ?: ($service->is_overdue ? 'Tempo de tolerância ultrapassado! Sem sinal de vida no intervalo previsto.' : 'Falha reportada pelo script.') }}</span>
                                    </div>
                                @endif

                                <div class="service-logs-area">
                                    <div class="logs-heading">
                                        <span>Últimos Logs Recebidos</span>
                                        <span>{{ $service->serviceLogs->count() }} log(s)</span>
                                    </div>

                                    @forelse($service->serviceLogs as $log)
                                        <div class="log-row">
                                            <span class="log-file" title="{{ $log->original_filename }}">{{ $log->original_filename ?: 'log.txt' }}</span>
                                            <span class="log-date">{{ $log->received_at->format('d/m H:i') }}</span>
                                            <span class="meta-value" style="font-size: 10px;">{{ number_format($log->file_size / 1024, 1) }} KB</span>
                                            <div class="log-actions">
                                                @if($log->log_excerpt)
                                                    <button class="btn-log-action" onclick="showLogModal('{{ addslashes($service->name) }}', '{{ addslashes($log->original_filename) }}', '{{ base64_encode($log->log_excerpt) }}', '{{ route('api.services.logs.download', ['service' => $service->id, 'log' => $log->id]) }}')">Preview</button>
                                                @endif
                                                <a href="{{ route('api.services.logs.download', ['service' => $service->id, 'log' => $log->id]) }}" class="btn-log-action" target="_blank" download>Baixar</a>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="empty-logs">Nenhum arquivo de log anexado recentemente.</div>
                                    @endforelse
                                </div>
                            </article>
                        @empty
                            <div class="empty-logs" style="grid-column: 1 / -1;">Nenhum serviço registrado para este cliente ainda.</div>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="empty-dashboard">
                    <h3>Nenhum cliente ou serviço cadastrado</h3>
                    <p>Cadastre clientes via <code>POST /api/clients/register</code> e envie os primeiros sinais de vida via <code>POST /api/heartbeat</code> para iniciar o monitoramento.</p>
                </div>
            @endforelse
        </main>
    </div>

    <!-- Modal Viewer -->
    <div class="modal-overlay" id="log-modal">
        <div class="modal-content">
            <div class="modal-head">
                <h4 class="modal-title" id="modal-log-title">Visualizador de Log</h4>
                <button class="modal-close" onclick="closeLogModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="log-terminal" id="modal-log-content">Carregando log...</div>
            </div>
            <div class="modal-foot">
                <a href="#" id="modal-download-btn" class="btn-log-action" style="padding: 8px 16px;" download>Baixar Arquivo Completo</a>
                <button class="btn-log-action" style="padding: 8px 16px;" onclick="closeLogModal()">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        let currentFilter = 'all';

        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateStr = now.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
            
            const timeElem = document.getElementById('clock-time');
            const dateElem = document.getElementById('clock-date');
            if (timeElem) timeElem.textContent = timeStr;
            if (dateElem) dateElem.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
        }
        setInterval(updateClock, 1000);
        updateClock();

        function setFilter(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.filter === filter);
            });
            filterServices();
        }

        function filterServices() {
            const query = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
            const clientSections = document.querySelectorAll('.client-section');

            clientSections.forEach(section => {
                const clientName = section.dataset.clientName || '';
                const serviceCards = section.querySelectorAll('.service-card');
                let visibleServicesInClient = 0;

                serviceCards.forEach(card => {
                    const serviceName = card.dataset.serviceName || '';
                    const status = card.dataset.status || '';

                    const matchesQuery = clientName.includes(query) || serviceName.includes(query);
                    let matchesFilter = true;

                    if (currentFilter === 'alerts') {
                        matchesFilter = (status === 'failed' || status === 'overdue');
                    } else if (currentFilter === 'ok') {
                        matchesFilter = (status === 'ok');
                    }

                    if (matchesQuery && matchesFilter) {
                        card.style.display = 'flex';
                        visibleServicesInClient++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                section.style.display = visibleServicesInClient > 0 ? 'block' : 'none';
            });
        }

        function showLogModal(serviceName, filename, b64Content, downloadUrl) {
            document.getElementById('modal-log-title').textContent = `${serviceName} — ${filename}`;
            try {
                document.getElementById('modal-log-content').textContent = decodeURIComponent(escape(atob(b64Content)));
            } catch (e) {
                document.getElementById('modal-log-content').textContent = atob(b64Content);
            }
            document.getElementById('modal-download-btn').href = downloadUrl;
            document.getElementById('log-modal').classList.add('active');
        }

        function closeLogModal() {
            document.getElementById('log-modal').classList.remove('active');
        }

        // Auto Refresh Polling com Contagem Regressiva & Renderização Dinâmica
        const refreshInterval = {{ $refreshInterval ?? 30 }};
        let currentCountdown = refreshInterval;

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function renderClientsList(clients) {
            const container = document.getElementById('clients-container');
            if (!container) return;

            if (!clients || clients.length === 0) {
                container.innerHTML = `
                    <div class="empty-dashboard">
                        <h3>Nenhum cliente ou serviço cadastrado</h3>
                        <p>Cadastre clientes via <code>POST /api/clients/register</code> e envie os primeiros sinais de vida via <code>POST /api/heartbeat</code> para iniciar o monitoramento.</p>
                    </div>
                `;
                return;
            }

            let html = '';
            clients.forEach(client => {
                const avatar = (client.name || 'CL').substring(0, 2).toUpperCase();
                html += `
                    <section class="client-section" data-client-id="${client.id}" data-client-name="${escapeHtml((client.name || '').toLowerCase())}">
                        <div class="client-header">
                            <div class="client-title-row">
                                <div class="client-avatar">${avatar}</div>
                                <div>
                                    <h2 class="client-name">${escapeHtml(client.name)}</h2>
                                    <span class="client-slug">${escapeHtml(client.slug)}</span>
                                </div>
                            </div>
                            <div class="client-badge-group">
                                <span class="brand-badge">${client.services ? client.services.length : 0} serviço(s)</span>
                            </div>
                        </div>

                        <div class="client-services-grid">
                `;

                if (!client.services || client.services.length === 0) {
                    html += `<div class="empty-logs" style="grid-column: 1 / -1;">Nenhum serviço registrado para este cliente ainda.</div>`;
                } else {
                    client.services.forEach(service => {
                        const status = service.computed_status || 'unknown';
                        const statusClass = status === 'ok' ? 'status--ok' : (status === 'failed' ? 'status--failed' : (status === 'overdue' ? 'status--overdue' : 'status--unknown'));
                        const statusLabel = service.status_label || (status === 'ok' ? 'Operacional' : (status === 'failed' ? 'Falha' : (status === 'overdue' ? 'Atrasado' : 'Aguardando')));
                        const isAlert = service.last_message || status === 'failed' || status === 'overdue';
                        const defaultAlertMsg = service.is_overdue ? 'Tempo de tolerância ultrapassado! Sem sinal de vida no intervalo previsto.' : 'Falha reportada pelo script.';

                        html += `
                            <article class="service-card ${status === 'failed' ? 'service-card--failed' : ''}" data-service-name="${escapeHtml((service.name || '').toLowerCase())}" data-status="${status}">
                                <div class="service-card-head">
                                    <h3 class="service-name" title="${escapeHtml(service.name)}">${escapeHtml(service.name)}</h3>
                                    <span class="status-chip ${statusClass}">${statusLabel}</span>
                                </div>

                                <div class="service-meta-grid">
                                    <div class="meta-item">
                                        <span class="meta-label">Periodicidade</span>
                                        <span class="meta-value">${service.interval_minutes} min (tol: ${service.grace_minutes}m)</span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Último Ping</span>
                                        <span class="meta-value">${escapeHtml(service.last_ping_at_formatted && service.last_ping_at_formatted !== 'Nunca' ? service.last_ping_at_formatted + ' (' + service.last_ping_at_human + ')' : 'Nunca')}</span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Previsão / Prazo</span>
                                        ${service.is_overdue ? `<span class="meta-value meta-value--alert">${escapeHtml(service.next_expected_at_human)}</span>` : `<span class="meta-value meta-value--highlight">${escapeHtml(service.next_expected_at_human)}</span>`}
                                    </div>
                                </div>

                                ${isAlert ? `
                                    <div class="service-alert-banner">
                                        <span>⚠️</span>
                                        <span>${escapeHtml(service.last_message || defaultAlertMsg)}</span>
                                    </div>
                                ` : ''}

                                <div class="service-logs-area">
                                    <div class="logs-heading">
                                        <span>Últimos Logs Recebidos</span>
                                        <span>${service.logs ? service.logs.length : 0} log(s)</span>
                                    </div>
                        `;

                        if (!service.logs || service.logs.length === 0) {
                            html += `<div class="empty-logs">Nenhum arquivo de log anexado recentemente.</div>`;
                        } else {
                            service.logs.forEach(log => {
                                const b64 = log.log_excerpt ? btoa(unescape(encodeURIComponent(log.log_excerpt))) : '';
                                html += `
                                    <div class="log-row">
                                        <span class="log-file" title="${escapeHtml(log.filename)}">${escapeHtml(log.filename || 'log.txt')}</span>
                                        <span class="log-date">${escapeHtml(log.received_at_formatted)}</span>
                                        <span class="meta-value" style="font-size: 10px;">${escapeHtml(log.file_size_formatted)}</span>
                                        <div class="log-actions">
                                            ${log.log_excerpt ? `<button class="btn-log-action" onclick="showLogModal('${escapeHtml(service.name)}', '${escapeHtml(log.filename)}', '${b64}', '${log.download_url}')">Preview</button>` : ''}
                                            <a href="${log.download_url}" class="btn-log-action" target="_blank" download>Baixar</a>
                                        </div>
                                    </div>
                                `;
                            });
                        }

                        html += `
                                </div>
                            </article>
                        `;
                    });
                }

                html += `
                        </div>
                    </section>
                `;
            });

            container.innerHTML = html;
            filterServices();
        }

        async function fetchDashboardMetrics() {
            const iconElem = document.getElementById('countdown-icon');
            if (iconElem) iconElem.classList.add('spinning');

            try {
                const res = await fetch('/api/dashboard/metrics');
                if (!res.ok) return;
                const data = await res.json();

                if (data.metrics) {
                    document.getElementById('metric-clients').textContent = data.metrics.clients_count;
                    document.getElementById('metric-total').textContent = data.metrics.total;
                    document.getElementById('metric-online').textContent = data.metrics.online;
                    document.getElementById('metric-attention').textContent = data.metrics.attention;
                    document.getElementById('metric-logs-today').textContent = data.metrics.logs_today;
                }

                if (data.clients) {
                    renderClientsList(data.clients);
                }
            } catch (e) {
                console.error('Polling error:', e);
            } finally {
                setTimeout(() => {
                    if (iconElem) iconElem.classList.remove('spinning');
                }, 600);
            }
        }

        function runCountdown() {
            currentCountdown--;
            const secondsElem = document.getElementById('countdown-seconds');
            if (secondsElem) {
                secondsElem.textContent = `${Math.max(0, currentCountdown)}s`;
            }

            if (currentCountdown <= 0) {
                currentCountdown = refreshInterval;
                fetchDashboardMetrics();
            }
        }

        setInterval(runCountdown, 1000);
    </script>
</body>
</html>
