<?php

namespace App\Support;

/**
 * Keyword / phrase search over a user's database notifications.
 *
 * A notification's text lives in its `data` JSON, so the search reads the
 * text fields out of it rather than LIKE-ing the raw JSON - that would match
 * key names ("url", "note") on every row, and miss values json_encode escaped
 * (a "/" is stored as "\/"). The phrase is matched as typed, case-insensitive.
 */
class NotificationSearch
{
    /** The `data` keys that hold text a person would search for. */
    public const FIELDS = [
        'note',
        'message',
        'subject',
        'comment',
        'mentioned_by',
        'assigned_by',
        'commenter',
        'resolved_by',
        'project_name',
        'email_subject',
        'email_from',
        'customer_address',
    ];

    /** The search term trimmed, or null when there is nothing to search for. */
    public static function term(?string $value): ?string
    {
        $term = trim((string) $value);

        return $term === '' ? null : $term;
    }

    /**
     * Limit the query to notifications whose text contains the phrase. A blank
     * term leaves the query untouched.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation  $query
     */
    public static function apply($query, ?string $value)
    {
        $term = self::term($value);

        if ($term === null) {
            return $query;
        }

        // JSON_UNQUOTE returns a binary-collated string, so LIKE on it would be
        // case-sensitive: lower both sides instead.
        $like = '%' . addcslashes(mb_strtolower($term), '%_\\') . '%';

        return $query->where(function ($query) use ($like) {
            foreach (self::FIELDS as $field) {
                $query->orWhereRaw(
                    sprintf("LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, '$.\"%s\"'))) LIKE ?", $field),
                    [$like]
                );
            }
        });
    }
}
