# Phase 7: Search & Filtering - Implementation Plan

**Start Date:** 2026-02-27
**Status:** 🚧 In Progress
**Estimated Completion:** Week 11 (2-3 days)

---

## 🎯 Objectives

Implement comprehensive search and filtering capabilities for the messaging system, enabling users to quickly find specific messages, conversations, and attachments across thousands of messages.

---

## 📋 Deliverables

### 1. Full-Text Search ✅ PLANNED
**Purpose:** Enable fast, accurate search across all messages and conversations

**Features:**
- MySQL full-text search indexes
- Multi-column search (message text, sender name, order ID)
- Relevance-based ranking
- Fuzzy matching for typos
- Boolean search operators (AND, OR, NOT)
- Phrase search with quotes
- Search performance: <200ms for 100,000+ messages

**Technical Implementation:**
```sql
-- Full-text indexes
ALTER TABLE messages ADD FULLTEXT INDEX idx_messages_search (message, file_name);
ALTER TABLE conversations ADD FULLTEXT INDEX idx_conversations_search (sender_name);
```

**API Endpoint:**
```php
GET /admin/messages/search?q={query}&type={all|messages|conversations}
```

---

### 2. Advanced Filters ✅ PLANNED
**Purpose:** Narrow down search results with multiple filter criteria

**Filter Types:**

**A. Date Range Filter**
- Today, Yesterday, Last 7 days, Last 30 days, Custom range
- Visual date picker UI
- Timezone-aware filtering
- Performance: Indexed on `created_at`

**B. User Type Filter**
- Admin, Vendor, Store, Customer, Delivery Man
- Multi-select capability
- Conversation filtering by participant type

**C. Attachment Filter**
- Has attachments, No attachments
- Image attachments only, Document attachments only
- File type filters (jpg, png, pdf, etc.)
- Attachment size range

**D. Order Filter**
- Has order reference, No order reference
- Order ID search
- Order status filter (pending, confirmed, delivered)

**E. Read/Unread Filter**
- Unread only, Read only, All
- Mark all as read/unread bulk action

**F. Conversation Status**
- Active, Archived, All
- Pinned conversations priority

**API Endpoint:**
```php
GET /admin/messages/filter?
  date_from={date}&
  date_to={date}&
  user_type[]={type}&
  has_attachments={bool}&
  file_type={ext}&
  has_order={bool}&
  order_id={id}&
  is_read={bool}&
  is_archived={bool}
```

---

### 3. Search Result Highlighting ✅ PLANNED
**Purpose:** Visually highlight matched terms in search results

**Features:**
- Yellow highlight for exact matches
- Context snippets (80 characters before/after match)
- Multiple matches per message highlighted
- Case-insensitive highlighting
- HTML entity safe (XSS protection)

**JavaScript Implementation:**
```javascript
function highlightSearchTerms(text, query) {
    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}
```

---

### 4. Search History & Suggestions ✅ PLANNED
**Purpose:** Speed up repeat searches and discover relevant content

**Features:**

**A. Search History**
- Store last 20 searches per user
- Quick re-run from dropdown
- Clear history option
- Recent searches prioritized

**B. Search Suggestions**
- Auto-complete while typing (300ms debounce)
- Suggest from: recent searches, contact names, order IDs
- Keyboard navigation (arrow keys)
- Click or Enter to select

**Database Table:**
```sql
CREATE TABLE message_search_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    user_type VARCHAR(50) NOT NULL,
    search_query VARCHAR(255) NOT NULL,
    result_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_searches (user_id, user_type, created_at)
);
```

---

### 5. Jump to Message ✅ PLANNED
**Purpose:** Navigate directly to a specific message from search results

**Features:**
- Click search result → opens conversation → scrolls to message
- Highlight target message (2-second yellow pulse)
- Auto-load context (10 messages before/after)
- Deep linking support (`/messages/{conversation_id}#{message_id}`)
- Back button returns to search results

**URL Format:**
```
/admin/messages/{conversation_id}?highlight={message_id}&context=10
```

