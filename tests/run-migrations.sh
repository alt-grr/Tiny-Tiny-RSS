#!/bin/bash

set -ev

CURRENT_DIR=`pwd`
MIGRATION_DB_NAME=travis_ci_test_migration
MIN_MIGRATION_VERSION=4

if [[ "$DB" == 'pgsql' ]]; then
	LAST_SCHEMA_VERSION=`ls schema/versions/pgsql/*.sql -1vr | head -1 | sed 's/\.sql$//'`

	psql --version
	psql -c "CREATE DATABASE $MIGRATION_DB_NAME;" -U postgres
	psql --echo-queries --set ON_ERROR_STOP=1 -U postgres -d $MIGRATION_DB_NAME -a -f tests/conf/pgsql_schema_v3.sql

	for i in {$MIN_MIGRATION_VERSION..$LAST_SCHEMA_VERSION}; do
		echo "Migrating $DB database from version $(($x-1)) to version $x using file $CURRENT_DIR/schema/versions/pgsql/$i.sql"
		psql --echo-queries --set ON_ERROR_STOP=1 -U postgres -d $MIGRATION_DB_NAME -a -f schema/versions/pgsql/$i.sql
	done
fi

if [[ "$DB" == 'mysql' ]]; then
	LAST_SCHEMA_VERSION=`ls schema/versions/mysql/*.sql -1vr | head -1 | sed 's/\.sql$//'`

	mysql --version
	mysql -e "CREATE DATABASE $MIGRATION_DB_NAME;" -uroot
	mysql --verbose -uroot $MIGRATION_DB_NAME < tests/conf/mysql_schema_v3.sql

	for i in {$MIN_MIGRATION_VERSION..$LAST_SCHEMA_VERSION}; do
		echo "Migrating $DB database from version $(($x-1)) to version $x using file $CURRENT_DIR/schema/versions/mysql/$i.sql"
		mysql --verbose -uroot $MIGRATION_DB_NAME < schema/versions/mysql/$i.sql
	done
fi

echo 'All migrations done'
