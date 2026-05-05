CREATE TABLE IF NOT EXISTS `client_allowed_origins` (
    `claoId` INT AUTO_INCREMENT PRIMARY KEY,
    `claoClientId` VARCHAR(255) NOT NULL,
    `claoOrigin` VARCHAR(255) NOT NULL,
    `claoCreatedTime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `claoUpdatedTime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `client_origin_unique` (`claoClientId`, `claoOrigin`)
);