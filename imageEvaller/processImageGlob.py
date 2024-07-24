import deepdanbooruEval
import os
import sys
from glob import glob, iglob
import argparse
from pathlib import Path

import tensorflow
import mysql.connector
from PIL import Image
import imagehash

parser = argparse.ArgumentParser(
                    prog='IllustStore Image Evaluator',
                    description='Scan image files and tagging/hashing them.')

argparse = parser.add_argument('--migrate-scan', action='store_true', help='Scan and update empty field if exists in DB.')
argparse = parser.add_argument('--verbose', action='store_true', help='Verbose mode. Shows filenames')
argparse = parser.add_argument('--force-delete-all-images', action='store_true', help='Delete all images. This will not re-scan')
argparse = parser.add_argument('--delete-all-tags', action='store_true', help='Delete all tags. This requires --force-delete-all-images option.')

args = parser.parse_args()

db = mysql.connector.connect(
    user="illustStore", passwd="illustStore", host="db", db="illustStore"
)
dbCursor = db.cursor(dictionary=True, buffered=True)

print("Db connected.")


def image_proc(image):
    res = deepdanbooruEval.evaluateTfImage(image)
    return {k: float(v) for k, v in res}


def get_image_id(img_path):
    """
    Return Image ID if exists in DB.
    Return False if not exists.
    """
    global cache_exists_parent, cache_exists_overflow, cache_exists

    img_path_info = Path(img_path)
    image = str(img_path_info.absolute())
    image_parent = str(img_path_info.parent.absolute())

    # Add last `/` to parent path
    if (image_parent.endswith("/") == False):
        image_parent = image_parent + "/"

    # if Cache exists and parent is same, check cache
    if (cache_exists_parent != None and image_parent == cache_exists_parent):
        # if ['path'] of cache_exists is same as image, return ['id'] field
        for i in cache_exists:
            if (i['path'] == image):
                return i['id']
            
        # if cache not overflowed, return False
        if (cache_exists_overflow == False):
            return False
        else:
            dbCursor.execute("SELECT id FROM illusts WHERE path = %s", (image,))
            if dbCursor.rowcount < 1:
                return False
            return dbCursor.fetchone()["id"]


    dbCursor.execute("SELECT id, path FROM illusts WHERE path LIKE CONCAT(%s, '%') LIMIT 251", (image_parent,))
    dbLength = dbCursor.rowcount

    # if there are no images that starts with image_parent, return False and cache nothing exists
    if dbLength == 0:
        cache_exists_parent = image_parent
        cache_exists = []
        cache_exists_overflow = False
    # if there are more than 250 images (LIMIT of cache), cache all and set cache_exists_overflow to True
    elif dbLength > 250:
        cache_exists_parent = image_parent
        cache_exists = dbCursor.fetchall()
        cache_exists_overflow = True
    # if there are less than 250 images, cache all and set cache_exists_overflow to False
    else:
        cache_exists_parent = image_parent
        cache_exists = dbCursor.fetchall()
        cache_exists_overflow = False

    return get_image_id(img_path)


cache_exists_parent = None
cache_exists_overflow = False
cache_exists = []


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


if args.force_delete_all_images:
    print("Deleting all images")
    dbCursor.execute("DELETE FROM tagNegativeAssign")
    dbCursor.execute("DELETE FROM tagAssign")
    dbCursor.execute("DELETE FROM illusts")
    if args.delete_all_tags:
        dbCursor.execute("DELETE FROM tags")
    db.commit()
    print("Deleted all images")

    sys.exit(0)


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
