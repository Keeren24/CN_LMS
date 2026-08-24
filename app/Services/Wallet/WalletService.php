<?php

namespace App\Services\Wallet;

use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Ninja Coins: the spendable half of a student's points.
 *
 * class_performances.points is the lifetime achievement total and is never
 * reduced. wallet_balance starts as a copy of that total and then diverges:
 * it grows with every future point award and shrinks only through
 * marketplace redemptions.
 */
class WalletService
{
    /** Lifetime class points — the immutable achievement total. */
    public function lifetimePoints(int $studentId): int
    {
        return (int) DB::table('class_performances')
            ->where('student_id', $studentId)
            ->sum('points');
    }

    /** Credit (or, for a tutor correction, debit) the wallet by a points delta. */
    public function credit(int $studentId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        Student::where('id', $studentId)->increment('wallet_balance', $delta);
    }

    /**
     * Atomically deduct a redemption cost. Returns false without changing
     * anything if the balance is insufficient — never lets wallet_balance go
     * negative, even under concurrent redemptions.
     */
    public function redeem(int $studentId, int $cost): bool
    {
        if ($cost <= 0) {
            return false;
        }

        $affected = Student::where('id', $studentId)
            ->where('wallet_balance', '>=', $cost)
            ->decrement('wallet_balance', $cost);

        return $affected > 0;
    }
}
