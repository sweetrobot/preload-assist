<?php
/**
 * File Manager Class
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
 * File Manager Class
 *
 * Handles file operations for the plugin.
 */
class File_Manager {

    /**
     * Database manager instance.
     *
     * @var Database_Manager
     */
    private $db_manager;

    /**
     * The plugin file directory.
     *
     * @var string
     */
    private $file_dir;

    /**
     * Initialize the class.
     */
    public function __construct() {
        $this->db_manager = new Database_Manager();
        
        $upload_dir = wp_upload_dir();
        $this->file_dir = $upload_dir['basedir'] . '/preload-assist';
        
        // Ensure directory exists
        if (!file_exists($this->file_dir)) {
            wp_mkdir_p($this->file_dir);
            
            // Create .htaccess to protect directory
            $htaccess_content = "# Disable directory browsing\nOptions -Indexes\n\n# Deny access to all files\n<FilesMatch \".*\">\nOrder Allow,Deny\nDeny from all\n</FilesMatch>";
            file_put_contents($this->file_dir . '/.htaccess', $htaccess_content);
        }
    }

    /**
     * Get all URL files.
     *
     * @return array
     */
    public function get_files() {
        return $this->db_manager->get_files();
    }

    /**
     * Get file by ID.
     *
     * @param int $file_id The file ID.
     * @return array|null
     */
    public function get_file($file_id) {
        global $wpdb;
        
        $table = $this->db_manager->get_table('files');
        
        if (!$table) {
            return null;
        }
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $file_id), ARRAY_A);
    }

    /**
     * Get selected file.
     *
     * @return array|null
     */
    public function get_selected_file() {
        return $this->db_manager->get_selected_file();
    }

    /**
     * Select a file for preloading.
     *
     * @param int $file_id The file ID.
     * @return bool
     */
    public function select_file($file_id) {
        return $this->db_manager->select_file($file_id);
    }

    /**
     * Delete a file.
     *
     * @param int $file_id The file ID.
     * @return bool
     */
    public function delete_file($file_id) {
        $file = $this->get_file($file_id);
        
        if (!$file) {
            return false;
        }
        
        if (file_exists($file['file_path'])) {
            if (!unlink($file['file_path'])) {
                return false;
            }
        }
        
        global $wpdb;
        
        $table = $this->db_manager->get_table('files');
        
        if (!$table) {
            return false;
        }
        
        return $wpdb->delete($table, ['id' => $file_id]);
    }

    /**
     * Get file URLs.
     *
     * @param int $file_id The file ID.
     * @param int $limit Maximum number of URLs to return.
     * @param int $offset Offset for pagination.
     * @return array
     */
    public function get_file_urls($file_id, $limit = 100, $offset = 0) {
        $file = $this->get_file($file_id);
        
        if (!$file || !file_exists($file['file_path'])) {
            return [];
        }
        
        $urls = [];
        $handle = fopen($file['file_path'], 'r');
        
        if ($handle) {
            // Skip to offset
            for ($i = 0; $i < $offset; $i++) {
                if (fgets($handle) === false) {
                    break;
                }
            }
            
            // Read the requested number of lines
            $count = 0;
            while ($count < $limit && ($line = fgets($handle)) !== false) {
                $urls[] = trim($line);
                $count++;
            }
            
            fclose($handle);
        }
        
        return $urls;
    }

    /**
     * Count URLs in a file.
     *
     * @param int|string $file_id_or_path The file ID or path.
     * @return int
     */
    public function count_file_urls($file_id_or_path) {
        if (is_numeric($file_id_or_path)) {
            $file = $this->get_file($file_id_or_path);
            $file_path = $file ? $file['file_path'] : '';
        } else {
            $file_path = $file_id_or_path;
        }
        
        if (!file_exists($file_path)) {
            return 0;
        }
        
        $count = 0;
        $handle = fopen($file_path, 'r');
        
        if ($handle) {
            while (fgets($handle) !== false) {
                $count++;
            }
            
            fclose($handle);
        }
        
        return $count;
    }

    /**
     * Export a file.
     *
     * @param int $file_id The file ID.
     * @return array|WP_Error
     */
    public function export_file($file_id) {
        $file = $this->get_file($file_id);
        
        if (!$file || !file_exists($file['file_path'])) {
            return new \WP_Error('file_not_found', __('File not found', 'preload-assist'));
        }
        
        $upload_dir = wp_upload_dir();
        $download_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file['file_path']);
        
        return [
            'url' => $download_url,
            'filename' => $file['file_name']
        ];
    }

    /**
     * Clean up old files.
     *
     * @param int $keep_count Number of files to keep.
     * @return int Number of files deleted.
     */
    public function cleanup_files($keep_count = 5) {
        global $wpdb;
        
        $table = $this->db_manager->get_table('files');
        
        if (!$table) {
            return 0;
        }
        
        // Get all files ordered by creation date
        $files = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);
        
        if (count($files) <= $keep_count) {
            return 0;
        }
        
        $delete_count = 0;
        
        // Skip the first $keep_count files
        for ($i = $keep_count; $i < count($files); $i++) {
            $file = $files[$i];
            
            // Don't delete the selected file
            if ($file['is_selected']) {
                continue;
            }
            
            if ($this->delete_file($file['id'])) {
                $delete_count++;
            }
        }
        
        return $delete_count;
    }

    /**
     * Schedule file cleanup.
     */
    public function schedule_cleanup() {
        if (!wp_next_scheduled('preload_assist_cleanup_files')) {
            wp_schedule_event(time(), 'daily', 'preload_assist_cleanup_files');
        }
    }

    /**
     * Get file directory.
     *
     * @return string
     */
    public function get_file_directory() {
        return $this->file_dir;
    }

    /**
     * Get directory storage info.
     *
     * @return array
     */
    public function get_directory_info() {
        $total_size = 0;
        $file_count = 0;
        
        if (is_dir($this->file_dir)) {
            $files = scandir($this->file_dir);
            
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && $file != '.htaccess') {
                    $file_path = $this->file_dir . '/' . $file;
                    
                    if (is_file($file_path)) {
                        $total_size += filesize($file_path);
                        $file_count++;
                    }
                }
            }
        }
        
        return [
            'directory' => $this->file_dir,
            'total_size' => $total_size,
            'formatted_size' => $this->format_file_size($total_size),
            'file_count' => $file_count
        ];
    }

    /**
     * Format file size for display.
     *
     * @param int $size Size in bytes.
     * @return string
     */
    public function format_file_size($size) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Delete all plugin files.
     *
     * @return bool
     */
    public function delete_all_files() {
        if (!is_dir($this->file_dir)) {
            return true;
        }
        
        $files = scandir($this->file_dir);
        
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && $file != '.htaccess') {
                $file_path = $this->file_dir . '/' . $file;
                
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }
        }
        
        return true;
    }
}