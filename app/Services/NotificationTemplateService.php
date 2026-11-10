<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
                    'tag_groups' => $this->tagGroups($key),
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
     * Placeholder names a template is allowed to use: its own (the values the
     * sending code passes) plus every tag of the records it carries.
     *
     * @return array<int, string>
     */
    public function placeholderNames(string $key): array
    {
        $names = array_keys(config("notification_templates.{$key}.placeholders", []));

        foreach ($this->recordTags($key) as $tags) {
            $names = array_merge($names, array_keys($tags));
        }

        return array_values(array_unique($names));
    }

    /**
     * The tags the editor offers, grouped: "This email" first (the template's
     * own placeholders), then one group per record it carries. A record tag
     * the template already supplies itself is listed once, under "This email".
     *
     * @return array<int, array{label: string, tags: array<string, string>}>
     */
    public function tagGroups(string $key): array
    {
        $own = config("notification_templates.{$key}.placeholders", []);
        $groups = [['label' => 'This email', 'tags' => $own]];

        foreach ($this->recordTags($key) as $record => $tags) {
            $tags = array_diff_key($tags, $own);

            if ($tags) {
                $groups[] = [
                    'label' => config("notification_tags.{$record}.label", ucfirst($record)),
                    'tags' => array_map(fn (array $tag) => $tag['label'], $tags),
                ];
            }
        }

        return $groups;
    }

    /**
     * Tag definitions of each record a template carries, keyed by record.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function recordTags(string $key): array
    {
        $tags = [];

        foreach (config("notification_templates.{$key}.records", []) as $record) {
            $tags[$record] = config("notification_tags.{$record}.tags", []);
        }

        return $tags;
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
     * $data holds the template's own placeholders. $records holds the models
     * the email is about (['project' => $project], ['ticket' => $ticket]);
     * record tags are read from them, and a record the caller did not pass is
     * reached through config('notification_tags.*.from') - a ticket's project,
     * a project's customer. Only the tags the template actually uses are read,
     * so an unused tag never costs a query.
     *
     * A value the sender passed in $data wins over a record tag of the same
     * name. Missing values render as an empty string rather than leaving a raw
     * {placeholder} in a customer-visible email.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, \Illuminate\Database\Eloquent\Model|null>  $records
     * @return array{subject: string, body: string}
     */
    public function render(string $key, array $data, array $records = []): array
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

        $own = array_keys(config("notification_templates.{$key}.placeholders", []));
        $used = $this->usedPlaceholders($template['subject'].' '.$template['body']);

        // Record values come from the database (names, notes typed by users),
        // so the body gets them HTML-escaped; the subject is plain text.
        $subjectValues = [];
        $bodyValues = [];

        foreach ($this->placeholderNames($key) as $name) {
            if (in_array($name, $own, true) && array_key_exists($name, $data)) {
                $subjectValues['{'.$name.'}'] = $bodyValues['{'.$name.'}'] = (string) ($data[$name] ?? '');

                continue;
            }

            $value = in_array($name, $used, true) ? $this->recordTagValue($key, $name, $records) : '';
            $subjectValues['{'.$name.'}'] = $value;
            $bodyValues['{'.$name.'}'] = e($value);
        }

        return [
            'subject' => strip_tags(strtr($template['subject'], $subjectValues)),
            'body' => strtr($template['body'], $bodyValues),
        ];
    }

    /** @return array<int, string> */
    private function usedPlaceholders(string $text): array
    {
        preg_match_all('/\{([a-z0-9_]+)\}/i', $text, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * The value of one record tag, or '' when the record is not reachable.
     *
     * @param  array<string, mixed>  $records
     */
    private function recordTagValue(string $key, string $name, array &$records): string
    {
        foreach ($this->recordTags($key) as $record => $tags) {
            if (! isset($tags[$name])) {
                continue;
            }

            $model = $this->resolveRecord($record, $records);

            return $model ? $this->readTag($model, $tags[$name]) : '';
        }

        return '';
    }

    /**
     * The model for a record, from $records or reached from another record
     * through its 'from' relation. Resolved models are kept in $records so a
     * customer reached through a ticket's project is loaded once.
     *
     * @param  array<string, mixed>  $records
     * @param  array<int, string>  $visiting
     */
    private function resolveRecord(string $record, array &$records, array $visiting = []): ?Model
    {
        if (array_key_exists($record, $records)) {
            return $records[$record] instanceof Model ? $records[$record] : null;
        }

        $visiting[] = $record;
        $model = null;

        foreach (config("notification_tags.{$record}.from", []) as $source => $relation) {
            if (in_array($source, $visiting, true)) {
                continue;
            }

            $parent = $this->resolveRecord($source, $records, $visiting);
            $related = $parent?->{$relation};

            if ($related instanceof Model) {
                $model = $related;
                break;
            }
        }

        return $records[$record] = $model;
    }

    /** @param  array<string, mixed>  $tag */
    private function readTag(Model $model, array $tag): string
    {
        try {
            if (isset($tag['route'])) {
                return route($tag['route'], $model->getKey());
            }

            if (isset($tag['url'])) {
                return url(str_replace('{id}', (string) $model->getKey(), $tag['url']));
            }

            $paths = (array) ($tag['path'] ?? []);
            $values = array_map(fn (string $path) => $this->scalar(data_get($model, $path)), $paths);
            $value = trim(implode(' ', array_filter($values, fn (string $v) => $v !== '')));

            if ($value !== '' && ($tag['format'] ?? null) === 'date') {
                return Carbon::parse($value)->format('M d, Y');
            }

            return $value;
        } catch (\Throwable $e) {
            // One bad tag (an unparseable date, a missing route) must not stop the email.
            Log::warning('Email template tag could not be read.', ['tag' => $tag, 'error' => $e->getMessage()]);

            return '';
        }
    }

    private function scalar(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
