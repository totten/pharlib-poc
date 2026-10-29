#!/usr/bin/env bash
set -e

if command -v use-bknix >/dev/null 2>&1; then
  COMPOSER="use-bknix php83 -r composer"
else
  COMPOSER="composer"
fi

$COMPOSER init --no-interaction --name="pharlib/phpspreadsheet-5.10"
$COMPOSER config prefer-stable true
$COMPOSER require "phpoffice/phpspreadsheet:~5.10.0" --no-interaction
