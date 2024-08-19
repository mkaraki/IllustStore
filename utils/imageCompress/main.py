import os
from glob import iglob
import argparse
from pathlib import Path

import mysql.connector
from lepton_utils import LeptonUtils
from webp_utils import WebPUtils


parser = argparse.ArgumentParser(
    prog='IllustStore Image Compression utils',
    description='Compress registered images')

argparse = parser.add_argument('--verbose', action='store_true', help='Verbose mode. Shows filenames')

args = parser.parse_args()

db = mysql.connector.connect(
    user="illustStore", passwd="illustStore", host="db", db="illustStore"
)
dbCursor = db.cursor(dictionary=True, buffered=True)

print("Db connected.")

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

print("glob: *.jpg")
for i in iglob("./images/**/*.jpg", recursive=True):
    img_id = get_image_id(i)

    if img_id == False:
        if args.verbose:
            print(f"Not registered: {i}")
        continue

    orig_path = Path(i)
    orig_parent = str(orig_path.parent.absolute())
    orig_name = str(orig_path.stem)

    new = os.path.join(orig_parent, orig_name + '.lep')

    res = LeptonUtils.try_convert_lepton(i, new)

    if res:
        dbCursor.execute("UPDATE illusts SET path = %s WHERE id = %s", (new, img_id))
        if args.verbose:
            print(f"{i}: converted to: {new}")
    else:
        if args.verbose:
            print(f"{i}: converted to: {new}")

print("glob: *.png")
for i in iglob("./images/**/*.png", recursive=True):
    img_id = get_image_id(i)

    if img_id == False:
        if args.verbose:
            print(f"Not registered: {i}")
        continue

    orig_path = Path(i)
    orig_parent = str(orig_path.parent.absolute())
    orig_name = str(orig_path.stem)

    new = os.path.join(orig_parent, orig_name + '.webp')

    res = WebPUtils.try_convert_webp(i, new)

    if res:
        dbCursor.execute("UPDATE illusts SET path = %s WHERE id = %s", (new, img_id))
        if args.verbose:
            print(f"{i}: converted to: {new}")
    else:
        if args.verbose:
            print(f"{i}: converted to: {new}")
