#!/bin/sh
input=$(cat)

model=$(echo "$input" | jq -r '.model.display_name // "Claude"')
cwd=$(echo "$input" | jq -r '.workspace.current_dir // .cwd // "unknown"')
used_pct=$(echo "$input" | jq -r '.context_window.used_percentage // empty')
version=$(echo "$input" | jq -r '.version // empty')

# Shorten home directory to ~
home="$HOME"
short_cwd=$(echo "$cwd" | sed "s|^$home|~|")

# Build status string
status="$model"

# Append version if present
if [ -n "$version" ]; then
  status="$status v$version"
fi

# Append current directory
status="$status | $short_cwd"

# Append context usage percentage if available
if [ -n "$used_pct" ]; then
  used_int=$(printf "%.0f" "$used_pct")
  status="$status | ctx: ${used_int}%"
fi

# Append 5-hour rate limit usage if available
five_pct=$(echo "$input" | jq -r '.rate_limits.five_hour.used_percentage // empty')
if [ -n "$five_pct" ]; then
  five_int=$(printf "%.0f" "$five_pct")
  status="$status | 5h: ${five_int}%"
fi

printf "%s" "$status"
