FROM tensorflow/tensorflow:2.14.0

WORKDIR /usr/src/app

RUN apt-get update && \
    apt-get install -y \
    default-libmysqlclient-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY requirements.embed.txt requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

COPY processImageGlob.py /usr/src/app/
COPY deepdanbooruEval.py /usr/src/app/

ENTRYPOINT [ "python", "/usr/src/app/processImageGlob.py" ]