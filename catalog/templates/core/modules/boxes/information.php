<?php
/**
  @package    catalog::templates::boxes
  @author     Loaded Commerce
  @copyright  Copyright 2003-2014 Loaded Commerce, LLC
  @copyright  Portions Copyright 2003 osCommerce
  @copyright  Template built on DevKit http://www.bootstraptor.com under GPL license 
  @license    https://github.com/loadedcommerce/loaded7/blob/master/LICENSE.txt
  @version    $Id: information.php v1.0 2013-08-08 datazen $
*/
?>
<!--modules/boxes/information.php start-->
<div class="well" >
  <ul class="box-information list-unstyled list-indent-large">
    <li class="box-header small-margin-bottom"><?php echo $lC_Box->getTitle(); ?></li>
    <?php echo $lC_Box->getContent(); ?>
  </ul>
</div>
<!--modules/boxes/information.php end-->