<?php
/**
 * Created V/05/06/2015
 * Updated V/12/02/2021
 *
 * Copyright 2015-2021 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/openmage/paypalrefund
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
			return sprintf('<p class="box">%s %s <span class="right"><a href="https://www.%s">%3$s</a> | ⚠ IPv6</span></p>'.
				'<p class="box" style="margin-top:-5px; color:white; background-color:#E60000;"><strong>%s</strong><br />%s</p>',
				'Luigifab/Paypalrefund', $this->helper('paypalrefund')->getVersion(), 'luigifab.fr/openmage/paypalrefund',
				$this->__('INCOMPLETE MODULE INSTALLATION'),
				$this->__('There is conflict (<em>%s</em>).', $msg));

		return sprintf('<p class="box">%s %s <span class="right"><a href="https://www.%s">%3$s</a> | ⚠ IPv6</span></p>',
			'Luigifab/Paypalrefund', $this->helper('paypalrefund')->getVersion(), 'luigifab.fr/openmage/paypalrefund');
	}

	private function checkRewrites() {

		$rewrites = [
			['block' => 'paypal/standard_redirect'],
			['model' => 'paypal/standard']
		];

		foreach ($rewrites as $rewrite) {
			foreach ($rewrite as $type => $class) {
				if (($type == 'model') && (mb_stripos(Mage::getConfig()->getModelClassName($class), 'luigifab') === false))
					return $class;
				else if (($type == 'block') && (mb_stripos(Mage::getConfig()->getBlockClassName($class), 'luigifab') === false))
					return $class;
				else if (($type == 'helper') && (mb_stripos(Mage::getConfig()->getHelperClassName($class), 'luigifab') === false))
					return $class;
			}
		}

		return true;
	}
}