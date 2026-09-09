// public/assets/admin/js/face-recognition-utils.js

// Face Recognition Variables
let faceVerifyStream = null;
let faceModelsLoaded = false;
let currentPunchAction = null; // 'in' or 'out'
let storedFaceData = null;
let faceRegistered = false;
let faceVerificationInProgress = false;
let lastFacePosition = null; // New: To track previous face position for liveness
let faceMovementCounter = 0; // New: To count subtle head movements

const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
const FACE_MATCH_THRESHOLD = 0.6; // Adjusted threshold for better matching (0.6 is more lenient)

// Get config from window object (set by Blade template)
function getConfig() {
    return window.FaceRecognitionConfig || {
        routes: {},
        csrfToken: '',
        translations: {}
    };
}

function getRoute(name) {
    const config = getConfig();
    return config.routes[name] || '';
}

function getTranslation(key) {
    const config = getConfig();
    return config.translations[key] || key;
}

function getCsrfToken() {
    const config = getConfig();
    return config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// Load Face API Models
async function loadFaceModels() {
    if (faceModelsLoaded) return;
    try {
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        faceModelsLoaded = true;
        console.log('Face models loaded successfully');
    } catch (error) {
        console.error('Error loading face models:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('Failed to load face recognition models. Please try again.');
        }
    }
}

// Initialize Face Verification Process
async function startFaceVerification(action) {
    if (faceVerificationInProgress) return;
    faceVerificationInProgress = true;
    currentPunchAction = action;
    lastFacePosition = null; // Reset
    faceMovementCounter = 0; // Reset

    // Reset UI
    $('#faceVerificationModal').modal('show');
    $('#face-verify-progress').hide();
    $('#face-verify-result').hide();
    $('#retry-face-verify-btn').hide();
    $('#face-verify-video').show();
    $('#face-verify-canvas').hide();
    $('#face-verify-status').hide().html('');
    $('#face-verify-action-text').text(
        currentPunchAction === 'in' ? getTranslation('punchInVerification') : getTranslation('punchOutVerification')
    );

    if (!faceModelsLoaded) {
        if (typeof toastr !== 'undefined') {
            toastr.info(getTranslation('loadingFaceModels'));
        }
        await loadFaceModels();
        if (!faceModelsLoaded) {
            showFaceVerifyResult(false, getTranslation('faceModelsNotLoaded'), true);
            faceVerificationInProgress = false;
            return;
        }
    }

    try {
        faceVerifyStream = await navigator.mediaDevices.getUserMedia({
            video: { width: 640, height: 480, facingMode: 'user' }
        });

        const video = document.getElementById('face-verify-video');
        video.srcObject = faceVerifyStream;
        video.onloadedmetadata = () => {
            $('#face-verify-progress-text').text(getTranslation('preparingCamera') + '...');
            $('#face-verify-progress').show();
            setTimeout(() => {
                $('#face-verify-progress').hide();
                performFaceVerification();
            }, 2000); // Give camera some time to adjust
        };
    } catch (error) {
        console.error('Camera error:', error);
        let errorMessage = getTranslation('cameraAccessDenied');
        if (error.name === 'NotAllowedError') {
            errorMessage = getTranslation('cameraPermissionDenied');
        } else if (error.name === 'NotFoundError') {
            errorMessage = getTranslation('noCameraFound');
        }
        showFaceVerifyResult(false, errorMessage, true);
        faceVerificationInProgress = false;
    }
}

// Perform Face Verification
async function performFaceVerification() {
    const video = document.getElementById('face-verify-video');
    const canvas = $('#face-verify-canvas').get(0);
    const context = canvas.getContext('2d');
    const displaySize = { width: video.videoWidth, height: video.videoHeight };
    faceapi.matchDimensions(canvas, displaySize);

    $('#face-verify-progress').hide();
    $('#face-verify-status').html('<span class="text-info"><i class="tio-sync mr-1"></i>Looking for face...</span>').show();

    console.log('Starting face verification loop...');

    // Loop for continuous detection - simplified without liveness check
    const detectionInterval = setInterval(async () => {
        if (!faceVerificationInProgress) {
            clearInterval(detectionInterval);
            return;
        }

        try {
            console.log('Detecting face...');
            const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptors();

            console.log('Detections found:', detections.length);

            if (detections.length === 0) {
                $('#face-verify-status').html('<span class="text-warning"><i class="tio-warning-outlined mr-1"></i>No face detected - look at camera</span>').show();
                return;
            } else if (detections.length > 1) {
                $('#face-verify-status').html('<span class="text-danger"><i class="tio-warning-outlined mr-1"></i>Multiple faces detected</span>').show();
                return;
            }

            // Single face detected
            const detection = detections[0];
            $('#face-verify-status').html('<span class="text-info"><i class="tio-face-id mr-1"></i>Face detected - comparing...</span>').show();

            // --- Face Comparison (skipping liveness for now) ---
            console.log('Starting face comparison...');
            console.log('storedFaceData:', storedFaceData);

            if (storedFaceData && storedFaceData.descriptor) {
                const storedDescriptor = new Float32Array(Object.values(storedFaceData.descriptor));
                const currentDescriptor = detection.descriptor;
                const distance = faceapi.euclideanDistance(storedDescriptor, currentDescriptor);

                console.log('Face comparison distance:', distance);
                console.log('Threshold:', FACE_MATCH_THRESHOLD);
                console.log('Match result:', distance < FACE_MATCH_THRESHOLD ? 'MATCH' : 'NO MATCH');

                if (distance < FACE_MATCH_THRESHOLD) {
                    clearInterval(detectionInterval);
                    console.log('Face MATCHED! Executing punch...');
                    showFaceVerifyResult(true, 'Face verified successfully!', false);
                    await executePunch();
                } else {
                    $('#face-verify-status').html('<span class="text-danger"><i class="tio-warning-outlined mr-1"></i>Face not matched (Distance: ' + distance.toFixed(3) + ', need < ' + FACE_MATCH_THRESHOLD + ')</span>').show();
                }
            } else {
                console.error('Stored face data invalid:', storedFaceData);
                clearInterval(detectionInterval);
                showFaceVerifyResult(false, 'No stored face data found', true);
            }
        } catch (e) {
            console.error('Error in face verification loop:', e);
            $('#face-verify-status').html('<span class="text-danger"><i class="tio-warning-outlined mr-1"></i>Error: ' + e.message + '</span>').show();
        }
    }, 1500); // Check every 1.5 seconds
}

// Show Face Verification Result
function showFaceVerifyResult(success, message, allowRetry = true) {
    faceVerificationInProgress = false;
    $('#face-verify-progress').hide();
    $('#face-verify-status').hide();
    $('#face-verify-result').show();

    const icon = $('#face-verify-result-icon');
    const text = $('#face-verify-result-text');

    if (success) {
        icon.removeClass('tio-clear-circle text-danger').addClass('tio-checkmark-circle text-success');
        text.removeClass('text-danger').addClass('text-success');
    } else {
        icon.removeClass('tio-checkmark-circle text-success').addClass('tio-clear-circle text-danger');
        text.removeClass('text-success').addClass('text-danger');
        if (allowRetry) {
            $('#retry-face-verify-btn').show();
        } else {
            $('#retry-face-verify-btn').hide();
        }
    }
    text.text(message);
}

// Execute Punch In/Out
async function executePunch() {
    const url = currentPunchAction === 'in'
        ? getRoute('punchInFace')
        : getRoute('punchOutFace');

    const notes = currentPunchAction === 'out' ? ($('#punch-out-notes').val() || '') : '';

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                face_verified: true,
                notes: notes
            })
        });
        const data = await response.json();

        if (data.success) {
            if (typeof toastr !== 'undefined') {
                toastr.success(data.message);
            }
            loadAttendanceStatus(); // Reload attendance status on success
            cancelFaceVerification(); // Close modal on success
        } else {
            showFaceVerifyResult(false, data.error || getTranslation('punchFailed'), true);
        }
    } catch (error) {
        console.error('Punch API error:', error);
        showFaceVerifyResult(false, getTranslation('punchFailed'), true);
    }
}

