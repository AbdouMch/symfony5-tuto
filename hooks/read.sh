#!/bin/bash

input=$(cat)
target_file=$(echo "$input" | jq -r '.tool_input.file_path // .tool_input.path // ""')

if echo "$target_file" | grep -q '\.env'; then
    echo "Error: access to .env files is not allowed (path: $target_file)" >&2
    exit 2
fi

exit 0
