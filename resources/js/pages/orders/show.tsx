import { Head, Link } from '@inertiajs/react';
import { formatMoney } from '@/lib/money';
import { catalog } from '@/routes';
import type { OrderData } from '@/types/api';

interface OrderShowProps {
    order: OrderData;
}

type MoneyTotals = Pick<
    OrderData,
    'original_price' | 'discount' | 'final_price'
>;

function Totals({ totals }: { totals: MoneyTotals }) {
    return (
        <dl className="flex flex-wrap gap-x-6 gap-y-1 text-sm">
            {totals.discount > 0 && (
                <>
                    <div className="flex gap-2">
                        <dt className="text-[#706f6c] dark:text-[#A1A09A]">
                            Subtotal
                        </dt>
                        <dd>{formatMoney(totals.original_price)}</dd>
                    </div>
                    <div className="flex gap-2 text-emerald-700 dark:text-emerald-400">
                        <dt>Discount</dt>
                        <dd>−{formatMoney(totals.discount)}</dd>
                    </div>
                </>
            )}
            <div className="flex gap-2 font-medium">
                <dt>Total</dt>
                <dd>{formatMoney(totals.final_price)}</dd>
            </div>
        </dl>
    );
}

export default function OrderShow({ order }: OrderShowProps) {
    return (
        <>
            <Head title="Order" />

            <div className="min-h-screen bg-[#FDFDFC] p-6 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <div className="mx-auto max-w-3xl space-y-6">
                    <header className="space-y-2">
                        <Link
                            href={catalog().url}
                            className="text-sm text-[#706f6c] underline underline-offset-4 dark:text-[#A1A09A]"
                        >
                            ← Back to catalog
                        </Link>
                        <h1 className="text-lg font-medium">
                            Order {order.ulid}
                        </h1>
                        {order.created_at && (
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                {new Date(order.created_at).toLocaleString(
                                    'he-IL',
                                )}
                            </p>
                        )}
                        <Totals totals={order} />
                    </header>

                    {order.sub_orders.map((subOrder) => (
                        <section
                            key={subOrder.ulid}
                            className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]"
                        >
                            <h2 className="font-medium">
                                {subOrder.vendor.name}
                            </h2>

                            <ul className="mt-3 divide-y divide-[#e3e3e0] text-sm dark:divide-[#3E3E3A]">
                                {subOrder.order_items.map((orderItem) => (
                                    <li
                                        key={orderItem.product.ulid}
                                        className="flex flex-wrap items-baseline justify-between gap-2 py-2"
                                    >
                                        <span>
                                            {orderItem.product.name}
                                            <span className="text-[#706f6c] dark:text-[#A1A09A]">
                                                {' '}
                                                × {orderItem.quantity} @{' '}
                                                {formatMoney(
                                                    orderItem.original_unit_price,
                                                )}
                                            </span>
                                        </span>
                                        <span className="flex gap-3">
                                            {orderItem.discount > 0 && (
                                                <span className="text-emerald-700 dark:text-emerald-400">
                                                    −
                                                    {formatMoney(
                                                        orderItem.discount,
                                                    )}
                                                </span>
                                            )}
                                            <span className="font-medium">
                                                {formatMoney(
                                                    orderItem.final_price,
                                                )}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ul>

                            <div className="mt-3 border-t border-[#e3e3e0] pt-3 dark:border-[#3E3E3A]">
                                <Totals totals={subOrder} />
                            </div>
                        </section>
                    ))}
                </div>
            </div>
        </>
    );
}
