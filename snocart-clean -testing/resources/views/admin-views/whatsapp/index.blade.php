@extends('layouts.admin.app')

@section('title', 'WhatsApp Campaigns')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .wa-upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #fafafa;
    }
    .wa-upload-area:hover, .wa-upload-area.dragover {
        border-color: #28a745;
        background: #f0fff4;
    }
    .wa-upload-area i { font-size: 2.5rem; color: #ccc; margin-bottom: 0.5rem; }
    .wa-upload-area.has-preview i { display: none; }
    #imagePreview { max-width: 100%; max-height: 200px; border-radius: 8px; display: none; }
    .status-badge { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.9rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .status-badge.active { background: #d4edda; color: #155724; }
    .status-badge.inactive { background: #f8d7da; color: #721c24; }
    .status-badge .dot { width: 8px; height: 8px; border-radius: 50%; animation: pulse 2s infinite; }
    .status-badge.active .dot { background: #28a745; }
    .status-badge.inactive .dot { background: #dc3545; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    .audience-option input[type="radio"] { display: none; }
    .audience-option label { display: block; padding: 0.75rem 1rem; border: 2px solid #dee2e6; border-radius: 10px; cursor: pointer; transition: all 0.2s; }
    .audience-option input[type="radio"]:checked + label { border-color: #28a745; background: #f0fff4; }
    .campaign-table th { font-size: 0.8rem; text-transform: uppercase; color: #6c757d; font-weight: 600; }
    .badge-running { background: #fff3cd; color: #856404; }
    .badge-completed { background: #d4edda; color: #155724; }
    .badge-failed { background: #f8d7da; color: #721c24; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header pb-0 mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-header-title">
                    <i class="fab fa-whatsapp text-success me-2"></i>WhatsApp Campaigns
                </h1>
                <p class="text-muted mb-0">Broadcast image messages to your customers</p>
            </div>
        </div>
    </div>

    <!-- Account Status -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-header-title mb-0"><i class="fas fa-circle-check me-2 text-success"></i>WhatsApp Account Status</h5>
            @if($accountStatus)
                <span class="status-badge active"><span class="dot"></span>Connected</span>
            @else
                <span class="status-badge inactive"><span class="dot"></span>Error</span>
            @endif
        </div>
        <div class="card-body">
            @if($accountStatus)
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <i class="fas fa-phone text-success mb-1"></i>
                        <div class="fw-bold">{{ $accountStatus['display_phone_number'] ?? 'N/A' }}</div>
                        <small class="text-muted">Phone Number</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <i class="fas fa-building text-success mb-1"></i>
                        <div class="fw-bold">{{ $accountStatus['verified_name'] ?? 'N/A' }}</div>
                        <small class="text-muted">Business Name</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <i class="fas fa-star text-success mb-1"></i>
                        <div class="fw-bold">{{ $accountStatus['quality_rating'] ?? 'N/A' }}</div>
                        <small class="text-muted">Quality Rating</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <i class="fas fa-users text-success mb-1"></i>
                        <div class="fw-bold">{{ number_format($allCustomerCount) }}</div>
                        <small class="text-muted">Total Customers</small>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-danger mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>API Error:</strong> {{ $accountError }}
            </div>
            @endif
        </div>
    </div>

    <!-- Live Progress (hidden unless running) -->
    <div id="live-progress-card" class="card mb-4" style="display:none">
        <div class="card-header">
            <i class="fas fa-spinner fa-spin me-2 text-success"></i>
            Campaign Running: <span id="lp-name">—</span>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Progress</span>
                <span><strong id="lp-sent">0</strong> sent / <strong id="lp-failed">0</strong> failed of <strong id="lp-total">0</strong></span>
            </div>
            <div class="progress mb-2" style="height:20px;border-radius:10px;">
                <div id="lp-bar" class="progress-bar bg-success" style="width:0%;border-radius:10px;transition:width .5s">0%</div>
            </div>
            <small class="text-muted"><i class="fas fa-circle-info me-1"></i>Updates every 2 seconds</small>
        </div>
    </div>

    <!-- Send New Campaign -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-header-title mb-0"><i class="fas fa-paper-plane me-2 text-success"></i>Send New Campaign</h5>
        </div>
        <div class="card-body">
            <form id="campaignForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Campaign Name</label>
                    <input type="text" class="form-control" id="campaignName" placeholder="e.g. March Reactivation Offer" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Campaign Image</label>
                    <ul class="nav nav-tabs mb-2" id="imageTab">
                        <li class="nav-item"><a class="nav-link active" data-tab="upload" href="#">Upload File</a></li>
                        <li class="nav-item"><a class="nav-link" data-tab="url" href="#">Image URL</a></li>
                    </ul>
                    <div id="tab-upload">
                        <div class="wa-upload-area" id="uploadArea" onclick="document.getElementById('imageFile').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="mb-1 fw-semibold">Click or drag to upload</p>
                            <small class="text-muted">JPG, PNG, GIF, WEBP — max 5MB</small>
                            <img id="imagePreview" src="" alt="Preview">
                        </div>
                        <input type="file" id="imageFile" accept="image/*" class="d-none">
                    </div>
                    <div id="tab-url" class="d-none">
                        <input type="url" class="form-control" id="imageUrl" placeholder="https://example.com/image.jpg">
                    </div>
                    <div id="uploadStatus" class="mt-2"></div>
                    <input type="hidden" id="mediaId" name="media_id">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Caption / Message</label>
                    <textarea class="form-control" id="caption" rows="4" placeholder="Hi, we miss you at Snocart! 🛒 Check out our latest offers..." required></textarea>
                    <small class="text-muted">Emojis are supported ✅</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Target Audience</label>
                    <div class="row g-2">
                        <div class="col-md-4 audience-option">
                            <input type="radio" name="audience" id="aud-inactive" value="inactive" checked>
                            <label for="aud-inactive">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-user-clock text-warning"></i>
                                    <div>
                                        <div class="fw-semibold">Inactive Customers</div>
                                        <small class="text-muted">No order in 30+ days</small>
                                        <div class="badge bg-warning text-dark mt-1">{{ number_format($inactiveCount) }} people</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4 audience-option">
                            <input type="radio" name="audience" id="aud-all" value="all">
                            <label for="aud-all">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-users text-success"></i>
                                    <div>
                                        <div class="fw-semibold">All Customers</div>
                                        <small class="text-muted">Everyone who ordered</small>
                                        <div class="badge bg-success mt-1">{{ number_format($allCustomerCount) }} people</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4 audience-option">
                            <input type="radio" name="audience" id="aud-custom" value="custom">
                            <label for="aud-custom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-list text-info"></i>
                                    <div>
                                        <div class="fw-semibold">Custom List</div>
                                        <small class="text-muted">Paste phone numbers</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div id="customPhoneArea" class="mt-3 d-none">
                        <textarea class="form-control" id="customPhones" rows="5"
                            placeholder="Paste phone numbers, one per line:&#10;919876543210&#10;919123456789"></textarea>
                        <small class="text-muted">Include country code, no + sign. E.g. 919876543210</small>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success" id="previewBtn">
                        <i class="fas fa-eye me-1"></i>Preview
                    </button>
                    <button type="button" class="btn btn-success px-4" id="sendBtn" disabled>
                        <i class="fab fa-whatsapp me-1"></i>Send Campaign
                    </button>
                </div>
            </form>

            <div id="previewBox" class="mt-3 d-none">
                <div class="card bg-light border-success">
                    <div class="card-body">
                        <h6 class="text-success mb-3"><i class="fab fa-whatsapp me-1"></i>Message Preview</h6>
                        <div class="d-flex gap-3 flex-wrap">
                            <img id="previewImg" src="" style="width:120px;border-radius:8px;" alt="Preview">
                            <div>
                                <div class="p-2 bg-white rounded-3 shadow-sm" style="max-width:300px;">
                                    <p class="mb-0 small" id="previewCaption"></p>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-users me-1"></i>Recipients: <strong id="previewAudience"></strong>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign History -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-header-title mb-0"><i class="fas fa-clock-rotate-left me-2 text-success"></i>Campaign History</h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()"><i class="fas fa-refresh"></i></button>
        </div>
        <div class="card-body p-0">
            @if(empty($campaigns))
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No campaigns yet. Send your first one!</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table campaign-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">Campaign</th>
                            <th>Audience</th>
                            <th>Total</th>
                            <th>Sent</th>
                            <th>Failed</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campaigns as $c)
                        @php
                            $badgeClass = ['running'=>'badge-running','completed'=>'badge-completed','failed'=>'badge-failed'][$c->status] ?? 'bg-secondary';
                            $icon = ['running'=>'fa-spinner fa-spin','completed'=>'fa-check','failed'=>'fa-xmark'][$c->status] ?? '';
                        @endphp
                        <tr>
                            <td class="px-3">
                                <div class="fw-semibold">{{ $c->name }}</div>
                                <small class="text-muted text-truncate d-block" style="max-width:200px">{{ $c->caption }}</small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $c->audience }}</span></td>
                            <td>{{ number_format($c->total) }}</td>
                            <td class="text-success fw-semibold">{{ number_format($c->sent) }}</td>
                            <td class="text-danger">{{ number_format($c->failed) }}</td>
                            <td><span class="badge {{ $badgeClass }}"><i class="fas {{ $icon }} me-1"></i>{{ ucfirst($c->status) }}</span></td>
                            <td><small class="text-muted">{{ $c->started_at ? date('d M Y, H:i', strtotime($c->started_at)) : '—' }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <!-- Failed Log -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-header-title mb-0"><i class="fas fa-triangle-exclamation me-2 text-warning"></i>Failed Numbers — Last Campaign</h5>
        </div>
        <div class="card-body">
            @if($statusData && !empty($statusData['failed_numbers']))
            <p class="text-muted mb-2">Campaign: <strong>{{ $statusData['campaign_name'] ?? '—' }}</strong></p>
            <div style="max-height:300px;overflow-y:auto">
                <table class="table table-sm table-striped">
                    <thead><tr><th>#</th><th>Phone / Error</th></tr></thead>
                    <tbody>
                        @foreach($statusData['failed_numbers'] as $i => $fn)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><code>{{ $fn }}</code></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <p>No failed numbers from last campaign</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
const UPLOAD_URL = "{{ route('admin.whatsapp.upload') }}";
const BLAST_URL  = "{{ route('admin.whatsapp.blast') }}";
const STATUS_URL = "{{ route('admin.whatsapp.status') }}";
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

// Tab switching
document.querySelectorAll('#imageTab .nav-link').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('#imageTab .nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        const tab = link.dataset.tab;
        document.getElementById('tab-upload').classList.toggle('d-none', tab !== 'upload');
        document.getElementById('tab-url').classList.toggle('d-none', tab !== 'url');
    });
});

// File upload & drag-drop
const uploadArea = document.getElementById('uploadArea');
const imageFile  = document.getElementById('imageFile');
const imgPreview = document.getElementById('imagePreview');

uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    if (e.dataTransfer.files[0]) { imageFile.files = e.dataTransfer.files; handleFileSelect(); }
});
imageFile.addEventListener('change', handleFileSelect);

function handleFileSelect() {
    const file = imageFile.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => { imgPreview.src = e.target.result; imgPreview.style.display='block'; uploadArea.classList.add('has-preview'); };
    reader.readAsDataURL(file);
    uploadImageFile(file);
}

function uploadImageFile(file) {
    const fd = new FormData();
    fd.append('type', 'file');
    fd.append('image', file);
    fd.append('_token', CSRF_TOKEN);
    showUploadStatus('uploading', 'Uploading image to WhatsApp...');
    fetch(UPLOAD_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('mediaId').value = data.media_id;
                showUploadStatus('success', '✅ Uploaded! Media ID: ' + data.media_id);
                document.getElementById('sendBtn').disabled = false;
            } else {
                showUploadStatus('error', '❌ ' + data.error);
            }
        })
        .catch(() => showUploadStatus('error', '❌ Upload failed. Check connection.'));
}

function showUploadStatus(type, msg) {
    const el = document.getElementById('uploadStatus');
    const cls = { uploading:'alert-info', success:'alert-success', error:'alert-danger' }[type];
    el.innerHTML = `<div class="alert ${cls} py-2 mb-0">${msg}</div>`;
}

// Audience toggle
document.querySelectorAll('input[name="audience"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('customPhoneArea').classList.toggle('d-none', r.value !== 'custom');
    });
});

