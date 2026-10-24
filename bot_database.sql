CREATE DATABASE IF NOT EXISTS aculearn_bot_database
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE aculearn_bot_database;

CREATE TABLE IF NOT EXISTS users (
    chat_id BIGINT PRIMARY KEY,
    first_name VARCHAR(255) DEFAULT NULL,
    last_name VARCHAR(255) DEFAULT NULL,
    username VARCHAR(255) DEFAULT NULL,
    language_code VARCHAR(10) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_states (
    chat_id BIGINT PRIMARY KEY,
    state VARCHAR(64) NOT NULL DEFAULT 'new_user',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_states_user
        FOREIGN KEY (chat_id) REFERENCES users(chat_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id BIGINT NOT NULL,
    message_text TEXT NOT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_messages_received (received_at),
    INDEX idx_messages_chat_received (chat_id, received_at),
    CONSTRAINT fk_messages_user
        FOREIGN KEY (chat_id) REFERENCES users(chat_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Additive migration; run once before deploying the updated webhook.
CREATE TABLE IF NOT EXISTS update_receipts (
    update_id BIGINT PRIMARY KEY,
    processed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_receipts_processed (processed_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Supports global message retention without scanning the chat-first index.
-- Run this only if SHOW INDEX FROM messages does not already list this name.
-- CREATE INDEX idx_messages_received ON messages (received_at);
