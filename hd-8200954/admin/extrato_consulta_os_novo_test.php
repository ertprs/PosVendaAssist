<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (strlen($_POST["btn_acao"]) == 0) {
	$data_inicial = $_GET["data_inicial"];
	$data_final = $_GET["data_final"];
	$cnpj = $_GET["cnpj"];
	$razao = $_GET["razao"];
}

if (strlen($_POST["btn_acao"]) == 0 && strlen($_POST["select_acao"]) == 0) {
	setcookie("link", $REQUEST_URI, time()+60*60*24); # Expira em 1 dia
}

$admin_privilegios="financeiro";
include "autentica_admin.php";

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim(strtolower($_POST["btn_acao"]));

if (strlen($_POST["select_acao"]) > 0) $select_acao = strtoupper($_POST["select_acao"]);

if (strlen($_POST["extrato"]) > 0) $extrato = trim($_POST["extrato"]);
if (strlen($_GET["extrato"]) > 0)  $extrato = trim($_GET["extrato"]);

$msg_erro = "";

if ($btn_acao == 'pedido'){
	header ("Location: relatorio_pedido_peca_kit.php?extrato=$extrato");
	exit;
}

if ($btn_acao == 'baixar') {

	if (strlen($_POST["extrato_pagamento"]) > 0) $extrato_pagamento = trim($_POST["extrato_pagamento"]);
	if (strlen($_GET["extrato_pagamento"]) > 0)  $extrato_pagamento = trim($_GET["extrato_pagamento"]);

	$valor_total     = trim($_POST["valor_total"]) ;
	if(strlen($valor_total) > 0)   $xvalor_total = "'".str_replace(",",".",$valor_total)."'";
	else                           $xvalor_total = 'NULL';

	$acrescimo       = trim($_POST["acrescimo"]) ;
	if(strlen($acrescimo) > 0)     $xacrescimo = "'".str_replace(",",".",$acrescimo)."'";
	else                           $xacrescimo = 'NULL';

	$desconto        = trim($_POST["desconto"]) ;
	if(strlen($desconto) > 0)      $xdesconto = "'".str_replace(",",".",$desconto)."'";
	else                           $xdesconto = 'NULL';

	$valor_liquido   = trim($_POST["valor_liquido"]) ;
	if(strlen($valor_liquido) > 0) $xvalor_liquido = "'".str_replace(",",".",$valor_liquido)."'";
	else                           $xvalor_liquido = 'NULL';

	$nf_autorizacao = trim($_POST["nf_autorizacao"]) ;
	if(strlen($nf_autorizacao) > 0) $xnf_autorizacao = "'$nf_autorizacao'";
	else                            $xnf_autorizacao = 'NULL';

	$autorizacao_pagto = trim($_POST["autorizacao_pagto"]) ;
	if(strlen($nf_autorizacao) > 0) $xautorizacao_pagto = "'$autorizacao_pagto'";
	else                            $xautorizacao_pagto = 'NULL';

	if (strlen($_POST["data_vencimento"]) > 0) {
		$data_pagamento = trim($_POST["data_pagamento"]) ;
		$xdata_pagamento = str_replace ("/","",$data_pagamento);
		$xdata_pagamento = str_replace ("-","",$xdata_pagamento);
		$xdata_pagamento = str_replace (".","",$xdata_pagamento);
		$xdata_pagamento = str_replace (" ","",$xdata_pagamento);

		$dia = trim (substr ($xdata_pagamento,0,2));
		$mes = trim (substr ($xdata_pagamento,2,2));
		$ano = trim (substr ($xdata_pagamento,4,4));
		if (strlen ($ano) == 2) $ano = "20" . $ano;

//-=============Verifica data=================-//
		
		$verifica = checkdate($mes,$dia,$ano);
		if ( $verifica ==1){
			$xdata_pagamento = $ano . "-" . $mes . "-" . $dia ;
			$xdata_pagamento = "'" . $xdata_pagamento . "'";
		}else{
			$msg_erro="A Data de Pagamento não está em um formato válido";
		}
	}else{
		$xdata_pagamento = "'NULL'";
	}
	
	if (strlen($_POST["data_vencimento"]) > 0) {
		$data_vencimento = trim($_POST["data_vencimento"]) ;
		$xdata_vencimento = str_replace ("/","",$data_vencimento);
		$xdata_vencimento = str_replace ("-","",$xdata_vencimento);
		$xdata_vencimento = str_replace (".","",$xdata_vencimento);
		$xdata_vencimento = str_replace (" ","",$xdata_vencimento);

		$dia = trim (substr ($xdata_vencimento,0,2));
		$mes = trim (substr ($xdata_vencimento,2,2));
		$ano = trim (substr ($xdata_vencimento,4,4));
		if (strlen ($ano) == 2) $ano = "20" . $ano;
		$verifica = checkdate($mes,$dia,$ano);
		if ( $verifica ==1){
			$xdata_vencimento = $ano . "-" . $mes . "-" . $dia ;
			$xdata_vencimento = "'" . $xdata_vencimento . "'";
		}else{
			$msg_erro .="<br>A Data de Vencimento não está em um formato válido<br>";
		}
	}else{
		$xdata_vencimento = "'NULL'";
	}

	if (strlen($_POST["obs"]) > 0) {
		$obs = trim($_POST["obs"]) ;
		$xobs = "'" . $obs . "'";
	}else{
		$xobs = "NULL";
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($extrato_pagamento) > 0) {
			$sql = "SELECT extrato FROM tbl_extrato WHERE fabrica = $login_fabrica AND extrato = $extrato";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) == 0) $msg_erro = "Erro ao cadastrar baixa. Extrato não pertence à esta fábrica.";
		}

		if (strlen($msg_erro) == 0) {
			if (strlen($extrato_pagamento) > 0) {
				$sql = "UPDATE tbl_extrato_pagamento SET
							extrato           = $extrato           ,
							valor_total       = $xvalor_total       ,
							acrescimo         = $xacrescimo         ,
							desconto          = $xdesconto          ,
							valor_liquido     = $xvalor_liquido     ,
							nf_autorizacao    = $xnf_autorizacao    ,
							data_vencimento   = $xdata_vencimento   ,
							data_pagamento    = $xdata_pagamento    ,
							autorizacao_pagto = $xautorizacao_pagto ,
							obs               = $xobs               ,
							admin             = $login_admin
						WHERE tbl_extrato_pagamento.extrato_pagamento = $extrato_pagamento
						AND   tbl_extrato_pagamento.extrato           = $extrato
						AND   tbl_extrato.fabrica = $login_fabrica";
			}else{
				$sql = "INSERT INTO tbl_extrato_pagamento (
							extrato           ,
							valor_total       ,
							acrescimo         ,
							desconto          ,
							valor_liquido     ,
							nf_autorizacao    ,
							data_vencimento   ,
							data_pagamento    ,
							autorizacao_pagto ,
							obs               ,
							admin
						)VALUES(
							$extrato           ,
							$xvalor_total      ,
							$xacrescimo        ,
							$xdesconto         ,
							$xvalor_liquido    ,
							$xnf_autorizacao   ,
							$xdata_vencimento  ,
							$xdata_pagamento   ,
							$xautorizacao_pagto,
							$xobs              ,
							$login_admin
						)";
			}
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: extrato_consulta.php?data_inicial=$data_inicial&data_final=$data_final&cnpj=$cnpj&razao=$razao");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btn_acao == "excluir") {
	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");

	for ($k = 0 ; $k < $qtde_os; $k++) {
		$x_os  = trim($_POST["os_" . $k]);
		$x_obs = trim($_POST["obs_" . $k]);

		if (strlen($x_obs) == 0) {
			$msg_erro    = " Informe a observação na OS $x_os. ";
			$linha_erro  = $k;
			$select_acao = "EXCLUIR";
		}

		$sql =	"INSERT INTO tbl_os_status (
						extrato    ,
						os         ,
						observacao ,
						status_os
					) VALUES (
						$extrato ,
						$x_os    ,
						'$x_obs' ,
						15
					);";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_os_extra SET extrato = null
						WHERE  tbl_os_extra.os      = $x_os
						AND    tbl_os_extra.extrato = $extrato
						AND    tbl_os_extra.os      = tbl_os.os
						AND    tbl_os_extra.extrato = tbl_extrato.extrato
						AND    tbl_extrato.extrato  = tbl_extrato_extra.extrato
						AND    tbl_extrato_extra.baixado IS NULL
						AND    tbl_os.fabrica  = $login_fabrica;";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
