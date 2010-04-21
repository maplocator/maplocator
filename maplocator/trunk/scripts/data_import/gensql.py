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

import os,sys

DBNAME = "IBPCCK"
DBUSER = "postgres"
GEOMCOL = "topology"
#shp2pgsql = r"C:\Program Files\PostgreSQL\8.3\bin\shp2pgsql"
shp2pgsql = "shp2pgsql"
psql = "psql"
cwdir = os.getcwd()

themes = {
"Abiotic: Soil, Water and Climate": "2",
"Conservation Areas": "3",
"Demography": "4",
"Administrative Units": "5",
"Land Use / Land Cover": "11",
"Species/Taxa": "9",
"Biogeography": "10",
"Vembanad": "12",
"Nilgiri_BR": "13"
}

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

def encodeUTF8(str1):
    print str1
    return unicode(str1, "utf-8")

def insertSql(fln1, sql, sql2):
    fln2 = fln1+"_tmp"
    #import codecs
    #f1 = codecs.open( fln1, "r", "utf-8" )
    f1 = open(fln1)
    f2 = open(fln2, "w")

    i = 0;
    j = 0;
    for line in f1:
        if(i == 1):
            f2.write(sql+"\n")
        if(j == 0 and line.startswith("CREATE INDEX")):
            f2.write(sql2)
            j += 1
        #f2.write(encodeUTF8(line))
        f2.write(line)
        i += 1

    f1.close()
    f2.close()

    import shutil
    shutil.move(fln2, fln1)

