#!/bin/bash

set -ev

if [[ "$DB" == "pgsql" ]]; then export DBUSER=postgres; fi
if [[ "$DB" == "mysql" ]]; then export DBUSER=root; fi

curl --version

echo "Test if install page is accessible"
curl --fail -s http://localhost/install/

echo "Test configuration and database"
curl --fail -s http://localhost/install/ --data "op=testconfig&DB_TYPE=$DB&DB_USER=$DBUSER&DB_PASS=&DB_NAME=travis_ci_test&DB_HOST=localhost&DB_PORT=&SELF_URL_PATH=http%3A%2F%2Flocalhost%2F" | awk '/Configuration check succeeded/ {f1=1} /Database test succeeded/ {f2=1} END {exit !(f1 && f2)}'

echo "Initialize database"
curl --fail -s http://localhost/install/ --data "op=installschema&DB_TYPE=$DB&DB_USER=$DBUSER&DB_PASS=&DB_NAME=travis_ci_test&DB_HOST=localhost&DB_PORT=&SELF_URL_PATH=http%3A%2F%2Flocalhost%2F" | grep -q "Database initialization completed"

echo "Save config"
curl --fail -s http://localhost/install/ --data "op=saveconfig&DB_TYPE=$DB&DB_USER=$DBUSER&DB_PASS=&DB_NAME=travis_ci_test&DB_HOST=localhost&DB_PORT=&SELF_URL_PATH=http%3A%2F%2Flocalhost%2F" | grep -q "Successfully saved config.php"
