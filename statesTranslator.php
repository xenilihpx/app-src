<?php
/**
 * One-time/dev batch translator for state & province names shown in the
 * shipping address state dropdown (see common/js/states.js and
 * form_obj.addStatesProvince() in common/js/integrated.js).
 *
 * Mirrors src/JsonTranslateCollector.php's convention: translations are only
 * ever generated here (localhost or CLI) and the resulting JSON is committed,
 * so production never calls DeepL live. Re-running this script is safe and
 * cheap - it only translates entries missing from lang/states/<lang>/states.json,
 * so it's the tool to use after adding a country/state to lang/states/en/states.json
 * or after a new lang/<lang> folder is added.
 *
 * Usage: php common/statesTranslator.php   (from the project root, CLI)
 *     or open http://localhost/melaraapex/common/statesTranslator.php in a browser
 */

$isCli = PHP_SAPI === 'cli';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
if (!$isCli && !in_array($serverName, ['localhost', '127.0.0.1'], true)) {
    http_response_code(403);
    exit('statesTranslator.php only runs on localhost or via CLI.');
}

// One DeepL request per target language easily runs past PHP's default 30s
// max_execution_time when opened in a browser - lift it for this script.
if (function_exists('set_time_limit')) {
    @set_time_limit(0);
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
// KonnektiveApi::loadEnv(), duplicated here since this script has no other
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

// Same key/mapping as src/JsonTranslateCollector.php, kept in sync manually
// since that class only translates one string at a time and this script
// needs batched array translation for the ~1250 state names.
$deepLKey = getenv('DEEPL_KEY');
if (!$deepLKey) {
    exit("DEEPL_KEY is not set (checked environment and {$envFile}).\n");
}
$deepLLanguageMap = [
    'pt-br' => 'pt-BR',
    'pt-pt' => 'pt-PT',
    'mx' => 'es',
];
$sourceLanguage = 'en';
$chunkSize = 50;

$sourcePath = BASEPATH . "/src/states/{$sourceLanguage}/states.json";
if (!file_exists($sourcePath)) {
    exit("Source file not found: {$sourcePath}\n");
}
$sourceStates = json_decode(file_get_contents($sourcePath), true);
if (!is_array($sourceStates)) {
    exit("Could not read source file: {$sourcePath}\n");
}

$langDir = BASEPATH . '/lang';
$statesDir = BASEPATH . '/src/states';
$languages = array_filter(scandir($langDir), function ($dir) use ($langDir, $sourceLanguage) {
    return $dir !== '.' && $dir !== '..' && $dir !== 'system' && $dir !== 'states' && $dir !== $sourceLanguage
        && is_dir($langDir . '/' . $dir);
});

$translator = new Translator($deepLKey);

foreach ($languages as $lang) {
    $targetDir = "{$statesDir}/{$lang}";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $targetFile = "{$targetDir}/states.json";
    $existing = file_exists($targetFile) ? json_decode(file_get_contents($targetFile), true) : [];
    if (!is_array($existing)) {
        $existing = [];
    }
    $deeplLang = $deepLLanguageMap[$lang] ?? $lang;
    $changed = false;

    foreach ($sourceStates as $countryCode => $states) {
        $missing = [];
        foreach ($states as $code => $englishName) {
            if (!isset($existing[$countryCode][$code])) {
                $missing[$code] = $englishName;
            }
        }
        if (!$missing) {
            continue;
        }

        foreach (array_chunk($missing, $chunkSize, true) as $chunk) {
            $codes = array_keys($chunk);
            $texts = array_values($chunk);
            try {
                $results = $translator->translateText($texts, $sourceLanguage, $deeplLang);
            } catch (\Throwable $e) {
                error_log("States translation failed for {$lang}/{$countryCode}: " . $e->getMessage());
                echo "  [skip] {$lang}/{$countryCode}: " . $e->getMessage() . "\n";
                continue 2; // leave this country's remaining chunks for the next run
            }
            foreach ($codes as $i => $code) {
                $existing[$countryCode][$code] = $results[$i]->text;
            }
            $changed = true;
        }
    }

    if ($changed) {
        ksort($existing);
        file_put_contents($targetFile, json_encode($existing, JSON_UNESCAPED_UNICODE));
        echo "Updated {$lang}/states.json\n";
    } else {
        echo "Up to date: {$lang}/states.json\n";
    }
}

echo "Done.\n";
