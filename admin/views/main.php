<?php
/**
 * Main admin page view.
 *
 * @package    PreloadAssist
 * @subpackage PreloadAssist/admin/views
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$facets = $this->facet_manager->get_facets_with_values();
$categories = $this->category_detector->get_hierarchical_categories();
$parameters = $this->db_manager->get_enabled_parameters();
$files = $this->file_manager->get_files();
$directory_info = $this->file_manager->get_directory_info();
$tables = $this->db_manager->get_tables();
?>

<div class="wrap preload-assist-wrap">
    <h1><?php esc_html_e( 'Preload Assist', 'preload-assist' ); ?></h1>
    
    <!-- Ensure jQuery UI CSS is available in the page -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    
    <!-- Fallback styles in case jQuery UI doesn't initialize -->
    <style>
        /* Ensure tabs still display content even if jQuery UI fails to load */
        .ui-tabs-panel, #tabs-1, #tabs-2, #tabs-3, #tabs-4, #tabs-5 {
            display: block !important;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            background: #fff;
        }
        
        /* Style the tab navigation */
        .preload-assist-tabs > ul, .categories-tabs > ul {
            display: flex;
            margin: 0 0 -1px 0;
            padding: 0;
            list-style: none;
        }
        
        .preload-assist-tabs > ul > li, .categories-tabs > ul > li {
            margin: 0 2px 0 0;
        }
        
        .preload-assist-tabs > ul > li > a, .categories-tabs > ul > li > a {
            display: block;
            padding: 10px 15px;
            border: 1px solid #ddd;
            background: #f5f5f5;
            text-decoration: none;
            border-bottom: 0;
        }
    </style>
    
    <div class="preload-assist-notice notice notice-info" style="display: none;"></div>
    
    <div class="preload-assist-container">
        <div id="preload-assist-tabs" class="preload-assist-tabs">
            <ul>
                <li><a href="#tabs-1"><?php esc_html_e( 'FacetWP Facets', 'preload-assist' ); ?></a></li>
                <li><a href="#tabs-2"><?php esc_html_e( 'Custom Parameters', 'preload-assist' ); ?></a></li>
                <li><a href="#tabs-3"><?php esc_html_e( 'Product Categories', 'preload-assist' ); ?></a></li>
                <li><a href="#tabs-4"><?php esc_html_e( 'URL Generation', 'preload-assist' ); ?></a></li>
                <li><a href="#tabs-5"><?php esc_html_e( 'System', 'preload-assist' ); ?></a></li>
            </ul>
            
            <!-- FacetWP Facets Tab -->
            <div id="tabs-1" class="preload-assist-tab-content">
                <h2><?php esc_html_e( 'FacetWP Facets', 'preload-assist' ); ?></h2>
                <p><?php esc_html_e( 'Import your FacetWP facets and their values, then enable or disable them for URL generation.', 'preload-assist' ); ?></p>
                
                <div class="facet-import-section">
                    <h3><?php esc_html_e( 'Import FacetWP Facets', 'preload-assist' ); ?></h3>
                    <p><?php esc_html_e( 'Paste your FacetWP export JSON here. You can get this by going to FacetWP → Settings → Backup, then clicking "Export facets".', 'preload-assist' ); ?></p>
                    
                    <textarea id="facetwp-import-json" class="large-text code" rows="10" placeholder='{"facets":[{"name":"product_type","label":"Product Type","type":"fselect",...}]}'></textarea>
                    
                    <p class="submit">
                        <button type="button" class="button button-primary import-facets">
                            <?php esc_html_e( 'Import Facets', 'preload-assist' ); ?>
                        </button>
                    </p>
                </div>
                
                <h3><?php esc_html_e( 'Imported Facets', 'preload-assist' ); ?></h3>
                <div class="preload-assist-table-container">
                    <table class="wp-list-table widefat fixed striped facets-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Enabled', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Name', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Label', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Source', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Values', 'preload-assist' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $facets ) ) : ?>
                                <tr>
                                    <td colspan="6"><?php esc_html_e( 'No facets found. Import your FacetWP facets using the form above.', 'preload-assist' ); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $facets as $facet ) : ?>
                                    <tr data-facet="<?php echo esc_attr( $facet['name'] ); ?>">
                                        <td>
                                            <input type="checkbox" class="toggle-facet" 
                                                   data-facet="<?php echo esc_attr( $facet['name'] ); ?>" 
                                                   <?php checked( isset( $facet['is_enabled'] ) && $facet['is_enabled'] ); ?>>
                                        </td>
                                        <td><?php echo esc_html( $facet['name'] ); ?></td>
                                        <td><?php echo esc_html( $facet['label'] ); ?></td>
                                        <td><?php echo esc_html( $facet['type'] ); ?></td>
                                        <td><?php echo esc_html( $facet['source'] ); ?></td>
                                        <td>
                                            <?php if ( isset( $facet['values'] ) && is_array( $facet['values'] ) ) : ?>
                                                <button type="button" class="button-link toggle-values">
                                                    <?php 
                                                    /* translators: %d: number of values */
                                                    echo esc_html( sprintf( __( 'Show %d values', 'preload-assist' ), count( $facet['values'] ) ) ); 
                                                    ?>
                                                </button>
                                                <div class="facet-values" style="display: none;">
                                                    <?php foreach ( $facet['values'] as $value ) : ?>
                                                        <span class="facet-value"><?php echo esc_html( $value ); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else : ?>
                                                <?php esc_html_e( 'No values found', 'preload-assist' ); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Custom Parameters Tab -->
            <div id="tabs-2" class="preload-assist-tab-content">
                <h2><?php esc_html_e( 'Custom Parameters', 'preload-assist' ); ?></h2>
                <p><?php esc_html_e( 'Add custom URL parameters for URL generation.', 'preload-assist' ); ?></p>
                
                <div class="parameter-form">
                    <h3><?php esc_html_e( 'Add New Parameter', 'preload-assist' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Parameter Name', 'preload-assist' ); ?></th>
                            <td><input type="text" id="param-name" class="regular-text" placeholder="e.g., color"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Parameter Values', 'preload-assist' ); ?></th>
                            <td>
                                <div class="param-values-container">
                                    <input type="text" class="param-value regular-text" placeholder="e.g., red">
                                    <button type="button" class="button add-param-value"><?php esc_html_e( 'Add Value', 'preload-assist' ); ?></button>
                                </div>
                                <div class="param-values-list"></div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Position', 'preload-assist' ); ?></th>
                            <td>
                                <label>
                                    <input type="radio" name="param-position" id="param-position-after" value="after" checked>
                                    <?php esc_html_e( 'After FacetWP Parameters', 'preload-assist' ); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="param-position" id="param-position-before" value="before">
                                    <?php esc_html_e( 'Before FacetWP Parameters', 'preload-assist' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'Controls whether this parameter appears before or after FacetWP parameters in generated URLs.', 'preload-assist' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enabled', 'preload-assist' ); ?></th>
                            <td><input type="checkbox" id="param-enabled" checked></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" class="button button-primary save-parameter"><?php esc_html_e( 'Add Parameter', 'preload-assist' ); ?></button>
                    </p>
                </div>
                
                <h3><?php esc_html_e( 'Existing Parameters', 'preload-assist' ); ?></h3>
                <div class="preload-assist-table-container">
                    <table class="wp-list-table widefat fixed striped parameters-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Enabled', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Name', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Values', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Position', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'preload-assist' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $parameters ) ) : ?>
                                <tr>
                                    <td colspan="5"><?php esc_html_e( 'No parameters found. Add a custom parameter above.', 'preload-assist' ); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $parameters as $param ) : ?>
                                    <tr data-param="<?php echo esc_attr( $param['param_name'] ); ?>">
                                        <td>
                                            <input type="checkbox" class="toggle-parameter" 
                                                   data-param="<?php echo esc_attr( $param['param_name'] ); ?>" 
                                                   <?php checked( isset( $param['is_enabled'] ) && $param['is_enabled'] ); ?>>
                                        </td>
                                        <td><?php echo esc_html( $param['param_name'] ); ?></td>
                                        <td>
                                            <?php if ( isset( $param['param_values'] ) && is_array( $param['param_values'] ) ) : ?>
                                                <button type="button" class="button-link toggle-values">
                                                    <?php 
                                                    /* translators: %d: number of values */
                                                    echo esc_html( sprintf( __( 'Show %d values', 'preload-assist' ), count( $param['param_values'] ) ) ); 
                                                    ?>
                                                </button>
                                                <div class="param-values" style="display: none;">
                                                    <?php foreach ( $param['param_values'] as $value ) : ?>
                                                        <span class="param-value"><?php echo esc_html( $value ); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else : ?>
                                                <?php esc_html_e( 'No values found', 'preload-assist' ); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select class="parameter-position" data-param="<?php echo esc_attr( $param['param_name'] ); ?>">
                                                <option value="after" <?php selected( !isset($param['position']) || $param['position'] !== 'before' ); ?>>
                                                    <?php esc_html_e( 'After FacetWP Parameters', 'preload-assist' ); ?>
                                                </option>
                                                <option value="before" <?php selected( isset($param['position']) && $param['position'] === 'before' ); ?>>
                                                    <?php esc_html_e( 'Before FacetWP Parameters', 'preload-assist' ); ?>
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="button delete-parameter" 
                                                    data-param="<?php echo esc_attr( $param['param_name'] ); ?>">
                                                <?php esc_html_e( 'Delete', 'preload-assist' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Product Categories Tab -->
            <div id="tabs-3" class="preload-assist-tab-content">
                <h2><?php esc_html_e( 'Product Categories', 'preload-assist' ); ?></h2>
                <p><?php esc_html_e( 'Select WooCommerce product categories for URL generation.', 'preload-assist' ); ?></p>
                
                <button type="button" class="button sync-categories">
                    <?php esc_html_e( 'Sync Categories', 'preload-assist' ); ?>
                </button>
                
                <?php if ( empty( $categories ) ) : ?>
                    <div class="notice notice-info">
                        <p><?php esc_html_e( 'No categories found. Click "Sync Categories" to detect WooCommerce categories.', 'preload-assist' ); ?></p>
                    </div>
                <?php else : ?>
                    <!-- Category Tabs -->
                    <div id="categories-tabs" class="categories-tabs">
                        <ul>
                            <?php foreach ( $categories as $index => $category ) : ?>
                                <li>
                                    <a href="#category-tab-<?php echo esc_attr( $category['id'] ); ?>">
                                        <?php echo esc_html( $category['name'] ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php foreach ( $categories as $category ) : ?>
                            <div id="category-tab-<?php echo esc_attr( $category['id'] ); ?>" class="category-tab-content">
                                <div class="category-header">
                                    <h3><?php echo esc_html( $category['name'] ); ?></h3>
                                    <div class="category-info">
                                        <span class="category-slug">
                                            <strong><?php esc_html_e( 'Slug:', 'preload-assist' ); ?></strong> 
                                            <?php echo esc_html( $category['slug'] ); ?>
                                        </span>
                                        <span class="category-products">
                                            <strong><?php esc_html_e( 'Products:', 'preload-assist' ); ?></strong> 
                                            <?php echo esc_html( $category['count'] ); ?>
                                        </span>
                                        <a href="<?php echo esc_url( $category['url'] ); ?>" target="_blank" class="category-url">
                                            <?php esc_html_e( 'View Category', 'preload-assist' ); ?>
                                        </a>
                                    </div>
                                    
                                    <div class="category-enabled-toggle">
                                        <label>
                                            <input type="checkbox" class="toggle-category" 
                                                   data-category="<?php echo esc_attr( $category['id'] ); ?>" 
                                                   <?php checked( isset( $category['is_enabled'] ) && $category['is_enabled'] ); ?>>
                                            <?php esc_html_e( 'Enable this category', 'preload-assist' ); ?>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="category-settings-container" <?php echo (!isset($category['is_enabled']) || !$category['is_enabled']) ? 'style="display:none;"' : ''; ?>>
                                    <div class="category-settings-content">
                                        <div class="category-section">
                                            <h4><?php esc_html_e( 'FacetWP Facets', 'preload-assist' ); ?></h4>
                                            <div class="category-facets-header">
                                                <div class="bulk-toggle">
                                                    <label>
                                                        <input type="checkbox" class="toggle-all-facets" data-category="<?php echo esc_attr( $category['id'] ); ?>">
                                                        <?php esc_html_e( 'Select All', 'preload-assist' ); ?>
                                                    </label>
                                                </div>
                                                <div class="search-facets">
                                                    <input type="text" class="search-facets-input" data-category="<?php echo esc_attr( $category['id'] ); ?>" placeholder="<?php esc_attr_e( 'Search facets...', 'preload-assist' ); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="category-facets">
                                                <?php foreach ( $facets as $facet ) : ?>
                                                    <?php 
                                                    $is_selected = false;
                                                    if (isset($category['settings']) && isset($category['settings']['facets']) && is_array($category['settings']['facets'])) {
                                                        $is_selected = in_array($facet['name'], $category['settings']['facets']);
                                                    }
                                                    ?>
                                                    <div class="category-facet" data-facet-name="<?php echo esc_attr( $facet['name'] ); ?>" data-facet-label="<?php echo esc_attr( $facet['label'] ); ?>">
                                                        <label>
                                                            <input type="checkbox" name="category_facets_<?php echo esc_attr( $category['id'] ); ?>[]" 
                                                                   value="<?php echo esc_attr( $facet['name'] ); ?>"
                                                                   <?php checked( $is_selected ); ?>
                                                                   <?php disabled( !isset( $facet['is_enabled'] ) || !$facet['is_enabled'] ); ?>>
                                                            <span class="facet-details">
                                                                <strong><?php echo esc_html( $facet['label'] ); ?></strong>
                                                                <small class="facet-extra">
                                                                    (<?php echo esc_html( $facet['name'] ); ?>) - 
                                                                    <?php echo esc_html( $facet['type'] ); ?>
                                                                    <?php if ( !empty( $facet['values'] ) ) : ?>
                                                                        - <?php echo esc_html( count( $facet['values'] ) ); ?> <?php esc_html_e( 'values', 'preload-assist' ); ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </span>
                                                            <?php if ( !isset( $facet['is_enabled'] ) || !$facet['is_enabled'] ) : ?>
                                                                <span class="disabled-item"><?php esc_html_e( '(disabled globally)', 'preload-assist' ); ?></span>
                                                            <?php endif; ?>
                                                        </label>
                                                        <?php if ( !empty( $facet['values'] ) ) : ?>
                                                            <a href="#" class="toggle-facet-values"><?php esc_html_e( 'Select values', 'preload-assist' ); ?></a>
                                                            <div class="facet-values-container" style="display: none;">
                                                                <div class="facet-values-header">
                                                                    <label>
                                                                        <input type="checkbox" class="toggle-all-facet-values" 
                                                                               data-category="<?php echo esc_attr( $category['id'] ); ?>" 
                                                                               data-facet="<?php echo esc_attr( $facet['name'] ); ?>">
                                                                        <?php esc_html_e( 'Select All Values', 'preload-assist' ); ?>
                                                                    </label>
                                                                    <div class="search-facet-values">
                                                                        <input type="text" class="search-facet-values-input" 
                                                                               data-category="<?php echo esc_attr( $category['id'] ); ?>" 
                                                                               data-facet="<?php echo esc_attr( $facet['name'] ); ?>" 
                                                                               placeholder="<?php esc_attr_e( 'Search values...', 'preload-assist' ); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="facet-values-list">
                                                                    <?php 
                                                                    // Get any previously selected values for this facet and category
                                                                    $selected_values = [];
                                                                    $facet_values_with_status = $db_manager->get_facet_values_with_status($category['id'], $facet['name']);
                                                                    
                                                                    // If no saved selection, default to all selected
                                                                    $have_saved_selections = !empty($facet_values_with_status);
                                                                    
                                                                    foreach ( $facet['values'] as $value ) : 
                                                                        // Default to selected if there's no saved data
                                                                        $is_value_selected = $have_saved_selections ? 
                                                                            (isset($facet_values_with_status[$value]) ? $facet_values_with_status[$value] : true) : 
                                                                            true;
                                                                    ?>
                                                                        <div class="facet-value-item">
                                                                            <label>
                                                                                <input type="checkbox" 
                                                                                       name="facet_values_<?php echo esc_attr( $category['id'] ); ?>_<?php echo esc_attr( $facet['name'] ); ?>[]" 
                                                                                       value="<?php echo esc_attr( $value ); ?>"
                                                                                       class="facet-value-checkbox"
                                                                                       data-category="<?php echo esc_attr( $category['id'] ); ?>"
                                                                                       data-facet="<?php echo esc_attr( $facet['name'] ); ?>"
                                                                                       <?php checked( $is_value_selected ); ?>>
                                                                                <span class="facet-value-text"><?php echo esc_html( $value ); ?></span>
                                                                            </label>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="category-section">
                                            <h4><?php esc_html_e( 'Custom URL Parameters', 'preload-assist' ); ?></h4>
                                            <div class="category-params-header">
                                                <div class="bulk-toggle">
                                                    <label>
                                                        <input type="checkbox" class="toggle-all-params" data-category="<?php echo esc_attr( $category['id'] ); ?>">
                                                        <?php esc_html_e( 'Select All', 'preload-assist' ); ?>
                                                    </label>
                                                </div>
                                                <div class="search-params">
                                                    <input type="text" class="search-params-input" data-category="<?php echo esc_attr( $category['id'] ); ?>" placeholder="<?php esc_attr_e( 'Search parameters...', 'preload-assist' ); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="category-parameters">
                                                <?php foreach ( $parameters as $param ) : ?>
                                                    <?php 
                                                    $is_selected = false;
                                                    if (isset($category['settings']) && isset($category['settings']['parameters']) && is_array($category['settings']['parameters'])) {
                                                        $is_selected = in_array($param['param_name'], $category['settings']['parameters']);
                                                    }
                                                    ?>
                                                    <div class="category-parameter" data-param-name="<?php echo esc_attr( $param['param_name'] ); ?>">
                                                        <label>
                                                            <input type="checkbox" name="category_parameters_<?php echo esc_attr( $category['id'] ); ?>[]" 
                                                                   value="<?php echo esc_attr( $param['param_name'] ); ?>"
                                                                   <?php checked( $is_selected ); ?>
                                                                   <?php disabled( !isset( $param['is_enabled'] ) || !$param['is_enabled'] ); ?>>
                                                            <span class="param-details">
                                                                <strong><?php echo esc_html( $param['param_name'] ); ?></strong>
                                                                <?php if ( !empty( $param['param_values'] ) ) : ?>
                                                                    <small class="param-extra">
                                                                        - <?php echo esc_html( count( $param['param_values'] ) ); ?> <?php esc_html_e( 'values', 'preload-assist' ); ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </span>
                                                            <?php if ( !isset( $param['is_enabled'] ) || !$param['is_enabled'] ) : ?>
                                                                <span class="disabled-item"><?php esc_html_e( '(disabled globally)', 'preload-assist' ); ?></span>
                                                            <?php endif; ?>
                                                        </label>
                                                        <?php if ( !empty( $param['param_values'] ) ) : ?>
                                                            <a href="#" class="toggle-param-values"><?php esc_html_e( 'Show values', 'preload-assist' ); ?></a>
                                                            <div class="param-values-preview" style="display: none;">
                                                                <?php foreach ( array_slice( $param['param_values'], 0, 10 ) as $value ) : ?>
                                                                    <span class="param-value"><?php echo esc_html( $value ); ?></span>
                                                                <?php endforeach; ?>
                                                                <?php if ( count( $param['param_values'] ) > 10 ) : ?>
                                                                    <span class="more-values">
                                                                        <?php 
                                                                        /* translators: %d: remaining number of values */
                                                                        echo esc_html( sprintf( __( '...and %d more', 'preload-assist' ), count( $param['param_values'] ) - 10 ) ); 
                                                                        ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="submit-container">
                                            <button type="button" class="button button-primary save-category-settings" data-category="<?php echo esc_attr( $category['id'] ); ?>">
                                                <?php esc_html_e( 'Save Category Settings', 'preload-assist' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- URL Generation Tab -->
            <div id="tabs-4" class="preload-assist-tab-content">
                <h2><?php esc_html_e( 'URL Generation', 'preload-assist' ); ?></h2>
                <p><?php esc_html_e( 'Generate URL permutations based on selected options.', 'preload-assist' ); ?></p>
                
                <div class="generation-form">
                    <h3><?php esc_html_e( 'Generation Options', 'preload-assist' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Max URLs', 'preload-assist' ); ?></th>
                            <td>
                                <input type="number" id="max-urls" class="regular-text" value="10000" min="1">
                                <p class="description"><?php esc_html_e( 'Maximum number of URLs to generate.', 'preload-assist' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Include Empty URLs', 'preload-assist' ); ?></th>
                            <td>
                                <input type="checkbox" id="include-empty">
                                <p class="description"><?php esc_html_e( 'Include base URLs without any parameters.', 'preload-assist' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Categories', 'preload-assist' ); ?></th>
                            <td>
                                <select id="categories" multiple class="regular-text">
                                    <option value=""><?php esc_html_e( 'All enabled categories', 'preload-assist' ); ?></option>
                                    <?php foreach ( $categories as $category ) : ?>
                                        <?php if ( isset( $category['is_enabled'] ) && $category['is_enabled'] ) : ?>
                                            <option value="<?php echo esc_attr( $category['id'] ); ?>">
                                                <?php echo esc_html( $category['name'] ); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e( 'Select categories to include, or leave empty for all enabled categories.', 'preload-assist' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Facets', 'preload-assist' ); ?></th>
                            <td>
                                <select id="facets" multiple class="regular-text">
                                    <option value=""><?php esc_html_e( 'All enabled facets', 'preload-assist' ); ?></option>
                                    <?php foreach ( $facets as $facet ) : ?>
                                        <?php if ( isset( $facet['is_enabled'] ) && $facet['is_enabled'] ) : ?>
                                            <option value="<?php echo esc_attr( $facet['name'] ); ?>">
                                                <?php echo esc_html( $facet['label'] ); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e( 'Select facets to include, or leave empty for all enabled facets.', 'preload-assist' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Parameters', 'preload-assist' ); ?></th>
                            <td>
                                <select id="parameters" multiple class="regular-text">
                                    <option value=""><?php esc_html_e( 'All enabled parameters', 'preload-assist' ); ?></option>
                                    <?php foreach ( $parameters as $param ) : ?>
                                        <?php if ( isset( $param['is_enabled'] ) && $param['is_enabled'] ) : ?>
                                            <option value="<?php echo esc_attr( $param['param_name'] ); ?>">
                                                <?php echo esc_html( $param['param_name'] ); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e( 'Select parameters to include, or leave empty for all enabled parameters.', 'preload-assist' ); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" class="button button-primary generate-urls"><?php esc_html_e( 'Generate URLs', 'preload-assist' ); ?></button>
                    </p>
                </div>
                
                <h3><?php esc_html_e( 'Generated URL Files', 'preload-assist' ); ?></h3>
                <div class="preload-assist-table-container">
                    <table class="wp-list-table widefat fixed striped files-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Selected', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Filename', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Size', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'URLs', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Generated', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'preload-assist' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $files ) ) : ?>
                                <tr>
                                    <td colspan="6"><?php esc_html_e( 'No URL files found. Generate URLs using the form above.', 'preload-assist' ); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $files as $file ) : ?>
                                    <tr data-file="<?php echo esc_attr( $file['id'] ); ?>">
                                        <td>
                                            <input type="radio" name="selected_file" class="select-file" 
                                                   data-file="<?php echo esc_attr( $file['id'] ); ?>" 
                                                   <?php checked( isset( $file['is_selected'] ) && $file['is_selected'] ); ?>>
                                        </td>
                                        <td><?php echo esc_html( $file['file_name'] ); ?></td>
                                        <td><?php echo esc_html( $this->file_manager->format_file_size( $file['file_size'] ) ); ?></td>
                                        <td><?php echo esc_html( $file['url_count'] ); ?></td>
                                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $file['generated_at'] ) ) ); ?></td>
                                        <td>
                                            <button type="button" class="button preview-file" 
                                                    data-file="<?php echo esc_attr( $file['id'] ); ?>">
                                                <?php esc_html_e( 'Preview', 'preload-assist' ); ?>
                                            </button>
                                            <button type="button" class="button export-file" 
                                                    data-file="<?php echo esc_attr( $file['id'] ); ?>">
                                                <?php esc_html_e( 'Export', 'preload-assist' ); ?>
                                            </button>
                                            <button type="button" class="button delete-file" 
                                                    data-file="<?php echo esc_attr( $file['id'] ); ?>">
                                                <?php esc_html_e( 'Delete', 'preload-assist' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="file-actions">
                    <h3><?php esc_html_e( 'FlyingPress Integration', 'preload-assist' ); ?></h3>
                    <p>
                        <label>
                            <input type="checkbox" id="enable-flyingpress-integration" 
                                   <?php checked( $this->db_manager->get_setting( 'flyingpress_integration_enabled', false ) ); ?>>
                            <?php esc_html_e( 'Enable FlyingPress integration', 'preload-assist' ); ?>
                        </label>
                    </p>
                    <div class="flyingpress-integration-options" <?php echo ! $this->db_manager->get_setting( 'flyingpress_integration_enabled', false ) ? 'style="display:none;"' : ''; ?>>
                        <p><?php esc_html_e( 'Select a file above and click the button below to integrate with FlyingPress.', 'preload-assist' ); ?></p>
                        <p>
                            <button type="button" class="button button-primary trigger-preload">
                                <?php esc_html_e( 'Trigger FlyingPress Preload', 'preload-assist' ); ?>
                            </button>
                        </p>
                        <?php if ( ! class_exists( 'FlyingPress' ) && ! function_exists( 'flying_press_preload_urls' ) ) : ?>
                            <div class="notice notice-warning inline">
                                <p><?php esc_html_e( 'FlyingPress is not active. Please install and activate FlyingPress to use this feature.', 'preload-assist' ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3><?php esc_html_e( 'File Management', 'preload-assist' ); ?></h3>
                    <p><?php esc_html_e( 'Clean up old files to save disk space.', 'preload-assist' ); ?></p>
                    <p>
                        <button type="button" class="button cleanup-files">
                            <?php esc_html_e( 'Clean Up Files', 'preload-assist' ); ?>
                        </button>
                        <label for="keep-count"><?php esc_html_e( 'Keep', 'preload-assist' ); ?></label>
                        <input type="number" id="keep-count" value="5" min="1" max="50" step="1">
                        <?php esc_html_e( 'most recent files', 'preload-assist' ); ?>
                    </p>
                </div>
                
                <!-- File Preview Modal -->
                <div id="file-preview-modal" title="<?php esc_attr_e( 'URL Preview', 'preload-assist' ); ?>" style="display: none;">
                    <div class="file-preview-content">
                        <div class="file-info"></div>
                        <div class="file-urls"></div>
                        <div class="pagination">
                            <button type="button" class="button prev-page"><?php esc_html_e( 'Previous', 'preload-assist' ); ?></button>
                            <span class="page-info"></span>
                            <button type="button" class="button next-page"><?php esc_html_e( 'Next', 'preload-assist' ); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Tab -->
            <div id="tabs-5" class="preload-assist-tab-content">
                <h2><?php esc_html_e( 'System Information', 'preload-assist' ); ?></h2>
                <p><?php esc_html_e( 'Technical information about the plugin.', 'preload-assist' ); ?></p>
                
                <h3><?php esc_html_e( 'Database Tables', 'preload-assist' ); ?></h3>
                <div class="preload-assist-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Table Name', 'preload-assist' ); ?></th>
                                <th><?php esc_html_e( 'Description', 'preload-assist' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $tables as $key => $table ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $table ); ?></td>
                                    <td>
                                        <?php
                                        switch ( $key ) {
                                            case 'settings':
                                                esc_html_e( 'Plugin settings and configuration', 'preload-assist' );
                                                break;
                                            case 'categories':
                                                esc_html_e( 'Product category settings', 'preload-assist' );
                                                break;
                                            case 'facets':
                                                esc_html_e( 'FacetWP facet settings', 'preload-assist' );
                                                break;
                                            case 'parameters':
                                                esc_html_e( 'Custom URL parameter settings', 'preload-assist' );
                                                break;
                                            case 'files':
                                                esc_html_e( 'Generated URL file information', 'preload-assist' );
                                                break;
                                            default:
                                                esc_html_e( 'Unknown', 'preload-assist' );
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <h3><?php esc_html_e( 'File Storage', 'preload-assist' ); ?></h3>
                <div class="preload-assist-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <tbody>
                            <tr>
                                <th><?php esc_html_e( 'Storage Directory', 'preload-assist' ); ?></th>
                                <td><?php echo esc_html( $directory_info['directory'] ); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'File Count', 'preload-assist' ); ?></th>
                                <td><?php echo esc_html( $directory_info['file_count'] ); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Total Size', 'preload-assist' ); ?></th>
                                <td><?php echo esc_html( $directory_info['formatted_size'] ); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h3><?php esc_html_e( 'Plugin Information', 'preload-assist' ); ?></h3>
                <div class="preload-assist-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <tbody>
                            <tr>
                                <th><?php esc_html_e( 'Version', 'preload-assist' ); ?></th>
                                <td><?php echo esc_html( PRELOAD_ASSIST_VERSION ); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'WooCommerce', 'preload-assist' ); ?></th>
                                <td>
                                    <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                        <span class="dashicons dashicons-yes" style="color: green;"></span>
                                        <?php 
                                        if ( defined( 'WC_VERSION' ) ) {
                                            echo esc_html( WC_VERSION );
                                        }
                                        ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-no" style="color: red;"></span>
                                        <?php esc_html_e( 'Not active', 'preload-assist' ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'FacetWP', 'preload-assist' ); ?></th>
                                <td>
                                    <?php if ( class_exists( 'FacetWP' ) ) : ?>
                                        <span class="dashicons dashicons-yes" style="color: green;"></span>
                                        <?php 
                                        if ( defined( 'FACETWP_VERSION' ) ) {
                                            echo esc_html( FACETWP_VERSION );
                                        }
                                        ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-no" style="color: red;"></span>
                                        <?php esc_html_e( 'Not active', 'preload-assist' ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'FlyingPress', 'preload-assist' ); ?></th>
                                <td>
                                    <?php if ( class_exists( 'FlyingPress' ) || function_exists( 'flying_press_preload_urls' ) ) : ?>
                                        <span class="dashicons dashicons-yes" style="color: green;"></span>
                                        <?php 
                                        if ( defined( 'FLYING_PRESS_VERSION' ) ) {
                                            echo esc_html( FLYING_PRESS_VERSION );
                                        }
                                        ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-no" style="color: red;"></span>
                                        <?php esc_html_e( 'Not active', 'preload-assist' ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h3><?php esc_html_e( 'Data Management', 'preload-assist' ); ?></h3>
                <div class="data-management">
                    <p><?php esc_html_e( 'Delete all plugin data, including database tables and files.', 'preload-assist' ); ?></p>
                    <p class="warning"><?php esc_html_e( 'WARNING: This action cannot be undone. All plugin data will be permanently deleted.', 'preload-assist' ); ?></p>
                    <p>
                        <button type="button" class="button button-danger delete-all-data">
                            <?php esc_html_e( 'Delete All Plugin Data', 'preload-assist' ); ?>
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>