def getlayerinfo(pth):
  opt = "[ \n"

  for root, dirs, files in os.walk(pth):
    for dirname in dirs:
      if dirname == "final":
        pth1 = os.path.join(root, dirname)
        fls = os.listdir(pth1)
        dbffl = ""
        shpfl = ""
        layer_name = ""
        layer_tablename = ""
        layer_description = ""
        layer_license = ""
        layer_attribution = ""
        layer_summary_columns = ""
        layer_related_layers = ""
        layer_link_tablename = ""
        theme_id = ""
        for flname in fls:
          if(flname.endswith(".dbf")):
            dbffl = os.path.join(pth1, flname)
          elif(flname.endswith(".shp")):
            shpfl = os.path.join(pth1, flname)
          elif(flname.endswith("metadata.txt") or flname.endswith("meta.txt") ):
            metadatafl = os.path.join(pth1, flname)
            f = open(metadatafl)
            try:
              for line in f:
                if(line.strip().startswith("layer_name")):
                  layer_name = line[len("layer_name"):].strip()
                elif(line.strip().startswith("layer_tablename")):
                  layer_tablename = line[len("layer_tablename"):].strip()
                elif(line.strip().startswith("layer_description")):
                  layer_description = line[len("layer_description"):].strip()
                elif(line.strip().startswith("status")):
                  layer_status = line[len("status"):].strip().replace('"', "'")
                elif(line.strip().startswith("min_scale")):
                  layer_min_scale = line[len("min_scale"):].strip().replace('"', "'")
                  layer_min_scale = (layer_min_scale,"0")[layer_min_scale == ""]
                elif(line.strip().startswith("max_scale")):
                  layer_max_scale = line[len("max_scale"):].strip().replace('"', "'")
                  layer_max_scale = (layer_max_scale,"0")[layer_max_scale == ""]
                elif(line.strip().startswith("pdf_link")):
                  layer_pdf_link = line[len("pdf_link"):].strip().replace('"', "'")
                elif(line.strip().startswith("url")):
                  layer_url = line[len("url"):].strip().replace('"', "'")
                elif(line.strip().startswith("aggregation")):
                  layer_aggregation = line[len("aggregation"):].strip().replace('"', "'")
                elif(line.strip().startswith("attribution")):
                  layer_attribution = line[len("attribution"):].strip()
                elif(line.strip().startswith("lineage")):
                  layer_lineage = line[len("lineage"):].strip().replace('"', "'")
                elif(line.strip().startswith("tags")):
                  layer_tags = line[len("tags"):].strip().replace('"', "'")
                elif(line.strip().startswith("license")):
                  layer_license = line[len("license"):].strip().replace('"', "'")
                elif(line.strip().startswith("summary_columns")):
                  layer_summary_columns = line[len("summary_columns"):].strip().replace('"', "'")
                elif(line.strip().startswith("comments")):
                  layer_comments = line[len("comments"):].strip().replace('"', "'")
                elif(line.strip().startswith("theme_id")):
                  theme_id = line[len("theme_id"):].strip().replace('"', "'")
                # elif(line.strip().startswith("related_layers")):
                  # layer_related_layers = line[len("related_layers"):].strip()
                # elif(line.strip().startswith("link_tablename")):
                  # layer_link_tablename = line[len("link_tablename"):].strip()
            finally:
              f.close()

        # Additional processing on the layers
        # remove .dbf from layer_tablename
        layer_tablename = layer_tablename.replace(".dbf", "")
        # TODO:
        # We need similar processing for summary_columns, related_layers, link_tablename

        layer_info = "{" + "\n"
        layer_info += "  'shp_filename': " + '"' + shpfl.replace("\\", "\\\\") + '",' + "\n"
        layer_info += "  'layer_name': " + '"' + layer_name + '",' + "\n"
        #layer_info += "  'dbf_filename': " + '"' + dbffl + '",' + "\n"
        #layer_info += "  'layer_fields': " + '%s, \n' % get_fields(dbffl) 
        layer_info += "  'layer_tablename' : " + '"' + layer_tablename + '",' + "\n"
        layer_info += "  'layer_description' : " + '"' + layer_description + '",' + "\n"
        layer_info += "  'status': " + '"' + layer_status + '",' + "\n"
        layer_info += "  'min_scale': " + '"' + layer_min_scale + '",' + "\n"
        layer_info += "  'max_scale': " + '"' + layer_max_scale + '",' + "\n"
        layer_info += "  'pdfLink': " + '"' + layer_pdf_link + '",' + "\n"
        layer_info += "  'url': " + '"' + layer_url + '",' + "\n"
        layer_info += "  'aggregation': " + '"' + layer_aggregation + '",' + "\n"
        layer_info += "  'attribution' : " + '"' + layer_attribution + '",' + "\n"
        layer_info += "  'lineage': " + '"' + layer_lineage + '",' + "\n"
        layer_info += "  'tags': " + '"' + layer_tags + '",' + "\n"
        layer_info += "  'licensing' : " + '"' + layer_license + '",' + "\n"
        layer_info += "  'summary_columns' : " + '"' + layer_summary_columns + '",' + "\n"
        layer_info += "  'comments': " + '"' + layer_comments + '",' + "\n"
        #layer_info += "  'related_layers' : " + '"' + layer_related_layers + '",' + "\n"
        #layer_info += "  'link_tablename' : " + '"' + layer_link_tablename + '",' + "\n"
        #sql = """CREATE TABLE "%s" ("comtran" int2, primary key (id)) INHERITS ("Layer_template");""" % layer_tablename
        #layer_info += "  'sql': " + "'" + sql + "'," + "\n"
        #shp_import = shp2pgsql + " -s -1 -I -a -g topology " + shpfl + " " + layer_tablename
        #shp_import = '"%s" -s -1 -I -a -g topology "%s" %s %s | psql -d %s -U %s' %(shp2pgsql, shpfl, layer_tablename, dbname, dbname, dbuser)
        #shp_import = '%s -s -1 -I -p -g topology "%s" %s %s' %(shp2pgsql, shpfl, layer_tablename, dbname)
        #layer_info += "  'shp_import': " + "'" + shp_import + "'," + "\n"
        layer_info += "  'theme_id': " + '"' + theme_id + '",' + "\n"
        layer_info += "}," + "\n"
        opt += layer_info

  opt += " \n] \n"
  return opt
  # extract fields from DBF out of the file.

