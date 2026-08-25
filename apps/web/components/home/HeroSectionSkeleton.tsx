// PRD §9: skeleton screens, not spinners — same min-height as the real hero
// section (HeroBanner/HeroSlider) so nothing shifts once it resolves.
export function HeroSectionSkeleton() {
  return (
    <section className="relative isolate flex min-h-[75vh] animate-pulse items-end overflow-hidden bg-primary lg:min-h-[720px]">
      <div className="relative z-10 mx-auto flex w-full max-w-2xl flex-col items-center gap-5 px-6 pb-16 text-center sm:pb-20">
        <div className="h-14 w-14 rounded-xl bg-background/10 sm:h-16 sm:w-16" />
        <div className="flex flex-col items-center gap-2.5">
          <div className="h-8 w-64 rounded bg-background/10 sm:w-96" />
          <div className="h-5 w-48 rounded bg-background/10 sm:w-72" />
        </div>
        <div className="mt-2 h-12 w-40 rounded-md bg-background/10" />
      </div>
    </section>
  );
}
