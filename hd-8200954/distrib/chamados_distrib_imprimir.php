<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

// $abrir =fopen("/www/assist/www/nosso_ip.txt", "r");
//  $teste=fread($abrir, filesize("/www/assist/www/nosso_ip.txt"));

//$teste = include ("../nosso_ip.php");

//if ($ip!=trim($teste)){
//	header("Location: index.php");
//}

## GRAVAR DADOS
if (isset($_GET['chamado']) AND strlen($_GET['chamado'])>0){

	$chamado=trim($_GET['chamado']);
	$sql="SELECT 	TO_CHAR(tbl_distrib_chamado.data_emissao,'DD/MM/YYYY')  AS data_emissao ,
				TO_CHAR(tbl_distrib_chamado.data_chamado,'DD/MM/YYYY')      AS data_chamado ,
				tbl_distrib_chamado.nota_fiscal                             AS nota_fiscal  ,
				tbl_distrib_chamado.valor_credito                           AS valor_credito,
				tbl_distrib_chamado.observacao                              AS observacao   ,
				tbl_distrib_chamado.gerar_credito                           AS gerar_credito,
				tbl_posto.nome                                              AS posto_nome   ,
				tbl_posto_fabrica.codigo_posto                              AS posto_codigo ,
				tbl_posto.fone                                                              ,
				tbl_posto_fabrica.contato_endereco                                          ,
				tbl_posto_fabrica.contato_numero                                            ,
				tbl_posto_fabrica.contato_complemento                                       ,
				tbl_posto_fabrica.contato_bairro                                            ,
				tbl_posto_fabrica.contato_cidade                                            ,
				tbl_posto_fabrica.contato_cep                                               ,
				tbl_posto_fabrica.contato_estado                                            ,
				tbl_posto_fabrica.contato_email
		FROM tbl_distrib_chamado
		JOIN tbl_posto         USING(posto)
		LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = tbl_distrib_chamado.fabrica
		WHERE tbl_distrib_chamado.distrib_chamado=$chamado";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	$total=pg_numrows($res);
	if (strlen($msg_erro)==0){
		$posto_codigo  = pg_result ($res,0,posto_codigo);
		$posto_nome    = pg_result ($res,0,posto_nome);
		$nota_fiscal   = pg_result ($res,0,nota_fiscal);
		$data_emissao  = pg_result ($res,0,data_emissao);
		$data_chamado  = pg_result ($res,0,data_chamado);
		$valor_credito = pg_result ($res,0,valor_credito);
		$gerar_credito = pg_result ($res,0,gerar_credito);
		$observacao    = pg_result ($res,0,observacao);

		$fone                = pg_result ($res,0,fone);
		$contato_endereco    = pg_result ($res,0,contato_endereco);
		$contato_numero      = pg_result ($res,0,contato_numero);
		$contato_complemento = pg_result ($res,0,contato_complemento);
		$contato_bairro      = pg_result ($res,0,contato_bairro);
		$contato_cidade      = pg_result ($res,0,contato_cidade);
		$contato_cep         = pg_result ($res,0,contato_cep);
		$contato_estado      = pg_result ($res,0,contato_estado);
		$contato_email       = pg_result ($res,0,contato_email);

		$contato_cep = substr($contato_cep, 0, 2).".".substr($contato_cep, 2, 3)."-".substr($contato_cep, 5, 3);


		$valor_credito = ($valor_credito>0)?number_format($valor_credito,2,",","."):"";
	}

}
else{
	//header("Location: chamados_consulta.php");
}


?>

<html>
<head>
<title>Chamados - Imprimir</title>
<link type="text/css" rel="stylesheet" href="css/css.css">

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:9px;
	height:12px;
}
.botao2{
	border:1px solid #666;
	font-size:9px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:9px;
	height:16px;
}
.nomes{
	font-family: "Verdana, Arial, Helvetica, sans-serif";
	font-size:11px;
	font-weight:normal;
}
.nomes1{
	font-family: "Verdana, Arial, Helvetica, sans-serif";
	font-size:12px;
	font-weight:bold;
}
.nomes2{
	font-family: "Verdana, Arial, Helvetica, sans-serif";
	font-size:14px;
	font-weight:bold;
}
.frm {
	BORDER: 1px solid #888888;
	FONT-WEIGHT: bold;
	FONT-SIZE: 9pt;
	FONT-FAMILY: "Verdana, Arial, Helvetica, sans-serif";
	BACKGROUND-COLOR: #f0f0f0
}
.loading
{
	font-size:12px;
	FONT-FAMILY: "Verdana, Arial, Helvetica, sans-serif";
	padding:5px;
}
.loaded
{
	font-size:12px;
	FONT-FAMILY: "Verdana, Arial, Helvetica, sans-serif";
	padding:5px;
}

</style>
</head>

<body>


<center>
<div style='width:650px;font-size:16px;font-weight:bold;color:black;border:1px solid #333'>Entrada de Chamados</div>
</center>

<p>
<div id='loading'></div>
<center>

