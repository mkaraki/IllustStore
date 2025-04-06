FROM rust:1-bookworm AS build-lepton_jpeg_rust

RUN apt-get update && \
    apt-get install -y \
    git && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN git clone --depth 1 https://github.com/microsoft/lepton_jpeg_rust.git /lepton_jpeg_rust
WORKDIR /lepton_jpeg_rust

RUN cargo build --release

# Check https://www.tensorflow.org/install/pip#software_requirements
# for supported python versions
FROM python:3.12-bookworm AS build-pyc

WORKDIR /usr/src/app

RUN apt-get update && \
    apt-get install -y \
    default-libmysqlclient-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY requirements.embed.txt requirements.txt
RUN pip install --upgrade pip && pip install --no-cache-dir -r requirements.txt

COPY LibLepton.py /usr/src/app/
COPY processImageGlob.py /usr/src/app/
COPY deepdanbooruEval.py /usr/src/app/

RUN python3 -m compileall /usr/src/app/processImageGlob.py

# Match python version
# Pyc is not compatible across python versions
FROM python:3.12-bookworm

RUN apt-get update && \
    apt-get install -y \
    default-libmysqlclient-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY requirements.embed.txt requirements.txt
RUN pip install --upgrade pip && pip install --no-cache-dir -r requirements.txt

COPY --from=build-lepton_jpeg_rust /lepton_jpeg_rust/target/release/liblepton_jpeg.so /usr/lib/liblepton_jpeg.so

COPY --from=build-pyc /usr/src/app/__pycache__ /usr/src/app/__pycache__

# Change pyc file name when python version changes
# File name will dynamically generates
ENTRYPOINT [ "python", "/usr/src/app/__pycache__/processImageGlob.cpython-312.pyc" ]