<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>alert('로그인 후 이용 가능합니다.');location.href='index.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postIdx = isset($_POST['post_idx']) ? intval($_POST['post_idx']) : -1;
    $postsFile = __DIR__ . '/posts.json';
    $username = $_SESSION['username'];
    if ($postIdx >= 0 && file_exists($postsFile)) {
        $posts = json_decode(file_get_contents($postsFile), true) ?: [];
        if (isset($posts[$postIdx])) {
            if (!isset($posts[$postIdx]['liked_users']) || !is_array($posts[$postIdx]['liked_users'])) {
                $posts[$postIdx]['liked_users'] = [];
            }
            // 이미 좋아요를 눴으면 취소, 아니면 추가 (토글)
            $likedUsers = &$posts[$postIdx]['liked_users'];
            if (in_array($username, $likedUsers)) {
                // 좋아요 취소
                $likedUsers = array_values(array_diff($likedUsers, [$username]));
                if (isset($posts[$postIdx]['like_count']) && $posts[$postIdx]['like_count'] > 0) {
                    $posts[$postIdx]['like_count']--;
                }
            } else {
                // 좋아요 추가
                $likedUsers[] = $username;
                if (!isset($posts[$postIdx]['like_count'])) {
                    $posts[$postIdx]['like_count'] = 0;
                }
                $posts[$postIdx]['like_count']++;
            }
            file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }
    header('Location: index.php');
    exit;
} else {
    header('Location: index.php');
    exit;
}