/*
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
*/
		if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_os SET excluida = true
						WHERE  tbl_os.os           = $x_os
						AND    tbl_os.fabrica      = $login_fabrica;";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			#158147 Paulo/Waldir desmarcar se for reincidente
			$sql = "SELECT fn_os_excluida_reincidente($x_os,$login_fabrica)";
			$res = pg_exec($con, $sql);


		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato 
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (pg_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
			$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}


if ($btn_acao == "recusar") {
	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");

	for ($k = 0 ; $k < $qtde_os; $k++) {
		$x_os  = trim($_POST["os_" . $k]);
		$x_obs = trim($_POST["obs_" . $k]);

		if (strlen($x_obs) == 0) {
			$msg_erro    = " Informe a observação na OS $x_os. ";
			$linha_erro  = $k;
			$select_acao = "RECUSAR";
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_recusa_os($login_fabrica, $extrato, $x_os, '$x_obs');";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato 
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (pg_numrows($res) > 0) {
			if (@pg_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
				$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "acumular") {
	$qtde_os = $_POST["qtde_os"];
	$res = pg_exec($con,"BEGIN TRANSACTION");

	for ($k = 0 ; $k < $qtde_os; $k++) {
		$x_os  = trim($_POST["os_" . $k]);
		$x_obs = trim($_POST["obs_" . $k]);

		if (strlen($x_obs) == 0) {
			$msg_erro    = " Informe a observação na OS $x_os. ";
			$linha_erro  = $k;
			$select_acao = "ACUMULAR";
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_acumula_os($login_fabrica, $extrato, $x_os, '$x_obs');";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT posto
				FROM   tbl_extrato 
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (pg_numrows($res) > 0) {
			if (@pg_result($res,0,posto) > 0 AND strlen($msg_erro) == 0){
				$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		$link = $_COOKIE["link"];
		header ("Location: $link");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "acumulartudo") {
	if (strlen($extrato) > 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");

//if($ip == '201.0.9.216') echo $extrato."<br>";
		$sql = "SELECT fn_acumula_extrato ($login_fabrica, $extrato);";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			$res = pg_exec($con,"COMMIT TRANSACTION");
			$link = $_COOKIE["link"];
			header ("Location: $link");
			exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}
}

$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços";
include "cabecalho.php";

?>
<p>

<style type="text/css">

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
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;

}

.table_line2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<script language="JavaScript">
var ok = false;
function checkaTodos() {
	f = document.frm_extrato_os;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
			}
		}
	}
}
</script>


<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<!--aqui-->
<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<?
}

?>
<!--aqui-->

<?
echo "<FORM METHOD=POST NAME='frm_extrato_os' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='extrato' value='$extrato'>";
echo "<input type='hidden' name='extrato_pagamento' value='$extrato_pagamento'>";
echo "<input type='hidden' name='btn_acao' value=''>";
?>

<?
/*
Verifica de a ação é "RECUSAR" ou "ACUMULAR"
para somente mostrar a tela para a digitação da observação.
*/
if (strlen($select_acao) == 0) {

$sql = "SELECT      lpad (tbl_os.sua_os,10,'0')                                  AS ordem           ,
					tbl_os.os                                                                       ,
					tbl_os.sua_os                                                                   ,
					to_char (tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data            ,
					to_char (tbl_os.data_abertura ,'DD/MM/YYYY')                 AS abertura        ,
					tbl_os.serie                                                                    ,
					tbl_os.codigo_fabricacao                                                        ,
					tbl_os.consumidor_nome                                                          ,
					tbl_os.consumidor_fone                                                          ,
					tbl_os.revenda_nome                                                             ,
					tbl_os.data_fechamento                                                          ,
					tbl_os.pecas                                                 AS total_pecas     ,
					tbl_os.mao_de_obra                                           AS total_mo        ,
					tbl_os.cortesia                                                                 ,
					tbl_produto.referencia                                                          ,
					tbl_produto.descricao                                                           ,
					tbl_os_extra.extrato                                                            ,
					tbl_os_extra.os_reincidente                                                     ,
					to_char (tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao    ,
					tbl_extrato.total                                            AS total           ,
					tbl_extrato.mao_de_obra                                      AS mao_de_obra     ,
					tbl_extrato.pecas                                            AS pecas           ,
					lpad (tbl_extrato.protocolo,5,'0')                           AS protocolo       ,
					tbl_posto.nome                                               AS nome_posto      ,
					tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
					tbl_extrato_pagamento.valor_total                                               ,
					tbl_extrato_pagamento.acrescimo                                                 ,
					tbl_extrato_pagamento.desconto                                                  ,
					tbl_extrato_pagamento.valor_liquido                                             ,
					tbl_extrato_pagamento.nf_autorizacao                                            ,
					to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
					to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
					tbl_extrato_pagamento.autorizacao_pagto                                         ,
					tbl_extrato_pagamento.obs                                                       ,
					tbl_extrato_pagamento.extrato_pagamento
		FROM        tbl_extrato
		LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato  = tbl_extrato.extrato
		LEFT JOIN tbl_os_extra          ON  tbl_os_extra.extrato           = tbl_extrato.extrato
		LEFT JOIN tbl_os                ON  tbl_os.os                      = tbl_os_extra.os
		JOIN      tbl_produto           ON  tbl_produto.produto            = tbl_os.produto
		JOIN      tbl_posto             ON  tbl_posto.posto                = tbl_os.posto
		JOIN      tbl_posto_fabrica     ON  tbl_posto.posto                = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica      = $login_fabrica
		WHERE		tbl_extrato.fabrica = $login_fabrica
		AND         tbl_extrato.extrato = $extrato
		ORDER BY    lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0)               ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,0),'-','') ASC";

if ($login_fabrica == 1){
	// sem paginacao
	//if ($ip == '201.0.9.216') echo "<br>$sql<br>";
	$res = pg_exec($con,$sql);
}else{
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //
}

$ja_baixado = false ;

if (@pg_numrows($res) == 0) {
	echo "<h1>Nenhum resultado encontrado.</h1>";
}else{
	?>
	<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td bgcolor="FFCCCC">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>REINCIDÊNCIAS</b></td>
	</tr>
	<? if ($login_fabrica == 1) { ?>
	<tr><td height="3"></td></tr>
	<tr>
		<td bgcolor="#D7FFE1">&nbsp;&nbsp;&nbsp;&nbsp;</td><td width='100%' valign="middle" align="left">&nbsp;<b>OS CORTESIA</b></td>
	</tr>
	<? } ?>
	</table>
	<br>
<?
	if (strlen ($msg_erro) == 0) {
		$extrato_pagamento = pg_result ($res,0,extrato_pagamento) ;
		$valor_total       = pg_result ($res,0,valor_total) ;
		$acrescimo         = pg_result ($res,0,acrescimo) ;
		$desconto          = pg_result ($res,0,desconto) ;
		$valor_liquido     = pg_result ($res,0,valor_liquido) ;
		$nf_autorizacao    = pg_result ($res,0,nf_autorizacao) ;
		$data_vencimento   = pg_result ($res,0,data_vencimento) ;
		$data_pagamento    = pg_result ($res,0,data_pagamento) ;
		$obs               = pg_result ($res,0,obs) ;
		$autorizacao_pagto = pg_result ($res,0,autorizacao_pagto) ;
		$codigo_posto      = pg_result ($res,0,codigo_posto) ;
		$protocolo         = pg_result ($res,0,protocolo) ;
	}

	if (strlen ($extrato_pagamento) > 0) $ja_baixado = true ;
	
	$sql = "SELECT count(*) as qtde
			FROM   tbl_os_extra
			WHERE  tbl_os_extra.extrato = $extrato";
	$resx = pg_exec($con,$sql);
	
	if (pg_numrows($resx) > 0) $qtde_os = pg_result($resx,0,qtde);
	
	echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0'>";
	
	echo"<TR class='menu_top'>";
	echo"<TD align='left'> Extrato: ";
	if ($login_fabrica == 1) echo $protocolo;
	else                     echo $extrato;
	echo "</TD>";
	echo "<TD align='left'> Data: " . pg_result ($res,0,data_geracao) . "</TD>";
	echo"<TD align='left'> Qtde de OS: ". $qtde_os ."</TD>";
	echo"<TD align='left'> Total: R$ " . number_format(pg_result ($res,0,total),2,",",".") . "</TD>";
	echo"</TR>";

	echo"<TR class='menu_top'>";
	echo"<TD align='left'> Código: " . pg_result ($res,0,codigo_posto) . " </TD>";
	echo"<TD align='left' colspan='3'> Posto: " . pg_result ($res,0,nome_posto) . "  </TD>";
	echo"</TR>";
	echo"</TABLE>";
	echo"<br>";
	
	if ($login_fabrica <> 6) {
		$sql = "SELECT  count(*) as qtde,
						tbl_linha.nome
				FROM   tbl_os
				JOIN   tbl_os_extra  ON tbl_os_extra.os     = tbl_os.os
				JOIN   tbl_produto   ON tbl_produto.produto = tbl_os.produto
				JOIN   tbl_linha     ON tbl_linha.linha     = tbl_produto.linha
									AND tbl_linha.fabrica   = $login_fabrica
				WHERE  tbl_os_extra.extrato = $extrato
				GROUP BY tbl_linha.nome
				ORDER BY count(*)";
		$resx = pg_exec($con,$sql);
		
		if (pg_numrows($resx) > 0) {
			echo "<TABLE width='50%' border='0' align='center' cellspacing='1' cellpadding='0'>";
			echo "<TR class='menu_top'>";
			
			echo "<TD align='left'>LINHA</TD>";
			echo "<TD align='center'>QTDE OS</TD>";
			
			echo "</TR>";
			
			for ($i = 0 ; $i < pg_numrows($resx) ; $i++) {
				$linha = trim(pg_result($resx,$i,nome));
				$qtde  = trim(pg_result($resx,$i,qtde));
				
				echo "<TR class='menu_top'>";
				
				echo "<TD align='left'>$linha</TD>";
				echo "<TD align='center'>$qtde</TD>";
				
				echo "</TR>";
			}
			
			echo "</TABLE>";
			echo"<br>";
		}
	}
	
	if ($login_fabrica <> 1){
		$sql = "SELECT pedido FROM tbl_pedido WHERE pedido_kit_extrato = $extrato";
		$resE = pg_exec($con,$sql);
		if (pg_numrows($resE) == 0)
			echo "<img src='imagens/btn_pedidopecaskit.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='pedido' ; document.frm_extrato_os.submit()\" ALT='Pedido de Peças do Kit' border='0' style='cursor:pointer;'>";
		echo "<br>";
		echo "<br>";
	}

	if ($login_fabrica == 1) {
		echo "<img border='0' src='imagens/btn_acumulartodoextrato.gif' onclick=\"javascript: document.frm_extrato_os.btn_acao.value='acumulartudo'; document.frm_extrato_os.submit();\" alt='Clique aqui p/ acumular todas OSs deste Extrato' style='cursor: hand;'><br><br>";
	}

	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	if (strlen($msg) > 0) {
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan=9>$msg</TD>\n";
		echo "</TR>\n";
	}

	echo "<TR class='menu_top'>\n";
	if ($ja_baixado == false AND $login_fabrica<>20) echo "<TD align='center' width='30'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></TD>\n";
	echo "<TD width='075' >OS</TD>\n";
	if ($login_fabrica == 1) echo "<TD width='075'>CÓD. FABR.</TD>\n";
	echo "<TD width='075'>SÉRIE</TD>\n";
	if ($login_fabrica <> 1) echo "<TD width='075'>ABERTURA</TD>\n";
/*	echo "<TD width='130'>CONSUMIDOR</TD>\n";*/
	echo "<TD width='130'>PRODUTO</TD>\n";
	if ($login_fabrica == 1 OR $login_fabrica == 20) {
/*		echo "<TD width='100'>REVENDA</TD>\n";*/
		echo "<TD width='100' nowrap>TOTAL PEÇA</TD>\n";
		echo "<TD width='100' nowrap>TOTAL MO</TD>\n";
		echo "<TD width='100' nowrap>PEÇA + MO</TD>\n";
	}
	if ($login_fabrica == 20) {
		echo "<TD width='30'></TD>\n";
		echo "<TD >STATUS</TD>\n";
	}
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$os                 = trim(pg_result ($res,$i,os));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$data               = trim(pg_result ($res,$i,data));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$codigo_fabricacao  = trim(pg_result ($res,$i,codigo_fabricacao));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$consumidor_fone    = trim(pg_result ($res,$i,consumidor_fone));
		$revenda_nome       = trim(pg_result ($res,$i,revenda_nome));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$os_reincidente     = trim(pg_result ($res,$i,os_reincidente));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$total_pecas        = trim(pg_result ($res,$i,total_pecas));
		$total_mo           = trim(pg_result ($res,$i,total_mo));
		$cortesia           = trim(pg_result ($res,$i,cortesia));
		$texto              = "";

		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}else{
			$cor = "#F7F5F0";
			$btn = "amarelo";
		}

		if (strlen($os_reincidente) > 0) {
			$texto = "-R";
			$cor   = "#FFCCCC";
		}

		if ($login_fabrica == 1 && $cortesia == "t") $cor = "#D7FFE1";

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		if ($ja_baixado == false AND $login_fabrica<>20) echo "<TD align='center'><input type='checkbox' name='os[$i]' value='$os'><input type='hidden' name='sua_os[$i]' value='$sua_os'></TD>\n";
		echo "<TD nowrap>";
		if ($login_fabrica <> 1) echo "<a href='os_press.php?os=$os' target='_blank'>";
		else                     echo "<a href=\"javascript:void(0);\" onclick=\"javascript:window.open('detalhe_ordem_servico.php?os=$os','','width=750,height=500,scrollbars=yes');\">";
		if ($login_fabrica == 1) echo $codigo_posto;
		echo $sua_os . $texto . "</a></TD>\n";
		if ($login_fabrica == 1) echo "<TD nowrap>$codigo_fabricacao</TD>\n";
		echo "<TD nowrap>$serie</TD>\n";
		if ($login_fabrica <> 1) echo "<TD align='center'>$abertura</TD>\n";
/*		echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17);
		if ($login_fabrica == 1) echo " - ".$consumidor_fone;
		echo "</ACRONYM></TD>\n";*/
		echo "<TD nowrap><ACRONYM TITLE=\"$produto_referencia - $produto_nome\">";
		if ($login_fabrica == 1) echo $produto_referencia; else echo substr($produto_nome,0,17);
		echo "</ACRONYM></TD>\n";

//Valores na OS
		if ($login_fabrica == 1 or $login_fabrica == 20) {
			$total_os = $total_pecas + $total_mo;
/*			echo "<TD align='left' nowrap><ACRONYM TITLE=\"$revenda_nome\">". substr($revenda_nome,0,17) . "</ACRONYM></TD>\n";*/
			echo "<TD align='right' nowrap>R$ " . number_format($total_pecas,2,",",".") . "</TD>\n";
			echo "<TD align='right' nowrap>R$ " . number_format($total_mo,2,",",".") . "</TD>\n";
			echo "<TD align='right' nowrap>R$ " . number_format($total_os,2,",",".") . "</TD>\n";
		}
//Cores para OS	
		if ($login_fabrica == 20) {
			echo "<TD align='center' nowrap width='30'><img src='imagens_admin/status_verde.gif'></TD>\n";
			echo	"<TD align='right' nowrap><INPUT TYPE='radio' NAME='status_$i' value='t' CHECKED>Aprovada <INPUT TYPE='radio' NAME='status_$i' value='f'>Recusada <INPUT TYPE='radio' NAME='status_$i' value='f'>Acumular</TD>\n";
		}

		echo "</TR>\n";
	}//FIM FOR
	if (strlen($extrato_valor) == 0 AND $ja_baixado == false AND $login_fabrica<>20) {
		if ($login_fabrica == 1) $colspan = 10; else $colspan = 6;
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan='$colspan' align='left'> &nbsp; &nbsp; &nbsp; <img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; COM MARCADOS: &nbsp; ";
		echo "<select name='select_acao' size='1' class='frm'>";
		echo "<option value=''></option>";
		echo "<option value='RECUSAR'";  if ($_POST["select_acao"] == "RECUSAR")  echo " selected"; echo ">RECUSADO PELO FABRICANTE</option>";
		echo "<option value='EXCLUIR'";  if ($_POST["select_acao"] == "EXCLUIR")  echo " selected"; echo ">EXCLUÍDA PELO FABRICANTE</option>";
		echo "<option value='ACUMULAR'"; if ($_POST["select_acao"] == "ACUMULAR") echo " selected"; echo ">ACUMULAR PARA PRÓXIMO EXTRATO</option>";
		echo "</select>";
		echo " &nbsp; <img border='0' src='imagens/btn_continuar.gif' align='absmiddle' onclick='javascript: document.frm_extrato_os.submit()' style='cursor: hand;'>";
		echo "</TD>\n";
		echo "</TR>\n";
	}
	echo "<input type='hidden' name='contador' value='$i'>";
	echo "</TABLE>\n";
}//FIM ELSE

if ($login_fabrica == 1){
	// sem paginacao
}else{
	// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	echo "<div>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	echo "</div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<div>";
		echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //
}

} // Fecha a visualização dos extratos

