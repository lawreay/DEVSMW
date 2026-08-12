import urllib.request
import re
url='https://github.com/kachaje'
req=urllib.request.Request(url, headers={'User-Agent':'Mozilla/5.0'})
with urllib.request.urlopen(req, timeout=30) as resp:
    html=resp.read().decode('utf-8', errors='ignore')
for pat in ['json-ld', 'p-name', 'p-nickname', 'p-org', 'p-label', 'u-email', 'u-url', 'vcard-detail', 'repo-shelf', 'pinned-item-list-item', 'aria-label', 'data-hydro-click']:
    print('===', pat)
    i = html.find(pat)
    if i==-1:
        print('NOT FOUND')
        continue
    print(html[max(0,i-400):i+400].replace('\n',' '))
