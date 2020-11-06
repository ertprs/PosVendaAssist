<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";


if ($login_posto==1537){ // provisorio
//	header("Location: new_extrato_posto.php");
//	exit();
}


$msg_erro="";
$msg="";

$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$ok_aceito="nao";
$tem_mais_itens='nao';
$contadorrr=0;

###### HABILITAR ESTE IF APï¿½S A EFETIVAï¿½ï¿½O #######
if ($extrato<144000){
//	header("Location: new_extrato_posto.php");
//	exit();
}

/*
POSTOS QUE PODEM ACESSAR ESTA TELA

Martello ï¿½ 2073 - 595 ---- OK - falta exprotar
Penha ï¿½ 80039 - 1537 -     notas zuadas
Janaï¿½na ï¿½ 80330 - 1773 - OK - falta exportar
Bertolucci - 80568 - 7080  --OK - falta exportar
Tecservi ï¿½ 80459 - 5037  --OK - falta exportar
NL ï¿½ 80636 - 13951        -OK - falta exportar (jah estava digitada)
Telecontrol ï¿½ 93509 - 4311
A.Carneiro ï¿½ 1256 - 564   -OK - falta exportar (jah estava digitada)
Centerservice 80150 - 1623  -OK - falta exportar (jah estava digitada)
Visiontec -  80200 - 1664   -NAO DIGITOU AINDA

*/

/*
###### NOVOS POSTOS

Nipon –           80437  - 2506
MR –              80539  - 6458
Bom Jesus –       80002  - 1511
Eletro Center –  601049  - 1870
Multitécnica –    38086  - 1266
Central B & B –   80540  - 6591
Edivideo –        80462  - 5496
Maria Suzana –    80685  - 14296
Moacir Florêncio  80492  - 6140
Luiz Claudio –    32051  - 1161
JC & M –          80424  - 1962

*/

//header("Location: new_extrato_posto.php");
//exit();

$postos_permitidos = array(0 => 'LIXO', 1 => '1537', 2 => '1773', 3 => '7080', 4 => '5037', 5 => '13951', 6 => '4311', 7 => '564', 8 => '1623', 9 => '1664',10 => '595',11 => '2506', 12 => '6458', 13 => '1511', 14 => '1870', 15 => '1266', 16 => '6591', 17 => '5496', 18 => '14296', 19 => '6140', 20 => '1161', 21 => '1962');

if (array_search($login_posto, $postos_permitidos)==0){ //verifica se o posto tem permissao
	header("Location: new_extrato_posto.php");
	exit();
}


$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0)
	$extrato = trim($_POST['extrato']);

if (strlen($extrato)==0){
	header("Location: new_extrato_posto.php");
}

$pecas_pendentes = trim($_GET['pendentes']);
if (strlen($pecas_pendentes)==0)
	$pecas_pendentes = trim($_POST['pendentes']);


$query = "SELECT count(*) FROM tbl_extrato_lgr WHERE extrato=$extrato AND posto=$login_posto AND qtde-qtde_nf>0";
$res = pg_exec ($con,$query);
if ( pg_result ($res,0,0)>0){
	$tem_mais_itens='sim';
}

// verificaÃ§Ã£o se o posto quer ver a Mao de obra mas ele ainda nï¿½o preencheu as notas
$mao = trim($_GET['mao']);
if (strlen($mao)>0 AND $mao=='sim'){
	$sql = "SELECT  faturamento,
			extrato_devolucao,
			nota_fiscal,
			distribuidor,
			NULL as produto_acabado,
			NULL as devolucao_obrigatoria
		FROM tbl_faturamento
		WHERE posto=13996
		AND distribuidor=$login_posto
		AND fabrica=$login_fabrica
		AND extrato_devolucao=$extrato
		ORDER BY faturamento ASC";
	$res = pg_exec ($con,$sql);
	$jah_digitado=pg_numrows ($res);
	if ($jah_digitado>0){
		header("Location: new_extrato_posto_mao_obra.php?extrato=$extrato");
		exit();
	}
	else{
		$msg_erro="Devem ser preenchidas as Notas Fiscais de devolução de Produtos e peças para liberar a tela de consulta de valores de mão-de-obra - extrato";
	}
}


$ok_aceito = trim($_POST['ok_aceito']);
if ($ok_aceito=='Concordo') 
	$numero_linhas = trim($_POST['qtde_linha']);

