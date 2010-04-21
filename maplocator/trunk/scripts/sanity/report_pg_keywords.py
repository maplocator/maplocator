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

PSQL = 'psql'

PG_KeyWords = [
'AUTHORIZATION',
'BETWEEN',
'BINARY',
'CROSS',
'FREEZE',
'FULL',
'ILIKE',
'INNER',
'IS',
'ISNULL',
'JOIN',
'LEFT',
'LIKE',
'NATURAL',
'NOTNULL',
'OUTER',
'OVERLAPS',
'RIGHT',
'SIMILAR',
'VERBOSE',
'FALSE',
'TRUE',
'ALL',
'ANALYSE',
'ANALYZE',
'AND',
'ANY',
'ARRAY',
'AS',
'ASC',
'ASYMMETRIC',
'BOTH',
'CASE',
'CAST',
'CHECK',
'COLLATE',
'COLUMN',
'CONSTRAINT',
'CREATE',
'CURRENT_DATE',
'CURRENT_ROLE',
'CURRENT_TIME',
'CURRENT_TIMESTAMP',
'CURRENT_USER',
'DEFAULT',
'DEFERRABLE',
'DESC',
'DISTINCT',
'DO',
'ELSE',
'END',
'EXCEPT',
'FOR',
'FOREIGN',
'FROM',
'GRANT',
'GROUP',
'HAVING',
'IN',
'INITIALLY',
'INTERSECT',
'INTO',
'LEADING',
'LIMIT',
'LOCALTIME',
'LOCALTIMESTAMP',
'NEW',
'NOT',
'NULL',
'OFF',
'OFFSET',
'OLD',
'ON',
'ONLY',
'OR',
'ORDER',
'PLACING',
'PRIMARY',
'REFERENCES',
'RETURNING',
'SELECT',
'SESSION_USER',
'SOME',
'SYMMETRIC',
'TABLE',
'THEN',
'TO',
'TRAILING',
'UNION',
'UNIQUE',
'USER',
'USING',
'WHEN',
'WHERE',
'WITH'
]

# query to get all <type>_tablename from <Meta table>
sql_tablenames = """ select %s_tablename from "%s" """

# query to get column names in table which are PG keywords
sql_cols = """ SELECT distinct pg_catalog.quote_ident(attname) as column_name FROM pg_catalog.pg_attribute WHERE attnum > 0 AND attisdropped IS FALSE AND attrelid = (SELECT oid FROM pg_class WHERE relname = '%s') AND pg_catalog.quote_ident(attname) like ('%"%'); """

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

def reportkeywordsfortable(tablename):
  global sql_cols
  a, b =  runSqlCmd(sql_cols.replace('%s', tablename))
  if len(a) > 0:
    print "Error: Table: %s; Msg: %s" % (tablename, ''.join(a))
    return
  c = b[0:-1]
  # TODO: Right now the script reports all column names which have been escaped and not just PG keywords.
  if len(c) > 0:
    print tablename, striparrayvals(c)

###############################################
##                                           ##
###############################################
def parsetables(table_type):
  print "========= %s ========" % table_type
  tablenames = gettablenames(table_type)
  for tablename in tablenames:
    if tablename != "":
      reportkeywordsfortable(tablename)
  print "========= end %s ========" % table_type

def main():
  parsetables('Layer')
  parsetables('Link')
  parsetables('Resource')
  pass

################ start of execution ##############
if len(sys.argv) != 3:
  print "Usage: %s DBNAME DBUSER" % sys.argv[0]
else:
  DBNAME = sys.argv[1]
  DBUSER = sys.argv[2]
  main()
