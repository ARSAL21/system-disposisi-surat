<?php

namespace App\Enums;

use App\Organization\OrganizationCatalog;

enum InitialLetterRoutePath: string
{
    case DirectToSekda = 'DIRECT_TO_SEKDA';
    case ViaMayor = 'VIA_MAYOR';

    public function targetPositionCode(): string
    {
        return match ($this) {
            self::DirectToSekda => OrganizationCatalog::REGIONAL_SECRETARY_POSITION,
            self::ViaMayor => OrganizationCatalog::MAYOR_POSITION,
        };
    }

    public function targetLevelCode(): string
    {
        return match ($this) {
            self::DirectToSekda => OrganizationCatalog::REGIONAL_SECRETARY_LEVEL,
            self::ViaMayor => OrganizationCatalog::MAYOR_LEVEL,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DirectToSekda => 'Langsung ke Sekda',
            self::ViaMayor => 'Melalui Wali Kota',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DirectToSekda => 'Sekda menerima surat dan membuat disposisi kepada Asisten.',
            self::ViaMayor => 'Wali Kota memberi arahan formal kepada Sekda sebelum disposisi diteruskan kepada Asisten.',
        };
    }
}
