<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";


$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0){
	$extrato = trim($_POST['extrato']);
}

#HD 278329
if(strlen($extrato)>0){
	$sql = "SELECT fabrica FROM tbl_extrato where fabrica = $login_fabrica AND extrato = $extrato";
	$res = @pg_exec($con, $sql);
	if (@pg_num_rows($res) == 0) {
		header('Location: menu_inicial.php');
		exit;
	}
}

if ($login_fabrica==11){
	$posto_da_fabrica = "20321";
}

$msg_erro="";
$msg="";

$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$tem_mais_itens='nao';
$contadorrr=0;


if (strlen($extrato)==0){
	#header("Location: os_extrato.php");
	echo "extrato: $extrato";
	exit;
}

$sql = "SELECT  CASE WHEN data_geracao > '2008-08-01'::date THEN '1' ELSE '0' END
		FROM tbl_extrato
		WHERE extrato = $extrato ";
$res2 = pg_exec ($con,$sql);
$verificacao = pg_result ($res2,0,0);
#2008-06-01 - HD 16362
#Prorrogado o prazo para AGOSTO/2008, conforme chamado 22638

if ($login_posto==6359 and $login_fabrica==11){#Somente para teste do PA TESTE
	$verificacao = "1";
}

$pecas_pendentes = trim($_GET['pendentes']);
if (strlen($pecas_pendentes)==0){
	$pecas_pendentes = trim($_POST['pendentes']);
}

$query = "SELECT count(*) FROM tbl_extrato_lgr WHERE extrato=$extrato AND posto=$login_posto AND qtde-qtde_nf>0";
$res = pg_exec ($con,$query);
if ( pg_result ($res,0,0)>0){
	$tem_mais_itens='sim';
}