$btn_acao = trim($_POST['botao_acao']);

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_qtde") {


	$sql_update = "UPDATE tbl_extrato_lgr 
			SET qtde_pedente_temp = null
			WHERE extrato=$extrato";
	$res_update = pg_exec ($con,$sql_update);
	$msg_erro .= pg_errormessage($con);

	$numero_linhas   = trim($_POST['qtde_linha']);
	$qtde_pecas      = trim($_POST['qtde_pecas']);
	$pecas_pendentes = trim($_POST['pendentes']);

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	for($i=1;$i<=$qtde_pecas;$i++){

		$extrato_lgr = trim($_POST["item_$i"]);
		$peca_tem = trim($_POST["peca_tem_$i"]);
		$peca = trim($_POST["peca_$i"]);
		$qtde_pecas_devolvidas = trim($_POST["$extrato_lgr"]);

		if ($peca_tem>$qtde_pecas_devolvidas){
			$diminuiu='sim';
		}

		if (strlen($qtde_pecas_devolvidas)>0){
			
				$sql_update = "UPDATE tbl_extrato_lgr 
						SET qtde_pedente_temp = $qtde_pecas_devolvidas
						WHERE extrato=$extrato
						AND peca=$peca";
				$res_update = pg_exec ($con,$sql_update);
				$msg_erro .= pg_errormessage($con);
			

		}
		else{
			//$msg_erro="Informe a quantidade de peças que serão devolvidas!";
		}
		if (strlen($msg_erro)>0) break;
	}

	if (strlen($msg_erro) == 0) {
		//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		echo $lista_pecas;
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_as_notas") {

	$qtde_pecas = trim($_POST['qtde_pecas']);
	$numero_linhas = trim($_POST['qtde_linha']);
	$numero_de_notas = trim($_POST['numero_de_notas']);
	$data_preenchimento = date("Y-m-d");
	$array_notas = array();



	$sql = "SELECT posto,distribuidor,extrato_devolucao
			FROM tbl_faturamento
			WHERE distribuidor=$login_posto
			AND posto=13996
			AND extrato_devolucao=$extrato";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res)>0){
		//header("Location: new_extrato_posto.php");
		//exit();
	}
	
	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	for($i=0;$i<$numero_de_notas;$i++){

			
			$nota_fiscal = trim($_POST["nota_fiscal_$i"]);

			if (strlen($nota_fiscal)==0){
				$msg_erro='Digite todas as notas fiscais!';
				break;
			}
	
			array_push($array_notas,$nota_fiscal);

			$nota_fiscal = str_replace(".","",$nota_fiscal);
			$nota_fiscal = str_replace(",","",$nota_fiscal);
			$nota_fiscal = str_replace("-","",$nota_fiscal);


			$total_nota = trim($_POST["id_nota_$i-total_nota"]);
			$base_icms = trim($_POST["id_nota_$i-base_icms"]);
			$valor_icms = trim($_POST["id_nota_$i-valor_icms"]);
			$base_ipi = trim($_POST["id_nota_$i-base_ipi"]);
			$valor_ipi = trim($_POST["id_nota_$i-valor_ipi"]);

			//$linha_nota = trim($_POST["id_nota_$i-linha"]);

			$qtde_peca_na_nota = trim($_POST["id_nota_$i-qtde_itens"]);


//echo "<br><br>NOTA: $total_nota |  $base_icms |  $valor_icms |  $base_ipi |  $valor_ipi | qtde=$qtde_peca_na_nota<br><br>";

 			$sql = "INSERT INTO tbl_faturamento		  
					(fabrica, emissao,saida, posto, distribuidor, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi, extrato_devolucao, obs)
					VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',13996,$login_posto,$total_nota,'$nota_fiscal','2','Devolução de peças em garantia', $base_icms, $valor_icms, $base_ipi, $valor_ipi, $extrato, 'Devolução de peças do posto para à  Fábrica')";
			$res = pg_exec ($con,$sql);

			$sql = "SELECT CURRVAL ('seq_faturamento')";
			$resZ = pg_exec ($con,$sql);
			$faturamento_codigo = pg_result ($resZ,0,0);


			for($x=1;$x<=$qtde_peca_na_nota;$x++){

				$lgr                = trim($_POST["id_item_LGR_$x-$i"]);
				$peca               = trim($_POST["id_item_peca_$x-$i"]);
				$peca_preco         = trim($_POST["id_item_preco_$x-$i"]);
				$peca_qtde_total_nf = trim($_POST["id_item_qtde_$x-$i"]);
				$peca_aliq_icms     = trim($_POST["id_item_icms_$x-$i"]);
				$peca_aliq_ipi      = trim($_POST["id_item_ipi_$x-$i"]);
				$peca_total_item    = trim($_POST["id_item_total_$x-$i"]);


				$sql_update = "UPDATE tbl_extrato_lgr 
						SET qtde_nf = (CASE WHEN qtde_nf IS NULL THEN 0 ELSE qtde_nf END) + $peca_qtde_total_nf
						WHERE extrato=$extrato
						AND peca=$peca";
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
							WHERE tbl_faturamento.fabrica = $login_fabrica
							AND   tbl_faturamento.posto   = $login_posto
							AND   tbl_faturamento.extrato_devolucao = $extrato
							AND   tbl_faturamento_item.peca=$peca
							AND   tbl_faturamento_item.preco=$peca_preco
							AND   tbl_faturamento.distribuidor IS NULL
							AND   tbl_faturamento_item.aliq_icms>0
							ORDER BY tbl_faturamento.nota_fiscal";
//echo "<br><br>$sql_nf";
//echo "<br>$peca | $peca_preco |	$peca_qtde_total_nf | $peca_total_item ";
						
				$resNF = pg_exec ($con,$sql_nf);
				$qtde_peca_inserir=0;
				if (pg_numrows ($resNF)==0){
					$msg_erro .= "Erro. Verificar.( $sql_nf )";
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
						

						$qtde_peca_inserir += $peca_qtde;

						if ($qtde_peca_inserir > $peca_qtde_total_nf){
//							echo "<br><br>Precisa desmembrar<br><br>";
							$peca_base_icms  = 0;
							$peca_valor_icms = 0;
							$peca_base_ipi   = 0;
							$peca_valor_ipi  = 0;
//							$peca_qtde       = $peca_qtde-$qtde_peca_inserir;
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

						if (strlen($peca_linha)==0) $peca_linha = "2";
						$sql = "INSERT INTO tbl_faturamento_item		  
								(faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, linha, base_ipi, valor_ipi,nota_fiscal_origem,sequencia)
								VALUES ($faturamento_codigo, $peca,$peca_qtde, $peca_preco, $peca_aliq_icms, $peca_aliq_ipi, $peca_base_icms, $peca_valor_icms, $peca_linha, $peca_base_ipi, $peca_valor_ipi,'$peca_nota','$sequencia')";
						$res = pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}else{
						break; //echo "<br>Break<br>";
					}
				}

			}


	}

	if (strlen($msg_erro) == 0) {
		$sql_update = "UPDATE tbl_extrato_lgr 
				SET qtde_pedente_temp = null
				WHERE extrato=$extrato";
		$res_update = pg_exec ($con,$sql_update);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		array_unique($array_notas);
		if (count($array_notas)<>$numero_de_notas){
			$msg_erro .= "Erro: não é permitido digitar número de notas iguais. Preencha novamente as notas.";
		}
	}

	

	if (strlen($msg_erro) == 0) {
		//$resX = pg_exec ($con,"COMMIT TRANSACTION");
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


$sql = "SELECT  faturamento,
		extrato_devolucao,
		nota_fiscal,
		distribuidor,
		NULL as produto_acabado,
		NULL as devolucao_obrigatoria
	FROM tbl_faturamento
	WHERE posto=13996
	AND distribuidor=$login_posto
	AND fabrica=$login_fabrica
	AND extrato_devolucao=$extrato
	ORDER BY faturamento ASC";
$res = pg_exec ($con,$sql);
$jah_digitado=pg_numrows ($res);

if ($jah_digitado>0 AND $pecas_pendentes!= 'sim'){
	header("location: new_extrato_posto_devolucao_new_itens.php?extrato=$extrato");
	exit();
}

// para redirecionar para a pagina antiga se a nota jï¿½ foi digitada. Novas notas irï¿½o para esta pagina
if (strlen($extrato)>0 and 1==2){
	$sql = "SELECT	*
		FROM tbl_faturamento 
		JOIN tbl_faturamento_item USING(faturamento)
		WHERE tbl_faturamento.extrato_devolucao = $extrato
		AND tbl_faturamento.fabrica= $login_fabrica
		AND tbl_faturamento.posto=$login_posto
		AND tbl_faturamento_item.extrato_devolucao is not null";
	$res = pg_exec ($con,$sql);
	$qntos_digitou = pg_numrows($res);

	if ($qntos_digitou==0){
		$sql = "SELECT	*
			FROM tbl_extrato_devolucao 
			WHERE extrato = $extrato";
		$res = pg_exec ($con,$sql);
		$qntos_tem = pg_numrows($res);
		
		$sql = "SELECT	*
			FROM tbl_extrato_devolucao 
			WHERE extrato = $extrato
			AND nota_fiscal is not null";
		$res = pg_exec ($con,$sql);
		$qntos_falta = pg_numrows($res);
		
		if ($qntos_falta == $qntos_tem AND $qntos_tem>0) {
			header("Location: new_extrato_posto_devolucao.php?extrato=$extrato");
			exit();
		}
	}
}

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
$data = pg_result ($res,0,data);
$periodo = pg_result ($res,0,periodo);
$nome = pg_result ($res,0,nome);
$codigo = pg_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='-1' face='arial'>$codigo - $nome</font>";

?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center' width='33%'><a href='<?php echo $PHP_SELF ?>?mao=sim&extrato=<? echo $extrato ?>'>Ver Mão-de-Obra</a></td>
<td align='center' width='33%'><a href='new_extrato_posto.php'>Ver outro extrato</a></td>
</tr>
</table>

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

<? if (strlen($numero_linhas) > 0 AND $diminuiu=='sim' AND $ok_aceito!='Concordo') { ?>
<br>

<form method='post' action='<? echo $PHP_SELF; ?>#notas_d' name='frm_confirmar' id='frm_confirmar' >
<input type='hidden' name='extrato' value='<? echo $extrato; ?>'>
<input type='hidden' name='qtde_linha' value='<? echo $numero_linhas; ?>'>
<input type='hidden' name='ok_aceito' value='Concordo'>
<input type='hidden' name='pendentes' value='sim'>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="0">
<TR>
	<TD colspan="10" class="menu_top2" ><div align="center" style='font-size:16px'>
	<b>
	ATENÇÃO
	</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="menu_top3" style='padding:10px;'>
	As peças ou produtos não devolvidos neste extrato serão apresentadas na tela de consulta de pendências. Caso não sejam efetivadas as devoluções, os itens serão cobrados do posto autorizado.
	<br><br>
	</td>
</tr>
<TR>
	<TD colspan='8' class="menu_top2" align='center'>
	<center>
	<input type='button' name='ok' value='Concordo' class='frm' onclick="javascript:if (this.value=='Concordo.'){altert('Aguarde submissão.');}else{if(confirm('Deseja continuar?')){this.value='Concordo.';document.frm_confirmar.submit();}}">
	<input type='button' value='Voltar' name='voltar' onclick="javascript:
				if(confirm('Deseja voltar?')) window.location='<? echo $PHP_SELF; ?>?extrato=<? echo $extrato; ?>';">
		<? echo ?>
	</center>
	</TD>
</TR>
</table>
</form>
<? exit(); } ?>

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
	As peças ou produtos não devolvidos neste extrato serão apresentadas na tela de consulta de pendências. Caso não sejam efetivadas as devoluções, os itens serão cobrados do posto autorizado.
<br><br>
<b style='font-size:14px;font-weight:normal'>Emitir as NF de devolução nos mesmos valores e impostos, referenciando NF de origem Britânia, e postagem da NF para Britânia Joinville-SC</b>
	</TD>
</TR>
</table>

<br>
<?php if ($numero_linhas==5000 AND 1==2){ ?>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<td style='padding-left:280px;padding-right:60px'>
	<IMG SRC="imagens/setona.gif" WIDTH="31" HEIGHT="52" BORDER="0" ALT="" align='right'>
	Preencha esta coluna com as quantidades de peças que serão devolvidas
	</TD>
</TR>
</table>
<? } ?>


<? 

$sql = "UPDATE tbl_faturamento_item SET linha = (SELECT tbl_produto.linha FROM tbl_produto 
				JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_faturamento_item.peca = tbl_lista_basica.peca LIMIT 1)
		FROM tbl_faturamento
		WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
		AND tbl_faturamento.fabrica = $login_fabrica 
		AND tbl_faturamento.extrato_devolucao = $extrato";
