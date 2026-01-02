# -----------------------------------------------------
FROM rust:1.91-alpine AS lepton_jpeg_build

RUN apk update && apk add git

WORKDIR /
RUN git clone --branch v0.5.3 --depth 1 https://github.com/microsoft/lepton_jpeg_rust.git
WORKDIR /lepton_jpeg_rust/dll

#RUN sed -i 's/cdylib/cdylib", "staticlib/' Cargo.toml

RUN RUSTFLAGS="-C target-feature=-crt-static" cargo build --release --workspace --locked --target=x86_64-unknown-linux-musl

# -----------------------------------------------------
FROM golang:1.25-alpine AS build

RUN apk update && apk add build-base pkgconf meson wget \
    glib-dev glib-static expat-dev expat-static zlib-static \
    gettext-static libwebp-dev libwebp-static libpng-dev \
    libpng-static libjpeg-turbo-dev libjpeg-turbo-static

ARG VIPS_VERSION=8.18.0

RUN wget https://github.com/libvips/libvips/releases/download/v${VIPS_VERSION}/vips-${VIPS_VERSION}.tar.xz -O vips.tar.xz && \
    tar xf vips.tar.xz && \
    cd vips-${VIPS_VERSION} && \
    meson setup build --prefix=/usr --libdir=/usr/lib --buildtype=release --strip && \
    cd build && \
    meson compile && \
    meson test && \
    meson install

WORKDIR /app
COPY --from=lepton_jpeg_build /lepton_jpeg_rust/target/x86_64-unknown-linux-musl/release/liblepton_jpeg_dll.so /usr/lib/liblepton_jpeg.so
COPY . /app/

RUN GOOS=linux GOARCH=amd64 CGO_ENABLED=1 CGO_CFLAGS_ALLOW="-Xpreprocessor" go build

# -----------------------------------------------------
FROM dhi.io/alpine-base:3.23-dev

RUN apk update && apk add glib expat zlib gettext libwebp \
    libpng libjpeg-turbo libwebpmux libwebpdemux libgcc

COPY --from=build /usr/lib/libvips* /usr/lib/
COPY --from=lepton_jpeg_build /lepton_jpeg_rust/target/x86_64-unknown-linux-musl/release/liblepton_jpeg_dll.so /usr/lib/liblepton_jpeg.so

WORKDIR /app
COPY --from=build /app/imageServer /app/

USER nonroot
VOLUME /usr/src/app/images
EXPOSE 8080

ENTRYPOINT ["/app/imageServer"]
