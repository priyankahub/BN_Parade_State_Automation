$input_json = $input | Out-String
$data = $input_json | ConvertFrom-Json -ErrorAction SilentlyContinue

$model = if ($data.model.display_name) { $data.model.display_name } else { "Claude" }
$cwd = if ($data.workspace.current_dir) { $data.workspace.current_dir } elseif ($data.cwd) { $data.cwd } else { "unknown" }
$version = $data.version
$used_pct = $data.context_window.used_percentage
$remaining_pct = $data.context_window.remaining_percentage
$total_in = $data.context_window.total_input_tokens
$total_out = $data.context_window.total_output_tokens
$ctx_size = $data.context_window.context_window_size
$five_pct = $data.rate_limits.five_hour.used_percentage
$week_pct = $data.rate_limits.seven_day.used_percentage

$home = $env:USERPROFILE
$short_cwd = $cwd -replace [regex]::Escape($home), "~"

# Format token counts in a compact human-readable way (e.g. 12.3k)
function Format-Tokens($n) {
    if ($null -eq $n) { return $null }
    if ($n -ge 1000) { return "$([math]::Round($n / 1000, 1))k" }
    return "$n"
}

$in_fmt  = Format-Tokens $total_in
$out_fmt = Format-Tokens $total_out

$status = $model
if ($version) { $status += " v$version" }
$status += " | $short_cwd"

# Token usage block
$token_parts = @()
if ($null -ne $in_fmt)  { $token_parts += "in:$in_fmt" }
if ($null -ne $out_fmt) { $token_parts += "out:$out_fmt" }
if ($token_parts.Count -gt 0) { $status += " | " + ($token_parts -join " ") }

# Context window usage
if ($null -ne $used_pct) {
    $ctx_str = "ctx: $([math]::Round($used_pct))% used"
    if ($null -ne $remaining_pct) { $ctx_str += " ($([math]::Round($remaining_pct))% left)" }
    $status += " | $ctx_str"
}

# Rate limits
if ($null -ne $five_pct) { $status += " | 5h: $([math]::Round($five_pct))%" }
if ($null -ne $week_pct) { $status += " | 7d: $([math]::Round($week_pct))%" }

Write-Host -NoNewline $status
