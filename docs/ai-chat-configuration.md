# AI Chat Configuration

## OpenAI

Set your OpenAI API key in `.env`:

```env
OPENAI_API_KEY=your-key
OPENAI_MODEL=gpt-4.1-mini
OPENAI_MAX_OUTPUT_TOKENS=1200
OPENAI_TIMEOUT=60
```

## Read-Only Database Connection

For production, create a SELECT-only database user and configure a dedicated connection.

```env
AI_READONLY_DB_CONNECTION=ai_readonly
```

Then add `database.connections.ai_readonly` in `config/database.php` with credentials for that read-only user.

## Schema Mapping

All AI-accessible tables and columns are controlled by `config/ai_schema.php`.

Security rules:

- Only add columns that AI is allowed to access.
- Never include passwords, tokens, secrets, or private credentials.
- Mark finance/profitability fields as sensitive.
- Review placeholder mappings before enabling live finance reports.

## Role Mapping

Current AI access roles:

- `Super Admin` and `Admin`: all allowed CRM data.
- `Finance`: finance and profitability data plus project/customer context.
- `Manager`: department/team CRM data only.
- `Employee`: assigned project/ticket style data only.

## Security Notes

- Never execute SQL written directly by OpenAI.
- Always validate generated plans against `ai_schema.php`.
- Always apply role filters before execution.
- Always use SELECT-only queries.
- Always pass generated SQL through `AiSqlValidatorService`.
