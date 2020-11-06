<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro="";
$msg="";

function getCFOP($uf) {
	global $login_fabrica;
    
	if (($login_fabrica == 3 && $uf == "SC") || ($login_fabrica == 158 && $uf == "SP")) {
        $cfop = 5949;
    } else {
        $cfop = 6949;
    }

    return $cfop;

}

if($_GET['ajax']=='sim') {

	$extrato = $_GET['extrato'];
	$nota_fiscal    = $_GET['nota'];

	$sql = "SELECT posto_fabrica from tbl_fabrica where fabrica = {$login_fabrica}";
	$res = pg_exec($con,$sql);

	if (pg_num_rows($res)>0) {
		$posto_fabrica = pg_result($res,0,0);
	}

	$total_nota = '0';
	$base_icms  = '0';
	$valor_icms = '0';
	$base_ipi   = '0';
	$valor_ipi  = '0';
	$movimento  = "RETORNAVEL";

	$sql = "SELECT contato_estado FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$fabrica};";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res)>0) {
		$posto_estado = pg_result($res,0,0);
	}

	$cfop = getCFOP($posto_estado);

	$sqlProduto = "
		SELECT DISTINCT
			tbl_os.os,
			tbl_os.sua_os,
			TO_CHAR(tbl_os_troca.data,'DD/MM/YYYY') AS data_ressarcimento,
			tbl_produto.produto AS produto,
			tbl_produto.referencia AS produto_referencia,
			tbl_produto.descricao AS produto_descricao,
			tbl_admin.login
		FROM tbl_os
		JOIN tbl_os_troca USING(os)
		JOIN tbl_os_extra USING(os)
		JOIN tbl_extrato ON tbl_extrato.fabrica = tbl_os.fabrica AND tbl_extrato.posto = tbl_os.posto
		LEFT JOIN tbl_admin ON tbl_os.troca_garantia_admin = tbl_admin.admin
		LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_extrato.extrato = {$extrato}
		AND tbl_os.fabrica = {$login_fabrica}
		AND tbl_os.posto = {$login_posto}
		AND tbl_os_troca.ressarcimento IS TRUE
		AND tbl_os.troca_garantia IS TRUE;
	";
	//echo nl2br($sql);

	$resProduto = pg_query($con,$sqlProduto);
	$msg_erro .= pg_errormessage($con);

	$qtde_produtos_ressarcimento = pg_num_rows($resProduto);

	if (strlen($msg_erro) == 0) {
 		$resX = pg_query ($con,"BEGIN TRANSACTION");

			$sql = "INSERT INTO tbl_faturamento
				(fabrica, emissao,saida, posto, distribuidor, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi,obs,cfop, movimento)
				VALUES ($login_fabrica,current_date,current_date,$posto_fabrica,$login_posto,$total_nota,'$nota_fiscal','2','Simples Remessa', $base_icms, $valor_icms, $base_ipi, $valor_ipi, 'Devolução de Ressarcimento',$cfop,'$movimento')";

			$res = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sqlZ = "SELECT CURRVAL('seq_faturamento')";
			$resZ = pg_query($con,$sqlZ);
			$faturamento_codigo = pg_fetch_result($resZ,0,0);
			$msg_erro .= pg_errormessage($con);

			for ($x = 0 ; $x < $qtde_produtos_ressarcimento ; $x++) {

				$produto_referencia = pg_fetch_result ($resProduto,$x,produto_referencia);

				$sql_peca = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '$produto_referencia' AND produto_acabado IS TRUE";

				$res_peca = pg_exec($con,$sql_peca);

				if (pg_num_rows($res_peca)>0) {
					$peca = pg_result($res_peca,0,0);
				} else {
					$msg_erro = "Peça não encontrada";
				}

				if (strlen($msg_erro)==0) {
					$sql = "INSERT INTO tbl_faturamento_item
								(faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, base_ipi, valor_ipi,nota_fiscal_origem,extrato_devolucao,devolucao_obrig)
								VALUES ($faturamento_codigo, $peca,1, 0, 0, 0, 0, 0, 0, 0,'$nota_fiscal',$extrato,true)";
					$res = pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

			}
		if (strlen($msg_erro)==0) {
			$resX = pg_query ($con,"COMMIT TRANSACTION");
			echo "ok";
		}else{
			$resX = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
	exit;
}

$sql = "SELECT posto_fabrica
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica ";
$res2 = pg_exec ($con,$sql);
$posto_da_fabrica = pg_result ($res2,0,0);

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0)
	$extrato = trim($_POST['extrato']);

if (strlen($extrato)==0){
	header("Location: extrato_posto.php");
}

$layout_menu = "os";
$title = traduz("Peças Retornáveis do Extrato");

include "cabecalho.php";
?>


<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>


<script src="js/jquery-1.8.3.min.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script src="plugins/jquery.form.js"></script>

<script language='javascript'>

function solicitaPostagemPosto(extrato, faturamento) {
      
    Shadowbox.open({
            content :   "solicitacao_postagem_positron.php?extrato="+ extrato+"&faturamento="+faturamento,
            player  :   "iframe",
            title   :   "Autorização de Postagem",
            width   :   900,
            height  :   500
    }); 
	
}


	$(function() {
 		Shadowbox.init();
	});


function gravaRessarcimento(nota,extrato) {
	if (nota.length > 0) {
		url = "<?= $PHP_SELF; ?>?ajax=sim&nota="+nota+"&extrato="+extrato;
		requisicaoHTTP('GET',url, true , 'respostas');
	} else {
		alert('<?= traduz("Digite o Número da Nota Fiscal") ?>');
	}
}

function respostas(campos) {
	if (campos == 'ok')	{
		document.getElementById('div_msg').style.display = 'block';
		document.getElementById('div_msg').innerHTML = '<?= traduz("Nota Gravada com Sucesso") ?>';
	}
}


</script>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.menu_top, .titulo_tabela {
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

.msgImposto {
	font-size: 14px;
	color: #FF0000;
	text-align: center;
}

</style>

<br><br>

<?
if (strlen($posto_da_fabrica)==0){
	echo "<center><h1>".traduz("Devolução não configurada").".</h1></center>";
	echo "<br>";
	echo "<br>";
	include "rodape.php";
	exit;
}

if ($login_fabrica == 94) {
?>
<br />
<table width="75%" border="0" align="center" class="msgImposto">
	<tr>
		<td>Empresa <strong>OPTANTE PELO SIMPLES "NÃO"</strong> deve mencionar os impostos em seus respectivos campos. Estes valores devem ser mencionados no campo <strong>"DADOS ADICIONAIS"</strong></td>
	</tr>
</table>
<br />
<?php } ?>

<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<?php if ($login_fabrica != 177) { ?>
<td align='center' width='33%'><a href='<?=($login_fabrica == 101) ? 'os_extrato_new.php' : 'os_extrato.php'?>'>Ver Mão-de-Obra</a></td>
<?php } ?>
<td align='center' width='33%'><a href='<?=($login_fabrica == 101) ? 'os_extrato_new.php' : 'os_extrato.php'?>'>Ver outro extrato</a></td>
</tr>
</table>

<div id='loading'></div>

<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="75%" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg_erro ?></td>
	</tr>
</table>
<? } ?>

<center>

<br>
<TABLE width="75%" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="10" class="menu_top" ><div align="center" style='font-size:16px'><b><?= traduz('ATENÇÃO') ?></b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
	<?= traduz('As peças ou produtos não devolvidos neste extrato serão cobrados do posto autorizado.') ?>
	<br><br>
	<?
		if ($login_fabrica==2) {
			echo "<b style='font-size:14px;font-weight:normal'>Emitir as NFs de devolução nos mesmos valores e impostos, referenciando NF de origem Dynacom, e postagem da NF para Dynacom</b>";
		}elseif ($login_fabrica==11) {
			echo "<b style='font-size:14px;font-weight:normal'>Emitir as NFs de devolução nos mesmos valores e impostos, referenciando NF de origem Lenoxx, e postagem da NF para Lenoxx</b>";
		}
	?>
	</TD>
</TR>
</table>

 <?
$array_nf_canceladas = array();

$sql="SELECT faturamento,nota_fiscal
		FROM tbl_faturamento
		WHERE fabrica             = $login_fabrica
		AND distribuidor          = $login_posto
		AND extrato_devolucao     = $extrato
		AND posto                 in ($posto_da_fabrica)
		AND cancelada IS NOT NULL";
$res_nota = pg_exec ($con,$sql);
$notasss = pg_numrows ($res_nota);
for ($i=0; $i<$notasss; $i++){
	$nf_cancelada = pg_result ($res_nota,$i,nota_fiscal);
	array_push($array_nf_canceladas,$nf_cancelada);
}

if (count($array_nf_canceladas)>0){
	if (count($array_nf_canceladas)>1){
		echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>".traduz('As notas').":</b><br>".implode(",<br>",$array_nf_canceladas)." <br>".traduz('foram canceladas')."</h3>";
		// e deverão ser preenchidas novamente! <br> <a href='extrato_posto_devolucao_lenoxx.php?extrato=$extrato&pendentes=sim'>Clique aqui</a> para o preenchimento das notas.
	}else{
		echo "<h3 style='border:1px solid #F7CB48;background-color:#FCF2CD;color:black;font-size:12px;width:600px;text-align:center;padding:4px;'><b>".traduz('A nota')."</b> ".implode(", ",$array_nf_canceladas)." ".traduz('foi cancelada')."</h3>";
		// e deverá ser preenchida novamente! <br> <a href='extrato_posto_devolucao_lenoxx.php?extrato=$extrato&pendentes=sim'>Clique aqui</a> para o preenchimento da nota.
	}
}

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);

