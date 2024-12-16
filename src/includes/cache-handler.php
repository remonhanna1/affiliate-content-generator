<?php
class ACG_Cache_Handler {
    private static $instance = null;
    private $cache_expiry = 24 * HOUR_IN_SECONDS; // Cache for 24 hours
    private $cache_group = 'acg_seo_cache';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize cache group if needed
        wp_cache_add_non_persistent_groups($this->cache_group);
    }

    public function get_cached_data($key) {
        $cache_key = $this->generate_cache_key($key);
        return wp_cache_get($cache_key, $this->cache_group);
    }

    public function set_cached_data($key, $data) {
        $cache_key = $this->generate_cache_key($key);
        wp_cache_set($cache_key, $data, $this->cache_group, $this->cache_expiry);
    }

    public function delete_cached_data($key) {
        $cache_key = $this->generate_cache_key($key);
        wp_cache_delete($cache_key, $this->cache_group);
    }

    private function generate_cache_key($key) {
        return 'acg_seo_' . md5($key);
    }

    public function is_cache_valid($key) {
        $data = $this->get_cached_data($key);
        return !empty($data);
    }
}