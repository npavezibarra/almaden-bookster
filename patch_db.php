<?php
$file = 'almaden-bookster.php';
$content = file_get_contents($file);

$search = "'padding_left' => 'float DEFAULT 0.0 NOT NULL',";
$replace = "'margin_left_odd' => 'float DEFAULT 2.0 NOT NULL',
		'margin_right_odd' => 'float DEFAULT 2.0 NOT NULL',
		'margin_left_even' => 'float DEFAULT 2.0 NOT NULL',
		'margin_right_even' => 'float DEFAULT 2.0 NOT NULL',
		'padding_left' => 'float DEFAULT 0.0 NOT NULL',";

if (strpos($content, "'margin_left_odd'") === false) {
    $content = str_replace($search, $replace, $content);
    file_put_contents($file, $content);
    echo "Patched db schema migration!";
} else {
    echo "Already patched.";
}
