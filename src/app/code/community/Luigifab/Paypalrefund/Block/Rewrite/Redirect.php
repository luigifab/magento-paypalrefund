<?php
/**
 * Created D/08/07/2018
 * Updated M/26/11/2019
 *
 * Copyright 2015-2020 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
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

class Luigifab_Paypalrefund_Block_Rewrite_Redirect extends Mage_Paypal_Block_Standard_Redirect {

	protected function _construct() {
		$this->setModuleName('Mage_Paypal');
	}

	protected function _toHtml() {

		$html = parent::_toHtml();
		if (!Mage::getStoreConfigFlag('paypalrefund/general/redirect'))
			return $html;

		$locale = mb_substr(Mage::getStoreConfig('general/locale/code'), 0, 2);
		$html   = preg_replace('#<html[^>]*><body>#', '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="'.$locale.'"><head><title>PayPal</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Content-Language" content="'.$locale.'">
<meta name="robots" content="noindex,nofollow">
<link rel="icon" type="image/x-icon" href="'.$this->getSkinUrl('favicon.ico').'">
<style type="text/css">
* { margin:0; padding:0; cursor:progress; }
body { font:0.85em sans-serif; background-color:#DDD; overflow-y:scroll; }
div.box { display:flex; align-items:center; justify-content:center; flex-direction:column; height:70vh; color:#333; }
h1 { padding:0.5em; }
span.field-row { display:none; }
</style></head><body><div class="box"><h1>PayPal</h1>', $html);

		return str_replace(['</body>', ' name=""'], ['</div></body>', ''], $html);
	}
}