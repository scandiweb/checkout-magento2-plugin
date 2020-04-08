<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Controller\Account;

/**
 * Class SaveCard
 */
class SaveCard extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var JsonFactory
     */
    public $jsonFactory;

    /**
     * @var UrlInterface
     */
    public $urlInterface;

    /**
     * @var VaultHandlerService
     */
    public $vaultHandler;

    /**
     * SaveCard constructor.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        \CheckoutCom\Magento2\Model\Service\VaultHandlerService $vaultHandler
    ) {
        parent::__construct($context);

        $this->messageManager = $messageManager;
        $this->jsonFactory = $jsonFactory;
        $this->urlInterface = $urlInterface;
        $this->vaultHandler = $vaultHandler;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        // Prepare the parameters
        $success = false;
        $url = $this->urlInterface->getUrl('vault/cards/listaction');
        $requestContent = explode("=", $this->getRequest()->getContent());
        if (isset($requestContent[1])) {
            $ckoCardToken = $requestContent[1];
        }

        // Process the request
        if ($this->getRequest()->isAjax() && !empty($ckoCardToken)) {
            // Save the card
            $result = $this->vaultHandler->setCardToken($ckoCardToken)
                ->setCustomerId()
                ->setCustomerEmail()
                ->authorizeTransaction();

            // Test the 3DS redirection case
            if (isset($result->response->_links['redirect']['href'])) {
                return $this->jsonFactory->create()->setData([
                    'success' => true,
                    'url' => $result->response->_links['redirect']['href']
                ]);
            } else {
                // Try to save the card
                $success = $result->saveCard();
            }
        }

        // Prepare the response UI message
        if ($success) {
            $this->messageManager->addSuccessMessage(
                        __('The payment card has been stored successfully.')
                    );
        } else {
            $this->messageManager->addErrorMessage(
                        __('The card could not be saved.')
                    );
        }

        // Build the AJAX response
        return $this->jsonFactory->create()->setData([
                'success' => $success,
                'url' => $url
            ]);
    }
}