// Preview
document.getElementById('previewBtn').addEventListener('click', () => {
    const caption  = document.getElementById('caption').value;
    const audience = document.querySelector('input[name="audience"]:checked')?.value;
    const labels   = { inactive: 'Inactive Customers (30+ days)', all: 'All Customers', custom: 'Custom List' };
    document.getElementById('previewCaption').textContent   = caption || '(no caption)';
    document.getElementById('previewAudience').textContent  = labels[audience] || audience;
    const imgSrc = document.getElementById('imagePreview').src || document.getElementById('imageUrl').value;
    if (imgSrc) document.getElementById('previewImg').src = imgSrc;
    const box = document.getElementById('previewBox');
    box.classList.remove('d-none');
    box.scrollIntoView({ behavior:'smooth', block:'nearest' });
});

// Send campaign
document.getElementById('sendBtn').addEventListener('click', () => {
    const name     = document.getElementById('campaignName').value.trim();
    const mediaId  = document.getElementById('mediaId').value.trim();
    const caption  = document.getElementById('caption').value.trim();
    const audience = document.querySelector('input[name="audience"]:checked')?.value;
    const custom   = document.getElementById('customPhones').value.trim();

    if (!name)    { alert('Please enter a campaign name'); return; }
    if (!mediaId) { alert('Please upload an image first'); return; }
    if (!caption) { alert('Please enter a caption'); return; }
    if (audience === 'custom' && !custom) { alert('Please enter custom phone numbers'); return; }

    const labels = { inactive:'Inactive customers (30+ days)', all:'All customers', custom:'Custom list' };
    if (!confirm(`Send campaign "${name}" to ${labels[audience]}?\n\nThis cannot be undone.`)) return;

    // Handle URL tab if media not yet uploaded
    const isUrlTab = !document.getElementById('tab-url').classList.contains('d-none');
    if (isUrlTab && !mediaId) {
        const url = document.getElementById('imageUrl').value.trim();
        if (!url) { alert('Please enter image URL'); return; }
        const fd = new FormData();
        fd.append('type','url'); fd.append('url',url); fd.append('_token',CSRF_TOKEN);
        showUploadStatus('uploading','Uploading from URL...');
        fetch(UPLOAD_URL, { method:'POST', body:fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { document.getElementById('mediaId').value = d.media_id; doSend(name,d.media_id,caption,audience,custom); }
                else alert('Image upload failed: ' + d.error);
            });
        return;
    }
    doSend(name, mediaId, caption, audience, custom);
});

