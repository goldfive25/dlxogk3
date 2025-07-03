<?php
session_start();
// 로그인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $usersFile = __DIR__ . '/users.json';
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true) ?: [];
        foreach ($users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                // 로그인 성공 (세션 등 처리 가능)
                $_SESSION['username'] = $username;
                $_SESSION['nickname'] = $user['nickname'];
                header('Location: index.php');
                exit;
            }
        }
    }
    // 로그인 실패: 쿼리스트링으로 실패 정보 전달
    header('Location: index.php?login=fail');
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인 - dlxogk</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <a href="index.php" class="logo">dlxogk</a>
    </header>
    <main>
        <h1>로그인</h1>
        <form method="post">
            <input type="text" name="username" placeholder="아이디" required><br>
            <input type="password" name="password" placeholder="비밀번호" required><br>
            <button type="submit">로그인</button>
        </form>
        <div class="error" style="color:red;">
            <?php
            if (isset($_GET['login']) && $_GET['login'] === 'fail') {
                echo '아이디 또는 비밀번호가 올바르지 않습니다.';
            }
            ?>
        </div>
    </main>
</body>
</html>