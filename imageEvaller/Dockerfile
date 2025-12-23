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
# Check https://www.tensorflow.org/install/pip#software_requirements
# for supported python versions
FROM dhi.io/python:3.12

WORKDIR /usr/src/app

COPY --from=dhi.io/mysql:9.5 /opt/mysql/lib/libmysqlclient* /usr/local/lib/

COPY --from=build-lepton_jpeg_rust /lepton_jpeg_rust/target/release/liblepton_jpeg_dll.so /usr/lib/liblepton_jpeg.so

ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

COPY requirements.embed.txt requirements.txt
RUN pip install --no-cache-dir -r requirements.txt &&\
    rm requirements.txt

COPY LibLepton.py processImageGlob.py deepdanbooruEval.py /usr/src/app/

ENTRYPOINT [ "python", "/usr/src/app/processImageGlob.py" ]