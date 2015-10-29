#!/bin/bash

set -ev

sudo apt-get install apache2 libapache2-mod-fastcgi
sudo a2enmod rewrite actions fastcgi alias

# Configure apache virtual hosts
sudo cp -f tests/conf/apache-hhvm.conf /etc/apache2/sites-available/default
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default

# Run HHVM
if [[ "$DB" == "pgsql" ]]; then
	mkdir ~/hhvm_extensions
	wget -nv -O ~/hhvm_extensions/pgsql.so https://github.com/PocketRent/hhvm-pgsql/raw/releases/3.10.0/ubuntu/vivid/pgsql.so
	sudo bash -c 'echo "DynamicExtensionPath = $HOME/hhvm_extensions
DynamicExtensions {
	* = pgsql.so
}" >> /etc/hhvm/config.hdf'
fi
sudo bash -c 'cat tests/conf/config.hdf >> /etc/hhvm/config.hdf'
sudo bash -c 'echo "ADDITIONAL_ARGS=\"-c /etc/hhvm/config.hdf\"" >> /etc/default/hhvm'

hhvm -m daemon -vServer.Type=fastcgi -vServer.Port=9000

sudo service apache2 restart
