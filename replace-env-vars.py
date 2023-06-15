import sys

env = sys.argv[1]

with open("./spiff-connect/spiff-connect.php") as spiffConnect:
    lines = spiffConnect.readlines()
    for line in lines:
        if line.startswith('define("SPIFF_API_BASE"'):
            if (env == 'production'):
                print('define("SPIFF_API_BASE", "https://api.spiff.com.au");')
            else:
                print('define("SPIFF_API_BASE", "https://api.app.dev.spiff.com.au");')
        else:
            print(line, end="")
