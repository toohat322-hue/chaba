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

function FacebookIcon() {
  return (
    <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
      <path
        fill="#1877F2"
        d="M24 12.07C24 5.63 18.63.5 12 .5S0 5.63 0 12.07c0 5.82 4.39 10.65 10.13 11.43v-8.08H7.08v-3.35h3.05V9.41c0-3 1.8-4.67 4.55-4.67 1.32 0 2.7.24 2.7.24v2.94h-1.52c-1.5 0-1.97.92-1.97 1.87v2.28h3.36l-.54 3.35h-2.82v8.08C19.61 22.72 24 17.89 24 12.07Z"
      />
    </svg>
  );
}

function AppleIcon() {
  return (
    <svg viewBox="0 0 24 24" className="h-5 w-5" fill="currentColor" aria-hidden="true">
      <path d="M16.36 1.43c0 1.14-.42 2.2-1.24 3.06-.99 1.03-2.2 1.62-3.5 1.5a3.7 3.7 0 0 1-.04-.46c0-1.1.5-2.24 1.28-3.02.82-.83 2.22-1.46 3.4-1.51.05.15.1.3.1.43ZM20.4 17.1c-.4.94-.87 1.83-1.44 2.68-.79 1.15-1.44 1.94-1.93 2.38-.76.72-1.58 1.09-2.45 1.11-.63 0-1.39-.18-2.27-.55-.88-.36-1.69-.55-2.42-.55-.77 0-1.6.19-2.5.55-.9.37-1.63.56-2.19.58-.84.04-1.68-.34-2.5-1.13-.53-.47-1.21-1.29-2.04-2.47-.89-1.27-1.62-2.74-2.19-4.42C.15 13.4-.16 11.66.08 9.99c.24-1.72.94-3.13 2.1-4.24.92-.9 2-1.35 3.22-1.37.66 0 1.53.2 2.61.62 1.08.42 1.77.62 2.08.62.23 0 1-.24 2.28-.71 1.22-.44 2.25-.62 3.09-.55 2.28.18 4 1.08 5.13 2.72-2.04 1.24-3.05 2.97-3.03 5.2.02 1.74.65 3.18 1.9 4.32.56.53 1.19.94 1.88 1.23-.15.44-.31.86-.5 1.27Z" />
    </svg>
  );
}

const PROVIDERS: { key: SocialProvider; Icon: () => React.ReactElement; labelKey: "continueWithGoogle" | "continueWithFacebook" | "continueWithApple" }[] = [
  { key: "google", Icon: GoogleIcon, labelKey: "continueWithGoogle" },
  { key: "facebook", Icon: FacebookIcon, labelKey: "continueWithFacebook" },
  { key: "apple", Icon: AppleIcon, labelKey: "continueWithApple" },
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
