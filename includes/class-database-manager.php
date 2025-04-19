<?php
/**
 * Database Manager Class
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
 * Database Manager Class
 *
 * Handles all database operations for the plugin including creating,
 * updating, and managing custom database tables.
 */
class Database_Manager {

    /**
     * The database table names.
     *
     * @var array
     */
    private $tables;

    /**
     * Initialize the class.
     */
    public function __construct() {
        global $wpdb;
        
        $this->tables = array(
            'settings'      => $wpdb->prefix . 'preload_assist_settings',
            'categories'    => $wpdb->prefix . 'preload_assist_categories',
            'facets'        => $wpdb->prefix . 'preload_assist_facets',
            'facet_values'  => $wpdb->prefix . 'preload_assist_facet_values',
            'parameters'    => $wpdb->prefix . 'preload_assist_parameters',
            'files'         => $wpdb->prefix . 'preload_assist_files'
        );
    }

    /**
     * Initialize hooks and filters.
     */
    public function init() {
        add_action('admin_init', array($this, 'check_tables'));
    }

    /**
     * Check if tables exist and create them if they don't.
     */
    public function check_tables() {
        $option_name = 'preload_assist_db_version';
        $current_version = get_option($option_name, '0');

        if (version_compare($current_version, PRELOAD_ASSIST_VERSION, '<')) {
            $this->create_tables();
            update_option($option_name, PRELOAD_ASSIST_VERSION);
        }
    }

