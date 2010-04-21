#! /bin/bash

# Script to upload layer data on linux machines

# $1 = dbname
# $2 = dbuser
# $3 = data_path

if [ $# != 3 ]; then 
  echo "Expected args 3, Got args $#"; 
  echo "Usage: ./data_import DBNAME DBUSER DATA_PATH"; 
  exit 1; 
fi

dbname=$1
dbuser=$2
datapath=$3

# to create sql script and metadata if required
python import_layers.py "$dbname" "$dbuser" "$datapath" 1> std_op 2> err_op
if [ $? != 0 ]; then echo "Error executing import_layers.py"; exit 1; fi

cat std_op | grep "^psql" > sql_cmds
chmod +x sql_cmds

# Add ROLLBACK to the sql scripts to verify if there are no errors.
python test.py "END" "ROLLBACK"
./sql_cmds

grep -irn "error" logs/
if [ $? = 0 ]; then echo "Error in the log files, go through logs/ dir to find out errors"; exit 1; fi


# Since no errors revert ROLLBACK to END to commit data.
python test.py "ROLLBACK" "END"
./sql_cmds
grep -irn "error" logs/
if [ $? = 0 ]; then echo "Database updated, Error in the log files, go through logs/ dir to find out errors"; exit 1; fi

echo "Data uploaded successfully!"
echo "Layers added:"
ls layersqls | sed s'/.sql//'g
#clean up
rm -rf logs/ layers.list layersqls sql_cmds std_op err_op
exit 0
