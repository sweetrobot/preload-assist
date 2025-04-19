<?php
/**
 * Facet Import Manager Class
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
 * Facet Import Manager Class
 *
 * Handles importing and processing of FacetWP facet data from exported JSON.
 */
class Facet_Import_Manager {

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
     * Process and import facets from a FacetWP export JSON string.
     *
     * @param string $json_string The JSON export string from FacetWP.
     * @return array An array with status and data.
     */
    public function import_facets($json_string) {
        // Try to decode JSON
        $data = json_decode($json_string, true);
        
        if (null === $data || json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'message' => __('Invalid JSON format. Please provide a valid FacetWP export string.', 'preload-assist')
            ];
        }
        
        // Check if it's a valid FacetWP export
        if (!isset($data['facets']) || !is_array($data['facets'])) {
            return [
                'status' => 'error',
                'message' => __('Invalid FacetWP export format. The export must contain a "facets" array.', 'preload-assist')
            ];
        }
        
        $facets = $data['facets'];
        $imported = 0;
        $errors = [];
        
        // Save the original export for reference
        $this->db_manager->save_setting('facetwp_export', $json_string);
        
        // Process each facet
        foreach ($facets as $facet) {
            if (empty($facet['name']) || empty($facet['type'])) {
                $errors[] = __('Skipped facet without name or type', 'preload-assist');
                continue;
            }
            
            // Extract facet values from modifier_values if present
            $values = [];
            if (!empty($facet['modifier_values'])) {
                $values = array_filter(array_map('trim', explode("\n", $facet['modifier_values'])));
            }
            
            // Add the facet to the database
            $is_enabled = 1; // Default to enabled
            $existing = $this->db_manager->get_facet($facet['name']);
            
            if ($existing) {
                // Preserve the enabled status if it already exists
                $is_enabled = $existing['is_enabled'];
            }
            
            $source = isset($facet['source']) ? $facet['source'] : '';
            $settings = [
                'values' => $values,
                'original' => $facet
            ];
            
            $success = $this->db_manager->save_facet(
                $facet['name'],
                $facet['label'] ?? $facet['name'],
                $facet['type'],
                $source,
                $is_enabled,
                $settings
            );
            
            if ($success) {
                $imported++;
            } else {
                $errors[] = sprintf(__('Failed to import facet "%s"', 'preload-assist'), $facet['name']);
            }
        }
        
        return [
            'status' => empty($errors) ? 'success' : 'partial',
            'message' => sprintf(__('Successfully imported %d facets', 'preload-assist'), $imported),
            'imported' => $imported,
            'total' => count($facets),
            'errors' => $errors
        ];
    }
    
    /**
     * Get all imported facets with their values.
     *
     * @return array Array of facets with values
     */
    public function get_facets_with_values() {
        $facets = $this->db_manager->get_facets();
        $result = [];
        
        foreach ($facets as $facet) {
            $settings = $facet['settings'] ?? [];
            $values = $settings['values'] ?? [];
            
            $facet_data = [
                'name' => $facet['facet_name'],
                'label' => $facet['facet_label'],
                'type' => $facet['facet_type'],
                'source' => $facet['facet_source'],
                'is_enabled' => (bool) $facet['is_enabled'],
                'values' => $values,
                'original' => $settings['original'] ?? []
            ];
            
            $result[$facet['facet_name']] = $facet_data;
        }
        
        return $result;
    }
    
    /**
     * Get enabled facets with their values.
     *
     * @return array
     */
    public function get_enabled_facets_with_values() {
        $facets = $this->get_facets_with_values();
        $result = [];
        
        foreach ($facets as $name => $facet) {
            if ($facet['is_enabled']) {
                $result[$name] = $facet;
            }
        }
        
        return $result;
    }
    
    /**
     * Get facet details by name.
     *
     * @param string $name The facet name.
     * @return array|null
     */
    public function get_facet($name) {
        $facet = $this->db_manager->get_facet($name);
        
        if (!$facet) {
            return null;
        }
        
        $settings = $facet['settings'] ?? [];
        $values = $settings['values'] ?? [];
        
        return [
            'name' => $facet['facet_name'],
            'label' => $facet['facet_label'],
            'type' => $facet['facet_type'],
            'source' => $facet['facet_source'],
            'is_enabled' => (bool) $facet['is_enabled'],
            'values' => $values,
            'original' => $settings['original'] ?? []
        ];
    }
    
    /**
     * Enable a facet.
     *
     * @param string $name The facet name.
     * @return bool
     */
    public function enable_facet($name) {
        $facet = $this->db_manager->get_facet($name);
        
        if (!$facet) {
            return false;
        }
        
        return $this->db_manager->save_facet(
            $facet['facet_name'],
            $facet['facet_label'],
            $facet['facet_type'],
            $facet['facet_source'],
            true,
            $facet['settings']
        );
    }
    
    /**
     * Disable a facet.
     *
     * @param string $name The facet name.
     * @return bool
     */
    public function disable_facet($name) {
        $facet = $this->db_manager->get_facet($name);
        
        if (!$facet) {
            return false;
        }
        
        return $this->db_manager->save_facet(
            $facet['facet_name'],
            $facet['facet_label'],
            $facet['facet_type'],
            $facet['facet_source'],
            false,
            $facet['settings']
        );
    }
    
    /**
     * Get URL parameter for a facet.
     *
     * @param string $facet_name The facet name.
     * @return string
     */
    public function get_facet_url_param($facet_name) {
        // FacetWP uses the facet name as the URL parameter
        return $facet_name;
    }
    
    /**
     * Build a URL parameter structure for a facet value
     *
     * @param array $facet The facet configuration.
     * @param string $value The facet value.
     * @return string The URL parameter string
     */
    public function build_facet_url_parameter($facet, $value) {
        if (!isset($facet['name'])) {
            return '';
        }
        
        $name = $facet['name'];
        
        // Handle different facet types differently if needed
        switch ($facet['type']) {
            case 'search':
                // Search facets use the facet name as the parameter
                return "{$name}={$value}";
                
            case 'slider':
            case 'number_range':
                // Range facets may use min/max format with commas
                if (strpos($value, ',') !== false) {
                    list($min, $max) = explode(',', $value);
                    return "{$name}={$min}%2C{$max}"; // URL encoded comma
                }
                return "{$name}={$value}";
                
            case 'date_range':
                // Date range facets
                return "{$name}={$value}";
                
            case 'hierarchy':
                // Hierarchical facets
                $levels = explode('/', $value);
                if (count($levels) > 1) {
                    // Multi-level hierarchy
                    $params = [];
                    foreach ($levels as $index => $level) {
                        $params[] = "{$name}_level_{$index}={$level}";
                    }
                    return implode('&', $params);
                }
                return "{$name}={$value}";
                
            default:
                // Default parameter format
                return "{$name}={$value}";
        }
    }
}