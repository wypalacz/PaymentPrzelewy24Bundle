<?php

namespace TicketSwap\Payment\Przelewy24Bundle\Tests\Helper;

use TicketSwap\Payment\Przelewy24Bundle\Helper\SessionIdHelper;

/**
 * @covers \TicketSwap\Payment\Przelewy24Bundle\Helper\SessionIdHelper
 * @group FullCoverage
 */
class SessionIdHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_generate_a_session_id()
    {
        $dateTime = new \DateTime();
        $trackingId = '42';

        $sessionId = SessionIdHelper::generateSessionIdFromTrackingId($trackingId, $dateTime);

        $this->assertStringStartsWith($trackingId, $sessionId);
        $this->assertStringEndsWith($dateTime->format('ymd-his'), $sessionId);
    }

    /**
     * @test
     */
    public function it_should_get_tracking_id_from_session_id()
    {
        $trackingId = SessionIdHelper::getTrackingIdFromSessionId('42-160303-043717');
        $this->assertSame('42', $trackingId);
    }
}
