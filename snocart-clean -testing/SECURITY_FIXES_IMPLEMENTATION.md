# SECURITY AUDIT FIXES - Implementation Guide

## CRITICAL PATCHES REQUIRED

### PATCH 1: Fix AdvertisementController File Extension Validation

**File:** `app/Http.4829/Controllers/Vendor/AdvertisementController.php`

**Replace Method:** `store()` method (around line 123-125)

```php
// VULNERABLE CODE - CURRENT
$advertisement->cover_image = $request->has('cover_image') &&  $request->advertisement_type == 'store_promotion' ?  
    Helpers::upload(dir: 'advertisement/', format:$request->file('cover_image')->getClientOriginalExtension(), image:$request->file('cover_image')) : null;

// SECURE CODE - REPLACEMENT
$advertisement->cover_image = null;
if ($request->has('cover_image') && $request->advertisement_type == 'store_promotion') {
    $imageValidation = $this->validateUploadedFile($request->file('cover_image'), 'image');
    if ($imageValidation['valid']) {
        $advertisement->cover_image = Helpers::upload(
            dir: 'advertisement/', 
            format: $imageValidation['extension'], 
            image: $request->file('cover_image')
        );
    } else {
        return response()->json([
            'errors' => [['code' => 'image', 'message' => $imageValidation['message']]]
        ], 400);
    }
}
```

**Also apply same fix to:**
- Line 124: profile_image
- Line 125: video_attachment
- Line 281: cover_image in update()
- Line 282: profile_image in update()
- Line 283: video_attachment in update()
- Line 413: cover_image in copyAddPost()
- Line 418: profile_image in copyAddPost()
- Line 427: video_attachment in copyAddPost()

### PATCH 2: Add Validation Helper Method

**Add to AdvertisementController class:**

```php
/**
 * Validate uploaded file for security
 * 
 * @param UploadedFile $file
 * @param string $type 'image' or 'video'
 * @return array
 */
private function validateUploadedFile($file, $type = 'image')
{
    // Whitelist allowed extensions
    $allowedExtensions = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'mov', 'avi', 'mkv', 'webm']
    ];
    
    $allowedMimes = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'video' => ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm']
    ];
    
    $extension = strtolower($file->getClientOriginalExtension());
    
    // Check extension
    if (!in_array($extension, $allowedExtensions[$type] ?? [])) {
        return [
            'valid' => false,
            'message' => 'Invalid file extension. Allowed: ' . implode(', ', $allowedExtensions[$type])
        ];
    }
    
    // Check MIME type
    $mimeType = $file->getMimeType();
    if (!in_array($mimeType, $allowedMimes[$type] ?? [])) {
        return [
            'valid' => false,
            'message' => 'Invalid file type: ' . $mimeType
        ];
    }
    
    // Check file size (50MB max)
    if ($file->getSize() > 50 * 1024 * 1024) {
        return [
            'valid' => false,
            'message' => 'File size exceeds 50MB limit'
        ];
    }
    
    // Verify magic bytes
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $actualMime = finfo_file($finfo, $file->path());
    finfo_close($finfo);
    
    if (!in_array($actualMime, $allowedMimes[$type] ?? [])) {
        return [
            'valid' => false,
            'message' => 'File content does not match extension'
        ];
    }
    
    return [
        'valid' => true,
        'extension' => $extension,
        'message' => 'File validation passed'
    ];
}
```

### PATCH 3: Fix Helpers::upload() Function

**File:** `app/CentralLogics/helpers.php` (Line 2260)

```php
// CURRENT VULNERABLE VERSION
public static function upload(string $dir, string $format, $image = null)
{
    try {
        if ($image != null) {
            $imageName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
            // ... rest of code
        }
    } catch (\Exception $e) {
    }
    return $imageName;
}

// SECURE VERSION - ADD VALIDATION
public static function upload(string $dir, string $format, $image = null)
{
    try {
        if ($image != null) {
            // Validate format to prevent injection
            if (!self::isValidFileExtension($format)) {
                throw new \InvalidArgumentException('Invalid file extension: ' . $format);
            }
            
            $imageName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
            if (!Storage::disk(self::getDisk())->exists($dir)) {
                Storage::disk(self::getDisk())->makeDirectory($dir);
            }
            Storage::disk(self::getDisk())->putFileAs($dir, $image, $imageName);
        } else {
            $imageName = 'def.png';
        }
    } catch (\Exception $e) {
        \Log::error('Upload error: ' . $e->getMessage());
    }
    return $imageName;
}

/**
 * Validate file extension
 * Whitelist only safe extensions
 */
private static function isValidFileExtension($extension)
{
    $safeExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',  // Images
        'mp4', 'mov', 'avi', 'mkv', 'webm',   // Videos
        'pdf', 'doc', 'docx', 'csv'           // Documents
    ];
    
    $extension = strtolower(trim($extension));
    
    // Allow only alphanumeric extensions
    if (!preg_match('/^[a-z0-9]{2,5}$/i', $extension)) {
        return false;
    }
    
    return in_array($extension, $safeExtensions);
}
```

