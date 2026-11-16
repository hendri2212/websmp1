<?php

declare(strict_types=1);

final class AnnouncementsSeeder extends Seeder
{
    public function run(PDO $pdo): int
    {
        $announcements = [
            [
                'title' => 'Layanan Informasi Sekolah Tahun Pelajaran 2025/2026',
                'content' => 'SMP Negeri 1 Kotabaru menyediakan informasi profil sekolah, agenda, dokumen, kegiatan kesiswaan, dan layanan PPDB melalui website resmi ini.',
                'link_label' => 'Lihat Kontak',
                'link_url' => '/kontak',
                'starts_at' => null,
                'ends_at' => null,
                'is_active' => 1,
                'sort_order' => 1,
            ],
            [
                'title' => 'Penguatan Pembelajaran Mendalam',
                'content' => 'Sekolah mengembangkan pembelajaran mendalam melalui eksplorasi, refleksi, kolaborasi, proyek, budaya lokal, dan pengambilan keputusan berbasis data.',
                'link_label' => 'Lihat Akademik',
                'link_url' => '/akademik',
                'starts_at' => null,
                'ends_at' => null,
                'is_active' => 1,
                'sort_order' => 2,
            ],
        ];

        return $this->seedRows($pdo, 'announcements', $announcements, ['title']);
    }
}
