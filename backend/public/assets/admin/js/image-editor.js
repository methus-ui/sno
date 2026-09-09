"use strict";

/**
 * Image Editor — drag & scale image on a 1:1 white canvas, then save.
 *
 * openImageEditor(imgSrc, {
 *   sourceType: 'existing',
 *   imageName: 'file.png',
 *   saveUrl: '/admin/item/save-edited-image',
 *   removeBgUrl: '/admin/item/remove-bg',
 *   csrfToken: '...',
 *   imgElement: DOM img to update after save,
 *   fileInput: DOM input[type=file] (for new images),
 *   onApply: function(blob, dataUrl) {}
 * });
 */

var _ie = {
    img: null,
    options: {},
    x: 0, y: 0,
    scale: 1,
    dragging: false,
    dragStartX: 0, dragStartY: 0,
    imgStartX: 0, imgStartY: 0
};

function openImageEditor(imgSrc, options) {
    _ie.options = options || {};
    _ie.scale = 1;
    _ie.x = 0;
    _ie.y = 0;
    $('#ie-scale').val(100);
    $('#ie-scale-label').text('100%');

    var img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function() {
        _ie.img = img;
        // Center image on canvas
        var canvas = document.getElementById('ie-canvas');
        _ie.x = (canvas.width - img.width) / 2;
        _ie.y = (canvas.height - img.height) / 2;
        // Fit scale so image fills canvas initially
        var fitScale = Math.min(canvas.width / img.width, canvas.height / img.height);
        _ie.scale = fitScale;
        _ie.x = (canvas.width - img.width * fitScale) / 2;
        _ie.y = (canvas.height - img.height * fitScale) / 2;
        $('#ie-scale').val(Math.round(fitScale * 100));
        $('#ie-scale-label').text(Math.round(fitScale * 100) + '%');
        _ieRender();
        $('#imageEditorModal').modal('show');
    };
    img.onerror = function() {
        if (typeof toastr !== 'undefined') toastr.error('Failed to load image');
    };
    _ie.options._originalSrc = imgSrc;
    img.src = imgSrc;
}

function _ieRender() {
    var canvas = document.getElementById('ie-canvas');
    if (!canvas || !_ie.img) return;
    var ctx = canvas.getContext('2d');
    // White background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    // Draw image
    var w = _ie.img.width * _ie.scale;
    var h = _ie.img.height * _ie.scale;
    ctx.drawImage(_ie.img, _ie.x, _ie.y, w, h);
}

// Mouse drag
$(document).on('mousedown', '#ie-canvas', function(e) {
    _ie.dragging = true;
    var rect = this.getBoundingClientRect();
    var scaleRatio = 500 / rect.width; // canvas is 500px but may be CSS-scaled
    _ie.dragStartX = e.clientX;
    _ie.dragStartY = e.clientY;
    _ie.imgStartX = _ie.x;
    _ie.imgStartY = _ie.y;
    this.style.cursor = 'grabbing';
    e.preventDefault();
});

$(document).on('mousemove', function(e) {
    if (!_ie.dragging) return;
    var canvas = document.getElementById('ie-canvas');
    var rect = canvas.getBoundingClientRect();
    var scaleRatio = 500 / rect.width;
    _ie.x = _ie.imgStartX + (e.clientX - _ie.dragStartX) * scaleRatio;
    _ie.y = _ie.imgStartY + (e.clientY - _ie.dragStartY) * scaleRatio;
    _ieRender();
});

$(document).on('mouseup', function() {
    if (_ie.dragging) {
        _ie.dragging = false;
        var c = document.getElementById('ie-canvas');
        if (c) c.style.cursor = 'grab';
    }
});

// Touch drag
$(document).on('touchstart', '#ie-canvas', function(e) {
    var t = e.originalEvent.touches[0];
    _ie.dragging = true;
    _ie.dragStartX = t.clientX;
    _ie.dragStartY = t.clientY;
    _ie.imgStartX = _ie.x;
    _ie.imgStartY = _ie.y;
    e.preventDefault();
});

$(document).on('touchmove', function(e) {
    if (!_ie.dragging) return;
    var t = e.originalEvent.touches[0];
    var canvas = document.getElementById('ie-canvas');
    var rect = canvas.getBoundingClientRect();
    var scaleRatio = 500 / rect.width;
    _ie.x = _ie.imgStartX + (t.clientX - _ie.dragStartX) * scaleRatio;
    _ie.y = _ie.imgStartY + (t.clientY - _ie.dragStartY) * scaleRatio;
    _ieRender();
});

$(document).on('touchend', function() {
    _ie.dragging = false;
});

// Scale slider
$(document).on('input', '#ie-scale', function() {
    var pct = parseInt(this.value);
    $('#ie-scale-label').text(pct + '%');
    var oldScale = _ie.scale;
    _ie.scale = pct / 100;
    // Keep center point stable
    var canvas = document.getElementById('ie-canvas');
    var cx = canvas.width / 2;
    var cy = canvas.height / 2;
    _ie.x = cx - (_ie.scale / oldScale) * (cx - _ie.x);
    _ie.y = cy - (_ie.scale / oldScale) * (cy - _ie.y);
    _ieRender();
});

