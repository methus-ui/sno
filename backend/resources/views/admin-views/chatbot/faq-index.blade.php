@extends('layouts.admin.app')

@section('title', translate('Chatbot FAQs'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .faq-card {
        transition: all 0.3s ease;
        border: 1px solid #eee;
    }
    .faq-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .keywords-badge {
        display: inline-block;
        background: #e9ecef;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        margin: 2px;
    }
    .priority-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 12px;
    }
    .hit-count {
        background: #28a745;
        color: white;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 12px;
    }
    .modal-lg-custom {
        max-width: 700px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/icons/conversation-icon.png')}}" class="w--20" alt="">
            </span>
            <span>{{ translate('Chatbot FAQs') }}</span>
        </h1>
    </div>
    <!-- End Page Header -->

    <!-- Add FAQ Card -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ translate('Add New FAQ') }}</h5>
        </div>
        <div class="card-body">
            <form id="faq-form">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>{{ translate('Question') }} <span class="text-danger">*</span></label>
                            <input type="text" name="question" class="form-control" placeholder="{{ translate('Enter question') }}" required maxlength="500">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>{{ translate('Answer') }} <span class="text-danger">*</span></label>
                            <textarea name="answer" class="form-control" rows="3" placeholder="{{ translate('Enter answer') }}" required maxlength="2000"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Keywords') }}</label>
                            <input type="text" name="keywords" class="form-control" placeholder="{{ translate('keyword1, keyword2, keyword3') }}" maxlength="500">
                            <small class="text-muted">{{ translate('Comma-separated keywords for better matching') }}</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ translate('Priority') }}</label>
                            <input type="number" name="priority" class="form-control" value="0" min="0" max="100">
                            <small class="text-muted">{{ translate('Higher = more important') }}</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="d-block">&nbsp;</label>
                            <label class="toggle-switch d-flex align-items-center">
                                <input type="checkbox" name="is_active" class="toggle-switch-input" checked>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                                <span class="ml-2">{{ translate('Active') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="btn--container justify-content-end">
                    <button type="reset" class="btn btn--reset">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Add FAQ') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FAQ List -->
    <div class="card">
        <div class="card-header py-2 border-0">
            <div class="search--button-wrapper">
                <h5 class="card-title">{{ translate('FAQs List') }} <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $faqs->total() }}</span></h5>
                <form class="search-form" method="GET">
                    <div class="input-group input--group">
                        <input id="datatableSearch" name="search" value="{{ request('search') }}" type="search" class="form-control" placeholder="{{ translate('Search FAQs...') }}">
                        <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive datatable-custom">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('SL') }}</th>
                            <th class="border-0" style="min-width: 250px;">{{ translate('Question') }}</th>
                            <th class="border-0" style="min-width: 300px;">{{ translate('Answer') }}</th>
                            <th class="border-0">{{ translate('Keywords') }}</th>
                            <th class="border-0 text-center">{{ translate('Priority') }}</th>
                            <th class="border-0 text-center">{{ translate('Hits') }}</th>
                            <th class="border-0 text-center">{{ translate('Status') }}</th>
                            <th class="border-0 text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="faq-table-body">
                        @forelse($faqs as $key => $faq)
                        <tr id="faq-row-{{ $faq->id }}">
                            <td>{{ $key + $faqs->firstItem() }}</td>
                            <td>
                                <span class="d-block text-wrap" style="max-width: 250px;">{{ Str::limit($faq->question, 80) }}</span>
                            </td>
                            <td>
                                <span class="d-block text-wrap" style="max-width: 300px;">{{ Str::limit($faq->answer, 100) }}</span>
                            </td>
                            <td>
                                @if($faq->keywords)
                                    @foreach(array_slice(explode(',', $faq->keywords), 0, 3) as $keyword)
                                        <span class="keywords-badge">{{ trim($keyword) }}</span>
                                    @endforeach
                                    @if(count(explode(',', $faq->keywords)) > 3)
                                        <span class="keywords-badge">+{{ count(explode(',', $faq->keywords)) - 3 }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="priority-badge">{{ $faq->priority }}</span>
                            </td>
                            <td class="text-center">
                                <span class="hit-count">{{ $faq->hit_count }}</span>
                            </td>
                            <td class="text-center">
                                <label class="toggle-switch toggle-switch-sm" for="faqStatus{{ $faq->id }}">
                                    <input type="checkbox" class="toggle-switch-input faq-status-toggle"
                                        data-id="{{ $faq->id }}"
                                        id="faqStatus{{ $faq->id }}"
                                        {{ $faq->is_active ? 'checked' : '' }}>
                                    <span class="toggle-switch-label mx-auto">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <button class="btn action-btn btn--primary btn-outline-primary edit-faq"
                                        data-id="{{ $faq->id }}" title="{{ translate('Edit') }}">
                                        <i class="tio-edit"></i>
                                    </button>
                                    <button class="btn action-btn btn--danger btn-outline-danger delete-faq"
                                        data-id="{{ $faq->id }}" title="{{ translate('Delete') }}">
                                        <i class="tio-delete-outlined"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="" style="width: 100px;">
                                <p class="mt-3">{{ translate('No FAQs found') }}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($faqs->hasPages())
        <hr>
        <div class="page-area px-3 pb-3">
            {!! $faqs->appends(request()->query())->links() !!}
        </div>
        @endif
    </div>
</div>

<!-- Edit FAQ Modal -->
<div class="modal fade" id="editFaqModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg-custom" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Edit FAQ') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="edit-faq-form">
                @csrf
                <input type="hidden" name="faq_id" id="edit-faq-id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ translate('Question') }} <span class="text-danger">*</span></label>
                        <input type="text" name="question" id="edit-question" class="form-control" required maxlength="500">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Answer') }} <span class="text-danger">*</span></label>
                        <textarea name="answer" id="edit-answer" class="form-control" rows="4" required maxlength="2000"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>{{ translate('Keywords') }}</label>
                                <input type="text" name="keywords" id="edit-keywords" class="form-control" maxlength="500">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ translate('Priority') }}</label>
                                <input type="number" name="priority" id="edit-priority" class="form-control" min="0" max="100">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="toggle-switch d-flex align-items-center">
                            <input type="checkbox" name="is_active" id="edit-is-active" class="toggle-switch-input">
                            <span class="toggle-switch-label">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                            <span class="ml-2">{{ translate('Active') }}</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Update FAQ') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
