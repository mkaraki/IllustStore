# How to create legals (dev note)

## PHP

Install [Comcast/php-legal-licenses](https://github.com/Comcast/php-legal-licenses).

```shell
php-legal-licenses generate --hide-version --csv
```

## GoLang

Install [Songmu/gocredits](https://github.com/Songmu/gocredits).

```shell
gocredits . > CREDITS
```

## Python

Install [pip-licenses](https://github.com/raimon49/pip-licenses).

```ps1
.\.venv\Scripts\Activate.ps1
pip-licenses --format=plain-vertical --with-license-file --no-license-path
```