def dbimport(theDict):
    for layer in theDict:
        cmmd = 'shp2pgsql -s -1 -I -%s -g %s "%s" "%s" %s' % ("%s", GEOMCOL, layer["shp_filename"], layer["layer_tablename"], DBNAME)

        a,b = runCmd(cmmd % "p")

        layer_type = a[0].replace("Shapefile type: ", "").replace("\n", "").upper()

        c = []
        for i in range(1, len(b)):
            d = b[i].replace("\n", "")
            if(i == 1):
                d = d.replace("gid serial PRIMARY KEY,", "PRIMARY KEY (id),")
            if(d.endswith(");")):
                d = d[0:len(d)-1]
                c.append("  " + d)
                c.append('  INHERITS ("Layer_template");')
                break
            else:
                c.append("  " + d)
        #print "sql: %s" % ''.join(c)

##        r, w, e = popen2.popen3(cmmd % "a")
##        print cmmd % "a"
##        a = e.readlines()
##        b = r.readlines()
##        #for line in r:
##        #    b.append(line)
##        print a
##        print b
##        d = b
##        d.insert(1, '\n'.join(c)+"\n")
##        r.close()
##        e.close()
##        try:
##            w.close()
##        except:
##            pass

        sql = '''INSERT INTO "Meta_Layer" ('''
        vals = ""
        for x in layer:
            #print x, layer[x]
            if(x != "shp_filename" and x != "theme_id"):
                sql += '"' + x + '",'
                vals += "'" + layer[x].replace("'", "''") + "',"

        sql += '"layer_type",'
        vals += "'%s'," % layer_type

        sql += '"created_by",'
        vals += "1,"

        sql += '"created_date",'
        vals += "now(),"

        sql += '"modified_by",'
        vals += "1,"

        sql += '"modified_date",'
        vals += "now(),"

        sql = sql[0:len(sql)-1]
        vals = vals[0:len(vals)-1]

        sql = "%s) values (%s);" % (sql, vals)

        sqlfl = os.path.join(cwdir, ("layersqls\%s.sql" % layer["layer_tablename"]))
        a,b = runCmd("%s > %s" %((cmmd % "a"), sqlfl))

        ## TODO: If a table already exists, skip layer. Need to develop a logic
        #sql1 = '''DROP INDEX %s_%s_gist;\nDROP TABLE "%s";\n\n''' % (layer["layer_tablename"],GEOMCOL,layer["layer_tablename"])
        sql1 = "%s\n\n%s\n" % ('\n'.join(c), sql)
        sql2 = '''\nUPDATE "%s" SET layer_id = (SELECT currval('"Meta_Layer_layer_id_seq"')), status = 1;\n''' % layer["layer_tablename"]
        sql2 += '''INSERT INTO "Theme_Layer_Mapping" ("theme_id", "layer_id", "created_by", "created_date", "modified_by", "modified_date", "status") VALUES (%s, (SELECT currval('"Meta_Layer_layer_id_seq"')), 1, now(), 1, now(), 1);\n\n''' % (themes[layer["theme_id"]], )
        insertSql(sqlfl, sql1, sql2)
        #print ('''psql -d %s -U %s -f "%s" > logs\\%s_import.log 2>&1''' % (DBNAME, DBUSER, sqlfl, layer["layer_tablename"]))
        print '''psql -d %s -U %s -q -f "%s"''' % (DBNAME, DBUSER, sqlfl)
        #v#a,b = runCmd('''psql -d %s -U %s < "%s"''' % (DBNAME, DBUSER, sqlfl))
        #v#print ''.join(a)
        #v#print ''.join(b)
        #v#print "\n"
        
        

def main(pth):
    y = getlayerinfo(pth)
    z = eval(y)
    dbimport(z)
    
if len(sys.argv) < 2:
  #print "I need a base directory to search for dbf and metadata."
  #sys.exit()
  pth = "D:\Code\ATree\DB\data\data"; #Default path.
else:
  pth = sys.argv[1]
  #print "Searching in ", pth

main(pth)

##if len(sys.argv) < 2:
##  #print "I need a base directory to search for dbf and metadata."
##  #sys.exit()
##  filename = "layers.list"
##else:
##  filename = sys.argv[1]
##  #print "Searching in ", pth
##x = open(filename)
##y = x.read()
##z = eval(y)
##main(z)

