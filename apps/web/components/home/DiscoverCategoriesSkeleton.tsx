// PRD §9: skeleton screens, not spinners. Renders unconditionally (unlike
// the real component, which returns null for zero categories) since we
// don't know the count yet — a brief generic skeleton beats nothing.
export function DiscoverCategoriesSkeleton() {
  return (
    <section className="mx-auto max-w-7xl px-6 py-16">
      <div className="text-center">
        <div className="mx-auto h-7 w-56 animate-pulse rounded bg-primary/10" />
        <div className="mx-auto mt-2 h-5 w-72 animate-pulse rounded bg-primary/5" />
      </div>
      <div className="mt-10 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="aspect-[4/5] animate-pulse rounded-2xl bg-primary/10" />
        ))}
      </div>
    </section>
  );
}
