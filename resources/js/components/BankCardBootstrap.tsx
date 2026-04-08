import React from "react";
import {url} from "@/helpers/general";
import {useTranslation} from "react-i18next";

interface BankCardBootstrapProps {
  cardHolder?: string;
  cardNumber?: string;
  expiryMonth?: number | string;
  expiryYear?: number | string;
  bankName?: string;
  cardType?: string;
  cardScheme?: string;
  className?: string;
}

const mainTeal = "#0b6c6f"; // قريب من لون الخلفية في الصورة
const gold = "#c89c3c"; // قريب من لون علامة الصح

const BankCardBootstrap: React.FC<BankCardBootstrapProps> = ({
  cardHolder,
  cardNumber,
  expiryMonth,
  expiryYear,
  bankName,
  cardType,
  cardScheme,
  className = "",
}) => {
  const {t} = useTranslation();
  return (
    <div
      className={`card text-white mb-3 shadow-lg border-0 rounded-4 position-relative ${className}`}
      style={{
        maxWidth: 340,
        minHeight: 200,
        background: mainTeal,
        boxShadow: "0 4px 24px 0 rgba(11,108,111,0.15)",
      }}
    >
      <div className="card-body d-flex flex-column justify-content-between h-100">
        <div className="d-flex justify-content-between align-items-center mb-3">
          <span className="fw-bold fs-5 text-truncate" style={{letterSpacing: 1}}>{bankName || cardScheme || "نجاز"}</span>
          <span className="badge text-uppercase px-2 py-1" style={{background: gold, color: mainTeal, fontWeight: 700}}>{cardType || t('card')}</span>
        </div>
        <div className="fs-4 fw-monospace mb-4" style={{letterSpacing: 2}}>
          {cardNumber || "•••• •••• •••• ••••"}
        </div>
        <div className="d-flex justify-content-between align-items-end">
          <div>
            <div className="small opacity-75">Card Holder</div>
            <div className="fw-semibold">{cardHolder || t('N/A')}</div>
          </div>
          <div>
            <div className="small opacity-75">Expires</div>
            <div className="fw-semibold">
              {expiryMonth && expiryYear
                ? `${String(expiryMonth).padStart(2, "0")}/${String(expiryYear).slice(-2)}`
                : "--/--"}
            </div>
          </div>
        </div>
      </div>
       <img src={url("/logo-no-bg.svg")} alt="نجاز" style={{position:'absolute',top:16,right:'50%',width:36}} />
    </div>
  );
};

export default BankCardBootstrap;
