<?php
/**
  @package    admin::modules
  @author     Loaded Commerce
  @copyright  Copyright 2003-2014 Loaded Commerce, LLC
  @copyright  Portions Copyright 2003 osCommerce
  @license    https://github.com/loadedcommerce/loaded7/blob/master/LICENSE.txt
  @version    $Id: low_stock.php v1.0 2013-08-08 datazen $
*/
global $lC_Vqmod;

if ( !class_exists('lC_Statistics') ) {
  include($lC_Vqmod->modCheck('includes/classes/statistics.php'));
}

class lC_Statistics_Low_Stock extends lC_Statistics {

  // Class constructor
  public function lC_Statistics_Low_Stock() {
    global $lC_Language;

    $lC_Language->loadIniFile('modules/statistics/low_stock.php');

    $this->_setIcon();
    $this->_setTitle();
  }

  // Private methods
  protected function _setIcon() {
    $this->_icon = lc_icon_admin('products.png');
  }

  protected function _setTitle() {
    global $lC_Language;

    $this->_title = $lC_Language->get('statistics_low_stock_title');
  }

  protected function _setHeader() {
    global $lC_Language;

    $this->_header = array($lC_Language->get('statistics_low_stock_table_heading_products'),
                           $lC_Language->get('statistics_low_stock_table_heading_level'));
  }

  protected function _setData() {
    global $lC_Database, $lC_Language;

    $this->_data = array();

    $this->_resultset = $lC_Database->query('select p.products_id, pd.products_name, products_quantity from :table_products p, :table_products_description pd where p.products_id = pd.products_id and pd.language_id = :language_id and p.products_quantity <= :stock_reorder_level order by p.products_quantity desc');
    $this->_resultset->bindTable(':table_products', TABLE_PRODUCTS);
    $this->_resultset->bindTable(':table_products_description', TABLE_PRODUCTS_DESCRIPTION);
    $this->_resultset->bindInt(':language_id', $lC_Language->getID());
    $this->_resultset->bindInt(':stock_reorder_level', STOCK_REORDER_LEVEL);
    $this->_resultset->setBatchLimit($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS);
    $this->_resultset->execute();

    while ( $this->_resultset->next() ) {
      $this->_data[] = array(lc_link_object(lc_href_link_admin(FILENAME_DEFAULT, 'products&pID=' . $this->_resultset->valueInt('products_id') . '&action=preview'), $this->_icon . '&nbsp;' . $this->_resultset->value('products_name')),
                             $this->_resultset->valueInt('products_quantity'));
    }
  }
}
?>