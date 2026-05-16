<?php
$projectRoot = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getRealPath();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) continue;
    if (str_contains($path, DIRECTORY_SEPARATOR . 'scratch' . DIRECTORY_SEPARATOR)) continue;

    $content = file_get_contents($path);
    
    // Fix unescaped double quotes in <i> tags within double-quoted PHP strings
    // This is a bit tricky with regex, but we'll try to find common patterns
    $fixed = preg_replace_callback(
        '/<i class=\"(fa-[^\"]+)\"(\s+style=\"([^\"]+)\")?>/',
        function($m) {
            // If it's already escaped, don't double escape
            // Wait, preg_replace_callback might be overkill if we just want to ensure all " inside <i> are escaped
            // Actually, let's look for $reply .= "...<i class="..."..."
            return $m[0]; // Placeholder for more complex logic
        },
        $content
    );

    // Let's use a simpler approach: if a file has a syntax error, we fix it.
    // Actually, I'll just run a batch syntax check first.
}

$filesToCheck = [
    $projectRoot . '/actions/chat_api_logic.php',
    $projectRoot . '/core/mailer_helper.php',
];

foreach ($filesToCheck as $path) {
    if (!file_exists($path)) continue;
    $content = file_get_contents($path);
    // Escape " to \" inside <i ...> tags
    $fixed = preg_replace_callback(
        '/<i class=\"([^\"]+)\"([^>]*)>/',
        function($m) {
            $class = $m[1];
            $rest = $m[2];
            $restFixed = str_replace('"', '\"', $rest);
            return "<i class=\\\"$class\\\"$restFixed>";
        },
        $content
    );
    file_put_contents($path, $fixed);
}

echo "String fix complete.\n";
