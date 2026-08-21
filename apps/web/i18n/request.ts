import { getRequestConfig } from "next-intl/server";
import { hasLocale } from "next-intl";
import { routing } from "./routing";
import arMessages from "../messages/ar.json";

type MessageNode = string | MessageNode[] | { [key: string]: MessageNode };

// Arabic is the canonical, always-complete locale (PRD default). If a key is
// ever missing from fr/en (e.g. added to ar.json but not yet translated),
// resolve it from the Arabic tree instead of surfacing a raw "Namespace.key"
// path or "undefined" to the shopper.
function resolveArFallback(namespace: string | undefined, key: string): string {
  const path = namespace ? `${namespace}.${key}` : key;
  const value = path
    .split(".")
    .reduce<MessageNode | undefined>(
      (node, segment) =>
        node && typeof node === "object" && !Array.isArray(node) ? node[segment] : undefined,
      arMessages as MessageNode,
    );

  return typeof value === "string" ? value : "";
}

export default getRequestConfig(async ({ requestLocale }) => {
  const requested = await requestLocale;
  const locale = hasLocale(routing.locales, requested)
    ? requested
    : routing.defaultLocale;

  return {
    locale,
    messages: (await import(`../messages/${locale}.json`)).default,
    onError(error) {
      if (process.env.NODE_ENV !== "production") {
        console.error(error);
      }
    },
    getMessageFallback({ namespace, key }) {
      return resolveArFallback(namespace, key);
    },
  };
});
