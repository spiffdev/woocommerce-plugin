<?php
/*
Plugin Name: Spiff Connect
Plugin URI: http://spiff3d.com
Description: Connect your WooCommerce store to Spiff and allow customers access to Spiff Workflows to personalize their products.
Author: Spiff Pty. Ltd.
License: GPL3
*/

require plugin_dir_path(__FILE__) . 'includes/spiff-connect-requests.php';

define("SPIFF_API_BASE", getenv("SPIFF_API_BASE")); // Legacy AU
define("SPIFF_API_AP_BASE", getenv("SPIFF_API_AP_BASE"));
define("SPIFF_API_US_BASE", getenv("SPIFF_API_US_BASE"));
define("SPIFF_API_INSTALLS_PATH", "/api/installs");
define("SPIFF_API_ORDERS_PATH", "/api/v2/orders");
define("SPIFF_API_TRANSACTIONS_PATH", "/api/transactions");
define("SPIFF_GRAPHQL_PATH", "/graphql");

// Get base API URL based on infrastructure choice.
function spiff_get_base_api_url() {
    if (get_option('spiff_infrastructure') === "AP") {
        return SPIFF_API_AP_BASE;
    }
    if (get_option('spiff_infrastructure') === "US") {
        return SPIFF_API_US_BASE;
    }
    return SPIFF_API_BASE;
}

/**
 * Activation hook.
 */

register_activation_hook(__FILE__, 'spiff_activation_hook');

function spiff_activation_hook() {
    $was_activated = get_option('spiff_plugin_was_activated');
    if ($was_activated) {
      return; // Only trigger notifcation on the first activation.
    }
    add_option('spiff_plugin_was_activated', '1');

    $shop_name = get_home_url();
    $admins = get_users(array('role' => 'Administrator'));
    if (count($admins) > 0) {
      $admin = $admins[0];
      $admin_id = $admin->ID;
      $email = $admin->data->user_email;
      $phone = get_user_meta($admin_id, 'billing_phone', true);
      $first_name = get_user_meta($admin_id, 'first_name', true);
      $last_name = get_user_meta($admin_id, 'last_name', true);

      $body = json_encode(array(
          'type' => 'WooCommerce',
          'shopName' => $shop_name,
          'owner' => "$first_name $last_name",
          'email' => $email,
          'phone' => $phone
      ));
      $headers = array(
        'Content-Type' => 'application/json',
      );
      // This is expected to always be Australia because the value hasn't been set yet.
      wp_remote_post(spiff_get_base_api_url() . SPIFF_API_INSTALLS_PATH, array(
        'body' => $body,
        'headers' => $headers
      ));
    }
}

/*
 * Create admin menu.
 */

add_action('admin_menu', 'spiff_create_admin_menu');

function spiff_create_admin_menu() {
    add_menu_page('Spiff Connect', 'Spiff Connect', 'administrator', 'spiff-connect', 'spiff_admin_menu_html');
    add_action('admin_init', 'spiff_register_admin_settings');
}

// Create all the global settings for the plugin.
function spiff_register_admin_settings() {
    register_setting('spiff-settings-group', 'spiff_api_key');
    register_setting('spiff-settings-group', 'spiff_api_secret');
    register_setting('spiff-settings-group', 'spiff_application_key');
    register_setting('spiff-settings-group', 'spiff_infrastructure');

    register_setting('spiff-settings-group', 'spiff_show_customer_selections_in_cart');
    register_setting('spiff-settings-group', 'spiff_show_preview_images_in_cart');

    register_setting('spiff-settings-group', 'spiff_non_bulk_text'); // Personalize button text setting has legacy name.
    register_setting('spiff-settings-group', 'spiff_font_size');
    register_setting('spiff-settings-group', 'spiff_font_weight');
    register_setting('spiff-settings-group', 'spiff_text_color');
    register_setting('spiff-settings-group', 'spiff_background_color');
    register_setting('spiff-settings-group', 'spiff_width');
    register_setting('spiff-settings-group', 'spiff_height');

    register_setting('spiff-settings-group', 'spiff_customer_portal_button_text');
    register_setting('spiff-settings-group', 'spiff_customer_portal_button_font_size');
    register_setting('spiff-settings-group', 'spiff_customer_portal_button_font_weight');
    register_setting('spiff-settings-group', 'spiff_customer_portal_button_text_color');
    register_setting('spiff-settings-group', 'spiff_customer_portal_button_background_color');
    register_setting('spiff-settings-group', 'spiff_customer_portal_button_width');
    register_setting('spiff-settings-group', 'spiff_customer_portal_button_height');
}

