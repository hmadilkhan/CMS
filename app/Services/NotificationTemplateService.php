<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the editable copy for the CRM's automatic emails.
 *
 * A template lives in config/notification_templates.php as the default, and an
 * admin edit is stored in the `notification_templates` table. Rendering always
 * falls back to the config default, so an email still goes out correctly if
 * the row is missing, deactivated, or was never created.
 */
class NotificationTemplateService
{
    /**
     * Every registered template, keyed by template key, with any stored
     * override merged in.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $overrides = NotificationTemplate::all()->keyBy('key');

        return collect(config('notification_templates', []))
            ->map(function (array $template, string $key) use ($overrides) {
                $override = $overrides->get($key);

                return array_merge($template, [
                    'key' => $key,
                    'subject' => $override->subject ?? $template['subject'],
                    'body' => $override->body ?? $template['body'],
                    'is_customised' => (bool) $override,
                    'is_active' => $override ? (bool) $override->is_active : true,
                    'updated_at' => $override->updated_at ?? null,
                    'updated_by' => $override?->editor?->name,
                ]);
            })
            ->all();
    }

    /**
     * Templates a user can switch on or off from their profile - the ones that
     * go to one specific user (`per_user` in config), grouped for display.
     * Read from the config alone: names and groups are not editable.
     *
     * @return array<string, array<string, array{name: string, description: string}>>
     */
    public function userSwitchable(): array
    {
        return collect(config('notification_templates', []))
            ->filter(fn (array $template) => $template['per_user'] ?? false)
            ->map(fn (array $template) => [
                'group' => $template['group'] ?? 'Other',
                'name' => $template['name'],
                'description' => $this->firstSentence($template['description'] ?? ''),
            ])
            ->groupBy('group', true)
            ->map(fn ($group) => $group->map(fn ($template) => [
                'name' => $template['name'],
                'description' => $template['description'],
            ])->all())
            ->all();
    }

    /** The editor descriptions run on into placeholder notes; a profile needs only the first sentence. */
    private function firstSentence(string $text): string
    {
        return preg_split('/(?<=\.)\s/', trim($text), 2)[0];
    }

    /** Keys of the templates a user can switch. */
    public static function userSwitchableKeys(): array
    {
        return array_keys(array_filter(
            config('notification_templates', []),
            fn (array $template) => $template['per_user'] ?? false
        ));
    }

    /**
     * One registered template, or null when the key is not registered.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Placeholder names a template is allowed to use.
     *
     * @return array<int, string>
     */
    public function placeholderNames(string $key): array
    {
        return array_keys(config("notification_templates.{$key}.placeholders", []));
    }

    /**
     * Placeholders used in the given text that the template does not support.
     * Used to reject a typo before it can reach a customer.
     *
     * @return array<int, string>
     */
    public function unknownPlaceholders(string $key, string ...$texts): array
    {
        $allowed = $this->placeholderNames($key);
        $used = [];

        foreach ($texts as $text) {
            preg_match_all('/\{([a-z0-9_]+)\}/i', (string) $text, $matches);
            $used = array_merge($used, $matches[1]);
        }

        return array_values(array_unique(array_diff($used, $allowed)));
    }

    /**
     * Fill a template with real values.
     *
     * Missing values render as an empty string rather than leaving a raw
     * {placeholder} in a customer-visible email.
     *
     * @param  array<string, mixed>  $data
     * @return array{subject: string, body: string}
     */
    public function render(string $key, array $data): array
    {
        $template = $this->find($key);

        if (! $template) {
            Log::warning('Unknown email template requested.', ['key' => $key]);

            return ['subject' => '', 'body' => ''];
        }

        // A deactivated template falls back to the shipped default rather than
        // sending an empty email.
        if (! $template['is_active']) {
            $template['subject'] = config("notification_templates.{$key}.subject");
            $template['body'] = config("notification_templates.{$key}.body");
        }

        $replacements = [];
        foreach ($this->placeholderNames($key) as $name) {
            $replacements['{'.$name.'}'] = (string) ($data[$name] ?? '');
        }

        return [
            'subject' => strip_tags(strtr($template['subject'], $replacements)),
            'body' => strtr($template['body'], $replacements),
        ];
    }
}
