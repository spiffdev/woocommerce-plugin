<?php
   /*
   Plugin Name: Spiff Connect
   Plugin URI: http://spiff3d.com
   Description: Connect your WooCommerce store to Spiff and allow customers access to Spiff Workflows to personalize their products.
   Version: 2.1
   Author: Spiff Pty. Ltd.
   License: GPL3
   */

define("SPIFF_API_BASE", "api.spiff.com.au");
define("SPIFF_API_ORDERS_PATH", "/api/v2/orders");
define("SPIFF_API_ORDERS_URL", "https://" . SPIFF_API_BASE . SPIFF_API_ORDERS_PATH);

/*
 * Create admin menu.
 */
add_action('admin_menu', 'spiff_create_admin_menu');
function spiff_create_admin_menu() {
    add_menu_page('Spiff Connect', 'Spiff Connect', 'administrator', 'spiff-connect', 'spiff_admin_menu_html');
    add_action('admin_init', 'spiff_register_admin_settings');
}
function spiff_register_admin_settings() {
    register_setting('spiff-settings-group', 'spiff_api_key');
    register_setting('spiff-settings-group', 'spiff_api_secret');
    register_setting('spiff-settings-group', 'spiff_show_customer_selections_in_cart');
}
function spiff_admin_menu_html() {

?>

<div class="wrap">
    <h1>Spiff Connect</h1>

    <form autocomplete="off" method="post" action="options.php">
        <h2>Integration Details</h1>
        <p>Enter your integration's access key and secret here.</p>
        <p>Your integration's key and secret may be found on your integration's page in the Spiff Hub.</p>

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

        <h2>Settings</h2>

        <table class="form-table">
            <tr valign="top">
            <th scope="row">Show customer selections in cart</th>
            <td><input type="checkbox" name="spiff_show_customer_selections_in_cart" value="1" <?php echo checked("1", get_option('spiff_show_customer_selections_in_cart')); ?> /></td>
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

    $enabled = isset($_POST['spiff_enabled']) && rest_sanitize_boolean($_POST['spiff_enabled']);
    $product->update_meta_data('spiff_enabled', $enabled ? 'yes' : 'no');

    $integration_product_id = sanitize_text_field($_POST['spiff_integration_product_id']);
    $product->update_meta_data('spiff_integration_product_id', $integration_product_id);

    $product->save();
}

/**
 * Enqueue Javascript.
 */
add_action('wp_enqueue_scripts', 'spiff_enqueue_ecommerce_client');
function spiff_enqueue_ecommerce_client() {
    wp_enqueue_script('spiff-ecommerce-client', plugin_dir_url(__FILE__) . 'public/js/api.js');
    wp_enqueue_script('spiff-create-design-button', plugin_dir_url(__FILE__) . 'public/js/create-design-button.js');
    wp_localize_script('spiff-create-design-button', 'ajax_object', array('ajax_url' => admin_url( 'admin-ajax.php' )));
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
        $new_button = $xml->asXML();
        return $new_button;
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
        add_action('woocommerce_single_product_summary', 'spiff_append_create_design_button_on_product_page', 35);
    }
}
function spiff_append_create_design_button_on_product_page() {
    global $product;
    $woo_product_id = esc_js($product->get_id());
    $integration_product_id_js = esc_js($product->get_meta('spiff_integration_product_id'));
    $integration_product_id_attr = esc_attr($product->get_meta('spiff_integration_product_id'));
    $currency_code = esc_js(get_woocommerce_currency());
    $cart_url = esc_js(wc_get_cart_url());
?>

<div class="spiff-button-integration-product-<?php echo $integration_product_id_attr; ?>"></div>
<script>
  window.spiffAppendCreateDesignButton(
    "<?php echo $woo_product_id; ?>",
    "<?php echo $integration_product_id_js; ?>",
    "<?php echo $currency_code; ?>",
    "<?php echo $cart_url; ?>",
  )
</script>

<?php

}

/**
 * Expose ability to add cart items from Javascript.
 */
