<?php

use PHPUnit\Framework\TestCase;

final class ActivationTest extends TestCase {
    protected function setUp(): void {
        spiff_test_reset();
    }

    private function addAdmin() {
        $GLOBALS['spiff_test_users'][] = (object) array(
            'ID' => 7,
            'data' => (object) array('user_email' => 'admin@shop.test'),
        );
        $GLOBALS['spiff_test_user_meta'][7] = array(
            'billing_phone' => '0400 000 000',
            'first_name' => 'Jamie',
            'last_name' => 'Smith',
        );
    }

    public function testFirstActivationNotifiesSpiff() {
        $this->addAdmin();

        spiff_activation_hook();

        $this->assertCount(1, $GLOBALS['spiff_test_requests']);
        $request = $GLOBALS['spiff_test_requests'][0];
        $this->assertSame(SPIFF_TEST_AP_BASE . '/graphql', $request['url']);
        $body = json_decode($request['args']['body'], true);
        $this->assertSame('InstallNotify', $body['operationName']);
        $this->assertSame(array(
            'type' => 'WooCommerce',
            'shopName' => 'https://shop.test',
            'owner' => 'Jamie Smith',
            'email' => 'admin@shop.test',
            'phone' => '0400 000 000',
        ), $body['variables']['input']);
    }

    public function testFirstActivationIsRecorded() {
        $this->addAdmin();

        spiff_activation_hook();

        $this->assertSame('1', get_option('spiff_plugin_was_activated'));
    }

    public function testLaterActivationsDoNotNotifySpiff() {
        $this->addAdmin();
        $GLOBALS['spiff_test_options']['spiff_plugin_was_activated'] = '1';

        spiff_activation_hook();

        $this->assertCount(0, $GLOBALS['spiff_test_requests']);
    }

    public function testActivationWithoutAdminsDoesNotNotifySpiff() {
        spiff_activation_hook();

        $this->assertCount(0, $GLOBALS['spiff_test_requests']);
        $this->assertSame('1', get_option('spiff_plugin_was_activated'));
    }
}
