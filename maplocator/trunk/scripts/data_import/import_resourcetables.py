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

import os, sys
import shutil

sqlsdir = "resourcetablesqls"

COLNAME_COLS = [
  'displayed_columns',
  #'url_columns',
  'italics_columns'
]

#######################################
# Allowed columns in link table file. #
#######################################
ALLOWED_COLUMNS = ['INTEGER','TEXT','DECIMAL','DATE']


#################################################
# SQL columns for corresponding allowed columns #
#################################################
SQL_COLUMNS = {'INTEGER': 'INTEGER',
               'TEXT': 'VARCHAR(1024)',
               'DECIMAL': 'float(24)',
               'DATE': 'DATE'}

#########################################
# Validate if we get an unknown column. #
#########################################
def validate_column_types(types):
    for t in types:
        if t not in ALLOWED_COLUMNS:
            print "Unknown column:", t
            sys.exit(2)
    pass


##############################################################
# Validate column names, see if they contain reserved words  #
# like "year", "count", or contain spaces.                   #
# TODO                                                       #
##############################################################
def validate_column_names(names):
    NOT_ALLOWED_CHARS = [' ', '\t']
    pass

def escapeStr(str1):
  str1 = str1.strip()
  str1 = str1.replace('"', '\"')
  str1 = str1.replace('\\', '\\\\')
  return str1

def convert_to_utf8(filename):
    # gather the encodings you think that the file may be
    # encoded inside a tuple
    encodings = ('windows-1253', 'iso-8859-7', 'macgreek')

    # try to open the file and exit if some IOError occurs
    try:
        f = open(filename, 'r').read()
    except Exception:
        sys.exit(1)

    # now start iterating in our encodings tuple and try to
    # decode the file
    for enc in encodings:
        try:
            # try to decode the file with the first encoding
            # from the tuple.
            # if it succeeds then it will reach break, so we
            # will be out of the loop (something we want on
            # success).
            # the data variable will hold our decoded text
            data = f.decode(enc)
            break
        except Exception:
            # if the first encoding fail, then with the continue
            # keyword will start again with the second encoding
            # from the tuple an so on.... until it succeeds.
            # if for some reason it reaches the last encoding of
            # our tuple without success, then exit the program.
            if enc == encodings[-1]:
                sys.exit(1)
            continue

    # now get the absolute path of our filename and append .bak
    # to the end of it (for our backup file)
    fpath = os.path.abspath(filename)
    newfilename = fpath + '.bak'
    # and make our backup file with shutil
    shutil.copy(filename, newfilename)

    # and at last convert it to utf-8
    f = open(filename, 'w')
    try:
        f.write(data.encode('utf-8'))
    except Exception, e:
        print e
    finally:
        f.close()

def validate_textfile_tabdata(fname, flines):
  chk_len = len(flines[0].replace("\n", "").split('\t'))

  taberr = 0
  errlog = ""
  flen = len(flines)
  for i in range(1,flen):
    curlen = len(flines[i].replace("\n", "").split('\t'))
    if not chk_len == curlen:
      if(taberr == 0):
        errlog = "Expecting %s tabs on each line\n" % (chk_len-1)
        taberr = 1
      errlog += "Line no: %s; Tabs: %s\n" % (i+1, curlen-1)

  if(taberr == 1):
    fname = os.path.join("textdata_issues", fname.replace("\\", "__"))
    f = open(fname, "w")
    f.write(errlog)
    f.close()
  return taberr

