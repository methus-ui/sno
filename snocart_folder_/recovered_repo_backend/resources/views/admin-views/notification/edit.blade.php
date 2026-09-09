@extends('layouts.admin.app')

@section('title',translate('messages.update_notification'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/notification.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('messages.notification_update')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card">
            <div class="card-body">
                <form action="{{route('admin.notification.update',[$notification['id']])}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <!-- AI Generation Section -->
                                <div class="col-12">
                                    <div class="card bg-light border-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="tio-bulb-on text-warning mr-2" style="font-size: 1.2rem;"></i>
                                                <label class="input-label mb-0" style="font-weight: 600;">{{translate('AI Notification Generator')}}</label>
                                            </div>
                                            <div class="input-group">
                                                <input type="text" id="ai_prompt" class="form-control" placeholder="{{translate('Describe your notification (e.g., Weekend sale 20% off on all items)')}}" maxlength="500">
                                                <div class="input-group-append">
                                                    <button type="button" id="generate_ai_btn" class="btn btn-primary">
                                                        <i class="tio-magic-wand mr-1"></i>{{translate('Generate with AI')}}
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="text-muted mt-1 d-block">{{translate('Select zone and target audience, then describe your notification to generate AI suggestions')}}</small>
                                        </div>
                                    </div>
                                </div>
                                <!-- End AI Generation Section -->
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.title')}}</label>
                                        <input type="text" value="{{$notification['title']}}" name="notification_title" id="notification_title" class="form-control" placeholder="{{translate('messages.new_notification')}}" required maxlength="191">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.zone')}}</label>
                                        <select name="zone" id="zone" class="form-control js-select2-custom" >
                                            <option value="all" {{isset($notification->zone_id)?'':'selected'}}>{{translate('messages.all_zone')}}</option>
                                            @foreach($zones as $zone)
                                                <option value="{{$zone['id']}}"  {{$notification->zone_id==$zone['id']?'selected':''}}>{{$zone['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="tergat">{{translate('messages.send_to')}}</label>

                                        <select name="tergat" class="form-control" id="tergat" data-placeholder="{{translate('messages.select_tergat')}}" required>
                                            <option value="customer" {{$notification->tergat=='customer'?'selected':''}}>{{translate('messages.customer')}}</option>
                                            <option value="deliveryman" {{$notification->tergat=='deliveryman'?'selected':''}}>{{translate('messages.deliveryman')}}</option>
                                            <option value="store" {{$notification->tergat=='store'?'selected':''}}>{{translate('messages.store')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.description')}}</label>
                                        <textarea name="description" id="notification_description" class="form-control" maxlength="1000" required>{{$notification['description']}}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="h-100 d-flex flex-column">
                                <label class="d-block text-center mt-auto mb-0">
                                    {{translate('messages.image')}}
                                    <small class="text-danger">* ( {{translate('messages.ratio')}} 900x300 )</small>
                                </label>
                                <div class="text-center py-3 my-auto">
                                    <img class="img--vertical onerror-image" id="viewer"
                                    src="{{ $notification['image_full_url'] }}"
                                    data-onerror-image="{{asset('public/assets/admin/img/900x400/img1.jpg')}}" alt="image"/>
                                </div>
                                <div class="custom-file">
                                    <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                    <label class="custom-file-label" for="customFileEg1">{{translate('messages.choose_file')}}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="btn--container mt-4 justify-content-end">
                        <button type="reset" id="reset_btn" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit" class="btn btn--primary">{{translate('messages.send_again')}}</button>
                    </div>
                </form>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/notification.js"></script>
    <script>
        "use strict";

        // AI Generate Button Click Handler
        $('#generate_ai_btn').on('click', function() {
            var prompt = $('#ai_prompt').val().trim();
            var zoneId = $('#zone').val();
            var target = $('#tergat').val();

            if (!prompt) {
                toastr.warning('{{translate('Please enter a description for the notification')}}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                $('#ai_prompt').focus();
                return;
            }

            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="tio-sync mr-1"></i>{{translate('Generating...')}}');
            $btn.prop('disabled', true);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: '{{route('admin.gemini.generate-notification')}}',
                type: 'POST',
                data: {
                    prompt: prompt,
                    zone_id: zoneId,
                    target: target
                },
                success: function(response) {
                    if (response.success) {
                        $('#notification_title').val(response.title);
                        $('#notification_description').val(response.description);
                        toastr.success('{{translate('Notification content generated successfully!')}}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    } else {
                        toastr.error(response.error || '{{translate('Failed to generate content')}}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                },
                error: function(xhr) {
                    var errorMessage = '{{translate('Failed to generate content. Please try again.')}}';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    }
                    toastr.error(errorMessage, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                complete: function() {
                    $btn.html(originalText);
                    $btn.prop('disabled', false);
                }
            });
        });

        // Allow Enter key to trigger AI generation
        $('#ai_prompt').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#generate_ai_btn').click();
            }
        });

        $('#reset_btn').click(function(){
            $('#zone').val("{{$notification->zone_id}}").trigger('change');
            $('#tergat').val("{{$notification->tergat}}");
            $('#notification_title').val("{{$notification['title']}}");
            $('#notification_description').val("{{$notification['description']}}");
            $('#viewer').attr('src', "{{asset('storage/app/public/notification')}}/{{$notification['image']}}");
            $('#ai_prompt').val('');
        })
    </script>
@endpush
