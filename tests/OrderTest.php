<?php

use PHPUnit\Framework\TestCase;

require 'spiff-connect/includes/spiff-connect-orders.php';

final class OrderTest extends TestCase {
    public function testComputesCorrectAuthHeader() {
        $known_auth_header = 'SOA access-key:T3Tonzh3i/x+OJ/Iyy5+IARBWKY=';

        $body = json_encode(array(
            'externalId' => 'external-id',
            'autoPrint' => false,
            'orderItems' => array()
        ));
        $computed_auth_header = spiff_auth_header('access-key', 'secret-key', 'POST', $body, 'application/json', 'Wed, 1 Jan 2020 0:0:0 GMT', 'api/v2/orders');

        $this->assertEquals($known_auth_header, $computed_auth_header);
    }
}
