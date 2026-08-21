"use client";

import { useState } from "react";

function Star({ filled }: { filled: boolean }) {
  return (
    <svg
      viewBox="0 0 24 24"
      fill={filled ? "currentColor" : "none"}
      stroke="currentColor"
      strokeWidth="1.5"
      className="h-7 w-7"
    >
      <path
        d="M12 2.5l2.9 6.3 6.9.7-5.2 4.7 1.5 6.8-6.1-3.6-6.1 3.6 1.5-6.8-5.2-4.7 6.9-.7z"
        strokeLinejoin="round"
      />
    </svg>
  );
}

export function StarRatingInput({ value, onChange }: { value: number; onChange: (value: number) => void }) {
  const [hover, setHover] = useState(0);

  return (
    <div className="flex items-center gap-1 text-accent" onMouseLeave={() => setHover(0)}>
      {[1, 2, 3, 4, 5].map((n) => (
        <button
          key={n}
          type="button"
          onClick={() => onChange(n)}
          onMouseEnter={() => setHover(n)}
          aria-label={`${n} / 5`}
          className="transition-transform hover:scale-110"
        >
          <Star filled={n <= (hover || value)} />
        </button>
      ))}
    </div>
  );
}
