DhakaMeal - Online Food Delivery System
Welcome to DhakaMeal, a web-based food delivery application built to streamline the ordering process for customers, manage orders for restaurants, and coordinate deliveries. This project is designed to provide a seamless experience for users in the Dhaka region, featuring a responsive interface and essential functionalities for a food delivery ecosystem.
Features

Customer Features:
Browse and order food items from the DhakaMeal restaurant.
View cart and place orders with cash-on-delivery (COD) option.
Track order status and review delivered orders anonymously.


Restaurant Features:
Login to manage orders (accept/reject pending orders).
View order details and update statuses.


Delivery Person Features:
Accept and manage assigned deliveries.
Update delivery statuses (e.g., picked up, out for delivery, delivered).


Technologies Used

Backend: PHP with PDO for database interactions.
Frontend: HTML, CSS (Tailwind CSS), JavaScript.
Database: MySQL (using a local dhakameal database).
Server: Tested with XAMPP (Apache/MySQL).
Other: Local image handling for food items.

Installation
Prerequisites

Web server (e.g., XAMPP with Apache and MySQL).
PHP 7.4 or higher.
MySQL 5.7 or higher.

Steps

Clone the Repository:
git clone https://github.com/your-username/dhakameal.git
cd dhakameal


Set Up the Database:

Import the database.sql file into your MySQL database:
mysql -u root < database.sql


Update config/database.php with your database credentials if different from the default (root with no password).



Place Images:

Copy the food images (chicken_burger.jpg, vegetable_samosa.jpg, chicken_biryani.jpg, paneer_tikka.jpg, rasgulla.jpg) to the images/ directory.


Start the Server:

Launch XAMPP and start Apache and MySQL.
Access the application at http://localhost/dhakameal/.


Test Accounts:

Customer: customer1@example.com / customer123
Restaurant: restaurant1@example.com / restaurant123
Delivery Person: delivery1@example.com / delivery123



Usage

Customer: Navigate to index.php to browse food, add to cart, and place orders. Check order status and reviews on order-details.php.
Restaurant: Log in and use restaurant-dashboard.php to manage orders.
Delivery Person: Log in and use delivery-dashboard.php to manage deliveries.

File Structure
dhakameal/
├── config/            # Database configuration
├── includes/          # Header and footer templates
├── images/            # Food item images
├── css/               # Custom CSS (if any)
├── js/                # JavaScript files (if any)
├── database.sql       # SQL dump for database setup
├── index.php          # Homepage
├── login.php          # Login page
├── register.php       # Registration page
├── cart.php           # Cart and order placement
├── order-details.php  # Order history and reviews
├── restaurant-dashboard.php  # Restaurant dashboard
├── delivery-dashboard.php    # Delivery dashboard
├── README.md          # This file
└── ...                # Other PHP files

Contributing

Fork the repository.
Create a feature branch (git checkout -b feature/AmazingFeature).
Commit changes (git commit -m 'Add some AmazingFeature').
Push to the branch (git push origin feature/AmazingFeature).
Open a Pull Request.

License
This project is licensed under the MIT License - see the LICENSE.md file for details.
Contact
For issues or suggestions, please open an issue on GitHub or contact the maintainer at your-email@example.com (replace with your email).
Acknowledgments

Inspired by the need for a local food delivery solution in Dhaka.
Thanks to the open-source community for Tailwind CSS and other tools.