**JavaScript Scroll Logic:**
```javascript
function jumpToMessage(conversationId, messageId) {
    loadConversation(conversationId);
    scrollToElement(`#message-${messageId}`, {
        behavior: 'smooth',
        block: 'center'
    });
    highlightMessage(messageId, 2000); // 2-sec pulse
}
```

---

### 6. Filter Presets & Saved Filters ✅ PLANNED
**Purpose:** Save common filter combinations for quick access

**Features:**

**A. Built-in Presets**
- "Unread Messages" (is_read = false)
- "Today's Messages" (date = today)
- "Orders with Issues" (has_order = true, unread = true)
- "Customer Support" (user_type = customer, archived = false)
- "Pending Orders" (order_status = pending)

**B. Custom Saved Filters**
- Save current filter combination
- Name custom filters
- Edit/delete saved filters
- Share filters with team (admin only)
- Quick access sidebar

**Database Table:**
```sql
CREATE TABLE message_filter_presets (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NULL,  -- NULL = global preset
    name VARCHAR(100) NOT NULL,
    filter_params JSON NOT NULL,
    is_global BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_presets (user_id),
    INDEX idx_global_presets (is_global, sort_order)
);
```

---

## 🗂️ Files to Create

### Backend (PHP)

1. **Migration: Full-Text Search Indexes**
   - `database/migrations/2026_02_28_000005_add_fulltext_search_to_messages.php`
   - Add FULLTEXT indexes on `messages.message`, `messages.file_name`
   - Add FULLTEXT index on `conversations` (if needed)

2. **Migration: Search History Table**
   - `database/migrations/2026_02_28_000006_create_message_search_history_table.php`
   - Stores user search queries and result counts

3. **Migration: Filter Presets Table**
   - `database/migrations/2026_02_28_000007_create_message_filter_presets_table.php`
   - Stores saved filter combinations

4. **Model: MessageSearchHistory**
   - `app/Models/MessageSearchHistory.php`
   - Scopes for recent searches, popular searches

5. **Model: MessageFilterPreset**
   - `app/Models/MessageFilterPreset.php`
   - Relationships to User
   - Global vs user-specific logic

6. **Service: MessageSearchService**
   - `app/Services/MessageSearchService.php` (~500 lines)
   - Full-text search logic
   - Filter application
   - Result ranking
   - Search history tracking

7. **Service: MessageFilterService**
   - `app/Services/MessageFilterService.php` (~300 lines)
   - Build filter queries
   - Apply date range, user type, attachment filters
   - Preset management (CRUD)

8. **Controller Methods**
   - `Admin/ConversationController.php`
     - `search(Request $request)` - Main search endpoint
     - `filterMessages(Request $request)` - Apply filters
     - `getSearchSuggestions(Request $request)` - Auto-complete
     - `saveSearchHistory(Request $request)` - Record search
     - `getSearchHistory(Request $request)` - Retrieve history
     - `clearSearchHistory(Request $request)` - Delete history
   - `Admin/MessageFilterPresetController.php` (new)
     - `index()` - List presets
     - `store(Request $request)` - Save preset
     - `update($id, Request $request)` - Update preset
     - `destroy($id)` - Delete preset

### Frontend (JavaScript)

9. **Search Component**
   - `public/assets/admin/js/messaging/components/SearchBar.js` (~400 lines)
   - Search input with debounce
   - Auto-complete dropdown
   - Search history dropdown
   - Keyboard navigation

10. **Filter Component**
    - `public/assets/admin/js/messaging/components/FilterPanel.js` (~500 lines)
    - Multi-select filters
    - Date range picker
    - Apply/reset functionality
    - Active filter badges

11. **Search Results Component**
    - `public/assets/admin/js/messaging/components/SearchResults.js` (~350 lines)
    - Result list rendering
    - Highlight matched text
    - Click to jump to message
    - Pagination

12. **Filter Preset Manager**
    - `public/assets/admin/js/messaging/components/PresetManager.js` (~280 lines)
    - Save/load presets
    - Preset dropdown
    - Edit/delete UI

13. **Utilities**
    - `public/assets/admin/js/messaging/utils/SearchHighlighter.js` (~150 lines)
    - Text highlighting logic
    - Context snippet extraction
    - XSS protection

### Frontend (CSS)

14. **Search Styles**
    - `public/assets/admin/css/messaging-search.css` (~300 lines)
    - Search bar styling
    - Filter panel layout
    - Result highlighting
    - Suggestion dropdown

### Views (Blade Templates)

15. **Search Interface**
    - `resources/views/admin-views/messages/partials/_search-bar.blade.php`
    - Search input + suggestions

16. **Filter Panel**
    - `resources/views/admin-views/messages/partials/_filter-panel.blade.php`
    - All filter controls

17. **Search Results**
    - `resources/views/admin-views/messages/partials/_search-results.blade.php`
    - Result list template

18. **Filter Presets Sidebar**
    - `resources/views/admin-views/messages/partials/_filter-presets.blade.php`
    - Saved filters quick access

### Routes

19. **API Routes**
    - `routes/admin.php`
    ```php
    Route::prefix('messages')->group(function() {
        Route::get('search', [ConversationController::class, 'search']);
        Route::post('filter', [ConversationController::class, 'filterMessages']);
        Route::get('suggestions', [ConversationController::class, 'getSearchSuggestions']);
        Route::post('search-history', [ConversationController::class, 'saveSearchHistory']);
        Route::get('search-history', [ConversationController::class, 'getSearchHistory']);
        Route::delete('search-history', [ConversationController::class, 'clearSearchHistory']);

        Route::resource('filter-presets', MessageFilterPresetController::class);
    });
    ```

### Configuration

20. **Config File**
    - `config/messaging_search.php`
    - Search settings (max results, timeout, etc.)
    - Filter defaults
    - History retention policy

### Documentation

21. **Implementation Summary**
    - `PHASE_7_SEARCH_FILTERING_COMPLETE.md` (when done)
    - Feature overview
    - Performance metrics
    - User guide

22. **Testing Guide**
    - `scripts/test-message-search.php`
    - Automated testing

---

## 🎯 Performance Targets

| Metric | Target | How to Achieve |
|--------|--------|----------------|
| Search query time | <200ms | Full-text indexes + result limit |
| Filter application | <100ms | Composite indexes on filter columns |
| Auto-complete response | <50ms | Redis cache for suggestions |
| Jump to message | <300ms | Virtual scrolling + lazy loading |
| Search history load | <20ms | Indexed user_id + created_at |
| Preset load | <30ms | Cached global presets |

---

## 🧪 Testing Strategy

### Unit Tests
- Search query builder
- Filter logic
- Highlight function
- Date range validation

### Integration Tests
- Full-text search accuracy
- Multi-filter combination
- Preset save/load
- Search history tracking

### Performance Tests
- 100,000 message search
- Complex multi-filter query
- Concurrent search requests
- Cache hit rate

### Manual Tests
- Search UI workflow
- Filter panel usability
- Jump to message navigation
- Keyboard shortcuts

---

## 📊 Success Metrics

| Metric | Before Phase 7 | Target | How to Measure |
|--------|----------------|--------|----------------|
| Time to find message | ~2-3 min (manual scroll) | <10s | User testing |
| Search accuracy | N/A | >95% | Relevance testing |
| Search adoption | 0% (no feature) | >60% | Analytics tracking |
| Support ticket resolution | ~15 min/ticket | <5 min | Support metrics |
| User satisfaction | Baseline | +30% | User survey |

---

## 🚀 Implementation Phases

### Phase 7.1: Core Search (Day 1)
1. ✅ Create migrations (full-text indexes, search history)
2. ✅ Build MessageSearchService
3. ✅ Implement search endpoint
4. ✅ Create SearchBar component
5. ✅ Test basic search functionality

### Phase 7.2: Advanced Filters (Day 2)
1. ✅ Build MessageFilterService
2. ✅ Implement filter endpoints
3. ✅ Create FilterPanel component
4. ✅ Test filter combinations
5. ✅ Optimize filter queries

### Phase 7.3: UX Enhancements (Day 3)
1. ✅ Search result highlighting
2. ✅ Jump to message feature
3. ✅ Search suggestions
4. ✅ Filter presets
5. ✅ Polish UI/UX

### Phase 7.4: Testing & Docs (Day 4)
1. ✅ Performance testing
2. ✅ Load testing (100k messages)
3. ✅ Write documentation
4. ✅ Create user guide
5. ✅ Production deployment

---

## 🔧 Configuration Options

```env
# .env
ENABLE_MESSAGE_SEARCH=true
ENABLE_ADVANCED_FILTERS=true
ENABLE_SEARCH_HISTORY=true
ENABLE_FILTER_PRESETS=true

