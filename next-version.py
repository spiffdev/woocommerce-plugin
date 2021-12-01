with open("./VERSION") as versionFile:
    content = versionFile.read()
    parts = content.split('.')
    parts[2] = str(int(parts[2]) + 1)
    newVersion = '.'.join(parts)
print newVersion
