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

import os, sys, shutil

fromstr = str(sys.argv[1])
tostr = str(sys.argv[2])

def main():
  pth = "layersqls"
  fls = os.listdir(pth)
  for fln1 in fls:
    fln1 = os.path.join(pth, fln1)
    fln2 = fln1 + "_tmp"
    
    f1 = open(fln1)
    f2 = open(fln2, "w")

    fl1 = f1.read()
    fl1 = fl1.replace(fromstr, tostr)
    f2.write(fl1)
    f1.close()
    f2.close()

    shutil.move(fln2, fln1)

main()
