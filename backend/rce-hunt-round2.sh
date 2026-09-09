#!/bin/bash
# ============================================================
# RCE HUNT — Round 2: Unexplored candidates
# Run from: /home/kamill/Desktop/snocart-clean
# Usage: bash rce-hunt-round2.sh
# ============================================================

echo "================================================================"
echo " CANDIDATE 1: FileManagerController (admin file manager)"
echo " Uses getClientOriginalName() — preserves FULL filename"
echo " File managers are classic RCE vectors"
echo "================================================================"
echo ""
sed -n '50,120p' app/Http/Controllers/Admin/FileManagerController.php
echo ""

echo "================================================================"
echo " CANDIDATE 2: EmployeeChatController file upload"
echo " Uses getClientOriginalExtension() in chat"
echo " Need to check: is this admin-only or can lower users reach it?"
echo "================================================================"
echo ""
echo "--- Lines 190,230 (the two upload locations) ---"
sed -n '190,230p' app/Http/Controllers/Api/V1/Admin/EmployeeChatController.php
echo ""
echo "--- Lines 380,420 ---"
sed -n '380,420p' app/Http/Controllers/Api/V1/Admin/EmployeeChatController.php
echo ""

echo "================================================================"
echo " CANDIDATE 3: exec('sh ' . \$scriptPath)"
echo " BusinessSettingsController.php:415 — need to trace \$scriptPath"
echo "================================================================"
echo ""
sed -n '400,430p' app/Http/Controllers/Admin/BusinessSettingsController.php
echo ""

echo "================================================================"
echo " CANDIDATE 4: Addon system — dynamic code loading"
echo " AddonController eval() + addon upload/install"
echo " If attacker can upload an addon, they get code execution"
echo "================================================================"
echo ""
echo "--- AddonController: full file (it's short) ---"
cat app/Http/Controllers/Admin/System/AddonController.php
echo ""

echo "================================================================"
echo " CANDIDATE 5: Customer profile image upload (API)"
echo " CustomerAuthController — public registration endpoint"
echo " Check if profile image uses getClientOriginalExtension"
echo "================================================================"
echo ""
grep -n -B5 -A10 "image\|upload\|getClientOriginal" app/Http/Controllers/Api/V1/CustomerController.php | head -40
echo ""

echo "================================================================"
echo " CANDIDATE 6: The Odoo integration"
echo " OdooDeploymentService decrypts passwords + makes requests"
echo " Potential SSRF or deserialization"
echo "================================================================"
echo ""
sed -n '400,440p' app/Services/OdooDeploymentService.php
echo ""

echo "================================================================"
echo " DONE"
echo "================================================================"
