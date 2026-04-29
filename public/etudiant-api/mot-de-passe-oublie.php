<?php
/** Stub — POST /api/mot-de-passe-oublie.php { email } */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

if (!etu_rate_limit('forgot', 5, 900)) {
    api_error('Trop de demandes. Réessaie plus tard.', 429);
}
$body = api_body();
$email = trim(strtolower((string)($body['email'] ?? '')));
if ($email === '') api_error('Email requis', 400);

// TODO: générer token sha256, INSERT etudiant_tokens, envoyer mail.
// Réponse uniforme (ne révèle pas l'existence du compte).
api_json(['ok' => true]);
