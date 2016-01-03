<?php
/**
 * Created V/05/06/2015
 * Updated V/05/06/2015
 * Version 1
 *
 * Copyright 2015-2016 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * https://redmine.luigifab.info/projects/magento/wiki/paypalrefund
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL) as published
 * by the free software foundation, either version 2 of the license, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but without any warranty, without even the implied warranty of
 * merchantability or fitness for a particular purpose. See the
 * GNU General Public License (GPL) for more details.
 */

class Luigifab_Paypalrefund_Block_Adminhtml_Config_Help extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

	public function render(Varien_Data_Form_Element_Abstract $element) {
		$url = 'https://redmine.luigifab.info/projects/magento/wiki/paypalrefund';
		return '<p class="box">Luigifab/Paypalrefund '.$this->helper('paypalrefund')->getVersion().' <a href="'.$url.'" onclick="window.open(this.href); return false;" style="float:right;">'.$url.'</a></p>';
	}
}