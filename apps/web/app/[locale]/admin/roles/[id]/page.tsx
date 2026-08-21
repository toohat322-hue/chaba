"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useParams } from "next/navigation";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import {
  ApiError,
  getAdminPermissions,
  getAdminRole,
  updateAdminRolePermissions,
  type AdminPermission,
  type AdminRole,
} from "@/lib/api";

export default function AdminRoleDetailPage() {
  const t = useTranslations("Admin");
  const params = useParams<{ id: string }>();
  const roleId = params.id;

  const [role, setRole] = useState<AdminRole | null>(null);
  const [permissions, setPermissions] = useState<AdminPermission[]>([]);
  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);

  useEffect(() => {
    Promise.all([getAdminRole(roleId), getAdminPermissions()])
      .then(([roleData, permissionData]) => {
        setRole(roleData);
        setPermissions(permissionData);
        setSelected(new Set(roleData.permission_keys));
      })
      .finally(() => setLoading(false));
  }, [roleId]);

  function toggle(key: string) {
    setSelected((current) => {
      const next = new Set(current);
      if (next.has(key)) {
        next.delete(key);
      } else {
        next.add(key);
      }
      return next;
    });
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSaved(false);
    setSaving(true);
    try {
      const updated = await updateAdminRolePermissions(roleId, Array.from(selected));
      setRole(updated);
      setSelected(new Set(updated.permission_keys));
      setSaved(true);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("errorGeneric"));
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return <p className="text-body text-ink/50">{t("loading")}</p>;
  }

  if (!role) {
    return <p className="text-body text-ink/50">{t("noResults")}</p>;
  }

  const isSuperAdmin = role.name === "Super Admin";

  const grouped = permissions.reduce<Record<string, AdminPermission[]>>((acc, permission) => {
    const permissionModule = permission.key.split(".")[0];
    (acc[permissionModule] ??= []).push(permission);
    return acc;
  }, {});

  return (
    <div className="max-w-3xl">
      <Link href="/admin/roles" className="mb-4 inline-block text-small text-ink/60 hover:text-ink">
        ← {t("back")}
      </Link>

      <h1 className="mb-2 text-h1 font-semibold text-primary">{role.name}</h1>
      {role.description && <p className="mb-6 text-body text-ink/60">{role.description}</p>}

      {isSuperAdmin && (
        <p className="mb-6 rounded-lg bg-primary/5 px-4 py-3 text-small text-ink/70">{t("superAdminNotEditable")}</p>
      )}

      <form onSubmit={handleSubmit}>
        <div className="space-y-6">
          {Object.entries(grouped).map(([permissionModule, modulePermissions]) => (
            <div key={permissionModule} className="rounded-2xl bg-white p-5 shadow-soft">
              <h2 className="mb-3 text-h3 font-semibold capitalize text-ink">{permissionModule}</h2>
              <div className="grid gap-2 sm:grid-cols-2">
                {modulePermissions.map((permission) => (
                  <label key={permission.key} className="flex items-center gap-2 text-small text-ink">
                    <input
                      type="checkbox"
                      checked={selected.has(permission.key)}
                      onChange={() => toggle(permission.key)}
                      disabled={isSuperAdmin}
                      className="h-4 w-4 accent-primary"
                    />
                    <span>{permission.description ?? permission.key}</span>
                  </label>
                ))}
              </div>
            </div>
          ))}
        </div>

        {error && <p className="mt-4 text-small text-error">{error}</p>}
        {saved && <p className="mt-4 text-small text-success">{t("saveSuccess")}</p>}

        {!isSuperAdmin && (
          <button
            type="submit"
            disabled={saving}
            className="mt-6 rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90 disabled:opacity-50"
          >
            {saving ? t("loading") : t("save")}
          </button>
        )}
      </form>
    </div>
  );
}
