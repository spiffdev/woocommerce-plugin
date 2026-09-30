<?php

/**
 * Minimal WordPress and WooCommerce stand-ins so the plugin can be loaded and exercised outside of WordPress.
 */

define('SPIFF_TEST_AP_BASE', 'https://ap.spiff.test');
define('SPIFF_TEST_US_BASE', 'https://us.spiff.test');
putenv('SPIFF_API_AP_BASE=' . SPIFF_TEST_AP_BASE);
putenv('SPIFF_API_US_BASE=' . SPIFF_TEST_US_BASE);

$GLOBALS['spiff_test_filters'] = array();

function spiff_test_reset() {
    $GLOBALS['spiff_test_options'] = array();
    $GLOBALS['spiff_test_requests'] = array();
    $GLOBALS['spiff_test_responses'] = array();
    $GLOBALS['spiff_test_orders'] = array();
    $GLOBALS['spiff_test_products'] = array();
    $GLOBALS['spiff_test_users'] = array();
    $GLOBALS['spiff_test_user_meta'] = array();
    $GLOBALS['spiff_test_menu_pages'] = array();
    $GLOBALS['spiff_test_settings'] = array();
    $GLOBALS['spiff_test_product_fields'] = array();
    $GLOBALS['spiff_test_scripts'] = array();
    $GLOBALS['spiff_test_localized_scripts'] = array();
    $GLOBALS['spiff_test_wp_die_calls'] = 0;
    $GLOBALS['woocommerce'] = (object) array('cart' => new Spiff_Test_Cart());
    $GLOBALS['product'] = null;
    $_POST = array();
    // Restore the hooks the plugin registered when it was loaded, dropping any added by tests.
    $GLOBALS['spiff_test_filters'] = $GLOBALS['spiff_test_initial_filters'];
}

// Queue a response for the next wp_remote_post call.
function spiff_test_queue_response($data, $code = 200) {
    $GLOBALS['spiff_test_responses'][] = array(
        'response' => array('code' => $code),
        'body' => json_encode($data),
    );
}

function spiff_test_has_action($tag, $callback, $priority = 10) {
    foreach ($GLOBALS['spiff_test_filters'][$tag][$priority] ?? array() as $registered) {
        if ($registered[0] === $callback) {
            return true;
        }
    }
    return false;
}

// Hooks.

function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['spiff_test_filters'][$tag][$priority][] = array($callback, $accepted_args);
    return true;
}

function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
    return add_filter($tag, $callback, $priority, $accepted_args);
}

function remove_action($tag, $callback, $priority = 10) {
    foreach ($GLOBALS['spiff_test_filters'][$tag][$priority] ?? array() as $index => $registered) {
        if ($registered[0] === $callback) {
            unset($GLOBALS['spiff_test_filters'][$tag][$priority][$index]);
            return true;
        }
    }
    return false;
}

function apply_filters($tag, $value, ...$args) {
    if (empty($GLOBALS['spiff_test_filters'][$tag])) {
        return $value;
    }
    $callbacks = $GLOBALS['spiff_test_filters'][$tag];
    ksort($callbacks);
    foreach ($callbacks as $priority_callbacks) {
        foreach ($priority_callbacks as list($callback, $accepted_args)) {
            $value = call_user_func_array($callback, array_slice(array_merge(array($value), $args), 0, $accepted_args));
        }
    }
    return $value;
}

function register_activation_hook($file, $callback) {}
function add_shortcode($tag, $callback) {}

// Options and admin.

function get_option($name, $default = false) {
    return array_key_exists($name, $GLOBALS['spiff_test_options']) ? $GLOBALS['spiff_test_options'][$name] : $default;
}

function add_option($name, $value) {
    if (array_key_exists($name, $GLOBALS['spiff_test_options'])) {
        return false;
    }
    $GLOBALS['spiff_test_options'][$name] = $value;
    return true;
}

function register_setting($group, $name) {
    $GLOBALS['spiff_test_settings'][$group][] = $name;
}

function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback) {
    $GLOBALS['spiff_test_menu_pages'][] = compact('page_title', 'menu_title', 'capability', 'menu_slug', 'callback');
}

function get_home_url() {
    return 'https://shop.test';
}

function get_users($args) {
    return $GLOBALS['spiff_test_users'];
}

function get_user_meta($user_id, $key, $single = false) {
    return $GLOBALS['spiff_test_user_meta'][$user_id][$key] ?? '';
}

// URLs and scripts.

function plugin_dir_path($file) {
    return dirname($file) . '/';
}

function plugin_dir_url($file) {
    return 'https://shop.test/wp-content/plugins/spiff-connect/';
}

function admin_url($path = '') {
    return 'https://shop.test/wp-admin/' . $path;
}

function wp_enqueue_script($handle, $src, $deps = array(), $ver = false) {
    $GLOBALS['spiff_test_scripts'][$handle] = $src;
}

