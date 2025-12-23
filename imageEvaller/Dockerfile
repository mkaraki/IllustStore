FROM rust:1-bookworm AS build-lepton_jpeg_rust

RUN apt-get update && \
    apt-get install -y \
    git && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN git clone --branch v0.5.3 --depth 1 https://github.com/microsoft/lepton_jpeg_rust.git /lepton_jpeg_rust
WORKDIR /lepton_jpeg_rust/dll

RUN cargo build --release --workspace --locked

# -----------------------------------------------------
FROM tensorflow/tensorflow:2.20.0

WORKDIR /usr/src/app

RUN apt-get update && \
    apt-get install -y \
    libmariadb3 \
    && apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY --from=build-lepton_jpeg_rust /lepton_jpeg_rust/target/release/liblepton_jpeg_dll.so /usr/lib/liblepton_jpeg.so

COPY requirements.embed.txt requirements.txt
RUN pip install --no-cache-dir -r requirements.txt && \
    rm requirements.txt

COPY LibLepton.py processImageGlob.py deepdanbooruEval.py /usr/src/app/

ENTRYPOINT [ "python", "/usr/src/app/processImageGlob.py" ]