<?php
/**
 * One-time/dev batch translator for the checkout "system" messages
 * (window.i18nData in src/system/<lang>.js - loaded as lang/system/<lang>.js
 * on checkout.php / thankyou.php, see efscriptAndSystemLang.php).
 *
 * Mirrors src/statesTranslator.php's convention: only fills in keys that are
 * MISSING from a target language file compared to src/system/en.js - it never
 * overwrites an existing translation. Re-running this script is safe and
 * cheap; it's the tool to use after adding a new key to src/system/en.js.
 * test again
 * Usage: php src/systemLangTranslator.php   (from the project root, CLI)
 *     or open http://localhost/melaraapex/src/systemLangTranslator.php in a browser
 */

$isCli = PHP_SAPI === 'cli';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
if (!$isCli && !in_array($serverName, ['localhost', '127.0.0.1'], true)) {
    http_response_code(403);
    exit('systemLangTranslator.php only runs on localhost or via CLI.');
}

if (!$isCli) {
    header('Content-Type: text/plain');
}

if (!defined('BASEPATH')) {
    $repoName = $isCli ? '' : '/' . basename(dirname(__DIR__));
    define('BASEPATH', $isCli ? dirname(__DIR__) : $_SERVER['DOCUMENT_ROOT'] . $repoName);
}

require_once BASEPATH . '/vendor/autoload.php';

use DeepL\Translator;

// getenv('DEEPL_KEY') only sees the OS/Apache environment; the key lives in
// .env, so it has to be parsed in first. Same approach as
// src/statesTranslator.php, duplicated here since this script has no other
// bootstrap and runs standalone (CLI or direct browser hit).
$envFile = BASEPATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = array_map('trim', explode('=', $line, 2));
        if (getenv($key) === false) {
            putenv($key . '=' . trim($value, "\"'"));
        }
    }
}

$deepLKey = getenv('DEEPL_KEY');
if (!$deepLKey) {
    exit("DEEPL_KEY is not set (checked environment and {$envFile}).\n");
}

// Same mapping convention as src/JsonTranslateCollector.php / src/statesTranslator.php.
$deepLLanguageMap = [
    'pt-br'   => 'PT-BR',
    'pt-pt'   => 'PT-PT',
    'pt'   => 'PT-PT',
    'sr-latn'   => 'sr',
    'mx'      => 'ES',      // Spanish (Mexico) - DeepL has no MX-specific target
    'zh-hans' => 'ZH-HANS',
    'zh-hant' => 'ZH-HANT',
];
// A couple of DeepL accounts/plans reject the split ZH-HANS/ZH-HANT targets -
// fall back to plain ZH (Simplified) rather than leaving the key untranslated.
$deepLFallbackMap = [
    'ZH-HANS' => 'ZH',
    'ZH-HANT' => 'ZH',
];

$sourceLanguage = 'en';
$systemDir = BASEPATH . '/src/system';
$sourcePath = "{$systemDir}/{$sourceLanguage}.js";

if (!file_exists($sourcePath)) {
    exit("Source file not found: {$sourcePath}\n");
}

$sourceData = parseI18nJs(file_get_contents($sourcePath));
if (!$sourceData) {
    exit("Could not parse source file: {$sourcePath}\n");
}

$translator = new Translator($deepLKey);

$files = glob("{$systemDir}/*.js");
sort($files);

