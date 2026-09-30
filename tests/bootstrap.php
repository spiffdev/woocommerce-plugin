<?php

/**
 * Minimal WordPress and WooCommerce stand-ins so the plugin can be loaded and exercised outside of WordPress.
 */

$GLOBALS['spiff_test_options'] = array();
$GLOBALS['spiff_test_filters'] = array();
$GLOBALS['spiff_test_requests'] = array();
$GLOBALS['spiff_test_orders'] = array();

function spiff_test_reset() {
    $GLOBALS['spiff_test_options'] = array();
    $GLOBALS['spiff_test_requests'] = array();
    $GLOBALS['spiff_test_orders'] = array();
    unset($GLOBALS['spiff_test_filters']['spiff_order_external_id']);
}

// Hooks.

function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['spiff_test_filters'][$tag][$priority][] = array($callback, $accepted_args);
    return true;
}

function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
    return add_filter($tag, $callback, $priority, $accepted_args);
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

// Options.

function get_option($name, $default = false) {
    return array_key_exists($name, $GLOBALS['spiff_test_options']) ? $GLOBALS['spiff_test_options'][$name] : $default;
}

function plugin_dir_path($file) {
    return dirname($file) . '/';
}

// HTTP. Requests are recorded rather than sent.

function wp_remote_post($url, $args) {
    $GLOBALS['spiff_test_requests'][] = array('url' => $url, 'args' => $args);
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

class Spiff_Test_Order_Item implements ArrayAccess {
    private $meta;
    private $quantity;

    public function __construct($meta, $quantity) {
        $this->meta = $meta;
        $this->quantity = $quantity;
    }

    public function get_meta($key) {
        return $this->meta[$key] ?? '';
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

    public function __construct($id, $order_number, $items, $paid = true) {
        $this->id = $id;
        $this->order_number = $order_number;
        $this->items = $items;
        $this->paid = $paid;
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
        return array();
    }

    // Remaining getters (addresses, email, phone, note) aren't under test, so return empty values.
    public function __call($name, $arguments) {
        return '';
    }
}

require_once __DIR__ . '/../spiff-connect/spiff-connect.php';
