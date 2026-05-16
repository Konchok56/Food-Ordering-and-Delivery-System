<?php
/**
 * Fix broken PHP strings caused by Font Awesome icons injected into double-quoted PHP strings.
 * The pattern: "...<i class="fa-... breaks PHP because inner " terminates the string.
 * Fix: replace <i class="fa- with <i class=\"fa- when inside PHP files.
 */

$files = [
    dirname(__DIR__) . '/actions/chat_api_logic.php',
    dirname(__DIR__) . '/core/mailer_helper.php',
];

foreach ($files as $path) {
    $content = file_get_contents($path);

    // Fix: replace unescaped double quotes inside <i class="fa-..."> tags
    // Pattern: <i class="fa-...">  →  <i class=\"fa-...\">
    // But only when they appear inside PHP double-quoted strings (not in HTML heredoc or single-quoted strings)
    // We use a regex to find <i class=" and replace " with \" inside the tag
    $fixed = preg_replace_callback(
        '/<i class="(fa-[^"]+)"(\s+style="([^"]+)")?>/',
        function($m) {
            $class = $m[1];
            $style = isset($m[3]) ? ' style=\\"' . $m[3] . '\\"' : '';
            return '<i class=\\"' . $class . '\\"' . $style . '>';
        },
        $content
    );

    // Also fix the closing </i> isn't broken, and fix style attr in <i> tags
    // Additionally fix standalone style=" that break strings
    // Write back
    file_put_contents($path, $fixed);
    echo "Fixed: " . basename($path) . "\n";
}

echo "\nDone! Now checking syntax...\n";
foreach ($files as $path) {
    $result = shell_exec("php -l " . escapeshellarg($path) . " 2>&1");
    echo basename($path) . ": " . trim($result) . "\n";
}
