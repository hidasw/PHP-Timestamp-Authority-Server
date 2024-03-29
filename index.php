<?php
// Script writted By Hida <hidactive@gmail.com>
// Last change at 14:30 Sore Senin 23 Maret 2009 req not based of content-type header,
// but based on req structure
// Last script update 20:56 Sore 23/03/2009
// Last script update 10:46 Esuk 09/07/2009
// Last script update 15:03 Sore 29/08/2009
// Last script update 03:32 Esuk 03/09/2009
// Last script update 7:24 PM 8/20/2018
// Last script update Minggu 14 Mei 2023 21:05:22 Sore
//  Selasa 16 Mei 2023 15:18:14 Sore
// error_reporting(0);
require_once(realpath('.').'/inc/tsa_log.php');
require_once(realpath('.').'/inc/tsa_config.php');
$cfg = tsa_config('tsa.cfg');
if(!is_array($cfg)) {
  tsalog("Configuration file error: $cfg", 'e');
  header("HTTP/1.0 500 Internal Server Error");
  exit;
}
//$req = $HTTP_RAW_POST_DATA;
$req = file_get_contents("php://input");
// testing purpose
//$req = file_get_contents("log/rq.der");

tsalogfile($req, 'req.der');



if(empty($req) || strlen($req) < 39) {
  tsalog("malformedRequest: request length (".strlen($req).") < 39 char. User agent: {$_SERVER["HTTP_USER_AGENT"]}", 'i');
   header("HTTP/1.0 403 Forbidden");
  exit;
}

require_once(realpath('.').'/inc/tsa_function.php');
require_once(realpath('.').'/inc/tsa_genOid.php');
if(!defined('OBJ_'.TSA_HASHALGORITHM)) {
  tsalog("Configuration file error: unknown algorithm: ".TSA_HASHALGORITHM, 'e');
  header("HTTP/1.0 500 Internal Server Error");
  exit;
}

$certsdir=realpath('certs');
$extracertsdir=realpath('certs/extracerts');
$crlsdir=realpath('certs/crls');
$signer = file_get_contents($certsdir."/".TSA_SIGNERCERT);
if(openssl_x509_read($signer) && openssl_pkey_get_private($signer)) {
  $TSA['signer'] = $signer;
} else {
  tsalog("openssl_x509_read signer error!! ".TSA_HASHALGORITHM, 'e');
  header("HTTP/1.0 500 Internal Server Error");
  exit;
}

if ($handle = opendir($extracertsdir)) {
  while (false !== ($entry = readdir($handle))) {
	  $file=$extracertsdir."/".$entry;
	  if (is_file($file)) {
		  $filect = file_get_contents($file);
		  if(openssl_x509_read($filect)) {
			 $TSA['extracerts'][] = $filect;
		  }
	  }
  }
  closedir($handle);
}

if (@$handle = opendir($crlsdir)) {
  while (false !== ($entry = readdir($handle))) {
	  $file=$crlsdir."/".$entry;
	  if (is_file($file)) {
		  $filect = file_get_contents($file);
			 $TSA['crls'][] = $filect;
	  }
  }
  closedir($handle);
}

$TSA['serial'] = file_get_contents('serial.txt');
  

if($PARSED_REQ = tsa_parsereq($req, $use_tsa)) {
  if($use_tsa == 1) {
    include 'tsa_0.php'; // rfc3161 (pdf signing etc)
  }
  if($use_tsa == 2) {
    include 'tsa_1.php'; // old sign code, signtool
  }
} else {
  tsalog("malformedRequest: Can't parse request", 'i');
  header("HTTP/1.0 403 Forbidden");
}
?>