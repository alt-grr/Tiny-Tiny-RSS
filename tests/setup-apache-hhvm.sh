#!/bin/bash

set -ev

sudo apt-get install apache2 libapache2-mod-fastcgi
sudo a2enmod rewrite actions fastcgi alias

# Configure apache virtual hosts
sudo cp -f tests/apache-hhvm.conf /etc/apache2/sites-available/default
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
sudo service apache2 restart

# Run HHVM
hhvm -m daemon -vServer.Type=fastcgi -vServer.Port=9000
