import { useState } from 'react';
import { useMessagesStore } from '../store/messagesStore';

export default function MessageView() {
  const { activeConversation, sendMessage } = useMessagesStore();
  const [messageText, setMessageText] = useState('');
  const [selectedImages, setSelectedImages] = useState<File[]>([]);
  const [isSending, setIsSending] = useState(false);

  const handleSend = async () => {
    if (!messageText.trim() && selectedImages.length === 0) return;

    setIsSending(true);
    try {
      await sendMessage(messageText, selectedImages);
      setMessageText('');
      setSelectedImages([]);
    } finally {
      setIsSending(false);
    }
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  const handleImageSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const files = Array.from(e.target.files).slice(0, 5); // Max 5 images
      setSelectedImages(files);
    }
  };

  if (!activeConversation) {
    return (
      <div className="flex-1 flex items-center justify-center bg-gray-50">
        <div className="text-center text-gray-500">
          <svg
            className="w-20 h-20 text-gray-300 mx-auto mb-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
            />
          </svg>
          <p className="text-lg">Select a conversation to start messaging</p>
        </div>
      </div>
    );
  }

  const customer = activeConversation.sender;

  return (
    <div className="flex-1 flex flex-col bg-white">
      {/* Header */}
      <div className="px-6 py-4 border-b bg-white shadow-sm">
        <div className="flex items-center space-x-3">
          <img
            src={customer.image_full_url || `https://ui-avatars.com/api/?name=${customer.f_name}+${customer.l_name}&background=4F46E5&color=fff`}
            alt={`${customer.f_name} ${customer.l_name}`}
            className="w-10 h-10 rounded-full"
          />
          <div>
            <h2 className="font-semibold text-gray-900">
              {customer.f_name} {customer.l_name}
            </h2>
            {customer.phone && (
              <p className="text-sm text-gray-500">{customer.phone}</p>
            )}
          </div>
        </div>
      </div>

      {/* Messages (iframe will display Laravel-rendered conversation) */}
      <div className="flex-1 overflow-hidden">
        <iframe
          key={activeConversation.id}
          src={`https://new.snocart.com/admin/messages/view/${activeConversation.id}/${customer.id}`}
          className="w-full h-full border-0"
          title="Messages"
        />
      </div>

      {/* Message Input */}
      <div className="p-4 border-t bg-white">
        {selectedImages.length > 0 && (
          <div className="mb-3 flex gap-2 flex-wrap">
            {selectedImages.map((file, index) => (
              <div key={index} className="relative">
                <img
                  src={URL.createObjectURL(file)}
                  alt={`Preview ${index + 1}`}
                  className="w-16 h-16 object-cover rounded"
                />
                <button
                  onClick={() => setSelectedImages(selectedImages.filter((_, i) => i !== index))}
                  className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs"
                >
                  ×
                </button>
              </div>
            ))}
          </div>
        )}

        <div className="flex items-end space-x-2">
          {/* Image upload button */}
          <label className="cursor-pointer p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <svg className="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <input
              type="file"
              accept="image/*"
              multiple
              onChange={handleImageSelect}
              className="hidden"
            />
          </label>

          {/* Text input */}
          <textarea
            value={messageText}
            onChange={(e) => setMessageText(e.target.value)}
            onKeyDown={handleKeyPress}
            placeholder="Type your message... (Enter to send, Shift+Enter for new line)"
            className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
            rows={2}
            disabled={isSending}
          />

          {/* Send button */}
          <button
            onClick={handleSend}
            disabled={isSending || (!messageText.trim() && selectedImages.length === 0)}
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
          >
            {isSending ? 'Sending...' : 'Send'}
          </button>
        </div>
      </div>
    </div>
  );
}
