import {PaymentDriverEnum, PaymentMethodEnum} from "@/Enums/Payment";
import './style.css'

type Props = {
  onPaymentMethodChange: (method: string) => void;
  onPaymentDriverChange: (driver: string) => void;
  paymentMethod?: string;
  paymentDriver?: string;
}

const Portal = ({onPaymentMethodChange, onPaymentDriverChange, paymentDriver, paymentMethod}: Props) => {
  const handlePaymentMethodChange = (key: string) => {
    onPaymentMethodChange(key);
  };
  return (
    <div>
      <div className="d-flex gap-3 flex-wrap">
        {Object.entries(PaymentMethodEnum).map(([k, v]) => (
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
              checked={paymentDriver === v}
              onChange={() => handlePaymentMethodChange(v)}
              className="absolute opacity-0 w-0 h-0 pointer-events-none"
              hidden

            />
            <div className="flex flex-col items-center justify-center gap-2">
              <span
                className={`fs-2 fw-bolder transition-colors ${paymentMethod === v ? "text-primary" : "text-gray-800 group-hover:text-primary/80"}`}>{v}</span>
            </div>
          </label>
        ))}
      </div>
      {paymentMethod == PaymentMethodEnum.Online && (
        <div className="d-flex gap-3 flex-wrap mt-5">
          {Object.values(PaymentDriverEnum).map((driver) => (
            <div
              key={driver.value}
            >
              <input
                id={`payment-${driver.value}`}
                className="form-check-input payment-input" type="radio" name="payment" value={driver.value}
                hidden
                onChange={() => onPaymentDriverChange(driver.value)}
                checked={paymentDriver === driver.value}
              />
              <label
                htmlFor={`payment-${driver.value}`}
                className="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex align-items-center cursor-pointer payment-label"
              >
                <img
                  src={driver.logo()}
                  alt={driver.name}
                  className="object-contain  cursor-pointer"
                  style={{
                    width: '100px',
                    height: '50px',
                  }}
                />
              </label>
            </div>
          ))}

        </div>
      )}
    </div>
  );
};

export default Portal;
