# Employee Chat System - Architecture Diagram

## System Overview

```
┌──────────────────────────────────────────────────────────────────┐
│                        BROWSER (Chrome/Safari)                    │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Laravel Admin Panel (http://new.snocart.com/admin)     │    │
│  │  - Traditional session-based authentication             │    │
│  │  - Blade templates                                       │    │
│  └────────────┬────────────────────────────────────────────┘    │
│               │                                                   │
│               │ Admin clicks "Employee Chat" menu item           │
│               ↓                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Employee Chat Iframe View                              │    │
│  │  Route: /admin/employee-chat                            │    │
│  │  - Generates Sanctum token                              │    │
│  │  - Embeds React app via iframe                          │    │
│  └────────────┬────────────────────────────────────────────┘    │
│               │                                                   │
│               │ Iframe loads with token in URL                   │
│               ↓                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  React Chat App (http://localhost:3000/admin/chat)      │    │
│  │  - Stores token in localStorage                         │    │
│  │  - 3-column layout (conversations, messages, employees) │    │
│  │  - Polling every 5 seconds for new messages             │    │
│  └────────────┬────────────────────────────────────────────┘    │
│               │                                                   │
└───────────────┼───────────────────────────────────────────────────┘
                │
                │ API calls with Bearer token
                │
                ↓
┌──────────────────────────────────────────────────────────────────┐
│                      LARAVEL BACKEND                              │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Sanctum Middleware (auth:sanctum)                      │    │
│  │  - Validates Bearer token                               │    │
│  │  - Checks admin status = 1                              │    │
│  │  - Returns 401 if invalid                               │    │
│  └────────────┬────────────────────────────────────────────┘    │
│               │                                                   │
│               │ Token valid, proceed                             │
│               ↓                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  EmployeeChatController                                 │    │
│  │  - conversations()  → Get list                          │    │
│  │  - messages($id)    → Get messages                      │    │
│  │  - send()           → Send message                      │    │
│  │  - poll()           → Real-time polling                 │    │
│  │  - employees()      → Get directory                     │    │
│  │  - upload()         → File upload                       │    │
│  └────────────┬────────────────────────────────────────────┘    │
│               │                                                   │
│               │ Calls service layer                              │
│               ↓                                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  EmployeeChatService                                    │    │
│  │  - createOrGetUserInfo($admin)                          │    │
│  │  - findOrCreateConversation($sender, $receiver)         │    │
│  │  - sendMessage($sender, $receiver, $msg, $files)        │    │
│  │  - getMessages($conversationId, $userId)                │    │
│  │  - getConversations($userId)                            │    │
│  │  - markAsRead($conversationId, $userId)                 │    │
│  │  - getAvailableEmployees($excludeId)                    │    │
│  │  - pollNewMessages($userId, $since)                     │    │
│  └────────────┬────────────────────────────────────────────┘    │
│               │                                                   │
│               │ Database queries via Eloquent                    │
│               ↓                                                   │
└──────────────────────────────────────────────────────────────────┘
                │
                ↓
┌──────────────────────────────────────────────────────────────────┐
│                        MySQL DATABASE                             │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐  │
│  │ conversations    │  │ messages         │  │ user_infos   │  │
│  ├──────────────────┤  ├──────────────────┤  ├──────────────┤  │
│  │ id               │  │ id               │  │ id           │  │
│  │ sender_id    ────┼──┼─→ sender_id  ────┼──┼→ (FK)        │  │
│  │ receiver_id  ────┼──┼─→ (FK)           │  │ admin_id ────┼─→│
│  │ sender_type      │  │ conversation_id ─┼─→│ (FK)         │  │
│  │ receiver_type    │  │ message          │  │ f_name       │  │
│  │ last_message_id ─┼─→│ file (JSON)      │  │ l_name       │  │
│  │ last_message_time│  │ is_seen          │  │ email        │  │
│  │ unread_count     │  │ created_at       │  │ phone        │  │
│  └──────────────────┘  └──────────────────┘  │ image        │  │
│                                               └──────────────┘  │
│                                                      │           │
│  ┌──────────────────┐  ┌──────────────────────────┐│           │
│  │ admins           │  │ personal_access_tokens   ││           │
│  ├──────────────────┤  ├──────────────────────────┤│           │
│  │ id           ←───┼──┼─ admin_id                ││           │
│  │ f_name           │  │ tokenable_type           ││           │
│  │ l_name           │  │ tokenable_id             ││           │
│  │ email            │  │ name                     ││           │
│  │ phone            │  │ token (hashed)           ││           │
│  │ status (1=active)│  │ abilities                ││           │
│  │ role_id          │  │ created_at               ││           │
│  └──────────────────┘  └──────────────────────────┘│           │
│                                                      └───────────┘
└──────────────────────────────────────────────────────────────────┘
```