// Render the HTML for the global settings page.
function spiff_admin_menu_html() {
?>

<div style="background-color: #fff;border-left: 4px solid #da1c5c;padding: 45px 20px 20px 30px;position: relative;overflow: hidden; max-width: 1200px; margin-top: 20px;">
    <style>
        input::placeholder {
            opacity: 0.5;
            color: #3399ff;
            font-style: italic;
        }
    </style>
    <img style="width:200px;" src="<?php echo plugins_url("assets/spiff_logo.png",__FILE__) ; ?>">
    <form autocomplete="off" method="post" action="options.php">
        <?php settings_fields('spiff-settings-group'); ?>
        <?php do_settings_sections('spiff-settings-group'); ?>

        <h2 style="font-size: 24px;line-height: 29px;position: relative;">Integration Details</h2>
        <p style="font-size: 16px;margin-bottom: 30px;position: relative;">Your integration's key and secret may be found on your integration's page in the Spiff Hub.</p>
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
        <p style="font-size: 16px;margin-bottom: 30px;position: relative;">If using the customer portal feature, you'll need to create an application key on your integration's page in the Spiff Hub.</p>
        <table class="form-table">
            <tr valign="top">
            <th scope="row">Application Key</th>
            <td><input autocomplete=off type="text" name="spiff_application_key" value="<?php echo esc_attr(get_option('spiff_application_key')); ?>" /></td>
            </tr>
        </table>
        <p style="font-size: 16px;margin-bottom: 30px;position: relative;">Ensure that you are using the Spiff infrastructure that your account was created against.</p>
        <table class="form-table">
            <tr valign="top">
            <th scope="row">Infrastructure</th>
            <td><select name="spiff_infrastructure">
                <option value="AP" <?php echo selected("AP", get_option("spiff_infrastructure"), false); ?>>Australia</option>
                <option value="AU" <?php echo selected("AU", get_option("spiff_infrastructure") ?? "AU", false); ?>>Australia (Legacy)</option>
                <option value="US" <?php echo selected("US", get_option("spiff_infrastructure"), false); ?>>United States</option>
            </select></td>
            </tr>
        </table>

        <h2 style="font-size: 24px;line-height: 29px;position: relative;">Cart</h2>
        <table class="form-table">
            <tr valign="top">
            <th scope="row">Show customer selections</th>
            <td><input type="checkbox" name="spiff_show_customer_selections_in_cart" value="1" <?php echo checked("1", get_option('spiff_show_customer_selections_in_cart')); ?> /></td>
            </tr>
            <tr valign="top">
            <th scope="row">Show preview images</th>
            <td><input type="checkbox" name="spiff_show_preview_images_in_cart" value="1" <?php echo checked("1", get_option('spiff_show_preview_images_in_cart')); ?> /></td>
            </tr>
        </table>

        <h2 style="font-size: 24px;line-height: 29px;position: relative;">Personalize Button</h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Text</th>
                <td><input autocomplete=off type="text" placeholder="Personalize" name="spiff_non_bulk_text" value="<?php echo esc_attr(get_option('spiff_non_bulk_text') ?: "Personalize"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Font Size</th>
                <td><input autocomplete=off placeholder="20px" type="text" name="spiff_font_size" value="<?php echo esc_attr(get_option('spiff_font_size') ?: "20px"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Font Weight</th>
                <td><input autocomplete=off placeholder="700" type="text" name="spiff_font_weight" value="<?php echo esc_attr(get_option('spiff_font_weight') ?: "700"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Text Color</th>
                <td><input autocomplete=off placeholder="#fff" type="text" name="spiff_text_color" value="<?php echo esc_attr(get_option('spiff_text_color') ?: "#fff"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Background Color</th>
                <td><input autocomplete=off placeholder="#da1c5c" type="text" name="spiff_background_color" value="<?php echo esc_attr(get_option('spiff_background_color') ?: "#da1c5c"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Width</th>
                <td><input autocomplete=off placeholder="100%" type="text" name="spiff_width" value="<?php echo esc_attr(get_option('spiff_width') ?: "100%"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Height</th>
                <td><input autocomplete=off placeholder="50px" type="text" name="spiff_height" value="<?php echo esc_attr(get_option('spiff_height') ?: "50px"); ?>" /></td>
            </tr>
        </table>

        <h2 style="font-size: 24px;line-height: 29px;position: relative;">Customer Portal Button</h2>
        <p style="font-size: 16px;margin-bottom: 30px;position: relative;">The customer portal button can be added to pages using the shortcode [spiff_customer_portal_button].</p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Text</th>
                <td><input autocomplete=off type="text" placeholder="Customer Portal" name="spiff_customer_portal_button_text" value="<?php echo esc_attr(get_option('spiff_customer_portal_button_text') ?: "Customer Portal"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Font Size</th>
                <td><input autocomplete=off placeholder="20px" type="text" name="spiff_customer_portal_button_font_size" value="<?php echo esc_attr(get_option('spiff_customer_portal_button_font_size') ?: "20px"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Font Weight</th>
                <td><input autocomplete=off placeholder="700" type="text" name="spiff_customer_portal_button_font_weight" value="<?php echo esc_attr(get_option('spiff_customer_portal_button_font_weight') ?: "700"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Text Color</th>
                <td><input autocomplete=off placeholder="#fff" type="text" name="spiff_customer_portal_button_text_color" value="<?php echo esc_attr(get_option('spiff_customer_portal_button_text_color') ?: "#fff"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Background Color</th>
                <td><input autocomplete=off placeholder="#da1c5c" type="text" name="spiff_customer_portal_button_background_color" value="<?php echo esc_attr(get_option('spiff_customer_portal_button_background_color') ?: "#da1c5c"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Width</th>
                <td><input autocomplete=off placeholder="100%" type="text" name="spiff_customer_portal_button_width" value="<?php echo esc_attr(get_option('spiff_customer_portal_button_width') ?: "100%"); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Height</th>
                <td><input autocomplete=off placeholder="50px" type="text" name="spiff_customer_portal_button_height" value="<?php echo esc_attr(get_option('spiff_customer_portal_button_height') ?: "50px"); ?>" /></td>
            </tr>
        </table>

        <p style="display:inline-block;">
            <button class="button button-primary" style="background-color: #2271b1; text-shadow: none; text-decoration: none; border-color: #2271b1; color: #fff" type="submit">
                Save Changes
            </button>
        </p>  
        <p style="display:inline-block;">
            <a class="button button-primary" style="margin-left: 10px; background-color: #da1c5c; text-shadow: none; text-decoration: none; border-color: #da1c5c; color: #fff" href="https://hub.spiff.com.au/#/login" target="_blank">
                Spiff Hub
            </a>
        </p>
    </form>
</div>
<?php
}

