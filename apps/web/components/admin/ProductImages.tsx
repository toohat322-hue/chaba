"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { deleteProductImage, reorderProductImages, updateProductImage, uploadProductImage, type AdminProduct } from "@/lib/api";
import { adminErrorMessage } from "@/lib/adminErrors";
import { compressImageFile } from "@/lib/imageCompression";
import { ImageDropzone, type DropzoneImage } from "./ImageDropzone";

type ImageItem = AdminProduct["images"][number];

type TrackedImage = ImageItem & {
  status: "uploaded" | "uploading" | "failed";
  pendingFile?: File; // kept only while status === "failed" so retry can re-send the same file
};

let localIdCounter = 0;

export function ProductImages({ productId, initial }: { productId: string; initial: ImageItem[] }) {
  const t = useTranslations("Admin");
  const [images, setImages] = useState<TrackedImage[]>(initial.map((image) => ({ ...image, status: "uploaded" })));
  const [error, setError] = useState<string | null>(null);

  async function uploadOne(file: File, localId: string) {
    setImages((prev) => prev.map((img) => (img.id === localId ? { ...img, status: "uploading" } : img)));
    try {
      const compressed = await compressImageFile(file);
      const uploaded = await uploadProductImage(productId, compressed);
      setImages((prev) => prev.map((img) => (img.id === localId ? { ...uploaded, status: "uploaded" } : img)));
    } catch (err) {
      setImages((prev) =>
        prev.map((img) => (img.id === localId ? { ...img, status: "failed", pendingFile: file } : img)),
      );
      setError(adminErrorMessage(err, t));
    }
  }

  function handleFilesSelected(files: File[]) {
    setError(null);
    const placeholders: TrackedImage[] = files.map((file) => ({
      id: `local-${++localIdCounter}`,
      url: URL.createObjectURL(file),
      alt_text: null,
      sort_order: images.length,
      status: "uploading",
      pendingFile: file,
    }));
    setImages((prev) => [...prev, ...placeholders]);
    placeholders.forEach((placeholder) => {
      void uploadOne(placeholder.pendingFile as File, placeholder.id);
    });
  }

  function handleRetry(id: string) {
    const target = images.find((img) => img.id === id);
    if (!target?.pendingFile) return;
    void uploadOne(target.pendingFile, id);
  }

  async function handleDelete(id: string) {
    const target = images.find((img) => img.id === id);
    if (!target) return;
    if (target.status !== "uploaded") {
      // A still-uploading/failed placeholder — nothing on the server yet.
      setImages((prev) => prev.filter((img) => img.id !== id));
      return;
    }
    setError(null);
    try {
      await deleteProductImage(productId, id);
      setImages((prev) => prev.filter((img) => img.id !== id));
    } catch (err) {
      setError(adminErrorMessage(err, t));
    }
  }

  async function handleAltTextBlur(id: string, altText: string) {
    const current = images.find((img) => img.id === id);
    if (!current || current.status !== "uploaded" || current.alt_text === altText) return;
    try {
      const updated = await updateProductImage(productId, id, altText);
      setImages((prev) => prev.map((img) => (img.id === id ? { ...img, alt_text: updated.alt_text } : img)));
    } catch (err) {
      setError(adminErrorMessage(err, t));
    }
  }

  async function handleReorder(orderedIds: string[]) {
    const reordered = orderedIds
      .map((id) => images.find((img) => img.id === id))
      .filter((img): img is TrackedImage => img !== undefined);
    setImages(reordered);

    // Only persist ordering among already-uploaded images — a still-
    // uploading or failed placeholder has no server row to reorder yet.
    const uploadedIds = reordered.filter((img) => img.status === "uploaded").map((img) => img.id);
    if (uploadedIds.length < 2) return;
    try {
      await reorderProductImages(productId, uploadedIds);
    } catch (err) {
      setError(adminErrorMessage(err, t));
    }
  }

  const dropzoneImages: DropzoneImage[] = images.map((img) => ({
    id: img.id,
    url: img.url,
    altText: img.alt_text,
    status: img.status,
  }));

  return (
    <div className="max-w-2xl space-y-3">
      <ImageDropzone
        images={dropzoneImages}
        onFilesSelected={handleFilesSelected}
        onDelete={handleDelete}
        onReorder={handleReorder}
        onRetry={handleRetry}
        onAltTextBlur={handleAltTextBlur}
      />
      {error && <p className="text-small text-error">{error}</p>}
    </div>
  );
}
