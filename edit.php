<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_GET['idx'])) {
    header('Location: index.php');
    exit;
}

$postsFile = __DIR__ . '/posts.json';
$idx = intval($_GET['idx']);
$nickname = $_SESSION['nickname'] ?? '';

if (!file_exists($postsFile)) {
    echo "<script>alert('글이 존재하지 않습니다.');location.href='index.php';</script>";
    exit;
}

$posts = json_decode(file_get_contents($postsFile), true) ?: [];
if (!isset($posts[$idx])) {
    echo "<script>alert('글이 존재하지 않습니다.');location.href='index.php';</script>";
    exit;
}

$post = $posts[$idx];
$files = isset($post['files']) ? $post['files'] : [];

// 본인 글이 아니면 수정 불가
if ($post['author'] !== $nickname) {
    echo "<script>alert('본인 글만 수정할 수 있습니다.');location.href='index.php';</script>";
    exit;
}

// 글 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title && $content) {
        $posts[$idx]['title'] = $title;
        $posts[$idx]['content'] = $content;
        file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo "<script>alert('수정되었습니다.');location.href='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('제목과 내용을 모두 입력하세요.');history.back();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글 수정</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>글 수정</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required><br>
        <textarea name="content" required style="width:90%;height:100px;"><?php echo htmlspecialchars($post['content']); ?></textarea><br>
        <?php if ($files): ?>
            <div style="margin-bottom:10px;">
                <b>첨부파일:</b><br>
                <?php foreach ($files as $f): ?>
                    <?php if (strpos($f['type'], 'image/') === 0): ?>
                        <div style="margin:8px 0;">
                            <img src="uploads/<?php echo htmlspecialchars($f['saved']); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>" style="max-width:200px;max-height:200px;display:block;">
                            <a href="uploads/<?php echo htmlspecialchars($f['saved']); ?>" download><?php echo htmlspecialchars($f['name']); ?></a>
                        </div>
                    <?php else: ?>
                        <div style="margin:8px 0;">
                            <a href="uploads/<?php echo htmlspecialchars($f['saved']); ?>" download><?php echo htmlspecialchars($f['name']); ?></a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <input type="file" name="upload_files[]" multiple accept="image/*,application/pdf,application/msword,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.*,text/plain"><br>
        <small style="color:#888;">이미지, PDF, 워드, 엑셀, 텍스트 등 파일 업로드 가능</small><br>
        <button type="submit">수정 완료</button>
        <button type="button" onclick="location.href='index.php'">취소</button>
    </form>
</body>
</html>
