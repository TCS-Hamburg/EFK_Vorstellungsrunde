<?php
declare(strict_types=1);
ini_set('display_errors', '0');
$config = require __DIR__ . '/config.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'self'");
$cookiePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';
session_name('tcs_' . substr(hash('sha256', __DIR__), 0, 16));
session_set_cookie_params(['path' => $cookiePath, 'httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax']);
ini_set('session.use_strict_mode', '1');
if (!session_start()) { http_response_code(503); exit('Die Sitzung konnte nicht gestartet werden. Bitte PHP-Sitzungsspeicher auf dem Webspace prüfen.'); }
$_SESSION['csrf'] ??= bin2hex(random_bytes(24));
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { return (string)$_SESSION['csrf']; }
function requireCsrf(?string $token): void {
    if (!is_string($token) || !hash_equals(csrf(), $token)) { throw new RuntimeException('Die Sitzung ist abgelaufen. Bitte die Seite neu laden.', 403); }
}
function schema(): string { global $config; return hash('sha256', json_encode($config['topics'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)); }
function fresh(): array { global $config; return ['schema' => schema(), 'round' => bin2hex(random_bytes(16)), 'count' => 0, 'answers' => array_fill_keys(array_keys($config['topics']), [])]; }
// PHP-Datei: auch bei direktem Browseraufruf werden niemals Rohdaten ausgegeben.
function storage(callable $fn): mixed {
    $directory = __DIR__ . '/data';
    if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Der Datenordner konnte nicht erstellt werden. Bitte Schreibberechtigung des Tool-Ordners prüfen.', 503);
    }
    if (!is_file($directory . '/.htaccess')) {
        @file_put_contents($directory . '/.htaccess', "Require all denied\n", LOCK_EX);
    }
    $path = $directory . '/answers.php';
    $fp = @fopen($path, 'c+b');
    if (!$fp) { throw new RuntimeException('Speichern nicht möglich: Der Ordner data muss für PHP beschreibbar sein.', 503); }
    try {
        if (!flock($fp, LOCK_EX)) { throw new RuntimeException('Die Datendatei ist momentan nicht verfügbar.', 503); }
        $raw = stream_get_contents($fp);
        $prefix = "<?php exit; ?>\n";
        if ($raw === '') { $state = fresh(); }
        else {
            if (!str_starts_with($raw, $prefix)) { throw new RuntimeException('Datendatei ungültig. Bitte Trainer informieren.', 503); }
            $state = json_decode(substr($raw, strlen($prefix)), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($state) || !isset($state['schema'], $state['round'], $state['count'], $state['answers']) || !is_array($state['answers'])) { throw new RuntimeException('Datendatei ungültig.', 503); }
        }
        $before = $state;
        $result = $fn($state);
        if ($state !== $before || $raw === '') {
            $out = $prefix . json_encode($state, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            rewind($fp);
            $written = 0;
            while ($written < strlen($out)) {
                $n = fwrite($fp, substr($out, $written));
                if ($n === false || $n === 0) { throw new RuntimeException('Speichern fehlgeschlagen. Bitte erneut versuchen.', 503); }
                $written += $n;
            }
            if (!ftruncate($fp, strlen($out)) || !fflush($fp)) { throw new RuntimeException('Speichern fehlgeschlagen.', 503); }
            if (function_exists('fsync') && !fsync($fp)) { throw new RuntimeException('Speichern fehlgeschlagen.', 503); }
        }
        return $result;
    } finally { flock($fp, LOCK_UN); fclose($fp); }
}
function checkSchema(array $state): void { if ($state['schema'] !== schema()) { throw new RuntimeException('Die Themen wurden geändert. Bitte auf der Trainerseite mit Clear eine neue Runde starten.', 409); } }
function pageStart(string $subtitle): void {
    global $config;
    echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . h($config['title']) . '</title><link rel="stylesheet" href="assets/style.css?v=intro1"></head><body><main><div class="page-heading"><img class="title-logo" src="assets/tcs-logo.png" width="80" height="44" alt="Technology Consulting Solutions GmbH"><div class="heading-text"><p class="eyebrow">' . h($subtitle) . '</p><h1>' . h($config['title']) . '</h1></div></div>';
}
function pageEnd(): void { echo '</main><footer>Technology Consulting Solutions GmbH · <a href="https://tcs-engineering.de/" target="_blank" rel="noopener">tcs-engineering.de</a></footer></body></html>'; }
