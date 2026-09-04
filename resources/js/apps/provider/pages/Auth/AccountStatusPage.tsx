import {AuthLayout} from "@/apps/provider/layouts/AuthLayout";
import {Head, Link} from "@inertiajs/react";
import React, {type ReactElement} from "react";
import {useTranslation} from "react-i18next";
import {ProviderStatusEnum} from "@/Enums/Providers";
import type {ProviderStatus} from "@/shared/types/models";
import {KTIcon} from "@/vendor/metronic/helpers";
import StatusBadge from "@/shared/components/ui/status-badge";
import AuthController from "@/actions/App/Http/Controllers/Provider/AuthController";

type AccountStatusGateStatus = {
  value: ProviderStatus;
  label: string;
  color: string;
};

export type AccountStatusPageProps = {
  status: AccountStatusGateStatus;
  reason: string | null;
  blocked_until: string | null;
  is_temporary_block: boolean;
  block_reason: string | null;
};

type StatusPresentation = {
  iconName: string;
  iconColorClass: string;
  circleClassName: string;
  titleKey: string;
  bodyKey: string;
};

const STATUS_PRESENTATION: Record<
  Exclude<ProviderStatus, typeof ProviderStatusEnum.Approved>,
  StatusPresentation
> = {
  [ProviderStatusEnum.Pending]: {
    iconName: "time",
    iconColorClass: "text-primary",
    circleClassName: "bg-light-primary",
    titleKey: "provider_status_gate_pending_title",
    bodyKey: "provider_status_gate_pending_body",
  },
  [ProviderStatusEnum.Suspended]: {
    iconName: "information-5",
    iconColorClass: "text-warning",
    circleClassName: "bg-light-warning",
    titleKey: "provider_status_gate_suspended_title",
    bodyKey: "provider_status_gate_suspended_body",
  },
  [ProviderStatusEnum.Rejected]: {
    iconName: "cross-circle",
    iconColorClass: "text-danger",
    circleClassName: "bg-light-danger",
    titleKey: "provider_status_gate_rejected_title",
    bodyKey: "provider_status_gate_rejected_body",
  },
  [ProviderStatusEnum.Blocked]: {
    iconName: "lock-2",
    iconColorClass: "text-danger",
    circleClassName: "bg-light-danger",
    titleKey: "provider_status_gate_blocked_title",
    bodyKey: "provider_status_gate_blocked_body",
  },
  [ProviderStatusEnum.SelfDeactivated]: {
    iconName: "user",
    iconColorClass: "text-gray-600",
    circleClassName: "bg-light-secondary",
    titleKey: "provider_status_gate_self_deactivated_title",
    bodyKey: "provider_status_gate_self_deactivated_body",
  },
};

const ICON_CIRCLE_SIZE_PX = 88;

/** Flip to `true` once a real support channel exists (phone / WhatsApp / email). */
const SHOW_CONTACT_SUPPORT = false;

function resolvePresentation(
  status: AccountStatusGateStatus,
  isTemporaryBlock: boolean,
): StatusPresentation {
  if (status.value === ProviderStatusEnum.Approved) {
    return STATUS_PRESENTATION[ProviderStatusEnum.Pending];
  }

  if (status.value === ProviderStatusEnum.Blocked && !isTemporaryBlock) {
    return {
      ...STATUS_PRESENTATION[ProviderStatusEnum.Blocked],
      titleKey: "provider_status_gate_banned_title",
      bodyKey: "provider_status_gate_banned_body",
    };
  }

  return STATUS_PRESENTATION[status.value];
}

function displayedReason(
  status: AccountStatusGateStatus,
  reason: string | null,
  blockReason: string | null,
): string | null {
  if (
    status.value === ProviderStatusEnum.Suspended
    || status.value === ProviderStatusEnum.Rejected
  ) {
    return reason && reason.trim() !== "" ? reason : null;
  }

  if (status.value === ProviderStatusEnum.Blocked) {
    return blockReason && blockReason.trim() !== "" ? blockReason : null;
  }

  return null;
}

const AccountStatusPage = ({
  status,
  reason,
  blocked_until: blockedUntil,
  is_temporary_block: isTemporaryBlock,
  block_reason: blockReason,
}: AccountStatusPageProps) => {
  const {t, i18n} = useTranslation();
  const presentation = resolvePresentation(status, isTemporaryBlock);
  const reasonText = displayedReason(status, reason, blockReason);
  const formattedBlockedUntil =
    blockedUntil != null
      ? new Date(blockedUntil).toLocaleString(i18n.language)
      : null;

  return (
    <>
      <Head title={t(presentation.titleKey)} />
      <div className="d-flex flex-stack py-2">
        <div className="me-2" />
        <div className="m-0">
          <Link
            href={AuthController.loginForm().url}
            className="link-primary fw-bold fs-5"
          >
            {t("provider_status_gate_back_to_login")}
          </Link>
        </div>
      </div>

      <div className="py-10">
        <article className="text-center" aria-labelledby="account-status-title">
          <div
            className={`${presentation.circleClassName} rounded-circle d-inline-flex align-items-center justify-content-center mb-8`}
            style={{width: ICON_CIRCLE_SIZE_PX, height: ICON_CIRCLE_SIZE_PX}}
            aria-hidden="true"
          >
            <KTIcon
              iconName={presentation.iconName}
              className={`fs-2qx ${presentation.iconColorClass}`}
            />
          </div>

          <div className="mb-4 d-flex justify-content-center">
            <StatusBadge status={status} />
          </div>

          <h1
            id="account-status-title"
            className="text-gray-900 mb-4 fs-2x fw-bolder"
          >
            {t(presentation.titleKey)}
          </h1>

          <p className="text-gray-600 fw-semibold fs-5 mb-8 mw-400px mx-auto">
            {t(presentation.bodyKey)}
          </p>

          {status.value === ProviderStatusEnum.Blocked
            && isTemporaryBlock
            && formattedBlockedUntil != null && (
            <p className="text-gray-700 fw-bold fs-6 mb-8">
              {t("provider_status_gate_blocked_until", {
                date: formattedBlockedUntil,
              })}
            </p>
          )}

          {reasonText != null && (
            <section
              className="text-start bg-light rounded-3 p-5 mb-8"
              aria-label={t("provider_status_gate_reason_label")}
            >
              <h2 className="fs-7 text-muted text-uppercase fw-bolder mb-2">
                {t("provider_status_gate_reason_label")}
              </h2>
              <p className="text-gray-800 fw-semibold fs-6 mb-0">{reasonText}</p>
            </section>
          )}

          {/*
            Contact support: no support phone / WhatsApp / email is configured in Settings yet.
            Hidden via SHOW_CONTACT_SUPPORT until a business decision picks the channel —
            do not invent a placeholder destination.
          */}
          {SHOW_CONTACT_SUPPORT && (
            <button
              type="button"
              className="btn btn-light btn-lg w-100 mb-4 opacity-50"
              disabled
              aria-disabled="true"
            >
              {t("provider_status_gate_contact_support")}
            </button>
          )}

          <Link
            href={AuthController.loginForm().url}
            className="btn btn-primary btn-lg w-100"
          >
            {t("provider_status_gate_back_to_login")}
          </Link>
        </article>
      </div>
    </>
  );
};

AccountStatusPage.layout = (page: ReactElement) => {
  return <AuthLayout>{page}</AuthLayout>;
};

export default AccountStatusPage;