// Retry Face Verification
function retryFaceVerification() {
    $('#face-verify-result').hide();
    $('#retry-face-verify-btn').hide();
    startFaceVerification(currentPunchAction);
}

// Cancel Face Verification
function cancelFaceVerification() {
    if (faceVerifyStream) {
        faceVerifyStream.getTracks().forEach(track => track.stop());
        faceVerifyStream = null;
    }
    faceVerificationInProgress = false;
    currentPunchAction = null;
    $('#faceVerificationModal').modal('hide');
}

// Export these functions to be accessible globally
window.loadFaceModels = loadFaceModels;
window.startFaceVerification = startFaceVerification;
window.cancelFaceVerification = cancelFaceVerification;
window.retryFaceVerification = retryFaceVerification;
window.faceRegistered = faceRegistered; // Expose for external access
window.storedFaceData = storedFaceData; // Expose for external access

// Punch In/Out functions (face authentication DISABLED)
function punchIn() {
    // Face authentication disabled - use regular punch in
    executePunchWithoutFace('in');
}

function punchOut() {
    // Face authentication disabled - use regular punch out
    executePunchWithoutFace('out');
}

// Execute punch without face verification
async function executePunchWithoutFace(action) {
    const url = action === 'in'
        ? getRoute('punchIn')
        : getRoute('punchOut');

    const notes = action === 'out' ? ($('#punch-out-notes').val() || '') : '';

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                notes: notes
            })
        });

        const data = await response.json();

        if (data.success || response.ok) {
            if (typeof toastr !== 'undefined') {
                toastr.success(data.message || (action === 'in' ? 'Punched in successfully' : 'Punched out successfully'));
            }
            loadAttendanceStatus(); // Reload attendance status

            // Reload break status if function exists
            if (typeof dashPollBreakStatus === 'function') {
                dashPollBreakStatus();
            }

            // Reload page to update UI
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.error(data.error || 'Punch failed');
            }
        }
    } catch (error) {
        console.error('Punch API error:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('Punch failed');
        }
    }
}