def gen_table_sql(filename, metadata):
    # Read linked table file.
    input_lines = open(filename).readlines()

    #if(validate_textfile_tabdata(filename, input_lines)):
    #  return ""

    # Read column names specified in linked table.
    specified_column_names = input_lines[0].strip("\n\r\t").split("\t")
    #v#specified_column_names.append('created_by')
    #v#specified_column_names.append('modified_by')
    #v#specified_column_names.append('creation_date')
    #v#specified_column_names.append('modified_date')
    specified_column_names = map(lambda x: '"' + x.lower() + '"', specified_column_names)
    table_column_names = input_lines[0].strip("\n\r\t").split("\t")
    table_column_names = map(lambda x: x.lower(), table_column_names)
    # Additional columns.
    table_column_names.append('__mlocate__created_by')
    table_column_names.append('__mlocate__modified_by')
    table_column_names.append('__mlocate__created_date')
    table_column_names.append('__mlocate__modified_date')
    validate_column_names(table_column_names)

    # Read column types specified in linked table.
    specified_column_types = input_lines[1].strip("\n\r\t").split("\t")
    table_column_types = input_lines[1].strip("\n\r\t").split("\t")
    # Additional column types.
    table_column_types.append('INTEGER')
    table_column_types.append('INTEGER')
    table_column_types.append('DATE')
    table_column_types.append('DATE')
    validate_column_types(table_column_types)

    # The table name to load the data.
    #table_name = filename.split("/")[-1].split(".")[0]
    table_name = metadata['resource_tablename']

    if len(table_column_types) != len(table_column_names):
        print "Can not read meta data.", table_column_names, table_column_types

    # Generate Table field definitions.
    i = 0
    field_defs = """"""
    while i < len(table_column_names) - 1:
        field_defs = field_defs + ('  "%s"\t%s,\n' % ( table_column_names[i], SQL_COLUMNS[table_column_types[i]]))
        i = i + 1
    field_defs = field_defs + ('  "%s"\t%s' % ( table_column_names[i], SQL_COLUMNS[table_column_types[i]]))


    # Start writing the SQL file.
    # Code to connect to database and create the table.
    createsql = """
SET client_encoding = Latin1;

-- Create the table
CREATE TABLE "%s" (
  "__mlocate__id" bigserial NOT NULL,
  PRIMARY KEY ("__mlocate__id"),
%s
);

""" % (table_name, field_defs)


    insertsql = ""
    # Code to generate insert statements to load the data.
    chk_len = len(specified_column_names)
    for data_line in input_lines[2:]:
        data_line = data_line.strip("\n\r")
        vals = []
        data_arr = data_line.split("\t", chk_len-1)
        for i in range(0, len(data_arr)):
            if(data_arr[i] == ''):
                vals.append("NULL")
            elif specified_column_types[i] == "INTEGER" or specified_column_types[i] == "DECIMAL":
                vals.append("%s" % data_arr[i])
            else:
                vals.append("'%s'" % data_arr[i].replace("'","''"))

        # append NULL values when number of tabs are less in a row
        len_diff = (chk_len - len(vals))
        if len_diff:
            vals.extend(["NULL"]*len_diff)

        y = """INSERT INTO "%s"(%s) VALUES (%s);
""" % ( table_name, ",".join(specified_column_names), ",".join( vals ))
#""" % ( table_name, ",".join(specified_column_names), ",".join( [ "'%s'" % k.replace("'","''") for k in data_line.split("\t", chk_len)] ))

        insertsql += y

    # Code to set created by and modified_by variables.
    updatesql = """
UPDATE "%s" set __mlocate__created_by = '1';
UPDATE "%s" set __mlocate__modified_by = '1';

UPDATE "%s" set __mlocate__created_date = now();
UPDATE "%s" set __mlocate__modified_date = now();
""" % (table_name, table_name, table_name, table_name)

    commentssql = ''
    if 'Column_Description' in metadata:
      commentssql = addcolcomments(table_name, metadata['Column_Description'])
      commentssql += '\n'
      
    commentssql += """COMMENT ON COLUMN "%s"."__mlocate__created_by" IS 'Created By';\n""" % table_name
    commentssql += """COMMENT ON COLUMN "%s"."__mlocate__created_date" IS 'Created On';\n""" % table_name
    commentssql += """COMMENT ON COLUMN "%s"."__mlocate__modified_by" IS 'Modified By';\n""" % table_name
    commentssql += """COMMENT ON COLUMN "%s"."__mlocate__modified_date" IS 'Modified On';\n""" % table_name

    cols = ','.join(map(lambda x: '"' + x.lower() + '"', COLNAME_COLS))
    vals_arr = []
    for col in COLNAME_COLS:
      vals_arr.append(metadata[col].replace("'", "''"))
    vals = ','.join(map(lambda x: "'" + x.lower() + "'", vals_arr))
    #displayed_columns = metadata['displayed_columns'].replace("'", "''")
    #url_columns = metadata['url_columns'].replace("'", "''")
    #italics_columns = metadata['italics_columns'].replace("'", "''")

