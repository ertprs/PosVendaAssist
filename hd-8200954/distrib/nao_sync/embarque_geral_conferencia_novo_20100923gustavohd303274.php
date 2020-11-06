<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

#echo "Programa de junção está processando...Aguarde!"; exit;
$btn_acao = trim($_POST['btn_acao']);
if (strlen($btn_acao) == 0) {
	$btn_acao = trim($_GET['btn_acao']);
}

$os_parcial    = trim($_GET['os_parcial']);
$excluir_troca = trim($_GET['excluir_troca']);
$aviso         = trim($_GET['aviso']);
$os            = trim($_GET['os']);
$pedido        = trim($_GET['pedido']);
$email         = trim($_GET['email']);
$tipo          = trim($_GET['tipo']);
$dado          = trim($_GET['dado']);
$posto         = trim($_GET['posto']);

$pedido_rec    = trim($_GET['pedido_recalcula']);

if (strlen($pedido_rec) > 0) {
	$sql = "SELECT fabrica FROM tbl_pedido WHERE pedido = $pedido_rec";
	$res = pg_exec($con,$sql);
	$fabrica_rec = pg_result ($res,0,fabrica);
	$sql = "SELECT fn_pedido_finaliza($pedido_rec,$fabrica_rec);";
	$res = pg_exec($con,$sql);
}


#HD 20202
if (strlen($aviso) > 0 AND $aviso == '1') {

	if (strlen($os) > 0 and strlen($pedido) > 0) {

		echo '	<style type="text/css">
					body {
						font-family : verdana;
						font-size:12px;
					}
					td {
						font-size: 15pt;
					}
					font {
						font-size: 15pt;
					}
					tr {
						font-size: 15pt;
					}
				</style>';

		$sql = "SELECT tbl_os.sua_os,tbl_posto_fabrica.codigo_posto,tbl_posto.nome
				FROM tbl_os
				JOIN tbl_posto USING(posto)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				WHERE tbl_os.os = $os";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX) >0) {
			$sua_os         = pg_result ($resX,$x,sua_os);
			$codigo_posto   = pg_result ($resX,$x,codigo_posto);
			$nome           = pg_result ($resX,$x,nome);

			if ($email=='1'){
				$sql = "SELECT tbl_admin.email
						FROM tbl_admin
						JOIN tbl_os USING(fabrica)
						WHERE tbl_os.os = $os
						AND tbl_admin.responsavel_troca IS TRUE";
				$resX = pg_exec ($con,$sql);
				$lista_email = array();
				array_push($lista_email,'ronaldo@telecontrol.com.br');
				array_push($lista_email,'fabio@telecontrol.com.br');
				for ($i=0; $i<pg_numrows ($resX); $i++){
					array_push($lista_email,pg_result ($resX,$i,email));
				}
				$remetente    = "Telecontrol <ronaldo@telecontrol.com.br>";
				$destinatario = implode(",",$lista_email);
				$assunto      = "OS Sujeito a Procon";
				$mensagem =  "Telecontrol - Sistema Inteligente<br><br>
							Prezado(a)<br><br>
							Foi detectado que o pedido <b>$pedido</b> do posto <b>$codigo_posto - $nome</b>, da Ordem de Serviço nº <b>$sua_os</b>, contém o pedido de uma peça em garantia para reparo a mais de 20 dias e pode causar um processo PROCON.<br><br><br> Sugerimos antecipar a troca do produto.<br><br>
							<br><br>
							Não responder este email, é gerado automaticamente pelo sistema!";
				$headers = "Return-Path: <ronaldo@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
				if(count($destinatario)>0) {
					if (mail($destinatario,$assunto,$mensagem,$headers)){
						echo "<p>EMAIL ENVIADO COM SUCESSO!</p>";
						echo "<p>Foi enviado para os seguintes destinatários: ".$destinatario."</p>";
						echo "<br>";
						echo "<p><a href='javascript:window.close()'>Fechar Janela</a>";
					}else{
						echo "<p>Ocorreu um erro no envio do email. Tente novamente.</p>";
						echo "<p><a href='javascript:window.close()'>Fechar Janela</a>";
					}
				}
			}else{
				echo "Foi detectado que o pedido <b>$pedido</b> do posto <b>$codigo_posto - $nome</b>, da Ordem de Serviço nº <b>$sua_os</b>, contém o pedido de uma peça em garantia para reparo a mais de 20 dias e pode causar um processo PROCON.";
				echo "<br>";
				echo "<br>";
				echo "<a href='$PHP_SELF?aviso=1&os=$os&pedido=$pedido&email=1'>ENVIAR EMAIL PARA AVISAR O FABRICANTE</a>";
			}
		}
	}
	exit;
}

if ($os_parcial == "1"){
	$sql = "SELECT fn_os_parcial ($login_posto)";
	//echo nl2br($sql);
	$res = pg_exec ($con,$sql);
}
if ($excluir_troca=="sim"){
	$Xos_item = trim($_GET['os_item']);
	$Xpedido  = trim($_GET['pedido']);
	$Xpeca    = trim($_GET['peca']);
	if (strlen($Xos_item)>0 AND strlen($Xpedido)>0 AND strlen($Xpeca)>0){
		$sql = "SELECT fabrica FROM tbl_peca WHERE peca = $Xpeca";
		$res = pg_exec($con,$sql);
		$fabrica_cancela = pg_result($res,0,0);
		$sql = "SELECT fn_pedido_cancela_garantia($login_posto,$fabrica_cancela, $Xpedido , $Xpeca, $Xos_item,'Cancelamento de Embarque');";
		$res = pg_exec ($con,$sql);
		#echo nl2br($sql);
		echo "Operação concluida com sucesso.";
	}else{
		echo "Operação cancelada.";
	}
	exit;
}

//Juntar embarque = system("/www/cgi-bin/distrib/juntar-embarques.pl",$ret);
//Desembarcar -parciais - fn_os_parcial_embarque