// Employee Clock Functions
function updateEmployeeClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    const clockElement = document.getElementById('employee-clock');
    if (clockElement) {
        clockElement.textContent = `${hours}:${minutes}:${seconds}`;
    }

    const dateElement = document.getElementById('employee-date');
    if (dateElement) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateElement.textContent = now.toLocaleDateString('en-US', options);
    }

    const greetingElement = document.getElementById('clock-greeting');
    if (greetingElement) {
        const hour = now.getHours();
        let greeting = getTranslation('goodMorning');
        if (hour >= 12 && hour < 17) {
            greeting = getTranslation('goodAfternoon');
        } else if (hour >= 17) {
            greeting = getTranslation('goodEvening');
        }
        greetingElement.textContent = greeting;
    }
}

// Load attendance status
function loadAttendanceStatus() {
    const url = getRoute('attendanceToday');
    if (!url) {
        console.error('Attendance route not configured');
        return;
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Attendance error:', data.error);
                return;
            }

            const punchInBtn = document.getElementById('punch-in-btn');
            const punchOutBtn = document.getElementById('punch-out-btn');

            // Update global faceRegistered and storedFaceData
            faceRegistered = data.face_registered;
            window.faceRegistered = faceRegistered; // Update window reference too

            console.log('=== Face Data Loading ===');
            console.log('face_registered from API:', data.face_registered);
            console.log('face_data from API:', data.face_data ? 'Present (length: ' + data.face_data.length + ')' : 'Not present');

            if (data.face_data) {
                try {
                    storedFaceData = JSON.parse(data.face_data);
                    window.storedFaceData = storedFaceData;
                    console.log('Parsed storedFaceData:', storedFaceData);
                    console.log('Descriptor length:', storedFaceData?.descriptor?.length || 0);
                } catch (e) {
                    console.error('Error parsing face data:', e);
                    console.error('Raw face_data:', data.face_data);
                }
            } else {
                console.log('No face_data in API response');
            }

            if (data.attendance) {
                const punchInTime = document.getElementById('punch-in-time');
                const punchOutTime = document.getElementById('punch-out-time');
                const workedHours = document.getElementById('worked-hours');

                if (punchInTime) {
                    punchInTime.textContent = data.attendance.punch_in
                        ? new Date(data.attendance.punch_in).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                        : '--:--';
                }
                if (punchOutTime) {
                    punchOutTime.textContent = data.attendance.punch_out
                        ? new Date(data.attendance.punch_out).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                        : '--:--';
                }
                if (workedHours && data.attendance.total_hours) {
                    const hours = Math.floor(data.attendance.total_hours);
                    const mins = Math.round((data.attendance.total_hours - hours) * 60);
                    workedHours.textContent = `${hours}h ${mins}m`;
                }
            }

            // Enable/disable buttons based on can_punch_in and can_punch_out
            if (punchInBtn) {
                punchInBtn.disabled = !data.can_punch_in;
                console.log('Punch In button disabled:', !data.can_punch_in);
            }
            if (punchOutBtn) {
                punchOutBtn.disabled = !data.can_punch_out;
                console.log('Punch Out button disabled:', !data.can_punch_out);
            }

            console.log('Attendance status loaded:', {
                can_punch_in: data.can_punch_in,
                can_punch_out: data.can_punch_out,
                face_registered: data.face_registered
            });
        })
        .catch(error => console.error('Error loading attendance:', error));
}

