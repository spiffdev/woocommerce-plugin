"""
Python2 script that replaces environment-dependent constants in the PHP files.
"""

import sys

env = sys.argv[1]
path = sys.argv[2]

def replaceConstants(file):
    lines = file.readlines()
    for line in lines:
        if line.find('define("SPIFF_API_BASE"') > -1:
            if (env == 'prod'):
                print 'define("SPIFF_API_BASE", "https://api.spiff.com.au");'
            else:
                print 'define("SPIFF_API_BASE", "https://api.app.dev.spiff.com.au");'
        else:
            print line,

with open(path) as file:
    replaceConstants(file)