<form name='frm_consulta' method='post' action='<? echo $PHP_SELF ?>'>

	<table width="650px" border="0" cellspacing="5" cellpadding="0" >

	<tr>

		<td nowrap>
			<b class='nomes'>Código do Posto</b>
			<br><b class='nomes2'><? echo $posto_codigo ?></b>

		</td>

		<td nowrap>
			<b class='nomes'>Nome do Posto</b>
			<br><b class='nomes2'><? echo substr($posto_nome,0,40) ?></b>
		</td>
		<td nowrap>
			<b class='nomes'>Data do Chamado</b>
			<br><b class='nomes1'><? echo $data_chamado ?></b>
		</td>
	</tr>

	<tr>
		<td nowrap>
			<b class='nomes'>Fone</b>
			<br><b class='nomes2'><? echo $fone ?></b>
		</td>
		<td nowrap>
			<b class='nomes'>E-Mail</b>
			<br><b class='nomes1'><? echo $contato_email ?></b>
		</td>
		<td nowrap>
			<b class='nomes'>CEP</b>
			<br><b class='nomes1'><? echo $contato_cep ?></b>
		</td>
	</tr>

	<tr>
		<td nowrap>
			<b class='nomes'>Endereço</b>
			<br><b class='nomes2' style='font-size:10px'><? echo $contato_endereco.", ".$contato_numero." <br>".$contato_complemento ?></b>
		</td>
		<td nowrap>
			<b class='nomes'>Bairro</b>
			<br><b class='nomes1' style='font-size:10px'><? echo $contato_bairro ?></b>
		</td>
		<td nowrap>
			<b class='nomes'>Cidade/Estado</b>
			<br><b class='nomes1' style='font-size:10px'><? echo $contato_cidade." - ".$contato_estado ?></b>
		</td>
	</tr>

	<tr>

		<td nowrap>
			<b class='nomes'>Número da NF</b>
			<br><b class='nomes1'><? echo $nota_fiscal ?></b>
		</td>

		<td nowrap>
			<b class='nomes'>Data de Emissão</b>
			<br><b class='nomes1'><? echo $data_emissao ?></b>
		</td>
		<td nowrap>
		</td>
	</tr>
	<tr>
		<td colspan='3'><br>
			<div style='width:650px;font-size:12px;border-bottom:2px solid #ccc;font-weight:bold;color:#333'>Peças</div>
		</td>
	</tr>
</table>

<table width="650px" border="0" cellspacing="5" cellpadding="0" id='tbl_pecas'>
	<tr>
		<td nowrap>
			<b class='nomes'>Referência</b>
		</td>

		<td nowrap>
			<b class='nomes'>Descrição</b>
		</td>
		<td nowrap>
			<b class='nomes'>Localização</b>
		</td>
		<td nowrap>
			<b class='nomes'>Qtde</b>
		</td>
		<td nowrap>
			<b class='nomes'>Ocorrência</b>
		</td>
	</tr>
	<tr>
<?php

	$sql="SELECT tbl_distrib_chamado_item.peca,
				tbl_distrib_chamado_item.quantidade AS quantidade,
				tbl_distrib_chamado_item.ocorrencia AS ocorrencia,
				tbl_peca.referencia AS referencia,
				tbl_peca.descricao AS descricao,
				tbl_posto_estoque_localizacao.localizacao AS localizacao
		FROM tbl_distrib_chamado_item
			JOIN tbl_peca USING(peca)
			LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
			LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		WHERE tbl_distrib_chamado_item.distrib_chamado=$chamado";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	$total=pg_numrows($res);
	for ($i = 0 ; $i < $total ; $i++) {
 		$peca = pg_result ($res,$i,peca);
 		$referencia = pg_result ($res,$i,referencia);
 		$descricao = pg_result ($res,$i,descricao);
 		$qtde = pg_result ($res,$i,quantidade);
 		$ocorrencia = pg_result ($res,$i,ocorrencia);
		$localizacao = pg_result ($res,$i,localizacao);

		echo "<tr>";
		echo 	"<td nowrap class='nomes1'>
			$referencia
			</td>";
		echo 	"<td nowrap class='nomes1'>
			$descricao
			</td>";
		echo 	"<td nowrap class='nomes1'>
			$localizacao
			</td>";
		echo 	"<td nowrap class='nomes1'>
			$qtde
			</td>";
		echo 	"<td nowrap class='nomes1'>
			$ocorrencia
			</td>";
		echo "</tr>";
	}

?>
	</table>
<table width="650px" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td colspan='3'><br>
			<div style='width:650px;font-size:12px;border-bottom:2px solid #ccc;font-weight:bold;color:#333'></div>
		</td>
	</tr>
	<tr>
		<td colspan='3'><br>
		</td>
	</tr>
	<tr>
		<td nowrap class='nomes'>
		<?php if($gerar_credito=='t') echo "<b style='display:inline;padding-left:30px;font-size:14px'>Fazer Coleta PAC</b>"; else echo "<b style='padding-left:30px;font-size:14px'><u>Não</u> Fazer Coleta PAC</b>"  ?>
		</td>
		<td nowrap colspan='2' class='nomes'>
		<?php if($valor_credito>0) echo "<p style='display:inline;padding-left:30px;font-size:14px'>Valor Crédito: <b style='padding-left:60px'>R$ $valor_credito</b></p>"; ?>
		</td>
	</tr>
	<tr>
		<td nowrap colspan='3' class='nomes' align='center'><br>
			<br><b style='padding-left:45px;font-size:15px'>
				<?php if($valor_credito>0) echo "Acondicionar a(s) peça(s) danificada(s) e aguardar o pedido do posto!"; else echo "Enviar a peça com urgência para o posto!"  ?><br><br><u>Fazer a contagem dessa(s) peça(s) no estoque!</u></b><br>
		</td>
	</tr>
	<tr>
		<td colspan='3' align='center' class='nomes'><br>Observação<br>

			<div style='width:400px;font-size:10px;border:1px solid #999;padding:10px'><?php echo $observacao ?></div>
		</td>
	</tr>
</table>
</form>
</center>

<center><br><img src="../imagens/btn_imprimir.gif" style='cursor:pointer' onclick='javascript:window.print();'>
<br><br><a href='chamados_consulta.php'>
<h2 style='padding:3px;text-align:center;font-size:13px;color:white;background-color:#0099CC;width:330px;cursor:pointer'>Voltar para os Chamados</h2>
</a>
</center>

</body>
</html>
