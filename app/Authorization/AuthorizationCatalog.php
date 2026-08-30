<?php

namespace App\Authorization;

use App\Enums\PermissionName;
use App\Enums\RoleName;

final class AuthorizationCatalog
{
    public const string GUARD_NAME = 'web';

    /**
     * @return list<array{name: string, label: string, description: string, group: string}>
     */
    public static function permissionDefinitions(): array
    {
        return array_values(array_map(
            static fn (PermissionName $permission): array => match ($permission) {
                PermissionName::ViewAuthorization => [
                    'name' => $permission->value,
                    'label' => 'Lihat pengaturan peran dan hak akses',
                    'description' => 'Melihat peran, daftar hak akses resmi, dan penetapan peran akun pegawai.',
                    'group' => 'Peran dan Hak Akses',
                ],
                PermissionName::ManageAuthorization => [
                    'name' => $permission->value,
                    'label' => 'Kelola peran dan hak akses',
                    'description' => 'Membuat peran khusus, mengatur hak akses, dan menetapkan peran akun pegawai.',
                    'group' => 'Peran dan Hak Akses',
                ],
                PermissionName::ViewOrganization => [
                    'name' => $permission->value,
                    'label' => 'Lihat struktur organisasi',
                    'description' => 'Membaca katalog level, unit organisasi, jabatan, dan riwayat pejabat.',
                    'group' => 'Organisasi',
                ],
                PermissionName::ManageOrganization => [
                    'name' => $permission->value,
                    'label' => 'Kelola struktur organisasi',
                    'description' => 'Membuat dan memperbarui unit serta jabatan tanpa mengubah katalog level terlindungi.',
                    'group' => 'Organisasi',
                ],
                PermissionName::ManagePositionAssignments => [
                    'name' => $permission->value,
                    'label' => 'Kelola penugasan jabatan',
                    'description' => 'Mengelola masa penugasan pejabat tanpa mengubah hierarki organisasi.',
                    'group' => 'Organisasi',
                ],
                PermissionName::ViewPrivilegeAudits => [
                    'name' => $permission->value,
                    'label' => 'Lihat audit perubahan hak akses',
                    'description' => 'Melihat riwayat pembuatan akun pegawai serta perubahan peran dan hak akses.',
                    'group' => 'Audit dan Keamanan',
                ],
                PermissionName::ViewLetterActivities => [
                    'name' => $permission->value,
                    'label' => 'Lihat aktivitas surat',
                    'description' => 'Melihat jejak penerimaan dan registrasi surat sesuai kewenangan jabatan aktif.',
                    'group' => 'Audit dan Keamanan',
                ],
                PermissionName::ViewIntake => [
                    'name' => $permission->value,
                    'label' => 'Lihat antrean penerimaan surat',
                    'description' => 'Melihat pengajuan surat yang masuk untuk diperiksa oleh Bagian Umum.',
                    'group' => 'Penerimaan Surat',
                ],
                PermissionName::ScreenIntake => [
                    'name' => $permission->value,
                    'label' => 'Periksa pengajuan surat',
                    'description' => 'Meminta pengirim memperbaiki surat atau mengajukannya kepada Kepala Bagian Umum.',
                    'group' => 'Penerimaan Surat',
                ],
                PermissionName::DecideIntake => [
                    'name' => $permission->value,
                    'label' => 'Putuskan dan registrasikan surat',
                    'description' => 'Mengembalikan hasil pemeriksaan kepada petugas, menolak pengajuan, atau meregistrasikannya sebagai surat masuk resmi.',
                    'group' => 'Penerimaan Surat',
                ],
            },
            PermissionName::cases(),
        ));
    }

    /** @return list<string> */
    public static function permissionNames(): array
    {
        return array_map(
            static fn (PermissionName $permission): string => $permission->value,
            PermissionName::cases(),
        );
    }

    /** @return list<string> */
    public static function roleNames(): array
    {
        return array_map(
            static fn (RoleName $role): string => $role->value,
            RoleName::cases(),
        );
    }

    public static function isProtectedRole(string $roleName): bool
    {
        return in_array($roleName, self::roleNames(), true);
    }

    /** @return list<string> */
    public static function permissionsFor(RoleName $role): array
    {
        return match ($role) {
            RoleName::SuperAdmin => [
                PermissionName::ViewAuthorization->value,
                PermissionName::ManageAuthorization->value,
                PermissionName::ViewOrganization->value,
                PermissionName::ManageOrganization->value,
                PermissionName::ManagePositionAssignments->value,
                PermissionName::ViewPrivilegeAudits->value,
                PermissionName::ViewLetterActivities->value,
                PermissionName::ViewIntake->value,
                PermissionName::ScreenIntake->value,
                PermissionName::DecideIntake->value,
            ],
        };
    }
}
