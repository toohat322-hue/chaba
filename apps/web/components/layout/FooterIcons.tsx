/**
 * The one shared icon file in this project (every other icon is a local,
 * unexported component per its own file) — justified here because social/
 * payment/feature icons are selected dynamically by a slug that comes from
 * the database (FooterFeatureController/FooterSocialLinkController/
 * FooterPaymentMethodController), not statically known per call site.
 * Slugs are kept in sync by hand with the backend's validated `in:` lists
 * (StoreFooterFeatureRequest::ICONS, StoreFooterSocialLinkRequest::PLATFORMS,
 * StoreFooterPaymentMethodRequest::ICONS) — same manual-sync convention this
 * project already uses for messages/{ar,fr,en}.json.
 */
import type { ComponentType, SVGProps } from "react";

type IconComponent = ComponentType<SVGProps<SVGSVGElement>>;

function InstagramIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" {...props}>
      <rect x="3" y="3" width="18" height="18" rx="5" />
      <circle cx="12" cy="12" r="4" />
      <circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none" />
    </svg>
  );
}

function FacebookIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
      <path d="M14 22v-8h2.7l.4-3.3H14V8.5c0-.96.27-1.6 1.65-1.6H17V3.9c-.28-.04-1.25-.12-2.37-.12-2.35 0-3.96 1.43-3.96 4.06v2.27H8v3.3h2.67V22z" />
    </svg>
  );
}

function TiktokIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
      <path d="M16.5 2h-3v13.2a2.7 2.7 0 1 1-2.15-2.65V9.4a5.85 5.85 0 1 0 5.15 5.8V9.1a7.2 7.2 0 0 0 4 1.2V7.2a4.2 4.2 0 0 1-4-4.2z" />
    </svg>
  );
}

function SnapchatIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" {...props}>
      <path d="M12 3c2.6 0 4.4 2 4.4 4.6 0 1.5-.1 2.6.2 3.4.4.2 1.1.3 1.6.1.3-.1.7 0 .8.4.1.5-.2.8-.6 1-.5.3-1.2.5-1.5.9-.2.4.1.9.6 1.3.6.5 1.5.8 1.5 1.3 0 .6-1 .8-1.9.9-.3.5-.2 1.2-.6 1.5-.4.3-1.1.1-1.8.2-.7.1-1.2.9-2.7.9s-2-.8-2.7-.9c-.7-.1-1.4.1-1.8-.2-.4-.3-.3-1-.6-1.5-.9-.1-1.9-.3-1.9-.9 0-.5.9-.8 1.5-1.3.5-.4.8-.9.6-1.3-.3-.4-1-.6-1.5-.9-.4-.2-.7-.5-.6-1 .1-.4.5-.5.8-.4.5.2 1.2.1 1.6-.1.3-.8.2-1.9.2-3.4C7.6 5 9.4 3 12 3z" />
    </svg>
  );
}

function TwitterIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
      <path d="M18.9 3H21l-6.6 7.5L22.2 21h-6.1l-4.8-6.3L5.8 21H3.6l7-8-7.8-10h6.2l4.3 5.8zM17.8 19.2h1.7L7.9 4.7H6.1z" />
    </svg>
  );
}

function YoutubeIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" {...props}>
      <rect x="2.5" y="5.5" width="19" height="13" rx="3.5" />
      <path d="M10.5 9.5l5 2.5-5 2.5z" fill="currentColor" stroke="none" />
    </svg>
  );
}

function WhatsappIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" {...props}>
      <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.32 4.96L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2zm5.8 14.09c-.24.68-1.4 1.32-1.93 1.4-.5.08-1.11.11-1.79-.11a15 15 0 0 1-2.05-.75c-3.6-1.55-5.94-5.16-6.12-5.4-.18-.24-1.46-1.94-1.46-3.7s.93-2.62 1.26-2.98c.32-.35.71-.44.94-.44l.68.01c.22 0 .5-.08.79.6.29.71.99 2.45 1.08 2.62.09.18.15.39.03.63-.12.24-.18.39-.36.6-.18.21-.38.47-.54.63-.18.18-.37.37-.16.73.21.35.93 1.53 2 2.48 1.37 1.22 2.53 1.6 2.89 1.78.35.18.56.15.77-.09.21-.24.9-1.05 1.14-1.41.24-.35.47-.29.79-.18.32.12 2.05.97 2.4 1.14.35.18.59.26.68.41.09.15.09.85-.15 1.53z" />
    </svg>
  );
}

export const SOCIAL_ICONS: Record<string, IconComponent> = {
  instagram: InstagramIcon,
  facebook: FacebookIcon,
  tiktok: TiktokIcon,
  snapchat: SnapchatIcon,
  twitter: TwitterIcon,
  youtube: YoutubeIcon,
  whatsapp: WhatsappIcon,
};

function CashIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" {...props}>
      <rect x="2.5" y="6.5" width="19" height="11" rx="2" />
      <circle cx="12" cy="12" r="2.5" />
      <path d="M5.5 9v0M18.5 15v0" strokeLinecap="round" />
    </svg>
  );
}

function CardIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" {...props}>
      <rect x="2.5" y="5.5" width="19" height="13" rx="2.5" />
      <path d="M2.5 10h19" strokeLinecap="round" />
      <path d="M6 14.5h4" strokeLinecap="round" />
    </svg>
  );
}

const badgeClass = "flex h-7 w-11 shrink-0 items-center justify-center rounded-md";

// Distinct, colored, brand-evoking badges (color + wordmark, not a pixel
// reproduction of any registered logo mark) — the same convention used
// site-wide by e-commerce checkouts to signal "we accept X" without a
// trademark license. cod/card stay simple/neutral since they aren't a
// third-party brand.
function CodBadge() {
  return (
    <span className={`${badgeClass} bg-background/10 text-background/80`}>
      <CashIcon className="h-4 w-4" />
    </span>
  );
}
function CardBadge() {
  return (
    <span className={`${badgeClass} bg-background/10 text-background/70`}>
      <CardIcon className="h-4 w-4" />
    </span>
  );
}
function CibBadge() {
  return <span className={`${badgeClass} bg-[#0B5ED7] text-[10px] font-bold text-white`}>CIB</span>;
}
function EdahabiaBadge() {
  return <span className={`${badgeClass} bg-accent text-[9px] font-bold text-primary`}>ذهبية</span>;
}
function VisaBadge() {
  return <span className={`${badgeClass} bg-[#1A1F71] text-[11px] font-bold italic text-white`}>VISA</span>;
}
function MastercardBadge() {
  return (
    <span className={`${badgeClass} bg-white`}>
      <span className="relative flex h-4 w-7 items-center justify-center">
        <span className="absolute start-0 h-4 w-4 rounded-full bg-[#EB001B]" />
        <span className="absolute end-0 h-4 w-4 rounded-full bg-[#F79E1B] opacity-90" />
      </span>
    </span>
  );
}
function ApplePayBadge() {
  return <span className={`${badgeClass} bg-black text-[10px] font-semibold text-white`}> Pay</span>;
}
function MadaBadge() {
  return (
    <span className={`${badgeClass} bg-gradient-to-r from-[#1B7B45] to-[#0B4A8F] text-[9px] font-bold text-white`}>
      mada
    </span>
  );
}

export const PAYMENT_ICONS: Record<string, IconComponent> = {
  cod: CodBadge,
  cib: CibBadge,
  edahabia: EdahabiaBadge,
  visa: VisaBadge,
  mastercard: MastercardBadge,
  applepay: ApplePayBadge,
  mada: MadaBadge,
  card: CardBadge,
};

function TruckIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" {...props}>
      <path d="M2 7h11v9H2z" strokeLinejoin="round" />
      <path d="M13 10h4l4 3.2V16h-8z" strokeLinejoin="round" />
      <circle cx="6.5" cy="18" r="1.6" />
      <circle cx="16.5" cy="18" r="1.6" />
    </svg>
  );
}

function ShieldIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" {...props}>
      <path d="M12 3l7 3v5.5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z" />
    </svg>
  );
}

function ReturnIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" {...props}>
      <path d="M3 11a9 9 0 1 1 2.6 6.3" />
      <path d="M3 5v6h6" />
    </svg>
  );
}

function BadgeIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" {...props}>
      <circle cx="12" cy="9" r="6" />
      <path d="M9 14.5L7 21l5-2.5 5 2.5-2-6.5" />
    </svg>
  );
}

function ClockIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" {...props}>
      <circle cx="12" cy="12" r="9" />
      <path d="M12 7v5l3.5 2" />
    </svg>
  );
}

function GiftIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" {...props}>
      <rect x="3" y="9" width="18" height="4" />
      <rect x="4.5" y="13" width="15" height="8" />
      <path d="M12 9v12M12 9C10 6 6 6 6 8.5S9 9 12 9zM12 9c2-3 6-3 6-.5S15 9 12 9z" />
    </svg>
  );
}

function StarIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" {...props}>
      <path d="M12 3v4M12 17v4M3 12h4M17 12h4" strokeLinecap="round" />
      <path d="M12 9a3 3 0 0 0 3 3 3 3 0 0 0-3 3 3 3 0 0 0-3-3 3 3 0 0 0 3-3z" />
    </svg>
  );
}

function LockIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" {...props}>
      <rect x="5" y="11" width="14" height="9" rx="2" />
      <path d="M8 11V8a4 4 0 0 1 8 0v3" strokeLinecap="round" />
    </svg>
  );
}

export const FEATURE_ICONS: Record<string, IconComponent> = {
  truck: TruckIcon,
  shield: ShieldIcon,
  return: ReturnIcon,
  badge: BadgeIcon,
  clock: ClockIcon,
  gift: GiftIcon,
  star: StarIcon,
  lock: LockIcon,
};

/** Defensive fallback for any slug not yet mapped on the frontend (new DB
 * content can exist before frontend support is deployed for it). */
export function GenericIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" {...props}>
      <circle cx="12" cy="12" r="9" />
    </svg>
  );
}
