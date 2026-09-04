import { Button } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import ActionButton from '@/shared/components/action-button';
import { availableSteps } from '../providerSchema';
import { LocaleNavArrow } from './LocaleNavArrow';

export type RegistrationFooterControlsProps = {
  currentStep: number;
  processing: boolean;
  stepIs: (step: number) => boolean;
  stepBetween: (first: number, second: number) => boolean;
  onPrevious: () => void;
  onNext: () => void | Promise<void>;
};

/**
 * Previous / Next / Submit controls for the registration wizard.
 * The completed step owns its own homepage CTA.
 */
export default function RegistrationFooterControls({
  currentStep,
  processing,
  stepIs,
  stepBetween,
  onPrevious,
  onNext,
}: RegistrationFooterControlsProps) {
  const { t } = useTranslation();
  const lastInteractiveStep = availableSteps.length - 1;
  const nextRangeEnd = availableSteps.length - 2;

  return (
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
  );
}
