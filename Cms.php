<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

require_once 'abstract.php';

/**
 * Magento Log Shell Script
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Shell_Cms extends Mage_Shell_Abstract
{
    
    public function __construct() {
        parent::__construct();
        // Time limit to infinity
        set_time_limit(0);
    }

    /**
     * Run script
     *
     */
    public function run()
    {
        if ($this->getArg('export')) {
            switch ($this->getArg('type')):
                case 'pages':
                    $this->exportCmsPages();
                    break;
                case 'blocks':
                    $this->exportCmsBlocks();
                    break;
                default:
                    $this->exportCmsPages();
                    $this->exportCmsBlocks();
                    break;
            endswitch;
        } elseif ($this->getArg('import')) {
            switch ($this->getArg('type')):
                case 'pages':
                    $this->importCmsPages();
                    break;
                case 'blocks':
                    $this->importCmsBlocks();
                    break;
                default:
                    $this->importCmsBlocks();
                    $this->importCmsPages();
                    break;
            endswitch;

        }
    }

    /**
     * Export CMS Page
     */
    private function exportCmsPages()
    {
        $exportPath = $this->_getRootPath().'var'.DS.'cms'.DS.'export'.DS;

        if (!file_exists($exportPath)) {
            mkdir($exportPath, 0777, true);
        }
        $storeId = $this->getArg('store_id');

        if ($storeId != '') {
            $cmsPageCollection = Mage::getModel('cms/page')->getCollection()->addStoreFilter($storeId, false);
        } else {
            $cmsPageCollection = Mage::getModel('cms/page')->getCollection();
        }

        $pageToStoreTable = Mage::getSingleton('core/resource')->getTableName('cms/page_store');
        $cmsPageCollection->getSelect()
            ->join(
                array('page_to_store'=> $pageToStoreTable),
                'main_table.page_id = page_to_store.page_id',
                'page_to_store.store_id AS store_id');

        $file_name = 'cms_pages'.$storeId.'.csv';

        $file = fopen($exportPath.$file_name, 'w+');
        $columnNames = array_keys($cmsPageCollection->getFirstItem()->getData());
        fputcsv($file,  $columnNames);
        foreach($cmsPageCollection as $page){
            fputcsv($file,  $page->getData());
        }
        fclose($file);

        echo 'CMS pages exported in store '.$storeId.' file name: '.$file_name.PHP_EOL;
    }

    /**
     * Export CMS Block
     */
    private function exportCmsBlocks()
    {
        $exportPath = $this->_getRootPath().'var'.DS.'cms'.DS.'export'.DS;

        if (!file_exists($exportPath)) {
            mkdir($exportPath, 0777, true);
        }
        $storeId = $this->getArg('store_id');

        if ($storeId != '') {
            $cmsBlocksCollection = Mage::getModel('cms/block')->getCollection()->addStoreFilter($storeId, false);
        } else {
            $cmsBlocksCollection = Mage::getModel('cms/block')->getCollection();
        }
        //Export blocks
        $blockToStoreTable = Mage::getSingleton('core/resource')->getTableName('cms/block_store');
        $cmsBlocksCollection->getSelect()
            ->join(
                array('block_to_store' => $blockToStoreTable),
                'main_table.block_id = block_to_store.block_id',
                'block_to_store.store_id AS store_id'
            );

        $file_name = 'cms_blocks'.$storeId.'.csv';

        $file = fopen($exportPath.$file_name, 'w+');
        $columnNames = array_keys($cmsBlocksCollection->getFirstItem()->getData());
        fputcsv($file,  $columnNames);
        foreach($cmsBlocksCollection as $block){
            fputcsv($file,  $block->getData());
        }
        fclose($file);

        echo 'CMS blocks exported in store '.$storeId.' file name: '.$file_name.PHP_EOL;
    }

    /**
     * Import CMS Page
     */
    private function importCmsPages()
    {
        $importPath = $this->_getRootPath().'var'.DS.'cms'.DS.'import'.DS;

        if (!file_exists($importPath)) {
            mkdir($importPath, 0777, true);
        }

        if ($this->getArg('file_name') != '') {
            $fileName = $this->getArg('file_name').'.csv';
        } else {
            $fileName = 'cms_pages.csv';
        }

        $file = fopen($importPath.$fileName, 'r');
        if($file === false){
            echo "importCmsPages: File does not exist ".$importPath.$fileName.PHP_EOL;
            return;
        }
        $isVerbose = $this->getArg('v') ? true : false;
        $customStoreId = $this->getArg('store_id');
        $columnNames = fgetcsv($file);
        $counter = $counterSkipped = $counterRewrited = $counterCreated = 0;
        while (($lineAr = fgetcsv($file)) !== FALSE) {
            if(empty($lineAr)) { continue; }
            $lineAr = array_combine($columnNames, $lineAr);
            $storeId = $customStoreId ? $customStoreId : $lineAr['store_id'];
            unset($lineAr['page_id']);
            if($this->isPageExists($lineAr, $storeId)){
                if($this->getArg('rewrite')){
                    $rewritedPage = Mage::getModel('cms/page')
                        ->setStore(array($storeId))
                        ->load($lineAr['identifier'], 'identifier');

                    $rewritedPage->addData($lineAr)
                        ->save();
                    if($isVerbose) {
                        echo 'Page with identifier '.$lineAr['identifier'].' was rewrited.'.PHP_EOL;
                    }
                    $counterRewrited++;
                } else {
                    if($isVerbose) {
                        echo 'Page with identifier ' . $lineAr['identifier'] . ' was skipped.' . PHP_EOL;
                    }
                    $counter++;
                    $counterSkipped++;
                    continue;
                }
            } else {
                Mage::getModel('cms/page')
                    ->setStores(array($storeId))
                    ->addData($lineAr)
                    ->save();
                if($isVerbose) {
                    echo 'Page with identifier '.$lineAr['identifier'].' was created.'.PHP_EOL;
                }
                $counterCreated++;
            }
            $counter++;
        }
        echo 'Pages import summary:'.PHP_EOL;
        echo $counterRewrited.' pages rewrited.'.PHP_EOL;
        echo $counterSkipped.' pages skipped.'.PHP_EOL;
        echo $counterCreated.' pages created.'.PHP_EOL;
        echo $counter.' pages from csv processed.'.PHP_EOL.PHP_EOL;
        fclose($file);
    }

    /**
     * Check identifier page exists
     *
     * @param $lineAr
     * @param $store
     *
     * @return bool
     */
    private function isPageExists($lineAr, $store) {
        $size = Mage::getResourceModel('cms/page_collection')
            ->addFieldToFilter('identifier', array('eq' => $lineAr['identifier']))
            ->addStoreFilter($store)
            ->getSize()
        ;
        return $size > 0 ? true : false;
    }

    /**
     * Import CMS Block
     */
    private function importCmsBlocks()
    {
        $importPath = $this->_getRootPath().'var'.DS.'cms'.DS.'import'.DS;
        if (!file_exists($importPath)) {
            mkdir($importPath, 0777, true);
        }
        
        if ($this->getArg('file_name') != '') {
            $fileName = $this->getArg('file_name').'.csv';
        } else {
            $fileName = 'cms_pages.csv';
        }
        
        $file = fopen($importPath.$fileName, 'r');
        if($file === false){
            echo "importCmsBlocks: File does not exist ".$importPath.$fileName.PHP_EOL;
            return;
        }
        $isVerbose = $this->getArg('v') ? true : false;
        $customStoreId = $this->getArg('store_id');
        $columnNames = fgetcsv($file);
        $counter = $counterSkipped = $counterRewrited = $counterCreated = 0;
        while (($lineAr = fgetcsv($file)) !== FALSE) {
            if(empty($lineAr)) { continue; }
            $lineAr = array_combine($columnNames, $lineAr);
            $storeId = $customStoreId ? $customStoreId : $lineAr['store_id'];
            unset($lineAr['block_id']);
            if($this->isBlockExists($lineAr, $storeId)){
                if($this->getArg('rewrite')){
                    $rewritedBlock = Mage::getModel('cms/block')
                        ->setStore(array($storeId))
                        ->load($lineAr['identifier'], 'identifier');
                    try{
                        $rewritedBlock
                            ->addData($lineAr)
                            ->save();
                    } catch (Mage_Core_Exception $e) {
                        echo 'Exception: block identifier '.$lineAr['identifier'].'. Message:';
                        Zend_Debug::dump($e->getMessage());
                    }
                    if($isVerbose) {
                        echo 'Block with identifier '.$lineAr['identifier'].' was rewrited.'.PHP_EOL;
                    }
                    $counterRewrited++;
                } else {
                    if($isVerbose) {
                        echo 'Block with identifier '.$lineAr['identifier'].' was skipped.'.PHP_EOL;
                    }
                    $counter++;
                    $counterSkipped++;
                    continue;
                }
            } else {
                Mage::getModel('cms/block')
                    ->addData($lineAr)
                    ->setStores(array($storeId))
                    ->save();
                if($isVerbose) {
                    echo 'Block with identifier '.$lineAr['identifier'].' was created.'.PHP_EOL;
                }
                $counterCreated++;
            }
            $counter++;
        }
        echo 'Blocks import summary:'.PHP_EOL;
        echo $counterRewrited.' blocks rewrited.'.PHP_EOL;
        echo $counterSkipped.' blocks skipped.'.PHP_EOL;
        echo $counterCreated.' blocks created.'.PHP_EOL;
        echo $counter.' blocks from csv processed.'.PHP_EOL.PHP_EOL;
        fclose($file);
    }

    /**
     * Check identifier block exists
     *
     * @param $lineAr
     * @param $store
     *
     * @return bool
     */
    private function isBlockExists($lineAr, $store) {
        $size = Mage::getResourceModel('cms/block_collection')
            ->addFieldToFilter('identifier', array('eq' => $lineAr['identifier']))
            ->addStoreFilter($store)
            ->getSize()
        ;
        return $size > 0 ? true : false;
    }

    /**
     * Usage instructions
     * 
     * @return string
     */
    public function usageHelp()
    {
        return <<<USAGE
    This file (Cms.php) must be placed in "shell" folder.
    Before import place csv files that were obtained during export process into var/cms/import
    Usage:  php -f Cms.php -- [options]
    Export options
    export                          Export blocks and pages into folder var/cms/export into files cms_blocks.csv and cms_pages.csv
        --type <type>               pages/blocks
        --store_id <id>             Store view id in which all pages and block will be imported
    Import options
    import                          Start import cms.
        --type <type>               pages/blocks
        rewrite                     Rewrite blocks/pages that already exist
        --store_id <id>             Store view id in which all pages and block will be imported
        -v                          Verbose option. Give you more information about import process
    Other options
    help, h                         This help
    Examples:
    php -f Cms.php -- export --type blocks --store_id 2             //Export blocks in store 2 to var/cms/export/cms_blocks.csv accordingly
    php -f Cms.php -- export --type pages --store_id 2              //Export pages in store 2 to var/cms/export/cms_pages.csv accordingly
    php -f Cms.php -- import                                        //Import blocks/pages with skipping on identifier match because 'rewrite' option doesn't use
    php -f Cms.php -- import rewrite                                //Import with rewrite. Use it if you want to update blocks and pages
    php -f Cms.php -- import --store_id 2                           //Import all to store view with id 2 without rewriting
    php -f Cms.php -- import --store_id 2 --file_name cms_pages4    //Import all to store view with id 2 without file name is cms_page4.csv
USAGE;
    }
   
}

$shell = new Mage_Shell_Cms();
$shell->run();