## Data Flow: Sending a Message

```
┌──────────────┐
│ React App    │
│              │  1. User types message and clicks Send
│ MessageInput │
└──────┬───────┘
       │
       │  2. Calls chatApi.sendMessage(receiverId, text, files)
       │     POST /api/v1/admin/employee-chat/messages
       │     Headers: Authorization: Bearer {token}
       │     Body: { receiver_id: 2578, message: "Hello", files: [] }
       │
       ↓
┌──────────────────────┐
│ Laravel Middleware   │
│ auth:sanctum         │  3. Validates token against personal_access_tokens table
└──────┬───────────────┘
       │
       │  4. Token valid → Retrieves authenticated Admin
       │
       ↓
┌──────────────────────┐
│ EmployeeChatController│
│ send()               │  5. Validates request data
└──────┬───────────────┘     - receiver_id exists
       │                      - message < 5000 chars
       │                      - files valid (max 5, 10MB each, correct MIME)
       │
       │  6. Gets current admin's UserInfo ID
       │     $userInfo = createOrGetUserInfo($admin)
       │
       ↓
┌──────────────────────┐
│ EmployeeChatService  │
│ sendMessage()        │  7. Starts DB transaction
└──────┬───────────────┘
       │
       │  8. Finds or creates conversation
       │     Conversation::firstOrCreate([
       │       'sender_id' => $senderId,
       │       'receiver_id' => $receiverId,
       │       'sender_type' => 'employee',
       │       'receiver_type' => 'employee'
       │     ])
       │
       │  9. Creates message record
       │     Message::create([
       │       'conversation_id' => $conv->id,
       │       'sender_id' => $senderId,
       │       'message' => $text,
       │       'file' => json_encode($files),
       │       'is_seen' => 0
       │     ])
       │
       │  10. Updates conversation
       │      $conv->last_message_id = $message->id
       │      $conv->last_message_time = now()
       │      $conv->unread_message_count++
       │
       │  11. Commits transaction
       │
       ↓
┌──────────────────────┐
│ Database             │  12. Inserts message row
│ messages table       │      Updates conversations row
└──────┬───────────────┘
       │
       │  13. Returns message with sender info
       │
       ↓
┌──────────────────────┐
│ EmployeeChatController│  14. Formats response
│ send()               │      { "success": true, "message": {...} }
└──────┬───────────────┘
       │
       │  15. Returns JSON response
       │
       ↓
┌──────────────┐
│ React App    │  16. Receives response
│ chatStore    │      - Adds message to local state (optimistic update)
└──────────────┘      - Updates conversation last_message
                      - UI shows message immediately
```

## Data Flow: Receiving a Message (Polling)

```
┌──────────────┐
│ React App    │
│              │  1. setInterval runs every 5 seconds
│ chatStore    │
│ startPolling │
└──────┬───────┘
       │
       │  2. Calls chatApi.poll(lastTimestamp)
       │     GET /api/v1/admin/employee-chat/poll?since=1710170000
       │     Headers: Authorization: Bearer {token}
       │
       ↓
┌──────────────────────┐
│ Laravel Middleware   │  3. Validates token
└──────┬───────────────┘
       │
       ↓
┌──────────────────────┐
│ EmployeeChatController│
│ poll()               │  4. Gets current admin's UserInfo ID
└──────┬───────────────┘
       │
       │  5. Calls service
       │
       ↓
┌──────────────────────┐
│ EmployeeChatService  │
│ pollNewMessages()    │  6. Finds all conversations user is part of
└──────┬───────────────┘
       │
       │  7. Queries messages table:
       │     WHERE conversation_id IN (user's conversations)
       │     AND created_at > $since
       │     AND sender_id != $userId (not own messages)
       │     ORDER BY created_at ASC
       │
       ↓
┌──────────────────────┐
│ Database             │  8. Returns 0-N new messages
│ messages table       │
└──────┬───────────────┘
       │
       │  9. Returns { messages: [...], timestamp: 1710170005 }
       │
       ↓
┌──────────────┐
│ React App    │  10. Receives response
│ chatStore    │      - If messages.length > 0:
└──────────────┘        • Adds to state
                        • Updates conversations list
                        • Shows notification badge
                        • Plays sound (optional)
                      - Updates lastPollTimestamp for next poll
```

