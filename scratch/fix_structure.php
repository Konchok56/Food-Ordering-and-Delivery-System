<?php
$root = '/Applications/XAMPP/xamppfiles/htdocs/food';
$sub  = $root . '/swiftbite_php_starter';

if (!is_dir($sub)) {
    die("Subfolder not found.\n");
}

function copy_recursive($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copy_recursive($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

echo "Copying files from $sub to $root...\n";
copy_recursive($sub, $root);

echo "Done. You can now delete the subfolder and commit.\n";
