<?php
/**
 * Created V/05/06/2015
 * Updated J/26/09/2019
 *
 * Copyright 2015-2020 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
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

class Luigifab_Paypalrefund_Block_Adminhtml_Config_Help extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

	public function render(Varien_Data_Form_Element_Abstract $element) {

		$msg = $this->checkRewrites();
		if ($msg !== true)
			return sprintf('<p class="box">%s %s <span style="float:right;"><a href="https://www.%s">%3$s</a> | ⚠ IPv6</span></p>'.
				'<p class="box" style="margin-top:-5px; color:white; background-color:#E60000;"><strong>%s</strong><br />%s</p>',
				'Luigifab/Paypalrefund', $this->helper('paypalrefund')->getVersion(), 'luigifab.fr/magento/paypalrefund',
				$this->__('INCOMPLETE MODULE INSTALLATION'),
				$this->__('There is conflict (<em>%s</em>).', $msg));

		return sprintf('<p class="box">%s %s <span style="float:right;"><a href="https://www.%s">%3$s</a> | ⚠ IPv6</span></p>',
			'Luigifab/Paypalrefund', $this->helper('paypalrefund')->getVersion(), 'luigifab.fr/magento/paypalrefund');
	}

	private function checkRewrites() {

		$rewrites = [
			['block', 'paypal/standard_redirect'],
			['model', 'paypal/standard']
		];

		foreach ($rewrites as $rewrite) {
			if (($rewrite[0] == 'model') && (mb_stripos(get_class(Mage::getModel($rewrite[1])), 'luigifab') === false))
				return $rewrite[1];
			else if (($rewrite[0] == 'block') && (mb_stripos(get_class(Mage::getBlockSingleton($rewrite[1])), 'luigifab') === false))
				return $rewrite[1];
		}

		return true;
	}
}