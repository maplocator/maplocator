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

# DBNAME = "IBPCCK"
# DBUSER = "postgres"
# path to psql command
PSQL = "psql"

################ Column types ##############

# Column types for Meta_Layer
ML_COLUMN_TYPES = [
  'summary_columns',
  'filter_columns',
  'search_columns',
  'color_by',
  'title_column',
  'size_by',
  'editable_columns',
  'media_columns'
]

# Column types for Meta_LinkTable
MLT_COLUMN_TYPES = [
'summary_columns',
'linked_column',
'layer_column',
'search_columns',
'editable_columns',
'filter_columns'
]

# Column types for Resource_Table
RT_COLUMN_TYPES = [
'displayed_columns'
]

# ignore
COLUMN_TYPES = [
  'summary_columns',
  'filter_columns',
  'search_columns',
  'editable_columns',
  'title_column',
  'color_by',
  'size_by',
  'linked_column',
  'layer_column',
  'resource_column',
  'table_column'
]

################ end Column types ##############

################## SQLs ########################

# query to get all <type>_tablename from <Meta table>
sql_tablenames = """ select %s_tablename from "%s" """

# query to get column names of particular type from Meta_<type> for particular <type>_tablename
sql_layer_columnnames = """ select %s from "%s" where %s_tablename = '%s' """

# query to get column names of particular type from Meta_Layer for particular layer_tablename
sql_layer_columnnames = """ select %s from "Meta_Layer" where layer_tablename = '%s' """

# query to get column names of particular type from Meta_LinkTable for particular link_tablename
sql_link_columnnames = """ select %s from "Meta_LinkTable" where link_tablename = '%s' """

# query to get column names of particular type from Meta_Global_Resource for particular resource_tablename
sql_resource_columnnames = """ select %s from "Meta_Global_Resource" where resource_tablename = '%s' """

# query to find if given columns exist in a table
sql_tablecols = """ SELECT distinct pg_catalog.quote_ident(attname) as column_name FROM pg_catalog.pg_attribute WHERE attnum > 0 AND attisdropped IS FALSE AND attrelid = (SELECT oid FROM pg_class WHERE relname = '%s') AND pg_catalog.quote_ident(attname) in (%s) """

#sql_layers_for_links = """ select ml.layer_tablename, mlt.layer_column from "Meta_LinkTable" mlt, "Meta_Layer" ml where mlt.layer_id = ml.layer_id group by mlt.layer_id, mlt.layer_column, ml.layer_tablename order by mlt.layer_id, mlt.layer_column """

# query to find layer_name, layer_tablename, layer_column given a link_tablename
sql_layer_from_link = """ select ml.layer_name, ml.layer_tablename, mlt.layer_column from "Meta_LinkTable" mlt left join "Meta_Layer" ml on mlt.layer_id = ml.layer_id where mlt.link_tablename = '%s' """

# query to get all resource_tablname, resource_column pair mapped to tablename, table_column pair
sql_resource_mapping = """ select resource_tablename, resource_column, tablename, table_column, table_type from "Global_Resource_Mapping" """

################## end SQLs ########################

################ methods ##############

# generate the psql command to run a single line query from command prompt
def genSqlCmd(sql):
  global PSQL, DBNAME, DBUSER
  cmmd = """ %s -d %s -U %s -t -c "%s" """ % (PSQL, DBNAME, DBUSER, sql.replace('"', '""'))
  return cmmd

# generate and run a psql command from the sql query given.
def runSqlCmd(sql):
  return runCmd(genSqlCmd(sql))

# function to run commands at command prompt
def runCmd(cmmd):
  import popen2

  r, w, e = popen2.popen3(cmmd)
  a = e.readlines() # error messages
  b = r.readlines() # output of command
  r.close()
  e.close()
  try:
    w.close()
  except:
    pass
  return a,b

# strip spaces and '\n' from all elements in an array
def striparrayvals(arr):
  return map(lambda x: x.strip(" \n") , arr)

