<?php
namespace ZipMoney\ZipMoneyPayment\Setup;

use Magento\Customer\Model\Customer;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\UrlRewrite\Model\UrlRewrite;
/**
 * @category  Zipmoney
 * @package   Zipmoney_ZipmoneyPayment
 * @author    Sagar Bhandari <sagar.bhandari@zipmoney.com.au>
 * @copyright 2017 zipMoney Payments Pty Ltd.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.zipmoney.com.au/
 */

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    protected $categorySetupFactory;

    protected $urlRewrite;

    /**
     * Init
     *
     */
    public function __construct(
        UrlRewrite $urlRewrite
    ) {
        $this->urlRewrite   = $urlRewrite;

    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        $rewriteCollection = $this->urlRewrite->getCollection()
                                              ->addFieldToFilter('target_path', "zipmoney");

        if (count($rewriteCollection) > 0) 
            return false;

        $this->urlRewrite
                ->setIsSystem(0)
                ->setStoreId(1)
                ->setIdPath('zipmoney-landingpage')
                ->setTargetPath("zipmoney")
                ->setRequestPath('zipmoney')
                ->save();

        /**  
          * Install  Status and States
          * 
          */

        $statuses = [
            'zip_authorised' => __('zipMoney Authorised')
        ];

        /**
         * Check if status exists already
         */
        $new = [];
        $data = [];
        foreach ($statuses as $key => $label ) {
            $select = $setup->getConnection()->select()
              ->from(array('e' => $setup->getTable('sales_order_status')))
              ->where("e.status=?", $key);
            $result = $setup->getConnection()->fetchAll($select);             
            if (!$result) {           
              $new[$key] = $label;
            }
        }

        foreach ($new as $code => $info) {
            $data[] = ['status' => $code, 'label' => $info];
        }

        if(count($data)>0){
          $setup->getConnection()->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);
        }

        // Order States
        $states = array(
          array('zip_authorised', 'pending_payment', 0)
        );


        /**
         * Check if state exists already
         */
        $new = [];        
        $data = [];

        foreach ($states as $status) {
          $select = $setup->getConnection()->select()
                          ->from(array('e' => $setup->getTable('sales_order_status_state')))
                          ->where("e.status=?", $status[0]);
          $result = $setup->getConnection()->fetchAll($select);
          if (!$result) {          
            $new[] = $status;
          }
       }
      

      if(count($new)>0) {
        $setup->getConnection()->insertArray(
            $setup->getTable('sales_order_status_state'),
            ['status', 'state', 'is_default'],
            $new
        );
      }



      $setup->endSetup();
    }

}