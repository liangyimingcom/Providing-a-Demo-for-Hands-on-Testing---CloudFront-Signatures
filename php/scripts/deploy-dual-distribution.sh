#!/bin/bash

# CloudFront双分发点部署脚本
# 使用域名: liangym.people.aws.dev

set -e

echo "🚀 开始部署CloudFront双分发点架构..."

# 配置变量
MAIN_DOMAIN="liangym.people.aws.dev"
APP_SUBDOMAIN="app.${MAIN_DOMAIN}"
CDN_SUBDOMAIN="cdn.${MAIN_DOMAIN}"
REGION="eu-central-1"

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_step() {
    echo -e "${BLUE}📋 $1${NC}"
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

# 检查必要的工具
print_step "检查必要工具..."
if ! command -v aws &> /dev/null; then
    print_error "AWS CLI 未安装"
    exit 1
fi

if ! command -v jq &> /dev/null; then
    print_error "jq 未安装"
    exit 1
fi

print_success "工具检查完成"

# 获取Route 53 Hosted Zone ID
print_step "获取Route 53 Hosted Zone ID..."
HOSTED_ZONE_ID=$(aws route53 list-hosted-zones-by-name \
    --dns-name "${MAIN_DOMAIN}" \
    --query "HostedZones[0].Id" \
    --output text \
    --region us-east-1 | sed 's|/hostedzone/||')

if [ "$HOSTED_ZONE_ID" = "None" ] || [ -z "$HOSTED_ZONE_ID" ]; then
    print_error "未找到域名 ${MAIN_DOMAIN} 的 Hosted Zone"
    exit 1
fi

print_success "找到 Hosted Zone ID: $HOSTED_ZONE_ID"

# 创建应用程序分发点
print_step "创建应用程序分发点 (${APP_SUBDOMAIN})..."
APP_DISTRIBUTION_ID=$(aws cloudfront create-distribution \
    --distribution-config file://cloudfront-app-r53-distribution.json \
    --query "Distribution.Id" \
    --output text \
    --region us-east-1)

if [ $? -eq 0 ]; then
    print_success "应用程序分发点创建成功: $APP_DISTRIBUTION_ID"
    
    # 获取分发点域名
    APP_DOMAIN_NAME=$(aws cloudfront get-distribution \
        --id "$APP_DISTRIBUTION_ID" \
        --query "Distribution.DomainName" \
        --output text \
        --region us-east-1)
    
    print_success "应用程序分发点域名: $APP_DOMAIN_NAME"
else
    print_error "应用程序分发点创建失败"
    exit 1
fi

# 创建私有内容分发点
print_step "创建私有内容分发点 (${CDN_SUBDOMAIN})..."
CDN_DISTRIBUTION_ID=$(aws cloudfront create-distribution \
    --distribution-config file://cloudfront-cdn-r53-distribution.json \
    --query "Distribution.Id" \
    --output text \
    --region us-east-1)

if [ $? -eq 0 ]; then
    print_success "私有内容分发点创建成功: $CDN_DISTRIBUTION_ID"
    
    # 获取分发点域名
    CDN_DOMAIN_NAME=$(aws cloudfront get-distribution \
        --id "$CDN_DISTRIBUTION_ID" \
        --query "Distribution.DomainName" \
        --output text \
        --region us-east-1)
    
    print_success "私有内容分发点域名: $CDN_DOMAIN_NAME"
else
    print_error "私有内容分发点创建失败"
    exit 1
fi

# 更新DNS记录配置文件
print_step "更新DNS记录配置..."
sed -i.bak "s/d1234567890123.cloudfront.net/$APP_DOMAIN_NAME/g" route53-dns-records.json
sed -i.bak "s/d0987654321098.cloudfront.net/$CDN_DOMAIN_NAME/g" route53-dns-records.json

# 创建DNS记录
print_step "创建Route 53 DNS记录..."
CHANGE_ID=$(aws route53 change-resource-record-sets \
    --hosted-zone-id "$HOSTED_ZONE_ID" \
    --change-batch file://route53-dns-records.json \
    --query "ChangeInfo.Id" \
    --output text \
    --region us-east-1)

if [ $? -eq 0 ]; then
    print_success "DNS记录创建成功: $CHANGE_ID"
    
    # 等待DNS记录生效
    print_step "等待DNS记录生效..."
    aws route53 wait resource-record-sets-changed --id "$CHANGE_ID" --region us-east-1
    print_success "DNS记录已生效"
else
    print_error "DNS记录创建失败"
    exit 1
fi

# 等待CloudFront分发点部署完成
print_step "等待CloudFront分发点部署完成..."
print_warning "这可能需要10-15分钟..."

echo "等待应用程序分发点部署..."
aws cloudfront wait distribution-deployed --id "$APP_DISTRIBUTION_ID" --region us-east-1 &
APP_WAIT_PID=$!

echo "等待私有内容分发点部署..."
aws cloudfront wait distribution-deployed --id "$CDN_DISTRIBUTION_ID" --region us-east-1 &
CDN_WAIT_PID=$!

# 等待两个进程完成
wait $APP_WAIT_PID
wait $CDN_WAIT_PID

print_success "所有CloudFront分发点部署完成"

# 生成部署总结
print_step "生成部署总结..."
cat > deployment-summary.txt << EOF
🎉 CloudFront双分发点部署完成

📋 部署信息:
- 主域名: ${MAIN_DOMAIN}
- 应用程序域名: ${APP_SUBDOMAIN}
- 私有内容域名: ${CDN_SUBDOMAIN}

🔧 CloudFront分发点:
- 应用程序分发点ID: ${APP_DISTRIBUTION_ID}
- 应用程序分发点域名: ${APP_DOMAIN_NAME}
- 私有内容分发点ID: ${CDN_DISTRIBUTION_ID}
- 私有内容分发点域名: ${CDN_DOMAIN_NAME}

🌐 Route 53:
- Hosted Zone ID: ${HOSTED_ZONE_ID}
- DNS变更ID: ${CHANGE_ID}

📝 下一步:
1. 访问 https://${APP_SUBDOMAIN}/app-r53-main.php 测试应用程序
2. 检查Cookie是否正确设置
3. 测试私有内容访问: https://${CDN_SUBDOMAIN}/
4. 验证URL中没有签名参数

⚠️  注意事项:
- 确保SSL证书已正确配置
- 检查S3存储桶的OAC配置
- 验证ELB的健康检查状态
EOF

print_success "部署总结已保存到 deployment-summary.txt"

echo ""
echo "🎉 部署完成！"
echo ""
echo "📋 快速测试:"
echo "1. 应用程序: https://${APP_SUBDOMAIN}/app-r53-main.php"
echo "2. 私有内容: https://${CDN_SUBDOMAIN}/"
echo ""
echo "📖 详细信息请查看 deployment-summary.txt"
