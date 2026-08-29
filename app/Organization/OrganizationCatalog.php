<?php

namespace App\Organization;

final class OrganizationCatalog
{
    /**
     * @return list<array{code: string, name: string, hierarchy_order: int, is_active: bool}>
     */
    public static function positionLevelDefinitions(): array
    {
        return [
            [
                'code' => 'GENERAL_AFFAIRS',
                'name' => 'Bagian Umum / Tata Usaha',
                'hierarchy_order' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'EXECUTIVE_ENTRY',
                'name' => 'Wali Kota / Sekretaris Daerah',
                'hierarchy_order' => 20,
                'is_active' => true,
            ],
            [
                'code' => 'ASSISTANT',
                'name' => 'Asisten',
                'hierarchy_order' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'SECTION_HEAD',
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
