import type { MetadataRoute } from "next";
import { getCategories, getProducts, type Category } from "@/lib/api";
import { routing } from "@/i18n/routing";
import { languageAlternates } from "@/lib/seo";

type Entry = {
  path: string;
  lastModified?: string;
  changeFrequency: "daily" | "weekly" | "monthly";
  priority: number;
};

function flattenCategories(categories: Category[]): Category[] {
  return categories.flatMap((category) => [category, ...flattenCategories(category.children)]);
}

async function getAllProducts(): Promise<{ slug: string; updated_at: string }[]> {
  const products: { slug: string; updated_at: string }[] = [];
  let page = 1;

  // Small catalog — a bounded loop with a sane upper limit keeps this safe
  // even if the total product count grows well beyond what it is today.
  for (let i = 0; i < 50; i++) {
    const { items, meta } = await getProducts({ page });
    products.push(...items.map((item) => ({ slug: item.slug, updated_at: item.updated_at })));
    if (page >= meta.last_page) break;
    page += 1;
  }

  return products;
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [categories, products] = await Promise.all([
    getCategories().catch(() => []),
    getAllProducts().catch(() => []),
  ]);

  const staticEntries: Entry[] = [
    { path: "/", changeFrequency: "daily", priority: 1.0 },
    { path: "/about", changeFrequency: "monthly", priority: 0.3 },
    { path: "/contact", changeFrequency: "monthly", priority: 0.3 },
    { path: "/faq", changeFrequency: "monthly", priority: 0.3 },
    { path: "/terms", changeFrequency: "monthly", priority: 0.2 },
    { path: "/privacy", changeFrequency: "monthly", priority: 0.2 },
    { path: "/shipping-returns", changeFrequency: "monthly", priority: 0.3 },
  ];

  const categoryEntries: Entry[] = flattenCategories(categories).map((category) => ({
    path: `/category/${category.slug}`,
    lastModified: category.updated_at,
    changeFrequency: "weekly",
    priority: 0.8,
  }));

  const productEntries: Entry[] = products.map((product) => ({
    path: `/products/${product.slug}`,
    lastModified: product.updated_at,
    changeFrequency: "weekly",
    priority: 0.7,
  }));

  const entries = [...staticEntries, ...categoryEntries, ...productEntries];

  return entries.flatMap(({ path, lastModified, changeFrequency, priority }) => {
    const alternates = languageAlternates(path);
    return routing.locales.map((locale) => ({
      url: alternates[locale],
      lastModified,
      changeFrequency,
      priority,
      alternates: { languages: alternates },
    }));
  });
}
