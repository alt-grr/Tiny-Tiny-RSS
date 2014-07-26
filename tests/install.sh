#!/bin/bash

set -ev

if [[ "$DB" == "pgsql" ]]; then export DBUSER=postgres; fi
if [[ "$DB" == "mysql" ]]; then export DBUSER=root; fi

curl --version

# Test if install page is accessible
curl --fail -sv http://localhost/install/

# Test configuration and database
curl --fail -s http://localhost/install/ --data "op=testconfig&DB_TYPE=$DB&DB_USER=$DBUSER&DB_PASS=&DB_NAME=travis_ci_test&DB_HOST=localhost&DB_PORT=&SELF_URL_PATH=http%3A%2F%2Flocalhost%2F" | tee /dev/tty | awk '/Configuration check succeeded/ {f1=1} /Database test succeeded/ {f2=1} END {exit !(f1 && f2)}'

# Initialize database
curl --fail -s http://localhost/install/ --data "op=installschema&DB_TYPE=$DB&DB_USER=$DBUSER&DB_PASS=&DB_NAME=travis_ci_test&DB_HOST=localhost&DB_PORT=&SELF_URL_PATH=http%3A%2F%2Flocalhost%2F" | tee /dev/tty | grep -q "Database initialization completed"

# Save config
curl --fail -s http://localhost/install/ --data "op=saveconfig&DB_TYPE=$DB&DB_USER=$DBUSER&DB_PASS=&DB_NAME=travis_ci_test&DB_HOST=localhost&DB_PORT=&SELF_URL_PATH=http%3A%2F%2Flocalhost%2F" | tee /dev/tty | grep -q "Successfully saved config.php"

# Change log destination to syslog
sed -i -e "s/define\('LOG_DESTINATION', 'sql'\)/define\('LOG_DESTINATION', 'syslog'\)/" config.php

# Check if main page is accesible and install page is not
curl --fail -s http://localhost/install/ | tee /dev/tty | grep -q "Error: config.php already exists in tt-rss directory; aborting."

# Test if main page is accessible
curl -sv http://localhost/
curl --fail -s http://localhost/ | tee /dev/tty | awk '/Tiny Tiny RSS : Login/ {f1=1} /Fatal Error/ {f2=1} END {exit !(f1 && !f2)}'