if (strlen($btn_acao)>0){
	$qtde_embarques = trim($_POST['qtde_embarques']);

	for ($i=0; $i<$qtde_embarques; $i++){
		$ativo    = trim($_POST['ativo_'.$i]);
		$embarque = trim($_POST['embarque_'.$i]);
		if (strlen($ativo)>0 AND strlen($embarque)>0){

			if ($btn_acao == "embarcar" AND strlen($ativo)>0){
				$sql = "SELECT fn_preparar_cargar($embarque)";
				#echo nl2br($sql);
				//$res = pg_exec ($con,$sql);
			}

			if ($btn_acao == "liberar_etiqueta"){
				/*
				$sql = "SELECT DISTINCT tbl_embarque.embarque
						FROM tbl_embarque_item
						JOIN tbl_embarque USING (embarque)
						WHERE tbl_embarque.distribuidor = $login_posto
						AND tbl_embarque.faturar       IS NULL
						AND tbl_embarque_item.liberado IS NULL
						AND tbl_embarque_item.impresso IS NULL
						AND tbl_embarque.embarque       = $embarque
						AND tbl_embarque.posto NOT IN (
							SELECT posto
							FROM  tbl_embarque
							WHERE faturar >= CURRENT_DATE - INTERVAL '10 days'
							AND   nf_conferencia IS NOT TRUE
							AND   distribuidor = $login_posto
						)";
				//echo nl2br($sql);
				$res = pg_exec ($con,$sql);

				if (pg_numrows($res) > 0) {
					$libera_embarque = pg_result ($res,0,0);
					//echo "<hr>".$libera_embarque."<hr>";*/
					$sql = "SELECT fn_preparar_cargar($embarque)";
					//echo nl2br($sql);
					$res = pg_exec ($con,$sql);
					$sql = "SELECT fn_etiqueta_libera ($embarque)";
					//echo nl2br($sql);
					$res = pg_exec ($con,$sql);
				//}
			}
		}
	}
	//exit;
	$linha          = "";
	$embarque       = "";
	$qtde_embarques = "";

	if ($btn_acao == "liberar_etiqueta"){
		header("Location: embarque_faturamento.php?quais_embarque=liberados");
		exit;
	}
}

$msg_erro = "";
?>

<html>
<head>
<title>Conferência do Embarque</title>

<style type="text/css">
	.body {
	font-family : verdana;
	}
</style>

<script>

	function abrirAviso(os,pedido){
		window.open('<?="$PHP_SELF?aviso=1&os="?>'+os+'&pedido='+pedido,"janela","toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no,width=200, height=200, top=18, left=0");
	}

	function excluirEmbarque(embarque){
		if(confirm('Deseja realmente excluir este embarque?')){
			window.location='<? echo $PHP_SELF ?>?excluir_embarque='+embarque;
		}
	}
	function excluirItem(url){
		if(confirm('Deseja realmente excluir esta peça deste embarque?')){
			window.location=url;
		}
	}

	function desembarcarParcial(url){
		if(confirm('Deseja desembarcar as OS´s parciais?')){
			window.location=url;
		}
	}

	function alteraDado(tipo,dado,posto){
		window.open('atualiza_posto.php?tipo=' + tipo+'&dado=' + dado +'&posto=' +posto,"janela","toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no,width=300, height=300, top=18, left=0");
	}



</script>

</head>

<body>

<style>
	.nivel1{
		background-color:#DFDFDF;
		border:1px solid #151515;
	}
	.nivel2{
		background-color:#DF0D12;
		color:#FFFFFF;
		border:1px solid #151515;
	}
	.selecionado{
		background-color:#33D237;
		border:1px solid #151515;
	}

</style>
<div class=noprint>
<? include 'menu.php' ?>
</div>

<center><h1>Conferência Geral do Embarque</h1></center>


<?

if (strlen($btn_acao)==0){
	$etapa=1;
}

if ($btn_acao == "embarcar"){
	$etapa = 2;
}
if ($btn_acao == "liberar_etiqueta"){
	$etapa = 3;
}

//include "etapas.php";

if (strlen($btn_acao)==0){
	//echo "<p><a href='$PHP_SELF?os_parcial=1'>Reprocessar OS's parcial</a></p>";
}

$quais_embarques = $_POST['quais_embarques'];
if (strlen ($quais_embarques) == 0) $quais_embarques = "todos";

?>


<center>
<form method='post' name='frm_conferencia' action='<?= $PHP_SELF ?>'>
<input type='radio' name='quais_embarques' <? if ($quais_embarques == "todos") echo " checked " ?> value='todos' >Não liberados
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type='radio' name='quais_embarques' <? if ($quais_embarques == "liberados") echo " checked " ?> value='liberados' >Apenas os liberados
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type='submit' name='btn_pesquisar' value='Listar'>
</form>
</center>

<p>
<?
$excluir_embarque      = trim($_GET['excluir_embarque']);
$numero_embarque       = trim($_GET['numero_embarque']);
$excluir_embarque_peca = trim($_GET['excluir_embarque_peca']);
$desembarcar_parcial   = trim($_GET['desembarcar_parcial']);

$msg = "";

