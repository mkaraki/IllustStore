import deepdanbooruEval
import os
import sys
import glob
import tensorflow
import mysql.connector

db = mysql.connector.connect(
    user="illustStore", passwd="illustStore", host="db", db="illustStore"
)
dbCursor = db.cursor(dictionary=True, buffered=True)


def image_proc(image):
    res = deepdanbooruEval.evaluateTfImage(image)
    return {k: float(v) for k, v in res}


def is_exists(img_path):
    image = os.path.abspath(img_path)
    dbCursor.execute("SELECT id FROM illusts WHERE path = %s", (image,))
    return dbCursor.rowcount > 0


def is_tag_danbooru_exists(tag):
    dbCursor.execute("SELECT id FROM tags WHERE tagDanbooru = %s", (tag,))
    if dbCursor.rowcount < 1:
        return False
    return dbCursor.fetchone()["id"]


def add_image(i_path, image):
    image_abs = os.path.abspath(i_path)
    dbCursor.execute("INSERT INTO illusts(path) VALUES(%s)", (image_abs,))
    illustId = dbCursor.lastrowid
    for t, a in image_proc(image).items():
        tagId = is_tag_danbooru_exists(t)
        if tagId == False:
            dbCursor.execute(
                "INSERT INTO tags(tagName, tagDanbooru) VALUES (%s, %s)", (t, t)
            )
            tagId = dbCursor.lastrowid
        dbCursor.execute(
            "INSERT INTO tagAssign(illustId, tagId, autoAssigned, accuracy) VALUES (%s, %s, TRUE, %s)",
            (illustId, tagId, a),
        )
        print(f"{i_path} has {t} ({a})")
    db.commit()


for i in glob.glob("./images/**/*.jpg"):
    if is_exists(i):
        print(f"Skipped: {i}")
        continue
    print(f"Processing: {i}")
    img = None
    try:
        raw_data = tensorflow.io.read_file(i)
        img = tensorflow.io.decode_jpeg(raw_data)
    except Exception as e:
        sys.stderr.write(f"Failed to read {i}: {e}\n")
        continue
    add_image(i, img)


for i in glob.glob("./images/**/*.png"):
    if is_exists(i):
        print(f"Skipped: {i}")
        continue
    print(f"Processing: {i}")
    img = None
    try:
        raw_data = tensorflow.io.read_file(i)
        img = tensorflow.io.decode_png(raw_data)
    except Exception as e:
        sys.stderr.write(f"Failed to read {i}: {e}\n")
        continue
    add_image(i, img)

db.close()
dbCursor.close()
