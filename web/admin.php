<?php
require __DIR__ . '/common.php';
$error = ''; $success = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrf($_POST['csrf'] ?? null);
        $action = $_POST['action'] ?? '';
        if ($action === 'login') {
            if (time() < ($_SESSION['retry_after'] ?? 0)) { throw new RuntimeException('Bitte kurz warten und erneut versuchen.'); }
            if (!hash_equals($config['password'], (string)($_POST['password'] ?? ''))) {
                $_SESSION['retry_after'] = time() + 3;
                throw new RuntimeException('Das Passwort ist nicht korrekt.');
            }
            session_regenerate_id(true); $_SESSION['admin'] = true; $_SESSION['csrf'] = bin2hex(random_bytes(24));
            header('Location: admin.php'); exit;
        }
        if (empty($_SESSION['admin'])) { throw new RuntimeException('Bitte anmelden.'); }
        if ($action === 'logout') { $_SESSION = []; session_destroy(); header('Location: admin.php'); exit; }
        if ($action === 'clear') {
            $oldRound = (string)($_POST['round'] ?? '');
            storage(function (&$s) use ($oldRound) {
                if ($s['round'] !== $oldRound) { throw new RuntimeException('Die Runde wurde bereits zurückgesetzt. Bitte Seite neu laden.'); }
                $s = fresh();
            });
            header('Location: admin.php?cleared=1'); exit;
        }
    }
} catch (Throwable $e) { $error = $e instanceof RuntimeException ? $e->getMessage() : 'Die Datendatei konnte nicht verarbeitet werden.'; }
$state = null;
if (!empty($_SESSION['admin'])) {
    try { $state = storage(fn(&$s) => $s); }
    catch (Throwable $e) { $error = $e instanceof RuntimeException ? $e->getMessage() : 'Die Datendatei konnte nicht gelesen werden.'; }
}
if ($state && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'export' && $error === '') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="EFK-Vorstellungsrunde.csv"');
    $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
    $safe=fn($v)=>preg_match('/^[\s]*[=+@\-]/u',(string)$v) ? "'".$v : $v;
    fputcsv($out,['Frage','Antwort'],';','"','');
    foreach ($config['topics'] as $id=>$question) {
        foreach (($state['answers'][$id] ?? []) as $answer) { fputcsv($out,[$safe($question),$safe($answer)],';','"',''); }
    }
    fclose($out); exit;
}
pageStart('DOZENTENANSICHT');
if ($error) { echo '<p class="notice error" role="alert">' . h($error) . '</p>'; }
if (empty($_SESSION['admin'])): ?>
<p class="intro">Auswertung öffnen</p>
<form method="post" class="login card"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="action" value="login"><label for="password">Dozenten-Passwort</label><input id="password" name="password" type="password" required autocomplete="current-password"><button>Anmelden</button></form>
<?php else: ?>
<div class="toolbar"><a href="./" target="_blank" rel="noopener">Teilnehmerseite öffnen ↗</a><form method="post"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><button name="action" value="logout" class="secondary">Abmelden</button></form></div>
<?php if (isset($_GET['cleared'])): ?><p class="notice">Alle Antworten wurden gelöscht. Die neue Abfragerunde ist geöffnet.</p><?php endif ?>
<?php if ($state): $mismatch = $state['schema'] !== schema(); ?>
<?php if ($mismatch): ?><p class="notice error">Die Themenkonfiguration wurde geändert. Bitte mit Clear eine neue Runde starten.</p><?php endif ?>
<div class="summary"><strong><?= $state['count'] ?></strong><span>Teilnehmer haben eine Rückmeldung abgegeben</span><a class="button secondary" href="admin.php">Aktualisieren</a></div>
<p class="notice">Die Antworten werden getrennt nach Fragen angezeigt und innerhalb jeder Frage gemischt. Es gibt keine Teilnehmerprofile, Kennungen, Abgabezeitpunkte oder Zuordnung zwischen Antworten verschiedener Fragen.</p>
<?php if ($state['count']===0): ?><p class="muted">Bisher wurden keine Antworten abgegeben.</p><?php endif ?>
<?php foreach ($config['topics'] as $id=>$question): ?>
<section class="answer-group"><h2><?= h($question) ?></h2><p class="muted"><?= count($state['answers'][$id] ?? []) ?> Antworten</p>
<?php foreach (($state['answers'][$id] ?? []) as $answer): ?><div class="card free-answer"><?= h($answer) ?></div><?php endforeach ?></section>
<?php endforeach ?>
<div class="actions"><form method="post"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><button name="action" value="export" class="secondary">CSV exportieren</button></form></div>
<section class="card reset"><h2>Neue Abfragerunde</h2><p>Clear löscht alle gespeicherten Antworten unwiderruflich. Anschließend können alle Teilnehmer erneut antworten.</p><form method="post" id="clear-form"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="round" value="<?= h($state['round']) ?>"><button name="action" value="clear" class="danger">Clear</button></form></section>
<script src="assets/admin.js" defer></script>
<?php endif; endif; pageEnd(); ?>
