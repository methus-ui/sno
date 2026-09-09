@extends('layouts.admin.app')
@section('title',\App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value??translate('messages.dashboard'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* ===== Modern Dashboard Redesign ===== */
        :root {
            --dash-primary: #1e293b;
            --dash-accent: #3b82f6;
            --dash-accent-light: #eff6ff;
            --dash-success: #10b981;
            --dash-warning: #f59e0b;
            --dash-danger: #ef4444;
            --dash-info: #06b6d4;
            --dash-purple: #8b5cf6;
            --dash-text: #1e293b;
            --dash-text-muted: #64748b;
            --dash-border: #e2e8f0;
            --dash-bg: #f8fafc;
            --dash-card: #ffffff;
            --dash-radius: 12px;
            --dash-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --dash-shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --dash-shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
        }

        .dash-content {
            padding: 20px 24px;
        }
        @media (max-width: 767px) {
            .dash-content { padding: 16px; }
        }

        /* Page Header */
        .dash-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }
        .dash-page-header .dash-title-area {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .dash-page-header .dash-module-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: var(--dash-accent-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dash-page-header .dash-module-icon img { width: 28px; height: 28px; object-fit: contain; }
        .dash-page-header h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dash-text);
            margin: 0;
            line-height: 1.3;
        }
        .dash-page-header p {
            font-size: 13px;
            color: var(--dash-text-muted);
            margin: 0;
        }
        .dash-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Live Clock - Compact */
        .dash-clock {
            background: var(--dash-primary);
            color: #fff;
            border-radius: 10px;
            padding: 10px 18px;
            text-align: center;
            min-width: 150px;
        }
        .dash-clock .clock-time {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            font-family: 'SF Mono', 'Courier New', monospace;
            line-height: 1.3;
        }
        .dash-clock .clock-date {
            font-size: 10px;
            opacity: 0.7;
            margin-top: 2px;
        }

        /* Zone Select */
        .dash-zone-select {
            min-width: 220px;
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            color: var(--dash-text);
            background: #fff;
            outline: none;
            transition: border-color 0.2s;
        }
        .dash-zone-select:focus { border-color: var(--dash-accent); }

        /* KPI Cards Row */
        .dash-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 991px) { .dash-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 575px) { .dash-kpi-grid { grid-template-columns: 1fr; } }

        .dash-kpi {
            background: var(--dash-card);
            border-radius: var(--dash-radius);
            padding: 20px;
            box-shadow: var(--dash-shadow);
            border: 1px solid var(--dash-border);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .dash-kpi:hover {
            box-shadow: var(--dash-shadow-md);
            transform: translateY(-2px);
        }
        .dash-kpi .kpi-content { flex: 1; }
        .dash-kpi .kpi-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--dash-text-muted);
            margin-bottom: 6px;
        }
        .dash-kpi .kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--dash-text);
            line-height: 1.2;
            margin-bottom: 6px;
        }
        .dash-kpi .kpi-sub {
            font-size: 12px;
            color: var(--dash-text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .dash-kpi .kpi-sub .trend-up { color: var(--dash-success); font-weight: 600; }
        .dash-kpi .kpi-sub .trend-down { color: var(--dash-danger); font-weight: 600; }
        .dash-kpi .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .kpi-icon.blue { background: #eff6ff; color: #3b82f6; }
        .kpi-icon.purple { background: #f5f3ff; color: #8b5cf6; }
        .kpi-icon.green { background: #ecfdf5; color: #10b981; }
        .kpi-icon.amber { background: #fffbeb; color: #f59e0b; }

        /* HR Management Bar */
        .dash-hr-bar {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: var(--dash-radius);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
            box-shadow: var(--dash-shadow);
        }
        .dash-hr-bar .hr-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dash-hr-bar .hr-title h6 {
            font-size: 14px;
            font-weight: 600;
            margin: 0;
            color: var(--dash-text);
        }
        .dash-hr-bar .hr-title small { color: var(--dash-text-muted); font-size: 12px; }
        .dash-hr-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .dash-hr-actions .hr-btn {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .hr-btn.hr-blue { background: #eff6ff; color: #3b82f6; }
        .hr-btn.hr-blue:hover { background: #3b82f6; color: #fff; text-decoration: none; }
        .hr-btn.hr-green { background: #ecfdf5; color: #10b981; }
        .hr-btn.hr-green:hover { background: #10b981; color: #fff; text-decoration: none; }
        .hr-btn.hr-amber { background: #fffbeb; color: #d97706; }
        .hr-btn.hr-amber:hover { background: #f59e0b; color: #fff; text-decoration: none; }
        .hr-btn.hr-pink { background: #fce7f3; color: #C2185B; position: relative; }
        .hr-btn.hr-pink:hover { background: #C2185B; color: #fff; text-decoration: none; }

        /* Delivery Boy Cards */
        .dash-dm-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 1199px) { .dash-dm-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 575px) { .dash-dm-grid { grid-template-columns: 1fr; } }

        .dash-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: var(--dash-radius);
            box-shadow: var(--dash-shadow);
            overflow: hidden;
            transition: box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .dash-card:hover { box-shadow: var(--dash-shadow-md); }

        .dash-card-header {
            padding: 14px 18px;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid var(--dash-border);
        }
        .dash-card-header.accent { background: var(--dash-accent-light); color: var(--dash-accent); }
        .dash-card-header.green-bg { background: #ecfdf5; color: #059669; }
        .dash-card-header.amber-bg { background: #fffbeb; color: #d97706; }
        .dash-card-header.red-bg { background: #fef2f2; color: #dc2626; }

        .dash-card-body { padding: 18px; flex: 1; }
        .dash-card-body.p-0 { padding: 0; }

        /* DM Profile Mini Card */
        .dm-profile-mini { text-align: center; }
        .dm-profile-mini .dm-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--dash-border);
            margin-bottom: 10px;
        }
        .dm-profile-mini .dm-avatar.ring-green { border-color: var(--dash-success); }
        .dm-profile-mini .dm-avatar.ring-amber { border-color: var(--dash-warning); }
        .dm-profile-mini h6 {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 2px;
            color: var(--dash-text);
        }
        .dm-profile-mini .dm-phone {
            font-size: 12px;
            color: var(--dash-text-muted);
            margin-bottom: 12px;
        }
        .dm-stats-row {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 12px;
        }
        .dm-stat { text-align: center; }
        .dm-stat .val {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
        }
        .dm-stat .lbl {
            font-size: 10px;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .dm-stat .val.text-green { color: var(--dash-success); }
        .dm-stat .val.text-blue { color: var(--dash-accent); }
        .dm-stat .val.text-amber { color: var(--dash-warning); }

        .dash-btn-outline {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid var(--dash-border);
            background: transparent;
            color: var(--dash-text-muted);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .dash-btn-outline:hover {
            border-color: var(--dash-accent);
            color: var(--dash-accent);
            background: var(--dash-accent-light);
            text-decoration: none;
        }

        /* Top Performers List */
        .performer-list { list-style: none; padding: 0; margin: 0; }
        .performer-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }
        .performer-list li:last-child { border-bottom: none; }
        .performer-list li:hover { background: #f8fafc; }
        .performer-rank {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .performer-rank.gold { background: #fef3c7; color: #b45309; }
        .performer-rank.silver { background: #f1f5f9; color: #475569; }
        .performer-rank.bronze { background: #fef2f2; color: #b91c1c; }
        .performer-rank.default { background: #f1f5f9; color: #94a3b8; }
        .performer-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .performer-info { flex: 1; min-width: 0; }
        .performer-info h6 {
            font-size: 13px;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--dash-text);
        }
        .performer-info small {
            font-size: 11px;
            color: var(--dash-text-muted);
        }

        /* Sticky Notes */
        .note-card {
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
            font-size: 13px;
            position: relative;
        }
        .note-card .note-date {
            font-size: 10px;
            color: rgba(0,0,0,0.4);
        }
        .note-card .note-delete {
            position: absolute;
            top: 6px;
            right: 8px;
            background: none;
            border: none;
            color: rgba(0,0,0,0.3);
            cursor: pointer;
            font-size: 14px;
            padding: 0;
        }
        .note-card .note-delete:hover { color: var(--dash-danger); }

        /* Statistics Section */
        .dash-stats-section {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: var(--dash-radius);
            box-shadow: var(--dash-shadow);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .dash-stats-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            border-bottom: 1px solid var(--dash-border);
        }
        .statistics-btn-grp-new {
            display: flex;
            background: #f1f5f9;
            border-radius: 8px;
            padding: 3px;
        }
        .statistics-btn-grp-new label {
            margin: 0;
            cursor: pointer;
        }
        .statistics-btn-grp-new label span {
            display: block;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--dash-text-muted);
            transition: all 0.2s;
        }
        .statistics-btn-grp-new label input:checked + span {
            background: #fff;
            color: var(--dash-text);
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }
        .dash-stats-body { padding: 20px; }

        /* Stat Summary Cards (Items, Orders, Stores, Customers) */
        .dash-stat-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }
        @media (max-width: 767px) { .dash-stat-summary-grid { grid-template-columns: repeat(2, 1fr); } }

        .dash-stat-summary {
            background: #f8fafc;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            border: 1px solid #f1f5f9;
            transition: all 0.2s;
        }
        .dash-stat-summary:hover {
            background: #fff;
            border-color: var(--dash-accent);
            box-shadow: 0 2px 8px rgba(59,130,246,0.08);
        }
        .dash-stat-summary img {
            width: 32px;
            height: 32px;
            object-fit: contain;
            margin-bottom: 10px;
            opacity: 0.8;
        }
        .dash-stat-summary .stat-name {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--dash-text-muted);
            margin-bottom: 4px;
        }
        .dash-stat-summary .stat-count {
            font-size: 24px;
            font-weight: 800;
            color: var(--dash-text);
            line-height: 1.2;
        }
        .dash-stat-summary .stat-new {
            font-size: 11px;
            color: var(--dash-text-muted);
            margin-top: 4px;
        }

        /* Order Status Grid */
        .dash-order-status-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        @media (max-width: 991px) { .dash-order-status-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 575px) { .dash-order-status-grid { grid-template-columns: 1fr; } }

        .dash-order-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
            text-decoration: none;
            transition: all 0.2s;
        }
        .dash-order-status:hover {
            background: #fff;
            border-color: var(--dash-accent);
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(59,130,246,0.06);
        }
        .dash-order-status .os-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dash-order-status .os-left img {
            width: 22px;
            height: 22px;
            object-fit: contain;
            opacity: 0.7;
        }
        .dash-order-status .os-left span {
            font-size: 13px;
            font-weight: 600;
            color: var(--dash-text);
        }
        .dash-order-status .os-count {
            font-size: 18px;
            font-weight: 800;
        }
        .os-count.blue { color: var(--dash-accent); }
        .os-count.green { color: var(--dash-success); }
        .os-count.amber { color: var(--dash-warning); }
        .os-count.red { color: var(--dash-danger); }

        /* Charts Section */
        .dash-charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 991px) { .dash-charts-grid { grid-template-columns: 1fr; } }

        /* Bottom Cards Grid */
        .dash-bottom-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 991px) { .dash-bottom-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 575px) { .dash-bottom-grid { grid-template-columns: 1fr; } }

        /* Bad Performers Table */
        .dash-table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .dash-table-modern thead th {
            background: #f8fafc;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--dash-text-muted);
            border-bottom: 1px solid var(--dash-border);
        }
        .dash-table-modern tbody td {
            padding: 12px 14px;
            font-size: 13px;
            border-bottom: 1px solid #f8fafc;
            vertical-align: middle;
        }
        .dash-table-modern tbody tr:hover td { background: #f8fafc; }
        .dash-table-modern tbody tr:last-child td { border-bottom: none; }

        .dash-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        .dash-badge.red { background: #fef2f2; color: #dc2626; }
        .dash-badge.blue { background: #eff6ff; color: #3b82f6; }
        .dash-badge.amber { background: #fffbeb; color: #d97706; }
        .dash-badge.green { background: #ecfdf5; color: #059669; }

        /* Notice Scroller */
        .dash-notice {
            background: var(--dash-accent);
            color: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .dash-notice .notice-icon { font-size: 18px; flex-shrink: 0; }
        .dash-notice .marquee-wrapper { overflow: hidden; flex: 1; }
        .dash-notice .marquee-content {
            display: inline-block;
            white-space: nowrap;
            animation: marquee 25s linear infinite;
        }
        .dash-notice .marquee-content:hover { animation-play-state: paused; }
        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* ===== Employee Dashboard ===== */
        .emp-welcome-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: var(--dash-radius);
            border: 1px solid var(--dash-border);
            padding: 28px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .emp-welcome-card .emp-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: var(--dash-shadow);
        }
        .emp-welcome-card h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--dash-text);
            margin: 0 0 4px;
        }
        .emp-welcome-card p {
            font-size: 13px;
            color: var(--dash-text-muted);
            margin: 0;
        }

        .emp-clock-card {
            background: linear-gradient(135deg, var(--dash-accent) 0%, var(--dash-purple) 100%);
            border-radius: var(--dash-radius);
            padding: 24px;
            color: #fff;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .emp-clock-card .emp-greeting {
            font-size: 13px;
            opacity: 0.8;
            margin-bottom: 6px;
        }
        .emp-clock-card .emp-time {
            font-size: 40px;
            font-weight: 700;
            letter-spacing: 2px;
            font-family: 'SF Mono', 'Courier New', monospace;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.15);
        }
        .emp-clock-card .emp-date {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 6px;
        }

        /* Active Employees Pulse Animation */
        @keyframes pulse-green {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
            }
        }

        .pulse-dot {
            animation: pulse-green 2s infinite;
        }

        /* Employee Stat Cards */
        .emp-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 991px) { .emp-stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 575px) { .emp-stat-grid { grid-template-columns: 1fr; } }

        .emp-stat-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: var(--dash-radius);
            padding: 20px;
            box-shadow: var(--dash-shadow);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .emp-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--dash-shadow-md);
        }
        .emp-stat-card .emp-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }
        .emp-stat-card .emp-stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--dash-text);
            margin-bottom: 4px;
        }
        .emp-stat-card .emp-stat-label {
            font-size: 12px;
            color: var(--dash-text-muted);
        }

        /* Attendance Section */
        .emp-attendance-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: var(--dash-radius);
            box-shadow: var(--dash-shadow);
            padding: 24px;
        }
        .emp-attendance-card h5 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .punch-btn-modern {
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .punch-btn-modern:disabled { opacity: 0.4; cursor: not-allowed; }
        .punch-btn-modern.punch-in {
            background: var(--dash-success);
            color: #fff;
        }
        .punch-btn-modern.punch-in:hover:not(:disabled) {
            background: #059669;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }
        .punch-btn-modern.punch-out {
            background: var(--dash-danger);
            color: #fff;
        }
        .punch-btn-modern.punch-out:hover:not(:disabled) {
            background: #dc2626;
            box-shadow: 0 4px 12px rgba(239,68,68,0.3);
        }

        .emp-time-display {
            text-align: center;
            padding: 8px;
        }
        .emp-time-display .time-val {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .emp-time-display .time-lbl {
            font-size: 11px;
            color: var(--dash-text-muted);
        }

        /* Quick Actions */
        .quick-action-modern {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 18px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid var(--dash-border);
            transition: all 0.2s;
            text-decoration: none;
            color: var(--dash-text);
            gap: 8px;
        }
        .quick-action-modern:hover {
            border-color: var(--dash-accent);
            background: var(--dash-accent-light);
            text-decoration: none;
            color: var(--dash-accent);
            transform: translateY(-2px);
        }
        .quick-action-modern i {
            font-size: 24px;
            color: var(--dash-accent);
        }
        .quick-action-modern span {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        /* Cart Section */
        .dash-cart-section {
            margin-bottom: 24px;
        }

        /* Fix select2 within dashboard */
        .dash-header-actions .select2-container { min-width: 220px; }

        /* Gross sale label */
        .dash-gross-sale h6 {
            font-size: 22px;
            font-weight: 800;
            color: var(--dash-text);
            margin-bottom: 2px;
        }
        .dash-gross-sale span {
            font-size: 12px;
            color: var(--dash-text-muted);
        }

        /* Chart select */
        .dash-chart-select {
            border: 1px solid var(--dash-border);
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 12px;
            color: var(--dash-text);
            background: #fff;
        }

        /* Donut chart center text */
        .dash-donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .dash-donut-center h3 {
            font-size: 22px;
            font-weight: 800;
            color: var(--dash-text);
            margin: 0;
        }
        .dash-donut-center span {
            font-size: 11px;
            color: var(--dash-text-muted);
            text-transform: capitalize;
        }

        /* Chart legend */
        .dash-chart-legend {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 14px;
        }
        .dash-chart-legend .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--dash-text-muted);
        }
        .dash-chart-legend .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid dash-content">
        @if(auth('admin')->user()->role_id == 1)
        @php
            $mod = \App\Models\Module::find(Config::get('module.current_module_id'));
        @endphp

        {{-- Page Header --}}
        <div class="dash-page-header">
            <div class="dash-title-area">
                <div class="dash-module-icon">
                    <img class="onerror-image" data-onerror-image="{{asset('/public/assets/admin/img/grocery.svg')}}"
                         src="{{$mod->icon_full_url }}" alt="img">
                </div>
                <div>
                    <h1>{{translate($mod->module_name)}} {{translate('messages.Dashboard')}}</h1>
                    <p>{{translate('Hello, Here You Can Manage Your')}} {{translate($mod->module_name)}} {{translate('orders by Zone.')}}</p>
                </div>
            </div>
            <div class="dash-header-actions">
                <div class="dash-clock">
                    <div class="clock-time" id="live-clock"></div>
                    <div class="clock-date" id="live-date"></div>
                </div>
                <select name="zone_id" class="dash-zone-select js-select2-custom fetch_data_zone_wise">
                    <option value="all">{{ translate('messages.All_Zones') }}</option>
                    @foreach(\App\Models\Zone::orderBy('name')->get() as $zone)
                        <option value="{{$zone['id']}}" {{$params['zone_id'] == $zone['id']?'selected':''}}>
                            {{$zone['name']}}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- HR Management Bar --}}
        <div class="dash-hr-bar">
            <div class="hr-title">
                <i class="tio-group" style="font-size: 20px; color: var(--dash-accent);"></i>
                <div>
                    <h6>{{translate('messages.hr_management')}}</h6>
                    <small>{{translate('messages.manage_employee_attendance_leaves')}}</small>
                </div>
            </div>
            <div class="dash-hr-actions">
                <a href="{{route('admin.attendance.index')}}" class="hr-btn hr-blue">
                    <i class="tio-chart-bar-4"></i> {{translate('messages.employee_attendance')}}
                </a>
                <a href="{{route('admin.deliveryman.attendance.index')}}" class="hr-btn hr-green">
                    <i class="tio-motorcycle"></i> {{translate('messages.deliveryman_attendance')}}
                </a>
                <a href="{{route('admin.leave.index')}}" class="hr-btn hr-amber">
                    <i class="tio-calendar-event"></i> {{translate('messages.leave_requests')}}
                </a>
                @if (auth('admin')->check() && auth('admin')->user()->role_id === 1)
                    @php
                        $pendingApplicationsCount = \App\Models\Admin::pending()->where('role_id', '!=', 1)->count() +
                                                     \App\Models\VendorEmployee::pending()->count();
                    @endphp
                    <a href="{{route('admin.employee-application.list')}}" class="hr-btn hr-pink">
                        <i class="tio-user-add"></i> {{translate('messages.employee_applications')}}
                        @if($pendingApplicationsCount > 0)
                            <span class="badge badge-danger" style="position: absolute; top: -8px; right: -8px; font-size: 11px; padding: 4px 7px; border-radius: 10px;">{{ $pendingApplicationsCount }}</span>
                        @endif
                    </a>
                @endif
            </div>
        </div>

        {{-- Active Admin Employees Widget --}}
        @php
            // Show only admins active on dashboard in last 2 minutes
            $twoMinutesAgo = now()->subMinutes(2);
            $activeAdmins = \App\Models\Admin::where('role_id', '!=', 1)
                ->where('status', 1)
                ->where('last_dashboard_activity', '>=', $twoMinutesAgo)
                ->whereNotNull('last_dashboard_activity')
                ->with('role')
                ->orderBy('f_name')
                ->get();
        @endphp

        <div id="dashboard-presence-widget" class="dash-card mb-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #86efac; {{ $activeAdmins->count() == 0 ? 'display: none;' : '' }}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 40px; height: 40px; background: #10b981; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="tio-users-switch" style="font-size: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h5 style="margin: 0; font-size: 16px; font-weight: 700; color: #047857;">
                            {{ translate('messages.active_on_dashboard') }}
                        </h5>
                        <small id="dashboard-presence-count" style="color: #059669; font-size: 12px;">
                            {{ $activeAdmins->count() }} {{ $activeAdmins->count() == 1 ? translate('messages.employee') : translate('messages.employees') }} {{ translate('messages.viewing_dashboard_now') }}
                        </small>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span class="pulse-dot" style="width: 12px; height: 12px; background: #10b981; border-radius: 50%; animation: pulse-green 2s infinite;"></span>
                        <small style="color: #047857; font-weight: 600; font-size: 12px;">{{ translate('messages.live') }}</small>
                    </div>
                    <small id="dashboard-presence-updated" style="color: #059669; font-size: 10px; opacity: 0.7;">
                        {{ translate('messages.updated_now') }}
                    </small>
                </div>
            </div>

            <div id="dashboard-presence-list" class="row g-2">
                @if($activeAdmins->count() > 0)
                    @foreach($activeAdmins as $admin)
                    <div class="col-md-3 col-sm-6">
                        <div style="background: white; border-radius: 10px; padding: 12px; border: 1px solid #d1fae5; display: flex; align-items: center; gap: 10px; transition: all 0.2s;">
                            <div style="position: relative;">
                                <img src="{{ $admin->image_full_url ?? asset('public/assets/admin/img/admin.png') }}"
                                     alt="{{ $admin->f_name }}"
                                     style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #10b981;">
                                <span style="position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; background: #10b981; border: 2px solid white; border-radius: 50%;"></span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; font-size: 13px; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                     title="{{ $admin->f_name }} {{ $admin->l_name }}">
                                    {{ $admin->f_name }} {{ $admin->l_name }}
                                </div>
                                <div style="font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                     title="{{ $admin->role?->name ?? 'Employee' }}">
                                    <i class="tio-briefcase" style="font-size: 10px;"></i> {{ $admin->role?->name ?? 'Employee' }}
                                </div>
                                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">
                                    <i class="tio-visible" style="color: #10b981;"></i> {{ translate('messages.on_dashboard') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="col-12 text-center py-3">
                        <small style="color: #059669;">{{ translate('messages.no_employees_on_dashboard') }}</small>
                    </div>
                @endif
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="dash-kpi-grid">
            <div class="dash-kpi">
                <div class="kpi-content">
                    <div class="kpi-label">{{ translate("Today's Orders") }}</div>
                    <div class="kpi-value">{{ $data['today_orders'] ?? 0 }}</div>
                    <div class="kpi-sub"><i class="tio-trending-up"></i> {{ translate('Live count') }}</div>
                </div>
                <div class="kpi-icon blue"><i class="tio-shopping-cart"></i></div>
            </div>
            <div class="dash-kpi">
                <div class="kpi-content">
                    <div class="kpi-label">{{ translate("Today's Revenue") }}</div>
                    <div class="kpi-value">{{ \App\CentralLogics\Helpers::format_currency($data['today_revenue'] ?? 0) }}</div>
                    <div class="kpi-sub"><i class="tio-checkmark-circle"></i> {{ translate('From delivered orders') }}</div>
                </div>
                <div class="kpi-icon purple"><i class="tio-dollar"></i></div>
            </div>
            <div class="dash-kpi">
                <div class="kpi-content">
                    <div class="kpi-label">{{ translate('This Month Revenue') }}</div>
                    <div class="kpi-value">{{ \App\CentralLogics\Helpers::format_currency($data['this_month_revenue'] ?? 0) }}</div>
                    <div class="kpi-sub">
                        @if(($data['revenue_growth'] ?? 0) >= 0)
                            <span class="trend-up">+{{ $data['revenue_growth'] ?? 0 }}%</span> {{ translate('vs last month') }}
                        @else
                            <span class="trend-down">{{ $data['revenue_growth'] ?? 0 }}%</span> {{ translate('vs last month') }}
                        @endif
                    </div>
                </div>
                <div class="kpi-icon green"><i class="tio-chart-bar-4"></i></div>
            </div>
            <div class="dash-kpi">
                <div class="kpi-content">
                    <div class="kpi-label">{{ translate('This Month') }}</div>
                    <div class="kpi-value">{{ $data['this_month_orders_count'] ?? 0 }}</div>
                    <div class="kpi-sub">
                        <span class="trend-up">{{ $data['this_month_delivered'] ?? 0 }} {{ translate('Delivered') }}</span>
                        &nbsp;&middot;&nbsp;
                        <span class="trend-down">{{ $data['this_month_canceled'] ?? 0 }} {{ translate('Canceled') }}</span>
                    </div>
                </div>
                <div class="kpi-icon amber"><i class="tio-receipt"></i></div>
            </div>
        </div>

        {{-- App Traffic Card --}}
        @php $trafficData = $trafficData ?? []; @endphp
        <div class="dash-kpi-grid">
            <div class="dash-kpi">
                <div class="kpi-content">
                    <div class="kpi-label">{{ translate("Today's Visitors") }}</div>
                    <div class="kpi-value">{{ $trafficData['today_visitors'] ?? 0 }}</div>
                    <div class="kpi-sub">
                        {{ translate('Yesterday') }}: {{ $trafficData['yesterday_visitors'] ?? 0 }}
                    </div>
                </div>
                <div class="kpi-icon blue"><i class="tio-user"></i></div>
            </div>
            <div class="dash-kpi">
                <div class="kpi-content">
                    <div class="kpi-label">{{ translate('App Opens Today') }}</div>
                    <div class="kpi-value">{{ $trafficData['today_app_opens'] ?? 0 }}</div>
                    <div class="kpi-sub">
                        {{ translate('Yesterday') }}: {{ $trafficData['yesterday_app_opens'] ?? 0 }}
                    </div>
                </div>
                <div class="kpi-icon green"><i class="tio-phone"></i></div>
            </div>
            <div class="dash-kpi">
                <div class="kpi-content">
                    <div class="kpi-label">{{ translate('7-Day Visitors') }}</div>
                    <div class="kpi-value">{{ $trafficData['week_visitors'] ?? 0 }}</div>
                    <div class="kpi-sub">
                        {{ translate('Avg') }}: {{ $trafficData['week_avg_visitors'] ?? 0 }}/{{ translate('day') }}
                    </div>
                </div>
                <div class="kpi-icon purple"><i class="tio-chart-bar-4"></i></div>
            </div>
            <div class="dash-kpi">
                <div class="kpi-content">
                    <div class="kpi-label">{{ translate('Peak Hour Today') }}</div>
                    <div class="kpi-value">{{ ($trafficData['today_peak_hour'] ?? '-') !== '-' ? $trafficData['today_peak_hour'] . ':00' : '-' }}</div>
                    <div class="kpi-sub">
                        {{ translate('API Requests') }}: {{ number_format($trafficData['today_requests'] ?? 0) }}
                    </div>
                </div>
                <div class="kpi-icon amber"><i class="tio-time"></i></div>
            </div>
        </div>

        {{-- Screen / Endpoint Traffic --}}
        @php
            $screenMap = [
                '/api/v1/config' => 'App Open',
                '/api/v1/config/get-zone-id' => 'Location Detection',
                '/api/v1/config/geocode-api' => 'Geocode Lookup',
                '/api/v1/banners' => 'Home Screen',
                '/api/v1/categories' => 'Categories',
                '/api/v1/items/latest' => 'Latest Items',
                '/api/v1/items/recommended' => 'Recommended Items',
                '/api/v1/items/popular' => 'Popular Items',
                '/api/v1/module' => 'Module Selection',
                '/api/v1/stores/popular' => 'Popular Stores',
                '/api/v1/stores/latest' => 'Latest Stores',
                '/api/v1/stores/get-stores/{id}' => 'Store Detail',
                '/api/v1/customer/info' => 'Profile',
                '/api/v1/customer/cart/list' => 'Cart',
                '/api/v1/customer/order/list' => 'Order History',
                '/api/v1/customer/order/place' => 'Checkout',
                '/api/v1/customer/wish-list' => 'Wishlist',
                '/api/v1/coupon/list' => 'Coupons',
                '/api/v1/advertisement/list' => 'Advertisements',
                '/api/v1/customer/notifications' => 'Notifications',
            ];

            // Get yesterday's data if today not available
            $displayTraffic = \App\Models\TrafficLog::whereDate('date', today())->first()
                ?? \App\Models\TrafficLog::orderBy('date', 'desc')->first();
            $topEps = $displayTraffic ? ($displayTraffic->top_endpoints ?? []) : [];
            $mappedScreens = [];
            foreach ($topEps as $ep => $count) {
                $label = $screenMap[$ep] ?? null;
                if ($label) {
                    $mappedScreens[$label] = ($mappedScreens[$label] ?? 0) + $count;
                }
            }
            arsort($mappedScreens);
            $mappedScreens = array_slice($mappedScreens, 0, 10, true);
            $trafficDate = $displayTraffic ? $displayTraffic->date->format('M d, Y') : '-';
        @endphp

        @if(!empty($mappedScreens))
        <div class="dash-card mb-3" style="padding: 12px 16px;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="font-size:13px;font-weight:600;color:#475569;">{{ translate('Screen Traffic') }} <span style="font-weight:400;color:#94a3b8;">({{ $trafficDate }})</span></span>
            </div>
            <div class="d-flex flex-wrap" style="gap:6px;">
                @foreach($mappedScreens as $screen => $views)
                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;background:#f1f5f9;font-size:12px;color:#334155;">
                    {{ $screen }} <strong style="color:#3b82f6;">{{ number_format($views) }}</strong>
                </span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Delivery Boy Cards Row --}}
        <div class="dash-dm-grid">
            {{-- Delivery Boy of the Day --}}
            <div class="dash-card">
                <div class="dash-card-header green-bg">
                    <i class="tio-flash"></i> {{ translate('Delivery Boy of the Day') }}
                </div>
                <div class="dash-card-body">
                    @if(isset($data['delivery_boy_of_day']) && $data['delivery_boy_of_day'])
                        @php $dm_day = $data['delivery_boy_of_day']; @endphp
                        <div class="dm-profile-mini">
                            <img class="dm-avatar ring-green"
                                 src="{{ $dm_day->image_full_url ?? asset('public/assets/admin/img/delivery_boy_map.png') }}"
                                 alt="{{ $dm_day->f_name }}">
                            <h6>{{ $dm_day->f_name }} {{ $dm_day->l_name }}</h6>
                            <div class="dm-phone">{{ $dm_day->phone }}</div>
                            <div class="dm-stats-row">
                                <div class="dm-stat">
                                    <div class="val text-green">{{ $dm_day->orders_count ?? 0 }}</div>
                                    <div class="lbl">{{ translate('Delivered') }}</div>
                                </div>
                                <div class="dm-stat">
                                    <div class="val text-blue">{{ $dm_day->today_order_count ?? 0 }}</div>
                                    <div class="lbl">{{ translate('Orders') }}</div>
                                </div>
                                <div class="dm-stat">
                                    <div class="val text-amber">{{ \App\Models\DeliveryTrackingStat::formatDuration($dm_day->today_idle_seconds ?? 0) }}</div>
                                    <div class="lbl">{{ translate('Idle') }}</div>
                                </div>
                            </div>
                            <a href="{{ route('admin.users.delivery-man.preview', $dm_day->id) }}" class="dash-btn-outline">
                                {{ translate('View Profile') }}
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <img src="{{ asset('public/assets/admin/img/delivery-man.png') }}" alt="" style="width: 50px; opacity: 0.3;">
                            <p style="color: var(--dash-text-muted); font-size: 12px; margin-top: 10px;">{{ translate('No tracking data today') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Delivery Boy of the Month --}}
            <div class="dash-card">
                <div class="dash-card-header amber-bg">
                    <i class="tio-star"></i> {{ translate('Delivery Boy of the Month') }}
                </div>
                <div class="dash-card-body">
                    @if(isset($data['delivery_boy_of_month']) && $data['delivery_boy_of_month'])
                        @php $dm = $data['delivery_boy_of_month']; @endphp
                        <div class="dm-profile-mini">
                            <img class="dm-avatar ring-amber"
                                 src="{{ $dm->image_full_url ?? asset('public/assets/admin/img/delivery_boy_map.png') }}"
                                 alt="{{ $dm->f_name }}">
                            <h6>{{ $dm->f_name }} {{ $dm->l_name }}</h6>
                            <div class="dm-phone">{{ $dm->phone }}</div>
                            <div class="dm-stats-row">
                                <div class="dm-stat">
                                    <div class="val text-green">{{ $dm->orders_count }}</div>
                                    <div class="lbl">{{ translate('Deliveries') }}</div>
                                </div>
                                <div class="dm-stat">
                                    <div class="val text-blue">{{ number_format($dm->avg_rating ?? 0, 1) }}</div>
                                    <div class="lbl">{{ translate('Rating') }}</div>
                                </div>
                            </div>
                            <a href="{{ route('admin.users.delivery-man.preview', $dm->id) }}" class="dash-btn-outline">
                                {{ translate('View Profile') }}
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <img src="{{ asset('public/assets/admin/img/delivery-man.png') }}" alt="" style="width: 50px; opacity: 0.3;">
                            <p style="color: var(--dash-text-muted); font-size: 12px; margin-top: 10px;">{{ translate('No deliveries this month yet') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Top Performers --}}
            <div class="dash-card">
                <div class="dash-card-header accent">
                    <i class="tio-bike"></i> {{ translate('Top Performers') }}
                </div>
                <div class="dash-card-body p-0">
                    <ul class="performer-list">
                        @forelse($data['top_deliveryman']->take(4) ?? [] as $index => $dm)
                        <li>
                            <span class="performer-rank {{ $index == 0 ? 'gold' : ($index == 1 ? 'silver' : ($index == 2 ? 'bronze' : 'default')) }}">
                                {{ $index + 1 }}
                            </span>
                            <img class="performer-avatar"
                                 src="{{ $dm->image_full_url ?? asset('public/assets/admin/img/delivery_boy_map.png') }}" alt="">
                            <div class="performer-info">
                                <h6>{{ $dm->f_name }} {{ Str::limit($dm->l_name, 1, '.') }}</h6>
                                <small>{{ $dm->orders_count }} {{ translate('orders') }}</small>
                            </div>
                        </li>
                        @empty
                        <li style="justify-content: center; color: var(--dash-text-muted); font-size: 13px; padding: 20px;">
                            {{ translate('No data') }}
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Sticky Notes --}}
            <div class="dash-card">
                <div class="dash-card-header" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="tio-bookmark-outlined"></i> {{ translate('My Notes') }}
                    </div>
                    <button type="button" style="background: var(--dash-accent); color: #fff; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; font-size: 14px;" onclick="addNewNote()">
                        <i class="tio-add"></i>
                    </button>
                </div>
                <div class="dash-card-body" id="sticky-notes-container" style="max-height: 280px; overflow-y: auto;">
                </div>
            </div>
        </div>

        {{-- Bad Performers --}}
        @if(isset($data['bad_performers']) && count($data['bad_performers']) > 0)
        <div class="dash-card" style="margin-bottom: 24px;">
            <div class="dash-card-header red-bg" style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="tio-warning-outlined"></i> {{ translate('Needs Improvement - Last 7 Days') }}
                </div>
                <small style="font-weight: 400; opacity: 0.8;">{{ translate('Based on average idle time per order') }}</small>
            </div>
            <div class="dash-card-body p-0" style="overflow-x: auto;">
                <table class="dash-table-modern">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>{{ translate('Delivery Man') }}</th>
                            <th class="text-center">{{ translate('Orders') }}</th>
                            <th class="text-center">{{ translate('Avg Idle/Order') }}</th>
                            <th class="text-center">{{ translate('Total Idle') }}</th>
                            <th class="text-center">{{ translate('At Store') }}</th>
                            <th class="text-center">{{ translate('At Customer') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['bad_performers'] as $index => $bp)
                        <tr>
                            <td>
                                <span class="dash-badge {{ $index == 0 ? 'red' : ($index == 1 ? 'amber' : 'blue') }}">{{ $index + 1 }}</span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img class="performer-avatar" src="{{ $bp->image_full_url ?? asset('public/assets/admin/img/delivery_boy_map.png') }}" alt="">
                                    <div>
                                        <div style="font-weight: 600; color: var(--dash-text);">{{ $bp->f_name }} {{ $bp->l_name }}</div>
                                        <div style="font-size: 11px; color: var(--dash-text-muted);">{{ $bp->phone }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><span class="dash-badge blue">{{ $bp->week_order_count }}</span></td>
                            <td class="text-center"><span class="dash-badge red">{{ \App\Models\DeliveryTrackingStat::formatDuration($bp->avg_idle_seconds) }}</span></td>
                            <td class="text-center" style="color: var(--dash-danger); font-weight: 600;">{{ \App\Models\DeliveryTrackingStat::formatDuration($bp->total_idle_seconds) }}</td>
                            <td class="text-center" style="color: var(--dash-warning);">{{ \App\Models\DeliveryTrackingStat::formatDuration($bp->total_store_seconds) }}</td>
                            <td class="text-center" style="color: var(--dash-info);">{{ \App\Models\DeliveryTrackingStat::formatDuration($bp->total_customer_seconds) }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.users.delivery-man.preview', $bp->id) }}" class="dash-btn-outline" title="{{ translate('View Profile') }}">
                                    <i class="tio-visible"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Customer Carts --}}
        <div class="dash-cart-section" id="customer-carts-view">
            @include('admin-views.partials._customer-carts', ['latest_carts' => $latest_carts ?? collect()])
        </div>

        {{-- Order Statistics --}}
        <div class="dash-stats-section">
            <div class="dash-stats-header">
                <div class="statistics-btn-grp-new">
                    <label>
                        <input type="radio" name="statistics" value="this_year" {{$params['statistics_type'] == 'this_year'?'checked':''}} class="order_stats_update" hidden>
                        <span>{{ translate('This_Year') }}</span>
                    </label>
                    <label>
                        <input type="radio" name="statistics" value="this_month" {{$params['statistics_type'] == 'this_month'?'checked':''}} class="order_stats_update" hidden>
                        <span>{{ translate('This_Month') }}</span>
                    </label>
                    <label>
                        <input type="radio" name="statistics" value="this_week" {{$params['statistics_type'] == 'this_week'?'checked':''}} class="order_stats_update" hidden>
                        <span>{{ translate('This_Week') }}</span>
                    </label>
                </div>
                <input type="date" id="custom_date_picker" class="form-control form-control-sm" style="width: auto; display: inline-block; margin-left: 10px;" value="{{ $params['custom_date'] ?? '' }}" placeholder="{{ translate('Select Date') }}">
            </div>
            <div class="dash-stats-body" id="order_stats">
                {{-- Summary Cards --}}
                <div class="dash-stat-summary-grid">
                    <div class="dash-stat-summary">
                        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/items.svg')}}" alt="">
                        <div class="stat-name">{{ translate('messages.items') }}</div>
                        <div class="stat-count">{{ $data['total_items'] }}</div>
                        <div class="stat-new">{{ $data['new_items'] }} {{ translate('newly added') }}</div>
                    </div>
                    <div class="dash-stat-summary">
                        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/orders.svg')}}" alt="">
                        <div class="stat-name">{{ translate('messages.orders') }}</div>
                        <div class="stat-count">{{ $data['total_orders'] }}</div>
                        <div class="stat-new">{{ $data['new_orders'] }} {{ translate('newly added') }}</div>
                    </div>
                    <div class="dash-stat-summary">
                        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/stores.svg')}}" alt="">
                        <div class="stat-name">{{ translate('Grocery Stores') }}</div>
                        <div class="stat-count">{{ $data['total_stores'] }}</div>
                        <div class="stat-new">{{ $data['new_stores'] }} {{ translate('newly added') }}</div>
                    </div>
                    <div class="dash-stat-summary">
                        <img src="{{asset('/public/assets/admin/img/dashboard/grocery/customers.svg')}}" alt="">
                        <div class="stat-name">{{ translate('messages.customers') }}</div>
                        <div class="stat-count">{{ $data['total_customers'] }}</div>
                        <div class="stat-new">{{ $data['new_customers'] }} {{ translate('newly added') }}</div>
                    </div>
                </div>

                {{-- Order Status Grid --}}
                <div class="dash-order-status-grid">
                    <a class="dash-order-status" href="{{route('admin.order.list',['searching_for_deliverymen'])}}">
                        <div class="os-left">
                            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/unassigned.svg')}}" alt="">
                            <span>{{translate('messages.unassigned_orders')}}</span>
                        </div>
                        <span class="os-count blue">{{$data['searching_for_dm']}}</span>
                    </a>
                    <a class="dash-order-status" href="{{route('admin.order.list',['accepted'])}}">
                        <div class="os-left">
                            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/accepted.svg')}}" alt="">
                            <span>{{translate('Accepted by DM')}}</span>
                        </div>
                        <span class="os-count green">{{$data['accepted_by_dm']}}</span>
                    </a>
                    <a class="dash-order-status" href="{{route('admin.order.list',['processing'])}}">
                        <div class="os-left">
                            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/packaging.svg')}}" alt="">
                            <span>{{translate('Packaging')}}</span>
                        </div>
                        <span class="os-count amber">{{$data['preparing_in_rs']}}</span>
                    </a>
                    <a class="dash-order-status" href="{{route('admin.order.list',['item_on_the_way'])}}">
                        <div class="os-left">
                            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/out-for.svg')}}" alt="">
                            <span>{{translate('Out for Delivery')}}</span>
                        </div>
                        <span class="os-count green">{{$data['picked_up']}}</span>
                    </a>
                    <a class="dash-order-status" href="{{route('admin.order.list',['delivered'])}}">
                        <div class="os-left">
                            <img src="{{asset('/public/assets/admin/img/dashboard/grocery/delivered.svg')}}" alt="">
                            <span>{{translate('messages.delivered')}}</span>
                        </div>
                        <span class="os-count green">{{$data['delivered']}}</span>
                    </a>
                    <a class="dash-order-status" href="{{route('admin.order.list',['canceled'])}}">
                        <div class="os-left">
                            <img src="{{asset('/public/assets/admin/img/order-status/canceled.svg')}}" alt="">
                            <span>{{translate('messages.canceled')}}</span>
                        </div>
                        <span class="os-count red">{{$data['canceled']}}</span>
                    </a>
                    <a class="dash-order-status" href="{{route('admin.order.list',['refunded'])}}">
                        <div class="os-left">
                            <img src="{{asset('/public/assets/admin/img/order-status/refunded.svg')}}" alt="">
                            <span>{{translate('messages.refunded')}}</span>
                        </div>
                        <span class="os-count red">{{$data['refunded']}}</span>
                    </a>
                    <a class="dash-order-status" href="{{route('admin.order.list',['failed'])}}">
                        <div class="os-left">
                            <img src="{{asset('/public/assets/admin/img/order-status/payment-failed.svg')}}" alt="">
                            <span>{{translate('messages.payment_failed')}}</span>
                        </div>
                        <span class="os-count red">{{$data['refund_requested']}}</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="dash-charts-grid">
            {{-- Sales Chart --}}
            <div class="dash-card">
                <div class="dash-card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap: 12px;">
                        <div class="dash-gross-sale" id="gross_sale">
                            <h6>{{\App\CentralLogics\Helpers::format_currency(array_sum($total_sell))}}</h6>
                            <span>{{ translate('messages.Gross Sale') }}</span>
                        </div>
                        <div class="d-flex align-items-center" style="gap: 12px;">
                            <div class="dash-chart-legend">
                                <div class="legend-item">
                                    <span class="legend-dot" style="background: #3b82f6;"></span>
                                    {{ translate('sale') }} ({{ date("Y") }})
                                </div>
                            </div>
                            <select class="dash-chart-select commission_overview_stats_update" name="commission_overview">
                                <option value="this_year" {{$params['commission_overview'] == 'this_year'?'selected':''}}>{{translate('This year')}}</option>
                                <option value="this_month" {{$params['commission_overview'] == 'this_month'?'selected':''}}>{{translate('This month')}}</option>
                                <option value="this_week" {{$params['commission_overview'] == 'this_week'?'selected':''}}>{{translate('This week')}}</option>
                            </select>
                        </div>
                    </div>
                    <div id="commission-overview-board">
                        <div id="grow-sale-chart"></div>
                    </div>
                </div>
            </div>

            {{-- User Statistics Donut --}}
            <div class="dash-card">
                <div class="dash-card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        {{translate('User Statistics')}}
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <div id="stat_zone">
                            @include('admin-views.partials._zone-change',['data'=>$data])
                        </div>
                        <select class="dash-chart-select user_overview_stats_update" name="user_overview">
                            <option value="this_year" {{$params['user_overview'] == 'this_year'?'selected':''}}>{{translate('This year')}}</option>
                            <option value="this_month" {{$params['user_overview'] == 'this_month'?'selected':''}}>{{translate('This month')}}</option>
                            <option value="this_week" {{$params['user_overview'] == 'this_week'?'selected':''}}>{{translate('This week')}}</option>
                            <option value="overall" {{$params['user_overview'] == 'overall'?'selected':''}}>{{translate('messages.Overall')}}</option>
                        </select>
                    </div>
                </div>
                <div class="dash-card-body" id="user-overview-board">
                    <div class="position-relative pie-chart">
                        <div id="dognut-pie"></div>
                        <div class="dash-donut-center">
                            <h3>{{ $data['customer'] + $data['stores'] + $data['delivery_man'] }}</h3>
                            <span>{{translate('messages.total_users')}}</span>
                        </div>
                    </div>
                    <div class="dash-chart-legend">
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #1e293b;"></span>
                            {{translate('messages.customer')}} {{$data['customer']}}
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #3b82f6;"></span>
                            {{translate('messages.store')}} {{$data['stores']}}
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #94a3b8;"></span>
                            {{translate('messages.delivery_man')}} {{$data['delivery_man']}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Cards --}}
        <div class="dash-bottom-grid">
            <div class="dash-card" id="top-restaurants-view">
                @include('admin-views.partials._top-restaurants',['top_restaurants'=>$data['top_restaurants']])
            </div>
            <div class="dash-card" id="popular-restaurants-view">
                @include('admin-views.partials._popular-restaurants',['popular'=>$data['popular']])
            </div>
            <div class="dash-card" id="top-selling-foods-view">
                @include('admin-views.partials._top-selling-foods',['top_sell'=>$data['top_sell']])
            </div>
            <div class="dash-card" id="top-rated-foods-view">
                @include('admin-views.partials._top-rated-foods',['top_rated_foods'=>$data['top_rated_foods']])
            </div>
            <div class="dash-card" id="top-deliveryman-view">
                @include('admin-views.partials._top-deliveryman',['top_deliveryman'=>$data['top_deliveryman']])
            </div>
            <div class="dash-card" id="top-customer-view">
                @include('admin-views.partials._top-customer',['top_customers'=>$data['top_customers']])
            </div>
        </div>

        @else
        {{-- ===== Employee Dashboard ===== --}}
        @php
            $adminNotice = \App\Models\BusinessSetting::where('key', 'employee_notice')->first();
            $noticeMessage = $adminNotice ? $adminNotice->value : null;
            $emp = $empData ?? [];
            $todayAtt = $emp['today_attendance'] ?? null;
            $todayShift = $emp['today_shift'] ?? null;
            $weekRoster = $emp['week_roster'] ?? collect();
            $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        @endphp

        <style>
            .emp-break-btn { padding: 10px 22px; font-size: 13px; font-weight: 700; border-radius: 10px; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
            .emp-break-btn:disabled { opacity: 0.4; cursor: not-allowed; }
            .emp-break-btn.start-break { background: var(--dash-warning); color: #fff; }
            .emp-break-btn.start-break:hover:not(:disabled) { background: #d97706; box-shadow: 0 4px 12px rgba(245,158,11,0.3); }
            .emp-break-btn.end-break { background: var(--dash-info); color: #fff; }
            .emp-break-btn.end-break:hover:not(:disabled) { background: #0891b2; box-shadow: 0 4px 12px rgba(6,182,212,0.3); }
            .shift-info-bar { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-radius: var(--dash-radius); padding: 18px 24px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
            .shift-info-bar .shift-item { text-align: center; }
            .shift-info-bar .shift-item .shift-val { font-size: 18px; font-weight: 700; }
            .shift-info-bar .shift-item .shift-lbl { font-size: 10px; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.05em; }
            .shift-info-bar .shift-role-badge { padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; }
            .break-info-strip { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 16px; }
            .break-info-strip .break-chip { background: #fff; border: 1px solid var(--dash-border); border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; }
            .week-roster-mini { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
            .week-roster-mini .roster-day { background: var(--dash-card); border: 1px solid var(--dash-border); border-radius: 8px; padding: 10px 4px; text-align: center; font-size: 11px; transition: all 0.2s; }
            .week-roster-mini .roster-day.today { border-color: var(--dash-accent); background: var(--dash-accent-light); }
            .week-roster-mini .roster-day.off { background: #fef2f2; border-color: #fecaca; }
            .week-roster-mini .roster-day .day-name { font-weight: 700; color: var(--dash-text); margin-bottom: 4px; }
            .week-roster-mini .roster-day .day-time { color: var(--dash-text-muted); font-size: 10px; line-height: 1.4; }
            .week-roster-mini .roster-day .day-role { font-size: 9px; margin-top: 3px; padding: 2px 6px; border-radius: 10px; display: inline-block; color: #fff; }
            .recent-att-row { display: flex; align-items: center; padding: 10px 16px; border-bottom: 1px solid var(--dash-border); gap: 12px; transition: background 0.15s; }
            .recent-att-row:hover { background: #f8fafc; }
            .recent-att-row:last-child { border-bottom: none; }
            .recent-att-row .att-date { min-width: 70px; font-size: 12px; font-weight: 600; color: var(--dash-text); }
            .recent-att-row .att-times { flex: 1; font-size: 11px; color: var(--dash-text-muted); }
            .recent-att-row .att-hours { font-size: 13px; font-weight: 700; color: var(--dash-accent); min-width: 60px; text-align: right; }
            .recent-att-row .att-status { min-width: 24px; }
            @media (max-width: 575px) { .week-roster-mini { grid-template-columns: repeat(4, 1fr); } }
        </style>

        {{-- Notice --}}
        @if($noticeMessage)
        <div class="dash-notice">
            <i class="tio-notifications-on notice-icon"></i>
            <div class="marquee-wrapper">
                <div class="marquee-content">
                    {{ $noticeMessage }}
                    <span style="margin: 0 40px; opacity: 0.5;">|</span>
                    {{ $noticeMessage }}
                </div>
            </div>
        </div>
        @endif

        {{-- Welcome + Clock --}}
        <div class="row mb-4" style="gap: 0;">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="emp-welcome-card h-100">
                    <img class="emp-avatar"
                         src="{{ auth('admin')->user()->image_full_url ?? asset('public/assets/admin/img/admin.png') }}"
                         alt="{{ auth('admin')->user()->f_name }}"
                         onerror="this.src='{{ asset('public/assets/admin/img/admin.png') }}'">
                    <div>
                        <h2>{{translate('messages.welcome_back')}}, {{auth('admin')->user()->f_name}}!</h2>
                        <p>{{translate('messages.employee_welcome_message')}}</p>
                        <small style="color: var(--dash-text-muted);"><i class="tio-briefcase mr-1"></i> {{ auth('admin')->user()->role ? auth('admin')->user()->role->name : translate('Employee') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="emp-clock-card">
                    <div class="emp-greeting" id="clock-greeting">{{translate('messages.good_morning')}}</div>
                    <div class="emp-time" id="employee-clock">00:00:00</div>
                    <div class="emp-date" id="employee-date">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Employee Performance Widget -->
        @if(isset($employee_performance))
            @include('admin-views.partials._employee-performance', ['data' => $employee_performance])
        @endif

        {{-- Today's Shift Info Bar --}}
        <div class="shift-info-bar">
            <div style="display: flex; align-items: center; gap: 14px;">
                <i class="tio-calendar" style="font-size: 24px; opacity: 0.7;"></i>
                <div>
                    <div style="font-size: 14px; font-weight: 700;">{{translate('messages.todays_shift')}}</div>
                    <div style="font-size: 11px; opacity: 0.7;">{{ \Carbon\Carbon::today()->format('l, d M Y') }}</div>
                </div>
            </div>
            @if($todayShift && !$todayShift->is_off_day)
                <div class="shift-item">
                    <div class="shift-val">{{ \Carbon\Carbon::parse($todayShift->shift_start)->format('h:i A') }}</div>
                    <div class="shift-lbl">{{translate('messages.shift_start')}}</div>
                </div>
                <div class="shift-item">
                    <div class="shift-val">{{ \Carbon\Carbon::parse($todayShift->shift_end)->format('h:i A') }}</div>
                    <div class="shift-lbl">{{translate('messages.shift_end')}}</div>
                </div>
                <div class="shift-item">
                    <div class="shift-val">9.5 {{translate('messages.hrs')}}</div>
                    <div class="shift-lbl">{{translate('messages.duration')}}</div>
                </div>
                @if($todayAtt && $todayAtt->expected_shift_end)
                <div class="shift-item">
                    <div class="shift-val" style="color: #fbbf24;">{{ $todayAtt->expected_shift_end->format('h:i A') }}</div>
                    <div class="shift-lbl">{{translate('messages.expected_end')}}</div>
                </div>
                @endif
                @if($todayShift->rosterRole)
                <div>
                    <span class="shift-role-badge" style="background: {{ $todayShift->rosterRole->color }};">{{ $todayShift->rosterRole->name }}</span>
                </div>
                @endif
            @elseif($todayShift && $todayShift->is_off_day)
                <div style="font-size: 16px; font-weight: 700; color: #fbbf24;">
                    <i class="tio-snooze mr-1"></i> {{translate('messages.off_day')}}
                </div>
            @else
                <div style="font-size: 13px; opacity: 0.7;">{{translate('messages.no_shift_assigned')}}</div>
            @endif
        </div>

        {{-- Attendance + Break Actions --}}
        <div class="row mb-4">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="emp-attendance-card h-100">
                    <h5><i class="tio-time" style="color: var(--dash-accent);"></i> {{translate('messages.todays_attendance')}}</h5>
                    <div class="row align-items-center">
                        <div class="col-md-5 mb-3 mb-md-0">
                            <div class="d-flex flex-wrap justify-content-center" style="gap: 10px;">
                                <button type="button" id="punch-in-btn" class="punch-btn-modern punch-in" onclick="punchIn()" disabled>
                                    <i class="tio-play"></i> {{translate('messages.punch_in')}}
                                </button>
                                <button type="button" id="punch-out-btn" class="punch-btn-modern punch-out" onclick="punchOut()" disabled>
                                    <i class="tio-stop"></i> {{translate('messages.punch_out')}}
                                </button>
                            </div>
                            {{-- Break Buttons --}}
                            <div class="d-flex flex-wrap justify-content-center mt-3" style="gap: 10px;" id="break-buttons-area">
                                <button type="button" id="start-break-btn" class="emp-break-btn start-break" onclick="dashStartBreak()" style="display:none;">
                                    <i class="tio-pause"></i> {{translate('messages.start_break')}}
                                </button>
                                <button type="button" id="end-break-btn" class="emp-break-btn end-break" onclick="dashEndBreak()" style="display:none;">
                                    <i class="tio-play"></i> {{translate('messages.end_break')}}
                                    <span id="dash-break-timer" style="font-family: monospace;"></span>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="row text-center" id="attendance-info">
                                <div class="col-3">
                                    <div class="emp-time-display" style="border-right: 1px solid var(--dash-border);">
                                        <div class="time-val" style="color: var(--dash-success);" id="punch-in-time">{{ $todayAtt && $todayAtt->punch_in ? $todayAtt->punch_in->format('h:i A') : '--:--' }}</div>
                                        <div class="time-lbl">{{translate('messages.in')}}</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="emp-time-display" style="border-right: 1px solid var(--dash-border);">
                                        <div class="time-val" style="color: var(--dash-danger);" id="punch-out-time">{{ $todayAtt && $todayAtt->punch_out ? $todayAtt->punch_out->format('h:i A') : '--:--' }}</div>
                                        <div class="time-lbl">{{translate('messages.out')}}</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="emp-time-display" style="border-right: 1px solid var(--dash-border);">
                                        <div class="time-val" style="color: var(--dash-accent);" id="worked-hours">
                                            @if($todayAtt && $todayAtt->actual_work_hours)
                                                {{ floor($todayAtt->actual_work_hours) }}h {{ round(($todayAtt->actual_work_hours - floor($todayAtt->actual_work_hours)) * 60) }}m
                                            @else
                                                0h 0m
                                            @endif
                                        </div>
                                        <div class="time-lbl">{{translate('messages.worked')}}</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="emp-time-display">
                                        <div class="time-val" style="color: var(--dash-warning);" id="break-time-display">{{ $todayAtt ? $todayAtt->total_break_minutes : 0 }}m</div>
                                        <div class="time-lbl">{{translate('messages.breaks')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Break Info Strip (dynamic via JS) --}}
                    <div class="break-info-strip" id="dash-break-info" style="{{ ($todayAtt && $todayAtt->total_break_minutes > 0) ? 'display:flex' : 'display:none' }}">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="tio-pause-circle" style="color: var(--dash-warning); font-size: 18px;"></i>
                            <span style="font-size: 13px; font-weight: 600;">{{translate('messages.break_summary')}}</span>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span class="break-chip" id="dash-break-total">{{translate('messages.total')}}: {{ $todayAtt ? $todayAtt->total_break_minutes : 0 }}min / {{ $todayAtt ? $todayAtt->allocated_break_minutes : 30 }}min</span>
                            <span class="break-chip" id="dash-break-extra" style="border-color: #fca5a5; color: var(--dash-danger); {{ ($todayAtt && $todayAtt->extra_break_minutes > 0) ? '' : 'display:none' }}">+{{ $todayAtt ? $todayAtt->extra_break_minutes : 0 }}min extra</span>
                            <span class="break-chip" id="dash-expected-end" style="border-color: #93c5fd; color: var(--dash-accent);">{{ $todayAtt && $todayAtt->expected_shift_end ? 'Expected End: ' . $todayAtt->expected_shift_end->format('h:i A') : '' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="col-lg-4">
                <div class="dash-card h-100">
                    <div class="dash-card-header">
                        <i class="tio-flash" style="color: var(--dash-warning);"></i> {{translate('messages.quick_actions')}}
                    </div>
                    <div class="dash-card-body">
                        <div class="row" style="gap: 0;">
                            <div class="col-6 mb-2">
                                <a href="{{route('admin.leave.create')}}" class="quick-action-modern">
                                    <i class="tio-calendar-note"></i>
                                    <span>{{translate('messages.request_leave')}}</span>
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="{{route('admin.leave.index')}}" class="quick-action-modern">
                                    <i class="tio-document-text"></i>
                                    <span>{{translate('messages.my_leaves')}}</span>
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="{{route('admin.shift-roster.my-shift')}}" class="quick-action-modern">
                                    <i class="tio-calendar"></i>
                                    <span>{{translate('messages.my_shift')}}</span>
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="{{route('admin.attendance.index')}}" class="quick-action-modern">
                                    <i class="tio-chart-bar-4"></i>
                                    <span>{{translate('messages.attendance')}}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Monthly Stats --}}
        <div class="emp-stat-grid">
            <div class="emp-stat-card">
                <div class="emp-stat-icon" style="background: #ecfdf5; color: var(--dash-success);">
                    <i class="tio-calendar-event"></i>
                </div>
                <div class="emp-stat-value">{{ $emp['month_present'] ?? 0 }}</div>
                <div class="emp-stat-label">{{translate('messages.days_present_this_month')}}</div>
            </div>
            <div class="emp-stat-card">
                <div class="emp-stat-icon" style="background: #eff6ff; color: var(--dash-info);">
                    <i class="tio-time"></i>
                </div>
                <div class="emp-stat-value">{{ number_format($emp['month_total_hours'] ?? 0, 1) }}h</div>
                <div class="emp-stat-label">{{translate('messages.work_hours_this_month')}}</div>
            </div>
            <div class="emp-stat-card">
                <div class="emp-stat-icon" style="background: #ecfdf5; color: var(--dash-success);">
                    <i class="tio-checkmark-circle"></i>
                </div>
                <div class="emp-stat-value">{{ $emp['month_completed'] ?? 0 }}</div>
                <div class="emp-stat-label">{{translate('messages.shifts_completed')}}</div>
            </div>
            <div class="emp-stat-card">
                <div class="emp-stat-icon" style="background: #fef2f2; color: var(--dash-danger);">
                    <i class="tio-warning"></i>
                </div>
                <div class="emp-stat-value">{{ $emp['month_early'] ?? 0 }}</div>
                <div class="emp-stat-label">{{translate('messages.early_departures')}}</div>
            </div>
        </div>

        {{-- Break Stats + Week Summary --}}
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="dash-card h-100">
                    <div class="dash-card-header">
                        <i class="tio-pause-circle" style="color: var(--dash-warning);"></i> {{translate('messages.monthly_break_stats')}}
                    </div>
                    <div class="dash-card-body">
                        <div class="text-center mb-3">
                            <div style="font-size: 36px; font-weight: 800; color: var(--dash-text);">{{ $emp['month_total_break'] ?? 0 }}</div>
                            <div style="font-size: 12px; color: var(--dash-text-muted);">{{translate('messages.total_break_minutes')}}</div>
                        </div>
                        <div style="display: flex; justify-content: space-around; border-top: 1px solid var(--dash-border); padding-top: 14px;">
                            <div class="text-center">
                                <div style="font-size: 20px; font-weight: 700; color: var(--dash-warning);">{{ $emp['month_extra_break'] ?? 0 }}</div>
                                <div style="font-size: 11px; color: var(--dash-text-muted);">{{translate('messages.extra_mins')}}</div>
                            </div>
                            <div class="text-center">
                                <div style="font-size: 20px; font-weight: 700; color: var(--dash-accent);">{{ $emp['week_present'] ?? 0 }}</div>
                                <div style="font-size: 11px; color: var(--dash-text-muted);">{{translate('messages.this_week_days')}}</div>
                            </div>
                            <div class="text-center">
                                <div style="font-size: 20px; font-weight: 700; color: var(--dash-success);">{{ number_format($emp['week_hours'] ?? 0, 1) }}h</div>
                                <div style="font-size: 11px; color: var(--dash-text-muted);">{{translate('messages.this_week_hours')}}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="dash-card h-100">
                    <div class="dash-card-header">
                        <i class="tio-calendar" style="color: var(--dash-accent);"></i> {{translate('messages.this_weeks_roster')}}
                    </div>
                    <div class="dash-card-body">
                        @php $todayDow = \Carbon\Carbon::now()->dayOfWeekIso - 1; @endphp
                        <div class="week-roster-mini">
                            @foreach($days as $di => $dn)
                                @php $r = $weekRoster[$di] ?? null; @endphp
                                <div class="roster-day {{ $di == $todayDow ? 'today' : '' }} {{ $r && $r->is_off_day ? 'off' : '' }}">
                                    <div class="day-name">{{ $dn }}</div>
                                    @if($r && $r->is_off_day)
                                        <div class="day-time" style="color: var(--dash-danger); font-weight: 600;">OFF</div>
                                    @elseif($r)
                                        <div class="day-time">
                                            {{ \Carbon\Carbon::parse($r->shift_start)->format('h:iA') }}<br>
                                            {{ \Carbon\Carbon::parse($r->shift_end)->format('h:iA') }}
                                        </div>
                                        @if($r->rosterRole)
                                            <span class="day-role" style="background: {{ $r->rosterRole->color }};">{{ $r->rosterRole->name }}</span>
                                        @endif
                                    @else
                                        <div class="day-time">-</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Attendance + Notes --}}
        <div class="row mb-4">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <div class="dash-card h-100">
                    <div class="dash-card-header">
                        <i class="tio-receipt" style="color: var(--dash-accent);"></i> {{translate('messages.recent_attendance')}}
                    </div>
                    <div class="dash-card-body p-0" style="max-height: 320px; overflow-y: auto;">
                        @forelse(($emp['recent_attendance'] ?? []) as $att)
                        <div class="recent-att-row">
                            <div class="att-status">
                                @if($att->shift_completed)
                                    <span style="color: var(--dash-success); font-size: 16px;"><i class="tio-checkmark-circle"></i></span>
                                @elseif($att->early_departure)
                                    <span style="color: var(--dash-danger); font-size: 16px;"><i class="tio-warning"></i></span>
                                @elseif(!$att->punch_out)
                                    <span style="color: var(--dash-warning); font-size: 16px;"><i class="tio-time"></i></span>
                                @else
                                    <span style="color: var(--dash-text-muted); font-size: 16px;"><i class="tio-minus-circle"></i></span>
                                @endif
                            </div>
                            <div class="att-date">{{ $att->attendance_date->format('d M') }}</div>
                            <div class="att-times">
                                {{ $att->punch_in ? $att->punch_in->format('h:iA') : '-' }} - {{ $att->punch_out ? $att->punch_out->format('h:iA') : translate('messages.working') }}
                                @if($att->total_break_minutes > 0)
                                    <span style="color: var(--dash-warning); margin-left: 6px;">({{ $att->total_break_minutes }}m break)</span>
                                @endif
                            </div>
                            <div class="att-hours">{{ $att->actual_work_hours ? number_format($att->actual_work_hours, 1).'h' : '-' }}</div>
                        </div>
                        @empty
                        <div class="text-center py-4" style="color: var(--dash-text-muted);">
                            <i class="tio-receipt" style="font-size: 36px; opacity: 0.2;"></i>
                            <p class="mt-2 mb-0" style="font-size: 13px;">{{translate('messages.no_attendance_records')}}</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="dash-card h-100">
                    <div class="dash-card-header" style="justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="tio-notebook" style="color: var(--dash-accent);"></i> {{translate('messages.my_notes')}}
                        </div>
                        <button style="background: var(--dash-accent); color: #fff; border: none; padding: 5px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;" data-toggle="modal" data-target="#addNoteModal">
                            <i class="tio-add"></i> {{translate('messages.add_note')}}
                        </button>
                    </div>
                    <div class="dash-card-body p-0" id="notes-container" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center py-4" style="color: var(--dash-text-muted);" id="no-notes-msg">
                            <i class="tio-notebook" style="font-size: 36px; opacity: 0.2;"></i>
                            <p class="mt-2 mb-0" style="font-size: 13px;">{{translate('messages.no_notes_yet')}}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Module Quick Access (permission-based) --}}
        @php $modules = auth('admin')->user()->role ? json_decode(auth('admin')->user()->role->modules, true) : []; @endphp
        @if(!empty($modules))
        <div class="dash-card mb-4">
            <div class="dash-card-header">
                <i class="tio-key" style="color: var(--dash-purple);"></i> {{translate('messages.your_modules')}}
            </div>
            <div class="dash-card-body">
                <div class="row" style="gap: 0;">
                    @if(in_array('order', $modules ?? []))
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <a href="{{ route('admin.order.list', ['all']) }}" class="quick-action-modern">
                            <i class="tio-shopping-cart" style="color: var(--dash-accent);"></i>
                            <span>{{ translate('Orders') }}</span>
                        </a>
                    </div>
                    @endif
                    @if(in_array('store', $modules ?? []))
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <a href="{{ route('admin.store.list') }}" class="quick-action-modern">
                            <i class="tio-shop" style="color: var(--dash-success);"></i>
                            <span>{{ translate('Stores') }}</span>
                        </a>
                    </div>
                    @endif
                    @if(in_array('deliveryman', $modules ?? []))
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <a href="{{ route('admin.users.delivery-man.list') }}" class="quick-action-modern">
                            <i class="tio-user" style="color: var(--dash-danger);"></i>
                            <span>{{ translate('Delivery Men') }}</span>
                        </a>
                    </div>
                    @endif
                    @if(in_array('customerList', $modules ?? []))
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <a href="{{ route('admin.customer.list') }}" class="quick-action-modern">
                            <i class="tio-users-switch" style="color: var(--dash-warning);"></i>
                            <span>{{ translate('Customers') }}</span>
                        </a>
                    </div>
                    @endif
                    @if(in_array('report', $modules ?? []))
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <a href="{{ route('admin.report.order-report') }}" class="quick-action-modern">
                            <i class="tio-chart-bar-4" style="color: var(--dash-purple);"></i>
                            <span>{{ translate('Reports') }}</span>
                        </a>
                    </div>
                    @endif
                    @if(in_array('pos', $modules ?? []))
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <a href="{{ route('admin.pos.index') }}" class="quick-action-modern">
                            <i class="tio-receipt" style="color: var(--dash-info);"></i>
                            <span>{{ translate('POS') }}</span>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Add Note Modal --}}
        <div class="modal fade" id="addNoteModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content" style="border-radius: var(--dash-radius); border: none;">
                    <div class="modal-header" style="border-bottom: 1px solid var(--dash-border);">
                        <h5 class="modal-title" style="font-size: 16px; font-weight: 700;"><i class="tio-notebook mr-2" style="color: var(--dash-accent);"></i>{{translate('messages.add_new_note')}}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <form id="noteForm">
                            <div class="form-group">
                                <label style="font-size: 13px; font-weight: 600;">{{translate('messages.note_title')}}</label>
                                <input type="text" class="form-control" id="noteTitle" placeholder="{{translate('messages.enter_title')}}" required style="border-radius: 8px;">
                            </div>
                            <div class="form-group">
                                <label style="font-size: 13px; font-weight: 600;">{{translate('messages.note_content')}}</label>
                                <textarea class="form-control" id="noteContent" rows="4" placeholder="{{translate('messages.enter_note')}}" required style="border-radius: 8px;"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--dash-border);">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">{{translate('messages.cancel')}}</button>
                        <button type="button" class="btn btn-primary" onclick="saveNote()" style="border-radius: 8px; background: var(--dash-accent); border-color: var(--dash-accent);">{{translate('messages.save_note')}}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Face Verification Modal --}}
        <div class="modal fade" id="faceVerificationModal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content" style="border-radius: var(--dash-radius); border: none; overflow: hidden;">
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--dash-accent) 0%, var(--dash-purple) 100%); color: #fff; border: none;">
                        <h5 class="modal-title"><i class="tio-face-id mr-2"></i>{{translate('messages.face_verification')}}</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" onclick="cancelFaceVerification()"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-7">
                                <div id="face-verify-container" class="position-relative" style="border-radius: 12px; overflow: hidden; background: #000;">
                                    <video id="face-verify-video" autoplay muted playsinline style="width: 100%; height: auto;"></video>
                                    <canvas id="face-verify-canvas" style="display: none;"></canvas>
                                    <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                        <div style="width: 200px; height: 250px; border: 3px dashed rgba(255,255,255,0.5); border-radius: 50%;"></div>
                                    </div>
                                </div>
                                <div id="face-verify-status" class="mt-3 p-3 rounded text-center" style="display: none;"></div>
                            </div>
                            <div class="col-md-5">
                                <div class="h-100 d-flex flex-column justify-content-center">
                                    <div class="text-center mb-4">
                                        <i class="tio-face-id" style="font-size: 50px; color: var(--dash-accent);"></i>
                                        <h5 class="mt-3" id="face-verify-action-text">{{translate('messages.verifying_face')}}</h5>
                                        <p style="color: var(--dash-text-muted); font-size: 13px;">{{translate('messages.look_at_camera_for_verification')}}</p>
                                    </div>
                                    <div id="face-verify-progress" class="text-center" style="display: none;">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2 mb-0" id="face-verify-progress-text">{{translate('messages.analyzing_face')}}...</p>
                                    </div>
                                    <div id="face-verify-result" class="text-center" style="display: none;">
                                        <i id="face-verify-result-icon" class="tio-checkmark-circle" style="font-size: 50px;"></i>
                                        <p id="face-verify-result-text" class="mt-2 mb-0"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--dash-border);">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="cancelFaceVerification()" style="border-radius: 8px;">{{translate('messages.cancel')}}</button>
                        <button type="button" id="retry-face-verify-btn" class="btn btn-warning" style="display: none; border-radius: 8px;" onclick="retryFaceVerification()">
                            <i class="tio-refresh"></i> {{translate('messages.retry')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Face Not Registered Modal --}}
        <div class="modal fade" id="faceNotRegisteredModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content" style="border-radius: var(--dash-radius); border: none; overflow: hidden;">
                    <div class="modal-header" style="background: #fffbeb; border: none;">
                        <h5 class="modal-title" style="color: #d97706;"><i class="tio-warning mr-2"></i>{{translate('messages.face_not_registered')}}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <i class="tio-face-id" style="font-size: 60px; color: var(--dash-warning);"></i>
                        <h5 class="mt-3">{{translate('messages.face_registration_required')}}</h5>
                        <p style="color: var(--dash-text-muted); font-size: 13px;">{{translate('messages.face_registration_required_desc')}}</p>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--dash-border);">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">{{translate('messages.close')}}</button>
                        <a href="{{route('admin.settings')}}" class="btn btn-primary" style="border-radius: 8px; background: var(--dash-accent); border-color: var(--dash-accent);">
                            <i class="tio-settings"></i> {{translate('messages.register_face')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection

@push('script')
    <script src="{{asset('public/assets/admin')}}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script src="{{asset('public/assets/admin')}}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js"></script>
    <script src="{{asset('/public/assets/admin/js/apex-charts/apexcharts.js')}}"></script>
@endpush

@push('script_2')
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>

    <script>
        window.FaceRecognitionConfig = {
            routes: {
                attendanceToday: '{{ route("admin.attendance.today") }}',
                punchInFace: '{{ route("admin.attendance.punch-in-face") }}',
                punchOutFace: '{{ route("admin.attendance.punch-out-face") }}',
                punchIn: '{{ route("admin.attendance.punch-in") }}',
                punchOut: '{{ route("admin.attendance.punch-out") }}',
                dashboard: '{{ route("admin.dashboard") }}'
            },
            csrfToken: '{{ csrf_token() }}',
            translations: {
                loadingFaceModels: '{{ translate("messages.loading_face_models") }}',
                faceModelsNotLoaded: '{{ translate("messages.face_models_not_loaded") }}',
                preparingCamera: '{{ translate("messages.preparing_camera") }}',
                cameraAccessDenied: '{{ translate("messages.camera_access_denied") }}',
                cameraPermissionDenied: '{{ translate("messages.camera_permission_denied") }}',
                noCameraFound: '{{ translate("messages.no_camera_found") }}',
                analyzingFace: '{{ translate("messages.analyzing_face") }}',
                noFaceDetected: '{{ translate("messages.no_face_detected") }}',
                multipleFacesDetected: '{{ translate("messages.multiple_faces_detected") }}',
                faceDetected: '{{ translate("messages.face_detected") }}',
                moveCloser: '{{ translate("messages.move_closer") }}',
                keepMovingFace: '{{ translate("messages.keep_moving_face") }}',
                faceDetectedStayStill: '{{ translate("messages.face_detected_stay_still") }}',
                moveYourHeadSlightly: '{{ translate("messages.move_your_head_slightly") }}',
                livenessDetected: '{{ translate("messages.liveness_detected") }}',
                faceVerified: '{{ translate("messages.face_verified") }}',
                faceNotMatched: '{{ translate("messages.face_not_matched") }}',
                faceComparisonError: '{{ translate("messages.face_comparison_error") }}',
                storedFaceDataInvalid: '{{ translate("messages.stored_face_data_invalid") }}',
                punchFailed: '{{ translate("messages.punch_failed") }}',
                punchInVerification: '{{ translate("messages.punch_in_verification") }}',
                punchOutVerification: '{{ translate("messages.punch_out_verification") }}',
                pleaseFillAllFields: '{{ translate("messages.please_fill_all_fields") }}',
                noteSavedSuccessfully: '{{ translate("messages.note_saved_successfully") }}',
                deleteNoteConfirm: '{{ translate("messages.delete_note_confirm") }}',
                noteDeleted: '{{ translate("messages.note_deleted") }}',
                goodMorning: '{{ translate("messages.good_morning") }}',
                goodAfternoon: '{{ translate("messages.good_afternoon") }}',
                goodEvening: '{{ translate("messages.good_evening") }}'
            }
        };
    </script>

    <script src="{{asset('public/assets/admin/js/face-recognition-utils.js')}}?v={{ time() }}"></script>
    <script>
        "use strict";
        let options;
        let chart;

        @if(auth('admin')->user()->role_id == 1)
        options = {
            series: [{{ $data['customer']}}, {{$data['stores']}}, {{$data['delivery_man']}}],
            chart: {
                width: 320,
                type: 'donut',
            },
            labels: ['{{ translate('Customer') }}', '{{ translate('Store') }}', '{{ translate('Delivery man') }}'],
            dataLabels: {
                enabled: false,
                style: {
                    colors: ['#1e293b', '#3b82f6', '#94a3b8']
                }
            },
            responsive: [{
                breakpoint: 1650,
                options: {
                    chart: { width: 250 },
                }
            }],
            colors: ['#1e293b','#3b82f6', '#94a3b8'],
            fill: {
                colors: ['#1e293b','#3b82f6', '#94a3b8']
            },
            legend: { show: false },
        };

        chart = new ApexCharts(document.querySelector("#dognut-pie"), options);
        chart.render();

        options = {
            series: [{
                name: '{{ translate('Gross Sale') }}',
                data: [{{ implode(",", is_array($total_sell) ? $total_sell : []) }}]
            },{
                name: '{{ translate('Admin Comission') }}',
                data: [{{ implode(",", is_array($commission) ? $commission : []) }}]
            },{
                name: '{{ translate('Delivery Comission') }}',
                data: [{{ implode(",", is_array($delivery_commission) ? $delivery_commission : []) }}]
            }],
            chart: {
                height: 350,
                type: 'area',
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            colors: ['#3b82f6','#ef4444', '#1e293b'],
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: 2,
                colors: ['#3b82f6','#ef4444', '#1e293b'],
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.15,
                    opacityTo: 0.02,
                },
                colors: ['#3b82f6','#ef4444', '#1e293b'],
            },
            xaxis: {
                categories: [{!! implode(",", is_array($label) ? $label : []) !!}],
                labels: { style: { colors: '#64748b', fontSize: '11px' } },
            },
            yaxis: {
                labels: { style: { colors: '#64748b', fontSize: '11px' } },
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4,
            },
            tooltip: {
                x: { format: 'dd/MM/yy HH:mm' },
            },
        };

        chart = new ApexCharts(document.querySelector("#grow-sale-chart"), options);
        chart.render();
        @endif

        @if(auth('admin')->user()->role_id != 1)
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof loadFaceModels === 'function') loadFaceModels();
            if (typeof loadAttendanceStatus === 'function') loadAttendanceStatus();
            if (typeof loadEmployeeStats === 'function') loadEmployeeStats();
            if (typeof updateEmployeeClock === 'function') {
                updateEmployeeClock();
                setInterval(updateEmployeeClock, 1000);
            }
            if (typeof loadNotes === 'function') loadNotes();
        });
        @endif

        Chart.plugins.unregister(ChartDataLabels);

        $('.js-chart').each(function () {
            $.HSCore.components.HSChartJS.init($(this));
        });

        let updatingChart = $.HSCore.components.HSChartJS.init($('#updatingData'));

        $('.order_stats_update').on('change', function (){
            let type = $(this).val();
            $('#custom_date_picker').val('');
            order_stats_update(type);
        })

        $('#custom_date_picker').on('change', function (){
            let date = $(this).val();
            if (date) {
                $('input[name="statistics"]').prop('checked', false);
                order_stats_update('custom_date', date);
            }
        })

        function order_stats_update(type, custom_date) {
            let postData = { statistics_type: type };
            if (custom_date) {
                postData.custom_date = custom_date;
            }
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.order')}}',
                data: postData,
                beforeSend: function () { $('#loading').show() },
                success: function (data) {
                    insert_param('statistics_type',type);
                    $('#order_stats').html(data.view)
                },
                complete: function () { $('#loading').hide() }
            });
        }

        $('.fetch_data_zone_wise').on('change', function (){
            let zone_id = $(this).val();
            fetch_data_zone_wise(zone_id);
        })

        function fetch_data_zone_wise(zone_id) {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.zone')}}',
                data: { zone_id: zone_id },
                beforeSend: function () { $('#loading').show() },
                success: function (data) {
                    insert_param('zone_id', zone_id);
                    $('#order_stats').html(data.order_stats);
                    $('#user-overview-board').html(data.user_overview);
                    $('#monthly-earning-graph').html(data.monthly_graph);
                    $('#popular-restaurants-view').html(data.popular_restaurants);
                    $('#top-deliveryman-view').html(data.top_deliveryman);
                    $('#top-rated-foods-view').html(data.top_rated_foods);
                    $('#top-restaurants-view').html(data.top_restaurants);
                    $('#top-selling-foods-view').html(data.top_selling_foods);
                    $('#stat_zone').html(data.stat_zone);
                },
                complete: function () { $('#loading').hide() }
            });
        }

        $('.user_overview_stats_update').on('change', function (){
            let type = $(this).val();
            user_overview_stats_update(type);
        })

        function user_overview_stats_update(type) {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.user-overview')}}',
                data: { user_overview: type },
                beforeSend: function () { $('#loading').show() },
                success: function (data) {
                    insert_param('user_overview',type);
                    $('#user-overview-board').html(data.view)
                },
                complete: function () { $('#loading').hide() }
            });
        }

        $('.commission_overview_stats_update').on('change', function (){
            let type = $(this).val();
            commission_overview_stats_update(type);
        })

        function commission_overview_stats_update(type) {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });
            $.post({
                url: '{{route('admin.dashboard-stats.commission-overview')}}',
                data: { commission_overview: type },
                beforeSend: function () { $('#loading').show() },
                success: function (data) {
                    insert_param('commission_overview',type);
                    $('#commission-overview-board').html(data.view)
                    $('#gross_sale').html(data.gross_sale)
                },
                complete: function () { $('#loading').hide() }
            });
        }

        function insert_param(key, value) {
            key = encodeURIComponent(key);
            value = encodeURIComponent(value);
            let kvp = document.location.search.substr(1).split('&');
            let i = 0;
            for (; i < kvp.length; i++) {
                if (kvp[i].startsWith(key + '=')) {
                    let pair = kvp[i].split('=');
                    pair[1] = value;
                    kvp[i] = pair.join('=');
                    break;
                }
            }
            if (i >= kvp.length) {
                kvp[kvp.length] = [key, value].join('=');
            }
            let params = kvp.join('&');
            window.history.pushState('page2', 'Title', '{{url()->current()}}?' + params);
        }

        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const clockEl = document.getElementById('live-clock');
            const dateEl = document.getElementById('live-date');
            if(clockEl) clockEl.textContent = now.toLocaleTimeString('en-US', timeOptions);
            if(dateEl) dateEl.textContent = now.toLocaleDateString('en-US', dateOptions);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Sticky Notes
        const ADMIN_NOTES_KEY = 'admin_notes_{{ auth("admin")->user()->id }}';

        function getNotes() {
            const notes = localStorage.getItem(ADMIN_NOTES_KEY);
            return notes ? JSON.parse(notes) : [];
        }

        function saveNotes(notes) {
            localStorage.setItem(ADMIN_NOTES_KEY, JSON.stringify(notes));
        }

        function renderNotes() {
            const container = document.getElementById('sticky-notes-container');
            if(!container) return;
            const notes = getNotes();
            if(notes.length === 0) {
                container.innerHTML = '<div class="text-center p-3" style="color: var(--dash-text-muted); font-size: 13px;">{{ translate("No notes yet. Click + to add one.") }}</div>';
                return;
            }
            container.innerHTML = notes.map((note, index) => `
                <div class="note-card" style="background: ${note.color || '#fff9c4'};">
                    <div class="note-date">${note.date}</div>
                    <button class="note-delete" onclick="deleteNote(${index})"><i class="tio-clear"></i></button>
                    <p class="mb-0 mt-1" style="white-space: pre-wrap; font-size: 13px;">${escapeHtml(note.text)}</p>
                </div>
            `).join('');
        }

        function addNewNote() {
            Swal.fire({
                title: '{{ translate("Add Note") }}',
                html: `
                    <textarea id="note-text" class="form-control mb-2" rows="4" placeholder="{{ translate('Write your note...') }}" style="border-radius: 8px;"></textarea>
                    <select id="note-color" class="form-control" style="border-radius: 8px;">
                        <option value="#fff9c4">{{ translate('Yellow') }}</option>
                        <option value="#c8e6c9">{{ translate('Green') }}</option>
                        <option value="#bbdefb">{{ translate('Blue') }}</option>
                        <option value="#ffccbc">{{ translate('Orange') }}</option>
                        <option value="#f8bbd9">{{ translate('Pink') }}</option>
                    </select>
                `,
                showCancelButton: true,
                confirmButtonText: '{{ translate("Save") }}',
                cancelButtonText: '{{ translate("Cancel") }}',
                preConfirm: () => {
                    const text = document.getElementById('note-text').value.trim();
                    const color = document.getElementById('note-color').value;
                    if(!text) {
                        Swal.showValidationMessage('{{ translate("Please enter a note") }}');
                        return false;
                    }
                    return { text, color };
                }
            }).then((result) => {
                if(result.isConfirmed) {
                    const notes = getNotes();
                    notes.unshift({
                        text: result.value.text,
                        color: result.value.color,
                        date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
                    });
                    saveNotes(notes);
                    renderNotes();
                    toastr.success('{{ translate("Note added!") }}');
                }
            });
        }

        function deleteNote(index) {
            Swal.fire({
                title: '{{ translate("Delete this note?") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: '{{ translate("Delete") }}',
                cancelButtonText: '{{ translate("Cancel") }}'
            }).then((result) => {
                if(result.isConfirmed) {
                    const notes = getNotes();
                    notes.splice(index, 1);
                    saveNotes(notes);
                    renderNotes();
                    toastr.success('{{ translate("Note deleted!") }}');
                }
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', renderNotes);

        // ===== Dashboard Break System =====
        @if(auth('admin')->user()->role_id != 1)
        var dashBreakTimerInterval = null;
        var dashBreakStartTime = null;

        function dashPollBreakStatus() {
            $.get('{{ route("admin.breaks.status") }}', function(data) {
                var startBtn = document.getElementById('start-break-btn');
                var endBtn = document.getElementById('end-break-btn');
                var breakInfo = document.getElementById('dash-break-info');
                if (!startBtn) return;

                if (!data.has_attendance) {
                    startBtn.style.display = 'none';
                    endBtn.style.display = 'none';
                    if (breakInfo) breakInfo.style.display = 'none';
                    return;
                }

                if (data.on_break) {
                    startBtn.style.display = 'none';
                    endBtn.style.display = 'inline-flex';
                    if (data.active_break) {
                        dashBreakStartTime = data.active_break.break_start;
                        dashStartBreakTimer();
                    }
                } else {
                    startBtn.style.display = 'inline-flex';
                    endBtn.style.display = 'none';
                    dashStopBreakTimer();
                }

                // Update break info strip
                if (breakInfo && (data.total_break_minutes > 0 || data.expected_shift_end)) {
                    breakInfo.style.display = 'flex';
                    var totalEl = document.getElementById('dash-break-total');
                    var extraEl = document.getElementById('dash-break-extra');
                    var endEl = document.getElementById('dash-expected-end');
                    if (totalEl) totalEl.textContent = data.total_break_minutes + '/' + data.allocated_break_minutes + ' min';
                    if (extraEl) {
                        if (data.extra_break_minutes > 0) {
                            extraEl.textContent = '+' + data.extra_break_minutes + ' min extra';
                            extraEl.style.display = 'inline';
                        } else {
                            extraEl.style.display = 'none';
                        }
                    }
                    if (endEl && data.expected_shift_end) {
                        endEl.textContent = 'Expected End: ' + data.expected_shift_end;
                    }
                }
            });
        }

        function dashStartBreak() {
            $.post('{{ route("admin.breaks.start") }}', {_token: '{{ csrf_token() }}'}, function(data) {
                if (data.success) {
                    toastr.success(data.message);
                    dashPollBreakStatus();
                }
            }).fail(function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Error starting break');
            });
        }

        function dashEndBreak() {
            $.post('{{ route("admin.breaks.end") }}', {_token: '{{ csrf_token() }}'}, function(data) {
                if (data.success) {
                    toastr.success(data.message + ' (' + data.duration_minutes + ' min)');
                    dashPollBreakStatus();
                }
            }).fail(function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Error ending break');
            });
        }

        function dashStartBreakTimer() {
            dashStopBreakTimer();
            dashBreakTimerInterval = setInterval(function() {
                if (!dashBreakStartTime) return;
                var now = new Date();
                var parts = dashBreakStartTime.split(':');
                var start = new Date();
                start.setHours(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2]));
                var diff = Math.floor((now - start) / 1000);
                var m = Math.floor(diff / 60);
                var s = diff % 60;
                var timerEl = document.getElementById('dash-break-timer');
                if (timerEl) timerEl.textContent = '(' + m + ':' + (s < 10 ? '0' : '') + s + ')';
            }, 1000);
        }

        function dashStopBreakTimer() {
            if (dashBreakTimerInterval) {
                clearInterval(dashBreakTimerInterval);
                dashBreakTimerInterval = null;
            }
            var timerEl = document.getElementById('dash-break-timer');
            if (timerEl) timerEl.textContent = '';
        }

        $(document).ready(function() {
            dashPollBreakStatus();
            setInterval(dashPollBreakStatus, 30000);
        });
        @endif

        // Dashboard Presence Tracking (Heartbeat)
        // ONLY runs on grocery dashboard page
        @if(Request::is('admin') || Request::is('admin/'))
        var dashboardHeartbeatInterval;

        function sendDashboardHeartbeat() {
            // Double check we're still on dashboard page
            if (!window.location.pathname.match(/^\/admin\/?$/)) {
                console.log('Not on dashboard, stopping heartbeat');
                clearInterval(dashboardHeartbeatInterval);
                return;
            }

            $.ajax({
                url: '{{ route("admin.dashboard.heartbeat") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    console.log('✓ Dashboard heartbeat sent:', response.timestamp);
                },
                error: function(xhr) {
                    console.error('✗ Dashboard heartbeat failed:', xhr.statusText);
                }
            });
        }

        // Send initial heartbeat on page load
        $(document).ready(function() {
            console.log('Dashboard presence tracking initialized');
            sendDashboardHeartbeat();

            // Send heartbeat every 30 seconds
            dashboardHeartbeatInterval = setInterval(sendDashboardHeartbeat, 30000);
        });

        // Clear heartbeat when leaving page
        $(window).on('beforeunload', function() {
            clearInterval(dashboardHeartbeatInterval);
        });
        @endif

        // Auto-refresh dashboard presence widget (Super Admin only)
        @if(auth('admin')->check() && auth('admin')->user()->role_id === 1)
        function refreshDashboardPresence() {
            $.ajax({
                url: '{{ route("admin.dashboard.presence") }}',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        var widget = $('#dashboard-presence-widget');
                        var list = $('#dashboard-presence-list');
                        var count = $('#dashboard-presence-count');
                        var updated = $('#dashboard-presence-updated');

                        // Update count
                        var countText = response.count + ' ' + (response.count == 1 ? 'employee' : 'employees') + ' viewing dashboard now';
                        count.text(countText);

                        // Update timestamp
                        var now = new Date();
                        var timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        updated.text('Updated: ' + timeStr);

                        // Show/hide widget
                        if (response.count > 0) {
                            widget.show();

                            // Rebuild employee list
                            var html = '';
                            response.employees.forEach(function(emp) {
                                html += '<div class="col-md-3 col-sm-6">';
                                html += '  <div style="background: white; border-radius: 10px; padding: 12px; border: 1px solid #d1fae5; display: flex; align-items: center; gap: 10px; transition: all 0.2s;">';
                                html += '    <div style="position: relative;">';
                                html += '      <img src="' + emp.image + '" alt="' + emp.name + '" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #10b981;">';
                                html += '      <span style="position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; background: #10b981; border: 2px solid white; border-radius: 50%;"></span>';
                                html += '    </div>';
                                html += '    <div style="flex: 1; min-width: 0;">';
                                html += '      <div style="font-weight: 600; font-size: 13px; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' + emp.name + '">' + emp.name + '</div>';
                                html += '      <div style="font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' + emp.role + '"><i class="tio-briefcase" style="font-size: 10px;"></i> ' + emp.role + '</div>';
                                html += '      <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;"><i class="tio-visible" style="color: #10b981;"></i> On Dashboard</div>';
                                html += '    </div>';
                                html += '  </div>';
                                html += '</div>';
                            });
                            list.html(html);
                        } else {
                            widget.hide();
                        }

                        console.log('✓ Dashboard presence updated: ' + response.count + ' employees');
                    }
                },
                error: function(xhr) {
                    console.error('✗ Failed to refresh dashboard presence:', xhr.statusText);
                }
            });
        }

        // Refresh every 15 seconds
        $(document).ready(function() {
            setInterval(refreshDashboardPresence, 15000);
        });
        @endif
    </script>
@endpush
