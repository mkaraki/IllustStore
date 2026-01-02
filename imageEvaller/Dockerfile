# -----------------------------------------------------
# Check https://www.tensorflow.org/install/pip#software_requirements
# for supported python versions
FROM dhi.io/python:3.14.2-debian13-dev AS builder
COPY --from=ghcr.io/astral-sh/uv:latest /uv /uvx /bin/

ARG UV_NO_DEV=1
ARG UV_COMPILE_BYTECODE=1
ARG UV_LINK_MODE=copy
ARG UV_PYTHON_DOWNLOADS=0

WORKDIR /usr/src/app
COPY pyproject.toml uv.lock /usr/src/app/
RUN --mount=type=cache,target=/root/.cache/uv \
    uv sync --frozen --no-install-project --no-editable

COPY src /usr/src/app/src
RUN --mount=type=cache,target=/root/.cache/uv \
    uv sync --locked --no-editable

# -----------------------------------------------------
FROM dhi.io/python:3.14.2-debian13

WORKDIR /usr/src/app

COPY --from=dhi.io/mysql:9.5-debian13 /opt/mysql/lib/libmysqlclient* /usr/lib/
COPY --from=builder /usr/src/app/.venv /usr/src/app/.venv
ENV PATH="/usr/src/app/.venv/bin:$PATH"

VOLUME ["/usr/src/app/images"]

ENTRYPOINT [ "/usr/src/app/.venv/bin/imageevaller" ]
