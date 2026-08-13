<?php

use PHPUnit\Framework\TestCase;

require 'spiff-connect/includes/spiff-connect-requests.php';

final class RequestsTest extends TestCase {
    public function testDummy() {
        $this->assertEquals(true, true);
    }
}
