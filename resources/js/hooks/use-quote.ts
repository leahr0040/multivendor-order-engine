import axios from 'axios';
import { useEffect, useState } from 'react';
import { cartPayload } from '@/hooks/use-cart';
import type { CartItem } from '@/hooks/use-cart';
import { validationError } from '@/lib/errors';
import { quote } from '@/routes/api/cart';
import type { PricedCartData, ValidationErrorResponse } from '@/types/api';

export interface Quote {
    pricedCart: PricedCartData | null;
    isLoading: boolean;
    error: ValidationErrorResponse | null;
}

interface QuoteResult {
    quotedItems: CartItem[];
    quotedUserUlid: string | null;
    pricedCart: PricedCartData | null;
    error: ValidationErrorResponse | null;
}

export function useQuote(
    items: CartItem[],
    userUlid: string | null,
    enabled: boolean,
): Quote {
    const [result, setResult] = useState<QuoteResult | null>(null);
    const shouldQuote = enabled && items.length > 0;

    useEffect(() => {
        if (!shouldQuote) {
            return;
        }

        let isLatestRequest = true;

        function settle(
            pricedCart: PricedCartData | null,
            error: ValidationErrorResponse | null,
        ) {
            if (isLatestRequest) {
                setResult({
                    quotedItems: items,
                    quotedUserUlid: userUlid,
                    pricedCart,
                    error,
                });
            }
        }

        axios
            .post<PricedCartData>(quote.url(), cartPayload(items, userUlid))
            .then(({ data }) => settle(data, null))
            .catch((failure: unknown) =>
                settle(null, validationError(failure)),
            );

        return () => {
            isLatestRequest = false;
        };
    }, [items, userUlid, shouldQuote]);

    // the reducer keeps `items` referentially stable, so identity says which cart was priced
    const isResultCurrent =
        result !== null &&
        result.quotedItems === items &&
        result.quotedUserUlid === userUlid;

    return {
        pricedCart: isResultCurrent ? result.pricedCart : null,
        error: isResultCurrent ? result.error : null,
        isLoading: shouldQuote && !isResultCurrent,
    };
}