######################################################################
## get table names of specific types from corresponding Meta table  ##
## Parameters:                                                      ##
##   table_type: Layer/ Link/ Resource                              ##
## Return:                                                          ##
##   array of table names                                           ##
######################################################################
def gettablenames(table_type):
  global sql_tablenames
  if table_type == "Layer":
    sql_tables = sql_tablenames % ('layer', 'Meta_Layer')
  elif table_type == "Link":
    sql_tables = sql_tablenames % ('link', 'Meta_LinkTable')
  elif table_type == "Resource":
    sql_tables = sql_tablenames % ('resource', 'Meta_Global_Resource')

  a,b = runSqlCmd(sql_tables)
  if len(a) > 0:
    print a
  tablenames = b[0:-1]
  tablenames = striparrayvals(tablenames)
  return tablenames

###############################################################
## get coulmns names of specific types for tables from db    ##
## Parameters:                                               ##
##   table_type: Layer/ Link/ Resource                       ##
##   tablename: tablename for which to get the column names  ##
##   col_type: summary/ editable/ search/ etc.               ##
## Return:                                                   ##
##   array of column names                                   ##
###############################################################
def gettablecolumns(table_type, tablename, col_type):
  if tablename == '':
    return []

  if table_type == 'Layer':
    global sql_layer_columnnames
    sql_columnnames = sql_layer_columnnames
  elif table_type == 'Link':
    global sql_link_columnnames
    sql_columnnames = sql_link_columnnames
  elif table_type == 'Resource':
    global sql_resource_columnnames
    sql_columnnames = sql_resource_columnnames

  a,b = runSqlCmd(sql_columnnames % (col_type, tablename))
  if len(a) > 0:
    print a
  return striparrayvals((b[0:-1])[0].split(","))

##########################################################
## If the coulmns name in metadata does not start/ end  ##
##  with "'" report it as error                         ##
## Parameters:                                          ##
##   cols: array of column names                        ##
## Return:                                              ##
##   list of erronous column names                      ##
##########################################################
def sanitizecolumnnames(cols):
  cols = cols.strip(" \n")
  cols_lst = cols.split(",")
  err_cols = ""
  for col in cols_lst:
    if col != "":
      if not col.startswith("'") or not col.endswith("'"):
        err_cols += col + ","

  return err_cols[0: -1]

###############################################################
## get the column names from metadata and sanitize.          ##
## Parameters:                                               ##
##   table_type: Layer/ Link/ Resource                       ##
##   tablename: tablename for which to get the column names  ##
##   col_type: summary/ editable/ search/ etc.               ##
## Return:                                                   ##
##   array of column names                                   ##
###############################################################
def getandsanitizetablecolumns(table_type, tablename, col_type):
  cols = gettablecolumns(table_type, tablename, col_type)
  sql_in_cols = ''
  for col in cols:
    if col != '':
      sql_in_cols += "%s," % col

  sql_in_cols = sql_in_cols[0:-1]
  sanitizecolumns(table_type, tablename, col_type, sql_in_cols)

#####################################################################
## sanitize the column names specified in metadata for that table. ##
## Parameters:                                                     ##
##   table_type: Layer/ Link/ Resource                             ##
##   tablename: tablename for which to get the column names        ##
##   col_type: summary/ editable/ search/ etc.                     ##
##   sql_in_cols: column names in format 'col1','col2'             ##
##                 to be used directly in 'IN' clause.             ##
## output:                                                         ##
##   print the error message                                       ##
#####################################################################
def sanitizecolumns(table_type, tablename, col_type, sql_in_cols):
  # sanitize the column names for "'"
  err_cols = sanitizecolumnnames(sql_in_cols)
  if err_cols != "":
    print """>>>>>>>>>>>
Error: "'" missing in column values in metadata:
 %s tablename: %s
 Column type: %s
 Columns in metadata: %s
-----------
""" % (table_type, tablename, col_type, err_cols)
    return

  # check if the columns specified in metadata do exist in the table
  err_str = ">>>>>>>>>>>\nError: Column names do not match\n "
  if(sql_in_cols != ''):
    global sql_tablecols
    a,b = runSqlCmd(sql_tablecols % (tablename, sql_in_cols))
    if len(a) > 0:
      for x in a:
        err_str += x
    x = striparrayvals(b[0:-1])
    cols = sql_in_cols.split(",")
    y = map(lambda x: x.strip("'"), cols)
    x.sort()
    y.sort()
    if x != y:
      err_str += "%s tablename: %s\n Column type: %s\n Columns in table: %s\n Columns in metadata: %s\n" % (table_type, tablename, col_type, ','.join(x), ','.join(y))
      err_str += "Diff: %s & %s\n" % ([item for item in y if not item in x], [item for item in x if not item in y])

  if err_str != ">>>>>>>>>>>\nError: Column names do not match\n ":
    print err_str + "-----------\n"

