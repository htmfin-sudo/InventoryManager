# StockFlow – InventoryManager

A web-based inventory management application developed as a university project.

**Author:** Fineas Hotima  
**University:** Politehnica University of Timișoara  
**Repository:** https://github.com/htmfin-sudo/InventoryManager

## Overview

StockFlow helps manage products, categories, suppliers, inventory transactions, users, and reports. It includes a dashboard with inventory statistics and charts, role-based access, CSV exports, and a read-only REST API.

## Technologies

- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript, Chart.js
- **Backend:** PHP and PDO
- **Database:** MySQL / MariaDB
- **Development environment:** XAMPP, Visual Studio Code
- **Version control:** Git and GitHub

## Features

### Dashboard
- Product, category, supplier, and stock-movement statistics
- Current stock quantities and low-stock warnings
- Total inventory value
- Interactive charts

### Inventory management
- Create, view, update, and delete products
- Manage categories and suppliers
- Search and filter products
- Record stock entries (`IN`) and withdrawals (`OUT`)
- Prevent withdrawals that exceed available stock
- View stock-movement history and the responsible user

### Accounts and access control
- Login and logout
- Administrator and Employee roles
- Administrator management of employee accounts
- Account activation/deactivation
- Password changes for authenticated users

**Administrators** can manage products, categories, suppliers, users, and reports. **Employees** can view products and record stock movements, subject to the application's access rules.

### Reports and exports
- Filter inventory and stock-movement reports
- Download product and movement reports as CSV files
- Print reports or save them as PDF using the browser's print dialog

**Excel note:** CSV files use UTF-8 and a semicolon (`;`) delimiter. Depending on regional Excel settings, manual import may be needed. See **[README_EXCEL.md](README_EXCEL.md)** for illustrated-by-steps instructions.

### REST API

The application includes a session-protected, read-only JSON API:

`GET /InventoryManager/api/products.php`

Examples:

```text
http://localhost/InventoryManager/api/products.php
http://localhost/InventoryManager/api/products.php?q=Laptop
http://localhost/InventoryManager/api/products.php?stock=low
http://localhost/InventoryManager/api/products.php?id=1
```

Available filters include product name, category, stock status, and result limit. Authentication is required; the API does not provide create, update, or delete operations.

See **[docs/API.md](docs/API.md)** for the API reference.

## Database

The `inventory_manager` database contains five related tables:

| Table | Purpose |
| --- | --- |
| `users` | Accounts, roles, and account status |
| `categories` | Product categories |
| `suppliers` | Product suppliers |
| `products` | Product information and stock thresholds |
| `stock_movements` | Inventory entries and withdrawals |

SQL schema: **[sql/schema.sql](sql/schema.sql)**.

## Local setup with XAMPP

1. Install XAMPP and start **Apache** and **MySQL**.
2. Copy or clone the repository into `C:\xampp\htdocs\InventoryManager`.
3. Open phpMyAdmin and import `sql/schema.sql` to create the database and tables.
4. Copy `config/db.example.php` to `config/db.php`.
5. Adjust the local database connection settings in `config/db.php`.
6. Provision an initial administrator account locally, using a password hash created by PHP's `password_hash()` function. The repository does not include default credentials.
7. Open `http://localhost/InventoryManager/login.php` and sign in.

**Security:** Keep `config/db.php` and any credentials out of Git. Do not publish development passwords or the application on a public server without additional security review.

## Project documentation

- [CSV and Microsoft Excel guide](README_EXCEL.md)
- [AI development journal](docs/JURNAL_AI.md)
- [REST API documentation](docs/API.md)
- [Database schema](sql/schema.sql)

## Testing and limitations

The application was developed and manually tested in a local XAMPP environment. Important checks include CRUD operations, stock calculations, user permissions, JSON responses, CSV contents, and browser PDF printing.

This is a **university demonstration project**, not a production-certified inventory platform. Responsive behavior across all devices and a full security audit have not been independently certified.

## AI assistance

ChatGPT was used to assist with planning, code generation, debugging, and documentation. The development process, example prompts, results, and verification steps are recorded in [docs/JURNAL_AI.md](docs/JURNAL_AI.md).
