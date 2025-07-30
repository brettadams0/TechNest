# TechNest Installation Guide

## Requirements
- PHP 8.0+
- MySQL 8.0+
- Web server (Apache recommended)
- Composer (optional, if you add packages)

## 1. Clone the Repository
```
git clone <your-repo-url>
cd project
```

## 2. Database Setup
- Create a MySQL database (e.g., `technest`).
- Import the schema and seed data:
```
mysql -u <user> -p technest < db_schema.sql
mysql -u <user> -p technest < db_seed.sql
```

## 3. Configure Database Connection
- Edit `config.php` and set your DB credentials:
```
define('DB_HOST', 'localhost');
define('DB_NAME', 'technest');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

## 4. Set File Permissions
- Ensure the web server can read all files.
- If uploading images, ensure the upload directory is writable.

## 5. Deploy to Hosting
- Upload all files to your web root (e.g., `public_html` or `www`).
- Make sure `.php`, `.css`, `.js`, and asset files are present.
- Place `favicon.ico` in the root directory.

## 6. Access the Site
- Visit your site URL (e.g., `https://myweb.cs.uwindsor.ca/~yourid/project/`).
- Log in as admin (see `db_seed.sql` for credentials, set your own password hash).

## 7. (Optional) Enable HTTPS
- Use your host's SSL tools to enable HTTPS for security.

## 8. Troubleshooting
- Check `config.php` for DB errors.
- Use `monitor.php` to check DB and server status.

---
For updates, see the Help Wiki or contact your instructor/support. 