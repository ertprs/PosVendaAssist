<?php

$email_remetente = "comercial@telecontrol.com.br";
$email_assunto = "Contato | Site Telecontrol";

$nome    = $_POST["nome"];
$email     = $_POST["email"];
$cnpj         =$_POST["cnpj"];
$setor         =$_POST["setor"];
$pedido         =$_POST["pedido"];
$telefone 		  =$_POST["telefone"];
$assunto        	  =$_POST["assunto"];
$mensagem         =$_POST["mensagem"];

$email_destinatario = "comercial@telecontrol.com.br";
$email_reply = "$email";

$email_headers = implode ( "\n",array ( "From: $email_remetente", "Reply-To: $email_reply","Return-Path:$email_remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html; charset=UTF-8" ) );

$email_form = "Contato | Site Telecontrol"."<br/><br/>";
$email_form .= "Razão Social / Nome: $nome"."<br/>";
$email_form .= "CNPJ: $cnpj"."<br/>";
$email_form .= "Email: $email"."<br/>";
$email_form .= "Telefone: $telefone"."<br/>";
$email_form .= "Setor: $setor"."<br/>";
$email_form .= "Pedido: $pedido"."<br/><br/>";
$email_form .= "Assunto: $assunto"."<br/><br/>";
$email_form .= "Mensagem: $mensagem"."<br/>";

mail ("$email_destinatario", "$email_assunto", "$email_form", "$email_headers" );
header("Location: contato-obrigado.php");
	
?>