import { describe, expect, it, vi, afterEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { NextIntlClientProvider } from "next-intl";
import { NewsletterForm } from "./NewsletterForm";
import { subscribeNewsletter } from "@/lib/api";

vi.mock("@/lib/api", () => ({ subscribeNewsletter: vi.fn() }));

const messages = {
  Footer: {
    newsletterHeading: "Subscribe to our newsletter",
    newsletterBody: "Be the first to know.",
    emailPlaceholder: "Your email",
    subscribeButton: "Subscribe",
    subscribeLoading: "Subscribing...",
    subscribeSuccess: "Subscribed successfully!",
    subscribeError: "Couldn't subscribe.",
  },
};

function renderForm() {
  return render(
    <NextIntlClientProvider locale="en" messages={messages}>
      <NewsletterForm />
    </NextIntlClientProvider>,
  );
}

describe("NewsletterForm", () => {
  afterEach(() => {
    vi.mocked(subscribeNewsletter).mockReset();
  });

  it("shows a success message after a successful subscribe", async () => {
    vi.mocked(subscribeNewsletter).mockResolvedValue({ id: "1", email: "a@example.com" });
    const user = userEvent.setup();
    renderForm();

    await user.type(screen.getByPlaceholderText("Your email"), "a@example.com");
    await user.click(screen.getByRole("button", { name: "Subscribe" }));

    await waitFor(() => expect(screen.getByText("Subscribed successfully!")).toBeInTheDocument());
    expect(subscribeNewsletter).toHaveBeenCalledWith("a@example.com", "en");
    // The field clears on success so the user can see the confirmation, not a stale value.
    expect(screen.getByPlaceholderText("Your email")).toHaveValue("");
  });

  it("shows an error message and keeps the typed email when the request fails", async () => {
    vi.mocked(subscribeNewsletter).mockRejectedValue(new Error("duplicate"));
    const user = userEvent.setup();
    renderForm();

    await user.type(screen.getByPlaceholderText("Your email"), "dup@example.com");
    await user.click(screen.getByRole("button", { name: "Subscribe" }));

    await waitFor(() => expect(screen.getByText("Couldn't subscribe.")).toBeInTheDocument());
    expect(screen.getByPlaceholderText("Your email")).toHaveValue("dup@example.com");
  });
});
