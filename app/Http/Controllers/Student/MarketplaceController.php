<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceRedemption;
use App\Models\Student;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    public function __construct(
        private MarketplaceService $marketplace,
        private WalletService $wallet
    ) {
    }

    public function index()
    {
        $studentId = Auth::user()->student_id;

        $items = MarketplaceItem::where('is_active', true)
            ->orderBy('cost')
            ->get();

        $walletBalance = $studentId ? (int) Student::where('id', $studentId)->value('wallet_balance') : 0;

        $history = $studentId
            ? MarketplaceRedemption::where('student_id', $studentId)
                ->orderByDesc('redeemed_at')
                ->get()
            : collect();

        return view('student.marketplace', [
            'items'         => $items,
            'walletBalance' => $walletBalance,
            'history'       => $history,
        ]);
    }

    public function redeem(Request $request, int $item)
    {
        $studentId = Auth::user()->student_id;

        if (! $studentId) {
            return response()->json(['success' => false, 'message' => 'No student profile linked to this account.'], 422);
        }

        $result = $this->marketplace->redeem($studentId, $item);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
