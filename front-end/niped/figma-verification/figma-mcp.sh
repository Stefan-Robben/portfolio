#!/bin/zsh
# Project: Niped (Radicle / WordPress)
# Origineel pad: .claude/skills/blade-component/scripts/figma-mcp.sh
# Wat het doet: Roept de lokale Figma Dev Mode MCP-server direct aan (JSON-RPC) om design context, metadata en screenshots van een Figma-node op te halen.
#
# Call the Figma Dev Mode MCP server directly over JSON-RPC.
#
# Why this exists: the Claude-side MCP client connects to the Figma server when
# the session starts. If Dev Mode was enabled afterwards, every mcp__Figma__*
# call keeps returning the "enable the Dev Mode MCP Server" instructions even
# though the server is up. Restarting the app would fix it but kills the
# session, so talk to the server ourselves instead.
#
# Usage:
#   figma-mcp.sh tools                                # list tools
#   figma-mcp.sh <tool> '<json arguments>'             # call a tool
#   figma-mcp.sh get_metadata '{"nodeId":"2145:4669"}'
#   figma-mcp.sh get_design_context '{"nodeId":"2145:4669","excludeScreenshot":true}'
#   figma-mcp.sh get_screenshot '{"nodeId":"2265:6862"}' shot.png   # 3rd arg = save image
#
# Node ids come from the Figma URL: ?node-id=2145-4669 -> "2145:4669" (dash to colon).
# The server acts on the file currently open in the Figma desktop app, so there
# is no fileKey argument - ask the user to open the right file and select the node.

set -e
URL=${FIGMA_MCP_URL:-http://127.0.0.1:3845/mcp}
TOOL=$1
ARGS=${2:-'{}'}
IMG_OUT=$3

if ! curl -s -o /dev/null -m 3 "$URL"; then
  echo "Figma Dev Mode MCP server is not reachable at $URL." >&2
  echo "Ask the user to enable it: Figma desktop app -> Figma menu -> Preferences" >&2
  echo "-> 'Enable Dev Mode MCP Server', with the design file open." >&2
  exit 1
fi

HDR=$(mktemp)
trap 'rm -f "$HDR"' EXIT

curl -s -D "$HDR" -o /dev/null -X POST "$URL" \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"blade-component-skill","version":"1.0"}}}' \
  -m 20

SID=$(grep -i '^mcp-session-id' "$HDR" | tr -d '\r' | awk '{print $2}')
if [[ -z "$SID" ]]; then
  echo "No mcp-session-id returned; the server rejected the handshake." >&2
  exit 1
fi

post() {
  curl -s -X POST "$URL" \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json, text/event-stream' \
    -H "mcp-session-id: $SID" \
    -d "$1" -m 180
}

post '{"jsonrpc":"2.0","method":"notifications/initialized"}' > /dev/null

if [[ "$TOOL" == "tools" ]]; then
  BODY='{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'
else
  BODY=$(TOOL="$TOOL" ARGS="$ARGS" python3 -c '
import json, os
print(json.dumps({"jsonrpc":"2.0","id":2,"method":"tools/call",
  "params":{"name":os.environ["TOOL"],"arguments":json.loads(os.environ["ARGS"])}}))')
fi

# Responses come back as SSE ("event: message" + "data: {...}"), so pull the data line out.
post "$BODY" | grep '^data: ' | IMG_OUT="$IMG_OUT" python3 -c '
import base64, json, os, sys

raw = sys.stdin.read()
if not raw.strip():
    sys.exit("empty response from the Figma MCP server")

d = json.loads(raw[6:])
if "error" in d:
    sys.exit("MCP error: " + json.dumps(d["error"])[:2000])

result = d.get("result", {})

if "tools" in result:
    for t in result["tools"]:
        print(t["name"], "->", list(t["inputSchema"].get("properties", {}).keys()))
    sys.exit(0)

img_out = os.environ.get("IMG_OUT") or ""
for c in result.get("content", []):
    if c.get("type") == "image":
        if img_out:
            open(img_out, "wb").write(base64.b64decode(c["data"]))
            print("saved image:", img_out, c.get("mimeType", ""))
        else:
            print("[image omitted - pass a 3rd argument to save it]")
    else:
        print(c.get("text", ""))
'
