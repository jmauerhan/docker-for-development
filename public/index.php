<?php
//index.php
header('Access-Control-Allow-Origin: *');

$current = date('H:i:s \o\n l, F jS Y');
$result  = (object)[
    'message' => 'Hello! It is ' . $current
];

$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];
$db   = $_ENV['DB_DATABASE'];
$host = $_ENV['DB_HOST'];
$dsn  = "pgsql:host={$host} user={$user} password={$pass} dbname={$db}";
$pdo  = new PDO($dsn);

$users = [];
$sql   = "SELECT * FROM users";
$row   = $pdo->query($sql);
if ($row !== false) {
    $users = $row->fetchAll(PDO::FETCH_COLUMN);
}

$result->users = $users;

echo json_encode($result);
