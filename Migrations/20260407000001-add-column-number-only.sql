ALTER TABLE master_file_text ADD COLUMN mftxNumber VARCHAR(1000) NOT NULL DEFAULT '' AFTER mftxText;
CREATE INDEX idx_mftxText ON master_file_text (mftxText);
CREATE INDEX idx_mftxNumber ON master_file_text (mftxNumber);