"use client";

import { useEffect, useState } from "react";
import { useTranslations } from "next-intl";
import { usePathname, useRouter } from "@/i18n/navigation";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { AccountNav } from "@/components/customer/AccountNav";
import { AddressForm } from "@/components/customer/AddressForm";
import { ShabaLoader } from "@/components/ui/ShabaLoader";
import {
  createAddress,
  deleteAddress,
  getAddresses,
  updateAddress,
  type Address,
  type AddressPayload,
} from "@/lib/api";

export default function AddressesPage() {
  const t = useTranslations("Auth");
  const tLoading = useTranslations("Loading");
  const router = useRouter();
  const pathname = usePathname();
  const { customer, loading: authLoading } = useCustomerAuth();

  const [addresses, setAddresses] = useState<Address[]>([]);
  const [loading, setLoading] = useState(true);
  const [adding, setAdding] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);

  function load() {
    setLoading(true);
    getAddresses()
      .then(setAddresses)
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    if (authLoading) return;

    if (!customer) {
      router.replace(`/login?return_to=${encodeURIComponent(pathname)}`);
      return;
    }

    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount/auth-ready, loading flag starts true and is only ever cleared from the promise callback
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authLoading, customer]);

  async function handleCreate(payload: AddressPayload) {
    await createAddress(payload);
    setAdding(false);
    load();
  }

  async function handleUpdate(id: string, payload: AddressPayload) {
    await updateAddress(id, payload);
    setEditingId(null);
    load();
  }

  async function handleDelete(id: string) {
    if (!window.confirm(t("confirmDeleteAddress"))) return;
    await deleteAddress(id);
    load();
  }

  async function handleSetDefault(id: string) {
    await updateAddress(id, { is_default: true });
    load();
  }

  if (authLoading || !customer) {
    return <ShabaLoader label={tLoading("label")} />;
  }

  return (
    <div className="mx-auto max-w-2xl px-6 py-10">
      <AccountNav />

      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-h1 font-semibold text-primary">{t("myAddresses")}</h1>
        {!adding && (
          <button
            type="button"
            onClick={() => setAdding(true)}
            className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
          >
            {t("addAddress")}
          </button>
        )}
      </div>

      {adding && (
        <div className="mb-6 rounded-2xl bg-white p-6 shadow-soft">
          <AddressForm onSubmit={handleCreate} onCancel={() => setAdding(false)} submitLabel={t("save")} />
        </div>
      )}

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : addresses.length === 0 && !adding ? (
        <p className="text-body text-ink/60">{t("noAddresses")}</p>
      ) : (
        <div className="space-y-4">
          {addresses.map((address) =>
            editingId === address.id ? (
              <div key={address.id} className="rounded-2xl bg-white p-6 shadow-soft">
                <AddressForm
                  initial={address}
                  onSubmit={(payload) => handleUpdate(address.id, payload)}
                  onCancel={() => setEditingId(null)}
                  submitLabel={t("save")}
                />
              </div>
            ) : (
              <div key={address.id} className="rounded-2xl bg-white p-5 shadow-soft">
                <div className="mb-1 flex items-center gap-2">
                  <p className="font-semibold text-ink">{address.full_name}</p>
                  {address.is_default && (
                    <span className="rounded-full bg-success/10 px-2 py-0.5 text-caption text-success">
                      {t("defaultBadge")}
                    </span>
                  )}
                </div>
                <p className="text-small text-ink/70">{address.phone}</p>
                <p className="text-small text-ink/70">{address.address_line}</p>

                <div className="mt-3 flex gap-3 text-small">
                  <button type="button" onClick={() => setEditingId(address.id)} className="text-primary hover:underline">
                    {t("edit")}
                  </button>
                  {!address.is_default && (
                    <button
                      type="button"
                      onClick={() => handleSetDefault(address.id)}
                      className="text-primary hover:underline"
                    >
                      {t("setAsDefault")}
                    </button>
                  )}
                  <button type="button" onClick={() => handleDelete(address.id)} className="text-error hover:underline">
                    {t("delete")}
                  </button>
                </div>
              </div>
            ),
          )}
        </div>
      )}
    </div>
  );
}