/**
 * Add fields to admin product pages.
 */

add_action('woocommerce_product_options_general_product_data', 'spiff_create_admin_product_fields');

// Display plugin-specific fields on product page.
function spiff_create_admin_product_fields() {
    woocommerce_wp_checkbox( array(
      'id' => 'spiff_enabled',
      'label' => 'Enable Personalize Button',
    ));

    woocommerce_wp_text_input(array(
      'desc_tip' => true,
      'description' => 'To get this ID, find this product on your integration page in the Spiff Hub.',
      'id' => 'spiff_integration_product_id',
      'label' => 'Spiff Integration Product ID',
    ));
}

add_action('woocommerce_process_product_meta', 'spiff_save_admin_product_fields');

// Handle saving of plugin-specific fields when product page is saved.
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
    wp_enqueue_script(
        'spiff-ecommerce-client',
        plugin_dir_url(__FILE__) . 'public/js/api.js',
        array(),
        null
    );

    wp_enqueue_script(
        'spiff-create-design-button',
        plugin_dir_url(__FILE__) . 'public/js/create-design-button.js',
        array(),
        null
    );
    wp_localize_script('spiff-create-design-button', 'ajax_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'region_code' => esc_js(strtolower(get_option('spiff_infrastructure') ?? "AU"))
    ));
}

/**
 * Replace add to cart buttons on product list pages.
 */

add_filter('woocommerce_loop_add_to_cart_link', 'spiff_replace_default_button_on_product_list', 10, 2);

