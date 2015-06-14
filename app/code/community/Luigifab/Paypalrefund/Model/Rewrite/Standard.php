<?php
/**
 * Created V/05/06/2015
 * Updated L/08/06/2015
 * Version 3
 *
 * Copyright 2015 | Fabrice Creuzot (luigifab) <code~luigifab~info>
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

class Luigifab_Paypalrefund_Model_Rewrite_Standard extends Mage_Paypal_Model_Standard {

	//protected $_canRefund               = true;
	//protected $_canRefundInvoicePartial = true;

	public function __construct() {

		if (Mage::getStoreConfig('paypalrefund/general/enabled') === '1') {
			$this->_canRefund = true;
			$this->_canRefundInvoicePartial = true;
		}

		return parent::__construct();
	}

	public function refund(Varien_Object $payment, $amount) {

		$order = $payment->getOrder();
		$captureTxnId = $payment->getParentTransactionId() ? $payment->getParentTransactionId() : $payment->getLastTransId();

		if ($captureTxnId) {

			$ip = (getenv('REMOTE_ADDR') !== false) ? getenv('REMOTE_ADDR') : '?';
			$admin = Mage::getSingleton('admin/session')->getUser()->getUsername();

			$version = 51;
			$username = Mage::getStoreConfig('paypalrefund/general/api_username', $order->getStoreId());
			$password = Mage::getStoreConfig('paypalrefund/general/api_password', $order->getStoreId());
			$signature = Mage::getStoreConfig('paypalrefund/general/api_signature', $order->getStoreId());
			$url = (Mage::getStoreConfig('paypalrefund/general/api_sandbox', $order->getStoreId()) === '1') ?
				'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';

			$canRefundMore = $order->canCreditmemo();
			$isFullRefund = !$canRefundMore && (($order->getBaseTotalOnlineRefunded() + $order->getBaseTotalOfflineRefunded()) == 0);

			$params = array();
			$params[] = 'METHOD=RefundTransaction';
			$params[] = 'VERSION='.$version;
			$params[] = 'PWD='.$password;
			$params[] = 'USER='.$username;
			$params[] = 'SIGNATURE='.$signature;
			$params[] = 'TRANSACTIONID='.$captureTxnId;
			$params[] = 'CURRENCYCODE='.$order->getBaseCurrencyCode();
			$params[] = ($isFullRefund) ? 'REFUNDTYPE=Full' : 'REFUNDTYPE=Partial';
			$params[] = 'NOTE='.urlencode(Mage::helper('paypalrefund')->__('Refund from %s (ip: %s).', $admin, $ip));
			$params[] = 'AMT='.floatval($amount);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $params));
			$curl = curl_exec($ch);

			$data = explode('&', $curl);
			$response = array();

			foreach ($data as $i => $value) {
				$tmp = explode('=', $value);
				if (count($tmp) > 1)
					$response[$tmp[0]] = urldecode($tmp[1]);
			}

			// REFUNDTRANSACTIONID=5JY40024A6126543H
			// &FEEREFUNDAMT=0%2e04
			// &GROSSREFUNDAMT=1%2e00
			// &NETREFUNDAMT=0%2e96
			// &CURRENCYCODE=EUR
			// &TIMESTAMP=2015%2d06%2d06T15%3a54%3a52Z
			// &CORRELATIONID=35a205e664fb
			// &ACK=Success
			// &VERSION=51
			// &BUILD=16915562
			if (strpos($curl, 'ACK=Success') !== false) {
				$payment->setTransactionId($response['REFUNDTRANSACTIONID']);
				$payment->setIsTransactionClosed(1); // refund initiated by merchant
				$payment->setShouldCloseParentTransaction(!$canRefundMore);
			}
			else {
				Mage::throwException(Mage::helper('paypalrefund')->__('%s: %s. %s.', $response['L_ERRORCODE0'], $response['L_SHORTMESSAGE0'], $response['L_LONGMESSAGE0']));
			}
		}
		else {
			Mage::throwException(Mage::helper('paypal')->__('Impossible to issue a refund transaction because the capture transaction does not exist.'));
		}

		return $this;
	}
}