<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceRedemption;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class MarketplaceService
{
    public function __construct(private WalletService $wallet)
    {
    }

    /**
     * Redeem an item for a student. Debits the wallet and decrements stock
     * atomically; rolls back entirely if either step can't be satisfied.
     *
     * @return array{success: bool, message: string}
     */
    public function redeem(int $studentId, int $itemId): array
    {
        return DB::transaction(function () use ($studentId, $itemId) {
            $item = MarketplaceItem::lockForUpdate()->find($itemId);

            if (! $item || ! $item->is_active) {
                return ['success' => false, 'message' => 'This item is not available.'];
            }

            if (! $item->isInStock()) {
                return ['success' => false, 'message' => 'This item is out of stock.'];
            }

            if (! $this->wallet->redeem($studentId, $item->cost)) {
                return ['success' => false, 'message' => 'Not enough Ninja Coins for this item.'];
            }

            if ($item->stock !== null) {
                $decremented = MarketplaceItem::where('id', $item->id)
                    ->where('stock', '>', 0)
                    ->decrement('stock');

                if (! $decremented) {
                    // Lost the race for the last unit — refund and bail.
                    $this->wallet->credit($studentId, $item->cost);

                    return ['success' => false, 'message' => 'This item just sold out.'];
                }
            }

            MarketplaceRedemption::create([
                'student_id'          => $studentId,
                'marketplace_item_id' => $item->id,
                'item_name'           => $item->name,
                'cost_paid'           => $item->cost,
                'status'              => 'pending',
                'redeemed_at'         => now(),
            ]);

            return ['success' => true, 'message' => "Redeemed \"{$item->name}\" for {$item->cost} Ninja Coins."];
        });
    }
}
