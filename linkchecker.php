<?php

error_reporting(0);
ini_set('display_errors', 0);

#error_reporting(E_ALL);
#ini_set('display_errors', E_ALL);

echo "test";

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Classes' . DIRECTORY_SEPARATOR . 'PHPExcel.php');

function check_parsed_url($url) {	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	
	curl_exec ($ch);
	
	$return = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	curl_close ($ch);
	
	return $return;
}

$urls = array(
	'URL_HERE'
);

$excel = new PHPExcel();
$sheet = $excel->getActiveSheet();

$row = 1;

foreach($urls as $url) {
	echo "Checking " . $url . "\n";
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$body = curl_exec($ch);
	
	curl_close($ch);
	
	$doc = new DOMDocument();
	$doc->loadHTML($body);
	
	$sheet->setCellValue('A' . $row, trim($doc->getElementsByTagName('title')->item(0)->nodeValue));
	$sheet->mergeCells('A' . $row . ':D' . $row);
	
	$sheet->setCellValue('A' . ++$row, $url);
	$sheet->mergeCells('A' . $row . ':D' . $row);
	
	$anchors = $doc->getElementsByTagName('a');
	
	foreach ($anchors as $anchor) {
		if ($anchor->hasAttribute('href')) {
			$href = $anchor->getAttribute('href');
			
			$parse = parse_url($href, PHP_URL_SCHEME);
			
			if ($parse !== null) {
				$check = check_parsed_url($href);
				
				if ((int)$check !== 200) {
					$sheet->setCellValue('A' . ++$row, "");
					$sheet->setCellValue('B' . $row, $href);
					$sheet->setCellValue('C' . $row, $anchor->nodeValue);
					$sheet->setCellValue('D' . $row, $check);
					
					echo "\t" . $href . "\t" . $anchor->nodeValue . "\t" . $check . "\n";
				}
			}
		}
	}
	
	++$row;
	
	echo "\n";
}

$writer = PHPExcel_IOFactory::createWriter($excel, "Excel2007");
$writer->save($url . "-scan-" . date('YmdHis') . ".xlsx");