<?php
/**
 * Multi-layer Caching System
 * Supports: File, Redis (if available), Memory
 */

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
    
    /**
     * Get cached value
     */
    public function get($key, $default = null) {
        $key = $this->prefix . $key;
        
        // Check memory first
        if (isset($this->memoryCache[$key])) {
            return $this->memoryCache[$key];
        }
        
        // Check Redis
        if ($this->driver) {
            $value = $this->driver->get($key);
            if ($value !== false) {
                $this->memoryCache[$key] = $value;
                return $value;
            }
            return $default;
        }
        
        // File cache
        return $this->getFileCache($key, $default);
    }
    
    /**
     * Set cached value
     */
    public function set($key, $value, $ttl = 3600) {
        $key = $this->prefix . $key;
        $this->memoryCache[$key] = $value;
        
        if ($this->driver) {
            return $this->driver->setex($key, $ttl, $value);
        }
        
        return $this->setFileCache($key, $value, $ttl);
    }
    
    /**
     * Delete cached value
     */
    public function delete($key) {
        $key = $this->prefix . $key;
        unset($this->memoryCache[$key]);
        
        if ($this->driver) {
            return $this->driver->del($key);
        }
        
        return $this->deleteFileCache($key);
    }
    
    /**
     * Clear all cache
     */
    public function flush() {
        $this->memoryCache = [];
        
        if ($this->driver) {
            $this->driver->flushDB();
        } else {
            $this->clearFileCache();
        }
    }
    
    /**
     * Remember pattern (Laravel-style)
     */
    public function remember($key, $ttl, $callback) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    // File cache methods
    private function getFileCache($key, $default) {
        $file = $this->getCachePath($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            unlink($file);
            return $default;
        }
        
        return $data['value'];
    }
    
    private function setFileCache($key, $value, $ttl) {
        $dir = __DIR__ . '/../cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $file = $this->getCachePath($key);
        $data = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0
        ];
        
        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }
    
    private function deleteFileCache($key) {
        $file = $this->getCachePath($key);
        return file_exists($file) ? unlink($file) : true;
    }
    
    private function clearFileCache() {
        $dir = __DIR__ . '/../cache';
        if (!is_dir($dir)) return;
        
        $files = glob($dir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    private function getCachePath($key) {
        return __DIR__ . '/../cache/' . md5($key) . '.cache';
    }
}

/**
 * Helper functions
 */
function cache_get($key, $default = null) {
    return Cache::getInstance()->get($key, $default);
}

function cache_set($key, $value, $ttl = 3600) {
    return Cache::getInstance()->set($key, $value, $ttl);
}

function cache_remember($key, $ttl, $callback) {
    return Cache::getInstance()->remember($key, $ttl, $callback);
}

function cache_flush() {
    Cache::getInstance()->flush();
}