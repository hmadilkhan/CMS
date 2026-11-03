<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Who an account transaction is paid to. The five built-ins are system rows;
 * the rest are added by users from the milestone editor. `payee` columns
 * store the key.
 */
class Payee extends Model
{
    protected $fillable = ['key', 'label', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => AccountTransaction::forgetPayeeOptions());
        static::deleted(fn () => AccountTransaction::forgetPayeeOptions());
    }

    /**
     * The payee called $label, created if there is none yet. Matching is by
     * name (case-insensitive), so adding "others" again returns Others rather
     * than a duplicate.
     */
    public static function findOrCreateByLabel(string $label): self
    {
        $label = trim(preg_replace('/\s+/', ' ', $label));

        $existing = static::whereRaw('LOWER(label) = ?', [Str::lower($label)])->first();

        if ($existing) {
            return $existing;
        }

        $base = Str::slug($label, '_') ?: 'payee';
        $key = $base;

        for ($i = 2; static::where('key', $key)->exists(); $i++) {
            $key = $base.'_'.$i;
        }

        return static::create(['key' => $key, 'label' => $label, 'is_system' => false]);
    }
}
