"use strict";

/**
 * Modern Vendor Registration JavaScript
 * Kashmir-Tailored UI with Enhanced UX
 * Handles form interactions, validations, animations, and accessibility
 */

// ============================================
// INITIALIZATION
// ============================================
$(document).ready(function() {
    // Initialize Select2 for dropdowns
    $('.js-example-basic-single').select2({
        minimumResultsForSearch: 10,
        placeholder: function() {
            return $(this).data('placeholder');
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize password strength indicator
    initPasswordStrength();

    // Initialize staggered form animations
    initFormAnimations();

    // Initialize mobile navigation
    initMobileNavigation();

    // Initialize slider dot navigation
    initSliderDots();

    // Initialize accessibility features
    initAccessibility();
});

// ============================================
// ZONE & MODULE TRACKING
// ============================================
let zone_id = 0;
$('#choice_zones').on('change', function() {
    if ($(this).val()) {
        zone_id = $(this).val();
    }
});

// ============================================
// GEOLOCATION & MAP
// ============================================
function handleLocationError(browserHasGeolocation, infoWindow, pos) {
    infoWindow.setPosition(pos);
    infoWindow.setContent(
        browserHasGeolocation
            ? "Error: The Geolocation service failed."
            : "Error: Your browser doesn't support geolocation."
    );
    infoWindow.open(map);
}

function initMap() {
    if (typeof google === 'undefined' || typeof map === 'undefined') {
        return;
    }

    // Open initial info window
    if (typeof infoWindow !== 'undefined') {
        infoWindow.open(map);
    }

    // Create new info window
    infoWindow = new google.maps.InfoWindow();

    // Try HTML5 geolocation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                myLatlng = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                infoWindow.setPosition(myLatlng);
                infoWindow.setContent("Location found.");
                infoWindow.open(map);
                map.setCenter(myLatlng);
            },
            () => {
                handleLocationError(true, infoWindow, map.getCenter());
            }
        );
    } else {
        handleLocationError(false, infoWindow, map.getCenter());
    }

    // Setup search box
    const input = document.getElementById("pac-input");
    if (!input) return;

    const searchBox = new google.maps.places.SearchBox(input);
    map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);

    let markers = [];

    searchBox.addListener("places_changed", () => {
        const places = searchBox.getPlaces();

        if (places.length === 0) {
            return;
        }

        // Clear old markers
        markers.forEach((marker) => {
            marker.setMap(null);
        });
        markers = [];

        // For each place, get the icon, name and location
        const bounds = new google.maps.LatLngBounds();
        places.forEach((place) => {
            if (!place.geometry || !place.geometry.location) {
                console.log("Returned place contains no geometry");
                return;
            }

            const icon = {
                url: place.icon,
                size: new google.maps.Size(71, 71),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(17, 34),
                scaledSize: new google.maps.Size(25, 25),
            };

            // Create a marker for each place
            markers.push(
                new google.maps.Marker({
                    map,
                    icon,
                    title: place.name,
                    position: place.geometry.location,
                })
            );

            if (place.geometry.viewport) {
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
        });
        map.fitBounds(bounds);
    });
}

// ============================================
// IMAGE UPLOAD HANDLING
// ============================================
function readURL(input, viewer) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            $('#' + viewer).attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Image upload handlers (legacy support)
$("#customFileEg1").change(function() {
    readURL(this, 'logoImageViewer');
});

$("#coverImageUpload").change(function() {
    readURL(this, 'coverImageViewer');
});

// ============================================
// LANGUAGE TAB SWITCHING
// ============================================
$(".lang_link").click(function(e) {
    e.preventDefault();
    $(".lang_link").removeClass('active');
    $(".lang_form").addClass('d-none');
    $(this).addClass('active');

    let form_id = this.id;
    let lang = form_id.substring(0, form_id.length - 5);
    $("#" + lang + "-form").removeClass('d-none');
});

// ============================================
// PASSWORD STRENGTH INDICATOR
// ============================================
function initPasswordStrength() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    if (!passwordInput) return;

    passwordInput.addEventListener('input', function(e) {
        const password = e.target.value;
        updatePasswordStrengthUI(password);
        checkPasswordMatch();
    });

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
}

