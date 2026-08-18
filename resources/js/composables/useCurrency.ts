const currencySymbols: Record<string, string> = {
    NGN: '₦',
    USD: '$',
    GBP: '£',
    EUR: '€',
};

export function useCurrency() {
    function formatCurrency(
        amount: number | string,
        currencyCode: string = 'NGN',
    ): string {
        const value = typeof amount === 'string' ? parseFloat(amount) : amount;
        const symbol = currencySymbols[currencyCode] ?? `${currencyCode} `;
        const formatted = new Intl.NumberFormat('en-NG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }).format(Number.isFinite(value) ? value : 0);

        return `${symbol}${formatted}`;
    }

    return { formatCurrency };
}
