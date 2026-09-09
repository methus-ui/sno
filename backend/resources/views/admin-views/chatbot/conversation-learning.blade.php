@extends('layouts.admin.app')

@section('title', translate('Conversation Learning'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .learning-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .learning-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-3px);
    }

    .stat-card.green {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .stat-card.orange {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card.blue {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card.purple {
        background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 13px;
        opacity: 0.9;
    }

    .keyword-tag {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        background: #f0f2f5;
        border-radius: 20px;
        margin: 4px;
        font-size: 13px;
        transition: all 0.2s;
    }

    .keyword-tag:hover {
        background: #e3e5e8;
    }

    .keyword-count {
        background: #667eea;
        color: white;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 8px;
    }

    .keyword-actions {
        margin-left: 8px;
        display: flex;
        gap: 4px;
    }

    .keyword-actions button {
        width: 22px;
        height: 22px;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        transition: all 0.2s;
    }

    .keyword-actions .approve {
        background: #28a745;
        color: white;
    }

    .keyword-actions .reject {
        background: #dc3545;
        color: white;
    }

    .suggestion-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 4px solid #667eea;
    }

    .suggestion-question {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }

    .suggestion-answer {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
        padding: 10px;
        background: white;
        border-radius: 6px;
    }

    .suggestion-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #888;
    }

    .analyze-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .analyze-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .analyze-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .question-item {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
    }

    .question-item:hover {
        background: #f8f9fa;
    }

    .question-item:last-child {
        border-bottom: none;
    }

    .question-text {
        font-size: 14px;
        color: #333;
        margin-bottom: 5px;
    }

    .question-meta {
        font-size: 12px;
        color: #999;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: #667eea;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #999;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    .analysis-results {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        display: none;
    }

    .results-title {
        font-weight: 600;
        margin-bottom: 15px;
        color: #333;
    }

    .progress-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin: 10px 0;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 4px;
        transition: width 0.5s ease;
    }

    /* Q&A Pairs Styles */
    .qa-pairs-list {
        max-height: 600px;
        overflow-y: auto;
    }

    .qa-pair-item {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 12px;
        border-left: 4px solid #667eea;
        transition: all 0.2s;
    }

    .qa-pair-item:hover {
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
    }

    .qa-pair-item.selected {
        background: #e8f4ff;
        border-left-color: #28a745;
    }

    .qa-pair-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 10px;
    }

    .qa-pair-checkbox {
        margin-top: 3px;
    }

    .qa-pair-checkbox input {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .qa-pair-question {
        flex: 1;
    }

    .qa-pair-question label {
        font-weight: 600;
        color: #333;
        cursor: pointer;
        margin-bottom: 0;
    }

    .qa-pair-answer {
        background: white;
        border-radius: 8px;
        padding: 12px;
        margin-left: 30px;
        margin-top: 8px;
        border: 1px solid #e9ecef;
    }

    .qa-pair-answer p {
        margin: 0;
        color: #495057;
        line-height: 1.6;
    }

    .qa-pair-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 8px;
        margin-left: 30px;
        font-size: 12px;
        color: #888;
    }

    .qa-pair-keywords {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-left: 30px;
        margin-top: 8px;
    }

    .qa-pair-keywords .badge {
        font-size: 11px;
        font-weight: 500;
    }

    .qa-pair-edit-btn {
        padding: 4px 10px;
        font-size: 12px;
    }

    .auto-faq-result {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
    }

    .auto-faq-result .question {
        font-weight: 600;
        color: #155724;
    }

    .auto-faq-result .answer {
        color: #333;
        margin-top: 5px;
        font-size: 14px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title d-flex align-items-center gap-2">
            <img src="{{asset('public/assets/admin/img/icons/conversation-icon.png')}}" class="w--20" alt="">
            <span>{{ translate('Conversation Learning') }}</span>
        </h1>
        <p class="text-muted mt-1">{{ translate('Analyze conversations to improve chatbot responses') }}</p>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-value">{{ number_format($stats['total_conversations']) }}</div>
                <div class="stat-label">{{ translate('Conversations') }}</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stat-card green">
                <div class="stat-value">{{ number_format($stats['total_messages']) }}</div>
                <div class="stat-label">{{ translate('Messages') }}</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stat-card orange">
                <div class="stat-value">{{ number_format($stats['customer_messages']) }}</div>
                <div class="stat-label">{{ translate('Customer Messages') }}</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stat-card blue">
                <div class="stat-value">{{ number_format($stats['learned_keywords']) }}</div>
                <div class="stat-label">{{ translate('Learned Keywords') }}</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="stat-card purple">
                <div class="stat-value">{{ number_format($stats['total_faqs'] ?? 0) }}</div>
                <div class="stat-label">{{ translate('Total FAQs') }}</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="d-flex flex-column align-items-center justify-content-center h-100 gap-2">
                <button class="analyze-btn w-100" id="analyze-btn" onclick="runAnalysis()">
                    <i class="tio-sync mr-1"></i>{{ translate('Analyze') }}
                </button>
                <button class="analyze-btn w-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);" onclick="autoGenerateFaqs()">
                    <i class="tio-magic-wand mr-1"></i>{{ translate('Auto Generate FAQs') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Extract Q&A Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="tio-message-add text-primary mr-2"></i>
                        {{ translate('Extract FAQs from Conversations') }}
                    </h5>
                    <button class="btn btn-primary" onclick="extractQAPairs()">
                        <i class="tio-download-from-cloud mr-1"></i>{{ translate('Extract Q&A Pairs') }}
                    </button>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label>{{ translate('Days Back') }}</label>
                            <input type="number" id="days-back" class="form-control" value="90" min="7" max="365">
                        </div>
                        <div class="col-md-3">
                            <label>{{ translate('Min Response Length') }}</label>
                            <input type="number" id="min-response-length" class="form-control" value="20" min="10" max="100">
                        </div>
                        <div class="col-md-3">
                            <label>{{ translate('Max Conversations') }}</label>
                            <input type="number" id="max-conversations" class="form-control" value="500" min="50" max="2000">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-success w-100" id="bulk-create-btn" onclick="bulkCreateSelectedFaqs()" style="display:none;">
                                <i class="tio-checkmark-circle mr-1"></i>{{ translate('Create Selected FAQs') }}
                            </button>
                        </div>
                    </div>

                    <!-- Extracted Q&A Pairs Container -->
                    <div id="qa-pairs-container" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="badge badge-primary" id="qa-count">0</span> {{ translate('Q&A pairs found') }}
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" onclick="selectAllPairs()">{{ translate('Select All') }}</button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="deselectAllPairs()">{{ translate('Deselect All') }}</button>
                            </div>
                        </div>
                        <div id="qa-pairs-list" class="qa-pairs-list"></div>
                    </div>

                    <!-- Loading State -->
                    <div id="qa-loading" style="display:none;" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">{{ translate('Extracting Q&A pairs from conversations...') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Results -->
    <div class="analysis-results" id="analysis-results">
        <div class="results-title">{{ translate('Analysis Results') }}</div>
        <div id="results-content"></div>
    </div>

    <div class="row g-3">
        <!-- Learned Keywords -->
        <div class="col-lg-4">
            <div class="card learning-card h-100">
                <div class="card-header">
                    <h5 class="section-title mb-0">
                        <i class="tio-label-important"></i>
                        {{ translate('Learned Keywords') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($learnedKeywords->count() > 0)
                        <div class="keywords-container">
                            @foreach($learnedKeywords as $keyword)
                                <div class="keyword-tag" id="keyword-{{ $keyword->id }}">
                                    {{ $keyword->keyword }}
                                    <span class="keyword-count">{{ $keyword->frequency }}</span>
                                    @if($keyword->status == 'pending')
                                    <div class="keyword-actions">
                                        <button class="approve" onclick="approveKeyword({{ $keyword->id }})" title="{{ translate('Approve') }}">
                                            <i class="tio-done"></i>
                                        </button>
                                        <button class="reject" onclick="rejectKeyword({{ $keyword->id }})" title="{{ translate('Reject') }}">
                                            <i class="tio-clear"></i>
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="tio-documents"></i>
                            <p>{{ translate('No keywords learned yet') }}</p>
                            <small>{{ translate('Run analysis to extract keywords') }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Suggested FAQs -->
        <div class="col-lg-4">
            <div class="card learning-card h-100">
                <div class="card-header">
                    <h5 class="section-title mb-0">
                        <i class="tio-help-outlined"></i>
                        {{ translate('Suggested FAQs') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($suggestedFaqs) > 0)
                        @foreach($suggestedFaqs as $suggestion)
                            <div class="suggestion-card">
                                <div class="suggestion-question">{{ Str::limit($suggestion['question'], 100) }}</div>
                                @if($suggestion['suggested_answer'])
                                    <div class="suggestion-answer">{{ Str::limit($suggestion['suggested_answer'], 150) }}</div>
                                @else
                                    <div class="suggestion-answer text-muted">{{ translate('No suggested answer found') }}</div>
                                @endif
                                <div class="suggestion-meta">
                                    <span><i class="tio-trending-up mr-1"></i>{{ translate('Asked') }} {{ $suggestion['frequency'] }}x</span>
                                    <button class="btn btn-sm btn-primary" onclick="createFaq('{{ addslashes($suggestion['question']) }}', '{{ addslashes($suggestion['suggested_answer'] ?? '') }}', '{{ $suggestion['keywords'] }}')">
                                        <i class="tio-add mr-1"></i>{{ translate('Create FAQ') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="tio-help-outlined"></i>
                            <p>{{ translate('No suggestions available') }}</p>
                            <small>{{ translate('More conversations needed') }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Questions -->
        <div class="col-lg-4">
            <div class="card learning-card h-100">
                <div class="card-header">
                    <h5 class="section-title mb-0">
                        <i class="tio-chat"></i>
                        {{ translate('Recent Customer Questions') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if(count($unansweredQuestions) > 0)
                        @foreach($unansweredQuestions as $question)
                            <div class="question-item">
                                <div class="question-text">{{ Str::limit($question['message'], 120) }}</div>
                                <div class="question-meta">
                                    <i class="tio-time mr-1"></i>{{ $question['created_at']->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="tio-chat"></i>
                            <p>{{ translate('No recent questions') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recently Created FAQs -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card learning-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="section-title mb-0">
                        <i class="tio-checkmark-circle text-success"></i>
                        {{ translate('Recently Created FAQs') }}
                    </h5>
                    <a href="{{ route('admin.chatbot.faq.index') }}" class="btn btn-sm btn-outline-primary">
                        {{ translate('View All FAQs') }} <i class="tio-arrow-forward ml-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    @if(isset($recentFaqs) && $recentFaqs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ translate('Question') }}</th>
                                        <th>{{ translate('Answer') }}</th>
                                        <th>{{ translate('Keywords') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Created') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentFaqs as $faq)
                                        <tr>
                                            <td>
                                                <span class="font-weight-semibold">{{ Str::limit($faq->question, 50) }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ Str::limit($faq->answer, 60) }}</span>
                                            </td>
                                            <td>
                                                @if($faq->keywords)
                                                    @foreach(explode(',', $faq->keywords) as $keyword)
                                                        <span class="badge badge-soft-info mr-1">{{ trim($keyword) }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($faq->is_active)
                                                    <span class="badge badge-success">{{ translate('Active') }}</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ translate('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $faq->created_at->diffForHumans() }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="tio-document-text" style="font-size: 48px; opacity: 0.3;"></i>
                            <p class="mt-2">{{ translate('No FAQs created yet') }}</p>
                            <p class="small">{{ translate('Use the extraction tools above to create FAQs from your conversations') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create FAQ Modal -->
<div class="modal fade" id="createFaqModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Create FAQ from Suggestion') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="createFaqForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ translate('Question') }}</label>
                        <input type="text" name="question" id="faq-question" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Answer') }}</label>
                        <textarea name="answer" id="faq-answer" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Keywords') }} <small class="text-muted">({{ translate('comma separated') }})</small></label>
                        <input type="text" name="keywords" id="faq-keywords" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Create FAQ') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
"use strict";

function runAnalysis() {
    let btn = document.getElementById('analyze-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="tio-sync tio-spin mr-2"></i>{{ translate("Analyzing...") }}';

    $.ajax({
        url: "{{ route('admin.chatbot.learning.analyze') }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            limit: 500
        },
        success: function(response) {
            if (response.success) {
                $('#analysis-results').show();
                let html = `
                    <div class="row">
                        <div class="col-md-3"><strong>{{ translate('Messages Analyzed') }}:</strong> ${response.stats.messages_analyzed}</div>
                        <div class="col-md-3"><strong>{{ translate('Unique Keywords') }}:</strong> ${response.stats.unique_keywords}</div>
                        <div class="col-md-3"><strong>{{ translate('Keywords Saved') }}:</strong> ${response.stats.keywords_saved}</div>
                        <div class="col-md-3"><strong>{{ translate('Question Patterns') }}:</strong> ${response.stats.question_patterns}</div>
                    </div>
                `;

                if (response.top_keywords) {
                    html += '<div class="mt-3"><strong>{{ translate("Top Keywords") }}:</strong><br>';
                    for (let keyword in response.top_keywords) {
                        html += `<span class="keyword-tag">${keyword} <span class="keyword-count">${response.top_keywords[keyword]}</span></span>`;
                    }
                    html += '</div>';
                }

                $('#results-content').html(html);
                toastr.success(response.message);

                // Reload page after 2 seconds to show new data
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        },
        error: function() {
            toastr.error('{{ translate("Analysis failed") }}');
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="tio-sync mr-2"></i>{{ translate("Analyze Now") }}';
        }
    });
}

function approveKeyword(id) {
    $.ajax({
        url: "/admin/chatbot/learning/keyword/" + id + "/approve",
        method: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            if (response.success) {
                $('#keyword-' + id).find('.keyword-actions').html('<span class="badge badge-success">{{ translate("Approved") }}</span>');
                toastr.success(response.message);
            }
        }
    });
}

function rejectKeyword(id) {
    $.ajax({
        url: "/admin/chatbot/learning/keyword/" + id + "/reject",
        method: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            if (response.success) {
                $('#keyword-' + id).fadeOut();
                toastr.success(response.message);
            }
        }
    });
}

function createFaq(question, answer, keywords) {
    $('#faq-question').val(question);
    $('#faq-answer').val(answer || '');
    $('#faq-keywords').val(keywords || '');
    $('#createFaqModal').modal('show');
}

$('#createFaqForm').on('submit', function(e) {
    e.preventDefault();

    $.ajax({
        url: "{{ route('admin.chatbot.learning.create-faq') }}",
        method: 'POST',
        data: $(this).serialize() + '&_token=' + $('meta[name="csrf-token"]').attr('content'),
        success: function(response) {
            if (response.success) {
                $('#createFaqModal').modal('hide');
                toastr.success(response.message);
                location.reload();
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                Object.values(xhr.responseJSON.errors).forEach(function(error) {
                    toastr.error(error[0]);
                });
            } else {
                toastr.error('{{ translate("Error creating FAQ") }}');
            }
        }
    });
});

// Store extracted Q&A pairs
let extractedPairs = [];

// Extract Q&A Pairs from conversations
function extractQAPairs() {
    $('#qa-loading').show();
    $('#qa-pairs-container').hide();
    $('#bulk-create-btn').hide();

    $.ajax({
        url: "{{ route('admin.chatbot.learning.extract-qa') }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            days_back: $('#days-back').val() || 90,
            min_response_length: $('#min-response-length').val() || 20,
            limit: $('#max-conversations').val() || 500
        },
        success: function(response) {
            $('#qa-loading').hide();

            if (response.success && response.qa_pairs && response.qa_pairs.length > 0) {
                extractedPairs = response.qa_pairs;
                renderQAPairs(response.qa_pairs);
                $('#qa-pairs-container').show();
                $('#bulk-create-btn').show();
                $('#qa-count').text(response.total_pairs_found);
                toastr.success('{{ translate("Found") }} ' + response.total_pairs_found + ' {{ translate("Q&A pairs") }}');
            } else {
                toastr.info('{{ translate("No Q&A pairs found. Try adjusting the filters or check if you have conversations with questions and responses.") }}');
            }
        },
        error: function(xhr) {
            $('#qa-loading').hide();
            let errorMsg = '{{ translate("Error extracting Q&A pairs") }}';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            toastr.error(errorMsg);
            console.error('Extract error:', xhr);
        }
    });
}

// Render Q&A pairs in the list
function renderQAPairs(pairs) {
    let html = '';

    pairs.forEach(function(pair, index) {
        let keywords = pair.keywords ? pair.keywords.split(',').map(k => `<span class="badge badge-soft-info">${k.trim()}</span>`).join('') : '';

        html += `
            <div class="qa-pair-item" data-index="${index}">
                <div class="qa-pair-header">
                    <div class="qa-pair-checkbox">
                        <input type="checkbox" id="pair-${index}" class="qa-checkbox" checked onchange="togglePairSelection(${index})">
                    </div>
                    <div class="qa-pair-question">
                        <label for="pair-${index}">${escapeHtml(pair.question)}</label>
                    </div>
                    <button class="btn btn-sm btn-outline-primary qa-pair-edit-btn" onclick="editPair(${index})">
                        <i class="tio-edit"></i>
                    </button>
                </div>
                <div class="qa-pair-answer">
                    <p id="answer-${index}">${escapeHtml(pair.answer)}</p>
                </div>
                <div class="qa-pair-keywords" id="keywords-${index}">
                    ${keywords}
                </div>
                <div class="qa-pair-meta">
                    <span><i class="tio-time mr-1"></i>${pair.created_at}</span>
                    <span><i class="tio-message mr-1"></i>Conv #${pair.conversation_id}</span>
                </div>
            </div>
        `;
    });

    $('#qa-pairs-list').html(html);
}

function escapeHtml(text) {
    if (!text) return '';
    let div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function togglePairSelection(index) {
    let item = $(`.qa-pair-item[data-index="${index}"]`);
    let checkbox = $(`#pair-${index}`);

    if (checkbox.is(':checked')) {
        item.addClass('selected');
    } else {
        item.removeClass('selected');
    }
}

function selectAllPairs() {
    $('.qa-checkbox').prop('checked', true);
    $('.qa-pair-item').addClass('selected');
}

function deselectAllPairs() {
    $('.qa-checkbox').prop('checked', false);
    $('.qa-pair-item').removeClass('selected');
}

function editPair(index) {
    let pair = extractedPairs[index];
    let newQuestion = prompt('{{ translate("Edit Question") }}:', pair.question);
    if (newQuestion) {
        extractedPairs[index].question = newQuestion;
        $(`.qa-pair-item[data-index="${index}"] .qa-pair-question label`).text(newQuestion);
    }

    let newAnswer = prompt('{{ translate("Edit Answer") }}:', pair.answer);
    if (newAnswer) {
        extractedPairs[index].answer = newAnswer;
        $(`#answer-${index}`).text(newAnswer);
    }
}

// Bulk create FAQs from selected pairs
function bulkCreateSelectedFaqs() {
    let selectedPairs = [];

    $('.qa-checkbox:checked').each(function() {
        let index = $(this).attr('id').replace('pair-', '');
        selectedPairs.push(extractedPairs[index]);
    });

    if (selectedPairs.length === 0) {
        toastr.warning('{{ translate("Please select at least one Q&A pair") }}');
        return;
    }

    if (!confirm('{{ translate("Create") }} ' + selectedPairs.length + ' {{ translate("FAQs from selected pairs?") }}')) {
        return;
    }

    $('#bulk-create-btn').prop('disabled', true).html('<i class="tio-sync tio-spin mr-1"></i>{{ translate("Creating...") }}');

    $.ajax({
        url: "{{ route('admin.chatbot.learning.bulk-create-faqs') }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            pairs: selectedPairs
        },
        success: function(response) {
            if (response.success) {
                toastr.success('{{ translate("Created") }} ' + response.created + ' {{ translate("FAQs") }}' +
                    (response.skipped > 0 ? ', {{ translate("skipped") }} ' + response.skipped + ' {{ translate("duplicates") }}' : ''));

                // Remove created items from list
                $('.qa-checkbox:checked').closest('.qa-pair-item').fadeOut(300, function() {
                    $(this).remove();
                });

                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        },
        error: function() {
            toastr.error('{{ translate("Error creating FAQs") }}');
        },
        complete: function() {
            $('#bulk-create-btn').prop('disabled', false).html('<i class="tio-checkmark-circle mr-1"></i>{{ translate("Create Selected FAQs") }}');
        }
    });
}

// Auto-generate FAQs (one-click)
function autoGenerateFaqs() {
    if (!confirm('{{ translate("This will automatically create FAQs from frequently asked questions in your conversations. Continue?") }}')) {
        return;
    }

    let btn = event.target.closest('button') || event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="tio-sync tio-spin mr-1"></i>{{ translate("Generating...") }}';

    $.ajax({
        url: "{{ route('admin.chatbot.learning.auto-generate-faqs') }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            max_faqs: 20,
            min_frequency: 1
        },
        success: function(response) {
            if (response.success) {
                if (response.created > 0) {
                    toastr.success('{{ translate("Created") }} ' + response.created + ' {{ translate("FAQs automatically") }}');

                    // Show the created FAQs
                    if (response.faqs && response.faqs.length > 0) {
                        let html = '<div class="mt-4"><h6>{{ translate("Auto-Generated FAQs") }}:</h6>';
                        response.faqs.forEach(function(faq) {
                            html += `
                                <div class="auto-faq-result">
                                    <div class="question"><i class="tio-help-outlined mr-1"></i>${escapeHtml(faq.question)}</div>
                                    <div class="answer">${escapeHtml(faq.answer)}</div>
                                    <small class="text-muted">{{ translate("Asked") }} ${faq.frequency}x</small>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $('#analysis-results').html(html).show();
                    }

                    // Reload after 3 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    toastr.info('{{ translate("No new FAQs could be generated. Either no suitable Q&A pairs were found or all questions are already in FAQs.") }}');
                }
            } else {
                toastr.warning(response.message || '{{ translate("No suitable Q&A pairs found for auto-generation") }}');
            }
        },
        error: function(xhr) {
            let errorMsg = '{{ translate("Error auto-generating FAQs") }}';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            toastr.error(errorMsg);
            console.error('Auto-generate error:', xhr);
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="tio-magic-wand mr-1"></i>{{ translate("Auto Generate FAQs") }}';
        }
    });
}
</script>
@endpush
