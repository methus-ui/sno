@extends('layouts.admin.app')

@section('title', translate('messages.system_check'))

@push('css_or_js')
<style>
    .troubleshoot-toggle { cursor:pointer; color:#0d6efd; font-size:13px; }
    .troubleshoot-toggle:hover { text-decoration:underline; }
    .troubleshoot-body { display:none; background:#f8f9fa; border-radius:6px; padding:12px 14px; margin-top:10px; font-size:13px; }
    .troubleshoot-body ul { margin-bottom:0; padding-left:18px; }
    .troubleshoot-body li { margin-bottom:4px; }
    .troubleshoot-body code { font-size:12px; background:#e9ecef; padding:1px 5px; border-radius:3px; }
    .ai-diagnose-btn { font-size:12px; padding:3px 10px; }
    .ai-diagnosis-content { white-space: pre-wrap; font-size: 14px; line-height: 1.6; }
    .ai-diagnosis-content code { background:#e9ecef; padding:1px 5px; border-radius:3px; font-size:13px; }
    .ai-diagnosis-content strong { color:#333; }
    .ai-diagnosis-content ul, .ai-diagnosis-content ol { padding-left:20px; }
    #aiDiagnosisModal .modal-body { max-height:70vh; overflow-y:auto; }
    .ai-loading { text-align:center; padding:40px 20px; }
    .ai-loading .spinner-border { width:2rem; height:2rem; }
    .copy-cmd { cursor:pointer; font-size:11px; color:#0d6efd; margin-left:6px; }
    .copy-cmd:hover { text-decoration:underline; }
</style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title mr-3">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/config.png') }}" class="w--26" alt="">
                </span>
                <span>{{ translate('messages.system_check') }}</span>
            </h1>
        </div>

        <div class="row g-3">
            {{-- MySQL --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-database mr-1"></i> MySQL</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('mysql', @json($checks['mysql']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['mysql']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        @if($checks['mysql']['status'] === 'ok')
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted">{{ translate('Version') }}</td><td class="text-right font-weight-bold">{{ $checks['mysql']['version'] }}</td></tr>
                                <tr><td class="text-muted">{{ translate('Uptime') }}</td><td class="text-right font-weight-bold">{{ gmdate('d\d H\h i\m', $checks['mysql']['uptime_seconds']) }}</td></tr>
                                <tr><td class="text-muted">{{ translate('Max Connections') }}</td><td class="text-right font-weight-bold">{{ $checks['mysql']['max_connections'] }}</td></tr>
                                <tr><td class="text-muted">{{ translate('Active Connections') }}</td><td class="text-right font-weight-bold">{{ $checks['mysql']['used_connections'] }}</td></tr>
                            </table>
                        @else
                            <p class="text-danger mb-0">{{ $checks['mysql']['message'] ?? 'Connection failed' }}</p>
                        @endif
                        <div>
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li>Verify <code>DB_HOST</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code> in <code>.env</code></li>
                                    <li>Check MySQL service is running: <code>sudo systemctl status mysql</code></li>
                                    <li>If max connections are nearly full, increase <code>max_connections</code> in MySQL config or investigate connection leaks</li>
                                    <li>Restart MySQL: <code>sudo systemctl restart mysql</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Disk Space --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-hard-drive mr-1"></i> {{ translate('Disk Space') }}</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('disk', @json($checks['disk']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['disk']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach(['storage' => 'Storage', 'root' => 'Root'] as $key => $label)
                            @php
                                $d = $checks['disk'][$key];
                                $usedPct = $d['total'] > 0 ? round((($d['total'] - $d['free']) / $d['total']) * 100) : 0;
                                $barColor = $usedPct > 90 ? 'danger' : ($usedPct > 75 ? 'warning' : 'success');
                            @endphp
                            <p class="mb-1 font-weight-bold">{{ $label }} ({{ $d['free'] }} GB {{ translate('free') }} / {{ $d['total'] }} GB {{ translate('total') }})</p>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-{{ $barColor }}" style="width: {{ $usedPct }}%"></div>
                            </div>
                        @endforeach
                        <div>
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li>Clear old logs: <code>php artisan log:clear</code> or delete files in <code>storage/logs/</code></li>
                                    <li>Remove cached data: <code>php artisan cache:clear</code></li>
                                    <li>Check large files: <code>du -sh storage/*</code></li>
                                    <li>Consider moving uploads to an external storage (S3) if disk is consistently full</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PHP --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-code mr-1"></i> PHP</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('php', @json($checks['php']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['php']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">{{ translate('Version') }}</td><td class="text-right font-weight-bold">{{ $checks['php']['version'] }}</td></tr>
                            <tr><td class="text-muted">{{ translate('Memory Limit') }}</td><td class="text-right font-weight-bold">{{ $checks['php']['memory_limit'] }}</td></tr>
                            <tr><td class="text-muted">{{ translate('Max Execution Time') }}</td><td class="text-right font-weight-bold">{{ $checks['php']['max_execution_time'] }}s</td></tr>
                            <tr>
                                <td class="text-muted">{{ translate('Extensions') }}</td>
                                <td class="text-right">
                                    @if(empty($checks['php']['missing_extensions']))
                                        <span class="badge badge-success">{{ translate('All loaded') }}</span>
                                    @else
                                        <span class="badge badge-warning">{{ translate('Missing') }}: {{ implode(', ', $checks['php']['missing_extensions']) }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        <div>
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li>Install missing extensions: <code>sudo apt install php{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}-EXTENSION_NAME</code></li>
                                    <li>Increase memory limit in <code>php.ini</code>: <code>memory_limit = 512M</code></li>
                                    <li>Increase execution time: <code>max_execution_time = 300</code></li>
                                    <li>After changes restart PHP-FPM: <code>sudo systemctl restart php{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}-fpm</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Laravel --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-star mr-1"></i> Laravel</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('laravel', @json($checks['laravel']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['laravel']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">{{ translate('Version') }}</td><td class="text-right font-weight-bold">{{ $checks['laravel']['laravel_version'] }}</td></tr>
                            <tr><td class="text-muted">{{ translate('Environment') }}</td><td class="text-right font-weight-bold">{{ $checks['laravel']['env'] }}</td></tr>
                            <tr>
                                <td class="text-muted">{{ translate('Debug Mode') }}</td>
                                <td class="text-right">
                                    <span class="badge badge-{{ $checks['laravel']['debug'] ? 'warning' : 'success' }}">{{ $checks['laravel']['debug'] ? 'ON' : 'OFF' }}</span>
                                </td>
                            </tr>
                            <tr><td class="text-muted">{{ translate('Cache Driver') }}</td><td class="text-right font-weight-bold">{{ $checks['laravel']['cache_driver'] }}</td></tr>
                            <tr><td class="text-muted">{{ translate('Queue Driver') }}</td><td class="text-right font-weight-bold">{{ $checks['laravel']['queue_driver'] }}</td></tr>
                            <tr><td class="text-muted">{{ translate('Log Channel') }}</td><td class="text-right font-weight-bold">{{ $checks['laravel']['log_channel'] }}</td></tr>
                        </table>
                        <div>
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li><strong>Debug ON in production</strong> exposes sensitive data. Set <code>APP_DEBUG=false</code> in <code>.env</code></li>
                                    <li>Clear config cache: <code>php artisan config:clear</code></li>
                                    <li>Queue driver <code>sync</code> blocks requests; use <code>redis</code> or <code>database</code> for production</li>
                                    <li>Optimize for production: <code>php artisan optimize</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Storage Permissions --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-folder-opened mr-1"></i> {{ translate('Storage Permissions') }}</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('permissions', @json($checks['permissions']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['permissions']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            @foreach($checks['permissions']['directories'] as $dir => $writable)
                                <tr>
                                    <td class="text-muted">{{ $dir }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-{{ $writable ? 'success' : 'danger' }}">{{ $writable ? translate('Writable') : translate('Not Writable') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                        <div>
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li>Fix ownership: <code>sudo chown -R www-data:www-data storage/ bootstrap/cache/</code></li>
                                    <li>Fix permissions: <code>sudo chmod -R 775 storage/ bootstrap/cache/</code></li>
                                    <li>Create missing dirs: <code>php artisan storage:link</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mail / SMTP --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-email mr-1"></i> {{ translate('Mail / SMTP') }}</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('mail', @json($checks['mail']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['mail']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">{{ translate('Driver') }}</td><td class="text-right font-weight-bold">{{ $checks['mail']['driver'] ?? 'N/A' }}</td></tr>
                            @if(isset($checks['mail']['host']))
                                <tr><td class="text-muted">{{ translate('Host') }}</td><td class="text-right font-weight-bold">{{ $checks['mail']['host'] }}</td></tr>
                            @endif
                            @if(isset($checks['mail']['message']))
                                <tr><td colspan="2" class="text-danger">{{ $checks['mail']['message'] }}</td></tr>
                            @endif
                        </table>
                        <div>
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li>Verify <code>MAIL_HOST</code>, <code>MAIL_PORT</code>, <code>MAIL_USERNAME</code>, <code>MAIL_PASSWORD</code> in <code>.env</code></li>
                                    <li>For Gmail use <code>MAIL_PORT=587</code>, <code>MAIL_ENCRYPTION=tls</code>, and an App Password</li>
                                    <li>Test connection: <code>php artisan tinker</code> then <code>Mail::raw('test', fn($m) => $m->to('you@mail.com'))</code></li>
                                    <li>Clear config after changes: <code>php artisan config:clear</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cache --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-flash mr-1"></i> {{ translate('Cache') }}</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('cache', @json($checks['cache']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['cache']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">{{ translate('Driver') }}</td><td class="text-right font-weight-bold">{{ $checks['cache']['driver'] }}</td></tr>
                            <tr>
                                <td class="text-muted">{{ translate('Put/Get Test') }}</td>
                                <td class="text-right">
                                    <span class="badge badge-{{ $checks['cache']['status'] === 'ok' ? 'success' : 'danger' }}">
                                        {{ $checks['cache']['status'] === 'ok' ? translate('Passed') : translate('Failed') }}
                                    </span>
                                </td>
                            </tr>
                            @if(isset($checks['cache']['message']))
                                <tr><td colspan="2" class="text-danger">{{ $checks['cache']['message'] }}</td></tr>
                            @endif
                        </table>
                        <div>
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li>If using Redis, check service: <code>sudo systemctl status redis</code></li>
                                    <li>Test Redis connectivity: <code>redis-cli ping</code> (should return PONG)</li>
                                    <li>Verify <code>REDIS_HOST</code> and <code>REDIS_PORT</code> in <code>.env</code></li>
                                    <li>Flush cache: <code>php artisan cache:clear</code></li>
                                    <li>Fallback to file driver: set <code>CACHE_DRIVER=file</code> in <code>.env</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Slow Query Log --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-time mr-1"></i> {{ translate('Slow Query Log') }}</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('slow_queries', @json($checks['slow_queries']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['slow_queries']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($checks['slow_queries']['message']))
                            <p class="text-muted mb-0">{{ $checks['slow_queries']['message'] }}</p>
                        @elseif(isset($checks['slow_queries']['queries']) && count($checks['slow_queries']['queries']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>{{ translate('Time') }}</th><th>{{ translate('Query') }}</th></tr></thead>
                                    <tbody>
                                    @foreach($checks['slow_queries']['queries'] as $q)
                                        <tr>
                                            <td class="text-nowrap">{{ $q->start_time ?? 'N/A' }}</td>
                                            <td><code class="small">{{ \Illuminate\Support\Str::limit($q->sql_text ?? '', 100) }}</code></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-success mb-0">{{ translate('No slow queries detected') }}</p>
                        @endif
                        <div class="mt-2">
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li>Enable slow query log: <code>SET GLOBAL slow_query_log = 'ON';</code></li>
                                    <li>Set threshold: <code>SET GLOBAL long_query_time = 2;</code> (seconds)</li>
                                    <li>Add missing indexes for frequently slow queries</li>
                                    <li>Use <code>EXPLAIN</code> to analyze slow queries</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cron / Scheduler --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-alarm mr-1"></i> {{ translate('Cron / Scheduler') }}</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('cron', @json($checks['cron']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['cron']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            @if(isset($checks['cron']['last_run']))
                                <tr><td class="text-muted">{{ translate('Last Run') }}</td><td class="text-right font-weight-bold">{{ $checks['cron']['last_run'] }}</td></tr>
                                <tr><td class="text-muted">{{ translate('Minutes Ago') }}</td><td class="text-right font-weight-bold">{{ $checks['cron']['minutes_ago'] }}</td></tr>
                            @endif
                            @if(isset($checks['cron']['last_entry']))
                                <tr><td colspan="2"><code class="small">{{ $checks['cron']['last_entry'] }}</code></td></tr>
                            @endif
                            @if(isset($checks['cron']['message']))
                                <tr><td colspan="2" class="text-muted">{{ $checks['cron']['message'] }}</td></tr>
                            @endif
                        </table>
                        <div>
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li>Verify crontab entry: <code>crontab -l -u www-data</code></li>
                                    <li>Expected entry: <code>* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1</code></li>
                                    <li>Test manually: <code>php artisan schedule:run</code></li>
                                    <li>Check cron log: <code>tail -20 storage/logs/cron.log</code></li>
                                    <li>Log scheduler output by replacing <code>/dev/null</code> with a log file path in crontab</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SSL Certificate --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="tio-lock mr-1"></i> {{ translate('SSL Certificate') }}</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary ai-diagnose-btn" onclick="aiDiagnose('ssl', @json($checks['ssl']))">
                                <i class="tio-bulb-outlined mr-1"></i>{{ translate('Diagnose with AI') }}
                            </button>
                            @include('admin-views.business-settings.partials._system-check-badge', ['status' => $checks['ssl']['status']])
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($checks['ssl']['valid_until']))
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted">{{ translate('Valid Until') }}</td><td class="text-right font-weight-bold">{{ $checks['ssl']['valid_until'] }}</td></tr>
                                <tr><td class="text-muted">{{ translate('Days Remaining') }}</td><td class="text-right font-weight-bold">{{ $checks['ssl']['days_remaining'] }}</td></tr>
                                <tr><td class="text-muted">{{ translate('Issuer') }}</td><td class="text-right font-weight-bold">{{ $checks['ssl']['issuer'] }}</td></tr>
                            </table>
                        @else
                            <p class="text-muted mb-0">{{ $checks['ssl']['message'] ?? 'N/A' }}</p>
                        @endif
                        <div class="mt-2">
                            <span class="troubleshoot-toggle" onclick="$(this).next('.troubleshoot-body').slideToggle(200)">{{ translate('Troubleshooting') }}</span>
                            <div class="troubleshoot-body">
                                <ul>
                                    <li>Renew Let's Encrypt: <code>sudo certbot renew</code></li>
                                    <li>Check certificate: <code>openssl s_client -connect yourdomain.com:443</code></li>
                                    <li>Ensure <code>APP_URL</code> in <code>.env</code> uses <code>https://</code></li>
                                    <li>Set up auto-renewal via cron: <code>0 0 * * * certbot renew --quiet</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Diagnosis Modal --}}
    <div class="modal fade" id="aiDiagnosisModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="tio-bulb-outlined mr-1"></i>
                        {{ translate('AI Diagnosis') }} — <span id="aiCheckName"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="aiLoading" class="ai-loading">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3 text-muted">{{ translate('Claude is analyzing your system...') }}</p>
                    </div>
                    <div id="aiResult" class="ai-diagnosis-content" style="display:none;"></div>
                    <div id="aiError" class="text-danger" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="aiCopyBtn" style="display:none;" onclick="copyDiagnosis()">
                        <i class="tio-copy mr-1"></i>{{ translate('Copy') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    const checkLabels = {
        mysql: 'MySQL',
        disk: 'Disk Space',
        php: 'PHP',
        laravel: 'Laravel',
        permissions: 'Storage Permissions',
        mail: 'Mail / SMTP',
        cache: 'Cache',
        slow_queries: 'Slow Query Log',
        cron: 'Cron / Scheduler',
        ssl: 'SSL Certificate'
    };

    function aiDiagnose(check, checkData) {
        $('#aiCheckName').text(checkLabels[check] || check);
        $('#aiLoading').show();
        $('#aiResult').hide().empty();
        $('#aiError').hide().empty();
        $('#aiCopyBtn').hide();
        $('#aiDiagnosisModal').modal('show');

        $.ajax({
            url: '{{ route("admin.business-settings.system-check.diagnose") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                check: check,
                check_data: checkData
            },
            timeout: 60000,
            success: function(response) {
                $('#aiLoading').hide();
                if (response.diagnosis) {
                    let html = formatMarkdown(response.diagnosis);
                    $('#aiResult').html(html).show();
                    $('#aiCopyBtn').show();
                } else if (response.error) {
                    $('#aiError').text(response.error).show();
                }
            },
            error: function(xhr) {
                $('#aiLoading').hide();
                let msg = 'Failed to get AI diagnosis.';
                try {
                    let err = JSON.parse(xhr.responseText);
                    if (err.error) msg = err.error;
                } catch(e) {}
                $('#aiError').text(msg).show();
            }
        });
    }

    function formatMarkdown(text) {
        // Basic markdown to HTML conversion
        let html = text
            // Code blocks
            .replace(/```(\w*)\n([\s\S]*?)```/g, '<pre style="background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:6px;overflow-x:auto;font-size:13px;position:relative;"><code>$2</code><button class="btn btn-sm btn-outline-light copy-code-btn" style="position:absolute;top:6px;right:6px;font-size:11px;padding:2px 8px;" onclick="copyCode(this)">Copy</button></pre>')
            // Inline code
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            // Bold
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            // Headers
            .replace(/^### (.+)$/gm, '<h6 class="mt-3 mb-1">$1</h6>')
            .replace(/^## (.+)$/gm, '<h5 class="mt-3 mb-2">$1</h5>')
            .replace(/^# (.+)$/gm, '<h4 class="mt-3 mb-2">$1</h4>')
            // Unordered lists
            .replace(/^- (.+)$/gm, '<li>$1</li>')
            // Ordered lists
            .replace(/^\d+\. (.+)$/gm, '<li>$1</li>')
            // Line breaks
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');

        // Wrap consecutive <li> items in <ul>
        html = html.replace(/((?:<li>.*?<\/li>\s*(?:<br>)?)+)/g, '<ul>$1</ul>');
        html = html.replace(/<br><\/ul>/g, '</ul>');
        html = html.replace(/<ul><br>/g, '<ul>');

        return '<p>' + html + '</p>';
    }

    function copyCode(btn) {
        let code = $(btn).siblings('code').text();
        navigator.clipboard.writeText(code).then(function() {
            let orig = $(btn).text();
            $(btn).text('Copied!');
            setTimeout(() => $(btn).text(orig), 1500);
        });
    }

    function copyDiagnosis() {
        let text = $('#aiResult').text();
        navigator.clipboard.writeText(text).then(function() {
            let btn = $('#aiCopyBtn');
            let orig = btn.html();
            btn.html('<i class="tio-checkmark-circle mr-1"></i>{{ translate("Copied!") }}');
            setTimeout(() => btn.html(orig), 1500);
        });
    }
</script>
@endpush
