# Bookstore Application

## Overview
This is a bookstore application built using the XAMPP stack (Apache, MySQL, PHP). The application allows users to browse books, make purchases, and manage inventory.

## Features
- User authentication and authorization
- Book browsing and searching
- Shopping cart functionality
- Order processing
- Admin panel for inventory management

## Installation
1. Make sure you have XAMPP installed on your system
2. Clone this repository to your htdocs folder
3. Import the database using the provided SQL file
4. Configure the database connection in the config file

## Configuration
Update the database connection settings in `/config/db_connect.php`:

```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookstore";
```

## Usage
1. Start Apache and MySQL from the XAMPP control panel
2. Navigate to `http://localhost/Bookstore/` in your web browser
3. Log in or create a new account to start using the application

## Project Structure
- `/assets` - CSS, JavaScript, and images
- `/config` - Configuration files
- `/includes` - Reusable PHP components
- `/models` - Database models
- `/views` - UI templates

## License
This project is licensed under the MIT License - see the LICENSE file for details.