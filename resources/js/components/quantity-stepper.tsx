interface QuantityStepperProps {
    quantity: number;
    onChange(nextQuantity: number): void;
}

export default function QuantityStepper({
    quantity,
    onChange,
}: QuantityStepperProps) {
    return (
        <div className="flex items-center gap-2 text-sm">
            <button
                type="button"
                aria-label="Decrease quantity"
                onClick={() => onChange(quantity - 1)}
                className="h-7 w-7 rounded-md border border-[#e3e3e0] transition-colors hover:border-accent hover:text-accent focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none dark:border-[#3E3E3A]"
            >
                −
            </button>
            <span className="w-6 text-center">{quantity}</span>
            <button
                type="button"
                aria-label="Increase quantity"
                onClick={() => onChange(quantity + 1)}
                className="h-7 w-7 rounded-md border border-[#e3e3e0] transition-colors hover:border-accent hover:text-accent focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none dark:border-[#3E3E3A]"
            >
                +
            </button>
        </div>
    );
}
