<?php
/**
 * Created V/05/06/2015
 * Updated M/28/02/2017
 *
 * Copyright 2015-2017 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * https://www.luigifab.info/magento/paypalrefund
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

		if (Mage::getStoreConfigFlag('paypalrefund/general/enabled')) {
			$this->_canRefund = true;
			$this->_canRefundInvoicePartial = true;
		}

		return parent::__construct();
	}

	public function refund(Varien_Object $payment, $amount) {

		$order = $payment->getOrder();

		$storeId = $order->getStoreId();
		$captureTxnId = $payment->getData('parent_transaction_id') ?
			$payment->getData('parent_transaction_id') : $payment->getData('last_trans_id');

		if ($captureTxnId) {

			$version = 51;
			$source  = Mage::getStoreConfig('paypalrefund/general/source', $storeId);
			$admin   = Mage::getSingleton('admin/session')->getData('user')->getData('username');

			if ($source === 'paypal/wpp') {
				$username = Mage::getStoreConfig($source.'/api_username', $storeId);
				$password = Mage::getStoreConfig($source.'/api_password', $storeId);
				$signature = Mage::getStoreConfig($source.'/api_signature', $storeId);
				$url = (Mage::getStoreConfigFlag($source.'/sandbox_flag', $storeId)) ?
					'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';
			}
			else {
				$username = Mage::helper('core')->decrypt(Mage::getStoreConfig($source.'/api_username', $storeId));
				$password = Mage::helper('core')->decrypt(Mage::getStoreConfig($source.'/api_password', $storeId));
				$signature = Mage::helper('core')->decrypt(Mage::getStoreConfig($source.'/api_signature', $storeId));
				$url = (Mage::getStoreConfigFlag($source.'/api_sandbox', $storeId)) ?
					'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';
			}

			$canRefundMore = $order->canCreditmemo();
			$isFullRefund = !$canRefundMore && (($order->getData('base_total_online_refunded') + $order->getData('base_total_offline_refunded')) == 0);

			$params = array();
			$params[] = 'METHOD=RefundTransaction';
			$params[] = 'VERSION='.$version;
			$params[] = 'PWD='.$password;
			$params[] = 'USER='.$username;
			$params[] = 'SIGNATURE='.$signature;
			$params[] = 'TRANSACTIONID='.$captureTxnId;
			$params[] = 'CURRENCYCODE='.$order->getData('base_currency_code');
			$params[] = ($isFullRefund) ? 'REFUNDTYPE=Full' : 'REFUNDTYPE=Partial';
			$params[] = 'NOTE='.urlencode(Mage::helper('paypalrefund')->__('Refund completed by %s (ip: %s).', $admin, getenv('REMOTE_ADDR')));
			$params[] = 'AMT='.floatval($amount);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $params));
			$curl = curl_exec($ch);
			curl_close($ch);

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
				$payment->setData('transaction_id', $response['REFUNDTRANSACTIONID']);
				$payment->setData('is_transaction_closed', 1); // refund initiated by merchant
				$payment->setData('should_close_parent_transaction', !$canRefundMore);
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