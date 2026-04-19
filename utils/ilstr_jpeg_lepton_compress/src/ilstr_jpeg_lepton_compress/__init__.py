import sys
from pathlib import Path
import os
from glob import iglob

import sentry_sdk
import lepton_jpeg_python
import mysql.connector
import lepton_jpeg_python

sentry_sdk.init(
    send_default_pii=True,
    traces_sample_rate=0.05,
    profile_session_sample_rate=0.05,
)
sentry_sdk.profiler.start_profiler()

def get_image_id(dbCursor, image_path) -> int|None:
    path_info = Path(image_path)
    absolute_path = str(path_info.absolute())

    try:
        dbCursor.execute("SELECT id FROM illusts WHERE path = %s LIMIT 1", (absolute_path,))
        if dbCursor.rowcount < 1:
            return None
        return dbCursor.fetchone()['id']
    except Exception as e:
        sys.stderr.write(f"Failed to query database for {image_path}: {e}\n")
        return None

def replace_db_extension(db, dbCursor, image_id, new_image_path) -> bool:
    try:
        dbCursor.execute("UPDATE illusts SET path = %s WHERE id = %s", (new_image_path, image_id))
        db.commit()
        return True
    except Exception as e:
        sys.stderr.write(f"Failed to update database for {image_id} to {new_image_path}: {e}\n")
        db.rollback()
        return False

def process_jpeg_image(db, dbCursor, i):
    lepton_config = {
            "max_jpeg_width": 1_000_000,
    }

    image_id = get_image_id(dbCursor, i)
    if image_id is None:
        sys.stderr.write(f"Image {i} not found in database, skipping.\n")
        return

    path_info = Path(i)
    absolute_path = str(path_info.absolute())

    with sentry_sdk.start_transaction(op="process_jpeg_image", name="Process JPEG Image") as transaction:
        raw_data = None
        span = sentry_sdk.start_span(op="loadJpgImage", description="Load Jpg Image")
        try:
            raw_data = None
            with open(i, 'rb') as f:
                raw_data = f.read()

            if raw_data is None:
                raise ValueError("Failed to read image data")
        except Exception as e:
            sys.stderr.write(f"Failed to read {i}: {e}\n")
            return
        finally:
            span.finish()
    
        compressed = None
        span = sentry_sdk.start_span(op="encodeToLepton", description="Encode to Lepton")
        try:
            compressed = lepton_jpeg_python.compress_bytes(raw_data, lepton_config)
        except Exception as e:
            sys.stderr.write(f"Failed to encode {i} to lepton: {e}\n")
            return
        finally:
            span.finish()

        decompressed = None
        span = sentry_sdk.start_span(op="decodeLepton", description="Decode Lepton")
        try:
            decompressed = lepton_jpeg_python.decompress_bytes(compressed)

        except Exception as e:
            sys.stderr.write(f"Failed to decode temporary lepton file {i}: {e}\n")
            return
        finally:
            span.finish()

        if decompressed != raw_data:
            sys.stderr.write(f"Decompressed data does not match original data for {i}\n")
            return

        file_ext_splitted = absolute_path.split(".")
        file_ext_splitted[-1] = "lep"
        lepton_file_path = ".".join(file_ext_splitted)

        if os.path.exists(lepton_file_path):
            sys.stderr.write(f"Lepton file {lepton_file_path} already exists, skipping {i} to avoid overwriting.\n")
            return

        span = sentry_sdk.start_span(op="saveLeptonFile", description="Save Lepton File")
        try:
            with open(lepton_file_path, 'wb') as f:
                f.write(compressed)
        except Exception as e:
            sys.stderr.write(f"Failed to save lepton file {lepton_file_path}: {e}\n")
            return
        finally:
            span.finish()

        lepton_file_data = None
        span = sentry_sdk.start_span(op="readLeptonFile", description="Read Lepton File for Verification")
        try:
            with open(lepton_file_path, 'rb') as f:
                lepton_file_data = f.read()
        except Exception as e:
            sys.stderr.write(f"Failed to read lepton file {lepton_file_path} for verification: {e}\n")
            try:
                os.remove(lepton_file_path)  # Clean up the lepton file if it was created
            except Exception as cleanup_e:
                sys.stderr.write(f"Failed to clean up lepton file {lepton_file_path}: {cleanup_e}\n")
            return
        finally:
            span.finish()

        if lepton_file_data != compressed:
            sys.stderr.write(f"Lepton file data does not match compressed data for {lepton_file_path}\n")
            try:
                os.remove(lepton_file_path)  # Clean up the lepton file if it was created
            except Exception as cleanup_e:
                sys.stderr.write(f"Failed to clean up lepton file {lepton_file_path}: {cleanup_e}\n")
            return

        replace_result = replace_db_extension(db, dbCursor, image_id, lepton_file_path)
        if not replace_result:
            sys.stderr.write(f"Failed to update database for {i}, skipping.\n")
            try:
                os.remove(lepton_file_path)  # Clean up the lepton file if it was created
            except Exception as cleanup_e:
                sys.stderr.write(f"Failed to clean up lepton file {lepton_file_path}: {cleanup_e}\n")
            return
        else:
            sys.stdout.write(f"Successfully processed {i} and updated database to {lepton_file_path}\n")
            try:
                os.remove(i)  # Remove the original JPEG file after successful processing
            except Exception as e:
                sys.stderr.write(f"Failed to remove original JPEG file {i}: {e}\n")
            return


def main():
    db = mysql.connector.connect(
        user=os.getenv("MYSQL_USER", "illustStore"),
        passwd=os.getenv("MYSQL_PASSWORD", "illustStore"),
        host=os.getenv("MYSQL_HOST", "db"),
        db=os.getenv("MYSQL_DATABASE", "illustStore"),
        port=os.getenv("MYSQL_PORT", 3306),
        collation="utf8mb4_unicode_520_ci"
    )
    dbCursor = db.cursor(dictionary=True, buffered=True)

    print("glob: *.jpg")
    for i in iglob("./images/**/*.jpg", recursive=True):
        process_jpeg_image(db, dbCursor, i)
    print("glob: *.jpeg")
    for i in iglob("./images/**/*.jpeg", recursive=True):
        process_jpeg_image(db, dbCursor, i)

    db.close()
    dbCursor.close()
