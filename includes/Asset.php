<?php
/**
 * Asset Management dengan Versioning
 */

class Asset {
    private static $manifest = [];
    
    /**
     * Get versioned URL untuk asset
     */
    public static function url($path) {
        $fullPath = __DIR__ . '/../' . ltrim($path, '/');
        
        if (!file_exists($fullPath)) {
            return url($path); // Fallback
        }
        
        $version = filemtime($fullPath);
        return url($path) . '?v=' . $version;
    }
    
    /**
     * Inline CSS dengan caching
     */
    public static function css($path) {
        return '<link rel="stylesheet" href="' . self::url($path) . '">';
    }
    
    /**
     * Inline JS dengan caching
     */
    public static function js($path) {
        return '<script src="' . self::url($path) . '" defer></script>';
    }
    
    /**
     * Preload critical assets
     */
    public static function preload($path, $as = 'style') {
        return '<link rel="preload" href="' . self::url($path) . '" as="' . $as . '">';
    }
    
    /**
     * Generate manifest file (untuk service worker)
     */
    public static function generateManifest() {
        $assets = [];
        $dirs = ['assets/public/css', 'assets/public/js', 'assets/public/img'];
        
        foreach ($dirs as $dir) {
            $fullDir = __DIR__ . '/../' . $dir;
            if (!is_dir($fullDir)) continue;
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullDir)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $path = str_replace(__DIR__ . '/../', '', $file->getPathname());
                    $assets[] = [
                        'url' => url($path),
                        'version' => $file->getMTime()
                    ];
                }
            }
        }
        
        file_put_contents(
            __DIR__ . '/../assets/manifest.json',
            json_encode($assets, JSON_PRETTY_PRINT)
        );
    }
}

// Helper functions
function asset_css($path) {
    echo Asset::css($path);
}

function asset_js($path) {
    echo Asset::js($path);
}

function asset_url($path) {
    return Asset::url($path);
}