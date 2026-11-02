<?php
require __DIR__ . '/common.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET','POST'], true)) { throw new RuntimeException('Methode nicht erlaubt.',405); }
    $body = [];
    if ($method === 'POST') {
        $raw = file_get_contents('php://input', false, null, 0, 150001);
        if (strlen($raw)>150000) { throw new RuntimeException('Anfrage zu groß.',413); }
        $body = json_decode($raw,true);
        if (!is_array($body)) { throw new RuntimeException('Ungültige Anfrage.',400); }
        requireCsrf($body['csrf'] ?? null);
    }
    // Sitzungssperre verhindert parallele doppelte Abgaben desselben Browsers.
    // Keine Sitzungs-ID oder Zeitstempel im Antwortbestand.
    $result = storage(function (&$s) use ($method,$body,$config) {
        checkSchema($s);
        $submitted = ($_SESSION['submitted_round'] ?? '') === $s['round'];
        if ($method === 'GET') { return ['round'=>$s['round'],'submitted'=>$submitted]; }
        if (($body['round'] ?? '') !== $s['round']) { throw new RuntimeException('Eine neue Runde hat begonnen. Bitte Seite neu laden.',409); }
        if ($submitted) { return ['submitted'=>true,'duplicate'=>true]; }
        $answers = $body['answers'] ?? null;
        if (!is_array($answers) || count($answers)!==count($config['topics']) || array_diff_key($answers,$config['topics']) || array_diff_key($config['topics'],$answers)) { throw new RuntimeException('Bitte alle sieben Fragen beantworten.',422); }
        $clean = [];
        foreach ($answers as $id=>$value) {
            if (!is_string($value) || !preg_match('//u',$value) || preg_match('/[^\s]/u',$value)!==1 || preg_match_all('/./us',$value)>3000) { throw new RuntimeException('Bitte jede Frage mit maximal 3.000 Zeichen beantworten.',422); }
            $clean[$id] = trim($value);
        }
        foreach ($clean as $id=>$value) { $s['answers'][$id][] = $value; shuffle($s['answers'][$id]); }
        $s['count']++;
        return ['submitted'=>true];
    });
    // Erst nach erfolgreicher Dateispeicherung sperren.
    if ($method === 'POST' && !empty($result['submitted'])) { $_SESSION['submitted_round'] = $body['round']; }
    session_write_close();
    echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    $code=$e->getCode(); http_response_code(in_array($code,[400,403,405,409,413,422,503],true)?$code:503);
    echo json_encode(['error'=>$e instanceof RuntimeException ? $e->getMessage() : 'Die Speicherung ist derzeit nicht verfügbar. Bitte Dozent informieren.'],JSON_UNESCAPED_UNICODE);
}
