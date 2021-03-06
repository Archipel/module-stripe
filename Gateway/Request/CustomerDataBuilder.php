<?php
/**
 * Pmclain_Stripe extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the OSL 3.0 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Pmclain
 * @package   Pmclain_Stripe
 * @copyright Copyright (c) 2017-2018
 * @license   Open Software License (OSL 3.0)
 */

namespace Pmclain\Stripe\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Pmclain\Stripe\Gateway\Helper\SubjectReader;
use Stripe\Customer;

class CustomerDataBuilder implements BuilderInterface
{
    const CUSTOMER = 'customer';
    const FIRST_NAME = 'firstName';
    const LAST_NAME = 'lastName';
    const COMPANY = 'company';
    const EMAIL = 'email';
    const PHONE = 'phone';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var Session
     */
    private $customerSession;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    /**
     * CustomerDataBuilder constructor.
     * @param SubjectReader $subjectReader
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        SubjectReader $subjectReader,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->subjectReader = $subjectReader;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param array $subject
     * @return bool
     */
    public function build(array $subject)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);

        if (!$this->isSavePaymentInformation($paymentDataObject)) {
            return false;
        }
        $stripeCustomerId = $this->getStripeCustomerId();


        $order = $paymentDataObject->getOrder();
        $billingAddress = $order->getBillingAddress();

        return false;
    }

    /**
     * @param $paymentDataObject
     * @return bool
     */
    protected function isSavePaymentInformation($paymentDataObject)
    {
        $payment = $paymentDataObject->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();

        if (isset($additionalInfo['is_active_payment_token_enabler'])) {
            return $additionalInfo['is_active_payment_token_enabler'];
        }

        return false;
    }

    /**
     * @return bool|\Magento\Framework\Api\AttributeInterface|null
     */
    protected function getStripeCustomerId()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }

        $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
        $stripeCustomerId = $customer->getCustomAttribute('stripe_customer_id');

        if (!$stripeCustomerId) {
            $stripeCustomerId = $this->createNewStripeCustomer($customer->getEmail());
            $customer->setCustomAttribute(
                'stripe_customer_id',
                $stripeCustomerId
            );

            $this->customerRepository->save($customer);
        }

        return $stripeCustomerId;
    }

    /**
     * @param $email
     * @return mixed
     */
    protected function createNewStripeCustomer($email)
    {
        $result = Customer::create([
            'description' => 'Customer for ' . $email,
        ]);

        return $result->id;
    }

    /**
     * @param $stripeCustomerId
     */
    protected function verifyStripeCustomer($stripeCustomerId)
    {

    }
}
