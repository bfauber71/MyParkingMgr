# ZPL Logo Integration Guide

## Overview

This guide explains the new ZPL logo integration feature that allows header logos to be printed on Zebra thermal printers (ZQ510 and compatible models).

## What's New

### Automatic Logo Conversion

When you upload a logo image in the Printer Settings:
1. **Original Image** - Stored for paper printing (HTML tickets)
2. **ZPL Version** - Automatically converted and stored for Zebra printer

The system now converts uploaded logos to ZPL ^GF (Graphics Field) format during upload, enabling logos to print on thermal printers.

## Technical Details

### Image Conversion Process

1. **Base64 Decode** - Extract image data from data URL
2. **Resize** - Scale to fit printer width (536 dots max for ZQ510 with margins)
3. **Grayscale** - Convert to grayscale
4. **Monochrome** - Convert to black & white bitmap (threshold: 128)
5. **ZPL Encoding** - Convert bitmap to hex format
6. **Compression** - Apply ZPL run-length encoding
7. **Store** - Save as `logo_top_zpl` or `logo_bottom_zpl` in database

### Supported Image Formats

- PNG
- JPG / JPEG
- GIF
- WEBP

### Database Schema

New fields added to `printer_settings` table:
- `logo_top_zpl` (MEDIUMTEXT) - ZPL format top logo
- `logo_bottom_zpl` (MEDIUMTEXT) - ZPL format bottom logo

### Logo Specifications

**Recommended:**
- Max width: 536 dots (for 3" printer with 20pt margins)
- Aspect ratio: Maintained automatically
- Color: Any (converted to black & white)
- File size: No specific limit (converted to ~50-100KB ZPL)

**Best Practices:**
- Use high-contrast logos (dark on light or light on dark)
- Avoid grayscale gradients (they convert to dithering)
- Simple, bold designs work best on thermal printers
- Square or horizontal logos work better than vertical

## Installation / Upgrade

### 1. Run SQL Migration

```bash
# In phpMyAdmin or MySQL command line
mysql -u username -p database_name < sql/add-zpl-logo-fields.sql
```

Or manually run:
```sql
ALTER TABLE printer_settings 
ADD COLUMN logo_top_zpl MEDIUMTEXT NULL 
COMMENT 'ZPL ^GF format graphic for top logo' 
AFTER logo_top;

ALTER TABLE printer_settings 
ADD COLUMN logo_bottom_zpl MEDIUMTEXT NULL 
COMMENT 'ZPL ^GF format graphic for bottom logo' 
AFTER logo_bottom;
```

### 2. Replace/Upload Files

**New Files:**
- `includes/zpl-image-converter.php` (New ZPL converter class)
- `sql/add-zpl-logo-fields.sql` (Migration script)

**Updated Files:**
- `api/printer-settings.php` (Logo conversion on upload)
- `api/violations-zpl.php` (Logo rendering in tickets)

### 3. Re-upload Existing Logos

**Important:** After upgrading, you must re-upload existing logos to generate ZPL versions:

1. Go to Settings → Printer Settings
2. Upload your logo again (even if already uploaded)
3. Click "Save Settings"

The system will automatically:
- Store the original image (for paper printing)
- Convert and store the ZPL version (for Zebra printing)

## Usage

### Uploading a Logo

1. **Login as Admin**
2. **Go to Settings → Printer Settings**
3. **Click "Upload Logo" under Top Logo or Bottom Logo**
4. **Select your image file** (PNG, JPG, GIF, WEBP)
5. **Enable the logo** with the checkbox
6. **Click "Save Settings"**

The conversion happens automatically in the background.

### Printing with Logo

**Paper Printing (Browser):**
- Uses original high-quality image
- Works as before

**ZPL Printing (Zebra Printer):**
- Uses converted ZPL graphic
- Logo appears at top of ticket
- Automatically centered
- Black & white rendering

### Troubleshooting

**Logo doesn't appear on Zebra printer:**
1. Check logo is enabled in Printer Settings
2. Re-upload the logo (to generate ZPL version)
3. Check PHP error logs for conversion errors
4. Verify logo file is valid image format

**Logo looks wrong on thermal printer:**
- Try increasing contrast in original image
- Simplify logo design (fewer gradients)
- Use solid black & white logo
- Test with different brightness threshold

**PHP Memory Error during conversion:**
```php
// In php.ini or .htaccess
memory_limit = 256M
```

Large images may require more memory for conversion.

## Technical Architecture

### ZPLImageConverter Class

Located in `includes/zpl-image-converter.php`

**Main Method:**
```php
ZPLImageConverter::convertToZPL($dataUrl, $maxWidth = 536, $threshold = 128)
```

**Parameters:**
- `$dataUrl` - Base64 encoded image data URL
- `$maxWidth` - Maximum width in dots (default 536)
- `$threshold` - Black/white threshold 0-255 (default 128)

**Returns:**
- String: ZPL ^GF command with hex data
- False: On conversion error

### API Integration

**printer-settings.php:**
```php
// When logo uploaded
$zplData = ZPLImageConverter::convertToZPL($value);
if ($zplData !== false) {
    // Store in database as logo_top_zpl or logo_bottom_zpl
    Database::execute($sql, [$zplKey, $zplData, $zplData]);
}
```

**violations-zpl.php:**
```php
// When generating ticket
$logoZpl = Database::query("SELECT setting_value FROM printer_settings 
                            WHERE setting_key = 'logo_top_zpl'");
if (!empty($logoZpl)) {
    $zpl .= "^FO20," . $yPos . $logoZpl . "^FS\n";
}
```

## Performance Considerations

### Conversion Time
- Small logos (<100KB): ~0.1-0.5 seconds
- Medium logos (100-500KB): ~0.5-2 seconds
- Large logos (>500KB): ~2-5 seconds

### Storage Size
- Original image: Variable (stored as base64)
- ZPL version: ~50-200KB (compressed hex)

### Memory Usage
- PHP GD library required
- Typical memory: 10-50MB during conversion
- Peak memory: Up to 100MB for large images

## Requirements

### PHP Extensions
- **GD** (image processing) - Required
- **mbstring** (string operations) - Required
- **PDO MySQL** (database) - Required

### PHP Version
- PHP 7.4+ (8.0+ recommended)

### Printer Compatibility
- Zebra ZQ510 (tested)
- Zebra ZQ520
- Zebra QL series
- Most Zebra thermal printers with ZPL support

## Known Limitations

1. **Bottom logo not yet implemented** - Only top logo currently supported in ZPL output
2. **Logo size estimation** - Vertical spacing is fixed (120 dots), may need adjustment for very tall logos
3. **Grayscale conversion** - Gradients become dithered patterns on thermal printer
4. **Color loss** - All colors converted to black & white

## Future Enhancements

- [ ] Dynamic logo height detection and spacing
- [ ] Custom threshold setting per logo
- [ ] Bottom logo support in ZPL
- [ ] Logo preview in settings (showing ZPL version)
- [ ] Advanced dithering algorithms
- [ ] Logo rotation/alignment options

## Support

For issues or questions:
1. Check PHP error logs
2. Verify GD extension is installed: `php -m | grep gd`
3. Test with simple black & white logo first
4. Check database for `logo_top_zpl` data

## Credits

ZPL image conversion based on ZPL programming language specification from Zebra Technologies.
