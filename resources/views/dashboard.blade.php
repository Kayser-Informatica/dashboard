<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Painel de monitoramento operacional de sistemas e backups em tempo real.">
    <title>Systems Control | Painel Operacional</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0c1219;
            --bg-surface: #141c26;
            --bg-surface-elevated: #1b2634;
            --bg-card-head: #182230;
            --bg-meta: #101722;
            
            --ink: #f0f6fc;
            --ink-muted: #8b99a8;
            --ink-subtle: #576575;
            --line: #223041;
            --line-light: #2c3e53;

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

            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 18px;
            --shadow-card: 0 10px 30px -10px rgba(0, 0, 0, 0.5), 0 0 1px 1px rgba(255, 255, 255, 0.05);
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: var(--bg-base);
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Fullscreen monitor layout */
        .app-shell {
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px 28px 16px;
            gap: 16px;
            max-width: 1920px;
            margin: 0 auto;
        }

        /* Top Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line);
            flex-shrink: 0;
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
            background: linear-gradient(135deg, #0d9488 0%, #0284c7 100%);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 17px;
            color: #ffffff;
            box-shadow: 0 0 20px rgba(13, 148, 136, 0.4);
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

        /* Center Clock in Monitor */
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
            font-size: 20px;
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

        /* Header Right Controls */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .live-badge {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 15px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: var(--bg-surface);
            border: 1px solid var(--line);
            transition: all 0.3s ease;
        }

        .live-badge--online {
            color: #34d399;
            border-color: rgba(52, 211, 153, 0.3);
            background: rgba(16, 185, 129, 0.08);
        }

        .live-badge--syncing {
            color: #38bdf8;
            border-color: rgba(56, 189, 248, 0.3);
            background: rgba(14, 165, 233, 0.08);
        }

        .live-badge--error {
            color: #fb7185;
            border-color: rgba(251, 113, 133, 0.35);
            background: rgba(244, 63, 94, 0.12);
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

        @keyframes pulse-ring {
            0% { transform: scale(0.6); opacity: 0.8; }
            70% { transform: scale(1.8); opacity: 0; }
            100% { transform: scale(1.8); opacity: 0; }
        }

        .sync-progress-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--ink-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .btn-fullscreen {
            background: var(--bg-surface);
            border: 1px solid var(--line);
            color: var(--ink-muted);
            border-radius: var(--radius-sm);
            padding: 7px 11px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-fullscreen:hover {
            color: #ffffff;
            border-color: var(--line-light);
            background: var(--bg-surface-elevated);
        }

        /* Metrics Row */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            flex-shrink: 0;
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
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink-muted);
        }

        .metric-icon {
            font-size: 14px;
            color: var(--ink-subtle);
        }

        .metric-value-row {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-top: 8px;
        }

        .metric-value {
            font-size: 38px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #ffffff;
            font-variant-numeric: tabular-nums;
        }

        .metric-card--online .metric-value { color: #34d399; }
        .metric-card--attention .metric-value { color: #fbbf24; }
        .metric-card--attention.has-attention .metric-value { color: #f87171; }
        .metric-card--backups .metric-value { color: #38bdf8; }

        .metric-note {
            font-size: 12px;
            color: var(--ink-muted);
            margin-top: 6px;
        }

        /* Systems Section */
        .systems-wrapper {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .section-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .section-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #ffffff;
        }

        .section-badge {
            background: var(--bg-surface);
            border: 1px solid var(--line);
            color: var(--ink-muted);
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
        }

        .section-meta-text {
            font-size: 12px;
            color: var(--ink-subtle);
        }

        /* Systems Grid Viewport with Scroll */
        .systems-scroll-area {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding-right: 4px;
        }

        /* Custom subtle scrollbar */
        .systems-scroll-area::-webkit-scrollbar {
            width: 6px;
        }
        .systems-scroll-area::-webkit-scrollbar-track {
            background: var(--bg-base);
        }
        .systems-scroll-area::-webkit-scrollbar-thumb {
            background: var(--line-light);
            border-radius: 4px;
        }

        .systems-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(460px, 1fr));
            gap: 16px;
            padding-bottom: 8px;
        }

        /* System Card */
        .system-card {
            background: var(--bg-surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
            transition: border-color 0.2s, transform 0.2s;
        }

        .system-card:hover {
            border-color: var(--line-light);
        }

        .system-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            background: var(--bg-card-head);
            border-bottom: 1px solid var(--line);
        }

        .system-identity {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .system-name {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .system-slug {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--ink-muted);
            background: var(--bg-base);
            padding: 2px 7px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--line);
            white-space: nowrap;
        }

        /* Status badges */
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .status-chip::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: currentColor;
        }

        .status--ok, .status--success {
            color: #34d399;
            background: var(--emerald-bg);
            border: 1px solid rgba(52, 211, 153, 0.25);
        }

        .status--failed {
            color: #f87171;
            background: var(--rose-bg);
            border: 1px solid rgba(248, 113, 113, 0.3);
            animation: pulse-border 1.8s infinite;
        }

        @keyframes pulse-border {
            0%, 100% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0.4); }
            50% { box-shadow: 0 0 0 4px rgba(248, 113, 113, 0); }
        }

        .status--unknown, .status--warning, .status--received {
            color: #fbbf24;
            background: var(--amber-bg);
            border: 1px solid rgba(251, 191, 36, 0.25);
        }

        /* Meta Grid */
        .system-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 12px 20px;
            background: var(--bg-meta);
            border-bottom: 1px solid var(--line);
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
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

        .meta-ip {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: #38bdf8;
        }

        /* Healthcheck message alert banner */
        .health-message-banner {
            padding: 8px 20px;
            font-size: 11px;
            background: rgba(244, 63, 94, 0.1);
            border-bottom: 1px solid rgba(244, 63, 94, 0.2);
            color: #fca5a5;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Backups Area */
        .backups-area {
            padding: 14px 20px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .backups-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .backups-heading h4 {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--ink-muted);
        }

        .backups-count {
            font-size: 11px;
            color: var(--ink-subtle);
            font-family: 'JetBrains Mono', monospace;
        }

        .backups-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .backup-row {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            align-items: center;
            gap: 12px;
            padding: 7px 10px;
            background: var(--bg-card-head);
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            font-size: 11px;
        }

        .backup-file {
            font-family: 'JetBrains Mono', monospace;
            color: #e2e8f0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .backup-date {
            color: var(--ink-muted);
            white-space: nowrap;
        }

        .backup-size {
            color: var(--ink-muted);
            font-family: 'JetBrains Mono', monospace;
            white-space: nowrap;
        }

        .backup-tag {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .backup-tag--success {
            color: #34d399;
            background: rgba(16, 185, 129, 0.15);
        }

        .backup-tag--failed {
            color: #f87171;
            background: rgba(244, 63, 94, 0.15);
        }

        .backup-tag--warning, .backup-tag--received {
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.15);
        }

        .empty-backups {
            padding: 12px;
            text-align: center;
            color: var(--ink-subtle);
            font-size: 11px;
            background: var(--bg-card-head);
            border: 1px dashed var(--line);
            border-radius: var(--radius-sm);
        }

        /* Empty Dashboard State */
        .empty-dashboard {
            padding: 60px 20px;
            text-align: center;
            background: var(--bg-surface);
            border: 1px dashed var(--line);
            border-radius: var(--radius-lg);
            margin: auto 0;
        }

        .empty-dashboard h3 {
            margin: 0 0 8px;
            font-size: 18px;
            color: #ffffff;
        }

        .empty-dashboard p {
            margin: 0 auto;
            max-width: 440px;
            color: var(--ink-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        /* Bottom Status Footer */
        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid var(--line);
            color: var(--ink-subtle);
            font-size: 11px;
            flex-shrink: 0;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-right {
            display: flex;
            align-items: center;
            gap: 16px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Responsive breakpoints */
        @media (max-width: 1200px) {
            .metrics-grid { grid-template-columns: repeat(2, 1fr); }
            .systems-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            html, body { overflow: auto; height: auto; }
            .app-shell { height: auto; }
            .header { flex-direction: column; align-items: flex-start; }
            .clock-widget { width: 100%; justify-content: space-between; }
            .metrics-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <!-- Header for Monitor -->
        <header class="header">
            <div class="brand-group">
                <div class="brand-logo">SC</div>
                <div>
                    <div class="brand-title">
                        Systems Control
                        <span class="brand-badge">NOC</span>
                    </div>
                    <div class="brand-sub">Painel Operacional de Monitoramento</div>
                </div>
            </div>

            <div class="clock-widget">
                <div class="clock-time" id="clock-time">--:--:--</div>
                <div class="clock-date" id="clock-date">Carregando data...</div>
            </div>

            <div class="header-actions">
                <div class="sync-progress-wrap">
                    <span id="sync-timer-label">Auto-refresh 15s</span>
                </div>
                <div class="live-badge live-badge--online" id="connection-status-badge">
                    <span class="pulse-dot"></span>
                    <span id="connection-status-text">Monitoramento ativo</span>
                </div>
                <button class="btn-fullscreen" id="btn-fullscreen" title="Alternar Tela Cheia" onclick="toggleFullscreen()">
                    ⛶
                </button>
            </div>
        </header>

        <!-- KPI Metrics Row -->
        <section class="metrics-grid" aria-label="Indicadores operacionais">
            <article class="metric-card metric-card--total">
                <div class="metric-header">
                    <span class="metric-label">Sistemas monitorados</span>
                    <span class="metric-icon">▣</span>
                </div>
                <div class="metric-value-row">
                    <span class="metric-value" id="metric-total">{{ $metrics['total'] }}</span>
                </div>
                <div class="metric-note">Serviços registrados</div>
            </article>

            <article class="metric-card metric-card--online">
                <div class="metric-header">
                    <span class="metric-label">Operacionais</span>
                    <span class="metric-icon">●</span>
                </div>
                <div class="metric-value-row">
                    <span class="metric-value" id="metric-online">{{ $metrics['online'] }}</span>
                </div>
                <div class="metric-note">Healthcheck status OK</div>
            </article>

            <article class="metric-card metric-card--attention {{ $metrics['attention'] > 0 ? 'has-attention' : '' }}">
                <div class="metric-header">
                    <span class="metric-label">Atenção / Falhas</span>
                    <span class="metric-icon">▲</span>
                </div>
                <div class="metric-value-row">
                    <span class="metric-value" id="metric-attention">{{ $metrics['attention'] }}</span>
                </div>
                <div class="metric-note">Falhas ou sem resposta</div>
            </article>

            <article class="metric-card metric-card--backups">
                <div class="metric-header">
                    <span class="metric-label">Backups hoje</span>
                    <span class="metric-icon">▤</span>
                </div>
                <div class="metric-value-row">
                    <span class="metric-value" id="metric-backups">{{ $metrics['backups'] }}</span>
                </div>
                <div class="metric-note">Logs gravados hoje</div>
            </article>
        </section>

        <!-- Systems & Backups Section -->
        <section class="systems-wrapper" aria-labelledby="systems-title">
            <div class="section-bar">
                <div class="section-title-group">
                    <h2 class="section-title" id="systems-title">Status dos Sistemas e Backups</h2>
                    <span class="section-badge" id="systems-count-badge">{{ $systems->count() }} sistema(s)</span>
                </div>
                <div class="section-meta-text">
                    Sincronização em tempo real via API
                </div>
            </div>

            <div class="systems-scroll-area">
                <div class="systems-grid" id="systems-container">
                    @if ($systems->isEmpty())
                        <div class="empty-dashboard" id="empty-state-card" style="grid-column: 1 / -1;">
                            <h3>Nenhum sistema recebido ainda</h3>
                            <p>Envie o primeiro healthcheck pela API para que os indicadores e backups apareçam automaticamente nesta tela.</p>
                        </div>
                    @else
                        @foreach ($systems as $system)
                            <article class="system-card" id="system-card-{{ $system->slug }}" data-slug="{{ $system->slug }}">
                                <div class="system-card-head">
                                    <div class="system-identity">
                                        <h3 class="system-name">{{ $system->name }}</h3>
                                        <span class="system-slug">/{{ $system->slug }}</span>
                                    </div>
                                    <span class="status-chip status--{{ $system->last_health_status }}">
                                        {{ match ($system->last_health_status) { 'ok' => 'Operacional', 'failed' => 'Falha', default => 'Sem dados' } }}
                                    </span>
                                </div>

                                @if ($system->last_health_status === 'failed' && $system->last_health_message)
                                    <div class="health-message-banner">
                                        <span>⚠️ {{ $system->last_health_message }}</span>
                                    </div>
                                @endif

                                <div class="system-meta-grid">
                                    <div class="meta-item">
                                        <span class="meta-label">IP Externo</span>
                                        <span class="meta-value meta-ip">{{ $system->external_ip ?? 'Não informado' }}</span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Último healthcheck</span>
                                        <span class="meta-value">{{ $system->last_health_at?->diffForHumans() ?? 'Aguardando' }}</span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Último backup</span>
                                        <span class="meta-value">{{ $system->last_backup_at?->diffForHumans() ?? 'Nenhum recebido' }}</span>
                                    </div>
                                </div>

                                <div class="backups-area">
                                    <div class="backups-heading">
                                        <h4>Backups recentes</h4>
                                        <span class="backups-count">{{ $system->backup_logs_count }} registro(s)</span>
                                    </div>
                                    @if ($system->backupLogs->isEmpty())
                                        <div class="empty-backups">Nenhum log de backup recebido.</div>
                                    @else
                                        <div class="backups-list">
                                            @foreach ($system->backupLogs as $backup)
                                                <div class="backup-row">
                                                    <span class="backup-file" title="{{ $backup->original_filename }}">{{ $backup->original_filename }}</span>
                                                    <span class="backup-date">{{ $backup->received_at->format('d/m H:i') }}</span>
                                                    <span class="backup-size">{{ number_format($backup->file_size / 1024, 1, ',', '.') }} KB</span>
                                                    <span class="backup-tag backup-tag--{{ $backup->status }}">{{ $backup->status }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    @endif
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-left">
                <span>Systems Control</span>
                <span>•</span>
                <span>Painel Contínuo para Monitor NOC</span>
            </div>
            <div class="footer-right">
                <span id="last-sync-time">Última checagem: {{ now()->format('H:i:s') }}</span>
                <span>•</span>
                <span>Intervalo: 15s</span>
            </div>
        </footer>
    </div>

    <!-- Reactive Live Engine -->
    <script>
        const POLL_INTERVAL_SECONDS = 15;
        let countdown = POLL_INTERVAL_SECONDS;
        let isFetching = false;

        // Digital Clock
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('pt-BR', { hour12: false });
            const dateStr = now.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
            
            const clockTimeEl = document.getElementById('clock-time');
            const clockDateEl = document.getElementById('clock-date');
            
            if (clockTimeEl) clockTimeEl.textContent = timeStr;
            if (clockDateEl) clockDateEl.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
        }

        setInterval(updateClock, 1000);
        updateClock();

        // Fullscreen Mode Toggle
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.warn(`Fullscreen error: ${err.message}`);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // Connection Badge State
        function setConnectionStatus(state, message) {
            const badge = document.getElementById('connection-status-badge');
            const text = document.getElementById('connection-status-text');
            if (!badge || !text) return;

            badge.className = 'live-badge';
            if (state === 'online') {
                badge.classList.add('live-badge--online');
                text.textContent = message || 'Monitoramento ativo';
            } else if (state === 'syncing') {
                badge.classList.add('live-badge--syncing');
                text.textContent = message || 'Atualizando dados...';
            } else if (state === 'error') {
                badge.classList.add('live-badge--error');
                text.textContent = message || 'Conexão perdida (reconectando...)';
            }
        }

        // Render Systems HTML from API Data
        function renderSystems(systems) {
            const container = document.getElementById('systems-container');
            const countBadge = document.getElementById('systems-count-badge');
            if (!container) return;

            if (countBadge) {
                countBadge.textContent = `${systems.length} sistema(s)`;
            }

            if (!systems || systems.length === 0) {
                container.innerHTML = `
                    <div class="empty-dashboard" id="empty-state-card" style="grid-column: 1 / -1;">
                        <h3>Nenhum sistema recebido ainda</h3>
                        <p>Envie o primeiro healthcheck pela API para que os indicadores e backups apareçam automaticamente nesta tela.</p>
                    </div>
                `;
                return;
            }

            let html = '';
            systems.forEach(sys => {
                const statusClass = sys.last_health_status || 'unknown';
                const statusLabel = sys.last_health_status_label || (statusClass === 'ok' ? 'Operacional' : (statusClass === 'failed' ? 'Falha' : 'Sem dados'));
                
                let backupsHtml = '';
                if (!sys.backup_logs || sys.backup_logs.length === 0) {
                    backupsHtml = `<div class="empty-backups">Nenhum log de backup recebido.</div>`;
                } else {
                    backupsHtml = `<div class="backups-list">`;
                    sys.backup_logs.forEach(bk => {
                        backupsHtml += `
                            <div class="backup-row">
                                <span class="backup-file" title="${escapeHtml(bk.original_filename)}">${escapeHtml(bk.original_filename)}</span>
                                <span class="backup-date">${escapeHtml(bk.received_at_formatted)}</span>
                                <span class="backup-size">${escapeHtml(bk.file_size_formatted)}</span>
                                <span class="backup-tag backup-tag--${escapeHtml(bk.status)}">${escapeHtml(bk.status)}</span>
                            </div>
                        `;
                    });
                    backupsHtml += `</div>`;
                }

                let messageBanner = '';
                if (statusClass === 'failed' && sys.last_health_message) {
                    messageBanner = `
                        <div class="health-message-banner">
                            <span>⚠️ ${escapeHtml(sys.last_health_message)}</span>
                        </div>
                    `;
                }

                html += `
                    <article class="system-card" id="system-card-${escapeHtml(sys.slug)}" data-slug="${escapeHtml(sys.slug)}">
                        <div class="system-card-head">
                            <div class="system-identity">
                                <h3 class="system-name">${escapeHtml(sys.name)}</h3>
                                <span class="system-slug">/${escapeHtml(sys.slug)}</span>
                            </div>
                            <span class="status-chip status--${escapeHtml(statusClass)}">
                                ${escapeHtml(statusLabel)}
                            </span>
                        </div>

                        ${messageBanner}

                        <div class="system-meta-grid">
                            <div class="meta-item">
                                <span class="meta-label">IP Externo</span>
                                <span class="meta-value meta-ip">${escapeHtml(sys.external_ip || 'Não informado')}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Último healthcheck</span>
                                <span class="meta-value">${escapeHtml(sys.last_health_at_human || 'Aguardando')}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Último backup</span>
                                <span class="meta-value">${escapeHtml(sys.last_backup_at_human || 'Nenhum recebido')}</span>
                            </div>
                        </div>

                        <div class="backups-area">
                            <div class="backups-heading">
                                <h4>Backups recentes</h4>
                                <span class="backups-count">${sys.backup_logs_count || 0} registro(s)</span>
                            </div>
                            ${backupsHtml}
                        </div>
                    </article>
                `;
            });

            container.innerHTML = html;
        }

        // Helper to escape HTML strings
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Fetch Dashboard Data from API
        async function fetchDashboardData() {
            if (isFetching) return;
            isFetching = true;

            try {
                const response = await fetch('/api/dashboard/metrics', {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                // Update Metrics Values
                if (data.metrics) {
                    const totalEl = document.getElementById('metric-total');
                    const onlineEl = document.getElementById('metric-online');
                    const attentionEl = document.getElementById('metric-attention');
                    const backupsEl = document.getElementById('metric-backups');
                    const attentionCard = document.querySelector('.metric-card--attention');

                    if (totalEl) totalEl.textContent = data.metrics.total ?? 0;
                    if (onlineEl) onlineEl.textContent = data.metrics.online ?? 0;
                    if (attentionEl) attentionEl.textContent = data.metrics.attention ?? 0;
                    if (backupsEl) backupsEl.textContent = data.metrics.backups ?? 0;

                    if (attentionCard) {
                        if ((data.metrics.attention ?? 0) > 0) {
                            attentionCard.classList.add('has-attention');
                        } else {
                            attentionCard.classList.remove('has-attention');
                        }
                    }
                }

                // Update Systems Grid
                if (data.systems) {
                    renderSystems(data.systems);
                }

                // Update Last Sync
                const lastSyncEl = document.getElementById('last-sync-time');
                if (lastSyncEl) {
                    const now = new Date();
                    lastSyncEl.textContent = `Última checagem: ${now.toLocaleTimeString('pt-BR', { hour12: false })}`;
                }

                setConnectionStatus('online', 'Monitoramento ativo');
            } catch (err) {
                console.error('Falha ao sincronizar dashboard:', err);
                setConnectionStatus('error', 'Conexão perdida (reconectando...)');
            } finally {
                isFetching = false;
                countdown = POLL_INTERVAL_SECONDS;
            }
        }

        // Countdown Timer & Auto-Polling Loop
        setInterval(() => {
            countdown--;
            const timerLabel = document.getElementById('sync-timer-label');
            if (timerLabel) {
                timerLabel.textContent = `Auto-refresh ${countdown}s`;
            }

            if (countdown <= 0) {
                fetchDashboardData();
            }
        }, 1000);
    </script>
</body>
</html>