$btn_acao = trim($_POST['botao_acao']);

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_qtde") {

	$sql_update = "UPDATE tbl_extrato_lgr 
			SET qtde_pedente_temp = qtde
			WHERE extrato=$extrato";
	$res_update = pg_exec ($con,$sql_update);
	$msg_erro .= pg_errormessage($con);
			#if ($ip=='201.71.54.144') echo nl2br($sql_update);


	$numero_linhas   = trim($_POST['qtde_linha']);
}

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_as_notas") {

	$qtde_pecas         = trim($_POST['qtde_pecas']);
	$numero_linhas      = trim($_POST['qtde_linha']);
	$numero_de_notas    = trim($_POST['numero_de_notas']);

	$data_preenchimento = date("Y-m-d");
	$array_notas        = array();

	$sql = "SELECT posto,distribuidor,extrato_devolucao
			FROM tbl_faturamento
			WHERE distribuidor      = $login_posto
			AND   posto             = $posto_da_fabrica
			AND   (tbl_faturamento.cfop  = '5949' or tbl_faturamento.cfop = '6949')
			AND   tbl_faturamento.cancelada      IS NULL
			AND   extrato_devolucao = $extrato";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res)>0){
		#header("Location: os_extrato.php");
		#exit();
	}

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	for($i=0;$i<$numero_de_notas;$i++){

		$nota_fiscal = trim($_POST["nota_fiscal_$i"]);

		//echo "Numero de notas: $nota_fiscal <br> $msg_erro";

		if (strlen($nota_fiscal)==0){
			$msg_erro .='Digite todas as notas fiscais!';
			break;
		}

		if (!is_numeric($nota_fiscal)){
			$msg_erro .='Digite somente número nas NF';
			break;
		}

		$nota_fiscal = str_replace(".","",$nota_fiscal);
		$nota_fiscal = str_replace(",","",$nota_fiscal);
		$nota_fiscal = str_replace("-","",$nota_fiscal);

		array_push($array_notas,$nota_fiscal);

		$total_nota = trim($_POST["id_nota_$i-total_nota"]);
		$base_icms  = trim($_POST["id_nota_$i-base_icms"]);
		$valor_icms = trim($_POST["id_nota_$i-valor_icms"]);
		$base_ipi   = trim($_POST["id_nota_$i-base_ipi"]);
		$valor_ipi  = trim($_POST["id_nota_$i-valor_ipi"]);
		$cfop       = trim($_POST["id_nota_$i-cfop"]);
		$movimento  = trim($_POST["id_nota_$i-movimento"]);

		$qtde_peca_na_nota = trim($_POST["id_nota_$i-qtde_itens"]);

		#HD 18528
		if ($verificacao=='1'){
			$base_ipi   = 0;
			$valor_ipi  = 0;
		}

		$sql = "INSERT INTO tbl_faturamento		  
				(fabrica, emissao,saida, posto, distribuidor, cfop, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi, extrato_devolucao, obs, movimento)
				VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',$posto_da_fabrica,$login_posto,$cfop,$total_nota,'$nota_fiscal','2','Devolução de peças em garantia', $base_icms, $valor_icms, $base_ipi, $valor_ipi, $extrato, 'Devolução de peças do posto para à Fábrica','$movimento')";
		//echo nl2br($sql);
		$res = pg_exec ($con,$sql);

		$sql = "SELECT CURRVAL ('seq_faturamento')";
		$resZ = pg_exec ($con,$sql);
		$faturamento_codigo = pg_result ($resZ,0,0);

		#echo "$faturamento_codigo - ";
		for($x=1;$x<=$qtde_peca_na_nota;$x++){

			$lgr                = trim($_POST["id_item_LGR_$x-$i"]);
			$peca               = trim($_POST["id_item_peca_$x-$i"]);
			$peca_preco         = trim($_POST["id_item_preco_$x-$i"]);
			$peca_qtde_total_nf = trim($_POST["id_item_qtde_$x-$i"]);
			$peca_aliq_icms     = trim($_POST["id_item_icms_$x-$i"]);
			$peca_aliq_ipi      = trim($_POST["id_item_ipi_$x-$i"]);
			$peca_total_item    = trim($_POST["id_item_total_$x-$i"]);

			$sql_update = "UPDATE tbl_extrato_lgr 
							SET qtde_nf   = (CASE WHEN qtde_nf IS NULL THEN 0 ELSE qtde_nf END) + $peca_qtde_total_nf
							WHERE extrato = $extrato
							AND peca      = $peca";
			$res_update = pg_exec ($con,$sql_update);
			$msg_erro .= pg_errormessage($con);

			$sql_nf = "SELECT
							tbl_faturamento_item.faturamento_item,
							tbl_faturamento.nota_fiscal,
							tbl_faturamento_item.qtde,
							tbl_faturamento_item.peca,
							tbl_faturamento_item.preco,
							tbl_faturamento_item.aliq_icms,
							tbl_faturamento_item.aliq_ipi,
							tbl_faturamento_item.base_icms,
							tbl_faturamento_item.valor_icms,
							tbl_faturamento_item.linha,
							tbl_faturamento_item.base_ipi,
							tbl_faturamento_item.valor_ipi,
							tbl_faturamento_item.sequencia
						FROM tbl_faturamento_item 
						JOIN tbl_faturamento      USING (faturamento)
						WHERE tbl_faturamento.fabrica           = $login_fabrica
						AND   tbl_faturamento.posto             = $login_posto
						AND   tbl_faturamento.extrato_devolucao = $extrato
						AND   tbl_faturamento_item.peca         = $peca
						AND   tbl_faturamento_item.preco        = $peca_preco 
						/*AND   tbl_faturamento_item.aliq_icms    = $peca_aliq_icms */";
			if ($verificacao <> '1'){
				$sql_nf .= "AND   tbl_faturamento_item.aliq_ipi = $peca_aliq_ipi ";
			}
			$sql_nf .= "AND   tbl_faturamento.distribuidor      IS NULL
						AND   tbl_faturamento.cancelada         IS NULL
						AND   (tbl_faturamento.cfop  = '5949' or tbl_faturamento.cfop = '6949')
						/*AND   tbl_faturamento_item.aliq_icms    > 0*/
						ORDER BY tbl_faturamento.nota_fiscal";
			$resNF = pg_exec ($con,$sql_nf);
			$qtde_peca_inserir=0;

			if (pg_numrows ($resNF)==0){
				$msg_erro .= "Erro.";
				$email_origem  = "helpdesk@telecontrol.com.br";
				$email_destino = 'helpdesk@telecontrol.com.br';
				$assunto       = "Extrato com erro LENOXX $extrato ";
				$corpo.="MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL \n\n $msg_erro \n $sql_nf";
				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
				@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem);
				break;
			}

			for ($w = 0 ; $w < pg_numrows ($resNF) ; $w++) {

				if ($qtde_peca_inserir < $peca_qtde_total_nf){

					$faturamento_item= pg_result ($resNF,$w,faturamento_item);
					$peca_nota       = pg_result ($resNF,$w,nota_fiscal);
					$peca_qtde       = pg_result ($resNF,$w,qtde);
					$peca_peca       = pg_result ($resNF,$w,peca);
					$peca_preco      = pg_result ($resNF,$w,preco);
					$peca_aliq_icms  = pg_result ($resNF,$w,aliq_icms);
					$peca_base_icms  = pg_result ($resNF,$w,base_icms);
					$peca_valor_icms = pg_result ($resNF,$w,valor_icms);
					$peca_linha      = pg_result ($resNF,$w,linha);
					$peca_aliq_ipi   = pg_result ($resNF,$w,aliq_ipi);
					$peca_base_ipi   = pg_result ($resNF,$w,base_ipi);
					$peca_valor_ipi  = pg_result ($resNF,$w,valor_ipi);
					$sequencia       = pg_result ($resNF,$w,sequencia);

					#HD 18528
					if ($verificacao=='1'){
						$aliq_ipi        = 0;
						$peca_valor_ipi  = 0;
						$peca_base_ipi   = 0;
					}

					if (strlen($peca_linha)==0){
						$peca_linha = " NULL ";
					}

					$qtde_peca_inserir += $peca_qtde;

					if ($qtde_peca_inserir > $peca_qtde_total_nf){
						$peca_base_icms  = 0;
						$peca_valor_icms = 0;
						$peca_base_ipi   = 0;
						$peca_valor_ipi  = 0;
						$peca_qtde       = $peca_qtde-$qtde_peca_inserir;
						$peca_qtde       = $peca_qtde - ($qtde_peca_inserir-$peca_qtde_total_nf);

						if ($peca_aliq_icms>0){
							$peca_base_icms = $peca_qtde_total_nf*$peca_preco;
							$peca_valor_icms= $peca_qtde_total_nf*$peca_preco*$peca_aliq_icms/100;
						}
						if ($peca_aliq_ipi>0){
							$peca_base_ipi = $peca_qtde_total_nf*$peca_preco;
							$peca_valor_ipi= $peca_qtde_total_nf*$peca_preco*$peca_aliq_ipi/100;
						}
					}

					$sql = "INSERT INTO tbl_faturamento_item
							(	faturamento, 
								peca, 
								qtde,
								preco, 
								aliq_icms, 
								aliq_ipi, 
								base_icms, 
								valor_icms, 
								linha, 
								base_ipi, 
								valor_ipi,
								nota_fiscal_origem,
								sequencia
							)
							VALUES (
								$faturamento_codigo, 
								$peca,
								$peca_qtde, 
								$peca_preco, 
								$peca_aliq_icms, 
								$peca_aliq_ipi, 
								$peca_base_icms, 
								$peca_valor_icms, 
								$peca_linha, 
								$peca_base_ipi, 
								$peca_valor_ipi,
								'$peca_nota',
								'$sequencia'
								)";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}else{
					break;
				}
			}

		}
	}

	if (strlen($msg_erro) == 0) {
		$sql_update = "UPDATE tbl_extrato_lgr 
				SET qtde_pedente_temp = null
				WHERE extrato = $extrato";
		$res_update = pg_exec ($con,$sql_update);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		if (count(array_unique($array_notas))<>$numero_de_notas){
			$msg_erro .= "Erro: não é permitido digitar número de notas iguais. Preencha novamente as notas.";
		}
	}

	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
		header("Location: extrato_posto_devolucao_lenoxx_itens.php?extrato=$extrato");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

