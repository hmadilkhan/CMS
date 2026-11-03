<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

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

    /**
     * The text, escaped, with each occurrence of the term wrapped in a <mark>
     * so a result shows why it matched. The raw text is split on the term and
     * each piece escaped on its own - marking up already-escaped text would let
     * a term like "amp" land inside an "&amp;" entity.
     */
    public static function highlight(?string $text, ?string $value): HtmlString
    {
        $text = (string) $text;
        $term = self::term($value);

        $parts = $term === null || $text === ''
            ? false
            : preg_split('/(' . preg_quote($term, '/') . ')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        // No term, or text that is not valid UTF-8: show it plain.
        if ($parts === false) {
            return new HtmlString(e($text));
        }

        $html = '';
        foreach ($parts as $i => $part) {
            // With DELIM_CAPTURE the matches sit at the odd indexes.
            $html .= $i % 2 ? '<mark class="search-hit">' . e($part) . '</mark>' : e($part);
        }

        return new HtmlString($html);
    }
}
