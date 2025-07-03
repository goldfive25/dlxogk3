<?php
session_start();
$loggedIn = isset($_SESSION['username']);
$username = $_SESSION['username'] ?? '';
$nickname = $_SESSION['nickname'] ?? '';
$isAdmin = ($username === 'soyeonglim' && $nickname === 'ㅇㅅㅇ');

if (
    !isset($_POST['idx']) ||
    (!$isAdmin && !$loggedIn)
) {
    header('Location: index.php');
    exit;
}

$postsFile = __DIR__ . '/posts.json';
$idx = intval($_POST['idx']);

if (!file_exists($postsFile)) {
    header('Location: index.php');
    exit;
}

$posts = json_decode(file_get_contents($postsFile), true) ?: [];
if (!isset($posts[$idx])) {
    header('Location: index.php');
    exit;
}

// 본인 글이거나 관리자면 삭제 가능
if ($isAdmin || ($loggedIn && $posts[$idx]['author'] === $nickname)) {
    array_splice($posts, $idx, 1);
    file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo "<script>alert('삭제되었습니다.');location.href='index.php';</script>";
    exit;
} else {
    echo "<script>alert('본인 글만 삭제할 수 있습니다.');location.href='index.php';</script>";
    exit;
}
