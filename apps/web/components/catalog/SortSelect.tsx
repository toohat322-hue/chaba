"use client";

import { useTranslations } from "next-intl";
import { useRouter, usePathname } from "@/i18n/navigation";
import { useSearchParams } from "next/navigation";

const OPTIONS = [
  { value: "newest", key: "sortNewest" },
  { value: "price_asc", key: "sortPriceAsc" },
  { value: "price_desc", key: "sortPriceDesc" },
  { value: "rating", key: "sortRating" },
] as const;

const RELEVANCE_OPTION = { value: "", key: "sortRelevance" } as const;

/**
 * `includeRelevance` adds a leading "most relevant" option whose value is
 * "" (no `sort` param at all) — only meaningful on the search page, where
 * an absent sort means relevance-ranked results, not "newest" like category
 * browsing defaults to.
 */
export function SortSelect({ current, includeRelevance = false }: { current: string; includeRelevance?: boolean }) {
  const t = useTranslations("Category");
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const options = includeRelevance ? [RELEVANCE_OPTION, ...OPTIONS] : OPTIONS;

  function handleChange(value: string) {
    const params = new URLSearchParams(searchParams.toString());
    if (value) {
      params.set("sort", value);
    } else {
      params.delete("sort");
    }
    params.delete("page"); // changing sort resets pagination
    // Not a click on an <a> — ShabaLoaderOverlay can't see this navigation
    // start on its own (see its docblock), so it's signaled explicitly.
    window.dispatchEvent(new Event("shaba:navigation-start"));
    router.push(`${pathname}?${params.toString()}`);
  }

  return (
    <label className="flex items-center gap-2 text-small text-ink/70">
      {t("sortBy")}
      <select
        value={current}
        onChange={(e) => handleChange(e.target.value)}
        className="rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none"
      >
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {t(option.key)}
          </option>
        ))}
      </select>
    </label>
  );
}
