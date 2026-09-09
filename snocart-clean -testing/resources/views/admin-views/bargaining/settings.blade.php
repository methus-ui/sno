@extends('layouts.admin.app')

@section('title', translate('bargaining_settings'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-settings"></i> {{ translate('bargaining_settings') }}
                </h1>
            </div>
            <div class="col-sm-auto">
                <a href="{{ route('admin.bargaining.dashboard') }}" class="btn btn-outline-primary">
                    <i class="tio-arrow-back"></i> {{ translate('dashboard') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Global Settings -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-header-title">{{ translate('global_settings') }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.bargaining.update-settings') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="toggle-switch d-flex align-items-center mb-3" for="enabled">
                            <input type="checkbox" class="toggle-switch-input" name="enabled" id="enabled" value="1" {{ $config['enabled'] ? 'checked' : '' }}>
                            <span class="toggle-switch-label">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                            <span class="toggle-switch-content">
                                <span class="d-block">{{ translate('enable_bargaining_mode') }}</span>
                                <span class="d-block text-muted">{{ translate('master_switch_for_entire_feature') }}</span>
                            </span>
                        </label>

                        <label class="toggle-switch d-flex align-items-center mb-3" for="instant_mode_enabled">
                            <input type="checkbox" class="toggle-switch-input" name="instant_mode_enabled" id="instant_mode_enabled" value="1" {{ $config['instant_mode_enabled'] ? 'checked' : '' }}>
                            <span class="toggle-switch-label">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                            <span class="toggle-switch-content">
                                <span class="d-block">{{ translate('enable_instant_mode') }}</span>
                                <span class="d-block text-muted">{{ translate('auto_award_best_offer_immediately') }}</span>
                            </span>
                        </label>

                        <label class="toggle-switch d-flex align-items-center mb-3" for="wait_mode_enabled">
                            <input type="checkbox" class="toggle-switch-input" name="wait_mode_enabled" id="wait_mode_enabled" value="1" {{ $config['wait_mode_enabled'] ? 'checked' : '' }}>
                            <span class="toggle-switch-label">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                            <span class="toggle-switch-content">
                                <span class="d-block">{{ translate('enable_wait_mode') }}</span>
                                <span class="d-block text-muted">{{ translate('wait_for_vendor_counter_offers') }}</span>
                            </span>
                        </label>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="wait_duration">{{ translate('wait_duration') }} ({{ translate('seconds') }})</label>
                            <input type="number" class="form-control" name="wait_duration" id="wait_duration"
                                   value="{{ $config['wait_duration'] }}" min="30" max="300" required>
                            <small class="form-text text-muted">{{ translate('how_long_to_wait_for_vendor_offers') }} (30-300s)</small>
                        </div>

                        <div class="form-group">
                            <label for="max_requests_per_user_per_day">{{ translate('max_requests_per_user_per_day') }}</label>
                            <input type="number" class="form-control" name="max_requests_per_user_per_day" id="max_requests_per_user_per_day"
                                   value="{{ $config['max_requests_per_user_per_day'] }}" min="1" max="100" required>
                            <small class="form-text text-muted">{{ translate('prevent_abuse_with_rate_limiting') }}</small>
                        </div>

                        <div class="form-group">
                            <label for="min_cart_value">{{ translate('minimum_cart_value') }}</label>
                            <input type="number" step="0.01" class="form-control" name="min_cart_value" id="min_cart_value"
                                   value="{{ $config['min_cart_value'] }}" min="0" required>
                            <small class="form-text text-muted">{{ translate('minimum_cart_value_to_enable_bargaining') }}</small>
                        </div>

                        <div class="form-group">
                            <label for="max_cart_items">{{ translate('max_cart_items') }}</label>
                            <input type="number" class="form-control" name="max_cart_items" id="max_cart_items"
                                   value="{{ $config['max_cart_items'] }}" min="1" max="100" required>
                            <small class="form-text text-muted">{{ translate('max_items_allowed_in_bargaining_cart') }}</small>
                        </div>

                        <div class="form-group">
                            <label for="fuzzy_similarity_threshold">{{ translate('fuzzy_similarity_threshold') }} (%)</label>
                            <input type="number" class="form-control" name="fuzzy_similarity_threshold" id="fuzzy_similarity_threshold"
                                   value="{{ $config['fuzzy_similarity_threshold'] }}" min="50" max="100" required>
                            <small class="form-text text-muted">{{ translate('name_matching_accuracy_for_items_without_barcode') }} (50-100%)</small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-soft-warning">
                    <i class="tio-info"></i>
                    {{ translate('note_these_settings_require_manual_env_update_for_persistence') }}
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-save"></i> {{ translate('save_settings') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Store-Specific Settings -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-header-title">{{ translate('store_specific_settings') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('store') }}</th>
                            <th>{{ translate('bargaining_enabled') }}</th>
                            <th>{{ translate('auto_participate') }}</th>
                            <th>{{ translate('manual_bidding') }}</th>
                            <th>{{ translate('auto_discount') }} (%)</th>
                            <th>{{ translate('action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($storeSettings as $setting)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-circle mr-3">
                                            <img class="avatar-img" src="{{ $setting->store->logo_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="{{ $setting->store->name }}">
                                        </div>
                                        <span>{{ $setting->store->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <label class="toggle-switch mb-0" for="enabled_{{ $setting->id }}">
                                        <input type="checkbox" class="toggle-switch-input store-setting-toggle"
                                               id="enabled_{{ $setting->id }}"
                                               data-store-id="{{ $setting->store_id }}"
                                               data-field="bargaining_enabled"
                                               {{ $setting->bargaining_enabled ? 'checked' : '' }}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <label class="toggle-switch mb-0" for="auto_{{ $setting->id }}">
                                        <input type="checkbox" class="toggle-switch-input store-setting-toggle"
                                               id="auto_{{ $setting->id }}"
                                               data-store-id="{{ $setting->store_id }}"
                                               data-field="auto_participate"
                                               {{ $setting->auto_participate ? 'checked' : '' }}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <label class="toggle-switch mb-0" for="manual_{{ $setting->id }}">
                                        <input type="checkbox" class="toggle-switch-input store-setting-toggle"
                                               id="manual_{{ $setting->id }}"
                                               data-store-id="{{ $setting->store_id }}"
                                               data-field="manual_bidding_enabled"
                                               {{ $setting->manual_bidding_enabled ? 'checked' : '' }}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control form-control-sm"
                                           style="width: 80px;"
                                           id="discount_{{ $setting->id }}"
                                           data-store-id="{{ $setting->store_id }}"
                                           data-field="auto_discount_percentage"
                                           value="{{ $setting->auto_discount_percentage }}"
                                           min="0" max="100">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary update-store-setting"
                                            data-store-id="{{ $setting->store_id }}">
                                        <i class="tio-save"></i> {{ translate('update') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">{{ translate('no_store_settings_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $storeSettings->links() }}
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    // Update store settings via AJAX
    $('.update-store-setting').on('click', function() {
        const storeId = $(this).data('store-id');

        const data = {
            _token: '{{ csrf_token() }}',
            bargaining_enabled: $(`#enabled_${storeId.replace('.', '\\.')}`).is(':checked') ? 1 : 0,
            auto_participate: $(`#auto_${storeId.replace('.', '\\.')}`).is(':checked') ? 1 : 0,
            manual_bidding_enabled: $(`#manual_${storeId.replace('.', '\\.')}`).is(':checked') ? 1 : 0,
            auto_discount_percentage: $(`#discount_${storeId.replace('.', '\\.')}`).val()
        };

        $.ajax({
            url: `{{ url('admin/bargaining/settings/store') }}/${storeId}`,
            method: 'POST',
            data: data,
            success: function(response) {
                toastr.success('{{ translate("settings_updated_successfully") }}');
            },
            error: function(xhr) {
                toastr.error('{{ translate("error_updating_settings") }}');
            }
        });
    });

    // Auto-save on toggle change
    $('.store-setting-toggle').on('change', function() {
        const storeId = $(this).data('store-id');
        $(`.update-store-setting[data-store-id="${storeId}"]`).click();
    });
</script>
@endpush
