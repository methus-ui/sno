<div class="card h-100">
    <!-- Header -->
    <div class="card-header justify-content-between">
        <div class="chat-user-info w-100 d-flex align-items-center">
            <div class="chat-user-info-img">
                <img class="avatar-img onerror-image"
                     data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                     src="{{ $user['image_full_url'] }}"
                     alt="Image Description">
            </div>
            <div class="chat-user-info-content">
                <h5 class="mb-0 text-capitalize">
                    {{$user['f_name'].' '.$user['l_name']}}</h5>
                <span dir="ltr">{{ $user['phone'] }}</span>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="scroll-down">
            @foreach($convs as $con)
                @if($con->sender_id != $vendor->id)
                    <div class="pt1 pb-1">
                        <div class="conv-reply-1">
                            <h6>{{$con->message}}</h6>
                            @if($con->file!=null)
                            @foreach ($con->file_full_url as $img)
                            <br>
                                <img class="w-100 mb-3"

                                src="{{$img }}"
                                >
                                @endforeach
                            @endif
                        </div>
                        <div class="pl-1">
                            <small>{{date('d M Y',strtotime($con->created_at))}} {{date(config('timeformat'),strtotime($con->created_at))}}</small>
                        </div>
                    </div>
                @else
                    <div class="pt-1 pb-1">
                        <div class="conv-reply-2">
                            <h6>{{$con->message}}</h6>
                            @if($con->file!=null)
                            @foreach ($con->file_full_url as $img)
                            <br>
                                <img class="w-100 mb-3"

                                src="{{$img }}"
                                >
                                @endforeach
                            @endif
                        </div>
                        <div class="text-right pr-1">
                            <small>{{date('d M Y',strtotime($con->created_at))}} {{date(config('timeformat'),strtotime($con->created_at))}}</small>
                            @if ($con->is_seen == 1)
                                <span class="text-primary"><i class="tio-checkmark-circle"></i></span>
                            @else
                                <span><i class="tio-checkmark-circle-outlined"></i></span>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
            <div id="scroll-here"></div>
        </div>

    </div>
    <!-- Body -->
    <div class="card-footer border-0 conv-reply-form">

        <form action="javascript:" method="post" id="reply-form-vnd" enctype="multipart/form-data" class="conv-txtarea">
            @csrf

            <!-- Quick Templates Dropdown -->
            <div class="mb-2">
                <div class="btn-group w-100">
                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="tio-flash-outlined"></i> {{ translate('Quick Templates') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" id="templates-dropdown" style="max-height: 300px; overflow-y: auto; min-width: 300px;">
                        <div class="px-3 py-2 text-center">
                            <small class="text-muted">{{ translate('Loading templates...') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quill-custom_">
                <!-- <label for="msg" class="layer-msg"></label> -->
                <textarea id="conv-textarea" class="form-control pr--180"  rows = "1" name="reply" placeholder="{{translate('Start a new message')}}"></textarea>
                <div class="upload__box">
                    <div class="upload__img-wrap"></div>
                    <div id="file-upload-filename" class="upload__file-wrap"></div>
                    <div class="upload-btn-grp">
                        <label class="m-0">
                            <img src="{{asset('/public/assets/admin/img/gallery.png')}}" alt="">
                            <input type="file" name="images[]" class="d-none upload_input_images" data-max_length="2"  multiple="" >
                        </label>
                        <label class="m-0 emoji-icon-hidden">
                            <img src="{{asset('/public/assets/admin/img/emoji.png')}}" alt="">
                        </label>
                    </div>
                </div>

                <button type="submit"
                        class="btn btn-primary btn--primary con-reply-btn">{{translate('messages.send')}}
                </button>
            </div>
        </form>
    </div>
</div>

<script src="{{asset('public/assets/admin')}}/js/view-pages/common.js"></script>
<!-- Emoji Conv -->
<script>
    "use strict";
    $(document).ready(function() {
        $("#conv-textarea").emojioneArea({
            pickerPosition: "top",
            tonesStyle: "bullet",
            events: {
                keyup: function (editor) {
                    console.log(editor.html());
                    console.log(this.getText());
                }
            }
        });
    });

    // Image Upload
    jQuery(document).ready(function () {
        ImgUpload();
    });
    function ImgUpload() {
        let imgWrap = "";
        let imgArray = [];

        $('.upload_input_images').each(function () {
            $(this).on('change', function (e) {
                imgWrap = $(this).closest('.upload__box').find('.upload__img-wrap');
                let maxLength = $(this).attr('data-max_length');

                let files = e.target.files;
                let filesArr = Array.prototype.slice.call(files);
                console.log(filesArr);
                let iterator = 0;
                filesArr.forEach(function (f) {

                    if (!f.type.match('image.*')) {
                        return;
                    }

                    if (imgArray.length > maxLength) {
                        return false
                    } else {
                        let len = 0;
                        for (let i = 0; i < imgArray.length; i++) {
                            if (imgArray[i] !== undefined) {
                                len++;
                            }
                        }
                        if (len > maxLength) {
                            return false;
                        } else {
                            imgArray.push(f);

                            let reader = new FileReader();
                            reader.onload = function (e) {
                                let html = "<div class='upload__img-box'><div style='background-image: url(" + e.target.result + ")' data-number='" + $(".upload__img-close").length + "' data-file='" + f.name + "' class='img-bg'><div class='upload__img-close'></div></div></div>";
                                imgWrap.append(html);
                                iterator++;
                            }
                            reader.readAsDataURL(f);
                        }
                    }
                });
            });
        });

        $('body').on('click', ".upload__img-close", function () {
            let file = $(this).parent().data("file");
            for (let i = 0; i < imgArray.length; i++) {
                if (imgArray[i].name === file) {
                    imgArray.splice(i, 1);
                    break;
                }
            }
            $(this).parent().parent().remove();
        });
    }

    //File Upload
    $('#file-upload').change(function(e){
        let fileName = e.target.files[0].name;
        $('#file-upload-filename').text(fileName)
    });


    $(document).ready(function () {
        $('.scroll-down').animate({
            scrollTop: $('#scroll-here').offset().top
        },0);
    });


    $(function() {
        $("#coba").spartanMultiImagePicker({
            fieldName: 'images[]',
            maxCount: 3,
            rowHeight: '55px',
            groupClassName: 'attc--img',
            maxFileSize: '',
            placeholderImage: {
                image: '{{ asset('public/assets/admin/img/attatchments.png') }}',
                width: '100%'
            },
            dropFileLabel: "Drop Here",
            onAddRow: function(index, file) {

            },
            onRenderedPreview: function(index) {

            },
            onRemoveRow: function(index) {

            },
            onExtensionErr: function() {
                toastr.error('{{ translate('messages.please_only_input_png_or_jpg_type_file') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
            },
            onSizeErr: function() {
                toastr.error('{{ translate('messages.file_size_too_big') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
            }
        });
    });


    $('#reply-form-vnd').on('submit', function() {
        $('button[type=submit], input[type=submit]').prop('disabled',true);
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('vendor.message.store', ['user_id'=>$user->id,'user_type'=>$user_type]) }}',
                // data: $('reply-form-vnd').serialize(),
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    if (data.errors && data.errors.length > 0) {
                        $('button[type=submit], input[type=submit]').prop('disabled',false);
                        toastr.error('Write something to send massage!', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }else{

                    toastr.success('Message sent', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                    $('#view-conversation').html(data.view);
                    conversationList();
                }
            },
            error() {
                toastr.error('Write something to send massage!', {
                    CloseButton: true,
                    ProgressBar: true
                });
            }
        });
    });

    // Load Quick Templates
    function loadTemplates() {
        $.get({
            url: '{{ route('vendor.message.templates') }}',
            success: function(data) {
                let dropdown = $('#templates-dropdown');
                dropdown.empty();

                if (data.templates && data.templates.length > 0) {
                    // Add "Create New" option at top
                    dropdown.append(`
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold">{{ translate('Quick Templates') }}</span>
                            <button class="btn btn-xs btn-primary" onclick="createNewTemplate()">
                                <i class="tio-add"></i> {{ translate('New') }}
                            </button>
                        </div>
                        <div class="dropdown-divider"></div>
                    `);

                    // Add template items
                    data.templates.forEach(function(template) {
                        dropdown.append(`
                            <a class="dropdown-item d-flex justify-content-between align-items-center template-item" href="javascript:" data-content="${template.content}" title="${template.content}">
                                <span class="text-truncate" style="max-width: 200px;">
                                    <strong>${template.title}</strong><br>
                                    <small class="text-muted">${template.content.substring(0, 40)}...</small>
                                </span>
                                <button class="btn btn-xs btn-soft-danger ml-2" onclick="deleteTemplate(${template.id}, event)">
                                    <i class="tio-delete"></i>
                                </button>
                            </a>
                        `);
                    });
                } else {
                    dropdown.append(`
                        <div class="dropdown-item text-center">
                            <small class="text-muted">{{ translate('No templates yet') }}</small><br>
                            <button class="btn btn-sm btn-primary mt-2" onclick="createNewTemplate()">
                                <i class="tio-add"></i> {{ translate('Create Template') }}
                            </button>
                        </div>
                    `);
                }
            },
            error: function() {
                console.error('Failed to load templates');
            }
        });
    }

    // Use template - insert content into textarea
    $(document).on('click', '.template-item', function() {
        let content = $(this).data('content');
        let textarea = $('#conv-textarea');

        // Insert template content
        textarea.val(content);

        // Trigger emojioneArea update if active
        if (textarea.data("emojioneArea")) {
            textarea.data("emojioneArea").setText(content);
        }

        // Focus textarea
        textarea.focus();
    });

    // Create new template
    function createNewTemplate() {
        let title = prompt('{{ translate('Template Title') }}:');
        if (!title) return;

        let content = prompt('{{ translate('Template Content') }}:');
        if (!content) return;

        $.post({
            url: '{{ route('vendor.message.templates.store') }}',
            data: {
                _token: '{{ csrf_token() }}',
                title: title,
                content: content
            },
            success: function(data) {
                toastr.success('{{ translate('Template created successfully') }}');
                loadTemplates(); // Reload dropdown
            },
            error: function() {
                toastr.error('{{ translate('Failed to create template') }}');
            }
        });
    }

    // Delete template
    function deleteTemplate(id, event) {
        event.stopPropagation(); // Prevent dropdown item click

        if (!confirm('{{ translate('Delete this template?') }}')) return;

        $.ajax({
            url: '{{ route('vendor.message.templates.delete', ['id' => '__ID__']) }}'.replace('__ID__', id),
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function() {
                toastr.success('{{ translate('Template deleted') }}');
                loadTemplates(); // Reload dropdown
            },
            error: function() {
                toastr.error('{{ translate('Failed to delete template') }}');
            }
        });
    }

    // Load templates on page load
    $(document).ready(function() {
        loadTemplates();
    });
</script>