/*
$sql = "SELECT posto,distribuidor,extrato_devolucao
		FROM tbl_faturamento
		WHERE distribuidor      = $login_posto
		AND   posto             = $posto_da_fabrica
		AND   extrato_devolucao = $extrato";
$res = pg_exec ($con,$sql);
if (pg_numrows($res)>0){
	#header("Location: extrato_posto_devolucao_lenoxx_itens.php?extrato=$extrato");
	#exit();
}
*/

$msg = "";

$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: red
}
.menu_top3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #FA8072
}


.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<script type="text/javascript">

function verificar(forrr){
	var theform = document.getElementById('frm_devolucao');
	var returnval=true;
	for (i=0; i<theform.elements.length; i++){
		if (theform.elements[i].type=="text"){
			if (theform.elements[i].value==""){ //if empty field
				alert("Por favor, informe todas as notas!");
				theform.botao_acao.value='';
				returnval=false;
				break;
			}
		}
	}
	return returnval;
}

</script>

<br><br>
<?

$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato ";
$res = pg_exec ($con,$sql);
$data    = pg_result ($res,0,data);
$periodo = pg_result ($res,0,periodo);
$nome    = pg_result ($res,0,nome);
$codigo  = pg_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='-1' face='arial'>$codigo - $nome</font>";

?> 

<p>
<div id='loading'></div>

<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg_erro ?></td>
	</tr>
</table>
<? } ?>

<center>

<br>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="10" class="menu_top" ><div align="center" style='font-size:16px'>
	<b>
	<?
		if ($pecas_pendentes=="sim") echo "DEVOLUÇÃO PENDENTE";
		else                         echo "ATENÇÃO";
	?>
	</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
	As peças ou produtos não devolvidos neste extrato serão cobrados do posto autorizado.
	<br><br>
	<b style='font-size:14px;font-weight:normal'>Emitir as NFs de devolução nos mesmos valores e impostos, referenciando NF de origem Lenoxx, e postagem da NF para Lenoxx</b>
	</TD>
</TR>
</table>

<br>

<? 
if($data =='16/10/2010') { 
	echo "<div style='width:650;padding: 10px 15px 15px 15px; text-align:left;border: 1pt groove red;' align='center'>Prezados,<br/> 
		<b>Informamos que o Sistema Telecontrol passou por um período de ajustes e nesta ocasião ocorreu um erro, <u>duplicando alguns pedidos de peças em garantia.</u></b><br/><br/>
		Nesta oportunidade aproveitamos para informá-los a respeito do ocorrido e que os itens enviados em duplicidade serão solicitados dentro desta rotina, excepcionalmente este mês, de forma separada, sendo sinalizados por dois espelhos de notas, conforme segue: <br/><br/>
		Espelho da nota a ser preenchida referente a: <b>PEÇAS – RETORNO OBRIGATÓRIO</b><br/><br/>
		Espelho da nota a ser preenchida referente a: <b>PEÇAS – ENVIADAS EM DUPLICIDADE</b> <br/><br/>
		<b>Obs.</b>: Em função do cadastro de peças, determinados itens (que foram enviados em duplicidade) serão cobrados na situação de retorno obrigatório. Melhor dizendo, serão mencionados no espelho referente à nota fiscal de retorno obrigatório. <br/><br/>
		Certo da colaboração de todos, <br/>
		Atenciosamente <br/><br/>
		<b>Departamento de Assistência Técnica – Lenoxx Sound</b></div>";
}

$nota_fiscal = "";

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

if ($numero_linhas!==5000){
	$distinct = " DISTINCT ";
}
	$sql = "SELECT $distinct
		tbl_faturamento.extrato_devolucao,
		tbl_faturamento.distribuidor,
		CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
		tbl_peca.devolucao_obrigatoria
	FROM    tbl_faturamento 
	JOIN    tbl_faturamento_item USING (faturamento) 
	JOIN    tbl_peca             USING (peca)
	WHERE   tbl_faturamento.extrato_devolucao = $extrato
	AND     tbl_faturamento.posto             = $login_posto
	AND     tbl_faturamento.distribuidor IS NULL
	AND     tbl_faturamento.cancelada is null
	AND   (tbl_faturamento.cfop  = '5949' or tbl_faturamento.cfop = '6949') 
	";
