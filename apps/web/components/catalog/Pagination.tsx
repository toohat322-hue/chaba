import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";

function hrefForPage(basePath: string, currentParams: Record<string, string | undefined>, page: number) {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(currentParams)) {
    if (value) params.set(key, value);
  }
  params.set("page", String(page));

  return `${basePath}?${params.toString()}`;
}

export async function Pagination({
  basePath,
  currentPage,
  lastPage,
  currentParams,
}: {
  basePath: string;
  currentPage: number;
  lastPage: number;
  currentParams: Record<string, string | undefined>;
}) {
  const t = await getTranslations("Category");

  if (lastPage <= 1) {
    return null;
  }

  return (
    <nav className="mt-10 flex items-center justify-center gap-4 text-small">
      {currentPage > 1 ? (
        <Link
          href={hrefForPage(basePath, currentParams, currentPage - 1)}
          className="rounded-lg border border-primary/15 px-4 py-2 text-ink hover:border-primary/40"
        >
          {t("previousPage")}
        </Link>
      ) : (
        <span className="rounded-lg border border-primary/5 px-4 py-2 text-ink/30">{t("previousPage")}</span>
      )}

      <span className="text-ink/60">{t("pageOf", { current: currentPage, total: lastPage })}</span>

      {currentPage < lastPage ? (
        <Link
          href={hrefForPage(basePath, currentParams, currentPage + 1)}
          className="rounded-lg border border-primary/15 px-4 py-2 text-ink hover:border-primary/40"
        >
          {t("nextPage")}
        </Link>
      ) : (
        <span className="rounded-lg border border-primary/5 px-4 py-2 text-ink/30">{t("nextPage")}</span>
      )}
    </nav>
  );
}