if ($login_fabrica == 11){
	$sql = "SELECT  CASE WHEN data_geracao > '2008-08-01'::date THEN '1' ELSE '0' END
			FROM tbl_extrato
			WHERE extrato = $extrato ";
	$res2 = pg_exec ($con,$sql);
	$verificacao = pg_result ($res2,0,0);
	#2008-06-01 - HD 16362
}

if ($login_fabrica==50){#HD 48024
	$verificacao = "1";
}
if ($telecontrol_distrib == 't') {
	$join_complemento = "JOIN tbl_peca on tbl_peca.peca = tbl_faturamento_item.peca";
}

$sql = "
	SELECT DISTINCT
		faturamento,
		tbl_faturamento_item.extrato_devolucao,
		nota_fiscal,
		qtde_volume,
		TO_CHAR(emissao,'DD/MM/YYYY') AS emissao,
		distribuidor,
		posto,
		tbl_faturamento.cfop,
		natureza,
		TO_CHAR(cancelada,'DD/MM/YYYY') AS cancelada,
		movimento,
		tbl_faturamento.obs,
		tbl_faturamento.transportadora, 
		tbl_faturamento.transp,
		tbl_faturamento.chave_nfe,
		tbl_faturamento.status_nfe
	FROM tbl_faturamento_item
	JOIN tbl_faturamento USING(faturamento)
	$join_complemento
	WHERE posto notnull and posto <> $login_posto
	AND distribuidor = {$login_posto}
	AND (obs <> 'Devolução de Ressarcimento' OR obs IS NULL)
";

if ($telecontrol_distrib == 't') {
	$sql .= "AND (tbl_faturamento.fabrica = {$login_fabrica} OR tbl_faturamento.fabrica = 10) AND tbl_peca.fabrica = {$login_fabrica}";
} else {
	$sql .= "AND tbl_faturamento.fabrica = {$login_fabrica}";
}

$sql .= "
	AND (tbl_faturamento_item.extrato_devolucao = {$extrato} or tbl_faturamento.extrato_devolucao= $extrato)
	ORDER BY faturamento ASC;
";
$res = pg_exec($con, $sql);
$qtde_for = pg_numrows($res);