// ##### EXIBE AS OS QUE SERÃO ACUMULADAS OU RECUSADAS ##### //
if (strlen($select_acao) > 0) {
	$os     = $_POST["os"];
	$sua_os = $_POST["sua_os"];

	echo "<br>\n";
	echo "<HR WIDTH='600' ALIGN='CENTER'>\n";
	echo "<br>\n";
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan='2'>";
	echo "Preencha o campo observação informando o motivo<br>pelo qual será ";
	if (strtoupper($select_acao) == "RECUSAR") echo "RECUSADO PELO FABRICANTE";
	elseif (strtoupper($select_acao) == "EXCLUIR") echo "EXCLUÍDA PELO FABRICANTE";
	elseif (strtoupper($select_acao) == "ACUMULAR") echo "ACUMULAR PARA PRÓXIMO EXTRATO";
	echo "</TD>\n";
	echo "</tr>\n";
	$kk = 0;
	for ($k = 0 ; $k < $contador ; $k++) {
		if ($k == 0) {
			echo "<tr class='menu_top'>\n";
			echo "<td>OS</td>\n";
			echo "<td>OBSERVAÇÃO</td>\n";
			echo "</tr>\n";
		}

		if (strlen($msg_erro) > 0) {
			$os[$k]     = $_POST["os_" . $kk];
			$sua_os[$k] = $_POST["sua_os_" . $kk];
			$obs        = $_POST["obs_" . $kk];
		}

		$cor = ($kk % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

		if ($linha_erro == $kk && strlen($linha_erro) != 0) $cor = "FF0000";

		if (strlen($os[$k]) > 0) {
			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td align='center'>";
			if ($login_fabrica == 1) echo $codigo_posto.$sua_os[$k];
			else                     echo $sua_os[$k];
			echo "<input type='hidden' name='os_$kk' value='" . $os[$k] . "'></td>\n";
			echo "<td align='center'><textarea name='obs_$kk' rows='1' cols='100' class='frm'>$obs</textarea></td>\n";
			echo "</tr>\n";
			$kk++;
		}
	}
	echo "</table>\n";
	echo "<input type='hidden' name='qtde_os' value='$kk'>";
	echo "<br>\n";
	echo "<img border='0' src='imagens/btn_confirmaralteracoes.gif' style='cursor: hand;' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { document.frm_extrato_os.btn_acao.value='$select_acao'; document.frm_extrato_os.submit(); }else{ alert('Aguarde submissão'); }\" alt='Confirmar Alterações'>\n";
	echo "<br>\n";
}

echo "<br>";

##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
if ($login_fabrica == 1) {
	$sql = "SELECT  'OS SEDEX' AS descricao          ,
					tbl_extrato_lancamento.os_sedex  ,
					''      AS historico             ,
					tbl_extrato_lancamento.automatico,
					sum(tbl_extrato_lancamento.valor) as valor
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			GROUP BY tbl_extrato_lancamento.os_sedex, tbl_extrato_lancamento.automatico";

	$sql = "SELECT 'OS SEDEX' AS descricao ,
					tbl_extrato_lancamento.descricao AS descricao_lancamento ,
					tbl_extrato_lancamento.os_sedex ,
					'' AS historico ,
					tbl_extrato_lancamento.historico AS historico_lancamento,
					tbl_extrato_lancamento.automatico,
					sum(tbl_extrato_lancamento.valor) as valor
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			GROUP BY tbl_extrato_lancamento.os_sedex,
						tbl_extrato_lancamento.automatico,
						tbl_extrato_lancamento.descricao,
						tbl_extrato_lancamento.historico;";
//echo $sql;
}else{
	$sql =	"SELECT tbl_lancamento.descricao         ,
					tbl_extrato_lancamento.os_sedex  ,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor     ,
					tbl_extrato_lancamento.automatico
			FROM    tbl_extrato_lancamento
			JOIN    tbl_lancamento USING (lancamento)
			WHERE   tbl_extrato_lancamento.extrato = $extrato
			AND     tbl_lancamento.fabrica         = $login_fabrica
			ORDER BY    tbl_extrato_lancamento.os_sedex,
						tbl_extrato_lancamento.descricao";
}
$res_avulso = pg_exec($con,$sql);

if (pg_numrows($res_avulso) > 0) {
	if ($login_fabrica == 1) $colspan = 5;
	else                     $colspan = 4;
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan='$colspan'>LANÇAMENTO DE EXTRATO AVULSO</td>\n";
	echo "</tr>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td>DESCRIÇÃO</td>\n";
	echo "<td>HISTÓRICO</td>\n";
	echo "<td>VALOR</td>\n";
	echo "<td>AUTOMÁTICO</td>\n";
	if ($login_fabrica == 1) echo "<td>AÇÕES</td>\n";
	echo "</tr>\n";
	for ($j = 0 ; $j < pg_numrows($res_avulso) ; $j++) {
		$cor = ($j % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		$descricao            = pg_result($res_avulso, $j, descricao);
		$historico            = pg_result($res_avulso, $j, historico);
		$os_sedex             = pg_result($res_avulso, $j, os_sedex);

		if ($login_fabrica == 1){
			if (strlen($os_sedex) == 0){
				$descricao = @pg_result($res_avulso, $j, descricao_lancamento);
				$historico = @pg_result($res_avulso, $j, historico_lancamento);
			}
		}
		echo "<tr height='18' class='table_line' style='background-color: $cor;'>\n";
		echo "<td width='35%'>" . $descricao . "</td>";
		echo "<td width='35%'>" . $historico . "</td>";
		echo "<td width='10%' align='right' nowrap> R$ " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "</td>";

		echo "<td width='10%' align='center' nowrap>" ;
		if (pg_result($res_avulso, $j, automatico) == 't') {
			echo "S";
		}else{
			echo "&nbsp;";
		}
		echo "</td>";
		echo "<td width='10%' align='center' nowrap>";
		if ($login_fabrica == 1 AND strlen($os_sedex) > 0) echo "<a href='sedex_finalizada.php?os_sedex=" . $os_sedex . "' target='_blank'><img border='0' src='imagens/btn_consulta.gif' style='cursor: hand;' alt='Consultar OS Sedex'>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>\n";
	echo "<br>\n";
}
##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

##### VERIFICA BAIXA MANUAL #####
$sql = "SELECT posicao_pagamento_extrato_automatico FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
$res = pg_exec($con,$sql);
$posicao_pagamento_extrato_automatico = pg_result($res,0,posicao_pagamento_extrato_automatico);

if ($posicao_pagamento_extrato_automatico == 'f' and $login_fabrica <> 1) {
?>

<HR WIDTH='600' ALIGN='CENTER'>

<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0'>
<TR>
	<TD height='20' class="menu_top2" colspan='4'>PAGAMENTO</TD>
</TR>
<TR>
	<TD align='left' class="menu_top2"><center>VALOR TOTAL (R$)</center></TD>
	<TD align='left' class="menu_top2"><center>ACRÉSCIMO (R$)</center></TD>
	<TD align='left' class="menu_top2"><center>DESCONTO (R$)</center></TD>
	<TD align='left' class="menu_top2"><center>VALOR LÍQUIDO (R$)</center></TD>
</TR>

<TR>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_total'  size='10' maxlength='10' value='" . $valor_total . "' style='text-align:right' class='frm'>";
	else                      echo number_format($valor_total,2,',','.');
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='acrescimo'  size='10' maxlength='10' value='" . $acrescimo . "' style='text-align:right' class='frm'>";
	else                      echo number_format($acrescimo,2,',','.');
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='desconto'  size='10' maxlength='10' value='" . $desconto . "' style='text-align:right' class='frm'>";
	else                      echo number_format($desconto,2,',','.');
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='valor_liquido'  size='10' maxlength='10' value='" . $valor_liquido . "' style='text-align:right' class='frm'>";
	else                      echo number_format($valor_liquido,2,',','.');
?>
	</TD>
</TR>

<TR>
	<TD align='left' class="menu_top2"><center>DATA DE VENCIMENTO</center></TD>
	<TD align='left' class="menu_top2"><center>Nº NOTA FISCAL</center></TD>
	<TD align='left' class="menu_top2"><center>DATA DE PAGAMENTO</center></TD>
	<TD align='left' class="menu_top2"><center>AUTORIZAÇÃO Nº</center></TD>
</TR>

<TR>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_vencimento'  size='10' maxlength='10' value='" . $data_vencimento . "' class='frm'>";
	else                      echo $data_vencimento;
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='nf_autorizacao'  size='10' maxlength='20' value='" . $nf_autorizacao . "' class='frm'>";
	else                      echo $nf_autorizacao;
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='data_pagamento'  size='10' maxlength='10' value='" . $data_pagamento . "' class='frm'>";
	else                      echo $data_pagamento;
?>
	</TD>
	<TD align='center' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='autorizacao_pagto' size='10' maxlength='20' value='" . $autorizacao_pagto . "' class='frm'>";
	else                      echo $autorizacao_pagto;
?>
	</TD>
</TR>

<TR>
	<TD align='left' class="menu_top2" colspan='4'><center>OBSERVAÇÃO</center></TD>
</TR>
<TR>
	<TD align='center' colspan='4' class='table_line2'>
<?
	if ($ja_baixado == false) echo "<INPUT TYPE='text' NAME='obs'  size='96' maxlength='255' value='" . $obs . "' class='frm'>";
	else                      echo $obs;
?>
	</TD>
</TR>
</TABLE>

<BR>

<?
if ($ja_baixado == false){
	echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
	echo "<input type='hidden' name='data_final' value='$data_final'>";
	echo "<input type='hidden' name='cnpj' value='$cnpj'>";
	echo "<input type='hidden' name='razao' value='$razao'>";
	echo"<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
	echo"<TR>";
	echo"	<TD ALIGN='center'><img src='imagens/btn_baixar.gif' onclick=\"javascript: if (document.frm_extrato_os.btn_acao.value == '' ) { document.frm_extrato_os.btn_acao.value='baixar' ; document.frm_extrato_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Baixar' border='0' style='cursor:pointer;'></TD>";
	echo"</TR>";
	echo"</TABLE>";
}

} // fecha verificação se fábrica usa baixa manual

?>
</FORM>
<br>

<center>
<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;'>
<? if ($login_fabrica == 1) { ?>
<img src='imagens/btn_imprimirsimplificado_15.gif' onclick="javascript: window.open('os_extrato_print_blackedecker.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Simplificado' border='0' style='cursor:pointer;'>
<img src='imagens/btn_imprimirdetalhado_15.gif' onclick="javascript: window.open('os_extrato_detalhe_print_blackedecker.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=yes,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir Detalhado' border='0' style='cursor:pointer;'>
<? } ?>
<br><br>
<img border='0' src='imagens/btn_voltar.gif' onclick="javascript: history.back(-1);" alt='Voltar' style='cursor: hand;'>
</center>

<? include "rodape.php"; ?>
