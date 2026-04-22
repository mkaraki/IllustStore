FROM rust:1-alpine as build

WORKDIR /app
COPY Cargo.toml Cargo.lock /app/
COPY src /app/src

RUN cargo build --locked --release --target=x86_64-unknown-linux-musl

FROM gcr.io/distroless/base-debian13

COPY --from=build /app/target/x86_64-unknown-linux-musl/release/imageServer /app/imageServer
CMD ["/app/imageServer"]
