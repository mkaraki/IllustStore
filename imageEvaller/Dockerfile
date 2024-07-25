FROM rust:1-bookworm AS build-lepton_jpeg_rust

RUN apt-get update && \
    apt-get install -y \
    git && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN git clone --depth 1 https://github.com/microsoft/lepton_jpeg_rust.git /lepton_jpeg_rust
WORKDIR /lepton_jpeg_rust

RUN cargo build --release

FROM tensorflow/tensorflow:2.17.0

WORKDIR /usr/src/app

RUN apt-get update && \
    apt-get install -y \
    default-libmysqlclient-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY requirements.embed.txt requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

COPY --from=build-lepton_jpeg_rust /lepton_jpeg_rust/target/release/liblepton_jpeg.so /usr/lib/liblepton_jpeg.so

COPY LibLepton.py /usr/src/app/
COPY processImageGlob.py /usr/src/app/
COPY deepdanbooruEval.py /usr/src/app/

ENTRYPOINT [ "python", "/usr/src/app/processImageGlob.py" ]