add_action('wp_ajax_spiff_create_cart_item', 'spiff_create_cart_item');
add_action('wp_ajax_nopriv_spiff_create_cart_item', 'spiff_create_cart_item');
function spiff_create_cart_item() {
    global $woocommerce;
    if (isset($_POST['spiff_create_cart_item_details'])) {
        $details = json_decode(stripslashes_deep($_POST['spiff_create_cart_item_details']));
        $price_in_subunits = $details->price;
        if ($price_in_subunits < 0) {
            error_log('Spiff Connect received negative price when completing design.');
            wp_die();
        }
        $transaction_id = sanitize_text_field($details->transactionId);

        $woo_product_id = sanitize_text_field($details->wooProductId);
        $metadata = array();
        $exportedData = array();
        foreach ($details->exportedData as $key => $value) {
            $exportedData[sanitize_text_field($key)] = sanitize_text_field($value->value);
        }
        $metadata['spiff_exported_data'] = $exportedData;
        $metadata['spiff_transaction_id'] = $transaction_id;
        $metadata['spiff_item_price'] = floatval($price_in_subunits / ( 10 ** wc_get_price_decimals()));

        $cart_item_key = $woocommerce->cart->add_to_cart($woo_product_id, 1, '', '', $metadata);
    }
    wp_die();
}

/**
 * Update cart item price to reflect option costs.
 */
add_action('woocommerce_before_calculate_totals', 'spiff_handle_cart_item_price', 20, 1);
function spiff_handle_cart_item_price($cart) {
    foreach ($cart->get_cart() as $item) {
        if(isset($item['spiff_item_price'])) {
            $item['data']->set_price($item['spiff_item_price']);
        }
    }
}

/**
 * Display metadata in the cart.
 */
if (get_option('spiff_show_customer_selections_in_cart')) {
    add_filter('woocommerce_get_item_data', 'spiff_show_metadata_in_cart', 10, 2);
}
function spiff_show_metadata_in_cart($cart_data, $cart_item) {
    $custom_items = array();
    if (!empty($cart_data)) {
        $custom_items = $cart_data;
    }
    if ($cart_item['spiff_exported_data']) {
        foreach ($cart_item['spiff_exported_data'] as $key => $value) {
            $custom_items[] = array('name' => esc_html($key), 'value' => esc_html($value));
        }
    }
    return $custom_items;
}

/**
 * Add cart item transaction ID to order item.
 */
add_action('woocommerce_checkout_create_order_line_item','spiff_add_cart_item_attributes_to_order_item', 10, 4);
function spiff_add_cart_item_attributes_to_order_item($item, $cart_item_key, $values, $order) {
    if (isset($values['spiff_transaction_id'])) {
        $item->update_meta_data('spiff_transaction_id', $values['spiff_transaction_id']);
    }
}

/**
 * Create a Spiff order
 */
add_action('woocommerce_order_status_processing', 'spiff_create_order');
function spiff_create_order($order_id) {
    $order = wc_get_order($order_id);
    $order_items = $order->get_items();

    $items = array();
    foreach($order_items as $key => $order_item) {
        $transaction_id = $order_item->get_meta('spiff_transaction_id');
        if (!$transaction_id) {
            continue;
        }
        $item = array();
        $item['amountToOrder'] = $order_item['qty'];
        $item['transactionId'] = $transaction_id;
        array_push($items, $item);
    }

    if (!empty($items)) {
        spiff_post_order($items, $order->get_id());
    }
}
function spiff_hex_to_base64($hex) {
    $return = "";
    foreach (str_split($hex, 2) as $pair) {
        $return .= chr(hexdec($pair));
    }
    return base64_encode($return);
}
function spiff_order_post_headers($method, $path, $content_type, $body) {
    $ACCESS_KEY = get_option('spiff_api_key');
    $SECRET_KEY = get_option('spiff_api_secret');

    $date = new DateTime("now", new DateTimeZone("GMT"));
    $dateString = $date->format("D, d M Y H:i:s") . " GMT";
    $md5 = md5($body, false);
    $string_to_sign = $method . "\n" . $md5 . "\n" . $content_type . "\n" . $dateString . "\n" . $path;
    $signature = spiff_hex_to_base64(hash_hmac("sha1", $string_to_sign, $SECRET_KEY));

    return array(
        'Authorization' => 'SOA '  . $ACCESS_KEY . ':' . $signature,
        'Content-Type' => $content_type,
        'Date' => $dateString,
    );
}
function spiff_post_order($items, $woo_order_id) {
    $body = json_encode(array(
        'externalId' => $woo_order_id,
        'autoPrint' => false,
        'orderItems' => $items
    ));
    $headers = spiff_order_post_headers('POST', SPIFF_API_ORDERS_PATH, 'application/json', $body);
    $response = wp_remote_post(SPIFF_API_ORDERS_URL, array(
        'body' => $body,
        'headers' => $headers,
    ));
    $response_status = wp_remote_retrieve_response_code($response);
    if ($response_status !== 200) {
        error_log(wp_remote_retrieve_body($response));
    }
}
