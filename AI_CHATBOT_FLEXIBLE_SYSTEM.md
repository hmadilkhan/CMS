# AI Chatbot - Flexible Query System

## Problem Solved ✅

**Before:** AI chatbot sirf 13 hardcoded intents handle kar sakta tha. Agar user kuch alag poochta tha, to "I cannot answer" kehta tha.

**After:** Ab AI chatbot **kuch bhi** answer kar sakta hai jo database schema mein available hai!

---

## What Changed? 🔄

### 1. **Removed Intent Restrictions**
- **File:** `app/Services/AiQueryPlannerService.php`
- **Change:** Hardcoded 13 intents ki enum hata di
- **Result:** Ab AI koi bhi intent dynamically generate kar sakta hai

### 2. **Added AI-Powered SQL Generation**
- **New File:** `app/Services/AiDynamicSqlBuilderService.php`
- **Feature:** AI ab SQL queries generate kar sakta hai natural language se
- **Security:** Strict validation ke sath - sirf SELECT queries allowed

### 3. **Improved AI Instructions**
- **File:** `app/Services/AiQueryPlannerService.php`
- **Change:** AI ko zyada flexible aur helpful banaya
- **Result:** Ab uncommon questions bhi handle kar sakta hai

### 4. **Hybrid Approach**
- Complex queries → Traditional query builder (safe, tested)
- Simple/new queries → AI-generated SQL (flexible, validated)
- Fallback mechanism → Agar AI fail ho to traditional builder use hoga

---

## How It Works Now 🚀

```
User Question
    ↓
AI Query Planner (Flexible - any intent)
    ↓
AI Dynamic SQL Builder (generates SQL)
    ↓
SQL Validator (strict security checks)
    ↓
Query Executor (read-only connection)
    ↓
Answer Formatter
    ↓
User Response
```

---

## Security Features 🔒

1. ✅ **Only SELECT queries** - INSERT/UPDATE/DELETE blocked
2. ✅ **Parameterized queries** - SQL injection protection
3. ✅ **Schema validation** - Only allowed tables/columns
4. ✅ **Role-based access** - User permissions enforced
5. ✅ **Query timeout** - 5 seconds max
6. ✅ **LIMIT enforcement** - Max 100 rows
7. ✅ **No subqueries** - Prevents bypass attempts
8. ✅ **No UNION** - Prevents data leakage
9. ✅ **No comments** - Prevents SQL injection tricks

---

## Examples of New Capabilities 💡

### Before (Would Fail ❌)
```
User: "Show me all customers"
AI: "I could not understand that CRM question yet."
```

### After (Works ✅)
```
User: "Show me all customers"
AI: "Here are 45 customers."
[Shows table with customer data]
```

### More Examples:
```
✅ "How many users are in the system?"
✅ "List all employees"
✅ "Show customers from California"
✅ "Projects with customer names"
✅ "Tickets grouped by priority"
✅ "Show me all departments"
✅ "List projects in Engineering department"
```

---

## Testing 🧪

Run the test script:
```bash
php test-ai-flexible.php
```

This will test:
- Basic counts
- Lists with filters
- Uncommon queries
- Grouped queries
- Complex joins
- Security blocks

---

## Configuration ⚙️

### Required in `.env`:
```env
OPENAI_API_KEY=your-key-here
OPENAI_MODEL=gpt-4.1-mini
OPENAI_MAX_OUTPUT_TOKENS=1200
OPENAI_TIMEOUT=60

AI_MAX_QUERY_LIMIT=100
AI_ENABLE_WRITE_BLOCK=true
```

### Optional (Recommended for Production):
```env
AI_READONLY_DB_CONNECTION=ai_readonly
AI_MAX_DAILY_REQUESTS_PER_USER=100
AI_QUERY_TIMEOUT_MS=5000
AI_QUERY_CACHE_TTL=300
```

---

## How to Use 📱

### In Chat Interface:
Just ask naturally in English or Urdu/Hindi:

**English:**
- "How many projects do I have?"
- "Show me all customers"
- "List pending tickets"

**Urdu/Hindi (Roman):**
- "Mere projects kitne hain?"
- "Sab customers dikhao"
- "Pending tickets list karo"

---

## Troubleshooting 🔧

### Issue: AI says "I cannot answer"
**Solution:** Check if:
1. Table exists in `config/ai_schema.php`
2. User has permission to access that table
3. OpenAI API key is valid

### Issue: Query rejected by validator
**Solution:** This is security working correctly. Check:
1. Is it a write operation? (Blocked by design)
2. Does query have LIMIT? (Required)
3. Are all tables/columns in allowed schema?

### Issue: No results found
**Solution:**
1. Check if data exists in database
2. Check role-based filters (Employee sees only assigned records)
3. Try broader query

---

## Performance Tips ⚡

1. **Cache Results:** Results are cached for 5 minutes (configurable)
2. **Specific Questions:** More specific = faster response
3. **Avoid Wildcards:** Ask for specific columns
4. **Use Filters:** "Show projects in Sales" vs "Show all projects"

---

## Limitations ⚠️

1. **Read-Only:** Cannot insert, update, or delete data
2. **Max 100 Rows:** Queries limited to 100 results
3. **No Subqueries:** For security reasons
4. **Schema-Bound:** Can only query tables in `ai_schema.php`
5. **Role-Based:** Users see only data they have permission for

---

## Future Improvements 🔮

- [ ] Query result caching per user
- [ ] Feedback system (thumbs up/down)
- [ ] Query suggestions based on history
- [ ] Multi-step queries (follow-up questions)
- [ ] Export results to Excel/PDF
- [ ] Scheduled reports

---

## Files Modified 📝

1. `app/Services/AiQueryPlannerService.php` - Removed intent restrictions
2. `app/Services/AiSqlBuilderService.php` - Added dynamic SQL builder integration
3. `app/Services/AiDynamicSqlBuilderService.php` - NEW - AI SQL generation
4. `test-ai-flexible.php` - NEW - Test script

---

## Support 💬

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check AI query logs: `ai_query_logs` table
3. Run test script: `php test-ai-flexible.php`
4. Verify OpenAI API key is working

---

## Credits 👏

Built with:
- Laravel 10+
- OpenAI GPT-4.1-mini
- Secure query builder pattern
- Role-based access control

---

**Last Updated:** January 2025
**Version:** 2.0 (Flexible Query System)
