<?php

declare(strict_types=1);

final class ExtracurricularsSeeder extends Seeder
{
    public function run(PDO $pdo): int
    {
        $extracurriculars = [
            [
                'name' => 'Pramuka',
                'category' => 'Wajib',
                'coach' => null,
                'schedule_text' => null,
                'location' => null,
                'description' => 'Pembinaan kedisiplinan, kemandirian, kerja sama, dan kepedulian sosial melalui kegiatan kepramukaan.',
                'image_path' => '',
                'is_active' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => 'Jurnalistik',
                'category' => 'Literasi',
                'coach' => null,
                'schedule_text' => null,
                'location' => null,
                'description' => 'Wadah pengembangan kemampuan menulis, wawancara, dokumentasi, dan publikasi kegiatan sekolah.',
                'image_path' => '',
                'is_active' => 1,
                'sort_order' => 2,
            ],
            [
                'name' => 'PMR',
                'category' => 'Kesehatan',
                'coach' => null,
                'schedule_text' => null,
                'location' => null,
                'description' => 'Pembinaan kepedulian, kesiapsiagaan, dan layanan dasar kesehatan di lingkungan sekolah.',
                'image_path' => '',
                'is_active' => 1,
                'sort_order' => 3,
            ],
            [
                'name' => 'Futsal',
                'category' => 'Olahraga',
                'coach' => null,
                'schedule_text' => null,
                'location' => null,
                'description' => 'Kegiatan pengembangan kebugaran, sportivitas, dan kerja sama tim melalui pembinaan olahraga.',
                'image_path' => '',
                'is_active' => 1,
                'sort_order' => 4,
            ],
            [
                'name' => 'Paduan Suara',
                'category' => 'Seni',
                'coach' => null,
                'schedule_text' => null,
                'location' => null,
                'description' => 'Pengembangan kepekaan seni, disiplin latihan, dan kerja sama melalui kegiatan vokal bersama.',
                'image_path' => '',
                'is_active' => 1,
                'sort_order' => 5,
            ],
            [
                'name' => 'Seni Tari',
                'category' => 'Seni',
                'coach' => null,
                'schedule_text' => null,
                'location' => null,
                'description' => 'Pembinaan kreativitas, apresiasi budaya, dan keberanian tampil melalui kegiatan seni pertunjukan.',
                'image_path' => '',
                'is_active' => 1,
                'sort_order' => 6,
            ],
        ];

        return $this->seedRows($pdo, 'extracurriculars', $extracurriculars, ['name']);
    }
}
