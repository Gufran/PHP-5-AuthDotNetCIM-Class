<?php

/**
 * PHP 5 class to assist with Authorize.net Customer Information Manager (CIM)
 *
 * Requires cURL and SimpleXML extensions in PHP 5
 *
 * Version 0.1 on 24 Aug 2010
 * By Chris Blay (chris@meosphere.com)
 * Copyright (c) 2010 Meosphere (http://meosphere.com, http://meolabs.com)
 *
 * License: http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public License (LGPL)
 *
 * Please keep this header information here
 *
 */

class AuthDotNetCIM
{
	private $api_login_id;
	private $transaction_key;
	public $test_mode;
	public $debug_mode;
	public $error;
	
	public function __construct($api_login_id, $transaction_key, $test_mode = false, $debug_mode = false)
	{
		$this->api_login_id = $api_login_id;
		$this->transaction_key = $transaction_key;
		$this->test_mode = $test_mode;
		$this->debug_mode = $debug_mode;
		$this->error = '';
	}
	
	public function __call($name, $arguments)
	{
		// suppress warnings about the namespace
		$xml = @new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><' . $name . ' xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"></' . $name . '>');
		
		// add merchant authentication
		$xml->merchantAuthentication->name = $this->api_login_id;
		$xml->merchantAuthentication->transactionKey = $this->transaction_key;
		
		// add arguments
		$this->add_params($xml, $arguments[0]);
		
		// return communication result
		return $this->communicate($xml);
	}
	
	private function add_params($xml, $array)
	{
		// recursively add values from $array to $xml
		foreach ($array as $param => $value) {
			if (is_array($value)) {
				$xml->addChild($param);
				$this->add_params($xml->$param, $value);
			} else {
				$xml->$param = $value;
			}
		}
	}
	
	private function communicate(SimpleXMLElement $xml)
	{
		// determine proper url
		if ($this->test_mode) {
			$url = 'https://apitest.authorize.net/xml/v1/request.api';
		} else {
			$url = 'https://api.authorize.net/xml/v1/request.api';
		}
		
		// get xml string from object
		$xml = $xml->asXML();
		
		// debug
		$this->debug('about to send "xml" to "url"', $xml, $url);
		
		// do request via curl
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec($ch);
		
		// check for curl error
		if ($response === false) {
			$this->error = curl_error($ch)
			return false;
		}
		
		// close curl handle
		curl_close($ch);
		
		// suppress warnings about the namespace
		$xml = @new SimpleXMLElement($response);
		
		// check for simplexml error
		if ($xml === false) {
			$this->error = $response;
		}
		
		// debug
		$this->debug('"plain" response and "xml" response', $response, $xml);
		
		// return xml object
		return $xml;
	}
	
	private function debug()
	{
		// vardump all the args passed in if in debug mode
		if ($this->debug_mode) {
			echo '[DEBUG]';
			var_dump(func_get_args());
			echo '[/DEBUG]';
		}
	}
}