// Mouse wheel zoom
$(document).on('wheel', '#ie-canvas', function(e) {
    e.preventDefault();
    var delta = e.originalEvent.deltaY > 0 ? -5 : 5;
    var slider = document.getElementById('ie-scale');
    var newVal = Math.max(10, Math.min(300, parseInt(slider.value) + delta));
    slider.value = newVal;
    $(slider).trigger('input');
});

// Remove BG button inside modal
$(document).on('click', '#ie-remove-bg', function() {
    var btn = $(this);
    var url = _ie.options.removeBgUrl;
    var name = _ie.options.imageName;
    if (!url || !name) {
        if (typeof toastr !== 'undefined') toastr.error('Remove BG not available');
        return;
    }
    btn.prop('disabled', true).html('<i class="tio-refresh"></i> Removing...');
    $.ajax({
        url: url,
        method: 'POST',
        data: { image_name: name, _token: _ie.options.csrfToken },
        success: function(res) {
            if (typeof toastr !== 'undefined') toastr.success(res.message || 'Background removed!');
            // Reload image with cache bust
            var newImg = new Image();
            newImg.crossOrigin = 'anonymous';
            newImg.onload = function() {
                _ie.img = newImg;
                var canvas = document.getElementById('ie-canvas');
                var fitScale = Math.min(canvas.width / newImg.width, canvas.height / newImg.height);
                _ie.scale = fitScale;
                _ie.x = (canvas.width - newImg.width * fitScale) / 2;
                _ie.y = (canvas.height - newImg.height * fitScale) / 2;
                $('#ie-scale').val(Math.round(fitScale * 100));
                $('#ie-scale-label').text(Math.round(fitScale * 100) + '%');
                _ieRender();
                btn.html('<i class="tio-image"></i> Remove BG').prop('disabled', false);
            };
            newImg.onerror = function() {
                if (typeof toastr !== 'undefined') toastr.error('Failed to reload image');
                btn.html('<i class="tio-image"></i> Remove BG').prop('disabled', false);
            };
            // Use the original src with cache bust
            var origSrc = _ie.options._originalSrc || '';
            newImg.src = origSrc.split('?')[0] + '?t=' + Date.now();
        },
        error: function(xhr) {
            if (typeof toastr !== 'undefined') toastr.error(xhr.responseJSON?.message || 'Remove BG failed');
            btn.html('<i class="tio-image"></i> Remove BG').prop('disabled', false);
        }
    });
});

// Save / Apply
$(document).on('click', '#ie-apply', function() {
    var canvas = document.getElementById('ie-canvas');
    if (!canvas || !_ie.img) return;

    var btn = $(this);
    btn.prop('disabled', true).text('Saving...');

    canvas.toBlob(function(blob) {
        if (!blob) { btn.prop('disabled', false).text('Save'); return; }

        var dataUrl = canvas.toDataURL('image/png');

        if (_ie.options.sourceType === 'existing' && _ie.options.saveUrl) {
            var fd = new FormData();
            fd.append('image', blob, _ie.options.imageName || 'edited.png');
            fd.append('image_name', _ie.options.imageName || '');
            fd.append('_token', _ie.options.csrfToken || '');

            $.ajax({
                url: _ie.options.saveUrl,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (typeof toastr !== 'undefined') toastr.success(res.message || 'Saved!');
                    if (_ie.options.imgElement) {
                        $(_ie.options.imgElement).attr('src', (res.url || dataUrl) + '?t=' + Date.now());
                    }
                    if (_ie.options.onApply) _ie.options.onApply(blob, dataUrl);
                    $('#imageEditorModal').modal('hide');
                    btn.prop('disabled', false).text('Save');
                },
                error: function(xhr) {
                    if (typeof toastr !== 'undefined') toastr.error(xhr.responseJSON?.message || 'Save failed');
                    btn.prop('disabled', false).text('Save');
                }
            });
        } else if (_ie.options.sourceType === 'new' && _ie.options.fileInput) {
            try {
                var file = new File([blob], 'edited.png', { type: 'image/png' });
                var dt = new DataTransfer();
                dt.items.add(file);
                _ie.options.fileInput.files = dt.files;
            } catch (ex) {
                console.warn('DataTransfer not supported', ex);
            }
            if (_ie.options.imgElement) {
                $(_ie.options.imgElement).attr('src', dataUrl);
            }
            if (_ie.options.onApply) _ie.options.onApply(blob, dataUrl);
            $('#imageEditorModal').modal('hide');
            btn.prop('disabled', false).text('Save');
        } else {
            if (_ie.options.imgElement) $(_ie.options.imgElement).attr('src', dataUrl);
            if (_ie.options.onApply) _ie.options.onApply(blob, dataUrl);
            $('#imageEditorModal').modal('hide');
            btn.prop('disabled', false).text('Save');
        }
    }, 'image/png');
});

// Cleanup
$('#imageEditorModal').on('hidden.bs.modal', function() {
    _ie.img = null;
    _ie.options = {};
    _ie.dragging = false;
    var canvas = document.getElementById('ie-canvas');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
});
