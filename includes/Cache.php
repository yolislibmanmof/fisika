<?php
/**
 * Multi-layer Caching System (v2.0 FINAL)
 * Supports: File, Redis (if available), Memory
 * 
 * Dibungkus class_exists() agar tidak konflik dengan config/config.php
 */

/* ================================================================
   PENTING: Class hanya di-declare jika belum ada.
   Jika config.php sudah declare class Cache duluan,
   file ini akan di-skip (tidak error).
   ================================================================ */
if (!class_exists('Cache', false)) {

    class Cache {
        private static $instance = null;
        private $driver;
        private $prefix = 'fisika_';
        private $memoryCache = [];
        
        private function __construct() {
            // Priority: Redis > File
            if (class_exists('Redis')) {
                try {
                    $this->driver = new Redis();
                    $this->driver->connect('127.0.0.1', 6379, 1);
                    $this->driver->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
                } catch (Exception $e) {
                    $this->driver = null;
                }
            }
        }
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function get($key, $default = null) {
            $key = $this->prefix . $key;
            
            // Check memory first
            if (isset($this->memoryCache[$key])) {
                return $this->memoryCache[$key];
            }
            
            // Check Redis
            if ($this->driver) {
                try {
                    $value = $this->driver->get($key);
                    if ($value !== false) {
                        $this->memoryCache[$key] = $value;
                        return $value;
                    }
                } catch (Exception $e) {}
                return $default;
            }
            
            // File cache
            return $this->getFileCache($key, $default);
        }
        
        public function set($key, $value, $ttl = 3600) {
            $key = $this->prefix . $key;
            $this->memoryCache[$key] = $value;
            
            if ($this->driver) {
                try {
                    return $this->driver->setex($key, $ttl, $value);
                } catch (Exception $e) {
                    // Fallback ke file cache
                }
            }
            
            return $this->setFileCache($key, $value, $ttl);
        }
        
        public function delete($key) {
            $key = $this->prefix . $key;
            unset($this->memoryCache[$key]);
            
            if ($this->driver) {
                try {
                    return $this->driver->del($key);
                } catch (Exception $e) {}
            }
            
            return $this->deleteFileCache($key);
        }
        
        public function flush() {
            $this->memoryCache = [];
            
            if ($this->driver) {
                try {
                    $this->driver->flushDB();
                    return;
                } catch (Exception $e) {}
            }
            
            $this->clearFileCache();
        }
        
        public function remember($key, $ttl, $callback) {
            $value = $this->get($key);
            if ($value !== null) return $value;
            $value = $callback();
            $this->set($key, $value, $ttl);
            return $value;
        }
        
        private function getFileCache($key, $default) {
            $file = $this->getCachePath($key);
            if (!file_exists($file)) return $default;
            
            $data = @unserialize(file_get_contents($file));
            if ($data === false) {
                @unlink($file);
                return $default;
            }
            if (isset($data['expires']) && $data['expires'] > 0 && $data['expires'] < time()) {
                @unlink($file);
                return $default;
            }
            return $data['value'] ?? $default;
        }
        
        private function setFileCache($key, $value, $ttl) {
            $dir = defined('CACHE_PATH') ? CACHE_PATH : __DIR__ . '/../cache/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            
            $file = $this->getCachePath($key);
            $data = [
                'value' => $value,
                'expires' => $ttl > 0 ? time() + $ttl : 0,
                'created' => time()
            ];
            
            return @file_put_contents($file, serialize($data), LOCK_EX) !== false;
        }
        
        private function deleteFileCache($key) {
            $file = $this->getCachePath($key);
            return file_exists($file) ? @unlink($file) : true;
        }
        
        private function clearFileCache() {
            $dir = defined('CACHE_PATH') ? CACHE_PATH : __DIR__ . '/../cache/';
            if (!is_dir($dir)) return;
            
            $files = glob($dir . '*.cache');
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
        
        private function getCachePath($key) {
            $path = defined('CACHE_PATH') ? CACHE_PATH : __DIR__ . '/../cache/';
            return $path . md5($key) . '.cache';
        }
    }
}

/* ===== HELPER FUNCTIONS (Safe Declaration) ===== */
if (!function_exists('cache_get')) {
    function cache_get($key, $default = null) {
        if (!class_exists('Cache', false)) return $default;
        return Cache::getInstance()->get($key, $default);
    }
}

if (!function_exists('cache_set')) {
    function cache_set($key, $value, $ttl = 3600) {
        if (!class_exists('Cache', false)) return false;
        return Cache::getInstance()->set($key, $value, $ttl);
    }
}

if (!function_exists('cache_remember')) {
    function cache_remember($key, $ttl, $callback) {
        if (!class_exists('Cache', false)) return $callback();
        return Cache::getInstance()->remember($key, $ttl, $callback);
    }
}

if (!function_exists('cache_flush')) {
    function cache_flush() {
        if (!class_exists('Cache', false)) return;
        Cache::getInstance()->flush();
    }
}