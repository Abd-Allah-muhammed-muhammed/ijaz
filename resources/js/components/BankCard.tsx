import React from "react";
import { twMerge } from "tailwind-merge";

interface BankCardProps {
  cardHolder?: string;
  cardNumber?: string;
  expiryMonth?: number | string;
  expiryYear?: number | string;
  bankName?: string;
  cardType?: string;
  cardScheme?: string;
  className?: string;
}

const BankCard: React.FC<BankCardProps> = ({
  cardHolder,
  cardNumber,
  expiryMonth,
  expiryYear,
  bankName,
  cardType,
  cardScheme,
  className = "",
}) => {
  return (
    <div
      className={twMerge(
        "relative w-full max-w-sm rounded-2xl shadow-lg p-6 bg-gradient-to-tr from-blue-600 to-indigo-500 text-white overflow-hidden dark:from-gray-800 dark:to-gray-900",
        className
      )}
    >
      {/* شعار البنك أو نوع البطاقة */}
      <div className="flex items-center justify-between mb-4">
        <span className="font-bold text-lg truncate">
          {bankName || cardScheme || "Bank"}
        </span>
        <span className="uppercase text-xs bg-white/20 px-2 py-1 rounded">
          {cardType || "CARD"}
        </span>
      </div>
      {/* رقم البطاقة */}
      <div className="text-2xl font-mono tracking-widest mb-6">
        {cardNumber || "•••• •••• •••• ••••"}
      </div>
      <div className="flex items-center justify-between text-xs">
        <div>
          <div className="opacity-70">Card Holder</div>
          <div className="font-semibold text-base">
            {cardHolder || "Not Available"}
          </div>
        </div>
        <div>
          <div className="opacity-70">Expires</div>
          <div className="font-semibold text-base">
            {expiryMonth && expiryYear
              ? `${expiryMonth}`.padStart(2, "0") + "/" + `${expiryYear}`.slice(-2)
              : "--/--"}
          </div>
        </div>
      </div>
    </div>
  );
};

export default BankCard;

