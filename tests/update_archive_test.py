#!/usr/bin/env python3
"""Apply the real ZIP to an isolated installation containing user-owned sentinels."""
import hashlib
import json
import pathlib
import sys
import tempfile
import zipfile

with tempfile.TemporaryDirectory(prefix='avocado-overlay-') as directory:
    root = pathlib.Path(directory)
    protected = {
        'data/dbconfig.php': b'existing database settings',
        'data/file/custom/image.png': b'existing uploaded image',
        'config.php': b'existing host configuration',
        'plugin/editor/user-added/custom.php': b'existing custom plugin',
        'skin/board/user-added/style.css': b'existing custom skin',
        'install/local-marker': b'existing installer state',
    }
    for name, payload in protected.items():
        target = root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(payload)
    with zipfile.ZipFile(sys.argv[1]) as archive:
        manifest = json.loads(archive.read('MANIFEST.json'))
        deployed = []
        for name in archive.namelist():
            if not name.startswith('UPLOAD/'):
                continue
            relative = pathlib.PurePosixPath(name[len('UPLOAD/'):])
            assert not relative.is_absolute() and '..' not in relative.parts
            assert relative.parts[0].lower() not in ('data', 'install')
            assert str(relative).lower() != 'config.php'
            target = root / str(relative)
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(archive.read(name))
            deployed.append(str(relative))
        assert (root / 'common.php').is_file()
        assert (root / 'plugin/editor/tiptap/editor.bundle.js').is_file()
        assert (root / 'lib/tomo_members.lib.php').is_file()
        assert len(deployed) == len(manifest['files'])
        for name, payload in protected.items():
            assert (root / name).read_bytes() == payload, name
        for item in manifest['files']:
            assert hashlib.sha256((root / item['path']).read_bytes()).hexdigest() == item['sha256']
print('PASS actual ZIP overlay: DB settings, uploads, host config, custom skin/plugin and installer state preserved; every changed file verified')
