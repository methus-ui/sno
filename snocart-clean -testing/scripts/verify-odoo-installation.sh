#!/bin/bash

################################################################################
# Odoo Installation Verification Script
#
# This script verifies that Odoo server is correctly installed and configured
# for Snocart's One-Click POS Integration.
#
# Usage:
#   chmod +x verify-odoo-installation.sh
#   sudo ./verify-odoo-installation.sh
#
################################################################################

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
}

print_test() {
    echo -ne "Testing: $1... "
}

print_pass() {
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASS_COUNT++))
}

print_fail() {
    echo -e "${RED}✗ FAIL${NC}"
    if [ -n "$1" ]; then
        echo -e "  ${RED}→ $1${NC}"
    fi
    ((FAIL_COUNT++))
}

print_warn() {
    echo -e "${YELLOW}⚠ WARN${NC}"
    if [ -n "$1" ]; then
        echo -e "  ${YELLOW}→ $1${NC}"
    fi
    ((WARN_COUNT++))
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

################################################################################
# Test Functions
################################################################################

test_odoo_user() {
    print_test "Odoo user exists"
    if id odoo &>/dev/null; then
        print_pass
    else
        print_fail "User 'odoo' does not exist"
    fi
}

test_odoo_home() {
    print_test "Odoo home directory exists"
    if [ -d "/opt/odoo/odoo17.0" ] || [ -d "/opt/odoo/odoo17" ]; then
        print_pass
    else
        print_fail "Directory /opt/odoo/odoo17.0 or /opt/odoo/odoo17 not found"
    fi
}

test_odoo_config() {
    print_test "Odoo configuration file exists"
    if [ -f "/etc/odoo.conf" ]; then
        print_pass

        # Check critical settings
        print_test "  dbfilter configured correctly"
        if grep -q "dbfilter.*odoo_vendor" /etc/odoo.conf; then
            print_pass
        else
            print_fail "dbfilter not set to ^odoo_vendor_.*$"
        fi

        print_test "  list_db is disabled"
        if grep -q "list_db.*False" /etc/odoo.conf; then
            print_pass
        else
            print_warn "list_db should be set to False for security"
        fi
    else
        print_fail "File /etc/odoo.conf not found"
    fi
}

test_postgresql() {
    print_test "PostgreSQL is installed"
    if command -v psql &>/dev/null; then
        print_pass
    else
        print_fail "PostgreSQL not found"
        return
    fi

    print_test "PostgreSQL is running"
    if systemctl is-active --quiet postgresql; then
        print_pass
    else
        print_fail "PostgreSQL service is not running"
        return
    fi

    print_test "PostgreSQL odoo user exists"
    if sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='odoo'" | grep -q 1; then
        print_pass
    else
        print_fail "PostgreSQL user 'odoo' not found"
    fi
}

test_python_packages() {
    print_test "Python 3 is installed"
    if command -v python3 &>/dev/null; then
        PYTHON_VERSION=$(python3 --version 2>&1 | awk '{print $2}')
        print_pass
        print_info "  Python version: $PYTHON_VERSION"
    else
        print_fail "Python 3 not found"
        return
    fi

    print_test "pip3 is installed"
    if command -v pip3 &>/dev/null; then
        print_pass
    else
        print_fail "pip3 not found"
    fi

    print_test "Critical Python packages installed"
    local packages=("psycopg2" "lxml" "werkzeug")
    local missing=()
    for pkg in "${packages[@]}"; do
        if ! python3 -c "import $pkg" 2>/dev/null; then
            missing+=("$pkg")
        fi
    done

    if [ ${#missing[@]} -eq 0 ]; then
        print_pass
    else
        print_fail "Missing packages: ${missing[*]}"
    fi
}

test_wkhtmltopdf() {
    print_test "wkhtmltopdf is installed"
    if command -v wkhtmltopdf &>/dev/null; then
        WKHTMLTOPDF_VERSION=$(wkhtmltopdf --version 2>&1 | head -1)
        print_pass
        print_info "  Version: $WKHTMLTOPDF_VERSION"
    else
        print_warn "wkhtmltopdf not found (PDF receipts won't work)"
    fi
}

test_odoo_service() {
    print_test "Odoo systemd service exists"
    if [ -f "/etc/systemd/system/odoo.service" ]; then
        print_pass
    else
        print_fail "Service file /etc/systemd/system/odoo.service not found"
        return
    fi

    print_test "Odoo service is enabled"
    if systemctl is-enabled --quiet odoo; then
        print_pass
    else
        print_warn "Odoo service is not enabled (won't start on boot)"
    fi

    print_test "Odoo service is running"
    if systemctl is-active --quiet odoo; then
        print_pass
    else
        print_fail "Odoo service is not running. Start with: sudo systemctl start odoo"
        return
    fi

    print_test "Odoo is listening on port 8069"
    if netstat -tulpn 2>/dev/null | grep -q ":8069"; then
        print_pass
    elif ss -tulpn 2>/dev/null | grep -q ":8069"; then
        print_pass
    else
        print_fail "Odoo is not listening on port 8069"
    fi
}

test_nginx() {
    print_test "Nginx is installed"
    if command -v nginx &>/dev/null; then
        print_pass
    else
        print_warn "Nginx not installed (domain access won't work)"
        return
    fi

    print_test "Nginx is running"
    if systemctl is-active --quiet nginx; then
        print_pass
    else
        print_warn "Nginx service is not running"
        return
    fi

    print_test "Odoo Nginx site is configured"
    if [ -f "/etc/nginx/sites-available/odoo" ]; then
        print_pass
    else
        print_warn "Nginx configuration for Odoo not found"
    fi

    print_test "Odoo Nginx site is enabled"
    if [ -L "/etc/nginx/sites-enabled/odoo" ]; then
        print_pass
    else
        print_warn "Odoo site not enabled in Nginx"
    fi
}

test_ssl() {
    print_test "Certbot is installed"
    if command -v certbot &>/dev/null; then
        print_pass
    else
        print_warn "Certbot not installed (SSL won't work)"
        return
    fi

    print_test "SSL certificate exists"
    # Try to find any SSL certificate in letsencrypt
    if [ -d "/etc/letsencrypt/live" ] && [ "$(ls -A /etc/letsencrypt/live 2>/dev/null)" ]; then
        CERT_DOMAIN=$(ls /etc/letsencrypt/live/ | head -1)
        print_pass
        print_info "  Domain: $CERT_DOMAIN"
    else
        print_warn "No SSL certificate found (HTTPS won't work)"
    fi
}

test_firewall() {
    print_test "UFW firewall is installed"
    if command -v ufw &>/dev/null; then
        print_pass
    else
        print_warn "UFW not installed"
        return
    fi

    print_test "UFW is enabled"
    if ufw status | grep -q "Status: active"; then
        print_pass
    else
        print_warn "UFW is not enabled"
        return
    fi

    print_test "Required ports are open"
    local ports=("22" "80" "443")
    local missing=()
    for port in "${ports[@]}"; do
        if ! ufw status | grep -q "$port"; then
            missing+=("$port")
        fi
    done

    if [ ${#missing[@]} -eq 0 ]; then
        print_pass
    else
        print_warn "Ports not open in firewall: ${missing[*]}"
    fi
}

test_http_access() {
    print_test "Odoo HTTP endpoint is accessible"
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8069 | grep -q "303\|200"; then
        print_pass
    else
        print_fail "Cannot access http://localhost:8069"
    fi

    print_test "Odoo XML-RPC endpoint is accessible"
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8069/xmlrpc/2/db | grep -q "200"; then
        print_pass
    else
        print_fail "Cannot access XML-RPC endpoint"
    fi
}

test_logs() {
    print_test "Odoo log directory exists"
    if [ -d "/var/log/odoo" ]; then
        print_pass
    else
        print_fail "Directory /var/log/odoo not found"
        return
    fi

    print_test "Odoo log file exists"
    if [ -f "/var/log/odoo/odoo.log" ]; then
        print_pass

        # Check for recent errors
        print_test "No critical errors in logs (last 50 lines)"
        if ! tail -50 /var/log/odoo/odoo.log | grep -qi "critical\|traceback"; then
            print_pass
        else
            print_warn "Found errors in log file. Check: sudo tail -50 /var/log/odoo/odoo.log"
        fi
    else
        print_warn "Log file /var/log/odoo/odoo.log not found (might be new installation)"
    fi
}

test_permissions() {
    print_test "Odoo files owned by odoo user"
    if [ -d "/opt/odoo" ]; then
        OWNER=$(stat -c "%U" /opt/odoo)
        if [ "$OWNER" = "odoo" ]; then
            print_pass
        else
            print_fail "Directory /opt/odoo is owned by $OWNER, should be odoo"
        fi
    else
        print_fail "Directory /opt/odoo not found"
    fi

    print_test "Odoo config has correct permissions"
    if [ -f "/etc/odoo.conf" ]; then
        PERMS=$(stat -c "%a" /etc/odoo.conf)
        OWNER=$(stat -c "%U" /etc/odoo.conf)
        if [ "$PERMS" = "640" ] && [ "$OWNER" = "odoo" ]; then
            print_pass
        else
            print_warn "Config permissions: $PERMS (should be 640), owner: $OWNER (should be odoo)"
        fi
    fi
}

test_database_creation() {
    print_test "Can connect to PostgreSQL as odoo user"

    # Try to connect (will fail if password not configured, but that's OK for this test)
    if sudo -u postgres psql -U odoo -d postgres -c "SELECT 1" &>/dev/null || \
       PGPASSWORD=odoo psql -U odoo -h localhost -d postgres -c "SELECT 1" &>/dev/null; then
        print_pass
    else
        print_warn "Cannot connect to PostgreSQL (password might need to be set)"
    fi
}

################################################################################
# Main Execution
################################################################################

main() {
    clear

    cat << "EOF"
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║          Odoo Installation Verification Script                   ║
║                  For Snocart POS Integration                      ║
║                                                                   ║
╚═══════════════════════════════════════════════════════════════════╝
EOF

    echo ""

    # Run all tests
    print_header "System Components"
    test_odoo_user
    test_odoo_home
    test_odoo_config
    test_python_packages
    test_wkhtmltopdf

    print_header "Database"
    test_postgresql
    test_database_creation

    print_header "Odoo Service"
    test_odoo_service
    test_logs
    test_permissions

    print_header "Web Server"
    test_nginx
    test_ssl
    test_firewall

    print_header "Network Access"
    test_http_access

    # Summary
    print_header "Test Summary"

    TOTAL=$((PASS_COUNT + FAIL_COUNT + WARN_COUNT))

    echo -e "${GREEN}Passed:  $PASS_COUNT${NC}"
    echo -e "${YELLOW}Warnings: $WARN_COUNT${NC}"
    echo -e "${RED}Failed:  $FAIL_COUNT${NC}"
    echo -e "Total:   $TOTAL"
    echo ""

    if [ $FAIL_COUNT -eq 0 ]; then
        echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
        echo -e "${GREEN}  ✓ Installation verified successfully!${NC}"
        echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
        echo ""
        echo -e "${GREEN}Next steps:${NC}"
        echo "  1. Update Laravel .env with Odoo credentials"
        echo "  2. Test Laravel → Odoo connection"
        echo "  3. Enable POS for a test vendor"
        echo ""
        exit 0
    else
        echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
        echo -e "${RED}  ✗ Installation has issues that need to be resolved${NC}"
        echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
        echo ""
        echo -e "${RED}Please fix the failed tests and run this script again.${NC}"
        echo ""
        exit 1
    fi
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
    echo -e "${RED}This script must be run as root or with sudo${NC}"
    exit 1
fi

# Run main function
main
