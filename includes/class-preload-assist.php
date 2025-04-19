<?php
/**
 * The main plugin class.
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
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    PreloadAssist
 * @subpackage PreloadAssist/includes
 */
class Preload_Assist {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      object    $facet_manager    Manages FacetWP facet importing and values.
     */
    protected $facet_manager;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      object    $category_detector    Manages WooCommerce category detection.
     */
    protected $category_detector;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      object    $url_generator    Manages URL generation.
     */
    protected $url_generator;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      object    $file_manager    Manages file operations.
     */
    protected $file_manager;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      object    $database_manager    Manages database operations.
     */
    protected $database_manager;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      object    $preload_integrator    Manages integration with FlyingPress.
     */
    protected $preload_integrator;

    /**
     * The admin functionality instance.
     *
     * @var      object    $admin    Manages admin interface and functionalities.
     */
    protected $admin;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->load_dependencies();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Include class files
        require_once PRELOAD_ASSIST_PLUGIN_DIR . 'includes/class-database-manager.php';
        require_once PRELOAD_ASSIST_PLUGIN_DIR . 'includes/class-facet-import-manager.php';
        require_once PRELOAD_ASSIST_PLUGIN_DIR . 'includes/class-category-detector.php';
        require_once PRELOAD_ASSIST_PLUGIN_DIR . 'includes/class-url-generator.php';
        require_once PRELOAD_ASSIST_PLUGIN_DIR . 'includes/class-file-manager.php';
        require_once PRELOAD_ASSIST_PLUGIN_DIR . 'includes/class-preload-integrator.php';
        require_once PRELOAD_ASSIST_PLUGIN_DIR . 'admin/class-admin.php';

        // Initialize components
        $this->database_manager = new Database_Manager();
        $this->facet_manager = new Facet_Import_Manager();
        $this->category_detector = new Category_Detector();
        $this->url_generator = new URL_Generator($this->facet_manager, $this->category_detector);
        $this->file_manager = new File_Manager();
        $this->preload_integrator = new Preload_Integrator($this->file_manager);
        $this->admin = new Admin\Admin($this->facet_manager, $this->category_detector, $this->url_generator, $this->file_manager, $this->database_manager);
    }

    /**
     * Initialize the plugin components and register hooks.
     */
    public function init() {
        // Check if required plugins are active
        if (!$this->check_dependencies()) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return;
        }

        // Initialize core components
        $this->database_manager->init();
        $this->preload_integrator->init();
        
        // Initialize admin interface if in admin area
        if (is_admin()) {
            $this->admin->init();
        }
    }

    /**
     * Check if required plugins are active.
     *
     * @return bool
     */
    private function check_dependencies() {
        $woocommerce_active = class_exists('WooCommerce');
        $facetwp_active = class_exists('FacetWP');
        
        // FlyingPress is only required if integration is enabled
        return $woocommerce_active && $facetwp_active;
    }

    /**
     * Display admin notice for missing dependencies.
     */
    public function dependency_notice() {
        $woocommerce_active = class_exists('WooCommerce');
        $facetwp_active = class_exists('FacetWP');
        $flyingpress_active = class_exists('FlyingPress') || function_exists('flying_press_preload_urls');
        
        $missing_plugins = array();
        
        if (!$woocommerce_active) {
            $missing_plugins[] = 'WooCommerce';
        }
        
        if (!$facetwp_active) {
            $missing_plugins[] = 'FacetWP';
        }
        
        // Only show FlyingPress as a requirement if integration is enabled and FlyingPress is not active
        $flyingpress_integration_enabled = $this->database_manager->get_setting('flyingpress_integration_enabled', false);
        if ($flyingpress_integration_enabled && !$flyingpress_active) {
            $missing_plugins[] = 'FlyingPress (required for preload integration)';
        }
        
        if (!empty($missing_plugins)) {
            $message = sprintf(
                '<div class="error"><p>%s %s</p></div>',
                __('Preload Assist requires the following plugins to be active:', 'preload-assist'),
                implode(', ', $missing_plugins)
            );
            echo wp_kses_post($message);
        }
    }

    /**
     * Fired during plugin activation.
     */
    public static function activate() {
        require_once PRELOAD_ASSIST_PLUGIN_DIR . 'includes/class-database-manager.php';
        $database_manager = new Database_Manager();
        $database_manager->create_tables();
        
        // Create necessary directories
        $upload_dir = wp_upload_dir();
        $preload_dir = $upload_dir['basedir'] . '/preload-assist';
        
        if (!file_exists($preload_dir)) {
            wp_mkdir_p($preload_dir);
            
            // Create .htaccess to protect directory
            $htaccess_content = "# Disable directory browsing\nOptions -Indexes\n\n# Deny access to all files\n<FilesMatch \".*\">\nOrder Allow,Deny\nDeny from all\n</FilesMatch>";
            file_put_contents($preload_dir . '/.htaccess', $htaccess_content);
        }
    }

    /**
     * Fired during plugin deactivation.
     */
    public static function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('preload_assist_cleanup_files');
    }
}