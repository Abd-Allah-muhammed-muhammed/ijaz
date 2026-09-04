import { useEffect } from 'react';
import type { IconDefinition } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faBell,
  faCircleCheck,
  faCommentDots,
  faRocket,
} from '@fortawesome/free-solid-svg-icons';
import confetti from 'canvas-confetti';
import { useTranslation } from 'react-i18next';
import GeneralController from '@/actions/App/Http/Controllers/Frontend/GeneralController';
import type { ProviderType } from '@/shared/types/models';
import type { RegistrationForm } from '../../hooks/use-registration-form';
import StepShell from '../StepShell';

export type CompletedStepProps = {
  isCurrent: boolean;
  form: RegistrationForm;
  providerType: ProviderType | null;
};

const SUCCESS_ICON_SIZE_REM = 5;
const NEXT_STEP_ICON_CIRCLE_PX = 48;
const CONFETTI_PARTICLE_COUNT = 120;
const CONFETTI_SPREAD_DEGREES = 70;
const CONFETTI_START_VELOCITY = 35;
const CONFETTI_ORIGIN_Y = 0.65;
const REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)';

type NextStepItemProps = {
  icon: IconDefinition;
  iconClassName: string;
  circleClassName: string;
  label: string;
};

function NextStepItem({ icon, iconClassName, circleClassName, label }: NextStepItemProps) {
  return (
    <div className="col-12 col-md-4">
      <div className="text-center px-2">
        <div
          className={`${circleClassName} rounded-circle d-inline-flex align-items-center justify-content-center mb-3`}
          style={{ width: NEXT_STEP_ICON_CIRCLE_PX, height: NEXT_STEP_ICON_CIRCLE_PX }}
        >
          <FontAwesomeIcon icon={icon} className={iconClassName} />
        </div>
        <div className="fs-7 fw-semibold text-gray-700">{label}</div>
      </div>
    </div>
  );
}

type SummaryRowProps = {
  label: string;
  value: string;
};

function SummaryRow({ label, value }: SummaryRowProps) {
  return (
    <div className="d-flex flex-column flex-sm-row justify-content-between gap-1 py-3 border-bottom border-gray-200">
      <span className="fw-semibold text-gray-600">{label}</span>
      <span className="fw-bold text-gray-900 text-sm-end">{value}</span>
    </div>
  );
}

function prefersReducedMotion(): boolean {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
    return false;
  }

  return window.matchMedia(REDUCED_MOTION_QUERY).matches;
}

function fireRegistrationConfetti(): void {
  if (prefersReducedMotion()) {
    return;
  }

  void confetti({
    particleCount: CONFETTI_PARTICLE_COUNT,
    spread: CONFETTI_SPREAD_DEGREES,
    startVelocity: CONFETTI_START_VELOCITY,
    origin: { y: CONFETTI_ORIGIN_Y },
    disableForReducedMotion: true,
  });
}

export default function CompletedStep({
  isCurrent,
  form,
  providerType,
}: CompletedStepProps) {
  const { t } = useTranslation();
  const registrationDate = new Date().toLocaleDateString();
  const phone = form.data.phone ?? '';
  const accountType = providerType?.name ?? '';

  useEffect(() => {
    if (! isCurrent) {
      return;
    }

    fireRegistrationConfetti();
  }, [isCurrent]);

  return (
    <StepShell isCurrent={isCurrent}>
      <div className="mb-0 text-center">
        <div className="mb-6">
          <FontAwesomeIcon
            icon={faCircleCheck}
            className="text-success"
            style={{ fontSize: `${SUCCESS_ICON_SIZE_REM}rem` }}
            aria-hidden
          />
        </div>

        <h2 className="fs-2 fw-bold text-gray-900 mb-3">
          {t('registration_complete_title')}
        </h2>
        <p className="fs-5 text-gray-600 mb-8 mx-auto" style={{ maxWidth: '36rem' }}>
          {t('registration_complete_body')}
        </p>

        <div className="row g-4 mb-8">
          <NextStepItem
            icon={faBell}
            circleClassName="bg-light-primary"
            iconClassName="text-primary"
            label={t('registration_next_wait_notification')}
          />
          <NextStepItem
            icon={faCommentDots}
            circleClassName="bg-light-success"
            iconClassName="text-success"
            label={t('registration_next_watch_updates')}
          />
          <NextStepItem
            icon={faRocket}
            circleClassName="bg-light-warning"
            iconClassName="text-warning"
            label={t('registration_next_prepare_services')}
          />
        </div>

        <div className="bg-light rounded-3 p-5 p-md-6 text-start mb-8 mx-auto" style={{ maxWidth: '28rem' }}>
          <h3 className="fs-6 fw-bold text-gray-800 mb-2">
            {t('registration_summary_heading')}
          </h3>
          <SummaryRow label={t('account_type')} value={accountType} />
          <SummaryRow label={t('phone')} value={phone} />
          <div className="d-flex flex-column flex-sm-row justify-content-between gap-1 py-3">
            <span className="fw-semibold text-gray-600">{t('registration_date')}</span>
            <span className="fw-bold text-gray-900 text-sm-end">{registrationDate}</span>
          </div>
        </div>

        <a
          href={GeneralController.index().url}
          className="btn btn-primary btn-lg"
          data-pan="register-completed-home-cta"
        >
          {t('return_to_home_page')}
        </a>
      </div>
    </StepShell>
  );
}
