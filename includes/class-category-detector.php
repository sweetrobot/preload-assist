<?php
/**
 * WooCommerce Category Detector Class
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
 * WooCommerce Category Detector Class
 *
 * Detects and manages WooCommerce product categories.
 */
class Category_Detector {

    /**
     * Database manager instance.
     *
     * @var Database_Manager
     */
    private $db_manager;

    /**
     * Initialize the class.
     */
    public function __construct() {
        $this->db_manager = new Database_Manager();
    }

    /**
     * Detect all WooCommerce product categories.
     *
     * @return array
     */
    public function detect_categories() {
        if (!function_exists('WC')) {
            return [];
        }
        
        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC'
        ];
        
        $product_categories = get_terms($args);
        
        if (is_wp_error($product_categories) || empty($product_categories)) {
            return [];
        }
        
        $result = [];
        
        foreach ($product_categories as $category) {
            $db_category = $this->db_manager->get_category($category->term_id);
            $is_enabled = $db_category ? $db_category['is_enabled'] : 0;
            $settings = $db_category ? $db_category['settings'] : [];
            
            $result[] = [
                'id'         => $category->term_id,
                'name'       => $category->name,
                'slug'       => $category->slug,
                'parent'     => $category->parent,
                'count'      => $category->count,
                'url'        => get_term_link($category, 'product_cat'),
                'is_enabled' => $is_enabled,
                'settings'   => $settings
            ];
        }
        
        return $result;
    }

    /**
     * Get category details by ID.
     *
     * @param int $category_id The category ID.
     * @return array|null
     */
    public function get_category($category_id) {
        $categories = $this->detect_categories();
        
        foreach ($categories as $category) {
            if ($category['id'] == $category_id) {
                return $category;
            }
        }
        
        return null;
    }

    /**
     * Sync categories with database.
     *
     * @return bool
     */
    public function sync_categories() {
        $categories = $this->detect_categories();
        
        if (empty($categories)) {
            return false;
        }
        
        foreach ($categories as $category) {
            $db_category = $this->db_manager->get_category($category['id']);
            $is_enabled = $db_category ? $db_category['is_enabled'] : 0;
            $settings = $db_category ? $db_category['settings'] : [];
            
            $this->db_manager->save_category(
                $category['id'],
                $is_enabled,
                $settings
            );
        }
        
        return true;
    }

    /**
     * Get enabled categories.
     *
     * @return array
     */
    public function get_enabled_categories() {
        $categories = $this->detect_categories();
        $result = [];
        
        foreach ($categories as $category) {
            if ($category['is_enabled']) {
                $result[] = $category;
            }
        }
        
        return $result;
    }

    /**
     * Enable a category.
     *
     * @param int $category_id The category ID.
     * @param array $settings The category settings.
     * @return bool
     */
    public function enable_category($category_id, $settings = []) {
        return $this->db_manager->save_category($category_id, true, $settings);
    }

    /**
     * Disable a category.
     *
     * @param int $category_id The category ID.
     * @return bool
     */
    public function disable_category($category_id) {
        return $this->db_manager->save_category($category_id, false, []);
    }

    /**
     * Get hierarchical categories.
     *
     * @return array
     */
    public function get_hierarchical_categories() {
        $categories = $this->detect_categories();
        $hierarchical = [];
        
        // First, organize by parent
        $children = [];
        foreach ($categories as $category) {
            $children[$category['parent']][] = $category;
        }
        
        // Then, build the tree
        $this->build_category_tree($hierarchical, $children, 0);
        
        return $hierarchical;
    }

    /**
     * Build category tree recursively.
     *
     * @param array $result The result array.
     * @param array $children Children organized by parent.
     * @param int $parent The parent ID.
     * @param int $depth The current depth.
     */
    private function build_category_tree(&$result, $children, $parent = 0, $depth = 0) {
        if (isset($children[$parent])) {
            foreach ($children[$parent] as $category) {
                $category['depth'] = $depth;
                $result[] = $category;
                
                if (isset($children[$category['id']])) {
                    $this->build_category_tree($result, $children, $category['id'], $depth + 1);
                }
            }
        }
    }

    /**
     * Get category URL.
     *
     * @param int $category_id The category ID.
     * @return string|false
     */
    public function get_category_url($category_id) {
        $category = $this->get_category($category_id);
        
        if (!$category) {
            return false;
        }
        
        return $category['url'];
    }
}