// public/assets/admin/js/face-registration-utils.js

"use strict";

// Face Recognition Variables
let videoStream = null;
let faceDetectionInterval = null;
let modelsLoaded = false;

const MODEL_URL_REGISTRATION = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';

// Get config from window object (set by Blade template)
function getRegistrationConfig() {
    return window.FaceRegistrationConfig || {
        translations: {}
    };
}

function getRegTranslation(key) {
    const config = getRegistrationConfig();
    return config.translations[key] || key;
}

// Load Face API Models
async function loadFaceApiModels() {
    if (modelsLoaded) return;
    try {
        updateFaceStatus(getRegTranslation('loadingFaceDetection') + '...', 'detecting');

        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL_REGISTRATION);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL_REGISTRATION);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL_REGISTRATION);

        modelsLoaded = true;
        updateFaceStatus(getRegTranslation('faceDetectionReady'), 'success');
        setTimeout(() => {
            $('#face-status').hide();
        }, 2000);
    } catch (error) {
        console.error('Error loading face models:', error);
        updateFaceStatus(getRegTranslation('failedToLoadFaceDetection'), 'error');
    }
}

// Start Camera
function startCamera() {
    if (!modelsLoaded) {
        updateFaceStatus(getRegTranslation('pleaseWaitModelsLoading'), 'detecting');
        return;
    }

    // Stop any existing stream
    if (videoStream) {
        stopCamera();
    }

    navigator.mediaDevices.getUserMedia({
        video: { width: 640, height: 480, facingMode: 'user' }
    })
    .then(stream => {
        videoStream = stream;
        const video = document.getElementById('face-video');
        video.srcObject = videoStream;

        $('#face-placeholder').hide();
        $('#face-capture-container').show();
        $('#start-camera-btn').hide();
        $('#capture-face-btn').show();

        // Start face detection
        video.addEventListener('playing', () => {
            startFaceDetection();
        });
    })
    .catch(error => {
        console.error('Error accessing camera:', error);
        updateFaceStatus(getRegTranslation('cameraAccessDenied'), 'error');
    });
}

// Start Face Detection
function startFaceDetection() {
    const video = document.getElementById('face-video');

    if (faceDetectionInterval) {
        clearInterval(faceDetectionInterval);
    }

    faceDetectionInterval = setInterval(async () => {
        if (video.paused || video.ended) return;

        const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (detections.length === 1) {
            updateFaceStatus(getRegTranslation('faceDetectedClickCapture'), 'success');
            $('#capture-face-btn').prop('disabled', false);
        } else if (detections.length === 0) {
            updateFaceStatus(getRegTranslation('noFaceDetected'), 'detecting');
            $('#capture-face-btn').prop('disabled', true);
        } else {
            updateFaceStatus(getRegTranslation('multipleFacesDetected'), 'error');
            $('#capture-face-btn').prop('disabled', true);
        }
    }, 500);
}

// Capture Face
async function captureFace() {
    const video = document.getElementById('face-video');
    const canvas = document.getElementById('face-canvas');

    updateFaceStatus(getRegTranslation('processingFace') + '...', 'detecting');

    try {
        const detections = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detections) {
            updateFaceStatus(getRegTranslation('noFaceDetectedTryAgain'), 'error');
            return;
        }

        // Get face descriptor
        const faceDescriptor = Array.from(detections.descriptor);

        // Capture image
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        const imageDataUrl = canvas.toDataURL('image/jpeg', 0.8);

        // Store face data
        const faceData = JSON.stringify({
            descriptor: faceDescriptor,
            image: imageDataUrl,
            capturedAt: new Date().toISOString()
        });

        $('#face_data_input').val(faceData);

        console.log('Face data captured and stored in input field');
        console.log('Face data length:', faceData.length);

        // Stop camera
        stopCamera();

        // Show preview
        $('#face-capture-container').hide();
        $('#face-preview').attr('src', imageDataUrl);
        $('#face-preview-container').show();
        $('#capture-face-btn').hide();
        $('#retake-face-btn').show();

        updateFaceStatus(getRegTranslation('faceCapturedSuccessfully'), 'success');

    } catch (error) {
        console.error('Error capturing face:', error);
        updateFaceStatus(getRegTranslation('faceCaptureFailed'), 'error');
    }
}

// Retake Face
function retakeFace() {
    resetFaceCapture();
    startCamera();
}

// Stop Camera
function stopCamera() {
    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }
    if (faceDetectionInterval) {
        clearInterval(faceDetectionInterval);
        faceDetectionInterval = null;
    }
}

// Reset Face Capture
function resetFaceCapture() {
    stopCamera();
    $('#face_data_input').val('');
    $('#face-capture-container').hide();
    $('#face-preview-container').hide();
    $('#face-placeholder').show();
    $('#start-camera-btn').show();
    $('#capture-face-btn').hide();
    $('#retake-face-btn').hide();
    $('#face-status').hide();
    $('#capture-face-btn').prop('disabled', true);
}

// Update Face Status
function updateFaceStatus(message, type) {
    const statusEl = $('#face-status');
    statusEl.removeClass('detecting success error').addClass(type);
    statusEl.html('<i class="tio-' + (type === 'success' ? 'checkmark-circle' : type === 'error' ? 'clear-circle' : 'sync') + '"></i> ' + message);
    statusEl.show();
}

// Event Listeners and Initialization
$(document).on('ready', function () {
    loadFaceApiModels();

    $('#start-camera-btn').click(startCamera);
    $('#capture-face-btn').click(captureFace);
    $('#retake-face-btn').click(retakeFace);

    $('#reset_btn').click(function(){
        resetFaceCapture();
    });
});

// Cleanup on page unload
$(window).on('beforeunload', function() {
    stopCamera();
});

// Export functions globally
window.loadFaceApiModels = loadFaceApiModels;
window.startCamera = startCamera;
window.captureFace = captureFace;
window.retakeFace = retakeFace;
window.stopCamera = stopCamera;
window.resetFaceCapture = resetFaceCapture;
window.updateFaceStatus = updateFaceStatus;
