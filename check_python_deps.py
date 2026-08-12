import sys
import importlib
print(sys.version)
print('requests', importlib.util.find_spec('requests') is not None)
print('bs4', importlib.util.find_spec('bs4') is not None)
