<?php

namespace Modules\Chat\Enums;

enum ChatTypeEnum: string
{
    case Member = 'member';
    case Order = 'order';
    case TicketSupport = 'ticket_support';
    case Opportunity = 'opportunity';
    case Guarantor = 'guarantor';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Direct Chat',
            self::Order => 'Order Chat',
            self::TicketSupport => 'Support Chat',
            self::Opportunity => 'Opportunity Chat',
            self::Guarantor => 'Guarantor Chat',
        };
    }
}
