<?php

declare(strict_types=1);

final class PpdbEntriesSeeder extends Seeder
{
    public function run(PDO $pdo): int
    {
        $ppdbEntries = [
            [
                'section' => 'alur',
                'title' => 'Langkah 1',
                'content' => 'Membaca informasi umum, persyaratan, dan jadwal pendaftaran peserta didik baru.',
                'sort_order' => 1,
            ],
            [
                'section' => 'alur',
                'title' => 'Langkah 2',
                'content' => 'Menyiapkan dokumen yang dipersyaratkan sesuai ketentuan resmi Dinas Pendidikan.',
                'sort_order' => 2,
            ],
            [
                'section' => 'alur',
                'title' => 'Langkah 3',
                'content' => 'Melakukan pendaftaran melalui sistem resmi Dinas Pendidikan, bukan website sekolah.',
                'sort_order' => 3,
            ],
            [
                'section' => 'alur',
                'title' => 'Langkah 4',
                'content' => 'Mengikuti proses verifikasi berkas serta menunggu pengumuman hasil seleksi sesuai jadwal resmi.',
                'sort_order' => 4,
            ],
            [
                'section' => 'persyaratan',
                'title' => 'Persyaratan 1',
                'content' => 'Fotokopi kartu keluarga atau dokumen identitas keluarga yang masih berlaku',
                'sort_order' => 1,
            ],
            [
                'section' => 'persyaratan',
                'title' => 'Persyaratan 2',
                'content' => 'Fotokopi akta kelahiran calon peserta didik',
                'sort_order' => 2,
            ],
            [
                'section' => 'persyaratan',
                'title' => 'Persyaratan 3',
                'content' => 'Fotokopi kartu identitas atau dokumen pendukung sesuai ketentuan jalur pendaftaran',
                'sort_order' => 3,
            ],
            [
                'section' => 'persyaratan',
                'title' => 'Persyaratan 4',
                'content' => 'Pas foto terbaru sesuai kebutuhan administrasi sekolah',
                'sort_order' => 4,
            ],
            [
                'section' => 'persyaratan',
                'title' => 'Persyaratan 5',
                'content' => 'Dokumen tambahan lain sesuai pengumuman resmi panitia PPDB sekolah',
                'sort_order' => 5,
            ],
            [
                'section' => 'informasi',
                'title' => 'Jalur Pendaftaran',
                'content' => 'Informasi jalur pendaftaran mengikuti pengumuman resmi PPDB tahun pelajaran berjalan dari sekolah dan dinas terkait.',
                'sort_order' => 1,
            ],
            [
                'section' => 'informasi',
                'title' => 'Jadwal Pelaksanaan',
                'content' => 'Jadwal pendaftaran, verifikasi berkas, pengumuman hasil seleksi, dan daftar ulang akan dipublikasikan melalui kanal resmi sekolah.',
                'sort_order' => 2,
            ],
            [
                'section' => 'informasi',
                'title' => 'Layanan Panitia',
                'content' => 'Calon peserta didik dan orang tua dapat menghubungi sekolah melalui telepon (0518) 21602 atau email smpn1_54ktb@yahoo.co.id pada jam layanan.',
                'sort_order' => 3,
            ],
            [
                'section' => 'kriteria',
                'title' => 'Zonasi',
                'content' => 'Jalur untuk calon peserta didik yang berdomisili dalam wilayah zonasi sekolah sesuai ketentuan PPDB yang berlaku.',
                'sort_order' => 1,
            ],
            [
                'section' => 'kriteria',
                'title' => 'Prestasi',
                'content' => 'Jalur untuk calon peserta didik yang memiliki prestasi akademik atau non-akademik sesuai bukti dan ketentuan panitia PPDB.',
                'sort_order' => 2,
            ],
            [
                'section' => 'kriteria',
                'title' => 'Afirmasi',
                'content' => 'Jalur untuk calon peserta didik dari keluarga kurang mampu atau kelompok sasaran afirmasi sesuai dokumen pendukung resmi.',
                'sort_order' => 3,
            ],
            [
                'section' => 'kriteria',
                'title' => 'Mutasi',
                'content' => 'Jalur perpindahan tugas orang tua/wali atau perpindahan peserta didik sesuai ketentuan dan dokumen pendukung.',
                'sort_order' => 4,
            ],
            [
                'section' => 'catatan',
                'title' => 'Catatan 1',
                'content' => 'Pastikan data calon peserta didik diisi sesuai dokumen resmi keluarga dan sekolah asal.',
                'sort_order' => 1,
            ],
            [
                'section' => 'catatan',
                'title' => 'Catatan 2',
                'content' => 'Berkas pendaftaran diverifikasi oleh panitia PPDB sesuai ketentuan yang berlaku.',
                'sort_order' => 2,
            ],
            [
                'section' => 'catatan',
                'title' => 'Catatan 3',
                'content' => 'Perubahan jadwal atau ketentuan PPDB akan diinformasikan melalui media resmi sekolah.',
                'sort_order' => 3,
            ],
            [
                'section' => 'pengantar',
                'title' => 'halaman',
                'content' => 'Informasi penerimaan peserta didik baru tahun ajaran {tahun_ajaran}. Pendaftaran resmi ditangani Dinas Pendidikan, bukan melalui website sekolah ini.',
                'sort_order' => 1,
            ],
            [
                'section' => 'pengantar',
                'title' => 'kriteria',
                'content' => 'Pilih jalur pendaftaran sesuai kondisi calon peserta didik dan siapkan dokumen pendukung yang diminta panitia.',
                'sort_order' => 2,
            ],
            [
                'section' => 'pengantar',
                'title' => 'dokumen',
                'content' => 'Unduh surat edaran, jadwal, formulir informasi, atau dokumen PPDB lain yang diterbitkan sekolah.',
                'sort_order' => 3,
            ],
            [
                'section' => 'pengantar',
                'title' => 'kontak',
                'content' => 'Hubungi panitia pada jam layanan untuk memastikan jadwal, persyaratan, dan kanal pendaftaran resmi.',
                'sort_order' => 4,
            ],
        ];

        return $this->seedRows($pdo, 'ppdb_entries', $ppdbEntries, ['section', 'title']);
    }
}
