<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $usersFile = __DIR__ . '/users.json';
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true) ?: [];
        $found = false;
        foreach ($users as $i => $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                $found = true;
                array_splice($users, $i, 1);
                file_put_contents($usersFile, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                // 세션 삭제
                session_unset();
                session_destroy();
                echo "<script>alert('계정이 삭제되었습니다.');location.href='index.php';</script>";
                exit;
            }
        }
    }
    // 실패 시
    header('Location: index.php?delete_account=fail');
    exit;
} else {
    header('Location: index.php');
    exit;
}