if ($qtde_for > 0) {

	$contador = 0;
	for ($i = 0; $i < $qtde_for; $i++) {

		$faturamento_nota    = trim (pg_result ($res,$i,faturamento));
		$distribuidor        = trim (pg_result ($res,$i,distribuidor));
		$posto               = trim (pg_result ($res,$i,posto));
		$nota_fiscal         = trim (pg_result ($res,$i,nota_fiscal));
		$emissao             = trim (pg_result ($res,$i,emissao));
		$extrato_devolucao	 = trim (pg_result ($res,$i,extrato_devolucao));
		$cfop                = trim (pg_result ($res,$i,cfop));
		$natureza            = trim (pg_result ($res,$i,natureza));
		$cancelada           = trim (pg_result ($res,$i,cancelada));
		$movimento           = trim (pg_result ($res,$i,movimento));
		$obs                 = pg_fetch_result($res, $i, "obs");

		if ($login_fabrica == 158) {
			$chave = pg_fetch_result($res, $i, "chave_nfe");
			$n_log = pg_fetch_result($res, $i, "status_nfe");
		}

		if($login_fabrica == 177){			
			$transp = pg_fetch_result($res, $i, 'transp');
			$transportadora = pg_fetch_result($res, $i, 'transportadora');
			$qtde_volume = pg_fetch_result($res, $i, "qtde_volume");
		}

		if(!$telecontrol_distrib){
            $distribuidor        = "";
            $produto_acabado     = "";
        }
		$sql_topo = "SELECT CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
							CASE WHEN tbl_faturamento.fabrica IN(91,120,201,129,134) THEN
								tbl_faturamento_item.devolucao_obrig
							ELSE
								tbl_peca.devolucao_obrigatoria
							END AS devolucao_obrigatoria ";
        if($login_fabrica == 6){
            $sql_topo .= " , tbl_peca.parametros_adicionais ";
        }

        $sql_topo .= " FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				JOIN tbl_peca USING(peca)
				WHERE tbl_faturamento.posto           = $posto";

        if($login_fabrica == 50){
        	$sql_topo .= " AND tbl_peca.devolucao_obrigatoria IS TRUE ";
        }

		if($login_fabrica == 153){
        	$sql_topo .= " AND tbl_peca.produto_acabado is not true ";
        }

		$sql_topo .= "AND tbl_faturamento.distribuidor      = $login_posto
				AND tbl_faturamento.fabrica           = $login_fabrica
				AND (tbl_faturamento_item.extrato_devolucao = {$extrato} or tbl_faturamento.extrato_devolucao= $extrato)
				AND tbl_faturamento.faturamento       = $faturamento_nota
				LIMIT 1";
		$res_topo              = pg_exec   ($con,$sql_topo);

		$produto_acabado       = pg_result ($res_topo,0,produto_acabado);
		$devolucao_obrigatoria = pg_result ($res_topo,0,devolucao_obrigatoria);
        if($login_fabrica == 6){
            $parametros_adicionais = pg_result($res_topo, 0, "parametros_adicionais");
            $parametros_adicionais = json_decode($parametros_adicionais);
        }

		if($devolucao_obrigatoria =='t') {
			if($login_fabrica == 90) {
				$devolucao = "RETORNO DE PEÇAS CRÍTICAS";
				$natureza = "Remessa para reposição em garantia";
			} else {
				$pecas_produtos = "PEÇAS";
				$devolucao = " RETORNO OBRIGATÓRIO ";
				if($login_fabrica == 50){
					$natureza = "Retorno de remessa para troca";
				}
			}
		} else {
				if($login_fabrica == 90){
					$devolucao = "RETORNO DE PEÇAS NÃO CRÍTICAS";
					$natureza = "Remessa para reposição em garantia";
				} else if($login_fabrica == 6) {
                    if($parametros_adicionais->devolucao_estoque_fabrica == "t"){
                        $pecas_produtos = "PEÇAS";
                        $devolucao = " DEVOLUÇÃO ESTOQUE FÁBRICA ";
                    }
                } else {
					$pecas_produtos = "PEÇAS";
					$devolucao = ($login_fabrica == 35) ?" RETORNÁVEIS" : " NÃO RETORNÁVEIS ";
				}
		}

		#HD 17436

		if ($produto_acabado == "TRUE"){
			$pecas_produtos = "$posto_desc PRODUTOS";
			$devolucao      = " RETORNO OBRIGATÓRIO ";
		}

		if (strlen ($posto) > 0) {
                $sql  = "SELECT * FROM tbl_posto WHERE posto = $posto";
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
		}

		if(in_array($login_fabrica, array(91,99))) {
			$sql = "
				SELECT
					endereco,
					cidade,
					fone,
					cnpj,
					ie,
					razao_social,
					cep,
					estado
				FROM tbl_fabrica
				WHERE fabrica = {$login_fabrica};
			";
			$resX = pg_query($con,$sql);

			$razao    = pg_result ($resX,0,razao_social);
			$endereco = pg_result ($resX,0,endereco);
			$cidade   = pg_result ($resX,0,cidade);
			$estado   = pg_result ($resX,0,estado);
			$cep      = pg_result ($resX,0,cep);
			$fone     = pg_result ($resX,0,fone);
			$cnpj     = pg_result ($resX,0,cnpj);
			$ie       = pg_result ($resX,0,ie);

		}

		$cabecalho  = "";
		$cabecalho  = "<br><br>\n";
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";

		$cabecalho .= "<tr align='left' height='16'>\n";
		if ($login_fabrica == 158) {
			$cabecalho .= "<td bgcolor='#E3E4E6' colspan='4' style='font-size:18px'>\n";
		} else {
			$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
		}
		if($login_fabrica == 90) {
			$cabecalho .= "<b>&nbsp;<b>$devolucao </b><br>\n";
		} else {
			$cabecalho .= "<b>&nbsp;<b>$pecas_produtos - $devolucao </b><br>\n";
		}
		$cabecalho .= "</td>\n";
		$cabecalho .= "</tr>\n";

		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Natureza <br> <b>".$natureza."</b> </td>\n";
		$cabecalho .= "<td>CFOP <br> <b>$cfop</b> </td>\n";
		$cabecalho .= "<td>Emissão <br> <b>$emissao</b> </td>\n";
		if ($login_fabrica == 158) {
			$cabecalho .= "<td>Número do Log <br> <b>$n_log</b> </td>\n";
		}
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		if ($login_fabrica == 158) {
			$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";

			$cabecalho .= "<tr>\n";
			$cabecalho .= "<td>Chave <br> <b>".$chave."</b> </td>\n";
			$cabecalho .= "</tr>\n";
			$cabecalho .= "</table>\n";
		}
		$cnpj = preg_replace('/\D/','',$cnpj);
		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>".traduz('Razão Social')." <br> <b>$razao</b> </td>\n";
		$cabecalho .= "<td>".traduz('CNPJ')." <br> <b>$cnpj</b> </td>\n";
		$cabecalho .= "<td>".traduz('Inscrição Estadual')." <br> <b>$ie</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";
		$cep = preg_replace('/\D/','',$cep);
		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
		$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
		$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
		$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		/* Produto */

		$cabecalho_produto  = "<br><br>\n";
		$cabecalho_produto .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
		$cabecalho_produto .= "<tr align='left' height='16'>\n";
		$cabecalho_produto .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
		$cabecalho_produto .= "<b>&nbsp;<b>".traduz('PRODUTOS - RETORNO OBRIGATÓRIO')."  </b><br>\n";
		$cabecalho_produto .= "</td>\n";
		$cabecalho_produto .= "</tr>\n";

		$cabecalho_produto .= "<tr>\n";
		$cabecalho_produto .= "<td>".traduz('Natureza')." <br> <b>".$natureza."</b> </td>\n";
		$cabecalho_produto .= "<td>".traduz('CFOP')." <br> <b>$cfop</b> </td>\n";
		$cabecalho_produto .= "<td>".traduz('Emissão')." <br> <b>$emissao</b> </td>\n";
		$cabecalho_produto .= "</tr>\n";
		$cabecalho_produto .= "</table>\n";

		$cnpj = preg_replace('/\D/','',$cnpj);
		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		$cabecalho_produto .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
		$cabecalho_produto .= "<tr>\n";
		$cabecalho_produto .= "<td>".traduz('Razão Social')." <br> <b>$razao</b> </td>\n";
		$cabecalho_produto .= "<td>".traduz('CNPJ')." <br> <b>$cnpj</b> </td>\n";
		$cabecalho_produto .= "<td>".traduz('Inscrição Estadual')." <br> <b>$ie</b> </td>\n";
		$cabecalho_produto .= "</tr>\n";
		$cabecalho_produto .= "</table>\n";

		$cep = preg_replace('/\D/','',$cep);
		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		$cabecalho_produto .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
		$cabecalho_produto .= "<tr>\n";
		$cabecalho_produto .= "<td>".traduz('Endereço')." <br> <b>$endereco </b> </td>\n";
		$cabecalho_produto .= "<td>".traduz('Cidade')." <br> <b>$cidade</b> </td>\n";
		$cabecalho_produto .= "<td>".traduz('Estado')." <br> <b>$estado</b> </td>\n";
		$cabecalho_produto .= "<td>".traduz('CEP')." <br> <b>$cep</b> </td>\n";
		$cabecalho_produto .= "</tr>\n";
		$cabecalho_produto .= "</table>\n";		

		/* Fim - Produto */

		$topo ="";
		$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' id='tbl_pecas_$i'>\n";

		$topo .=  "<thead>\n";
		$topo .=  "<tr align='center'>\n";
		if(in_array($login_fabrica, array(50,153))){
            $topo .=  "<td><b>OS</b></td>\n";    
        }  
		$topo .=  "<td><b>".traduz('Código')."</b></td>\n";
		$topo .=  "<td><b>".traduz('Descrição')."</b></td>\n";

		if($login_fabrica == 91){
			$topo .=  "<td><b>NCM</b></td>\n";
		}

		$topo .=  "<td><b>".traduz('Qtde').".</b></td>\n";
		if($login_fabrica == 177){
			$topo .=  "<td><b>".traduz('Peso').".</b></td>\n";
		}

		if ($login_fabrica != 177){
			$topo .=  "<td><b>".traduz('Preço')."</b></td>\n";
			$topo .=  "<td><b>".traduz('Total')."</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			if ($verificacao!=='1'){
				$topo .=  "<td><b>% IPI</b></td>\n";
			}
			if ($login_fabrica == 175) {
				$topo .=  "<td><b>% ".traduz('ST')."</b></td>\n";
			}
		}

		if(in_array($login_fabrica,array(120,201,175))){
			$topo .=  "<td><b>Valor IPI</b></td>\n";	
			$topo .=  "<td><b>Valor ICMS</b></td>\n";
			$topo .=  "<td><b>Valor Base Sub. ICMS</b></td>\n";
			$topo .=  "<td><b>Valor base IPI</b></td>\n";
			$topo .=  "<td><b>Valor base ICMS</b></td>\n";
			$topo .=  "<td><b>Valor Sub ICMS</b></td>\n";
			//$topo .=  "<td><b>Valor ICMS ST</b></td>\n";
			//$topo .=  "<td><b>Valor Base ST</b></td>\n";
		}
		$topo .=  "</tr>\n";
		$topo .=  "</thead>\n";

		if(in_array($login_fabrica, array(50))){
            $campo_os_colormaq = " tbl_faturamento_item.os, ";
        }

        if(in_array($login_fabrica,array(120,201,175))){
        	$campo_os_newmaq = " tbl_faturamento_item.valor_subs_trib, tbl_faturamento_item.base_subs_trib, tbl_faturamento_item.valor_icms_st, tbl_faturamento_item.base_st, ";
        }

        if($login_fabrica == 175){
        	$campo_os_st =  " COALESCE(tbl_faturamento_item.valor_subs_trib, 0) AS valor_subs_trib, COALESCE(tbl_faturamento_item.base_subs_trib, 0) AS base_subs_trib, ";
        	$campo_order_by  =  " valor_subs_trib, base_subs_trib, ";
        }

        if($login_fabrica == 153){

        	$sql_prod = "
				SELECT 
					tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					tbl_peca.ncm,
					CASE WHEN tbl_faturamento.fabrica IN(91,129,134) THEN
						tbl_faturamento_item.devolucao_obrig
					ELSE
						tbl_peca.devolucao_obrigatoria
					END AS devolucao_obrigatoria,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco,
					tbl_faturamento.valor_ipi as valor_ipi_fat,
					SUM (tbl_faturamento_item.qtde) as qtde,
					SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco) as total,
					SUM (tbl_faturamento_item.base_icms) AS base_icms,
					SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
					SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
					SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING (faturamento)
				JOIN tbl_peca             USING (peca)
				WHERE tbl_faturamento.fabrica = $login_fabrica
					AND (tbl_faturamento_item.extrato_devolucao = {$extrato} or tbl_faturamento.extrato_devolucao= $extrato)
					AND   tbl_faturamento.faturamento=$faturamento_nota
					AND   tbl_faturamento.posto = $posto
					AND   tbl_faturamento.distribuidor=$login_posto 
					AND tbl_peca.produto_acabado IS TRUE 
				GROUP BY 
					tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.devolucao_obrigatoria,
					tbl_peca.ipi,
					tbl_peca.ncm,
					valor_ipi_fat,
					tbl_faturamento.fabrica,
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.devolucao_obrig,
					tbl_faturamento_item.preco
				ORDER BY tbl_peca.referencia;
			";

			$resP = pg_exec($con,$sql_prod);
			$notas_fiscais = array();
			$qtde_peca = 0;

			if (pg_numrows($resP) > 0) {

				echo $cabecalho_produto;
				echo $topo;

				$total_base_icms  = 0;
				$total_valor_icms = 0;
				$total_base_ipi   = 0;
				$total_valor_ipi  = 0;
				$total_nota       = 0;
				$aliq_final       = 0;
				$tota_pecas       = 0;
				for ($x = 0; $x < pg_numrows($resP); $x++) {

					$peca                = pg_result ($resP,$x,peca);
					$peca_referencia     = pg_result ($resP,$x,referencia);
					$peca_descricao      = pg_result ($resP,$x,descricao);
					$ipi                 = pg_result ($resP,$x,ipi);
					$ncm                 = pg_result ($resP,$x,ncm);
					$peca_produto_acabado = pg_result ($resP,$x,produto_acabado);
					$peca_devolucao_obrigatoria = pg_result ($resX,$x,devolucao_obrigatoria);
					$aliq_icms           = pg_result ($resP,$x,aliq_icms);
					$aliq_ipi            = pg_result ($resP,$x,aliq_ipi);
					$peca_preco          = pg_result ($resP,$x,preco);

		            if(in_array($login_fabrica, array(153))){
		            	$sql_oss = "SELECT distinct os FROM tbl_faturamento_item WHERE extrato_devolucao = {$extrato} AND peca = {$peca} and nota_fiscal_origem notnull";
		            	$res_oss = pg_query($con, $sql_oss);
		            	if(pg_num_rows($res_oss) > 0){

		            		$os_faturamento = array();
		            		
		            		for($k = 0; $k < pg_num_rows($res_oss); $k++){

		            			$os_fat = pg_fetch_result($res_oss, $k, "os");
		            			$os_faturamento[] = $os_fat;

		            		}

		            		$os_faturamento = implode("<br>", $os_faturamento);

		            	}else{	
		            		$os_faturamento = "";
		            	}
		            }
					$valor_ipi_fat 		 = pg_result ($resP,$x,valor_ipi_fat);
					$base_icms           = pg_result ($resP,$x,base_icms);
					$valor_icms          = pg_result ($resP,$x,valor_icms);
					$base_ipi            = pg_result ($resP,$x,base_ipi);
					$valor_ipi           = pg_result ($resP,$x,valor_ipi);
					$total               = pg_result ($resP,$x,total);
					$qtde                = pg_result ($resP,$x,qtde);

					$sql_nf = "
						SELECT
							tbl_faturamento_item.nota_fiscal_origem
						FROM tbl_faturamento_item
						JOIN tbl_faturamento USING(faturamento)
						WHERE tbl_faturamento.fabrica = $login_fabrica
						AND tbl_faturamento.distribuidor = $login_posto
						AND tbl_faturamento.posto = $posto
						AND tbl_faturamento_item.extrato_devolucao = {$extrato}
						AND tbl_faturamento.faturamento = {$faturamento_nota}
						ORDER BY tbl_faturamento.nota_fiscal;
					";
					$resNF = pg_exec($con,$sql_nf);
					for ($y = 0; $y < pg_numrows($resNF); $y++) {
						$notafiscal = pg_result ($resNF,$y,nota_fiscal_origem);
		                if(!empty($notafiscal)){
		                    array_push($notas_fiscais,$notafiscal);    
		                }  				
					}
					$notas_fiscais = array_unique($notas_fiscais);
					asort($notas_fiscais);

					$peca_preco = ($qtde==0) ? $peca_preco : $total / $qtde;

					if (strlen ($aliq_icms)  == 0) {
						$aliq_icms = 0;
					}
					//$login_fabrica <> 90 and $login_fabrica <> 91 and $login_fabrica <> 94
					if (!in_array($login_fabrica, array(90,91,94,120,201))) {
						if ($peca_produto_acabado=='NOT TRUE'){ # se for peca, IPI = 0
							$aliq_ipi=0;
						}
					}

					if (strlen($aliq_ipi) == 0) {
						$aliq_ipi = 0;
					}

					if($login_fabrica == 35) { # HD 1831641
						$base_icms           = 0 ;
						$valor_icms          = 0 ;
						$aliq_icms           = 0 ;
						$base_ipi            = 0 ;
						$aliq_ipi            = 0 ;
						$valor_ipi           = 0 ;
					}

					$total_item  = $peca_preco * $qtde;
					$tota_pecas += $total_item;

					if (in_array($login_fabrica,array(120,201,175))) {
						$base_subs_trib *= $qtde;
						$valor_subs_trib *= $qtde;

						$total_valor_icms_st += $base_subs_trib;
						$total_base_st		 += $valor_subs_trib;
					}

					if($login_fabrica <> 43 or $login_fabrica <> 91) {
						$base_icms = ($aliq_icms==0) ? 0 : $total_item;
						$valor_icms= ($aliq_icms==0) ? 0 : $total_item * $aliq_icms / 100;
					}

					if($login_fabrica <> 91){
						$base_ipi = ($aliq_ipi==0) ? 0 : $total_item;
						$valor_ipi= ($aliq_ipi==0) ? 0 : $total_item*$aliq_ipi/100;
						$total_valor_ipi  += $valor_ipi;
					}else{ //HD 704422
						$total_valor_ipi  = $valor_ipi_fat;
					}

					$total_base_icms  += $base_icms;
					$total_valor_icms += $valor_icms;
					$total_base_ipi   += $base_ipi;
					$total_nota       += $total_item;

					echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
					if(in_array($login_fabrica, array(50,153))){
		                echo "<td align='left'>".$os_faturamento."</td>\n";
		            }
					echo "<td align='left'>".$peca_referencia."</td>\n";
					echo "<td align='left'>".$peca_descricao."</td>\n";

					if($login_fabrica == 91){
						echo "<td align='left'>".$ncm."</td>\n";
					}

					echo "<td align='center'>$qtde</td>\n";
					echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
					echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
					echo "<td align='right'>$aliq_icms</td>\n";
					if ($verificacao!=='1'){
						echo "<td align='right'>$aliq_ipi</td>\n";
					}
					if(in_array($login_fabrica,array(120,201,175))){
						echo "<td align='right'>$valor_ipi</td>\n";	
						echo "<td align='right'>$valor_icms</td>\n";
						echo "<td align='right'>$base_subs_trib</td>\n";
						echo "<td align='right'>$base_ipi</td>\n";
						echo "<td align='right'>$base_icms</td>\n";
						echo "<td align='right'>$valor_subs_trib</td>\n";
						//echo "<td align='right'>$valor_icms_st</td>\n";
						//echo "<td align='right'>$base_st</td>\n";
					}

					echo "</tr>\n";

				}

				echo "<table>";

			}

        }

        $cond_produto_acabado = (in_array($login_fabrica, array(153))) ? " AND tbl_peca.produto_acabado IS NOT TRUE " : "";

		$sql = "
			SELECT $campo_os_colormaq
			$campo_os_newmaq
			$campo_os_st
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.ipi,
				tbl_peca.peso,
				tbl_peca.ncm,
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
				CASE WHEN tbl_faturamento.fabrica IN(91,129,134) THEN
					tbl_faturamento_item.devolucao_obrig
				ELSE
					tbl_peca.devolucao_obrigatoria
				END AS devolucao_obrigatoria,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.preco,
				tbl_faturamento.valor_ipi as valor_ipi_fat,
				SUM (tbl_faturamento_item.qtde) as qtde,
				SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco) as total,
				SUM (tbl_faturamento_item.base_icms) AS base_icms,
				SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
				SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
				SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING (faturamento)
			JOIN tbl_peca             USING (peca)
			WHERE tbl_faturamento.fabrica = $login_fabrica
				AND (tbl_faturamento_item.extrato_devolucao = {$extrato} or tbl_faturamento.extrato_devolucao= $extrato)
				AND   tbl_faturamento.faturamento=$faturamento_nota
				AND   tbl_faturamento.posto = $posto
				AND   tbl_faturamento.distribuidor=$login_posto 
				$cond_produto_acabado
			GROUP BY $campo_os_colormaq
			$campo_os_newmaq
			$campo_order_by
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.devolucao_obrigatoria,
				tbl_peca.produto_acabado,
				tbl_peca.ipi,
				tbl_peca.ncm,
				valor_ipi_fat,
				tbl_faturamento.fabrica,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.devolucao_obrig,
				tbl_faturamento_item.preco
			ORDER BY tbl_peca.referencia;
		";

		$resX = pg_exec($con,$sql);
		$notas_fiscais = array();
		$qtde_peca = 0;

		if (pg_numrows($resX)==0) continue;

		echo $cabecalho;
		echo $topo;

		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$aliq_final       = 0;
		$tota_pecas       = 0;
		for ($x = 0; $x < pg_numrows($resX); $x++) {

			$peca                = pg_result ($resX,$x,peca);
			$peca_referencia     = pg_result ($resX,$x,referencia);
			$peca_descricao      = pg_result ($resX,$x,descricao);
			$ipi                 = pg_result ($resX,$x,ipi);
			$ncm                 = pg_result ($resX,$x,ncm);
			$peca_produto_acabado = pg_result ($resX,$x,produto_acabado);
			$peca_devolucao_obrigatoria = pg_result ($resX,$x,devolucao_obrigatoria);
			$aliq_icms           = pg_result ($resX,$x,aliq_icms);
			$aliq_ipi            = pg_result ($resX,$x,aliq_ipi);
			$peca_preco          = pg_result ($resX,$x,preco);
			if(in_array($login_fabrica, array(50))){
                $os_faturamento      = pg_result ($resX,$x,os);
            }
            if(in_array($login_fabrica, array(153))){
            	$sql_oss = "SELECT distinct os FROM tbl_faturamento_item WHERE extrato_devolucao = {$extrato} AND peca = {$peca} and nota_fiscal_origem notnull";
            	$res_oss = pg_query($con, $sql_oss);
            	if(pg_num_rows($res_oss) > 0){

            		$os_faturamento = array();
            		
            		for($k = 0; $k < pg_num_rows($res_oss); $k++){

            			$os_fat = pg_fetch_result($res_oss, $k, "os");
            			$os_faturamento[] = $os_fat;

            		}

            		$os_faturamento = implode("<br>", $os_faturamento);

            	}else{	
            		$os_faturamento = "";
            	}
            }
			$valor_ipi_fat 		 = pg_result ($resX,$x,valor_ipi_fat);
			$base_icms           = pg_result ($resX,$x,base_icms);
			$valor_icms          = pg_result ($resX,$x,valor_icms);
			$base_ipi            = pg_result ($resX,$x,base_ipi);
			$valor_ipi           = pg_result ($resX,$x,valor_ipi);
			$total               = pg_result ($resX,$x,total);
			$qtde                = pg_result ($resX,$x,qtde);
			$peso 				 = pg_result ($resX,$x,peso);

			if(in_array($login_fabrica, [120,201,175])){
				$base_subs_trib 	 = pg_result ($resX,$x,'base_subs_trib');
				$valor_subs_trib	 = pg_result ($resX,$x,'valor_subs_trib');
				//$valor_icms_st 	 = pg_result ($resX,$x,valor_icms_st);
				//$base_st	 = pg_result ($resX,$x,base_st);

				/*if (empty($valor_icms_st)) {
					$valor_icms_st = 0;
				}

				if (empty($base_st)) {
					$base_st = 0;
				}*/

			}

			$sql_nf = "
				SELECT
					tbl_faturamento_item.nota_fiscal_origem
				FROM tbl_faturamento_item
				JOIN tbl_faturamento USING(faturamento)
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND tbl_faturamento.distribuidor = $login_posto
				AND tbl_faturamento.posto = $posto
				AND tbl_faturamento_item.extrato_devolucao = {$extrato}
				AND tbl_faturamento.faturamento = {$faturamento_nota}
				ORDER BY tbl_faturamento.nota_fiscal;
			";
			$resNF = pg_exec($con,$sql_nf);
			for ($y = 0; $y < pg_numrows($resNF); $y++) {
				$notafiscal = pg_result ($resNF,$y,nota_fiscal_origem);
                if(!empty($notafiscal)){
                    array_push($notas_fiscais,$notafiscal);    
                }  				
			}
			$notas_fiscais = array_unique($notas_fiscais);
			asort($notas_fiscais);

			$peca_preco = ($qtde==0) ? $peca_preco : $total / $qtde;

			if (strlen ($aliq_icms)  == 0) {
				$aliq_icms = 0;
			}
			//$login_fabrica <> 90 and $login_fabrica <> 91 and $login_fabrica <> 94
			if (!in_array($login_fabrica, array(90,91,94,120,201))) {
				if ($peca_produto_acabado=='NOT TRUE'){ # se for peca, IPI = 0
					$aliq_ipi=0;
				}
			}

			if (strlen($aliq_ipi) == 0) {
				$aliq_ipi = 0;
			}

			if($login_fabrica == 35) { # HD 1831641
				$base_icms           = 0 ;
				$valor_icms          = 0 ;
				$aliq_icms           = 0 ;
				$base_ipi            = 0 ;
				$aliq_ipi            = 0 ;
				$valor_ipi           = 0 ;
			}

			$total_item  = $peca_preco * $qtde;
			$tota_pecas += $total_item;

			if (in_array($login_fabrica,array(120,201,175))) {
				$base_subs_trib *= $qtde;
				$valor_subs_trib *= $qtde;

				$total_valor_icms_st += $base_subs_trib;
				$total_base_st		 += $valor_subs_trib;
			}

			if($login_fabrica <> 43 or $login_fabrica <> 91) {
				$base_icms = ($aliq_icms==0) ? 0 : $total_item;
				$valor_icms= ($aliq_icms==0) ? 0 : $total_item * $aliq_icms / 100;
			}

			if($login_fabrica <> 91){
				$base_ipi = ($aliq_ipi==0) ? 0 : $total_item;
				$valor_ipi= ($aliq_ipi==0) ? 0 : $total_item*$aliq_ipi/100;
				$total_valor_ipi  += $valor_ipi;
			}else{ //HD 704422
				$total_valor_ipi  = $valor_ipi_fat;
			}

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi   += $base_ipi;
			$total_nota       += $total_item;

			if ($login_fabrica == 175) {
				$total_valor_st      += $valor_subs_trib;
                $total_valor_st_base += $base_subs_trib;
			}

			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
			if(in_array($login_fabrica, array(50,153))){
                echo "<td align='left'>".$os_faturamento."</td>\n";
            }
			echo "<td align='left'>".$peca_referencia."</td>\n";
			echo "<td align='left'>".$peca_descricao."</td>\n";

			if($login_fabrica == 91){
				echo "<td align='left'>".$ncm."</td>\n";
			}

			echo "<td align='center'>$qtde</td>\n";

			if($login_fabrica == 177){
				echo "<td align='center'>$peso</td>\n";				
			}

			if ($login_fabrica != 177){
				echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
				echo "<td align='right'>$aliq_icms</td>\n";
				if ($verificacao!=='1'){
					echo "<td align='right'>$aliq_ipi</td>\n";
				}
				if ($login_fabrica == 175) {
					echo "<td align='right'>$valor_subs_trib</td>\n";	
				}
			}
			if(in_array($login_fabrica,array(120,201,175))){
				echo "<td align='right'>$valor_ipi</td>\n";	
				echo "<td align='right'>$valor_icms</td>\n";
				echo "<td align='right'>$base_subs_trib</td>\n";
				echo "<td align='right'>$base_ipi</td>\n";
				echo "<td align='right'>$base_icms</td>\n";
				echo "<td align='right'>$valor_subs_trib</td>\n";
				//echo "<td align='right'>$valor_icms_st</td>\n";
				//echo "<td align='right'>$base_st</td>\n";
			}

			echo "</tr>\n";
			flush();
		}

		if ($login_fabrica == 142) {
            $notaFiscalSemDevolucao = array();

            foreach ($notas_fiscais as $nota_fiscal_sd) {
                $notaFiscalSemDevolucao[] = "'{$nota_fiscal_sd}'";
            }

            $sqlPecaSemDevolucao = "
                SELECT
                    tbl_peca.referencia,
                    tbl_peca.descricao,
                    tbl_faturamento_item.qtde,
                    tbl_faturamento_item.preco,
                    COALESCE(tbl_faturamento_item.aliq_icms, 0) AS aliq_icms,
                    COALESCE(tbl_faturamento_item.aliq_ipi, 0) AS aliq_ipi,
                    COALESCE(tbl_faturamento_item.base_icms, 0) AS base_icms,
	                COALESCE(tbl_faturamento_item.valor_icms, 0) AS valor_icms,
	                COALESCE(tbl_faturamento_item.base_ipi, 0) AS base_ipi,
	                COALESCE(tbl_faturamento_item.valor_ipi, 0) AS valor_ipi
                FROM tbl_os_item
                INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.os_item = tbl_os_item.os_item
                INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica} AND tbl_faturamento.posto = {$login_posto}
                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                WHERE tbl_faturamento_item.extrato_devolucao IS NULL
                AND tbl_faturamento.nota_fiscal IN (".implode(", ", $notaFiscalSemDevolucao).")
            ";
            $resPecaSemDevolucao = pg_query($con, $sqlPecaSemDevolucao);

            if (pg_num_rows($resPecaSemDevolucao) > 0) {
                while ($pecaSemDevolucao = pg_fetch_object($resPecaSemDevolucao)) {
                	$total_base_icms  += $pecaSemDevolucao->base_icms;
					$total_valor_icms += $pecaSemDevolucao->valor_icms;
					$total_base_ipi   += $pecaSemDevolucao->base_ipi;
					$total_nota       += ($pecaSemDevolucao->preco * $pecaSemDevolucao->qtde);

					if ($pecaSemDevolucao->valor_ipi > 0) {
						$valor_ipi = ($pecaSemDevolucao->preco * $pecaSemDevolucao->qtde) * ($aliq_ipi / 100);
						$total_valor_ipi += $valor_ipi;
					}

                    echo "
                        <tr style='background-color: #FFF; color: #000; text-align: left; font-size: 10px;' >
                            <td>{$pecaSemDevolucao->referencia}</td>
                            <td>{$pecaSemDevolucao->descricao}<br /><span style='color: #FF0000; text-align: center; font-weight: bold;' >".traduz('devolução não obrigatória')."</span></td>
                            <td style='text-align: center;' >{$pecaSemDevolucao->qtde}</td>
                            <td style='text-align: right;' >".number_format($pecaSemDevolucao->preco, 2, ",", ".")."</td>
                            <td style='text-align: right;' >".number_format(($pecaSemDevolucao->preco * $pecaSemDevolucao->qtde), 2, ",", ".")."</td>
                            <td style='text-align: right;' >{$pecaSemDevolucao->aliq_icms}</td>
                            <td style='text-align: right;' >{$pecaSemDevolucao->aliq_ipi}</td>
                        </tr>
                    ";
                }
            }
        }
        if(in_array($login_fabrica,array(120,201,175))){
        	$colspan = "15";
        }else{
        	$colspan = "8";
        }

		if (count($notas_fiscais) > 0 && $login_fabrica != 158){
			echo "<tfoot>";
			echo "<tr>";
			echo "<td colspan='$colspan'> ".traduz('Referente as NFs')." " . implode(", ",$notas_fiscais) . "</td>";
			echo "</tr>";
			echo "</tfoot>";
		}

		if ($login_fabrica == 158) {
			echo "<tfoot>";
			echo "<tr>";
			echo "<td colspan='8'> {$obs}</td>";
			echo "</tr>";
			echo "</tfoot>";
		}

		if($login_fabrica == 153 and 1==2){
			/* TROCA DE PRODUTO */
			$sql = "
				SELECT
					tbl_faturamento_item.os, 
					peca_antiga.referencia
				FROM tbl_faturamento_item 
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca AND tbl_peca.fabrica = {$login_fabrica} AND tbl_peca.produto_acabado IS TRUE
				INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_faturamento_item.os AND tbl_os_extra.extrato = {$extrato}
				INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_extra.os
				INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
				INNER JOIN tbl_peca AS peca_antiga ON peca_antiga.referencia = tbl_produto.referencia 
				AND peca_antiga.descricao = tbl_produto.descricao
				AND peca_antiga.fabrica = {$login_fabrica}
				WHERE extrato_devolucao = {$extrato};
			";
			$resTroca = pg_query($con,$sql);

			if(pg_num_rows($resTroca) > 0){
				$array_troca_produto = array();
				while($objeto_troca = pg_fetch_object($resTroca)) {
					$array_troca_produto[] = $objeto_troca->os; ?>
					<tfoot>
						<tr>
							<td colspan='8'> OS <?=$objeto_troca->os?> referente a Troca de Produto <?=$objeto_troca->referencia?></td>
						</tr>
					</tfoot>
				<? }
			}
		}

		echo "</table>\n";
		if ($login_fabrica != 177){
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
			echo "<tr>";
			echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
			echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";

			if ($verificacao == '1') {
				echo "<td>Total de Peças <br> <b> " . number_format ($tota_pecas,2,",",".") . " </b> </td>\n";
			} else {
				if ($login_fabrica == 91) { //HD 704422
					echo "<td>Total do Produto<br> <b> " . number_format ($total_nota,2,",",".") . " </b> </td>";
					echo "<td>Valor IPI <br> <b> " . number_format ($valor_ipi_fat,2,",",".") . "</b> </td>";
				} else {
					if ($login_fabrica == 90){ //HD 724855
						echo "<td colspan='2' valign='middle'>";
						echo "<table cellspacing='0' width='100%' cellpadding='0'>";
						echo "<tr>";
						echo"<td width='60%' align='center'>";
						echo "<label style='font:13px Arial'>Valor Total dos Produtos</label>  <br> <b>". number_format ($total_base_ipi,2,",",".") . " </b>";
						echo"</td>";
						echo"<td width='10%' align='center'>+</td>";
						echo"<td width='30%' align='center'>";
						echo " Valor IPI <br> <b>". number_format ($total_valor_ipi,2,",",".") ." </b>";
						echo"</td>";
						echo"</tr>";
						echo"</table>";
						echo "</td>";
					} else {
						echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
						echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . "</b> </td>";
						if ($login_fabrica == 175) {
							echo '<td>Base ST<br><b>'       . number_format($total_valor_st_base,      2, ', ', '.') . '</b></td>';
							echo '<td>Valor ST<br><b>'      . number_format($total_valor_st,           2, ', ', '.') . '</b></td>';
						}
					}
				}
			}

			$tota_geral = ($verificacao == '1') ? $total_nota : $total_nota + $total_valor_ipi;

			if (in_array($login_fabrica,array(120,201,175))) {
				$tota_geral += ($total_base_st);
			}

			if ($login_fabrica == 120 or $login_fabrica == 201) {
				echo "<td>Valor Base Sub ICMS <br> <b>".number_format ($total_valor_icms_st,2,",",".")."</b></td>";
				echo "<td>Valor Sub. ICMS<br> <b>".number_format ($total_base_st,2,",",".")."</b></td>";
				$total_valor_icms_st = 0;
				$total_base_st 		 = 0;
				echo "<td>Total das Peças <br> <b> " . number_format ($tota_pecas,2,",",".") . " </b> </td>";
			}

			echo "<td>".traduz('Total da Nota')." <br> <b> " . number_format ($tota_geral,2,",",".") . " </b> </td>";
			echo "</tr>";
			echo "</table>";
		}
		if (strlen($cancelada) == 0) {
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";

			$colspan='0';
			if($login_fabrica == 177){
				if(strlen(trim($transportadora))>0){
					$sqlT = "SELECT   
	                        tbl_transportadora.cnpj, 
	                        tbl_transportadora.nome,
	                        tbl_transportadora.transportadora
	                    FROM tbl_transportadora 
	                    JOIN tbl_transportadora_fabrica ON tbl_transportadora.transportadora = tbl_transportadora_fabrica.transportadora
	                    WHERE tbl_transportadora.transportadora = $transportadora 
	                    AND fabrica = $login_fabrica";
	                $resT = pg_query($con, $sqlT);
	                if(pg_num_rows($resT)>0){
	                	$nome_transportadora = pg_fetch_result($resT, 0, 'nome');
	                }
				}elseif($transp == "conta"){
					$nome_transportadora = "Frete por Conta";
				}
			

				$colspan='2';
				echo "<tr>\n";
					echo "<td>Volume: <b>$qtde_volume</b> </td>";
					echo "<td>Transportadora: <b> $nome_transportadora </b></td>";
				echo "</tr>";
			}
			echo "<tr>\n";
			echo "<td colspan='$colspan'>";
			/* HD 48024*/
			if ($login_fabrica == 50){
				echo "<p><b>Observação:</b> RETORNO DE REMESSA DE MERCADORIA PARA TROCA EM GARANTIA.  PARTES E PECAS DE BEM VENDIDO EM GARANTIA, SUSPENSO DO IPI, ART 42  ITEM XIII, DECRETO No. 4.544/02</p>";
			}
			echo "<h1><center>Nota de Devolução $nota_fiscal</center></h1>";
			echo "</td>\n";
			if(in_array($login_fabrica, array(125,153))){
				echo "<td align='center'><button onclick='solicitaPostagemPosto($extrato, $faturamento_nota)'>Solicitar Autorização de Postagem </button> </td>";
			}
			echo "</tr>";
            
            if ($login_fabrica == 35) {
	            $verificaObservacao = "SELECT obs FROM tbl_faturamento where fabrica = {$login_fabrica} AND nota_fiscal = '{$nota_fiscal}'";
                $resVerificaObservacao = pg_query($con, $verificaObservacao);
                $obs = pg_fetch_result($resVerificaObservacao, 0, "obs"); ?>
				<tr>
					<td colspan='2'>OBSERVAÇÃO: <?= (strlen($obs) > 0) ?  $obs :  " - "; ?></td>
                </tr>
			<? }
			echo "</table>";
		} else {
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
			echo "<tr>\n";
			echo "<td><h1><center><strike>".traduz('Nota de Devolução'). "$nota_fiscal</strike></center></h1><br>\n";
			echo "<h4 style='color:red'><center>".traduz("ESTA NOTA FOI CANCELADA EM"). "$cancelada</center></h4></td>\n";
			echo "</tr>";
			echo "</table>";
		}
		if($login_fabrica == 177){   

		echo '<br><br><TABLE width="75%" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr><td>';
            if(isset($faturamento_nota)>0){
                $tempUniqueId = $faturamento_nota;
                $anexoNoHash = null;
            } else {
                $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
                $anexoNoHash = true;
            }
            $boxUploader = array(
                "div_id" => "div_anexos",
                "prepend" => $anexo_prepend,
                "context" => "lgr",
                "unique_id" => $tempUniqueId,
                "hash_temp" => $anexoNoHash
            );
            echo "<input type='hidden' name='hashBox' id='hashBox' value='{$tempUniqueId}'/>";
            include "box_uploader.php";
         
        echo "</td></tr></table>";
    	}

		if ($login_fabrica == 142 && pg_num_rows($resPecaSemDevolucao) > 0) { ?>
            <div style="width: 75%; margin: 0 auto; background-color: #FF0000;" >
                <h4 style="color: #FFFFFF;" >
                    As peças marcadas como DEVOLUÇÃO NÃO OBRIGATÓRIA não precisam ser devolvidas, porém precisam constar na nota fiscal de devolução para fins fiscais.
                </h4>
            </div>
        <?php
        }

		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$tota_pecas       = 0;
	}
} else {
	echo "<h1><center> ".traduz('Sem peças para Devolução')." </center></h1>";
}

