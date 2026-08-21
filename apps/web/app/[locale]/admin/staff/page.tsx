"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import {
  getAdminRoles,
  getAdminStaff,
  updateAdminStaff,
  type AdminRole,
  type AdminStaff,
  type ProductListMeta,
} from "@/lib/api";

type Locale = "ar" | "fr" | "en";

export default function AdminStaffPage() {
  const t = useTranslations("Admin");
  const tCategory = useTranslations("Category");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<AdminStaff[]>([]);
  const [roles, setRoles] = useState<AdminRole[]>([]);
  const [meta, setMeta] = useState<ProductListMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [q, setQ] = useState("");
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);

  async function load(query: string, pageNum: number) {
    setLoading(true);
    try {
      const [staffRes, rolesRes] = await Promise.all([
        getAdminStaff({ q: query || undefined, page: pageNum }),
        roles.length === 0 ? getAdminRoles() : Promise.resolve(roles),
      ]);
      setItems(staffRes.items);
      setMeta(staffRes.meta);
      setRoles(rolesRes);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/page change, loading flag starts true and is only ever cleared from the promise callback
    load(q, page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  function handleSearch(event: FormEvent) {
    event.preventDefault();
    setPage(1);
    load(q, 1);
  }

  async function handleRoleChange(staffId: string, roleId: string) {
    setError(null);
    try {
      const updated = await updateAdminStaff(staffId, { role_id: roleId });
      setItems((current) => current.map((item) => (item.id === staffId ? updated : item)));
    } catch {
      setError(t("errorGeneric"));
    }
  }

  async function handleToggleStatus(staff: AdminStaff) {
    setError(null);
    const nextStatus = staff.status === "active" ? "blocked" : "active";
    try {
      const updated = await updateAdminStaff(staff.id, { status: nextStatus });
      setItems((current) => current.map((item) => (item.id === staff.id ? updated : item)));
    } catch {
      setError(t("errorGeneric"));
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-h1 font-semibold text-primary">{t("navStaff")}</h1>
        <Link
          href="/admin/staff/new"
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
        >
          {t("newStaff")}
        </Link>
      </div>

      <form onSubmit={handleSearch} className="mb-4">
        <input
          type="search"
          value={q}
          onChange={(event) => setQ(event.target.value)}
          placeholder={t("searchPlaceholder")}
          className="w-full max-w-sm rounded-lg border border-primary/15 bg-white px-3 py-2 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
        />
      </form>

      {error && <p className="mb-4 text-small text-error">{error}</p>}

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : items.length === 0 ? (
        <p className="text-body text-ink/50">{t("noResults")}</p>
      ) : (
        <div className="overflow-x-auto rounded-2xl bg-white shadow-soft">
          <table className="w-full text-start text-small">
            <thead>
              <tr className="border-b border-primary/10 text-ink/60">
                <th className="px-4 py-3 text-start font-medium">{t("name")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("phone")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("navRoles")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("status")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("joined")}</th>
                <th className="px-4 py-3 text-start font-medium">{t("actions")}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((staff) => (
                <tr key={staff.id} className="border-b border-primary/5 last:border-0">
                  <td className="px-4 py-3 text-ink">{staff.full_name}</td>
                  <td className="px-4 py-3 text-ink/70">{staff.phone}</td>
                  <td className="px-4 py-3">
                    <select
                      value={staff.role?.id ?? ""}
                      onChange={(event) => handleRoleChange(staff.id, event.target.value)}
                      className="rounded-lg border border-primary/15 bg-white px-2 py-1.5 text-small text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
                    >
                      {roles.map((role) => (
                        <option key={role.id} value={role.id}>
                          {role.name}
                        </option>
                      ))}
                    </select>
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={`rounded-full px-2.5 py-1 text-caption font-medium ${
                        staff.status === "active" ? "bg-success/10 text-success" : "bg-error/10 text-error"
                      }`}
                    >
                      {staff.status === "active" ? t("active") : t("blocked")}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-ink/70">{new Date(staff.created_at).toLocaleDateString(locale)}</td>
                  <td className="px-4 py-3">
                    <button
                      type="button"
                      onClick={() => handleToggleStatus(staff)}
                      className="text-small font-medium text-primary hover:underline"
                    >
                      {staff.status === "active" ? t("block") : t("unblock")}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {meta && meta.last_page > 1 && (
        <nav className="mt-6 flex items-center justify-center gap-4 text-small">
          <button
            type="button"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
            className="rounded-lg border border-primary/15 px-4 py-2 text-ink hover:border-primary/40 disabled:opacity-30"
          >
            {tCategory("previousPage")}
          </button>
          <span className="text-ink/60">{tCategory("pageOf", { current: meta.current_page, total: meta.last_page })}</span>
          <button
            type="button"
            disabled={page >= meta.last_page}
            onClick={() => setPage((p) => p + 1)}
            className="rounded-lg border border-primary/15 px-4 py-2 text-ink hover:border-primary/40 disabled:opacity-30"
          >
            {tCategory("nextPage")}
          </button>
        </nav>
      )}
    </div>
  );
}
