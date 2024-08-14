UPDATE illusts
SET aHash = CONV(HEX(aHash), 16, 10)
WHERE aHash IS NOT NULL;

UPDATE illusts
SET dHash = CONV(HEX(dHash), 16, 10)
WHERE dHash IS NOT NULL;

UPDATE illusts
SET pHash = CONV(HEX(pHash), 16, 10)
WHERE pHash IS NOT NULL;

UPDATE illusts
SET colorHash = CONV(HEX(colorHash), 16, 10)
WHERE colorHash IS NOT NULL;
