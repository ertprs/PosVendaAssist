<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "financeiro";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {

	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

//include "gera_relatorio_pararelo_include.php";

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);

if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);

if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
if (strlen(trim($_GET["data_final"])) > 0)  $data_final = trim($_GET["data_final"]);

if (strlen(trim($_POST["agrupar"])) > 0) $agrupar = trim($_POST["agrupar"]);
if (strlen(trim($_GET["agrupar"])) > 0)  $agrupar = trim($_GET["agrupar"]);

if (strlen(trim($_POST["nota_sem_baixa"])) > 0) $nota_sem_baixa = trim($_POST["nota_sem_baixa"]);
if (strlen(trim($_GET["nota_sem_baixa"])) > 0)  $nota_sem_baixa = trim($_GET["nota_sem_baixa"]);

if (strlen(trim($_POST["nota_com_baixa"])) > 0) $nota_com_baixa = trim($_POST["nota_com_baixa"]);
if (strlen(trim($_GET["nota_com_baixa"])) > 0)  $nota_com_baixa = trim($_GET["nota_com_baixa"]);

if (strlen($btn_acao) > 0) {

	if (strlen($posto_codigo) > 0) {
		$cond1 = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
	}
	
	if($data_inicial){
		$dat = explode ("/", $data_inicial);
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if( (!checkdate($m,$d,$y)) ){
			$msg_erro ="Data Inválida.";
		}
	}
	
	if($data_final){
		$dat = explode ("/", $data_final);
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if( (!checkdate($m,$d,$y)) ){
			$msg_erro ="Data Inválida.";
		}
	}

	else{
		$msg_erro="Data Inválida.";
	}

	if(strlen($msg_erro)==0){
		$data_inicial = str_replace (" " , "" , $data_inicial);
		$data_inicial = str_replace ("-" , "" , $data_inicial);
		$data_inicial = str_replace ("/" , "" , $data_inicial);
		$data_inicial = str_replace ("." , "" , $data_inicial);

		$data_final   = str_replace (" " , "" , $data_final)  ;
		$data_final   = str_replace ("-" , "" , $data_final)  ;
		$data_final   = str_replace ("/" , "" , $data_final)  ;
		$data_final   = str_replace ("." , "" , $data_final)  ;
		
		if($data_inicial > $data_final){
			$msg_erro = "Data Inválida.";
		}
		if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
		if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

		if (strlen ($data_inicial) > 0)  $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
		if (strlen ($data_final)   > 0)  $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);
	}
}

$layout_menu = "financeiro";
$title = "RELATÓRIO DE VALORES DE EXTRATOS";

include 'cabecalho.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Titulo2 {
	text-align: center;
	font-family: Arial;
	font-size: 11px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #CC0033;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF
}

	.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
</style>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});


</script>
<script language="JavaScript">
/* ============= Função PESQUISA DE POSTOS ====================
Nome da Função : fnc_pesquisa_posto (cnpj,nome)
		Abre janela com resultado da pesquisa de Postos pela
		Código ou CNPJ (cnpj) ou Razão Social (nome).
=================================================================*/
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}

}

function SomenteNumero(event){

	var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

	if (tecla == 9) {
		$("#posto_nome").focus();
	}	

    if((tecla > 47 && tecla < 58)) return true;
    else{
		if (tecla != 8) return false;
		else return true;
    }
}

</script>

<?php

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	//include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro) == 0) {
//	include "gera_relatorio_pararelo_verifica.php";
}

?>

<? if (strlen($msg_erro) > 0) { ?>
<table width="700px" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>

<? } ?>

