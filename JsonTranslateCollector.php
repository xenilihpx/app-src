<?php
use DeepL\Translator;

class JsonCollector {
    // Each of these is keyed by namespace ('lang' or 'shipping') so a single
    // collector instance can save part of its keys under /lang and part under
    // /shipping/localize (see namespaceForKey()).
    private $data = ['lang' => [], 'shipping' => []]; // Pending key => text per namespace
    private $storeTranslateLang = ['lang' => [], 'shipping' => []]; // namespace => targetLanguage => key => text
    private $existingTranslation = ['lang' => [], 'shipping' => []]; // namespace => targetLanguage => decoded json
    private $translatedJson = ['lang' => null, 'shipping' => null]; // namespace => decoded json for $this->targetLanguage
    private $untranslatedItems = []; // Entries DeepL couldn't translate, for the manual-translation dialog
    private $deepLKey;
    private $targetLanguage;
    private $sourceLanguage ="en";
    private $subFolder ="";
    private $pageName="";
    private bool $isShippingFile = false;
    private static array $calls = [];
    private int $progressTotal = 0;
    private int $progressCurrent = 0;
    private bool $progressBarShown = false;

    // Keys with this prefix (e.g. "country_nameUS") are shared, identical
    // content across every checkout page, so they always save under
    // shipping/localize instead of the calling page's own /lang file -
    // regardless of which page's collector instance produced them.
    private const SHIPPING_KEY_PREFIX = "country_name";

    // country_name* keys always live in checkout.json specifically (not each
    // calling page's own funnel name, e.g. checkout_v2/cloud-air-cover), so
    // every page shares the same set of translated country names.
    private const SHIPPING_COUNTRY_NAME_PAGE = "checkout";


    public function __construct($targetLanguage="en",$pageName,$subFolder="") {
        $this->deepLKey = getenv('DEEPL_KEY');
        $this->targetLanguage = $targetLanguage;
        $this->subFolder = $subFolder;
        $this->pageName = $pageName;
        $this->isShippingFile = $this->callerIsInShippingFolder();

        $this->makeFolder(dirname($this->getLocalizedFilePath($targetLanguage, $this->defaultNamespace())));
    }

