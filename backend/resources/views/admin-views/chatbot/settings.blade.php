@extends('layouts.admin.app')

@section('title', translate('Chatbot Settings'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .settings-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    .settings-section {
        border-bottom: 1px solid #eee;
        padding: 20px;
    }
    .settings-section:last-child {
        border-bottom: none;
    }
    .settings-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
    }
    .ai-config-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
    }
    .stats-card h3 {
        font-size: 28px;
        margin: 0;
    }
    .stats-card p {
        margin: 5px 0 0;
        opacity: 0.9;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/icons/conversation-icon.png')}}" class="w--20" alt="">
            </span>
            <span>{{ translate('Chatbot Settings') }}</span>
        </h1>
    </div>
    <!-- End Page Header -->

    <div class="row g-3">
        <!-- Stats Cards -->
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <h3 id="total-faqs">{{ \App\Models\ChatbotFaq::count() }}</h3>
                <p>{{ translate('Total FAQs') }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h3 id="active-faqs">{{ \App\Models\ChatbotFaq::active()->count() }}</h3>
                <p>{{ translate('Active FAQs') }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);">
                <h3 id="total-hits">{{ \App\Models\ChatbotFaq::sum('hit_count') }}</h3>
                <p>{{ translate('Total Hits') }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);">
                <h3>{{ $settings['chatbot_enabled'] ?? 'false' == 'true' ? translate('ON') : translate('OFF') }}</h3>
                <p>{{ translate('Chatbot Status') }}</p>
            </div>
        </div>

        <!-- Settings Form -->
        <div class="col-12">
            <div class="card settings-card">
                <form id="chatbot-settings-form">
                    @csrf
                    <!-- General Settings -->
                    <div class="settings-section">
                        <h5 class="settings-title">{{ translate('General Settings') }}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="toggle-switch d-flex align-items-center">
                                        <input type="checkbox" name="chatbot_enabled" class="toggle-switch-input"
                                            {{ ($settings['chatbot_enabled'] ?? 'false') == 'true' ? 'checked' : '' }}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                        <span class="ml-3">{{ translate('Enable Chatbot') }}</span>
                                    </label>
                                    <small class="text-muted d-block mt-1">{{ translate('Enable or disable the chatbot feature') }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Auto Response Delay (seconds)') }}</label>
                                    <input type="number" name="auto_response_delay" class="form-control"
                                        value="{{ $settings['auto_response_delay'] ?? 2 }}" min="0" max="30">
                                    <small class="text-muted">{{ translate('Delay before sending auto response') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="settings-section">
                        <h5 class="settings-title">{{ translate('Messages') }}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Welcome Message') }}</label>
                                    <textarea name="welcome_message" class="form-control" rows="3"
                                        placeholder="{{ translate('Hello! How can I help you today?') }}">{{ $settings['welcome_message'] ?? '' }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Fallback Message') }}</label>
                                    <textarea name="fallback_message" class="form-control" rows="3"
                                        placeholder="{{ translate('Sorry, I could not understand. Please contact support.') }}">{{ $settings['fallback_message'] ?? '' }}</textarea>
                                    <small class="text-muted">{{ translate('Message when no FAQ matches') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Configuration -->
                    <div class="settings-section">
                        <h5 class="settings-title">{{ translate('AI Configuration (Optional)') }}</h5>
                        <div class="form-group">
                            <label class="toggle-switch d-flex align-items-center">
                                <input type="checkbox" name="ai_fallback_enabled" class="toggle-switch-input" id="ai-fallback-toggle"
                                    {{ ($settings['ai_fallback_enabled'] ?? 'false') == 'true' ? 'checked' : '' }}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                                <span class="ml-3">{{ translate('Enable AI Fallback') }}</span>
                            </label>
                            <small class="text-muted d-block mt-1">{{ translate('Use AI to answer questions when no FAQ match is found') }}</small>
                        </div>

                        <div class="ai-config-section" id="ai-config" style="{{ ($settings['ai_fallback_enabled'] ?? 'false') != 'true' ? 'display:none;' : '' }}">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('AI Provider') }}</label>
                                        <select name="ai_provider" class="form-control" id="ai-provider-select">
                                            <option value="openai" {{ ($settings['ai_provider'] ?? '') == 'openai' ? 'selected' : '' }}>OpenAI</option>
                                            <option value="gemini" {{ ($settings['ai_provider'] ?? '') == 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('AI Model') }}</label>
                                        <select name="ai_model" class="form-control" id="ai-model-select">
                                            <!-- OpenAI Models -->
                                            <optgroup label="OpenAI" class="openai-models">
                                                <option value="gpt-4o-mini" {{ ($settings['ai_model'] ?? '') == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini</option>
                                                <option value="gpt-4o" {{ ($settings['ai_model'] ?? '') == 'gpt-4o' ? 'selected' : '' }}>GPT-4o</option>
                                                <option value="gpt-3.5-turbo" {{ ($settings['ai_model'] ?? '') == 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                                            </optgroup>
                                            <!-- Google Gemini Models -->
                                            <optgroup label="Google Gemini" class="gemini-models">
                                                <option value="gemini-1.5-flash" {{ ($settings['ai_model'] ?? '') == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash (Fast)</option>
                                                <option value="gemini-1.5-pro" {{ ($settings['ai_model'] ?? '') == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro</option>
                                                <option value="gemini-2.0-flash" {{ ($settings['ai_model'] ?? '') == 'gemini-2.0-flash' ? 'selected' : '' }}>Gemini 2.0 Flash</option>
                                                <option value="gemini-pro" {{ ($settings['ai_model'] ?? '') == 'gemini-pro' ? 'selected' : '' }}>Gemini Pro (Legacy)</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('Confidence Threshold') }}</label>
                                        <input type="number" name="confidence_threshold" class="form-control"
                                            value="{{ $settings['confidence_threshold'] ?? 0.7 }}" min="0" max="1" step="0.1">
                                        <small class="text-muted">{{ translate('0.0 to 1.0') }}</small>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label id="api-key-label">{{ translate('API Key') }}</label>
                                        <input type="password" name="ai_api_key" class="form-control"
                                            value="{{ $settings['ai_api_key'] ?? '' }}" placeholder="{{ translate('Enter your API key') }}">
                                        <small class="text-muted" id="api-key-help">{{ translate('Your API key for AI-powered responses') }}</small>
                                        <div class="mt-2">
                                            <small class="text-info" id="api-key-link">
                                                <i class="tio-info mr-1"></i>
                                                <span id="openai-link" style="{{ ($settings['ai_provider'] ?? 'openai') == 'gemini' ? 'display:none;' : '' }}">
                                                    {{ translate('Get your OpenAI API key from') }} <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>
                                                </span>
                                                <span id="gemini-link" style="{{ ($settings['ai_provider'] ?? 'openai') != 'gemini' ? 'display:none;' : '' }}">
                                                    {{ translate('Get your Gemini API key from') }} <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>
                                                </span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="settings-section">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.chatbot.faq.index') }}" class="btn btn-info">
                                    <i class="tio-help-outlined mr-1"></i> {{ translate('Manage FAQs') }}
                                </a>
                                <a href="{{ route('admin.chatbot.learning.index') }}" class="btn btn-success">
                                    <i class="tio-chart-bar-1 mr-1"></i> {{ translate('Conversation Learning') }}
                                </a>
                            </div>
                            <div>
                                <button type="reset" class="btn btn--reset">{{ translate('Reset') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('Save Settings') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
"use strict";

$(document).ready(function() {
    // Toggle AI config visibility
    $('#ai-fallback-toggle').on('change', function() {
        if ($(this).is(':checked')) {
            $('#ai-config').slideDown();
        } else {
            $('#ai-config').slideUp();
        }
    });

    // Handle AI provider change
    $('#ai-provider-select').on('change', function() {
        let provider = $(this).val();
        let modelSelect = $('#ai-model-select');

        // Show/hide relevant model options
        if (provider === 'openai') {
            modelSelect.find('.openai-models').show();
            modelSelect.find('.gemini-models').hide();
            modelSelect.val('gpt-4o-mini');
            $('#openai-link').show();
            $('#gemini-link').hide();
        } else if (provider === 'gemini') {
            modelSelect.find('.openai-models').hide();
            modelSelect.find('.gemini-models').show();
            modelSelect.val('gemini-1.5-flash');
            $('#openai-link').hide();
            $('#gemini-link').show();
        }
    });

    // Initialize model visibility on page load
    let currentProvider = $('#ai-provider-select').val();
    if (currentProvider === 'gemini') {
        $('#ai-model-select .openai-models').hide();
    } else {
        $('#ai-model-select .gemini-models').hide();
    }

    // Form submission
    $('#chatbot-settings-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('admin.chatbot.settings.update') }}",
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message || '{{ translate("Something went wrong") }}');
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(function(error) {
                        toastr.error(error[0]);
                    });
                } else {
                    toastr.error('{{ translate("Something went wrong") }}');
                }
            }
        });
    });
});
</script>
@endpush