### PATCH 4: Fix processProductImage() - Remove or Replace

**File:** `app/CentralLogics/helpers.php` (Line 2277)

**OPTION A - Remove the function entirely (RECOMMENDED if not used)**

```php
// Delete this entire function if not actively used
// Search codebase for "processProductImage" to confirm it's not called
```

**OPTION B - Replace exec() with safe PHP image processing**

```php
public static function processProductImage($dir, $imageName)
{
    if (!$imageName || $imageName === 'def.png') {
        return false;
    }

    try {
        $fullPath = Storage::disk(self::getDisk())->path($dir . $imageName);
        if (!file_exists($fullPath)) {
            return false;
        }

        // Use PHP-GD or Intervention Image instead of exec
        $image = Image::make($fullPath);
        
        // Optimize image
        $image->resize(2000, 2000, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // Remove EXIF data (privacy + security)
        $image->orientate();
        
        // Save without metadata
        $image->save($fullPath, 85);
        
        return true;
    } catch (\Exception $e) {
        \Log::warning('Image processing failed: ' . $e->getMessage());
        return false;
    }
}
```

### PATCH 5: Add .htaccess to Upload Directories

**File:** `storage/app/public/advertisement/.htaccess` (CREATE NEW)

```apache
# Prevent execution of PHP files
<FilesMatch "\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent execution of scripts
AddHandler text/plain .exe .com .bat .cmd .scr .vbs .js

# Disable directory listing
Options -Indexes

# Prevent access to configuration files
<FilesMatch "\.env|\.config|\.htaccess|web\.config">
    Order allow,deny
    Deny from all
</FilesMatch>

# Default to plaintext for unknown types
DefaultType text/plain

# Prevent MIME type sniffing
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

**Also create for other upload directories:**
- `storage/app/public/product/.htaccess`
- `storage/app/public/store/.htaccess`
- `storage/app/public/banner/.htaccess`

### PATCH 6: Improve json_decode() Safety

**File:** `app/Http.4829/Controllers/Vendor/OrderController.php` (Line 605)

```php
// VULNERABLE
$img_names = $order->order_proof?json_decode($order->order_proof):[];

