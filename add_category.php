<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    if ($category) {
        $categoriesFile = __DIR__ . '/categories.json';
        $categories = [];
        if (file_exists($categoriesFile)) {
            $categories = json_decode(file_get_contents($categoriesFile), true) ?: [];
        }
        if (!in_array($category, $categories)) {
            $categories[] = $category;
            file_put_contents($categoriesFile, json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }
}
header('Location: index.php?category=' . urlencode($category));
exit;
