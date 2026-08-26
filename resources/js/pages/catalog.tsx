import { Head } from '@inertiajs/react';
import { useState } from 'react';
import CartPanel from '@/components/cart-panel';
import QuantityStepper from '@/components/quantity-stepper';
import { useCart } from '@/hooks/use-cart';
import type { Cart } from '@/hooks/use-cart';
import type { ProductData, UserData } from '@/types/api';

interface CatalogProps {
    products: ProductData[];
    users: UserData[];
}

interface ProductCardProps {
    product: ProductData;
    cart: Cart;
}

function ProductCard({ product, cart }: ProductCardProps) {
    const [pendingQuantity, setPendingQuantity] = useState(1);
    const cartItem = cart.items.find(
        (item) => item.productUlid === product.ulid,
    );

    return (
        <article className="flex flex-col rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
            <p className="text-xs tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]">
                {product.category.name}
            </p>
            <h2 className="mt-1 font-medium">{product.name}</h2>
            {product.description && (
                <p className="mt-1 flex-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {product.description}
                </p>
            )}

            <div className="mt-4 flex items-center justify-between gap-3">
                <QuantityStepper
                    quantity={cartItem?.quantity ?? pendingQuantity}
                    onChange={(nextQuantity) =>
                        cartItem
                            ? cart.setQuantity(product.ulid, nextQuantity)
                            : setPendingQuantity(Math.max(1, nextQuantity))
                    }
                />
                {cartItem ? (
                    <button
                        type="button"
                        onClick={() => cart.remove(product.ulid)}
                        className="rounded-md border border-[#e3e3e0] px-3 py-2 text-sm font-medium text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                    >
                        Remove from cart
                    </button>
                ) : (
                    <button
                        type="button"
                        onClick={() => cart.add(product.ulid, pendingQuantity)}
                        className="rounded-md border border-[#e3e3e0] px-3 py-2 text-sm font-medium dark:border-[#3E3E3A]"
                    >
                        Add to cart
                    </button>
                )}
            </div>
        </article>
    );
}

export default function Catalog({ products, users }: CatalogProps) {
    const cart = useCart();
    const [userUlid, setUserUlid] = useState<string | null>(null);

    return (
        <>
            <Head title="Catalog" />

            <div className="min-h-screen bg-[#FDFDFC] p-6 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4">
                    <h1 className="text-lg font-medium">Catalog</h1>

                    <label className="flex items-center gap-2 text-sm">
                        <span className="text-[#706f6c] dark:text-[#A1A09A]">
                            Shopping as
                        </span>
                        <select
                            value={userUlid ?? ''}
                            onChange={(event) =>
                                setUserUlid(event.target.value || null)
                            }
                            className="rounded-md border border-[#e3e3e0] bg-white px-2 py-1 dark:border-[#3E3E3A] dark:bg-[#161615]"
                        >
                            <option value="">Guest</option>
                            {users.map((user) => (
                                <option key={user.ulid} value={user.ulid}>
                                    {user.name} — {user.loyalty_tier}
                                </option>
                            ))}
                        </select>
                    </label>
                </header>

                <main className="mx-auto mt-6 grid max-w-5xl gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {products.map((product) => (
                        <ProductCard
                            key={product.ulid}
                            product={product}
                            cart={cart}
                        />
                    ))}
                </main>
            </div>

            <CartPanel cart={cart} userUlid={userUlid} />
        </>
    );
}
