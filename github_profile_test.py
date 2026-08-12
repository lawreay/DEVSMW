import urllib.request
from html.parser import HTMLParser

class GHProfileParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.data = []
    def handle_starttag(self, tag, attrs):
        self.data.append((tag, dict(attrs)))
    def handle_data(self, data):
        pass

url = 'https://github.com/kachaje'
print('fetching', url)
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
with urllib.request.urlopen(req, timeout=30) as resp:
    html = resp.read().decode('utf-8', errors='ignore')
print('len html', len(html))
print('has title', '<title>' in html)
print(html[:1000])
