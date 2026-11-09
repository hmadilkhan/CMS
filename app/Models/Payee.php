<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Who an account transaction is paid to. The five built-ins are system rows;
 * the rest are added by users, from Operations -> Payees or the milestone
 * editor. `payee` columns store the key, which never changes - renaming a payee
 * changes only its label.
 *
 * Built-ins are locked (the commission and labor payables are keyed on them).
 * A user-added payee can be renamed, switched off (left out of the pickers but
 * still labelling the rows that use it) and, while nothing uses it, deleted.
 */
class Payee extends Model
{
    protected $fillable = ['key', 'label', 'is_system', 'is_active'];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saved(fn () => AccountTransaction::forgetPayeeOptions());
        static::deleted(fn () => AccountTransaction::forgetPayeeOptions());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function normaliseLabel(string $label): string
    {
        return trim(preg_replace('/\s+/', ' ', $label));
    }

    /** Another payee already called $label (case-insensitive), if any. */
    public static function labelTaken(string $label, ?int $exceptId = null): bool
    {
        return static::whereRaw('LOWER(label) = ?', [Str::lower(static::normaliseLabel($label))])
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();
    }

    /**
     * The payee called $label, created if there is none yet. Matching is by
     * name (case-insensitive), so adding "others" again returns Others rather
     * than a duplicate. A matching payee that was switched off is switched back
     * on, since someone has just asked for it.
     */
    public static function findOrCreateByLabel(string $label): self
    {
        $label = static::normaliseLabel($label);

        $existing = static::whereRaw('LOWER(label) = ?', [Str::lower($label)])->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return $existing;
        }

        $base = Str::slug($label, '_') ?: 'payee';
        $key = $base;

        for ($i = 2; static::where('key', $key)->exists(); $i++) {
            $key = $base.'_'.$i;
        }

        return static::create(['key' => $key, 'label' => $label, 'is_system' => false]);
    }

    /** Account transactions and milestone rows that store this payee, deleted ones included. */
    public function usage(): array
    {
        return [
            'transactions' => AccountTransaction::withTrashed()->where('payee', $this->key)->count(),
            'milestones' => FinanceOptionMilestone::withTrashed()->where('payee', $this->key)->count(),
        ];
    }

    public function isInUse(): bool
    {
        return array_sum($this->usage()) > 0;
    }
}
