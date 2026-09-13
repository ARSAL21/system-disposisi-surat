<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case LetterOfficer = 'petugas-surat';
    case GeneralAffairsHead = 'kabag-umum';
    case Mayor = 'wali-kota';
    case RegionalSecretary = 'sekda';
    case Assistant = 'asisten';
    case SectionHead = 'kepala-bagian';
    case UnitStaff = 'staf-bagian';
    case ExpertAdvisor = 'staf-ahli';
}
