<?php
session_start();
if (!isset($_SESSION['nickname'])) {
    echo "<script>alert('로그인 후 이용 가능합니다.');location.href='index.php';</script>";
    exit;
}

// 글 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = $_SESSION['nickname'];
    $category = trim($_POST['category'] ?? '전체');

    // 파일 업로드 처리
    $uploadedFiles = [];
    if (!empty($_FILES['upload_files']) && is_array($_FILES['upload_files']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        foreach ($_FILES['upload_files']['name'] as $i => $name) {
            if ($_FILES['upload_files']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['upload_files']['tmp_name'][$i];
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $saveName = uniqid('file_', true) . '.' . $ext;
                $savePath = $uploadDir . $saveName;
                if (move_uploaded_file($tmpName, $savePath)) {
                    $uploadedFiles[] = [
                        'name' => $name,
                        'saved' => $saveName,
                        'type' => $_FILES['upload_files']['type'][$i]
                    ];
                }
            }
        }
    }

    if ($title && $content) {
        $postsFile = __DIR__ . '/posts.json';
        $posts = [];
        if (file_exists($postsFile)) {
            $posts = json_decode(file_get_contents($postsFile), true) ?: [];
        }
        $posts[] = [
            'title' => $title,
            'content' => $content,
            'author' => $author,
            'category' => $category,
            'like_count' => 0,
            'liked_users' => [],
            'files' => $uploadedFiles
        ];
        file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo "<script>alert('글이 등록되었습니다.');location.href='index.php?category=" . urlencode($category) . "';</script>";
        exit;
    } else {
        echo "<script>alert('제목과 내용을 모두 입력하세요.');history.back();</script>";
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글쓰기 - dlxogk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <a href="index.php" class="logo">dlxogk</a>
    </header>
    <main>
        <h1>글쓰기</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="제목" required><br>
            <textarea name="content" placeholder="내용" required></textarea><br>
            <input type="text" name="category" placeholder="분야 (예: 전체, 뉴스, 스포츠)" value="전체"><br>
            <input type="file" name="upload_files[]" multiple><br>
            <button type="submit">등록</button>
        </form>
    </main>
</body>
</html>