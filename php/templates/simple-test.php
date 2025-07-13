<?php
// 简化的CloudFront Signed Cookie测试
$main_domain = 'liangym.people.aws.dev';
$app_subdomain = 'app.' . $main_domain;
$cdn_subdomain = 'cdn.' . $main_domain;
$cookie_domain = '.' . $main_domain;

echo "<h1>CloudFront双分发点测试</h1>";
echo "<p>应用程序域名: $app_subdomain</p>";
echo "<p>私有内容域名: $cdn_subdomain</p>";
echo "<p>Cookie域名: $cookie_domain</p>";
echo "<p>当前时间: " . date('Y-m-d H:i:s') . "</p>";

// 测试链接
echo "<h2>测试链接</h2>";
echo "<a href='https://$cdn_subdomain/index.html' target='_blank'>私有内容测试</a><br>";
echo "<a href='https://$cdn_subdomain/test.txt' target='_blank'>私有文档测试</a>";
?>
