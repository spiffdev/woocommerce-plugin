"""
Python2 script that replaces environment-dependent constants in the PHP files.
"""

import sys

env = sys.argv[1]

def replaceConstants(file):
    lines = file.readlines()
    for line in lines:
        if line.startswith('define("SPIFF_API_BASE"'):
            if (env == 'prod'):
                print 'define("SPIFF_API_BASE", "https://api.spiff.com.au");'
            else:
                print 'define("SPIFF_API_BASE", "https://api.app.dev.spiff.com.au");'
        else:
            print line,


with open("./spiff-connect/spiff-connect.php") as file:
    replaceConstants(file)

with open("./spiff-connect/includes/spiff-connect-requests.php") as file:
    replaceConstants(file)
