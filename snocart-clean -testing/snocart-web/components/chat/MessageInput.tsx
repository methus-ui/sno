'use client';

import { useState, useRef, KeyboardEvent } from 'react';
import { useChatStore } from '@/lib/store/chatStore';
import TemplateDropdown from './TemplateDropdown';

export default function MessageInput() {
  const { activeConversation, sendMessage } = useChatStore();
  const [text, setText] = useState('');
  const [files, setFiles] = useState<File[]>([]);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleSend = async () => {
    if (!text.trim() && files.length === 0) return;
    if (!activeConversation) return;

    await sendMessage(text, files);
    setText('');
    setFiles([]);
  };

  const handleKeyPress = (e: KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selectedFiles = Array.from(e.target.files || []);

    // Validate file count
    if (selectedFiles.length + files.length > 5) {
      alert('Maximum 5 files allowed');
      return;
    }

    // Validate file size
    const invalidFiles = selectedFiles.filter(f => f.size > 10 * 1024 * 1024);
    if (invalidFiles.length > 0) {
      alert('Some files exceed 10MB limit');
      return;
    }

    setFiles([...files, ...selectedFiles]);
  };

  const handleTemplateSelect = (content: string) => {
    setText(content);
  };

  const handleInsertGreeting = () => {
    // Get admin name from localStorage or use placeholder
    const adminName = localStorage.getItem('admin_name') || 'Support Agent';

    const greeting = `السلام علیکم (Assalamualaikum),\n\nI'm ${adminName} and I've been assigned to assist you with your matter. Please give me a moment to look into it.\n\n`;

    // If there's existing text, prepend greeting, otherwise just set greeting
    setText(text ? greeting + text : greeting);
  };

  if (!activeConversation) {
    return null;
  }

  return (
    <div className="border-t bg-white p-4">
      {/* Quick Templates and Greeting Button */}
      <div className="mb-3 flex items-center gap-2">
        <TemplateDropdown onSelectTemplate={handleTemplateSelect} />
        <button
          onClick={handleInsertGreeting}
          className="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all shadow-sm hover:shadow-md"
          title="Insert personalized greeting"
        >
          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
          <span className="text-sm font-medium">Add Greeting</span>
        </button>
      </div>

      {/* File previews */}
      {files.length > 0 && (
        <div className="mb-2 flex flex-wrap gap-2">
          {files.map((file, index) => (
            <div key={index} className="flex items-center space-x-2 bg-gray-100 rounded px-3 py-1">
              <svg className="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              <span className="text-sm truncate max-w-[200px]">{file.name}</span>
              <button
                onClick={() => setFiles(files.filter((_, i) => i !== index))}
                className="text-red-500 hover:text-red-700 font-bold text-lg leading-none"
              >
                ×
              </button>
            </div>
          ))}
        </div>
      )}

      <div className="flex items-end space-x-2">
        {/* File upload button */}
        <button
          onClick={() => fileInputRef.current?.click()}
          className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors flex-shrink-0"
          title="Attach file"
        >
          <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
            />
          </svg>
        </button>

        <input
          ref={fileInputRef}
          type="file"
          multiple
          accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
          onChange={handleFileChange}
          className="hidden"
        />

        {/* Text input */}
        <textarea
          value={text}
          onChange={(e) => setText(e.target.value)}
          onKeyPress={handleKeyPress}
          placeholder="Type a message... (Shift+Enter for new line)"
          className="flex-1 resize-none border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-600 max-h-48"
          rows={3}
          style={{
            minHeight: '90px',
            height: 'auto',
            maxHeight: '192px',
          }}
        />

        {/* Send button */}
        <button
          onClick={handleSend}
          disabled={!text.trim() && files.length === 0}
          className="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex-shrink-0"
          title="Send message"
        >
          <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
            />
          </svg>
        </button>
      </div>

      <p className="text-xs text-gray-500 mt-2">
        Max 5 files, 10MB each. Supported: jpg, png, pdf, doc, docx
      </p>
    </div>
  );
}
