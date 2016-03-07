<?php

namespace TicketSwap\PaymentPrzelewy24Bundle\Controller;

use AppBundle\Tests\Dummy;
use Doctrine\ORM\EntityManager;
use JMS\Payment\CoreBundle\Entity\FinancialTransaction;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\PluginController\PluginController;
use JMS\Payment\CoreBundle\PluginController\Result;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketSwap\PaymentPrzelewy24Bundle\Helper\SessionIdHelper;

/**
 * Webhook controller for Przewely24. After a payment is done, this webhook is called as a request for verification.
 */
class NotificationController
{
    /**
     * @var \JMS\Payment\CoreBundle\PluginController\PluginController
     */
    protected $pluginController;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    private $crc;

    /**
     * @param PluginController $pluginController
     * @param EntityManager $entityManager
     * @param string $crc
     */
    public function __construct(PluginController $pluginController, EntityManager $entityManager, $crc)
    {
        $this->pluginController = $pluginController;
        $this->entityManager = $entityManager;
        $this->crc = $crc;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function processNotification(Request $request)
    {
        if ($this->logger) {
            $this->logger->debug(print_r($request->request->all(), true));
        }

        if (false === $request->request->has('p24_session_id')) {
            if ($this->logger) {
                $this->logger->error('Przewely24 webhook called without p24_session_id');
            }

            return new Response('Failed: No session id', 400);
        }

        $sessionId = $request->request->get('p24_session_id');
        $trackingId = SessionIdHelper::getTrackingIdFromSessionId($sessionId);
        $referenceNumber = $request->request->get('p24_order_id');

        if (false === $this->isSignatureCorrect(
            $request->request->get('p24_sign'),
            $sessionId,
            $referenceNumber,
            $request->request->get('p24_amount'),
            $request->request->get('p24_currency'),
            $this->crc
        )) {
            if ($this->logger) {
                $this->logger->error('Given signature is not correct');
            }

            return new Response('[failed]', 400);
        }

        try {

            /** @var FinancialTransaction $financialTransaction */
            $financialTransaction = $this
                ->entityManager
                ->getRepository('JMS\Payment\CoreBundle\Entity\FinancialTransaction')
                ->findOneByTrackingId($trackingId);

            if (null === $financialTransaction) {
                if ($this->logger) {
                    $this->logger->error(sprintf(
                        'Financial transaction not available for tracking id %s',
                        $trackingId
                    ));
                }

                return new Response('[failed]', 404);
            }

            /**
             * @var \JMS\Payment\CoreBundle\Entity\Payment $payment
             */
            $payment = $financialTransaction->getPayment();

            if (PaymentInterface::STATE_APPROVING !== $payment->getState()) {
                if ($this->logger) {
                    $states = array(null, 'STATE_APPROVED', 'STATE_APPROVING', 'STATE_CANCELED' , 'STATE_EXPIRED', 'STATE_FAILED' , 'STATE_NEW', 'STATE_DEPOSITING', 'STATE_DEPOSITED');
                    $this->logger->error('Payment state is not STATE_APPROVING but  -> ' . $states[$payment->getState()]);
                }

                return new Response('[failed]', 500);
            }

            $financialTransaction->setReferenceNumber($referenceNumber);

            $this->entityManager->flush($payment);
            $this->entityManager->flush($financialTransaction);

            $result = $this
                ->pluginController
                ->approveAndDeposit($payment->getId(), $financialTransaction->getRequestedAmount());

            if ($this->logger) {
                $status = array(null, 'STATUS_FAILED', 'STATUS_PENDING', 'STATUS_SUCCESS', 'STATUS_UNKNOWN');
                $this->logger->debug('Result -> ' . $status[$result->getStatus()]);
            }

            if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                return new Response('OK', 201);
            }

            $instruction = $payment->getPaymentInstruction();
            $this->pluginController->closePaymentInstruction($instruction);

            if ($this->logger) {
                $this->logger->debug('closePaymentInstruction');
            }

            return new Response('OK', 200);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error($e->getMessage());
            }

            return new Response('[failed]', 500);
        }
    }

    /**
     * @param string $signature
     * @param string $trackingId
     * @param string $referenceNumber
     * @param string $amount
     * @param string $currency
     * @param string $crc
     * @return bool
     */
    private function isSignatureCorrect($signature, $trackingId, $referenceNumber, $amount, $currency, $crc)
    {
        $calculatedSignature = md5(sprintf('%s|%s|%s|%s|%s', $trackingId, $referenceNumber, $amount, $currency, $crc));

        return $calculatedSignature === $signature;
    }
}
