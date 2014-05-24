#! /bin/bash

set -x

originalDirectory=$(pwd)

function installMediaWiki {
	cd ..

	wget https://github.com/wikimedia/mediawiki-core/archive/$MW.tar.gz
	tar -zxf $MW.tar.gz
	mv mediawiki-core-$MW phase3

	cd phase3

	mysql -e 'create database its_a_mw;'
	php maintenance/install.php --dbtype $DB --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin
}

function installExtensionViaComposerOnMediaWikiRoot {

	# dev is only needed for as long no stable release is available
	composer init --stability dev

	composer require 'phpunit/phpunit=3.7.*' --prefer-source
	composer require 'mediawiki/semantic-glossary=@dev' --prefer-source

	cd extensions
	cd SemanticGlossary

	# Pull request number, "false" if it's not a pull request
	if [ "$TRAVIS_PULL_REQUEST" != "false" ]
	then
		git fetch origin +refs/pull/"$TRAVIS_PULL_REQUEST"/merge:
		git checkout -qf FETCH_HEAD
	else
		git fetch origin "$TRAVIS_BRANCH"
		git checkout -qf FETCH_HEAD
	fi

	cd ../..

	# Rebuild the class map after git fetch
	composer dump-autoload

	echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
	echo 'ini_set("display_errors", 1);' >> LocalSettings.php
	echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
	echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
	echo "putenv( 'MW_INSTALL_PATH=$(pwd)' );" >> LocalSettings.php

	php maintenance/update.php --quick
}

installMediaWiki
installExtensionViaComposerOnMediaWikiRoot