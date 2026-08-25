<?php

namespace App\Enums;

enum SubOrderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
}
