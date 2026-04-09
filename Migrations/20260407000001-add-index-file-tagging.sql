ALTER TABLE master_file_tagging ADD FULLTEXT INDEX idx_fulltext_mftgEventName (mftgEventName);
ALTER TABLE master_file_tagging ADD FULLTEXT INDEX idx_fulltext_mftgPhotoLocation (mftgPhotoLocation);