function wp_localize_script($handle, $name, $data) {
    $GLOBALS['spiff_test_localized_scripts'][$handle][$name] = $data;
}

// Sanitising and escaping. Simplified versions of the WordPress functions.

function sanitize_text_field($value) {
    return trim(strip_tags((string) $value));
}

function rest_sanitize_boolean($value) {
    return is_string($value) && strtolower($value) === 'false' ? false : (bool) $value;
}

function stripslashes_deep($value) {
    return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
}

function esc_attr($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES);
}

function esc_html($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES);
}

function esc_js($value) {
    return addslashes(htmlspecialchars((string) $value, ENT_COMPAT));
}

function esc_url($value) {
    return (string) $value;
}

function wp_die() {
    $GLOBALS['spiff_test_wp_die_calls']++;
}

// HTTP. Requests are recorded rather than sent, and answered from the response queue.

function wp_remote_post($url, $args) {
    $GLOBALS['spiff_test_requests'][] = array('url' => $url, 'args' => $args);
    if (!empty($GLOBALS['spiff_test_responses'])) {
        return array_shift($GLOBALS['spiff_test_responses']);
    }
    return array(
        'response' => array('code' => 200),
        'body' => json_encode(array('data' => array('orderCreate' => array('id' => 'spiff-order-id')))),
    );
}

function wp_remote_retrieve_response_code($response) {
    return $response['response']['code'];
}

function wp_remote_retrieve_body($response) {
    return $response['body'];
}

// WooCommerce.

function wc_get_order($order_id) {
    return $GLOBALS['spiff_test_orders'][$order_id] ?? false;
}

function wc_get_product($product_id) {
    return $GLOBALS['spiff_test_products'][$product_id] ?? false;
}

function wc_get_products($args) {
    return array_values($GLOBALS['spiff_test_products']);
}

function wc_get_cart_url() {
    return 'https://shop.test/cart/';
}

function wc_get_price_decimals() {
    return 2;
}

function get_woocommerce_currency() {
    return 'AUD';
}

function woocommerce_wp_checkbox($field) {
    $GLOBALS['spiff_test_product_fields'][] = array('type' => 'checkbox') + $field;
}

function woocommerce_wp_text_input($field) {
    $GLOBALS['spiff_test_product_fields'][] = array('type' => 'text') + $field;
}

class Spiff_Test_Product {
    public $meta;
    public $saved = false;
    public $price = null;
    private $id;

    public function __construct($id, $meta = array()) {
        $this->id = $id;
        $this->meta = $meta;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_meta($key) {
        return $this->meta[$key] ?? '';
    }

    public function update_meta_data($key, $value) {
        $this->meta[$key] = $value;
    }

    public function save() {
        $this->saved = true;
    }

    public function get_permalink() {
        return 'https://shop.test/product/' . $this->id . '/';
    }

    public function set_price($price) {
        $this->price = $price;
    }
}

class Spiff_Test_Cart {
    public $added = array();
    public $items = array();

    public function add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $this->added[] = compact('product_id', 'quantity', 'variation_id', 'variation', 'cart_item_data');
    }

    public function get_cart() {
        return $this->items;
    }
}

class Spiff_Test_Order_Item implements ArrayAccess {
    public $meta;
    private $quantity;

    public function __construct($meta, $quantity) {
        $this->meta = $meta;
        $this->quantity = $quantity;
    }

    public function get_meta($key) {
        return $this->meta[$key] ?? '';
    }

    public function update_meta_data($key, $value) {
        $this->meta[$key] = $value;
    }

    public function get_quantity() {
        return $this->quantity;
    }

    public function offsetExists($offset): bool {
        return $offset === 'qty';
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $offset === 'qty' ? $this->quantity : null;
    }

    public function offsetSet($offset, $value): void {}
    public function offsetUnset($offset): void {}
}

class Spiff_Test_Order {
    private $id;
    private $order_number;
    private $items;
    private $paid;
    private $fields;

    public function __construct($id, $order_number, $items, $paid = true, $fields = array()) {
        $this->id = $id;
        $this->order_number = $order_number;
        $this->items = $items;
        $this->paid = $paid;
        $this->fields = $fields;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_order_number() {
        return $this->order_number;
    }

    public function get_items() {
        return $this->items;
    }

    public function is_paid() {
        return $this->paid;
    }

    public function get_coupon_codes() {
        return $this->fields['coupon_codes'] ?? array();
    }

    // Remaining getters (addresses, email, phone, note) read from $fields, e.g. get_billing_city => 'billing_city'.
    public function __call($name, $arguments) {
        return $this->fields[substr($name, strlen('get_'))] ?? '';
    }
}

$GLOBALS['spiff_test_options'] = array();
require_once __DIR__ . '/../spiff-connect/spiff-connect.php';
$GLOBALS['spiff_test_initial_filters'] = $GLOBALS['spiff_test_filters'];
spiff_test_reset();
