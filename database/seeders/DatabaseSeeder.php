<?php

declare(strict_types=1);

final class DatabaseSeeder extends Seeder
{
    public function run(PDO $pdo): int
    {
        $seeders = [
            new SiteSettingsSeeder(),
            new SchoolFacilitiesSeeder(),
            new ExtracurricularsSeeder(),
            new PpdbEntriesSeeder(),
            new AgendaItemsSeeder(),
            new AnnouncementsSeeder(),
            new FaqsSeeder(),
        ];
        $changed = 0;
        foreach ($seeders as $seeder) {
            $changed += $seeder->run($pdo);
        }
        return $changed;
    }
}
