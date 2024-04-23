import deepdanbooruEval
import os
import sys
from glob import glob, iglob
import argparse

import tensorflow
import mysql.connector
from PIL import Image
import imagehash

parser = argparse.ArgumentParser(
                    prog='IllustStore Image Evaluator',
                    description='Scan image files and tagging/hashing them.')

argparse = parser.add_argument('--migrate-scan', action='store_true', help='Scan and update empty field if exists in DB.')
argparse = parser.add_argument('--verbose', action='store_true', help='Verbose mode. Shows filenames')

args = parser.parse_args()

db = mysql.connector.connect(
    user="illustStore", passwd="illustStore", host="db", db="illustStore"
)
dbCursor = db.cursor(dictionary=True, buffered=True)

print("Db connected.")


def image_proc(image):
    res = deepdanbooruEval.evaluateTfImage(image)
    return {k: float(v) for k, v in res}


def is_exist(i_path):
    image = os.path.abspath(i_path)
    dbCursor.execute("SELECT id FROM illusts WHERE path = %s", (image,))
    if dbCursor.rowcount < 1:
        return False
    return True


def get_image_id(img_path):
    image = os.path.abspath(img_path)
    dbCursor.execute("SELECT id FROM illusts WHERE path = %s", (image,))
    if dbCursor.rowcount < 1:
        return False
    return dbCursor.fetchone()["id"]


def is_tag_danbooru_exists(tag):
    dbCursor.execute("SELECT id FROM tags WHERE tagDanbooru = %s", (tag,))
    if dbCursor.rowcount < 1:
        return False
    return dbCursor.fetchone()["id"]


def is_need_scan_even_exists():
    if args.migrate_scan == False:
        return False
    return True


def add_image(i_path, image):
    image_abs = os.path.abspath(i_path)
    dbCursor.execute("INSERT INTO illusts(path) VALUES(%s)",
                      (image_abs,))
    illustId = dbCursor.lastrowid

    res = try_update_image_info(i_path, image, illustId)

    if (res == False):
        db.rollback()
    else:
        db.commit()


def call_try_update_image_info(i_path, image, img_id):
    """
    Call try_update_image_info and commit or rollback.
    This should be called from image glob loop (when Image exists in DB).
    This function will skip if not migrate scan mode.
    """
    if args.migrate_scan == False:
        return True

    res = try_update_image_info(i_path, image, img_id)
    if res == False:
        sys.stderr.write(f"Failed to update {i_path}: {e}. Rolling Back.\n")
        db.rollback()
        return False
    else:
        db.commit()
        return True


def try_update_image_info(i_path, image, img_id):
    # Check is aHash, pHash, dHash, colorHash exists
    dbCursor.execute("SELECT id FROM illusts WHERE id = %s AND aHash IS NULL OR pHash IS NULL OR dHash IS NULL OR colorHash IS NULL", (img_id,))

    if dbCursor.rowcount > 0:
        res = add_image_hash(img_id, image)
        if res == False:
            return False

    # Check is tags exists
    dbCursor.execute("SELECT tagId FROM tagAssign WHERE illustId = %s", (img_id,))
    if dbCursor.rowcount < 1:
        res = add_image_tags(img_id, image)
        if res == False:
            return False

    return True


def add_image_tags(illustId, image):
    tag_items = None
    try:
        tag_items = image_proc(image).items()
    except Exception as e:
        sys.stderr.write(f"Failed to tagging {i_path}: {e}\n")
        return False

    for t, a in tag_items:
        tagId = is_tag_danbooru_exists(t)
        if tagId == False:
            dbCursor.execute(
                "INSERT INTO tags(tagName, tagDanbooru) VALUES (%s, %s)", (t, t)
            )
            tagId = dbCursor.lastrowid
        dbCursor.execute(
            "INSERT INTO tagAssign(illustId, tagId, autoAssigned, accuracy) VALUES (%s, %s, TRUE, %s)",
            (illustId, tagId, a,),
        )


def add_image_hash(img_id, image):
    pilImg = tensorflow.keras.utils.array_to_img(image)

    aHash = None
    dHash = None
    pHash = None
    colorHash = None
    try:
        aHash = str(imagehash.average_hash(pilImg))
        dHash = str(imagehash.dhash(pilImg))
        pHash = str(imagehash.phash(pilImg))
        colorHash = str(imagehash.colorhash(pilImg))
    except Exception as e:
        sys.stderr.write(f"Failed to hash: {e}\n")
        return False

    dbCursor.execute("UPDATE illusts SET aHash = %s, pHash = %s, dHash = %s, colorHash = %s WHERE id = %s", (aHash, pHash, dHash, colorHash, img_id,))


print("glob: *.jpg")
for i in iglob("./images/**/*.jpg", recursive=True):
    img_id = get_image_id(i)

    if img_id != False and is_need_scan_even_exists() == False:
        if args.verbose:
            print(f"Exists: {i}")
        continue

    img = None

    try:
        raw_data = tensorflow.io.read_file(i)
        img = tensorflow.io.decode_jpeg(raw_data, channels=3)
    except Exception as e:
        sys.stderr.write(f"Failed to read {i}: {e}\n")
        continue

    if img_id != False:
        if args.verbose:
            print(f"Exists: {i}")
        call_try_update_image_info(i, img, img_id)
        continue
    
    if img_id == False:
        if args.verbose:
            print(f"Processing: {i}")
        add_image(i, img)



print("glob: *.png")
for i in iglob("./images/**/*.png", recursive=True):
    img_id = get_image_id(i)

    if img_id != False and is_need_scan_even_exists() == False:
        if args.verbose:
            print(f"Exists: {i}")
        continue

    img = None

    try:
        raw_data = tensorflow.io.read_file(i)
        img = tensorflow.io.decode_png(raw_data, channels=3)
    except Exception as e:
        sys.stderr.write(f"Failed to read {i}: {e}\n")
        continue

    if img_id != False:
        if args.verbose:
            print(f"Exists: {i}")
        call_try_update_image_info(i, img, img_id)
        continue
    
    if img_id == False:
        if args.verbose:
            print(f"Processing: {i}")
        add_image(i, img)

db.close()
dbCursor.close()

print("Db closed.")
