<?php

namespace Modules\Guarantor\Enums;

enum GuarantorWithdrawnNotificationAudience: string
{
    case Withdrawer = 'withdrawer';
    case OtherParty = 'other_party';
    case Admin = 'admin';
}
