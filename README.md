# Shantini Crackers

A premium fireworks catalog and estimate-request application built with PHP and MySQL.

---

## 💻 1. Local Development Setup (XAMPP)

1. **Clone the repository** into your `C:\xampp\htdocs\` folder.
2. **Start Apache and MySQL** in the XAMPP Control Panel.
3. **Database Setup**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`).
   - Create a new database.
   - Import your local `.sql` database export.
4. **Configuration files**:
   - The code automatically uses local XAMPP defaults if no `config/db.php` is found.
   - To send emails locally, ensure you have created `config/smtp.local.php` (this file is ignored by Git for security) and added your Gmail App Password.
5. **Access the site** at `http://localhost/fireworks-php`.

---

## 🚀 2. Live Server Deployment (Hostinger)

This project uses **Clean URLs** and secure configuration files that are hidden from GitHub. Follow these exact steps to deploy to a live server like Hostinger:

### Step A: Deploy the Code from GitHub
1. Log into your **Hostinger hPanel**.
2. Navigate to **Advanced** -> **Git**.
3. Select your GitHub repository (`ananthavalli0707-rgb/shantinicrackers.shop`).
4. Click **Deploy**. Hostinger will pull the latest code from the `main` branch.

### Step B: Create the Missing Configuration Files
Because passwords should **never** be uploaded to GitHub, your Git repository ignores local configuration files. You must create them manually in Hostinger's File Manager!

1. In Hostinger, open the **File Manager** and navigate to your `public_html/config/` folder.
2. **Create `db.php`** for database connection:
   ```php
   <?php
   return [
       'host' => 'localhost',
       'dbname' => 'your_hostinger_database_name',
       'user' => 'your_hostinger_database_user',
       'password' => 'your_database_password'
   ];
   ```
3. **Create `smtp.local.php`** for sending emails:
   ```php
   <?php
   return [
       'username' => 'shantinicrackerssivakasi@gmail.com',
       'password' => 'your_16_letter_gmail_app_password',
       'from_email' => 'shantinicrackerssivakasi@gmail.com',
   ];
   ```

### Step C: Setup the Live Database
1. Go to **Databases** -> **Management** in Hostinger.
2. Create a new MySQL database and user.
3. Enter phpMyAdmin in Hostinger.
4. **Import** the `.sql` export file from your local XAMPP database.

---

## 🛠️ 3. Important Notes

- **Clean URLs**: The `.htaccess` file hides `index.php?page=` from the address bar, so links look like `/products` instead of `/index.php?page=products`. Do not delete the `.htaccess` file.
- **Admin Panel**: The admin login is located at `/admin`.
- **Security**: Never upload `.env`, `db.php`, or `smtp.local.php` to GitHub. The `.gitignore` file is specifically designed to block them.
