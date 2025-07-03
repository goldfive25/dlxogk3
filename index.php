<?php
if ($_SERVER['SERVER_PORT'] != '8080') {
    $host = $_SERVER['HTTP_HOST'];
    $host = preg_replace('/:\d+$/', '', $host); // 포트 제거
    $uri = $_SERVER['REQUEST_URI'];
    header("Location: http://{$host}:8080{$uri}");
    echo "<meta http-equiv='refresh' content='0;url=http://{$host}:8080{$uri}'>";
    exit;
}
session_start();
$loggedIn = isset($_SESSION['username']);
$nickname = $_SESSION['nickname'] ?? '';
$username = $_SESSION['username'] ?? '';
$isAdmin = ($username === 'soyeonglim' && $nickname === 'ㅇㅅㅇ');

// 분야 목록 불러오기
$categoriesFile = __DIR__ . '/categories.json';
$categories = ['전체'];
if (file_exists($categoriesFile)) {
    $cats = json_decode(file_get_contents($categoriesFile), true);
    if ($cats && is_array($cats)) {
        sort($cats, SORT_LOCALE_STRING); // 가나다순 정렬
        $categories = array_merge($categories, $cats);
    }
}
// 선택된 분야
$currentCategory = $_GET['category'] ?? '전체';

// 게시글 불러오기
$postsFile = __DIR__ . '/posts.json';
$posts = [];
if (file_exists($postsFile)) {
    $allPosts = json_decode(file_get_contents($postsFile), true);
    if ($allPosts && is_array($allPosts)) {
        // 분야별 필터링
        if ($currentCategory === '전체') {
            $posts = $allPosts;
        } else {
            foreach ($allPosts as $p) {
                if (($p['category'] ?? '전체') === $currentCategory) {
                    $posts[] = $p;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>기본 웹사이트</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* ...existing code... */
    .like-btn.liked {
        background: #ffb3b3;
        color: #fff;
        border: 1px solid #ffb3b3;
    }
    .like-btn {
        background: #eee;
        color: #333;
        border: 1px solid #ccc;
        padding: 4px 10px;
        border-radius: 4px;
        cursor: pointer;
    }
    /* 글쓰기/수정 모달 전체화면 스타일 */
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0; top: 0; width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background: #fff;
        /* 전체 화면 크기 */
        width: 100vw;
        height: 100vh;
        min-width: unset;
        min-height: unset;
        max-width: unset;
        max-height: unset;
        border-radius: 0;
        box-shadow: none;
        padding: 0;
        margin: 0;
        position: relative;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .modal-content .close {
        position: absolute;
        right: 24px;
        top: 18px;
        font-size: 32px;
        font-weight: bold;
        color: #888;
        cursor: pointer;
    }
    .modal-content input[type="text"],
    .modal-content input[type="password"],
    .modal-content textarea {
        width: 90%;
        margin-bottom: 12px;
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ccc;
        font-size: 16px;
    }
    .modal-content button[type="submit"] {
        padding: 8px 24px;
        border-radius: 4px;
        border: none;
        background: #4a90e2;
        color: #fff;
        font-size: 16px;
        cursor: pointer;
        margin-top: 8px;
    }
    .category-bar {
        margin: 20px 0 10px 0;
        text-align: center;
    }
    .category-bar form {
        display: inline-block;
        margin-left: 10px;
    }
    .category-btn {
        margin: 0 4px;
        padding: 6px 18px;
        border-radius: 20px;
        border: 1px solid #4a90e2;
        background: #fff;
        color: #4a90e2;
        cursor: pointer;
        font-size: 15px;
        transition: background 0.2s, color 0.2s;
    }
    .category-btn.selected, .category-btn:hover {
        background: #4a90e2;
        color: #fff;
    }
    .category-delete-btn {
        background: #f66;
        color: #fff;
        border: 1px solid #f66;
        border-radius: 10px;
        margin-left: 4px;
        padding: 2px 8px;
        font-size: 13px;
        cursor: pointer;
    }
    .hamburger {
        position: absolute;
        left: 20px;
        top: 24px;
        width: 36px;
        height: 36px;
        background: none;
        border: none;
        cursor: pointer;
        z-index: 1100;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .hamburger span {
        display: block;
        width: 28px;
        height: 4px;
        margin: 4px 0;
        background: #4a90e2;
        border-radius: 2px;
        transition: 0.3s;
    }
    .category-bar {
        display: none;
        position: fixed;
        left: 0; top: 0; bottom: 0;
        width: 220px;
        background: #fff;
        border-right: 1px solid #e0e0e0;
        box-shadow: 2px 0 12px rgba(0,0,0,0.08);
        z-index: 1050;
        padding: 32px 0 0 0;
        flex-direction: column;
        align-items: flex-start;
        min-height: 100vh;
        justify-content: flex-start;
        box-sizing: border-box;
        padding-bottom: 70px;
    }
    .category-bar.open {
        display: flex;
    }
    .category-toggle-btn {
        width: 80%;
        margin: 32px 10% 8px 10%; /* 기존 0 → 32px로 변경 */
        padding: 10px 0;
        border-radius: 20px;
        border: 1px solid #4a90e2;
        background: #fff;
        color: #4a90e2;
        font-size: 17px;
        cursor: pointer;
        display: block;
        text-align: center;
        font-weight: bold;
        transition: background 0.2s, color 0.2s;
    }
    .category-toggle-btn.active {
        background: #4a90e2;
        color: #fff;
    }
    .category-list {
        width: 100%;
        display: none;
        flex-direction: column;
        align-items: flex-start;
    }
    .category-list.open {
        display: flex;
    }
    .category-btn {
        margin: 8px 0 0 24px;
        padding: 8px 20px;
        border-radius: 20px;
        border: 1px solid #4a90e2;
        background: #fff;
        color: #4a90e2;
        cursor: pointer;
        font-size: 16px;
        text-align: left;
        width: 160px;
        transition: background 0.2s, color 0.2s;
    }
    .category-btn.selected, .category-btn:hover {
        background: #4a90e2;
        color: #fff;
    }
    .category-delete-btn {
        margin-left: 8px;
        margin-top: 8px;
        width: 40px;
        padding: 4px 0;
        font-size: 13px;
        display: inline-block;
        vertical-align: middle;
    }
    .category-bar form {
        margin: 16px 0 0 24px;
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .category-bar input[type="text"] {
        width: 90px;
        margin-right: 6px;
        padding: 4px 6px;
    }
    .category-login-btn,
    .category-logout-btn,
    .category-delete-account-btn {
        width: 80%;
        margin: 0 10% 40px 10%;
        padding: 12px 0;
        border-radius: 20px;
        border: 1px solid #4a90e2;
        background: #4a90e2;
        color: #fff;
        font-size: 17px;
        cursor: pointer;
        display: block;
        transition: background 0.2s, color 0.2s;
        position: absolute;
        left: 0;
        right: 0;
    }
    .category-login-btn { bottom: 0; }
    .category-delete-account-btn { bottom: 56px; background: #f66; border-color: #f66; }
    .category-logout-btn { bottom: 112px; background: #888; border-color: #888; }
    @media (max-width: 600px) {
        .category-bar { width: 80vw; min-width: 0; }
        .category-btn { width: 60vw; }
        .category-login-btn, .category-logout-btn, .category-delete-account-btn { width: 90%; margin-left: 5%; }
    }
    .write-btn-custom {
        display: inline-block;
        margin-bottom: 18px;
        padding: 14px 40px;
        font-size: 20px;
        font-weight: bold;
        border-radius: 30px;
        border: none;
        background: linear-gradient(90deg, #4a90e2 60%, #357ab8 100%);
        color: #fff;
        box-shadow: 0 2px 12px rgba(74,144,226,0.12);
        cursor: pointer;
        transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
    }
    .write-btn-custom:hover {
        background: linear-gradient(90deg, #357ab8 60%, #4a90e2 100%);
        box-shadow: 0 4px 18px rgba(74,144,226,0.18);
        transform: translateY(-2px) scale(1.04);
    }
    .draw-btn-custom {
        display: inline-block;
        margin-bottom: 18px;
        margin-left: 12px;
        padding: 14px 32px;
        font-size: 19px;
        font-weight: bold;
        border-radius: 30px;
        border: none;
        background: linear-gradient(90deg, #f9d423 60%, #ff4e50 100%);
        color: #fff;
        box-shadow: 0 2px 12px rgba(255, 78, 80, 0.10);
        cursor: pointer;
        transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
        vertical-align: middle;
    }
    .draw-btn-custom:hover {
        background: linear-gradient(90deg, #ff4e50 60%, #f9d423 100%);
        box-shadow: 0 4px 18px rgba(255, 78, 80, 0.18);
        transform: translateY(-2px) scale(1.04);
    }
    /* 그림판 모달 */
    #drawModal .modal-content {
        width: 700px;
        height: 600px;
        max-width: 98vw;
        max-height: 98vh;
        padding: 24px 12px 12px 12px;
        border-radius: 16px;
        box-shadow: 0 4px 32px rgba(0,0,0,0.13);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
    }
    .draw-modal-flex {
        display: flex;
        flex-direction: row;
        width: 100%;
        height: 480px;
        gap: 32px;
        align-items: flex-start;
        justify-content: center;
    }
    #drawCanvas {
        border: 1.5px solid #aaa;
        border-radius: 8px;
        background: #fff;
        cursor: crosshair;
        margin-bottom: 0;
        width: 440px;
        height: 440px;
        max-width: 95vw;
        max-height: 70vw;
        box-sizing: border-box;
    }
    .draw-controls {
        margin-bottom: 12px;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .draw-controls input[type="color"] {
        width: 32px;
        height: 32px;
        border: none;
        background: none;
        padding: 0;
    }
    .draw-controls input[type="range"] {
        width: 80px;
    }
    .draw-controls button {
        padding: 6px 16px;
        border-radius: 8px;
        border: 1px solid #aaa;
        background: #eee;
        color: #333;
        font-size: 15px;
        cursor: pointer;
        margin-left: 4px;
    }
    .draw-controls button:hover {
        background: #4a90e2;
        color: #fff;
        border-color: #4a90e2;
    }
    .draw-form-side {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        width: 200px;
        gap: 10px;
        margin-top: 0;
    }
    .draw-form-side input[type="text"] {
        width: 100%;
        padding: 8px;
        font-size: 16px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }
    .draw-form-side textarea {
        width: 100%;
        height: 80px;
        padding: 8px;
        font-size: 15px;
        border-radius: 6px;
        border: 1px solid #ccc;
        resize: none;
    }
    .draw-form-side button[type="submit"] {
        margin-top: 10px;
        background: #f9d423;
        color: #fff;
        border: none;
        padding: 10px 0;
        border-radius: 20px;
        font-size: 17px;
        font-weight: bold;
        cursor: pointer;
        width: 100%;
        transition: background 0.2s;
    }
    .draw-form-side button[type="submit"]:hover {
        background: #ff4e50;
    }
    @media (max-width: 900px) {
        #drawModal .modal-content { width: 98vw; height: auto; }
        .draw-modal-flex { flex-direction: column; align-items: center; height: auto; gap: 10px;}
        #drawCanvas { width: 90vw; height: 60vw; min-width: 220px; min-height: 180px; }
        .draw-form-side { width: 90vw; }
    }
    </style>
</head>
<body>
    <header>
        <button class="hamburger" id="hamburgerBtn" aria-label="분야 열기">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <h1 style="margin-left:60px;">환영합니다!</h1>
        <div class="top-buttons">
            <?php if ($loggedIn): ?>
                <span style="margin-right:10px;"><?php echo htmlspecialchars($nickname); ?>님 환영합니다!</span>
                <!-- 로그아웃/계정삭제 버튼 제거 -->
            <?php endif; ?>
            <!-- 로그인 버튼도 제거 -->
        </div>
    </header>
    <nav class="category-bar" id="categoryBar">
        <button id="categoryToggleBtn" class="category-toggle-btn">분야</button>
        <div id="categoryList" class="category-list">
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo urlencode($cat); ?>">
                    <button class="category-btn<?php if ($cat === $currentCategory) echo ' selected'; ?>">
                        <?php echo htmlspecialchars($cat); ?>
                    </button>
                </a>
                <?php if ($isAdmin && $cat !== '전체'): ?>
                    <form method="post" action="delete_category.php" style="display:inline;">
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($cat); ?>">
                        <button type="submit" class="category-delete-btn" onclick="return confirm('정말 이 분야를 삭제하시겠습니까?');">삭제</button>
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
            <!-- 분야 추가 폼 -->
            <form method="post" action="add_category.php">
                <input type="text" name="category" placeholder="분야 추가" required>
                <button type="submit">추가</button>
            </form>
        </div>
        <?php if (!$loggedIn): ?>
            <button id="sideLoginBtn" class="category-login-btn">로그인</button>
        <?php else: ?>
            <button id="sideDeleteAccountBtn" class="category-delete-account-btn">계정 삭제</button>
            <button id="sideLogoutBtn" class="category-logout-btn">로그아웃</button>
        <?php endif; ?>
    </nav>
    <main style="margin-left:0;">
        <h2><?php echo htmlspecialchars($currentCategory); ?> 게시판</h2>
        <button id="writeBtn" class="write-btn-custom">글쓰기</button>
        <?php if ($currentCategory === '미술'): ?>
            <button id="drawBtn" class="draw-btn-custom">그림 그리기</button>
        <?php endif; ?>
        <ul class="post-list">
        <?php
            // $posts는 분야별로 필터링된 배열이므로, 전체 게시글에서 실제 인덱스를 찾아야 함
            $allPosts = [];
            if (file_exists($postsFile)) {
                $allPosts = json_decode(file_get_contents($postsFile), true) ?: [];
            }
            if ($posts && is_array($posts)) {
                foreach ($posts as $idx => $post) {
                    $title = htmlspecialchars($post['title']);
                    $author = htmlspecialchars($post['author']);
                    $content = htmlspecialchars($post['content'] ?? '');
                    $likeCount = isset($post['like_count']) ? (int)$post['like_count'] : 0;
                    $likedUsers = isset($post['liked_users']) && is_array($post['liked_users']) ? $post['liked_users'] : [];
                    $userLiked = $loggedIn && in_array($_SESSION['username'], $likedUsers);
                    $files = isset($post['files']) ? $post['files'] : [];
                    $category = htmlspecialchars($post['category'] ?? '전체');
                    // 실제 전체 게시글에서의 인덱스 찾기
                    $realIdx = array_search($post, $allPosts, true);
                    echo "<li>";
                    echo "<a href='#' class='view-post' data-idx='{$idx}' data-title=\"{$title}\" data-content=\"{$content}\" data-author=\"{$author}\">{$title}</a> - {$author}";
                    // 좋아요 버튼
                    echo " <form method='post' action='like.php' style='display:inline;'>";
                    echo "<input type='hidden' name='post_idx' value='{$realIdx}'>";
                    $likeBtnClass = $userLiked ? "like-btn liked" : "like-btn";
                    $likeBtnText = $userLiked ? "좋아요 취소" : "좋아요";
                    echo "<button type='submit' class='{$likeBtnClass}'>{$likeBtnText}</button>";
                    echo "</form>";
                    // 본인 글이거나 관리자면 수정/삭제 버튼
                    if (($loggedIn && $post['author'] === $nickname) || $isAdmin) {
                        if ($loggedIn && $post['author'] === $nickname) {
                            echo " <button onclick=\"location.href='edit.php?idx={$realIdx}&category=" . urlencode($category) . "'\">수정</button>";
                        }
                        echo " <form method='post' action='delete.php' style='display:inline;' onsubmit=\"return confirm('정말 삭제하시겠습니까?');\">";
                        echo "<input type='hidden' name='idx' value='{$realIdx}'>";
                        echo "<button type='submit' style='background:#f66;color:#fff;border:1px solid #f66;border-radius:4px;padding:4px 10px;cursor:pointer;'>삭제</button>";
                        echo "</form>";
                    }
                    echo " <span style='color:#888;'>♥ {$likeCount}</span>";
                    echo "</li>";
                }
            } else {
                echo "<li>등록된 글이 없습니다.</li>";
            }
        ?>
        </ul>
    </main>
    <!-- 로그인 모달 -->
    <div id="loginModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeLogin">&times;</span>
            <h2>로그인</h2>
            <form method="post" action="login.php" id="loginForm">
                <input type="text" name="username" placeholder="아이디" required><br>
                <input type="password" name="password" placeholder="비밀번호" required><br>
                <button type="submit">로그인</button>
            </form>
            <p id="loginErrorMsg" style="color:#f66; margin:8px 0 0 0; display:none;"></p>
            <p>아직 회원이 아니신가요? <button id="showRegister">회원가입</button></p>
        </div>
    </div>
    <!-- 회원가입 모달 -->
    <div id="registerModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeRegister">&times;</span>
            <h2>회원가입</h2>
            <form method="post" action="register.php">
                <input type="text" name="username" placeholder="아이디" required><br>
                <input type="password" name="password" placeholder="비밀번호" required><br>
                <input type="text" name="nickname" placeholder="닉네임" required><br>
                <button type="submit">회원가입</button>
            </form>
        </div>
    </div>
    <!-- 글쓰기 모달 -->
    <div id="writeModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeWrite">&times;</span>
            <h2>글쓰기</h2>
            <form method="post" action="write.php" enctype="multipart/form-data">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($currentCategory); ?>">
                <input type="text" name="title" placeholder="제목" required><br>
                <textarea name="content" placeholder="글 내용" required style="width:90%;height:100px;"></textarea><br>
                <input type="file" name="upload_files[]" multiple accept="image/*,application/pdf,application/msword,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.*,text/plain"><br>
                <small style="color:#888;">이미지, PDF, 워드, 엑셀, 텍스트 등 파일 업로드 가능</small><br>
                <button type="submit">업로드</button>
            </form>
        </div>
    </div>
    <!-- 글 내용 보기 모달 -->
    <div id="viewPostModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeViewPost">&times;</span>
            <h2 id="viewPostTitle"></h2>
            <div style="color:#888; margin-bottom:10px;" id="viewPostAuthor"></div>
            <div id="viewPostContent" style="white-space:pre-wrap; text-align:left;"></div>
            <div id="viewPostFiles" style="margin-top:16px;"></div>
        </div>
    </div>
    <!-- 계정 삭제 모달 -->
    <div id="deleteAccountModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeDeleteAccount">&times;</span>
            <h2>계정 삭제</h2>
            <form method="post" action="delete_account.php" id="deleteAccountForm">
                <input type="text" name="username" placeholder="아이디" required><br>
                <input type="password" name="password" placeholder="비밀번호" required><br>
                <button type="submit" style="background:#f66;">계정 삭제</button>
            </form>
            <p id="deleteAccountErrorMsg" style="color:#f66; margin:8px 0 0 0; display:none;"></p>
        </div>
    </div>
    <!-- 그림판 모달 -->
    <div id="drawModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeDraw">&times;</span>
            <h2>그림 그리기</h2>
            <div class="draw-controls">
                <label>색상 <input type="color" id="drawColor" value="#222"></label>
                <label>굵기 <input type="range" id="drawLineWidth" min="1" max="20" value="3"></label>
                <button type="button" id="drawClearBtn">지우기</button>
            </div>
            <div class="draw-modal-flex">
                <canvas id="drawCanvas" width="440" height="440"></canvas>
                <form id="drawUploadForm" class="draw-form-side" method="post" action="upload_drawing.php" enctype="multipart/form-data">
                    <input type="hidden" name="drawing_data" id="drawingDataInput">
                    <input type="hidden" name="category" value="미술">
                    <input type="text" name="title" placeholder="제목" required>
                    <textarea name="content" placeholder="설명(선택)"></textarea>
                    <button type="submit">업로드</button>
                </form>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
    <script>
    // 햄버거 버튼으로 분야 메뉴 열고 닫기
    var hamburgerBtn = document.getElementById('hamburgerBtn');
    var categoryBar = document.getElementById('categoryBar');
    if (hamburgerBtn && categoryBar) {
        hamburgerBtn.onclick = function() {
            categoryBar.classList.toggle('open');
        };
        // 바깥 클릭 시 닫기
        document.addEventListener('click', function(e) {
            if (!categoryBar.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                categoryBar.classList.remove('open');
                categoryList.classList.remove('open');
                categoryToggleBtn.classList.remove('active');
            }
        });
    }
    // 분야 토글 버튼 제어
    var categoryToggleBtn = document.getElementById('categoryToggleBtn');
    var categoryList = document.getElementById('categoryList');
    if (categoryToggleBtn && categoryList) {
        categoryToggleBtn.onclick = function(e) {
            e.stopPropagation();
            categoryList.classList.toggle('open');
            categoryToggleBtn.classList.toggle('active');
        };
    }
    // 사이드 로그인/로그아웃/계정삭제 버튼(분야 메뉴 하단) 제어
    var sideLoginBtn = document.getElementById('sideLoginBtn');
    if (sideLoginBtn) {
        sideLoginBtn.onclick = function() {
            document.getElementById('loginModal').style.display = 'block';
            categoryBar.classList.remove('open');
            categoryList.classList.remove('open');
            categoryToggleBtn.classList.remove('active');
        };
    }
    var sideLogoutBtn = document.getElementById('sideLogoutBtn');
    if (sideLogoutBtn) {
        sideLogoutBtn.onclick = function() {
            location.href = 'logout.php';
        };
    }
    var sideDeleteAccountBtn = document.getElementById('sideDeleteAccountBtn');
    if (sideDeleteAccountBtn) {
        sideDeleteAccountBtn.onclick = function() {
            document.getElementById('deleteAccountModal').style.display = 'flex';
            categoryBar.classList.remove('open');
            categoryList.classList.remove('open');
            categoryToggleBtn.classList.remove('active');
            var msg = document.getElementById('deleteAccountErrorMsg');
            if (msg) msg.style.display = 'none';
        };
    }
    // 모달 제어 스크립트
    var loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.onclick = function() {
            document.getElementById('loginModal').style.display = 'block';
        };
    }
    var closeLogin = document.getElementById('closeLogin');
    if (closeLogin) {
        closeLogin.onclick = function() {
            document.getElementById('loginModal').style.display = 'none';
        };
    }
    var showRegister = document.getElementById('showRegister');
    if (showRegister) {
        showRegister.onclick = function() {
            document.getElementById('loginModal').style.display = 'none';
            document.getElementById('registerModal').style.display = 'block';
        };
    }
    var closeRegister = document.getElementById('closeRegister');
    if (closeRegister) {
        closeRegister.onclick = function() {
            document.getElementById('registerModal').style.display = 'none';
        };
    }
    // 글쓰기 모달 제어
    var writeBtn = document.getElementById('writeBtn');
    if (writeBtn) {
        writeBtn.onclick = function() {
            document.getElementById('writeModal').style.display = 'block';
        };
    }
    var closeWrite = document.getElementById('closeWrite');
    if (closeWrite) {
        closeWrite.onclick = function() {
            document.getElementById('writeModal').style.display = 'none';
        };
    }
    // 게시글 데이터 posts를 JS로 전달
    var postsData = <?php echo json_encode($posts, JSON_UNESCAPED_UNICODE); ?>;

    // 글 내용 보기 모달 제어
    document.querySelectorAll('.view-post').forEach(function(el) {
        el.onclick = function(e) {
            e.preventDefault();
            var idx = this.dataset.idx;
            var post = postsData[idx];
            document.getElementById('viewPostTitle').textContent = post.title;
            document.getElementById('viewPostAuthor').textContent = '작성자: ' + post.author;
            document.getElementById('viewPostContent').textContent = post.content;
            // 파일 표시
            var files = post.files || [];
            var filesDiv = document.getElementById('viewPostFiles');
            filesDiv.innerHTML = '';
            if (files && files.length > 0) {
                filesDiv.innerHTML = '<b>첨부파일:</b><br>';
                files.forEach(function(f) {
                    var isImg = f.type && f.type.startsWith('image/');
                    var url = 'uploads/' + f.saved;
                    if (isImg) {
                        filesDiv.innerHTML += '<div style="margin:8px 0;"><img src="'+url+'" alt="'+f.name+'" style="max-width:200px;max-height:200px;display:block;"><a href="'+url+'" download>'+f.name+'</a></div>';
                    } else {
                        filesDiv.innerHTML += '<div style="margin:8px 0;"><a href="'+url+'" download>'+f.name+'</a></div>';
                    }
                });
            }
            document.getElementById('viewPostModal').style.display = 'flex';
        };
    });
    var closeViewPost = document.getElementById('closeViewPost');
    if (closeViewPost) {
        closeViewPost.onclick = function() {
            document.getElementById('viewPostModal').style.display = 'none';
        };
    }
    // 계정 삭제 모달 제어
    var deleteAccountBtn = document.getElementById('deleteAccountBtn');
    if (deleteAccountBtn) {
        deleteAccountBtn.onclick = function() {
            document.getElementById('deleteAccountModal').style.display = 'flex';
            var msg = document.getElementById('deleteAccountErrorMsg');
            if (msg) msg.style.display = 'none';
        };
    }
    var closeDeleteAccount = document.getElementById('closeDeleteAccount');
    if (closeDeleteAccount) {
        closeDeleteAccount.onclick = function() {
            document.getElementById('deleteAccountModal').style.display = 'none';
        };
    }
    // 그림판 모달 제어
    var drawBtn = document.getElementById('drawBtn');
    var drawModal = document.getElementById('drawModal');
    var closeDraw = document.getElementById('closeDraw');
    if (drawBtn && drawModal) {
        drawBtn.onclick = function() {
            drawModal.style.display = 'flex';
        };
    }
    if (closeDraw && drawModal) {
        closeDraw.onclick = function() {
            drawModal.style.display = 'none';
        };
    }
    // 그림판 기능
    var canvas = document.getElementById('drawCanvas');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        var drawing = false;
        var colorInput = document.getElementById('drawColor');
        var lineWidthInput = document.getElementById('drawLineWidth');
        var clearBtn = document.getElementById('drawClearBtn');
        var lastX, lastY;

        function getPos(e) {
            var rect = canvas.getBoundingClientRect();
            if (e.touches) {
                return {
                    x: e.touches[0].clientX - rect.left,
                    y: e.touches[0].clientY - rect.top
                };
            } else {
                return {
                    x: e.offsetX,
                    y: e.offsetY
                };
            }
        }

        function startDraw(e) {
            drawing = true;
            var pos = getPos(e);
            lastX = pos.x;
            lastY = pos.y;
        }
        function draw(e) {
            if (!drawing) return;
            e.preventDefault();
            var pos = getPos(e);
            ctx.strokeStyle = colorInput.value;
            ctx.lineWidth = lineWidthInput.value;
            ctx.lineCap = "round";
            ctx.lineJoin = "round";
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            lastX = pos.x;
            lastY = pos.y;
        }
        function endDraw() {
            drawing = false;
        }
        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', endDraw);
        canvas.addEventListener('mouseleave', endDraw);
        // 모바일 터치 지원
        canvas.addEventListener('touchstart', function(e){ startDraw(e); }, {passive:false});
        canvas.addEventListener('touchmove', function(e){ draw(e); }, {passive:false});
        canvas.addEventListener('touchend', endDraw);

        clearBtn.onclick = function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        };
    }
    // 그림 업로드 시 base64 데이터 전송
    var drawUploadForm = document.getElementById('drawUploadForm');
    if (drawUploadForm) {
        drawUploadForm.onsubmit = function(e) {
            var canvas = document.getElementById('drawCanvas');
            var dataUrl = canvas.toDataURL('image/png');
            document.getElementById('drawingDataInput').value = dataUrl;
            // 업로드 시 모달 닫기 (성공 시 새로고침)
            setTimeout(function() {
                document.getElementById('drawModal').style.display = 'none';
            }, 100);
        };
    }
    // 계정 삭제 실패 시 에러 메시지 표시 (쿼리스트링으로 전달)
    window.addEventListener('DOMContentLoaded', function() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('delete_account') === 'fail') {
            document.getElementById('deleteAccountModal').style.display = 'flex';
            var msg = document.getElementById('deleteAccountErrorMsg');
            if (msg) {
                msg.textContent = '아이디 또는 비밀번호가 올바르지 않습니다.';
                msg.style.display = 'block';
            }
        }
    });

    // 로그인 실패 시 에러 메시지 표시 (쿼리스트링으로 전달)
    window.addEventListener('DOMContentLoaded', function() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('login') === 'fail') {
            document.getElementById('loginModal').style.display = 'flex';
            var msg = document.getElementById('loginErrorMsg');
            if (msg) {
                msg.textContent = '아이디 또는 비밀번호가 올바르지 않습니다.';
                msg.style.display = 'block';
            }
        }
    });

    // 로그인 버튼 클릭 시 에러 메시지 숨기기
    var loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        loginBtn.onclick = function() {
            document.getElementById('loginModal').style.display = 'block';
            var msg = document.getElementById('loginErrorMsg');
            if (msg) msg.style.display = 'none';
        };
    }
    </script>
</body>
</html>
</html>
