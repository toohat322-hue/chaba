"use client";

import { useEffect, useState } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { getAdminRoles, type AdminRole } from "@/lib/api";

export default function AdminRolesPage() {
  const t = useTranslations("Admin");
  const [roles, setRoles] = useState<AdminRole[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getAdminRoles()
      .then(setRoles)
      .finally(() => setLoading(false));
  }, []);

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-h1 font-semibold text-primary">{t("rolesTitle")}</h1>
        <Link
          href="/admin/staff"
          className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
        >
          {t("navStaff")}
        </Link>
      </div>

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {roles.map((role) => (
            <Link
              key={role.id}
              href={`/admin/roles/${role.id}`}
              className="rounded-2xl bg-white p-5 shadow-soft transition-shadow hover:shadow-lift"
            >
              <h2 className="text-h3 font-semibold text-ink">{role.name}</h2>
              {role.description && <p className="mt-1 text-small text-ink/60">{role.description}</p>}
              <div className="mt-4 flex items-center gap-4 text-caption text-ink/50">
                <span>{t("staffCount", { count: role.staff_count })}</span>
                <span>{t("permissionCount", { count: role.permission_keys.length })}</span>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
