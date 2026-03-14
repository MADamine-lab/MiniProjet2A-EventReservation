CREATE DATABASE IF NOT EXISTS event_reservation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE event_reservation;

-- Table events
CREATE TABLE IF NOT EXISTS `event` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NOT NULL,
    `date` DATETIME NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `seats` INT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table reservations
CREATE TABLE IF NOT EXISTS `reservation` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `event`(`id`) ON DELETE CASCADE
);

-- Table users
CREATE TABLE IF NOT EXISTS `user` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(180) NOT NULL UNIQUE,
    `roles` JSON NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `passkey_credential_id` VARCHAR(255) DEFAULT NULL,
    `passkey_public_key` LONGTEXT DEFAULT NULL,
    `passkey_counter` INT DEFAULT 0
);

-- Insert demo admin
INSERT INTO `user` (`username`, `roles`, `password_hash`) VALUES 
('admin', '["ROLE_ADMIN"]', '$2y$13$H.8OfpJiMDq/Hn3F3RLlguGfMkBKfpVMpuFovfK5Z8jvTaIe1c/Ey'),
('user1', '["ROLE_USER"]', '$2y$13$H.8OfpJiMDq/Hn3F3RLlguGfMkBKfpVMpuFovfK5Z8jvTaIe1c/Ey');
-- Default password: password123

-- Insert sample events
INSERT INTO `event` (`title`, `description`, `date`, `location`, `seats`) VALUES
('Conférence Tech 2026', 'Une conférence sur les dernières innovations technologiques incluant l''IA, le Cloud et la Cybersécurité.', '2026-04-15 09:00:00', 'Palais des Congrès, Tunis', 200),
('Hackathon National', 'Participez au plus grand hackathon de Tunisie. 48h pour créer des solutions innovantes.', '2026-04-20 08:00:00', 'ISSAT Sousse', 100),
('Workshop Symfony & Docker', 'Formation intensive sur Symfony 6 et la conteneurisation avec Docker.', '2026-05-05 14:00:00', 'ISSAT Sousse - Salle Info', 30),
('Startup Weekend Sousse', 'Weekend immersif pour entrepreneurs. Pitchez votre idée et formez votre équipe.', '2026-05-15 18:00:00', 'Hôtel Sousse Palace', 150);
