"use client";

export function QuantityStepper({
  quantity,
  max,
  min = 1,
  onChange,
}: {
  quantity: number;
  max: number;
  /** Lets a caller allow decrementing down to 0 (e.g. "un-add" a product
   * size) instead of the default floor of 1. */
  min?: number;
  onChange: (quantity: number) => void;
}) {
  const atMax = quantity >= max;
  const atMin = quantity <= min;

  return (
    <div className="flex items-center gap-3 rounded-full border border-primary/15 px-3 py-1.5">
      <button
        type="button"
        onClick={() => onChange(Math.max(min, quantity - 1))}
        disabled={atMin}
        className="text-h3 text-ink/70 hover:text-primary disabled:opacity-30"
        aria-label="-"
      >
        −
      </button>
      <span className="w-8 text-center text-body text-ink">{quantity}</span>
      <button
        type="button"
        onClick={() => onChange(Math.min(max, quantity + 1))}
        disabled={atMax}
        className="text-h3 text-ink/70 hover:text-primary disabled:opacity-30"
        aria-label="+"
      >
        +
      </button>
    </div>
  );
}
