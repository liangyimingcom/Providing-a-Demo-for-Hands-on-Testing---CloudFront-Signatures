<?php

///-----------------------------------------------------
// 双CloudFront分发点配置 - 使用R53域名
///-----------------------------------------------------

// CloudFront签名配置
$private_key_filename = '../keys/private.pem';
$key_pair_id = 'K3UHZUBESECTVE';

// 主域名
$main_domain = 'liangym.people.aws.dev';

// 分发点1: 应用程序 (ELB后端) - 不开启CloudFront签名
$app_subdomain = 'app.' . $main_domain;

// 分发点2: 私有内容 (S3后端) - 开启CloudFront签名
$cdn_subdomain = 'cdn.' . $main_domain;

// Cookie域名 - 设置为主域名，这样两个子域名都能共享Cookie
$cookie_domain = '.' . $main_domain;

// Cookie有效期
$expires = time() + 3600; // 1小时

///-----------------------------------------------------
// 生成针对私有内容分发点的Cookie
///-----------------------------------------------------

function generateCDNSignedCookies($cdn_subdomain, $private_key_file, $key_pair_id, $expires, $cookie_domain) {
    // 资源模式 - 允许访问CDN分发点的所有内容
    $resource_pattern = "https://{$cdn_subdomain}/*";
    
    // 创建策略
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
    
    // 生成签名
    $signature = '';
    $private_key = file_get_contents($private_key_file);
    $pkeyid = openssl_get_privatekey($private_key);
    openssl_sign($policy, $signature, $pkeyid);
    openssl_free_key($pkeyid);
    
    $signature_base64 = str_replace(['+', '=', '/'], ['-', '_', '~'], base64_encode($signature));
    $policy_base64 = str_replace(['+', '=', '/'], ['-', '_', '~'], base64_encode($policy));
    
    // 设置Cookie到主域名，这样app和cdn子域名都能访问
    setcookie("CloudFront-Policy", $policy_base64, $expires, "/", $cookie_domain, true, true);
    setcookie("CloudFront-Signature", $signature_base64, $expires, "/", $cookie_domain, true, true);
    setcookie("CloudFront-Key-Pair-Id", $key_pair_id, $expires, "/", $cookie_domain, true, true);
    
    return [
        'policy' => $policy_base64,
        'signature' => $signature_base64,
        'key_pair_id' => $key_pair_id,
        'expires' => $expires,
        'resource_pattern' => $resource_pattern
    ];
}

///-----------------------------------------------------
// 调试信息函数
///-----------------------------------------------------

function getCookieDebugInfo() {
    return [
        'policy' => $_COOKIE['CloudFront-Policy'] ?? 'Not Set',
        'signature' => $_COOKIE['CloudFront-Signature'] ?? 'Not Set',
        'key_pair_id' => $_COOKIE['CloudFront-Key-Pair-Id'] ?? 'Not Set'
    ];
}

///-----------------------------------------------------
?>
