<?php
require __DIR__ . '/common.php';
pageStart('IHRE ERFAHRUNGEN IM ARBEITSALLTAG');
?>
<section class="privacy-notice" aria-labelledby="privacy-title"><h2 id="privacy-title">Keine Namen oder persönlichen Daten eingeben!</h2>
<p>Diese Abfrage benötigt keine persönlichen Daten und fragt keine Namen oder Kontaktdaten ab. Zu den Antworten speichert das Tool keine IP-Adressen, Teilnehmerkennungen oder Zeitstempel. Die Antworten werden nach Fragen getrennt und ohne Zuordnung zu Personen angezeigt.</p>
<p>Bitte nennen Sie weder Ihren Namen noch Namen anderer Personen, Unternehmen, genaue Standorte oder andere Angaben, die Rückschlüsse auf Personen ermöglichen. Beschreiben Sie ausschließlich Ihre fachlichen Tätigkeiten und Erfahrungen.</p>
<p class="privacy-detail">Freitexte können durch ihren Inhalt erkennbar sein. Der Webhost kann unabhängig von dieser Anwendung technische Zugriffsprotokolle führen.</p></section>
<p class="muted">Bitte beantworten Sie alle sieben Fragen. Falls etwas nicht zu Ihren Aufgaben gehört, genügt „Nicht zutreffend“.</p>
<div id="message" role="status" aria-live="polite">Die Abfrage wird geladen …</div>
<form id="survey" hidden><input type="hidden" id="csrf" value="<?= h(csrf()) ?>"><div class="topics">
<?php $i=0; foreach ($config['topics'] as $id=>$question): $i++; ?>
<section class="topic"><div class="topic-heading"><span class="number"><?= str_pad((string)$i,2,'0',STR_PAD_LEFT) ?></span><label for="<?= h($id) ?>"><?= h($question) ?></label></div>
<textarea id="<?= h($id) ?>" name="<?= h($id) ?>" rows="4" maxlength="3000" required autocomplete="off" spellcheck="false" placeholder="Ihre fachliche Antwort – ohne persönliche Angaben" aria-describedby="hint-<?= h($id) ?>"></textarea>
<span class="field-hint" id="hint-<?= h($id) ?>">Maximal 3.000 Zeichen. Keine Namen oder identifizierenden Angaben.</span></section>
<?php endforeach ?></div><div class="submit-row"><span id="progress" class="muted">0 von 7 Fragen beantwortet</span><button id="submit" type="submit" disabled>Absenden</button></div></form>
<noscript><p class="notice error">Bitte JavaScript aktivieren, um die Fragen zu beantworten.</p></noscript>
<script src="assets/survey.js" defer></script>
<?php pageEnd(); ?>
