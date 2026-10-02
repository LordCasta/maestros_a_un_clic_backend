<?php

namespace App\Enums;

enum Role: string
{
    case Client = 'client';
    case Professional = 'professional';
    case Admin = 'admin';
}