// Load employee stats - disabled for now as endpoint doesn't return JSON
function loadEmployeeStats() {
    // TODO: Implement employee stats API endpoint
    console.log('Employee stats loading skipped - endpoint not implemented');

    // Set default values
    const daysWorked = document.getElementById('days-worked');
    if (daysWorked) daysWorked.textContent = '-';

    const totalHours = document.getElementById('total-hours-month');
    if (totalHours) totalHours.textContent = '-';

    const pendingLeaves = document.getElementById('pending-leaves');
    if (pendingLeaves) pendingLeaves.textContent = '-';

    const approvedLeaves = document.getElementById('approved-leaves');
    if (approvedLeaves) approvedLeaves.textContent = '-';
}


// Notes Management (Local Storage)
const NOTES_KEY = 'employee_notes'; // Unique key for employee notes

function loadNotes() {
    const notes = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
    const container = document.getElementById('notes-container');
    const noNotesMsg = document.getElementById('no-notes-msg');

    if (!container) return;

    if (notes.length === 0) {
        if (noNotesMsg) noNotesMsg.style.display = 'block';
        container.innerHTML = ''; // Clear container if no notes
        return;
    }

    if (noNotesMsg) noNotesMsg.style.display = 'none';

    let html = '';
    notes.forEach((note, index) => {
        const date = new Date(note.created_at);
        html += `
            <div class="note-item d-flex justify-content-between align-items-start">
                <div>
                    <strong>${note.title}</strong>
                    <p class="mb-0 text-muted small">${note.content}</p>
                    <span class="note-time">${date.toLocaleDateString()} ${date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                </div>
                <button class="btn btn-sm btn-link text-danger" onclick="deleteNote(${index})">
                    <i class="tio-delete"></i>
                </button>
            </div>
        `;
    });

    container.innerHTML = html;
}

function saveNote() {
    const title = document.getElementById('noteTitle').value;
    const content = document.getElementById('noteContent').value;

    if (!title || !content) {
        if (typeof toastr !== 'undefined') {
            toastr.warning(getTranslation('pleaseFillAllFields'));
        }
        return;
    }

    const notes = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
    notes.unshift({
        title: title,
        content: content,
        created_at: new Date().toISOString()
    });

    localStorage.setItem(NOTES_KEY, JSON.stringify(notes));

    document.getElementById('noteTitle').value = '';
    document.getElementById('noteContent').value = '';
    $('#addNoteModal').modal('hide');

    loadNotes();
    if (typeof toastr !== 'undefined') {
        toastr.success(getTranslation('noteSavedSuccessfully'));
    }
}

function deleteNote(index) {
    if (confirm(getTranslation('deleteNoteConfirm'))) {
        const notes = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
        notes.splice(index, 1);
        localStorage.setItem(NOTES_KEY, JSON.stringify(notes));
        loadNotes();
        if (typeof toastr !== 'undefined') {
            toastr.success(getTranslation('noteDeleted'));
        }
    }
}

// Global functions for dashboard-grocery.blade.php
window.updateEmployeeClock = updateEmployeeClock;
window.loadFaceModels = loadFaceModels;
window.loadAttendanceStatus = loadAttendanceStatus;
window.loadEmployeeStats = loadEmployeeStats;
window.loadNotes = loadNotes;
window.saveNote = saveNote;
window.deleteNote = deleteNote;
window.punchIn = punchIn;
window.punchOut = punchOut;
window.cancelFaceVerification = cancelFaceVerification;
window.retryFaceVerification = retryFaceVerification;
