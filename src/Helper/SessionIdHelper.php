<?php

namespace TicketSwap\Payment\Przelewy24Bundle\Helper;

/**
 * Helper to work with session identifiers, which must be unique to Przelewy24.
 */
class SessionIdHelper
{
    /**
     * @param string $trackingId
     * @param \DateTime $transactionDate
     *
     * @return string
     */
    public static function generateSessionIdFromTrackingId(string $trackingId, \DateTime $transactionDate) : string
    {
        return sprintf(
            '%s-%s',
            $trackingId,
            $transactionDate->format('ymd-his')
        );
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    public static function getTrackingIdFromSessionId(string $sessionId) : string
    {
        return substr(
            $sessionId,
            0,
            strpos($sessionId, '-')
        );
    }
}
