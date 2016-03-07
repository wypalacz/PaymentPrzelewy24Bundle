<?php

namespace TicketSwap\Payment\Przelewy24Bundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use JMS\Payment\CoreBundle\Entity\FinancialTransaction;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\PluginController\PluginController;
use JMS\Payment\CoreBundle\PluginController\Result;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use TicketSwap\Payment\Przelewy24Bundle\Controller\NotificationController;

/**
 * @covers \TicketSwap\Payment\Przelewy24Bundle\Controller\NotificationController
 * @group FullCoverage
 */
class NotificationControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PluginController
     */
    private $pluginController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $crc;

    /**
     * @var NotificationController
     */
    private $notificationController;

    public function setUp()
    {
        $this->pluginController = $this->createPluginControllerMock();
        $this->entityManager = $this->createEntityManagerMock();
        $this->crc = '234p98573498398t39';

        $this->notificationController = new NotificationController(
            $this->pluginController,
            $this->entityManager,
            $this->crc
        );
    }

    /**
     * @test
     */
    public function it_should_fail_without_session_id()
    {
        $request = $this->createRequestMock();
        $requestParameters = $this->createParameterBagMock();
        $request->request = $requestParameters;

        $requestParameters->expects($this->once())
            ->method('has')
            ->with('p24_session_id')
            ->willReturn(false);

        $response = $this->notificationController->processNotification($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Failed: No session id', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_fail_if_signature_is_incorrect()
    {
        $sessionId = '12345-160302-154316';
        $orderId = 12383098;
        $amount = 1200;
        $currency = 'PLN';
        $signature = 'mf049jf30f94h34hf934hf9hfo';

        $request = $this->createRequestMock();
        $requestParameters = $this->createParameterBagMock();
        $request->request = $requestParameters;

        $requestParameters->expects($this->once())
            ->method('has')
            ->with('p24_session_id')
            ->willReturn(true);

        $requestParameters->expects($this->at(1))
            ->method('get')
            ->with('p24_session_id')
            ->willReturn($sessionId);

        $requestParameters->expects($this->at(2))
            ->method('get')
            ->with('p24_order_id')
            ->willReturn($orderId);

        $requestParameters->expects($this->at(3))
            ->method('get')
            ->with('p24_sign')
            ->willReturn($signature);

        $requestParameters->expects($this->at(4))
            ->method('get')
            ->with('p24_amount')
            ->willReturn($amount);

        $requestParameters->expects($this->at(5))
            ->method('get')
            ->with('p24_currency')
            ->willReturn($currency);

        $response = $this->notificationController->processNotification($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('[failed]', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_fail_if_an_unknown_exception_is_thrown()
    {
        $sessionId = '12345-160302-154316';
        $trackingId = '12345';
        $orderId = 12383098;
        $amount = 1200;
        $currency = 'PLN';
        $signature = '0b73c31fa6cc0bff1218c8e680895c5e';

        $request = $this->createRequestMock();
        $requestParameters = $this->createParameterBagMock();
        $request->request = $requestParameters;
        $financialTransactionRepository = $this->createEntityRepositoryMock();

        $requestParameters->expects($this->once())
            ->method('has')
            ->with('p24_session_id')
            ->willReturn(true);

        $requestParameters->expects($this->at(1))
            ->method('get')
            ->with('p24_session_id')
            ->willReturn($sessionId);

        $requestParameters->expects($this->at(2))
            ->method('get')
            ->with('p24_order_id')
            ->willReturn($orderId);

        $requestParameters->expects($this->at(3))
            ->method('get')
            ->with('p24_sign')
            ->willReturn($signature);

        $requestParameters->expects($this->at(4))
            ->method('get')
            ->with('p24_amount')
            ->willReturn($amount);

        $requestParameters->expects($this->at(5))
            ->method('get')
            ->with('p24_currency')
            ->willReturn($currency);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('JMS\Payment\CoreBundle\Entity\FinancialTransaction')
            ->willReturn($financialTransactionRepository);

        $financialTransactionRepository->expects($this->once())
            ->method('__call')
            ->with('findOneByTrackingId', [$trackingId])
            ->willThrowException(new \Exception('foo'));

        $response = $this->notificationController->processNotification($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('[failed]', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_fail_if_transaction_is_unknown()
    {
        $sessionId = '12345-160302-154316';
        $trackingId = '12345';
        $orderId = 12383098;
        $amount = 1200;
        $currency = 'PLN';
        $signature = '0b73c31fa6cc0bff1218c8e680895c5e';

        $request = $this->createRequestMock();
        $requestParameters = $this->createParameterBagMock();
        $request->request = $requestParameters;
        $financialTransactionRepository = $this->createEntityRepositoryMock();

        $requestParameters->expects($this->once())
            ->method('has')
            ->with('p24_session_id')
            ->willReturn(true);

        $requestParameters->expects($this->at(1))
            ->method('get')
            ->with('p24_session_id')
            ->willReturn($sessionId);

        $requestParameters->expects($this->at(2))
            ->method('get')
            ->with('p24_order_id')
            ->willReturn($orderId);

        $requestParameters->expects($this->at(3))
            ->method('get')
            ->with('p24_sign')
            ->willReturn($signature);

        $requestParameters->expects($this->at(4))
            ->method('get')
            ->with('p24_amount')
            ->willReturn($amount);

        $requestParameters->expects($this->at(5))
            ->method('get')
            ->with('p24_currency')
            ->willReturn($currency);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('JMS\Payment\CoreBundle\Entity\FinancialTransaction')
            ->willReturn($financialTransactionRepository);

        $financialTransactionRepository->expects($this->once())
            ->method('__call')
            ->with('findOneByTrackingId', [$trackingId])
            ->willReturn(null);

        $response = $this->notificationController->processNotification($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('[failed]', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_transaction_is_not_in_the_state_of_approving()
    {
        $sessionId = '12345-160302-154316';
        $trackingId = '12345';
        $orderId = 12383098;
        $amount = 1200;
        $currency = 'PLN';
        $signature = '0b73c31fa6cc0bff1218c8e680895c5e';

        $request = $this->createRequestMock();
        $requestParameters = $this->createParameterBagMock();
        $request->request = $requestParameters;
        $financialTransactionRepository = $this->createEntityRepositoryMock();
        $financialTransaction = $this->createFinancialTransactionMock();
        $payment = $this->createPaymentInterfaceMock();

        $requestParameters->expects($this->once())
            ->method('has')
            ->with('p24_session_id')
            ->willReturn(true);

        $requestParameters->expects($this->at(1))
            ->method('get')
            ->with('p24_session_id')
            ->willReturn($sessionId);

        $requestParameters->expects($this->at(2))
            ->method('get')
            ->with('p24_order_id')
            ->willReturn($orderId);

        $requestParameters->expects($this->at(3))
            ->method('get')
            ->with('p24_sign')
            ->willReturn($signature);

        $requestParameters->expects($this->at(4))
            ->method('get')
            ->with('p24_amount')
            ->willReturn($amount);

        $requestParameters->expects($this->at(5))
            ->method('get')
            ->with('p24_currency')
            ->willReturn($currency);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('JMS\Payment\CoreBundle\Entity\FinancialTransaction')
            ->willReturn($financialTransactionRepository);

        $financialTransactionRepository->expects($this->once())
            ->method('__call')
            ->with('findOneByTrackingId', [$trackingId])
            ->willReturn($financialTransaction);

        $financialTransaction->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($payment);

        $payment->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(PaymentInterface::STATE_NEW);

        $response = $this->notificationController->processNotification($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('[failed]', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_if_transaction_is_not_completed_yet()
    {
        $sessionId = '12345-160302-154316';
        $trackingId = '12345';
        $orderId = 12383098;
        $amount = 1200;
        $currency = 'PLN';
        $signature = '0b73c31fa6cc0bff1218c8e680895c5e';
        $paymentId = 42;

        $request = $this->createRequestMock();
        $requestParameters = $this->createParameterBagMock();
        $request->request = $requestParameters;
        $financialTransactionRepository = $this->createEntityRepositoryMock();
        $financialTransaction = $this->createFinancialTransactionMock();
        $payment = $this->createPaymentInterfaceMock();
        $result = $this->createResultMock();

        $requestParameters->expects($this->once())
            ->method('has')
            ->with('p24_session_id')
            ->willReturn(true);

        $requestParameters->expects($this->at(1))
            ->method('get')
            ->with('p24_session_id')
            ->willReturn($sessionId);

        $requestParameters->expects($this->at(2))
            ->method('get')
            ->with('p24_order_id')
            ->willReturn($orderId);

        $requestParameters->expects($this->at(3))
            ->method('get')
            ->with('p24_sign')
            ->willReturn($signature);

        $requestParameters->expects($this->at(4))
            ->method('get')
            ->with('p24_amount')
            ->willReturn($amount);

        $requestParameters->expects($this->at(5))
            ->method('get')
            ->with('p24_currency')
            ->willReturn($currency);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('JMS\Payment\CoreBundle\Entity\FinancialTransaction')
            ->willReturn($financialTransactionRepository);

        $financialTransactionRepository->expects($this->once())
            ->method('__call')
            ->with('findOneByTrackingId', [$trackingId])
            ->willReturn($financialTransaction);

        $financialTransaction->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($payment);

        $payment->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(PaymentInterface::STATE_APPROVING);

        $financialTransaction->expects($this->once())
            ->method('setReferenceNumber')
            ->with($orderId);

        $this->entityManager->expects($this->at(1))
            ->method('flush')
            ->with($payment);

        $this->entityManager->expects($this->at(2))
            ->method('flush')
            ->with($financialTransaction);

        $payment->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($paymentId);

        $financialTransaction->expects($this->atLeastOnce())
            ->method('getRequestedAmount')
            ->willReturn($amount);

        $this->pluginController->expects($this->once())
            ->method('approveAndDeposit')
            ->with($paymentId, $amount)
            ->willReturn($result);

        $result->expects($this->once())
            ->method('getStatus')
            ->willReturn(Result::STATUS_PENDING);

        $response = $this->notificationController->processNotification($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_and_close_payment_if_transaction_is_complete()
    {
        $sessionId = '12345-160302-154316';
        $trackingId = '12345';
        $orderId = 12383098;
        $amount = 1200;
        $currency = 'PLN';
        $signature = '0b73c31fa6cc0bff1218c8e680895c5e';
        $paymentId = 42;

        $request = $this->createRequestMock();
        $requestParameters = $this->createParameterBagMock();
        $request->request = $requestParameters;
        $financialTransactionRepository = $this->createEntityRepositoryMock();
        $financialTransaction = $this->createFinancialTransactionMock();
        $payment = $this->createPaymentInterfaceMock();
        $result = $this->createResultMock();
        $paymentInstruction = $this->createPaymentInstructionInterfaceMock();

        $requestParameters->expects($this->once())
            ->method('has')
            ->with('p24_session_id')
            ->willReturn(true);

        $requestParameters->expects($this->at(1))
            ->method('get')
            ->with('p24_session_id')
            ->willReturn($sessionId);

        $requestParameters->expects($this->at(2))
            ->method('get')
            ->with('p24_order_id')
            ->willReturn($orderId);

        $requestParameters->expects($this->at(3))
            ->method('get')
            ->with('p24_sign')
            ->willReturn($signature);

        $requestParameters->expects($this->at(4))
            ->method('get')
            ->with('p24_amount')
            ->willReturn($amount);

        $requestParameters->expects($this->at(5))
            ->method('get')
            ->with('p24_currency')
            ->willReturn($currency);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('JMS\Payment\CoreBundle\Entity\FinancialTransaction')
            ->willReturn($financialTransactionRepository);

        $financialTransactionRepository->expects($this->once())
            ->method('__call')
            ->with('findOneByTrackingId', [$trackingId])
            ->willReturn($financialTransaction);

        $financialTransaction->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($payment);

        $payment->expects($this->atLeastOnce())
            ->method('getState')
            ->willReturn(PaymentInterface::STATE_APPROVING);

        $financialTransaction->expects($this->once())
            ->method('setReferenceNumber')
            ->with($orderId);

        $this->entityManager->expects($this->at(1))
            ->method('flush')
            ->with($payment);

        $this->entityManager->expects($this->at(2))
            ->method('flush')
            ->with($financialTransaction);

        $payment->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($paymentId);

        $financialTransaction->expects($this->atLeastOnce())
            ->method('getRequestedAmount')
            ->willReturn($amount);

        $this->pluginController->expects($this->once())
            ->method('approveAndDeposit')
            ->with($paymentId, $amount)
            ->willReturn($result);

        $result->expects($this->once())
            ->method('getStatus')
            ->willReturn(Result::STATUS_SUCCESS);

        $payment->expects($this->atLeastOnce())
            ->method('getPaymentInstruction')
            ->willReturn($paymentInstruction);

        $this->pluginController->expects($this->once())
            ->method('closePaymentInstruction')
            ->with($paymentInstruction);

        $response = $this->notificationController->processNotification($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PluginController
     */
    private function createPluginControllerMock()
    {
        return $this->getMockBuilder(PluginController::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Request
     */
    private function createRequestMock()
    {
        return $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ParameterBag
     */
    private function createParameterBagMock()
    {
        return $this->getMockBuilder(ParameterBag::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
    
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityRepository
     */
    private function createEntityRepositoryMock()
    {
        return $this->getMockBuilder(EntityRepository::class)
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
     * @return \PHPUnit_Framework_MockObject_MockObject|Result
     */
    private function createResultMock()
    {
        return $this->getMockBuilder(Result::class)
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
}
