import { useEffect } from 'react';
import { useMessagesStore } from './store/messagesStore';
import ConversationList from './components/ConversationList';
import MessageView from './components/MessageView';

function App() {
  const { loadConversations, startPolling, stopPolling, error, clearError } = useMessagesStore();

  useEffect(() => {
    // Load initial conversations
    loadConversations();

    // Start polling for new messages
    startPolling();

    // Cleanup on unmount
    return () => stopPolling();
  }, []);

  return (
    <div className="h-screen flex flex-col bg-gray-100">
      {/* Error notification */}
      {error && (
        <div className="bg-red-50 border-l-4 border-red-500 p-4 m-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <svg className="w-5 h-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
              <p className="text-red-700">{error}</p>
            </div>
            <button
              onClick={clearError}
              className="text-red-500 hover:text-red-700"
            >
              <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      )}

      {/* Main chat interface */}
      <div className="flex-1 flex overflow-hidden">
        {/* Conversations sidebar (30%) */}
        <div className="w-[30%] flex flex-col">
          <ConversationList />
        </div>

        {/* Message view (70%) */}
        <div className="flex-1 flex flex-col">
          <MessageView />
        </div>
      </div>
    </div>
  );
}

export default App;