#    metasql = """
#INSERT INTO "Meta_Global_Resource" ("resource_tablename", "displayed_columns", "created_by", "created_date", "modified_by", "modified_date")
#  VALUES ('%s', '%s', 1, now(), 1, now());
#""" % (table_name, displayed_columns)
    metasql = """
INSERT INTO "Meta_Global_Resource" ("resource_tablename", %s, "created_by", "created_date", "modified_by", "modified_date")
  VALUES ('%s', %s, 1, now(), 1, now());
""" % (cols, table_name, vals)

    return "\nBEGIN;\n%s\nEND;\n" % (createsql+commentssql+insertsql+updatesql+metasql)

def addcolcomments(tablename, theDict):
  sql = ''
  for col in theDict:
    try:
      sql += """COMMENT ON COLUMN "%s"."%s" IS '%s';\n""" % (tablename, col.lower(), theDict[col].replace("'", "''"))
    except:
      print "@REM # -- "+col
      print "@REM # -- "+str(theDict[col])
  return sql

def parsemetadata(pth):
  f = open(pth)
  lines = f.readlines()
  metadataDictStr = "{\n"
  spc = '  '
  inColDesc = 0
  for line in lines:
    if line == "\n":
      continue
    elif line.startswith("$"):
      metadataDictStr += spc + '"Column_Description": {\n'
      inColDesc = 1
      continue
    else:
      key, sep, val = line.partition(":")
      if key.strip() == '':
        continue
      indnt = ''
      if inColDesc == 1:
        indnt = '  '
      if(key.strip() == 'resource_tablename'):
        val = val.replace(".txt", "")
      #elif(key.strip() == 'displayed_columns'):
      elif key.strip() in COLNAME_COLS:
        val = val.lower()
      metadataDictStr += indnt + spc + '"' + escapeStr(key) + '" : "' + escapeStr(val) + '",\n'
  if inColDesc == 1:
    metadataDictStr += spc + "},\n"
  metadataDictStr += "},\n"
  return metadataDictStr

def getresourceinfo(pth):
  global sqlsdir, DBNAME, DBUSER
  lst = os.listdir(pth)
  for itm in lst:
    pth1 = os.path.join(pth, itm)
    if(os.path.isdir(pth1)):
      fls = os.listdir(pth1)
      metadatafl = ""
      resourcetablefl = ""
      for fl1 in fls:
        fl = os.path.join(pth1, fl1)
        if(os.path.isfile(fl)):
          if(fl1.endswith("metadata.txt")):
            metadatafl = fl
          elif(fl1.endswith(".txt")):
            resourcetablefl = fl

      x = parsemetadata(metadatafl)
      #print "\n/* Metadata: \n%s*/" %x
      metadata = eval(x)[0]

      sql = gen_table_sql(resourcetablefl, metadata)

      if sql == "":
        print "@REM # Error in data: %s. Check textdata_issues." % flname
      else:
        flname = os.path.join(os.getcwd(), sqlsdir, metadata['resource_tablename']+".sql")
        f = open(flname, "w")
        f.write(sql)
        f.close
        #convert_to_utf8(flname)
        print '''psql -d %s -U %s -f "%s" > rlogs/%s.log 2>&1''' % (DBNAME, DBUSER, flname, metadata['resource_tablename'])

def main(pth):
  global sqlsdir
  if(os.path.exists(sqlsdir)):
    shutil.rmtree(sqlsdir)
  os.mkdir(sqlsdir)
  if(os.path.exists("rlogs")):
    shutil.rmtree("rlogs")
  os.mkdir("rlogs")
  getresourceinfo(pth)


############################################################
if len(sys.argv) < 4:
  #print "I need a base directory to search for dbf and metadata."
  #sys.exit()
  #pth = "D:\Code\ATree\DB\data\layerimports\data"; #Default path.
  #pth = r"D:\Code\ATree\DB\FilesFromATREE\mlocate_data_load\resourcetables"
  print "USAGE: %s DBNAME DBUSER DATA_PATH" % sys.argv[0]
  sys.exit()
else:
  global DBNAME, DBUSER
  DBNAME = sys.argv[1]
  DBUSER = sys.argv[2]
  pth = sys.argv[3]
  pth = pth.replace("\\", "\\\\")
  #print "Searching in ", pth

main(pth)
