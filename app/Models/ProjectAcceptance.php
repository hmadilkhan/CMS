<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAcceptance extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'inverter_base_price' => 'decimal:2',
        'dealer_fee_amount' => 'decimal:2',
        'module_qty_price' => 'decimal:2',
        'modules_amount' => 'decimal:2',
        'contract_amount' => 'decimal:2',
        'redline_costs' => 'decimal:2',
        'adders_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'adders_list' => 'array',
        'adders_detail' => 'array',
    ];

    /**
     * The adders of a deal as an acceptance snapshots them: `adders_list` keeps
     * the names (what it always held, and what the assistant reads) and
     * `adders_detail` the name + amount of each, in the deal's order. An adder
     * whose type is gone is left out of both, as the names list always did.
     *
     * @param  iterable<\App\Models\CustomerAdder>  $adders
     */
    public static function adderSnapshot(iterable $adders): array
    {
        $list = [];
        $detail = [];

        foreach ($adders as $adder) {
            if (empty($adder->type)) {
                continue;
            }
            $list[] = $adder->type->name;
            $detail[] = ['name' => $adder->type->name, 'amount' => round((float) $adder->amount, 2)];
        }

        return ['adders_list' => $list, 'adders_detail' => $detail];
    }

    /** "Tesla Expansion Battery ($8,250.00)"; a credit reads "(-$553.00)". */
    public static function adderLabel(string $name, float $amount): string
    {
        return $name.' ('.($amount < 0 ? '-' : '').'$'.number_format(abs($amount), 2).')';
    }

    /**
     * What the acceptance page lists after "Adders :". Each adder with its
     * price when the snapshot has them; otherwise just the names - acceptances
     * whose prices could not be recovered (see migration 2026_11_24_000003).
     */
    public function addersForDisplay(): array
    {
        if (! empty($this->adders_detail)) {
            return array_map(
                fn ($adder) => self::adderLabel((string) ($adder['name'] ?? ''), (float) ($adder['amount'] ?? 0)),
                $this->adders_detail
            );
        }

        return array_values((array) $this->adders_list);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "action_by", "id");
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
