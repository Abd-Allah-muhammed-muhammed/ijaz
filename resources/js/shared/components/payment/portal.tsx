import {usePage} from "@inertiajs/react";
import {PaymentDriverEnum, PaymentMethodEnum} from "@/Enums/Payment";
import {useTranslation} from "react-i18next";
import './style.css'

type Props = {
  onPaymentMethodChange: (method: string) => void;
  onPaymentDriverChange: (driver: string) => void;
  paymentMethod?: string;
  paymentDriver?: string;
}

const Portal = ({onPaymentMethodChange, onPaymentDriverChange, paymentDriver, paymentMethod}: Props) => {
  const {t} = useTranslation();
  const {payment} = usePage().props;
  const onlineEnabled = payment?.online_enabled === true;
  const activeDriverValue = payment?.driver;
  const activeDriver = onlineEnabled
    ? Object.values(PaymentDriverEnum).find((driver) => driver.value === activeDriverValue)
    : undefined;

  const methods = Object.entries(PaymentMethodEnum).filter(([, value]) => {
    if (value === PaymentMethodEnum.Online) {
      return onlineEnabled && Boolean(activeDriver);
    }

    return true;
  });

  const handlePaymentMethodChange = (value: string) => {
    onPaymentMethodChange(value);
    if (value === PaymentMethodEnum.Online && activeDriver) {
      onPaymentDriverChange(activeDriver.value);
    }
  };

  return (
    <div>
      <div className="d-flex gap-3 flex-wrap">
        {methods.map(([k, v]) => (
          <label
            key={k}
            className={`relative border border-gray-300 border-dashed rounded min-w-32 py-3 px-4 cursor-pointer transition-colors flex-1 text-center select-none overflow-hidden group
            ${paymentMethod === v
              ? "border-primary bg-primary/10 ring-2 ring-primary shadow-lg scale-105 z-10"
              : "hover:border-primary/60 hover:bg-primary/5"}
          ` + ""}
          >
            <input
              type="radio"
              name="payment_method"
              value={k}
              checked={paymentMethod === v}
              onChange={() => handlePaymentMethodChange(v)}
              className="absolute opacity-0 w-0 h-0 pointer-events-none"
              hidden
            />
            <div className="flex flex-col items-center justify-center gap-2">
              <span
                className={`fs-2 fw-bolder transition-colors ${paymentMethod === v ? "text-primary" : "text-gray-800 group-hover:text-primary/80"}`}>{t(v)}</span>
            </div>
          </label>
        ))}
      </div>
      {paymentMethod == PaymentMethodEnum.Online && activeDriver && (
        <div className="d-flex gap-3 flex-wrap mt-5">
          <div key={activeDriver.value}>
            <input
              id={`payment-${activeDriver.value}`}
              className="form-check-input payment-input"
              type="radio"
              name="payment"
              value={activeDriver.value}
              hidden
              readOnly
              checked={paymentDriver === activeDriver.value || paymentMethod === PaymentMethodEnum.Online}
            />
            <label
              htmlFor={`payment-${activeDriver.value}`}
              className="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex align-items-center cursor-pointer payment-label"
            >
              <img
                src={activeDriver.logo()}
                alt={activeDriver.name}
                className="object-contain cursor-pointer max-h-20"
                style={{
                  width: '100px',
                  objectFit: 'contain',
                }}
              />
            </label>
          </div>
        </div>
      )}
    </div>
  );
};

export default Portal;
