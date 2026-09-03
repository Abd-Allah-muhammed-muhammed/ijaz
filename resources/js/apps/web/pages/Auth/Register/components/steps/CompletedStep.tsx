import type { IconDefinition } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faBell,
  faCircleCheck,
  faCommentDots,
  faRocket,
} from '@fortawesome/free-solid-svg-icons';
import { useTranslation } from 'react-i18next';
import type { ProviderType } from '@/shared/types/models';
import type { RegistrationForm } from '../../hooks/use-registration-form';
import StepShell from '../StepShell';

export type CompletedStepProps = {
  isCurrent: boolean;
  form: RegistrationForm;
  providerType: ProviderType | null;
};

type NextStepCardProps = {
  icon: IconDefinition;
  iconClassName: string;
  circleClassName: string;
  caption: string;
};

function NextStepCard({ icon, iconClassName, circleClassName, caption }: NextStepCardProps) {
  return (
    <div className="col-12 col-md-4">
      <div className="text-center">
        <div
          className={`${circleClassName} rounded-circle d-inline-flex align-items-center justify-content-center mb-3`}
          style={{ width: '50px', height: '50px' }}
        >
          <FontAwesomeIcon icon={icon} size="2x" className={iconClassName} />
        </div>
        <div className="fs-7 fw-semibold text-gray-700">{caption}</div>
      </div>
    </div>
  );
}

function enrichEmojiHtml(html: string, replacements: Array<[RegExp, string]>): string {
  return replacements.reduce(
    (value, [pattern, replacement]) => value.replace(pattern, replacement),
    html.replace(new RegExp('\r?\n', 'g'), '<br/>'),
  );
}

export default function CompletedStep({
  isCurrent,
  form,
  providerType,
}: CompletedStepProps) {
  const { t } = useTranslation();

  const endRegisterHtml = enrichEmojiHtml(t('end_register'), [
    [/💚/g, '<span class="text-success fs-4">💚</span>'],
    [/🔔/g, '<span class="text-primary fs-4">🔔</span>'],
    [/🎯/g, '<span class="text-warning fs-4">🎯</span>'],
    [/🚀/g, '<span class="text-info fs-4">🚀</span>'],
    [/🎊/g, '<span class="text-success fs-4">🎊</span>'],
    [/💪/g, '<span class="text-primary fs-4">💪</span>'],
    [/🇸🇦/g, '<span class="text-success fs-4">🇸🇦</span>'],
  ]);

  const registrationSummaryHtml = enrichEmojiHtml(
    t('registration_summary', {
      created_at: new Date().toLocaleDateString(),
      phone: form.data.phone as string,
      account_type: providerType?.name || '',
      order_id: '',
    }),
    [
      [/🧾/g, '<span class="text-primary fs-4">🧾</span>'],
      [/✅/g, '<span class="text-success fs-4">✅</span>'],
      [/✨/g, '<span class="text-warning fs-4">✨</span>'],
    ],
  );

  return (
    <StepShell isCurrent={isCurrent}>
      <div className="mb-0">
        <div className="bg-white rounded-3 shadow-sm border border-light-subtle p-8 mb-6">
          <div className="text-center mb-8">
            <div className="bg-gradient-to-br from-emerald-100 to-teal-100 rounded-circle d-inline-flex align-items-center justify-content-center mb-4">
              <FontAwesomeIcon
                icon={faCircleCheck}
                size="2xl"
                className="text-success"
                style={{
                  fontSize: '10rem',
                }}
              />
            </div>
          </div>

          <div className="welcome-message mb-8">
            <div className="text-center mb-6">
              <h2 className="fs-2 fw-bold text-gray-900 mb-4">
                🎉 {t('completed')}
              </h2>
            </div>

            <div className="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2 p-6 mb-6 border border-emerald-200">
              <div className="registration-content text-center">
                <div
                  className="fs-5 text-gray-800 lh-lg fw-medium"
                  style={{
                    textAlign: 'center',
                  }}
                  dangerouslySetInnerHTML={{ __html: endRegisterHtml }}
                />
              </div>
            </div>
          </div>

          <div className="registration-summary">
            <div className="bg-light-primary rounded-2 p-6 border border-primary-subtle">
              <div className="summary-content">
                <div
                  className="fs-6 text-gray-800 lh-lg"
                  style={{
                    textAlign: 'center',
                  }}
                  dangerouslySetInnerHTML={{ __html: registrationSummaryHtml }}
                />
              </div>
            </div>
          </div>

          <div className="next-steps mt-8">
            <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2 p-6 border border-blue-200">
              <div className="row g-4">
                <NextStepCard
                  icon={faBell}
                  circleClassName="bg-primary bg-opacity-10"
                  iconClassName="text-primary"
                  caption="انتظار الإشعار"
                />
                <NextStepCard
                  icon={faCommentDots}
                  circleClassName="bg-success bg-opacity-10"
                  iconClassName="text-success"
                  caption="تابع الإشعارات"
                />
                <NextStepCard
                  icon={faRocket}
                  circleClassName="bg-warning bg-opacity-10"
                  iconClassName="text-warning"
                  caption="جهز خدماتك"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </StepShell>
  );
}
