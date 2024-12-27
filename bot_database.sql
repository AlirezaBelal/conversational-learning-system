CREATE DATABASE
IF
  NOT EXISTS aculearn_bot_database;
USE aculearn_bot_database;
CREATE TABLE
    IF
    NOT EXISTS users (
                         user_id BIGINT NOT NULL PRIMARY KEY,
                         user_number_id BIGINT NOT NULL,
                         username VARCHAR (255) DEFAULT NULL,
    phone_number VARCHAR (20) DEFAULT NULL,
    UNIQUE (user_number_id)) CHARACTER
    SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE
    IF
    NOT EXISTS messages (id INT AUTO_INCREMENT PRIMARY KEY, chat_id BIGINT NOT NULL, message_text TEXT NOT NULL, received_at DATETIME DEFAULT CURRENT_TIMESTAMP) CHARACTER
    SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE
    IF
    NOT EXISTS support_requests (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    chat_id BIGINT NOT NULL,
                                    STATUS ENUM ('awaiting_message', 'completed') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) CHARACTER
    SET utf8mb4 COLLATE utf8mb4_unicode_ci;