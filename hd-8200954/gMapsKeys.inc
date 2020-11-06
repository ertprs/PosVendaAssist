<?php
/* chaves do GoogleMaps para o sdomínios da Telecontrol */

	$domain = $_SERVER['HTTP_HOST'];
	//$gAPI_key = 'ABQIAAAA4k5ZzVjDVAWrCyj3hmFzTxR_fGCUxdSNOqIGjCnpXy7SRGDdcRTb85b5W8d9rUg4N-hhOItnZScQwQ'; // www.telecontrol.com.br
	$gAPI_key = 'ABQIAAAAb4A3kIIYRbpkshUKv3o4kxToboNFIg9McltIAghIM0m3QXKlnxSr_faUMCkLvwqsaDOIiRgmtahSyw'; // telecontrol.com.br
	if (strpos($domain, 'conquistar') !== false)	$gAPI_key = 'ABQIAAAA58Y5NwUpOJR6Pos3XqtrxBTlBQ5aOUYyU5rKl0O8w3U_H__qfxTLzCSlrdwGCxTIR3P1Jtj-ZRdz6w'; // conquistar
	if (strpos($domain, '.net.br')    !== false)	$gAPI_key = 'ABQIAAAA58Y5NwUpOJR6Pos3XqtrxBSXzuw64REmAHFbLybXzpS0ysbbShRqfU4U8Ml9-PIIJrRfhec89KxBWA'; // .net.br
	
	if ($domain == '192.168.0.1'){
		$gAPI_key = 'ABQIAAAAEA4Skvl1mOcHt5tRFHJMchRc5N-P1NpY03Egq3jSesNMkYFHpRRM9CpbnEKYRHD3532iWFR1em1z3Q'; // conquistar
	}

	if ($domain == 'urano.telecontrol.com.br'){
		$gAPI_key = 'ABQIAAAAEA4Skvl1mOcHt5tRFHJMchQK9QjVYvSNU2xwg82-DA7mQFqCSxREYVGsKIda3fRYF30kntt_xczTlg'; // conquistar
	}

	if ($domain == 'testes.telecontrol.com.br'){
		$gAPI_key = 'ABQIAAAAEA4Skvl1mOcHt5tRFHJMchTPlpv75pRsE3SqJCAVrnY6ThKT4xREZ_34BjY1L5oJ38gl0FOozBtFhQ'; // conquistar
	}