function updatePasswordStrengthUI(password) {
    const strengthFill = document.getElementById('passwordStrengthFill');
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password)
    };

    // Update checklist items
    Object.keys(requirements).forEach(req => {
        const item = document.querySelector(`[data-requirement="${req}"]`);
        if (item) {
            if (requirements[req]) {
                item.classList.add('valid');
            } else {
                item.classList.remove('valid');
            }
        }
    });

    // Calculate strength
    const validCount = Object.values(requirements).filter(Boolean).length;
    let strengthClass = '';

    if (validCount === 1) strengthClass = 'weak';
    else if (validCount === 2) strengthClass = 'fair';
    else if (validCount === 3) strengthClass = 'good';
    else if (validCount === 4) strengthClass = 'strong';

    // Update strength bar
    if (strengthFill) {
        strengthFill.className = 'password-strength-fill';
        if (strengthClass) {
            strengthFill.classList.add(strengthClass);
        }
    }

    return validCount >= 3; // Return true if password is acceptable
}

function checkPasswordMatch() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const indicator = document.getElementById('password-match-status');
    const matchIcon = document.getElementById('matchIcon');
    const matchText = document.getElementById('matchText');

    if (!password || !confirmPassword || !indicator) return;

    const passVal = password.value;
    const confirmVal = confirmPassword.value;

    if (confirmVal.length > 0) {
        indicator.classList.add('show');

        if (passVal === confirmVal) {
            indicator.classList.remove('no-match');
            indicator.classList.add('match');
            if (matchIcon) matchIcon.className = 'fas fa-check-circle';
            if (matchText) matchText.textContent = 'Passwords match';
            confirmPassword.style.borderColor = 'var(--success-clr)';
            $('.pass').hide();
        } else {
            indicator.classList.remove('match');
            indicator.classList.add('no-match');
            if (matchIcon) matchIcon.className = 'fas fa-times-circle';
            if (matchText) matchText.textContent = 'Passwords do not match';
            confirmPassword.style.borderColor = 'var(--danger-clr)';
            $('.pass').show();
        }
    } else {
        indicator.classList.remove('show');
        confirmPassword.style.borderColor = '';
        $('.pass').hide();
    }
}

// Legacy password validation support
$('#exampleInputPassword, #exampleRepeatPassword').on('keyup', function() {
    let pass = $("#exampleInputPassword").val();
    let passRepeat = $("#exampleRepeatPassword").val();

    if (passRepeat.length > 0) {
        if (pass === passRepeat) {
            $('.pass').hide();
        } else {
            $('.pass').show();
        }
    } else {
        $('.pass').hide();
    }
});

// ============================================
// PASSWORD VISIBILITY TOGGLE
// ============================================
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    if (button) {
        button.classList.toggle('active', isPassword);
    }
}

// ============================================
// MODERN IMAGE UPLOAD WITH PREVIEW
// ============================================
function setupImageUpload(inputId, previewId, zoneId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const zone = document.getElementById(zoneId);

    if (!input || !preview || !zone) return;

    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                zone.classList.add('has-image');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Drag and drop visual feedback
    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    zone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    zone.addEventListener('drop', function(e) {
        this.classList.remove('dragover');
    });
}

// Initialize image uploads when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setupImageUpload('coverImageUpload', 'coverImageViewer', 'coverUploadZone');
    setupImageUpload('customFileEg1', 'logoImageViewer', 'logoUploadZone');
});

// ============================================
// FORM VALIDATION
// ============================================
$('#form-id').on('submit', function(e) {
    let pass = $("#exampleInputPassword").val();
    let passRepeat = $("#exampleRepeatPassword").val();

    if (pass !== passRepeat) {
        e.preventDefault();
        if (typeof toastr !== 'undefined') {
            toastr.error('Passwords do not match!');
        }
        return false;
    }

    return true;
});

// ============================================
// REAL-TIME FIELD VALIDATION
// ============================================
function initRealTimeValidation() {
    // Email validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            validateEmail(this);
        });
    }

    // Phone validation
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('blur', function() {
            validatePhone(this);
        });
    }
}

function validateEmail(input) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isValid = emailRegex.test(input.value);

    if (input.value.length > 0) {
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            input.style.borderColor = 'var(--success-clr)';
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            input.style.borderColor = 'var(--danger-clr)';
        }
    } else {
        input.classList.remove('is-valid', 'is-invalid');
        input.style.borderColor = '';
    }

    return isValid;
}

