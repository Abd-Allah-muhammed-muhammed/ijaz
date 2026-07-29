import {useEffect, useState} from "react";

type Options = {
  totalSteps: number;
  initialStep?: number;
  onStepChange?: (step: number, direction?: 'next' | 'prev' | 'current') => void;
  onComplete?: (step: number) => void;
};

const useSteps = (options: Options) => {
  const [currentStep, setCurrentStep] = useState(options.initialStep || 1);
  const [prevStepNumber, setPrevStepNumber] = useState<null | number>(null);
  const nextStep = () => {
    if (currentStep < options.totalSteps) {
      setPrevStepNumber(currentStep);
      setCurrentStep(currentStep + 1);
    }
  };

  const prevStep = () => {
    if (currentStep > 1) {
      setPrevStepNumber(currentStep);
      setCurrentStep(currentStep - 1);
    }
  };

  const goToStep = (step: number) => {
    if (step >= 1 && step <= options.totalSteps) {
      setPrevStepNumber(currentStep);
      setCurrentStep(step);
    }
  };
  const isLastStep = () => {
    return currentStep === options.totalSteps;
  }
  const isFirstStep = () => {
    return currentStep === 1;
  };
  const isBetweenStep = () => {
    return currentStep > 1 && currentStep < options.totalSteps;
  };
  const stepIs = (step: number) => {
    return currentStep === step;
  }
  const stepBetween = (first:number, second :number) => {
    return currentStep >= first && currentStep <= second;
  }
  const directionTo = (step: number) => {
    if (step < currentStep) {
      return 'prev';
    } else if (step > currentStep) {
      return 'next';
    }
    return 'current';
  }
  const direction = () => {
    if (prevStepNumber === null || prevStepNumber === currentStep) {
      return 'current';
    }
    if (prevStepNumber < currentStep) {
      return 'next';
    } else if (prevStepNumber > currentStep) {
      return 'prev';
    }

  }

  useEffect(() => {
    if (options.onStepChange) {
      options.onStepChange(currentStep, direction())
    }
    if (options.onComplete && isLastStep()) {
      options.onComplete(currentStep);
    }
  }, [currentStep]);

  return {
    totalSteps: options.totalSteps,
    currentStep,
    nextStep,
    prevStep,
    goToStep,
    isLastStep,
    isFirstStep,
    isBetweenStep,
    stepIs,
    stepBetween
  };

};
export default useSteps;
