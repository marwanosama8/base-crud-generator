# 🛠️ Base CRUD Generator for Kabret

A powerful Laravel package by **Kabret** that helps you quickly scaffold fully functional **CRUD (Create, Read, Update, Delete)** operations for your admin dashboard — all with one command.

---

## 🚀 Features

- ✅ **Full CRUD Generation** – Controller, model, views, requests, migration, repository
- 📚 **Repository Pattern** – Clean architecture using interfaces and implementations
- 🛡 **Validation** – Auto-generates Store & Update Form Request classes
- 🗃 **Soft Delete & Archive** – Includes archive, restore, force delete operations
- 🔁 **Status Toggle** – Adds `changeActive` method to switch status
- 🎨 **Blade Views** – Standard, extendable view stubs
- 🧭 **Automatic Routing** – Adds routes in `routes/admin.php`
- 🧩 **Customizable Stubs** – Modify templates to fit your project structure

---

## 📦 Installation

Follow these steps to install and configure the package:

### 1. Add the Package Repository

First, you need to add the package repository to your `composer.json` file. Open `composer.json` in your project root and add the following under the `"repositories"` key (create this key if it doesn't exist):

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/marwanosama8/base-crud-generator"
    }
],
```

### 2. Require the Package

Add the package to your require-dev section in composer.json:

```json
"require-dev": {
    "marwanosama8/base-crud-generator": "dev-main"
}
```

### 3. Update Dependencies

Run the following command to update your dependencies:

```bash
composer update
```

### 4. Run the package command

After the success installation of the package, you can use the main command:
```bash
php artisan make:crud Product dashboard
```
the Product is the model name, and the dashboard is the namespace of the parent Directory

### 5. Run Migrations

After run the generate CRUD command, run your database migrations:

```bash
php artisan migrate
```
