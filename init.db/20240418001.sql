ALTER TABLE illusts
ADD COLUMN aHash TEXT,
ADD COLUMN pHash TEXT,
ADD COLUMN dHash TEXT, 
ADD COLUMN colorHash TEXT;

CREATE INDEX illusts_hash_aHash ON illusts(aHash);
CREATE INDEX illusts_hash_pHash ON illusts(pHash);
CREATE INDEX illusts_hash_dHash ON illusts(dHash);
CREATE INDEX illusts_hash_colorHash ON illusts(colorHash);
