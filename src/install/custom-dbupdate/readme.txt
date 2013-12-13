SQL scripts in this directory will be executed before
execution of the dbupdate.php functionality.
Each line of a script is supposed to be one SQL command.

The script name must comply with the following naming convention:

initSectionName + '_' + suffix + '.sql'

where 'initSectionName' is the name of a configuration
section that defines the database connection to be used with this
script and suffix is any string, e.g. 'database_1.sql'.