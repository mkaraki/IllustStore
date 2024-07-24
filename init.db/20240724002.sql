CREATE TABLE tagNegativeAssign(
    illustId int not null,
    tagId int not null,
    FOREIGN KEY (illustId) REFERENCES illusts(id),
    FOREIGN KEY (tagId) REFERENCES tags(id),
    primary key (illustId, tagId)
);
