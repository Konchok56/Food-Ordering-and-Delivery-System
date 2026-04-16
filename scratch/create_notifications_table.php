<?php
/**
 * Migration: Create the notifications table.
 * Run this once via browser:  /food/swiftbite_php_starter/scratch/create_notifications_table.php
 */
include('../core/db.php');

$sql = "
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        VARCHAR(50) NOT NULL DEFAULT 'info',
    title       VARCHAR(255) NOT NULL,
    message     TEXT NOT NULL,
    icon        VARCHAR(10) DEFAULT '🔔',
    image_path  VARCHAR(500) DEFAULT NULL,
    link        VARCHAR(500) DEFAULT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_user_created (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql);
    echo "<div style='font-family:monospace;padding:40px;text-align:center;'>";
    echo "<h2 style='color:#1a7a34;'>✅ notifications table created successfully!</h2>";
    echo "<p><a href='../index.php'>← Back to SwiftBite</a></p>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div style='font-family:monospace;padding:40px;color:red;text-align:center;'>";
    echo "<h2>❌ Error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