<!-- FORMULÁRIO DE PESQUISA -->
<FORM name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
<table width='700px' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>

	<tr class='titulo_tabela'>
		<td >Parâmetros de Pesquisa</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td>

			<table width='700px' border='0' cellspacing='1' cellpadding='2' class='formulario'>

					<tr class='table_line'>
					<td width="50">&nbsp;</td>
					<td align='left'>Data Inicial</td>
					<td align='left' width='130' nowrap>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>">
					</td>
					<td align='right' nowrap>Data Final</td>
					<td align='left' nowrap>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;?>" >
					</td>
				</tr>
				<tr class="table_line">
					<td width="10">&nbsp;</td>
					
					<td align='left' nowrap>Código do Posto</td>
					<td nowrap><input class="frm" type="text" name="posto_codigo" id="posto_codigo" size="10" value="<? echo $posto_codigo ?>" onkeypress='return SomenteNumero(event);' >&nbsp;<img src="imagens/lupa.png"
					border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2
					(document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>
					<td align='right' nowrap>Nome do Posto</td>
					<td align='left' nowrap><input class="frm" type="text" name="posto_nome" id="posto_nome" size="30" value="<? echo $posto_nome ?>">&nbsp;<img src="imagens/lupa.png" style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></a>
					</td>
				</tr>
				<tr class='table_line'>
				<td width="10">&nbsp;</td>
					<td align='left' nowrap colspan='4'>
						 Agrupar por Posto 
					
						<INPUT TYPE="checkbox" NAME="agrupar" value='sim' <?if(strlen($agrupar)>0)echo "CHECKED"?>>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						Aprovados para Pagamento
						<INPUT TYPE="checkbox" NAME="pago" value='sim' <?if(strlen($pago)>0)echo "CHECKED"?>> 
						<? if ($login_fabrica == 45) {?>
					</td>
					
					<tr>
						<td>
						Extratos com Nota Fiscal sem baixa  <INPUT TYPE="checkbox" NAME="nota_sem_baixa" value='sim' <?if(strlen($nota_sem_baixa)>0)echo "CHECKED"?>>  <br>
						Extratos com Nota Fiscal baixado <INPUT TYPE="checkbox" NAME="nota_com_baixa" value='sim' <?if(strlen($nota_com_baixa)>0)echo "CHECKED"?>> <font size='2'>
						<? } ?>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td colspan='5' align='center'>
					<br />
						<input type='submit' name='enviar' value='Consultar'>
						<input type='hidden' name='btn_acao' value='consultar'></center>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
</table>

</form>

<?php
//--=== RESULTADO DA PESQUISA ====================================================--\\
flush();

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
	
	/*nao agrupado  takashi 21-12 HD 916*/
	if (strlen($data_inicial) > 0 AND strlen($data_final) > 0 AND $agrupar <> 'sim') {

		if (strlen ($data_inicial) < 8)
			$data_inicial = date ("d/m/Y");
		
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

		if (strlen ($data_final) < 8)
			$data_final = date ("d/m/Y");
		
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

		$sql = "SELECT  tbl_posto.nome                                                      ,
						tbl_posto_fabrica.contato_estado                                                    ,
						tbl_posto_fabrica.codigo_posto                                      ,";
		
		//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
		if ($login_fabrica == 45 || $login_fabrica == 40) {
			$sql .= "tbl_posto.cnpj                                                      ,
					 tbl_posto_fabrica.banco                                             ,
					 tbl_posto_fabrica.agencia                                           ,
					 tbl_posto_fabrica.conta                                             ,
					 tbl_posto_fabrica.favorecido_conta                                  ,
					 tbl_posto_fabrica.conta_operacao                                    ,";
		}
		
		//HD-15422
		if ($login_fabrica == 20) {
			$sql.= "tbl_escritorio_regional.descricao        as escritorio_regional,";
		}

		$sql.=  "tbl_posto_fabrica.reembolso_peca_estoque                            ,
				TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY')    AS data_geracao   ,
				tbl_extrato.extrato                                                 ,
				tbl_extrato.protocolo                                               ,
				tbl_extrato.mao_de_obra                                             ,
				tbl_extrato.pecas                                                   ,
				tbl_extrato.avulso                                                  ,
				tbl_extrato.total                                                   ,
				( 0
				)                                                 AS total_os
			INTO TEMP tmp_extrato_pagamento /* hd 39502 */
			FROM tbl_extrato
			JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
			JOIN tbl_posto         ON tbl_posto.posto           = tbl_extrato.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_extrato.posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						$cond1 ";
				
		//HD-15422
		if ($login_fabrica == 20) {
			$sql .= "LEFT JOIN tbl_escritorio_regional using (escritorio_regional)    ";
		}

		if ($login_fabrica == 45) {
			$sql .= "LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato    ";
		}

		$sql .=  "WHERE tbl_extrato.fabrica = $login_fabrica ";

		if (strlen($pago) > 0)
			$sql .= " AND tbl_extrato.aprovado IS NOT NULL";
		
		if (strlen ($data_inicial) < 8)
			$data_inicial = date ("d/m/Y");
		
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

		if (strlen ($data_final) < 8)
			$data_final = date ("d/m/Y");
		
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

		if ($login_fabrica == 45) {

			if ($nota_sem_baixa=='sim') {
				$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL
						  AND tbl_extrato_pagamento.data_pagamento IS NULL    ";
			} elseif ($nota_com_baixa=='sim') {
				$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL
						  AND tbl_extrato_pagamento.data_pagamento IS NOT NULL    ";
			} else {
				$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL    ";
				$sql .= " AND tbl_extrato_pagamento.data_pagamento IS NULL    ";
			}

		}

		if ($login_fabrica <> 20) {
			if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
				$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
		} else {
			if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
				$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
		}

		$sql .= " ORDER BY tbl_posto.nome";
		@$res = pg_exec($con, $sql);

		/* hd 39502 */
		if ($login_fabrica == 20) {
			$sql = "ALTER table tmp_extrato_pagamento add column total_cortesia double precision";
			@$res = pg_exec ($con,$sql);

			$sql = "UPDATE tmp_extrato_pagamento SET
						total_cortesia = (
							SELECT sum(tbl_os.mao_de_obra) + sum(tbl_os.pecas)
							FROM tbl_os
							JOIN tbl_os_extra USING(os)
							WHERE extrato = tmp_extrato_pagamento.extrato
							AND   tbl_os.tipo_atendimento = 16
						)";
			@$res = pg_exec ($con,$sql);
		}

		$sql = "SELECT * FROM tmp_extrato_pagamento";
		$res = pg_exec ($con,$sql);

		/*SELECT DO TOTAL GERAL DOS EXTRATOS 21/12/2007 HD 9983
		****************************************/
		if ($login_fabrica == 5) {
			
			$sqlx .= "SELECT
					SUM(tbl_extrato.total) AS total_geral
				FROM tbl_extrato
				JOIN tbl_extrato_extra
				ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_extrato.fabrica = $login_fabrica";

			if (strlen ($data_inicial) < 8)
				$data_inicial = date ("d/m/Y");
				
			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

			if (strlen ($data_final) < 8)
				$data_final = date ("d/m/Y");
			
			$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

			if ($login_fabrica <> 20) {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sqlx .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			} else {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sqlx .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			}
			echo "<br>".nl2br($sqlx);
			$samuel = pg_query($con,"/* QUERY -> $sqlx */");
			$resx = pg_exec($con, $sqlx);

			if (pg_numrows($resx) > 0) {
				$total_geral = trim(pg_result($resx,0,total_geral));
				$total_geral = number_format($total_geral,2,",",".");
			}

		}
		/****************************************/

		if (pg_numrows($res) > 0) {

			$data = date ("dmY");

			echo "<p id='id_download' style='display:none'><img src='imagens/excell.gif'> <a href='xls/relatorio_pagamento_posto-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o download </font> do arquivo em EXCEL</a></p>";

	/*		echo "<br><center><a href='extrato_pagamento.php?agrupar=sim&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final'><font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>Agrupar por posto</font></a></center>";
	*/		
			echo "<table border='0' cellpadding='2' cellspacing='0' class='formulario' align='center' width='700px'>";
			//HD 9983 26/12/2007
			if ($login_fabrica == 5) {
				echo "<tr class='titulo_coluna' align='center'>";
				echo "<td colspan='8' align='right'>Valor Total</td>";
				echo "<td colspan='2'>$total_geral</td>";
				echo "</tr>";
			}
				
			echo "<tr class='titulo_coluna'>";
			echo "<td >Código</td>";
			echo "<td nowrap>Nome</td>";
			
			//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
			if ($login_fabrica == 45 || $login_fabrica == 40) {
				echo "<td >CNPJ</td>";
				echo "<td >Banco</td>";
				echo "<td >Agência</td>";
				echo "<td >Conta</td>";
				echo "<td >Favorecido</td>";
				echo "<td >Operação</td>";
			}

			//HD-15422
			echo ($login_fabrica == 20 ) ? "<td>ER</td>" : "<td >UF</td>";
			echo ($login_fabrica == 1 ) ? "<td>Pedido<BR>Garantia</td>" : "";
			echo "<td >Extrato</td>";
			echo "<td >Geração</td>";
			echo "<td nowrap>M.O</td>";
			echo "<td nowrap>Peças</td>";
			echo "<td nowrap>Avulso</td>";
			
			//hd 39502
			if ($login_fabrica==20) {
				echo "<td nowrap>TOTAL Cortesia</td>";
				echo "<td nowrap>Total Geral</td>";
			} else {
				echo "<td nowrap>Total</td>";
			}
			
			echo "<td >Total<br>OS</td>";
			echo "</tr>";
			flush();

			echo `rm /tmp/assist/relatorio_pagamento_posto-$login_fabrica.xls`;

			$fp = fopen ("/tmp/assist/relatorio_pagamento_posto-$login_fabrica.html","w");

			fputs($fp,"<html>");
			fputs($fp,"<head>");
			fputs($fp,"<title>RELATÓRIO DE VALORES DE EXTRATOS - $data");
			fputs($fp,"</title>");
			fputs($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs($fp,"</head>");
			fputs($fp,"<body>");

			fputs($fp,"<br><table border='0' cellpadding='2' cellspacing='0'class='formulario' width='700px' align='center'>");
			fputs($fp,"<tr class='titulo_coluna'>");
			fputs($fp,"<td >Código</td>");
			fputs($fp,"<td >Nome Posto</td>");
			
			//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
			if ($login_fabrica == 45 || $login_fabrica == 40) {
				fputs($fp,"<td >CNPJ</td>");
				fputs($fp,"<td >Banco</td>");
				fputs($fp,"<td >Agência</td>");
				fputs($fp,"<td >Conta</td>");
				fputs($fp,"<td >Favorecido</td>");
				fputs($fp,"<td >Operação</td>");
			}

			//HD-15422
			if ($login_fabrica == 20) {
				fputs($fp,"<td >ER</td>");
			} else {
				fputs($fp,"<td >UF</td>");
			}

			if($login_fabrica == 1 ) fputs($fp,"<td>Pedido<BR>Garantia</td>");
			fputs($fp,"<td>Extrato</td>");
			fputs($fp,"<td>Geração</td>");
			fputs($fp,"<td nowrap>M.O</td>");
			fputs($fp,"<td nowrap>Peças</td>");
			fputs($fp,"<td nowrap>Avulso</td>");
			
			//hd 39502
			if ($login_fabrica==20) {
				fputs($fp,"<td nowrap>Total Cortesia</td>");
				fputs($fp,"<td nowrap>Total Geral</td>");
			} else {
				fputs($fp,"<td nowrap>Total</td>");
			}

			fputs($fp,"<td >Total<br />OS</td>");
			fputs($fp,"</tr>");

			for ($i = 0; $i < pg_numrows($res); $i++) {

				$nome                    = trim(pg_result($res,$i,nome))          ;
				
				//HD-15422
				if ($login_fabrica == 20) {
					$escritorio_regional = trim(pg_result($res,$i,escritorio_regional));
				}

				$estado                  = trim(pg_result($res,$i,contato_estado))        ;
				$codigo_posto            = trim(pg_result($res,$i,codigo_posto))  ;
				
				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					$cnpj = trim(pg_result($res,$i,cnpj));
					if (strlen($cnpj) == 14)
						$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
					if (strlen($cnpj) == 11)
						$cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
					$banco            = trim(pg_result($res,$i,banco))            ;
					$agencia          = trim(pg_result($res,$i,agencia))          ;
					$conta            = trim(pg_result($res,$i,conta))           ;
					$favorecido_conta = trim(pg_result($res,$i,favorecido_conta)) ;
					$conta_operacao   = trim(pg_result($res,$i,conta_operacao))   ;
				}

				$extrato                 = trim(pg_result($res,$i,extrato))       ;
				$protocolo               = trim(pg_result($res,$i,protocolo))     ;
				$data_geracao            = trim(pg_result($res,$i,data_geracao))  ;
				$mao_de_obra             = trim(pg_result($res,$i,mao_de_obra))   ;
				$pecas                   = trim(pg_result($res,$i,pecas))         ;
				$avulso                  = trim(pg_result($res,$i,avulso))        ;
				
				if ($login_fabrica == 20) {
					$total_cortesia = trim(pg_result($res,$i,total_cortesia));
				}

				$total              = trim(pg_result($res,$i,total))         ;
				$total_os           = trim(pg_result($res,$i,total_os))      ;
				$pedido_em_garantia = trim(pg_result($res,$i,reembolso_peca_estoque))      ;

				$pedido_em_garantia = ($pedido_em_garantia=='t') ? "Sim" : "Não";

				$cor = ( $i%2 ) ? '#F7F5F0' : '#F1F4FA';

				$sql1 = "SELECT count(*) AS total_os
						 FROM tbl_os_extra
						 WHERE tbl_os_extra.extrato = $extrato";
				$res1 = pg_exec($con, $sql1);
				$total_os    = trim(pg_result($res1,0,total_os));
				$pecas       = number_format($pecas,2,",",".");
				$mao_de_obra = number_format($mao_de_obra,2,",",".");
				$avulso      = number_format($avulso,2,",",".");
				$total       = number_format($total,2,",",".");
				if ($login_fabrica==20) {
					$total_cortesia = number_format($total_cortesia,2,",",".")      ;
				}

				echo "<tr align='center'>";
				echo "<td bgcolor='$cor'>$codigo_posto</td>";
				echo "<td bgcolor='$cor' align='left' title='$nome'>".substr($nome,0,20)."</td>";
				
				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					echo "<td bgcolor='$cor'>$cnpj</td>";
					echo "<td bgcolor='$cor'>$banco</td>";
					echo "<td bgcolor='$cor'>$agencia</td>";
					echo "<td bgcolor='$cor'>$conta</td>";
					echo "<td bgcolor='$cor'>$favorecido_conta</td>";
					echo "<td bgcolor='$cor'>$conta_operacao</td>";
				}

				//HD-15422
				echo "<td bgcolor='$cor'>";
				echo ($login_fabrica == 20) ? $escritorio_regional : $estado;
				echo "</td>";
				echo ($login_fabrica == 1) ? "<td bgcolor='$cor'>$pedido_em_garantia</td>" : "";
				echo "<td bgcolor='$cor'>";
				echo ($login_fabrica == 1) ? $protocolo : $extrato;
				echo "</td>";
				echo "<td bgcolor='$cor'>$data_geracao</td>";
				echo "<td bgcolor='$cor' align='right'>R$ $mao_de_obra</td>";
				echo "<td bgcolor='$cor' align='right'>R$ $pecas</td>";
				echo "<td bgcolor='$cor' align='right'>R$ $avulso</td>";
				
				//hd 39502
				echo ($login_fabrica==20) ? "<td bgcolor='$cor' align='right'>R$ $total_cortesia</td>" : "";
				echo "<td bgcolor='$cor' align='right'>R$ $total</td>";
				echo "<td bgcolor='$cor' align='center'>$total_os</td>";
				echo "</tr>";

				$total_mo   += $mao_de_obra;
				$total_todo += $total;

				fputs($fp,"<tr class='Conteudo'>");
				fputs($fp,"<td bgcolor='$cor' >$codigo_posto</td>");
				fputs($fp,"<td bgcolor='$cor' align='left' title='nome' nowrap>".substr($nome,0,20)."</td>");
				
				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					fputs($fp,"<td bgcolor='$cor'>$cnpj</td>");
					fputs($fp,"<td bgcolor='$cor'>$banco</td>");
					fputs($fp,"<td bgcolor='$cor'>$agencia</td>");
					fputs($fp,"<td bgcolor='$cor'>$conta</td>");
					fputs($fp,"<td bgcolor='$cor'>$favorecido_conta</td>");
					fputs($fp,"<td bgcolor='$cor'>$conta_operacao</td>");
				}

				//HD-15422
				if ($login_fabrica == 20) {
					fputs($fp,"<td bgcolor='$cor'>$escritorio_regional</td>");
				} else {
					fputs($fp,"<td bgcolor='$cor'>$estado</td>");
				}
				if($login_fabrica == 1 ) fputs($fp,"<td bgcolor='$cor' >$pedido_em_garantia</td>");
				fputs($fp,"<td bgcolor='$cor'>");
				if ($login_fabrica == 1) fputs($fp,$protocolo);
				else                     fputs($fp,$extrato);
				fputs($fp,"</td>");

				fputs($fp,"<td bgcolor='$cor'>$data_geracao</td>");
				fputs($fp,"<td bgcolor='$cor' align='right' nowrap>R$ $mao_de_obra</td>");
				fputs($fp,"<td bgcolor='$cor' align='right' nowrap>R$ $pecas</td>");
				fputs($fp,"<td bgcolor='$cor' align='right' nowrap>R$ $avulso</td>");
				//hd 39502
				if ($login_fabrica==20) {
					fputs($fp,"<td bgcolor='$cor' align='right' nowrap>R$ $total_cortesia</td>");
				}
				fputs($fp,"<td bgcolor='$cor' align='right' nowrap>R$ $total</td>");
				fputs($fp,"<td bgcolor='$cor' align='center'>$total_os</td>");
				fputs($fp,"</tr>");
				flush();

			}

			if ($login_fabrica == 50) { // HD 49535
				echo "<tfoot>";
				echo "<tr><td colspan='5' class='titulo_coluna' align='center'>Total</td>";
				echo "<td align='right' nowrap>R$ ".number_format($total_mo,2,",",".")."</td>";
				echo "<td colspan='2'>&nbsp;</td>";
				echo "<td align='right' nowrap>R$ ".number_format($total_todo,2,",",".")."</td>";
				echo "<td>&nbsp;</td>";
				echo "</tr>";
				echo "</tfoot>";
			}

			echo "</table>";

			fputs($fp,"</table>");
			fputs($fp,"</body>");
			fputs($fp,"</html>");
			fclose ($fp);

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_pagamento_posto-$login_fabrica.$data.xls /tmp/assist/relatorio_pagamento_posto-$login_fabrica.html`;

			echo "<script language='javascript'>";
			echo "document.getElementById('id_download').style.display='block';";
			echo "</script>";

		}  else {
			echo "<br><br><p>Nenhum resultado encontrado!</p>";
		}

	}
	/*nao agrupado  takashi 21-12 HD 916*/

	/*agrupado takashi 21-12 HD 916*/
	if (strlen($data_inicial) > 0 AND strlen($data_final) > 0 AND $agrupar == 'sim') {

	$sql = "SELECT 	X.id                       ,
					X.posto                    ,
					X.nome                     ,";
			
			//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
			if ($login_fabrica == 45 || $login_fabrica == 40) {
				$sql .= "X.cnpj,
						 X.banco                    ,
						 X.agencia                  ,
						 X.conta                    ,
						 X.favorecido_conta         ,
						 X.conta_operacao           ,";
			}

			//HD-15422
			$sql .= ($login_fabrica == 20) ? "X.escritorio_regional           ," : "X.contato_estado                   ,";
			
			$sql.= "X.tipo_posto               ,
					X.reembolso_peca_estoque       ,
					sum(X.mao_de_obra) as mao  ,
					sum(X.pecas) as pecas      ,
					sum(X.avulso) as avulso    ,
					sum(X.total) as total      ,
					sum(X.total_os) as total_os,
					sum(X.total_cortesia) as total_cortesia
				FROM (
						SELECT  tbl_posto.posto                           AS id  ,
								tbl_posto_fabrica.codigo_posto as posto          ,
								tbl_posto_fabrica.reembolso_peca_estoque         ,
								tbl_posto.nome                            AS nome,";
			//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
			if ($login_fabrica == 45 || $login_fabrica == 40) {
				$sql .= "		tbl_posto.cnpj                                   ,
								tbl_posto_fabrica.banco                          ,
								tbl_posto_fabrica.agencia                        ,
								tbl_posto_fabrica.conta                          ,
								tbl_posto_fabrica.favorecido_conta               ,
								tbl_posto_fabrica.conta_operacao                 ,";}
			//HD-15422
			if ($login_fabrica == 20) {
				$sql.= "		tbl_escritorio_regional.descricao as escritorio_regional,";
			} else {
				$sql.= "		tbl_posto_fabrica.contato_estado                        ,";
			}
			$sql.= "			tbl_tipo_posto.descricao as tipo_posto          ,
								tbl_extrato.mao_de_obra as mao_de_obra,
								tbl_extrato.pecas as pecas,
								tbl_extrato.avulso as avulso,
								tbl_extrato.total as total,
								(0) as total_os,
								(SELECT sum(tbl_os.mao_de_obra) + sum(tbl_os.pecas) FROM tbl_os JOIN tbl_os_extra USING(os) WHERE extrato = tbl_extrato.extrato AND tbl_os.tipo_atendimento = 16) as total_cortesia /*hd 39502*/
					FROM tbl_extrato
					JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
					JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					$cond1
					JOIN tbl_tipo_posto on tbl_posto_fabrica.tipo_posto=tbl_tipo_posto.tipo_posto ";
			
			//HD-15422
			if ($login_fabrica == 20) {
				$sql.= " LEFT JOIN tbl_escritorio_regional using (escritorio_regional) ";
			}

			if ($login_fabrica == 45) {
				$sql.= " LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato ";
			}

			$sql.= "WHERE tbl_extrato.fabrica = $login_fabrica ";

		if ($login_fabrica == 45) {
			
			if ($nota_sem_baixa == 'sim') {
				$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL
						  AND tbl_extrato_pagamento.data_pagamento IS NULL    ";
			} elseif ($nota_com_baixa == 'sim') {
				$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL
						  AND tbl_extrato_pagamento.data_pagamento IS NOT NULL    ";
			} else {
				$sql .= " AND tbl_extrato_pagamento.nf_autorizacao IS NOT NULL ";
			}

		}

		if (strlen ($data_inicial) < 8)
			$data_inicial = date ("d/m/Y");
		
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

		if (strlen ($data_final) < 8)
			$data_final = date ("d/m/Y");
		
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

		if ($login_fabrica <> 20) {
			if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
				$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' order by tbl_posto.nome ) as X";
		} else {
			if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
				$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59' order by tbl_posto.nome ) as X";
		}

		$sql .= " GROUP BY id,posto, nome,";
		
		//HD-15422
		$sql.= ($login_fabrica == 20) ? " escritorio_regional," : " contato_estado,";

		//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
		if ($login_fabrica == 45 || $login_fabrica == 40) {
			$sql .= "cnpj,
					banco,
					agencia,
					conta,
					favorecido_conta,
					conta_operacao,";
		}

		$sql.= "tipo_posto,
				reembolso_peca_estoque
				order by nome";

		$res = pg_exec($con, $sql);

		/*SELECT DO TOTAL GERAL DOS EXTRATOS 26/12/2007 HD 9983
		****************************************/
		if ($login_fabrica == 5) {

			$sqlx .= "SELECT
					SUM(tbl_extrato.total) AS total_geral
				FROM tbl_extrato
				JOIN tbl_extrato_extra
				ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_extrato.fabrica = $login_fabrica";

			if (strlen ($data_inicial) < 8)
				$data_inicial = date ("d/m/Y");
			
			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

			if (strlen ($data_final) < 8)
				$data_final = date ("d/m/Y");
			
			$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

			if ($login_fabrica <> 20) {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sqlx .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			} else {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sqlx .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			}
			$resx = pg_exec($con, $sqlx);

			if (pg_numrows($resx) > 0) {
				$total_geral = trim(pg_result($resx,0,total_geral));
				$total_geral = number_format($total_geral,2,",",".");
			}

		}
		/****************************************/
		if (pg_numrows($res) > 0) {

			$data = date ("dmY");

			echo "<p id='id_download' style='display:none'><img src='imagens/excell.gif'> <a href='xls/relatorio_pagamento_posto_agrupado-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o download </font> do arquivo em EXCEL</a></p>";

			echo "<br><center><a href='extrato_pagamento.php?btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final'><font face='Arial, Verdana, Times, Sans' size='1' color='#000000'>Desagrupar</font></a></center><table border='0' cellpadding='2' cellspacing='0' class='formulario' align='center' width='700px'>";
			
			//HD 9983 26/12/2007
			if ($login_fabrica == 5) {
				echo "<tr class='titulo_coluna'>";
				echo "<td colspan='8' align='right'>Valor Total</td>";
				echo "<td colspan='2'>$total_geral</td>";
				echo "</tr>";
			}

			echo "<tr class='titulo_coluna'>";
			echo "<td >Código</td>";
			echo "<td >Nome Posto</td>";
			
			//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
			if ($login_fabrica == 45 || $login_fabrica == 40) {
				echo "<td >CNPJ</td>";
				echo "<td >Banco</td>";
				echo "<td >Agência</td>";
				echo "<td >Conta</td>";
				echo "<td >Favorecido</td>";
				echo "<td >Operação</td>";
			}
			
			//HD 15422 - Jean
			echo ($login_fabrica == 20) ? "<td>ER</td>" : "<td>UF</td>";
			echo "<td >Tipo Posto</td>";
			if ($login_fabrica == 1) echo "<td>Pedido Garantia</td>";
			echo "<td >M.O</td>";
			echo "<td >Peças</td>";
			echo "<td >Avulso</td>";
			
			//hd 39502
			if ($login_fabrica == 20) {
				echo "<td >Total Cortesia</td>";
				echo "<td >Total Geral</td>";
			} else {
				echo "<td >Total</td>";
			}

			echo "<td >Total<br>OS</td>";
			echo "</tr>";

			echo `rm /tmp/assist/relatorio_pagamento_posto_agrupado-$login_fabrica.xls`;

			$fp = fopen ("/tmp/assist/relatorio_pagamento_posto_agrupado-$login_fabrica.html","w");

			fputs($fp,"<html>");
			fputs($fp,"<head>");
			fputs($fp,"<title>RELATÓRIO DE VALORES DE EXTRATOS - $data");
			fputs($fp,"</title>");
			fputs($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs($fp,"</head>");
			fputs($fp,"<body>");

			fputs($fp,"<br><table border='1' cellpadding='2' width='700px' cellspacing='0' class='formulario' align='center'>");
			fputs($fp,"<tr class='titulo_coluna'>");
			fputs($fp,"<td >Código</td>");
			fputs($fp,"<td >Nome Posto</td>");
			
			//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
			if ($login_fabrica == 45 || $login_fabrica == 40) {
				fputs($fp,"<td >CNPJ</td>");
				fputs($fp,"<td >Banco</td>");
				fputs($fp,"<td >Agência</td>");
				fputs($fp,"<td >Conta</td>");
				fputs($fp,"<td >Favorecido</td>");
				fputs($fp,"<td >Operação</td>");
			}
			
			//HD 15422 - Jean
			if($login_fabrica == 20){
				fputs($fp,"<td>ER</td>");
			} else {
				fputs($fp,"<td>UF</td>");
			}

			fputs($fp,"<td >Tipo Posto</td>");
			if($login_fabrica == 1) fputs($fp,"<td >Pedido<BR>Garantia</td>");
			fputs($fp,"<td nowrap>M.O</td>");
			fputs($fp,"<td nowrap>Peças</td>");
			fputs($fp,"<td nowrap>Avulso</td>");
			
			//hd 39502
			if ($login_fabrica==20) {
				fputs($fp,"<td nowrap>Total Cortesia</td>");
				fputs($fp,"<td nowrap>Total Geral</td>");
			} else {
				fputs($fp,"<td nowrap>Total</td>");
			}

			fputs($fp,"<td >Total<br>OS</td>");
			fputs($fp,"</tr>");

			if ($login_fabrica <> 20) {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sql_data .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			} else {
				if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
					$sql_data .= " AND tbl_extrato_extra.exportado BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			}

			for ($i = 0; $i < pg_numrows($res); $i++) {
				
				$id   = trim(pg_result($res,$i,id));
				$nome = trim(pg_result($res,$i,nome));
				
				if ($login_fabrica == 20) {
					$escritorio_regional = trim(pg_result($res,$i,escritorio_regional));
				} else {
					$estado = trim(pg_result($res,$i,contato_estado));
				}

				$codigo_posto = trim(pg_result($res,$i,posto));
				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					
					$cnpj = trim(pg_result($res,$i,cnpj));
					
					if (strlen($cnpj) == 14)//CNPJ
						$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
					if (strlen($cnpj) == 11)//CPF
						$cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
					
					$banco            = trim(pg_result($res,$i,banco));
					$agencia          = trim(pg_result($res,$i,agencia));
					$conta            = trim(pg_result($res,$i,conta));
					$favorecido_conta = trim(pg_result($res,$i,favorecido_conta));
					$conta_operacao   = trim(pg_result($res,$i,conta_operacao));

				}
				
				$mao_de_obra = trim(pg_result($res,$i,mao));
				$pecas       = trim(pg_result($res,$i,pecas));
				$avulso      = trim(pg_result($res,$i,avulso));
				$total       = trim(pg_result($res,$i,total));
				
				if ($login_fabrica == 20) {
					$total_cortesia = trim(pg_result($res,$i,total_cortesia));
				}

				$total_os           = trim(pg_result($res,$i,total_os));
				$tipo_posto         = trim(pg_result($res,$i,tipo_posto));
				$pedido_em_garantia = trim(pg_result($res,$i,reembolso_peca_estoque));
				
				if ($pedido_em_garantia == 't') {
					$pedido_em_garantia = "Sim";
				} else {
					$pedido_em_garantia = "Não";
				}

				$cor = ($i%2 ) ? '#F7F5F0' : '#F1F4FA';

				echo $sql1 = "SELECT tbl_extrato.extrato
						INTO TEMP tmp_extrato_pagamento_$i
						FROM tbl_extrato
						JOIN tbl_extrato_extra USING(extrato)
						WHERE posto = $id
						AND  tbl_extrato.fabrica = $login_fabrica
						$sql_data;
						CREATE INDEX tmp_extrato_pagamento_extrato_$i ON tmp_extrato_pagamento_$i(extrato);";
				
				$res1 = pg_exec($con, $sql1);
				
				echo $sql1 = "SELECT count(*) AS total_os
						FROM tbl_os_extra
						JOIN tmp_extrato_pagamento_$i USING(extrato);";

				$res1 = pg_exec($con, $sql1);
				$total_os = trim(pg_result($res1,0,total_os)) ;

				$pecas       = number_format($pecas,2,",",".");
				$mao_de_obra = number_format($mao_de_obra,2,",",".");
				$avulso      = number_format($avulso,2,",",".");
				$total       = number_format($total,2,",",".");

				if ($login_fabrica == 20) {
					$total_cortesia = number_format($total_cortesia,2,",",".");
				}

				echo "<tr class='Conteudo' bgcolor='$cor'>";
				echo "<td bgcolor='$cor' >$codigo_posto</td>";
				echo "<td bgcolor='$cor' align='left' title='nome' nowrap>".substr($nome,0,20)."</td>";
				
				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					echo "<td bgcolor='$cor' >$cnpj</td>";
					echo "<td bgcolor='$cor' >$banco</td>";
					echo "<td bgcolor='$cor' >$agencia</td>";
					echo "<td bgcolor='$cor' >$conta</td>";
					echo "<td bgcolor='$cor' >$favorecido_conta</td>";
					echo "<td bgcolor='$cor' >$conta_operacao</td>";
				}

				//HD 15422 - Jean
				if ($login_fabrica == 20) {
					echo "<td bgcolor='$cor' align='left'>$escritorio_regional</td>";
				} else {
					echo "<td bgcolor='$cor' align='left'>$estado</td>";
				}

				echo "<td bgcolor='$cor' align='left'>$tipo_posto</td>";
				if ($login_fabrica == 1) echo "<td bgcolor='$cor' align='left'>$pedido_em_garantia</td>";
				echo "<td bgcolor='$cor' align='right' nowrap>R$ $mao_de_obra</td>";
				echo "<td bgcolor='$cor' align='right' nowrap>R$ $pecas</td>";
				echo "<td bgcolor='$cor' align='right' nowrap>R$ $avulso</td>";
				
				//hd 39502
				if ($login_fabrica == 20) {
					echo "<td bgcolor='$cor' align='right' nowrap>$ $total_cortesia</td>";
				}

				echo "<td bgcolor='$cor' align='right' nowrap>R$ $total</td>";
				echo "<td bgcolor='$cor' align='center'>$total_os</td>";
				echo "</tr>";
				flush();

				fputs($fp,"<tr class='Conteudo'>");
				fputs($fp,"<td bgcolor='$cor' >$codigo_posto</td>");
				fputs($fp,"<td bgcolor='$cor' align='left' title='nome'>".substr($nome,0,20)."</td>");
				
				//DADOS BANCARIOS NKS HD 8190 Gustavo 29/11/2007
				if ($login_fabrica == 45 || $login_fabrica == 40) {
					fputs($fp,"<td bgcolor='$cor' >$cnpj</td>");
					fputs($fp,"<td bgcolor='$cor' >$banco</td>");
					fputs($fp,"<td bgcolor='$cor' >$agencia</td>");
					fputs($fp,"<td bgcolor='$cor' >$conta</td>");
					fputs($fp,"<td bgcolor='$cor' >$favorecido_conta</td>");
					fputs($fp,"<td bgcolor='$cor' >$conta_operacao</td>");
				}

				//HD 15422 - Jean
				if ($login_fabrica == 20) {
					fputs($fp,"<td bgcolor='$cor' >$escritorio_regional</td>");
				} else {
					fputs($fp,"<td bgcolor='$cor' >$estado</td>");
				}

				fputs($fp,"<td bgcolor='$cor' >$tipo_posto</td>");
				if ($login_fabrica == 1) fputs($fp,"<td bgcolor='$cor' >$pedido_em_garantia</td>");
				fputs($fp,"<td bgcolor='$cor' align='right' nowrap>R$ $mao_de_obra</td>");
				fputs($fp,"<td bgcolor='$cor' align='right' nowrap>R$ $pecas</td>");
				fputs($fp,"<td bgcolor='$cor' align='right' nowrap>R$ $avulso</td>");
				
				//hd 39502
				if ($login_fabrica == 20) {
					fputs($fp,"<td bgcolor='$cor' align='right'>R$ $total_cortesia</td>");
				}
				
				fputs($fp,"<td bgcolor='$cor' align='right' nowrap>R$ $total</td>");
				fputs($fp,"<td bgcolor='$cor' align='center'>$total_os</td>");

			}

			fputs($fp,"</table>");
			fputs($fp,"</body>");
			fputs($fp,"</html>");
			fclose ($fp);

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_pagamento_posto_agrupado-$login_fabrica.$data.xls /tmp/assist/relatorio_pagamento_posto_agrupado-$login_fabrica.html`;

			echo "</table>";
			echo "<script language='javascript'>";
			echo "document.getElementById('id_download').style.display='block';";
			echo "</script>";

		} else {
			echo "<br><br><p>Nenhum resultado encontrado!</p>";
		}

	}

}

include 'rodape.php';

?>