## Authentication Flow

```
┌──────────────┐
│ Admin User   │  1. Visits http://new.snocart.com/admin
└──────┬───────┘
       │
       │  2. Logs in via traditional Laravel session auth
       │
       ↓
┌──────────────────────┐
│ Laravel Admin Panel  │  3. Session created, authenticated
└──────┬───────────────┘
       │
       │  4. Clicks "Employee Chat" menu
       │
       ↓
┌──────────────────────┐
│ routes/admin.php     │  5. Route: GET /admin/employee-chat
│ employee-chat route  │
└──────┬───────────────┘
       │
       │  6. Gets authenticated admin from session
       │     $admin = auth('admin')->user()
       │
       │  7. Checks status = 1 (approved)
       │     if ($admin->status != 1) abort(403)
       │
       │  8. Generates Sanctum token
       │     $token = $admin->createToken('employee-chat')->plainTextToken
       │
       │  9. Inserts token into personal_access_tokens table
       │
       ↓
┌──────────────────────┐
│ Database             │  Token stored:
│ personal_access_     │  - tokenable_type: App\Models\Admin
│ tokens table         │  - tokenable_id: 123 (admin ID)
└──────┬───────────────┘  - token: hashed value
       │                   - name: 'employee-chat'
       │
       │  10. Renders iframe view with token
       │
       ↓
┌──────────────────────┐
│ employee-chat-       │  11. Blade template loads:
│ iframe.blade.php     │      <iframe src="http://localhost:3000/admin/chat?token={token}">
└──────┬───────────────┘
       │
       │  12. Iframe loads React app with token in URL
       │
       ↓
┌──────────────┐
│ React App    │  13. useEffect on mount:
│ ChatPage     │      - Reads token from URL (?token=...)
└──────────────┘      - Stores in localStorage: localStorage.setItem('chat_token', token)
                      - All API calls include: Authorization: Bearer {token}
```

## File Upload Flow

```
┌──────────────┐
│ User         │  1. Clicks file attach button
└──────┬───────┘
       │
       │  2. Selects 1-5 files (jpg, png, pdf, doc, docx)
       │
       ↓
┌──────────────┐
│ MessageInput │  3. Shows file previews
│ Component    │
└──────┬───────┘
       │
       │  4. User clicks Send
       │     POST /api/v1/admin/employee-chat/messages
       │     Content-Type: multipart/form-data
       │     Body:
       │       - receiver_id: 2578
       │       - message: "Here are the files"
       │       - files[]: File object 1
       │       - files[]: File object 2
       │
       ↓
┌──────────────────────┐
│ EmployeeChatController│  5. Validates each file:
│ send()               │     - Max 10MB
└──────┬───────────────┘     - MIME type in whitelist
       │                      - Max 5 files total
       │
       │  6. Loops through files:
       │     foreach ($request->file('files') as $file) {
       │       $filename = time() . '_' . random(10) . '.ext'
       │       $path = $file->storeAs('chat-files', $filename, 'public')
       │       $uploadedFiles[] = [
       │         'name' => $file->getClientOriginalName(),
       │         'path' => $path,
       │         'url' => asset('storage/' . $path),
       │         'type' => $file->getMimeType(),
       │         'size' => $file->getSize()
       │       ]
       │     }
       │
       ↓
┌──────────────────────┐
│ File System          │  7. Saves files to:
│ storage/app/public/  │     /var/www/html/.../storage/app/public/chat-files/
│ chat-files/          │
└──────┬───────────────┘
       │
       │  8. Passes $uploadedFiles array to sendMessage()
       │     - Stored as JSON in messages.file column
       │
       ↓
┌──────────────────────┐
│ Database             │  9. Inserts message:
│ messages table       │     file: '[{"name":"doc.pdf","url":"...","type":"..."}]'
└──────┬───────────────┘
       │
       │  10. Returns message with file URLs
       │
       ↓
┌──────────────┐
│ React App    │  11. Receives response
│ MessageList  │      - Renders file preview links
└──────────────┘      - User can click to download/view
```

