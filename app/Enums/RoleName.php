<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case LetterOfficer = 'petugas-surat';
    case GeneralAffairsHead = 'kabag-umum';
    case ExecutiveLeader = 'pimpinan-eksekutif';
    case Assistant = 'asisten';
    case SectionHead = 'kepala-bagian';
}
