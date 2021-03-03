<?php
/**
 * Created V/05/06/2015
 * Updated V/25/12/2020
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

class Luigifab_Paypalrefund_Model_Rewrite_Standard extends Mage_Paypal_Model_Standard {

	public function __construct() {

		if (Mage::getStoreConfigFlag('paypalrefund/general/enabled')) {
			$this->_canRefund = true;
			$this->_canRefundInvoicePartial = true;
		}

		parent::__construct();
	}

	public function refund(Varien_Object $payment, $amount) {

		$help  = Mage::helper('paypalrefund');
		$order = $payment->getOrder();

		$storeId   = $order->getStoreId();
		$captureId = $payment->getData('parent_transaction_id') ?: $payment->getData('last_trans_id');

		if (!empty($captureId)) {

			$ip = empty(getenv('HTTP_X_FORWARDED_FOR')) ? false : explode(',', getenv('HTTP_X_FORWARDED_FOR'));
			$ip = empty($ip) ? getenv('REMOTE_ADDR') : array_pop($ip);

			$source = Mage::getStoreConfig('paypalrefund/general/source', $storeId);
			if ($source == 'paypal/wpp') {
				$username  = Mage::getStoreConfig($source.'/api_username', $storeId);
				$password  = Mage::getStoreConfig($source.'/api_password', $storeId);
				$signature = Mage::getStoreConfig($source.'/api_signature', $storeId);
				$url = Mage::getStoreConfigFlag($source.'/sandbox_flag', $storeId) ?
					'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';
			}
			else {
				$username  = Mage::helper('core')->decrypt(Mage::getStoreConfig($source.'/api_username', $storeId));
				$password  = Mage::helper('core')->decrypt(Mage::getStoreConfig($source.'/api_password', $storeId));
				$signature = Mage::helper('core')->decrypt(Mage::getStoreConfig($source.'/api_signature', $storeId));
				$url = Mage::getStoreConfigFlag($source.'/api_sandbox', $storeId) ?
					'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';
			}

			$canRefundMore = $order->canCreditmemo();
			$isFull = !$canRefundMore && (($order->getData('base_total_online_refunded') + $order->getData('base_total_offline_refunded')) == 0);
			$params = [
				'METHOD=RefundTransaction',
				'VERSION=51',
				'PWD='.$password,
				'USER='.$username,
				'SIGNATURE='.$signature,
				'TRANSACTIONID='.$captureId,
				'CURRENCYCODE='.$order->getData('base_currency_code'),
				'REFUNDTYPE='.($isFull ? 'Full' : 'Partial'),
				'NOTE='.urlencode($help->__('Refund completed by %s (ip: %s).', $this->getUsername(), $ip)),
				'AMT='.((float) $amount)
			];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
			curl_setopt($ch, CURLOPT_TIMEOUT, 18);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $params));
			$result = curl_exec($ch);
			$result = ((curl_errno($ch) !== 0) || ($result === false)) ? trim('CURL_ERROR_'.curl_errno($ch).' '.curl_error($ch)) : $result;
			curl_close($ch);

			if (mb_stripos($result, 'CURL_ERROR_') !== false)
				Mage::throwException($help->__('Invalid response received from PayPal, please try again.').'<br />'.$result);

			$arr  = explode('&', $result);
			$data = [];

			foreach ($arr as $i => $value) {
				$tmp = (array) explode('=', $value); // (yes)
				if (count($tmp) > 1)
					$data[$tmp[0]] = urldecode($tmp[1]);
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
			if (mb_stripos($result, 'ACK=Success') !== false) {
				$payment->setData('transaction_id', $data['REFUNDTRANSACTIONID']);
				$payment->setData('is_transaction_closed', 1); // refund initiated by merchant
				$payment->setData('should_close_parent_transaction', !$canRefundMore);
			}
			else if (!empty($data['L_ERRORCODE0'])) {
				Mage::throwException($help->__('%s: %s. %s.', $data['L_ERRORCODE0'], $data['L_SHORTMESSAGE0'], $data['L_LONGMESSAGE0']));
			}
			else {
				Mage::throwException($help->__('Invalid response received from PayPal, please try again.'));
			}
		}
		else {
			Mage::throwException($help->__('Impossible to issue a refund transaction because the capture transaction does not exist.'));
		}

		return $this;
	}

	public function getUsername() {

		$file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$file = array_pop($file);
		$file = array_key_exists('file', $file) ? basename($file['file']) : '';

		// backend
		if ((PHP_SAPI != 'cli') && Mage::app()->getStore()->isAdmin() && Mage::getSingleton('admin/session')->isLoggedIn())
			$user = sprintf('admin %s', Mage::getSingleton('admin/session')->getData('user')->getData('username'));
		// cron
		else if (is_object($cron = Mage::registry('current_cron')))
			$user = sprintf('cron %d - %s', $cron->getId(), $cron->getData('job_code'));
		// xyz.php
		else if ($file != 'index.php')
			$user = $file;
		// full action name
		else if (is_object($action = Mage::app()->getFrontController()->getAction()))
			$user = $action->getFullActionName();
		// frontend
		else
			$user = sprintf('frontend %d', Mage::app()->getStore()->getData('code'));

		return $user;
	}
}