function spiff_replace_default_button_on_product_list($button, $product) {
    // Don't replace default add to cart button unless Spiff is enabled for that product.
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
    // Don't replace default add to cart button unless Spiff is enabled for that product.
    if ($product->get_meta('spiff_enabled') === 'yes') {
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        add_action('woocommerce_single_product_summary', 'spiff_append_create_design_button_on_product_page', 35);
    }
}

// Append script element to page that creates the new button.
function spiff_append_create_design_button_on_product_page() {
    global $product;
    $woo_product_id = esc_js($product->get_id());
    $integration_product_id_js = esc_js($product->get_meta('spiff_integration_product_id'));
    $integration_product_id_attr = esc_attr($product->get_meta('spiff_integration_product_id'));
    $currency_code = esc_js(get_woocommerce_currency());
    $cart_url = esc_js(wc_get_cart_url());
    $button_config = json_encode(array(
        'personalizeButtonText' =>  esc_attr(get_option('spiff_non_bulk_text') ?: "Personalize"),
        'size' => esc_attr(get_option('spiff_font_size') ?: "20px"),
        'weight' => esc_attr(get_option('spiff_font_weight') ?: "700"),
        'textColor' => esc_attr(get_option('spiff_text_color') ?: "#fff"),
        'backgroundColor' => esc_attr(get_option('spiff_background_color') ?: "#da1c5c"),
        'width' => esc_attr(get_option('spiff_width') ?: "100%"),
        'height' => esc_attr(get_option('spiff_height') ?: "50px")
    ));
    $application_key = esc_js(get_option('spiff_application_key'));

    if ($product->get_meta('spiff_enabled') === 'yes') {
        ?>
            <div class="spiff-button-integration-product-<?php echo $integration_product_id_attr; ?>"></div>
            <script>
            window.spiffAppendCreateDesignButton(
                "<?php echo $woo_product_id; ?>",
                "<?php echo $integration_product_id_js; ?>",
                "<?php echo $currency_code; ?>",
                "<?php echo $cart_url; ?>",
                <?php echo $button_config; ?>,
                "<?php echo $application_key ?>"
            )
            </script>
        <?php
    }
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

        // Marshall the data received from the Javascript post into a new cart item.
        // Everything except the product ID is used to add metadata to the cart item.

        $metadata = array();

        $transaction_id = sanitize_text_field($details->transactionId);

        $exportedData = array();
        foreach ($details->exportedData as $key => $value) {
            $exportedData[sanitize_text_field($key)] = sanitize_text_field($value->value);
        }

        $metadata['spiff_exported_data'] = $exportedData;
        $metadata['spiff_transaction_id'] = $transaction_id;

        $transaction = spiff_get_transaction($transaction_id);
        if (!$transaction) {
            error_log('Failed to retrieve transaction ' . $transaction_id);
        } else {
            $woo_product_id = sanitize_text_field($details->wooProductId ?? spiff_get_woo_id_from_transaction($transaction));
            $price_in_subunits = $transaction->product->basePrice + $transaction->priceModifierTotal;
            $metadata['spiff_item_price'] = floatval($price_in_subunits / ( 10 ** wc_get_price_decimals()));
            $woocommerce->cart->add_to_cart($woo_product_id, 1, '', '', $metadata);
        }
    }
    wp_die();
}

// Get the data associated with a transaction.
function spiff_get_transaction($transaction_id) {
    $url = spiff_get_base_api_url() . SPIFF_GRAPHQL_PATH;
    $access_key = get_option('spiff_api_key');
    $secret_key = get_option('spiff_api_secret');
    $body = json_encode(array(
        'operationName' => 'GetTransaction',
        'query' => "query GetTransaction { transactions(ids: [\"$transaction_id\"]) { priceModifierTotal, product { basePrice, integrationProducts { id } } } }",
    ));
    $headers = spiff_request_headers($access_key, $secret_key, $body, SPIFF_GRAPHQL_PATH);
    $response = wp_remote_post($url, array(
        'body' => $body,
        'headers' => $headers,
    ));
    $response_status = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $decoded = json_decode($response_body);
    return $decoded->data->transactions[0];
}

function spiff_get_woo_id_from_transaction($transaction) {
    $products = wc_get_products(array('limit' => -1));
    foreach ($products as $product) {
        foreach ($transaction->product->integrationProducts as $integration_product) {
            if($integration_product->id === (esc_js($product->get_meta('spiff_integration_product_id')))) {
                return $product->get_id();
            }
        }
    }
    error_log('Failed to find product for integration product ID ' . $integration_product_id);
    return null;
}

