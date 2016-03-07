TicketSwapPaymentPrzelewy24Bundle
========================

A Symfony2 Bundle that provides access to the [Przelewy24](http://www.przelewy24.pl/en) API. Based on JMSPaymentCoreBundle.

[![Build Status](https://travis-ci.org/TicketSwap/PaymentPrzelewy24Bundle.svg?branch=master)](https://travis-ci.org/TicketSwap/PaymentPrzelewy24Bundle)

## Installation

### Step1: Require the package with Composer

````
composer require ticketswap/payment-przelewy24-bundle
````

### Step2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...

        new TicketSwap\Payment\Przelewy24Bundle\TicketSwapPaymentPrzelewy24Bundle(),
    );
}
```

### Step3: Configure

Add the following to your routing.yml:
```yaml
ticket_swap_payment_przewely24_notifications:
    pattern:  /webhook/przewely24
    defaults: { _controller: ticket_swap_payment_przelewy24.controller.notification:processNotification }
    methods:  [POST]
```

Add the following to your config.yml:
```yaml
ticket_swap_payment_przelewy24:
    merchant_id: Your Merchant ID
    pos_id:      Your pos ID (usually the same as Merchant ID) 
    crc:         Your CRC key
    test:        true/false   # Default true
    report_url:  https://host/webhook/przewely24
```

Make sure you set the `return_url` in the `predefined_data` for every payment method you enable:
````php
$form = $this->getFormFactory()->create('jms_choose_payment_method', null, array(
    'amount'   => $order->getAmount(),
    'currency' => 'EUR',
    'predefined_data' => array(
        'przewely24_checkout' => array(
            'return_url' => $this->generateUrl('order_complete', array(), true),
        ),
    ),
));
````
It's also possible to set a `description` for the transaction in the `predefined_data`.

See [JMSPaymentCoreBundle documentation](http://jmsyst.com/bundles/JMSPaymentCoreBundle/master/usage) for more info.
