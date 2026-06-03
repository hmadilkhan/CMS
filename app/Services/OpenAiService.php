<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiService
{
    public function createResponse(string $message, ?string $previousResponseId = null, array $context = []): array
    {
        $apiKey = config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $payload = array_filter([
            'model' => config('services.openai.model', 'gpt-4.1-mini'),
            'instructions' => $this->buildAssistantInstructions($context),
            'input' => $message,
            'previous_response_id' => $previousResponseId,
            'max_output_tokens' => (int) config('services.openai.max_output_tokens', 1200),
        ], fn ($value) => ! is_null($value));

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.openai.timeout', 60))
            ->post('https://api.openai.com/v1/responses', $payload);

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?? 'OpenAI request failed.');
        }

        $data = $response->json();

        return [
            'id' => Arr::get($data, 'id'),
            'model' => Arr::get($data, 'model', $payload['model']),
            'text' => $this->extractText($data),
            'usage' => Arr::get($data, 'usage', []),
            'payload' => $payload,
            'raw' => $data,
        ];
    }

    public function createJsonResponse(string $instructions, array|string $input, int $maxOutputTokens = 1200, ?array $jsonSchema = null, ?string $model = null): array
    {
        $apiKey = config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $payload = [
            'model' => $model ?: config('services.openai.model', 'gpt-4.1-mini'),
            'instructions' => $instructions,
            'input' => is_array($input) ? json_encode($input, JSON_PRETTY_PRINT) : $input,
            'text' => [
                'format' => $jsonSchema ?? [
                    'type' => 'json_object',
                ],
            ],
            'max_output_tokens' => $maxOutputTokens,
        ];

        $lastData = null;
        $lastText = null;
        $fallbackModel = config('services.openai.model', 'gpt-4.1-mini');

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.openai.timeout', 60))
                ->post('https://api.openai.com/v1/responses', $payload);

            if ($response->failed()) {
                // Resilience: if a stronger/custom model is unavailable for this
                // account (e.g. no access to gpt-4.1), retry once on the default
                // model rather than failing the whole request.
                if ($payload['model'] !== $fallbackModel) {
                    $payload['model'] = $fallbackModel;
                    continue;
                }

                throw new RuntimeException($response->json('error.message') ?? 'OpenAI request failed.');
            }

            $lastData = $response->json();
            $lastText = $this->extractText($lastData);
            $decoded = json_decode($lastText, true);

            if (is_array($decoded)) {
                return [
                    'id' => Arr::get($lastData, 'id'),
                    'model' => Arr::get($lastData, 'model', $payload['model']),
                    'json' => $decoded,
                    'text' => $lastText,
                    'usage' => Arr::get($lastData, 'usage', []),
                    'payload' => $payload,
                    'raw' => $lastData,
                ];
            }

            $payload['input'] = "The previous response was not valid JSON. Return JSON only that matches the required schema.\n\nOriginal input:\n" . $payload['input'];
        }

        throw new RuntimeException('OpenAI did not return valid JSON.');
    }

    /**
     * The stronger GPT-4-class model reserved for query planning and SQL generation.
     */
    public function sqlModel(): string
    {
        return (string) config('services.openai.sql_model')
            ?: (string) config('services.openai.model', 'gpt-4.1-mini');
    }

    private function buildAssistantInstructions(array $context): string
    {
        $appName = $context['app_name'] ?? config('app.name', 'CRM');
        $userName = $context['user_name'] ?? 'there';
        $username = $context['username'] ?? null;
        $roles = $context['roles'] ?? [];
        $rolesStr = implode(', ', array_filter($roles));

        $lines = [
            "You are SolenAssist, the intelligent AI assistant built into the {$appName} CRM system — a solar energy installation company.",
            '',
            '## Your Personality & Behavior',
            '- Be warm, friendly, and engaging — like a knowledgeable colleague, not a formal corporate bot.',
            "- Address the user by their first name ({$userName}) when it feels natural.",
            '- Be concise but complete. Avoid filler phrases like "Certainly!" or "Of course!".',
            '- After giving a helpful answer, suggest 1-2 relevant follow-up questions the user might want to explore next.',
            '- If the user writes in Urdu or mixed Urdu/English, respond naturally in the same style.',
            '- When the user is frustrated or confused, acknowledge it warmly and guide them to what you can help with.',
            '- Be proactive: if a question is vague, ask one focused clarifying question.',
            '',
            '## What You Can Do',
            '- Answer general questions about how to use the CRM, project workflows, ticket processes.',
            '- Explain solar industry terminology and business processes.',
            '- Retrieve live CRM data (projects, tasks, tickets, customers, teams) via a secure read-only pipeline.',
            '- Help users understand their data, spot trends, and plan next steps.',
            '- Guide users toward asking better data questions for clearer insights.',
            '',
            '## What You Cannot Do',
            '- You cannot create, update, or delete any CRM records — this is a read-only assistant.',
            '- You cannot access data outside the current user\'s role-based permissions.',
            '- Do not invent or guess CRM records, project names, customer data, or numbers.',
            '- CRM data is fetched by a secure system — never answer data questions from your own memory.',
            '',
            '## Solar CRM Domain Knowledge',
            'This CRM manages solar energy installation projects. Key terms and workflow:',
            '',
            '### Solar Industry Abbreviations',
            '- NTP = Notice to Proceed — authorization to begin solar installation work',
            '- PTO = Permission to Operate — utility company approval to turn on the solar system',
            '- HOA = Homeowners Association — requires approval before installation in some neighborhoods',
            '- AHJ = Authority Having Jurisdiction — local government body that approves permits',
            '- MPU = Main Panel Upgrade — electrical panel upgrade required before solar installation',
            '- COC = Certificate of Completion — final document packet mailed to customer after PTO',
            '- ADU = Accessory Dwelling Unit — a secondary home unit on the same property',
            '',
            '### Typical Solar Project Workflow (in order)',
            '1. Project Created → Customer sold, project record created',
            '2. Site Survey → Physical inspection of the customer\'s home',
            '3. HOA Approval (if required) → Submit and wait for HOA sign-off',
            '4. Permitting → Submit permit to AHJ, wait for approval',
            '5. NTP → Notice to Proceed received from utility company',
            '6. MPU Install (if required) → Panel upgrade before solar',
            '7. Solar Installation → Solar panels installed on roof',
            '8. Battery Installation (if included) → Battery storage system installed',
            '9. Meter Spot → Utility company meter spot request',
            '10. Rough Inspection → Mid-construction inspection',
            '11. Final Inspection → Post-installation inspection by AHJ',
            '12. Fire Inspection (if required) → Fire department review',
            '13. PTO Submission → Submits to utility for Permission to Operate',
            '14. PTO Approval → Utility approves, system goes live',
            '15. COC Packet → Certificate of completion sent to customer',
            '',
            '### CRM Modules',
            '- Projects: Core module — each project = one solar installation job',
            '- Customers: The homeowner/client for each project',
            '- Tasks: Work items within a project',
            '- Service Tickets: Support issues, bugs, or service requests',
            '- Follow Ups: Scheduled follow-up actions on a project',
            '- Call Logs: Recorded phone calls related to a project',
            '- Project Design: Technical design details (panel layout, specs)',
            '- Project Acceptance: Sales partner sign-off on project details',
            '- Account Transactions: Financial transactions / remittances',
            '- Ghost / Pre-Inspection Lane: Projects in early pre-inspection stage',
            '',
            '### Project Status Concepts',
            '- "Ghost projects" or "Pre-Inspection Lane" = projects waiting to enter active workflow',
            '- Pending dates (NULL) = that milestone has not happened yet',
            '- Completed dates (not NULL) = that milestone is done',
            '',
            '## Current User',
            "- Name: {$userName}",
            $username ? "- Username: {$username}" : null,
            $rolesStr ? "- Role(s): {$rolesStr}" : null,
            '- Role scope: ' . $this->getRoleDescription($roles),
            '',
            '## This User\'s Data Access',
            $this->getRoleCapabilities($roles),
        ];

        return implode("\n", array_filter($lines));
    }

    private function getRoleCapabilities(array $roles): string
    {
        $isAdmin = ! empty(array_intersect($roles, ['Super Admin', 'Admin']));
        $isFinance = in_array('Finance', $roles);
        $isManager = ! empty(array_intersect($roles, ['Manager', 'Sales Manager', 'Sub-Contractor Manager']));

        if ($isAdmin) {
            return implode("\n", [
                '- Full access: all projects, customers, finance, tickets, users, departments.',
                '- Can view profitability reports, financial transactions, and all employee data.',
                '- Suggested questions: total active projects, profitability report, tickets by department, customer-wise projects.',
            ]);
        }

        if ($isFinance) {
            return implode("\n", [
                '- Access to financial data: customer finances, transactions, loan terms, finance options.',
                '- Access to projects and customer records.',
                '- No access to: profitability reports, admin-only data, other team\'s HR info.',
                '- Suggested questions: customer finance summary, pending payments, project finance details.',
            ]);
        }

        if ($isManager) {
            return implode("\n", [
                '- Access to department projects, team tickets, and employee data within their department.',
                '- Can view project details, task status, and team workload.',
                '- No access to: finance data, other departments, admin-only reports.',
                '- Suggested questions: department projects, team pending tickets, project delays.',
            ]);
        }

        return implode("\n", [
            '- Access limited to own assigned projects and tickets.',
            '- Can view their own tasks, project details, and ticket history.',
            '- No access to other users\' data, finance, or department-wide reports.',
            '- Suggested questions: my assigned projects, my pending tickets, my tasks today.',
        ]);
    }

    private function getRoleDescription(array $roles): string
    {
        if (! empty(array_intersect($roles, ['Super Admin', 'Admin']))) {
            return 'System administrator with full CRM access.';
        }
        if (in_array('Finance', $roles)) {
            return 'Finance team member with access to financial and project data.';
        }
        if (! empty(array_intersect($roles, ['Manager', 'Sales Manager', 'Sub-Contractor Manager']))) {
            return 'Department manager with access to team and project data.';
        }
        if (in_array('Sales Person', $roles)) {
            return 'Sales person with access to their own projects and tasks.';
        }
        if (in_array('Sub-Contractor User', $roles)) {
            return 'Sub-contractor with access to their assigned projects and tickets.';
        }

        return 'CRM user with access to their assigned work.';
    }

    private function extractText(array $response): string
    {
        if (filled(Arr::get($response, 'output_text'))) {
            return Arr::get($response, 'output_text');
        }

        $parts = [];

        foreach (Arr::get($response, 'output', []) as $item) {
            foreach (Arr::get($item, 'content', []) as $content) {
                $text = Arr::get($content, 'text');

                if (filled($text)) {
                    $parts[] = $text;
                }
            }
        }

        return trim(implode("\n", $parts)) ?: 'I could not generate a response. Please try again.';
    }
}
