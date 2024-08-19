FROM rust:1-bookworm AS build-lepton_jpeg_rust

RUN apt-get update && \
    apt-get install -y \
    git && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN git clone --depth 1 https://github.com/microsoft/lepton_jpeg_rust.git /lepton_jpeg_rust
WORKDIR /lepton_jpeg_rust

RUN cargo build --release

FROM python:3-bookworm

WORKDIR /usr/src/app

RUN apt-get update && \
    apt-get install -y \
    default-libmysqlclient-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY requirements.txt requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

COPY --from=build-lepton_jpeg_rust /lepton_jpeg_rust/target/release/liblepton_jpeg.so /usr/lib/liblepton_jpeg.so

COPY *.py /usr/src/app/
