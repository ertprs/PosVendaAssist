<?
	$remetente    = "LENOXXSOUND FINANCEIRO <wellington@telecontrol.com.br>"; 
	$destinatario = "wellington@telecontrol.com.br"; 
	$assunto      = "EMAIL PHP"; 
	$mensagem     = "* TESTE *"; 
	$headers      = "Return-Path: <wellington@telecontrol.com.br>\nFrom: ".$remetente."\nBcc:takashi@telecontrol.com.br \nContent-type: text/html\n";
	mail($destinatario,$assunto,$mensagem,$headers);
?>