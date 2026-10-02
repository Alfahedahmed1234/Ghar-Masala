#!/bin/sh
# Package the theme as an uploadable WordPress zip: dist/ghar-masala.zip
set -e
cd "$(dirname "$0")"
mkdir -p dist
rm -f dist/ghar-masala.zip
zip -rq dist/ghar-masala.zip ghar-masala -x '*.DS_Store'
echo "Built dist/ghar-masala.zip"
