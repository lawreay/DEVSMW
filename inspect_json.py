import json
from pathlib import Path
p = Path('malawi_rank.json')
with p.open('r', encoding='utf-8-sig') as f:
    data = json.load(f)
print('user len', len(data.get('user', [])))
print('user_public len', len(data.get('user_public', [])))
print('user_private len', len(data.get('user_private', [])))
print('org len', len(data.get('org', [])))
print('org_public len', len(data.get('org_public', [])))
print('org_private len', len(data.get('org_private', [])))
