@extends('layouts.admin.app')

@section('title','Advertisement Create')


@section('advertisement')
active
@endsection
@section('advertisement_create')
active
@endsection


@push('css_or_js')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>
    <!-- Instagram Embed Script -->
    <script async src="https://www.instagram.com/embed.js"></script>
@endpush

@section('content')
<div class="content container-fluid">


    <!-- Advertisement -->
    <h1 class="page-header-title mb-3">
        {{ translate('Create_Advertisement') }}
    </h1>
    <div class="card mb-20">
        <div class="card-body p-30">
            <form id="create-add-form"  method="POST" enctype="multipart/form-data" >
                @csrf
                @method("POST")
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="js-nav-scroller hs-nav-scroller-horizontal">
                        <ul class="nav nav-tabs mb-3 border-0">
                        <li class="nav-item">
                            <a class="nav-link lang_link active"
                            href="#"
                            id="default-link">{{translate('messages.default')}}</a>
                        </li>

                        @if ($language)
                        @foreach ($language as $lang)
                            <li class="nav-item">
                                <a class="nav-link lang_link"
                                    href="#"
                                    id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                </li>
                                @endforeach
                            </ul>
                        </div>

                            <div class="lang_form" id="default-form">


                            <div class="mb-20">
                                <label class="form-label">{{ translate('Advertisement_Title') }} ({{ translate('Default') }})</label>
                                <input type="text" class="form-control" id="title" name="title[]"
                                    value="{{ old('title.0') }}" placeholder="{{ translate('Exclusive_Offer') }}" maxlength="255"
                                    data-preview-text="preview-title">
                            </div>
                            <div class="form-floating mb-20">
                                <label class="form-label">{{ translate('Short_Description') }} ({{ translate('Default') }})</label>
                                <textarea class="form-control resize-none" id="description"
                                    placeholder="{{ translate('Get_Discount') }}" name="description[]"
                                    data-preview-text="preview-description">{{ old('description.0') }}</textarea>
                                </div>

                            <input type="hidden" name="lang[]" value="default">
                            </div>

                            @foreach ($language as $key => $lang)
                            <div class="d-none lang_form"
                                id="{{ $lang }}-form">




                                <div class="mb-20">
                                    <label class="form-label">{{ translate('Advertisement_Title') }}   ({{ strtoupper($lang) }})</label>
                                    <input type="text" class="form-control" id="title" name="title[]"
                                        value="{{ old('title.0') }}" placeholder="{{ translate('Exclusive_Offer') }}" maxlength="255"
                                        data-preview-text="preview-title">
                                </div>
                                <div class="form-floating mb-20">
                                    <label class="form-label">{{ translate('Short_Description') }}   ({{ strtoupper($lang) }})</label>
                                    <textarea class="form-control resize-none" id="description"
                                        placeholder="{{ translate('Get_Discount') }}" name="description[]"
                                        data-preview-text="preview-description">{{ old('description.0') }}</textarea>
                                    </div>

                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                            </div>
                        @endforeach

                            @else

                            <div class="mb-20">
                                <label class="form-label">{{ translate('Advertisement_Title') }}</label>
                                <input type="text" class="form-control" id="title" name="title[]"
                                    value="{{ old('title.0') }}" placeholder="{{ translate('Exclusive_Offer') }}" maxlength="255"
                                    data-preview-text="preview-title">
                            </div>
                            <div class="form-floating mb-20">
                                <label class="form-label">{{ translate('Short_Description') }}</label>
                                <textarea class="form-control resize-none" id="description"
                                    placeholder="{{ translate('Get_Discount') }}" name="description[]"
                                    data-preview-text="preview-description">{{ old('description.0') }}</textarea>
                            </div>
                            <input type="hidden" name="lang[]" value="default">

                            @endif










                        <label class="form-label" for="exampleFormControlSelect1">{{ translate('messages.Select_Store') }} </label>
                        <div class="mb-20">
                            <select name="store_id" id="store_id"  data-placeholder="{{ translate('messages.select_store') }}"
                            class="js-data-example-ajax form-control">
                            </select>
                        </div>

                        <label class="form-label">{{ translate('Select_Priority') }}</label>
                        <div class="mb-20">
                            <select class="form-control w-100 js-select2-custom" name="priority">
                                <option value="" selected="" disabled="">{{ translate('Priority') }}</option>
                                <option value="">{{ translate('messages.N/A') }}</option>
                                @for ($i = 1; $i <= $total_adds; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="mb-20">
                            <label class="form-label">{{ translate('Advertisement_Type') }}</label>
                            <select class="js-select form-control w-100 promotion_type" name="advertisement_type">
                                <option value="video_promotion">{{ translate('Video_Promotion') }}</option>
                                <option value="store_promotion" selected="">{{ translate('store_promotion') }}</option>
                            </select>
                        </div>
                        <div class="mb-20">
                            <label class="form-label">{{ translate('Validity') }}</label>
                            <div class="position-relative">
                                <i class="tio-calendar-month icon-absolute-on-right"></i>
                                <input type="text" class="form-control h-45 position-relative bg-transparent"  name="dates" placeholder="{{ translate('messages.Select_Date') }}">
                            </div>
                        </div>

                        <div class="promotion-typewise-upload-box" id="video-upload-box">
                            <label class="form-label">{{ translate('Video Source') }}</label>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input type="radio" class="form-check-input video-source-radio" name="video_source" value="upload" checked>
                                        <span class="form-check-label">{{ translate('Upload Video File') }}</span>
                                    </label>
                                    <label class="form-check">
                                        <input type="radio" class="form-check-input video-source-radio" name="video_source" value="instagram_reel">
                                        <span class="form-check-label">{{ translate('Select from Instagram') }}</span>
                                    </label>
                                    <label class="form-check">
                                        <input type="radio" class="form-check-input video-source-radio" name="video_source" value="instagram_url">
                                        <span class="form-check-label">{{ translate('Paste Instagram URL') }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Upload Video Section -->
                            <div id="upload-video-section" class="border rounded p-3">
                                <div class="d-flex flex-column align-items-center gap-3">
                                    <p class="title-color mb-0 ">{{ translate('Upload Your Video') }}
                                        ({{ translate('16:9') }})</p>

                                    <div class="upload-file">
                                        <input type="file" class="video_attachment" name="video_attachment"
                                            accept="video/mp4, video/webm, video/mkv">
                                        <div class="upload-file__img upload-file__img_banner upload-file__video-not-playable h-140">
                                        </div>
                                        <button class="remove-file-button" type="button">
                                            <i class="tio-clear"></i>
                                        </button>
                                    </div>

                                    <p class="opacity-75 max-w220 mx-auto text-center fs-12">
                                        {{ translate('Maximum 5 MB') }}
                                        <br>
                                        {{ translate('Supports: MP4, WEBM, MKV') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Instagram Reel Selection Section -->
                            <div id="instagram-reel-section" class="border rounded p-3 d-none">
                                <div class="text-center">
                                    <p class="mb-3">{{ translate('Select a reel from your Instagram account') }}</p>
                                    <button type="button" class="btn btn-primary" id="select-instagram-reel-btn">
                                        <i class="tio-instagram"></i> {{ translate('Browse Instagram Reels') }}
                                    </button>
                                    <input type="hidden" name="instagram_reel_url" id="instagram_reel_url">
                                    <div id="selected-reel-preview" class="mt-3 d-none">
                                        <p class="text-success"><i class="tio-checkmark-circle"></i> {{ translate('Reel Selected') }}</p>
                                        <div id="selected-reel-thumbnail"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Instagram Manual URL Section -->
                            <div id="instagram-url-section" class="border rounded p-3 d-none">
                                <div class="d-flex flex-column gap-3">
                                    <label class="form-label">{{ translate('Instagram Reel URL') }}</label>
                                    <div class="input-group">
                                        <input type="url" class="form-control" name="instagram_manual_url" id="instagram_manual_url"
                                            placeholder="https://www.instagram.com/username/reel/ABC123/">
                                        <button class="btn btn-primary" type="button" id="preview-instagram-url-btn">
                                            <i class="tio-visible"></i> {{ translate('Preview') }}
                                        </button>
                                    </div>
                                    <p class="opacity-75 fs-12">
                                        {{ translate('Paste any Instagram reel URL. Examples:') }}<br>
                                        • https://www.instagram.com/reel/ABC123/<br>
                                        • https://www.instagram.com/username/reel/ABC123/
                                    </p>
                                    <div id="manual-url-preview" class="d-none mt-3">
                                        <div class="border rounded p-3 text-center">
                                            <div id="manual-url-embed"></div>
                                        </div>
                                    </div>
                                    <div id="manual-url-error" class="alert alert-danger d-none mt-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="promotion-typewise-upload-box" id="profile-upload-box">
                            <h5 class="mb-3">{{ translate('Show Review') }} &amp; {{ translate('Ratings') }}</h5>
                            <div class="card bg--secondary shadow-none">
                                <div class="card-body p-3">
                                    <div class="w-100 d-flex flex-wrap gap-3">
                                        <label class="form-check form--check-2 me-3">
                                            <input type="checkbox" id="is_review_checked" class="form-check-input" value="1" name="review" checked="">
                                            <span class="form-check-label">{{ translate('Review') }}</span>
                                        </label>
                                        <label class="form-check form--check-2">
                                            <input type="checkbox" id="is_rating_checked" class="form-check-input" value="1" name="rating" checked="">
                                            <span class="form-check-label">{{ translate('Rating') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <label class="form-label">{{ translate('Upload Related Files') }}</label>
                            <div class="d-flex flex-wrap flex-sm-nowrap justify-content-center gap-3 border rounded p-3">
                                <div class="d-flex flex-column align-items-center gap-3">
                                    <p class="title-color mb-0">{{ translate('Profile Image') }} <span class="text-danger">({{ translate('Ratio - 1:1') }})</span></p>

                                    <div class="upload-file">
                                        <input type="file" class="cover_attachment js-upload-input"
                                            data-target="profile-prev-image" name="profile_image"
                                            accept=".png,.jpg,.jpeg,.gif, |image/*">
                                        <div class="upload-file__img">
                                            <img src="{{asset('public/assets/admin/img/media/upload-file.png')}}" alt="" >
                                        </div>
                                        <button class="remove-file-button" type="button">
                                            <i class="tio-clear"></i>
                                        </button>
                                    </div>

                                    <p class="opacity-75 max-w220 mx-auto text-center fs-12">
                                        {{ translate('Supports: PNG, JPG, JPEG, WEBP') }}
                                        <br>
                                        {{ translate('Maximum 2 MB') }}
                                    </p>
                                </div>
                                <div class="d-flex flex-column align-items-center gap-3">
                                    <p class="title-color mb-0">{{ translate('Upload Cover') }} <span class="text-danger">({{ translate('Ratio - 2:1') }})</span></p>
                                    <div class="upload-file">
                                        <input type="file" class="cover_attachment js-upload-input"
                                            data-target="main-image" name="cover_image"
                                            accept=".png,.jpg,.jpeg,.gif, |image/*">
                                        <div class="upload-file__img upload-file__img_banner">
                                            <img src="{{asset('public/assets/admin/img/media/banner-upload-file.png')}}" alt="" >
                                        </div>
                                        <button class="remove-file-button" type="button">
                                            <i class="tio-clear"></i>
                                        </button>
                                    </div>

                                    <p class="opacity-75 max-w220 mx-auto text-center fs-12">
                                        {{ translate('Supports: PNG, JPG, JPEG, WEBP') }}
                                        <br>
                                        {{ translate('Maximum 2 MB') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="position-sticky top-80px text-8797AB">
                            <div class="bg-light p-3 p-sm-4 rounded">
                                <label class="form-label">{{ translate('Advertisement Preview') }}</label>
                                <div id="video-preview-box" class="video-preview-box">
                                    <div class="bg--secondary rounded">
                                        <!-- Video/Instagram Embed Container -->
                                        <div class="video-embed-container">
                                            <!-- Regular Video Upload Preview -->
                                            <div class="video h-200" id="regular-video-preview">
                                                <video controls>
                                                    {{ translate('Your browser does not support the video tag.') }}
                                                </video>
                                            </div>

                                            <!-- Instagram Embed Preview -->
                                            <div class="instagram-embed-preview d-none" id="instagram-embed-preview">
                                                <div class="instagram-embed-wrapper">
                                                    <div class="instagram-embed-content" id="instagram-embed-content">
                                                        <!-- Instagram blockquote will be inserted here -->
                                                    </div>
                                                </div>
                                                <div class="instagram-embed-badge">
                                                    <i class="tio-instagram"></i>
                                                    <span>{{ translate('Instagram Reel') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Video Info Card -->
                                        <div class="prev-video-box rounded bg-white px-3 py-4 position-relative gap-4 mt-n2">
                                            <div class="profile-img">
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <div class="d-flex flex-column gap-2 flex-grow-1">
                                                    <div class="preview-title w-100">
                                                        <h5 class="main-text pe-4">{{ translate('Title') }}</h5>
                                                        <div class="placeholder-text bg--secondary p-2 w-50"></div>
                                                    </div>
                                                    <div class="preview-description w-100">
                                                        <div class="main-text line-limit-2">{{ translate('messages.Description') }}
                                                        </div>
                                                        <div class="placeholder-text bg--secondary p-2 w-75"></div>
                                                    </div>
                                                    <div class="preview-description w-100">
                                                        <div class="placeholder-text bg--secondary p-2 w-65"></div>
                                                    </div>
                                                </div>
                                                <a class="btn btn--primary py-2 px-3 cursor-auto">
                                                    <span class="tio-arrow-forward"></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="profile-preview-box" class="profile-preview-box">
                                    <div class="bg--secondary rounded">
                                        <!-- Existing Profile Banner Image -->
                                        <div class="main-image rounded min-h-200" style="background: url('') center center / cover no-repeat">
                                        </div>
                                        <div class="rounded bg-white px-3 py-4 position-relative mt-n2">
                                            <div class="preview-title preview-description">
                                                <div class="wishlist-btn bg--secondary placeholder-text"></div>
                                                <div class="static-text wishlist-btn-2" style="display: block;">
                                                    <div
                                                        class="h-100 w-100 d-flex align-items-center justify-content-center">
                                                        <i class="tio-heart-outlined"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="d-flex align-items-center justify-content-between gap-2">
                                                <!-- Existing Profile Image -->
                                                <div class="profile-prev-image bg--secondary me-xl-3" style="background: url('') center center / cover no-repeat">
                                                </div>
                                                <div class="review-rating-demo">
                                                    <div class="rating-text static-text">
                                                        <div class="rating-number d-flex align-items-center">
                                                            <i  class="tio-star"></i><span id="rating_data">{{ translate('4.7') }}</span>
                                                        </div>
                                                    </div>
                                                    <span id="review_data" class="review--text static-text">({{ translate('25+') }})</span>
                                                </div>
                                                <div class="w-0 d-flex flex-column gap-2 flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <div class="preview-title w-100">
                                                            <h5 class="main-text pe-4">{{ translate('Title') }}</h5>
                                                            <div class="placeholder-text bg--secondary p-2 w-50"></div>
                                                        </div>
                                                    </div>
                                                    <div class="preview-description w-100">
                                                        <div class="main-text line-limit-2">{{ translate('messages.Description') }}
                                                        </div>
                                                        <div class="placeholder-text bg--secondary p-2 w-75"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <br>
                        </div>
                    </div>
                </div>
                <div class="btn--container justify-content-end">
                    <button type="reset" id="reset_btn" class="btn btn--reset">{{ translate('Reset') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Submit') }}</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Advertisement -->

    <!-- Instagram Reels Modal -->
    <div class="modal fade" id="instagramReelsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Select Instagram Reel') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="instagram-reels-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-3">{{ translate('Loading Instagram reels...') }}</p>
                    </div>
                    <div id="instagram-reels-error" class="alert alert-danger d-none"></div>
                    <div id="instagram-reels-container" class="row d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('script_2')

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(function() {
            $('input[name="dates"]').daterangepicker({
                // timePicker: true,
                minDate: new Date(),
                startDate: moment().startOf('hour'),
                endDate: moment().startOf('hour').add(10, 'day'),
            });

            $('.js-select').each(function () {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>


    <!-- Video Upload Handlr -->
    <script>
        $(".video_attachment").on("change", function (event) {
            // Reset to regular video preview (hide Instagram embed if shown)
            resetToVideoPreview();

            const videoEl = $("#regular-video-preview video");
            const prevVideoBox = $('.prev-video-box');
            let file = event.target.files[0];
            let blobURL = URL.createObjectURL(file);
            const prevImage = $(this).closest('.upload-file').find('.upload-file__img').find('img').attr('src');

            videoEl.css('display', 'block');
            videoEl.attr('src', blobURL);
            videoEl.siblings('.play-icon').hide();
            $(this).closest('.upload-file').find('.upload-file__img').html('<video src="' + blobURL + '" controls></video>');
            $(this).closest('.upload-file').find('.remove-file-button').show();

            $(this).closest('.upload-file').find('.remove-file-button').on('click', function () {
                $(this).hide();
                videoEl.siblings('.play-icon').show();
                $(this).closest('.upload-file').find('.upload-file__img').find('img').attr('src', prevImage);
                $(this).closest('.upload-file').find('.video_attachment').val('');
                $(this).closest('.upload-file').find('.video > video').css('display', 'none');
                videoEl.css('display', 'none');
                videoEl.attr('src', '');
            });
        })

        $(window).on('load', function () {
            handleUploadBox();

            const videoEl = $(".video > video")
            let blobURL = "";
            // prev video attachment file
            {{-- blobURL = "{{asset('storage/app/public/advertisement').'/' . $advertisement?->attachment?->file_name}}"; --}}

            videoEl.css('display', 'block');
            videoEl.attr('src', blobURL);
            $(".video_attachment").closest('.upload-file').find('.upload-file__img').html('<video src="' + blobURL + '" controls></video>');
            $(".video_attachment").closest('.upload-file').find('.remove-file-button').show()
            $(".video_attachment").closest('.upload-file').find('.remove-file-button').on('click', function () {
                $(this).hide()
                $(this).closest('.upload-file').find('.upload-file__img').html('<img src="{{asset('public/assets/admin/img/media/video-banner.png')}}" alt="">');
                $(this).closest('.upload-file').find('.video_attachment').val('');
                $(this).closest('.upload-file').find('.video > video').css('display', 'none');
                videoEl.css('display', 'none');
                videoEl.attr('src', '');
            })
        })
    </script>

    <!-- Select Toggler Scripts -->
    <script>
        const handleUploadBox = () => {
            const value = $('.promotion_type').val();
            if (value == 'video_promotion') {
                $('#video-upload-box, #video-preview-box').show();
                $('#profile-upload-box, #profile-preview-box').hide();
            } else {
                $('#video-upload-box, #video-preview-box').hide();
                $('#profile-upload-box, #profile-preview-box').show();
            }
        }
        $(window).on('load', function () {
            handleUploadBox()
        })

        $('.promotion_type').on('change', function () {
            handleUploadBox();
            $('.remove-file-button').click()
        })
    </script>

    <!-- Profile Promotion Image Upload Handlr -->
    <script>
        $(".js-upload-input").on("change", function (event) {
            let file = event.target.files[0];
            const target = $(this).data('target');
            let blobURL = URL.createObjectURL(file);
            const prevImage = $(this).closest('.upload-file').find('.upload-file__img').find('img').attr('src');
            $(this).closest('.upload-file').find('.upload-file__img').html('<img src="' + blobURL + '" alt="">');
            $(this).closest('.upload-file').find('.remove-file-button').show()
            $('#profile-preview-box').find('.' + target).css('background', 'url(' + blobURL + ') no-repeat center center / cover');
            $(this).closest('.upload-file').find('.remove-file-button').on('click', function () {
                $('#profile-preview-box').find('.' + target).css('background', 'rgba(117, 133, 144, 0.1)');
                $(this).hide();
                $(this).closest('.upload-file').find('.upload-file__img').find('img').attr('src', prevImage);
                file ? $(this).closest('.upload-file').find('.js-upload-input').val(file) : ''
            })
        })
    </script>

    <!-- Title and Description Change Handlr -->
    <script>
        $('[data-preview-text]').on('input', function (event) {
            const target = $(this).data('preview-text');
            if (event.target.value) {
                $('.' + target).each(function () {
                    $(this).find('.main-text').text(event.target.value)
                    $(this).find('.placeholder-text').hide()
                    $(this).find('.static-text').show()
                })
            } else {
                $('.' + target).each(function () {
                    $(this).find('.main-text').text('')
                    $(this).find('.placeholder-text').show()
                    $(this).find('.static-text').hide()
                })
            }
        })
        const resetTextHandlr = () => {
            $('[data-preview-text]').each(function () {
                const target = $(this).data('preview-text');
                const value = $(this).val()
                if (value) {
                    $('.' + target).each(function () {
                        $(this).find('.main-text').text(value)
                        $(this).find('.placeholder-text').hide()
                        $(this).find('.static-text').show()
                    })
                }
            })
        }
        $(window).on('load', function () {
            resetTextHandlr()
        })

        $('#create-add-form').on('reset', function () {
            window.location.reload()
        })
    </script>

    <!-- Review and Rating Handlr -->
    <script>
        $('[name="review"]').on('change', function () {
            if ($(this).is(':checked')) {
                $('.review-placeholder').hide()
                $('.review--text').show()
                $('.review-rating-demo').css('opacity', '1')
            } else {
                $('.review-placeholder').show()
                $('.review--text').hide()
                if(!$('[name="rating"]').is(':checked')){
                    $('.review-rating-demo').css('opacity', '0')
                }
            }
        })
        $('[name="rating"]').on('change', function () {
            if ($(this).is(':checked')) {
                $('.rating-text').show()
                $('.review-rating-demo').css('opacity', '1')
            } else {
                $('.rating-text').hide()
                if(!$('[name="review"]').is(':checked')){
                    $('.review-rating-demo').css('opacity', '0')
                }
            }
        })


        $(window).on('load', function () {
            $('[name="review"]').each(function () {
                if ($(this).is(':checked')) {
                    $('.review--text').show()
                } else {
                    $('.review--text').hide()
                    if(!$('[name="rating"]').is(':checked')){
                        $('.review-rating-demo').css('opacity', '0')
                    }
                }
            })
            $('[name="rating"]').each(function () {
                if ($(this).is(':checked')) {
                    $('.rating-text').show()
                } else {
                    $('.rating-text').hide()
                    if(!$('[name="review"]').is(':checked')){
                        $('.review-rating-demo').css('opacity', '0')
                    }
                }
            })
        })
    </script>

<script>
            $(document).on('ready', function() {
                    $('.js-data-example-ajax').select2({
                        ajax: {
                            url: '{{ url('/') }}/admin/store/get-stores',
                            data: function(params) {
                                return {
                                    q: params.term, // search term
                                    page: params.page,
                                    module_id:{{ config('module')['current_module_id'] }}
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data
                                };
                            },
                            __port: function(params, success, failure) {
                                let $request = $.ajax(params);

                                $request.then(success);
                                $request.fail(failure);

                                return $request;
                            }
                        }
                    });


                $('#create-add-form').on('submit', function (event) {
                    event.preventDefault();
                    let formData = new FormData(this);
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{ route('admin.advertisement.store') }}',
                        data: $('#create-add-form').serialize(),
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        beforeSend: function () {
                            $('#loading').show();
                        },
                        success: function (data) {
                            $('#loading').hide();

                            if (data.errors) {
                                for (let i = 0; i < data.errors.length; i++) {
                                    toastr.error(data.errors[i].message, {
                                        CloseButton: true,
                                        ProgressBar: true
                                    });
                                }
                            } else {
                                toastr.success(data.message, {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                                setTimeout(function () {
                                    location.href = '{{route('admin.advertisement.index')}}';
                                }, 2000);
                            }
                        }
                    });
                });
            });



            $(document).on('change', '.js-data-example-ajax', function () {
                var store_id= $(this).val();
                check_review_and_rating(store_id)
            });
            $(document).on('change', '#is_review_checked', function () {

                if($(this).is(':checked') == true){
                    var store_id= $('.js-data-example-ajax').val();
                    if(store_id){
                        check_review_and_rating(store_id)
                    }
                }

            });
            $(document).on('change', '#is_rating_checked', function () {

                if($(this).is(':checked') == true){
                    var store_id= $('.js-data-example-ajax').val();
                    if(store_id){
                        check_review_and_rating(store_id)
                    }
                }
            });






            function check_review_and_rating(store_id){
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: "{{route('admin.store.get-store-ratings')}}",
                    method: 'get',
                    data: {
                        store_id: store_id,
                    },
                    beforeSend: function () {

                    },
                    success: function (response) {
                        $('#rating_data').html(response.rating);
                        $('#review_data').html( ' (' + response.review +  '+)' ) ;

                    },
                    complete: function () {
                    },
                });
            }

</script>

<!-- Instagram Integration Scripts -->
<script>
    // Video source selection handler
    $('.video-source-radio').on('change', function() {
        const selectedSource = $(this).val();

        // Hide all sections
        $('#upload-video-section').addClass('d-none');
        $('#instagram-reel-section').addClass('d-none');
        $('#instagram-url-section').addClass('d-none');

        // Show selected section
        if (selectedSource === 'upload') {
            $('#upload-video-section').removeClass('d-none');
            // Reset to regular video preview when switching to upload
            resetToVideoPreview();
        } else if (selectedSource === 'instagram_reel') {
            $('#instagram-reel-section').removeClass('d-none');
        } else if (selectedSource === 'instagram_url') {
            $('#instagram-url-section').removeClass('d-none');
        }
    });

    // Open Instagram reels modal
    $('#select-instagram-reel-btn').on('click', function() {
        const storeId = $('#store_id').val();

        if (!storeId) {
            toastr.error('{{ translate("Please select a store first") }}', {
                CloseButton: true,
                ProgressBar: true
            });
            return;
        }

        // Reset modal
        $('#instagram-reels-loading').show();
        $('#instagram-reels-error').addClass('d-none');
        $('#instagram-reels-container').addClass('d-none').html('');

        // Open modal
        $('#instagramReelsModal').modal('show');

        // Fetch Instagram reels
        $.ajax({
            url: '{{ route("admin.advertisement.instagram.reels") }}',
            method: 'GET',
            data: { store_id: storeId },
            success: function(response) {
                $('#instagram-reels-loading').hide();

                if (response.success && response.reels.length > 0) {
                    displayInstagramReels(response.reels);
                } else {
                    $('#instagram-reels-error')
                        .removeClass('d-none')
                        .html('<i class="tio-info-outined"></i> ' + (response.message || '{{ translate("No Instagram reels found") }}'));
                }
            },
            error: function(xhr) {
                $('#instagram-reels-loading').hide();
                let errorMessage = '{{ translate("Error loading Instagram reels") }}';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                $('#instagram-reels-error')
                    .removeClass('d-none')
                    .html('<i class="tio-error"></i> ' + errorMessage);
            }
        });
    });

    // Display Instagram reels in modal
    function displayInstagramReels(reels) {
        const container = $('#instagram-reels-container');
        container.html('');

        reels.forEach(function(reel) {
            const reelCard = `
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="card h-100 instagram-reel-card" data-reel-url="${reel.permalink}" data-reel-thumbnail="${reel.thumbnail_url}" style="cursor: pointer;">
                        <img src="${reel.thumbnail_url}" class="card-img-top" alt="Reel thumbnail" style="height: 200px; object-fit: cover;">
                        <div class="card-body p-2">
                            <p class="card-text small text-truncate" title="${reel.caption}">${reel.caption}</p>
                            <small class="text-muted">${reel.formatted_date}</small>
                        </div>
                    </div>
                </div>
            `;
            container.append(reelCard);
        });

        container.removeClass('d-none');

        // Handle reel selection
        $('.instagram-reel-card').on('click', function() {
            const reelUrl = $(this).data('reel-url');
            const reelThumbnail = $(this).data('reel-thumbnail');

            // Highlight selected card
            $('.instagram-reel-card').removeClass('border-primary');
            $(this).addClass('border-primary border-3');

            // Set hidden input
            $('#instagram_reel_url').val(reelUrl);

            // Show preview
            $('#selected-reel-preview').removeClass('d-none');
            $('#selected-reel-thumbnail').html(`
                <img src="${reelThumbnail}" alt="Selected reel" style="max-width: 200px; border-radius: 8px;">
                <p class="mt-2 small"><a href="${reelUrl}" target="_blank">${reelUrl}</a></p>
            `);

            // Update video preview with Instagram embed
            updateMainVideoPreviewWithEmbed(reelUrl, '');

            // Close modal after selection
            setTimeout(function() {
                $('#instagramReelsModal').modal('hide');
                toastr.success('{{ translate("Instagram reel selected successfully") }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
            }, 500);
        });
    }

    // Store selection handler - clear Instagram reel selection
    $('#store_id').on('change', function() {
        $('#instagram_reel_url').val('');
        $('#selected-reel-preview').addClass('d-none');
    });

    // Preview Instagram URL handler
    $('#preview-instagram-url-btn').on('click', function() {
        const reelUrl = $('#instagram_manual_url').val().trim();

        if (!reelUrl) {
            toastr.error('{{ translate("Please enter Instagram reel URL") }}', {
                CloseButton: true,
                ProgressBar: true
            });
            return;
        }

        // Validate Instagram URL format - accepts all valid Instagram formats
        // Supports:
        // - https://www.instagram.com/reel/ABC123/
        // - https://instagram.com/reel/ABC123/
        // - https://www.instagram.com/username/reel/ABC123/
        // - https://www.instagram.com/p/ABC123/
        // - https://instagr.am/reel/ABC123/
        // - URLs with query parameters (?igsh=...)
        const instagramUrlPattern = /^https?:\/\/(www\d?\.)?instagram\.com\/([\w.]+\/)?(reel|p)\/[A-Za-z0-9_-]+/i;
        const instagramShortPattern = /^https?:\/\/(www\.)?instagr\.am\/(reel|p)\/[A-Za-z0-9_-]+/i;

        if (!instagramUrlPattern.test(reelUrl) && !instagramShortPattern.test(reelUrl)) {
            $('#manual-url-error')
                .removeClass('d-none')
                .html('<i class="tio-error"></i> {{ translate("Invalid Instagram URL. Please paste a valid Instagram reel or post URL.") }}');
            $('#manual-url-preview').addClass('d-none');
            return;
        }

        // Hide error
        $('#manual-url-error').addClass('d-none');

        // Show loading
        $('#manual-url-embed').html(`
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">{{ translate("Loading preview...") }}</p>
        `);
        $('#manual-url-preview').removeClass('d-none');

        // Show Instagram embed
        showInstagramEmbed(reelUrl);
    });

    // Show Instagram embed using oEmbed API
    function showInstagramEmbed(reelUrl) {
        // IMPORTANT: Facebook Graph API instagram_oembed endpoint requires authentication
        // and returns 403 Forbidden when called directly from browser without access token.
        // Using fallback method (direct blockquote embed) which works without authentication.
        showInstagramEmbedFallback(reelUrl);

        /* DISABLED: oEmbed API requires authentication (returns 403 Forbidden)
        $.ajax({
            url: 'https://graph.facebook.com/v21.0/instagram_oembed',
            method: 'GET',
            data: {
                url: reelUrl,
                omitscript: false,
                maxwidth: 400
            },
            success: function(oembed) {
                const embedHtml = `
                    <div style="max-width: 400px; margin: 0 auto;" id="instagram-embed-container">
                        ${oembed.html}
                        <p class="mt-3 text-success">
                            <i class="tio-checkmark-circle"></i> {{ translate("Preview loaded successfully") }}
                        </p>
                        <p class="small">
                            <a href="${reelUrl}" target="_blank" rel="noopener">
                                <i class="tio-open-in-new"></i> {{ translate("Open in Instagram") }}
                            </a>
                        </p>
                    </div>
                `;

                $('#manual-url-embed').html(embedHtml);

                loadInstagramEmbedScript(function() {
                    if (window.instgrm && window.instgrm.Embeds) {
                        window.instgrm.Embeds.process();
                    }
                });

                updateMainVideoPreviewWithEmbed(reelUrl, oembed.html);

                toastr.success('{{ translate("Instagram reel preview loaded") }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
            },
            error: function(xhr) {
                showInstagramEmbedFallback(reelUrl);
            }
        });
        */
    }

    // Fallback method using direct embed with Instagram script
    function showInstagramEmbedFallback(reelUrl) {
        const embedHtml = `
            <div style="max-width: 400px; margin: 0 auto;">
                <blockquote class="instagram-media"
                    data-instgrm-permalink="${reelUrl}"
                    data-instgrm-version="14"
                    style="background:#FFF; border:0; border-radius:8px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:540px; min-width:326px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);">
                    <div style="padding:16px;">
                        <a href="${reelUrl}" target="_blank" style="background:#FFFFFF; line-height:0; padding:0 0; text-align:center; text-decoration:none; width:100%;">
                            View this post on Instagram
                        </a>
                    </div>
                </blockquote>
                <p class="mt-3 text-success">
                    <i class="tio-checkmark-circle"></i> {{ translate("Preview loaded successfully") }}
                </p>
                <p class="small">
                    <a href="${reelUrl}" target="_blank" rel="noopener">
                        <i class="tio-open-in-new"></i> {{ translate("Open in Instagram") }}
                    </a>
                </p>
            </div>
        `;

        $('#manual-url-embed').html(embedHtml);

        // Load Instagram embed script and process
        loadInstagramEmbedScript(function() {
            if (window.instgrm && window.instgrm.Embeds) {
                window.instgrm.Embeds.process();
            }
        });

        // Update main preview
        updateMainVideoPreviewWithEmbed(reelUrl, embedHtml);

        toastr.success('{{ translate("Instagram reel preview loaded") }}', {
            CloseButton: true,
            ProgressBar: true
        });
    }

    // Update main video preview with Instagram embed (blockquote method)
    function updateMainVideoPreviewWithEmbed(reelUrl, embedHtml) {
        // Hide regular video preview
        $('#regular-video-preview').addClass('d-none');
        $('#regular-video-preview video').attr('src', '');

        // Show Instagram embed preview
        const instagramPreview = $('#instagram-embed-preview');
        const instagramContent = $('#instagram-embed-content');

        instagramPreview.removeClass('d-none');
        instagramContent.addClass('loading');

        // Add Instagram blockquote embed
        instagramContent.html(`
            <blockquote class="instagram-media"
                data-instgrm-permalink="${reelUrl}"
                data-instgrm-version="14"
                style="background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:540px; min-width:326px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);">
                <div style="padding:16px;">
                    <a href="${reelUrl}" target="_blank" style="background:#FFFFFF; line-height:0; padding:0 0; text-align:center; text-decoration:none; width:100%; display:block;">
                        <div style="display: flex; flex-direction: row; align-items: center;">
                            <div style="background-color: #F4F4F4; border-radius: 50%; flex-grow: 0; height: 40px; margin-right: 14px; width: 40px;"></div>
                            <div style="display: flex; flex-direction: column; flex-grow: 1; justify-content: center;">
                                <div style="background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; margin-bottom: 6px; width: 100px;"></div>
                                <div style="background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; width: 60px;"></div>
                            </div>
                        </div>
                        <div style="padding: 19% 0;"></div>
                        <div style="display:block; height:50px; margin:0 auto 12px; width:50px;">
                            <svg width="50px" height="50px" viewBox="0 0 60 60" version="1.1" xmlns="https://www.w3.org/2000/svg">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <g transform="translate(-511.000000, -20.000000)" fill="#000000">
                                        <g>
                                            <path d="M556.869,30.41 C554.814,30.41 553.148,32.076 553.148,34.131 C553.148,36.186 554.814,37.852 556.869,37.852 C558.924,37.852 560.59,36.186 560.59,34.131 C560.59,32.076 558.924,30.41 556.869,30.41 M541,60.657 C535.114,60.657 530.342,55.887 530.342,50 C530.342,44.114 535.114,39.342 541,39.342 C546.887,39.342 551.658,44.114 551.658,50 C551.658,55.887 546.887,60.657 541,60.657 M541,33.886 C532.1,33.886 524.886,41.1 524.886,50 C524.886,58.899 532.1,66.113 541,66.113 C549.9,66.113 557.115,58.899 557.115,50 C557.115,41.1 549.9,33.886 541,33.886 M565.378,62.101 C565.244,65.022 564.756,66.606 564.346,67.663 C563.803,69.06 563.154,70.057 562.106,71.106 C561.058,72.155 560.06,72.803 558.662,73.347 C557.607,73.757 556.021,74.244 553.102,74.378 C549.944,74.521 548.997,74.552 541,74.552 C533.003,74.552 532.056,74.521 528.898,74.378 C525.979,74.244 524.393,73.757 523.338,73.347 C521.94,72.803 520.942,72.155 519.894,71.106 C518.846,70.057 518.197,69.06 517.654,67.663 C517.244,66.606 516.755,65.022 516.623,62.101 C516.479,58.943 516.448,57.996 516.448,50 C516.448,42.003 516.479,41.056 516.623,37.899 C516.755,34.978 517.244,33.391 517.654,32.338 C518.197,30.938 518.846,29.942 519.894,28.894 C520.942,27.846 521.94,27.196 523.338,26.654 C524.393,26.244 525.979,25.756 528.898,25.623 C532.057,25.479 533.004,25.448 541,25.448 C548.997,25.448 549.943,25.479 553.102,25.623 C556.021,25.756 557.607,26.244 558.662,26.654 C560.06,27.196 561.058,27.846 562.106,28.894 C563.154,29.942 563.803,30.938 564.346,32.338 C564.756,33.391 565.244,34.978 565.378,37.899 C565.522,41.056 565.552,42.003 565.552,50 C565.552,57.996 565.522,58.943 565.378,62.101 M570.82,37.631 C570.674,34.438 570.167,32.258 569.425,30.349 C568.659,28.377 567.633,26.702 565.965,25.035 C564.297,23.368 562.623,22.342 560.652,21.575 C558.743,20.834 556.562,20.326 553.369,20.18 C550.169,20.033 549.148,20 541,20 C532.853,20 531.831,20.033 528.631,20.18 C525.438,20.326 523.257,20.834 521.349,21.575 C519.376,22.342 517.703,23.368 516.035,25.035 C514.368,26.702 513.342,28.377 512.574,30.349 C511.834,32.258 511.326,34.438 511.181,37.631 C511.035,40.831 511,41.851 511,50 C511,58.147 511.035,59.17 511.181,62.369 C511.326,65.562 511.834,67.743 512.574,69.651 C513.342,71.625 514.368,73.296 516.035,74.965 C517.703,76.634 519.376,77.658 521.349,78.425 C523.257,79.167 525.438,79.673 528.631,79.82 C531.831,79.965 532.853,80.001 541,80.001 C549.148,80.001 550.169,79.965 553.369,79.82 C556.562,79.673 558.743,79.167 560.652,78.425 C562.623,77.658 564.297,76.634 565.965,74.965 C567.633,73.296 568.659,71.625 569.425,69.651 C570.167,67.743 570.674,65.562 570.82,62.369 C570.966,59.17 571,58.147 571,50 C571,41.851 570.966,40.831 570.82,37.631"></path>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </div>
                        <div style="padding-top: 8px;">
                            <div style="color:#3897f0; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:550; line-height:18px;">View this post on Instagram</div>
                        </div>
                    </a>
                </div>
            </blockquote>
        `);

        // Load and process Instagram embeds with proper error handling
        loadInstagramEmbedScript(function() {
            if (window.instgrm && window.instgrm.Embeds) {
                console.log('Processing Instagram embed for:', reelUrl);
                window.instgrm.Embeds.process();

                // Remove loading state after processing
                setTimeout(function() {
                    instagramContent.removeClass('loading');
                }, 1000);
            } else {
                console.error('Instagram Embeds object not available');
                instagramContent.removeClass('loading');
                instagramContent.html(`
                    <div class="alert alert-warning m-3">
                        <i class="tio-error"></i> Failed to load Instagram embed.
                        <a href="${reelUrl}" target="_blank" class="alert-link">View on Instagram</a>
                    </div>
                `);
            }
        });
    }

    // Function to reset to regular video preview
    function resetToVideoPreview() {
        $('#instagram-embed-preview').addClass('d-none');
        $('#regular-video-preview').removeClass('d-none');
        $('#instagram-embed-content').html('');
    }

    // Helper function to ensure Instagram embed script is loaded
    function loadInstagramEmbedScript(callback) {
        // If already loaded and initialized
        if (window.instgrm && window.instgrm.Embeds) {
            callback();
            return;
        }

        // If script is loading
        if (window.instagramScriptLoading) {
            // Wait for it to finish
            const checkInterval = setInterval(function() {
                if (window.instgrm && window.instgrm.Embeds) {
                    clearInterval(checkInterval);
                    callback();
                }
            }, 100);
            return;
        }

        // Mark as loading
        window.instagramScriptLoading = true;

        // Load script
        $.getScript('https://www.instagram.com/embed.js')
            .done(function() {
                console.log('Instagram embed script loaded');
                // Wait for instgrm object to be available
                const waitForInstgrm = setInterval(function() {
                    if (window.instgrm && window.instgrm.Embeds) {
                        clearInterval(waitForInstgrm);
                        window.instagramScriptLoading = false;
                        callback();
                    }
                }, 50);

                // Timeout after 5 seconds
                setTimeout(function() {
                    clearInterval(waitForInstgrm);
                    window.instagramScriptLoading = false;
                    if (!window.instgrm) {
                        console.error('Instagram embed script failed to initialize');
                    }
                }, 5000);
            })
            .fail(function(jqxhr, settings, exception) {
                console.error('Failed to load Instagram embed script:', exception);
                window.instagramScriptLoading = false;
            });
    }

    // Auto-preview on URL paste/input (with debounce)
    let urlInputTimeout;
    $('#instagram_manual_url').on('input', function() {
        const url = $(this).val().trim();

        // Clear previous timeout
        clearTimeout(urlInputTimeout);

        // Hide previous preview
        $('#manual-url-preview').addClass('d-none');
        $('#manual-url-error').addClass('d-none');

        // If URL looks complete, auto-trigger preview after 1 second
        // Supports: instagram.com/reel/ABC, instagram.com/username/reel/ABC, instagr.am/reel/ABC
        if (url && (url.match(/instagram\.com\/([\w.]+\/)?(reel|p)\/[A-Za-z0-9_-]+/i) || url.match(/instagr\.am\/(reel|p)\/[A-Za-z0-9_-]+/i))) {
            urlInputTimeout = setTimeout(function() {
                $('#preview-instagram-url-btn').click();
            }, 1000);
        }
    });
</script>

<!-- Instagram Reels Modal Styles -->
<style>
    .instagram-reel-card {
        transition: all 0.3s ease;
    }

    .instagram-reel-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .instagram-reel-card.border-primary {
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
    }

    /* Instagram Embed Preview Styles */
    .video-embed-container {
        position: relative;
        min-height: 200px;
    }

    #regular-video-preview {
        display: block;
    }

    #regular-video-preview.d-none {
        display: none !important;
    }

    .instagram-embed-preview {
        position: relative;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 8px;
        padding: 20px;
        min-height: 500px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .instagram-embed-preview.d-none {
        display: none !important;
    }

    .instagram-embed-wrapper {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .instagram-embed-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Instagram blockquote styling */
    .instagram-embed-content .instagram-media {
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        min-width: auto !important;
    }

    .instagram-embed-badge {
        position: absolute;
        top: 30px;
        right: 30px;
        background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 2;
    }

    .instagram-embed-badge i {
        font-size: 18px;
    }

    /* Loading state for Instagram embed */
    .instagram-embed-content.loading::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .instagram-embed-preview {
            min-height: 400px;
            padding: 15px;
        }

        .instagram-embed-content {
            min-height: 350px;
        }

        .instagram-embed-badge {
            top: 20px;
            right: 20px;
            padding: 6px 12px;
            font-size: 12px;
        }
    }
</style>
@endpush
