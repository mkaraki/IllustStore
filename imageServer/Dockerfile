FROM rust:1-bookworm AS lepton_jpeg_build

RUN apt-get update && apt-get install -y git

WORKDIR /
RUN git clone --depth 1 https://github.com/microsoft/lepton_jpeg_rust.git
WORKDIR lepton_jpeg_rust

RUN cargo build --release

FROM golang:1.22-bookworm AS build

RUN apt-get update && \
    apt-get install -y \
    libvips-dev

WORKDIR /app
COPY --from=lepton_jpeg_build /lepton_jpeg_rust/target/release/liblepton_jpeg.so /app/
COPY . /app/

RUN go build -ldflags '-linkmode external -extldflags=-L=.'

FROM debian:bookworm

RUN apt-get update && \
    apt-get install -y \
    libvips42 \
    && apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY --from=lepton_jpeg_build /lepton_jpeg_rust/target/release/liblepton_jpeg.so /usr/lib/
RUN ldconfig

WORKDIR /app
COPY --from=build /app/imageServer /app/

VOLUME /usr/src/app/images
EXPOSE 8080

ENTRYPOINT ["/app/imageServer"]