function doSend(name, mediaId, caption, audience, custom) {
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Starting...';
    const fd = new FormData();
    fd.append('name',name); fd.append('media_id',mediaId); fd.append('caption',caption);
    fd.append('audience',audience); fd.append('custom_phones',custom); fd.append('_token',CSRF_TOKEN);
    fetch(BLAST_URL, { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check me-1"></i>Campaign Started!';
                document.getElementById('live-progress-card').style.display = 'block';
                startProgressPolling();
                setTimeout(() => location.reload(), 4000);
            } else {
                alert('Error: ' + data.error);
                btn.disabled = false;
                btn.innerHTML = '<i class="fab fa-whatsapp me-1"></i>Send Campaign';
            }
        })
        .catch(err => {
            alert('Request failed: ' + err);
            btn.disabled = false;
            btn.innerHTML = '<i class="fab fa-whatsapp me-1"></i>Send Campaign';
        });
}

// Progress polling
let pollingInterval = null;
function startProgressPolling() {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(pollStatus, 2000);
    pollStatus();
}
function pollStatus() {
    fetch(STATUS_URL + '?t=' + Date.now())
        .then(r => r.json())
        .then(data => {
            const card = document.getElementById('live-progress-card');
            if (data.status === 'running') {
                card.style.display = 'block';
                document.getElementById('lp-name').textContent   = data.campaign_name || '—';
                document.getElementById('lp-sent').textContent   = data.sent;
                document.getElementById('lp-failed').textContent = data.failed;
                document.getElementById('lp-total').textContent  = data.total;
                const pct = data.total > 0 ? Math.round(((data.sent + data.failed) / data.total) * 100) : 0;
                const bar = document.getElementById('lp-bar');
                bar.style.width = pct + '%'; bar.textContent = pct + '%';
            } else if (data.status === 'completed') {
                clearInterval(pollingInterval);
                card.style.display = 'none';
                location.reload();
            }
        })
        .catch(() => {});
}

@if($statusData && isset($statusData['status']) && $statusData['status'] === 'running')
document.getElementById('live-progress-card').style.display = 'block';
startProgressPolling();
@endif
</script>
@endpush
