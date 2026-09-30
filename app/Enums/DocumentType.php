<?php

namespace App\Enums;

enum DocumentType: string
{
    case IdDocument = 'id_document';
    case Selfie = 'selfie';
    case Certificate = 'certificate';
    case WorkEvidence = 'work_evidence';
}
