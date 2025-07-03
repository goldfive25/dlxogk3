<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');

    if ($username && $password && $nickname) {
        $usersFile = __DIR__ . '/users.json';
        $users = [];
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?: [];
        }
        // 아이디/닉네임 중복 체크
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                echo "<script>alert('이미 존재하는 아이디입니다.');history.back();</script>";
                exit;
            }
            if ($user['nickname'] === $nickname) {
                echo "<script>alert('이미 존재하는 닉네임입니다.');history.back();</script>";
                exit;
            }
        }
        // 비밀번호는 실제 서비스에서는 반드시 암호화해야 함
        $users[] = [
            'username' => $username,
            'password' => $password,
            'nickname' => $nickname
        ];
        file_put_contents($usersFile, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo "<script>alert('회원가입이 완료되었습니다.');location.href='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('모든 항목을 입력하세요.');history.back();</script>";
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
