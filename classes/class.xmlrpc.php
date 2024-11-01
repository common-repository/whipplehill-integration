<?php
class XMLRPClientWH
{

	var $XMLRPCURL = "";
	var $UserName  = "";
	var $PassWord = "";
	
	// constructor
    public function __construct($xmlrpcurl, $username, $password) 
	{
        $this->XMLRPCURL = $xmlrpcurl;
		$this->UserName  = $username;
		$this->PassWord = $password;
       
    }
	function send_request($requestname, $params) 
	{
		//$request = xmlrpc_encode_request($requestname, $params);
		$request ='<?xml version="1.0" encoding="iso-8859-1"?>
<methodCall>
<methodName>wh.getAllSites</methodName>
<params>
 <param>
  <value>
   <int>1</int>
  </value>
 </param>
</params>
</methodCall>';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_URL, $this->XMLRPCURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		$results = curl_exec($ch);
		curl_close($ch);
		return $results;
	}
	
	function get_all_sites()
	{
		$params = array();
		return $this->send_request('wh.getAllSites',$params);
	}

}