MESSAGE_SEARCH_MAX_RESULTS=100
MESSAGE_SEARCH_TIMEOUT=10
SEARCH_HISTORY_RETENTION_DAYS=90
SEARCH_SUGGESTION_COUNT=10
```

---

## 🎨 UI/UX Design

### Search Bar Location
- Top of conversation list (sticky position)
- Expandable on focus
- Icon: Magnifying glass
- Placeholder: "Search messages, orders, contacts..."

### Filter Panel
- Right sidebar (collapsible)
- Accordion sections for each filter type
- Active filter badges above results
- Clear all filters button

### Search Results
- Modal overlay or inline replacement
- Highlighted matched text
- Context snippet (80 chars)
- Click to jump to message
- Pagination (20 results per page)

### Filter Presets
- Left sidebar section
- "Quick Filters" header
- Built-in presets (non-editable)
- User presets (editable)
- "+ New Preset" button

---

## 📚 Related Documentation

- Phase 1-6: Previous messaging system improvements
- GLOBAL_SEARCH_IMPLEMENTATION.md: Global admin search (different feature)
- MySQL Full-Text Search: https://dev.mysql.com/doc/refman/8.0/en/fulltext-search.html

---

## ✅ Definition of Done

- [ ] All migrations run successfully
- [ ] Full-text search working with <200ms response
- [ ] All 6 filter types implemented and tested
- [ ] Search highlighting working correctly
- [ ] Jump to message feature functional
- [ ] Search history saving and loading
- [ ] Filter presets CRUD complete
- [ ] Performance targets met
- [ ] Documentation written
- [ ] User guide created
- [ ] Production deployment successful
- [ ] Zero critical bugs

---

**Next Step:** Begin Phase 7.1 - Core Search Implementation

Would you like me to proceed with creating the database migrations and backend services?
