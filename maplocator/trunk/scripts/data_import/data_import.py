"""
All MapLocator code is Copyright 2010 by the original authors.

This work is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of the License, or any
later version.

This work is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See version 3 of
the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
Version 3 along with this program as the file LICENSE.txt; if not,
please see http://www.gnu.org/licenses/gpl-3.0.html.

"""

import os,sys,shutil


length = len(sys.argv)


if(len(sys.argv) not in [4,5,6]):
	print "Expected args 4 or 5, Got args "+str(len(sys.argv) -1)
	print "Usage: python data_import DBNAME DBUSER DATA_PATH [GEO_CITY]"; 
	sys.exit(1)

dbname = sys.argv[1]
dbuser = sys.argv[2]
datapath = sys.argv[3]
theme = ''
category = ''
if(len(sys.argv) == 5):
	theme = sys.argv[4]
# to create sql script and metadata if required

if(len(sys.argv) == 6):
        theme = sys.argv[4]
	category = sys.argv[5]

if('' != theme and '' != category):
        status = os.system("python import_layers.py "+dbname+" "+dbuser+" "+datapath+" \""+theme+"\" \""+category+"\" 1> std_op 2> err_op")
elif('' != theme):
        status = os.system("python import_layers.py "+dbname+" "+dbuser+" "+datapath+" \""+theme+"\" 1> std_op 2> err_op")
else:
	status = os.system("python import_layers.py "+dbname+" "+dbuser+" "+datapath+" 1> std_op 2> err_op")
if status != 0:
	print "Error executing import_layers.py"
	sys.exit(1)

if os.name == 'nt':
	os.system("findstr \"psql\" std_op > sql_cmds.bat")
else:
	os.system("grep '^psql' std_op > sql_cmds;chmod +x sql_cmds")

# Add ROLLBACK to the sql scripts to verify if there are no errors.
os.system("python test.py END ROLLBACK")
if os.name == 'nt':
	os.system("sql_cmds.bat > tmp")
	status = os.system("findstr /I /S \"error\" logs/*")
else:
	os.system("./sql_cmds")
	status = os.system("grep -irn 'error' logs/")
if status == 0:
	print "Error in the log files, go through logs/ dir to find out errors"
	sys.exit(1)

# Since no errors revert ROLLBACK to END to commit data.
os.system("python test.py ROLLBACK END")
if os.name == 'nt':
	os.system("sql_cmds.bat > tmp")
	status = os.system("findstr /I /S \"error\" logs/*")
else:
	os.system("./sql_cmds")
	status = os.system("grep -irn 'error' logs/")
if status == 0:
	print "Database updated, Error in the log files, go through logs/ dir to find out errors"
	sys.exit(1)

print "Data uploaded successfully!"
print "Layers added:"

for i in os.listdir("layersqls"):
	print i.replace(".sql","")
		
#clean up
shutil.rmtree("layersqls")
shutil.rmtree("logs")
os.remove("layers.list")
os.remove("std_op")
os.remove("err_op")

if os.name == 'nt':
	os.remove("sql_cmds.bat")
	os.remove("tmp")
else:
	os.remove("sql_cmds")

sys.exit(0)

