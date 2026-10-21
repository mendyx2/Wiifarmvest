/** Same rounding as the old jQuery pages: Number.toFixed. */
export const fixed = (value: number, digits = 2) => (Number.isFinite(value) ? value : 0).toFixed(digits);

/** Parse what a user typed in an amount box ("1,000.50" → 1000.5). */
export function parseAmount(input: string): number {
    const value = parseFloat(input.replace(/,/g, ''));
    return Number.isFinite(value) ? value : 0;
}

/**
 * Deposit total. Mirrors deposit.blade.php's calculation() and
 * PaymentController@depositInsert: the fixed charge always applies.
 */
export function depositQuote(amount: number, gate: { percentCharge: number; fixedCharge: number; rate: number }) {
    const charge = (amount ? (amount / 100) * gate.percentCharge : 0) + gate.fixedCharge;
    const total = amount + charge;
    return { charge, total, inCurrency: total * gate.rate };
}

/**
 * Withdraw amount after fees. Mirrors withdraw/methods.blade.php's
 * calculation() and WithdrawController@withdrawStore.
 */
export function withdrawQuote(amount: number, method: { percentCharge: number; fixedCharge: number; rate: number }) {
    const charge = amount ? (amount / 100) * method.percentCharge + method.fixedCharge : 0;
    const receive = amount - charge;
    return { charge, receive, inCurrency: receive * method.rate };
}
