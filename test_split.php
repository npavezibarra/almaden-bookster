<?php
define('ALMADEN_TYPST_TESTING', true);
require_once 'includes/pdf-typst/page-templates/page-template-word-flow.php';
$text = "Hola mundo. Esto es una prueba.";
$res = almaden_bookster_typst_page_template_split_body_at_word($text, 3);
var_dump($res);
