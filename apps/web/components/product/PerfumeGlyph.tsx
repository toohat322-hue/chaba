/**
 * Brand-palette placeholder art standing in for real product/hero photography
 * (none exists yet). A simple bottle silhouette on a soft teal→gold gradient,
 * reused at different sizes in the hero and product cards so both share one
 * visual identity until real photos are dropped in during content population.
 */
export function PerfumeGlyph({ className }: { className?: string }) {
  return (
    <svg
      viewBox="0 0 200 200"
      className={className}
      role="presentation"
      aria-hidden="true"
    >
      <defs>
        <linearGradient id="chaba-glyph-bg" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="#0e3b36" />
          <stop offset="100%" stopColor="#175c53" />
        </linearGradient>
        <linearGradient id="chaba-glyph-bottle" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="#c9a24b" />
          <stop offset="100%" stopColor="#e8cf8f" />
        </linearGradient>
      </defs>
      <rect width="200" height="200" fill="url(#chaba-glyph-bg)" />
      <rect x="90" y="35" width="20" height="18" rx="3" fill="#22272b" />
      <rect x="82" y="53" width="36" height="14" rx="4" fill="url(#chaba-glyph-bottle)" />
      <path
        d="M70 75 h60 a8 8 0 0 1 8 8 v78 a10 10 0 0 1 -10 10 h-56 a10 10 0 0 1 -10 -10 v-78 a8 8 0 0 1 8 -8 z"
        fill="url(#chaba-glyph-bottle)"
        opacity="0.92"
      />
      <rect x="70" y="120" width="60" height="8" fill="#0e3b36" opacity="0.25" />
    </svg>
  );
}
