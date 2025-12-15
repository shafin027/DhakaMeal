# 🍔 DhakaMeal

![PHP](https://img.shields.io/badge/Backend-PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Styling-Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Status](https://img.shields.io/badge/Status-Active-success?style=for-the-badge)

> **Overview:** DhakaMeal is a comprehensive food delivery web application connecting hungry customers with local restaurants and delivery personnel. It features a complete ecosystem with distinct dashboards for managing orders, menus, and deliveries in real-time.

---

## 📑 Table of Contents

* [✨ Key Features](#-key-features)
* [👥 User Roles](#-user-roles)
* [📸 App Screenshots](#-app-screenshots)
* [🛠️ Tech Stack](#-tech-stack)
* [🔐 Demo Credentials](#-demo-credentials)
* [🚀 Installation & Setup](#-installation--setup)

---

## ✨ Key Features

* **🔐 Multi-Role Authentication:** Secure login system for Customers, Restaurants, and Delivery Riders.
* **🛒 Dynamic Cart System:** Add items, view summaries, and checkout seamlessly.
* **📦 Order Management:** Real-time tracking of order status (Pending → Cooking → On the Way → Delivered).
* **🍽️ Restaurant Dashboard:** Manage food items, stock availability, and accept/reject incoming orders.
* **🛵 Delivery Hub:** Riders can view assigned tasks and manage delivery routes.
* **👤 Profile Management:** Users can update addresses, upload profile pictures, and manage security settings.
* **📱 Responsive Design:** Fully optimized for Desktop, Tablet, and Mobile devices using Tailwind CSS.

---

## 👥 User Roles

| Role | Responsibilities |
| :--- | :--- |
| **Customer** | Browse menus, add to cart, place orders, write reviews. |
| **Restaurant** | Manage menu (CRUD), update stock, process orders. |
| **Delivery Man** | View delivery details, update delivery status. |

---

## 📸 App Screenshots

> *Note: These images showcase the live application interface.*

| **Home Page** | **Restaurant Dashboard** |
| :---: | :---: |
| ![Home Page](screenshots/homepage.png) | ![Restaurant Dashboard](screenshots/Restaurant%20Dashboard.png) |
| **Add Food Item** | **Delivery Dashboard** |
| ![Add Food](screenshots/Restaurant%20Add%20Food%20Item.png) | ![Delivery](screenshots/Delivery%20Person%20Dashboard.png) |

---

## 🛠️ Tech Stack

| Component | Technology | Description |
| :--- | :--- | :--- |
| **Backend** | `PHP (Native)` | Server-side logic and session management. |
| **Database** | `MySQL` | Relational database for users, orders, and menus. |
| **Frontend** | `HTML5`, `Tailwind CSS` | Modern, responsive UI structure and styling. |
| **Scripting** | `JavaScript` | Dynamic interactions and AJAX requests. |

---

## 🔐 Demo Credentials

You can use these pre-configured accounts to explore the different dashboards:

| User Type | Email | Password |
| :--- | :--- | :--- |
| **Customer** | `customer@example.com` | `password123` |
| **Restaurant** | `restaurant@example.com` | `password123` |
| **Delivery** | `delivery@example.com` | `password123` |

---

## 🚀 Installation & Setup

Follow these steps to run the project locally on your machine.

### 1. Prerequisites
Ensure you have a local server environment installed (e.g., **XAMPP**, **MAMP**, or **WAMP**).

### 2. Clone the Repository

git clone [https://github.com/shafin027/DhakaMeal.git](https://github.com/shafin027/DhakaMeal.git)

⚙️ Configuration
Update the database connection settings in includes/db_connect.php:

PHP

<?php
$host = 'localhost';
$db   = 'edumatrix';
$user = 'root';      // Default XAMPP username
$pass = '';          // Default XAMPP password is empty

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
📂 File Structure
Plaintext

edumatrix/
├── includes/
│   ├── db_connect.php     # Database connection
│   ├── header.php         # Navbar & CSS links
│   └── footer.php         # Copyright & Scripts
├── uploads/               # Stores course & category images
├── programs.php           # Main page displaying categories
├── category_courses.php   # Displays courses inside a category
└── README.md              # Project documentation
🚀 Getting Started
Clone the Repository:

Bash

git clone [https://github.com/shafin027/EduMatriix.git](https://github.com/shafin027/EduMatriix.git)
Move Files: Place the project folder inside your server's root directory (e.g., htdocs for XAMPP).

Start Server: Launch Apache and MySQL via your control panel.

Launch App: Visit http://localhost/edumatrix/programs.php in your browser.

🛡️ Security Note
This project uses MySQLi Prepared Statements (assumed in implementation) to prevent SQL injection. For a live production environment, ensure input validation is strictly enforced.

<div align="center">

**EduMatrix** | Developed by [Shafin027](https://github.com/shafin027)

</div>
