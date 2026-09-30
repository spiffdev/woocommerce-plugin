<?php

use PHPUnit\Framework\TestCase;

final class CartTest extends TestCase {
    protected function setUp(): void {
        spiff_test_reset();
        $GLOBALS['spiff_test_options']['spiff_application_key'] = 'test-application-key';
    }

    private function queueTransaction() {
        spiff_test_queue_response(array('data' => array('transactions' => array(array(
            'priceModifierTotal' => 250,
            'product' => array(
                'basePrice' => 1000,
                'integrationProducts' => array(array('id' => 'integration-product-1')),
            ),
        )))));
    }

    private function postCartItemDetails($details) {
        // WordPress adds slashes to request data, which the plugin strips.
        $_POST['spiff_create_cart_item_details'] = addslashes(json_encode($details));
    }

    // Adding designs to the cart.

    public function testAddsDesignToCart() {
        $this->queueTransaction();
        $this->postCartItemDetails(array(
            'transactionId' => 'transaction-1',
            'wooProductId' => '42',
            'exportedData' => array('Name' => array('value' => 'Jamie')),
        ));

        spiff_create_cart_item();

        $this->assertSame(array(array(
            'product_id' => '42',
            'quantity' => 1,
            'variation_id' => '',
            'variation' => '',
            'cart_item_data' => array(
                'spiff_exported_data' => array('Name' => 'Jamie'),
                'spiff_transaction_id' => 'transaction-1',
                'spiff_item_price' => 12.5,
            ),
        )), $GLOBALS['woocommerce']->cart->added);
        $this->assertSame(1, $GLOBALS['spiff_test_wp_die_calls']);
    }

    public function testRequestsTransactionFromSpiff() {
        $this->queueTransaction();
        $this->postCartItemDetails(array(
            'transactionId' => 'transaction-1',
            'wooProductId' => '42',
            'exportedData' => array(),
        ));

        spiff_create_cart_item();

        $request = $GLOBALS['spiff_test_requests'][0];
        $this->assertSame(SPIFF_TEST_AP_BASE . '/graphql', $request['url']);
        $this->assertSame('test-application-key', $request['args']['headers']['X-Application-Key']);
        $this->assertStringContainsString('transaction-1', json_decode($request['args']['body'], true)['query']);
    }

    public function testFindsProductFromIntegrationProductWhenProductIdIsMissing() {
        $GLOBALS['spiff_test_products'] = array(
            41 => new Spiff_Test_Product(41, array('spiff_integration_product_id' => 'integration-product-other')),
            42 => new Spiff_Test_Product(42, array('spiff_integration_product_id' => 'integration-product-1')),
        );
        $this->queueTransaction();
        $this->postCartItemDetails(array(
            'transactionId' => 'transaction-1',
            'exportedData' => array(),
        ));

        spiff_create_cart_item();

        $this->assertSame('42', $GLOBALS['woocommerce']->cart->added[0]['product_id']);
    }

    public function testSanitisesCustomerSelections() {
        $this->queueTransaction();
        $this->postCartItemDetails(array(
            'transactionId' => 'transaction-1',
            'wooProductId' => '42',
            'exportedData' => array('<b>Name</b>' => array('value' => ' <i>Jamie</i> ')),
        ));

        spiff_create_cart_item();

        $this->assertSame(
            array('Name' => 'Jamie'),
            $GLOBALS['woocommerce']->cart->added[0]['cart_item_data']['spiff_exported_data']
        );
    }

    public function testDoesNothingWithoutCartItemDetails() {
        spiff_create_cart_item();

        $this->assertCount(0, $GLOBALS['spiff_test_requests']);
        $this->assertCount(0, $GLOBALS['woocommerce']->cart->added);
        $this->assertSame(1, $GLOBALS['spiff_test_wp_die_calls']);
    }

    // Cart prices.

    public function testSetsPriceOfSpiffItems() {
        $spiff_product = new Spiff_Test_Product(42);
        $other_product = new Spiff_Test_Product(43);
        $GLOBALS['woocommerce']->cart->items = array(
            array('data' => $spiff_product, 'spiff_item_price' => 12.5),
            array('data' => $other_product),
        );

        spiff_handle_cart_item_price($GLOBALS['woocommerce']->cart);

        $this->assertSame(12.5, $spiff_product->price);
        $this->assertNull($other_product->price);
    }

    // Customer selections in cart.

    public function testShowsCustomerSelectionsInCart() {
        $cart_data = array(array('name' => 'Colour', 'value' => 'Red'));
        $cart_item = array('spiff_exported_data' => array('Name' => 'Jamie', 'Message' => '<b>Hi</b>'));

        $this->assertSame(array(
            array('name' => 'Colour', 'value' => 'Red'),
            array('name' => 'Name', 'value' => 'Jamie'),
            array('name' => 'Message', 'value' => '&lt;b&gt;Hi&lt;/b&gt;'),
        ), spiff_show_metadata_in_cart($cart_data, $cart_item));
    }

    // Preview images in cart.

    public function testShowsPreviewImageForSpiffItems() {
        spiff_test_queue_response(array('data' => array('transactions' => array(array(
            'previewImageLink' => 'https://images.spiff.test/preview.png',
        )))));

        $image = spiff_show_preview_image_in_cart('<img src="product.png" />', array('spiff_transaction_id' => 'transaction-1'), 'key');

        $this->assertSame('<img src="https://images.spiff.test/preview.png" alt="preview" />', $image);
        $body = json_decode($GLOBALS['spiff_test_requests'][0]['args']['body'], true);
        $this->assertSame(array('ids' => array('transaction-1')), $body['variables']);
    }

    public function testKeepsProductImageWhenSpiffHasNoPreview() {
        spiff_test_queue_response(array('data' => array('transactions' => array(array('previewImageLink' => null)))));

        $image = spiff_show_preview_image_in_cart('<img src="product.png" />', array('spiff_transaction_id' => 'transaction-1'), 'key');

        $this->assertSame('<img src="product.png" />', $image);
    }

    public function testKeepsProductImageWhenSpiffRequestFails() {
        spiff_test_queue_response(array('errors' => array(array('message' => 'Unauthorised'))), 401);

        $image = spiff_show_preview_image_in_cart('<img src="product.png" />', array('spiff_transaction_id' => 'transaction-1'), 'key');

        $this->assertSame('<img src="product.png" />', $image);
    }

    public function testKeepsProductImageForOtherItems() {
        $image = spiff_show_preview_image_in_cart('<img src="product.png" />', array(), 'key');

        $this->assertSame('<img src="product.png" />', $image);
        $this->assertCount(0, $GLOBALS['spiff_test_requests']);
    }

    // Checkout.

    public function testCopiesTransactionIdToOrderItem() {
        $order_item = new Spiff_Test_Order_Item(array(), 1);

        spiff_add_cart_item_attributes_to_order_item($order_item, 'key', array('spiff_transaction_id' => 'transaction-1'), null);

        $this->assertSame('transaction-1', $order_item->get_meta('spiff_transaction_id'));
    }

    public function testLeavesOtherOrderItemsAlone() {
        $order_item = new Spiff_Test_Order_Item(array(), 1);

        spiff_add_cart_item_attributes_to_order_item($order_item, 'key', array(), null);

        $this->assertSame(array(), $order_item->meta);
    }
}
