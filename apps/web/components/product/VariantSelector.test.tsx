import { describe, expect, it, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { VariantSelector } from "./VariantSelector";
import type { ProductVariantDetail } from "@/lib/api";

// AddToCartButton pulls in CartProvider/CartDrawerProvider/Toast context —
// out of scope for this test (which is about independent per-size
// quantities and the aggregate total math, not the cart integration
// itself). The mock renders each item's variantId:quantity pair so tests
// can assert exactly which sizes/quantities would be sent to the cart.
vi.mock("@/components/product/AddToCartButton", () => ({
  AddToCartButton: ({ items }: { items: { variantId: string; quantity: number }[] }) => (
    <button type="button" disabled={items.length === 0} data-testid="add-to-cart">
      {items.map((i) => `${i.variantId}:${i.quantity}`).join(",")}
    </button>
  ),
}));

const messages = {
  Product: {
    chooseSize: "Choose size",
    unavailable: "Unavailable",
    addSize: "Add",
    totalQuantityLabel: "{count} item(s)",
  },
};

const variants: ProductVariantDetail[] = [
  {
    id: "v10", sku: "SKU-10", color: null, size: null, size_value: 10, size_unit: "ml",
    price: 50000, compare_at_price: null, sort_order: 0, available_quantity: 5, low_stock: false,
  },
  {
    id: "v20", sku: "SKU-20", color: null, size: null, size_value: 20, size_unit: "ml",
    price: 85000, compare_at_price: null, sort_order: 1, available_quantity: 0, low_stock: false,
  },
  {
    id: "v50", sku: "SKU-50", color: null, size: null, size_value: 50, size_unit: "ml",
    price: 180000, compare_at_price: 220000, sort_order: 2, available_quantity: 3, low_stock: true,
  },
];

function renderSelector() {
  return render(
    <NextIntlClientProvider locale="en" messages={messages}>
      <VariantSelector variants={variants} locale="en" />
    </NextIntlClientProvider>,
  );
}

function addCardButtons() {
  return screen.getAllByText("+ Add");
}

describe("VariantSelector", () => {
  it("starts with every size at quantity 0 and the total at 0", () => {
    renderSelector();

    expect(screen.getByText("10 ml")).toBeInTheDocument();
    // Renders in both the desktop summary block and the mobile sticky bar.
    expect(screen.getAllByText("0 item(s)").length).toBeGreaterThan(0);
    expect(screen.getAllByTestId("add-to-cart")[0].textContent).toBe("");
  });

  it("disables the out-of-stock size and never offers an add control for it", () => {
    renderSelector();

    const unavailableLabel = screen.getByText("Unavailable");
    const card = unavailableLabel.closest("div");
    expect(card?.querySelector("button")).toBeNull();
  });

  it("adding one size does not affect another size's quantity", () => {
    renderSelector();

    const [add10] = addCardButtons();
    fireEvent.click(add10); // 10ml -> qty 1

    // 10ml now shows a stepper (qty 1); 20ml is unavailable (no control);
    // 50ml should still show its own "+ Add" control, untouched.
    expect(screen.getAllByText("+ Add")).toHaveLength(1); // only 50ml left unadded
    expect(screen.getAllByTestId("add-to-cart")[0].textContent).toBe("v10:1");
  });

  it("increments and decrements one size independently of others", () => {
    renderSelector();

    const buttons = addCardButtons();
    fireEvent.click(buttons[0]); // 10ml -> 1
    fireEvent.click(buttons[1]); // 50ml -> 1

    const plusButtons = screen.getAllByLabelText("+");
    fireEvent.click(plusButtons[0]); // 10ml -> 2

    expect(screen.getAllByTestId("add-to-cart")[0].textContent).toBe("v10:2,v50:1");

    const minusButtons = screen.getAllByLabelText("-");
    fireEvent.click(minusButtons[1]); // 50ml -> 0, reverts to "+ Add"

    expect(screen.getAllByTestId("add-to-cart")[0].textContent).toBe("v10:2");
    expect(screen.getAllByText("+ Add")).toHaveLength(1); // 50ml's card again
  });

  it("sums quantity and price across multiple active sizes for the total", () => {
    renderSelector();

    const buttons = addCardButtons();
    fireEvent.click(buttons[0]); // 10ml: 500 DZD
    fireEvent.click(buttons[1]); // 50ml: 1800 DZD

    const plusButtons = screen.getAllByLabelText("+");
    fireEvent.click(plusButtons[0]); // 10ml -> 2: 1000 + 1800 = 2800 DZD, qty 3

    expect(screen.getAllByText("3 item(s)").length).toBeGreaterThan(0);
    expect(screen.getAllByText("2,800 DZD").length).toBeGreaterThan(0);
  });

  it("orders sizes numerically (10, 20, 50) even when sort_order says otherwise", () => {
    const outOfOrder: ProductVariantDetail[] = [
      { id: "v50", sku: "SKU-50", color: null, size: null, size_value: 50, size_unit: "ml", price: 180000, compare_at_price: null, sort_order: 0, available_quantity: 3, low_stock: false },
      { id: "v10", sku: "SKU-10", color: null, size: null, size_value: 10, size_unit: "ml", price: 50000, compare_at_price: null, sort_order: 1, available_quantity: 5, low_stock: false },
      { id: "v20", sku: "SKU-20", color: null, size: null, size_value: 20, size_unit: "ml", price: 85000, compare_at_price: null, sort_order: 2, available_quantity: 4, low_stock: false },
    ];

    render(
      <NextIntlClientProvider locale="en" messages={messages}>
        <VariantSelector variants={outOfOrder} locale="en" />
      </NextIntlClientProvider>,
    );

    const labels = screen.getAllByText(/^\d+ ml$/).map((el) => el.textContent);
    // Each size label renders twice (desktop grid + nothing else here, but
    // guard by taking the unique sequence) — assert the first three appear
    // smallest-to-largest.
    expect(labels.slice(0, 3)).toEqual(["10 ml", "20 ml", "50 ml"]);
  });

  it("caps a size's quantity at its own available stock", () => {
    renderSelector();

    const buttons = addCardButtons();
    fireEvent.click(buttons[1]); // 50ml, available_quantity: 3

    const plusButtons = screen.getAllByLabelText("+");
    fireEvent.click(plusButtons[0]);
    fireEvent.click(plusButtons[0]);

    expect(screen.getAllByTestId("add-to-cart")[0].textContent).toBe("v50:3");
    expect(screen.getAllByLabelText("+")[0]).toBeDisabled();
  });
});
