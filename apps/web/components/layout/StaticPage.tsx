export function StaticPageHeader({ title, intro }: { title: string; intro?: string }) {
  return (
    <div className="mx-auto max-w-3xl px-4 pt-6 pb-8 text-center sm:px-6">
      <h1 className="text-h1 font-semibold text-primary">{title}</h1>
      {intro && <p className="mt-4 text-body leading-relaxed text-ink/70">{intro}</p>}
    </div>
  );
}

export function StaticSection({ heading, body }: { heading: string; body: string }) {
  return (
    <section>
      <h2 className="text-h3 font-semibold text-primary">{heading}</h2>
      <p className="mt-2 text-body leading-relaxed text-ink/70">{body}</p>
    </section>
  );
}

export function StaticPageBody({ children }: { children: React.ReactNode }) {
  return <div className="mx-auto max-w-3xl space-y-8 px-4 pb-20 sm:px-6">{children}</div>;
}
