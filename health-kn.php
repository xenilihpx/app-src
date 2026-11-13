<?php
/**
 * Konnektive (Checkout Champ) health check.
 *
 *   GET /src/health-kn.php?key=<HEALTHCHECK_KEY>
 *
 *   200 "OK"      - campaign query succeeded
 *   503 "KN DOWN" - API unreachable, timed out, or returned an error
 *   404           - missing/wrong key, or HEALTHCHECK_KEY not configured
 *
 * Makes a single read-only campaign/query call (no orders, no leads) with a
 * 10 second timeout. It deliberately bypasses KonnektiveApi::get_campaign()
 * because that method serves from a 30-minute file cache.
 */

require_once __DIR__ . '/KonnektiveApi.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

// Constructing KonnektiveApi loads the project's .env into getenv().
new KonnektiveApi();

$expectedKey = (string) getenv('HEALTHCHECK_KEY');
$givenKey    = isset($_GET['key']) && is_string($_GET['key']) ? $_GET['key'] : '';

if ($expectedKey === '' || !hash_equals($expectedKey, $givenKey)) {
	http_response_code(404);
	exit;
}

$campaignId = getenv('HEALTHCHECK_CAMPAIGN_ID') ?: '963';

$ch = curl_init('https://api.checkoutchamp.com/campaign/query/');
curl_setopt_array($ch, [
	CURLOPT_POST           => true,
	CURLOPT_POSTFIELDS     => http_build_query([
		'loginId'    => getenv('KONNEKTIVE_LOGIN_ID'),
		'password'   => getenv('KONNEKTIVE_PASSWORD'),
		'campaignId' => $campaignId,
	]),
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_CONNECTTIMEOUT => 10,
	CURLOPT_TIMEOUT        => 10,
	CURLOPT_HTTPHEADER     => ['Accept: application/json'],
	// Matches KonnektiveApi so this check reflects what checkout actually experiences.
	CURLOPT_SSL_VERIFYPEER => false,
]);

$body     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_errno($ch);
curl_close($ch);

$resp = ($body !== false && !$curlErr) ? json_decode($body) : null;

if ($httpCode === 200 && isset($resp->result) && $resp->result === 'SUCCESS') {
	http_response_code(200);
	echo 'OK';
} else {
	http_response_code(503);
	echo 'KN DOWN';
}
