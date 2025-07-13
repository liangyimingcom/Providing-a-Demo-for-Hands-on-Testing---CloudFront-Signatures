<?php
include '../configs/config-r53-dual-distribution.php';

// 自动生成并设置Cookie
$cookies = generateCDNSignedCookies($cdn_subdomain, $private_key_filename, $key_pair_id, $expires, $cookie_domain);
?>
<!DOCTYPE html>
<html>
<head>
    <title>应用程序主页 - <?= $main_domain ?></title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .info-box { background: #e3f2fd; padding: 20px; border-left: 4px solid #2196f3; margin: 20px 0; border-radius: 5px; }
        .success-box { background: #e8f5e8; padding: 20px; border-left: 4px solid #4caf50; margin: 20px 0; border-radius: 5px; }
        .private-links { background: #fff3e0; padding: 20px; border-radius: 5px; margin: 20px 0; border: 1px solid #ff9800; }
        .private-links a { display: block; margin: 10px 0; padding: 15px; background: #ff9800; color: white; text-decoration: none; border-radius: 5px; transition: background 0.3s; }
        .private-links a:hover { background: #f57c00; }
        .cookie-info { background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; overflow-x: auto; }
        .architecture { background: #f3e5f5; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .domain-info { background: #e1f5fe; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .test-steps { background: #fff8e1; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .emoji { font-size: 1.2em; margin-right: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><span class="emoji">🚀</span>CloudFront双分发点演示</h1>
            <h2>基于 <?= $main_domain ?> 域名</h2>
        </div>
        
        <div class="architecture">
            <h3><span class="emoji">🏗️</span>架构说明</h3>
            <div class="domain-info">
                <strong>分发点1 (应用程序)</strong>: <code>https://<?= $app_subdomain ?></code><br>
                <small>• 源站: ELB (应用服务器)</small><br>
                <small>• 签名: 不开启CloudFront签名</small><br>
                <small>• 功能: 生成Cookie并提供应用界面</small>
            </div>
            <div class="domain-info">
                <strong>分发点2 (私有内容)</strong>: <code>https://<?= $cdn_subdomain ?></code><br>
                <small>• 源站: S3 (私有内容存储)</small><br>
                <small>• 签名: 开启CloudFront签名</small><br>
                <small>• 功能: 通过Cookie验证提供私有内容</small>
            </div>
            <div class="domain-info">
                <strong>Cookie域名</strong>: <code><?= $cookie_domain ?></code><br>
                <small>• 两个子域名共享Cookie</small><br>
                <small>• 有效期: <?= date('Y-m-d H:i:s', $expires) ?></small>
            </div>
        </div>

        <div class="success-box">
            <h3><span class="emoji">✅</span>Cookie已自动设置</h3>
            <p>当您访问此页面时，系统已自动为您生成并设置了访问私有内容的Cookie。</p>
            <p><strong>Cookie详细信息:</strong></p>
            <div class="cookie-info">
                <strong>CloudFront-Policy:</strong><br><?= substr($cookies['policy'], 0, 80) ?>...<br><br>
                <strong>CloudFront-Signature:</strong><br><?= substr($cookies['signature'], 0, 80) ?>...<br><br>
                <strong>CloudFront-Key-Pair-Id:</strong><br><?= $cookies['key_pair_id'] ?><br><br>
                <strong>Resource Pattern:</strong><br><?= $cookies['resource_pattern'] ?><br><br>
                <strong>Expires:</strong><br><?= date('Y-m-d H:i:s', $cookies['expires']) ?>
            </div>
        </div>

        <div class="private-links">
            <h3><span class="emoji">🔒</span>私有内容访问测试</h3>
            <p><strong>重要:</strong> 以下链接指向私有内容分发点，<strong>URL中不包含任何签名参数</strong>，完全通过Cookie进行验证：</p>
            
            <a href="https://<?= $cdn_subdomain ?>/index.html" target="_blank">
                <span class="emoji">📄</span>私有页面: https://<?= $cdn_subdomain ?>/index.html
            </a>
            
            <a href="https://<?= $cdn_subdomain ?>/test.txt" target="_blank">
                <span class="emoji">📝</span>私有文档: https://<?= $cdn_subdomain ?>/test.txt
            </a>
            
            <a href="https://<?= $cdn_subdomain ?>/images/sample.jpg" target="_blank">
                <span class="emoji">🖼️</span>私有图片: https://<?= $cdn_subdomain ?>/images/sample.jpg
            </a>
            
            <a href="https://<?= $cdn_subdomain ?>/data/report.pdf" target="_blank">
                <span class="emoji">📊</span>私有报告: https://<?= $cdn_subdomain ?>/data/report.pdf
            </a>
        </div>

        <div class="test-steps">
            <h3><span class="emoji">🧪</span>测试步骤</h3>
            <ol>
                <li><strong>检查Cookie设置:</strong>
                    <ul>
                        <li>打开浏览器开发工具 (F12)</li>
                        <li>转到 Application → Cookies → https://<?= $app_subdomain ?></li>
                        <li>确认看到 CloudFront-Policy, CloudFront-Signature, CloudFront-Key-Pair-Id</li>
                    </ul>
                </li>
                <li><strong>测试私有内容访问:</strong>
                    <ul>
                        <li>点击上面的私有内容链接</li>
                        <li>观察是否能正常访问 (应该返回200状态码)</li>
                        <li>注意URL地址栏中没有签名参数</li>
                    </ul>
                </li>
                <li><strong>验证Cookie共享:</strong>
                    <ul>
                        <li>在私有内容页面检查Cookie</li>
                        <li>确认Cookie域名为 <?= $cookie_domain ?></li>
                    </ul>
                </li>
            </ol>
        </div>

        <div class="info-box">
            <h3><span class="emoji">🔍</span>成功验证标志</h3>
            <ul>
                <li>✅ Cookie正确设置到 <code><?= $cookie_domain ?></code> 域名</li>
                <li>✅ 点击私有内容链接能正常访问 (200状态码)</li>
                <li>✅ 私有内容URL不包含签名参数</li>
                <li>✅ 两个子域名能共享Cookie</li>
                <li>✅ 没有403 Forbidden错误</li>
            </ul>
            
            <h4>如果遇到问题:</h4>
            <ul>
                <li>❌ 403错误: 检查Cookie是否正确设置和传递</li>
                <li>❌ Cookie不存在: 检查域名配置和浏览器安全设置</li>
                <li>❌ 签名错误: 检查私钥和Key Pair ID配置</li>
            </ul>
        </div>

        <div class="info-box">
            <h3><span class="emoji">⚙️</span>当前Cookie状态</h3>
            <?php $debug_cookies = getCookieDebugInfo(); ?>
            <div class="cookie-info">
                <strong>当前浏览器Cookie状态:</strong><br>
                Policy: <?= $debug_cookies['policy'] === 'Not Set' ? '❌ 未设置' : '✅ 已设置' ?><br>
                Signature: <?= $debug_cookies['signature'] === 'Not Set' ? '❌ 未设置' : '✅ 已设置' ?><br>
                Key-Pair-Id: <?= $debug_cookies['key_pair_id'] === 'Not Set' ? '❌ 未设置' : '✅ 已设置' ?>
            </div>
        </div>
    </div>
</body>
</html>
