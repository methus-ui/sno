@extends('layouts.vendor.app')

@section('title',translate('messages.Product_Gallery'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
@php($store_data=\App\CentralLogics\Helpers::get_store_data())
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="btn--container align-items-center mb-0">
                <div class="d-flex gap-2">
                    <img class="h--50px"
                        src="{{ asset('public/assets/admin/img/group.png') }}" alt="Product_Gallery">
                    <div>
                        <h1 class="page-header-title"> {{translate('messages.Product_Gallery')}}<span class="badge badge-soft-dark ml-2" id="itemCount"></span></h1>
                    <p>{{ translate('search_product_and_use_its_info_to_create_own_product') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card mb-3">
            <!-- Header -->
            <div class="card-body border-0">
                <form id="search-form" class="search-form">
                    @csrf
                    <input type="hidden" value="1" name="product_gallery">
                    <div class="row g-2">
                        <div class="col-12 col-sm-10 col-lg-11">
                            <input id="datatableSearch" type="search" value="{{ request()?->search ?? null }}" name="search" class="form-control" placeholder="{{ translate('messages.ex_search_name') }}" aria-label="{{ translate('messages.search_here') }}">
                        </div>
                        <div class="col-12 col-sm-2 col-lg-1">
                            <button type="submit" class="btn btn--primary w-100">{{ translate('messages.search') }}</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- End Header -->
        </div>
        <!-- End Card -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-1">{{ translate('messages.Product_List') }}</h2>
                <p class="mb-0">{{ translate('search_product_and_use_its_info_to_create_own_product') }}</p>
            </div>
            @if($items->total() > 0)
            <div class="text-right">
                <span class="badge badge-soft-primary p-2 px-3">
                    <i class="tio-checkmark-circle-outlined mr-1"></i>
                    <strong>{{ $items->total() }}</strong> {{ translate('messages.products_found') }}
                </span>
            </div>
            @endif
        </div>

                    <div class="row" id="set-rows">
                        @include('vendor-views.product.partials._gallery', [
                            $items,
                        ])
                    </div>
                @if(count($items) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
                @endif

                <!-- Pagination -->
                @if($items->hasPages())
                <div class="page-area mt-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="text-muted">
                            {!! translate('messages.showing') !!} {{ $items->firstItem() }} {!! translate('messages.to') !!} {{ $items->lastItem() }} {!! translate('messages.of') !!} {{ $items->total() }} {!! translate('messages.entries') !!}
                        </div>
                        <nav aria-label="Page navigation">
                            {!! $items->appends(request()->all())->links('pagination::bootstrap-4') !!}
                        </nav>
                    </div>
                </div>
                @endif
                <!-- End Pagination -->

            <!-- End Table -->
    </div>

@endsection

@push('script_2')
    <script>
        "use strict";
        $(document).on('ready', function () {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            let datatable = $.HSCore.components.HSDatatables.init($('#datatable'), {
          select: {
            style: 'multi',
            classMap: {
              checkAll: '#datatableCheckAll',
              counter: '#datatableCounter',
              counterInfo: '#datatableCounterInfo'
            }
          },
          language: {
            zeroRecords: '<div class="text-center p-4">' +
                '<img class="w-7rem mb-3" src="{{asset('public/assets/admin/svg/illustrations/sorry.svg')}}" alt="Image Description">' +

                '</div>'
          }
        });

        $('#datatableSearch').on('mouseup', function (e) {
          let $input = $(this),
            oldValue = $input.val();

          if (oldValue == "") return;

          setTimeout(function(){
            let newValue = $input.val();

            if (newValue == ""){
              // Gotcha
              datatable.search('').draw();
            }
          }, 1);
        });

        $('#toggleColumn_index').change(function (e) {
          datatable.columns(0).visible(e.target.checked)
        })
        $('#toggleColumn_name').change(function (e) {
          datatable.columns(1).visible(e.target.checked)
        })

        $('#toggleColumn_type').change(function (e) {
          datatable.columns(2).visible(e.target.checked)
        })

        $('#toggleColumn_status').change(function (e) {
          datatable.columns(4).visible(e.target.checked)
        })
        $('#toggleColumn_price').change(function (e) {
          datatable.columns(3).visible(e.target.checked)
        })
        $('#toggleColumn_action').change(function (e) {
          datatable.columns(5).visible(e.target.checked)
        })


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        $('#category').select2({
            ajax: {
                url: '{{route("vendor.category.get-all")}}',
                data: function (params) {
                    return {
                        q: params.term, // search term
                        all:true,
                        page: params.page
                    };
                },
                processResults: function (data) {
                    return {
                    results: data
                    };
                },
                __port: function (params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });

        // Enhanced AJAX search with debouncing
        let searchTimer = null;
        const DEBOUNCE_DELAY = 400;

        function performSearch() {
            let formData = new FormData($('#search-form')[0]);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route("vendor.item.search") }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                    $('#datatableSearch').addClass('is-loading');
                },
                success: function (data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.count);
                    $('.page-area').hide();
                },
                complete: function () {
                    $('#loading').hide();
                    $('#datatableSearch').removeClass('is-loading');
                },
                error: function() {
                    toastr.error('{{ translate("messages.search_failed") }}');
                }
            });
        }

        // Live search on input with debouncing
        $('#datatableSearch').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(performSearch, DEBOUNCE_DELAY);
        });

        // Form submit (for explicit search button click)
        $('#search-form').on('submit', function (e) {
            e.preventDefault();
            clearTimeout(searchTimer);
            performSearch();
        });

        // Clear search when X button is clicked or search is empty
        $('#datatableSearch').on('search', function () {
            if ($(this).val() === '') {
                // Reload page to show pagination again
                window.location.href = '{{ route("vendor.item.product_gallery") }}';
            }
        });

        // Handle clear button
        $('#datatableSearch').on('keyup', function(e) {
            if (e.keyCode === 27 || $(this).val() === '') { // ESC key or empty
                if ($(this).val() === '') {
                    window.location.href = '{{ route("vendor.item.product_gallery") }}';
                }
            }
        });
    </script>
@endpush
