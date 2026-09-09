'use client';

import { useChatStore } from '@/lib/store/chatStore';
import { formatDistanceToNow } from 'date-fns';

export default function ConversationList() {
  const { conversations, activeConversation, selectConversation, isLoading } = useChatStore();

  if (isLoading && conversations.length === 0) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (conversations.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center h-full text-gray-500 p-4">
        <svg
          className="w-16 h-16 text-gray-300 mb-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
          />
        </svg>
        <p className="text-center">No conversations yet</p>
        <p className="text-sm text-center mt-1">Start a chat with an employee from the right panel</p>
      </div>
    );
  }

  return (
    <div className="flex-1 overflow-y-auto">
      {conversations.map((conversation) => (
        <div
          key={conversation.id}
          onClick={() => selectConversation(conversation)}
          className={`
            p-4 border-b cursor-pointer hover:bg-gray-50 transition-colors
            ${activeConversation?.id === conversation.id ? 'bg-blue-50 border-l-4 border-l-blue-600' : ''}
          `}
        >
          <div className="flex items-start space-x-3">
            {/* Avatar */}
            <img
              src={conversation.participant.image}
              alt={conversation.participant.name}
              className="w-12 h-12 rounded-full flex-shrink-0"
            />

            {/* Content */}
            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between mb-1">
                <h3 className="font-semibold text-gray-900 truncate">
                  {conversation.participant.name}
                </h3>
                {conversation.last_message && (
                  <span className="text-xs text-gray-500 flex-shrink-0 ml-2">
                    {formatDistanceToNow(new Date(conversation.last_message.created_at), {
                      addSuffix: true,
                    })}
                  </span>
                )}
              </div>

              {conversation.last_message && (
                <p className="text-sm text-gray-600 truncate">
                  {conversation.last_message.is_mine && 'You: '}
                  {conversation.last_message.text}
                </p>
              )}

              {/* Unread badge */}
              {conversation.unread_count > 0 && (
                <div className="mt-2">
                  <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-600 text-white">
                    {conversation.unread_count} new
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
