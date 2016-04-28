<?php
error_reporting(1);
include_once('./simple_html_dom.php');
$url = 'https://www.avito.ru/moskva/avtomobili';

function getProxy() {
	return null;
}

function getMobilePhone($href) {
	$ch = curl_init('https://m.avito.ru'.$href);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt ($ch, CURLOPT_ENCODING, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/3B48b Safari/419.3');
	curl_setopt( $ch, CURLOPT_COOKIEJAR,  'cookie.txt');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_COOKIEFILE,  'cookie.txt');
	curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
	curl_setopt($ch, CURLOPT_PROXY, getProxy());
	$code = curl_exec($ch);

	$html = str_get_html($code);
	$link = $html->find('.action-show-number', 0)->href;

	$ch = curl_init('https://m.avito.ru'.$link.'?async');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt( $ch, CURLOPT_COOKIEJAR,  'cookie.txt');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_COOKIEFILE,  'cookie.txt');
	curl_setopt($ch, CURLOPT_REFERER, 'https://m.avito.ru'.$href);
	curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
	curl_setopt($ch, CURLOPT_PROXY, getProxy());
	$json = json_decode(curl_exec($ch));
	
	$phone = preg_match('/8\ (.*)/', $json->phone, $matches);

	return str_replace(['-', ' '], '', $matches[1]);
}

function openPage($page) {
	$ch = curl_init('https://m.avito.ru'.$page);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt ($ch, CURLOPT_ENCODING, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/3B48b Safari/419.3');
	curl_setopt( $ch, CURLOPT_COOKIEJAR,  'cookie.txt');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_COOKIEFILE,  'cookie.txt');
	curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
	curl_setopt($ch, CURLOPT_PROXY, getProxy());
	$code = curl_exec($ch);

	$html = str_get_html($code);

	return $html;
}

function insertTovars($page)
{
	global $url;

	$html = file_get_html($url . '?p=' . $page);

	foreach($html->find('.item') as $item)
	{

		$a = $item->find('.title > a', 0);
		$title = trim($a->plaintext);
		$price = preg_match('/(.*)\ руб/', $item->find('.about', 0)->plaintext, $matches);

		$price = isset($matches[0]) ? str_replace(' ', '', $matches[1]) : 0;

		$data = $item->find('.data > p', 0)->plaintext;
		$date = $item->find('.data > .date', 0)->plaintext;

		$info = explode('|', $data);

		$hrefId = 'http://avito.ru' . $a->href;
		$htmlId = openPage($hrefId);
		$images = [];
		$phone = getMobilePhone($a->href);

		$itemId = file_get_html($hrefId);
		$type = $itemId->find('.description-expanded > .item-params', 0)->plaintext;
		$typeInfo = $itemId->find('.description-expanded > .item-params', 1)->plaintext;
		$seller = $itemId->find('#seller', 0)->children[0]->plaintext;
		$is_company = str_replace(' ', '', $itemId->find('.description_seller', 0)->plaintext) == 'Агентство' ? 1 : 0;


		$addr = $itemId->find('#toggle_map', 0)->plaintext;
		$region = 'Москва';

		$city = $itemId->find('span[itemprop="name"]', 0)->plaintext;

		//$description = $htmlId->find('#desc_text',0)->plaintext;

		if(isset($info[0]) && isset($info[1])) {
			$category = $info[0];
		} else {
			$category = $data;
		}

		$tovar = array(
			'avito_id' => $item->id,
			'title'  => $title, 
			'is_company' => $is_company,
			'price'  => $price,
			'date_avito' => $date,
			'description' => $description,
			'href'   => $hrefId,
			'seller' => $seller,
			'phone' => $phone,
			'city'  => $city,
			'region' => $region,
			'addr' => $addr,
			'type' => $type,
			'type_info' => $typeInfo,
			'images' => json_encode($images),
		);
		
		$string = '+7' . $tovar['phone'] . ' | ' . $tovar['region'] . ' | ' . $hrefId . '|' . $tovar['date_avito'] . PHP_EOL;

		file_put_contents('out.out', file_get_contents('out.out').$string);
		echo $string;
	}
}


$html = openPage($url);

$infoUrl = $html->find('.pagination-pages', 0)->last_child()->href;
$info = explode('?p=', $infoUrl);
$lastPage = $info[1];

for($page = 0; $page < $lastPage; $page++)
{
	insertTovars($page);
	sleep(mt_rand(1, 3));
}
