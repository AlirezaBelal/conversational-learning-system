CREATE DATABASE
IF
  NOT EXISTS aculearn_bot_database;
USE aculearn_bot_database;-- users table
CREATE TABLE
    IF
    NOT EXISTS users (
                         chat_id BIGINT PRIMARY KEY,
                         first_name VARCHAR (255) DEFAULT NULL,
    last_name VARCHAR (255) DEFAULT NULL,
    username VARCHAR (255) DEFAULT NULL,
    language_code VARCHAR (10) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) CHARACTER
    SET utf8mb4 COLLATE utf8mb4_unicode_ci;-- messages table
CREATE TABLE
    IF
    NOT EXISTS messages (id INT AUTO_INCREMENT PRIMARY KEY, chat_id BIGINT NOT NULL, message_text TEXT NOT NULL, received_at DATETIME DEFAULT CURRENT_TIMESTAMP) CHARACTER
    SET utf8mb4 COLLATE utf8mb4_unicode_ci;-- user_states table
CREATE TABLE
    IF
    NOT EXISTS user_states (
                               id INT AUTO_INCREMENT PRIMARY KEY,
                               chat_id BIGINT NOT NULL UNIQUE,
                               state VARCHAR (255) DEFAULT NULL,
    FOREIGN KEY (chat_id) REFERENCES users (chat_id) ON DELETE CASCADE) CHARACTER
    SET utf8mb4 COLLATE utf8mb4_unicode_ci;