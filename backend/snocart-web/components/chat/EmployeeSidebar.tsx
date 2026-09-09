'use client';

import { useChatStore } from '@/lib/store/chatStore';

export default function EmployeeSidebar() {
  const { employees, createConversation } = useChatStore();

  return (
    <div className="flex flex-col h-full">
      <div className="p-4 border-b">
        <h2 className="font-semibold text-gray-900">Available Employees</h2>
        <p className="text-xs text-gray-500 mt-1">{employees.length} total</p>
      </div>

      <div className="flex-1 overflow-y-auto">
        {employees.length === 0 ? (
          <div className="flex items-center justify-center h-full text-gray-500 p-4">
            <p className="text-sm text-center">Loading employees...</p>
          </div>
        ) : (
          employees.map((employee) => (
            <div
              key={employee.id}
              onClick={() => createConversation(employee.id)}
              className="p-3 border-b hover:bg-gray-50 cursor-pointer transition-colors"
            >
              <div className="flex items-center space-x-3">
                <div className="relative flex-shrink-0">
                  <img
                    src={employee.image}
                    alt={employee.name}
                    className="w-10 h-10 rounded-full"
                  />
                  <span className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                </div>

                <div className="flex-1 min-w-0">
                  <p className="font-medium text-sm text-gray-900 truncate">{employee.name}</p>
                  <p className="text-xs text-gray-500 truncate">{employee.role}</p>
                </div>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