// SECURE
$img_names = [];
if (!empty($order->order_proof)) {
    $decoded = json_decode($order->order_proof, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        // Validate decoded data structure
        $img_names = array_filter($decoded, function($item) {
            return is_string($item) && !str_contains($item, '../') && !str_contains($item, '..\\');
        });
    }
}
```

**Apply similar fixes to all json_decode() calls in BannerController and POSController**

### PATCH 7: Create FileUploadValidator Service

**New File:** `app/Services/FileUploadValidator.php`

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class FileUploadValidator
{
    // Maximum file size: 50MB
    const MAX_FILE_SIZE = 50 * 1024 * 1024;

    // Allowed file types with MIME types
    const ALLOWED_FILES = [
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        ],
        'video' => [
            'extensions' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],
            'mimes' => ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm'],
        ],
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx'],
            'mimes' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ],
    ];

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @param string|array $allowedTypes Allowed file types ('image', 'video', 'document', etc.)
     * @return array [valid => bool, message => string, extension => string|null]
     */
    public static function validate(UploadedFile $file, $allowedTypes = 'image'): array
    {
        $allowedTypes = (array) $allowedTypes;

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            Log::warning("File upload rejected: size exceeds limit", [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'limit' => self::MAX_FILE_SIZE
            ]);
            return [
                'valid' => false,
                'message' => 'File size exceeds 50MB limit',
                'extension' => null
            ];
        }

        $extension = strtolower($file->getClientOriginalExtension());

        // Validate extension against allowed types
        $validExtensions = [];
        $validMimes = [];
        
        foreach ($allowedTypes as $type) {
            if (isset(self::ALLOWED_FILES[$type])) {
                $validExtensions = array_merge($validExtensions, self::ALLOWED_FILES[$type]['extensions']);
                $validMimes = array_merge($validMimes, self::ALLOWED_FILES[$type]['mimes']);
            }
        }

        if (!in_array($extension, $validExtensions)) {
            Log::warning("File upload rejected: invalid extension", [
                'filename' => $file->getClientOriginalName(),
                'extension' => $extension,
                'allowed' => $validExtensions
            ]);
            return [
                'valid' => false,
                'message' => 'Invalid file type. Allowed: ' . implode(', ', $validExtensions),
                'extension' => null
            ];
        }

        // Validate MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $validMimes)) {
            Log::warning("File upload rejected: invalid MIME type", [
                'filename' => $file->getClientOriginalName(),
                'mime' => $mimeType,
                'expected' => $validMimes
            ]);
            return [
                'valid' => false,
                'message' => 'Invalid file MIME type: ' . $mimeType,
                'extension' => null
            ];
        }

        // Verify file magic bytes
        if (!self::verifyMagicBytes($file, $allowedTypes)) {
            Log::warning("File upload rejected: magic bytes mismatch", [
                'filename' => $file->getClientOriginalName(),
                'extension' => $extension,
                'actualMime' => mime_content_type($file->path())
            ]);
            return [
                'valid' => false,
                'message' => 'File content does not match extension',
                'extension' => null
            ];
        }

        Log::info("File upload validated successfully", [
            'filename' => $file->getClientOriginalName(),
            'extension' => $extension,
            'size' => $file->getSize()
        ]);

        return [
            'valid' => true,
            'message' => 'File validation passed',
            'extension' => $extension
        ];
    }

    /**
     * Verify file magic bytes match expected type
     */
    private static function verifyMagicBytes(UploadedFile $file, array $allowedTypes): bool
    {
        if (!function_exists('finfo_file')) {
            // Fallback if finfo not available
            return true;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMime = finfo_file($finfo, $file->path());
        finfo_close($finfo);

        $validMimes = [];
        foreach ($allowedTypes as $type) {
            if (isset(self::ALLOWED_FILES[$type])) {
                $validMimes = array_merge($validMimes, self::ALLOWED_FILES[$type]['mimes']);
            }
        }

        return in_array($actualMime, $validMimes);
    }
}
```

---

## QUICK IMPLEMENTATION CHECKLIST

- [ ] Apply PATCH 1: Fix AdvertisementController validation (9 locations)
- [ ] Apply PATCH 2: Add validateUploadedFile() method
- [ ] Apply PATCH 3: Add validation to Helpers::upload()
- [ ] Apply PATCH 4: Remove or replace processProductImage()
- [ ] Apply PATCH 5: Create .htaccess in all upload directories
- [ ] Apply PATCH 6: Fix json_decode() calls (min 5 locations)
- [ ] Apply PATCH 7: Create FileUploadValidator service
- [ ] Test all file uploads with whitelist validation
- [ ] Test with malicious file extensions (should be rejected)
- [ ] Deploy to production after testing

---

## VERIFICATION TESTS

### Test 1: Verify Extension Whitelist

```php
// Test in artisan tinker
$this->validateUploadedFile($invalidFile, 'image');
// Should return valid = false for .php, .exe, etc.
```

### Test 2: Verify MIME Type Check

```php
// Create polyglot file (image with PHP content)
// Upload should be rejected
```

### Test 3: Verify .htaccess Protection

```bash
# Try to access uploaded PHP
curl http://localhost/storage/app/public/advertisement/malicious.php
# Should return 403 Forbidden or plain text
```

### Test 4: Verify Magic Bytes

```php
// Rename PHP file to .jpg
// Upload should be rejected (actual content is PHP, not image)
```

---

## SECURITY HEADERS - Add to app/Http/Middleware/TrustProxies.php

```php
// Add to response middleware
'X-Content-Type-Options' => 'nosniff',
'X-Frame-Options' => 'SAMEORIGIN',
'X-XSS-Protection' => '1; mode=block',
'Content-Security-Policy' => "default-src 'self'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'",
```

---

## DEPLOYMENT NOTES

**CRITICAL: Do not deploy to production without:**

1. Testing all three RCE vulnerabilities are fixed
2. Implementing file upload validation on all controllers
3. Configuring .htaccess protection on upload directories
4. Running security tests
5. Code review by security team

**Estimate: 2-4 hours implementation + 1-2 hours testing**

