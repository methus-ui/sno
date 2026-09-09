@extends('layouts.admin.app')
@section('title', 'Delivery Man Leaderboard')
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="page-header-title"><i class="tio-trophy"></i> Leaderboard</h1>
            <form method="POST" action="{{ route('admin.transactions.dm-performance.leaderboard.generate') }}" class="d-inline">
                @csrf
                <input type="hidden" name="period_type" value="{{ $periodType }}">
                <button type="submit" class="btn btn-info"><i class="tio-refresh"></i> Generate Now</button>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.transactions.dm-performance.leaderboard.index') }}">
                <div class="row">
                    <div class="col-sm-3">
                        <select name="period_type" class="form-control">
                            <option value="daily" {{ $periodType == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ $periodType == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ $periodType == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <select name="zone_id" class="form-control">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ $zoneId == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th><th>Delivery Man</th><th>Deliveries</th><th>Earnings</th><th>Avg Rating</th><th>Acceptance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaderboard as $entry)
                        <tr class="{{ $entry['rank_position'] <= 3 ? 'table-warning' : '' }}">
                            <td>
                                @if($entry['rank_position'] == 1) <span class="text-warning h4">1</span>
                                @elseif($entry['rank_position'] == 2) <span class="text-secondary h5">2</span>
                                @elseif($entry['rank_position'] == 3) <span class="text-info h5">3</span>
                                @else {{ $entry['rank_position'] }}
                                @endif
                            </td>
                            <td>
                                @if($entry['delivery_man'])
                                    {{ $entry['delivery_man']['f_name'] }} {{ $entry['delivery_man']['l_name'] }}
                                @else N/A @endif
                            </td>
                            <td><strong>{{ $entry['deliveries_completed'] }}</strong></td>
                            <td>{{ \App\CentralLogics\Helpers::format_currency($entry['total_earnings']) }}</td>
                            <td>{{ $entry['avg_rating'] }} / 5</td>
                            <td>{{ $entry['acceptance_rate'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($leaderboard) === 0)
                <div class="empty--data text-center py-5">
                    <h5>No leaderboard data. Click "Generate Now" to create.</h5>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
