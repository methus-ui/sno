// Check if React app is receiving postMessage
console.log('=== CHAT DEBUG INFO ===');
console.log('1. Current URL:', window.location.href);
console.log('2. Token in localStorage:', localStorage.getItem('chat_token') ? 'EXISTS' : 'NONE');
console.log('3. Token expiry:', localStorage.getItem('chat_token_expiry'));
console.log('4. Admin name:', localStorage.getItem('admin_name'));

// Listen for postMessage
window.addEventListener('message', (event) => {
  console.log('5. Received postMessage from:', event.origin);
  console.log('6. Message type:', event.data?.type);
  console.log('7. Has token:', !!event.data?.token);
});

console.log('=== Waiting for postMessage... ===');
