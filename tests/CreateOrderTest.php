<?php

use PHPUnit\Framework\TestCase;

final class CreateOrderTest extends TestCase {
    protected function setUp(): void {
        spiff_test_reset();
        $GLOBALS['spiff_test_options']['spiff_application_key'] = 'test-application-key';
    }

    private function addOrder($id, $order_number, $items, $paid = true) {
        $GLOBALS['spiff_test_orders'][$id] = new Spiff_Test_Order($id, $order_number, $items, $paid);
    }

    private function spiffItem($transaction_id, $quantity = 1) {
        return new Spiff_Test_Order_Item(array('spiff_transaction_id' => $transaction_id), $quantity);
    }

    private function sentVariables() {
        $this->assertCount(1, $GLOBALS['spiff_test_requests']);
        return json_decode($GLOBALS['spiff_test_requests'][0]['args']['body'], true)['variables'];
    }

    public function testUsesOrderIdAsExternalIdByDefault() {
        $this->addOrder(123, 'VEG-1042', array($this->spiffItem('transaction-1')));

        spiff_create_order(123);

        $this->assertSame('123', $this->sentVariables()['externalId']);
    }

    public function testFilterCanReplaceExternalIdWithOrderNumber() {
        $this->addOrder(123, 'VEG-1042', array($this->spiffItem('transaction-1')));
        add_filter('spiff_order_external_id', function ($external_id, $order) {
            return $order->get_order_number();
        }, 10, 2);

        spiff_create_order(123);

        $this->assertSame('VEG-1042', $this->sentVariables()['externalId']);
    }

    public function testFilterReceivesOrderIdAndOrder() {
        $this->addOrder(123, 'VEG-1042', array($this->spiffItem('transaction-1')));
        $received = null;
        add_filter('spiff_order_external_id', function ($external_id, $order) use (&$received) {
            $received = array($external_id, $order);
            return $external_id;
        }, 10, 2);

        spiff_create_order(123);

        $this->assertSame(123, $received[0]);
        $this->assertSame($GLOBALS['spiff_test_orders'][123], $received[1]);
    }

    public function testNumericFilterResultIsSentAsString() {
        $this->addOrder(123, 'VEG-1042', array($this->spiffItem('transaction-1')));
        add_filter('spiff_order_external_id', function () {
            return 5000;
        });

        spiff_create_order(123);

        $this->assertSame('5000', $this->sentVariables()['externalId']);
    }

    public function testOrderNumberIsAlwaysSentInExternalData() {
        $this->addOrder(123, 'VEG-1042', array($this->spiffItem('transaction-1')));

        spiff_create_order(123);

        $this->assertSame('VEG-1042', $this->sentVariables()['externalData']['orderNumber']);
    }

    public function testOnlyItemsWithTransactionsAreSent() {
        $this->addOrder(123, 'VEG-1042', array(
            $this->spiffItem('transaction-1', 2),
            new Spiff_Test_Order_Item(array(), 1),
            $this->spiffItem('transaction-2', '3'),
        ));

        spiff_create_order(123);

        $this->assertSame(array(
            array('amountToOrder' => 2, 'transactionId' => 'transaction-1'),
            array('amountToOrder' => 3, 'transactionId' => 'transaction-2'),
        ), $this->sentVariables()['orderItems']);
    }

    public function testNoRequestIsSentWithoutSpiffItems() {
        $this->addOrder(123, 'VEG-1042', array(new Spiff_Test_Order_Item(array(), 1)));

        spiff_create_order(123);

        $this->assertCount(0, $GLOBALS['spiff_test_requests']);
    }

    public function testSendsCustomerDetailsInExternalData() {
        $GLOBALS['spiff_test_orders'][123] = new Spiff_Test_Order(123, 'VEG-1042', array($this->spiffItem('transaction-1')), true, array(
            'billing_email' => 'jamie@example.test',
            'billing_phone' => '0400 000 000',
            'customer_note' => 'Leave at the door',
            'coupon_codes' => array('SAVE10'),
            'billing_first_name' => 'Jamie',
            'billing_city' => 'Melbourne',
            'shipping_address_1' => '1 Example St',
            'shipping_state' => 'VIC',
            'shipping_postcode' => '3000',
            'shipping_country' => 'AU',
        ));

        spiff_create_order(123);

        $external_data = $this->sentVariables()['externalData'];
        $this->assertSame('jamie@example.test', $external_data['customerEmail']);
        $this->assertSame('0400 000 000', $external_data['customerPhone']);
        $this->assertSame('Leave at the door', $external_data['note']);
        $this->assertSame(array('SAVE10'), $external_data['discountCodes']);
        $this->assertSame('Jamie', $external_data['billingAddress']['firstName']);
        $this->assertSame('Melbourne', $external_data['billingAddress']['city']);
        $this->assertSame('1 Example St', $external_data['shippingAddress']['address1']);
        $this->assertSame('VIC', $external_data['shippingAddress']['province']);
        $this->assertSame('VIC', $external_data['shippingAddress']['provinceCode']);
        $this->assertSame('3000', $external_data['shippingAddress']['zip']);
        $this->assertSame('AU', $external_data['shippingAddress']['country']);
        $this->assertSame('AU', $external_data['shippingAddress']['countryCode']);
    }

    public function testSendsOrderCreateMutationToAustraliaByDefault() {
        $this->addOrder(123, 'VEG-1042', array($this->spiffItem('transaction-1')));

        spiff_create_order(123);

        $request = $GLOBALS['spiff_test_requests'][0];
        $this->assertSame(SPIFF_TEST_AP_BASE . '/graphql', $request['url']);
        $this->assertSame('OrderCreate', json_decode($request['args']['body'], true)['operationName']);
    }

    public function testSendsOrderToUnitedStatesWhenConfigured() {
        $GLOBALS['spiff_test_options']['spiff_infrastructure'] = 'US';
        $this->addOrder(123, 'VEG-1042', array($this->spiffItem('transaction-1')));

        spiff_create_order(123);

        $this->assertSame(SPIFF_TEST_US_BASE . '/graphql', $GLOBALS['spiff_test_requests'][0]['url']);
    }

    public function testFailedRequestDoesNotStopCheckout() {
        spiff_test_queue_response(array('errors' => array(array('message' => 'Unauthorised'))), 401);
        $this->addOrder(123, 'VEG-1042', array($this->spiffItem('transaction-1')));
        $log = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        try {
            spiff_create_order(123);
        } finally {
            ini_set('error_log', $log);
        }

        $this->assertCount(1, $GLOBALS['spiff_test_requests']);
    }

    public function testSendsPaidFlagAndApplicationKey() {
        $this->addOrder(123, 'VEG-1042', array($this->spiffItem('transaction-1')), false);

        spiff_create_order(123);

        $this->assertFalse($this->sentVariables()['paid']);
        $headers = $GLOBALS['spiff_test_requests'][0]['args']['headers'];
        $this->assertSame('test-application-key', $headers['X-Application-Key']);
    }
}