function validatePhone(input) {
    const phoneRegex = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
    const cleanPhone = input.value.replace(/\D/g, '');
    const isValid = cleanPhone.length >= 10;

    if (input.value.length > 0) {
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            input.style.borderColor = 'var(--success-clr)';
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            input.style.borderColor = 'var(--danger-clr)';
        }
    } else {
        input.classList.remove('is-valid', 'is-invalid');
        input.style.borderColor = '';
    }

    return isValid;
}

// ============================================
// STAGGERED FORM ANIMATIONS
// ============================================
function initFormAnimations() {
    // Add staggered animation to form groups on step change
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const target = mutation.target;
                if (target.classList.contains('form-step') && target.classList.contains('active')) {
                    animateFormGroups(target);
                }
            }
        });
    });

    document.querySelectorAll('.form-step').forEach(function(step) {
        observer.observe(step, { attributes: true });
    });
}

function animateFormGroups(stepElement) {
    const formGroups = stepElement.querySelectorAll('.form-group');
    formGroups.forEach(function(group, index) {
        group.style.opacity = '0';
        group.style.transform = 'translateY(15px)';

        setTimeout(function() {
            group.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            group.style.opacity = '1';
            group.style.transform = 'translateY(0)';
        }, index * 50); // 50ms stagger delay
    });
}

// ============================================
// MOBILE STICKY NAVIGATION
// ============================================
function initMobileNavigation() {
    if (window.innerWidth > 768) return;

    const mobileNav = document.createElement('div');
    mobileNav.className = 'mobile-sticky-nav';
    mobileNav.id = 'mobileNav';
    mobileNav.innerHTML = `
        <button type="button" class="btn btn-secondary" id="mobileBackBtn" onclick="prevStep()">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <button type="button" class="btn btn-primary" id="mobileNextBtn" onclick="nextStep()">
            Next <i class="fas fa-arrow-right"></i>
        </button>
    `;
    document.body.appendChild(mobileNav);
    document.body.classList.add('has-mobile-nav');

    // Update mobile nav visibility based on current step
    updateMobileNav();
}

function updateMobileNav() {
    const mobileNav = document.getElementById('mobileNav');
    const mobileBackBtn = document.getElementById('mobileBackBtn');
    const mobileNextBtn = document.getElementById('mobileNextBtn');

    if (!mobileNav || window.innerWidth > 768) return;

    // Get current step from the page context
    const currentStep = typeof window.currentStep !== 'undefined' ? window.currentStep : 1;
    const totalSteps = typeof window.totalSteps !== 'undefined' ? window.totalSteps : 8;

    mobileNav.classList.add('show');

    if (mobileBackBtn) {
        mobileBackBtn.style.display = currentStep > 1 ? 'block' : 'none';
    }

    if (mobileNextBtn) {
        if (currentStep === totalSteps) {
            mobileNextBtn.innerHTML = '<i class="fas fa-check"></i> Submit';
            mobileNextBtn.onclick = function() {
                document.getElementById('registrationForm').submit();
            };
        } else {
            mobileNextBtn.innerHTML = 'Next <i class="fas fa-arrow-right"></i>';
            mobileNextBtn.onclick = function() {
                if (typeof nextStep === 'function') nextStep();
            };
        }
    }
}

// ============================================
// SLIDER DOT NAVIGATION
// ============================================
function initSliderDots() {
    const dots = document.querySelectorAll('.slider-dot');

    dots.forEach(function(dot, index) {
        dot.addEventListener('click', function() {
            goToSlide(index);
        });
    });
}

function goToSlide(index) {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dot');

    slides.forEach(function(slide, i) {
        slide.classList.remove('active');
        slide.setAttribute('aria-hidden', 'true');
        if (i === index) {
            slide.classList.add('active');
            slide.setAttribute('aria-hidden', 'false');
        }
    });

    dots.forEach(function(dot, i) {
        dot.classList.remove('active');
        dot.setAttribute('aria-selected', 'false');
        if (i === index) {
            dot.classList.add('active');
            dot.setAttribute('aria-selected', 'true');
        }
    });
}

