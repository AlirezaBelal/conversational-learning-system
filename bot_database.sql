CREATE DATABASE
IF
  NOT EXISTS bot_database;
USE bot_database;
CREATE TABLE
    IF
    NOT EXISTS users (
                         user_id BIGINT NOT NULL PRIMARY KEY,
                         user_number_id BIGINT NOT NULL,
                         username VARCHAR (255) DEFAULT NULL,
    phone_number VARCHAR (20) DEFAULT NULL,
    UNIQUE (user_number_id)
    );
CREATE TABLE
    IF
    NOT EXISTS messages (id INT AUTO_INCREMENT PRIMARY KEY,
                         chat_id BIGINT NOT NULL,
                         message_text TEXT NOT NULL,
                         received_at DATETIME DEFAULT CURRENT_TIMESTAMP
);