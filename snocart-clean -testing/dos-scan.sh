#!/bin/bash
# ============================================================
# DoS / RESOURCE EXHAUSTION VULNERABILITY SCANNER
# Run from: /home/kamill/Desktop/snocart-clean
# Usage: bash dos-scan.sh > dos-results.txt
# ============================================================

echo "================================================================"
echo " DOS / RESOURCE EXHAUSTION VULNERABILITY SCAN"
echo " $(date)"
echo " Running in: $(pwd)"
echo "================================================================"
echo ""

# ============================================================
# 1. MISSING RATE LIMITING (THROTTLE MIDDLEWARE)
# ============================================================
echo "########## 1. RATE LIMITING / THROTTLE CHECK ##########"
echo ""
echo "--- Routes WITH throttle middleware (PROTECTED): ---"
grep -rn "throttle" routes/ --include="*.php" 2>/dev/null | head -20
echo ""

echo "--- Route files checking for throttle in middleware groups: ---"
for f in routes/web.php routes/api/v1/api.php routes/vendor.php routes/admin/routes.php; do
  if [ -f "$f" ]; then
    THROTTLE_COUNT=$(grep -c "throttle" "$f" 2>/dev/null)
    ROUTE_COUNT=$(grep -c "Route::" "$f" 2>/dev/null)
    echo "  $f: $ROUTE_COUNT routes, $THROTTLE_COUNT have throttle"
  fi
done
echo ""

echo "--- Login/auth endpoints WITHOUT throttle (BRUTE FORCE): ---"
grep -n "login\|register\|reset.*password\|verify.*otp\|sign.up\|forgot" routes/ -r --include="*.php" 2>/dev/null | grep -v "throttle" | head -20
echo ""

# ============================================================
# 2. FILE UPLOAD WITHOUT SIZE LIMITS
# ============================================================
echo "########## 2. FILE UPLOAD SIZE LIMITS ##########"
echo ""
echo "--- Upload calls WITHOUT max:size validation: ---"
# Find all upload handlers
UPLOAD_FILES=$(grep -rl "getClientOriginalExtension\|getClientOriginalName\|\$_FILES\|move_uploaded_file\|->store(\|->storeAs(\|->putFileAs(" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829)

for f in $UPLOAD_FILES; do
  # Check if the controller has 'max:' validation for the file field
  HAS_MAX=$(grep -l "max:\|max_size\|upload_max\|file_size" "$f" 2>/dev/null)
  if [ -z "$HAS_MAX" ]; then
    echo "  ⚠️  NO SIZE LIMIT: $f"
  fi
done
echo ""

echo "--- PHP upload limits (php.ini): ---"
echo "  upload_max_filesize (check in php.ini)"
echo "  post_max_size (check in php.ini)"
echo "  Run: php -i | grep 'upload_max_filesize\|post_max_size'"
echo ""

# ============================================================
# 3. UNPAGINATED QUERIES (DATABASE EXHAUSTION)
# ============================================================
echo "########## 3. UNPAGINATED DATABASE QUERIES ##########"
echo ""
echo "--- ::all() calls (loads ENTIRE table into memory): ---"
grep -rn "::all()" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -20
echo ""

echo "--- ::get() calls WITHOUT limit/paginate (potential memory exhaustion): ---"
grep -rn "->get()" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | grep -v "paginate\|limit\|take\|first\|count" | head -30
echo ""

echo "--- Queries with WITH (eager loading) but no limit (N+1 memory): ---"
grep -rn "->with(" app/Http/Controllers/ --include="*.php" 2>/dev/null | grep -v "paginate\|limit\|take\|first\|find" | head -20
echo ""

# ============================================================
# 4. EXPENSIVE OPERATIONS WITHOUT TIMEOUTS
# ============================================================
echo "########## 4. EXPENSIVE OPERATIONS WITHOUT TIMEOUTS ##########"
echo ""
echo "--- HTTP requests WITHOUT timeout (hangs forever): ---"
grep -rn "Http::get\|Http::post\|curl_exec\|file_get_contents.*http" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | grep -v "timeout" | head -20
echo ""

echo "--- HTTP requests WITH timeout (PROTECTED): ---"
grep -rn "Http::timeout\|CURLOPT_TIMEOUT" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -10
echo ""

# ============================================================
# 5. REGEX DENIAL OF SERVICE (ReDoS)
# ============================================================
echo "########## 5. REGEX DENIAL OF SERVICE (ReDoS) ##########"
echo ""
echo "--- preg_match with user input (potential ReDoS): ---"
grep -rn "preg_match\|preg_replace\|preg_match_all" app/Http/Controllers/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -20
echo ""

echo "--- Complex regex patterns (nested quantifiers *+{): ---"
grep -rn "preg_match.*\(.*[+*].*[+*]" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -10
echo ""

# ============================================================
# 6. INFINITE LOOPS / UNBOUNDED LOOPS
# ============================================================
echo "########## 6. UNBOUNDED / INFINITE LOOPS ##########"
echo ""
echo "--- while(true) or while(1) without break: ---"
grep -rn "while\s*(\s*true\s*)\|while\s*(\s*1\s*)" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -10
echo ""

echo "--- while loops without clear termination: ---"
grep -rn "while\s*(" app/Http/Controllers/ app/CentralLogics/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -15
echo ""

