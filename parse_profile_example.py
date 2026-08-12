import urllib.request, re
url='https://github.com/kachaje'
req=urllib.request.Request(url, headers={'User-Agent':'Mozilla/5.0'})
with urllib.request.urlopen(req, timeout=30) as resp:
    html=resp.read().decode('utf-8', errors='ignore')

def extract(pattern, text, group=1, flags=re.S):
    m=re.search(pattern, text, flags)
    return m.group(group).strip() if m else None

name=extract(r'<span[^>]*class="p-name[^"]*"[^>]*>(.*?)</span>', html)
username=extract(r'<span[^>]*class="p-nickname[^"]*"[^>]*>(.*?)</span>', html)
company=extract(r'<span[^>]*class="p-org[^"]*"[^>]*>(.*?)</span>', html)
location=extract(r'<span[^>]*class="p-label[^"]*"[^>]*>(.*?)</span>', html)
email=extract(r'<a[^>]*href="mailto:([^"]+)"', html)
blog=extract(r'<a[^>]*class="[^\"]*u-url[^\"]*"[^>]*href="([^"]+)"', html)
bio=extract(r'<div[^>]*class="[^\"]*p-note user-profile-bio[^\"]*"[^>]*>(.*?)</div>', html)
pinned=re.findall(r'<a[^>]*href="/kachaje/([^"]+)"[^>]*class="[^"]*Link[^>]*"', html)
print('name', name)
print('username', username)
print('company', company)
print('location', location)
print('email', email)
print('blog', blog)
print('bio', bio)
print('pinned', pinned[:10])
