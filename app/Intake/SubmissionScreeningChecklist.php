<?php

namespace App\Intake;

final class SubmissionScreeningChecklist
{
    /**
     * @return list<array{id: string, label: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            [
                'id' => 'sender',
                'label' => 'Identitas pengirim dapat diverifikasi',
                'description' => 'Nama instansi dan kontak pengirim tersedia.',
            ],
            [
                'id' => 'letter-metadata',
                'label' => 'Data surat konsisten',
                'description' => 'Nomor, tanggal, perihal, dan ringkasan saling sesuai.',
            ],
            [
                'id' => 'document',
                'label' => 'Dokumen PDF terbaca dan lengkap',
                'description' => 'Lampiran dapat dibuka dan tidak terpotong.',
            ],
            [
                'id' => 'scope',
                'label' => 'Surat termasuk jalur pimpinan',
                'description' => 'Surat layak diteruskan ke Sekretaris Daerah.',
            ],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_column(self::definitions(), 'id');
    }

    /**
     * @param  list<array{id: string, checked: bool}>  $items
     * @return array<string, bool>
     */
    public static function normalize(array $items): array
    {
        $submitted = [];

        foreach ($items as $item) {
            $submitted[$item['id']] = $item['checked'];
        }

        $normalized = [];

        foreach (self::keys() as $key) {
            $normalized[$key] = $submitted[$key] ?? false;
        }

        return $normalized;
    }

    /**
     * @param  array<string, bool>|null  $checklist
     * @return list<array{id: string, label: string, description: string, checked: bool}>
     */
    public static function present(?array $checklist): array
    {
        return array_map(
            static fn (array $definition): array => [
                ...$definition,
                'checked' => $checklist[$definition['id']] ?? false,
            ],
            self::definitions(),
        );
    }

    /** @param array<string, bool> $checklist */
    public static function isComplete(array $checklist): bool
    {
        return count($checklist) === count(self::keys())
            && ! in_array(false, $checklist, true);
    }
}
