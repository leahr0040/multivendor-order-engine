import { router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';
import QuantityStepper from '@/components/quantity-stepper';
import { cartPayload } from '@/hooks/use-cart';
import type { Cart } from '@/hooks/use-cart';
import { useQuote } from '@/hooks/use-quote';
import { validationError } from '@/lib/errors';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import { store } from '@/routes/api/orders';
import { show } from '@/routes/orders';
import type { OrderData, ValidationErrorResponse } from '@/types/api';

interface CartPanelProps {
    cart: Cart;
    userUlid: string | null;
}

function ErrorMessages({ error }: { error: ValidationErrorResponse }) {
    const messages = Object.values(error.errors).flat();

    return (
        <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
            {messages.length > 0 ? (
                messages.map((message) => <p key={message}>{message}</p>)
            ) : (
                <p>{error.message}</p>
            )}
        </div>
    );
}

export default function CartPanel({ cart, userUlid }: CartPanelProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitError, setSubmitError] =
        useState<ValidationErrorResponse | null>(null);
    const { pricedCart, isLoading, error } = useQuote(
        cart.items,
        userUlid,
        isOpen,
    );

    async function submit() {
        setIsSubmitting(true);
        setSubmitError(null);

        try {
            const { data: order } = await axios.post<OrderData>(store.url(), {
                ...cartPayload(cart.items, userUlid),
                idempotency_key: cart.idempotencyKey,
            });

            cart.clear();
            router.visit(show(order.ulid).url);
        } catch (failure) {
            setSubmitError(validationError(failure));
            setIsSubmitting(false);
        }
    }

    if (!isOpen) {
        return (
            <button
                type="button"
                onClick={() => setIsOpen(true)}
                disabled={cart.items.length === 0}
                className="fixed right-6 bottom-6 rounded-full bg-[#1b1b18] px-5 py-3 text-sm font-medium text-white shadow-lg disabled:opacity-40 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
            >
                Review cart ({cart.items.length})
            </button>
        );
    }

    return (
        <aside className="fixed inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-[#e3e3e0] bg-white shadow-xl dark:border-[#3E3E3A] dark:bg-[#161615]">
            <header className="flex items-center justify-between border-b border-[#e3e3e0] p-4 dark:border-[#3E3E3A]">
                <h2 className="font-medium">Your cart</h2>
                <button
                    type="button"
                    onClick={() => setIsOpen(false)}
                    className="text-sm text-[#706f6c] underline underline-offset-4 dark:text-[#A1A09A]"
                >
                    Close
                </button>
            </header>

            <div className="flex-1 space-y-3 overflow-y-auto p-4">
                {error && <ErrorMessages error={error} />}

                {pricedCart?.lines.map((line) => (
                    <article
                        key={line.product_ulid}
                        className="border-b border-[#e3e3e0] pb-3 last:border-b-0 dark:border-[#3E3E3A]"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="font-medium">
                                    {line.product_name}
                                </p>
                                <p className="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    {line.vendor_name} ·{' '}
                                    {formatMoney(line.original_unit_price)} each
                                </p>
                            </div>
                            <p
                                className={cn(
                                    'text-sm',
                                    line.discount > 0 &&
                                        'text-[#706f6c] line-through dark:text-[#A1A09A]',
                                )}
                            >
                                {formatMoney(line.original_price)}
                            </p>
                        </div>

                        <div className="mt-2 flex items-center gap-2">
                            <QuantityStepper
                                quantity={line.quantity}
                                onChange={(quantity) =>
                                    cart.setQuantity(
                                        line.product_ulid,
                                        quantity,
                                    )
                                }
                            />
                            <button
                                type="button"
                                onClick={() => cart.remove(line.product_ulid)}
                                className="ml-auto text-xs text-[#706f6c] underline underline-offset-4 dark:text-[#A1A09A]"
                            >
                                Remove
                            </button>
                        </div>

                        {line.discount > 0 && (
                            <>
                                <div className="mt-2 flex items-baseline justify-between gap-3 text-sm text-emerald-700 dark:text-emerald-400">
                                    <span className="text-xs">
                                        {line.applied_rules.join(' · ')}
                                    </span>
                                    <span>−{formatMoney(line.discount)}</span>
                                </div>
                                <p className="mt-1 text-right font-medium">
                                    {formatMoney(line.final_price)}
                                </p>
                            </>
                        )}
                    </article>
                ))}
            </div>

            <footer className="space-y-3 border-t border-[#e3e3e0] p-4 dark:border-[#3E3E3A]">
                <dl className="space-y-1 text-sm">
                    {pricedCart && pricedCart.discount > 0 && (
                        <>
                            <div className="flex justify-between">
                                <dt className="text-[#706f6c] dark:text-[#A1A09A]">
                                    Subtotal
                                </dt>
                                <dd>
                                    {formatMoney(pricedCart.original_price)}
                                </dd>
                            </div>
                            <div className="flex justify-between text-emerald-700 dark:text-emerald-400">
                                <dt>Discount</dt>
                                <dd>−{formatMoney(pricedCart.discount)}</dd>
                            </div>
                        </>
                    )}
                    <div className="flex justify-between text-base font-medium">
                        <dt>Total</dt>
                        <dd>{formatMoney(pricedCart?.final_price ?? 0)}</dd>
                    </div>
                </dl>

                {submitError && <ErrorMessages error={submitError} />}

                <button
                    type="button"
                    onClick={submit}
                    disabled={!pricedCart || isLoading || isSubmitting}
                    className="w-full rounded-md bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-40 dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                >
                    {isSubmitting ? 'Placing order…' : 'Place order'}
                </button>
            </footer>
        </aside>
    );
}
