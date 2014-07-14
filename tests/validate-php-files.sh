#!/bin/bash

set -ev

find . -name \*.php -exec php --syntax-check "{}" \; | grep "Parse error:" | tee /dev/tty | awk '/Parse error/ {f1=1} END {exit f1}'
php --syntax-check config.php-dist | grep "Parse error:" | tee /dev/tty | awk '/Parse error/ {f1=1} END {exit f1}'
