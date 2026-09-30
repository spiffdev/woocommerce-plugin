<?php

use PHPUnit\Framework\TestCase;

final class RequestsTest extends TestCase {
    protected function setUp(): void {
        spiff_test_reset();
    }

    public function testRequestHeadersIncludeApplicationKeyAndJsonContentType() {
        $headers = spiff_request_headers('test-application-key', '{}', SPIFF_GRAPHQL_PATH);

        $this->assertSame('test-application-key', $headers['X-Application-Key']);
        $this->assertSame('application/json', $headers['Content-Type']);
    }

    public function testRequestHeadersIncludeGmtDate() {
        $headers = spiff_request_headers('test-application-key', '{}', SPIFF_GRAPHQL_PATH);

        $this->assertMatchesRegularExpression('/^[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} \d{2}:\d{2}:\d{2} GMT$/', $headers['Date']);
    }

    public function testHexToBase64() {
        $this->assertSame(base64_encode('Hello'), spiff_hex_to_base64('48656c6c6f'));
    }

    public function testGraphqlUrlDefaultsToAustralia() {
        $this->assertSame(SPIFF_TEST_AP_BASE . '/graphql', spiff_get_graphql_url());
    }

    public function testGraphqlUrlForAustralia() {
        $GLOBALS['spiff_test_options']['spiff_infrastructure'] = 'AP';

        $this->assertSame(SPIFF_TEST_AP_BASE . '/graphql', spiff_get_graphql_url());
    }

    public function testGraphqlUrlForUnitedStates() {
        $GLOBALS['spiff_test_options']['spiff_infrastructure'] = 'US';

        $this->assertSame(SPIFF_TEST_US_BASE . '/graphql', spiff_get_graphql_url());
    }

    public function testLegacyAustraliaInfrastructureFallsBackToAustralia() {
        $GLOBALS['spiff_test_options']['spiff_infrastructure'] = 'AU';

        $this->assertSame(SPIFF_TEST_AP_BASE . '/graphql', spiff_get_graphql_url());
    }
}
