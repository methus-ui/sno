{{-- Image Editor Modal - drag/scale on 1:1 canvas with Remove BG --}}
<div class="modal fade" id="imageEditorModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:560px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">{{ translate('Image Editor') }} (1:1)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center" style="background:#f4f4f4;">
                <canvas id="ie-canvas" width="500" height="500"
                    style="border:2px dashed #ccc;cursor:grab;background:#fff;max-width:100%;"></canvas>
                <div class="d-flex align-items-center justify-content-center mt-2">
                    <label class="mb-0 small mr-2">{{ translate('Scale') }}</label>
                    <input type="range" id="ie-scale" min="10" max="300" value="100" style="width:180px;">
                    <span class="small ml-2" id="ie-scale-label">100%</span>
                    <button type="button" class="btn btn-sm btn-info ml-3" id="ie-remove-bg">
                        <i class="tio-image"></i> {{ translate('Remove BG') }}
                    </button>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-sm btn-primary" id="ie-apply">{{ translate('Save') }}</button>
            </div>
        </div>
    </div>
</div>
