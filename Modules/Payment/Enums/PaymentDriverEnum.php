<?php

namespace Modules\Payment\Enums;

use App\Console\Commands\JsEnums\Attributes\JsClass;
use App\Console\Commands\JsEnums\Attributes\JsFunction;
use App\Console\Commands\JsEnums\Attributes\JsIgnore;

#[JsClass(
    name: 'PaymentDriver',
    ts: true
)]
enum PaymentDriverEnum: string
{
    case PayTabs = 'paytabs';
    #[JsIgnore(['production'])]
    case Testing = 'testing';

    public function toArray(): array
    {
        return [
            'label' => $this->toString(),
            'value' => $this->value,
        ];
    }

    public function toString(): string
    {
        return trans(strtolower($this->value));
    }

    #[JsFunction(
        name: 'logo<ThisType extends this>',
        arguments: ['this: ThisType'],
        body: 'switch(this.value) {
      case "paytabs":
        return "ASSET_URL/media/svg/brand-logos/visa.svg";
      case "testing":
        return "ASSET_URL/logo.png";
      case "paytabs-apple":
        return "ASSET_URL/apple.jpeg";
      default:
        return "";
    }
  ',
        ts: true
    )]
    public function logo(): string
    {
        return match ($this) {
            self::PayTabs => 'https://paytabs.com/wp-content/uploads/2017/05/paytabs-logo-colored.svg',
            self::Testing => asset('logo.png'),
        };
    }

    public function fees(): float
    {
        return match ($this) {
            self::PayTabs => 2.9,
            self::Testing => 1.0,
        };
    }
}
