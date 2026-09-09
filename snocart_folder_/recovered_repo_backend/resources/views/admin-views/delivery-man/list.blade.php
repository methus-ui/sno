@extends('layouts.admin.app')

@section('title',translate('messages.deliverymen'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/delivery-man.png')}}" class="w--26" alt="">
                </span>
                <span>{{translate('messages.deliveryman')}}</span>
            </h1>
        </div>
        <!-- End Page Header -->


        <!-- Active Delivery Men Cards -->
        @if($activeDMs->count() > 0)
        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <span style="display:inline-block;width:11px;height:11px;background:#28a745;border-radius:50%;margin-right:8px;box-shadow:0 0 0 3px rgba(40,167,69,.2);"></span>
                <h6 class="mb-0 font-weight-bold text-dark">{{ translate('messages.online') }} {{ translate('messages.deliveryman') }}</h6>
                <span class="badge badge-success ml-2" style="font-size:12px;">{{ $activeDMs->count() }}</span>
                <a href="{{ url()->current() }}?filter=active" class="btn btn-xs btn-soft-success ml-auto" style="font-size:11px;padding:3px 10px;">{{ translate('messages.view_all') }}</a>
            </div>
            <div style="display:flex;gap:14px;overflow-x:auto;padding-bottom:6px;scrollbar-width:thin;">
                @foreach($activeDMs as $dm)
                <?php
                    $att = $dm->todayAttendance;
                    $activeTimeStr = '';
                    if ($att && $att->punch_in_time) {
                        $punchIn = \Carbon\Carbon::parse($att->punch_in_time);
                        $endTime = ($att->punch_out_time) ? \Carbon\Carbon::parse($att->punch_out_time) : now();
                        $totalMins = max(0, $punchIn->diffInMinutes($endTime) - ($att->total_offline_minutes ?? 0));
                        $h = floor($totalMins / 60);
                        $m = $totalMins % 60;
                        $activeTimeStr = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                    }
                ?>
                <div style="flex:0 0 auto;">
                    <div style="width:130px;background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.07);overflow:hidden;transition:transform .15s;border:1px solid #f0f0f0;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <a href="{{ route('admin.users.delivery-man.preview', [$dm->id]) }}" style="text-decoration:none;display:block;">
                            <div style="position:relative;">
                                <img src="{{ $dm->image_full_url }}"
                                     onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                     style="width:130px;height:88px;object-fit:cover;display:block;">
                                <span style="position:absolute;bottom:5px;right:6px;width:11px;height:11px;background:#28a745;border:2px solid #fff;border-radius:50%;display:inline-block;"></span>
                            </div>
                            <div style="padding:7px 8px 4px;text-align:center;">
                                <p style="margin:0 0 2px;font-size:11px;font-weight:600;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:114px;">{{ $dm->f_name }} {{ $dm->l_name }}</p>
                                @if($dm->zone)
                                <p style="margin:0 0 3px;font-size:10px;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:114px;">{{ $dm->zone->name }}</p>
                                @endif
                                @if($activeTimeStr)
                                <span style="display:inline-block;background:#e3f0ff;color:#0056b3;font-size:10px;font-weight:600;padding:1px 6px;border-radius:20px;margin-bottom:2px;" title="Active time today">⏱ {{ $activeTimeStr }}</span><br>
                                @endif
                                <span style="display:inline-block;background:#e8f8ed;color:#1a8c3e;font-size:10px;font-weight:600;padding:1px 7px;border-radius:20px;margin-bottom:2px;" title="{{ translate('messages.currently_assigned_orders') }}">
                                    {{ $dm->current_orders }} {{ translate('messages.active') }}
                                </span>
                                <span style="display:inline-block;background:#fff3cd;color:#856404;font-size:10px;font-weight:600;padding:1px 7px;border-radius:20px;" title="Orders today">
                                    📦 {{ $dm->today_orders_count ?? 0 }} {{ translate('messages.today') }}
                                </span>
                            </div>
                        </a>
                        <div style="padding:0 6px 7px;text-align:center;">
                            <a href="{{ route('admin.users.delivery-man.preview', [$dm->id, 'day_close']) }}" style="display:block;background:#343a40;color:#fff;font-size:10px;font-weight:600;padding:3px 0;border-radius:6px;text-decoration:none;letter-spacing:.3px;" title="Day Close">
                                🔒 {{ translate('messages.day_close') }}
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        <!-- End Active Delivery Men Cards -->

        <!-- Active Today Cards -->
        @if($todayActiveDMs->count() > 0)
        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <span style="display:inline-block;width:11px;height:11px;background:#007bff;border-radius:50%;margin-right:8px;box-shadow:0 0 0 3px rgba(0,123,255,.18);"></span>
                <h6 class="mb-0 font-weight-bold text-dark">{{ translate('messages.active_today') }}</h6>
                <span class="badge badge-primary ml-2" style="font-size:12px;">{{ $todayActiveDMs->count() }}</span>
            </div>
            <div style="display:flex;gap:14px;overflow-x:auto;padding-bottom:8px;scrollbar-width:thin;">
                @foreach($todayActiveDMs as $dm)
                <?php
                    $att = $dm->todayAttendance;
                    $activeTimeStr = '';
                    $punchInStr   = '';
                    $isOnline     = (bool)$dm->active;
                    if ($att && $att->punch_in_time) {
                        $punchIn  = \Carbon\Carbon::parse($att->punch_in_time);
                        $endTime  = ($att->punch_out_time) ? \Carbon\Carbon::parse($att->punch_out_time) : now();
                        $offlineMins = $att->total_offline_minutes ?? 0;
                        $totalMins   = max(0, $punchIn->diffInMinutes($endTime) - $offlineMins);
                        $h = floor($totalMins / 60); $m = $totalMins % 60;
                        $activeTimeStr = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                        $punchInStr    = $punchIn->format('h:i A');
                    }
                ?>
                <div style="flex:0 0 auto;">
                    <div style="width:130px;background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.07);overflow:hidden;transition:transform .15s;border:1.5px solid {{ $isOnline ? '#b6f0c8' : '#e2e8f0' }};"
                         onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <a href="{{ route('admin.users.delivery-man.preview', [$dm->id]) }}" style="text-decoration:none;display:block;">
                            <div style="position:relative;">
                                <img src="{{ $dm->image_full_url }}"
                                     onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                     style="width:130px;height:88px;object-fit:cover;display:block;">
                                {{-- Online/Offline dot --}}
                                <span style="position:absolute;bottom:6px;right:7px;width:11px;height:11px;background:{{ $isOnline ? '#28a745' : '#94a3b8' }};border:2px solid #fff;border-radius:50%;display:inline-block;" title="{{ $isOnline ? 'Online' : 'Offline' }}"></span>
                                {{-- Punch-in time chip --}}
                                @if($punchInStr)
                                <span style="position:absolute;top:5px;left:5px;background:rgba(0,0,0,.52);color:#fff;font-size:9px;font-weight:600;padding:1px 5px;border-radius:8px;">In {{ $punchInStr }}</span>
                                @endif
                            </div>
                            <div style="padding:7px 8px 4px;text-align:center;">
                                <p style="margin:0 0 1px;font-size:11px;font-weight:700;color:#1e2022;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:114px;">{{ $dm->f_name }} {{ $dm->l_name }}</p>
                                @if($dm->zone)
                                <p style="margin:0 0 3px;font-size:10px;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:114px;">{{ $dm->zone->name }}</p>
                                @endif
                                @if($activeTimeStr)
                                <span style="display:inline-block;background:#e3f0ff;color:#0056b3;font-size:10px;font-weight:600;padding:1px 6px;border-radius:20px;margin-bottom:3px;">⏱ {{ $activeTimeStr }}</span><br>
                                @endif
                                <span style="display:inline-block;font-size:10px;font-weight:600;padding:1px 7px;border-radius:20px;margin-bottom:2px;{{ $isOnline ? 'background:#e8f8ed;color:#1a8c3e;' : 'background:#f1f3f5;color:#6c757d;' }}">
                                    {{ $isOnline ? translate('messages.online') : translate('messages.offline') }}
                                </span>
                                <span style="display:inline-block;background:#fff3cd;color:#856404;font-size:10px;font-weight:600;padding:1px 7px;border-radius:20px;" title="Orders today">
                                    📦 {{ $dm->today_orders_count ?? 0 }} {{ translate('messages.today') }}
                                </span>
                            </div>
                        </a>
                        <div style="padding:0 6px 7px;text-align:center;">
                            <a href="{{ route('admin.users.delivery-man.preview', [$dm->id, 'day_close']) }}" style="display:block;background:#343a40;color:#fff;font-size:10px;font-weight:600;padding:3px 0;border-radius:6px;text-decoration:none;letter-spacing:.3px;" title="Day Close">
                                🔒 {{ translate('messages.day_close') }}
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        <!-- End Active Today Cards -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper justify-content-end">
                    <h5 class="card-title mr-auto">
                        {{translate('messages.deliveryman_list')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$deliveryMen->total()}}</span>
                    </h5>
                    <div class="min--200">
                        <select name="filter" class="form-control js-select2-custom set-filter" data-filter="filter"
                        data-url="{{ url()->full() }}">
                            <option  value="all">{{ translate('messages.All_Types') }}</option>
                            <option {{  request()?->get('filter') == 'active' ? 'selected' : '' }}  value="active">{{ translate('messages.Online') }}</option>
                            <option  {{  request()?->get('filter') == 'inactive' ? 'selected' : '' }} value="inactive">{{ translate('messages.Offline') }}</option>
                            <option {{  request()?->get('filter') == 'blocked' ? 'selected' : '' }}  value="blocked">{{ translate('messages.Suspended') }}</option>
                        </select>
                    </div>
                    <div class="min--200">
                        <select name="job_type" class="form-control js-select2-custom set-filter" data-filter="job_type"
                        data-url="{{ url()->full() }}">
                            <option  value="all">{{ translate('messages.All_Job_Types') }}</option>
                            <option  {{ request()?->get('job_type') == 'freelancer' ? 'selected' : '' }} value="freelancer">{{ translate('messages.Freelancer') }}</option>
                            <option {{  request()?->get('job_type') == 'salary_base' ? 'selected' : '' }}  value="salary_base">{{ translate('messages.Salary_Base') }}</option>
                        </select>
                    </div>
                    @if(!isset(auth('admin')->user()->zone_id))
                    <div class="min--200">
                        <select name="zone_id" class="form-control js-select2-custom set-filter" data-filter="zone_id"
                        data-url="{{ url()->full() }}">
                            <option value="all">{{ translate('messages.All_Zones') }}</option>
                            @foreach(\App\Models\Zone::orderBy('name')->get() as $z)
                                <option
                                    value="{{$z['id']}}" {{isset($zone) && $zone->id == $z['id']?'selected':''}}>
                                    {{$z['name']}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <form class="search-form">
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control h--45px"
                            placeholder="{{translate('ex:_DM_name_email_or_phone')}}" value="{{ request()->get('search') }}" aria-label="Search" required>
                            <button type="submit" class="btn btn--secondary h--45px"><i class="tio-search"></i></button>

                        </div>
                        <!-- End Search -->
                    </form>
                    @if(request()->get('search'))
                    <button type="reset" class="btn btn--primary ml-2 location-reload-to-base" data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                    @endif

                    <!-- Unfold -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle h--45px min-height-40" href="javascript:;"
                            data-hs-unfold-options='{
                                    "target": "#usersExportDropdown",
                                    "type": "css-animation"
                                }'>
                            <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                        </a>

                        <div id="usersExportDropdown"
                            class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                            <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                            <a id="export-excel" class="dropdown-item" href="{{route('admin.users.delivery-man.export', ['type'=>'excel',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="{{route('admin.users.delivery-man.export', ['type'=>'csv',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>
                        </div>
                    </div>
                    <!-- End Unfold -->
                </div>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                    <thead class="thead-light">
                    <tr>
                        <th class="border-0 text-capitalize">{{translate('sl')}}</th>
                        <th class="border-0 text-capitalize">{{translate('messages.name')}}</th>
                        <th class="border-0 text-capitalize">{{translate('messages.contact_info')}}</th>
                        <th class="border-0 text-capitalize">{{translate('messages.zone')}}</th>
                        <th class="border-0 text-capitalize">{{translate('messages.Total_Completed_Orders')}}</th>
                        <th class="border-0 text-capitalize">{{translate('messages.availability_status')}}</th>
                        <th class="border-0 text-capitalize">{{ translate('messages.today') }}</th>
                        <th class="border-0 text-capitalize">{{translate('messages.Status')}}</th>
                        <th class="border-0 text-center text-capitalize">{{translate('messages.action')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($deliveryMen as $key=>$dm)
                        <tr>
                            <td>{{$key+$deliveryMen->firstItem()}}</td>
                            <td>
                                <a class="table-rest-info" href="{{route('admin.users.delivery-man.preview',[$dm['id']])}}">
                                    <img class="onerror-image" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                    src="{{$dm['image_full_url'] }}"
                                    alt="{{$dm['f_name']}} {{$dm['l_name']}}">
                                    <div class="info">
                                        <h5 class="text-hover-primary mb-0">{{$dm['f_name'].' '.$dm['l_name']}}</h5>
                                        <span class="d-block text-body">
                                            <span class="rating">
                                            <i class="tio-star"></i> {{count($dm->rating)>0?number_format($dm->rating[0]->average, 1, '.', ' '):0}}
                                            </span>
                                        </span>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <a class="deco-none" href="tel:{{$dm['phone']}}">{{$dm['phone']}}</a>
                            </td>
                            <td>
                                @if($dm->zone)
                                <label class="text--title font-medium mb-0">{{$dm->zone->name}}</label>
                                @else
                                <label class="text--title font-medium mb-0">{{translate('messages.zone_deleted')}}</label>
                                @endif
                            </td>
                            <td>
                                <a class="deco-none" href="{{route('admin.users.delivery-man.preview',['id'=> $dm['id'],'tab' => 'transaction' ])}}">{{count($dm['order_transaction'])}}</a>
                            </td>
                            <td>
                                <div>
                                    {{translate('messages.currently_assigned_orders')}} : {{$dm->current_orders}}
                                </div>
                                <div>
                                    {{translate('messages.active_status')}} :
                                    @if($dm->application_status == 'approved')
                                        @if($dm->active)
                                        <strong class="text-capitalize text-primary">{{translate('messages.online')}}</strong>
                                        @else
                                        <strong class="text-capitalize text-secondary">{{translate('messages.offline')}}</strong>
                                        @endif
                                    @elseif ($dm->application_status == 'denied')
                                        <strong class="text-capitalize text-danger">{{translate('messages.denied')}}</strong>
                                    @else
                                        <strong class="text-capitalize text-info">{{translate('messages.pending')}}</strong>
                                    @endif
                                </div>
                            </td>

                            <td>
                                @if ($dm->status == 1)
                                <strong class="text-capitalize text-primary">{{translate('messages.Active')}}</strong>
                                @else
                                <strong class="text-capitalize text-danger">{{translate('messages.Suspended')}}</strong>

                                @endif

                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn action-btn btn--warning btn-outline-warning"
                                            href="{{route('admin.users.delivery-man.preview',[$dm['id']])}}"
                                            title="{{ translate('messages.view') }}"><i
                                                class="tio-visible-outlined"></i>
                                        </a>
                                    <a class="btn action-btn btn--primary btn-outline-primary" href="{{route('admin.users.delivery-man.edit',[$dm['id']])}}" title="{{translate('messages.edit')}}"><i class="tio-edit"></i>
                                        </a>
                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:" data-id="delivery-man-{{$dm['id']}}" data-message="{{ translate('Want to remove this deliveryman ?') }}" title="{{translate('messages.delete')}}"><i class="tio-delete-outlined"></i>
                                    </a>
                                    <form action="{{route('admin.users.delivery-man.delete',[$dm['id']])}}" method="post" id="delivery-man-{{$dm['id']}}">
                                        @csrf @method('delete')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
                @if(count($deliveryMen) !== 0)
                <hr>
                @endif
                <div class="page-area">
                    {!! $deliveryMen->links() !!}
                </div>
                @if(count($deliveryMen) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
                @endif
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection

@push('script_2')
    <script>
        "use strict";
        $(document).on('ready', function () {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function () {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function () {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('keyup', function () {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function () {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        $('#search-form').on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.users.delivery-man.search')}}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.count);
                    $('.page-area').hide();
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush
