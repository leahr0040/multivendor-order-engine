export interface ValidationErrorResponse {
    message: string;
    errors: Record<string, string[]>;
}

export type CategorySlug = 'electronics' | 'books' | 'home';
export type LoyaltyTier = 'none' | 'silver' | 'gold';
export type OrderStatus = 'pending' | 'completed';
export type SubOrderStatus = 'pending' | 'completed';

export interface CategoryData {
    slug: CategorySlug;
    name: string;
}

export interface ProductData {
    ulid: string;
    name: string;
    description: string | null;
    category: CategoryData;
}

export interface UserData {
    ulid: string;
    name: string;
    loyalty_tier: LoyaltyTier;
}

export interface VendorData {
    ulid: string;
    name: string;
}

export interface PricedLineData {
    product_ulid: string;
    product_name: string;
    vendor_ulid: string;
    vendor_name: string;
    quantity: number;
    original_unit_price: number;
    original_price: number;
    discount: number;
    final_price: number;
    applied_rules: string[];
}

export interface PricedCartData {
    lines: PricedLineData[];
    original_price: number;
    discount: number;
    final_price: number;
}

export interface OrderItemData {
    product: ProductData;
    quantity: number;
    original_unit_price: number;
    original_price: number;
    discount: number;
    final_price: number;
}

export interface SubOrderData {
    ulid: string;
    status: SubOrderStatus;
    vendor: VendorData;
    original_price: number;
    discount: number;
    final_price: number;
    order_items: OrderItemData[];
}

export interface OrderData {
    ulid: string;
    status: OrderStatus;
    original_price: number;
    discount: number;
    final_price: number;
    created_at: string | null;
    sub_orders: SubOrderData[];
}
