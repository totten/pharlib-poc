#!/usr/bin/env bash
set -e

if command -v use-bknix >/dev/null 2>&1; then
  COMPOSER="use-bknix php83 -r composer"
else
  COMPOSER="composer"
fi

$COMPOSER init --no-interaction --name="pharlib/phpspreadsheet-5.8"
$COMPOSER config prefer-stable true
$COMPOSER config audit.block-insecure false
$COMPOSER config --no-plugins allow-plugins.cweagans/composer-patches true

use-bknix php83 -r php -r '
  $json = json_decode(file_get_contents("composer.json"), true);
  if (isset($json["require"]) && empty($json["require"])) {
    unset($json["require"]);
  }
  $json["extra"]["patches"]["phpoffice/phpspreadsheet"] = [
    "Apply backported security packages" => "https://download.civicrm.org/patches/929f65845c4348d553997babe80d471ff3ad48237c91f0b9b0055757457bceba/security_patches.patch"
  ];
  file_put_contents("composer.json", json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
'

$COMPOSER require "cweagans/composer-patches:^1.7" "phpoffice/phpspreadsheet:~5.8.0" --no-interaction
