with open("./VERSION") as versionFile:
    content = versionFile.read()
    parts = content.split('.')
    if len(parts) < 3:
        raise Exception("Only {1} parts in version number {0}".format(content, len(parts)))
    parts[2] = str(int(parts[2]) + 1)
    newVersion = '.'.join(parts)
print newVersion