$res = pg_exec ($con,$sql);

if ($login_fabrica == 3) {
	$sql = "UPDATE tbl_faturamento_item SET linha = 2
			FROM tbl_faturamento
			WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			AND tbl_faturamento.fabrica = $login_fabrica 
			AND tbl_faturamento.extrato_devolucao = $extrato
			AND tbl_faturamento_item.linha IS NULL";
	$res = pg_exec ($con,$sql);
}

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

$sql = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao,
		tbl_faturamento.distribuidor,
		CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
		tbl_peca.devolucao_obrigatoria
	FROM    tbl_faturamento 
	JOIN    tbl_faturamento_item USING (faturamento) 
	JOIN    tbl_peca             USING (peca)
	WHERE   tbl_faturamento.extrato_devolucao = $extrato
	AND     tbl_faturamento.posto             = $login_posto
	AND     tbl_faturamento.distribuidor IS NULL
	AND tbl_faturamento.cfop IN ('694921','694922','591919','594920','594921','594922','594923')
					
	ORDER BY produto_acabado DESC , devolucao_obrigatoria DESC ";
$res = pg_exec ($con,$sql);
//AND     tbl_faturamento_item.aliq_icms > 0 
//echo $sql;

if (pg_numrows ($res) > 0) {

	echo "<form method='post' action='$PHP_SELF' name='frm_devolucao' id='frm_devolucao'>";
	echo "<input type='hidden' name='notas_d' value=''>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>\n";

	$contador=0;
//	$qtde_for = pg_numrows ($res);
//	for ($i=0; $i < $qtde_for; $i++) {

	for($xx=1;$xx<4;$xx++) {

	/*	$distribuidor          = trim (pg_result ($res,$i,distribuidor));
		$produto_acabado       = trim (pg_result ($res,$i,produto_acabado));
		$devolucao_obrigatoria = trim (pg_result ($res,$i,devolucao_obrigatoria));
		$extrato_devolucao     = trim (pg_result ($res,$i,extrato_devolucao));*/

		$extrato_devolucao = $extrato;

		switch ($xx) {
			case 1:
					$devolucao = " RETORNO OBRIGATÓRIO ";
					$pecas_produtos = "PRODUTOS";
					$condicao_2 = " AND tbl_peca.produto_acabado IS TRUE ";
					$sql_adicional_peca2 = "";
				break;
			case 2:
					$devolucao = " RETORNO OBRIGATÓRIO ";
					$pecas_produtos = "PEÇAS";
					$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
					$sql_adicional_peca2 = " AND tbl_peca.devolucao_obrigatoria = 't'";
				break;
			case 3:
					$devolucao = " NÃO RETORNÁVEIS ";
					$pecas_produtos = "PEÇAS";
					$condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE ";
					$sql_adicional_peca2 = " AND tbl_peca.devolucao_obrigatoria = 'f'";
				break;
		}

	
		$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
		$endereco = "Rua Dona Francisca, 8300 - Mod.4 e 5 - Bloco A";
		$cidade   = "Joinville";
		$estado   = "SC";
		$cep      = "89239270";
		$fone     = "(41) 2102-7700";
		$cnpj     = "76492701000742";
		$ie       = "254.861.652";

		$distribuidor = "null";
		$condicao_1 = " AND tbl_faturamento.distribuidor IS NULL ";


		$cabecalho  = "<br><br>\n";
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

		$cabecalho .= "<tr align='left'  height='16'>\n";
		$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
		$cabecalho .= "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
		$cabecalho .= "</td>\n";
		$cabecalho .= "</tr>\n";

		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Natureza <br> <b>Devolução de Garantia</b> </td>\n";
		$cabecalho .= "<td>CFOP <br> <b>$cfop</b> </td>\n";
		$cabecalho .= "<td>Emissao <br> <b>$data</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
		$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
		$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
		$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
		$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
		$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$topo ="";
		$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
		$topo .=  "<thead>\n";

		if ($numero_linhas==5000){
			$topo .=  "<tr align='left'>\n";
			$topo .=  "<td bgcolor='#E3E4E6' colspan='4' style='font-size:18px'>\n";
			$topo .=  "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
			$topo .=  "</td>\n";
			$topo .=  "</tr>\n";
		}

		$topo .=  "<tr align='center'>\n";
		$topo .=  "<td><b>Código</b></td>\n";
		$topo .=  "<td><b>Descrição</b></td>\n";
		$topo .=  "<td><b>Qtde.</b></td>\n";

		if ($numero_linhas==5000){
			$topo .=  "<td><b>Qtde. Devolução</b></td>\n";
		}
		else{
			$topo .=  "<td><b>Preço</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			$topo .=  "<td><b>% IPI</b></td>\n";
		}
		$topo .=  "</tr>\n";
		$topo .=  "</thead>\n";


		if ($numero_linhas!=5000){
			$sql_adicional_peca=" AND tbl_extrato_lgr.qtde_pedente_temp>0";
		}
		else{
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
				AND (tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END)>0";
		$sql .= "
				AND tbl_faturamento.cfop IN ('694921','694922','591919','594920','594921','594922','594923')
				$condicao_1
				$condicao_2
				$sql_adicional_peca
				$sql_adicional_peca2
				AND   tbl_faturamento.emissao > '2005-10-01'
				AND   tbl_faturamento.distribuidor IS NULL
				";
		$sql .= "
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

		$notas_fiscais=array();
		$qtde_peca=0;


		$resX = pg_exec ($con,$sql);

		if (pg_numrows ($resX)==0) continue;

		if ($numero_linhas!=5000){
			echo $cabecalho;
		}
		echo $topo;

		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$aliq_final       = 0;
		$peca_ant="";

		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
			$tem_mais_itens='sim';
			
			if ($numero_linhas!=5000){
				if ($x%$numero_linhas==0 AND $x>0){
					//$total_valor_icms = $total_base_icms * $aliq_final / 100;
					$total_geral=$total_nota+$total_valor_ipi;
					echo "</table>\n";
					echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
					echo "<tr>\n";
					echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>\n";
					echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";
					echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>\n";
					echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>\n";
					echo "<td>Total da Nota <br> <b> " . number_format ($total_geral,2,",",".") . " </b> </td>\n";
					echo "</tr>\n";
					if (count($notas_fiscais)>0){
						echo "<tfoot>";
						echo "<tr>";
						echo "<td colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
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
//						echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-base_icms' value='$total_base_icms'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi' value='$total_base_ipi'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi' value='$total_valor_ipi'>\n";
						echo "<center>";
						echo "<b>Preencha este Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";
						echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='6' value='$nota_fiscal'>";
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
					echo $cabecalho;
					echo $topo;
					$total_base_icms  = 0;
					$total_valor_icms = 0;
					$total_base_ipi   = 0;
					$total_valor_ipi  = 0;
					$total_nota       = 0;
					$item_nota=0;
				}
			}
			$contador++;
			$item_nota++;

			$peca                = pg_result ($resX,$x,peca);
			$peca_referencia     = pg_result ($resX,$x,referencia);
			$peca_descricao      = pg_result ($resX,$x,descricao);
			$peca_preco          = pg_result ($resX,$x,preco);
			$qtde_real           = pg_result ($resX,$x,qtde_real);
			$qtde_total_item     = pg_result ($resX,$x,qtde_total_item);
			$qtde_total_nf       = pg_result ($resX,$x,qtde_total_nf);
			$qtde_pedente_temp   = pg_result ($resX,$x,qtde_pedente_temp);
//			$qtde_restatante     = pg_result ($resX,$x,qtde_restatante);
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

			if ($pecas_pendentes=='sim' and 1==2){
				$qtde_total_item    = $qtde_restatante;
				$qtde_pedente_temp  = $qtde_restatante;
			}
			if ($qtde_pedente_temp>$qtde_real AND $numero_linhas!=5000){
				$qtde_pedente_temp=$qtde_real;
			}
			if ($peca_ant==$peca){
				if ($numero_linhas==5000){
					continue;
				}
			}
			$peca_ant=$peca;

			$sql_nf = "SELECT tbl_faturamento.nota_fiscal,
							  tbl_faturamento_item.qtde
					FROM tbl_faturamento_item 
					JOIN tbl_faturamento      USING (faturamento)
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.posto   = $login_posto
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND tbl_faturamento_item.peca=$peca
					ORDER BY tbl_faturamento.nota_fiscal";
			$resNF = pg_exec ($con,$sql_nf);
			
			if (strlen($qtde_total_nf)==0) $qtde_total_nf=0;

			$qtde_aux=0;
			$qtde_peca=0;
			if (strlen($qtde_pedente_temp)==0)
				$qtde_pedente_temp=$qtde_total_item;

			for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
				if ($qtde_aux<$qtde_total_nf) {
					$qtde_aux += pg_result ($resNF,$y,qtde);
					continue;
				}
				if ($qtde_peca < $qtde_pedente_temp){
					$qtde_peca += pg_result ($resNF,$y,qtde);
					array_push($notas_fiscais,pg_result ($resNF,$y,nota_fiscal));
				}
				$notas_fiscais = array_unique($notas_fiscais);
				asort($notas_fiscais);
//				print_r($notas_fiscais);
			}

//			if ($qtde_pedente_temp==0)
//				$preco       =  $total_item;
//			else
//				$preco       =  $total_item / $qtde_total_item;
			
			$total_item  = $peca_preco * $qtde_pedente_temp;

//			$nota_fiscal_item = pg_result ($resX,$x,nota_fiscal);
//			$faturamento = pg_result ($resX,$x,faturamento);

			if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

			if ($aliq_icms==0){
				$base_icms=0;
				$valor_icms=0;
			}
			else{
				$base_icms=$total_item;
				$valor_icms = $total_item * $aliq_icms / 100;
			}

			if (strlen($aliq_ipi)==0) $aliq_ipi=0;

			if ($aliq_ipi==0) 	{
				$base_ipi=0;
				$valor_ipi=0;
			}
			else {
				$base_ipi=$total_item;
				$valor_ipi = $total_item*$aliq_ipi/100;
			}

//			if ($base_icms > $total_item) $base_icms = $total_item;
//			if ($aliq_final == 0) $aliq_final = $aliq_icms;
//			if ($aliq_final <> $aliq_icms) $aliq_final = -1;

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi   += $base_ipi;
			$total_valor_ipi  += $valor_ipi;
			$total_nota       += $total_item;

			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
			echo "<td align='left'>";
			echo "$peca_referencia";
			echo "<input type='hidden' name='id_item_LGR_$item_nota-$numero_nota' value='$extrato_lgr'>\n";
			echo "<input type='hidden' name='id_item_peca_$item_nota-$numero_nota' value='$peca'>\n";
			echo "<input type='hidden' name='id_item_preco_$item_nota-$numero_nota' value='$peca_preco'>\n";
			echo "<input type='hidden' name='id_item_qtde_$item_nota-$numero_nota' value='$qtde_pedente_temp'>\n";
			echo "<input type='hidden' name='id_item_icms_$item_nota-$numero_nota' value='$aliq_icms'>\n";
			echo "<input type='hidden' name='id_item_ipi_$item_nota-$numero_nota' value='$aliq_ipi'>\n";
			echo "<input type='hidden' name='id_item_total_$item_nota-$numero_nota' value='$total_item'>\n";
			echo "</td>\n";
			echo "<td align='left'>$peca_descricao</td>\n";

			$contadorrr += $qtde_pedente_temp;
			if ($numero_linhas==5000){
				echo "<td align='center'>$qtde_total_item</td>\n";
				echo "<td align='center' bgcolor='#FAE7A5'>\n
						<input type='hidden' name='item_$contador' value='$extrato_lgr'>\n
						<input type='hidden' name='peca_tem_$contador' value='$qtde_total_item'>\n
						<input type='hidden' name='peca_$contador' value='$peca'>\n
						<input style='text-align:right' type='text' size='4' maxlength='4' name='$extrato_lgr' value='$qtde_pedente_temp' onblur='javascript:if (this.value > $qtde_total_item || this.value==\"\" ) {alert(\"Quantidade superior!\");this.value=\"$qtde_total_item\"}'>\n
						</td>\n";
			}
			else{
				echo "<td align='center'>$qtde_pedente_temp</td>\n";
				echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
				echo "<td align='right'>$aliq_icms</td>\n";
				echo "<td align='right'>$aliq_ipi</td>\n";
			}
			echo "</tr>\n";
			flush();
		}
		if (count($notas_fiscais)>0){
			echo "<tfoot>";
			echo "<tr>";
			echo "<td colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
			echo "</tr>";
			echo "</tfoot>";
		}

		echo "</table>\n";

//		$total_valor_icms = $total_base_icms * $aliq_final / 100;

		if ($numero_linhas!=5000) {
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
			echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
			echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
			echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
			echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi,2,",",".") . " </b> </td>";
			echo "</tr>";
			echo "</table>";

		}

		if ($numero_linhas!=5000 AND strlen ($nota_fiscal) == 0) {

			$total_geral=$total_nota+$total_valor_ipi;

//			echo "\n<br>";
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>";
			echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-base_icms' value='$total_base_icms'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi' value='$total_base_ipi'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi' value='$total_valor_ipi'>\n";
			echo "<center>";
			echo "<b>Preencha este Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";
			echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='6' value='$nota_fiscal'>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";

			//echo "<br><br>";
			$item_nota=0;
			$numero_nota++;
		}else{
			if (strlen ($nota_fiscal)>0){
				echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
				echo "<tr>\n";
				echo "<td><h1><center>Nota de Devolução $nota_fiscal</center></h1></td>\n";
				echo "</tr>";
				echo "</table>";
			}
		}
		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;

	}

	if ($numero_linhas==5000){

		if ($tem_mais_itens=='nao'){
			echo "<b>Não há mais peçaas para devolução.<br><br> <a href='new_extrato_posto_devolucao_new_itens.php?extrato=$extrato'>Clique aqui para consultar as notas de devolução</a></b>";
		}else{
			if ($pecas_pendentes=='sim') echo "<input type='hidden' name='pendentes' value='sim'>";

			echo "<br>
					<input type='hidden' name='qtde_pecas' value='$contador'>
					<IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'> 
					<b style='font-size:12px'>

		
					<b>Informar a quantidade de linhas no formulário de Nota Fiscal do Posto Autorizado:</b>
					<input type='text' size='5' maxlength='3' value='' name='qtde_linha'><br>
					Essa informação definirá a quantidade de NFs que o posto autorizado deverá emitir e enviar à Britânia
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
		}
	}
	else{
		/*echo "<br><br><br>
				<input type='hidden' name='qtde_linha' value='$numero_linhas'>
				<input type='hidden' name='numero_de_notas' value='$numero_nota'>
				
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
				
				<br>";*/
		echo "<br><br><br>
				<input type='hidden' name='qtde_linha' value='$numero_linhas'>
				<input type='hidden' name='numero_de_notas' value='$numero_nota'>
				
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

	}
	echo "</form>";

	$sql = "SELECT  tbl_os.os                                                         ,
			tbl_os.sua_os                                                     ,
			TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_ressarcimento,
			tbl_produto.referencia                       AS produto_referencia,
			tbl_produto.descricao                        AS produto_descricao ,
			tbl_admin.login
		FROM tbl_os
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING(os_produto)
		JOIN tbl_os_extra   USING(os)
		LEFT JOIN tbl_admin      ON tbl_os.troca_garantia_admin   = tbl_admin.admin
		LEFT JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_os_extra. extrato = $extrato
		AND  tbl_os.ressarcimento  IS TRUE
		AND  tbl_os.troca_garantia IS TRUE";

	$resX = pg_exec ($con,$sql);
	if(pg_numrows($resX)>0 AND strlen($nota_fiscal)>0){

		echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";

		echo "<tr align='left'  height='16'>\n";
		echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
		echo "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>";
		echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
		echo "<td>CFOP <br> <b>$cfop</b> </td>";
		echo "<td>Emissão <br> <b>$data</b> </td>";
		echo "</tr>";
		echo "</table>";
	
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr>";
		echo "<td>Razão Social <br> <b>$razao</b> </td>";
		echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
		echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
		echo "</tr>";
		echo "</table>";
	
	
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr>";
		echo "<td>Endereço <br> <b>$endereco </b> </td>";
		echo "<td>Cidade <br> <b>$cidade</b> </td>";
		echo "<td>Estado <br> <b>$estado</b> </td>";
		echo "<td>CEP <br> <b>$cep</b> </td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr align='center'>";
		echo "<td><b>Código</b></td>";
		echo "<td><b>Descrição</b></td>";
		echo "<td><b>Ressarcimento</b></td>";
		echo "<td><b>Responsavel</b></td>";
		echo "<td><b>OS</b></td>";
		echo "</tr>";
	
		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
	
			$sua_os             = pg_result ($resX,$x,sua_os);
			$produto_referencia = pg_result ($resX,$x,produto_referencia);
			$produto_descricao  = pg_result ($resX,$x,produto_descricao);
			$data_ressarcimento = pg_result ($resX,$x,data_ressarcimento);
			$quem_trocou        = pg_result ($resX,$x,login);
	
			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
			echo "<td align='left'>$produto_referencia</td>";
			echo "<td align='left'>$produto_descricao</td>";
			echo "<td align='left'>$data_ressarcimento</td>";
			echo "<td align='right'>$quem_trocou</td>";
			echo "<td align='right'>$sua_os</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

}else{

	echo "<h1><center> Extrato de Mão-de-obra Liberado. Recarregue a página. </center></h1>";
	$sql =	"UPDATE tbl_extrato_extra SET
				nota_fiscal_devolucao              = '000000' ,
				valor_total_devolucao              = 0        ,
				base_icms_devolucao                = 0        ,
				valor_icms_devolucao               = 0        ,
				nota_fiscal_devolucao_distribuidor = '000000' ,
				valor_total_devolucao_distribuidor = 0        ,
				base_icms_devolucao_distribuidor   = 0        ,
				valor_icms_devolucao_distribuidor  = 0
			WHERE extrato = $extrato;";
	//$res = pg_exec ($con,$sql);

}
//echo $contadorrr;
?>


<? include "rodape.php"; ?>
