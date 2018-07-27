<?php
//index.php
header('Access-Control-Allow-Origin: *');

$current = date('H:i:s \o\n l, F jS Y');
$result  = (object)[
    'message' => 'Hello! It is ' . $current
];

echo json_encode($result);
