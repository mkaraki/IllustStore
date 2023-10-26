import deepdanbooruEval
import gradio as gr
import os


#def dde(image_path):
#    for tag, score in deepdanbooruEval.evaluate(image_path=image_path):
#        print(f"({score:05.3f}) {tag}")
#
#
#dde("local-tests/00205-26102765.png")
#dde("local-tests/00000-48126753.png")


def image_mod(image):
    print('[DeepDanbooru] Image queued');
    res = deepdanbooruEval.evaluateForGradioInput(image);
    print('[DeepDanbooru] Got result');
    return {k: float(v) for k, v in res}


gr_app = gr.Interface(
    image_mod,
    gr.Image(type="numpy"),
    gr.Label(),
    allow_flagging='never',
)


if __name__ == "__main__":
    gr_app.launch()

