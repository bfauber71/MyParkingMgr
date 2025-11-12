<?php
/**
 * ZPL Image Converter
 * Converts images to ZPL ^GF (Graphics Field) format for Zebra printers
 */

class ZPLImageConverter {
    
    /**
     * Convert a base64 image data URL to ZPL format
     * 
     * @param string $dataUrl Base64 encoded image data URL (e.g., data:image/png;base64,...)
     * @param int $maxWidth Maximum width in dots (default 536 for ZQ510 3" width with margins)
     * @param int $threshold Brightness threshold for black/white conversion (0-255, default 128)
     * @param bool $compress Whether to use ZPL compression (default false for reliability)
     * @return array|false Array with 'zpl' and 'height' keys, or false on error
     */
    public static function convertToZPL($dataUrl, $maxWidth = 536, $threshold = 128, $compress = false) {
        try {
            // Extract base64 data from data URL
            if (!preg_match('/^data:image\/(\w+);base64,(.*)$/', $dataUrl, $matches)) {
                error_log("ZPL Converter: Invalid data URL format");
                return false;
            }
            
            $imageType = $matches[1];
            $base64Data = $matches[2];
            
            // Decode base64 data
            $imageData = base64_decode($base64Data);
            if ($imageData === false) {
                error_log("ZPL Converter: Failed to decode base64 data");
                return false;
            }
            
            // Create image resource from data
            $image = imagecreatefromstring($imageData);
            if ($image === false) {
                error_log("ZPL Converter: Failed to create image from data");
                return false;
            }
            
            // Get original dimensions
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);
            
            // Calculate new dimensions maintaining aspect ratio
            if ($originalWidth > $maxWidth) {
                $scale = $maxWidth / $originalWidth;
                $newWidth = $maxWidth;
                $newHeight = (int)($originalHeight * $scale);
            } else {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }
            
            // Create resized image with white background (for transparency handling)
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Fill with white background to handle transparent PNGs
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $white);
            
            // Enable alpha blending for transparent images
            imagealphablending($resized, true);
            imagesavealpha($resized, true);
            
            // Resample image onto white background
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            imagedestroy($image);
            
            // Convert to grayscale and then to monochrome
            imagefilter($resized, IMG_FILTER_GRAYSCALE);
            
            // Convert to bitmap array (1 = black, 0 = white)
            $bitmap = [];
            for ($y = 0; $y < $newHeight; $y++) {
                $row = [];
                for ($x = 0; $x < $newWidth; $x++) {
                    $rgb = imagecolorat($resized, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    // Use brightness to determine black/white
                    $row[] = ($r < $threshold) ? 1 : 0;
                }
                $bitmap[] = $row;
            }
            imagedestroy($resized);
            
            // Convert bitmap to ZPL hex format
            $zplHex = self::bitmapToZPLHex($bitmap, $newWidth, $newHeight, $compress);
            
            // Calculate bytes per row (width rounded up to nearest byte)
            $bytesPerRow = (int)ceil($newWidth / 8);
            $totalBytes = $bytesPerRow * $newHeight;
            
            // Build ZPL graphic field command
            // Format: ^GFA,total_bytes,total_bytes,bytes_per_row,hex_data
            $zpl = "^GFA," . $totalBytes . "," . $totalBytes . "," . $bytesPerRow . "," . $zplHex;
            
            // Return ZPL code and dimensions for proper spacing
            return [
                'zpl' => $zpl,
                'width' => $newWidth,
                'height' => $newHeight
            ];
            
        } catch (Exception $e) {
            error_log("ZPL Converter error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Convert bitmap array to ZPL hex format
     * 
     * @param array $bitmap 2D array of 1s (black) and 0s (white)
     * @param int $width Image width
     * @param int $height Image height
     * @param bool $compress Whether to use ZPL compression
     * @return string Hex encoded bitmap data
     */
    private static function bitmapToZPLHex($bitmap, $width, $height, $compress = false) {
        $hex = '';
        $bytesPerRow = (int)ceil($width / 8);
        
        for ($y = 0; $y < $height; $y++) {
            $bits = '';
            for ($x = 0; $x < $width; $x++) {
                $bits .= $bitmap[$y][$x];
            }
            
            // Pad to multiple of 8 bits (must match bytesPerRow * 8)
            $remainder = strlen($bits) % 8;
            if ($remainder > 0) {
                $bits .= str_repeat('0', 8 - $remainder);
            }
            
            // Convert bits to hex bytes
            for ($i = 0; $i < strlen($bits); $i += 8) {
                $byte = substr($bits, $i, 8);
                $hex .= sprintf('%02X', bindec($byte));
            }
        }
        
        // Optionally compress hex using ZPL run-length encoding
        // Compression disabled by default for reliability - uncompressed is more stable
        if ($compress) {
            $hex = self::compressZPLHex($hex);
        }
        
        return $hex;
    }
    
    /**
     * Compress hex data using ZPL run-length encoding
     * 
     * @param string $hex Uncompressed hex string
     * @return string Compressed hex string
     */
    private static function compressZPLHex($hex) {
        $compressed = '';
        $length = strlen($hex);
        $i = 0;
        
        while ($i < $length) {
            $char = $hex[$i];
            $count = 1;
            
            // Count consecutive identical characters
            while ($i + $count < $length && $hex[$i + $count] === $char && $count < 400) {
                $count++;
            }
            
            // ZPL compression rules:
            // 1-19 repetitions: g-z represent 1-20 (g=1, h=2, ..., z=20)
            // 20-399 repetitions: G-Z followed by digit represent multiples of 20
            if ($count === 1) {
                $compressed .= $char;
            } elseif ($count <= 20) {
                $compressed .= chr(ord('f') + $count) . $char;
            } else {
                $groups = (int)floor($count / 20);
                $remainder = $count % 20;
                
                if ($groups <= 20) {
                    $compressed .= chr(ord('F') + $groups) . $char;
                    if ($remainder > 0) {
                        $compressed .= chr(ord('f') + $remainder) . $char;
                    }
                } else {
                    // For very long runs, just repeat
                    $compressed .= str_repeat($char, $count);
                }
            }
            
            $i += $count;
        }
        
        return $compressed;
    }
    
    /**
     * Get recommended settings for logo conversion
     * 
     * @return array Recommended width and threshold values
     */
    public static function getRecommendedSettings() {
        return [
            'max_width' => 536,  // 3" printer with 20pt margins on each side
            'threshold' => 128,  // Middle brightness threshold
            'info' => 'For Zebra ZQ510 (3" / 203 DPI thermal printer)'
        ];
    }
}
