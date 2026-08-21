// PRD §9: skeleton screens, not spinners, for catalog loading states.
export function BestSellersRailSkeleton() {
  return (
    <section className="mx-auto max-w-7xl px-6 py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div className="space-y-2">
          <div className="flex items-center gap-3">
            <div className="h-11 w-11 shrink-0 animate-pulse rounded-full bg-primary/10" />
            <div className="h-7 w-48 animate-pulse rounded bg-primary/10" />
          </div>
          <div className="h-5 w-72 animate-pulse rounded bg-primary/5" />
        </div>
        <div className="h-5 w-32 animate-pulse rounded bg-primary/5" />
      </div>

      <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 md:gap-6 lg:grid-cols-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <div key={i} className="overflow-hidden rounded-2xl bg-white shadow-soft">
            <div className="aspect-square animate-pulse bg-primary/10" />
            <div className="space-y-2 p-4">
              <div className="h-4 w-3/4 animate-pulse rounded bg-primary/10" />
              <div className="h-4 w-1/3 animate-pulse rounded bg-primary/10" />
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}
