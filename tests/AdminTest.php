<?php

use PHPUnit\Framework\TestCase;

final class AdminTest extends TestCase {
    protected function setUp(): void {
        spiff_test_reset();
    }

    public function testCreatesAdminMenuPage() {
        spiff_create_admin_menu();

        $this->assertSame(array(array(
            'page_title' => 'Spiff Connect',
            'menu_title' => 'Spiff Connect',
            'capability' => 'administrator',
            'menu_slug' => 'spiff-connect',
            'callback' => 'spiff_admin_menu_html',
        )), $GLOBALS['spiff_test_menu_pages']);
        $this->assertTrue(spiff_test_has_action('admin_init', 'spiff_register_admin_settings'));
    }

    public function testRegistersSettings() {
        spiff_register_admin_settings();

        $this->assertSame(array(
            'spiff_application_key',
            'spiff_infrastructure',
            'spiff_show_customer_selections_in_cart',
            'spiff_show_preview_images_in_cart',
            'spiff_non_bulk_text',
            'spiff_font_size',
            'spiff_font_weight',
            'spiff_text_color',
            'spiff_background_color',
            'spiff_width',
            'spiff_height',
            'spiff_customer_portal_button_text',
            'spiff_customer_portal_button_font_size',
            'spiff_customer_portal_button_font_weight',
            'spiff_customer_portal_button_text_color',
            'spiff_customer_portal_button_background_color',
            'spiff_customer_portal_button_width',
            'spiff_customer_portal_button_height',
        ), $GLOBALS['spiff_test_settings']['spiff-settings-group']);
    }

    public function testAddsProductFields() {
        spiff_create_admin_product_fields();

        $fields = $GLOBALS['spiff_test_product_fields'];
        $this->assertCount(2, $fields);
        $this->assertSame('checkbox', $fields[0]['type']);
        $this->assertSame('spiff_enabled', $fields[0]['id']);
        $this->assertSame('text', $fields[1]['type']);
        $this->assertSame('spiff_integration_product_id', $fields[1]['id']);
    }

    public function testSavesEnabledProductFields() {
        $product = new Spiff_Test_Product(42);
        $GLOBALS['spiff_test_products'][42] = $product;
        $_POST = array('spiff_enabled' => 'yes', 'spiff_integration_product_id' => ' integration-product-1 ');

        spiff_save_admin_product_fields(42);

        $this->assertSame('yes', $product->meta['spiff_enabled']);
        $this->assertSame('integration-product-1', $product->meta['spiff_integration_product_id']);
        $this->assertTrue($product->saved);
    }

    public function testSavesDisabledProductFieldsWhenCheckboxIsUnticked() {
        $product = new Spiff_Test_Product(42, array('spiff_enabled' => 'yes'));
        $GLOBALS['spiff_test_products'][42] = $product;
        $_POST = array('spiff_integration_product_id' => 'integration-product-1');

        spiff_save_admin_product_fields(42);

        $this->assertSame('no', $product->meta['spiff_enabled']);
        $this->assertTrue($product->saved);
    }
}
