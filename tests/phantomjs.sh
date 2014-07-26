#!/bin/bash

set -ev

phantomjs ./tests/phantomjs/loginAndLogout.js

# From practical test: only after first login and logout feeds update can happen successfully
./tests/update-feeds.sh
phantomjs ./tests/phantomjs/feedsUpdated.js
