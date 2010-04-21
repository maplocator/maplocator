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

import os, sys, shutil, string

theme = ''
category = ''
filename = "metadata.txt"
GEOMCOL = "__mlocate__topology"
#shp2pgsql = r"C:\Program Files\PostgreSQL\8.3\bin\shp2pgsql"
shp2pgsql = "shp2pgsql"
layer_tablename = str(sys.argv[1])
DBNAME = str(sys.argv[2])
DBUSER = str(sys.argv[3])
opFilename = str(sys.argv[4])
count = len(sys.argv)
if(count > 5):
    theme =  str(sys.argv[5])

if(count > 6):
    category =  str(sys.argv[6])

def_license = "(by-nc)"

def runCmd(cmmd):
    import popen2

    r, w, e = popen2.popen3(cmmd)
    a = e.readlines()
    b = r.readlines()
    r.close()
    e.close()
    try:
        w.close()
    except:
        pass
    return a,b

def getLinkTables(lyr_frst_col):
    linktables = []
    if(os.path.exists("linktable")):
        lnkfiles = os.listdir("linktable")
        for lnkfile in lnkfiles:
            if (lnkfile.endswith(".txt")):
                linktable = []
                f = open("linktable/%s" % lnkfile)
                try:
                    frst_col = ""
                    char = f.read(1)
                    while char:
                        if(char == '\t'):
                            break
                        frst_col += char
                        char = f.read(1)
                    #fl_line = f.readline()
                    #print fl_line
                    #if (fl_line[7] == '\t'):
                    #    print 'tab'
                    linktable.append("*Meta_LinkTable")
                    linktable.append("link_tablename : %s" % lnkfile)
                    linktable.append("link_name : %s" % lnkfile.replace(".txt", ""))
                    linktable.append("description : %s" % lnkfile.replace(".txt", ""))
                    linktable.append("created_by : ")
                    linktable.append("created_date : ")
                    linktable.append("modified_by : ")
                    linktable.append("modified_date : ")
                    linktable.append("status : 1")
                    linktable.append("summary_columns : '%s'" % frst_col)
                    linktable.append("linked_column : '%s'" % frst_col)
                    linktable.append("layer_column : '%s'" % lyr_frst_col)
                    linktable.append("access : 0")
                    linktable.append("search_columns : '%s'" % frst_col)
                    linktable.append("editable_columns: ")
                    linktable.append("is_filterable : 0")
                    linktable.append("filter_columns : ")
                    linktable.append("italics_columns : ")
                    linktable.append("")
                    linktables.extend(linktable)
                finally:
                    f.close()
    return linktables

def genMetaData(layer_tablename):
    shpfile = "%s.shp" % layer_tablename 
    cmmd = '%s -s %s -I -%s -g %s "%s" "%s" %s' % (shp2pgsql, "-1", "%s", GEOMCOL, shpfile, layer_tablename, DBNAME)
    a,b = runCmd(cmmd % "p")
    layer_type = a[1].replace("Postgis type: ", "").replace("\n", "")
    indx = layer_type.find("[")
    if indx != -1:
      layer_type = layer_type[0:indx]

    indx = b[2].find('"', 1)
    frst_col = (b[2])[1:indx]
    
    linktables = getLinkTables(frst_col)
    #print ('\n').join(linktables)

    metadata = []
    metadata.append("*Meta_Layer")
    metadata.append("layer_name : %s" % layer_tablename)
    metadata.append("layer_tablename : %s" % string.lower(layer_tablename))
    metadata.append("layer_description : %s" % layer_tablename)
    metadata.append("status : 1")
    metadata.append("created_by : ")
    metadata.append("created_date : ")
    metadata.append("modified_by : ")
    metadata.append("modified_date : ")
    metadata.append("min_scale : ")
    metadata.append("max_scale : ")
    metadata.append("pdf_link : ")
    metadata.append("url : ")
    metadata.append("aggregation : ")
    metadata.append("attribution : ")
    metadata.append("license : %s" % def_license)
    metadata.append("url : ")
    metadata.append("lineage : ")
    metadata.append("tags : ")
    metadata.append("comments : ")
    metadata.append("access : 0")
    metadata.append("layer_type : %s" % layer_type)
    metadata.append("summary_columns : '%s'" % frst_col)
    metadata.append("editable_columns : ")
    metadata.append("is_filterable : 0")
    metadata.append("filter_columns : ")
    metadata.append("search_columns : '%s'" % frst_col)
    metadata.append("title_column : '%s'" % frst_col)
    metadata.append("color_by : ")
    metadata.append("size_by : ")
    metadata.append("page_info : ")
    metadata.append("")

    metadata.append("*Theme_Layer_Mapping")
    #metadata.append("theme_id : General")
    if('' != theme):
      metadata.append("geo_id: %s" % theme)
    elif DBNAME.lower().find("uap") != -1:
      metadata.append("geo_id : Stockholm")
    elif DBNAME.lower().find("ibp") != -1:
      metadata.append("geo_id : India")
    if('' != category):
      metadata.append("theme_id: %s" % category)
    else:
      metadata.append("theme_id : General")

    metadata.append("")

    metadata.extend(linktables)

    return metadata

def main():
    metadata = genMetaData(layer_tablename)
    
    f = open(opFilename, 'w')
    try:
        f.write(('\n').join(metadata))
    except Exception, e:
        print e
    finally:
        f.close()

main()