    /**
     * Create the required database tables.
     */
    public function create_tables() {
        global $wpdb;
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Settings table
        $table_name = $this->tables['settings'];
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_name varchar(191) NOT NULL,
            setting_value longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_name (setting_name)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Categories table
        $table_name = $this->tables['categories'];
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            category_id bigint(20) NOT NULL,
            is_enabled tinyint(1) NOT NULL DEFAULT 0,
            settings longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY category_id (category_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Facets table
        $table_name = $this->tables['facets'];
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            facet_name varchar(191) NOT NULL,
            facet_label varchar(191) NOT NULL,
            facet_type varchar(50) NOT NULL,
            facet_source varchar(191) NOT NULL,
            is_enabled tinyint(1) NOT NULL DEFAULT 0,
            settings longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY facet_name (facet_name)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Facet values table
        $table_name = $this->tables['facet_values'];
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            category_id bigint(20) NOT NULL,
            facet_name varchar(191) NOT NULL,
            facet_value varchar(191) NOT NULL,
            is_selected tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY category_facet_value (category_id, facet_name, facet_value),
            KEY category_id (category_id),
            KEY facet_name (facet_name)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Parameters table
        $table_name = $this->tables['parameters'];
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            param_name varchar(191) NOT NULL,
            param_values longtext NOT NULL,
            is_enabled tinyint(1) NOT NULL DEFAULT 0,
            position varchar(10) NOT NULL DEFAULT 'after',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY param_name (param_name)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Files table
        $table_name = $this->tables['files'];
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            file_name varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            file_size bigint(20) NOT NULL DEFAULT 0,
            url_count int(11) NOT NULL DEFAULT 0,
            is_selected tinyint(1) NOT NULL DEFAULT 0,
            generated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * Get all database tables.
     *
     * @return array
     */
    public function get_tables() {
        return $this->tables;
    }

    /**
     * Get a single table name.
     *
     * @param string $table The table key.
     * @return string|null
     */
    public function get_table($table) {
        return isset($this->tables[$table]) ? $this->tables[$table] : null;
    }

    /**
     * Save a setting.
     *
     * @param string $name The setting name.
     * @param mixed $value The setting value.
     * @return bool
     */
    public function save_setting($name, $value) {
        global $wpdb;
        
        $table = $this->tables['settings'];
        $data = array(
            'setting_name'  => $name,
            'setting_value' => is_array($value) || is_object($value) ? wp_json_encode($value) : $value
        );
        
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE setting_name = %s", $name));
        
        if ($exists) {
            return $wpdb->update(
                $table,
                $data,
                array('setting_name' => $name)
            );
        } else {
            return $wpdb->insert($table, $data);
        }
    }

    /**
     * Get a setting.
     *
     * @param string $name The setting name.
     * @param mixed $default The default value.
     * @return mixed
     */
    public function get_setting($name, $default = null) {
        global $wpdb;
        
        $table = $this->tables['settings'];
        $value = $wpdb->get_var($wpdb->prepare("SELECT setting_value FROM $table WHERE setting_name = %s", $name));
        
        if (null === $value) {
            return $default;
        }
        
        // Try to decode JSON
        $decoded = json_decode($value, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return $value;
    }

    /**
     * Save category settings.
     *
     * @param int   $category_id The category ID.
     * @param bool  $is_enabled Whether the category is enabled.
     * @param array $settings The category settings.
     * @return bool
     */
    public function save_category($category_id, $is_enabled, $settings = array()) {
        global $wpdb;
        
        $table = $this->tables['categories'];
        $data = array(
            'category_id' => $category_id,
            'is_enabled'  => $is_enabled ? 1 : 0,
            'settings'    => wp_json_encode($settings)
        );
        
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE category_id = %d", $category_id));
        
        if ($exists) {
            return $wpdb->update(
                $table,
                $data,
                array('category_id' => $category_id)
            );
        } else {
            return $wpdb->insert($table, $data);
        }
    }

    /**
     * Get category settings.
     *
     * @param int $category_id The category ID.
     * @return array|null
     */
    public function get_category($category_id) {
        global $wpdb;
        
        $table = $this->tables['categories'];
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE category_id = %d", $category_id), ARRAY_A);
        
        if (!$row) {
            return null;
        }
        
        $row['settings'] = json_decode($row['settings'], true);
        
        return $row;
    }

    /**
     * Get all enabled categories.
     *
     * @return array
     */
    public function get_enabled_categories() {
        global $wpdb;
        
        $table = $this->tables['categories'];
        $rows = $wpdb->get_results("SELECT * FROM $table WHERE is_enabled = 1", ARRAY_A);
        
        foreach ($rows as &$row) {
            $row['settings'] = json_decode($row['settings'], true);
        }
        
        return $rows;
    }

    /**
     * Save facet settings.
     *
     * @param string $name The facet name.
     * @param string $label The facet label.
     * @param string $type The facet type.
     * @param string $source The facet source.
     * @param bool   $is_enabled Whether the facet is enabled.
     * @param array  $settings The facet settings.
     * @return bool
     */
    public function save_facet($name, $label, $type, $source, $is_enabled, $settings = array()) {
        global $wpdb;
        
        $table = $this->tables['facets'];
        $data = array(
            'facet_name'   => $name,
            'facet_label'  => $label,
            'facet_type'   => $type,
            'facet_source' => $source,
            'is_enabled'   => $is_enabled ? 1 : 0,
            'settings'     => wp_json_encode($settings)
        );
        
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE facet_name = %s", $name));
        
        if ($exists) {
            return $wpdb->update(
                $table,
                $data,
                array('facet_name' => $name)
            );
        } else {
            return $wpdb->insert($table, $data);
        }
    }

    /**
     * Get facet settings.
     *
     * @param string $name The facet name.
     * @return array|null
     */
    public function get_facet($name) {
        global $wpdb;
        
        $table = $this->tables['facets'];
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE facet_name = %s", $name), ARRAY_A);
        
        if (!$row) {
            return null;
        }
        
        $row['settings'] = json_decode($row['settings'], true);
        
        return $row;
    }

    /**
     * Get all enabled facets.
     *
     * @return array
     */
    public function get_enabled_facets() {
        global $wpdb;
        
        $table = $this->tables['facets'];
        $rows = $wpdb->get_results("SELECT * FROM $table WHERE is_enabled = 1", ARRAY_A);
        
        foreach ($rows as &$row) {
            $row['settings'] = json_decode($row['settings'], true);
        }
        
        return $rows;
    }
    
    /**
     * Get all facets regardless of enabled status.
     *
     * @return array
     */
    public function get_facets() {
        global $wpdb;
        
        $table = $this->tables['facets'];
        $rows = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
        
        foreach ($rows as &$row) {
            $row['settings'] = json_decode($row['settings'], true);
        }
        
        return $rows;
    }

    /**
     * Save parameter.
     *
     * @param string $name The parameter name.
     * @param array  $values The parameter values.
     * @param bool   $is_enabled Whether the parameter is enabled.
     * @param string $position Position ('before' or 'after').
     * @return bool
     */
    public function save_parameter($name, $values, $is_enabled, $position = 'after') {
        global $wpdb;
        
        $table = $this->tables['parameters'];
        $data = array(
            'param_name'   => $name,
            'param_values' => wp_json_encode($values),
            'is_enabled'   => $is_enabled ? 1 : 0,
            'position'     => ($position === 'before') ? 'before' : 'after'
        );
        
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE param_name = %s", $name));
        
        if ($exists) {
            return $wpdb->update(
                $table,
                $data,
                array('param_name' => $name)
            );
        } else {
            return $wpdb->insert($table, $data);
        }
    }
    
    /**
     * Save facet value selection.
     *
     * @param int    $category_id The category ID.
     * @param string $facet_name The facet name.
     * @param string $facet_value The facet value.
     * @param bool   $is_selected Whether the value is selected.
     * @return bool
     */
    public function save_facet_value($category_id, $facet_name, $facet_value, $is_selected) {
        global $wpdb;
        
        $table = $this->tables['facet_values'];
        $data = array(
            'category_id' => $category_id,
            'facet_name'  => $facet_name,
            'facet_value' => $facet_value,
            'is_selected' => $is_selected ? 1 : 0
        );
        
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE category_id = %d AND facet_name = %s AND facet_value = %s",
                $category_id,
                $facet_name,
                $facet_value
            )
        );
        
        if ($exists) {
            return $wpdb->update(
                $table,
                $data,
                array(
                    'category_id' => $category_id,
                    'facet_name'  => $facet_name,
                    'facet_value' => $facet_value
                )
            );
        } else {
            return $wpdb->insert($table, $data);
        }
    }
    
    /**
     * Get selected facet values for a category.
     *
     * @param int    $category_id The category ID.
     * @param string $facet_name The facet name.
     * @return array Selected values
     */
    public function get_selected_facet_values($category_id, $facet_name) {
        global $wpdb;
        
        $table = $this->tables['facet_values'];
        
        $query = $wpdb->prepare(
            "SELECT facet_value FROM $table 
             WHERE category_id = %d AND facet_name = %s AND is_selected = 1",
            $category_id,
            $facet_name
        );
        
        return $wpdb->get_col($query);
    }
    
    /**
     * Get all facet values for a category with selection status.
     *
     * @param int    $category_id The category ID.
     * @param string $facet_name The facet name.
     * @return array Values with selection status
     */
    public function get_facet_values_with_status($category_id, $facet_name) {
        global $wpdb;
        
        $table = $this->tables['facet_values'];
        
        $query = $wpdb->prepare(
            "SELECT facet_value, is_selected FROM $table 
             WHERE category_id = %d AND facet_name = %s",
            $category_id,
            $facet_name
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $values = array();
        foreach ($results as $row) {
            $values[$row['facet_value']] = (bool) $row['is_selected'];
        }
        
        return $values;
    }
    
    /**
     * Save all facet values for a category.
     *
     * @param int    $category_id The category ID.
     * @param string $facet_name The facet name.
     * @param array  $selected_values The selected values array.
     * @param array  $all_values All possible values for the facet.
     * @return bool
     */
    public function save_facet_values($category_id, $facet_name, $selected_values, $all_values) {
        global $wpdb;
        
        // Start a transaction
        $wpdb->query('START TRANSACTION');
        
        $success = true;
        
        foreach ($all_values as $value) {
            $is_selected = in_array($value, $selected_values);
            $result = $this->save_facet_value($category_id, $facet_name, $value, $is_selected);
            
            if ($result === false) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $wpdb->query('COMMIT');
            return true;
        } else {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Update parameter position.
     *
     * @param string $param_name The parameter name.
     * @param string $position The parameter position ('before' or 'after').
     * @return bool
     */
    public function update_parameter_position($param_name, $position) {
        global $wpdb;
        
        $table = $this->tables['parameters'];
        
        return $wpdb->update(
            $table,
            array('position' => ($position === 'before') ? 'before' : 'after'),
            array('param_name' => $param_name)
        );
    }

    /**
     * Get parameter.
     *
     * @param string $name The parameter name.
     * @return array|null
     */
    public function get_parameter($name) {
        global $wpdb;
        
        $table = $this->tables['parameters'];
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE param_name = %s", $name), ARRAY_A);
        
        if (!$row) {
            return null;
        }
        
        $row['param_values'] = json_decode($row['param_values'], true);
        
        return $row;
    }

    /**
     * Get all enabled parameters.
     *
     * @return array
     */
    public function get_enabled_parameters() {
        global $wpdb;
        
        $table = $this->tables['parameters'];
        $rows = $wpdb->get_results("SELECT * FROM $table WHERE is_enabled = 1", ARRAY_A);
        
        foreach ($rows as &$row) {
            $row['param_values'] = json_decode($row['param_values'], true);
        }
        
        return $rows;
    }

    /**
     * Save file.
     *
     * @param string $name The file name.
     * @param string $path The file path.
     * @param int    $size The file size.
     * @param int    $url_count The number of URLs in the file.
     * @param bool   $is_selected Whether the file is selected for preloading.
     * @return bool
     */
    public function save_file($name, $path, $size, $url_count, $is_selected = false) {
        global $wpdb;
        
        $table = $this->tables['files'];
        $data = array(
            'file_name'    => $name,
            'file_path'    => $path,
            'file_size'    => $size,
            'url_count'    => $url_count,
            'is_selected'  => $is_selected ? 1 : 0,
            'generated_at' => current_time('mysql')
        );
        
        return $wpdb->insert($table, $data);
    }

    /**
     * Get all files.
     *
     * @return array
     */
    public function get_files() {
        global $wpdb;
        
        $table = $this->tables['files'];
        return $wpdb->get_results("SELECT * FROM $table ORDER BY generated_at DESC", ARRAY_A);
    }

    /**
     * Get the selected file.
     *
     * @return array|null
     */
    public function get_selected_file() {
        global $wpdb;
        
        $table = $this->tables['files'];
        return $wpdb->get_row("SELECT * FROM $table WHERE is_selected = 1 ORDER BY generated_at DESC LIMIT 1", ARRAY_A);
    }

    /**
     * Select a file for preloading.
     *
     * @param int $file_id The file ID.
     * @return bool
     */
    public function select_file($file_id) {
        global $wpdb;
        
        $table = $this->tables['files'];
        
        // Reset all files
        $wpdb->update(
            $table,
            array('is_selected' => 0),
            array('is_selected' => 1)
        );
        
        // Select the specified file
        return $wpdb->update(
            $table,
            array('is_selected' => 1),
            array('id' => $file_id)
        );
    }

    /**
     * Delete a file.
     *
     * @param int $file_id The file ID.
     * @return bool
     */
    public function delete_file($file_id) {
        global $wpdb;
        
        $table = $this->tables['files'];
        
        // Get file path before deleting
        $file = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $file_id), ARRAY_A);
        
        if ($file && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        return $wpdb->delete($table, array('id' => $file_id));
    }

    /**
     * Delete all plugin database tables.
     *
     * @return bool
     */
    public function delete_tables() {
        global $wpdb;
        
        foreach ($this->tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('preload_assist_db_version');
        
        return true;
    }
}