<?php

namespace ZipMoney\ZipMoneyPayment\Controller\Adminhtml\HealthCheck;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultFactory;

    /**
     * @var ZipMoney\ZipMoneyPayment\Model\Config\HealthCheck
     */
    protected $_healthCheck;

    // environment
    const SANDBOX = 'sandbox';
    const PRODUCTION = 'production';

    /**
     * @var \Zip\ZipPayment\Helper\Logger
     */
    protected $_logger;

    /**
     * Index constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \ZipMoney\ZipMoneyPayment\Model\Config\HealthCheck $healthCheck
     * @param \Zip\ZipPayment\Helper\Logger $logger
     */

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \ZipMoney\ZipMoneyPayment\Model\Config\HealthCheck $healthCheck,
        \Zip\ZipPayment\Helper\Logger $logger
    )
    {
        parent::__construct($context);
        $this->resultFactory = $resultJsonFactory;
        $this->_healthCheck = $healthCheck;
        $this->_logger = $logger;
    }

    /**
     * Ajax action for checking api credentials
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $apiKey = $this->getRequest()->getParam('apikey');
        $publicKey = $this->getRequest()->getParam('publickey');
        $environment = $this->getRequest()->getParam('env');
        if (preg_match('/^[\*]+$/m', $apiKey)) {
            $apiKey = null;
        }
        if (preg_match('/^[\*]+$/m', $publicKey)) {
            $publicKey = null;
        }
        $environmentList = $this->getEnvironmentList();
        if (!in_array($environment, $environmentList)) {
            $environment = null;
        }
        $websiteId = (int)$this->getRequest()->getParam('website', 0);
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create();
        try {
            $healthCheck = $this->_healthCheck->getHealthResult($websiteId, $apiKey, $publicKey,$environment);
            $response = $healthCheck;
        } catch (\Exception $e) {
            $response = ['error' => 'true', 'message' => $e->getMessage()];
            $this->_logger->debug($e->getMessage());
        }

        $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);
        return $resultJson->setData($response);
    }

    /**
     * get environment list
     * @return array
     */
    private function getEnvironmentList() {
        $result = array(
            self::PRODUCTION,
            self::SANDBOX
        );

        return $result;
    }

}
