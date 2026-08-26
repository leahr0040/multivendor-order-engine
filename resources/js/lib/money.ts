const formatter = new Intl.NumberFormat('he-IL', {
    style: 'currency',
    currency: 'ILS',
});

export function formatMoney(agorot: number): string {
    return formatter.format(agorot / 100);
}
