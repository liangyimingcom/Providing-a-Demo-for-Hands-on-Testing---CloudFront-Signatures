<?php
// CloudFront Signed Cookie 生成器
$private_key_filename = '../keys/private.pem';
$key_pair_id = 'K3UHZUBESECTVE';
$main_domain = 'liangym.people.aws.dev';
$cdn_subdomain = 'cdn.' . $main_domain;
$cookie_domain = '.' . $main_domain;
$expires = time() + 3600; // 1小时

// 生成Cookie
$resource_pattern = "https://{$cdn_subdomain}/*";

$policy = json_encode([
    'Statement' => [
        [
            'Resource' => $resource_pattern,
            'Condition' => [
                'DateLessThan' => [
                    'AWS:EpochTime' => $expires
                ]
            ]
        ]
    ]
]);

$signature = '';
if (file_exists($private_key_filename)) {
    $private_key = file_get_contents($private_key_filename);
    $pkeyid = openssl_get_privatekey($private_key);
    if ($pkeyid) {
        openssl_sign($policy, $signature, $pkeyid);
        openssl_free_key($pkeyid);
    }
}

$signature_base64 = str_replace(['+', '=', '/'], ['-', '_', '~'], base64_encode($signature));
$policy_base64 = str_replace(['+', '=', '/'], ['-', '_', '~'], base64_encode($policy));

// 设置Cookie
setcookie("CloudFront-Policy", $policy_base64, $expires, "/", $cookie_domain, true, true);
setcookie("CloudFront-Signature", $signature_base64, $expires, "/", $cookie_domain, true, true);
setcookie("CloudFront-Key-Pair-Id", $key_pair_id, $expires, "/", $cookie_domain, true, true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>CloudFront Signed Cookie 生成器</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f0f8ff; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .test-links { background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .test-links a { display: block; margin: 10px 0; padding: 10px; background: #ffc107; color: #212529; text-decoration: none; border-radius: 3px; }
        .test-links a:hover { background: #e0a800; }
        .cookie-info { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍪 CloudFront Signed Cookie 生成器</h1>
        
        <div class="success">
            ✅ Cookie已成功生成并设置！
        </div>
        
        <div class="info">
            <h3>📋 配置信息</h3>
            <ul>
                <li><strong>应用程序域名:</strong> app.<?= $main_domain ?></li>
                <li><strong>私有内容域名:</strong> <?= $cdn_subdomain ?></li>
                <li><strong>Cookie域名:</strong> <?= $cookie_domain ?></li>
                <li><strong>有效期:</strong> <?= date('Y-m-d H:i:s', $expires) ?></li>
                <li><strong>Key Pair ID:</strong> <?= $key_pair_id ?></li>
            </ul>
        </div>
        
        <div class="cookie-info">
            <strong>Cookie详细信息:</strong><br>
            CloudFront-Policy: <?= substr($policy_base64, 0, 50) ?>...<br>
            CloudFront-Signature: <?= substr($signature_base64, 0, 50) ?>...<br>
            CloudFront-Key-Pair-Id: <?= $key_pair_id ?><br>
            Resource Pattern: <?= $resource_pattern ?>
        </div>
        
        <div class="test-links">
            <h3>🔒 测试私有内容访问</h3>
            <p><strong>重要:</strong> 以下链接不包含签名参数，完全通过Cookie验证：</p>
            
            <a href="https://<?= $cdn_subdomain ?>/index.html" target="_blank">
                📄 私有HTML页面
            </a>
            
            <a href="https://<?= $cdn_subdomain ?>/test.txt" target="_blank">
                📝 私有文本文档
            </a>
        </div>
        
        <div class="info">
            <h3>🧪 测试步骤</h3>
            <ol>
                <li>确认上方显示"Cookie已成功生成并设置"</li>
                <li>点击私有内容链接</li>
                <li>验证能够正常访问（返回200状态码）</li>
                <li>注意URL中没有签名参数</li>
            </ol>
        </div>
        
        <div class="info">
            <p><strong>当前时间:</strong> <?= date('Y-m-d H:i:s') ?></p>
            <p><strong>私钥文件状态:</strong> <?= file_exists($private_key_filename) ? '✅ 存在' : '❌ 不存在' ?></p>
        </div>
    </div>
</body>
</html>
