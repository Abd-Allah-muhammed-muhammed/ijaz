import { Button } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import ActionButton from '@/shared/components/action-button';
import GeneralController from '@/actions/App/Http/Controllers/Frontend/GeneralController';
import { availableSteps } from '../providerSchema';
import { LocaleNavArrow } from './LocaleNavArrow';

export type RegistrationFooterControlsProps = {
  currentStep: number;
  processing: boolean;
  isLastStep: boolean;
  stepIs: (step: number) => boolean;
  stepBetween: (first: number, second: number) => boolean;
  onPrevious: () => void;
  onNext: () => void | Promise<void>;
};

/**
 * Previous / Next / Submit / return-home controls for the registration wizard.
 */
export default function RegistrationFooterControls({
  currentStep,
  processing,
  isLastStep,
  stepIs,
  stepBetween,
  onPrevious,
  onNext,
}: RegistrationFooterControlsProps) {
  const { t } = useTranslation();
  const lastInteractiveStep = availableSteps.length - 1;
  const nextRangeEnd = availableSteps.length - 2;

  return (
    <>
      <div className="d-flex flex-column flex-md-row justify-content-between pt-8 pt-md-15 gap-3">
        <div className="order-2 order-md-1">
          {stepBetween(2, lastInteractiveStep) ? (
            <Button
              data-pan={`register-step-${currentStep}-previous-button`}
              onClick={onPrevious}
              type="button"
              variant="light-primary"
              className="w-100 w-md-auto"
            >
              <LocaleNavArrow position="start" />
              {t('previous')}
            </Button>
          ) : null}
        </div>
        <div className="order-1 order-md-2">
          {stepIs(lastInteractiveStep) ? (
            <ActionButton
              isProcessing={processing}
              type="submit"
              data-pan={`register-step-${currentStep}-submit-button`}
              text={(
                <>
                  {t('submit')}
                  <LocaleNavArrow position="end" />
                </>
              )}
            />
          ) : null}
          {stepBetween(1, nextRangeEnd) ? (
            <Button
              type="button"
              variant="primary"
              className="w-100 w-md-auto"
              data-pan={`register-step-${currentStep}-next-button`}
              onClick={() => {
                void onNext();
              }}
            >
              {t('next')}
              <LocaleNavArrow position="end" />
            </Button>
          ) : null}
        </div>
      </div>
      <div className={`d-flex justify-content-center mt-15 ${isLastStep ? '' : 'd-none'}`}>
        <a href={GeneralController.index().url} className="btn btn-primary btn-lg">
          {t('return_to_home_page')}
        </a>
      </div>
    </>
  );
}