if ($verificacao=='1'){
	/*HD 49036 - Retirado produto_acabado */
	$sql .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE ";

	if($data == '16/10/2010') { # HD 302016 Peça enviada em duplicidade
		$sql .=" or (tbl_peca.devolucao_obrigatoria is not true and tbl_peca.produto_acabado is not true ) ";
	}
	$sql .=" ) ";
}

if ($numero_linhas!==5000){
	$sql .= " ORDER BY produto_acabado DESC , devolucao_obrigatoria DESC ";
}else{
	$sql .= " LIMIT 1";
}
//if ($ip=='201.71.54.144') echo $sql;
$res = pg_exec ($con,$sql);
#if ($ip=='200.228.76.7') echo nl2br("<br><br>".$sql);
#exit;

$res_qtde = pg_numrows ($res);

if ($res_qtde > 0) {

	echo "<form method='post' action='$PHP_SELF' name='frm_devolucao' id='frm_devolucao'>";
	echo "<input type='hidden' name='notas_d' value=''>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>\n";

	$contador=0;

	for( $xx = 0; $xx < $res_qtde ; $xx++) {

		$distribuidor          = trim (pg_result ($res,$xx,distribuidor));
		$produto_acabado       = trim (pg_result ($res,$xx,produto_acabado));
		$devolucao_obrigatoria = trim (pg_result ($res,$xx,devolucao_obrigatoria));
		$extrato_devolucao     = trim (pg_result ($res,$xx,extrato_devolucao));

		$extrato_devolucao = $extrato;

		$condicao_3 = "";
		$mostra_qtde = "";
		if ($produto_acabado == "NOT TRUE"){
			$devolucao = " ENVIADA EM DUPLICIDADE";
			$movimento = "NAO_RETOR.";
			$pecas_produtos = "PEÇAS";
			$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
			$condicao_4 = " AND tbl_faturamento_item.pedido in (
						SELECT distinct tbl_pedido.pedido
					FROM tbl_pedido
					JOIN tbl_pedido_item USING(pedido)
					JOIN tbl_faturamento_item USING(pedido,peca)
					JOIN tbl_faturamento USING(faturamento)
					LEFT JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido_item.pedido and tbl_os_item.peca = tbl_pedido_item.peca
					WHERE tbl_pedido.fabrica = $login_fabrica
					AND   tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_pedido.posto = $login_posto
					AND   tbl_faturamento.posto = $login_posto
					AND   tbl_pedido_item.qtde_cancelada =0
					AND   tbl_pedido.status_pedido <>14
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   data >= '2010-09-13 00:00:00'
					AND   tbl_os_item.os_item IS NULL
			) ";
			$sql_adicional_peca2 = " AND tbl_peca.devolucao_obrigatoria IS NOT TRUE";
		}

		if ($devolucao_obrigatoria == 't'){
			$devolucao = " RETORNO OBRIGATÓRIO ";
			$movimento = "RETORNAVEL";
			$pecas_produtos = "PEÇAS";
			$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
			$condicao_3 = "";
			$condicao_4 = "";
			$sql_adicional_peca2 = " AND tbl_peca.devolucao_obrigatoria IS TRUE";
			$mostra_qtde = true;
		}

		if ($produto_acabado == "TRUE"){
			$devolucao = " RETORNO OBRIGATÓRIO ";
			$movimento = "RETORNAVEL";
			$pecas_produtos = "PRODUTOS";
			$condicao_2 = " AND tbl_peca.produto_acabado IS TRUE ";
			$condicao_3 = "";
			$condicao_4 = "";
			$sql_adicional_peca2 = "";
		}

		$razao    = "LENOXX SOUND";
		$endereco = "Rua .................";
		$cidade   = "São Paulo";
		$estado   = "SP";
		$cep      = "0000000000";
		$fone     = "(11) 3339955";
		$cnpj     = "9999999/9999-99";
		$ie       = "999.999.999";

	
		if (strlen ($posto_da_fabrica) > 0) {
			$sql  = "SELECT * FROM tbl_posto WHERE posto = $posto_da_fabrica";
			$resX = pg_exec ($con,$sql);

			$estado   = pg_result ($resX,0,estado);
			$razao    = pg_result ($resX,0,nome);
			$endereco = trim (pg_result ($resX,0,endereco)) . " " . trim (pg_result ($resX,0,numero));
			$cidade   = pg_result ($resX,0,cidade);
			$estado   = pg_result ($resX,0,estado);
			$cep      = pg_result ($resX,0,cep);
			$fone     = pg_result ($resX,0,fone);
			$cnpj     = pg_result ($resX,0,cnpj);
			$ie       = pg_result ($resX,0,ie);
		}else{
			$sql  = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
			$resX = pg_exec ($con,$sql);

			$razao    = pg_result ($resX,0,razao_social);
			$endereco = pg_result ($resX,0,endereco);
			$cidade   = pg_result ($resX,0,cidade);
			$estado   = pg_result ($resX,0,estado);
			$cep      = pg_result ($resX,0,cep);
			$fone     = pg_result ($resX,0,fone);
			$cnpj     = pg_result ($resX,0,cnpj);
			$ie       = pg_result ($resX,0,ie);
		}

		$condicao_3 = "  AND tbl_faturamento.distribuidor IS NULL ";

		$distribuidor = "null";
		$condicao_1   = " AND tbl_faturamento.distribuidor IS NULL ";

		if ($numero_linhas!=5000){
			//$sql_adicional_peca=" AND tbl_extrato_lgr.qtde_pedente_temp>0";
		}else{
			$sql_adicional_peca="";
		}


		$sql = "SELECT  
				tbl_peca.peca, 
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.ipi, 
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
				tbl_peca.devolucao_obrigatoria,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.preco,
				sum(tbl_faturamento_item.qtde) as qtde_real,
				tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END AS qtde_total_item,
				tbl_extrato_lgr.qtde_nf AS qtde_total_nf,
				tbl_extrato_lgr.qtde_pedente_temp AS qtde_pedente_temp,
				tbl_extrato_lgr.extrato_lgr AS extrato_lgr,
				(tbl_extrato_lgr.qtde_pedente_temp * tbl_faturamento_item.preco) AS total_item,
				tbl_faturamento.cfop,
				SUM (tbl_faturamento_item.base_icms) AS base_icms, 
				SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
				SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
				SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
				FROM tbl_peca
				JOIN tbl_faturamento_item USING (peca)
				JOIN tbl_faturamento      USING (faturamento)
				JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato=tbl_faturamento.extrato_devolucao AND tbl_extrato_lgr.peca=tbl_faturamento_item.peca
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND   tbl_faturamento.extrato_devolucao = $extrato
				AND   tbl_faturamento.posto=$login_posto
				AND (tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END)>0
				AND   (tbl_faturamento.cfop  = '5949' or tbl_faturamento.cfop = '6949')
				AND tbl_faturamento.cancelada is null 
				$condicao_1
				$condicao_2
				$condicao_3
				$condicao_4
				$sql_adicional_peca
				$sql_adicional_peca2
				AND   tbl_faturamento.emissao > '2005-10-01'
				GROUP BY tbl_peca.peca, 
					tbl_peca.referencia, 
					tbl_peca.descricao,
					tbl_peca.devolucao_obrigatoria, 
					tbl_peca.produto_acabado, 
					tbl_peca.ipi,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco,
					tbl_faturamento.cfop,
					tbl_extrato_lgr.qtde,
					total_item,
					qtde_total_nf,
					qtde_pedente_temp,
					extrato_lgr
				ORDER BY tbl_peca.referencia";
				//				AND tbl_faturamento.cfop IN ('694921','694922','594919','594920','594921','594922','594923')
//if ($ip=='201.71.54.144') echo nl2br("<br><hr>".$sql);
		$notas_fiscais=array();
		$qtde_peca=0;
		$resX = pg_exec ($con,$sql);

//if ($ip=='201.71.54.144') echo "--------".pg_numrows ($resX)."sdasas";

		if (pg_numrows ($resX)==0) continue;
	
		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$aliq_final       = 0;
		$tota_pecas       = 0;
		$peca_ant="";
		$qtde_acumulada=0;
		
		$z=0;
		$total_qtde = pg_numrows ($resX);
		for ($x = 0 ; $x < $total_qtde ; $x++) {
//if ($ip=='201.71.54.144') echo "<br><br>qualquer coisa";
			$tem_mais_itens = 'sim';

			$contador++;
			$item_nota++;
			$z++;

			$peca                = pg_result ($resX,$x,peca);
			$peca_referencia     = pg_result ($resX,$x,referencia);
			$peca_descricao      = pg_result ($resX,$x,descricao);
			$peca_preco          = pg_result ($resX,$x,preco);
			$qtde_real           = pg_result ($resX,$x,qtde_real);
			$qtde_total_item     = pg_result ($resX,$x,qtde_total_item);
			$qtde_total_nf       = pg_result ($resX,$x,qtde_total_nf);
			$qtde_pedente_temp   = pg_result ($resX,$x,qtde_pedente_temp);
			$qtde_pedente_temp_AUX= pg_result ($resX,$x,qtde_pedente_temp);
			$extrato_lgr         = pg_result ($resX,$x,extrato_lgr);
			$total_item          = pg_result ($resX,$x,total_item);
			$base_icms           = pg_result ($resX,$x,base_icms);
			$valor_icms          = pg_result ($resX,$x,valor_icms);
			$aliq_icms           = pg_result ($resX,$x,aliq_icms);
			$base_ipi            = pg_result ($resX,$x,base_ipi);
			$aliq_ipi            = pg_result ($resX,$x,aliq_ipi);
			$valor_ipi           = pg_result ($resX,$x,valor_ipi);
			$ipi                 = pg_result ($resX,$x,ipi);
			$cfop                = pg_result ($resX,$x,cfop);
			$peca_produto_acabado= pg_result ($resX,$x,produto_acabado);
			$peca_devolucao_obrigatoria= pg_result ($resX,$x,devolucao_obrigatoria);

			if ($qtde_pedente_temp>$qtde_real AND $numero_linhas!=5000){
				$qtde_pedente_temp=$qtde_real;
			}
			
			

			$qtde_acumulada = $qtde_real;
			$qtde_acumulada += $qtde_real;

			$sql_nf = "		SELECT	tbl_faturamento.nota_fiscal,
									tbl_faturamento_item.qtde
							FROM tbl_faturamento_item 
							JOIN tbl_faturamento      USING (faturamento)
							WHERE tbl_faturamento.fabrica = $login_fabrica
							AND   tbl_faturamento.posto   = $login_posto
							AND   tbl_faturamento.extrato_devolucao = $extrato
							AND   tbl_faturamento.cancelada      IS NULL
							AND tbl_faturamento_item.peca=$peca
							/*hd 20204*/
							AND   tbl_faturamento_item.preco     = $peca_preco
							AND   tbl_faturamento_item.aliq_icms = $aliq_icms ";
			if ($verificacao <> '1'){
				$sql_nf .= "AND   tbl_faturamento_item.aliq_ipi  = $aliq_ipi ";
			}
			$sql_nf .= "	AND   tbl_faturamento.cfop = $cfop::text 
							ORDER BY tbl_faturamento.nota_fiscal";
//if ($ip=='201.71.54.144' and $peca==565467) echo nl2br("<br><hr>".$sql_nf);
			$resNF = pg_exec ($con,$sql_nf);
			
			if (strlen($qtde_total_nf)==0) {
				$qtde_total_nf=0;
			}

			$qtde_aux=0;
			$qtde_peca=0;

			if (strlen($qtde_pedente_temp)==0){
				$qtde_pedente_temp=$qtde_total_item;
			}

			for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
				
				//if ($ip=='201.71.54.144' and $peca==565467) echo "<br>antes do if = ".pg_result ($resNF,$y,nota_fiscal);
				
				//if ($ip=='201.71.54.144' and $peca==565467) echo "<br>qtde_aux menor qtde_total_nf = $qtde_aux menor $qtde_total_nf";
				if ($qtde_aux<$qtde_total_nf) {
				//	if ($ip=='201.71.54.144' and $peca==565467) echo "<br>1º if = ".pg_result ($resNF,$y,nota_fiscal);
					$qtde_aux += pg_result ($resNF,$y,qtde);
					continue;
				}

				//if ($ip=='201.71.54.144' and $peca==565467) echo "<br>qtde_peca menor_igual qtde_real = $qtde_peca menor_igual $qtde_real";
				if ($qtde_peca <= $qtde_real){
					//if ($ip=='201.71.54.144' and $peca==565467) echo "<br>2º if = ".pg_result ($resNF,$y,nota_fiscal);

					$qtde_peca += pg_result ($resNF,$y,qtde);
					
					array_push($notas_fiscais,pg_result ($resNF,$y,nota_fiscal));
				}
				
				$notas_fiscais = array_unique($notas_fiscais);
				asort($notas_fiscais);
//if ($ip=='201.71.54.144' and $peca==565467) echo "<br>".implode(',',$notas_fiscais);
			}


			if (strlen ($aliq_icms)  == 0) {
				$aliq_icms = 0;
			}

			if (strlen($aliq_ipi)==0) {
				$aliq_ipi=0;
			}

			if ($verificacao=='1'){
				$aliq_ipi = "0"; #HD 18528
				if ($aliq_ipi>0){
					$peca_preco = $peca_preco + ($peca_preco * $aliq_ipi/100);
				}
			}

			$total_item  = $peca_preco * $qtde_real;
			$tota_pecas += $total_item;

			if ($aliq_icms==0){
				$base_icms=0;
				$valor_icms=0;
			}else{
				$base_icms  = $total_item;
				$valor_icms = $total_item * $aliq_icms / 100;
			}

			if ($peca_produto_acabado=='NOT TRUE'){ # se for peca, IPI = 0
				//$aliq_ipi=0;
			}

			if ($aliq_ipi==0) 	{
				$base_ipi=0;
				$valor_ipi=0;
			}else {
				$base_ipi  = $total_item;
				$valor_ipi = $total_item*$aliq_ipi/100;
			}

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi   += $base_ipi;
			$total_valor_ipi  += $valor_ipi;
			$total_nota       += $total_item;


			/* CABEÇALHO DA NOTA */
			$cabecalho  = "<br><br>\n";
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

			$cabecalho .= "<tr align='left'  height='16'>\n";
			$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			$cabecalho .= "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
			$cabecalho .= "</td>\n";
			$cabecalho .= "</tr>\n";

			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Natureza <br> <b>Devolução de Garantia</b> </td>\n";
			$cabecalho .= "<td>CFOP <br> <b> $cfop </b> </td>\n";
			$cabecalho .= "<td>Emissão <br> <b>".date("d/m/Y")."</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$cnpjx = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
			$cabecalho .= "<td>CNPJ <br> <b>$cnpjx</b> </td>\n";
			$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$cepx = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
			$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
			$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
			$cabecalho .= "<td>CEP <br> <b>$cepx</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";

			$topo ="";
			$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
			$topo .=  "<thead>\n";

			$topo .=  "<tr align='center'>\n";
			$topo .=  "<td><b>Código</b></td>\n";
			$topo .=  "<td><b>Descrição</b></td>\n";
			$topo .=  "<td><b>Qtde.</b></td>\n";
			$topo .=  "<td><b>Preço</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			if ($verificacao <> '1'){
				$topo .=  "<td><b>% IPI</b></td>\n";
			}
			$topo .=  "</tr>\n";
			$topo .=  "</thead>\n";
			/* FIM CABEÇALHO DA NOTA */