echo "--- foreach without limit on user-controlled arrays: ---"
grep -rn "foreach.*\$request" app/Http/Controllers/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -15
echo ""

# ============================================================
# 7. IMAGE / FILE PROCESSING (CPU EXHAUSTION)
# ============================================================
echo "########## 7. IMAGE/FILE PROCESSING (CPU EXHAUSTION) ##########"
echo ""
echo "--- Image processing without dimension limits: ---"
grep -rn "imagecreatefrom\|Image::make\|Intervention\|imagick\|GD\|imagepng\|imagejpeg" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -20
echo ""

echo "--- ZIP extraction without file count limits: ---"
grep -rn "extractTo\|ZipArchive\|Madzipper" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -10
echo ""

echo "--- PDF generation (CPU heavy): ---"
grep -rn "PDF\|DomPDF\|Snappy\|mpdf\|tcpdf" app/Http/Controllers/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -10
echo ""

# ============================================================
# 8. EXPORT / DOWNLOAD WITHOUT LIMITS
# ============================================================
echo "########## 8. BULK EXPORT / DOWNLOAD (MEMORY EXHAUSTION) ##########"
echo ""
echo "--- Excel/CSV export endpoints: ---"
grep -rn "Excel\|export\|download\|->toArray()\|->chunk(" app/Http/Controllers/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | grep -i "export\|download\|csv\|excel" | head -15
echo ""

# ============================================================
# 9. JSON/API ENDPOINTS WITHOUT PAGINATION
# ============================================================
echo "########## 9. API ENDPOINTS WITHOUT PAGINATION ##########"
echo ""
echo "--- API GET endpoints returning collections: ---"
grep -rn -B5 "->get()\|->all()" app/Http/Controllers/Api/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | grep -v "paginate\|limit\|take\|first\|find\|count" | head -20
echo ""

# ============================================================
# 10. CACHE STAMPede / THUNDERING HERD
# ============================================================
echo "########## 10. CACHE STAMPEDE RISK ##########"
echo ""
echo "--- Cache remember/get without locks (stampede risk): ---"
grep -rn "Cache::remember\|cache()->remember\|Cache::get" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -15
echo ""

# ============================================================
# 11. SESSION / LOG FILE EXHAUSTION
# ============================================================
echo "########## 11. SESSION / LOG EXHAUSTION ##########"
echo ""
echo "--- Logging user input directly (disk exhaustion): ---"
grep -rn "Log::info.*\$request\|Log::error.*\$request\|Log::debug.*\$request" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -10
echo ""

echo "--- Session writes with user input: ---"
grep -rn "session()->put.*\$request\|\$_SESSION\[.*\$request" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -10
echo ""

# ============================================================
# 12. SLEEP / DELAY (SLOWLORIS-TYPE)
# ============================================================
echo "########## 12. SLEEP / DELAY (SLOWLORIS) ##########"
echo ""
echo "--- sleep() or usleep() in controllers: ---"
grep -rn "sleep\s*(\|usleep\s*(" app/Http/Controllers/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -10
echo ""

# ============================================================
# 13. DNS REBINDING / SSRF TO INTERNAL
# ============================================================
echo "########## 13. SSRF TO INTERNAL SERVICES ##########"
echo ""
echo "--- HTTP requests where URL might be user-controlled: ---"
grep -rn "Http::get(.*\$request\|Http::post(.*\$request\|curl_exec.*\$request\|file_get_contents.*\$request" app/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -15
echo ""

# ============================================================
# 14. RECURSION (STACK OVERFLOW)
# ============================================================
echo "########## 14. RECURSION (STACK OVERFLOW) ##########"
echo ""
echo "--- Functions that call themselves: ---"
grep -rn "function " app/CentralLogics/ app/Services/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | while read line; do
  FUNC_NAME=$(echo "$line" | grep -oP "function \K\w+")
  FILE=$(echo "$line" | cut -d: -f1)
  if [ -n "$FUNC_NAME" ] && [ -n "$FILE" ]; then
    RECURSIVE=$(grep -c "$FUNC_NAME" "$FILE" 2>/dev/null)
    if [ "$RECURSIVE" -gt 1 ]; then
      echo "  ⚠️  Potential recursion: $FUNC_NAME() in $FILE (appears $RECURSIVE times)"
    fi
  fi
done
echo ""

# ============================================================
# 15. CONCURRENT REQUEST / RACE CONDITION
# ============================================================
echo "########## 15. RACE CONDITION / DOUBLE-SPEND ##########"
echo ""
echo "--- Wallet/order operations without DB transactions: ---"
grep -rn "wallet\|Wallet\|balance\|deduct\|charge\|order_amount" app/Http/Controllers/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | grep -v "DB::transaction\|lockForUpdate\|sharedLock" | head -20
echo ""

echo "--- DB::transaction usage (PROTECTED): ---"
grep -rn "DB::transaction\|DB::beginTransaction\|lockForUpdate\|sharedLock" app/Http/Controllers/ --include="*.php" 2>/dev/null | grep -v vendor | grep -v Http.4829 | head -10
echo ""

echo "================================================================"
echo " SCAN COMPLETE"
echo " Review all ⚠️ items above"
echo "================================================================"
