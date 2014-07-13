#!/bin/bash

set -ev

php update.php --feeds | tee /dev/tty > _feeds-update.log
! grep -q "^error" _feeds-update.log
