"use client";

import { useRef, useState, type DragEvent } from "react";
import { useTranslations } from "next-intl";

export type DropzoneImage = {
  id: string;
  url: string;
  altText?: string | null;
  status: "ready" | "uploading" | "uploaded" | "failed";
  errorMessage?: string;
};

/**
 * Presentational drag-and-drop image grid shared by the create-product page
 * (staged, in-browser files — nothing uploaded yet) and the edit-product page
 * (real, already-persisted images). The parent owns all network calls and
 * state; this component only renders the dropzone, the grid, per-image
 * status, and the reorder/delete/retry/alt-text interactions.
 */
export function ImageDropzone({
  images,
  onFilesSelected,
  onDelete,
  onReorder,
  onRetry,
  onAltTextBlur,
}: {
  images: DropzoneImage[];
  onFilesSelected: (files: File[]) => void;
  onDelete: (id: string) => void;
  onReorder: (orderedIds: string[]) => void;
  onRetry?: (id: string) => void;
  onAltTextBlur?: (id: string, altText: string) => void;
}) {
  const t = useTranslations("Admin");
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [dragIndex, setDragIndex] = useState<number | null>(null);
  const [dropzoneActive, setDropzoneActive] = useState(false);

  function handleFileInputChange(event: React.ChangeEvent<HTMLInputElement>) {
    const files = Array.from(event.target.files ?? []);
    if (files.length > 0) onFilesSelected(files);
    if (fileInputRef.current) fileInputRef.current.value = "";
  }

  function handleDropzoneDrop(event: DragEvent<HTMLDivElement>) {
    event.preventDefault();
    setDropzoneActive(false);
    const files = Array.from(event.dataTransfer.files ?? []).filter((file) => file.type.startsWith("image/"));
    if (files.length > 0) onFilesSelected(files);
  }

  function handleCardDragStart(index: number) {
    setDragIndex(index);
  }

  function handleCardDrop(index: number) {
    if (dragIndex === null || dragIndex === index) {
      setDragIndex(null);
      return;
    }
    const reordered = [...images];
    const [moved] = reordered.splice(dragIndex, 1);
    reordered.splice(index, 0, moved);
    setDragIndex(null);
    onReorder(reordered.map((image) => image.id));
  }

  return (
    <div className="space-y-4">
      <div
        onClick={() => fileInputRef.current?.click()}
        onDragOver={(event) => {
          event.preventDefault();
          setDropzoneActive(true);
        }}
        onDragLeave={() => setDropzoneActive(false)}
        onDrop={handleDropzoneDrop}
        role="button"
        tabIndex={0}
        className={`flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-6 py-10 text-center transition-colors ${
          dropzoneActive ? "border-primary bg-primary/5" : "border-primary/20 hover:border-primary/40"
        }`}
      >
        <svg className="h-8 w-8 text-primary/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M12 16.5V9m0 0-3 3m3-3 3 3M4.5 19.5h15a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5h-15A1.5 1.5 0 0 0 3 6v12a1.5 1.5 0 0 0 1.5 1.5Z"
          />
        </svg>
        <p className="text-small font-medium text-ink">{t("dragImagesHere")}</p>
        <span className="rounded-full border border-primary/20 px-4 py-1.5 text-caption font-semibold text-primary">
          {t("selectImages")}
        </span>
        <input
          ref={fileInputRef}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          onChange={handleFileInputChange}
          onClick={(event) => event.stopPropagation()}
          className="hidden"
        />
      </div>

      {images.length > 0 && (
        <div className="flex flex-wrap gap-4">
          {images.map((image, index) => (
            <div
              key={image.id}
              draggable={image.status !== "uploading"}
              onDragStart={() => handleCardDragStart(index)}
              onDragOver={(event) => event.preventDefault()}
              onDrop={() => handleCardDrop(index)}
              className="group relative w-28 cursor-move space-y-1"
            >
              <div className="relative h-24 w-24 overflow-hidden rounded-lg border border-primary/10 bg-primary/5">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={image.url} alt={image.altText ?? ""} className="h-full w-full object-cover" />
                {index === 0 && image.status !== "failed" && (
                  <span className="absolute start-0 top-0 rounded-br-lg bg-primary/90 px-1.5 py-0.5 text-caption text-background">
                    {t("coverImage")}
                  </span>
                )}
                {image.status === "uploading" && (
                  <div className="absolute inset-0 flex items-center justify-center bg-ink/40">
                    <span className="h-5 w-5 animate-spin rounded-full border-2 border-background border-t-transparent" />
                  </div>
                )}
                {image.status === "failed" && (
                  <div className="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-error/80 p-1 text-center">
                    <span className="text-caption font-medium text-background">{t("imageFailed")}</span>
                    {onRetry && (
                      <button
                        type="button"
                        onClick={() => onRetry(image.id)}
                        className="rounded-full bg-background px-2 py-0.5 text-caption font-semibold text-error"
                      >
                        {t("retryUpload")}
                      </button>
                    )}
                  </div>
                )}
                {image.status !== "uploading" && (
                  <button
                    type="button"
                    onClick={() => onDelete(image.id)}
                    className="absolute inset-x-0 bottom-0 bg-error/90 py-1 text-caption font-medium text-background opacity-0 transition-opacity group-hover:opacity-100"
                  >
                    {t("delete")}
                  </button>
                )}
              </div>
              {onAltTextBlur && (
                <input
                  type="text"
                  defaultValue={image.altText ?? ""}
                  placeholder={t("altText")}
                  onBlur={(event) => onAltTextBlur(image.id, event.target.value)}
                  className="w-full rounded-md border border-primary/15 bg-white px-1.5 py-1 text-caption text-ink focus:outline-none focus:ring-2 focus:ring-primary/30"
                />
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
