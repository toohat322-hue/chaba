"use client";

import { useEffect, useState, type DragEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import {
  createAdminHeroSlide,
  deleteAdminHeroSlide,
  getAdminHeroSlides,
  reorderAdminHeroSlides,
  updateAdminHeroSlide,
  type AdminHeroSlide,
  type HeroSlidePayload,
} from "@/lib/api";
import { HeroSlideForm } from "@/components/admin/HeroSlideForm";

type Locale = "ar" | "fr" | "en";

export default function AdminHeroSliderPage() {
  const t = useTranslations("Admin");
  const locale = useLocale() as Locale;

  const [items, setItems] = useState<AdminHeroSlide[]>([]);
  const [loading, setLoading] = useState(true);
  const [adding, setAdding] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [dragIndex, setDragIndex] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  function load() {
    setLoading(true);
    getAdminHeroSlides()
      .then(setItems)
      .finally(() => setLoading(false));
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch on mount, loading flag starts true and is only ever cleared from the promise callback
    load();
  }, []);

  async function handleCreate(payload: HeroSlidePayload) {
    const created = await createAdminHeroSlide(payload);
    setAdding(false);
    // Straight into edit mode — uploading an image needs a real slide id,
    // so there's no separate "now go add the image" navigation step.
    setEditingId(created.id);
    load();
  }

  async function handleUpdate(id: string, payload: HeroSlidePayload) {
    await updateAdminHeroSlide(id, payload);
    setEditingId(null);
    load();
  }

  async function handleDelete(id: string) {
    if (!window.confirm(t("confirmDeleteGeneric"))) return;
    await deleteAdminHeroSlide(id);
    load();
  }

  function handleDragStart(index: number) {
    setDragIndex(index);
  }

  function handleDragOver(event: DragEvent<HTMLDivElement>) {
    event.preventDefault();
  }

  async function handleDrop(index: number) {
    if (dragIndex === null || dragIndex === index) {
      setDragIndex(null);
      return;
    }

    const reordered = [...items];
    const [moved] = reordered.splice(dragIndex, 1);
    reordered.splice(index, 0, moved);
    setItems(reordered);
    setDragIndex(null);

    try {
      const updated = await reorderAdminHeroSlides(reordered.map((item) => item.id));
      setItems(updated);
    } catch {
      setError(t("errorGeneric"));
      load();
    }
  }

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-h1 font-semibold text-primary">{t("heroSliderTitle")}</h1>
          <p className="text-small text-ink/60">{t("heroSliderSubtitle")}</p>
        </div>
        {!adding && (
          <button
            type="button"
            onClick={() => setAdding(true)}
            className="rounded-full bg-primary px-5 py-2.5 text-small font-semibold text-background hover:bg-primary/90"
          >
            {t("newHeroSlide")}
          </button>
        )}
      </div>

      {error && <p className="mb-4 text-small text-error">{error}</p>}

      {adding && (
        <div className="mb-6 rounded-2xl bg-white p-6 shadow-soft">
          <HeroSlideForm onSubmit={handleCreate} onCancel={() => setAdding(false)} submitLabel={t("save")} />
        </div>
      )}

      {loading ? (
        <p className="text-body text-ink/50">{t("loading")}</p>
      ) : items.length === 0 && !adding ? (
        <p className="text-body text-ink/50">{t("noResults")}</p>
      ) : (
        <div className="space-y-4">
          {items.map((item, index) =>
            editingId === item.id ? (
              <div key={item.id} className="rounded-2xl bg-white p-6 shadow-soft">
                <HeroSlideForm
                  initial={item}
                  onSubmit={(payload) => handleUpdate(item.id, payload)}
                  onCancel={() => setEditingId(null)}
                  submitLabel={t("save")}
                  onImageUploaded={(slide) => setItems((prev) => prev.map((i) => (i.id === slide.id ? slide : i)))}
                />
              </div>
            ) : (
              <div
                key={item.id}
                draggable
                onDragStart={() => handleDragStart(index)}
                onDragOver={handleDragOver}
                onDrop={() => handleDrop(index)}
                className="flex cursor-move flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-soft"
              >
                <div className="flex items-center gap-3">
                  {item.image_url ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={item.image_url} alt="" className="h-12 w-20 rounded-md object-cover" />
                  ) : (
                    <span className="flex h-12 w-20 items-center justify-center rounded-md border border-dashed border-primary/20 text-caption text-ink/40">
                      {t("noImage")}
                    </span>
                  )}
                  <div>
                    <div className="flex items-center gap-2">
                      <p className="font-semibold text-ink">{item.title[locale] || item.product?.name[locale]}</p>
                      <span
                        className={`rounded-full px-2 py-0.5 text-caption font-medium ${
                          item.is_active ? "bg-success/10 text-success" : "bg-error/10 text-error"
                        }`}
                      >
                        {item.is_active ? t("active") : t("inactive")}
                      </span>
                    </div>
                    <p className="text-small text-ink/60">{item.product?.name[locale]}</p>
                  </div>
                </div>
                <div className="flex gap-3 text-small">
                  <button type="button" onClick={() => setEditingId(item.id)} className="text-primary hover:underline">
                    {t("edit")}
                  </button>
                  <button type="button" onClick={() => handleDelete(item.id)} className="text-error hover:underline">
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
