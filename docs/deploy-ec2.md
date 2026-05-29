# Triển khai Laravel App lên AWS EC2

## Tổng quan kiến trúc

```
Internet → ALB (HTTPS 443) → EC2 (Nginx + PHP-FPM) → RDS PostgreSQL (IAM Auth)
```

- **EC2**: Amazon Linux 2023, PHP 8.3+, Nginx, PHP-FPM
- **RDS**: PostgreSQL, xác thực qua IAM token (không dùng password tĩnh)
- **IAM Role**: EC2 instance role cấp quyền `rds-db:connect`
- **Session / Cache / Queue**: lưu trong RDS (driver `database`)

---

## Bước 1: Tạo IAM Role cho EC2

### 1.1 Tạo IAM Policy cho RDS IAM Auth

Vào **IAM → Policies → Create policy**, dùng JSON:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": "rds-db:connect",
      "Resource": "arn:aws:rds-db:REGION:ACCOUNT_ID:dbuser:DB_RESOURCE_ID/DB_USERNAME"
    }
  ]
}
```

Thay:
- `REGION`: ví dụ `ap-northeast-1`
- `ACCOUNT_ID`: AWS account ID
- `DB_RESOURCE_ID`: lấy từ RDS → Databases → chọn instance → **Configuration** → `Resource ID` (dạng `db-XXXX`)
- `DB_USERNAME`: username DB, ví dụ `postgres`

Đặt tên policy: `rds-iam-connect-policy`

### 1.2 Tạo IAM Role cho EC2

Vào **IAM → Roles → Create role**:
- Trusted entity: **AWS service → EC2**
- Attach policies:
  - `rds-iam-connect-policy` (vừa tạo)
  - `AmazonSSMManagedInstanceCore` (để dùng Session Manager thay SSH)

Đặt tên role: `ec2-laravel-app-role`

---

## Bước 2: Cấu hình RDS

### 2.1 Bật IAM Authentication trên RDS instance

Vào **RDS → Databases → chọn instance → Modify**:
- Trong phần **Database authentication** → bật **Password and IAM database authentication**
- Apply immediately

### 2.2 Tạo DB user với quyền rds_iam (PostgreSQL)

Kết nối vào RDS bằng master user, chạy:

```sql
-- Tạo user với role rds_iam
CREATE USER postgres WITH LOGIN;
GRANT rds_iam TO postgres;

-- Cấp quyền trên database
GRANT ALL PRIVILEGES ON DATABASE "ad-dev" TO postgres;
GRANT ALL ON SCHEMA public TO postgres;
```

> **Lưu ý**: User dùng IAM auth **không được set password** — xác thực hoàn toàn qua IAM token.

### 2.3 Security Group RDS

Cho phép inbound từ Security Group của EC2:
- Port `5432` (PostgreSQL) hoặc `3306` (MySQL)
- Source: Security Group ID của EC2

---

## Bước 3: Khởi tạo EC2

### 3.1 Launch EC2 instance

- **AMI**: Amazon Linux 2023
- **Instance type**: `t3.small` trở lên
- **IAM instance profile**: `ec2-laravel-app-role`
- **Security Group**:
  - Inbound: 80, 443 từ ALB (hoặc 0.0.0.0/0 nếu không có ALB)
  - Inbound: 22 từ bastion (hoặc dùng SSM, không cần port 22)
- **Storage**: 20GB gp3

### 3.2 Cài đặt phần mềm

```bash
# Update system
sudo dnf update -y

# PHP 8.3 + extensions cần thiết
sudo dnf install -y \
  php8.3 php8.3-fpm php8.3-cli \
  php8.3-pgsql php8.3-pdo \
  php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-intl php8.3-bcmath \
  php8.3-opcache php8.3-redis

# Nginx
sudo dnf install -y nginx

# Node.js 22 (để build assets)
curl -fsSL https://rpm.nodesource.com/setup_22.x | sudo bash -
sudo dnf install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Git
sudo dnf install -y git

# Bật services
sudo systemctl enable --now nginx php-fpm
```

---

## Bước 4: Deploy ứng dụng

### 4.1 Clone và cài dependencies

```bash
# Tạo thư mục app
sudo mkdir -p /var/www/laravel
sudo chown ec2-user:ec2-user /var/www/laravel

# Clone code
cd /var/www/laravel
git clone https://github.com/YOUR_ORG/YOUR_REPO.git .

# Cài PHP dependencies (production, bỏ dev)
composer install --no-dev --optimize-autoloader

# Build frontend assets
npm ci
npm run build

# Xóa Node modules sau khi build (tiết kiệm disk)
rm -rf node_modules
```

### 4.2 Cấu hình file `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Chỉnh `.env` cho production:

```dotenv
APP_NAME="Your App Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

# Database: dùng RDS IAM auth
DB_CONNECTION=rds_pgsql

# Session / Cache / Queue dùng database
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# AWS Region (EC2 tự lấy credentials từ Instance Profile, không cần KEY/SECRET)
AWS_DEFAULT_REGION=ap-northeast-1

# RDS
RDS_IAM_AUTH=true
RDS_HOST=your-rds-endpoint.rds.amazonaws.com
RDS_PORT=5432
RDS_DATABASE=your_database
RDS_USERNAME=postgres
RDS_DRIVER=pgsql
RDS_CA_BUNDLE=/var/www/aws-laravel/storage/rds/global-bundle.pem
```

