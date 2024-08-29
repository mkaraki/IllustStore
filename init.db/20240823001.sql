CREATE TABLE tagGroups(
                          id INT PRIMARY KEY NOT NULL,
                          name TEXT NOT NULL,
                          description MEDIUMTEXT,
                          taggingNote LONGTEXT,
                          parentId INT,
                          FOREIGN KEY (parentId) REFERENCES tagGroups(id)
);

CREATE TABLE selectiveTagGroups(
                                   id INT PRIMARY KEY NOT NULL,
                                   name TEXT NOT NULL,
                                   description MEDIUMTEXT,
                                   taggingNote LONGTEXT,
                                   parentTagGroupId INT,
                                   FOREIGN KEY (parentTagGroupId) REFERENCES tagGroups(id)
);

ALTER TABLE tagAssign
    ADD notSure BOOLEAN DEFAULT false;

ALTER TABLE tags
    ADD (
        shortDescription TEXT,
        description MEDIUMTEXT,
        taggingNote LONGTEXT,
        aliasOf INT,
        tagGroup INT,
        selectiveTagGroup INT,
        FOREIGN KEY (aliasOf) REFERENCES tags(id),
        FOREIGN KEY (tagGroup) REFERENCES tagGroups(id),
        FOREIGN KEY (selectiveTagGroup) REFERENCES selectiveTagGroups(id)
    );

CREATE TABLE tagDependRelation(
    tagId INT NOT NULL,
    dependTagId INT NOT NULL,
    FOREIGN KEY (tagId) REFERENCES tags(id),
    FOREIGN KEY (dependTagId) REFERENCES tags(id),
    PRIMARY KEY (tagId, dependTagId)
);

CREATE TABLE tagRelatedRelation(
                                  tagId INT NOT NULL,
                                  relatedTagId INT NOT NULL,
                                  FOREIGN KEY (tagId) REFERENCES tags(id),
                                  FOREIGN KEY (relatedTagId) REFERENCES tags(id),
                                  PRIMARY KEY (tagId, relatedTagId)
);

CREATE TABLE tagCantWithRelation(
                                   tagId INT NOT NULL,
                                   cantWithTagId INT NOT NULL,
                                   FOREIGN KEY (tagId) REFERENCES tags(id),
                                   FOREIGN KEY (cantWithTagId) REFERENCES tags(id),
                                   PRIMARY KEY (tagId, cantWithTagId)
);
