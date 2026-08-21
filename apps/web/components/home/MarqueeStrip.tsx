import { useTranslations } from "next-intl";

function SparkleIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="h-3 w-3 shrink-0">
      <path d="M12 3v4M12 17v4M3 12h4M17 12h4" strokeLinecap="round" />
      <path d="M12 9a3 3 0 0 0 3 3 3 3 0 0 0-3 3 3 3 0 0 0-3-3 3 3 0 0 0 3-3z" strokeLinejoin="round" />
    </svg>
  );
}

export function MarqueeStrip() {
  const t = useTranslations("Marquee");
  const items = t.raw("items") as string[];
  // Rendered twice back-to-back so the 50%-translate keyframe (globals.css)
  // loops seamlessly with no visible seam or jump.
  const doubled = [...items, ...items];

  return (
    <div className="overflow-hidden border-y border-accent/20 bg-primary py-3">
      <div className="animate-marquee flex w-max gap-10 whitespace-nowrap">
        {doubled.map((item, i) => (
          <span key={i} className="flex items-center gap-10 text-small font-medium tracking-wide text-background/90">
            {item}
            <span className="text-accent" aria-hidden="true">
              <SparkleIcon />
            </span>
          </span>
        ))}
      </div>
    </div>
  );
}