"use strict";

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Add FAQ
$('#faq-form').on('submit', function(e) {
    e.preventDefault();

    $.ajax({
        url: "{{ route('admin.chatbot.faq.store') }}",
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                Object.values(xhr.responseJSON.errors).forEach(function(error) {
                    toastr.error(error[0]);
                });
            } else {
                toastr.error('{{ translate("Something went wrong") }}');
            }
        }
    });
});

// Edit FAQ - Load data
$(document).on('click', '.edit-faq', function() {
    let id = $(this).data('id');

    $.get("{{ url('admin/chatbot/faq') }}/" + id + "/edit", function(response) {
        let faq = response.faq;
        $('#edit-faq-id').val(faq.id);
        $('#edit-question').val(faq.question);
        $('#edit-answer').val(faq.answer);
        $('#edit-keywords').val(faq.keywords);
        $('#edit-priority').val(faq.priority);
        $('#edit-is-active').prop('checked', faq.is_active);
        $('#editFaqModal').modal('show');
    });
});

// Update FAQ
$('#edit-faq-form').on('submit', function(e) {
    e.preventDefault();
    let id = $('#edit-faq-id').val();

    $.ajax({
        url: "{{ url('admin/chatbot/faq') }}/" + id,
        method: 'PUT',
        data: $(this).serialize(),
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#editFaqModal').modal('hide');
                location.reload();
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                Object.values(xhr.responseJSON.errors).forEach(function(error) {
                    toastr.error(error[0]);
                });
            } else {
                toastr.error('{{ translate("Something went wrong") }}');
            }
        }
    });
});

// Delete FAQ
$(document).on('click', '.delete-faq', function() {
    let id = $(this).data('id');

    if (confirm('{{ translate("Are you sure you want to delete this FAQ?") }}')) {
        $.ajax({
            url: "{{ url('admin/chatbot/faq') }}/" + id,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#faq-row-' + id).fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            },
            error: function() {
                toastr.error('{{ translate("Something went wrong") }}');
            }
        });
    }
});

// Toggle FAQ Status
$(document).on('change', '.faq-status-toggle', function() {
    let id = $(this).data('id');
    let status = $(this).is(':checked') ? 1 : 0;

    $.get("{{ url('admin/chatbot/faq') }}/" + id + "/status/" + status, function(response) {
        if (response.success) {
            toastr.success(response.message);
        }
    }).fail(function() {
        toastr.error('{{ translate("Something went wrong") }}');
    });
});
</script>
@endpush