$sql = "
	SELECT DISTINCT
		faturamento,
		tbl_faturamento_item.extrato_devolucao,
		nota_fiscal
	FROM tbl_faturamento_item
	JOIN tbl_faturamento USING(faturamento)
	WHERE posto IN ({$posto_da_fabrica})
	AND distribuidor = {$login_posto}
	AND fabrica = {$login_fabrica}
	AND tbl_faturamento_item.extrato_devolucao = {$extrato}
	AND obs = 'Devolução de Ressarcimento'
	ORDER BY faturamento ASC;
";

$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0 || $login_fabrica == 146) {
	$nota_fiscal_ressarcimento = pg_fetch_result($res,0,2);

	### Produtos Ressarcidos ###
	/*HD: 126697 - Quando o posto não tem faturamento, não estrava na impressão da nota de ressarcimento*/
	if ($numero_linhas != 5000 || $res_qtde == 0 || $login_fabrica == 146){
		#Tirei as partes de faturamento - Fabio - 31-03-2008
		if ($login_fabrica != 146) {
            $where_troca_garantia = " AND tbl_os.troca_garantia  IS TRUE ";
        }
		$sql = "
			SELECT DISTINCT
				tbl_os.os,
				tbl_os.sua_os,
				TO_CHAR(tbl_os_troca.data,'DD/MM/YYYY') AS data_ressarcimento,
				tbl_produto.produto AS produto,
				tbl_produto.referencia AS produto_referencia,
				tbl_produto.descricao AS produto_descricao,
				tbl_admin.login
			FROM tbl_os
			JOIN tbl_os_troca USING(os)
			JOIN tbl_os_extra USING(os)
			JOIN tbl_extrato ON tbl_extrato.fabrica = tbl_os.fabrica AND tbl_extrato.posto = tbl_os.posto AND tbl_extrato.extrato = tbl_os_extra.extrato
			LEFT JOIN tbl_admin ON tbl_os.troca_garantia_admin = tbl_admin.admin
			LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_extrato.extrato = {$extrato}
			AND  tbl_os.fabrica = {$login_fabrica}
			AND  tbl_os.posto = {$login_posto}
			AND  tbl_os_troca.ressarcimento IS TRUE
			{$where_troca_garantia};
		";

		$resX = pg_query($con,$sql);

		$qtde_produtos_ressarcimento = pg_num_rows($resX);

		if ($qtde_produtos_ressarcimento > 0) {

			//HD43448
			$razao    = "$razao";
			$endereco = "$endereco";
			$cidade   = "$cidade";
			$estado   = "$estado";
			$cep      = "$cep";
			$cnpj     = "$cnpj";
			$ie       = "$ie";

			$natureza_operacao = "Simples Remessa";

			# HD 13354
			$sql = "
				SELECT
					contato_estado
				FROM tbl_posto_fabrica
				WHERE fabrica = {$login_fabrica}
				AND posto = {$login_posto};
			";
			$resW = pg_query($con,$sql);

			if (pg_num_rows($resW) > 0) {
				$estado_posto = strtoupper(trim(pg_fetch_result($resW, 0, contato_estado)));
			}

			$cfop = getCFOP($estado_posto);

			echo "<input type='hidden' name='ressarcimento' value='$extrato'>\n";
			echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";

			echo "<tr align='left'  height='16'>\n";
			echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
			echo "<b>&nbsp;<b>".traduz('RESSARCIMENTO FINANCEIRO - RETORNO OBRIGATÓRIO')." '</b><br>\n";
			echo "</td>\n";
			echo "</tr>\n";
			$data = date("d/m/Y");
			echo "<tr>";
			echo "<td>".traduz("Natureza")." <br> <b>".traduz("Simples Remessa")."</b> </td>";
			echo "<td>CFOP <br> <b>$cfop</b> </td>";
			echo "<td>".traduz('Emissão')." <br> <b>$data</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
			echo "<tr>";
			echo "<td>".traduz("Razão Social")." <br> <b>$razao</b> </td>";
			echo "<td>".traduz("CNPJ")." <br> <b>$cnpj</b> </td>";
			echo "<td>".traduz("Inscrição Estadual")." <br> <b>$ie</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
			echo "<tr>";
			echo "<td>".traduz("Endereço")." <br> <b>$endereco </b> </td>";
			echo "<td>".traduz("Cidade")." <br> <b>$cidade</b> </td>";
			echo "<td>".traduz("Estado")." <br> <b>$estado</b> </td>";
			echo "<td>".traduz("CEP")." <br> <b>$cep</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
			echo "<tr align='center' style='font-weight:bold'>";
			echo "<td>".traduz("Código")."</td>";
			echo "<td>".traduz("Descrição")."</td>";
			echo "<td>".traduz("Ressarcimento")."</td>";
			echo "<td>".traduz("Responsavel")."</td>";
			echo "<td>OS</td>";
			echo "</tr>";

			for ($x = 0; $x < $qtde_produtos_ressarcimento; $x++) {
				$os                 = pg_fetch_result ($resX,$x,os);
				$sua_os             = pg_fetch_result ($resX,$x,sua_os);
				$produto            = pg_fetch_result ($resX,$x,produto);
				$produto_referencia = pg_fetch_result ($resX,$x,produto_referencia);
				$produto_descricao  = pg_fetch_result ($resX,$x,produto_descricao);
				$data_ressarcimento = pg_fetch_result ($resX,$x,data_ressarcimento);
				$quem_trocou        = pg_fetch_result ($resX,$x,login);

				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
				echo "<input type='hidden' name='ressarcimento_produto_".$x."' value='$produto'>";
				echo "<input type='hidden' name='ressarcimento_os_".$x."' value='$os'>";
				echo "<td align='left'>$produto_referencia</td>";
				echo "<td align='left'>$produto_descricao</td>";
				echo "<td align='left'>$data_ressarcimento</td>";
				echo "<td align='right'>$quem_trocou</td>";
				echo "<td align='right'>$sua_os</td>";
				echo "</tr>";
			}

			echo "</table>";
			echo "<input type='hidden' name='qtde_produtos_ressarcimento' value='$qtde_produtos_ressarcimento'>";
			echo "<input type='hidden' name='ressarcimento_natureza' value='$natureza_operacao'>";
			echo "<input type='hidden' name='ressarcimento_cfop' value='$cfop'>";

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
			echo "<tr>";
			echo "<td>";
			echo "<center>";
			echo "<b>".traduz("Preencha este Nota de Devolução e informe o número da Nota Fiscal")."</b><br>".traduz("Este número não poderá ser alterado")."<br>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center'>";
			echo "<br><div style='margin:0 auto; text-align:center;color:#D90000;font-weight:bold'>* ".traduz("Para o preenchimento da Nota Fiscal de Simples Remessa, utilizar o mesmo valor da Nota Fiscal de compra do consumidor")."</div>";

			if (strlen($nota_fiscal_ressarcimento)==0) {
				echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>".traduz('Número da Nota').": <input type='text' name='ressarcimento_nota_fiscal' id='ressarcimento_nota_fiscal' size='12' maxlength='12' value='$nota_fiscal_ressarcimento'
				> &nbsp;<input type='button' name='gravar' value='Gravar' onclick='gravaRessarcimento(document.getElementById(\"ressarcimento_nota_fiscal\").value,$extrato)'>";
			} else {
				echo "<h1><center>".traduz("Nota de Devolução")." $nota_fiscal_ressarcimento</center></h1>";
			}
			echo "</td>";
			echo "<tr>
			<td align='center'><div id='div_msg' style='display:none; border: 1px solid #949494;background-color: #F1F0E7;width:180px;'></div>
			</td>
			</tr>";
			echo "</tr>";
			echo "</table>";
		}
	}
} else {
	echo "<h1><center></center></h1>";
}

if($login_fabrica == 50) { ?>
	<script>
		window.print();
	</script>
<? } ?>
<br />
<br />
<? include "rodape.php"; ?>
