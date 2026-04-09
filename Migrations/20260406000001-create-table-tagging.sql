CREATE TABLE IF NOT EXISTS master_file_tagging (
    `mftgId` bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `mftgMfilId` bigint UNSIGNED NOT NULL,
    `mftgFotografer` varchar(1000) NOT NULL DEFAULT '',
    `mftgEventName` varchar(1000) NOT NULL DEFAULT '',
    `mftgPhotoLocation` varchar(1000) NOT NULL DEFAULT '',
    `mftgUrl` varchar(2000) NOT NULL DEFAULT '',
    `mftgPhotoDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `mftgMeta` text,
    `mftgPeople` text,
    `mftgIsPublished` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '0:unpublished, 1:published',
    `mftgCreatedTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `mftgUpdatedTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS master_file_text (
    `mftxId` bigint UNSIGNED NOT NULL,
    `mftxText` varchar(1000) NOT NULL DEFAULT '',
    `mftxCreatedTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `mftxUpdatedTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE master_file_text ADD CONSTRAINT `fk_master_file_text_mftxId` FOREIGN KEY (`mftxId`) REFERENCES master_file_tagging(`mftgId`) ON DELETE CASCADE ON UPDATE CASCADE;