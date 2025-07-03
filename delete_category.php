<?php
session_start();
$username = $_SESSION['username'] ?? '';
$nickname = $_SESSION['nickname'] ?? '';
if ($username !== 'soyeonglim' || $nickname !== 'ㅇㅅㅇ') {
    header('Location: index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    if ($category) {
        $categoriesFile = __DIR__ . '/categories.json';
        $categories = [];
        if (file_exists($categoriesFile)) {
            $categories = json_decode(file_get_contents($categoriesFile), true) ?: [];
        }
        $categories = array_values(array_filter($categories, function($cat) use ($category) {
            return $cat !== $category;
        }));
        file_put_contents($categoriesFile, json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
header('Location: index.php');
exit;
