-- Initialize multiple databases for Syntax multi-application ecosystem
CREATE DATABASE IF NOT EXISTS `db_cms` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `db_school` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant permissions to syntax user
GRANT ALL PRIVILEGES ON `db_cms`.* TO 'syntax_user'@'%';
GRANT ALL PRIVILEGES ON `db_school`.* TO 'syntax_user'@'%';
FLUSH PRIVILEGES;
