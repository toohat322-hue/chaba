"use client";

import { useEffect, useRef, useState, type FormEvent } from "react";
import { useLocale, useTranslations } from "next-intl";
import { Link, useRouter } from "@/i18n/navigation";
import { useCart } from "@/components/cart/CartProvider";
import { CartTotals } from "@/components/cart/CartTotals";
import { useCustomerAuth } from "@/components/customer/CustomerAuthProvider";
import { SocialLoginButtons } from "@/components/customer/SocialLoginButtons";
import { useToast } from "@/components/ui/Toast";
import { ShabaLoader } from "@/components/ui/ShabaLoader";
import { formatPrice, formatSize } from "@/lib/currency";
import {
  ApiError,
  checkout,
  getAddresses,
  getCommunes,
  getDeliveryFee,
  getFooterData,
  getWilayas,
  notifyWhatsAppOpened,
  type Address,
  type Commune,
  type Wilaya,
  type WhatsAppCheckoutInfo,
} from "@/lib/api";

type Locale = "ar" | "fr" | "en";

const inputClass =
  "w-full rounded-lg border border-primary/15 bg-white px-4 py-3.5 text-body text-ink focus:outline-none focus:ring-2 focus:ring-primary/30";

export default function CheckoutPage() {
  const t = useTranslations("Checkout");
  const tAuth = useTranslations("Auth");
  const tProduct = useTranslations("Product");
  const tCart = useTranslations("Cart");
  const locale = useLocale() as Locale;
  const router = useRouter();
  const toast = useToast();
  const { cart, loading: cartLoading } = useCart();
  const { customer, loading: authLoading } = useCustomerAuth();

  const [wilayas, setWilayas] = useState<Wilaya[]>([]);
  const [communes, setCommunes] = useState<Commune[]>([]);
  const [deliveryMethod, setDeliveryMethod] = useState<"home" | "pickup">("home");
  const [homeFee, setHomeFee] = useState<number | null>(null);
  const [pickupFee, setPickupFee] = useState<number | null>(null);

  const [savedAddresses, setSavedAddresses] = useState<Address[]>([]);
  const [selectedAddressId, setSelectedAddressId] = useState<string | null>(null);
  const [useNewAddress, setUseNewAddress] = useState(false);

  const [customerName, setCustomerName] = useState("");
  const [customerPhone, setCustomerPhone] = useState("");
  const [customerEmail, setCustomerEmail] = useState("");
  const [wilayaCode, setWilayaCode] = useState("");
  const [communeId, setCommuneId] = useState("");
  const [addressLine, setAddressLine] = useState("");
  const [landmark, setLandmark] = useState("");
  const [postalCode, setPostalCode] = useState("");
  const [notes, setNotes] = useState("");

  const [whatsappAvailable, setWhatsappAvailable] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState<"cod" | "whatsapp">("cod");

  const [submitting, setSubmitting] = useState(false);
  const [stage, setStage] = useState<"idle" | "confirming" | "opening">("idle");
  const [error, setError] = useState<string | null>(null);
  const [placedOrderNumber, setPlacedOrderNumber] = useState<string | null>(null);
  const [pendingWhatsapp, setPendingWhatsapp] = useState<WhatsAppCheckoutInfo | null>(null);
  const mountedRef = useRef(true);

  useEffect(() => {
    return () => {
      mountedRef.current = false;
    };
  }, []);

  useEffect(() => {
    getWilayas()
      .then(setWilayas)
      .catch(() => setWilayas([]));
  }, []);

  useEffect(() => {
    getFooterData()
      .then((data) => {
        const available = Boolean(data.settings.whatsapp.orders_enabled && data.settings.whatsapp.number);
        setWhatsappAvailable(available);
        if (available) setPaymentMethod("whatsapp");
      })
      .catch(() => setWhatsappAvailable(false));
  }, []);

  useEffect(() => {
    if (authLoading || !customer) return;

    // eslint-disable-next-line react-hooks/set-state-in-effect -- prefilling from the session-hydrated customer identity, not deriving state from a render value
    setCustomerName((prev) => prev || customer.full_name);
    setCustomerPhone((prev) => prev || customer.phone);
    setCustomerEmail((prev) => prev || customer.email || "");

    getAddresses()
      .then((addresses) => {
        setSavedAddresses(addresses);
        const defaultAddress = addresses.find((a) => a.is_default) ?? addresses[0];
        if (defaultAddress) {
          setSelectedAddressId(defaultAddress.id);
        } else {
          setUseNewAddress(true);
        }
      })
      .catch(() => setUseNewAddress(true));
  }, [authLoading, customer]);

  const selectedAddress = savedAddresses.find((a) => a.id === selectedAddressId) ?? null;
  const activeWilayaCode = !useNewAddress && selectedAddress ? selectedAddress.wilaya_code : wilayaCode;

  useEffect(() => {
    if (!activeWilayaCode) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- resetting the fee display when no wilaya is active, not deriving state from a render value
      setHomeFee(null);
      setPickupFee(null);
      return;
    }

    getDeliveryFee(activeWilayaCode)
      .then((data) => {
        setHomeFee(data.home?.fee ?? null);
        setPickupFee(data.pickup?.fee ?? null);
      })
      .catch(() => {
        setHomeFee(null);
        setPickupFee(null);
      });
  }, [activeWilayaCode]);

  // Pickup isn't configured for every wilaya — if the customer had it
  // selected and then switched to a wilaya where it isn't available, this
  // falls back to home delivery for every calculation/submission below
  // rather than leaving an unusable option selected. Derived at render
  // time (not via an effect) so it takes effect the instant pickupFee
  // changes, with no extra render round-trip.
  const effectiveDeliveryMethod = deliveryMethod === "pickup" && pickupFee === null ? "home" : deliveryMethod;

  useEffect(() => {
    if (!wilayaCode) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- resetting dependent fields when the wilaya selection is cleared, not deriving state from a prop/render value
      setCommunes([]);
      setCommuneId("");
      return;
    }

    getCommunes(wilayaCode)
      .then(setCommunes)
      .catch(() => setCommunes([]));
    setCommuneId("");
  }, [wilayaCode]);

  const subtotal = cart?.subtotal ?? 0;
  const discountTotal = cart?.discount_total ?? 0;
  const isFreeShipping = cart?.coupon?.type === "free_shipping";
  const selectedDeliveryFee = (effectiveDeliveryMethod === "pickup" ? pickupFee : homeFee) ?? 0;
  const effectiveDeliveryFee = isFreeShipping ? 0 : selectedDeliveryFee;
  const total = subtotal - discountTotal + effectiveDeliveryFee;

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);
    setStage("confirming");

    try {
      const usingSavedAddress = !useNewAddress && selectedAddress;

      const { order, whatsapp } = await checkout({
        customer_name: customerName,
        customer_phone: customerPhone,
        customer_email: customerEmail || undefined,
        ...(usingSavedAddress
          ? { address_id: selectedAddress.id }
          : {
              address: {
                full_name: customerName,
                phone: customerPhone,
                wilaya_code: wilayaCode,
                commune_id: communeId,
                address_line: addressLine,
                landmark: landmark || undefined,
                postal_code: postalCode || undefined,
              },
            }),
        delivery_method: effectiveDeliveryMethod,
        payment_method: paymentMethod,
        notes: notes || undefined,
        locale,
      });

      window.sessionStorage.setItem("chaba_last_order", JSON.stringify(order));

      if (!whatsapp) {
        router.push(`/orders/${order.order_number}/confirmation`);
        return;
      }

      toast.show({ message: t("orderSavedShort", { orderNumber: order.order_number }) });
      setStage("opening");
      setPlacedOrderNumber(order.order_number);
      setPendingWhatsapp(whatsapp);

      // Fire-and-forget — never blocks or affects the redirect itself.
      notifyWhatsAppOpened(order.order_number, customerPhone);
      window.location.href = whatsapp.url;

      // If the browser blocked the redirect (e.g. a popup/deep-link
      // restriction), this component is still mounted after a short delay —
      // show a manual fallback reusing the same order + whatsapp payload,
      // never calling checkout() again.
      window.setTimeout(() => {
        if (mountedRef.current) {
          setSubmitting(false);
          setStage("idle");
        }
      }, 1500);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : t("errorGeneric"));
      toast.show({ message: err instanceof ApiError ? err.message : t("errorGeneric") });
      setSubmitting(false);
      setStage("idle");
    }
  }

  if (cartLoading) {
    return <div className="mx-auto max-w-3xl px-6 py-16" />;
  }

  if (!cart || cart.items.length === 0) {
    return (
      <div className="mx-auto flex max-w-3xl flex-col items-center gap-4 px-6 py-24 text-center">
        <p className="text-body text-ink/60">{tCart("empty")}</p>
        <Link
          href="/"
          className="rounded-full bg-primary px-6 py-3 text-body font-semibold text-background hover:bg-primary/90"
        >
          {tCart("browseCategories")}
        </Link>
      </div>
    );
  }

  const showAddressPicker = customer && savedAddresses.length > 0 && !useNewAddress;

  return (
    <div className="mx-auto max-w-4xl px-6 py-10">
      <h1 className="mb-8 text-h1 font-semibold text-primary">{t("title")}</h1>

      <form onSubmit={handleSubmit} className="grid gap-10 md:grid-cols-[1.5fr_1fr]">
        <div className="space-y-8">
          {!authLoading && !customer && (
            <section className="rounded-2xl border border-primary/10 bg-white p-5">
              <h2 className="mb-1 text-h3 font-semibold text-ink">{t("haveAccountTitle")}</h2>
              <p className="mb-4 text-caption text-ink/50">{t("haveAccountHint")}</p>
              <SocialLoginButtons returnTo="/checkout" locale={locale} />
            </section>
          )}

          <section>
            <h2 className="mb-4 text-h3 font-semibold text-ink">{t("customerInfoTitle")}</h2>
            <div className="space-y-4">
              <input
                type="text"
                required
                placeholder={t("fullName")}
                value={customerName}
                onChange={(e) => setCustomerName(e.target.value)}
                className={inputClass}
              />
              <input
                type="tel"
                required
                placeholder={t("phone")}
                value={customerPhone}
                onChange={(e) => setCustomerPhone(e.target.value)}
                className={inputClass}
              />
              <input
                type="email"
                placeholder={t("email")}
                value={customerEmail}
                onChange={(e) => setCustomerEmail(e.target.value)}
                className={inputClass}
              />
            </div>
          </section>

          <section>
            <h2 className="mb-4 text-h3 font-semibold text-ink">{t("deliveryAddressTitle")}</h2>

            {showAddressPicker ? (
              <div className="space-y-3">
                {savedAddresses.map((address) => (
                  <label
                    key={address.id}
                    className={`flex cursor-pointer items-start gap-3 rounded-lg border-2 px-4 py-4 transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent/50 ${
                      selectedAddressId === address.id ? "border-primary bg-primary/5" : "border-primary/10 hover:border-primary/25"
                    }`}
                  >
                    <input
                      type="radio"
                      name="saved-address"
                      checked={selectedAddressId === address.id}
                      onChange={() => setSelectedAddressId(address.id)}
                      className="mt-1 h-4 w-4 accent-primary"
                    />
                    <span className="text-small text-ink">
                      <span className="block font-medium">{address.full_name} · {address.phone}</span>
                      <span className="block text-ink/60">{address.address_line}</span>
                    </span>
                  </label>
                ))}
                <button
                  type="button"
                  onClick={() => setUseNewAddress(true)}
                  className="text-small font-medium text-primary hover:underline"
                >
                  {tAuth("addAddress")}
                </button>
              </div>
            ) : (
              <div className="space-y-4">
                {customer && savedAddresses.length > 0 && (
                  <button
                    type="button"
                    onClick={() => setUseNewAddress(false)}
                    className="text-small font-medium text-primary hover:underline"
                  >
                    {tAuth("myAddresses")}
                  </button>
                )}

                <select
                  required
                  value={wilayaCode}
                  onChange={(e) => setWilayaCode(e.target.value)}
                  className={inputClass}
                >
                  <option value="" disabled>
                    {t("selectWilaya")}
                  </option>
                  {wilayas.map((wilaya) => (
                    <option key={wilaya.code} value={wilaya.code}>
                      {wilaya.name[locale]}
                    </option>
                  ))}
                </select>

                <select
                  required
                  disabled={!wilayaCode}
                  value={communeId}
                  onChange={(e) => setCommuneId(e.target.value)}
                  className={inputClass}
                >
                  <option value="" disabled>
                    {t("selectCommune")}
                  </option>
                  {communes.map((commune) => (
                    <option key={commune.id} value={commune.id}>
                      {commune.name[locale]}
                    </option>
                  ))}
                </select>

                <input
                  type="text"
                  required
                  placeholder={t("addressLine")}
                  value={addressLine}
                  onChange={(e) => setAddressLine(e.target.value)}
                  className={inputClass}
                />
                <input
                  type="text"
                  placeholder={t("landmark")}
                  value={landmark}
                  onChange={(e) => setLandmark(e.target.value)}
                  className={inputClass}
                />
                <input
                  type="text"
                  placeholder={t("postalCode")}
                  value={postalCode}
                  onChange={(e) => setPostalCode(e.target.value)}
                  className={inputClass}
                />
              </div>
            )}
          </section>

          <section>
            <h2 className="mb-4 text-h3 font-semibold text-ink">{t("deliveryMethodTitle")}</h2>
            <div className="space-y-2">
              <label
                className={`flex cursor-pointer items-center justify-between rounded-lg border-2 px-4 py-4 transition-colors ${
                  effectiveDeliveryMethod === "home" ? "border-primary bg-primary/5" : "border-primary/10 hover:border-primary/25"
                }`}
              >
                <span className="flex items-center gap-3">
                  <input
                    type="radio"
                    name="delivery-method"
                    checked={effectiveDeliveryMethod === "home"}
                    onChange={() => setDeliveryMethod("home")}
                    className="h-4 w-4 accent-primary"
                  />
                  <span className="text-body font-medium text-ink">{t("homeDelivery")}</span>
                </span>
                <span className="text-small font-medium text-primary">{formatPrice(homeFee ?? 0, locale)}</span>
              </label>
              {pickupFee !== null ? (
                <label
                  className={`flex cursor-pointer items-center justify-between rounded-lg border-2 px-4 py-4 transition-colors ${
                    effectiveDeliveryMethod === "pickup" ? "border-primary bg-primary/5" : "border-primary/10 hover:border-primary/25"
                  }`}
                >
                  <span className="flex items-center gap-3">
                    <input
                      type="radio"
                      name="delivery-method"
                      checked={effectiveDeliveryMethod === "pickup"}
                      onChange={() => setDeliveryMethod("pickup")}
                      className="h-4 w-4 accent-primary"
                    />
                    <span className="text-body font-medium text-ink">{t("pickupDelivery")}</span>
                  </span>
                  <span className="text-small font-medium text-primary">{formatPrice(pickupFee, locale)}</span>
                </label>
              ) : (
                <div className="flex items-center justify-between rounded-lg border border-primary/10 px-4 py-4 opacity-50">
                  <span className="text-body text-ink/60">{t("pickupDelivery")}</span>
                </div>
              )}
            </div>
          </section>

          <section>
            <h2 className="mb-4 text-h3 font-semibold text-ink">{t("paymentMethodTitle")}</h2>
            <div className="space-y-2">
              {whatsappAvailable && (
                <label
                  className={`flex cursor-pointer items-center justify-between rounded-lg border-2 px-4 py-4 transition-colors ${
                    paymentMethod === "whatsapp" ? "border-primary bg-primary/5" : "border-primary/10 hover:border-primary/25"
                  }`}
                >
                  <span className="flex items-center gap-3">
                    <input
                      type="radio"
                      name="payment-method"
                      checked={paymentMethod === "whatsapp"}
                      onChange={() => setPaymentMethod("whatsapp")}
                      className="h-4 w-4 accent-primary"
                    />
                    <span className="text-body font-medium text-ink">{t("whatsappPaymentOption")}</span>
                  </span>
                </label>
              )}
              <label
                className={`flex cursor-pointer items-center justify-between rounded-lg border-2 px-4 py-4 transition-colors ${
                  paymentMethod === "cod" ? "border-primary bg-primary/5" : "border-primary/10 hover:border-primary/25"
                }`}
              >
                <span className="flex items-center gap-3">
                  {whatsappAvailable && (
                    <input
                      type="radio"
                      name="payment-method"
                      checked={paymentMethod === "cod"}
                      onChange={() => setPaymentMethod("cod")}
                      className="h-4 w-4 accent-primary"
                    />
                  )}
                  <span className="text-body font-medium text-ink">{t("cod")}</span>
                </span>
              </label>
              <div className="flex items-center justify-between rounded-lg border border-primary/10 px-4 py-4 opacity-50">
                <span className="text-body text-ink/60">{t("onlinePayment")}</span>
                <span className="text-caption text-ink/40">({tProduct("comingSoon")})</span>
              </div>
            </div>
          </section>

          <section>
            <textarea
              placeholder={t("notesPlaceholder")}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={3}
              className={inputClass}
            />
          </section>
        </div>

        <aside className="h-fit rounded-2xl bg-white p-6 shadow-soft">
          <h2 className="mb-4 text-h3 font-semibold text-ink">{t("orderSummaryTitle")}</h2>

          <ul className="mb-4 space-y-2 text-small text-ink/70">
            {cart.items.map((item) => (
              <li key={item.id} className="flex items-center justify-between gap-3">
                <span>
                  {item.product_name[locale]}
                  {item.size_value != null && item.size_unit && (
                    <span className="text-ink/50"> — {formatSize(item.size_value, item.size_unit, locale)}</span>
                  )}{" "}
                  × {item.quantity}
                </span>
                <span>{formatPrice(item.current_price * item.quantity, locale)}</span>
              </li>
            ))}
          </ul>

          <div className="border-t border-primary/10 pt-4">
            <CartTotals
              subtotal={subtotal}
              discount={discountTotal}
              deliveryFee={effectiveDeliveryFee}
              freeShipping={isFreeShipping}
              total={total}
              locale={locale}
              size="compact"
            />
          </div>

          {stage === "confirming" || stage === "opening" ? (
            <div className="mt-6">
              <ShabaLoader label={stage === "confirming" ? t("confirmingOrder") : t("openingWhatsApp")} />
              <p className="-mt-4 text-center text-small font-medium text-ink/70">
                {stage === "confirming" ? t("confirmingOrder") : t("openingWhatsApp")}
              </p>
            </div>
          ) : pendingWhatsapp && placedOrderNumber ? (
            <div className="mt-6 space-y-3 rounded-lg border border-primary/10 px-4 py-5 text-center">
              <p className="text-small font-medium text-ink">{t("orderSavedShort", { orderNumber: placedOrderNumber })}</p>
              <p className="text-small text-ink/60">{t("whatsappFallbackHint")}</p>
              <a
                href={pendingWhatsapp.url}
                className="flex w-full items-center justify-center rounded-full bg-primary px-6 py-3.5 text-body font-semibold text-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
              >
                {t("openWhatsAppManually")}
              </a>
            </div>
          ) : (
            <>
              {error && (
                <p className="mt-4 text-small text-error" role="alert">
                  {error}
                </p>
              )}

              <button
                type="submit"
                disabled={submitting}
                className="mt-6 flex w-full items-center justify-center gap-2 rounded-full bg-primary px-6 py-3.5 text-body font-semibold text-background transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/50"
              >
                {paymentMethod === "whatsapp" ? t("placeOrderWhatsapp") : t("placeOrder")}
              </button>
            </>
          )}
        </aside>
      </form>
    </div>
  );
}
