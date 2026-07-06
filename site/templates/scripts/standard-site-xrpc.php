<?php

/**
 * Minimal AT Protocol XRPC helpers for Standard.site record management.
 * Safe to include multiple times (functions are guarded by the early return).
 */

if (function_exists('standardSiteXrpcPost')) return;

// ─── LOW-LEVEL HTTP ───────────────────────────────────────────────────────────

function standardSiteXrpcPost(string $base, string $method, array $body, ?string $token = null): array {
	$url     = rtrim($base, '/') . '/xrpc/' . $method;
	$headers = "Content-Type: application/json\r\n";
	if ($token) $headers .= "Authorization: Bearer {$token}\r\n";

	$ctx = stream_context_create(['http' => [
		'method'        => 'POST',
		'header'        => $headers,
		'content'       => json_encode($body),
		'ignore_errors' => true,   // don't throw on 4xx/5xx — we handle it below
		'timeout'       => 15,
	]]);

	$response = file_get_contents($url, false, $ctx);

	// Parse the HTTP status code from the response headers PHP populates.
	$status = 0;
	if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
		$status = (int) $m[1];
	}

	$data = json_decode((string) $response, true);

	if ($status < 200 || $status >= 300) {
		$msg = is_array($data) ? json_encode($data) : (string) $response;
		throw new RuntimeException("{$method} HTTP {$status}: {$msg}");
	}

	return is_array($data) ? $data : [];
}

// ─── AUTHENTICATION ───────────────────────────────────────────────────────────

function standardSiteCreateSession(string $pds, string $handle, string $password): array {
	$session = standardSiteXrpcPost($pds, 'com.atproto.server.createSession', [
		'identifier' => $handle,   // your Bluesky handle, e.g. "gav.cloud"
		'password'   => $password, // app password from Bluesky settings
	]);

	if (empty($session['did']) || empty($session['accessJwt'])) {
		throw new RuntimeException('createSession: missing did or accessJwt in response.');
	}

	return [
		'did'   => $session['did'],        // your permanent identity, e.g. "did:plc:aq3…"
		'token' => $session['accessJwt'],  // bearer token, valid ~2 hours
	];
}

// ─── RECORD CREATION ─────────────────────────────────────────────────────────

function standardSiteCreateDocument(string $pds, string $did, string $token, array $record): string {
	$result = standardSiteXrpcPost($pds, 'com.atproto.repo.createRecord', [
		'repo'       => $did,                      // whose repo to write to
		'collection' => 'site.standard.document',  // the lexicon namespace
		'record'     => $record,                   // the document payload
	], $token);

	if (empty($result['uri'])) {
		throw new RuntimeException('createRecord returned no uri.');
	}

	return $result['uri']; // e.g. "at://did:plc:aq3…/site.standard.document/3xyz…"
}
