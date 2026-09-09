'use client';

import { useState, useEffect } from 'react';
import { chatApi, MessageTemplate } from '@/lib/api/chat';

interface TemplateDropdownProps {
  onSelectTemplate: (content: string) => void;
}

export default function TemplateDropdown({ onSelectTemplate }: TemplateDropdownProps) {
  const [templates, setTemplates] = useState<MessageTemplate[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [newTemplate, setNewTemplate] = useState({ title: '', content: '' });

  useEffect(() => {
    loadTemplates();
  }, []);

  const loadTemplates = async () => {
    setIsLoading(true);
    try {
      const response = await chatApi.getTemplates();
      setTemplates(response.templates || []);
    } catch (error) {
      console.error('Failed to load templates:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleSelectTemplate = async (template: MessageTemplate) => {
    onSelectTemplate(template.content);
    setIsOpen(false);

    // Track template usage in background (don't await)
    chatApi.trackTemplateUsage(template.id).catch((error) => {
      console.error('Failed to track template usage:', error);
    });
  };

  const handleCreateTemplate = async () => {
    if (!newTemplate.title || !newTemplate.content) {
      alert('Please fill in both title and content');
      return;
    }

    try {
      await chatApi.createTemplate(newTemplate.title, newTemplate.content);
      setNewTemplate({ title: '', content: '' });
      setShowCreateModal(false);
      loadTemplates();
    } catch (error) {
      console.error('Failed to create template:', error);
      alert('Failed to create template');
    }
  };

  const handleDeleteTemplate = async (id: number, event: React.MouseEvent) => {
    event.stopPropagation();

    if (!confirm('Delete this template?')) return;

    try {
      await chatApi.deleteTemplate(id);
      loadTemplates();
    } catch (error) {
      console.error('Failed to delete template:', error);
      alert('Failed to delete template');
    }
  };

  return (
    <div className="relative inline-block">
      {/* Dropdown Button */}
      <button
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        className="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        Quick Templates
        <svg className="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
          <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
        </svg>
      </button>

      {/* Dropdown Menu */}
      {isOpen && (
        <div className="absolute z-10 w-80 bottom-full mb-2 bg-white rounded-lg shadow-lg border border-gray-200 max-h-96 overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h3 className="text-sm font-semibold text-gray-900">Quick Templates</h3>
            <button
              onClick={() => {
                setShowCreateModal(true);
                setIsOpen(false);
              }}
              className="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700"
            >
              + New
            </button>
          </div>

          {/* Template List */}
          <div className="overflow-y-auto max-h-72">
            {isLoading ? (
              <div className="px-4 py-8 text-center text-gray-500">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p className="mt-2 text-sm">Loading templates...</p>
              </div>
            ) : templates.length === 0 ? (
              <div className="px-4 py-8 text-center text-gray-500">
                <svg className="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p className="mt-2 text-sm">No templates yet</p>
                <button
                  onClick={() => {
                    setShowCreateModal(true);
                    setIsOpen(false);
                  }}
                  className="mt-3 px-3 py-1 text-sm text-blue-600 hover:text-blue-800"
                >
                  Create your first template
                </button>
              </div>
            ) : (
              templates.map((template) => (
                <div
                  key={template.id}
                  onClick={() => handleSelectTemplate(template)}
                  className="flex items-start px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 group"
                >
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 truncate">
                      {template.title}
                    </p>
                    <p className="text-xs text-gray-500 line-clamp-2 mt-1">
                      {template.content}
                    </p>
                  </div>
                  <button
                    onClick={(e) => handleDeleteTemplate(template.id, e)}
                    className="ml-2 p-1 text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
                    title="Delete template"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              ))
            )}
          </div>
        </div>
      )}

      {/* Create Template Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-900">Create Template</h3>
            </div>
            <div className="px-6 py-4 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Template Title
                </label>
                <input
                  type="text"
                  value={newTemplate.title}
                  onChange={(e) => setNewTemplate({ ...newTemplate, title: e.target.value })}
                  placeholder="e.g., Order Confirmation"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  maxLength={255}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Template Content
                </label>
                <textarea
                  value={newTemplate.content}
                  onChange={(e) => setNewTemplate({ ...newTemplate, content: e.target.value })}
                  placeholder="Enter your message template..."
                  rows={5}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  maxLength={1000}
                />
                <p className="mt-1 text-xs text-gray-500">
                  {newTemplate.content.length}/1000 characters
                </p>
              </div>
            </div>
            <div className="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
              <button
                onClick={() => {
                  setShowCreateModal(false);
                  setNewTemplate({ title: '', content: '' });
                }}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={handleCreateTemplate}
                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
              >
                Create Template
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Backdrop to close dropdown */}
      {isOpen && (
        <div
          className="fixed inset-0 z-0"
          onClick={() => setIsOpen(false)}
        />
      )}
    </div>
  );
}
