import sys
import argparse 

print("THIS IS A FAKE INTERPRETER", file=sys.stderr)
parser = argparse.ArgumentParser(description="meow")
parser.add_argument("--source", dest="source", help="Path to input XML file", default="STDIN")
parser.add_argument("--input", dest="input", help="Path a file with inputs used as STDIN inputs", default="STDIN")
args = parser.parse_args()
if args.source == args.input == "STDIN":
    print("At least one argument of --input, --source must be specified!", file=sys.stderr)
    exit(10)
file = open("both_tests/inter_test.src", "r")
content = file.read()
print(content, file=sys.stderr)
print(content)

exit(42)