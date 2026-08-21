function Star({ filled }: { filled: boolean }) {
  return (
    <svg
      viewBox="0 0 24 24"
      fill={filled ? "currentColor" : "none"}
      stroke="currentColor"
      strokeWidth="1.5"
      className="h-4 w-4"
    >
      <path
        d="M12 2.5l2.9 6.3 6.9.7-5.2 4.7 1.5 6.8-6.1-3.6-6.1 3.6 1.5-6.8-5.2-4.7 6.9-.7z"
        strokeLinejoin="round"
      />
    </svg>
  );
}

export function StarRating({ value, className = "" }: { value: number; className?: string }) {
  return (
    <div className={`flex items-center gap-0.5 text-accent ${className}`} aria-label={`${value} / 5`}>
      {[1, 2, 3, 4, 5].map((n) => (
        <Star key={n} filled={n <= Math.round(value)} />
      ))}
    </div>
  );
}
