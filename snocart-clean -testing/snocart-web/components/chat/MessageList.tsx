'use client';

import { useEffect, useRef } from 'react';
import { useChatStore } from '@/lib/store/chatStore';
import { format, isSameDay } from 'date-fns';

export default function MessageList() {
  const { activeConversation, messages, isLoading } = useChatStore();
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // Auto-scroll to bottom on new message
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  if (!activeConversation) {
    return (
      <div className="flex-1 flex items-center justify-center text-gray-500">
        <div className="text-center">
          <svg
            className="mx-auto h-16 w-16 text-gray-300"
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
          <p className="mt-4 text-lg">Select a conversation to start chatting</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex-1 flex flex-col h-full overflow-hidden">
      {/* Header */}
      <div className="bg-white border-b p-4 flex-shrink-0">
        <div className="flex items-center space-x-3">
          <img
            src={activeConversation.participant.image}
            alt={activeConversation.participant.name}
            className="w-10 h-10 rounded-full"
          />
          <div>
            <h2 className="font-semibold text-gray-900">{activeConversation.participant.name}</h2>
            <p className="text-sm text-gray-500">{activeConversation.participant.email}</p>
          </div>
        </div>
      </div>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4 min-h-0">
        {messages.length === 0 && !isLoading && (
          <div className="flex items-center justify-center h-full text-gray-500">
            <p>No messages yet. Start the conversation!</p>
          </div>
        )}

        {messages.map((message, index) => {
          const showDateSeparator =
            index === 0 ||
            !isSameDay(new Date(message.created_at), new Date(messages[index - 1].created_at));

          return (
            <div key={message.id}>
              {/* Date separator */}
              {showDateSeparator && (
                <div className="flex items-center justify-center my-4">
                  <span className="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                    {format(new Date(message.created_at), 'MMMM d, yyyy')}
                  </span>
                </div>
              )}

              {/* Message bubble */}
              <div className={`flex ${message.is_mine ? 'justify-end' : 'justify-start'}`}>
                <div className={`max-w-[70%] ${message.is_mine ? 'order-2' : 'order-1'}`}>
                  {!message.is_mine && (
                    <div className="flex items-center space-x-2 mb-1">
                      <img
                        src={message.sender.image}
                        alt={message.sender.name}
                        className="w-6 h-6 rounded-full"
                      />
                      <span className="text-xs text-gray-600">{message.sender.name}</span>
                    </div>
                  )}

                  <div
                    className={`
                      rounded-lg px-4 py-2
                      ${message.is_mine ? 'bg-blue-600 text-white' : 'bg-white text-gray-900 border'}
                    `}
                  >
                    <p className="text-sm whitespace-pre-wrap">{message.text}</p>

                    {/* Files */}
                    {message.files && message.files.length > 0 && (
                      <div className="mt-2 space-y-2">
                        {message.files.map((file, idx) => (
                          <a
                            key={idx}
                            href={file.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={`
                              flex items-center space-x-2 p-2 rounded text-xs
                              ${message.is_mine ? 'bg-blue-700 hover:bg-blue-800' : 'bg-gray-50 hover:bg-gray-100'}
                              transition-colors
                            `}
                          >
                            <svg
                              className="w-4 h-4 flex-shrink-0"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                              />
                            </svg>
                            <span className="truncate">{file.name}</span>
                          </a>
                        ))}
                      </div>
                    )}

                    <div className="mt-1 flex items-center justify-end space-x-2">
                      <span
                        className={`text-xs ${message.is_mine ? 'text-blue-200' : 'text-gray-500'}`}
                      >
                        {format(new Date(message.created_at), 'h:mm a')}
                      </span>
                      {message.is_mine && (
                        <span className="text-xs text-blue-200">{message.is_seen ? '✓✓' : '✓'}</span>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          );
        })}

        <div ref={messagesEndRef} />
      </div>
    </div>
  );
}
