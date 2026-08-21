import { getLocale } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import { buildBreadcrumbJsonLd } from "@/lib/seo";

export type Crumb = { label: string; href?: string };

// PRD §18: visible breadcrumbs on all catalog/PDP pages, for SEO and
// orientation. "/" as separator reads fine unmirrored in both directions,
// so it doesn't need per-locale flipping the way directional arrows do.
// The BreadcrumbList JSON-LD script is built from this exact `items` prop,
// so structured data can never drift from what's visibly on the page.
export async function Breadcrumbs({ items }: { items: Crumb[] }) {
  const locale = await getLocale();
  const jsonLd = buildBreadcrumbJsonLd(items, locale);

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd).replace(/</g, "\\u003c") }} />
      <nav aria-label="Breadcrumb" className="mx-auto max-w-7xl px-6 pt-6 text-small text-ink/60">
        <ol className="flex flex-wrap items-center gap-2">
          {items.map((item, i) => (
            <li key={i} className="flex items-center gap-2">
              {i > 0 && <span aria-hidden="true">/</span>}
              {item.href ? (
                <Link href={item.href} className="hover:text-primary">
                  {item.label}
                </Link>
              ) : (
                <span className="text-ink" aria-current="page">
                  {item.label}
                </span>
              )}
            </li>
          ))}
        </ol>
      </nav>
    </>
  );
}
