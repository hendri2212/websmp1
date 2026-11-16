<?php

declare(strict_types=1);

final class FaqsSeeder extends Seeder
{
    public function run(PDO $pdo): int
    {
        $faqs = [
            ['question' => 'Di mana alamat SMP Negeri 1 Kotabaru?', 'answer' => 'Alamat sekolah berada di Jl. M. Alwi No. 158 Kotabaru, Semayap, Kecamatan Pulau Laut Utara, Kabupaten Kotabaru, Kalimantan Selatan 72117.', 'category' => 'Profil', 'is_active' => 1, 'sort_order' => 1],
            ['question' => 'Bagaimana cara menghubungi sekolah?', 'answer' => 'Sekolah dapat dihubungi melalui telepon (0518) 21602 atau email smpn1_54ktb@yahoo.co.id pada jam layanan Senin-Jumat pukul 07.00-15.00.', 'category' => 'Layanan', 'is_active' => 1, 'sort_order' => 2],
            ['question' => 'Apakah informasi PPDB tersedia di website?', 'answer' => 'Ya. Informasi PPDB, persyaratan, dan catatan penting tersedia pada menu PPDB. Pendaftaran resmi ditangani Dinas Pendidikan, bukan website sekolah. Jadwal resmi mengikuti pengumuman sekolah dan dinas terkait.', 'category' => 'PPDB', 'is_active' => 1, 'sort_order' => 3],
            ['question' => 'Dokumen apa saja yang biasanya diperlukan untuk PPDB?', 'answer' => 'Calon peserta didik biasanya perlu menyiapkan kartu keluarga, akta kelahiran, dokumen identitas atau pendukung sesuai jalur pendaftaran, pas foto, dan dokumen tambahan sesuai pengumuman panitia.', 'category' => 'PPDB', 'is_active' => 1, 'sort_order' => 4],
            ['question' => 'Apakah sekolah memiliki kegiatan ekstrakurikuler?', 'answer' => 'Ya. Sekolah menyediakan kegiatan pengembangan minat dan bakat seperti Pramuka, Jurnalistik, PMR, Futsal, Paduan Suara, Seni Tari, dan kegiatan lain yang dapat diperbarui melalui menu Ekstrakurikuler.', 'category' => 'Kesiswaan', 'is_active' => 1, 'sort_order' => 5],
            ['question' => 'Bagaimana cara melihat agenda sekolah?', 'answer' => 'Agenda kegiatan sekolah dapat dilihat melalui menu Agenda. Admin sekolah dapat memperbarui jadwal kegiatan sesuai tahun ajaran aktif.', 'category' => 'Agenda', 'is_active' => 1, 'sort_order' => 6],
            ['question' => 'Apakah dokumen sekolah bisa diunduh?', 'answer' => 'Ya. Dokumen resmi seperti kalender akademik, formulir, surat edaran, tata tertib, dan file PDF lain dapat diunggah admin dan diunduh melalui menu Dokumen.', 'category' => 'Dokumen', 'is_active' => 1, 'sort_order' => 7],
            ['question' => 'Apa fokus pengembangan pembelajaran sekolah?', 'answer' => 'Sekolah memperkuat pembelajaran mendalam melalui eksplorasi, refleksi, kolaborasi, proyek, budaya lokal, teknologi, dan pengambilan keputusan berbasis data.', 'category' => 'Akademik', 'is_active' => 1, 'sort_order' => 8],
        ];

        return $this->seedRows($pdo, 'faqs', $faqs, ['question']);
    }
}
