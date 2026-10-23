<?php
/**
 * Asset Management dengan Versioning (v2.0 FINAL)
 * Safe declaration untuk mencegah konflik redeclare
 */

/* ================================================================
   Class Asset dibungkus class_exists() agar tidak konflik
   ================================================================ */
if (!class_exists('Asset', false)) {
    class Asset {
        private static $manifest = [];
        
        public static function url($path) {
            $fullPath = __DIR__ . '/../' . ltrim($path, '/');
            $baseUrl = function_exists('url') ? url($path) : '/' . ltrim($path, '/');
            
            if (!file_exists($fullPath)) {
                return $baseUrl; // Fallback
            }
            
            $version = filemtime($fullPath);
            return $baseUrl . '?v=' . $version;
        }
        
        public static function css($path) {
            return '<link rel="stylesheet" href="' . self::url($path) . '">';
        }
        
        public static function js($path, $defer = true) {
            $deferAttr = $defer ? ' defer' : '';
            return '<script src="' . self::url($path) . '"' . $deferAttr . '></script>';
        }
        
        public static function preload($path, $as = 'style') {
            return '<link rel="preload" href="' . self::url($path) . '" as="' . $as . '">';
        }
        
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
                            'url' => function_exists('url') ? url($path) : '/' . ltrim($path, '/'),
                            'version' => $file->getMTime()
                        ];
                    }
                }
            }
            
            $manifestPath = __DIR__ . '/../assets/manifest.json';
            if (!is_dir(dirname($manifestPath))) {
                @mkdir(dirname($manifestPath), 0755, true);
            }
            @file_put_contents($manifestPath, json_encode($assets, JSON_PRETTY_PRINT));
        }
    }
}

/* ===== HELPER FUNCTIONS (Safe Declaration) ===== */
if (!function_exists('asset_css')) {
    function asset_css($path) {
        if (class_exists('Asset', false)) {
            echo Asset::css($path);
        } else {
            $url = function_exists('url') ? url($path) : '/' . ltrim($path, '/');
            echo '<link rel="stylesheet" href="' . $url . '">';
        }
    }
}

if (!function_exists('asset_js')) {
    function asset_js($path, $defer = true) {
        if (class_exists('Asset', false)) {
            echo Asset::js($path, $defer);
        } else {
            $url = function_exists('url') ? url($path) : '/' . ltrim($path, '/');
            $deferAttr = $defer ? ' defer' : '';
            echo '<script src="' . $url . '"' . $deferAttr . '></script>';
        }
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path) {
        if (class_exists('Asset', false)) {
            return Asset::url($path);
        }
        return function_exists('url') ? url($path) : '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset_preload')) {
    function asset_preload($path, $as = 'style') {
        if (class_exists('Asset', false)) {
            return Asset::preload($path, $as);
        }
        $url = function_exists('url') ? url($path) : '/' . ltrim($path, '/');
        return '<link rel="preload" href="' . $url . '" as="' . $as . '">';
    }
}