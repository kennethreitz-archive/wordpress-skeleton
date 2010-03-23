<?php
/** 
 ** WARNING! DO NOT EDIT!
 **
 ** These templates are part of the core Shopp files 
 ** and will be overwritten when upgrading Shopp.
 **
 ** For editable templates, setup Shopp theme templates:
 ** http://docs.shopplugin.net/Setting_Up_Theme_Templates
 **
 **/
?>
<?php shopp('catalog','views','label=Views: '); ?>
<br class="clear" />
<?php shopp('catalog','featured-products','show=3&controls=false'); ?>
<?php shopp('catalog','onsale-products','show=3&controls=false'); ?>
<?php shopp('catalog','bestseller-products','show=3&controls=false'); ?>
<?php shopp('catalog','new-products','show=3&controls=false'); ?>
