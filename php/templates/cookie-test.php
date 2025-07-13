<?php
include '../configs/config-r53-dual-distribution.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cookie测试页面 - <?= $main_domain ?></title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f0f2f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #007bff; }
        .cookie-display { background: #e9ecef; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; margin: 10px 0; }
        .status-good { color: #28a745; font-weight: bold; }
        .status-bad { color: #dc3545; font-weight: bold; }
        .test-links { background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .test-links a { display: inline-block; margin: 5px; padding: 10px 15px; background: #ffc107; color: #212529; text-decoration: none; border-radius: 3px; }
        .test-links a:hover { background: #e0a800; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍪 Cookie测试页面</h1>
        <p>此页面用于测试CloudFront Signed Cookie的设置和共享机制</p>

        <div class="test-section">
            <h3>📊 当前Cookie状态</h3>
            <?php
            $cookies_present = [
                'CloudFront-Policy' => isset($_COOKIE['CloudFront-Policy']),
                'CloudFront-Signature' => isset($_COOKIE['CloudFront-Signature']),
                'CloudFront-Key-Pair-Id' => isset($_COOKIE['CloudFront-Key-Pair-Id'])
            ];
            
            $all_cookies_present = array_reduce($cookies_present, function($carry, $item) {
                return $carry && $item;
            }, true);
            ?>
            
            <p>总体状态: 
                <?php if ($all_cookies_present): ?>
                    <span class="status-good">✅ 所有Cookie已正确设置</span>
                <?php else: ?>
                    <span class="status-bad">❌ Cookie设置不完整</span>
                <?php endif; ?>
            </p>

            <div class="cookie-display">
                <strong>Cookie详细状态:</strong><br>
                <?php foreach ($cookies_present as $name => $present): ?>
                    <?= $name ?>: <?= $present ? '<span class="status-good">✅ 存在</span>' : '<span class="status-bad">❌ 缺失</span>' ?><br>
                <?php endforeach; ?>
            </div>

            <?php if ($all_cookies_present): ?>
                <div class="cookie-display">
                    <strong>Cookie值 (前50字符):</strong><br>
                    CloudFront-Policy: <?= substr($_COOKIE['CloudFront-Policy'], 0, 50) ?>...<br>
                    CloudFront-Signature: <?= substr($_COOKIE['CloudFront-Signature'], 0, 50) ?>...<br>
                    CloudFront-Key-Pair-Id: <?= $_COOKIE['CloudFront-Key-Pair-Id'] ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="test-section">
            <h3>🌐 域名信息</h3>
            <div class="cookie-display">
                当前访问域名: <?= $_SERVER['HTTP_HOST'] ?? 'Unknown' ?><br>
                Cookie域名设置: <?= $cookie_domain ?><br>
                应用程序域名: <?= $app_subdomain ?><br>
                私有内容域名: <?= $cdn_subdomain ?>
            </div>
        </div>

        <div class="test-links">
            <h3>🔗 测试链接</h3>
            <p>使用以下链接测试Cookie在不同子域名间的共享:</p>
            
            <a href="https://<?= $app_subdomain ?>/cookie-test.php" target="_blank">
                应用域名测试
            </a>
            
            <a href="https://<?= $app_subdomain ?>/app-r53-main.php" target="_blank">
                生成新Cookie
            </a>
            
            <a href="javascript:location.reload()">
                刷新页面
            </a>
        </div>

        <div class="test-section">
            <h3>🧪 JavaScript Cookie检查</h3>
            <div id="js-cookie-info" class="cookie-display">
                正在检查JavaScript可访问的Cookie...
            </div>
        </div>

        <div class="test-section">
            <h3>📝 测试说明</h3>
            <ol>
                <li><strong>Cookie生成:</strong> 访问 app.<?= $main_domain ?>/app-r53-main.php 生成Cookie</li>
                <li><strong>Cookie验证:</strong> 在此页面检查Cookie是否正确设置</li>
                <li><strong>跨域测试:</strong> 测试Cookie在 app 和 cdn 子域名间的共享</li>
                <li><strong>私有内容访问:</strong> 使用Cookie访问 cdn.<?= $main_domain ?> 的私有内容</li>
            </ol>
        </div>
    </div>

    <script>
        // JavaScript Cookie检查
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        function checkJSCookies() {
            const cookieInfo = document.getElementById('js-cookie-info');
            const cookies = [
                'CloudFront-Policy',
                'CloudFront-Signature', 
                'CloudFront-Key-Pair-Id'
            ];
            
            let html = '<strong>JavaScript可见的Cookie:</strong><br>';
            let allPresent = true;
            
            cookies.forEach(cookieName => {
                const value = getCookie(cookieName);
                if (value) {
                    html += `${cookieName}: <span class="status-good">✅ 存在</span> (${value.substring(0, 30)}...)<br>`;
                } else {
                    html += `${cookieName}: <span class="status-bad">❌ 不存在</span><br>`;
                    allPresent = false;
                }
            });
            
            html += '<br><strong>注意:</strong> CloudFront Cookie通常设置为HttpOnly，JavaScript可能无法访问。';
            cookieInfo.innerHTML = html;
        }

        // 页面加载完成后检查Cookie
        document.addEventListener('DOMContentLoaded', checkJSCookies);
    </script>
</body>
</html>