//if ($ip=='201.71.54.144') echo nl2br("<br><br>".$cabecalho);

			if ( ( $x == 0 OR $imprimir_cabecalho == 1 ) AND $numero_linhas!=5000 ){
				echo $cabecalho;
				echo $topo;
				$imprimir_cabecalho=0;
			}
			
			if ( $numero_linhas!=5000 ){
				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
				echo "<td align='left'>";
				echo "$peca_referencia";
				echo "<input type='hidden' name='id_item_LGR_$item_nota-$numero_nota' value='$extrato_lgr'>\n";
				echo "<input type='hidden' name='id_item_peca_$item_nota-$numero_nota' value='$peca'>\n";
				echo "<input type='hidden' name='id_item_preco_$item_nota-$numero_nota' value='$peca_preco'>\n";
				echo "<input type='hidden' name='id_item_qtde_$item_nota-$numero_nota' value='$qtde_real'>\n";
				echo "<input type='hidden' name='id_item_icms_$item_nota-$numero_nota' value='$aliq_icms'>\n";
				echo "<input type='hidden' name='id_item_ipi_$item_nota-$numero_nota' value='$aliq_ipi'>\n";
				echo "<input type='hidden' name='id_item_total_$item_nota-$numero_nota' value='$total_item'>\n";
				echo "</td>\n";
				echo "<td align='left'>$peca_descricao</td>\n";
				echo "<td align='center'>$qtde_real</td>\n";
				echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
				echo "<td align='right'>$aliq_icms</td>\n";
				if ($verificacao <> '1'){
					echo "<td align='right'>$aliq_ipi</td>\n";
				}
				echo "</tr>\n";
			}


			if ($numero_linhas !=5000 AND ($z%$numero_linhas==0 OR $x+1 == $total_qtde)){
				//$total_valor_icms = $total_base_icms * $aliq_final / 100;
				if ($verificacao=='1'){
					$total_geral = $total_nota;
				}else{
					$total_geral = $total_nota+$total_valor_ipi;
				}
				echo "</table>\n";
				echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
				echo "<tr>\n";
				echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>\n";
				echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";
				if ($verificacao=='1'){
					echo "<td>Total de Peças <br> <b> " . number_format ($tota_pecas,2,",",".") . " </b> </td>\n";
				}else{
					echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>\n";
					echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>\n";
				}
				echo "<td>Total da Nota <br> <b> " . number_format ($total_geral,2,",",".") . " </b> </td>\n";
				echo "</tr>\n";
				if (count($notas_fiscais)>0){
					echo "<tfoot>";
					echo "<tr>";
					echo "<td colspan='8'> Referente as NFs. " . implode(", ",$notas_fiscais) . "</td>";
					echo "</tr>";
					echo "</tfoot>";
				}
				$notas_fiscais=array();
				$qtde_peca="";
				echo "</table>\n";

				if (strlen ($nota_fiscal)==0) {
					echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
					echo "<tr>";
					echo "<td>";
					echo "\n<br>";
//					echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-cfop'       value='$cfop'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-movimento'  value='$movimento'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-base_icms'  value='$total_base_icms'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi'   value='$total_base_ipi'>\n";
					echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi'  value='$total_valor_ipi'>\n";
					echo "<center>";
					echo "<b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";
					echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='8' value='$nota_fiscal'>";
					echo "<br><br>";
					echo "</td>";
					echo "</tr>";
					echo "</table>";
					$numero_nota++;
				}else{
					if (strlen ($nota_fiscal) >0){
						echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
						echo "<tr>\n";
						echo "<td><h1><center>Nota de Devolução $nota_fiscal</center></h1></td>\n";
						echo "</tr>";
						echo "</table>";
					}
				}

				$imprimir_cabecalho = 1;
				$total_base_icms  = 0;
				$total_valor_icms = 0;
				$total_base_ipi   = 0;
				$total_valor_ipi  = 0;
				$total_nota       = 0;
				$tota_pecas       = 0;
				$item_nota=0;
			}

			flush();
		}

		echo "</table>\n";
	}



	if ($numero_linhas==5000){

			if ($pecas_pendentes=='sim'){
				echo "<input type='hidden' name='pendentes' value='sim'>";
			}

			echo "<br>
					<input type='hidden' name='qtde_pecas' value='$contador'>
					<IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'> 
					<b style='font-size:12px'>

		
					<b>Informar a quantidade de linhas no formulário da Nota Fiscal do Posto Autorizado:</b>
					<input type='text' size='5' maxlength='3' value='' name='qtde_linha'><br>
					Essa informação definirá a quantidade de NFs que o posto autorizado deverá emitir e enviar à Fábrica
					<br><br>
					<input type='button' id='fechar' value='Gerar Nota Fiscal de Devolução' name='gravar' onclick=\"javascript:
					if(document.frm_devolucao.qtde_linha.value=='' || document.frm_devolucao.qtde_linha.value=='0')
							alert('Informe a quantidade de itens!!');
					else{
						if (document.frm_devolucao.botao_acao.value=='digitou_qtde'){
							alert('Aguarde submissão');
						}
						else{
							document.frm_devolucao.botao_acao.value='digitou_qtde';
							this.form.submit();
						}
					}
						\"><br><br>
				  ";
	}else{


		# Se tiver extrato, verifica se o PA digitou as notas
		$sqlConf = "
					SELECT extrato,admin_lgr
					FROM tbl_extrato 
					WHERE fabrica=11 
					AND posto=$login_posto
					AND extrato < $extrato
					AND extrato > 206832
					AND liberado IS NOT NULL 
					ORDER BY data_geracao DESC 
					LIMIT 1";
		$resConf = pg_exec ($con,$sqlConf);
		if (pg_numrows($resConf)>0){

			$admin_lgr  = trim(pg_result($resConf,0,admin_lgr));

			# Verifica se as notas de devolução do Mes anterior foi recebido pela Fabrica
			$sqlConf = "SELECT	faturamento, 
								nota_fiscal,
								emissao - CURRENT_DATE AS dias_emitido, 
								conferencia,
								devolucao_concluida
						FROM tbl_faturamento
						WHERE fabrica = $login_fabrica
						AND distribuidor = $login_posto
						AND   tbl_faturamento.cancelada      IS NULL
						AND   (tbl_faturamento.cfop  = '5949' or tbl_faturamento.cfop = '6949')
						AND posto IS NOT NULL
						AND extrato_devolucao in (
									SELECT extrato
									FROM tbl_extrato 
									WHERE fabrica = $login_fabrica 
									AND posto     = $login_posto
									AND extrato < $extrato
									AND liberado IS NOT NULL 
									ORDER BY data_geracao DESC 
									LIMIT 1
						)";
			$resConf = pg_exec ($con,$sqlConf);
			$notas_array = array();
			$msg_notas = "";

			$sqlLgr = " SELECT count(*)
						FROM   tbl_faturamento
						JOIN   tbl_faturamento_item USING (faturamento)
						JOIN   tbl_peca             USING (peca)
						WHERE  tbl_faturamento.fabrica    = $login_fabrica
						AND    tbl_faturamento.cancelada  IS NULL
						AND   tbl_faturamento.extrato_devolucao in (
									SELECT extrato
									FROM tbl_extrato 
									WHERE fabrica = $login_fabrica 
									AND posto     = $login_posto
									AND extrato < $extrato
									AND liberado IS NOT NULL 
									ORDER BY data_geracao DESC 
									LIMIT 1
						)
						AND   (tbl_faturamento.cfop  = '5949' or tbl_faturamento.cfop = '6949')
						AND   tbl_faturamento.distribuidor IS NULL ";
			if ($verificacao=='1'){
				/*HD 49036 - Retirado produto_acabado */
				$sqlLgr .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE) ";
			}
			$resLGR = pg_exec ($con,$sqlLgr);
			$qtde_devolucao = trim(@pg_result($resLGR,0,0));

			if (pg_numrows($resConf)>0 OR $qtde_devolucao == 0){
				for ( $w=0; $w < pg_numrows($resConf); $w++ ){
					$fat_faturamento  = trim(pg_result($resConf,$w,faturamento));
					$fat_nota_fiscal  = trim(pg_result($resConf,$w,nota_fiscal));
					$fat_dias_emitido = trim(pg_result($resConf,$w,dias_emitido));
					$fat_conferencia  = trim(pg_result($resConf,$w,conferencia));
					$fat_concluido    = trim(pg_result($resConf,$w,devolucao_concluida));
					
					// $admin_lgr -> se a Fábrica liberou o mes anterior, deixa digitar este mes
					if (strlen($admin_lgr)==0 AND strlen($fat_conferencia)==0 AND $fat_concluido!='t'){ 
						array_push($notas_array,$fat_nota_fiscal);
					}
				}
				if (count($notas_array)==1){
					$msg_notas = "O Fabricante não acusou o recebimento da nota N° ".implode(",",$notas_array).". Favor entrar em contato c/ a Taiz TEL: 071 3379-1997, P/ posterior liberação da M.O.";
				}elseif(count($notas_array)>1){
					$msg_notas = "O Fabricante não acusou o recebimento das notas N° ".implode(",",$notas_array).". Favor entrar em contato c/ o Sr. Alberto TEL: 071 3379-1997, P/ posterior liberação da M.O.";
				}
			}else{
				# Se nenhuma nota foi preenchida
				$msg_notas = "As notas fiscais referente ao extrato anterior não foram preenchidas.";
			}
		}

		if(strlen($msg_notas)==0){
			echo "<br><br><br>
					<input type='hidden' name='qtde_linha' value='$numero_linhas'>
					<input type='hidden' name='numero_de_notas' value='$numero_nota'>
					<input type='hidden' name='numero_de_notas_tc' value='$numero_nota_tc'>
					
					<b>Preencha TODAS as notas acima e clique no botão abaixo para confirmar!</b>
					<br><br>
					<input type='button' value='Confirmar notas de devolução' name='gravar' onclick=\"javascript:
						if (document.frm_devolucao.botao_acao.value=='digitou_as_notas') {
							alert('Aguarde Submissão');
						}else{
							if(confirm('Deseja continuar? As notas de devolução não poderão ser alteradas!')){
								if (verificar('frm_devolucao')){
									document.frm_devolucao.botao_acao.value='digitou_as_notas';
									document.frm_devolucao.submit();
								}
							}
						}
						\">
					
					<br>";
					
			echo "<br><br><input type='button' value='Voltar a Tela Anterior' name='gravar' onclick=\"javascript:
					if(confirm('Deseja voltar?')) window.location='$PHP_SELF?extrato=$extrato';\">";
		}else{
			echo "<h4>$msg_notas</h4>";
		}

	}
	echo "</form>";
}else{
	echo "<h1><center> Não há peças para devolução </center></h1>";
}

?>


<? include "rodape.php"; ?>
