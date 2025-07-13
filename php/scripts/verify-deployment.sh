#!/bin/bash

# CloudFront双分发点部署验证脚本

set -e

# 配置变量
MAIN_DOMAIN="liangym.people.aws.dev"
APP_SUBDOMAIN="app.${MAIN_DOMAIN}"
CDN_SUBDOMAIN="cdn.${MAIN_DOMAIN}"

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_step() {
    echo -e "${BLUE}🔍 $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

echo "🔍 开始验证CloudFront双分发点部署..."
echo ""

# 1. DNS解析检查
print_step "检查DNS解析..."

if nslookup "$APP_SUBDOMAIN" > /dev/null 2>&1; then
    print_success "应用程序域名DNS解析正常: $APP_SUBDOMAIN"
else
    print_error "应用程序域名DNS解析失败: $APP_SUBDOMAIN"
fi

if nslookup "$CDN_SUBDOMAIN" > /dev/null 2>&1; then
    print_success "私有内容域名DNS解析正常: $CDN_SUBDOMAIN"
else
    print_error "私有内容域名DNS解析失败: $CDN_SUBDOMAIN"
fi

echo ""

# 2. HTTPS连接检查
print_step "检查HTTPS连接..."

if curl -s -I "https://$APP_SUBDOMAIN" | grep -q "HTTP/"; then
    STATUS=$(curl -s -I "https://$APP_SUBDOMAIN" | head -n1)
    print_success "应用程序HTTPS连接正常: $STATUS"
else
    print_error "应用程序HTTPS连接失败"
fi

if curl -s -I "https://$CDN_SUBDOMAIN" | grep -q "HTTP/"; then
    STATUS=$(curl -s -I "https://$CDN_SUBDOMAIN" | head -n1)
    print_success "私有内容HTTPS连接正常: $STATUS"
else
    print_error "私有内容HTTPS连接失败"
fi

echo ""

# 3. CloudFront分发点状态检查
print_step "检查CloudFront分发点状态..."

APP_DISTRIBUTIONS=$(aws cloudfront list-distributions \
    --query "DistributionList.Items[?contains(Aliases.Items, '$APP_SUBDOMAIN')].[Id,Status]" \
    --output text 2>/dev/null || echo "")

if [ -n "$APP_DISTRIBUTIONS" ]; then
    echo "$APP_DISTRIBUTIONS" | while read -r ID STATUS; do
        if [ "$STATUS" = "Deployed" ]; then
            print_success "应用程序分发点已部署: $ID"
        else
            print_warning "应用程序分发点状态: $STATUS ($ID)"
        fi
    done
else
    print_error "未找到应用程序分发点"
fi

CDN_DISTRIBUTIONS=$(aws cloudfront list-distributions \
    --query "DistributionList.Items[?contains(Aliases.Items, '$CDN_SUBDOMAIN')].[Id,Status]" \
    --output text 2>/dev/null || echo "")

if [ -n "$CDN_DISTRIBUTIONS" ]; then
    echo "$CDN_DISTRIBUTIONS" | while read -r ID STATUS; do
        if [ "$STATUS" = "Deployed" ]; then
            print_success "私有内容分发点已部署: $ID"
        else
            print_warning "私有内容分发点状态: $STATUS ($ID)"
        fi
    done
else
    print_error "未找到私有内容分发点"
fi

echo ""

# 4. Route 53记录检查
print_step "检查Route 53记录..."

HOSTED_ZONE_ID=$(aws route53 list-hosted-zones-by-name \
    --dns-name "$MAIN_DOMAIN" \
    --query "HostedZones[0].Id" \
    --output text 2>/dev/null | sed 's|/hostedzone/||' || echo "")

if [ -n "$HOSTED_ZONE_ID" ] && [ "$HOSTED_ZONE_ID" != "None" ]; then
    print_success "找到Hosted Zone: $HOSTED_ZONE_ID"
    
    # 检查A记录
    APP_RECORD=$(aws route53 list-resource-record-sets \
        --hosted-zone-id "$HOSTED_ZONE_ID" \
        --query "ResourceRecordSets[?Name=='$APP_SUBDOMAIN.' && Type=='A'].AliasTarget.DNSName" \
        --output text 2>/dev/null || echo "")
    
    if [ -n "$APP_RECORD" ] && [ "$APP_RECORD" != "None" ]; then
        print_success "应用程序A记录存在: $APP_RECORD"
    else
        print_error "应用程序A记录不存在"
    fi
    
    CDN_RECORD=$(aws route53 list-resource-record-sets \
        --hosted-zone-id "$HOSTED_ZONE_ID" \
        --query "ResourceRecordSets[?Name=='$CDN_SUBDOMAIN.' && Type=='A'].AliasTarget.DNSName" \
        --output text 2>/dev/null || echo "")
    
    if [ -n "$CDN_RECORD" ] && [ "$CDN_RECORD" != "None" ]; then
        print_success "私有内容A记录存在: $CDN_RECORD"
    else
        print_error "私有内容A记录不存在"
    fi
else
    print_error "未找到Hosted Zone for $MAIN_DOMAIN"
fi

echo ""

# 5. 应用程序功能检查
print_step "检查应用程序功能..."

if curl -s "https://$APP_SUBDOMAIN/app-r53-main.php" | grep -q "CloudFront双分发点演示"; then
    print_success "应用程序主页正常加载"
else
    print_warning "应用程序主页可能有问题"
fi

if curl -s "https://$APP_SUBDOMAIN/cookie-test.php" | grep -q "Cookie测试页面"; then
    print_success "Cookie测试页面正常加载"
else
    print_warning "Cookie测试页面可能有问题"
fi

echo ""

# 6. 生成验证报告
print_step "生成验证报告..."

cat > verification-report.txt << EOF
🔍 CloudFront双分发点部署验证报告
生成时间: $(date)

📋 验证项目:
✓ DNS解析检查
✓ HTTPS连接检查  
✓ CloudFront分发点状态检查
✓ Route 53记录检查
✓ 应用程序功能检查

🌐 测试URL:
- 应用程序主页: https://$APP_SUBDOMAIN/app-r53-main.php
- Cookie测试页面: https://$APP_SUBDOMAIN/cookie-test.php
- 私有内容测试: https://$CDN_SUBDOMAIN/

📝 下一步测试:
1. 在浏览器中访问应用程序主页
2. 检查Cookie是否正确设置
3. 点击私有内容链接测试访问
4. 验证URL中没有签名参数

⚠️  注意事项:
- 如果CloudFront分发点状态不是"Deployed"，请等待部署完成
- DNS记录可能需要时间传播
- 首次访问可能需要等待缓存预热
EOF

print_success "验证报告已保存到 verification-report.txt"

echo ""
echo "🎉 验证完成！"
echo ""
echo "📋 快速测试链接:"
echo "• 应用程序: https://$APP_SUBDOMAIN/app-r53-main.php"
echo "• Cookie测试: https://$APP_SUBDOMAIN/cookie-test.php"
echo "• 私有内容: https://$CDN_SUBDOMAIN/"
echo ""
echo "📖 详细报告请查看 verification-report.txt"