// ============================================
// ACCESSIBILITY FEATURES
// ============================================
function initAccessibility() {
    // Screen reader announcements
    const announceElement = document.getElementById('sr-announcements');
    if (!announceElement) {
        const srAnnounce = document.createElement('div');
        srAnnounce.id = 'sr-announcements';
        srAnnounce.className = 'sr-only';
        srAnnounce.setAttribute('aria-live', 'polite');
        srAnnounce.setAttribute('aria-atomic', 'true');
        document.body.appendChild(srAnnounce);
    }

    // Update ARIA attributes on step change
    window.announceStepChange = function(step, totalSteps) {
        const announcer = document.getElementById('sr-announcements');
        if (announcer) {
            announcer.textContent = `Step ${step} of ${totalSteps}`;
        }

        // Update progress steps ARIA
        const progressSteps = document.querySelectorAll('.progress-step');
        progressSteps.forEach(function(stepEl, index) {
            const isActive = index < step;
            const isCurrent = index === step - 1;

            stepEl.setAttribute('aria-selected', isCurrent ? 'true' : 'false');

            if (isActive && !isCurrent) {
                stepEl.setAttribute('aria-label', stepEl.getAttribute('aria-label').replace('Step', 'Completed: Step'));
            }
        });
    };
}

// ============================================
// SCROLL TO ERROR
// ============================================
function scrollToError() {
    const firstError = document.querySelector('.is-invalid, .form-control-modern.is-invalid, .form-group.error');
    if (firstError) {
        firstError.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        // Focus the input
        const input = firstError.querySelector('input, select, textarea') || firstError;
        if (input && input.focus) {
            setTimeout(function() {
                input.focus();
            }, 500);
        }
    }
}

// ============================================
// INPUT ANIMATION CLASSES
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-control-modern, .form-group input, .form-group select, .form-group textarea');

    inputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            const parent = this.closest('.form-group') || this.parentElement;
            if (parent) parent.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            const parent = this.closest('.form-group') || this.parentElement;
            if (parent) parent.classList.remove('focused');

            if (this.value) {
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
        });
    });

    // Initialize real-time validation
    initRealTimeValidation();
});

// ============================================
// STEP TRANSITION ANIMATIONS
// ============================================
function animateStepTransition(direction, currentStepEl, nextStepEl, callback) {
    // Determine animation classes based on direction
    const exitClass = direction === 'forward' ? 'step-exit-left' : 'step-exit-right';
    const enterClass = direction === 'forward' ? 'step-enter-right' : 'step-enter-left';

    // Animate current step out
    if (currentStepEl) {
        currentStepEl.classList.add(exitClass);

        setTimeout(function() {
            currentStepEl.classList.remove('active', exitClass);
        }, 300);
    }

    // Animate next step in
    setTimeout(function() {
        if (nextStepEl) {
            nextStepEl.classList.add('active', enterClass);

            setTimeout(function() {
                nextStepEl.classList.remove(enterClass);

                // Animate form groups inside
                animateFormGroups(nextStepEl);

                if (callback) callback();
            }, 300);
        }
    }, 150);
}

// ============================================
// BUTTON LOADING STATE
// ============================================
function setButtonLoading(button, loading) {
    if (!button) return;

    if (loading) {
        button.classList.add('loading');
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    } else {
        button.classList.remove('loading');
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
        }
    }
}

// ============================================
// PROGRESS GLOW EFFECT
// ============================================
function addProgressGlow(stepElement) {
    stepElement.classList.add('glow');
    setTimeout(function() {
        stepElement.classList.remove('glow');
    }, 1000);
}

// ============================================
// RESIZE HANDLER
// ============================================
window.addEventListener('resize', function() {
    const mobileNav = document.getElementById('mobileNav');

    if (window.innerWidth <= 768) {
        if (!mobileNav) {
            initMobileNavigation();
        }
    } else {
        if (mobileNav) {
            mobileNav.remove();
            document.body.classList.remove('has-mobile-nav');
        }
    }
});

// ============================================
// EXPORT FUNCTIONS FOR GLOBAL ACCESS
// ============================================
window.togglePassword = togglePassword;
window.scrollToError = scrollToError;
window.animateStepTransition = animateStepTransition;
window.updateMobileNav = updateMobileNav;
window.setButtonLoading = setButtonLoading;
window.addProgressGlow = addProgressGlow;
window.validateEmail = validateEmail;
window.validatePhone = validatePhone;
window.goToSlide = goToSlide;
