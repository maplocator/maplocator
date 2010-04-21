"""
This script is used to generate a JavaScript file which is a concatenation
and minification of all the files which are found between two lines of a html
file.

These two lines are delimited by the strings START_MERGE and END_MERGE for
starting and end lines respectively.
"""

import sys
import re
import os.path
import urllib2
from jsmin import jsmin

# Minify the javascript?
MINIFY = True

def main():
    if len(sys.argv) != 3:
        print "Usage: %s PATH_TO/<FILE>html PATH_TO/<OUTPUT_MERGED>.js" % sys.argv[0]
        sys.exit(1)
    extract_js_url = False
    urls = []
    map_path = sys.argv[1]
    base_path = os.path.dirname(map_path)

    for l in open(map_path):
        if "START_MERGE" in l:
            extract_js_url = True
            continue
        if "END_MERGE" in l:
            extract_js_url = False
            continue
        if not extract_js_url:
            continue
        # Skip comments
        if "<!--" in l:
            continue
        m = re.search("""src=["']([^"']+)["']""", l)
        if not m:
            continue
        urls.append(m.group(1))

    merged_js = ""
    for u in urls:
        if not MINIFY:
            merged_js += "\n// Including file %s\n\n" % u
        if u.startswith("http://"):
            f = urllib2.urlopen(u)
        elif u.startswith("mfbase"):
            f = open(os.path.join(base_path, "..", "..", "..",
                                  "MapFish", "client", u))
        else:
            f = open(os.path.join(base_path, u))
        merged_js += f.read()

    output = "// This file is generated, do not edit!\n\n"
    if MINIFY:
        output += jsmin(merged_js)
    else:
        output += merged_js
    open(sys.argv[2], "w").write(output)
    print "%s: End of merge" % sys.argv[0]

if __name__ == "__main__":
    main()