if (strlen($numero_embarque)>0 AND $desembarcar_parcial == "1"){

	$msg .= "Desembarque das OS´s parciais do embarque $numero_embarque ... ";

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$res  = @pg_exec($con,"SELECT MAX(embarque) FROM tbl_embarque");
	$t_embarque_max = pg_result ($res,0,0);

/*
	$sql = "
		SELECT tbl_embarque.posto, tbl_embarque_item.embarque, osx.os_item, tbl_embarque_item.pedido_item, tbl_embarque_item.peca, tbl_embarque_item.qtde
		INTO TEMPORARY  TABLE x_os_parcial
		FROM (
			SELECT DISTINCT oss.os_item
			FROM (
				SELECT tbl_os.os, tbl_os_item.os_item
				FROM tbl_os
				JOIN tbl_os_produto    USING (os)
				JOIN tbl_os_item       USING (os_produto)
				JOIN tbl_embarque_item USING (os_item)
				JOIN tbl_embarque      USING (embarque)
				WHERE tbl_embarque.distribuidor   = $login_posto
				AND   tbl_embarque.embarque       = $numero_embarque
				AND   tbl_embarque.faturar       IS NULL
				AND   tbl_embarque_item.impresso IS NULL
			) oss
			JOIN tbl_os                 ON tbl_os.os                     = oss.os
			JOIN tbl_os_produto         ON oss.os                        = tbl_os_produto.os
			JOIN tbl_os_item            ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
			JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			LEFT JOIN tbl_embarque_item ON tbl_os_item.os_item           = tbl_embarque_item.os_item
			LEFT JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.os = tbl_os.os AND tbl_pedido_cancelado.pedido = tbl_os_item.pedido AND tbl_pedido_cancelado.peca = tbl_os_item.peca
			WHERE (tbl_servico_realizado.troca_de_peca OR tbl_servico_realizado.troca_produto OR tbl_servico_realizado.ressarcimento)
			AND tbl_embarque_item.os_item   IS NULL
			AND tbl_pedido_cancelado.pedido IS NULL
		) osx
		JOIN tbl_os_item       ON osx.os_item           = tbl_os_item.os_item
		JOIN tbl_embarque_item ON osx.os_item           = tbl_embarque_item.os_item
		JOIN tbl_embarque      ON tbl_embarque.embarque = tbl_embarque_item.embarque
		;

		DELETE FROM tbl_embarque_item USING x_os_parcial WHERE tbl_embarque_item.os_item = x_os_parcial.os_item ;


		DELETE FROM tbl_embarque WHERE faturar IS NULL AND embarque IN (SELECT tbl_embarque.embarque FROM tbl_embarque LEFT JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque.faturar IS NULL AND tbl_embarque_item.embarque IS NULL) ;

		INSERT INTO tbl_embarque (posto, distribuidor) (SELECT DISTINCT x_os_parcial.posto,  $login_posto FROM x_os_parcial) ;

		INSERT INTO tbl_embarque_item (embarque, peca, qtde, os_item, pedido_item)
			(SELECT tbl_embarque.embarque, x_os_parcial.peca, x_os_parcial.qtde, x_os_parcial.os_item, x_os_parcial.pedido_item
			FROM tbl_embarque
			JOIN x_os_parcial ON tbl_embarque.posto = x_os_parcial.posto AND tbl_embarque.embarque > $t_embarque_max ) ;
	";
*/

	$sql = "SELECT fn_os_parcial_embarque($login_posto, $numero_embarque);";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		$msg .=  "Operação realizada com sucesso.";
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		//$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$msg .=  "Operação não realizada. Erro: $msg_erro";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if (strlen($numero_embarque)>0 AND strlen($excluir_embarque_peca)>0){

	$msg .= "Excluindo peca do embarque: $numero_embarque ...";

	$res = @pg_exec($con,"BEGIN TRANSACTION");
	$arquivo  = fopen ("log_delete_embarque_item.txt", "a+");
	$peca = $excluir_embarque_peca;

	#Envia email para o PA quando é feito o cancelamento de peças faturadas
	$sqlX = "SELECT DISTINCT tbl_posto.nome,
					tbl_posto.email,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_embarque_item.pedido_item,
					tbl_pedido.pedido
			FROM   tbl_embarque
			JOIN   tbl_embarque_item USING(embarque)
			JOIN   tbl_peca          USING(peca)
			JOIN   tbl_posto       ON tbl_posto.posto             = tbl_embarque.posto
			JOIN   tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
			JOIN   tbl_pedido      ON tbl_pedido.pedido           = tbl_pedido_item.pedido
			JOIN   tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
			WHERE  tbl_embarque.embarque  = $numero_embarque
			AND    tbl_embarque_item.peca = $excluir_embarque_peca
			AND    (tbl_tipo_pedido.descricao ilike '%faturad%' OR tbl_tipo_pedido.descricao ilike '%venda%')";
	$resX = pg_exec ($con,$sqlX);
	if (pg_numrows ($resX) >0) {
		$nome      = pg_result ($resX,$x,nome);
		$email     = pg_result ($resX,$x,email);
		$referenca = pg_result ($resX,$x,referenca);
		$descricao = pg_result ($resX,$x,descricao);
		$pedido    = pg_result ($resX,$x,pedido);
		if (strlen($email)>0){
			$remetente    = "Telecontrol <ronaldo@telecontrol.com.br>";
			$destinatario = $email;
			$assunto      = "Cancelamento de Pedido de Peça do Distribuidor";
			$mensagem =  "At. Responsável,<br><br>A peça $referencia - $descricao do pedido de número $pedido foi cancelado.
			<br>
			<br> Caso tenha alguma dúvida, entre em contato com o distribuidor Telecontrol
			<br><br>Telecontrol Networking";
			$headers="Return-Path: <ronaldo@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
			if(strlen($mensagem)>0) {
				//Comentei a pedido do Paulo Lin. Ébano
				//mail($destinatario,$assunto,$mensagem,$headers);
			}
		}
	}

	$sqlX = "SELECT	embarque_item
			FROM   tbl_embarque_item
			WHERE  embarque = $numero_embarque
			AND    peca     = $peca";
	$resX = pg_exec ($con,$sqlX);
	for ($x = 0 ; $x < pg_numrows ($resX); $x++) {
		$embarque_item = pg_result ($resX,$x,embarque_item);

		#$sql = "SELECT fn_cancelar_embarque_item($embarque_item);";
		$sql = "SELECT fn_delete_embarque_item($embarque_item);";
		fwrite($arquivo, "\n\n Excluir item $embarque_item (".date("d/m/Y H:i:s").") \n [ $sql ]\n");
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$msg .=  "Operação realizada com sucesso.";
		fwrite($arquivo, "\n COMMIT TRANSACTION \n");
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$msg .=  "Operação não realizada. Erro: $msg_erro";
		fwrite($arquivo, "\n ROLLBACK \n");
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
	fclose ($arquivo);
	echo "<br><br>";
}

if (strlen($excluir_embarque)>0){

	$msg .=  "Excluindo embarque: $excluir_embarque ...";

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$sql="SELECT fn_cancelar_embarque($excluir_embarque)";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	#echo nl2br($sql);


	if (strlen ($msg_erro) == 0) {
		$msg .=  "Operação realizada com sucesso.";
		$arquivo  = fopen ("log_excluir_embarque.txt", "a+");
		fwrite($arquivo, "\n\n Excluir embarque $excluir_embarque (".date("d/m/Y H:i:s").") \n [ $sql ]\n");
		fclose ($arquivo);

		$res = @pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$msg .=  "Operação não realizada. Erro: $msg_erro";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
	echo "<br><br>";
}
?>

<?
if (strlen($msg)>0){
	echo "<h4 style='color:black;text-align:center;border:1px solid #2FCEFD;background-color:#E1FDFF'>$msg</h4>";
}
?>


<?
$embarque = trim($_GET['embarque']);
$maior_embarque = trim($_GET['maior_embarque']);
$cond_01 = " 1=1 ";

if (strlen ($maior_embarque) > 0 AND 1==2) {
	$cond_01 = " tbl_embarque.embarque <= $maior_embarque ";

	$sql = "SELECT DISTINCT tbl_embarque.embarque
			FROM tbl_embarque_item
			JOIN tbl_embarque USING (embarque)
			WHERE tbl_embarque.distribuidor = $login_posto
			AND tbl_embarque.faturar IS NULL
			AND tbl_embarque_item.liberado IS NULL
			AND tbl_embarque_item.impresso IS NULL
			AND tbl_embarque.embarque <= $maior_embarque
			AND tbl_embarque.posto NOT IN (
				SELECT posto
				FROM  tbl_embarque
				WHERE faturar >= CURRENT_DATE - INTERVAL '10 days'
				AND   nf_conferencia IS NOT TRUE
				AND   distribuidor = $login_posto
			)";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$libera_embarque = pg_result ($res,$i,0);
		pg_exec ($con,"SELECT fn_etiqueta_libera ($libera_embarque)");
	}
}

if (strlen ($embarque) > 0) $cond_01 = " tbl_embarque.embarque = $embarque ";

if ($quais_embarques == "aprovados") {
	//$cond_01 = " tbl_embarque.embarque IN (SELECT DISTINCT embarque FROM tbl_embarque_item WHERE liberado IS NOT NULL ) ";
}

$sql = "SELECT TO_CHAR (tbl_embarque.data,'DD/MM') AS data_embarque,
				tbl_posto.posto,
				tbl_posto.nome,
				tbl_posto.cidade,
				tbl_posto.cnpj,
				tbl_posto.ie,
				tbl_posto.estado,
				tbl_posto.fone,
				tbl_posto.data_expira_sintegra,
				tbl_embarque.carga_preparada,
				tbl_embarque.embarque
		FROM tbl_embarque ";
if ($quais_embarques == "liberados") {
	$sql .= "
			JOIN (
			SELECT DISTINCT embarque
			FROM tbl_embarque_item
			WHERE liberado IS NOT NULL
		) emb ON emb.embarque = tbl_embarque.embarque
		";
}else{
	$sql .= "
			JOIN (
			SELECT DISTINCT embarque
			FROM tbl_embarque_item
			WHERE liberado IS NULL
		) emb ON emb.embarque = tbl_embarque.embarque
		";
}
$sql .= "
		JOIN tbl_posto USING (posto)
		WHERE tbl_embarque.faturar IS NULL
		AND   $cond_01
		AND   tbl_embarque.distribuidor = $login_posto ";


/*
if ($btn_acao == ""){
	$sql .= " AND tbl_embarque.carga_preparada IS NOT TRUE ";
}
if ($btn_acao == "embarcar"){
	$sql .= " AND tbl_embarque.carga_preparada IS TRUE ";
}
if ($btn_acao == "liberar_etiqueta"){
	$sql .= " AND tbl_embarque.carga_preparada IS TRUE ";
}*/
$sql .= " ORDER BY embarque";
$res = pg_exec ($con,$sql);


$embarque = "";
$valor_mercadorias = 0;
$pendencia_total   = 0;
$total_pecas       = 0;
$total_embarques   = 0;

echo "<form name='frm_embarque' action='$PHP_SELF' method='POST'>";

if (strlen($btn_acao)==0){
	//echo "<input type='hidden' name='btn_acao' value='embarcar'>";
	echo "<input type='hidden' name='btn_acao' value='liberar_etiqueta'>";
}

if ($btn_acao == "embarcar"){
	//echo "<input type='hidden' name='btn_acao' value='liberar_etiqueta'>";
}
$qtde_embarques = pg_numrows ($res);
for ($i = 0 ; $i < $qtde_embarques ; $i++) {

	$embarque      = pg_result ($res,$i,embarque);
	$posto         = pg_result ($res,$i,posto);
	$data_embarque = pg_result ($res,$i,data_embarque);
	$carga_preparada= pg_result ($res,$i,carga_preparada);
	$nome          = pg_result ($res,$i,nome);
	$cidade        = pg_result ($res,$i,cidade);
	$estado        = pg_result ($res,$i,estado);
	$fone          = pg_result ($res,$i,fone);
	$cnpj        = pg_result ($res,$i,cnpj);
	$ie          = pg_result ($res,$i,ie);
	$data_expira_sintegra = pg_result ($res,$i,data_expira_sintegra);
	if(strlen($data_expira_sintegra) > 0){
		$sqld="select current_date - '$data_expira_sintegra' > 90 ;";
		$resd=pg_exec($con,$sql);
		$bloqueia = pg_result($resd,0,0);
	}
	$nivel = 0;
	$valor_mercadorias = 0;

	if ($i>0){
		echo "<p align='right' class='embarquenumimpressao'>Embs.: $total_embarques ; Acumulado Peças: $total_pecas <br></p>";
		if ($btn_acao == "embarcar"){
			//echo "<a href='$PHP_SELF?maior_embarque=$embarque'>Embarcar até aqui</a>";
		}
	}

	echo "\n<table border='1' align='center' cellpadding='3' cellspacing='0' width='750'>";

	$sql = "SELECT  tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					emb.peca,
					emb.qtde,
					tbl_posto_estoque_localizacao.localizacao,
					(	SELECT tbl_tabela_item.preco
						FROM tbl_tabela_item
						JOIN tbl_posto_linha ON tbl_posto_linha.posto = $login_posto
						AND tbl_posto_linha.tabela = tbl_tabela_item.tabela
						WHERE tbl_peca.peca = tbl_tabela_item.peca
						ORDER BY preco DESC
						LIMIT 1) AS preco
			FROM tbl_embarque
			JOIN (SELECT embarque, peca, SUM (qtde) AS qtde
				FROM tbl_embarque_item ";
			if ($quais_embarques == "liberados") {
				$sql .= " WHERE liberado IS NOT NULL ";
			}else{
				$sql .= " WHERE liberado IS NULL ";
			}
			$sql .= "GROUP BY embarque,peca
			) emb ON tbl_embarque.embarque = emb.embarque
			JOIN tbl_posto USING (posto)
			JOIN tbl_peca  USING (peca)
			LEFT JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.posto = tbl_embarque.distribuidor AND tbl_posto_estoque_localizacao.peca = emb.peca
			WHERE tbl_embarque.embarque      = $embarque
			AND   tbl_embarque.distribuidor  = $login_posto
			AND   tbl_embarque.faturar       IS NULL
			ORDER BY referencia";
		//Buscar preço dos itens da loja virtual tb
			$sql = "SELECT  tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_peca.ipi,
						emb.peca,
						emb.qtde,
						tbl_posto_estoque_localizacao.localizacao,
						(
							SELECT (
									SELECT tbl_tabela_item.preco
									FROM tbl_tabela_item
									JOIN tbl_posto_linha ON tbl_posto_linha.posto = $login_posto
									AND tbl_posto_linha.tabela = tbl_tabela_item.tabela
									WHERE tbl_peca.peca = tbl_tabela_item.peca
									ORDER BY preco DESC
									LIMIT 1
									)
							UNION
									(SELECT tbl_tabela_item.preco
									FROM tbl_tabela_item
									WHERE tbl_peca.peca = tbl_tabela_item.peca AND tbl_peca.fabrica = 10
									ORDER BY preco DESC
									LIMIT 1
									)
							LIMIT 1
						) AS preco
					FROM tbl_embarque
					JOIN (SELECT embarque, peca, SUM (qtde) AS qtde
					FROM tbl_embarque_item ";
				if ($quais_embarques == "liberados") {
					$sql .= " WHERE liberado IS NOT NULL ";
				}else{
					$sql .= " WHERE liberado IS NULL ";
				}
				$sql .= "
					GROUP BY embarque,peca
					) emb ON tbl_embarque.embarque = emb.embarque
					JOIN tbl_posto USING (posto)
					JOIN tbl_peca  USING (peca)
					LEFT JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.posto = tbl_embarque.distribuidor AND tbl_posto_estoque_localizacao.peca = emb.peca
					WHERE tbl_embarque.embarque      = $embarque
					AND   tbl_embarque.distribuidor  = $login_posto
					AND   tbl_embarque.faturar       IS NULL
					ORDER BY referencia";
	$resZ = pg_exec ($con,$sql);
	echo "<tbody>";
	for ($j = 0 ; $j < pg_numrows ($resZ) ; $j++) {
		$referencia = pg_result ($resZ,$j,referencia);
		$descricao  = pg_result ($resZ,$j,descricao);
		$ipi        = pg_result ($resZ,$j,ipi);
		$peca       = pg_result ($resZ,$j,peca);
		$qtde       = pg_result ($resZ,$j,qtde);
		$localizacao= pg_result ($resZ,$j,localizacao);
		$preco      = pg_result ($resZ,$j,preco);

		echo "<tr style='font-size:12px'>";

		echo "<td nowrap>";
			$sql = "SELECT	tbl_embarque_item.embarque_item ,
							CASE WHEN tbl_os.data_abertura IS NOT NULL THEN
									CURRENT_DATE - tbl_os.data_abertura::date
								ELSE CURRENT_DATE - tbl_pedido.data::date END  AS dias,
							CASE WHEN tbl_embarque_item.os_item IS NULL THEN 'F' ELSE 'G' END  AS fat_gar ,
							tbl_os.sua_os ,
							tbl_os.os,
							tbl_os.fabrica,
							tbl_os_item.os_item,
							tbl_os_item.pedido,
							tbl_embarque_item.impresso
					FROM   tbl_embarque_item
					JOIN   tbl_pedido_item USING (pedido_item)
					JOIN   tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
					LEFT JOIN tbl_os_item  ON tbl_embarque_item.os_item = tbl_os_item.os_item
					LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					LEFT JOIN tbl_os         ON tbl_os_produto.os = tbl_os.os
					WHERE  tbl_embarque_item.embarque = $embarque
					AND    tbl_embarque_item.peca     = $peca ";
				if ($quais_embarques == "liberados") {
					$sql .= "AND tbl_embarque_item.liberado IS NOT NULL ";
				}else{
					$sql .= "AND tbl_embarque_item.liberado IS NULL ";
				}
			$sql .= "ORDER BY tbl_embarque_item.embarque_item";
			$resx = pg_exec ($con,$sql);

			for ($x = 0 ; $x < pg_numrows ($resx); $x++) {

				$sql3 = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca limit 1";
				$res3 = pg_exec($con,$sql3);
				if(pg_numrows($res3)>0){
					$os_item_preco = pg_result($res3,0,0);
				}

				$os            = pg_result ($resx,$x,os);
				$sua_os        = pg_result ($resx,$x,sua_os);
				$fabrica       = pg_result ($resx,$x,fabrica);
				$embarque_item = pg_result ($resx,$x,embarque_item);
				$fat_gar       = pg_result ($resx,$x,fat_gar);
				$dias          = pg_result ($resx,$x,dias);
				$impresso      = pg_result ($resx,$x,impresso);
				$pedido        = pg_result ($resx,$x,pedido);
				$os_item       = pg_result ($resx,$x,os_item);

				$parcial = "";
				if (strlen($os)>0){

					$sqlTroca = "SELECT tbl_os.os
								FROM tbl_os
								JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
								WHERE tbl_os.fabrica = $fabrica
								AND   tbl_os.os      = $os";
					$resTroca = pg_exec ($con,$sqlTroca);
					if ( pg_numrows ($resTroca) >0 ){
						echo "<acronym title='Esta OS é de TROCA. Excluia o item deste embarque'><b style='color:red'>(T) <a href='$PHP_SELF?excluir_troca=sim&os_item=$os_item&pedido=$pedido&peca=$peca' target='_blank' class='noprint'>(Excluir)</a></b></acronym> ";
					}

					if (1==1){
						$sql_parcial = "
							SELECT tbl_embarque.posto, tbl_embarque_item.embarque, osx.os_item, tbl_embarque_item.pedido_item, tbl_embarque_item.peca, tbl_embarque_item.qtde
							FROM (
								SELECT DISTINCT oss.os_item
								FROM (
									SELECT tbl_os.os, tbl_os_item.os_item
									FROM tbl_os
									JOIN tbl_os_produto USING (os)
									JOIN tbl_os_item    USING (os_produto)
									JOIN tbl_embarque_item USING (os_item)
									JOIN tbl_embarque      USING (embarque)
									WHERE tbl_embarque.distribuidor  = $login_posto
									AND   tbl_os.os                  = $os
									AND   tbl_embarque.faturar       IS NULL
									AND tbl_embarque_item.impresso   IS NULL
								) oss
								JOIN tbl_os                 ON tbl_os.os                     = oss.os AND tbl_os.os = $os
								JOIN tbl_os_produto         ON oss.os                        = tbl_os_produto.os
								JOIN tbl_os_item            ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
								JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
								LEFT JOIN tbl_embarque_item ON tbl_os_item.os_item           = tbl_embarque_item.os_item
								LEFT JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.os = tbl_os.os AND tbl_pedido_cancelado.pedido = tbl_os_item.pedido AND tbl_pedido_cancelado.peca = tbl_os_item.peca
								WHERE (tbl_servico_realizado.troca_de_peca OR tbl_servico_realizado.troca_produto OR tbl_servico_realizado.ressarcimento)
								AND tbl_embarque_item.os_item IS NULL
								AND tbl_pedido_cancelado.pedido IS NULL
							) osx
							JOIN tbl_os_item        ON osx.os_item           = tbl_os_item.os_item
							JOIN tbl_embarque_item  ON osx.os_item           = tbl_embarque_item.os_item
							JOIN tbl_embarque       ON tbl_embarque.embarque = tbl_embarque_item.embarque";
						$resParcial = pg_exec ($con,$sql_parcial);
						//echo nl2br($sql_parcial);

						if ( pg_numrows ($resParcial) >0 ){
	/*
							$sql = "SELECT tbl_os_item.peca
									FROM   tbl_os
									JOIN   tbl_os_produto USING (os)
									JOIN   tbl_os_item    USING (os_produto)
									LEFT JOIN tbl_embarque_item  ON tbl_embarque_item.os_item = tbl_os_item.os_item
									WHERE  tbl_os.os         = $os
									AND    tbl_os.posto      = $posto
									AND    tbl_embarque_item.embarque_item IS NULL
									AND    tbl_os_item.os_item <> $os_item";
							$resPecas = pg_exec ($con,$sql);

							for ($Y = 0 ; $Y < pg_numrows ($resPecas); $Y++) {
								$Xpeca = pg_result ($resPecas,$Y,peca);
	/*
								$sql = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
												TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
										FROM    tbl_faturamento
										JOIN    tbl_faturamento_item USING (faturamento)
										WHERE   tbl_faturamento.posto        = $posto
										AND     tbl_faturamento.distribuidor = $login_posto
										AND     tbl_faturamento_item.pedido  = $pedido
										AND     tbl_faturamento_item.peca    = $Xpeca
										 ";
								$resPedido = pg_exec ($con,$sql);
								if ( pg_numrows ($resPedido) == 0 ){
									*/
									if ($os <> "4119143" AND $os <> "759223" AND $os<>"4215483"  AND $os<>"4215484"  AND $os<>"4215487"  AND $os<>"4418718"  AND $os<>"4169775" AND $os<>"4039364"){
										$os_parcial .= " / ".$os;
										$parcial = 1;
									}
								//}
							#}
						}
					}
				}

				if (strlen($impresso)>0){
					echo "";
				}else{
				}

				echo $embarque_item;
				echo " - " ;
				echo $fat_gar;
				echo " " ;

				if ($dias > 15){
					echo "<font size='+1' color='#ff0000'><b>".$dias."</b></font> - ";
				}else{
					echo $dias." - ";
				}

				echo "<a href='/assist/os_press.php?os=".$os."&login_posto=".$posto."&distribuidor=4311' target='_blank'>";

				if ($parcial==1){
					echo "<span style='color:#FF0909'>";
					echo $sua_os." <span style='font-size:10px'>(parcial)</span>";
					echo "</span>";
				}else{
					echo $sua_os;
				}
				echo "</a>";

				#HD 20202
				if ($dias > 20 and $fat_gar=='G' and strlen($os)>0 ){
					echo " <span style='color:#FF0033' class='noprint'>- (<a href='javascript:abrirAviso($os,$pedido)' style='color:#FF0033'>ATENÇÃO</a>)</span> ";
					echo "</span>";
				}

				if ($x <= pg_numrows($resx)) echo "<br>";
			}
		echo "</td>";
		if(strlen($os_item_preco) == 0){$cor_preco = "style='color:#33CC00'";}else{$cor_preco = '';}
		echo "<td $cor_preco class=destaqueimpressao nowrap>".$referencia."</td>";
		$os_item_preco = '';
		echo "<td class='produtoimpressao'>". $descricao. "</td>";
		echo "<td align='right' width='20' class=destaqueimpressao>".$qtde."</td>";
		echo "<td nowrap align='left' class=destaqueimpressao>".$localizacao."</td>";

		$total_pecas += $qtde;

		echo "<td class=noprint nowrap align='left' title='Cancela o embarque, volta as peças para o estoque, mas não cancela o pedido!'.>";
		echo "<a href=\"javascript:excluirItem('$PHP_SELF?numero_embarque=$embarque&excluir_embarque_peca=$peca')\" class=noprint>Desembarcar</a>";
		echo "</td>";

		echo "</tr>";

		$valor_mercadorias += $qtde * $preco;
	}
	echo "</tbody>";

	$sql = "
			SELECT  TO_CHAR(emissao,'DD/MM/YYYY') AS ultimo_faturamento,
			CURRENT_DATE - emissao AS dias_do_ultimo_faturamento
			FROM  (
				SELECT embarque
				FROM tbl_embarque
				WHERE posto      = $posto
				AND distribuidor = $login_posto
				AND faturar      IS NOT NULL
				ORDER BY data DESC LIMIT 1
			) emb
			JOIN tbl_faturamento ON tbl_faturamento.embarque = emb.embarque
			WHERE posto    = $posto
	";
	$resY = pg_exec ($con,$sql);
	if (pg_numrows ($resY)>0){
		$ultimo_faturamento      = pg_result ($resY,0,ultimo_faturamento);
		$dias_do_ultimo_embarque = pg_result ($resY,0,dias_do_ultimo_faturamento);
	}else{
		$ultimo_faturamento      = "Primeiro Embarque";
		$dias_do_ultimo_embarque = "8";
	}

	if ($dias_do_ultimo_embarque > 7){
		$nivel += 5;
	}

	if ($valor_mercadorias > 50){
		$nivel += 5;
	}

/*
	$sql = "SELECT	count(*) AS qtde_os_prazo
			FROM   tbl_embarque_item
			JOIN   tbl_pedido_item USING (pedido_item)
			JOIN   tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
			LEFT JOIN tbl_os_item  ON tbl_embarque_item.os_item = tbl_os_item.os_item
			LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			LEFT JOIN tbl_os         ON tbl_os_produto.os = tbl_os.os
			WHERE  tbl_embarque_item.embarque = $embarque
			AND    tbl_embarque_item.os_item  IS NOT NULL
			AND    CURRENT_DATE - tbl_pedido.data::date > 14
			";
	$resX = pg_exec ($con,$sql);
	if (pg_numrows ($resX)>0){
		$qtde_os_prazo = pg_result ($resX,0,qtde_os_prazo);
		if ($qtde_os_prazo>0){
			$nivel += 4;
		}
	}
*/
	echo "<thead>";
	echo "<tr>";
	echo "\n<input type='hidden' name='embarque_".$i."' value='$embarque'>\n";
	echo "<td colspan='5' align='center'>";
		#HD 195632
		if(strlen($embarque)>0){
			$sqlP = "SELECT DISTINCT tbl_pedido.pedido             ,
							tbl_pedido.posto              ,
							tbl_pedido.pedido_loja_virtual,
							(select fabrica from tbl_posto_fabrica where tbl_posto_fabrica.posto = tbl_pedido.posto and tbl_posto_fabrica.fabrica = 51) as atende
					FROM tbl_pedido
					JOIN tbl_pedido_item   USING(pedido)
					JOIN tbl_embarque_item USING(pedido_item)
					JOIN tbl_embarque      USING(embarque)
					WHERE tbl_embarque.embarque = $embarque;";
					#echo nl2br($sqlP);
			$resP = pg_exec($con, $sqlP);

			if(pg_numrows($resP)>0){
				$pedido_loja_virtual = pg_result($resP,0,pedido_loja_virtual);
				$atende              = pg_result($resP,0,atende);

				if($pedido_loja_virtual=="t"){
					if($atende=="51"){
						echo "<div style='text-align: left; color:#FF0000; font-size:11px; font-weight:bold;'>Frete Grátis - Loja Virtual (Posto Gama)</div>";
					}else{
						echo "<div style='text-align: left; color:#FF0000; font-size:11px; font-weight:bold;'>Cobrar Frete - Loja Virtual</div>";
					}
				}
			}
		}
	echo "<b>";
	//echo "<a href='embarque_conferencia.php?embarque=$embarque&etiqueta=S' target='_blank'>Etiquetas: </a>";
	// 16/4/9 MLG Alterado tamanho do nº de embarque a pedido do Sr. Laudir
	echo "<B style='font-size: 2em;font-weight: normal;'>(".$embarque.")</B> - " . $data_embarque . " - <a href =\"javascript: alteraDado('nome','$nome','$posto')\" class='destaqueimpressao'>$nome</a>";
	echo "</b><br>";
	echo "<a href =\"javascript: alteraDado('cnpj','$cnpj','$posto')\">CNPJ: $cnpj</a>  -  <a href =\"javascript: alteraDado('ie','$ie','$posto')\">I.E.: $ie</a> ";
	echo " - ";
	echo $cidade . " - " . $estado;
	echo " - ";
	echo $fone;

	echo " <br> Último embarque: ";
	if ($dias_do_ultimo_embarque > 7){
		echo "<span style='color:red'>".$ultimo_faturamento."</span>";
	}else{
		echo "<span>".$ultimo_faturamento."</span>";
	}
	echo "</td>";

	$classe = "nivel1";

	if ($nivel > 9){
		$classe = "nivel2";
	}

	echo "<td align='center' class=noprint>";
//	if (strlen($btn_acao)==0){
		//echo $nivel;

	if ($quais_embarques != "liberados") {
		if (strlen($os_parcial) > 0 && 1 == 2) {//HD 280635 - DESATIVADO
			echo "<b style='color:#FF0909'>OS Parcial</b>";
			// <a href='$PHP_SELF?desembarcar_parcial=$embarque'>(desembarcar parcial)</a>
		} else {
			$sql3 = "SELECT tbl_pedido_item.peca, pedido
						FROM tbl_embarque_item
						JOIN tbl_pedido_item using(pedido_item)
						WHERE embarque = $embarque
						AND (tbl_pedido_item.preco IS NULL OR tbl_pedido_item.preco = '0.01');";
			$res3 = pg_exec($con,$sql3);
			if (pg_numrows($res3) > 0) {
				$pedido_cal = pg_result($res3,0,pedido);
				echo "<FONT COLOR='#33CC00'>Bloqueado por falta de preço na peça<a href='embarque_geral_conferencia_novo.php?pedido_recalcula=$pedido_cal'>&nbsp;Recalcula</a></FONT>";
			} else {
				//samuel desabilitou provisoriamente
				//if(strlen($data_expira_sintegra) ==0 or strlen($ie) ==0 or $bloqueia == 't' ){
				//	echo "Bloqueado por falta<br>de dados na Sintegra";
				//}else{
					echo "<input type='button' class='$classe' onClick=\"

							if (this.value=='LIBERAR'){
								this.form.ativo_".$i.".value='$embarque';
								this.value='CANCELAR';
								this.className='selecionado';
							}else{
								this.form.ativo_".$i.".value='';
								this.value='LIBERAR';
								this.className='$classe';
							}

							\" value='LIBERAR'>";
					echo "<input type='hidden' name='ativo_".$i."' value=''>";
				//}
			}
		}
	}
//	}

	echo "</td>";

	echo "</tr>";
	echo "</thead>";

	echo "<tfoot>";
	echo "<tr>";
	echo "<td colspan='2'><b>Qtde.Volumes</b></td>";
	echo "<td colspan='3'><b>Transportadora</b></td>";
	echo "</tr>";

	$sql = "SELECT  SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada_distribuidor - tbl_pedido_item.qtde_cancelada) AS qtde ,
					TO_CHAR (AVG (CURRENT_DATE - tbl_pedido.data::date)::numeric,'999') AS media_dias
			FROM  tbl_pedido
			JOIN  tbl_pedido_item USING (pedido)
			WHERE tbl_pedido.posto = $posto
			AND   tbl_pedido.distribuidor = $login_posto
			AND   tbl_pedido.status_pedido_posto IN (1,2,5,7,8,9,10,11,12)";
	//$resX = pg_exec ($con,$sql);
	//$pendencia_total = pg_result ($resX,0,qtde);
	//$media_dias      = pg_result ($resX,0,media_dias);

	echo "<tr>";
	echo "<td colspan='2'><b>Valor Mercadorias: </b> R$ " . number_format ($valor_mercadorias,2,",",".") . "</td>";
	//echo "<td colspan='5'><b>Pendência Total: </b> " . number_format ($pendencia_total,0,",",".") . " peças (média $media_dias dias) </td>";
	echo "<td colspan='3'><b>Pendência Total: </b> DESABILITADO</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td colspan='5' align='right'>";
			//echo "<a href='javascript:excluirEmbarque($embarque)' alt='Excluir Embarque'>Excluir Embarque";
			if (strlen($os_parcial)>0){
				echo "<br><a href=\"javascript:desembarcarParcial('$PHP_SELF?numero_embarque=$embarque&desembarcar_parcial=1')\"> Desembarcar as OS´s parciais</a>";
			}
	echo "</td>";
	echo "</tr>";
	echo "</tfoot>";
	echo "</table>";

	$os_parcial = "";
	$total_embarques += 1;
}
echo "</table>";
echo "<p align='right'>Embs.: $total_embarques ; Acumulado Peças: $total_pecas <br></p>";
if ($btn_acao == "embarcar"){
	//echo "<a href='$PHP_SELF?maior_embarque=$embarque'>Embarcar até aqui</a>";
}

if ($quais_embarques=="todos"){
	echo "<p>Selecione os embarques e clique em Gravar para liberar</p>";
	echo "<input type='button' name='btn_gravar' value='LIBERAR EMBARQUES' onClick='this.form.submit()'>";
}

if ($btn_acao == "embarcar"){
	echo "<p>Todos os embarques acima serão liberados. Clique em 'Continuar'</p>";
	echo "<input type='button' name='btn_gravar' value='Continuar >>>>' onClick='this.form.submit()'>";
}

echo "<input type='hidden' name='qtde_embarques' value='".$qtde_embarques."'>";
echo "</form>";


?>


<p>

<? #include "rodape.php"; ?>

<style>
@media print {
	td, th, a, a:hover {
		font-size: 10pt;
		font-family: verdana;
	}

	td {
		border-collapse: collapse;
	}

	.noprint {
		display:none;
	}

	.destaqueimpressao {
		font-size: 12pt;
	}

	.produtoimpressao {
		font-size: 9pt;
	}

	.embarquenumimpressao {
		display: inline;
		margin-bottom: 10px;
	}
}
</style>

</body>
</html>
