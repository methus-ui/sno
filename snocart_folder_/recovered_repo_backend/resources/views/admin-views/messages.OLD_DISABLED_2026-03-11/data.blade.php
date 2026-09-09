@foreach($conversations as $conv)
    @php($user = $conv->sender_type == 'admin' ? $conv->receiver : $conv->sender)
    @if ($user)
        @php($unchecked = ($conv->last_message?->sender_id == $user->id) ? $conv->unread_message_count : 0)
        <div class="conversation-item-modern view-admin-conv {{ $unchecked ? 'has-unread' : '' }}"
            id="conv-{{ $conv->id }}"
            data-url="{{ route('admin.message.view', ['conversation_id' => $conv->id, 'user_id' => $user->id]) }}"
            data-conv-id="{{ $conv->id }}"
            data-sender-id="{{ $user->id }}"
            onclick="openConversation({{ $conv->id }}, {{ $user->id }})"
            role="button"
            tabindex="0">

            <div class="conversation-content">
                <div class="conversation-avatar-wrapper">
                    <img class="conversation-avatar-modern onerror-image"
                        src="{{ $user['image_full_url'] }}"
                        data-onerror-image="{{ asset('public/assets/admin') }}/img/160x160/img1.jpg"
                        alt="{{ $user['f_name'] }}">
                    @if($unchecked)
                        <span class="avatar-badge-pulse"></span>
                    @endif
                </div>

                <div class="conversation-details">
                    <div class="conversation-header">
                        <h6 class="conversation-name">
                            {{ $user['f_name'] . ' ' . $user['l_name'] }}
                        </h6>
                        <span class="conversation-time">
                            {{ $conv->last_message ? \Carbon\Carbon::parse($conv->last_message->created_at)->diffForHumans(null, true, true) : '' }}
                        </span>
                    </div>

                    <div class="conversation-preview">
                        <div class="message-preview">
                            @if($conv->last_message)
                                @if($conv->last_message->sender_id != $user->id)
                                    <i class="tio-checkmark-circle-outlined text-primary mr-1"></i>
                                @endif
                                <span class="preview-text">
                                    {{ Str::limit($conv->last_message->message ?? translate('📎 Attachment'), 35, '...') }}
                                </span>
                            @else
                                <span class="preview-text text-muted">{{ translate('No messages yet') }}</span>
                            @endif
                        </div>

                        <div class="conversation-meta">
                            @if($unchecked)
                                <span class="unread-count-badge">{{ $unchecked }}</span>
                            @endif
                            @if($conv->assignedAdmin)
                                <img src="{{ $conv->assignedAdmin->image
                                            ? asset('storage/admins/'.$conv->assignedAdmin->image)
                                            : asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                    alt="{{ $conv->assignedAdmin->f_name }}"
                                    class="assigned-admin-avatar"
                                    title="{{ translate('Assigned to') }} {{ $conv->assignedAdmin->f_name }}">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="conversation-item-modern disabled">
            <div class="conversation-content">
                <div class="conversation-avatar-wrapper">
                    <img class="conversation-avatar-modern"
                        src="{{ asset('public/assets/admin') }}/img/160x160/img1.jpg"
                        alt="User">
                </div>
                <div class="conversation-details">
                    <h6 class="conversation-name text-muted">{{ translate('User not found') }}</h6>
                </div>
            </div>
        </div>
    @endif
@endforeach

@if($conversations->isEmpty())
    <div class="empty-conversations-state">
        <div class="empty-state-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                <path d="M8 10h.01M12 10h.01M16 10h.01"/>
            </svg>
        </div>
        <h6 class="empty-state-title">{{ translate('No conversations yet') }}</h6>
        <p class="empty-state-text">{{ translate('Customer messages will appear here') }}</p>
    </div>
@endif

<style>
/* Conversation List Styles */
.conversation-item-modern {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f5;
    cursor: pointer;
    transition: background 0.2s;
    position: relative;
    background: white;
}

.conversation-item-modern:hover {
    background: #f8f9fa;
}

.conversation-item-modern.active,
.conversation-item-modern:active {
    background: #e9ecef;
    border-left: 3px solid #007bff;
}

.conversation-item-modern.has-unread {
    background: #f0f7ff;
    border-left: 3px solid #007bff;
}

.conversation-item-modern.has-unread:hover {
    background: #e3f2ff;
}

.conversation-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.conversation-avatar-wrapper {
    position: relative;
    flex-shrink: 0;
}

.conversation-avatar-modern {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

.avatar-badge-pulse {
    position: absolute;
    top: 0;
    right: 0;
    width: 12px;
    height: 12px;
    background: #28a745;
    border: 2px solid white;
    border-radius: 50%;
}

.conversation-details {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}

.conversation-name {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.has-unread .conversation-name {
    color: #007bff;
}

.conversation-time {
    font-size: 12px;
    color: #6c757d;
    white-space: nowrap;
    flex-shrink: 0;
}

.has-unread .conversation-time {
    color: #007bff;
    font-weight: 600;
}

.conversation-preview {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}

.message-preview {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0;
}

.preview-text {
    font-size: 13px;
    color: #65676b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.has-unread .preview-text {
    font-weight: 600;
    color: #1a1a1a;
}

.conversation-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.unread-count-badge {
    min-width: 20px;
    height: 20px;
    background: #007bff;
    color: white;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}

.assigned-admin-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #e9ecef;
}

/* Empty State */
.empty-conversations-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    color: #6c757d;
}

.empty-state-title {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.empty-state-text {
    font-size: 14px;
    color: #8b8b8b;
    margin: 0;
}

/* Disabled state */
.conversation-item-modern.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.conversation-item-modern.disabled:hover {
    background: white;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .conversation-item-modern {
        padding: 10px 12px;
    }

    .conversation-avatar-modern {
        width: 46px;
        height: 46px;
    }

    .conversation-name {
        font-size: 14px;
    }

    .preview-text {
        font-size: 12px;
    }
}
</style>
