# Illust Store

AI powered illustration store

## Pre requisites

This project only support docker-compose and mariadb on it.
If you want to use external mariadb or other database, you need to modify source codes.

You cannot edit database database name/password/username in this application.

### Prepare DeepDanbooru model

This project use DeepDanbooru model to detect tags from illustration.

You need to download model from [DeepDanbooru GitHub Release](https://github.com/KichangKim/DeepDanbooru/releases)
and put `imageEvaller/models/deepdanbooru` directory and rename `model-resnet_custom_v3.h5` to `model.h5`.
In default, tagger will include character tags.
If you want to exclude character tags, you need to rename `tags-general.txt` to `tags.txt`.

You can also [generate your own model](https://github.com/KichangKim/DeepDanbooru?tab=readme-ov-file#usage) and use it.
