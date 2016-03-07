<?php

namespace TicketSwap\Payment\Przelewy24Bundle\Plugin;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\BlockedException;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use Omnipay\Common\CreditCard;
use Psr\Log\LoggerInterface;
use Omnipay\Przelewy24\Gateway;
use TicketSwap\Payment\Przelewy24Bundle\Helper\SessionIdHelper;

/**
 * JMSPayment plugin to process Przewely24 payments.
 */
class DefaultPlugin extends AbstractPlugin
{
    /**
     * @var Gateway
     */
    protected $gateway;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    private $reportUrl;

    /**
     * @param Gateway $gateway
     * @param string $reportUrl
     */
    public function __construct(Gateway $gateway, $reportUrl)
    {
        $this->gateway = $gateway;
        $this->reportUrl = $reportUrl;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Whether this plugin can process payments for the given payment system.
     *
     * A plugin may support multiple payment systems. In these cases, the requested
     * payment system for a specific transaction  can be determined by looking at
     * the PaymentInstruction which will always be accessible either directly, or
     * indirectly.
     *
     * @param string $paymentSystemName
     * @return boolean
     */
    public function processes($paymentSystemName)
    {
        return 'przewely24_checkout' === $paymentSystemName;
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @param bool $retry
     * @throws ActionRequiredException
     * @throws BlockedException
     * @throws FinancialException
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        if ($transaction->getState() === FinancialTransactionInterface::STATE_NEW) {
            throw $this->createRedirectActionException($transaction);
        }

        $trackingId = $transaction->getTrackingId();
        if (null === $trackingId) {
            return;
        }

        $referenceNumber = $transaction->getReferenceNumber();

        if (null === $referenceNumber) {
            if ($this->logger) {
                $this->logger->info(sprintf(
                    'Waiting for notification from Przewely24 for transaction "%s".',
                    $transaction->getTrackingId()
                ));
            }

            throw new BlockedException('Waiting for notification from Przewely24.');
        }

        /**
         * @var \JMS\Payment\CoreBundle\Entity\Payment $payment
         */
        $payment = $transaction->getPayment();

        /**
         * @var \JMS\Payment\CoreBundle\Model\PaymentInstructionInterface $paymentInstruction
         */
        $paymentInstruction = $payment->getPaymentInstruction();

        $completePurchaseRequest = $this->gateway->completePurchase(array(
            'sessionId' => SessionIdHelper::generateSessionIdFromTrackingId($trackingId, $transaction->getCreatedAt()),
            'transactionId' => $referenceNumber,
            'amount' => $payment->getTargetAmount(),
            'currency' => $paymentInstruction->getCurrency()
        ));

        $completePurchaseResponse = $completePurchaseRequest->send();

        if ($this->logger) {
            $this->logger->info('Completing payment');
            $this->logger->info(print_r($completePurchaseResponse->getData(), true));
        }

        if (false === $completePurchaseResponse->isSuccessful()) {
            $ex = new FinancialException('Payment failed.');
            $ex->setFinancialTransaction($transaction);
            $transaction->setState(FinancialTransactionInterface::STATE_FAILED);
            $transaction->setResponseCode('FAILED');
            $transaction->setReasonCode('FAILED');

            if ($this->logger) {
                $this->logger->info(sprintf(
                    'Payment failed for transaction "%s".',
                    $transaction->getTrackingId()
                ));
            }

            throw $ex;
        }

        $payment->setState(PaymentInterface::STATE_APPROVED);
        $transaction->setState(FinancialTransactionInterface::STATE_SUCCESS);
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);

        if ($this->logger) {
            $this->logger->info(sprintf(
                'Payment is successful for transaction "%s".',
                $transaction->getTrackingId()
            ));
        }
    }

    /**
     * @param $transaction
     * @return ActionRequiredException
     * @throws FinancialException
     */
    private function createRedirectActionException($transaction)
    {
        $parameters = $this->getPurchaseParameters($transaction);
        $purchaseRequest = $this->gateway->purchase($parameters);

        if ($this->logger) {
            $this->logger->debug(print_r($purchaseRequest->getData(), true));
        }

        $purchaseResponse = $purchaseRequest->send();

        if ($this->logger) {
            $this->logger->debug(print_r($purchaseResponse->getData(), true));
        }

        if (false === $purchaseResponse->isSuccessful()) {
            if ($this->logger) {
                $this->logger->error(sprintf('Payment failed with error %s', $purchaseResponse->getCode()));
            }

            $ex = new FinancialException('Payment failed.');
            throw $ex;
        }

        $url = $purchaseResponse->getRedirectUrl();
        if (empty($url)) {
            $ex = new FinancialException('Payment failed.');
            throw $ex;
        }

        $actionRequest = new ActionRequiredException('Redirect the user to Przewely24.');
        $actionRequest->setFinancialTransaction($transaction);
        $actionRequest->setAction(new VisitUrl($url));

        if ($this->logger) {
            $this->logger->info(sprintf(
                'Create a new redirect exception for transaction "%s".',
                $purchaseResponse->getToken()
            ));
        }

        return $actionRequest;
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @return array
     */
    protected function getPurchaseParameters(FinancialTransactionInterface $transaction)
    {
        /**
         * @var \JMS\Payment\CoreBundle\Model\PaymentInterface $payment
         */
        $payment = $transaction->getPayment();

        /**
         * @var \JMS\Payment\CoreBundle\Model\PaymentInstructionInterface $paymentInstruction
         */
        $paymentInstruction = $payment->getPaymentInstruction();

        /**
         * @var \JMS\Payment\CoreBundle\Model\ExtendedDataInterface $data
         */
        $data = $transaction->getExtendedData();

        $transaction->setTrackingId($payment->getId());

        $card = new CreditCard();
        $card->setEmail($data->get('email'));
        $card->setCountry($data->get('country'));

        $parameters = array(
            'sessionId'     => SessionIdHelper::generateSessionIdFromTrackingId(
                $transaction->getTrackingId(),
                $transaction->getCreatedAt()
            ),
            'amount'        => $payment->getTargetAmount(),
            'currency'      => $paymentInstruction->getCurrency(),
            'description'   => $data->has('description') ?
                $data->get('description') :
                'Transaction ' . $payment->getId(),
            'card'          => $card,
            'notifyUrl'     => $this->reportUrl,
            'cancelUrl'     => $data->get('cancel_url'),
            'returnUrl'     => $data->get('return_url'),
        );

        return $parameters;
    }
}
