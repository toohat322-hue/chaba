import { useTranslations } from "next-intl";
import { socialRedirectUrl, type SocialProvider } from "@/lib/api";

function GoogleIcon() {
  return (
    <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
      <path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.63h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.87c2.27-2.09 3.58-5.17 3.58-8.81Z" />
      <path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.92l-3.87-3c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.27v3.11A12 12 0 0 0 12 24Z" />
      <path fill="#FBBC05" d="M5.27 14.27a7.2 7.2 0 0 1 0-4.54v-3.1H1.27a12 12 0 0 0 0 10.75l4-3.11Z" />
      <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.27 6.63l4 3.1C6.22 6.86 8.87 4.75 12 4.75Z" />
    </svg>
  );
}

const PROVIDERS: { key: SocialProvider; Icon: () => React.ReactElement; labelKey: "continueWithGoogle" }[] = [
  { key: "google", Icon: GoogleIcon, labelKey: "continueWithGoogle" },
];

export function SocialLoginButtons({ returnTo, locale }: { returnTo: string; locale: string }) {
  const t = useTranslations("Auth");

  return (
    <div className="space-y-2.5">
      {PROVIDERS.map(({ key, Icon, labelKey }) => (
        <a
          key={key}
          href={socialRedirectUrl(key, returnTo, locale)}
          className="flex w-full items-center justify-center gap-3 rounded-full border border-primary/15 bg-white px-6 py-3 text-body font-medium text-ink transition-colors hover:border-primary/30 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
        >
          <Icon />
          {t(labelKey)}
        </a>
      ))}
    </div>
  );
}
