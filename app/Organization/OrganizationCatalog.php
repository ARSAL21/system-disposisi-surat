<?php

namespace App\Organization;

final class OrganizationCatalog
{
    public const string MAYOR_LEVEL = 'MAYOR';

    public const string REGIONAL_SECRETARY_LEVEL = 'REGIONAL_SECRETARY';

    /**
     * Compatibility alias for isolated fixtures that model the direct Sekda
     * route. New production code must use REGIONAL_SECRETARY_LEVEL explicitly.
     */
    public const string EXECUTIVE_ENTRY_LEVEL = self::REGIONAL_SECRETARY_LEVEL;

    public const string GENERAL_AFFAIRS_LEVEL = 'GENERAL_AFFAIRS';

    public const string ASSISTANT_LEVEL = 'ASSISTANT';

    public const string SECTION_HEAD_LEVEL = 'SECTION_HEAD';

    public const string UNIT_STAFF_LEVEL = 'UNIT_STAFF';

    public const string GENERAL_AFFAIRS_UNIT = 'BAGIAN_UMUM';

    public const string MAYOR_POSITION = 'WALI_KOTA';

    public const string REGIONAL_SECRETARY_POSITION = 'SEKDA';

    /**
     * @return list<array{code: string, name: string, hierarchy_order: int, is_active: bool}>
     */
    public static function positionLevelDefinitions(): array
    {
        return [
            [
                'code' => self::MAYOR_LEVEL,
                'name' => 'Wali Kota',
                'hierarchy_order' => 10,
                'is_active' => true,
            ],
            [
                'code' => self::REGIONAL_SECRETARY_LEVEL,
                'name' => 'Sekretaris Daerah',
                'hierarchy_order' => 20,
                'is_active' => true,
            ],
            [
                'code' => self::ASSISTANT_LEVEL,
                'name' => 'Asisten',
                'hierarchy_order' => 30,
                'is_active' => true,
            ],
            [
                'code' => self::SECTION_HEAD_LEVEL,
                'name' => 'Kepala Bagian',
                'hierarchy_order' => 40,
                'is_active' => true,
            ],
            [
                'code' => self::GENERAL_AFFAIRS_LEVEL,
                'name' => 'Bagian Umum / Tata Usaha',
                'hierarchy_order' => 50,
                'is_active' => true,
            ],
            [
                'code' => self::UNIT_STAFF_LEVEL,
                'name' => 'Staf Unit',
                'hierarchy_order' => 60,
                'is_active' => true,
            ],
        ];
    }

    /** @return list<string> */
    public static function positionLevelCodes(): array
    {
        return array_column(self::positionLevelDefinitions(), 'code');
    }

    /** @return list<string> */
    public static function executiveLevelCodes(): array
    {
        return [self::MAYOR_LEVEL, self::REGIONAL_SECRETARY_LEVEL];
    }

    public static function isProtectedPositionLevel(string $code): bool
    {
        return in_array($code, self::positionLevelCodes(), true);
    }
}
