-- Create orders table if not exists
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date DATETIME NOT NULL,
    order_status VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    postcode VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    company VARCHAR(100),
    payment_method VARCHAR(50) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    shipping DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create order_items table if not exists
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Alter users table to add necessary fields if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER email,
ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER first_name,
ADD COLUMN IF NOT EXISTS address TEXT AFTER last_name,
ADD COLUMN IF NOT EXISTS city VARCHAR(100) AFTER address,
ADD COLUMN IF NOT EXISTS state VARCHAR(100) AFTER city,
ADD COLUMN IF NOT EXISTS postcode VARCHAR(20) AFTER state,
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) AFTER postcode,
ADD COLUMN IF NOT EXISTS company VARCHAR(100) AFTER phone;

-- Make sure books table has stock field
ALTER TABLE books 
ADD COLUMN IF NOT EXISTS stock INT DEFAULT 10 AFTER price;
