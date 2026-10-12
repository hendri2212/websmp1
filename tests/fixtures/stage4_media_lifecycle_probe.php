<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require $argv[1];
$GLOBALS['lifecycle_database'] = stage3_test_database();
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/repositories.php';
function db() { return $GLOBALS['lifecycle_database']; }
function log_admin_activity(...$arguments): void {}
$pdo = db();
$results = [];
$ids = [];
$paths = [];
$tag = 'TEST_TAHAP3_LIFECYCLE_' . bin2hex(random_bytes(6));
$settingKey = $tag;
function lifecycle_check(bool $condition, string $label): void
{
    global $results;
    $results[] = ['passed'=>$condition, 'label'=>$label];
}
function lifecycle_image(): string
{
    global $paths;
    $name = 'gallery-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.png';
    $relative = 'public/uploads/gallery/' . $name;
    foreach ([$relative, 'public/uploads/gallery/thumbs/' . $name] as $path) {
        $local = __DIR__ . '/' . $path;
        if (!is_dir(dirname($local))) {
            mkdir(dirname($local), 0700, true);
        }
        if (!reserve_uploaded_file($local)) {
            throw new RuntimeException('Isolated media reservation failed.');
        }
        $image = imagecreatetruecolor(640, 480);
        imagepng($image, $local);
        imagedestroy($image);
        $paths[] = $path;
    }
    return '/' . $relative;
}
function lifecycle_exists(string $path): bool
{
    clearstatcache();
    return is_file(__DIR__ . '/' . ltrim($path, '/'));
}
function lifecycle_row(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM gallery_items WHERE id = ?');
    $statement->execute([$id]);
    return $statement->fetch() ?: null;
}
function lifecycle_id(string $title): int
{
    $statement = db()->prepare('SELECT id FROM gallery_items WHERE title = ?');
    $statement->execute([$title]);
    return (int) $statement->fetchColumn();
}
function lifecycle_expect_failure(callable $operation, string $label): void
{
    try {
        $operation();
        lifecycle_check(false, $label);
    } catch (Throwable $exception) {
        lifecycle_check(true, $label);
    }
}
try {
    $old = lifecycle_image();
    $data = ['title'=>$tag, 'description'=>'Safe lifecycle fixture', 'sort_order'=>1, 'image_path'=>$old];
    save_gallery_item($data);
    $id = lifecycle_id($data['title']);
    $ids[] = $id;
    lifecycle_check(lifecycle_exists($old) && lifecycle_row($id)['image_path'] === $old, 'create: persisted record references existing owned media');
    save_gallery_item($data + ['id'=>$id]);
    lifecycle_check(lifecycle_exists($old), 'unchanged update: old file not collected');

    $new = lifecycle_image();
    unlink(__DIR__ . '/' . ltrim($new, '/'));
    lifecycle_expect_failure(fn()=>save_gallery_item(array_replace($data, ['id'=>$id,'image_path'=>$new])), 'missing newly written file: save rejected before DB commit');
    lifecycle_check(lifecycle_row($id)['image_path'] === $old && lifecycle_exists($old), 'missing new file: old DB reference and old file preserved');
    lifecycle_check(!lifecycle_exists(dirname($new) . '/thumbs/' . basename($new)), 'missing new file: sibling thumbnail rolled back');

    $new = lifecycle_image();
    lifecycle_expect_failure(fn()=>save_gallery_item(array_replace($data, ['id'=>$id,'image_path'=>$new,'sort_order'=>'not-an-integer'])), 'SQL failure after upload: save rejected');
    lifecycle_check(lifecycle_row($id)['image_path'] === $old && lifecycle_exists($old), 'SQL failure: original record/file preserved');
    lifecycle_check(!lifecycle_exists($new) && !lifecycle_exists(dirname($new) . '/thumbs/' . basename($new)), 'SQL failure: new image and thumbnail cleaned');

    $new = lifecycle_image();
    lifecycle_expect_failure(fn()=>save_gallery_item(array_replace($data, ['id'=>2147483647,'image_path'=>$new])), 'nonexistent record: upload update rejected');
    lifecycle_check(!lifecycle_exists($new) && lifecycle_exists($old), 'nonexistent record: new upload cleaned, valid old file unchanged');
    lifecycle_expect_failure(fn()=>delete_gallery_item(2147483647), 'nonexistent delete: no false success');

    save_gallery_item(array_replace($data, ['title'=>$tag . '_SHARED']));
    $shared = lifecycle_id($tag . '_SHARED');
    $ids[] = $shared;
    delete_gallery_item($id);
    lifecycle_check(lifecycle_row($id) === null && lifecycle_exists($old) && lifecycle_row($shared)['image_path'] === $old, 'shared reference: deleting one record preserves media for remaining owner');

    $local = __DIR__ . '/' . ltrim($old, '/');
    $held = $local . '.held';
    if (!rename($local, $held) || !mkdir($local)) {
        throw new RuntimeException('Cannot arrange isolated unlink failure fixture.');
    }
    try {
        delete_gallery_item($shared);
        $journal = __DIR__ . '/storage/media-cleanup/' . hash('sha256', ltrim($old, '/')) . '.json';
        lifecycle_check(lifecycle_row($shared) === null && is_file($journal), 'unlink failure: DB delete completed; durable retry journal retained');
    } finally {
        rmdir($local);
        rename($held, $local);
    }
    retry_media_cleanup($pdo);
    lifecycle_check(!lifecycle_exists($old) && !is_file($journal), 'retry after unlink failure: unreferenced image collected and job removed');
    lifecycle_check(!lifecycle_exists(dirname($old) . '/thumbs/' . basename($old)), 'retry: obsolete thumbnail collected');

    $unreferenced = lifecycle_image();
    commit_uploaded_files();
    foreach (media_paths_in_value($unreferenced) as $path) {
        media_cleanup_enqueue($path);
    }
    $unavailable = new class {
        public function query(string $sql) { throw new PDOException('Synthetic reference lookup failure'); }
    };
    retry_media_cleanup($unavailable);
    lifecycle_check(lifecycle_exists($unreferenced), 'reference query unavailable: collector fails closed and preserves file');
    retry_media_cleanup($pdo);
    lifecycle_check(!lifecycle_exists($unreferenced), 'reference database recovery: deferred unreferenced media collected');

    $uncertain = lifecycle_image();
    $GLOBALS['media_commit_in_progress'] = true;
    rollback_uploaded_files();
    $GLOBALS['media_commit_in_progress'] = false;
    lifecycle_check(lifecycle_exists($uncertain), 'shutdown during uncertain commit: direct unlink is suppressed');
    retry_media_cleanup($pdo);
    lifecycle_check(!lifecycle_exists($uncertain), 'uncertain shutdown retry: actual references determine collection');

    $new = lifecycle_image();
    $GLOBALS['lifecycle_database'] = null;
    lifecycle_expect_failure(fn()=>save_gallery_item(array_replace($data, ['image_path'=>$new])), 'database unavailable: no false successful save');
    $GLOBALS['lifecycle_database'] = $pdo;
    lifecycle_check(!lifecycle_exists($new), 'database unavailable: new upload cleaned immediately');

    $new = lifecycle_image();
    $nonTransactional = new class($pdo) {
        public function __construct(private PDO $pdo) {}
        public function __call(string $method, array $arguments) { return $this->pdo->$method(...$arguments); }
        public function query(string $sql) {
            if (str_contains($sql, 'information_schema.TABLES')) {
                return new class { public function fetchColumn(): string { return 'MyISAM'; } };
            }
            return $this->pdo->query($sql);
        }
    };
    $GLOBALS['lifecycle_database'] = $nonTransactional;
    lifecycle_expect_failure(fn()=>save_gallery_item(array_replace($data, ['image_path'=>$new])), 'nontransactional engine: file mutation fails closed without schema conversion');
    $GLOBALS['lifecycle_database'] = $pdo;
    lifecycle_check(!lifecycle_exists($new), 'nontransactional engine: pending upload rolled back');

    $old = lifecycle_image();
    save_site_sections([$settingKey=>['path'=>$old,'text'=>'old']]);
    $new = lifecycle_image();
    lifecycle_expect_failure(fn()=>save_site_sections([$settingKey=>['path'=>$new],$settingKey . '_BAD'=>["\xff"]]), 'settings JSON failure after first write: operation rejected');
    $statement = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    $statement->execute([$settingKey]);
    lifecycle_check(json_decode($statement->fetchColumn(), true)['path'] === $old && lifecycle_exists($old), 'settings failure: transaction restores old reference and file');
    lifecycle_check(!lifecycle_exists($new), 'settings failure: new media rolled back');

    $new = lifecycle_image();
    $proxy = new class($pdo) {
        public function __construct(private PDO $pdo) {}
        public function __call(string $method, array $arguments) { return $this->pdo->$method(...$arguments); }
        public function commit(): bool {
            $this->pdo->commit();
            throw new PDOException('Synthetic lost commit acknowledgement');
        }
    };
    $GLOBALS['lifecycle_database'] = $proxy;
    lifecycle_expect_failure(fn()=>save_site_sections([$settingKey=>['path'=>$new]]), 'ambiguous commit acknowledgement: failure propagated safely');
    $GLOBALS['lifecycle_database'] = $pdo;
    lifecycle_check(lifecycle_exists($new), 'ambiguous commit: shutdown rollback cannot delete possibly persisted file');
    retry_media_cleanup($pdo);
    $statement->execute([$settingKey]);
    lifecycle_check(json_decode($statement->fetchColumn(), true)['path'] === $new && lifecycle_exists($new) && !lifecycle_exists($old), 'ambiguous commit retry: actual DB reference protects new file and collects obsolete old file');

    // Database deletion failure must not remove the still-referenced old file.
    $data['image_path'] = $new;
    save_gallery_item($data);
    $id = lifecycle_id($data['title']);
    $ids[] = $id;
    $proxy = new class($pdo) {
        public function __construct(private PDO $pdo) {}
        public function __call(string $method, array $arguments) { return $this->pdo->$method(...$arguments); }
        public function prepare(string $sql) {
            if (str_starts_with($sql, 'DELETE FROM gallery_items')) {
                return $this->pdo->prepare('DELETE FROM gallery_items WHERE TEST_TAHAP3_NONEXISTENT_COLUMN = 1');
            }
            return $this->pdo->prepare($sql);
        }
    };
    $GLOBALS['lifecycle_database'] = $proxy;
    lifecycle_expect_failure(fn()=>delete_gallery_item($id), 'DB delete failure: operation rejected');
    $feedback = admin_delete_feedback(static fn()=>delete_gallery_item($id), 'Item galeri berhasil dihapus.');
    lifecycle_check($feedback === ['success'=>false,'message'=>'Data gagal dihapus. Silakan coba lagi.'], 'actual SQL delete failure: form feedback is generic and never success');
    $GLOBALS['lifecycle_database'] = $pdo;
    retry_media_cleanup($pdo);
    lifecycle_check(lifecycle_row($id) !== null && lifecycle_exists($new), 'DB delete failure: live reference and file remain intact');
    lifecycle_check(managed_media_path('../public/logo-sekolah.png') === null
        && managed_media_path('storage/uploads/ppdb/file.pdf') === null
        && managed_media_path('/public/logo-sekolah.png') === null, 'collector never accepts traversal, PPDB-owned media, or original site assets');
} finally {
    $GLOBALS['lifecycle_database'] = $pdo;
    rollback_uploaded_files();
    foreach ($ids as $id) {
        if (lifecycle_row($id)) {
            delete_gallery_item($id);
        }
    }
    $statement = $pdo->prepare('DELETE FROM site_settings WHERE setting_key IN (?, ?)');
    $statement->execute([$settingKey, $settingKey . '_BAD']);
    retry_media_cleanup($pdo);
    foreach ($paths as $path) {
        if (lifecycle_exists($path)) {
            media_cleanup_enqueue($path);
        }
    }
    retry_media_cleanup($pdo);
}
lifecycle_check((glob(__DIR__ . '/storage/media-cleanup/*.json') ?: []) === [], 'isolated failure probes leave no outstanding cleanup jobs');
echo json_encode($results, JSON_THROW_ON_ERROR);
