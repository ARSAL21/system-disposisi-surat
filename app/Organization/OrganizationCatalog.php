<?php

namespace App\Organization;

final class OrganizationCatalog
{
    public const string GENERAL_AFFAIRS_LEVEL = 'GENERAL_AFFAIRS';

    public const string EXECUTIVE_ENTRY_LEVEL = 'EXECUTIVE_ENTRY';

    public const string ASSISTANT_LEVEL = 'ASSISTANT';

    public const string SECTION_HEAD_LEVEL = 'SECTION_HEAD';

    public const string GENERAL_AFFAIRS_UNIT = 'BAGIAN_UMUM';

    /**
     * @return list<array{code: string, name: string, hierarchy_order: int, is_active: bool}>
     */
    public static function positionLevelDefinitions(): array
    {
        return [
            [
                'code' => self::GENERAL_AFFAIRS_LEVEL,
                'name' => 'Bagian Umum / Tata Usaha',
                'hierarchy_order' => 10,
                'is_active' => true,
            ],
            [
                'code' => self::EXECUTIVE_ENTRY_LEVEL,
                'name' => 'Wali Kota / Sekretaris Daerah',
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
        ];
    }

    /** @return list<string> */
    public static function positionLevelCodes(): array
    {
        return array_column(self::positionLevelDefinitions(), 'code');
    }

    public static function isProtectedPositionLevel(string $code): bool
    {
        return in_array($code, self::positionLevelCodes(), true);
    }
}
