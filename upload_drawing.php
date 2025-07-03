<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_POST['drawing_data'])) {
    header('Location: index.php');
    exit;
}

$title = trim(isset($_POST['title']) ? $_POST['title'] : '');
$content = trim(isset($_POST['content']) ? $_POST['content'] : '');
$category = '미술';
$author = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : $_SESSION['username'];
$postsFile = __DIR__ . '/posts.json';

// 그림 데이터 저장
$drawingData = $_POST['drawing_data'];
if (preg_match('/^data:image\/png;base64,/', $drawingData)) {
    $drawingData = substr($drawingData, strpos($drawingData, ',') + 1);
    $drawingData = base64_decode($drawingData);
    $filename = 'drawing_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.png';
    $savePath = __DIR__ . '/uploads/' . $filename;
    file_put_contents($savePath, $drawingData);
    $files = array(
        array(
            'name' => $filename,
            'saved' => $filename,
            'type' => 'image/png'
        )
    );
} else {
    $files = array();
}

// 게시글 저장
$posts = array();
if (file_exists($postsFile)) {
    $posts = json_decode(file_get_contents($postsFile), true);
    if (!is_array($posts)) $posts = array();
}
$newPost = array(
    'title' => $title,
    'content' => $content,
    'author' => $author,
    'category' => $category,
    'files' => $files,
    'like_count' => 0,
    'liked_users' => array()
);
$posts[] = $newPost;
file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "<script>alert('그림이 업로드되었습니다.');location.href='index.php?category=" . urlencode($category) . "';</script>";
exit;
header("Location: index.php?category=미술");
$posts[] = $newPostAll;

file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
header("Location: index.php?category=미술");
exit;
