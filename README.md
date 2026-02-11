# Direct Alert Project

This project is a Laravel application for managing direct alert information, including a database model, history table, and an admin export feature.

## Deployment to cPanel

Deploying a Laravel application to cPanel involves several steps to ensure both PHP dependencies and frontend assets are correctly set up, and the web server points to the correct directory.

Here's a general guide:

1.  **Upload Project Files:**
    *   Compress your project directory (excluding `node_modules` and `vendor` directories, as these will be installed on the server).
    *   Upload the compressed file to your cPanel file manager or via FTP/SFTP.
    *   Extract the files into the desired location (e.g., within your `home` directory, outside the `public_html` folder for security).

2.  **Set up the `.env` file:**
    *   Copy the `.env.example` file in your project root and rename it to `.env`.
    *   Edit the `.env` file to configure your database connection details (`DB_*` variables), `APP_URL`, `APP_KEY`, and any other environment-specific settings.
    *   You can generate a new `APP_KEY` using Artisan, but you'll need SSH access for that. If you don't have SSH, you can generate one locally (`php artisan key:generate --show`) and paste it into the `.env` file.

3.  **Install PHP Dependencies (Composer):**
    *   cPanel often provides a "Composer" tool in the "Software" section. Use this to navigate to your project root directory on the server and run `composer install --no-dev`. This will install all the necessary PHP packages.
    *   If you have SSH access, you can simply SSH into your account, navigate to the project root, and run `composer install --no-dev`.

4.  **Install Frontend Dependencies and Build Assets (NPM/Yarn/Bun & Vite):**
    *   cPanel might have a "Node.js" or "Setup Node.js App" tool. Use this to set up a Node.js environment for your project.
    *   Use the terminal within the Node.js tool (or SSH) to navigate to your project root.
    *   Install JavaScript dependencies: `npm install` (or `yarn install` or `bun install` depending on your project's lock file).
    *   Build the frontend assets: `npm run build` (or `yarn build` or `bun build`). This compiles CSS and JavaScript using Vite and places them in the `public` directory.
    *   Alternatively, you can use the `npm run zip` command to build the project and create a `dist.zip` file. This file will include the necessary `public` assets, `vendor` directory, and `.env` file for deployment.

5.  **Run Database Migrations:**
    *   If you have SSH access, navigate to your project root and run `php artisan migrate`.
    *   If you don't have SSH, some cPanel setups allow running Artisan commands via a web interface or a custom script. Alternatively, you might need to use a tool like phpMyAdmin to manually create the tables based on the migration files (though this is less recommended).

6.  **Configure Web Server Document Root:**
    *   This is a crucial step. You need to tell cPanel's web server (Apache or Nginx) to point the domain or subdomain to the `public` directory inside your project folder, not the project root itself.
    *   In cPanel, this is usually done in the "Domains" or "Subdomains" section. Edit the domain/subdomain and change the "Document Root" to point to `/path/to/your/project/public`.

7.  **Verify `.htaccess`:**
    *   Laravel includes a `public/.htaccess` file which is usually sufficient for routing. Ensure this file is present in your `public` directory on the server.

8.  **Set Directory Permissions:**
    *   Ensure that the `storage` and `bootstrap/cache` directories within your project have write permissions for the web server user. You can usually do this via the cPanel File Manager by selecting the folders and changing permissions (often to 755 or 775, but consult your host's documentation).

After completing these steps, your Laravel application should be accessible via your domain, and the `/admin/export` page and `/api/dump/csv` endpoint should be functional.
