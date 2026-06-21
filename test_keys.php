<?php
$ajax_content = file_get_contents('includes/ajax/ajax-settings.php');
preg_match("/\\\$data\s*=\s*array\s*\((.*?)\);/s", $ajax_content, $matches);
$lines = explode("\n", $matches[1]);
$keys = [];
foreach ($lines as $line) {
    if (preg_match("/'([a-zA-Z0-9_]+)'\s*=>/", $line, $m)) {
        $keys[] = $m[1];
    }
}

$db_content = file_get_contents('almaden-bookster.php');
$missing = [];
foreach ($keys as $key) {
    if ($key === 'book_id') continue;
    if (strpos($db_content, " $key ") === false && strpos($db_content, "'$key'") === false) {
        $missing[] = $key;
    }
}
echo "Missing columns in schema:\n";
print_r($missing);
