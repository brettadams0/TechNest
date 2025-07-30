# TechNest Installation Guide

## Overview

This guide provides step-by-step instructions for installing TechNest on any web server. TechNest is a PHP/MySQL e-commerce application designed for selling electronics and gadgets.

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Pre-Installation Checklist](#pre-installation-checklist)
3. [Installation Methods](#installation-methods)
4. [Database Setup](#database-setup)
5. [Configuration](#configuration)
6. [File Permissions](#file-permissions)
7. [Testing Installation](#testing-installation)
8. [Post-Installation Setup](#post-installation-setup)
9. [Troubleshooting](#troubleshooting)
10. [Security Considerations](#security-considerations)

## System Requirements

### Minimum Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher (or MariaDB 10.3+)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: 512MB RAM minimum
- **Storage**: 100MB available space

### PHP Extensions Required
- **PDO**: For database connectivity
- **PDO_MySQL**: MySQL database driver
- **JSON**: For data processing
- **Session**: For user sessions
- **Fileinfo**: For file uploads
- **GD**: For image processing (optional)

## Pre-Installation Checklist

### Server Environment
- [ ] Web server (Apache/Nginx) installed and running
- [ ] PHP 8.0+ installed with required extensions
- [ ] MySQL 8.0+ installed and running
- [ ] SSL certificate configured (recommended)
- [ ] Domain name pointed to server (optional)

### Database Preparation
- [ ] MySQL server accessible
- [ ] Database user with appropriate privileges
- [ ] Database created for TechNest
- [ ] Database credentials documented

### File System
- [ ] Web root directory accessible
- [ ] Write permissions for web server user
- [ ] Backup location configured
- [ ] Log directory created

## Installation Methods

### Method 1: Direct File Upload

#### Step 1: Download Source Code
1. **Download TechNest** from the source repository
2. **Extract files** to a temporary location
3. **Review file structure** to understand the application

#### Step 2: Upload to Server
1. **Connect to your server** via FTP/SFTP
2. **Navigate to web root** (e.g., `/public_html/`)
3. **Upload all files** maintaining directory structure
4. **Verify upload** - all files should be present

### Method 2: Git Clone (Recommended)

#### Step 1: Clone Repository
```bash
# Navigate to web root
cd /var/www/html/

# Clone the repository
git clone https://github.com/brettadams0/TechNest.git

# Set proper ownership
chown -R www-data:www-data technest/
```

#### Step 2: Set Permissions
```bash
# Set directory permissions
find technest/ -type d -exec chmod 755 {} \;

# Set file permissions
find technest/ -type f -exec chmod 644 {} \;

# Set executable permissions for scripts
chmod +x technest/scripts/*.sh
```

### Method 3: Package Manager (if available)

#### Step 1: Install via Package Manager
```bash
# Example for custom package manager
sudo apt-get install technest

# Or using composer (if available)
composer create-project technest/technest
```

## Database Setup

### Step 1: Create Database
```sql
-- Connect to MySQL as root or privileged user
mysql -u root -p

-- Create database
CREATE DATABASE technest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace with your credentials)
CREATE USER 'technest_user'@'localhost' IDENTIFIED BY 'your_secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON technest.* TO 'technest_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### Step 2: Import Database Schema
```bash
# Import the database schema
mysql -u technest_user -p technest < db_schema.sql

# Import sample data (optional)
mysql -u technest_user -p technest < db_seed.sql
```

### Step 3: Verify Database Setup
```sql
-- Connect to the database
mysql -u technest_user -p technest

-- Check tables were created
SHOW TABLES;

-- Verify sample data (if imported)
SELECT COUNT(*) FROM products;
SELECT COUNT(*) FROM users;
```

## Configuration

### Step 1: Database Configuration
1. **Navigate to** `includes/config.php`
2. **Edit database settings**:
```php
<?php
// Database configuration for TechNest
define('DB_HOST', 'localhost');
define('DB_NAME', 'technest');
define('DB_USER', 'technest_user');
define('DB_PASS', 'your_secure_password');
```

### Step 2: Environment Variables (Optional)
For enhanced security, use environment variables:
```bash
# Set environment variables
export DB_HOST=localhost
export DB_NAME=technest
export DB_USER=technest_user
export DB_PASS=your_secure_password
```

### Step 3: Site Configuration
1. **Edit site settings** in `includes/config.php`:
```php
// Site configuration
define('SITE_URL', 'https://yourdomain.com');
define('SITE_NAME', 'TechNest');
define('ADMIN_EMAIL', 'admin@yourdomain.com');
```

### Step 4: Email Configuration (Optional)
If email functionality is needed:
```php
// Email configuration
define('SMTP_HOST', 'smtp.yourdomain.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@yourdomain.com');
define('SMTP_PASS', 'your_email_password');
```

## File Permissions

### Step 1: Set Directory Permissions
```bash
# Set web server ownership
chown -R www-data:www-data /var/www/html/technest/

# Set directory permissions
find /var/www/html/technest/ -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/html/technest/ -type f -exec chmod 644 {} \;
```

### Step 2: Set Special Permissions
```bash
# Make config file secure
chmod 600 /var/www/html/technest/includes/config.php

# Set upload directory permissions (if exists)
chmod 755 /var/www/html/technest/uploads/
chown www-data:www-data /var/www/html/technest/uploads/

# Set log directory permissions
chmod 755 /var/www/html/technest/logs/
chown www-data:www-data /var/www/html/technest/logs/
```

### Step 3: Verify Permissions
```bash
# Check permissions
ls -la /var/www/html/technest/
ls -la /var/www/html/technest/includes/config.php
```

## Testing Installation

### Step 1: Basic Connectivity Test
1. **Open web browser** and navigate to your site
2. **Check homepage** loads without errors
3. **Verify database connection** by viewing products
4. **Test navigation** between pages

### Step 2: Database Connection Test
1. **Navigate to** `/monitor.php`
2. **Check database status** shows "Connected"
3. **Verify PHP version** meets requirements
4. **Check server information** is displayed

### Step 3: Admin Access Test
1. **Navigate to** `/login.php`
2. **Login with admin credentials** (from db_seed.sql)
3. **Access admin panel** at `/admin/`
4. **Test admin functions** (products, users, orders)

### Step 4: User Registration Test
1. **Navigate to** `/register.php`
2. **Create a test user account**
3. **Verify account creation** in admin panel
4. **Test user login** functionality

## Post-Installation Setup

### Step 1: Create Admin Account
```sql
-- Connect to database
mysql -u technest_user -p technest

-- Create admin user (replace with your details)
INSERT INTO users (username, email, password_hash, is_active) 
VALUES ('admin', 'admin@yourdomain.com', '$2y$10$your_hashed_password', 1);

-- Get user ID
SELECT user_id FROM users WHERE username = 'admin';

-- Add admin privileges (replace USER_ID with actual ID)
INSERT INTO admin (user_id) VALUES (USER_ID);
```

### Step 2: Configure Themes
```sql
-- Insert default themes
INSERT INTO themes (name, css_file, is_active) VALUES 
('Regular', 'theme-regular.css', 1),
('Minimal', 'theme-minimal.css', 0),
('Holiday', 'theme-holiday.css', 0);
```

### Step 3: Set Up Product Categories
1. **Access admin panel**
2. **Navigate to Product Management**
3. **Add initial products** for your store
4. **Configure product options** as needed

### Step 4: Configure Email (Optional)
1. **Set up SMTP server** or use local mail
2. **Test email functionality** with order confirmations
3. **Configure email templates** for notifications

## Troubleshooting

### Common Installation Issues

#### Database Connection Errors
**Error**: "Database connection failed"
**Solutions**:
- Verify database credentials in `config.php`
- Check MySQL server is running
- Confirm database user has proper privileges
- Test connection manually: `mysql -u user -p database`

#### File Permission Errors
**Error**: "Permission denied" or blank pages
**Solutions**:
- Check file ownership: `ls -la /path/to/files`
- Set correct permissions: `chmod 755 directories`, `chmod 644 files`
- Verify web server user can read files
- Check PHP error logs for specific issues

#### PHP Version Issues
**Error**: "PHP version not supported"
**Solutions**:
- Check PHP version: `php -v`
- Upgrade PHP if below 8.0
- Install required PHP extensions
- Restart web server after PHP changes

#### Missing Extensions
**Error**: "Class 'PDO' not found"
**Solutions**:
- Install PDO extension: `apt-get install php-pdo php-mysql`
- Enable extensions in `php.ini`
- Restart web server
- Check extension status: `php -m | grep pdo`

### Performance Issues

#### Slow Page Loads
**Causes and Solutions**:
- **Database queries**: Optimize SQL queries, add indexes
- **Large images**: Compress product images
- **PHP processing**: Enable OPcache, use PHP 8.1+
- **Server resources**: Increase memory, use SSD storage

#### Memory Issues
**Solutions**:
- Increase PHP memory limit in `php.ini`
- Optimize database queries
- Use caching for frequently accessed data
- Monitor server resource usage

### Security Issues

#### SQL Injection Prevention
- Use prepared statements (already implemented)
- Validate all user inputs
- Use parameterized queries
- Regular security audits

#### XSS Prevention
- Escape all output with `htmlspecialchars()`
- Validate and sanitize user inputs
- Use Content Security Policy headers
- Regular security updates

## Security Considerations

### File Security
1. **Protect configuration files**:
```bash
chmod 600 includes/config.php
chown www-data:www-data includes/config.php
```

2. **Secure sensitive directories**:
```apache
# .htaccess protection
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>
```

### Database Security
1. **Use strong passwords** for database users
2. **Limit database privileges** to minimum required
3. **Regular database backups**
4. **Monitor database access logs**

### Server Security
1. **Keep software updated** (PHP, MySQL, web server)
2. **Use HTTPS** for all connections
3. **Implement firewall rules**
4. **Regular security scans**

### Application Security
1. **Validate all user inputs**
2. **Use prepared statements** for database queries
3. **Implement proper session management**
4. **Regular security audits**

## Backup and Recovery

### Database Backup
```bash
# Create backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u technest_user -p technest > backup_$DATE.sql

# Automated daily backup
0 2 * * * /path/to/backup_script.sh
```

### File Backup
```bash
# Backup application files
tar -czf technest_backup_$(date +%Y%m%d).tar.gz /var/www/html/technest/

# Exclude unnecessary files
tar -czf technest_backup_$(date +%Y%m%d).tar.gz \
    --exclude='*.log' \
    --exclude='cache/*' \
    /var/www/html/technest/
```

### Recovery Procedures
1. **Stop web server** during recovery
2. **Restore database** from backup
3. **Restore application files**
4. **Verify permissions** are correct
5. **Test functionality** before going live

## Maintenance

### Regular Maintenance Tasks
- **Daily**: Check error logs, backup database
- **Weekly**: Review security logs, update software
- **Monthly**: Performance optimization, security audit
- **Quarterly**: Full system backup, disaster recovery test

### Monitoring
- **Server resources**: CPU, memory, disk usage
- **Application performance**: Response times, error rates
- **Security events**: Failed login attempts, suspicious activity
- **Database performance**: Query times, connection counts

---

**Need Help?** If you encounter issues during installation, check the troubleshooting section above or contact me at adamsbrett00@gmail.com. 
