<?php
/*
  $Id: coupons.php v1.0 2013-01-01 datazen $

  LoadedCommerce, Innovative eCommerce Solutions
  http://www.loadedcommerce.com

  Copyright (c) 2013 Loaded Commerce, LLC

  @author     LoadedCommerce Team
  @copyright  (c) 2013 LoadedCommerce Team
  @license    http://loadedcommerce.com/license.html
*/
class lC_Coupons {
  public $is_enabled = false;

  private $_contents = array();
  
  // class constructor
  public function __construct() {
    $this->is_enabled = (defined('MODULE_SERVICES_INSTALLED') && in_array('coupons', explode(';', MODULE_SERVICES_INSTALLED))) ? true : false;
    
    if ($this->is_enabled) {
      if ( !isset($_SESSION['lC_Coupons_data']) ) {
        $_SESSION['lC_Coupons_data'] = array('contents' => array());
      }

      $this->_contents =& $_SESSION['lC_Coupons_data']['contents'];     
    }
  }
  
  // public methods
  public function addEntry($code) {
    global $lC_Coupons, $lC_ShoppingCart, $lC_OrderTotal;
    
    $cInfo = $lC_Coupons->_getData($code);
         
    if (is_array($cInfo) && empty($cInfo) === false) {    
      if ($lC_Coupons->_isValid($cInfo)) {

        $name = $cInfo['name'];
        $discount = 10.00;

        $this->_contents[$code] = array('title' => $name . ' (' . $code . ')',
                                        'total' => $discount); 

        $lC_ShoppingCart->refresh(true);
        $this->_refreshCouponOrderTotals();

        return 1;                                              
      } else {
        // coupon not valid
        return -3;
      }
    
    } else {
      // coupon not found
      return -2;
    }   
          
  }
  
  public function removeEntry($code) {
    global $lC_ShoppingCart;
    
    if (array_key_exists($code, $this->_contents)) {    
      unset($this->_contents[$code]);
      $lC_ShoppingCart->refresh(true);
      $this->_refreshCouponOrderTotals();

    }    

    return true;
  }
  
  public function getTotalDiscount() {
    global $lC_ShoppingCart;
    
    $dTotal = 0;
    foreach ($this->_contents as $key => $module) {
      $dTotal += (float)$module['total'];
    }    

    return $dTotal;
  }
  
  public function reset() {
    $this->_contents = array();
  }
  
  public function getAll() {
    return $this->_contents;
  }
  
  public function redeem($code, $order_id) {
    global $lC_Database, $lC_Customer;
    
    if ($code == null) return false;
    
    $cInfo = $this->_getData($code);  
    
    if (isset($cInfo['coupons_id']) && empty($cInfo['coupons_id']) === false) {
    
      $Qchk = $lC_Database->query('select id from :table_coupons_redeemed where coupons_id = :coupons_id and order_id = :order_id limit 1)');
      $Qchk->bindTable(':table_coupons_redeemed', TABLE_COUPONS_REDEEMED);
      $Qchk->bindInt(':coupons_id', $cInfo['coupons_id']);
      $Qchk->bindInt(':order_id', $order_id);
      $Qchk->execute();
      
      if ($Qchk->numberOfRows() > 0) {
        $Qredeemed = $lC_Database->query('update :table_coupons_redeemed set coupons_id = :coupons_id, customers_id = :customers_id, redeem_date = now(), redeem_ip = :redeem_ip, order_id = :order_id where coupons_id = :coupons_id and order_id = :order_id ');
      } else {
        $Qredeemed = $lC_Database->query('insert into :table_coupons_redeemed (coupons_id, customers_id, redeem_date, redeem_ip, order_id) values (:coupons_id, :customers_id, now(), :redeem_ip, :order_id)');
      }
      
      $Qredeemed->bindTable(':table_coupons_redeemed', TABLE_COUPONS_REDEEMED);
      $Qredeemed->bindInt(':coupons_id', $cInfo['coupons_id']);
      $Qredeemed->bindInt(':customers_id', $lC_Customer->getID());
      $Qredeemed->bindValue(':redeem_ip', lc_get_ip_address());
      $Qredeemed->bindInt(':order_id', $order_id);
      $Qredeemed->execute();

      $Qchk->freeResult();
      
      return ( $Qredeemed->affectedRows() === 1 );    
    }
    
    return false;
  }
  
  public function hasContents() {
    return !empty($this->_contents);
  }  
  
  private function _getData($code, $status = 1) {
    global $lC_Database, $lC_Language;

    $Qcoupons = $lC_Database->query('select * from :table_coupons c left join :table_coupons_description cd on (c.coupons_id = cd.coupons_id) where c.code = :code and c.status = :status and cd.language_id = :language_id limit 1');
    $Qcoupons->bindTable(':table_coupons', TABLE_COUPONS);
    $Qcoupons->bindTable(':table_coupons_description', TABLE_COUPONS_DESCRIPTION);
    $Qcoupons->bindValue(':code', $code);
    $Qcoupons->bindInt(':status', $status);
    $Qcoupons->bindInt(':language_id', $lC_Language->getID());
    $Qcoupons->execute();   
    
    return (is_array($Qcoupons->toArray())) ? $Qcoupons->toArray() : false;     
  } 
  
  private function _isValid($cInfo) {
    return true;
  }   
  
  private function _calculate() {
    $ret = 10.00;
    
    return $ret;
  }  
   
  private function _refreshCouponOrderTotals() {
    global $lC_Coupons, $lC_ShoppingCart, $lC_Currencies;
    
    // remove coupon OT entries
    foreach ($lC_ShoppingCart->getOrderTotals() as $key => $module) {
      if ($module['code'] == 'coupon') {
        if (is_array($_SESSION['lC_ShoppingCart_data']['order_totals'][$key])) unset( $_SESSION['lC_ShoppingCart_data']['order_totals'][$key]);
      }
    }

    // add back the entries
    foreach ($lC_Coupons->getAll() as $code => $val) {
      if ($val['total'] > 0) {
        $_SESSION['lC_ShoppingCart_data']['order_totals'][] = array('code' => 'coupon',
                                                                    'title' => $val['title'],
                                                                    'text' => '<span onclick="removeCoupon(\'' . $code . '\');" style="padding:0; cursor:pointer;">' . lc_image(DIR_WS_CATALOG . 'templates/default/images/icons/16/cross_round.png', null, null, null, 'style="vertical-align:middle;"') . '&nbsp;' . $lC_Currencies->format($val['total']) . '</span>',
                                                                    'value' => $val['total'],
                                                                    'sort_order' => (int)MODULE_ORDER_TOTAL_COUPON_SORT_ORDER);
      }
    } 
    
    // sort the array
    $i = 0;
    $otArr = array();
    foreach ($lC_ShoppingCart->getOrderTotals() as $key => $module) {
      if ($module['code'] == 'coupon') {
        $sort = (int)MODULE_ORDER_TOTAL_COUPON_SORT_ORDER + $i;
        $i++;
      } else {
        $sort = $module['sort_order'];
      }
      $otArr[$sort] = $module;
    } 
    ksort($otArr); 
    
    $_SESSION['lC_ShoppingCart_data']['order_totals'] = $otArr;   
  }
}
?>