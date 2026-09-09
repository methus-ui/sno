@extends('layouts.admin.app')

@section('title', translate('messages.my_shift'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{translate('messages.my_shift_schedule')}}</h1>
            </div>
        </div>
    </div>

    <!-- Week Navigation -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-center">
                <a href="{{ route('admin.shift-roster.my-shift', ['week_start' => $weekStart->copy()->subWeek()->format('Y-m-d')]) }}" class="btn btn-sm btn-white mr-3">
                    <i class="tio-chevron-left"></i>
                </a>
                <h5 class="mb-0">{{ $weekStart->format('M d') }} - {{ $weekStart->copy()->addDays(6)->format('M d, Y') }}</h5>
                <a href="{{ route('admin.shift-roster.my-shift', ['week_start' => $weekStart->copy()->addWeek()->format('Y-m-d')]) }}" class="btn btn-sm btn-white ml-3">
                    <i class="tio-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Shift Cards -->
    <div class="row">
        @foreach($days as $i => $day)
            @php
                $roster = $rosters[$i] ?? null;
                $date = $weekStart->copy()->addDays($i);
                $isToday = $date->isToday();
            @endphp
            <div class="col-md-3 col-lg mb-3">
                <div class="card h-100 {{ $isToday ? 'border-primary' : '' }}">
                    <div class="card-header text-center {{ $isToday ? 'bg-primary text-white' : '' }}">
                        <h6 class="mb-0 {{ $isToday ? 'text-white' : '' }}">{{ $day }}</h6>
                        <small>{{ $date->format('d M') }}</small>
                    </div>
                    <div class="card-body text-center d-flex align-items-center justify-content-center">
                        @if($roster && $roster->is_off_day)
                            <div>
                                <span class="badge badge-soft-danger p-2 px-3" style="font-size:14px">OFF</span>
                            </div>
                        @elseif($roster)
                            <div>
                                <div class="text-success font-weight-bold" style="font-size:16px">
                                    {{ \Carbon\Carbon::parse($roster->shift_start)->format('h:i A') }}
                                </div>
                                <small class="text-muted">{{translate('messages.to')}}</small>
                                <div class="text-danger font-weight-bold" style="font-size:16px">
                                    {{ \Carbon\Carbon::parse($roster->shift_end)->format('h:i A') }}
                                </div>
                                @if($roster->template)
                                    <small class="text-muted">{{ $roster->template->name }}</small>
                                @endif
                            </div>
                        @else
                            <span class="text-muted">{{translate('messages.not_assigned')}}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
