<?php
require("curlClient.php");
$supportedCommands = array("product" => array("search" => "searchProducts"));

// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['REQUEST_URI'],'/'));
//Remove query string
$lastElementRequest = count($request)-1;
$request[$lastElementRequest] = strtok($request[$lastElementRequest],'?');

$input = json_decode(file_get_contents('php://input'),true);

$api = array_shift($request);
if(strtolower($api) != "api") {
	header("Location: index.html");
	exit;
}

switch ($method) {
  case 'GET':
  	$input = $_GET;
  	$response = parseURI($request, $input, $supportedCommands);
  	if($response["valid"] == true) {
	  	header("HTTP/1.1 200");
	} else {
		header("HTTP/1.1 500");
	}
  	echo $response["message"];

  	break;
  case 'PUT':
	header("HTTP/1.1 500");
  	echo json_encode("Method Not Supported");
  	break;
  case 'POST':
  	$input = $_POST;
	header("HTTP/1.1 500");
  	echo json_encode("Method Not Supported");
  	break;
  case 'DELETE':
	header("HTTP/1.1 500");
  	echo json_encode("Method Not Supported");
  	break;
}

/**
* Parses the Uri to find the command to run
* @param $request String[] the list of commands to proceed down.
* @param $input String[] The parameters supplied by the user.
* @param $supportedCommands String[] The list of commands and their dependencies to validate against the request.
* @return String[] returns "valid" to indicate whether it was successful or not, and "message" to be returned to the user
*/
function parseURI($request, $input, $supportedCommands) {
	if(!is_array($request)) {
		return array("valid" => false, "message" => json_encode("Invalid command."));
	}

	$curCommand = array_shift($request);
	while(strtolower($curCommand) == "api")
		$curCommand = array_shift($request);
	
	if($curCommand == null) {
		return array("valid" => false, "message" => json_encode("Please enter a valid command."));
	}

	$command = $supportedCommands[strtolower($curCommand)];
	if($command) {
		if(!is_array($command)) {
			return $command($input);
		} else {
			return parseURI($request, $input, $command);
		}
	} else {
		return array("valid" => false, "message" => json_encode("Incomplete Request"));
	}
}

/**
* Searches for the products with the supplied store information
* @param $input String[] the product name supplied in the get parameter
* @return String[] returns "valid" to indicate whether it was successful or not, and "message" to be returned to the user
*/
function searchProducts($input) {
	$productName = $input["name"];
	$name = "";
	$cost = null;
	$cheapestStore = "";

	$apiKeys = array("walmart" => array("apiKey" => "rm25tyum3p9jm9x9x7zxshfa", "url" => "http://api.walmartlabs.com/v1/search?query={:productName:}&format=json&sort=price&ord=asc&apiKey={:apiKey:}"), "bestBuy" => array("apiKey" => "pfe9fpy68yg28hvvma49sc89", "url" => "http://api.bestbuy.com/v1/products((name={:productName:}*))?show=name,salePrice&pageSize=10&sort=salePrice.asc&format=json&apiKey={:apiKey:}"));
	foreach ($apiKeys as $store => $info) {
		$url = $info["url"];
		$url = str_replace("{:apiKey:}", $info["apiKey"], $url);
		$url = str_replace("{:productName:}", $productName, $url);
		$curlClient = new curlClient();

		try{
			$response = $curlClient->send($url, "GET");
		} catch(Exception $e) {
			continue;
		}

		$productList = json_decode($response["body"]);

		if($store == "walmart") {
			$productItems = $productList->items;
			
		} else {
			$productItems = $productList->products;
		}

		foreach ($productItems as $product) {
			if($product->name != null) {
				if($cost == null) {
					$name = $product->name;
					$cost = $product->salePrice;
					$cheapestStore = $store;
				} else if($product->salePrice < $cost) {
					$name = $product->name;
					$cost = $product->salePrice;
					$cheapestStore = $store;
				}
			}
		}
	}

	if($name != "") {
		return array("valid" => true, "message" => json_encode(array("bestPrice" => $cost, "location" => $cheapestStore, "productName" => $name, "currency" => "CAD")));
	} else {
		return array("valid" => true, "message" => json_encode("No Products found"));
	}
}