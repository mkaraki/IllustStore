CREATE TABLE metadata_provider(
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name TEXT NOT NULL,
    pathPattern TEXT NOT NULL,
    providerUrlReplacement TEXT NOT NULL,
    apiUrlReplacement TEXT,
    sourceUrlReplacement TEXT
);