FROM rust:1-trixie AS lepton_jpeg_build

RUN apt-get update && apt-get install -y git

WORKDIR /
RUN git clone --branch v0.5.3 --depth 1 https://github.com/microsoft/lepton_jpeg_rust.git
WORKDIR lepton_jpeg_rust/dll

RUN cargo build --release --workspace --locked

FROM golang:1.25-trixie AS build

RUN apt-get update && \
    apt-get install -y \
    libvips-dev

WORKDIR /app
COPY --from=lepton_jpeg_build /lepton_jpeg_rust/target/release/liblepton_jpeg_dll.so /app/liblepton_jpeg.so
COPY . /app/

RUN go build -ldflags '-linkmode external -extldflags=-L=.'

FROM debian:trixie-slim

RUN apt-get update && \
    apt-get install -y \
    libvips42t64 \
    && apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY --from=lepton_jpeg_build /lepton_jpeg_rust/target/release/liblepton_jpeg_dll.so /usr/lib/liblepton_jpeg.so
RUN ldconfig

WORKDIR /app
COPY --from=build /app/imageServer /app/

VOLUME /usr/src/app/images
EXPOSE 8080

ENTRYPOINT ["/app/imageServer"]
