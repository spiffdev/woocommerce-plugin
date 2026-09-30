<?php

use PHPUnit\Framework\TestCase;

final class ProductPageTest extends TestCase {
    const ADD_TO_CART_BUTTON = '<a href="?add-to-cart=42" data-quantity="1" class="button add_to_cart_button ajax_add_to_cart" aria-label="Add to your cart" rel="nofollow">Add to cart</a>';

    protected function setUp(): void {
        spiff_test_reset();
        $GLOBALS['spiff_test_options']['spiff_application_key'] = 'test-application-key';
    }

    private function enabledProduct() {
        return new Spiff_Test_Product(42, array(
            'spiff_enabled' => 'yes',
            'spiff_integration_product_id' => 'integration-product-1',
        ));
    }

    private function renderCreateDesignButton() {
        ob_start();
        spiff_append_create_design_button_on_product_page();
        return ob_get_clean();
    }

    // Product list.

    public function testLeavesListButtonAloneWhenSpiffIsDisabled() {
        $product = new Spiff_Test_Product(42, array('spiff_enabled' => 'no'));

        $this->assertSame(self::ADD_TO_CART_BUTTON, spiff_replace_default_button_on_product_list(self::ADD_TO_CART_BUTTON, $product));
    }

    public function testReplacesListButtonWithLinkToProductWhenSpiffIsEnabled() {
        $button = spiff_replace_default_button_on_product_list(self::ADD_TO_CART_BUTTON, $this->enabledProduct());

        $xml = simplexml_load_string($button);
        $this->assertSame('https://shop.test/product/42/', (string) $xml['href']);
        $this->assertSame('View details', (string) $xml['aria-label']);
        $this->assertSame('View details', (string) $xml);
        $this->assertStringNotContainsString('ajax_add_to_cart', (string) $xml['class']);
        $this->assertStringContainsString('add_to_cart_button', (string) $xml['class']);
    }

    // Single product page.

    public function testSwapsAddToCartForSpiffButtonWhenSpiffIsEnabled() {
        add_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        $GLOBALS['product'] = $this->enabledProduct();

        spiff_replace_default_element_on_product_page();

        $this->assertFalse(spiff_test_has_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30));
        $this->assertTrue(spiff_test_has_action('woocommerce_single_product_summary', 'spiff_append_create_design_button_on_product_page', 35));
    }

    public function testKeepsAddToCartWhenSpiffIsDisabled() {
        add_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        $GLOBALS['product'] = new Spiff_Test_Product(42, array('spiff_enabled' => 'no'));

        spiff_replace_default_element_on_product_page();

        $this->assertTrue(spiff_test_has_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30));
        $this->assertFalse(spiff_test_has_action('woocommerce_single_product_summary', 'spiff_append_create_design_button_on_product_page', 35));
    }

    public function testRendersCreateDesignButton() {
        $GLOBALS['product'] = $this->enabledProduct();

        $html = $this->renderCreateDesignButton();

        $this->assertStringContainsString('<div class="spiff-button-integration-product-integration-product-1"></div>', $html);
        $this->assertStringContainsString('window.spiffAppendCreateDesignButton(', $html);
        $this->assertStringContainsString('"42"', $html);
        $this->assertStringContainsString('"integration-product-1"', $html);
        $this->assertStringContainsString('"AUD"', $html);
        $this->assertStringContainsString('"https://shop.test/cart/"', $html);
        $this->assertStringContainsString('"test-application-key"', $html);
    }

    public function testCreateDesignButtonUsesDefaultStyles() {
        $GLOBALS['product'] = $this->enabledProduct();

        $html = $this->renderCreateDesignButton();

        $this->assertStringContainsString(json_encode(array(
            'personalizeButtonText' => 'Personalize',
            'size' => '20px',
            'weight' => '700',
            'textColor' => '#fff',
            'backgroundColor' => '#da1c5c',
            'width' => '100%',
            'height' => '50px',
        )), $html);
    }

    public function testCreateDesignButtonUsesConfiguredStyles() {
        $GLOBALS['product'] = $this->enabledProduct();
        $GLOBALS['spiff_test_options'] += array(
            'spiff_non_bulk_text' => 'Customise',
            'spiff_font_size' => '16px',
            'spiff_font_weight' => '400',
            'spiff_text_color' => '#000',
            'spiff_background_color' => '#ff0',
            'spiff_width' => '50%',
            'spiff_height' => '40px',
        );

        $html = $this->renderCreateDesignButton();

        $this->assertStringContainsString(json_encode(array(
            'personalizeButtonText' => 'Customise',
            'size' => '16px',
            'weight' => '400',
            'textColor' => '#000',
            'backgroundColor' => '#ff0',
            'width' => '50%',
            'height' => '40px',
        )), $html);
    }

    public function testDoesNotRenderCreateDesignButtonWhenSpiffIsDisabled() {
        $GLOBALS['product'] = new Spiff_Test_Product(42, array('spiff_enabled' => 'no'));

        $this->assertSame('', $this->renderCreateDesignButton());
    }

    // Scripts.

    public function testEnqueuesScripts() {
        spiff_enqueue_ecommerce_client();

        $this->assertSame(array(
            'spiff-ecommerce-client' => 'https://shop.test/wp-content/plugins/spiff-connect/public/js/api.js',
            'spiff-create-design-button' => 'https://shop.test/wp-content/plugins/spiff-connect/public/js/create-design-button.js',
        ), $GLOBALS['spiff_test_scripts']);
    }

    public function testPassesAjaxUrlAndRegionToScript() {
        $GLOBALS['spiff_test_options']['spiff_infrastructure'] = 'US';

        spiff_enqueue_ecommerce_client();

        $this->assertSame(array(
            'ajax_url' => 'https://shop.test/wp-admin/admin-ajax.php',
            'region_code' => 'us',
        ), $GLOBALS['spiff_test_localized_scripts']['spiff-create-design-button']['ajax_object']);
    }
}
