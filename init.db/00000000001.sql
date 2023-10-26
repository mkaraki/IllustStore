CREATE TABLE illusts(
    id int primary key auto_increment,
    path text not null
);
CREATE TABLE tags(
    id int primary key auto_increment,
    tagName text not null,
    tagDanbooru text,
    tagPixivJpn text,
    tagPixivEng text
);
CREATE TABLE tagAssign(
    illustId int not null,
    tagId int not null,
    autoAssigned boolean default(FALSE),
    accuracy float,
    FOREIGN KEY (illustId) REFERENCES illusts(id),
    FOREIGN KEY (tagId) REFERENCES tags(id),
    primary key (illustId, tagId)
);