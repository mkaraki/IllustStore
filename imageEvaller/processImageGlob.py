import deepdanbooruEval
import os
import sys
from glob import iglob
import argparse
from pathlib import Path

from PIL import Image
import tensorflow
import numpy
import mysql.connector
import imagehash

import LibLepton

parser = argparse.ArgumentParser(
                    prog='IllustStore Image Evaluator',
                    description='Scan image files and tagging/hashing them.')

parser.add_argument('--migrate-scan', action='store_true', help='Scan and update empty field if exists in DB.')
parser.add_argument('--verbose', action='store_true', help='Verbose mode. Shows filenames')
parser.add_argument('--force-delete-all-images', action='store_true', help='Delete all images. This will not re-scan')
parser.add_argument('--delete-all-tags', action='store_true', help='Delete all tags. This requires --force-delete-all-images option.')

args = parser.parse_args()


db = mysql.connector.connect(
    user=os.getenv("MYSQL_USER", "illustStore"),
    passwd=os.getenv("MYSQL_PASSWORD", "illustStore"),
    host=os.getenv("MYSQL_HOST", "db"),
    db=os.getenv("MYSQL_DATABASE", "illustStore"),
    port=os.getenv("MYSQL_PORT", 3306),
    collation="utf8mb4_unicode_520_ci"
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
        # If there are empty hash field
        res = add_image_hash(img_id, image)
        if res == False:
            return False

    # Check is tags exists
    dbCursor.execute("SELECT tagId FROM tagAssign WHERE illustId = %s", (img_id,))
    if dbCursor.rowcount < 1:
        # if tags not exists
        res = add_image_tags(img_id, image)
        if res == False:
            return False

    # Check is image size exists
    dbCursor.execute("SELECT id FROM illusts WHERE id = %s AND width IS NULL OR height IS NULL", (img_id,))
    if dbCursor.rowcount > 0:
        # If there are empty img size field
        res = add_image_size(img_id, image)
        if res == False:
            return False

    return True


def add_image_size(img_id, image):
    pilImg = tensorflow.keras.utils.array_to_img(image)

    width, height = pilImg.size

    dbCursor.execute("UPDATE illusts SET width = %s, height = %s WHERE id = %s", (width, height, img_id,))

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

        # Skip if detected tag is blacklisted in illust.
        # This may won't work as user expected. Because this method only runs when program didn't detected any tags registered in DB.
        # ToDo: Re-scan tag for each image to register new detected tags (by model changes). #12
        dbCursor.execute("SELECT tagId FROM tagNegativeAssign WHERE illustId = %s AND tagId = %s", (illustId, tagId,))
        if dbCursor.rowcount > 0:
            continue

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

    dbCursor.execute("UPDATE illusts SET aHash = CONV(%s, 16, 10), pHash = CONV(%s, 16, 10), dHash = CONV(%s, 16, 10), colorHash = CONV(%s, 16, 10) WHERE id = %s", (aHash, pHash, dHash, colorHash, img_id,))


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



print("glob: *.webp")
for i in iglob("./images/**/*.webp", recursive=True):
    img_id = get_image_id(i)

    if img_id != False and is_need_scan_even_exists() == False:
        if args.verbose:
            print(f"Exists: {i}")
        continue

    img = None

    try:
        img = numpy.array(Image.open(i))
        img = img[:,:,:3]
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


print("glob: *.lep")
lepton_util = LibLepton.LeptonConvert()
for i in iglob("./images/**/*.lep", recursive=True):
    img_id = get_image_id(i)

    if img_id != False and is_need_scan_even_exists() == False:
        if args.verbose:
            print(f"Exists: {i}")
        continue

    img = None

    try:
        jpeg_data = lepton_util.load_lepton_from_path(i)
        img = tensorflow.io.decode_jpeg(bytes(jpeg_data), channels=3)
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
