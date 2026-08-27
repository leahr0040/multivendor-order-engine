import { Head, InfiniteScroll } from '@inertiajs/react';
import { useState } from 'react';
import CartPanel from '@/components/cart-panel';
import QuantityStepper from '@/components/quantity-stepper';
import { useCart } from '@/hooks/use-cart';
import type { Cart } from '@/hooks/use-cart';
import { cn } from '@/lib/utils';
import type {
    CategorySlug,
    ProductData,
    ScrollData,
    UserData,
} from '@/types/api';

const categoryPill: Record<CategorySlug, string> = {
    electronics: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    books: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    home: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
};

const outlineButton =
    'rounded-md border border-[#e3e3e0] px-3 py-2 text-sm font-medium transition-colors hover:border-accent hover:text-accent focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none dark:border-[#3E3E3A]';

interface CatalogProps {
    products: ScrollData<ProductData>;
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
        <article className="flex flex-col rounded-lg border border-[#e3e3e0] bg-white p-4 transition-shadow hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]">
            <p
                className={cn(
                    'self-start rounded-full px-2 py-0.5 text-xs font-medium tracking-wide uppercase',
                    categoryPill[product.category.slug],
                )}
            >
                {product.category.name}
            </p>
            <h2 className="mt-2 font-medium">{product.name}</h2>
            {product.description && (
                <p className="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {product.description}
                </p>
            )}

            <div className="mt-auto flex items-center justify-between gap-3 pt-4">
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
                        className={cn(
                            outlineButton,
                            'text-[#706f6c] dark:text-[#A1A09A]',
                        )}
                    >
                        Remove from cart
                    </button>
                ) : (
                    <button
                        type="button"
                        onClick={() => cart.add(product.ulid, pendingQuantity)}
                        className={outlineButton}
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

            <div className="min-h-screen bg-gradient-to-b from-[#FDFDFC] to-violet-50/60 p-6 text-[#1b1b18] dark:from-[#0a0a0a] dark:to-violet-950/20 dark:text-[#EDEDEC]">
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
                            className="rounded-md border border-[#e3e3e0] bg-white px-2 py-1 transition-colors hover:border-accent focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]"
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

                <InfiniteScroll
                    data="products"
                    as="main"
                    className="mx-auto mt-6 grid max-w-5xl gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    loading={
                        <p className="py-6 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Loading more products…
                        </p>
                    }
                >
                    {products.data.map((product) => (
                        <ProductCard
                            key={product.ulid}
                            product={product}
                            cart={cart}
                        />
                    ))}
                </InfiniteScroll>
            </div>

            <CartPanel cart={cart} userUlid={userUlid} />
        </>
    );
}
