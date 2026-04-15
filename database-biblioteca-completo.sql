-- Database for Library Management System

CREATE DATABASE biblioteca;
USE biblioteca;

-- Table for storing information about books
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(13) UNIQUE NOT NULL,
    published_year YEAR,
    genre VARCHAR(100),
    copies_available INT DEFAULT 0
);

-- Table for storing information about members
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(15),
    address VARCHAR(255),
    joined_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table for storing information about loans
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    member_id INT NOT NULL,
    loan_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    return_date DATETIME,
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- Table for storing returns
CREATE TABLE returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    return_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id)
);