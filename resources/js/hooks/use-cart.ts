import { useReducer } from 'react';
import { ulid } from 'ulid';

export interface CartItem {
    productUlid: string;
    quantity: number;
}

interface CartState {
    items: CartItem[];
    idempotencyKey: string | null;
}

type CartAction =
    | { type: 'add'; productUlid: string; quantity: number }
    | { type: 'setQuantity'; productUlid: string; quantity: number }
    | { type: 'remove'; productUlid: string }
    | { type: 'clear' };

export interface Cart extends CartState {
    add(productUlid: string, quantity: number): void;
    setQuantity(productUlid: string, quantity: number): void;
    remove(productUlid: string): void;
    clear(): void;
}

const EMPTY_CART: CartState = { items: [], idempotencyKey: null };

export function cartPayload(items: CartItem[], userUlid: string | null) {
    return {
        items: items.map((item) => ({
            product: item.productUlid,
            quantity: item.quantity,
        })),
        user: userUlid,
    };
}

const quantityOf = (items: CartItem[], productUlid: string) =>
    items.find((item) => item.productUlid === productUlid)?.quantity ?? 0;

function upsert(
    items: CartItem[],
    productUlid: string,
    quantity: number,
): CartItem[] {
    return items.some((item) => item.productUlid === productUlid)
        ? items.map((item) =>
              item.productUlid === productUlid ? { ...item, quantity } : item,
          )
        : [...items, { productUlid, quantity }];
}

function itemsReducer(items: CartItem[], action: CartAction): CartItem[] {
    switch (action.type) {
        case 'add':
            return upsert(
                items,
                action.productUlid,
                quantityOf(items, action.productUlid) + action.quantity,
            );

        case 'setQuantity':
            return action.quantity < 1
                ? itemsReducer(items, {
                      type: 'remove',
                      productUlid: action.productUlid,
                  })
                : upsert(items, action.productUlid, action.quantity);

        case 'remove':
            return items.filter(
                (item) => item.productUlid !== action.productUlid,
            );

        case 'clear':
            return [];
    }
}

// a retry must not become a second order
function cartReducer(state: CartState, action: CartAction): CartState {
    const items = itemsReducer(state.items, action);

    return {
        items,
        idempotencyKey:
            items.length === 0 ? null : (state.idempotencyKey ?? ulid()),
    };
}

export function useCart(): Cart {
    const [state, dispatch] = useReducer(cartReducer, EMPTY_CART);

    return {
        ...state,
        add: (productUlid, quantity) =>
            dispatch({ type: 'add', productUlid, quantity }),
        setQuantity: (productUlid, quantity) =>
            dispatch({ type: 'setQuantity', productUlid, quantity }),
        remove: (productUlid) => dispatch({ type: 'remove', productUlid }),
        clear: () => dispatch({ type: 'clear' }),
    };
}
