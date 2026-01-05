# 🚀 VPS DEPLOYMENT GUIDE (NO DOMAIN)
## Berkah Production - Ubuntu 22 + Nginx + PHP 8.4 + MySQL

**Deployment Type:** IP Address Only (No Domain) - TRIAL ENVIRONMENT  
**Repository:** `girixxz/berkah-production`  
**Custom Folder:** Anda bisa pilih nama folder sendiri (contoh: `berkah-trial`, `berkah-dev`, dll)  
**Date:** December 30, 2025

---

## 📋 TABLE OF CONTENTS

1. [Initial VPS Setup](#step-1-initial-vps-setup)
2. [Security Setup](#step-2-security-setup)
3. [Create New User](#step-3-create-new-sudo-user)
4. [Install Software Stack](#step-4-install-software-stack)
5. [Setup MySQL Database](#step-5-setup-mysql-database)
6. [Install Composer](#step-6-install-composer)
7. [Clone & Setup Laravel](#step-7-clone-setup-laravel-project)
8. [Configure Nginx](#step-8-configure-nginx)
9. [Optimize Application](#step-9-optimize-application)
10. [Monitoring & Maintenance](#step-10-monitoring-maintenance)

---

## STEP 1: INITIAL VPS SETUP

### 1.1 Login ke VPS
```bash
# Login sebagai root
ssh root@YOUR_VPS_IP
# Masukkan password yang diberikan provider
```

### 1.2 Update Sistem
```bash
# Update package list & Upgrade semua packages
apt update -y && apt upgrade -y

# Install tools basic
apt install -y curl wget git unzip zip software-properties-common \
              build-essential gnupg2 ca-certificates lsb-release apt-transport-https
```

### 1.3 Set Timezone ke Indonesia
```bash
# Set timezone
timedatectl set-timezone Asia/Jakarta

# Verify
timedatectl
# Output: Time zone: Asia/Jakarta (WIB, +0700)
```

### 1.4 Set Hostname
```bash
# Set hostname
hostnamectl set-hostname berkah-production

# Update /etc/hosts
nano /etc/hosts
```

**Tambahkan di file `/etc/hosts`:**
```
127.0.0.1 localhost
127.0.1.1 berkah-trial
YOUR_VPS_IP berkah-trial
```

**Save:** `CTRL + X` → `Y` → `ENTER`

---

## STEP 2: SECURITY SETUP

### 2.1 Setup Firewall (UFW)
```bash
# Install UFW (biasanya sudah terinstall)
apt install -y ufw

# Default policies
ufw default deny incoming
ufw default allow outgoing

# Allow SSH (PENTING! Jangan lupa!)
ufw allow 22/tcp comment 'SSH'

# Allow HTTP (NO HTTPS karena tidak ada SSL)
ufw allow 80/tcp comment 'HTTP'

# Enable firewall
ufw enable

# Check status
ufw status numbered

# Expected output:
# Status: active
# 
#      To                         Action      From
#      --                         ------      ----
# [ 1] 22/tcp                     ALLOW IN    Anywhere                   # SSH
# [ 2] 80/tcp                     ALLOW IN    Anywhere                   # HTTP
```

### 2.2 Install Fail2Ban (Anti Brute Force)
```bash
# Install Fail2Ban
apt install -y fail2ban

# Buat custom config
nano /etc/fail2ban/jail.d/custom.conf
```

**Copy paste konfigurasi ini:**
```ini
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
```

**Save:** `CTRL + X` → `Y` → `ENTER`

```bash
# Start & enable Fail2Ban
systemctl enable fail2ban
systemctl start fail2ban

# Check status
fail2ban-client status
```

### 2.3 Secure SSH Configuration
```bash
# Backup original SSH config
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.backup

# Edit SSH config
nano /etc/ssh/sshd_config
```

**Cari dan ubah/tambahkan:**
```
# Disable root login setelah user baru dibuat (nanti di step 3)
PermitRootLogin no

# Disable empty passwords
PermitEmptyPasswords no

# Allow specific users only (ganti 'deployuser' dengan username kamu nanti)
AllowUsers deployuser

# Set login grace time


# Maximum auth tries
MaxAuthTries 3
```

**⚠️ JANGAN RESTART SSH DULU!** Tunggu sampai user baru dibuat di Step 3!

---

## STEP 3: CREATE NEW SUDO USER

### 3.1 Create User
```bash
# Create user 'deployuser' (atau nama lain yang kamu mau)
adduser deployuser

# Akan muncul pertanyaan:
# - Enter new UNIX password: (masukkan password kuat)
# - Retype new UNIX password: (ulangi password)
# - Full Name []: Berkah Deploy User
# - Room Number []: (tekan ENTER)
# - Work Phone []: (tekan ENTER)
# - Home Phone []: (tekan ENTER)
# - Other []: (tekan ENTER)
# - Is the information correct? [Y/n]: Y
```

### 3.2 Add User to Sudo Group
```bash
# Add user ke sudo group
usermod -aG sudo deployuser

# Verify
groups deployuser
# Output: deployuser : deployuser sudo
```

### 3.3 Test New User
```bash
# Switch ke user baru
su - deployuser

# Test sudo access
sudo ls /root
# Masukkan password deployuser

# Keluar ke root lagi
exit
```

### 3.4 (OPTIONAL) Setup SSH Key untuk User Baru
```bash
# Masih sebagai root, buat .ssh directory untuk user baru
mkdir -p /home/deployuser/.ssh

# Copy authorized_keys dari root (kalau ada)
cp /root/.ssh/authorized_keys /home/deployuser/.ssh/authorized_keys 2>/dev/null || echo "No SSH keys to copy"

# Set permissions
chown -R deployuser:deployuser /home/deployuser/.ssh
chmod 700 /home/deployuser/.ssh
chmod 600 /home/deployuser/.ssh/authorized_keys 2>/dev/null || true
```

### 3.5 Restart SSH (Sekarang AMAN)
```bash
# Restart SSH service
systemctl restart sshd

# Check status
systemctl status sshd
```

### 3.6 Test Login dengan User Baru
**JANGAN LOGOUT dari root dulu!**

Buka terminal baru dan test login:
```bash
ssh deployuser@YOUR_VPS_IP
# Masukkan password deployuser
```

Kalau berhasil login, **SELAMAT!** User baru sudah aktif.

---

## STEP 4: INSTALL SOFTWARE STACK

**Dari sekarang, gunakan user `deployuser`!**

```bash
# Login sebagai deployuser (kalau belum)
ssh deployuser@YOUR_VPS_IP
```

### 4.1 Install Nginx
```bash
# Install Nginx
sudo apt install -y nginx

# Start & enable Nginx
sudo systemctl enable nginx
sudo systemctl start nginx

# Check status
sudo systemctl status nginx

# Test: buka browser http://YOUR_VPS_IP
# Harusnya muncul "Welcome to nginx!"
```

### 4.2 Install PHP 8.4
```bash
# Add Ondrej PPA repository
sudo add-apt-repository ppa:ondrej/php -y

# Update package list
sudo apt update

# Install PHP 8.4 + extensions
sudo apt install -y php8.4-fpm php8.4-cli php8.4-common php8.4-mysql \
                    php8.4-mbstring php8.4-xml php8.4-bcmath php8.4-curl \
                    php8.4-zip php8.4-gd php8.4-intl php8.4-soap php8.4-imagick

# Check PHP version
php -v
# Output: PHP 8.4.x

# Enable & start PHP-FPM
sudo systemctl enable php8.4-fpm
sudo systemctl start php8.4-fpm

# Check status
sudo systemctl status php8.4-fpm
```

### 4.3 Configure PHP for Production

**⚠️ PENTING untuk Work Orders!**  
Admin bisa upload **7 gambar sekaligus** di Work Orders.

```bash
# Edit php.ini untuk FPM
sudo nano /etc/php/8.4/fpm/php.ini
```

**Cari dan ubah nilai ini (gunakan CTRL+W untuk search):**
```ini
; ========================================
; UPLOAD & POST SIZE (CRITICAL FOR WORK ORDERS!)
; ========================================
upload_max_filesize = 50M
post_max_size = 60M
max_file_uploads = 20

; ========================================
; EXECUTION LIMITS
; ========================================
max_execution_time = 600
max_input_time = 600
max_input_vars = 3000
memory_limit = 512M

; ========================================
; ERROR REPORTING (PRODUCTION)
; ========================================
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php-fpm-errors.log

; ========================================
; TIMEZONE
; ========================================
date.timezone = Asia/Jakarta

; ========================================
; REALPATH CACHE (PERFORMANCE)
; ========================================
realpath_cache_size = 4096K
realpath_cache_ttl = 600

; ========================================
; SESSION
; ========================================
session.save_handler = files
session.save_path = "/var/lib/php/sessions"
session.gc_maxlifetime = 7200
```

**Save:** `CTRL + X` → `Y` → `ENTER`

```bash
# Create PHP session directory
sudo mkdir -p /var/lib/php/sessions
sudo chown -R www-data:www-data /var/lib/php/sessions
sudo chmod 1733 /var/lib/php/sessions

# Restart PHP-FPM (WAJIB!)
sudo systemctl restart php8.4-fpm

# Verify PHP FPM settings
php-fpm8.4 -i | grep upload_max_filesize
# Output: upload_max_filesize => 50M => 50M

php-fpm8.4 -i | grep post_max_size
# Output: post_max_size => 60M => 60M

php-fpm8.4 -i | grep memory_limit
# Output: memory_limit => 512M => 512M
```

### 4.4 Configure ImageMagick (For PDF Generation)

**⚠️ PENTING untuk Download Work Order PDF!**

```bash
# Check ImageMagick installation
convert -version
# Output: Version: ImageMagick 6.9.x

# Backup original policy
sudo cp /etc/ImageMagick-6/policy.xml /etc/ImageMagick-6/policy.xml.backup

# Edit ImageMagick policy
sudo nano /etc/ImageMagick-6/policy.xml
```

**⚠️ IMPORTANT: Sesuaikan dengan RAM VPS!**
- VPS 1GB RAM → gunakan `memory: 256MiB`
- VPS 2GB RAM → gunakan `memory: 512MiB` 
- VPS 4GB+ RAM → gunakan `memory: 1GiB`

**Cari section `<policymap>` dan ubah/tambahkan:**
```xml
<policymap>
  <!-- Resource limits -->
  <policy domain="resource" name="memory" value="512MiB"/>
  <policy domain="resource" name="map" value="1GiB"/>
  <policy domain="resource" name="width" value="10KP"/>
  <policy domain="resource" name="height" value="10KP"/>
  <policy domain="resource" name="area" value="128MP"/>
  <policy domain="resource" name="disk" value="1GiB"/>
  <policy domain="resource" name="thread" value="2"/>
  <policy domain="resource" name="time" value="300"/>
  
  <!-- Allow PDF processing -->
  <policy domain="coder" rights="read|write" pattern="PDF" />
  <policy domain="coder" rights="read|write" pattern="PNG" />
  <policy domain="coder" rights="read|write" pattern="JPEG" />
  <policy domain="coder" rights="read|write" pattern="JPG" />
</policymap>
```

**Save:** `CTRL + X` → `Y` → `ENTER`

```bash
# Verify ImageMagick limits
convert -list resource

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

### 4.5 Install Node.js & NPM
```bash
# Install Node.js 20.x LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -

# Install Node.js & NPM
sudo apt install -y nodejs

# Verify installation
node --version
# Output: v20.x.x

npm --version
# Output: 10.x.x

# Install build-essential
sudo apt install -y build-essential
```

### 4.6 Install MySQL Server
```bash
# Install MySQL
sudo apt install -y mysql-server

# Start & enable MySQL
sudo systemctl enable mysql
sudo systemctl start mysql

# Check status
sudo systemctl status mysql
```

### 4.7 Secure MySQL Installation
```bash
# Login ke MySQL (tanpa password atau dengan password default)
sudo mysql
```

**Di dalam MySQL console, ubah password root:**
```sql
-- Ubah password root ke password baru
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'BerkahMySQL2024!@';

-- Flush privileges
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

**Sekarang jalankan mysql_secure_installation:**
```bash
sudo mysql_secure_installation
```

**Jawab pertanyaan:**
```
1. Enter password for user root:
   → BerkahMySQL2024!@

2. Would you like to setup VALIDATE PASSWORD component? 
   → NO

3. Change the password for root?
   → NO

4. Remove anonymous users?
   → YES

5. Disallow root login remotely?
   → YES

6. Remove test database and access to it?
   → YES

7. Reload privilege tables now?
   → YES
```

---

## STEP 5: SETUP MYSQL DATABASE

### 5.1 Login ke MySQL
```bash
# Login dengan password baru
sudo mysql -u root -p
# Masukkan password: BerkahMySQL2024!@
```

### 5.2 Create Database & User
**Dalam MySQL console:**

```sql
-- Create database
CREATE DATABASE berkah_trial CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'berkah_user'@'localhost' IDENTIFIED BY 'BerkahDB2024!Secure';

-- Grant privileges
GRANT ALL PRIVILEGES ON berkah_trial.* TO 'berkah_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Show databases
SHOW DATABASES;

-- Exit
EXIT;
```

### 5.3 Test Database Connection
```bash
mysql -u berkah_user -p berkah_trial
# Masukkan password: BerkahDB2024!Secure

# Kalau berhasil login, ketik:
# SHOW TABLES;
# EXIT;
```

### 5.4 Configure MySQL for Production
```bash
# Edit MySQL config
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

**Tambahkan/ubah di section `[mysqld]`:**
```ini
[mysqld]
# Performance tuning
max_connections = 200
connect_timeout = 10
wait_timeout = 600
max_allowed_packet = 64M

# InnoDB Settings
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Slow Query Log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2

# Character Set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

**Save:** `CTRL + X` → `Y` → `ENTER`

```bash
# Restart MySQL
sudo systemctl restart mysql
```

---

## STEP 6: INSTALL COMPOSER

```bash
# Download Composer installer
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php

# Verify installer SHA-384
HASH=`curl -sS https://composer.github.io/installer.sig`
php -r "if (hash_file('SHA384', '/tmp/composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('/tmp/composer-setup.php'); } echo PHP_EOL;"

# Install Composer globally
sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Verify installation
composer --version
# Output: Composer version 2.x.x
```

---

## STEP 7: CLONE & SETUP LARAVEL PROJECT

### 7.1 Create Web Directory
```bash
# Create directory
sudo mkdir -p /var/www

# Set ownership ke deployuser
sudo chown -R deployuser:deployuser /var/www
```

### 7.2 Clone Repository from GitHub
```bash
# Move to web directory
cd /var/www

# Clone repository dengan custom folder name
# Ganti 'berkah-trial' dengan nama folder yang kamu mau
# Contoh: berkah-dev, berkah-test, berkah-staging, dll
git clone https://github.com/girixxz/berkah-production.git berkah-trial

# Verify
ls -la berkah-trial/

# Set variable untuk memudahkan (gunakan nama folder yang sama)
export PROJECT_DIR="berkah-trial"
```

**⚠️ CATATAN:**  
Dari sini, ganti semua path `/var/www/berkah-production` dengan `/var/www/berkah-trial` (atau nama folder custom kamu).  
Contoh command selanjutnya akan pakai `/var/www/$PROJECT_DIR` untuk fleksibilitas.

### 7.3 Install PHP Dependencies
```bash
# Move to project directory (ganti berkah-trial dengan nama folder kamu)
cd /var/www/berkah-trial

# Install dependencies (production mode)
composer install --optimize-autoloader --no-dev
```

### 7.4 Install Node Dependencies & Build Assets
```bash
# Still in /var/www/berkah-trial (atau nama folder kamu)

# Install NPM packages
npm install

# Build production assets
npm run build

# Verify build directory
ls -la public/build/
```

### 7.5 Setup Environment File
```bash
# Copy .env.example ke .env
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file
nano .env
```

**Edit `.env` dengan konfigurasi production (NO DOMAIN - USE IP):**

```env
APP_NAME="Berkah Production"
APP_ENV=production
APP_KEY=base64:xxxxx  # Already generated
APP_DEBUG=false
APP_URL=http://YOUR_VPS_IP

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=berkah_production
DB_USERNAME=berkah_user
DB_PASSWORD=BerkahDB2024!Secure

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=local

BROADCAST_CONNECTION=log
MAIL_MAILER=log
```

**⚠️ PENTING: Ganti `YOUR_VPS_IP` dengan IP VPS kamu!**

**Save:** `CTRL + X` → `Y` → `ENTER`

### 7.6 Set Permissions
```bash
# Set owner ke www-data (Nginx user)
# Ganti berkah-trial dengan nama folder kamu
sudo chown -R www-data:www-data /var/www/berkah-trial

# Set directory permissions
sudo find /var/www/berkah-trial -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/berkah-trial -type f -exec chmod 644 {} \;

# Set special permissions for storage & cache
sudo chmod -R 775 /var/www/berkah-trial/storage
sudo chmod -R 775 /var/www/berkah-trial/bootstrap/cache

# Create private storage directory
sudo mkdir -p /var/www/berkah-trial/storage/app/private
sudo chown -R www-data:www-data /var/www/berkah-trial/storage/app/private
sudo chmod -R 775 /var/www/berkah-trial/storage/app/private

# Make artisan executable
sudo chmod +x /var/www/berkah-trial/artisan
```

### 7.7 Run Database Migrations
```bash
# Run migrations
php artisan migrate --force
```

### 7.8 (Optional) Seed Database
```bash
# Kalau ada seeder, jalankan:
php artisan db:seed --force
```

### 7.9 Create Default Admin User
```bash
# Login ke MySQL
mysql -u berkah_user -p berkah_trial
# Password: BerkahDB2024!Secure
```

**Dalam MySQL console:**

```sql
-- Insert default owner user
INSERT INTO users (fullname, username, phone_number, password, role, created_at, updated_at) 
VALUES (
    'Admin Berkah',
    'admin',
    '081234567890',
    '$2y$12$LQv3c1yycMGWGqVJ8iVWL.nPsxdJGK0m2eR0.LdZxJ5PkK6XVDJ6O',
    'owner',
    NOW(),
    NOW()
);

-- Verify
SELECT id, fullname, username, role FROM users;

EXIT;
```

**Default Login Credentials:**
- **Username:** `admin`
- **Password:** `password123`

⚠️ **GANTI PASSWORD SETELAH LOGIN PERTAMA KALI!**

---

## STEP 8: CONFIGURE NGINX

### 8.1 Create Nginx Configuration
```bash
# Create config file (ganti berkah-trial dengan nama folder kamu)
sudo nano /etc/nginx/sites-available/berkah-trial
```

**Copy paste konfigurasi ini (NO SSL - HTTP ONLY):**

**⚠️ PENTING: Ganti semua `/var/www/berkah-trial` dengan nama folder kamu!**

```nginx
# HTTP server (NO DOMAIN - IP ONLY)
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    # NO server_name needed for IP-only access
    # Nginx will serve ANY request to this server block

    root /var/www/berkah-trial/public;
    index index.php index.html;

    # Character set
    charset utf-8;

    # Logging
    access_log /var/log/nginx/berkah-access.log;
    error_log /var/log/nginx/berkah-error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # ========================================
    # CLIENT MAX BODY SIZE (CRITICAL FOR WORK ORDERS!)
    # Work Orders upload 7 images simultaneously (~35-50MB total)
    # ========================================
    client_max_body_size 60M;
    client_body_timeout 600s;
    client_header_timeout 600s;

    # ========================================
    # TIMEOUTS
    # ========================================
    proxy_connect_timeout 600;
    proxy_send_timeout 600;
    proxy_read_timeout 600;
    send_timeout 600;

    # ========================================
    # BUFFER SIZE
    # ========================================
    client_body_buffer_size 10M;
    client_header_buffer_size 4k;
    large_client_header_buffers 8 16k;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Block direct access to storage private
    location ~ ^/storage/app/private {
        deny all;
        return 404;
    }

    # PHP-FPM handler
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # ========================================
        # FASTCGI TIMEOUTS & BUFFERS (CRITICAL!)
        # ========================================
        fastcgi_read_timeout 600;
        fastcgi_send_timeout 600;
        fastcgi_connect_timeout 600;
        fastcgi_buffers 16 32k;
        fastcgi_buffer_size 64k;
        fastcgi_busy_buffers_size 128k;
        fastcgi_temp_file_write_size 256k;
        
        # Untuk upload progress tracking
        fastcgi_request_buffering off;
    }

    # Deny .htaccess
    location ~ /\.ht {
        deny all;
    }

    # Deny hidden files
    location ~ /\. {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|webp|woff|woff2|ttf|eot)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
```

**Save:** `CTRL + X` → `Y` → `ENTER`

### 8.2 Enable Site & Test Configuration
```bash
# Enable site (ganti berkah-trial dengan nama config kamu)
sudo ln -s /etc/nginx/sites-available/berkah-trial /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t

# Expected output:
# nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
# nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### 8.3 Restart Nginx
```bash
# Restart Nginx
sudo systemctl restart nginx

# Check status
sudo systemctl status nginx
```

### 8.4 Test Website
Buka browser:
```
http://YOUR_VPS_IP
```

**Harusnya muncul halaman login!** 🎉

---

## STEP 9: OPTIMIZE APPLICATION

### 9.1 Clear All Caches
```bash
# Ganti berkah-trial dengan nama folder kamu
cd /var/www/berkah-trial

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 9.2 Build Production Caches
```bash
# Build optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 9.3 Setup Queue Worker (Optional)
```bash
# Create systemd service (ganti berkah-trial dengan nama folder kamu)
sudo nano /etc/systemd/system/berkah-trial-queue.service
```

**Copy paste:**

**⚠️ PENTING: Ganti `/var/www/berkah-trial` dengan nama folder kamu!**

```ini
[Unit]
Description=Berkah Trial Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php /var/www/berkah-trial/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

**Save:** `CTRL + X` → `Y` → `ENTER`

```bash
# Enable & start queue worker (ganti berkah-trial dengan nama service kamu)
sudo systemctl enable berkah-trial-queue
sudo systemctl start berkah-trial-queue

# Check status
sudo systemctl status berkah-trial-queue
```

### 9.4 Setup Cron Jobs for Laravel Scheduler
```bash
# Edit crontab for www-data user
sudo crontab -u www-data -e

# Pilih editor: 1 (nano)
```

**Tambahkan baris ini (ganti berkah-trial dengan nama folder kamu):**
```cron
* * * * * php /var/www/berkah-trial/artisan schedule:run >> /dev/null 2>&1
```

**Save:** `CTRL + X` → `Y` → `ENTER`

### 9.5 Enable OPcache for PHP
```bash
# Edit php.ini
sudo nano /etc/php/8.4/fpm/php.ini
```

**Cari section `[opcache]` dan aktifkan:**
```ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

**Save:** `CTRL + X` → `Y` → `ENTER`

```bash
# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

---

## STEP 10: MONITORING & MAINTENANCE

### 10.1 Setup Automatic Updates
```bash
# Install unattended-upgrades
sudo apt install -y unattended-upgrades

# Enable automatic security updates
sudo dpkg-reconfigure -plow unattended-upgrades
# Select: Yes
```

### 10.2 Setup Log Rotation
```bash
# Create logrotate config (ganti berkah-trial dengan nama folder kamu)
sudo nano /etc/logrotate.d/berkah-trial
```

**Copy paste (ganti path sesuai nama folder kamu):**
```
/var/www/berkah-trial/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
}
```

**Save:** `CTRL + X` → `Y` → `ENTER`

### 10.3 Monitoring Commands

**Check Nginx Logs:**
```bash
# Access log
sudo tail -f /var/log/nginx/berkah-access.log

# Error log
sudo tail -f /var/log/nginx/berkah-error.log
```

**Check Laravel Logs:**
```bash
# Laravel log (ganti berkah-trial dengan nama folder kamu)
sudo tail -f /var/www/berkah-trial/storage/logs/laravel.log
```

**Check Services Status:**
```bash
sudo systemctl status php8.4-fpm
sudo systemctl status nginx
sudo systemctl status mysql
```

**Check Resources:**
```bash
# Disk usage
df -h

# Memory usage
free -h

# Server load
uptime
```

### 10.4 Database Backup Script

```bash
# Create backup directory (ganti berkah-trial dengan nama folder kamu)
sudo mkdir -p /var/backups/berkah-trial
sudo chown deployuser:deployuser /var/backups/berkah-trial

# Create backup script
nano ~/backup-database.sh
```

**Copy paste (sesuaikan BACKUP_DIR dengan nama folder kamu):**
```bash
#!/bin/bash

# Configuration
DB_NAME="berkah_production"
DB_USER="berkah_user"
DB_PASS="BerkahDB2024!Secure"
BACKUP_DIR="/var/backups/berkah-trial"  # Ganti sesuai nama folder
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/berkah_db_$DATE.sql"

# Create backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_FILE

# Compress backup
gzip $BACKUP_FILE

# Delete backups older than 7 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

echo "Backup completed: $BACKUP_FILE.gz"
```

**Save:** `CTRL + X` → `Y` → `ENTER`

```bash
# Make script executable
chmod +x ~/backup-database.sh

# Test backup
./backup-database.sh

# Verify (ganti berkah-trial dengan nama folder kamu)
ls -lh /var/backups/berkah-trial/
```

**Setup automatic daily backup:**
```bash
# Edit crontab
crontab -e
```

**Tambahkan:**
```cron
# Database backup setiap hari jam 2 pagi
0 2 * * * /home/deployuser/backup-database.sh >> /var/log/berkah-backup.log 2>&1
```

**Save:** `CTRL + X` → `Y` → `ENTER`

### 10.5 Restart All Services
```bash
# Restart semua services
sudo systemctl restart nginx
sudo systemctl restart php8.4-fpm
sudo systemctl restart mysql

# Check status
sudo systemctl status nginx
sudo systemctl status php8.4-fpm
sudo systemctl status mysql
```

---

## 🔧 TROUBLESHOOTING

### Problem 1: 502 Bad Gateway
```bash
# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm

# Check sock file
ls -la /var/run/php/php8.4-fpm.sock
```

### Problem 2: Permission Denied
```bash
# Ganti berkah-trial dengan nama folder kamu
cd /var/www/berkah-trial

# Reset permissions
sudo chown -R www-data:www-data .
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;
sudo chmod -R 775 storage bootstrap/cache
```

### Problem 3: 500 Internal Server Error
```bash
# Check Laravel log (ganti berkah-trial dengan nama folder kamu)
sudo tail -100 /var/www/berkah-trial/storage/logs/laravel.log

# Check Nginx error log
sudo tail -100 /var/log/nginx/berkah-error.log

# Clear cache
cd /var/www/berkah-trial
php artisan cache:clear
php artisan config:clear
php artisan optimize
```

### Problem 4: Work Order Image Upload Failed
```bash
# Check PHP settings
php -i | grep -E "upload_max_filesize|post_max_size|max_file_uploads"

# Check Nginx config (ganti berkah-trial dengan nama config kamu)
sudo nano /etc/nginx/sites-available/berkah-trial
# Verify: client_max_body_size 60M;

# Check storage permissions (ganti berkah-trial dengan nama folder kamu)
sudo chown -R www-data:www-data /var/www/berkah-trial/storage/
sudo chmod -R 775 /var/www/berkah-trial/storage/

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.4-fpm
```

### Problem 5: Work Order PDF Download Failed
```bash
# Check ImageMagick limits
convert -list resource

# Should show:
# Memory: 512MiB (adjust based on VPS RAM)
# Map: 1GiB
# Disk: 1GiB

# If wrong, edit policy:
sudo nano /etc/ImageMagick-6/policy.xml

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

---

## 📝 UPDATE APPLICATION FROM GITHUB

```bash
# Login sebagai deployuser
ssh deployuser@YOUR_VPS_IP

# Go to project directory (ganti berkah-trial dengan nama folder kamu)
cd /var/www/berkah-trial

# Add safe directory (first time only, ganti berkah-trial dengan nama folder kamu)
git config --global --add safe.directory /var/www/berkah-trial

# Backup current version (optional)
sudo cp -r /var/www/berkah-trial /var/backups/berkah-trial-$(date +%Y%m%d)

# Pull latest code
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev

# Install new NPM packages (if changed)
npm install

# Rebuild assets
npm run build

# Run new migrations (if any)
php artisan migrate --force

# Clear & rebuild cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Fix permissions (ganti berkah-trial dengan nama folder kamu)
sudo chown -R www-data:www-data /var/www/berkah-trial
sudo chmod -R 775 /var/www/berkah-trial/storage

# Verify storage directories
sudo mkdir -p /var/www/berkah-trial/storage/app/orders
sudo mkdir -p /var/www/berkah-trial/storage/app/work-orders
sudo mkdir -p /var/www/berkah-trial/storage/app/private
sudo chown -R www-data:www-data /var/www/berkah-trial/storage/app/
sudo chmod -R 775 /var/www/berkah-trial/storage/app/

# Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

# Check logs
sudo tail -50 /var/www/berkah-trial/storage/logs/laravel.log

echo "✓ Update completed!"
```

---

## ✅ DEPLOYMENT CHECKLIST

**Initial Setup:**
- [ ] Update sistem Ubuntu
- [ ] Setup firewall (UFW) - HTTP only
- [ ] Install Fail2Ban
- [ ] Secure SSH configuration
- [ ] Create sudo user (deployuser)
- [ ] Test new user access

**Software Stack:**
- [ ] Install Nginx
- [ ] Install PHP 8.4 + extensions
- [ ] Configure PHP for production
- [ ] Configure ImageMagick
- [ ] Install Node.js & NPM
- [ ] Install MySQL
- [ ] Secure MySQL
- [ ] Create database & user
- [ ] Install Composer

**Application Setup:**
- [ ] Clone project from GitHub
- [ ] Install PHP dependencies
- [ ] Install Node dependencies
- [ ] Build production assets
- [ ] Setup .env file (with IP address)
- [ ] Generate APP_KEY
- [ ] Set file permissions
- [ ] Run migrations
- [ ] Create default admin user

**Web Server:**
- [ ] Configure Nginx (HTTP only, no SSL)
- [ ] Enable site configuration
- [ ] Test Nginx config

**Optimization:**
- [ ] Build production caches
- [ ] Setup queue worker (optional)
- [ ] Setup cron jobs
- [ ] Enable OPcache

**Monitoring:**
- [ ] Setup log rotation
- [ ] Create database backup script
- [ ] Setup automatic backups
- [ ] Test all services

**Final Test:**
- [ ] Access website via HTTP (http://YOUR_VPS_IP)
- [ ] Test login functionality
- [ ] Test image upload
- [ ] Monitor logs for errors

---

## 🎉 DEPLOYMENT COMPLETED!

**Website URL:** http://YOUR_VPS_IP  
**Admin Login:** http://YOUR_VPS_IP/login

**Default Credentials:**
- Username: `admin`
- Password: `password123`

**⚠️ IMPORTANT:**
1. Ganti IP `YOUR_VPS_IP` di semua tempat dengan IP VPS kamu
2. Website hanya bisa diakses via HTTP (tanpa SSL/HTTPS)
3. Tidak ada redirect HTTPS karena tidak ada domain/SSL
4. GANTI PASSWORD admin setelah login pertama kali!

---

## 🔒 SECURITY NOTES

**⚠️ TANPA SSL/HTTPS:**
- Data dikirim tanpa enkripsi (termasuk password)
- Rentan terhadap Man-in-the-Middle attacks
- Tidak recommended untuk production serius
- Cocok untuk development/testing/internal use

**REKOMENDASI:**
- Gunakan VPN saat akses dari luar
- Atau gunakan SSH tunnel: `ssh -L 8080:localhost:80 deployuser@YOUR_VPS_IP`
- Lalu akses via `http://localhost:8080`

---

**Happy Deploying! 🚀**

**Last Updated:** December 30, 2025
