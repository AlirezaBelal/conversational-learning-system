-- Additive migration; run once before deploying the updated webhook.
CREATE TABLE IF NOT EXISTS update_receipts (
    update_id BIGINT PRIMARY KEY,
    processed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_receipts_processed (processed_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Supports global message retention without scanning the chat-first index.
-- Run this only if SHOW INDEX FROM messages does not already list this name.
-- CREATE INDEX idx_messages_received ON messages (received_at);