foreach ($files as $filePath) {
    $lang = basename($filePath, '.js');
    if ($lang === $sourceLanguage) {
        continue;
    }

    $raw = file_get_contents($filePath);
    $existingKeys = array_keys(parseI18nJs($raw));

    $missing = [];
    foreach ($sourceData as $key => $englishText) {
        if (!in_array($key, $existingKeys, true)) {
            $missing[$key] = $englishText;
        }
    }

    if (!$missing) {
        echo "Up to date: system/{$lang}.js\n";
        continue;
    }

    $deeplLang = $deepLLanguageMap[$lang] ?? strtoupper($lang);
    $translated = [];

    foreach ($missing as $key => $text) {
        $result = translateWithFallback($translator, $text, $sourceLanguage, $deeplLang, $deepLFallbackMap);
        if ($result === null) {
            echo "  [skip] system/{$lang}.js: \"{$key}\" - DeepL could not translate to {$deeplLang}\n";
            continue;
        }
        $translated[$key] = $result;
    }

    if (!$translated) {
        continue;
    }

    file_put_contents($filePath, appendI18nEntries($raw, $translated));
    echo "Updated system/{$lang}.js (" . count($translated) . " new key(s))\n";
}

echo "Done.\n";

// ---- helpers ----

function translateWithFallback(Translator $translator, string $text, string $source, string $target, array $fallbackMap): ?string {
    try {
        return $translator->translateText($text, $source, $target, ['tagHandling' => 'html'])->text;
    } catch (\Throwable $e) {
        error_log("DeepL translation failed for target '{$target}': " . $e->getMessage());
        if (isset($fallbackMap[$target])) {
            try {
                return $translator->translateText($text, $source, $fallbackMap[$target], ['tagHandling' => 'html'])->text;
            } catch (\Throwable $e2) {
                error_log("DeepL fallback to '{$fallbackMap[$target]}' also failed: " . $e2->getMessage());
            }
        }
        return null;
    }
}

/**
 * Parses the flat window.i18nData = { "key": "value", 'key2': 'value2', ... }
 * object literal used by every src/system/<lang>.js file into a PHP assoc
 * array. Keys/values may use either quote style; this is not a general JS
 * parser, just enough for this file's flat string-only shape.
 */
function parseI18nJs(string $content): array {
    $start = strpos($content, '{');
    $end = strrpos($content, '}');
    if ($start === false || $end === false || $end <= $start) {
        return [];
    }
    $inner = substr($content, $start + 1, $end - $start - 1);

    $pattern = '/(["\'])((?:\\\\.|(?!\1).)*)\1\s*:\s*(["\'])((?:\\\\.|(?!\3).)*)\3/su';
    preg_match_all($pattern, $inner, $matches, PREG_SET_ORDER);

    $data = [];
    foreach ($matches as $m) {
        $data[unescapeJsString($m[2])] = unescapeJsString($m[4]);
    }
    return $data;
}

function unescapeJsString(string $s): string {
    return preg_replace_callback('/\\\\(.)/s', function ($m) {
        switch ($m[1]) {
            case 'n': return "\n";
            case 't': return "\t";
            case 'r': return "\r";
            default: return $m[1]; // \" \' \\ \/ -> literal char
        }
    }, $s);
}

/**
 * Inserts new key/value pairs into a window.i18nData object literal right
 * before its closing brace, matching the indentation of the file's existing
 * entries and adding a trailing comma to the previously-last entry if it
 * doesn't already have one.
 */
function appendI18nEntries(string $raw, array $entries): string {
    $closeBracePos = strrpos($raw, '}');
    if ($closeBracePos === false) {
        return $raw;
    }

    $indent = '    ';
    if (preg_match('/\n([ \t]*)["\'][^"\']*["\']\s*:/', $raw, $m)) {
        $indent = $m[1];
    }

    $before = rtrim(substr($raw, 0, $closeBracePos));
    $after = substr($raw, $closeBracePos);

    if ($before !== '' && substr($before, -1) !== ',' && substr($before, -1) !== '{') {
        $before .= ',';
    }

    $lines = [];
    foreach ($entries as $key => $value) {
        $lines[] = $indent . json_encode((string) $key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',';
    }
    // No trailing comma on the last inserted line, to match the file's style
    // right before the closing brace.
    $lines[count($lines) - 1] = rtrim($lines[count($lines) - 1], ',');

    return $before . "\n" . implode("\n", $lines) . "\n" . $after;
}
