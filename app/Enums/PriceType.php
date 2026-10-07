<?php

namespace App\Enums;

enum PriceType: string
{
    case Hourly = 'hourly';
    case Fixed = 'fixed';
}
