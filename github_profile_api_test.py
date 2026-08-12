import urllib.request, json
url='https://api.github.com/users/kachaje'
req=urllib.request.Request(url, headers={'User-Agent':'Mozilla/5.0'})
with urllib.request.urlopen(req, timeout=30) as resp:
    data=json.load(resp)
print(json.dumps(data, indent=2))
