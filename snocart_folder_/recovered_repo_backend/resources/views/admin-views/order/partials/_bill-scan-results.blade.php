{{-- Bill Scan Results Partial --}}
@if(isset($scanResult) && $scanResult)
    @php
        $overallStatus = $scanResult['overall_status'] ?? 'unknown';
        $checks = $scanResult['checks'] ?? [];
        $rawExtraction = $scanResult['raw_extraction'] ?? [];
        $scannedAt = $scanResult['scanned_at'] ?? null;
        $statusClass = $overallStatus === 'pass' ? 'success' : ($overallStatus === 'warn' ? 'warning' : 'danger');
        $bgColor = $overallStatus === 'pass' ? '#e8f5e9' : ($overallStatus === 'warn' ? '#fff8e1' : '#ffebee');
    @endphp

    <div class="bill-scan-results mt-3 p-3 border rounded border-{{ $statusClass }}" style="background-color: {{ $bgColor }};">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="m-0">
                <span class="text-{{ $statusClass }}" style="font-size:18px;">&#10003;</span>
                {{ translate('messages.ai_bill_verification') }}
            </h6>
            <span class="badge badge-{{ $statusClass }}">
                {{ strtoupper($overallStatus) }}
            </span>
        </div>

        @if($scannedAt)
            <small class="text-muted d-block mb-2">
                {{ translate('messages.scanned_at') }}: {{ \Carbon\Carbon::parse($scannedAt)->format('d M Y, h:i A') }}
            </small>
        @endif

        {{-- Checks List --}}
        <div class="scan-checks-list">
            {{-- Store Name --}}
            @if(isset($checks['store_name']))
                @php($check = $checks['store_name'])
                <div class="d-flex align-items-start mb-2 p-2" style="background:#f8f9fa;border-radius:4px;">
                    <span class="mr-2">
                        @if($check['status'] === 'pass')
                            <span class="text-success" style="font-size:16px;">&#10003;</span>
                        @elseif($check['status'] === 'warn')
                            <span class="text-warning" style="font-size:16px;">&#9888;</span>
                        @else
                            <span class="text-danger" style="font-size:16px;">&#10007;</span>
                        @endif
                    </span>
                    <div class="flex-grow-1">
                        <strong>{{ translate('messages.store_name') }}</strong><br>
                        <small class="text-muted">{{ translate('messages.expected') }}:</small> {{ $check['expected'] ?? 'N/A' }}<br>
                        <small class="text-muted">{{ translate('messages.found') }}:</small> {{ $check['found'] ?? 'N/A' }}
                    </div>
                </div>
            @endif

            {{-- Total Price --}}
            @if(isset($checks['total_price']))
                @php($check = $checks['total_price'])
                <div class="d-flex align-items-start mb-2 p-2" style="background:#f8f9fa;border-radius:4px;">
                    <span class="mr-2">
                        @if($check['status'] === 'pass')
                            <span class="text-success" style="font-size:16px;">&#10003;</span>
                        @elseif($check['status'] === 'warn')
                            <span class="text-warning" style="font-size:16px;">&#9888;</span>
                        @else
                            <span class="text-danger" style="font-size:16px;">&#10007;</span>
                        @endif
                    </span>
                    <div class="flex-grow-1">
                        <strong>{{ translate('messages.total_price') }}</strong><br>
                        <small class="text-muted">{{ translate('messages.expected') }}:</small> {{ \App\CentralLogics\Helpers::format_currency($check['expected'] ?? 0) }}<br>
                        <small class="text-muted">{{ translate('messages.found') }}:</small> {{ \App\CentralLogics\Helpers::format_currency($check['found'] ?? 0) }}
                        @if(isset($check['difference']) && $check['difference'] > 0)
                            <span class="text-{{ $check['status'] === 'fail' ? 'danger' : 'warning' }} ml-1">
                                ({{ translate('messages.difference') }}: {{ \App\CentralLogics\Helpers::format_currency($check['difference']) }})
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Customer Name --}}
            @if(isset($checks['customer_name']))
                @php($check = $checks['customer_name'])
                <div class="d-flex align-items-start mb-2 p-2" style="background:#f8f9fa;border-radius:4px;">
                    <span class="mr-2">
                        @if($check['status'] === 'pass')
                            <span class="text-success" style="font-size:16px;">&#10003;</span>
                        @elseif($check['status'] === 'warn')
                            <span class="text-warning" style="font-size:16px;">&#9888;</span>
                        @else
                            <span class="text-danger" style="font-size:16px;">&#10007;</span>
                        @endif
                    </span>
                    <div class="flex-grow-1">
                        <strong>{{ translate('messages.customer_name') }}</strong><br>
                        <small class="text-muted">{{ translate('messages.expected') }}:</small> {{ $check['expected'] ?? 'SnoCart' }}<br>
                        <small class="text-muted">{{ translate('messages.found') }}:</small> {{ $check['found'] ?? 'N/A' }}
                    </div>
                </div>
            @endif

            {{-- Date --}}
            @if(isset($checks['date']))
                @php($check = $checks['date'])
                <div class="d-flex align-items-start mb-2 p-2" style="background:#f8f9fa;border-radius:4px;">
                    <span class="mr-2">
                        @if($check['status'] === 'pass')
                            <span class="text-success" style="font-size:16px;">&#10003;</span>
                        @elseif($check['status'] === 'warn')
                            <span class="text-warning" style="font-size:16px;">&#9888;</span>
                        @else
                            <span class="text-danger" style="font-size:16px;">&#10007;</span>
                        @endif
                    </span>
                    <div class="flex-grow-1">
                        <strong>{{ translate('messages.date') }}</strong><br>
                        <small class="text-muted">{{ translate('messages.expected') }}:</small> {{ $check['expected'] ?? 'N/A' }}<br>
                        <small class="text-muted">{{ translate('messages.found') }}:</small> {{ $check['found'] ?? 'N/A' }}
                    </div>
                </div>
            @endif

            {{-- Discount --}}
            @if(isset($checks['discount']))
                @php($check = $checks['discount'])
                <div class="d-flex align-items-start mb-2 p-2" style="background:#f8f9fa;border-radius:4px;">
                    <span class="mr-2">
                        @if($check['status'] === 'pass')
                            <span class="text-success" style="font-size:16px;">&#10003;</span>
                        @elseif($check['status'] === 'warn')
                            <span class="text-warning" style="font-size:16px;">&#9888;</span>
                        @else
                            <span class="text-danger" style="font-size:16px;">&#10007;</span>
                        @endif
                    </span>
                    <div class="flex-grow-1">
                        <strong>{{ translate('messages.discount') }}</strong><br>
                        <small class="text-muted">{{ translate('messages.expected') }}:</small> {{ \App\CentralLogics\Helpers::format_currency($check['expected'] ?? 0) }}<br>
                        <small class="text-muted">{{ translate('messages.found') }}:</small> {{ \App\CentralLogics\Helpers::format_currency($check['found'] ?? 0) }}
                    </div>
                </div>
            @endif

            {{-- Items Table --}}
            @if(isset($checks['items']) && count($checks['items']) > 0)
                <div class="mt-3">
                    <strong>{{ translate('messages.item_prices') }}:</strong>
                    <table class="table table-sm table-bordered mt-2" style="font-size: 12px;">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('messages.item') }}</th>
                                <th>{{ translate('messages.expected') }}</th>
                                <th>{{ translate('messages.found') }}</th>
                                <th>{{ translate('messages.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($checks['items'] as $item)
                                <tr>
                                    <td>{{ $item['name'] ?? 'Unknown' }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($item['expected_price'] ?? 0) }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($item['found_price'] ?? 0) }}</td>
                                    <td class="text-center">
                                        @if($item['status'] === 'pass')
                                            <span class="text-success">&#10003;</span>
                                        @elseif($item['status'] === 'warn')
                                            <span class="text-warning">&#9888;</span>
                                        @else
                                            <span class="text-danger">&#10007;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Extra Items --}}
            @if(isset($checks['extra_items']) && count($checks['extra_items']) > 0)
                <div class="mt-3 p-2" style="background:#fff3cd;border-radius:4px;">
                    <strong class="text-warning">&#9888; {{ translate('messages.extra_items_on_bill') }}:</strong>
                    <table class="table table-sm table-bordered mt-2 mb-0" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>{{ translate('messages.item') }}</th>
                                <th>{{ translate('messages.qty') }}</th>
                                <th>{{ translate('messages.price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($checks['extra_items'] as $extraItem)
                                <tr>
                                    <td>{{ $extraItem['name'] ?? 'Unknown' }}</td>
                                    <td>{{ $extraItem['quantity'] ?? 1 }}</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($extraItem['price'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif
