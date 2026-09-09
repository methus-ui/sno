'use client';

import { useChatStore } from '@/lib/store/chatStore';

export default function CustomerInfoPanel() {
  const { customerInfo, customerOrders, activeConversation } = useChatStore();

  // Only show for customer conversations
  if (!activeConversation || activeConversation.type !== 'customer') {
    return (
      <div className="p-4">
        <h3 className="text-sm font-semibold text-gray-700 mb-2">Customer Info</h3>
        <p className="text-xs text-gray-500">Select a customer conversation to view details</p>
      </div>
    );
  }

  if (!customerInfo) {
    return (
      <div className="p-4">
        <h3 className="text-sm font-semibold text-gray-700 mb-2">Customer Info</h3>
        <p className="text-xs text-gray-500">Loading customer information...</p>
      </div>
    );
  }

  const getStatusBadgeColor = (status: string) => {
    const statusColors: Record<string, string> = {
      pending: 'bg-yellow-100 text-yellow-800',
      confirmed: 'bg-blue-100 text-blue-800',
      processing: 'bg-purple-100 text-purple-800',
      handover: 'bg-indigo-100 text-indigo-800',
      picked_up: 'bg-cyan-100 text-cyan-800',
      delivered: 'bg-green-100 text-green-800',
      canceled: 'bg-red-100 text-red-800',
      failed: 'bg-red-100 text-red-800',
      refund_requested: 'bg-orange-100 text-orange-800',
      refunded: 'bg-gray-100 text-gray-800',
    };
    return statusColors[status] || 'bg-gray-100 text-gray-800';
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) {
      return `${diffMins} min ago`;
    } else if (diffHours < 24) {
      return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    } else if (diffDays < 7) {
      return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    } else {
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
  };

  const handleOrderClick = (orderId: number) => {
    // Open order details page in new tab
    window.open(`https://new.snocart.com/admin/order/details/${orderId}`, '_blank');
  };

  return (
    <div className="flex flex-col h-full">
      {/* Customer Info Card */}
      <div className="p-4 border-b bg-white">
        <h3 className="text-sm font-semibold text-gray-700 mb-3">Customer Info</h3>
        <div className="flex items-center space-x-3 mb-3">
          {customerInfo.image ? (
            <img
              src={customerInfo.image}
              alt={customerInfo.name}
              className="w-12 h-12 rounded-full object-cover border-2 border-blue-100"
            />
          ) : (
            <div className="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold text-lg">
              {customerInfo.name.charAt(0).toUpperCase()}
            </div>
          )}
          <div className="flex-1 min-w-0">
            <p className="font-medium text-gray-900 truncate">{customerInfo.name}</p>
            <p className="text-xs text-gray-500 truncate">{customerInfo.phone}</p>
          </div>
        </div>
        {customerInfo.email && (
          <p className="text-xs text-gray-600 truncate mb-2">{customerInfo.email}</p>
        )}
      </div>

      {/* Recent Orders Section */}
      <div className="flex-1 overflow-y-auto p-4 bg-gray-50">
        <h3 className="text-sm font-semibold text-gray-700 mb-3">Recent Orders</h3>

        {customerOrders.length === 0 ? (
          <div className="text-center py-8">
            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <p className="mt-2 text-sm text-gray-500">No orders yet</p>
          </div>
        ) : (
          <div className="space-y-3">
            {customerOrders.map((order) => (
              <div
                key={order.id}
                onClick={() => handleOrderClick(order.id)}
                className="bg-white rounded-lg border border-gray-200 p-3 hover:shadow-md hover:border-blue-300 transition-all cursor-pointer group"
              >
                <div className="flex items-start justify-between mb-2">
                  <div className="flex-1">
                    <p className="text-sm font-semibold text-gray-900 group-hover:text-blue-600">
                      Order #{order.id}
                    </p>
                    <p className="text-xs text-gray-500 mt-0.5">
                      {formatDate(order.created_at)}
                    </p>
                  </div>
                  <svg
                    className="w-4 h-4 text-gray-400 group-hover:text-blue-600 transition-colors"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                </div>

                <div className="flex items-center justify-between mb-2">
                  <span
                    className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusBadgeColor(
                      order.status
                    )}`}
                  >
                    {order.status.replace(/_/g, ' ').toUpperCase()}
                  </span>
                  <span className="text-sm font-bold text-gray-900">
                    ₹{order.amount.toFixed(2)}
                  </span>
                </div>

                {order.scheduled_at && (
                  <div className="flex items-center text-xs text-gray-500 mt-2 pt-2 border-t border-gray-100">
                    <svg className="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Scheduled: {new Date(order.scheduled_at).toLocaleString('en-US', {
                      month: 'short',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    })}
                  </div>
                )}

                <div className="mt-2 pt-2 border-t border-gray-100">
                  <button
                    onClick={(e) => {
                      e.stopPropagation();
                      handleOrderClick(order.id);
                    }}
                    className="text-xs text-blue-600 hover:text-blue-800 font-medium group-hover:underline"
                  >
                    View Order Details →
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}

        {customerOrders.length > 0 && (
          <div className="mt-4 text-center">
            <button
              onClick={() => window.open(`https://new.snocart.com/admin/customer/view/${customerInfo.id}`, '_blank')}
              className="text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline"
            >
              View All Orders →
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
