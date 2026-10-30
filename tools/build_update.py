#!/usr/bin/env python3
"""Build an overlay ZIP from reviewed Git blobs; never package installation/user data."""
import argparse
import datetime
import hashlib
import json
import pathlib
import subprocess
import zipfile

ROOT = pathlib.Path(__file__).resolve().parents[1]
PREFIX = 'AvocadoEdition_Light/'

def git(*args, check=True):
    return subprocess.run(['git', '-C', str(ROOT), *args], check=check, capture_output=True).stdout

def allowed(path):
    if not path.startswith(PREFIX):
        return False
    relative = pathlib.PurePosixPath(path.removeprefix(PREFIX))
    return (relative.parts and '..' not in relative.parts and
            relative.parts[0].lower() not in {'data', 'install'} and
            str(relative).lower() != 'config.php')

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--base', required=True)
    parser.add_argument('--revision', default='HEAD')
    parser.add_argument('--output', required=True)
    args = parser.parse_args()
    base = git('rev-parse', '--verify', args.base+'^{commit}').decode().strip()
    revision = git('rev-parse', '--verify', args.revision+'^{commit}').decode().strip()
    paths = sorted(set(git('diff', '--name-only', '--diff-filter=ACMRT', base, revision).decode().splitlines()))
    deleted = [p for p in git('diff', '--name-only', '--diff-filter=D', base, revision).decode().splitlines() if allowed(p)]
    if deleted:
        raise SystemExit('Program deletions require a separate reviewed procedure: '+', '.join(deleted))
    timestamp = int(git('show', '-s', '--format=%ct', revision).decode())
    date = datetime.datetime.fromtimestamp(timestamp, datetime.timezone.utc).timetuple()[:6]
    files = []
    payloads = {}
    for path in filter(allowed, paths):
        entry = git('ls-tree', revision, '--', path).decode()
        if not entry.startswith('100644 ') and not entry.startswith('100755 '):
            raise SystemExit('Refusing non-regular file: '+path)
        payload = git('show', revision+':'+path)
        relative = path.removeprefix(PREFIX)
        payloads['UPLOAD/'+relative] = payload
        previous = git('show', base+':'+path, check=False)
        files.append({'path': relative, 'bytes': len(payload),
                      'sha256': hashlib.sha256(payload).hexdigest(),
                      'base_sha256': hashlib.sha256(previous).hexdigest() if previous else None})
    if not files:
        raise SystemExit('No program changes to package.')
    manifest = {'base_commit': base, 'source_commit': revision,
                'excluded': ['data/', 'install/', 'config.php'], 'files': files}
    payloads['MANIFEST.json'] = (json.dumps(manifest, ensure_ascii=False, indent=2)+'\n').encode()
    payloads['PATCH_FILES.txt'] = ('\n'.join(item['path'] for item in files)+'\n').encode()
    readme = '''아보카도 TOMO 프로그램 업데이트 전용 묶음

이 ZIP은 전체 설치본이 아닙니다. 변경된 프로그램 파일만 들어 있습니다.
data 폴더, DB 연결 설정, 설치 폴더, 프로그램 최상위 config.php는 포함하지 않습니다.
새로 추가된 사용자의 스킨/플러그인 폴더를 삭제하는 작업은 하지 않습니다.
같은 경로의 프로그램 파일을 직접 수정했다면 해당 수정은 덮어써질 수 있습니다.

1. 복구해 놓은 현재 서버 파일과 DB를 백업합니다.
2. ZIP을 내 컴퓨터에서 압축 해제합니다.
3. UPLOAD 폴더 안의 파일과 폴더만, 서버에서 기존 common.php가 있는 위치에 올립니다.
4. 같은 이름의 변경 파일만 덮어씁니다. 서버 폴더를 삭제하거나 동기화 삭제하지 않습니다.
5. 설치 화면은 실행하지 않습니다. 최고관리자로 관리자 페이지에 접속해 회원 필드를 준비합니다.
   DB 테이블 변경 권한이 없으면 관리자 화면 안내를 확인하고 호스팅 업체에 문의하세요.
   기존 편집기를 사용 중이라면 환경설정의 본문 편집기에서 Tiptap을 선택할 수 있습니다.
6. 회원가입 승인: 환경설정 → 회원가입 승인에서 켭니다. 기본환경의 계정생성 가능도 켜야 합니다.
7. 색상 일괄설정: 디자인 설정 → 색상 일괄설정에서 적용 후 상단 저장을 누릅니다.

PATCH_FILES.txt에 덮어쓸 파일 목록이 있습니다.
MANIFEST.json에는 기준/수정 소스 커밋과 각 파일의 SHA-256이 있습니다.
이 묶음은 기존 PHP 8.4 TOMO 버전(PR #3 이후)을 기준으로 한 업데이트입니다.
다른 아보카도 배포판 전체를 교체하는 용도로 사용하지 마세요.
'''
    payloads['README_UPDATE_KO.txt'] = readme.encode()
    output = pathlib.Path(args.output)
    output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED) as archive:
        for name, payload in payloads.items():
            info = zipfile.ZipInfo(name, date)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, payload)
    # Verify the actual archive, including exclusions and byte integrity.
    with zipfile.ZipFile(output) as archive:
        for name in archive.namelist():
            if name.startswith('UPLOAD/') and not allowed(PREFIX+name.removeprefix('UPLOAD/')):
                raise SystemExit('Protected path found in update: '+name)
        for item in files:
            if hashlib.sha256(archive.read('UPLOAD/'+item['path'])).hexdigest() != item['sha256']:
                raise SystemExit('Archive integrity failure: '+item['path'])
    print(f'PASS update ZIP: {len(files)} program files, no data/install/config.php, every file hash verified')
    print(str(output))

if __name__ == '__main__':
    main()