######################################################
##  get the layer_name, layer_tablename, mapped     ##
##    layer_column for given link table             ##
######################################################
def getlayerinfoforlink(tablename):
  global sql_layer_from_link
  a,b = runSqlCmd(sql_layer_from_link % tablename)
  if len(a) > 0:
    print a
  vals = striparrayvals(b[0].split("|"))
  return vals[0], vals[1], vals[2]

#########################################################################
##  check sanity for the mapped layer_column for given link_tablename  ##
#########################################################################
def sanitizemappedlayercolumn(table_type, tablename):
  layer_name, layer_tablename, layer_column = getlayerinfoforlink(tablename)
  sanitizecolumns('Layer' , layer_tablename, 'Layer column mapped in %s' % tablename, layer_column)

###############################################
##  check sanity for the data in Meta_Layer  ##
###############################################
def sanitizelayer():
  print "========= Meta_Layer ========"
  global ML_COLUMN_TYPES
  layer_tablenames = gettablenames('Layer')
  for layer_tablename in layer_tablenames:
    if layer_tablename != "":
      for col_type in ML_COLUMN_TYPES:
        getandsanitizetablecolumns('Layer', layer_tablename, col_type)
  print "========= end Meta_Layer ========"

###################################################
##  check sanity for the data in Meta_LinkTable  ##
###################################################
def sanitizelink():
  print "========= Meta_LinkTable ========"
  global MLT_COLUMN_TYPES
  link_tablenames = gettablenames('Link')
  for link_tablename in link_tablenames:
    if link_tablename != "":
      for col_type in MLT_COLUMN_TYPES:
        if col_type == "layer_column":
          sanitizemappedlayercolumn('Link', link_tablename)
        else:
          getandsanitizetablecolumns('Link', link_tablename, col_type)
          pass
  print "========= end Meta_LinkTable ========"

#########################################################
##  check sanity for the data in Meta_Global_Resource  ##
#########################################################
def sanitizeresource():
  print "========= Meta_Global_Resource ========"
  global RT_COLUMN_TYPES
  tablenames = gettablenames('Resource')
  for tablename in tablenames:
    if tablename != "":
      for col_type in RT_COLUMN_TYPES:
        getandsanitizetablecolumns('Resource', tablename, col_type)
  print "========= end Meta_Global_Resource ========"

############################################################
##  check sanity for the data in Global_Resource_Mapping  ##
############################################################
def sanitizeresourcemapping():
  print "========= Global_Resource_Mapping ========"
  global sql_resource_mapping
  a,b = runSqlCmd(sql_resource_mapping)
  if len(a) > 0:
    print a
  rows = b[0:-1]
  for row1 in rows:
    row = striparrayvals(row1.split("|"))
    sanitizecolumns('Resource' , row[0], 'Resource column mapped in %s:%s' % (row[2],row[3]), row[1])
    sanitizecolumns(row[4] , row[2], 'Table column mapped in resource %s:%s' % (row[0],row[1]), row[3])
  print "========= end Global_Resource_Mapping ========"

## the main function
def main():
  sanitizelayer()
  sanitizelink()
  sanitizeresource()
  sanitizeresourcemapping()

################ end methods ##############

################ start of execution ##############
if len(sys.argv) != 3:
  print "Usage: %s DBNAME DBUSER" % sys.argv[0]
else:
  DBNAME = sys.argv[1]
  DBUSER = sys.argv[2]
  main()
