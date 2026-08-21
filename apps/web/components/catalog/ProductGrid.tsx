import { ProductCard } from "@/components/product/ProductCard";
import type { ProductListItem } from "@/lib/api";

export function ProductGrid({ products }: { products: ProductListItem[] }) {
  return (
    <div className="grid grid-cols-2 gap-5 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4 lg:gap-8">
      {products.map((product) => (
        <ProductCard key={product.id} product={product} />
      ))}
    </div>
  );
}
