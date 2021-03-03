<?php
/**
 * Created V/05/06/2015
 * Updated M/20/08/2019
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

class Luigifab_Paypalrefund_Model_Source {

	public function toOptionArray() {

		return [
			['value' => 'paypalrefund/general', 'label' => Mage::helper('paypalrefund')->__('Below')],
			['value' => 'paypal/wpp', 'label' => Mage::helper('paypalrefund')->__('From PayPal configuration')]
		];
	}
}