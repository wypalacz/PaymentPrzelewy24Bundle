<?php

namespace TicketSwap\Payment\Przelewy24Bundle\Tests\Plugin;

use JMS\Payment\CoreBundle\Entity\FinancialTransaction;
use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use Omnipay\Przelewy24\Gateway;
use Omnipay\Przelewy24\Message\CompletePurchaseRequest;
use Omnipay\Przelewy24\Message\CompletePurchaseResponse;
use Omnipay\Przelewy24\Message\PurchaseRequest;
use Omnipay\Przelewy24\Message\PurchaseResponse;
use Psr\Log\LoggerInterface;
use TicketSwap\Payment\Przelewy24Bundle\Plugin\DefaultPlugin;

/**
 * @covers \TicketSwap\Payment\Przelewy24Bundle\Plugin\DefaultPlugin
 * @group FullCoverage
 */
class DefaultPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Gateway
     */
    private $gateway;

    /**
     * @var string
     */
    private $reportUrl;

    /**
     * @var DefaultPlugin
     */
    private $defaultPlugin;

    public function setUp()
    {
        $this->gateway = $this->createGatewayMock();
        $this->reportUrl = 'http://www.ticketswap.nl/notify';

        $this->defaultPlugin = new DefaultPlugin(
            $this->gateway,
            $this->reportUrl
        );
    }

    /**
     * @test
     * @expectedException \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     * @expectedExceptionMessage Payment failed.
     */
    public function it_should_fail_if_creating_a_new_purchase_fails()
    {
        $paymentId = 42;
        $email = 'test@ticketswap.com';
        $country = 'PL';
        $createdAt = new \DateTime();
        $targetAmount = 1200;
        $currency = 'PLN';
        $cancelUrl = 'http://www.ticketswap.nl/cancel';
        $returnUrl = 'http://www.ticketswap.nl/return';

        $transaction = $this->createFinancialTransactionMock();
        $payment = $this->createPaymentInterfaceMock();
        $paymentInstruction = $this->createPaymentInstructionInterfaceMock();
        $extendedData = $this->createExtendedDataInterfaceMock();
        $purchaseRequest = $this->createPurchaseRequestMock();
        $purchaseResponse = $this->createPurchaseResponseMock();

        $transaction->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(FinancialTransactionInterface::STATE_NEW);

        $transaction->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($payment);

        $payment->expects($this->atLeastOnce())
            ->method('getPaymentInstruction')
            ->willReturn($paymentInstruction);

        $transaction->expects($this->atLeastOnce())
            ->method('getExtendedData')
            ->willReturn($extendedData);

        $payment->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($paymentId);

        $transaction->expects($this->once())
            ->method('setTrackingId')
            ->with($paymentId);

        $extendedData->expects($this->at(0))
            ->method('get')
            ->with('email')
            ->willReturn($email);

        $extendedData->expects($this->at(1))
            ->method('get')
            ->with('country')
            ->willReturn($country);

        $extendedData->expects($this->at(2))
            ->method('has')
            ->with('description')
            ->willReturn(false);

        $extendedData->expects($this->at(3))
            ->method('get')
            ->with('cancel_url')
            ->willReturn($cancelUrl);

        $extendedData->expects($this->at(4))
            ->method('get')
            ->with('return_url')
            ->willReturn($returnUrl);

        $transaction->expects($this->atLeastOnce())
            ->method('getTrackingId')
            ->willReturn($paymentId);

        $transaction->expects($this->atLeastOnce())
            ->method('getCreatedAt')
            ->willReturn($createdAt);

        $payment->expects($this->atLeastOnce())
            ->method('getTargetAmount')
            ->willReturn($targetAmount);

        $paymentInstruction->expects($this->atLeastOnce())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->gateway->expects($this->once())
            ->method('purchase')
            ->willReturn($purchaseRequest);

        $purchaseRequest->expects($this->once())
            ->method('send')
            ->willReturn($purchaseResponse);

        $purchaseResponse->expects($this->once())
            ->method('isSuccessful')
            ->willReturn(false);

        $this->defaultPlugin->approveAndDeposit($transaction, true);
    }

    /**
     * @test
     * @expectedException \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     * @expectedExceptionMessage Payment failed.
     */
    public function it_should_fail_if_creating_new_purchase_returns_empty_return_url()
    {
        $paymentId = 42;
        $email = 'test@ticketswap.com';
        $country = 'PL';
        $createdAt = new \DateTime();
        $targetAmount = 1200;
        $currency = 'PLN';
        $cancelUrl = 'http://www.ticketswap.nl/cancel';
        $returnUrl = 'http://www.ticketswap.nl/return';

        $transaction = $this->createFinancialTransactionMock();
        $payment = $this->createPaymentInterfaceMock();
        $paymentInstruction = $this->createPaymentInstructionInterfaceMock();
        $extendedData = $this->createExtendedDataInterfaceMock();
        $purchaseRequest = $this->createPurchaseRequestMock();
        $purchaseResponse = $this->createPurchaseResponseMock();

        $transaction->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(FinancialTransactionInterface::STATE_NEW);

        $transaction->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($payment);

        $payment->expects($this->atLeastOnce())
            ->method('getPaymentInstruction')
            ->willReturn($paymentInstruction);

        $transaction->expects($this->atLeastOnce())
            ->method('getExtendedData')
            ->willReturn($extendedData);

        $payment->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($paymentId);

        $transaction->expects($this->once())
            ->method('setTrackingId')
            ->with($paymentId);

        $extendedData->expects($this->at(0))
            ->method('get')
            ->with('email')
            ->willReturn($email);

        $extendedData->expects($this->at(1))
            ->method('get')
            ->with('country')
            ->willReturn($country);

        $extendedData->expects($this->at(2))
            ->method('has')
            ->with('description')
            ->willReturn(false);

        $extendedData->expects($this->at(3))
            ->method('get')
            ->with('cancel_url')
            ->willReturn($cancelUrl);

        $extendedData->expects($this->at(4))
            ->method('get')
            ->with('return_url')
            ->willReturn($returnUrl);

        $transaction->expects($this->atLeastOnce())
            ->method('getTrackingId')
            ->willReturn($paymentId);

        $transaction->expects($this->atLeastOnce())
            ->method('getCreatedAt')
            ->willReturn($createdAt);

        $payment->expects($this->atLeastOnce())
            ->method('getTargetAmount')
            ->willReturn($targetAmount);

        $paymentInstruction->expects($this->atLeastOnce())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->gateway->expects($this->once())
            ->method('purchase')
            ->willReturn($purchaseRequest);

        $purchaseRequest->expects($this->once())
            ->method('send')
            ->willReturn($purchaseResponse);

        $purchaseResponse->expects($this->once())
            ->method('isSuccessful')
            ->willReturn(true);

        $purchaseResponse->expects($this->once())
            ->method('getRedirectUrl')
            ->willReturn(null);

        $this->defaultPlugin->approveAndDeposit($transaction, true);
    }

    /**
     * @test
     */
    public function it_should_create_a_redirect_action_when_the_payment_is_new()
    {
        $paymentId = 42;
        $email = 'test@ticketswap.com';
        $country = 'PL';
        $createdAt = new \DateTime();
        $targetAmount = 1200;
        $currency = 'PLN';
        $cancelUrl = 'http://www.ticketswap.nl/cancel';
        $returnUrl = 'http://www.ticketswap.nl/return';
        $redirectUrl = 'https://secure.przelewy24.pl/trnRequest/aa-bb-cc-11';

        $transaction = $this->createFinancialTransactionMock();
        $payment = $this->createPaymentInterfaceMock();
        $paymentInstruction = $this->createPaymentInstructionInterfaceMock();
        $extendedData = $this->createExtendedDataInterfaceMock();
        $purchaseRequest = $this->createPurchaseRequestMock();
        $purchaseResponse = $this->createPurchaseResponseMock();

        $transaction->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(FinancialTransactionInterface::STATE_NEW);

        $transaction->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($payment);

        $payment->expects($this->atLeastOnce())
            ->method('getPaymentInstruction')
            ->willReturn($paymentInstruction);

        $transaction->expects($this->atLeastOnce())
            ->method('getExtendedData')
            ->willReturn($extendedData);

        $payment->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($paymentId);

        $transaction->expects($this->once())
            ->method('setTrackingId')
            ->with($paymentId);

        $extendedData->expects($this->at(0))
            ->method('get')
            ->with('email')
            ->willReturn($email);

        $extendedData->expects($this->at(1))
            ->method('get')
            ->with('country')
            ->willReturn($country);

        $extendedData->expects($this->at(2))
            ->method('has')
            ->with('description')
            ->willReturn(false);

        $extendedData->expects($this->at(3))
            ->method('get')
            ->with('cancel_url')
            ->willReturn($cancelUrl);

        $extendedData->expects($this->at(4))
            ->method('get')
            ->with('return_url')
            ->willReturn($returnUrl);

        $transaction->expects($this->atLeastOnce())
            ->method('getTrackingId')
            ->willReturn($paymentId);

        $transaction->expects($this->atLeastOnce())
            ->method('getCreatedAt')
            ->willReturn($createdAt);

        $payment->expects($this->atLeastOnce())
            ->method('getTargetAmount')
            ->willReturn($targetAmount);

        $paymentInstruction->expects($this->atLeastOnce())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->gateway->expects($this->once())
            ->method('purchase')
            ->willReturn($purchaseRequest);

        $purchaseRequest->expects($this->once())
            ->method('send')
            ->willReturn($purchaseResponse);

        $purchaseResponse->expects($this->once())
            ->method('isSuccessful')
            ->willReturn(true);

        $purchaseResponse->expects($this->once())
            ->method('getRedirectUrl')
            ->willReturn($redirectUrl);

        $this->setExpectedException(ActionRequiredException::class);

        $this->defaultPlugin->approveAndDeposit($transaction, true);
    }

    /**
     * @test
     */
    public function it_should_not_complete_the_payment_without_tracking_id()
    {
        $transaction = $this->createFinancialTransactionMock();

        $transaction->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(FinancialTransactionInterface::STATE_PENDING);

        $transaction->expects($this->atLeastOnce())
            ->method('getTrackingId')
            ->willReturn(null);

        $this->defaultPlugin->approveAndDeposit($transaction, true);
    }

    /**
     * @test
     * @expectedException \JMS\Payment\CoreBundle\Plugin\Exception\BlockedException
     * @expectedExceptionMessage Waiting for notification from Przewely24.
     */
    public function it_should_block_without_a_reference_number()
    {
        $paymentId = 42;

        $transaction = $this->createFinancialTransactionMock();

        $transaction->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(FinancialTransactionInterface::STATE_PENDING);

        $transaction->expects($this->atLeastOnce())
            ->method('getTrackingId')
            ->willReturn($paymentId);

        $transaction->expects($this->once())
            ->method('getReferenceNumber')
            ->willReturn(null);

        $this->defaultPlugin->approveAndDeposit($transaction, true);
    }

    /**
     * @test
     * @expectedException \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     * @expectedExceptionMessage Payment failed.
     */
    public function it_should_fail_payment_if_purchase_was_not_completed_succesfully()
    {
        $paymentId = 42;
        $referenceNumber = 12383098;
        $createdAt = new \DateTime();
        $targetAmount = 1200;
        $currency = 'PLN';

        $transaction = $this->createFinancialTransactionMock();
        $payment = $this->createPaymentInterfaceMock();
        $paymentInstruction = $this->createPaymentInstructionInterfaceMock();
        $completePurchaseRequest = $this->createCompletePurchaseRequestMock();
        $completePurchaseResponse = $this->createCompletePurchaseResponseMock();

        $transaction->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(FinancialTransactionInterface::STATE_PENDING);

        $transaction->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($payment);

        $payment->expects($this->atLeastOnce())
            ->method('getPaymentInstruction')
            ->willReturn($paymentInstruction);

        $transaction->expects($this->atLeastOnce())
            ->method('getTrackingId')
            ->willReturn($paymentId);

        $transaction->expects($this->once())
            ->method('getReferenceNumber')
            ->willReturn($referenceNumber);

        $transaction->expects($this->atLeastOnce())
            ->method('getCreatedAt')
            ->willReturn($createdAt);

        $payment->expects($this->atLeastOnce())
            ->method('getTargetAmount')
            ->willReturn($targetAmount);

        $paymentInstruction->expects($this->atLeastOnce())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->gateway->expects($this->once())
            ->method('completePurchase')
            ->willReturn($completePurchaseRequest);

        $completePurchaseRequest->expects($this->once())
            ->method('send')
            ->willReturn($completePurchaseResponse);

        $completePurchaseResponse->expects($this->atLeastOnce())
            ->method('isSuccessful')
            ->willReturn(false);

        $transaction->expects($this->once())
            ->method('setResponseCode')
            ->with('FAILED');

        $transaction->expects($this->once())
            ->method('setReasonCode')
            ->with('FAILED');

        $transaction->expects($this->once())
            ->method('setState')
            ->with(FinancialTransactionInterface::STATE_FAILED);

        $this->defaultPlugin->approveAndDeposit($transaction, true);
    }

    /**
     * @test
     */
    public function it_should_complete_the_payment()
    {
        $paymentId = 42;
        $referenceNumber = 12383098;
        $createdAt = new \DateTime();
        $targetAmount = 1200;
        $currency = 'PLN';

        $transaction = $this->createFinancialTransactionMock();
        $payment = $this->createPaymentInterfaceMock();
        $paymentInstruction = $this->createPaymentInstructionInterfaceMock();
        $completePurchaseRequest = $this->createCompletePurchaseRequestMock();
        $completePurchaseResponse = $this->createCompletePurchaseResponseMock();

        $transaction->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(FinancialTransactionInterface::STATE_PENDING);

        $transaction->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($payment);

        $payment->expects($this->atLeastOnce())
            ->method('getPaymentInstruction')
            ->willReturn($paymentInstruction);

        $transaction->expects($this->atLeastOnce())
            ->method('getTrackingId')
            ->willReturn($paymentId);

        $transaction->expects($this->once())
            ->method('getReferenceNumber')
            ->willReturn($referenceNumber);

        $transaction->expects($this->atLeastOnce())
            ->method('getCreatedAt')
            ->willReturn($createdAt);

        $payment->expects($this->atLeastOnce())
            ->method('getTargetAmount')
            ->willReturn($targetAmount);

        $paymentInstruction->expects($this->atLeastOnce())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->gateway->expects($this->once())
            ->method('completePurchase')
            ->willReturn($completePurchaseRequest);

        $completePurchaseRequest->expects($this->once())
            ->method('send')
            ->willReturn($completePurchaseResponse);

        $payment->expects($this->once())
            ->method('setState')
            ->with(PaymentInterface::STATE_APPROVED);

        $transaction->expects($this->once())
            ->method('setState')
            ->with(FinancialTransactionInterface::STATE_SUCCESS);

        $transaction->expects($this->once())
            ->method('setResponseCode')
            ->with(PluginInterface::RESPONSE_CODE_SUCCESS);

        $transaction->expects($this->once())
            ->method('setReasonCode')
            ->with(PluginInterface::REASON_CODE_SUCCESS);

        $this->defaultPlugin->approveAndDeposit($transaction, true);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Gateway
     */
    private function createGatewayMock()
    {
        return $this->getMockBuilder(Gateway::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FinancialTransaction
     */
    private function createFinancialTransactionMock()
    {
        return $this->getMockBuilder(FinancialTransaction::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentInterface
     */
    private function createPaymentInterfaceMock()
    {
        return $this->getMockBuilder(PaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentInstructionInterface
     */
    private function createPaymentInstructionInterfaceMock()
    {
        return $this->getMockBuilder(PaymentInstructionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtendedDataInterface
     */
    private function createExtendedDataInterfaceMock()
    {
        return $this->getMockBuilder(ExtendedDataInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PurchaseRequest
     */
    private function createPurchaseRequestMock()
    {
        return $this->getMockBuilder(PurchaseRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PurchaseResponse
     */
    private function createPurchaseResponseMock()
    {
        return $this->getMockBuilder(PurchaseResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CompletePurchaseRequest
     */
    private function createCompletePurchaseRequestMock()
    {
        return $this->getMockBuilder(CompletePurchaseRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CompletePurchaseResponse
     */
    private function createCompletePurchaseResponseMock()
    {
        return $this->getMockBuilder(CompletePurchaseResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
