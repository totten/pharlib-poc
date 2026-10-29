#!/usr/bin/env bash
set -e

if command -v use-bknix >/dev/null 2>&1; then
  COMPOSER="use-bknix php83 -r composer"
else
  COMPOSER="composer"
fi

$COMPOSER init --no-interaction --name="pharlib/psr-log-3"
$COMPOSER config prefer-stable true
$COMPOSER require "psr/log:^3.0" --no-interaction
