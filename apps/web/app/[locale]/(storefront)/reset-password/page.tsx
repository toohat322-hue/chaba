import { getTranslations } from "next-intl/server";
import { ResetPasswordForm } from "@/components/auth/ResetPasswordForm";

type Props = {
  searchParams: Promise<{ phone?: string }>;
};

export async function generateMetadata() {
  const t = await getTranslations("Auth");
  return { title: t("resetPasswordTitle") };
}

export default async function ResetPasswordPage({ searchParams }: Props) {
  const { phone } = await searchParams;

  return <ResetPasswordForm initialPhone={phone ?? ""} />;
}