## Conversation States

```
┌─────────────────────────────────────────────────────────────┐
│                     CONVERSATION LIFECYCLE                   │
└─────────────────────────────────────────────────────────────┘

STATE 1: No Conversation Exists
┌──────────────────────┐
│ User A selects       │
│ User B from employee │  → No conversation record in DB
│ directory            │
└──────────────────────┘

       ↓ User A sends first message

STATE 2: Conversation Created
┌──────────────────────────────────────────┐
│ conversations table                      │
│ ─────────────────────────────────────── │
│ sender_id: 2577 (User A's UserInfo ID)  │
│ receiver_id: 2578 (User B's UserInfo ID)│
│ sender_type: 'employee'                  │
│ receiver_type: 'employee'                │
│ last_message_id: 18110                   │
│ last_message_time: 2026-03-11 16:30:00  │
│ unread_message_count: 1                  │
└──────────────────────────────────────────┘

       ↓ User B opens conversation

STATE 3: Messages Read
┌──────────────────────────────────────────┐
│ conversations table                      │
│ ─────────────────────────────────────── │
│ unread_message_count: 0  (reset)        │
└──────────────────────────────────────────┘
┌──────────────────────────────────────────┐
│ messages table                           │
│ ─────────────────────────────────────── │
│ is_seen: 1  (updated from 0)            │
└──────────────────────────────────────────┘

       ↓ New message sent

STATE 4: Active Conversation
┌──────────────────────────────────────────┐
│ conversations table                      │
│ ─────────────────────────────────────── │
│ last_message_id: 18111 (updated)        │
│ last_message_time: 2026-03-11 16:31:00  │
│ unread_message_count: 1 (incremented)   │
└──────────────────────────────────────────┘

       ↓ Conversation continues...

BIDIRECTIONAL LOOKUP:
- Conversation matches if:
  (sender_id = UserA AND receiver_id = UserB) OR
  (sender_id = UserB AND receiver_id = UserA)
- Both users see the same conversation
- Messages are sorted by created_at ASC
```

## Security Layers

```
┌────────────────────────────────────────────────────────────┐
│                      SECURITY LAYERS                        │
└────────────────────────────────────────────────────────────┘

LAYER 1: Laravel Admin Session
┌──────────────────────┐
│ Admin must be        │  ✓ Email/password authentication
│ logged in first      │  ✓ Session-based (cookies)
└──────────────────────┘  ✓ CSRF protection

       ↓

LAYER 2: Admin Status Check
┌──────────────────────┐
│ status = 1           │  ✓ Only approved employees
│ (approved)           │  ✗ Pending/denied applications blocked
└──────────────────────┘

       ↓

LAYER 3: Sanctum Token Generation
┌──────────────────────┐
│ Personal access      │  ✓ Token stored in personal_access_tokens
│ token created        │  ✓ Hashed in database
└──────────────────────┘  ✓ Expires after inactivity (optional)

       ↓

LAYER 4: API Request Validation
┌──────────────────────┐
│ auth:sanctum         │  ✓ Bearer token in Authorization header
│ middleware           │  ✓ Validates against DB
└──────────────────────┘  ✗ Returns 401 if invalid

       ↓

LAYER 5: Conversation Access Control
┌──────────────────────┐
│ Service layer        │  ✓ User can only see own conversations
│ checks ownership     │  ✓ Checks: sender_id = user OR receiver_id = user
└──────────────────────┘  ✗ Returns 404 if not participant

       ↓

LAYER 6: File Upload Validation
┌──────────────────────┐
│ Server-side checks   │  ✓ Max 10MB per file
└──────────────────────┘  ✓ MIME type whitelist
                          ✓ Max 5 files per message
                          ✗ Rejects executable files

       ↓

LAYER 7: Rate Limiting
┌──────────────────────┐
│ 600 req/min per user │  ✓ Prevents API abuse
└──────────────────────┘  ✗ Returns 429 if exceeded

       ↓

✅ Request Authorized & Processed
```

---

**This architecture supports:**
- 12 concurrent employees (current)
- Scales to 100+ employees (with WebSocket)
- ~720-1152 DB queries/min (polling)
- ~50-100 DB queries/min (WebSocket)
- <500ms response time (cached)
- 99.9% uptime potential
