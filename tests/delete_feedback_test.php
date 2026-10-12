<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/../includes/functions.php';
$checks = 0;
$failures = 0;
function delete_feedback_check(bool $passed, string $label): void
{
    global $checks, $failures;
    $checks++;
    if (!$passed) {
        $failures++;
        echo '[FAIL] ' . $label . PHP_EOL;
    }
}
$success = admin_delete_feedback(static fn()=>true, 'Berita berhasil dihapus.');
delete_feedback_check($success === ['success'=>true,'message'=>'Berita berhasil dihapus.'], 'confirmed success');
foreach ([static fn()=>false, static function () { throw new MediaRecordNotFoundException('Private record detail'); }] as $operation) {
    $result = admin_delete_feedback($operation, 'Berhasil dihapus.');
    delete_feedback_check($result === ['success'=>false,'message'=>'Data tidak ditemukan atau sudah dihapus.'], 'missing record never succeeds');
}
foreach ([PDOException::class, RuntimeException::class, Error::class] as $type) {
    $result = admin_delete_feedback(static function () use ($type) { throw new $type('SQLSTATE password secret C:/private SELECT'); }, 'Berhasil dihapus.');
    delete_feedback_check($result === ['success'=>false,'message'=>'Data gagal dihapus. Silakan coba lagi.'], 'internal exception hidden');
}
foreach ([null, 1, 'true', ['success'=>'yes','message'=>'secret']] as $invalid) {
    $result = admin_delete_feedback(static fn()=>$invalid, 'Berhasil dihapus.');
    delete_feedback_check($result['success'] === false && $result['message'] === 'Data gagal dihapus. Silakan coba lagi.', 'unconfirmed result cannot succeed');
}
$policy = ['success'=>false,'message'=>'Superadmin aktif terakhir tidak bisa dihapus.'];
delete_feedback_check(admin_delete_feedback(static fn()=>$policy, 'Berhasil dihapus.') === $policy, 'existing account protection preserved');

require __DIR__ . '/../includes/repositories.php';
function db() { return $GLOBALS['delete_feedback_database']; }
function log_admin_activity(...$arguments): void { $GLOBALS['delete_feedback_log_count']++; }
$mock = new class {
    public int $affected = 0;
    public bool $fail = false;
    public function prepare(string $sql): object {
        return new class($this) {
            public function __construct(private object $database) {}
            public function execute(array $values): void {
                if ($this->database->fail) {
                    throw new PDOException('Synthetic private SQL failure');
                }
            }
            public function fetch(): array { return ['title'=>'synthetic','question'=>'synthetic']; }
            public function rowCount(): int { return $this->database->affected; }
        };
    }
};
$GLOBALS['delete_feedback_database'] = $mock;
$GLOBALS['delete_feedback_log_count'] = 0;
foreach (['delete_announcement','delete_agenda_item','delete_faq','delete_contact_message'] as $delete) {
    $mock->affected = 0;
    $before = $GLOBALS['delete_feedback_log_count'];
    delete_feedback_check($delete(123) === false && $GLOBALS['delete_feedback_log_count'] === $before, $delete . ': zero affected rows cannot log success');
    $mock->affected = 1;
    delete_feedback_check($delete(123) === true && $GLOBALS['delete_feedback_log_count'] === $before + 1, $delete . ': one affected row succeeds');
    $mock->fail = true;
    delete_feedback_check(admin_delete_feedback(static fn()=>$delete(123), 'Berhasil dihapus.')['success'] === false, $delete . ': query failure returns generic error');
    $mock->fail = false;
}
$GLOBALS['delete_feedback_database'] = null;
delete_feedback_check(admin_delete_feedback(static fn()=>delete_contact_message(123), 'Berhasil dihapus.')['message'] === 'Data gagal dihapus. Silakan coba lagi.', 'database unavailable never reports success');
echo 'RESULT: ' . ($checks - $failures) . '/' . $checks . ' passed' . PHP_EOL;
exit($failures ? 1 : 0);
