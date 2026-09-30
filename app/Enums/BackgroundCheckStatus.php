<?php

namespace App\Enums;

enum BackgroundCheckStatus: string
{
    case NotChecked = 'not_checked';
    case Clear = 'clear';
    case Flagged = 'flagged';
}
