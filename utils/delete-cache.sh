#!/bin/sh

CURRENT_DIR=$(pwd)

cd "${CURRENT_DIR}/../cache/export" && ls | xargs rm -f
cd "${CURRENT_DIR}/../cache/images" && ls | xargs rm -f
cd "${CURRENT_DIR}/../cache/js" && ls | xargs rm -f
cd "${CURRENT_DIR}/../cache/simplepie" && ls | xargs rm -f
cd "${CURRENT_DIR}/../cache/upload" && ls | xargs rm -f

cd "${CURRENT_DIR}"
