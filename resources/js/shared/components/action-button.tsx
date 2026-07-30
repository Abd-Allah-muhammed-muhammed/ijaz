import {ButtonHTMLAttributes, DetailedHTMLProps, ReactNode} from "react";
import {useTranslation} from "react-i18next";

type Props = DetailedHTMLProps<ButtonHTMLAttributes<HTMLButtonElement>, HTMLButtonElement> & {
  isProcessing: boolean;
  text?: string|ReactNode;
  loadingText?: string;

}

export default function ActionButton(props: Props) {
  const {isProcessing, text, loadingText, ...rest} = props;
  const {t} = useTranslation();
  return (
    <button
      type='submit'
      id='kt_sign_in_submit'
      className='btn btn-primary'
      disabled={isProcessing}
      {...rest}
    >
      {!isProcessing && <span className='indicator-label'>{text ? text : t('Continue')}</span>}
      {isProcessing && (
        <span className='indicator-progress' style={{display: 'block'}}>
              {loadingText ? loadingText : t('Please wait...')}
          <span className='spinner-border spinner-border-sm align-middle ms-2'></span>
            </span>
      )}
    </button>
  )
};
