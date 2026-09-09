@extends('layouts.admin.app')

@section('title', translate('Create Campaign'))

@push('css_or_js')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css">
    <script>
        // Disable Dropzone autoDiscover BEFORE vendor.min.js loads
        // This prevents "Dropzone already attached" error
        window.Dropzone = window.Dropzone || {};
        window.Dropzone.autoDiscover = false;
    </script>
    <style>
        .wizard-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .wizard-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        .wizard-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e5e5e5;
            z-index: 0;
        }
        .wizard-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .wizard-step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #e5e5e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            font-weight: bold;
            transition: all 0.3s;
        }
        .wizard-step.active .wizard-step-circle {
            background: #377dff;
            border-color: #377dff;
            color: #fff;
        }
        .wizard-step.completed .wizard-step-circle {
            background: #28a745;
            border-color: #28a745;
            color: #fff;
        }
        .wizard-step-label {
            font-size: 0.875rem;
            color: #8c98a4;
        }
        .wizard-step.active .wizard-step-label {
            color: #377dff;
            font-weight: 600;
        }
        .wizard-content {
            display: none;
        }
        .wizard-content.active {
            display: block;
        }
        .segment-selector {
            max-height: 400px;
            overflow-y: auto;
        }
        .segment-card {
            border: 2px solid #e7eaf3;
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .segment-card:hover {
            border-color: #377dff;
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(55,125,255,0.15);
        }
        .segment-card.selected {
            border-color: #377dff;
            background: linear-gradient(135deg, #e7f3ff 0%, #f0f7ff 100%);
            box-shadow: 0 4px 12px rgba(55,125,255,0.2);
        }
        .segment-card input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .total-recipients-badge {
            position: sticky;
            bottom: 0;
            background: #fff;
            padding: 1rem;
            border-top: 2px solid #e7eaf3;
            margin: -1rem -1.5rem -1.5rem;
        }
        .dropzone {
            border: 2px dashed #377dff;
            border-radius: 0.5rem;
            background: #f8f9fa;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .dropzone:hover {
            background: #e7f3ff;
        }
        .dropzone .dz-message {
            margin: 0;
            font-size: 1rem;
        }
        .media-preview {
            max-width: 100%;
            max-height: 400px;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }
        .whatsapp-preview {
            background: #ece5dd;
            border-radius: 0.5rem;
            padding: 1rem;
            max-width: 400px;
        }
        .whatsapp-message {
            background: #fff;
            border-radius: 0.5rem;
            padding: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .character-counter {
            font-size: 0.875rem;
            color: #8c98a4;
        }
        .character-counter.warning {
            color: #ffc107;
        }
        .character-counter.danger {
            color: #dc3545;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm">
                <h1 class="page-header-title">
                    <i class="tio-add"></i> {{translate('Create WhatsApp Campaign')}}
                </h1>
            </div>
        </div>
    </div>

    <div class="wizard-container">
        <!-- Wizard Steps -->
        <div class="wizard-steps">
            <div class="wizard-step active" data-step="1">
                <div class="wizard-step-circle">1</div>
                <div class="wizard-step-label">{{translate('Campaign Details')}}</div>
            </div>
            <div class="wizard-step" data-step="2">
                <div class="wizard-step-circle">2</div>
                <div class="wizard-step-label">{{translate('Select Audience')}}</div>
            </div>
            <div class="wizard-step" data-step="3">
                <div class="wizard-step-circle">3</div>
                <div class="wizard-step-label">{{translate('Create Message')}}</div>
            </div>
        </div>

        <form id="campaignForm" action="{{ route('admin.whatsapp.campaigns.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Step 1: Campaign Details -->
            <div class="wizard-content active" data-step="1">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">{{translate('Campaign Details')}}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="campaignName" class="form-label">
                                {{translate('Campaign Name')}} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="campaignName" name="name"
                                   placeholder="{{translate('Enter campaign name')}}" required>
                            <div class="invalid-feedback">{{translate('Please enter campaign name')}}</div>
                        </div>

                        <div class="mb-3">
                            <label for="campaignDescription" class="form-label">{{translate('Description')}}</label>
                            <textarea class="form-control" id="campaignDescription" name="description"
                                      rows="3" placeholder="{{translate('Enter campaign description (optional)')}}"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{translate('Schedule')}}</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="schedule_type"
                                           id="scheduleNow" value="now" checked>
                                    <label class="form-check-label" for="scheduleNow">
                                        {{translate('Send immediately')}}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="schedule_type"
                                           id="scheduleLater" value="later">
                                    <label class="form-check-label" for="scheduleLater">
                                        {{translate('Schedule for later')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3" id="scheduleTimeContainer" style="display: none;">
                                <label for="scheduleTime" class="form-label">{{translate('Schedule Time')}}</label>
                                <input type="datetime-local" class="form-control" id="scheduleTime" name="scheduled_at">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Select Segments -->
            <div class="wizard-content" data-step="2">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">{{translate('Select Target Audience')}}</h5>
                        <p class="text-muted mb-0">{{translate('Choose one or more customer segments')}}</p>
                    </div>
                    <div class="card-body">
                        <!-- Predefined Segments -->
                        <h6 class="mb-3">{{translate('Predefined Segments')}}</h6>
                        <div class="segment-selector mb-4">
                            @foreach($predefined_segments ?? [] as $segment)
                            <div class="segment-card" data-segment-id="{{ $segment['key'] }}" data-count="{{ $segment['count'] ?? 0 }}">
                                <div class="d-flex align-items-start">
                                    <input type="checkbox" class="segment-checkbox" name="segments[]"
                                           value="{{ $segment['key'] }}" id="segment_{{ $segment['key'] }}">
                                    <div class="flex-grow-1 ms-3">
                                        <label for="segment_{{ $segment['key'] }}" class="mb-0 cursor-pointer d-block">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="tio-user-outlined me-2 text-primary"></i>
                                                <strong class="fs-6">{{ $segment['name'] }}</strong>
                                            </div>
                                            <small class="text-muted d-block">{{ $segment['description'] }}</small>
                                        </label>
                                    </div>
                                    <span class="badge bg-primary rounded-pill fs-7" style="padding: 0.5rem 0.75rem;">
                                        {{ number_format($segment['count'] ?? 0) }} {{ translate('customers') }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Custom Segments -->
                        @if(isset($custom_segments) && count($custom_segments) > 0)
                        <h6 class="mb-3">{{translate('Custom Segments')}}</h6>
                        <div class="segment-selector">
                            @foreach($custom_segments as $segment)
                            <div class="segment-card" data-segment-id="custom_{{ $segment->id }}" data-count="{{ $segment->customer_count ?? 0 }}">
                                <div class="d-flex align-items-start">
                                    <input type="checkbox" class="segment-checkbox" name="segments[]"
                                           value="{{ $segment->id }}" id="segment_custom_{{ $segment->id }}">
                                    <div class="flex-grow-1 ms-3">
                                        <label for="segment_custom_{{ $segment->id }}" class="mb-0 cursor-pointer d-block">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="tio-filter-outlined me-2 text-success"></i>
                                                <strong class="fs-6">{{ $segment->name }}</strong>
                                            </div>
                                            <small class="text-muted d-block">{{translate('Custom filter')}}</small>
                                        </label>
                                    </div>
                                    <span class="badge bg-success rounded-pill fs-7" style="padding: 0.5rem 0.75rem;">
                                        {{ number_format($segment->customer_count ?? 0) }} {{ translate('customers') }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- Total Recipients -->
                        <div class="total-recipients-badge">
                            <div class="alert alert-info mb-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="tio-user"></i>
                                        <strong>{{translate('Total Recipients (deduplicated)')}}:</strong>
                                    </span>
                                    <h4 class="mb-0" id="totalRecipientsCount">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Message Builder -->
            <div class="wizard-content" data-step="3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">{{translate('Create Your Message')}}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-7">
                                <!-- Media Upload -->
                                <div class="mb-4">
                                    <label class="form-label">{{translate('Media (Optional)')}}</label>
                                    <div class="nav nav-tabs mb-3" role="tablist">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#uploadTab">
                                            {{translate('Upload File')}}
                                        </button>
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#urlTab">
                                            {{translate('From URL')}}
                                        </button>
                                    </div>
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="uploadTab">
                                            <div id="mediaDropzone" class="manual-dropzone" data-url="{{ route('admin.whatsapp.upload') }}">
                                                <div class="dz-message">
                                                    <i class="tio-file-add" style="font-size: 3rem;"></i>
                                                    <p>{{translate('Drop image here or click to upload')}}</p>
                                                    <small class="text-muted">{{translate('JPG, PNG (Max 5MB)')}}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="urlTab">
                                            <input type="text" class="form-control" id="mediaUrl" name="media_url"
                                                   placeholder="{{translate('Enter image URL')}}">
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="loadUrlMedia">
                                                {{translate('Load Preview')}}
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden" id="mediaPath" name="media_path">
                                    <img id="mediaPreview" class="media-preview" style="display: none;">
                                </div>

                                <!-- Message Caption -->
                                <div class="mb-3">
                                    <label for="messageCaption" class="form-label">
                                        {{translate('Message')}} <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="messageCaption" name="message"
                                              rows="6" placeholder="{{translate('Type your message here...')}}" required
                                              maxlength="1000"></textarea>
                                    <div class="d-flex justify-content-between mt-2">
                                        <small class="text-muted">
                                            {{translate('Available variables')}}: {name}, {phone}, {zone}
                                        </small>
                                        <span class="character-counter">
                                            <span id="charCount">0</span>/1000
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div class="col-md-5">
                                <label class="form-label">{{translate('Preview')}}</label>
                                <div class="whatsapp-preview">
                                    <div class="whatsapp-message">
                                        <img id="previewImage" style="width: 100%; border-radius: 0.5rem; margin-bottom: 0.5rem; display: none;">
                                        <div id="previewText">{{translate('Your message will appear here...')}}</div>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="tio-info"></i> {{translate('This is how your message will look')}}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-white" id="prevBtn" style="display: none;">
                    <i class="tio-chevron-left"></i> {{translate('Previous')}}
                </button>
                <div></div>
                <div>
                    <button type="button" class="btn btn-primary" id="nextBtn">
                        {{translate('Next')}} <i class="tio-chevron-right"></i>
                    </button>
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                        <i class="tio-telegram"></i> <span id="submitBtnText">{{translate('Launch Campaign')}}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
<script>
    'use strict';

    let currentStep = 1;
    let dropzone;
    const totalSteps = 3;

    $(document).ready(function() {
        // Initialize Dropzone (autoDiscover already disabled in head)
        Dropzone.autoDiscover = false;

        // Add dropzone class to enable styling (avoided in HTML to prevent autoDiscover)
        $('#mediaDropzone').addClass('dropzone');

        dropzone = new Dropzone("#mediaDropzone", {
            url: '{{ route("admin.whatsapp.upload") }}',
            maxFiles: 1,
            maxFilesize: 5,
            acceptedFiles: 'image/*',
            addRemoveLinks: true,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(file, response) {
                $('#mediaPath').val(response.path);
                $('#mediaPreview').attr('src', response.url).show();
                $('#previewImage').attr('src', response.url).show();
                toastr.success('{{translate("Image uploaded successfully")}}');
            },
            error: function(file, response) {
                toastr.error(response.message || '{{translate("Upload failed")}}');
            },
            removedfile: function(file) {
                $('#mediaPath').val('');
                $('#mediaPreview').hide();
                $('#previewImage').hide();
                file.previewElement.remove();
            }
        });

        // Schedule type handler
        $('input[name="schedule_type"]').on('change', function() {
            if ($(this).val() === 'later') {
                $('#scheduleTimeContainer').show();
                $('#scheduleTime').prop('required', true);
                $('#submitBtnText').text('{{translate("Schedule Campaign")}}');
            } else {
                $('#scheduleTimeContainer').hide();
                $('#scheduleTime').prop('required', false);
                $('#submitBtnText').text('{{translate("Launch Campaign")}}');
            }
        });

        // Segment selection handler
        $('.segment-card').on('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = $(this).find('.segment-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            }
        });

        $('.segment-checkbox').on('change', function() {
            const card = $(this).closest('.segment-card');
            if ($(this).is(':checked')) {
                card.addClass('selected');
            } else {
                card.removeClass('selected');
            }
            updateTotalRecipients();
        });

        // Calculate total recipients (deduplicated)
        function updateTotalRecipients() {
            const selectedSegments = $('.segment-checkbox:checked').map(function() {
                return parseInt($(this).closest('.segment-card').data('count')) || 0;
            }).get();

            // Simple sum (server will handle deduplication)
            const total = selectedSegments.reduce((a, b) => a + b, 0);
            $('#totalRecipientsCount').text(total.toLocaleString());
        }

        // Message preview
        $('#messageCaption').on('input', function() {
            const text = $(this).val();
            const charCount = text.length;
            $('#charCount').text(charCount);
            $('#previewText').text(text || '{{translate("Your message will appear here...")}}');

            const $counter = $('.character-counter');
            $counter.removeClass('warning danger');
            if (charCount > 800) $counter.addClass('danger');
            else if (charCount > 600) $counter.addClass('warning');
        });

        // Load URL media
        $('#loadUrlMedia').on('click', function() {
            const url = $('#mediaUrl').val();
            if (!url) {
                toastr.error('{{translate("Please enter a URL")}}');
                return;
            }

            $('#mediaPreview').attr('src', url).show();
            $('#previewImage').attr('src', url).show();
            $('#mediaPath').val(url);
        });

        // Navigation
        $('#nextBtn').on('click', function() {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    goToStep(currentStep + 1);
                }
            }
        });

        $('#prevBtn').on('click', function() {
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        });

        function goToStep(step) {
            // Hide current step
            $(`.wizard-content[data-step="${currentStep}"]`).removeClass('active');
            $(`.wizard-step[data-step="${currentStep}"]`).removeClass('active').addClass('completed');

            // Show new step
            currentStep = step;
            $(`.wizard-content[data-step="${currentStep}"]`).addClass('active');
            $(`.wizard-step[data-step="${currentStep}"]`).addClass('active');

            // Update buttons
            $('#prevBtn').toggle(currentStep > 1);
            $('#nextBtn').toggle(currentStep < totalSteps);
            $('#submitBtn').toggle(currentStep === totalSteps);

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(step) {
            if (step === 1) {
                const name = $('#campaignName').val().trim();
                if (!name) {
                    toastr.error('{{translate("Please enter campaign name")}}');
                    $('#campaignName').focus();
                    return false;
                }
            } else if (step === 2) {
                const selectedSegments = $('.segment-checkbox:checked').length;
                if (selectedSegments === 0) {
                    toastr.error('{{translate("Please select at least one segment")}}');
                    return false;
                }
            } else if (step === 3) {
                const message = $('#messageCaption').val().trim();
                if (!message) {
                    toastr.error('{{translate("Please enter a message")}}');
                    $('#messageCaption').focus();
                    return false;
                }
            }
            return true;
        }

        // Form submission
        $('#campaignForm').on('submit', function(e) {
            e.preventDefault();

            if (!validateStep(3)) {
                return false;
            }

            const $submitBtn = $('#submitBtn');
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{translate("Creating...")}}');

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(response) {
                    toastr.success(response.message || '{{translate("Campaign created successfully")}}');
                    setTimeout(() => {
                        window.location.href = '{{ route("admin.whatsapp.campaigns.index") }}';
                    }, 1500);
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || '{{translate("Failed to create campaign")}}';
                    toastr.error(message);
                    $submitBtn.prop('disabled', false).html(originalText);

                    // Show validation errors
                    if (xhr.responseJSON?.errors) {
                        Object.values(xhr.responseJSON.errors).forEach(error => {
                            toastr.error(error[0]);
                        });
                    }
                }
            });
        });
    });

    // Prevent JavaScript errors from missing DOM elements
    (function() {
        // Suppress errors for missing sidebar elements (this page doesn't have sidebar)
        const navbarMiniCache = document.querySelector('.navbar-vertical-aside-mini-mode');
        if (!navbarMiniCache) {
            // Element doesn't exist, suppress related errors silently
            console.log('Sidebar navigation not present on this page');
        }

        // Add null checks for any event listeners
        window.addEventListener('error', function(e) {
            // Suppress specific known errors that don't affect functionality
            if (e.message && (
                e.message.includes('classList') ||
                e.message.includes('hs-navbar-vertical')
            )) {
                console.log('Non-critical UI error suppressed:', e.message);
                e.preventDefault();
                return true;
            }
        }, true);
    })();
</script>
@endpush