    private function makeFolder($folderPath){
        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0755, true);
        }
    }

    private function callerIsInShippingFolder(): bool {
        // Frame 0 is this method's caller (__construct, inside this file);
        // frame 1 is the script that instantiated JsonCollector.
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $callerFile = $trace[1]['file'] ?? '';
        return basename(dirname($callerFile)) === 'shipping';
    }

    private function defaultNamespace(): string {
        return $this->isShippingFile ? 'shipping' : 'lang';
    }

    private function namespaceForKey(string $key): string {
        return ($this->isShippingFile || str_starts_with($key, self::SHIPPING_KEY_PREFIX)) ? 'shipping' : 'lang';
    }

    private function getLocalizedFilePath(string $targetLanguage, string $namespace): string {
        if($namespace === 'shipping') {
            // Shipping text must stay colocated inside the src/shipping submodule
            // (src/shipping/localize/<lang>/...), not the main repo's root /lang.
            // Only the shipping form itself (isShippingFile) uses its own page
            // name (checkout-shipping); every other page lands here only via a
            // country_name* key, which always belongs in checkout.json instead
            // of that page's own funnel name.
            $shippingPageName = $this->isShippingFile ? $this->pageName : self::SHIPPING_COUNTRY_NAME_PAGE;
            return BASEPATH.$this->subFolder."/src/shipping/localize/".$targetLanguage."/".$shippingPageName.".json";
        }
        return BASEPATH.$this->subFolder."/lang/".$targetLanguage."/".$this->pageName.".json";
    }

    private function isLocalEnvironment(): bool {
        // SERVER_NAME alone can be spoofed via the Host header on a live/dev
        // server, so also require the request to actually originate from the
        // local loopback interface before treating this as local dev.
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($serverName, ['localhost', '127.0.0.1'], true)
            && in_array($remoteAddr, ['127.0.0.1', '::1'], true);
    }

    public function translate(string $name, string $text, bool $checkDuplicate=true): string {
        $namespace = $this->namespaceForKey($name);
        $this->getTranslatedJson($this->getLocalizedFilePath($this->targetLanguage, $namespace), $namespace);
        //check duplicates only in local
        if($this->isLocalEnvironment()){
            if($checkDuplicate){
                if($this->checkDuplicateName($name)){
                    exit;
                }
            }
        }
        $searchValue=$this->searchTranslateKey($name, $namespace);
        if( $searchValue!=""){
            return $searchValue;
        }else{

            // Trim the text and add with incremental key
            //$translatedText=$this->deepLTranslation(trim($text));
            $this->data[$namespace][$name] = trim($text);
            return trim($text);
        }
    }


    private function deepLTranslation(string $text, string $targetLanguage){
        // Map language codes to DeepL supported codes
        $deepLLanguageMap = [
            'pt-br' => 'pt-BR',    // Portuguese (Brazil)
            'pt-pt' => 'pt-PT',    // Portuguese (Portugal)
            'mx' => 'es',          // Spanish (Mexico) - DeepL has no MX-specific target
        ];

        // Get the correct DeepL language code
        $deeplLang = $deepLLanguageMap[$targetLanguage] ?? $targetLanguage;

        $translator = new Translator($this->deepLKey);
        $translateSettings=[
                    'tagHandling' => 'html',
                    'glossary'    => 'bac73f85-5b50-4681-b770-fa9b624e7165',
                    'ignoreTags' => "nt"
                ];

        $noGlossaryLangs = ['tl','da','pl','hu','cs','it','fi','es','fr','de','nl','sv','no','ro','sk','sl','et','lv','lt','vi','id','ca','ar', 'ro'];
        if(in_array(strtolower($targetLanguage), $noGlossaryLangs)){
            unset($translateSettings['glossary']);
        }
        
        try {
            $result = $translator->translateText(
                $text,
                $this->sourceLanguage,
                $deeplLang,
                $translateSettings
            );
            return $result->text;
        } catch (\Throwable $e) {
             error_log("DeepL translation failed for target language '{$targetLanguage}' ({$deeplLang}): " . $e->getMessage());

            // Retry once without the glossary, in case the pair isn't covered by it.
            if (isset($translateSettings['glossary'])) {
                unset($translateSettings['glossary']);
                try {
                    $result = $translator->translateText(
                        $text,
                        $this->sourceLanguage,
                        $deeplLang,
                        $translateSettings
                    );
                    return $result->text;
                } catch (\Throwable $e2) {
                    error_log("DeepL retry without glossary also failed for target language '{$targetLanguage}' ({$deeplLang}): " . $e2->getMessage());
                    // Fall back to the untranslated source text so one bad language pair
                    // doesn't abort translation/save for every other language.
                    // DeepL doesn't support (or failed on) this target language - fall back
                    // to the English source text and flag it so saveTranslation() can warn
                    // the developer to translate it manually.
                    $this->untranslatedItems[] = [
                        'targetLanguage' => $targetLanguage,
                        'text' => $text,
                        'reason' => $e->getMessage(),
                    ];
                }
            }           
            return $text;
        }
    }

    private function showUntranslatedItemsDialog(): void {
        $lines = array_map(
            fn($item) => "[{$item['targetLanguage']}] {$item['text']}",
            $this->untranslatedItems
        );
        $message = "DeepL could not translate the following (unsupported target language or API error) - please translate manually:\n\n" . implode("\n", $lines);
        echo "<script>if(window.__jsonCollectorProgress){window.__jsonCollectorProgress.hide();}alert(" . json_encode($message) . ");</script>";
        flush();
    }

    // Which target-language folders deepLExec() will actually touch for the
    // current $this->data[$namespace] batch - mirrors the file_exists() branching
    // inside translateDefinedLanguages()/translateDefinedLanguagesNewPage() so the
    // progress bar's total matches the real number of iterations.
    private function getApplicableTargetLanguages(string $namespace): array {
        $directory = BASEPATH.$this->subFolder."/lang";
        $usingExistingFileFlow = file_exists($this->getLocalizedFilePath($this->targetLanguage, $namespace));
        $languages = [];

        if (is_dir($directory)) {
            foreach (scandir($directory) as $targetLanguage) {
                if ($targetLanguage === '.' || $targetLanguage === '..' || $targetLanguage === 'system') {
                    continue;
                }
                $path = $directory . DIRECTORY_SEPARATOR . $targetLanguage;
                if (!is_dir($path)) {
                    continue;
                }
                $filePath = $this->getLocalizedFilePath($targetLanguage, $namespace);
                // Source-language entries are copied verbatim, never sent to
                // DeepL, so they're not part of the slow work being tracked.
                if ($targetLanguage !== $this->sourceLanguage && file_exists($filePath) === $usingExistingFileFlow) {
                    $languages[] = $targetLanguage;
                }
            }
        }

        return $languages;
    }

    // Releases the buffered page output (index.php et al. wrap the whole
    // page in ob_start() so header("Location") still works at the end) so
    // the browser shows the already-rendered page instead of hanging on a
    // blank tab while the DeepL API calls below run synchronously.
    private function startProgressBar(): void {
        // A full lang x namespace DeepL batch can run long enough to hit PHP's
        // default execution time limit mid-translation - disable it the same
        // way JsonTranslateCollector.php does.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ini_set('zlib.output_compression', '0');
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        // Without these two, a reverse proxy (or PHP itself) can hold every
        // flush() below until the whole request finishes, so the progress
        // bar never actually animates - it just appears to hang, then jump
        // straight to "Done." Matches JsonTranslateCollector.php's setup.
        if (function_exists('ob_implicit_flush')) {
            ob_implicit_flush(true);
        }
        if (!headers_sent()) {
            header('X-Accel-Buffering: no');
        }
        $this->progressBarShown = true;
        ?>
        <div id="jsonCollectorProgress" style="position:fixed;left:0;right:0;top:0;z-index:2147483647;background:#111;color:#fff;font:12px/1.4 -apple-system,BlinkMacSystemFont,sans-serif;padding:10px 16px;box-shadow:0 -2px 8px rgba(0,0,0,.3);">
            <div id="jsonCollectorProgressLabel">Translating new text&hellip;</div>
            <div style="margin-top:6px;height:6px;background:#333;border-radius:3px;overflow:hidden;">
                <div id="jsonCollectorProgressBar" style="height:100%;width:0%;background:#4caf50;transition:width .2s ease;"></div>
            </div>
        </div>
        <script>
        window.__jsonCollectorProgress = {
            update: function(pct, text) {
                var bar = document.getElementById('jsonCollectorProgressBar');
                var label = document.getElementById('jsonCollectorProgressLabel');
                if (bar) bar.style.width = pct + '%';
                if (label && text) label.textContent = text;
            },
            hide: function() {
                var el = document.getElementById('jsonCollectorProgress');
                if (el) el.remove();
            }
        };
        </script>
        <?php
        flush();
    }

    private function reportProgress(string $key, string $targetLanguage): void {
        $this->progressCurrent++;
        $percent = $this->progressTotal > 0
            ? (int) min(99, round(($this->progressCurrent / $this->progressTotal) * 100))
            : 99;
        // Same "N% (done/total)" readout as JsonTranslateCollector.php's progress
        // bar, plus the key/language currently being sent to DeepL.
        $label = "In progress: {$percent}% ({$this->progressCurrent}/{$this->progressTotal}) \u{2014} "
            . "Translating \"{$key}\" \u{2192} {$targetLanguage}\u{2026}";
        echo "<script>if(window.__jsonCollectorProgress){window.__jsonCollectorProgress.update("
            . $percent . "," . json_encode($label) . ");}</script>\n";
        flush();
    }

    private function finishProgressBar(): void {
        echo "<script>if(window.__jsonCollectorProgress){window.__jsonCollectorProgress.update(100,'Done translating.');}</script>\n";
        flush();
    }

    private function redirectAfterProgressBar(string $redirectUrl): void {
        echo "<script>"
            . "if(window.__jsonCollectorProgress){window.__jsonCollectorProgress.hide();}"
            . "window.location.href=" . json_encode($redirectUrl) . ";"
            . "</script>";
        flush();
    }

    private function isAjaxRequest(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    // Files like src/shipping/shipping-address-*.php are fetched directly via
    // jQuery .load() (checkout.js's loadDynamicShipFields) and injected into
    // a container on the checkout page, rather than navigated to. For that
    // AJAX case, $_SERVER['REQUEST_URI'] is the fragment's own URL, so
    // redirecting there strands the browser on the bare fragment - fall back
    // to the referring (checkout) page instead.
    private function getRedirectUrl(): string {
        if ($this->isAjaxRequest() && !empty($_SERVER['HTTP_REFERER'])) {
            return $_SERVER['HTTP_REFERER'];
        }
        return $_SERVER['REQUEST_URI'];
    }

    private function checkDuplicateName($name) {

        if (!isset(self::$calls[$name])) {
            self::$calls[$name] = 0;
        }

        self::$calls[$name]++;
        if (self::$calls[$name] > 1) {
            echo "<script>".
                 "alert('Translation key {$name} was assigned more than once! (".self::$calls[$name]." times).It should be unique');".
                 "</script>";

            return true;
        } else {
            return false;
        }
    }

    private function getTranslatedJson($filePath, string $namespace){
        $jsonContents = file_get_contents($filePath);
        if ($jsonContents === false) {
            $this->translatedJson[$namespace] = null;
            return null;
        }
        // Decode JSON to an associative array
        $data = json_decode($jsonContents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->translatedJson[$namespace] = null;
            return null;
        }else{
            $this->translatedJson[$namespace] = $data;
            return $data;
        }
    }

    private function searchTranslateKey(string $searchKey, string $namespace){
        $translatedJson = $this->translatedJson[$namespace];
        if($translatedJson==null){
            return "";
        }
        if (array_key_exists($searchKey, $translatedJson)) {
            return $translatedJson[$searchKey];
        }else {
            return "";
        }
    }

    public function translateDefinedLanguages(string $key, string $value, string $namespace){

        $directory = BASEPATH.$this->subFolder."/lang"; // Replace with your directory path

        if (is_dir($directory)) {
            // Get all files and directories in the specified path
            $items = scandir($directory);

            foreach ($items as $targetLanguage) {
                // Skip the special "." (current directory) and ".." (parent directory) entries
                if ($targetLanguage !== '.' && $targetLanguage !== '..') {
                    $path = $directory . DIRECTORY_SEPARATOR . $targetLanguage;
                    // Check if the item is a directory
                    if (is_dir($path) && $targetLanguage !="system") {

                          $filePath = $this->getLocalizedFilePath($targetLanguage, $namespace);
                          if(file_exists($filePath)){
                                if($targetLanguage!=$this->sourceLanguage) {
                                    $this->existingTranslation[$namespace][$targetLanguage]=$this->getTranslatedJson($filePath, $namespace);
                                    if($this->searchTranslateKey($key, $namespace)==""){
                                        $this->reportProgress($key, $targetLanguage);
                                        $this->storeTranslateLang[$namespace][$targetLanguage][$key]=$this->deepLTranslation($value,$targetLanguage);
                                    }

                                } else {
                                    $this->existingTranslation[$namespace][$targetLanguage]=$this->getTranslatedJson($filePath, $namespace);
                                    if($this->searchTranslateKey($key, $namespace)==""){
                                        $this->storeTranslateLang[$namespace][$targetLanguage][$key]=$value;
                                    }
                                }
                          }

                    }
                }
            }
        }
    }


    public function translateDefinedLanguagesNewPage(string $key, string $value, string $namespace){

        $directory = BASEPATH.$this->subFolder."/lang"; // Replace with your directory path

        if (is_dir($directory)) {
            // Get all files and directories in the specified path
            $items = scandir($directory);

            foreach ($items as $targetLanguage) {
                // Skip the special "." (current directory) and ".." (parent directory) entries
                if ($targetLanguage !== '.' && $targetLanguage !== '..') {
                    $path = $directory . DIRECTORY_SEPARATOR . $targetLanguage;
                    // Check if the item is a directory
                    if (is_dir($path) && $targetLanguage !="system") {
                        $filePath = $this->getLocalizedFilePath($targetLanguage, $namespace);
                        if(!file_exists($filePath)){
                            if($targetLanguage!=$this->sourceLanguage) {
                                $this->reportProgress($key, $targetLanguage);
                                $this->storeTranslateLang[$namespace][$targetLanguage][$key]=$this->deepLTranslation($value,$targetLanguage);
                            }else{
                                $this->storeTranslateLang[$namespace][$targetLanguage][$key]=$value;
                            }
                        }
                    }
                }
            }
        }
    }

    private function deepLExec(){
        $this->progressTotal = 0;
        foreach (['lang', 'shipping'] as $namespace) {
            $this->progressTotal += count($this->data[$namespace]) * count($this->getApplicableTargetLanguages($namespace));
        }

        if ($this->progressTotal > 0) {
            $this->startProgressBar();
        }

        foreach (['lang', 'shipping'] as $namespace) {
            $ownJsonPath = $this->getLocalizedFilePath($this->targetLanguage, $namespace);
            foreach ($this->data[$namespace] as $key => $value) {
                if(file_exists($ownJsonPath)){
                    $this->translateDefinedLanguages($key, $value, $namespace);
                }else{
                    $this->translateDefinedLanguagesNewPage($key, $value, $namespace);
                    //self::$storeTranslateLang[$this->targetLanguage][$key]=$this->deepLTranslation($value,$this->targetLanguage);
                }
            }
        }

        if ($this->progressBarShown) {
            $this->finishProgressBar();
        }
    }

    public function saveTranslation(): bool {

        //don't save if there's nothing pending in either namespace, or if this isn't genuinely local dev
        if((count($this->data['lang']) + count($this->data['shipping'])) === 0 || !$this->isLocalEnvironment()){
            return false;
        }

        $this->deepLExec();

        foreach (['lang', 'shipping'] as $namespace) {
            foreach ($this->storeTranslateLang[$namespace] as $key => $value) {
                $filePath = $this->getLocalizedFilePath($key, $namespace);
                $this->makeFolder(dirname($filePath));
                if($this->existingTranslation[$namespace][$key]!=null){
                     // Existing values must win on conflict - never let a fresh DeepL
                     // result overwrite a property that's already been translated.
                     $merged =array_merge($this->storeTranslateLang[$namespace][$key], $this->existingTranslation[$namespace][$key]);
                     $jsonData = json_encode($merged, JSON_PRETTY_PRINT);

                     if (file_put_contents($filePath, $jsonData) === false) {
                         trigger_error("Failed to write to file: $filePath", E_USER_WARNING);
                     }
                }else{
                    $jsonData = json_encode($this->storeTranslateLang[$namespace][$key], JSON_PRETTY_PRINT);
                    if (file_put_contents($filePath, $jsonData) === false) {
                         trigger_error("Failed to write to file: $filePath", E_USER_WARNING);
                    }
                }
            }
        }

        // Check for JSON encoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON encoding error: " . json_last_error_msg());
            return false;
        }

        if (!empty($this->untranslatedItems)) {
            $this->showUntranslatedItemsDialog();
            // Skip the redirect below - it would navigate the browser away
            // before the alert ever gets a chance to render.
            return true;
        }

        $redirectUrl = $this->getRedirectUrl();

        if ($this->progressBarShown) {
            // The progress bar already flushed the buffered page to the
            // browser, so headers are long gone - redirect from the client
            // instead of via header("Location").
            $this->redirectAfterProgressBar($redirectUrl);
        } else {
            header("Location: $redirectUrl");
        }

        return true;
    }
}
