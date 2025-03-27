<?php

namespace Ownego\Cashier\Enums;

enum PaypalSubscriptionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case FUTURE = 'FUTURE';
    case CANCELLED = 'CANCELLED';
    case SUSPENDED = 'SUSPENDED';
    case EXPIRED = 'EXPIRED';
    case PENDING = 'PENDING';
    case APPROVAL_PENDING = 'APPROVAL_PENDING';
    case APPROVAL_DENIED = 'APPROVAL_DENIED';
    case APPROVAL_REVOKED = 'APPROVAL_REVOKED';
    case DEACTIVATED = 'DEACTIVATED';
    case REACTIVATED = 'REACTIVATED';
}
