<?php
/**
 * Created S/12/01/2019
 * Updated S/12/01/2019
 *
 * Copyright 2015-2019 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/magento/paypalrefund
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

use Varien_Data_Form_Element_Renderer_Interface as Varien_DFER_Interface;
class Luigifab_Paypalrefund_Block_Adminhtml_Config_Obscure extends Mage_Adminhtml_Block_System_Config_Form_Field implements Varien_DFER_Interface {

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
		return str_replace('type="password"', 'type="text" autocomplete="off"', parent::_getElementHtml($element));
	}
}