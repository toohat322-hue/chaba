"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { downloadAdminCsv } from "@/lib/api";

export function ExportCsvButton({ path, filename }: { path: string; filename: string }) {
  const t = useTranslations("Admin");
  const [busy, setBusy] = useState(false);

  async function handleClick() {
    setBusy(true);
    try {
      await downloadAdminCsv(path, filename);
    } catch {
      window.alert(t("exportFailed"));
    } finally {
      setBusy(false);
    }
  }

  return (
    <button
      type="button"
      onClick={handleClick}
      disabled={busy}
      className="rounded-full border border-primary/20 px-5 py-2.5 text-small font-semibold text-primary hover:bg-primary/5 disabled:opacity-50"
    >
      {busy ? t("exporting") : t("exportCsv")}
    </button>
  );
}