/**
 * Update cart item price to reflect option costs.
 */

add_action('woocommerce_before_calculate_totals', 'spiff_handle_cart_item_price', 20, 1);

function spiff_handle_cart_item_price($cart) {
    foreach ($cart->get_cart() as $item) {
        if (isset($item['spiff_item_price'])) {
            $item['data']->set_price($item['spiff_item_price']);
        }
    }
}

/**
 * Display metadata in the cart.
 */

if (get_option('spiff_show_customer_selections_in_cart')) {
    // Exported data won't show in cart unless the relevant setting is turned on.
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
 * Display preview image in cart.
 */

if (get_option('spiff_show_preview_images_in_cart')) {
    add_filter('woocommerce_cart_item_thumbnail', 'spiff_show_preview_image_in_cart', 10, 3);
}

function spiff_get_transaction_image($transaction_id) {
    $url = spiff_get_base_api_url() . SPIFF_API_TRANSACTIONS_PATH . '/' . $transaction_id . '/image';
    $response = wp_remote_get($url, array('redirection' => 0));
    $response_location_header = wp_remote_retrieve_header($response, 'location');
    if ($response_location_header === '') {
      return null;
    }
    return '<img src="' . $response_location_header . '" alt="preview" />';
}

function spiff_show_preview_image_in_cart($product_image, $cart_item, $cart_item_key) {
    if (!array_key_exists('spiff_transaction_id', $cart_item)) {
      return $product_image;
    }
    $preview_image = spiff_get_transaction_image($cart_item['spiff_transaction_id']);
    if ($preview_image === null) {
      return $product_image;
    }
    return $preview_image;
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
 * Create a Spiff order when a WooCommerce order is placed.
 */

add_action('woocommerce_order_status_processing', 'spiff_create_order');

function spiff_create_order($order_id) {
    $order = wc_get_order($order_id);
    $order_items = $order->get_items();

    // Convert each order item into an item for the create order request.
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

    // Post the order.
    if (!empty($items)) {
        $access_key = get_option('spiff_api_key');
        $secret_key = get_option('spiff_api_secret');
        spiff_post_order($access_key, $secret_key, $items, $order->get_id(), $order->is_paid());
    }
}

// Craft the request to the Spiff orders endpoint.
function spiff_post_order($access_key, $secret_key, $items, $woo_order_id, $paid) {
    $body = json_encode(array(
        'externalId' => $woo_order_id,
        'paid' => $paid,
        'orderItems' => $items
    ));
    $headers = spiff_request_headers($access_key, $secret_key, $body, SPIFF_API_ORDERS_PATH);
    $response = wp_remote_post(spiff_get_base_api_url() . SPIFF_API_ORDERS_PATH, array(
        'body' => $body,
        'headers' => $headers,
    ));
    $response_status = wp_remote_retrieve_response_code($response);
    if ($response_status !== 201) {
        error_log('Response status: ' . $response_status);
    }
}

/**
 * Shortcodes.
 */

function spiff_customer_portal_button_shortcode_handler($atts) {
    ob_start();
    ?>
        <button
            class="spiff-customer-portal-button"
            onclick="window.spiffLaunchCustomerPortal('<?php echo esc_attr(get_option('spiff_application_key')) ?>', '<?php echo esc_js(wc_get_cart_url()) ?>')"
            style="font-size: <?php echo esc_attr(get_option('spiff_customer_portal_button_font_size') ?: "20px") ?>; background: <?php echo esc_attr(get_option('spiff_customer_portal_button_background_color') ?: "#da1c5c") ?>; color: <?php echo esc_attr(get_option('spiff_customer_portal_button_text_color') ?: "#fff") ?>; font-weight: <?php echo esc_attr(get_option('spiff_customer_portal_button_font_weight') ?: "700") ?>; width: <?php echo esc_attr(get_option('spiff_customer_portal_button_width') ?: "100%") ?>; height: <?php echo esc_attr(get_option('spiff_customer_portal_button_height') ?: "50px") ?>; cursor: pointer; border: none;"
        >
            <?php echo esc_attr(get_option('spiff_customer_portal_button_text') ?: "Customer Portal") ?>
        </button>
    <?php
    return ob_get_clean();
}
add_shortcode("spiff_customer_portal_button", "spiff_customer_portal_button_shortcode_handler");
