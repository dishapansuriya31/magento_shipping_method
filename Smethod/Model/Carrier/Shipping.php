<?php
namespace Kitchen\Smethod\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Checkout\Model\Session as CheckoutSession;

class Shipping extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'simpleshipping';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;
    protected $checkoutSession;
    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * Shipping constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory
     * @param \Psr\Log\LoggerInterface                                    $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory                  $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array                                                       $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        CheckoutSession $checkoutSession,
        array $data = []
        
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * get allowed methods
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @return float
     */
    private function getShippingPrice()
    {
        $configPrice = $this->getConfigData('price');

        $shippingPrice = $this->getFinalPriceWithHandlingFee($configPrice);

        return $shippingPrice;
    }

   /**
 * @param RateRequest $request
 * @return bool|Result
 */
public function collectRates(RateRequest $request)
{
    if (!$this->getConfigFlag('active')) {
        return false;
    }

    $quote = $this->checkoutSession->getQuote();
    $shipType = $quote->getShippingType();
    if($shipType != 'Shipping'){
        return false;
    }
    
    /** @var \Magento\Shipping\Model\Rate\Result $result */
    $result = $this->_rateResultFactory->create();

    /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
    $method = $this->_rateMethodFactory->create();

    $method->setCarrier($this->_code);
    $method->setCarrierTitle($this->getConfigData('title'));

    $method->setMethod($this->_code);
    $method->setMethodTitle($this->getConfigData('name'));

    // Get the base shipping price
    $basePrice = (float)$this->getConfigData('price');

    // Get the handling fee percentage
    $handlingFeePercentage = (float)$this->getConfigData('handling_fee');

    // Get the cart subtotal
    $subtotal = $request->getBaseSubtotalInclTax();

    // Calculate the handling fee based on the percentage
    $handlingFee = ($basePrice * $handlingFeePercentage) / 100;

    // Calculate the discounted shipping price
    //$discountedPrice = $basePrice - $handlingFee;

    // Set the shipping price
    $method->setPrice($handlingFee);
    $method->setCost($handlingFee);

    $result->append($method);

    return $result;
}


}