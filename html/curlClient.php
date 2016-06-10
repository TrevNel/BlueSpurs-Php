<?php

class curlClient {
	public function __construct() {

	}

	public function send($url, $method = "GET", $content, $additionalHeaders) {
		$handle = curl_init();
		switch($method)
		{
			case 'POST':
				curl_setopt($handle, CURLOPT_POST, true);
				curl_setopt($handle, CURLOPT_POSTFIELDS, $content);
			break;
			case 'PUT':
				$length = strlen($content);
				$pointer = fopen('php://memory', 'rw');
				fwrite($pointer, $content);
				rewind($pointer);

				curl_setopt($handle, CURLOPT_INFILE, $pointer);
				curl_setopt($handle, CURLOPT_INFILESIZE, $length);
				curl_setopt($handle, CURLOPT_PUT, true);
			break;
			case 'DELETE':
				curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
			break;
		}
		curl_setopt($handle, CURLOPT_HTTPHEADER, $additionalHeaders);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($handle, CURLOPT_URL, $url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_HEADER, true);
		curl_setopt($handle, CURLINFO_HEADER_OUT, true);
		curl_setopt($handle, CURLOPT_VERBOSE, true);

		$payload = curl_exec($handle);
		
		$request_header_info = curl_getinfo($handle, CURLINFO_HEADER_OUT);
		
		$information = curl_getinfo($handle);
		
		$header_size = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
		$raw_headers = substr($payload, 0, $header_size);
		$body = trim(substr($payload, $header_size));
		
		$headers = $this->http_parse_headers($raw_headers);
		$headers = array_merge($information, $headers);
		
		if($method == 'PUT')
			fclose($pointer);
		curl_close($handle);
		
		if($headers["http_code"] == 0){
			throw new Exception("Host is unreachable.",0);
		} elseif($headers["http_code"] == 302){
			$url = $headers['Location'];
			return $this->send($url, $method, $content, $additionalHeaders);
		}

		if($headers["http_code"] >= 400) {
			if(strlen($body) != 0 && $body != '') {
				if( strrpos($headers['Content-Type'], "json") != false)
				{
					$content = json_decode($body, true);
					$attributes = $content['attributes'];
				} elseif(strrpos($headers['Content-Type'], "xml") != false) {
					$content = new SimpleXmlElement($body);
					$attributes = $content->attributes();
				} else {
					$attributes = array();
					$attributes["message"] = $body;
				}
				
			} else {
				$attributes = array();
			}
			
			throw new Exception((string)$attributes["message"]);
		}
		return array("headers" => $headers, "body" => $body);
	}

	private function http_parse_headers($headers) {
		$retVal = array();
		$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $headers));
    	foreach($fields as $field) {
		    if(preg_match('/([^:]+): (.+)/m', $field, $match)) {
			    $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
			    if(isset($retVal[$match[1]])) {
				    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
    			} else {
				    $retVal[$match[1]] = trim($match[2]);
				}
			}
		}
		return $retVal;
	}
}