<?php
   /*
   Plugin Name: Spiff Connect
   Plugin URI: http://spiff3d.com
   Description: Connect your WooCommerce store to Spiff and allow customers access to Spiff Workflows to personalize their products.
   Version: 2.1
   Author: Spiff Pty. Ltd.
   License: GPL3
   */

/*
 * Create admin menu.
 */
add_action('admin_menu', 'spiff_create_admin_menu');
function spiff_create_admin_menu() {
    add_menu_page('Spiff Connect', 'Spiff Connect', 'administrator', 'spiff-connect', 'spiff_admin_menu_html');
    add_action( 'admin_init', 'spiff_register_admin_settings' );
}
function spiff_register_admin_settings() {
    register_setting( 'spiff-settings-group', 'spiff_api_key' );
    register_setting( 'spiff-settings-group', 'spiff_api_secret' );
}
function spiff_admin_menu_html() {
?>
<div class="wrap">
    <h1>Spiff Connect</h1>
    <p>Enter your integration's access key and secret here.</p>
    <p>Your integration's key and secret may be found on your integration's page in the Spiff Hub.</p>

    <form autocomplete="off" method="post" action="options.php">
        <?php settings_fields('spiff-settings-group'); ?>
        <?php do_settings_sections('spiff-settings-group'); ?>
        <table class="form-table">
            <tr valign="top">
            <th scope="row">Access Key</th>
            <td><input autocomplete=off type="text" name="spiff_api_key" value="<?php echo esc_attr(get_option('spiff_api_key')); ?>" /></td>
            </tr>

            <tr valign="top">
            <th scope="row">Secret</th>
            <td><input autocomplete=off type="password" name="spiff_api_secret" value="<?php echo esc_attr(get_option('spiff_api_secret')); ?>" /></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
<?php
}

/**
 * Add fields to admin product pages.
 */
add_action('woocommerce_product_options_general_product_data', 'spiff_create_admin_product_fields');
function spiff_create_admin_product_fields() {
    woocommerce_wp_checkbox( array(
      'id' => 'spiff_enabled',
      'label' => 'Enable Spiff',
    ));
    woocommerce_wp_text_input(array(
      'desc_tip' => true,
      'description' => 'To get this ID, find this product on your integration page in the Spiff Hub.',
      'id' => 'spiff_integration_product_id',
      'label' => 'Spiff Integration Product ID',
    ));
}
add_action('woocommerce_process_product_meta', 'spiff_save_admin_product_fields');
function spiff_save_admin_product_fields($post_id) {
    $product = wc_get_product($post_id);

    $enabled = rest_sanitize_boolean($_POST['spiff_enabled']);
    $product->update_meta_data('spiff_enabled', $enabled ? 'yes' : 'no');

    $integration_product_id = sanitize_text_field($_POST['spiff_integration_product_id']);
    $product->update_meta_data('spiff_integration_product_id', $integration_product_id);

    $product->save();
}

/**
 * Enqueue ecommerce client script.
 */
add_action('wp_enqueue_scripts', 'spiff_enqueue_ecommerce_client');
function spiff_enqueue_ecommerce_client() {
    wp_enqueue_script('spiff-ecommerce-client', plugin_dir_url(__FILE__) . 'public/js/api.js');
}

/**
 * Replace add to cart buttons on product list pages.
 */
add_filter('woocommerce_loop_add_to_cart_link', 'spiff_replace_default_button_on_product_list', 10, 2);
function spiff_replace_default_button_on_product_list($button, $product) {
    if ($product->get_meta('spiff_enabled') === 'yes') {
        $decoded = html_entity_decode($button);
        $xml = simplexml_load_string($decoded);
        $xml->attributes()->class = str_replace('ajax_add_to_cart', '', $xml->attributes()->class);
        $xml->attributes()->href = $product->get_permalink();
        $xml->attributes()->{'aria-label'} = 'View details';
        $xml[0] = 'View details';
        $newButton = $xml->asXML();
        return $newButton;
    }
    return $button;
}

/**
 * Replace add to cart button on single product pages.
 */
add_action('woocommerce_single_product_summary', 'spiff_replace_default_element_on_product_page');
function spiff_replace_default_element_on_product_page() {
    global $product;
    if ($product->get_meta('spiff_enabled') === 'yes') {
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    }
}
