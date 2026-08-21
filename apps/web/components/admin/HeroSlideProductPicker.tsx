"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { getAdminProducts, type AdminProduct } from "@/lib/api";

type Locale = "ar" | "fr" | "en";

export type PickedProduct = { id: string; name: string; image_url: string | null };

/**
 * No "pick a Product" control exists anywhere else in the admin UI
 * (confirmed by research) — this is the one genuinely new piece of UI in
 * the hero slider feature. Backed by the existing paginated
 * getAdminProducts({q}) search, debounced like any typeahead.
 */
export function HeroSlideProductPicker({
  value,
  onChange,
}: {
  value: PickedProduct | null;
  onChange: (product: PickedProduct) => void;
}) {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [query, setQuery] = useState("");
  const [results, setResults] = useState<AdminProduct[]>([]);
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    // Nothing to fetch for an empty query — the dropdown itself is only
    // rendered when query.trim() is truthy, so stale results just stay
    // unused rather than needing to be cleared here.
    if (!query.trim()) {
      return;
    }

    const handle = setTimeout(() => {
      getAdminProducts({ q: query })
        .then((res) => setResults(res.items))
        .finally(() => setLoading(false));
    }, 300);

    return () => clearTimeout(handle);
  }, [query]);

  function handlePick(product: AdminProduct) {
    onChange({ id: product.id, name: product.name[locale], image_url: product.images[0]?.url ?? null });
    setQuery("");
    setResults([]);
    setOpen(false);
  }

  return (
    <div className="relative">
      {value && (
        <div className="mb-2 flex items-center gap-2 rounded-lg border border-primary/15 bg-primary/5 p-2">
          {value.image_url && (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={value.image_url} alt="" className="h-10 w-10 rounded-md object-cover" />
          )}
          <span className="text-small font-medium text-ink">{value.name}</span>
        </div>
      )}
      <input
        type="text"
        value={query}
        onChange={(event) => {
          setQuery(event.target.value);
          setOpen(true);
          if (event.target.value.trim()) setLoading(true);
        }}
        onFocus={() => setOpen(true)}
        onBlur={() => setTimeout(() => setOpen(false), 150)}
        placeholder={value ? t("changeProduct") : t("searchProduct")}
        className="w-full rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
      />

      {open && query.trim() && (
        <div className="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-primary/15 bg-white shadow-lift">
          {loading ? (
            <p className="p-3 text-small text-ink/50">{t("loading")}</p>
          ) : results.length === 0 ? (
            <p className="p-3 text-small text-ink/50">{t("noResults")}</p>
          ) : (
            results.map((product) => (
              <button
                key={product.id}
                type="button"
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => handlePick(product)}
                className="flex w-full items-center gap-2 px-3 py-2 text-start text-small hover:bg-primary/5"
              >
                {product.images[0]?.url ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={product.images[0].url} alt="" className="h-8 w-8 rounded-md object-cover" />
                ) : (
                  <span className="h-8 w-8 rounded-md bg-primary/10" />
                )}
                <span className="text-ink">{product.name[locale]}</span>
              </button>
            ))
          )}
        </div>
      )}
    </div>
  );
}
