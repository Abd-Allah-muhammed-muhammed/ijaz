import { Nav } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import { url } from '@/shared/helpers/general';
import { KTIcon } from '@/vendor/metronic/helpers';
import { availableSteps } from '../providerSchema';

export type RegistrationStepperProps = {
  currentStep: number;
  stepIs: (step: number) => boolean;
};

/**
 * Desktop + mobile registration stepper navigation (aside).
 */
export default function RegistrationStepper({
  currentStep,
  stepIs,
}: RegistrationStepperProps) {
  const { t } = useTranslation();
  const current = availableSteps[currentStep - 1];

  const formatStepTitle = (stepNumber: number, titleKey: string) =>
    t('registration_step_label', { number: stepNumber, title: t(titleKey) });

  return (
    <div className="d-flex flex-column flex-lg-row-auto w-lg-350px w-xl-500px">
      <div
        className="d-flex flex-column position-lg-fixed top-0 bottom-0 w-lg-350px w-xl-500px scroll-y bgi-size-cover bgi-position-center register-stepper-aside"
        style={{ backgroundImage: `url(${url('/media/auth/bg10-dark.jpeg')})` }}
      >
        <div className="d-flex flex-center py-10 py-lg-20 mt-lg-20 d-none d-lg-flex">
          <a href="/">
            <img alt="Logo" src={url('/media/logos/default.svg')} className="h-70px" />
          </a>
        </div>
        <div className="d-flex flex-row-fluid justify-content-center">
          <Nav className="stepper-nav justify-content-center flex-column m-5 d-none d-lg-flex">
            {availableSteps.map((step, index) => {
              const stepNumber = index + 1;

              return (
                <Nav.Item
                  key={`step-nav${index}`}
                  className={`stepper-item ${stepIs(stepNumber) ? 'current' : ''} ${currentStep > stepNumber ? 'completed' : ''}`}
                  data-kt-stepper-element="nav"
                >
                  <div className="stepper-wrapper">
                    <div className="stepper-icon rounded-3">
                      <KTIcon iconName="check" className="ki-duotone ki-check fs-2 stepper-check" />
                      <span className="stepper-number">{stepNumber}</span>
                    </div>
                    <div className="stepper-label d-flex flex-column">
                      <h3 className="stepper-title fs-2">
                        {formatStepTitle(stepNumber, step.titleKey)}
                      </h3>
                      <div className="stepper-desc fw-normal">{t(step.descriptionKey)}</div>
                    </div>
                  </div>
                  {stepNumber < availableSteps.length ? (
                    <div className="stepper-line h-40px" />
                  ) : null}
                </Nav.Item>
              );
            })}
          </Nav>

          <div className="d-flex d-lg-none flex-column w-100 px-2 py-3">
            <div className="mb-4">
              <style>{`
                .mobile-stepper-scroll::-webkit-scrollbar {
                  display: none;
                }
                .mobile-stepper-scroll {
                  -ms-overflow-style: none;
                  scrollbar-width: none;
                }
              `}</style>
              <div className="d-flex align-items-center overflow-auto pb-2 mobile-stepper-scroll">
                <div className="d-flex align-items-center" style={{ minWidth: 'max-content' }}>
                  {availableSteps.map((step, index) => {
                    const stepNumber = index + 1;
                    const isActive = stepIs(stepNumber);
                    const isCompleted = currentStep > stepNumber;

                    return (
                      <div key={`mobile-step-${index}`} className="d-flex align-items-center flex-shrink-0">
                        <div className="d-flex flex-column align-items-center">
                          <div
                            className={`d-flex align-items-center justify-content-center rounded-circle position-relative ${
                              isActive
                                ? 'bg-primary text-white shadow-sm'
                                : isCompleted
                                  ? 'bg-success text-white shadow-sm'
                                  : 'bg-white bg-opacity-20 text-white border border-white border-opacity-50'
                            }`}
                            style={{
                              width: '30px',
                              height: '30px',
                              fontSize: '12px',
                              fontWeight: 'bold',
                              minWidth: '30px',
                              transition: 'all 0.3s ease',
                              transform: isActive ? 'scale(1.15)' : 'scale(1)',
                            }}
                          >
                            {isCompleted ? (
                              <KTIcon iconName="check" className="ki-duotone ki-check fs-6 text-white" />
                            ) : (
                              stepNumber
                            )}
                          </div>
                          <div
                            className={`mt-2 px-2 text-center ${isActive ? 'text-white' : 'text-white'}`}
                            style={{
                              fontSize: '9px',
                              lineHeight: '1.2',
                              maxWidth: '70px',
                              opacity: isActive ? 1 : 0.9,
                            }}
                          >
                            <div className="fw-bold">
                              {formatStepTitle(stepNumber, step.titleKey)}
                            </div>
                          </div>
                        </div>
                        {stepNumber < availableSteps.length ? (
                          <div
                            className={`mx-3 rounded flex-shrink-0 ${isCompleted ? 'bg-success' : 'bg-white bg-opacity-20'}`}
                            style={{
                              height: '2px',
                              width: '20px',
                              minWidth: '20px',
                              transition: 'all 0.5s ease',
                              marginTop: '-20px',
                            }}
                          />
                        ) : null}
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>

            <div className="text-center mb-3">
              <div
                className="bg-white bg-opacity-10 rounded-3 px-3 py-2"
                style={{ backdropFilter: 'blur(10px)' }}
              >
                <div className="text-white fw-semibold" style={{ fontSize: '14px' }}>
                  <span className="opacity-75">
                    {t('registration_step_of', {
                      current: currentStep,
                      total: availableSteps.length,
                    })}
                  </span>{' '}
                  {current ? t(current.titleKey) : ''}
                </div>
                <div className="text-white opacity-90 mt-1" style={{ fontSize: '12px' }}>
                  {current ? t(current.descriptionKey) : ''}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
