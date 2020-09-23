<?php
   /*
   Plugin Name: Spiff Connect
   Plugin URI: http://spiff3d.com
   Description: Connect your WooCommerce store to Spiff and allow customers access to Spiff Workflows to personalize their products.
   Version: 2.1
   Author: Spiff Pty. Ltd.
   License: GPL3
   */

//define("SPIFF_API_BASE", "api.spiff.com.au");
define("SPIFF_API_BASE", "api.app.dev.spiff.com.au");
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

    $enabled = isset($_POST['spiff_enabled']) && rest_sanitize_boolean($_POST['spiff_enabled']);
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
    wp_enqueue_script('spiff-create-design-button', plugin_dir_url(__FILE__) . 'public/js/create-design-button.js');
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
    $integration_product_id_js = esc_js($product->get_meta('spiff_integration_product_id'));
    $integration_product_id_attr = esc_attr($product->get_meta('spiff_integration_product_id'));
    $currency_code = esc_js(get_woocommerce_currency());
    $add_to_cart_url = esc_js(add_query_arg(
      array("add-to-cart" => $product->get_id()),
      wc_get_cart_url()
    ));
?>

<div class="spiff-button-integration-product-<?php echo $integration_product_id_attr; ?>"></div>
<script>
  window.spiffAppendCreateDesignButton(
    "<?php echo $integration_product_id_js; ?>",
    "<?php echo $currency_code; ?>",
    "<?php echo $add_to_cart_url; ?>",
  )
</script>

<?php

}

/**
 * Save transaction IDs to cart items.
 */
add_filter('woocommerce_add_cart_item_data', 'spiff_save_cart_item', 10, 2);
function spiff_save_cart_item($cart_item_data, $product_id) {
    if (isset($_GET['spiff-transaction-id'])) {
        $transaction_id = sanitize_text_field($_GET['spiff-transaction-id']);
        $cart_item_data['spiff_transaction_id'] = $transaction_id;
    }
    return $cart_item_data;
}

/**
 * Display transaction ID in the cart.
 */
//add_filter('woocommerce_get_item_data', 'spiff_show_transaction_id_in_cart', 10, 2);
function spiff_show_transaction_id_in_cart($cart_data, $cart_item = null) {
    $custom_items = array();
    if(!empty($cart_data)) {
        $custom_items = $cart_data;
    }
    $transaction_id = esc_html($cart_item['spiff_transaction_id']);
    if($transaction_id) {
        $custom_items[] = array("name" => 'Transaction ID', "value" => $transaction_id);
    }
    return $custom_items;
}

/**
 * Add cart item transaction ID to order item.
 */
add_action('woocommerce_add_order_item_meta','spiff_add_cart_item_attributes_to_order_item', 10, 3 );
function spiff_add_cart_item_attributes_to_order_item($item_id, $cart_item, $order_id) {
    if (isset($cart_item['spiff_transaction_id'])) {
        wc_add_order_item_meta($item_id, __('Spiff Transaction ID'), $cart_item['spiff_transaction_id'], true);
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
        $transactionId = wc_get_order_item_meta($key, __('Spiff Transaction ID'));
        if (!$transactionId) {
          continue;
        }
        $item = array();
        $item['amountToOrder'] = $order_item['qty'];
        $item['transactionId'] = $transactionId;
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
