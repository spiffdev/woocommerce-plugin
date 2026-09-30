<?php

use PHPUnit\Framework\TestCase;

final class ShortcodeTest extends TestCase {
    protected function setUp(): void {
        spiff_test_reset();
        $GLOBALS['spiff_test_options']['spiff_application_key'] = 'test-application-key';
    }

    public function testCustomerPortalButtonLaunchesPortal() {
        $html = spiff_customer_portal_button_shortcode_handler(array());

        $this->assertStringContainsString('class="spiff-customer-portal-button"', $html);
        $this->assertStringContainsString("window.spiffLaunchCustomerPortal('test-application-key', 'https://shop.test/cart/')", $html);
    }

    public function testCustomerPortalButtonUsesDefaults() {
        $html = spiff_customer_portal_button_shortcode_handler(array());

        $this->assertStringContainsString('Customer Portal', $html);
        $this->assertStringContainsString('font-size: 20px;', $html);
        $this->assertStringContainsString('background: #da1c5c;', $html);
        $this->assertStringContainsString('color: #fff;', $html);
        $this->assertStringContainsString('font-weight: 700;', $html);
        $this->assertStringContainsString('width: 100%;', $html);
        $this->assertStringContainsString('height: 50px;', $html);
    }

    public function testCustomerPortalButtonUsesConfiguredStyles() {
        $GLOBALS['spiff_test_options'] += array(
            'spiff_customer_portal_button_text' => 'My Designs',
            'spiff_customer_portal_button_font_size' => '16px',
            'spiff_customer_portal_button_background_color' => '#000',
            'spiff_customer_portal_button_text_color' => '#ff0',
            'spiff_customer_portal_button_font_weight' => '400',
            'spiff_customer_portal_button_width' => '50%',
            'spiff_customer_portal_button_height' => '40px',
        );

        $html = spiff_customer_portal_button_shortcode_handler(array());

        $this->assertStringContainsString('My Designs', $html);
        $this->assertStringContainsString('font-size: 16px;', $html);
        $this->assertStringContainsString('background: #000;', $html);
        $this->assertStringContainsString('color: #ff0;', $html);
        $this->assertStringContainsString('font-weight: 400;', $html);
        $this->assertStringContainsString('width: 50%;', $html);
        $this->assertStringContainsString('height: 40px;', $html);
        $this->assertStringNotContainsString('Customer Portal', $html);
    }

    public function testCustomerPortalButtonEscapesConfiguredText() {
        $GLOBALS['spiff_test_options']['spiff_customer_portal_button_text'] = '<script>alert(1)</script>';

        $html = spiff_customer_portal_button_shortcode_handler(array());

        $this->assertStringNotContainsString('<script>', $html);
    }
}
