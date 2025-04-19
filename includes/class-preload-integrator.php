<?php
/**
 * Preload Integrator Class
 *
 * @package    PreloadAssist
 * @subpackage PreloadAssist/includes
 */

namespace PreloadAssist;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Preload Integrator Class
 *
 * Integrates with FlyingPress for cache preloading.
 */
class Preload_Integrator {

    /**
     * File manager instance.
     *
     * @var File_Manager
     */
    private $file_manager;

    /**
     * Initialize the class.
     *
     * @param File_Manager $file_manager The file manager instance.
     */
    public function __construct($file_manager) {
        $this->file_manager = $file_manager;
    }

    /**
     * Initialize hooks and filters.
     */
    public function init() {
        // Only add the filter if integration is enabled and FlyingPress is active
        $db_manager = new Database_Manager();
        $integration_enabled = $db_manager->get_setting('flyingpress_integration_enabled', false);
        
        if ($integration_enabled && $this->is_flyingpress_active()) {
            add_filter('flying_press_preload_urls', array($this, 'add_preload_urls'));
        }
    }

    /**
     * Add URLs to FlyingPress preload list.
     *
     * @param array $urls The existing URLs.
     * @return array
     */
    public function add_preload_urls($urls) {
        $selected_file = $this->file_manager->get_selected_file();
        
        if (!$selected_file || !file_exists($selected_file['file_path'])) {
            return $urls;
        }
        
        $file_urls = file($selected_file['file_path'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (is_array($file_urls)) {
            $urls = array_merge($urls, $file_urls);
        }
        
        return $urls;
    }

    /**
     * Check if FlyingPress is active.
     *
     * @return bool
     */
    public function is_flyingpress_active() {
        return class_exists('FlyingPress') || function_exists('flying_press_preload_urls');
    }

    /**
     * Get selected file status.
     *
     * @return array
     */
    public function get_integration_status() {
        $selected_file = $this->file_manager->get_selected_file();
        $flyingpress_active = $this->is_flyingpress_active();
        
        return [
            'flyingpress_active' => $flyingpress_active,
            'file_selected' => !empty($selected_file),
            'file_exists' => !empty($selected_file) && file_exists($selected_file['file_path']),
            'file_details' => $selected_file
        ];
    }

    /**
     * Trigger manual preload.
     *
     * @return bool|WP_Error
     */
    public function trigger_preload() {
        if (!$this->is_flyingpress_active()) {
            return new \WP_Error('flyingpress_inactive', __('FlyingPress is not active', 'preload-assist'));
        }
        
        $selected_file = $this->file_manager->get_selected_file();
        
        if (!$selected_file || !file_exists($selected_file['file_path'])) {
            return new \WP_Error('no_file_selected', __('No file selected or file not found', 'preload-assist'));
        }
        
        // Check if FlyingPress has the function to manually trigger preload
        if (function_exists('flying_press_trigger_preload')) {
            flying_press_trigger_preload();
            return true;
        }
        
        return new \WP_Error('preload_function_missing', __('FlyingPress preload trigger function not found', 'preload-assist'));
    }
}