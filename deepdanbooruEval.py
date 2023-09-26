# Original source from DeepDanbooru by Kichang Kim
# Path: /deepdanbooru/commands/evaluate.py
# Path: /deepdanbooru/data/__init__.py
#
# > MIT License
# >
# > Copyright (c) 2019 Kichang Kim
# >
# > Permission is hereby granted, free of charge, to any person obtaining a copy
# > of this software and associated documentation files (the "Software"), to deal
# > in the Software without restriction, including without limitation the rights
# > to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# > copies of the Software, and to permit persons to whom the Software is
# > furnished to do so, subject to the following conditions:
# >
# > The above copyright notice and this permission notice shall be included in all
# > copies or substantial portions of the Software.
# >
# > THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# > IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# > FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# > AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# > LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# > OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# > SOFTWARE.

import tensorflow
import deepdanbooru
from functools import cache


@cache
def _load_model(model_path = None):
    if (model_path == None):
        model_path = "models/deepdanbooru/model.h5";
    return tensorflow.keras.models.load_model(filepath=model_path, compile=False)


@cache
def _load_tags(tags_path=None):
    if (tags_path == None):
        tags_path = "models/deepdanbooru/tags.txt"
    return deepdanbooru.data.load_tags(tags_path=tags_path)


# Based on:
# - https://github.com/KichangKim/DeepDanbooru/blob/05eb3c39b0fae43e3caf39df801615fe79b27c2f/deepdanbooru/data/__init__.py#L13
def _image_preprocess(
    image,
    width,
    height
):
    image = tensorflow.image.resize(
                image,
                size=(height, width),
                method=tensorflow.image.ResizeMethod.AREA,
                preserve_aspect_ratio=True,
            )

    image = image.numpy()
    image = deepdanbooru.image.transform_and_pad_image(image, width, height)

    image = image / 255.0

    return image


# Based on:
# - https://github.com/KichangKim/DeepDanbooru/blob/05eb3c39b0fae43e3caf39df801615fe79b27c2f/deepdanbooru/commands/evaluate.py#L21
def _evaluate(
    image,
    model,
    tags,
    threshold=None,
):
    width = model.input_shape[2]
    height = model.input_shape[1]

    if (threshold == None):
        threshold = 0.5

    image = _image_preprocess(image, width, height)

    image_shape = image.shape
    image = image.reshape((1, image_shape[0], image_shape[1], image_shape[2]))
    y = model.predict(image)[0]

    result_dict = {}

    for i, tag in enumerate(tags):
        result_dict[tag] = y[i]

    for tag in tags:
        if result_dict[tag] >= threshold:
            yield tag, result_dict[tag]


# Requires Image(type="numpy")
def evaluateForGradioInput(
    image,
    tags_path = None,
    model_path = None,
    threshold = None,
):
    model = _load_model(model_path)
    tags = _load_tags(tags_path)

    taglist = _evaluate(image, model, tags)
    return taglist


def evaluateImageFile(
    image_path,
    tags_path=None,
    model_path=None,
    threshold=0.5,
):
    model = _load_model(model_path)
    tags = _load_tags(tags_path)

    return deepdanbooru.commands.evaluate_image(image_path, model, tags, threshold)
