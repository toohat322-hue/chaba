// Client-side resize/compression before a product photo ever leaves the
// browser — no new backend dependency, and phone-camera photos (often
// 3000-4000px, several MB) shrink to a size the storefront actually needs
// without visibly hurting product photography quality. Backs off quality
// and then dimension until the result clears the server's 5MB cap
// (StoreProductImageRequest: image|max:5120), so a single detailed photo
// never dead-ends in a rejection the admin can't do anything about.
const MAX_DIMENSION = 2000;
const MIN_DIMENSION = 800;
const TARGET_BYTES = 4.5 * 1024 * 1024;
const SKIP_THRESHOLD_BYTES = 800 * 1024;
const JPEG_QUALITIES = [0.88, 0.75, 0.6, 0.45];

async function tryCompress(bitmap: ImageBitmap, outputType: string, dimension: number): Promise<Blob | null> {
  const scale = dimension / Math.max(bitmap.width, bitmap.height);
  const width = Math.round(bitmap.width * scale);
  const height = Math.round(bitmap.height * scale);
  const canvas = document.createElement("canvas");
  canvas.width = width;
  canvas.height = height;
  const ctx = canvas.getContext("2d");
  if (!ctx) return null;
  ctx.drawImage(bitmap, 0, 0, width, height);

  // PNG stays PNG (product cutouts may rely on transparency) — quality is
  // ignored for it, only dimension helps. Everything else re-encodes as
  // JPEG, backing off quality before ever shrinking dimensions further.
  const qualities = outputType === "image/jpeg" ? JPEG_QUALITIES : [undefined];
  let last: Blob | null = null;
  for (const quality of qualities) {
    const blob: Blob | null = await new Promise((resolve) => canvas.toBlob(resolve, outputType, quality));
    if (!blob) continue;
    last = blob;
    if (blob.size <= TARGET_BYTES) return blob;
  }
  return last;
}

export async function compressImageFile(file: File): Promise<File> {
  if (!file.type.startsWith("image/") || file.type === "image/gif") return file;

  const bitmap = await createImageBitmap(file).catch(() => null);
  if (!bitmap) return file; // unsupported in this browser — upload the original rather than fail silently

  const longestSide = Math.max(bitmap.width, bitmap.height);
  if (longestSide <= MAX_DIMENSION && file.size <= SKIP_THRESHOLD_BYTES) {
    bitmap.close?.();
    return file;
  }

  const outputType = file.type === "image/png" ? "image/png" : "image/jpeg";
  let dimension = Math.min(MAX_DIMENSION, longestSide);
  let best: Blob | null = null;

  while (dimension >= MIN_DIMENSION) {
    const blob = await tryCompress(bitmap, outputType, dimension);
    if (blob && (!best || blob.size < best.size)) best = blob;
    if (blob && blob.size <= TARGET_BYTES) break;
    dimension = Math.round(dimension * 0.75);
  }
  bitmap.close?.();

  if (!best) return file;
  const newName = outputType === "image/jpeg" ? file.name.replace(/\.\w+$/, "") + ".jpg" : file.name;
  const compressed = new File([best], newName, { type: outputType });
  // The resize/re-encode is meant to shrink — never hand back something
  // larger than the original (a pathological or already-optimized input).
  return compressed.size < file.size ? compressed : file;
}
