#!/bin/bash
# Snocart white-box RCE sink scan
# Run this from the ROOT of your Snocart source code folder
# Usage:   bash rce-sink-scan.sh   >  scan-results.txt
# Then paste scan-results.txt to your AI assistant for analysis.

echo "============================================================"
echo " SNOCART RCE SINK SCAN — $(date)"
echo " Running in: $(pwd)"
echo "============================================================"
echo ""

echo "########## TYPE 1: COMMAND INJECTION ##########"
grep -rni "system\s*(\|exec\s*(\|shell_exec\s*(\|passthru\s*(\|popen\s*(\|proc_open\s*(\|\\\\\`" --include="*.php" . 2>/dev/null | grep -v "/vendor/" | grep -v "/node_modules/"
echo ""

echo "########## TYPE 2: DESERIALIZATION ##########"
grep -rni "unserialize\s*(\|Crypt::decrypt\|decrypt\s*(" --include="*.php" . 2>/dev/null | grep -v "/vendor/" | grep -v "/node_modules/"
echo ""

echo "########## TYPE 3: FILE UPLOAD ##########"
grep -rni "move_uploaded_file\|\\\$_FILES\|getClientOriginalExtension\|getClientOriginalName\|->store(\|->upload(\|->putFile(" --include="*.php" . 2>/dev/null | grep -v "/vendor/" | grep -v "/node_modules/"
echo ""

echo "########## TYPE 4: SSTI / EVAL ##########"
grep -rni "eval\s*(\|Twig\|Smarty::\|StringLoader\|createTemplate" --include="*.php" . 2>/dev/null | grep -v "/vendor/" | grep -v "/node_modules/"
echo ""

echo "########## TYPE 5: FILE INCLUSION ##########"
grep -rni "include\s*(\|require\s*(\|include_once\s*(\|require_once\s*(" --include="*.php" . 2>/dev/null | grep -v "/vendor/" | grep -v "/node_modules/" | grep '\\\$'
echo ""

echo "########## TYPE 6: SSRF / URL FETCH ##########"
grep -rni "curl_exec\|file_get_contents\|GuzzleHttp\|Http::get\|Http::post\|->get(.*http" --include="*.php" . 2>/dev/null | grep -v "/vendor/" | grep -v "/node_modules/" | grep "http"
echo ""

echo "########## BONUS: DANGEROUS PATTERNS ##########"
echo "--- eval / assert ---"
grep -rni "eval\s*(\|assert\s*(" --include="*.php" . 2>/dev/null | grep -v "/vendor/"
echo "--- preg_replace with /e modifier ---"
grep -rni "preg_replace\s*(.*\\/e" --include="*.php" . 2>/dev/null | grep -v "/vendor/"
echo "--- create_function (deprecated, RCE) ---"
grep -rni "create_function" --include="*.php" . 2>/dev/null | grep -v "/vendor/"
echo ""

echo "########## EXTRA: RAW SQL (SQLi sinks) ##########"
grep -rni "DB::raw\|DB::select\|DB::statement\|->whereRaw\|->selectRaw\|->havingRaw\|->orderByRaw\|->groupByRaw" --include="*.php" . 2>/dev/null | grep -v "/vendor/"
echo ""

echo "============================================================"
echo " SCAN COMPLETE"
echo "============================================================"
