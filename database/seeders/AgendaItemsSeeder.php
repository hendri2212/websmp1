<?php

declare(strict_types=1);

final class AgendaItemsSeeder extends Seeder
{
    public function run(PDO $pdo): int
    {
        $agendaItems = [
            [
                'title' => 'Sosialisasi Program Semester Genap',
                'category' => 'Agenda Sekolah',
                'location' => '',
                'description' => 'Penyampaian agenda pembelajaran, asesmen, dan kegiatan sekolah kepada orang tua atau wali peserta didik.',
                'starts_at' => '2026-05-05 08:00:00',
                'ends_at' => null,
                'is_active' => 1,
                'academic_year' => '2026/2027',
                'sort_order' => 1,
            ],
            [
                'title' => 'Pekan Literasi dan Numerasi',
                'category' => 'Agenda Sekolah',
                'location' => '',
                'description' => 'Pelaksanaan kegiatan kelas, penguatan budaya baca, dan proyek numerasi terintegrasi untuk seluruh tingkat.',
                'starts_at' => '2026-05-11 08:00:00',
                'ends_at' => null,
                'is_active' => 1,
                'academic_year' => '2026/2027',
                'sort_order' => 2,
            ],
            [
                'title' => 'Seleksi Internal Lomba Siswa',
                'category' => 'Agenda Sekolah',
                'location' => '',
                'description' => 'Tahap persiapan peserta didik untuk mewakili sekolah dalam ajang akademik dan non-akademik tingkat kabupaten.',
                'starts_at' => '2026-05-18 08:00:00',
                'ends_at' => null,
                'is_active' => 1,
                'academic_year' => '2026/2027',
                'sort_order' => 3,
            ],
        ];

        return $this->seedRows($pdo, 'agenda_items', $agendaItems, ['title']);
    }
}