> **Quan trọng**: Không cần `AWS_ACCESS_KEY_ID` hay `AWS_SECRET_ACCESS_KEY` trên EC2. SDK tự lấy credentials từ **Instance Metadata Service (IMDS)** qua IAM Role đã gắn.

### 4.3 Tải RDS SSL Certificate

```bash
mkdir -p storage/rds

# Download global bundle (bao gồm tất cả regions)
curl -o storage/rds/global-bundle.pem \
  https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem
```

### 4.4 Quyền file

```bash
# PHP-FPM chạy với user nginx (hoặc www-data)
sudo chown -R nginx:nginx /var/www/laravel/storage
sudo chown -R nginx:nginx /var/www/laravel/bootstrap/cache
sudo chmod -R 775 /var/www/laravel/storage
sudo chmod -R 775 /var/www/laravel/bootstrap/cache
```

### 4.5 Migrate database

```bash
php artisan migrate --force
```

### 4.6 Optimize cho production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Bước 5: Cấu hình Nginx

Tạo file `/etc/nginx/conf.d/laravel.conf`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;

    # Redirect HTTP → HTTPS (nếu có ALB xử lý SSL, bỏ phần này)
    # return 301 https://$host$request_uri;

    root /var/www/laravel/public;
    index index.php;

    # Xử lý X-Forwarded headers từ ALB
    set_real_ip_from 10.0.0.0/8;
    real_ip_header X-Forwarded-For;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Tắt log cho static files
    location ~* \.(ico|css|js|gif|jpe?g|png|svg|woff2?)$ {
        expires max;
        log_not_found off;
    }
}
```

```bash
# Test và reload Nginx
sudo nginx -t && sudo systemctl reload nginx
```

---

## Bước 6: Cấu hình PHP-FPM

Chỉnh `/etc/php-fpm.d/www.conf`:

```ini
user = nginx
group = nginx
listen = /run/php-fpm/www.sock
listen.owner = nginx
listen.group = nginx
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
```

```bash
sudo systemctl restart php-fpm
```

---

## Bước 7: Queue Worker (Systemd)

Tạo file `/etc/systemd/system/laravel-queue.service`:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=nginx
Group=nginx
WorkingDirectory=/var/www/laravel
ExecStart=/usr/bin/php /var/www/laravel/artisan queue:work \
  --sleep=3 \
  --tries=3 \
  --timeout=90 \
  --max-time=3600
Restart=always
RestartSec=5
StandardOutput=append:/var/www/laravel/storage/logs/queue.log
StandardError=append:/var/www/laravel/storage/logs/queue.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now laravel-queue
sudo systemctl status laravel-queue
```

---

## Bước 8: Scheduled Tasks (Cron)

```bash
sudo -u nginx crontab -e
```

Thêm dòng:

```cron
* * * * * /usr/bin/php /var/www/laravel/artisan schedule:run >> /dev/null 2>&1
```

---

## Bước 9: ALB + HTTPS (tùy chọn)

Nếu dùng **Application Load Balancer**:

1. Tạo ALB với listener HTTPS:443 (certificate từ ACM)
2. Forwarded đến Target Group port 80 trên EC2
3. Thêm vào `.env`:

```dotenv
TRUSTPROXIES_PROXIES=*
```

4. Thêm vào `config/trustedproxies.php` (hoặc trong `bootstrap/app.php`):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB);
})
```

---

## Bước 10: Deploy script (CI/CD)

Script deploy khi có code mới (`/usr/local/bin/deploy.sh`):

```bash
#!/bin/bash
set -e

APP_DIR="/var/www/laravel"
cd "$APP_DIR"

echo "→ Pulling latest code..."
git pull origin main

echo "→ Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "→ Building assets..."
npm ci && npm run build && rm -rf node_modules

echo "→ Running migrations..."
php artisan migrate --force

echo "→ Clearing and re-caching config..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "→ Restarting queue worker..."
sudo systemctl restart laravel-queue

echo "✓ Deploy complete"
```

```bash
chmod +x /usr/local/bin/deploy.sh
```

---

## Kiểm tra sau deploy

```bash
# Kiểm tra IAM credentials (phải trả về instance role, không phải user key)
aws sts get-caller-identity

# Kiểm tra kết nối RDS
php artisan tinker --execute 'DB::connection("rds_pgsql")->select("SELECT current_user");'

# Kiểm tra app
curl -I https://yourdomain.com/

# Xem logs
tail -f /var/www/laravel/storage/logs/laravel.log
```

---

## Xử lý sự cố thường gặp

| Triệu chứng | Nguyên nhân | Cách fix |
|---|---|---|
| `Error retrieving credentials from instance profile` | EC2 chưa gắn IAM Role | Attach `ec2-laravel-app-role` vào instance |
| `FATAL ROLE "postgres" does not exist` | DB user chưa được tạo với `rds_iam` role | Chạy lại Bước 2.2 |
| `SSL connection required` | Thiếu CA bundle | Kiểm tra `RDS_CA_BUNDLE` trỏ đúng file `.pem` |
| `502 Bad Gateway` | PHP-FPM không chạy | `sudo systemctl restart php-fpm` |
| `419 Page Expired` | `APP_URL` sai hoặc proxy không tin tưởng | Kiểm tra `APP_URL` và TrustedProxies |
| `storage` không ghi được | Sai quyền file | `sudo chown -R nginx:nginx storage bootstrap/cache` |
