<?php

namespace App\Enums;

enum AlertType: string
{
    case LowRating = 'low_rating';
    case Report = 'report';
}
