<?php

namespace Modules\Sms\Enums;

enum SmsMessageType: string
{
    case Otp = 'otp';
    case Custom = 'custom';
}
