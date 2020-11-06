<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj         = trim(pg_result($res,$i,cnpj));
				$nome         = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$layout_menu = "auditoria";
$title = "AUDITORIA DE OS REINCIDENTES, SEM PEÇAS OU COM MAIS DE 03 PEÇAS";

include "cabecalho.php";

?>

<style type="text/css">
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
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.espaco{padding-left:150px;}
</style>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

</script>

<script language="JavaScript">
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

</script>

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


<? include "javascript_pesquisas.php";

if ($btn_acao == 'Pesquisar') {

	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$status_os    = trim($_POST['status_os']);
	$os           = trim($_POST['os']);
	$tipo_os      = trim($_POST['tipo_os']);
	if (strlen($os)>0){
		$Xos = " AND tbl_os.sua_os = '$os' ";
	}

	$sql_tipo = "140, 141, 142, 143";

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
	if(!empty($data_inicial) && !empty($data_final) ){
		if($data_inicial > $data_final)
			$msg_erro = 'Data Inválida';
		else {
			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y)) 
				$msg_erro = "Data Inválida";
			else {
				list($d, $m, $y) = explode("/", $data_final);
				if(!checkdate($m,$d,$y)) 
					$msg_erro = "Data Inválida";
			}
		
		}
	}
}

?>
<div id="msg"></div>
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<table width="700" align="center" border="0" cellspacing='1' cellpadding='0' class='formulario'>

<tbody>
<tr><td class="titulo_tabela" colspan="3">Parâmetros de Pesquisa</td></tr>
<tr>
	<td class="espaco">Número da OS<br><input type="text" name="os" id="os" size="15" maxlength="20" value="<? echo $os ?>" class="frm" /></td>
</tr>
<tr>
	<td class="espaco" width="200px">
		Data Inicial<br>
		<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
	</td>
	<td>
		Data Final<br>
		<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
	</td>
</tr>
<tr>
	<td class="espaco">
		Código Posto<br>
		<input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm"></td>
	<td>
		Nome do Posto<br>
		<input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm">
	</td>
</tr>
<tr>
	<td colspan='2' class="espaco">
		<fieldset style="width:380px;">
			<legend>Status OS</legend>
			<input type="radio" name="status_os" value='140' <? if(trim($status_os) == '140' OR trim($status_os)==0) echo "checked='checked'"; ?>>Bloqueada&nbsp;&nbsp;&nbsp;
			<input type="radio" name="status_os" value='141' <? if(trim($status_os) == '141') echo "checked='checked'"; ?>>Justificada  &nbsp;&nbsp;&nbsp;
			<input type="radio" name="status_os" value='142' <? if(trim($status_os) == '142') echo "checked='checked'"; ?>>Liberada Alteração&nbsp;&nbsp;&nbsp;
			<input type="radio" name="status_os" value='143' <? if(trim($status_os) == '143') echo "checked='checked'"; ?>>Cancelada&nbsp;&nbsp;&nbsp;
		</fieldset>
	</td>
</tr>
</tbody>
<tr>
	<td colspan="2" align="center" style="padding:10px 0 10px;">
		<input type='hidden' name='btn_acao' value='' />
		<input type="button" value="Pesquisar" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer;" />
	</td>
</tr>
</table>
</form>

<?php
if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
	
	$posto_codigo = trim($_POST["posto_codigo"]);

	if(strlen($posto_codigo)>0)         $sql_add .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
	#HD 100725 foi acrescentado o campo admin e data de auditoria para fabrica britanica
		if ($login_fabrica == 3) {
			$sql =  "SELECT interv.os, admin
					INTO TEMP tmp_interv_$login_admin
					FROM (
						SELECT
							ultima.os,
							(
								SELECT status_os
								FROM tbl_os_status
								WHERE status_os IN ($sql_tipo)
									AND tbl_os_status.fabrica_status = $login_fabrica
									AND tbl_os_status.os = ultima.os
								ORDER BY data DESC LIMIT 1
							) AS ultimo_status,
							(
								SELECT admin
								FROM tbl_os_status
								WHERE status_os IN ($sql_tipo)
									AND tbl_os_status.fabrica_status = $login_fabrica
									AND tbl_os_status.os = ultima.os
								ORDER BY data DESC LIMIT 1
							) AS admin
						FROM (
							SELECT
								DISTINCT os
							FROM tbl_os_status
							WHERE status_os IN ($sql_tipo)
							AND tbl_os_status.fabrica_status = $login_fabrica
						) ultima
					) interv
					WHERE interv.ultimo_status IN ($status_os);

					CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

					/* HD 54005 */
					SELECT  os,
							data
					INTO TEMP tmp_interv_data_$login_admin
					FROM tmp_interv_$login_admin
					JOIN tbl_os_status USING(os)
					WHERE status_os IN ($status_os)
					AND tbl_os_status.fabrica_status = $login_fabrica;

					SELECT	tbl_os.os                                                                       ,
							tbl_os.sua_os                                                                   ,
							tbl_os.consumidor_nome                                                          ,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')        AS data_abertura              ,
							TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')       AS data_digitacao             ,
							TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY')     AS data_fechamento            ,
							TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY hh24:mi')  AS finalizada                 ,
							TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY')       AS data_conserto              ,
							tbl_os.fabrica                                                                  ,
							tbl_os.consumidor_nome                                                          ,
							tbl_posto.nome                                    AS posto_nome                 ,
							tbl_posto_fabrica.codigo_posto                                                  ,
							tbl_posto_fabrica.contato_email                   AS posto_email                ,
							tbl_produto.referencia                            AS produto_referencia         ,
							tbl_produto.descricao                             AS produto_descricao          ,
							tbl_produto.voltagem                                                            ,
							tbl_admin.nome_completo                           AS nome_completo              ,
							tbl_peca.referencia as peca_referencia                                          ,
							tbl_peca.descricao as peca_descricao                                            ,
							tbl_os_item.qtde                                                                ,
							tbl_servico_realizado.descricao                   AS servico_realizado_descricao,
							tbl_defeito.descricao                             AS defeito_descricao          ,
							tbl_os_item.pedido                                                              ,
							to_char(tbl_os_item.digitacao_item, 'DD/MM/YYYY') AS digitacao_item             ,
							tbl_os.nota_fiscal                                                              ,
							TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY')             AS data_nf                    ,
							TO_CHAR(tmp_interv_data_$login_admin.data,'DD/MM/YYYY') AS data_auditada        ,
							(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_os         ,
							(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_observacao,
							(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) ORDER BY data DESC LIMIT 1) AS status_descricao
						FROM tmp_interv_$login_admin X
						JOIN tbl_os ON tbl_os.os = X.os
						LEFT JOIN tbl_admin on X.admin =  tbl_admin.admin
						JOIN tmp_interv_data_$login_admin ON tmp_interv_data_$login_admin.os = X.os
						JOIN tbl_produto                  ON tbl_produto.produto = tbl_os.produto
						LEFT JOIN tbl_os_produto          ON tbl_os.os                     = tbl_os_produto.os
						LEFT JOIN tbl_os_item             ON tbl_os_item.os_produto        = tbl_os_produto.os_produto
						LEFT JOIN tbl_peca                ON tbl_os_item.peca              = tbl_peca.peca
														  AND tbl_peca.fabrica             = $login_fabrica
						LEFT JOIN tbl_servico_realizado   ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
						LEFT JOIN tbl_defeito             ON tbl_os_item.defeito = tbl_defeito.defeito
						JOIN tbl_posto                    ON tbl_os.posto                  = tbl_posto.posto
						JOIN tbl_posto_fabrica            ON tbl_posto.posto               = tbl_posto_fabrica.posto
														  AND tbl_posto_fabrica.fabrica    = $login_fabrica
						$sql_add
						WHERE tbl_os.fabrica = $login_fabrica
						$Xos
						";
		} else {
			$sql =  "SELECT interv.os
					INTO TEMP tmp_interv_$login_admin
					FROM (
						SELECT
							ultima.os,
							(
								SELECT status_os
								FROM tbl_os_status
								WHERE status_os IN ($sql_tipo)
									AND tbl_os_status.fabrica_status = $login_fabrica
									AND tbl_os_status.os = ultima.os
								ORDER BY data DESC LIMIT 1
							) AS ultimo_status
						FROM (
							SELECT
								DISTINCT os
							FROM tbl_os_status
							WHERE status_os IN ($sql_tipo)
							AND tbl_os_status.fabrica_status = $login_fabrica
						) ultima
					) interv
					WHERE interv.ultimo_status IN ($status_os);

					CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

					/* HD 54005 */
					SELECT  os,
							data
					INTO TEMP tmp_interv_data_$login_admin
					FROM tmp_interv_$login_admin
					JOIN tbl_os_status USING(os)
					WHERE status_os IN ($status_os)
					AND tbl_os_status.fabrica_status = $login_fabrica;

					SELECT	tbl_os.os                                                                       ,
							tbl_os.sua_os                                                                   ,
							tbl_os.consumidor_nome                                                          ,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')        AS data_abertura              ,
							TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')       AS data_digitacao             ,
							tbl_os.fabrica                                                                  ,
							tbl_os.consumidor_nome                                                          ,
							tbl_posto.nome                                    AS posto_nome                 ,
							tbl_posto_fabrica.codigo_posto                                                  ,
							tbl_posto_fabrica.contato_email                   AS posto_email                ,
							tbl_produto.referencia                            AS produto_referencia         ,
							tbl_produto.descricao                             AS produto_descricao          ,
							tbl_produto.voltagem                                                            ,
							(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) AND tbl_os_status.fabrica_status = $login_fabrica
							ORDER BY data DESC LIMIT 1) AS status_os         ,
							(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) AND tbl_os_status.fabrica_status = $login_fabrica
							ORDER BY data DESC LIMIT 1) AS status_observacao,
							(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo)
							AND tbl_os_status.fabrica_status = $login_fabrica
							ORDER BY data DESC LIMIT 1) AS status_descricao
						FROM tmp_interv_$login_admin X
						JOIN tbl_os ON tbl_os.os = X.os
						JOIN tmp_interv_data_$login_admin ON tmp_interv_data_$login_admin.os = X.os
						JOIN tbl_produto                  ON tbl_produto.produto = tbl_os.produto
						JOIN tbl_posto                    ON tbl_os.posto        = tbl_posto.posto
						JOIN tbl_posto_fabrica            ON tbl_posto.posto     = tbl_posto_fabrica.posto
														  AND tbl_posto_fabrica.fabrica = $login_fabrica
						$sql_add
						WHERE tbl_os.fabrica = $login_fabrica
						$Xos
						";
		}

		//echo nl2br($sql);

	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tmp_interv_data_$login_admin.data BETWEEN '$xdata_inicial' AND '$xdata_final' ";
		$condicao = "&data_inicial=$data_inicial&data_final=$data_final";
	}

	$sql.="ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.os";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<br />";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='status_os'         value='$status_os'>";

		echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
			echo "<td>OS</td>";
			echo "<td>Abertura</td>";
			echo "<td>Digitação</td>";
			if ($login_fabrica == 3) {
				echo "<td>Auditada</td>";
				echo "<td>Admin</td>";
			}
			echo "<td>Posto</td>";
			echo "<td>Nome do Posto</td>";
			echo "<td>Email</td>";
			echo "<td>Produto</td>";
			echo "<td>Descrição</td>";
			echo "<td width='300'>Obervação</td>";
			echo "<td>Status</td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x = 0; $x < pg_numrows($res); $x++) {

			$os				= pg_result($res, $x, os);
			$sua_os				= pg_result($res, $x, sua_os);
			$codigo_posto			= pg_result($res, $x, codigo_posto);
			$posto_nome			= pg_result($res, $x, posto_nome);
			$posto_email			= pg_result($res, $x, posto_email);
			$produto_referencia		= pg_result($res, $x, produto_referencia);
			$produto_descricao		= pg_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_result($res, $x, voltagem);
			$data_digitacao			= pg_result($res, $x, data_digitacao);
			$data_abertura			= pg_result($res, $x, data_abertura);
			$status_os			= pg_result($res, $x, status_os);
			$status_observacao		= pg_result($res, $x, status_observacao);
			$status_descricao		= pg_result($res, $x, status_descricao);

			if ($login_fabrica == 3) {
				$admin         = pg_result($res, $x, nome_completo);
				$data_auditada = pg_result($res, $x, data_auditada);
			}

                        if ($os != $os_anterior) {

				$os_anterior = $os;
				$cores++;

				$cor = ($cores % 2 == 0) ? "#F7F5F0": '#F1F4FA';

				if (strlen($sua_os) == 0)$sua_os = $os;

				echo "<tr bgcolor='$cor' id='linha_$x'>";
					echo "<td style='font-size: 9px; font-family: verdana' nowrap ><a href='os_press.php?os=$os'  target='_blank'>$sua_os</a></td>";
					echo "<td style='font-size: 9px; font-family: verdana'>".$data_abertura. "</td>";
					echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
					if ($login_fabrica == 3) {
						echo "<td style='font-size: 9px; font-family: verdana'>".$data_auditada. "</td>";
						echo "<td style='font-size: 9px; font-family: verdana'>".$admin. "</td>";
					}
					echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>$codigo_posto</td>";
					echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$codigo_posto." - ".$posto_nome."'>".substr($posto_nome,0,20) ."...</td>";
					echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap ><acronym title='$posto_email'><a href='mailto:$posto_email'>email</a></acronym></td>";
					echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia' style='cursor: help'>". $produto_referencia ."</acronym></td>";
					echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". substr($produto_descricao ,0,20) ."...
					</acronym></td>";
					echo "<td style='font-size: 9px; font-family: verdana'><acronym title=''>$status_observacao</acronym></td>";
					echo "<td style='font-size: 9px; font-family: verdana' nowrap><acronym title=''>$status_descricao</acronym></td>";
				echo "</tr>";

			}

		}

		if ($login_fabrica == 3) {
			echo 'Total de OS\'s: '.$cores;
			flush();
			$arquivo_nome     = "relatorio_os_aberta_45-$login_fabrica-$login_admin.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo `;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<html>\n");
			fputs ($fp,"<head>\n");
			fputs ($fp,"<title>Auditoria de OS Aberta a mais de 45 dias\n");
			fputs ($fp,"</title>\n");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>\n");
			fputs ($fp,"</head>\n");
			fputs ($fp,"<body>\n\n");

			fputs ($fp,"<p>Ordens de Serviços abertas a mais de 45 dias</p>\n\n");

			fputs ($fp,"<TABLE width='750' border='1' align='center' cellspacing='1' cellpadding='1'>\n");
			fputs ($fp, "<TR bgcolor='#000000'>\n");
			fputs ($fp, "<TD><font color='#FFFFFF'>OS</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>CÓDIGO POSTO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>NOME POSTO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>ABERTURA</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>DIGITAÇÃO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>FECHAMENTO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>FINALIZADA</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>CONSERTO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>AUDITADA</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>ADMIN</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>CONSUMIDOR</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>PRODUTO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>DESCRIÇÃO PRODUTO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>PEÇA</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>DESCRIÇÃO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>QTDE</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>DEFEITO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>SERVIÇO REALIZADO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>PEDIDO</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>DIGITAÇÃO ITEM</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>NOTA FISCAL</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>DATA NF</font></TD>");
			fputs ($fp, "<TD><font color='#FFFFFF'>STATUS</font></TD>");

			fputs ($fp, "</TR>\n");

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			//	echo $i;
				$sua_os             = trim(pg_result ($res,$i,sua_os));
				$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
				$posto_nome         = trim(pg_result ($res,$i,posto_nome));
				$data_abertura      = trim(pg_result ($res,$i,data_abertura));
				$data_digitacao     = trim(pg_result ($res,$i,data_digitacao));
				$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
				$finalizada         = trim(pg_result ($res,$i,finalizada));
				$data_conserto      = trim(pg_result ($res,$i,data_conserto));

				$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));

				$produto_referencia = trim(pg_result ($res,$i,produto_referencia));
				$produto_descricao  = trim(pg_result ($res,$i,produto_descricao));
				$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
				$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
				$qtde               = trim(pg_result ($res,$i,qtde));
				$servico_realizado_descricao = trim(pg_result ($res,$i,servico_realizado_descricao));
				$defeito_descricao  = trim(pg_result ($res,$i,defeito_descricao));
				$pedido             = trim(pg_result ($res,$i,pedido));
				$digitacao_item     = trim(pg_result ($res,$i,digitacao_item));
				$nota_fiscal        = trim(pg_result ($res,$i,nota_fiscal));
				$data_nf            = trim(pg_result ($res,$i,data_nf));
				$status_descricao   = trim(pg_result ($res,$i,status_descricao));
				if ($login_fabrica==3){
					$admin			= trim(pg_result ($res, $i, nome_completo));
					$data_auditada	= trim(pg_result ($res, $i, data_auditada));
				}

				if ($i % 2 == 0) {
					$cor = "#F1F4FA";
					$btn = "azul";
				} else {
					$cor = "#F7F5F0";
					$btn = "amarelo";
				}

				fputs ($fp,  "<TR class='table_line' style='background-color: $cor;'>\n");
				fputs ($fp,  "<TD nowrap>".$sua_os."</a></TD>");
				fputs ($fp,  "<TD nowrap>".$codigo_posto."</a></TD>");
				fputs ($fp,  "<TD nowrap>".$posto_nome."</TD>");
				fputs ($fp,  "<TD align='center'>".$data_abertura."</TD>");
				fputs ($fp,  "<TD align='center'>".$data_digitacao."</TD>");
				fputs ($fp,  "<TD align='center'>".$data_auditada."</TD>");
				fputs ($fp,  "<TD align='center'>".$data_fechamento."</TD>");
				fputs ($fp,  "<TD align='center'>".$finalizada."</TD>");
				fputs ($fp,  "<TD align='center'>".$data_conserto."</TD>");
				fputs ($fp,  "<TD align='center'>".$admin."</TD>");
				fputs ($fp,  "<TD nowrap>".$consumidor_nome."</TD>");
				fputs ($fp,  "<TD nowrap>".$produto_referencia."</TD>");
				fputs ($fp,  "<TD nowrap>".$produto_descricao."</TD>");
				fputs ($fp,  "<TD nowrap>".$peca_referencia."</TD>");
				fputs ($fp,  "<TD nowrap>".$peca_descricao."</TD>");
				fputs ($fp,  "<TD nowrap>".$qtde."</TD>");
				fputs ($fp,  "<TD nowrap>".$defeito_descricao."</TD>");
				fputs ($fp,  "<TD nowrap>".$servico_realizado_descricao."</TD>");
				fputs ($fp,  "<TD nowrap>".$pedido."</TD>");
				fputs ($fp,  "<TD nowrap>".$digitacao_item."</TD>");
				fputs ($fp,  "<TD nowrap>".$nota_fiscal."</TD>");
				fputs ($fp,  "<TD nowrap>".$data_nf."</TD>");
				fputs ($fp,  "<TD nowrap>".$status_descricao."</TD>");
				fputs ($fp,  "</TR>\n");
			}
			fputs ($fp,"</table>\n\n");
			fputs ($fp,"</body>\n");
			fputs ($fp,"</html>\n");
			fclose ($fp);

			echo ` cp $arquivo_completo_tmp $path `;
			$data = date("Y-m-d").".".date("H-i-s");

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
			$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			$resposta .="<tr>";
			$resposta .= "<td align='center'><input type='button' value='Download em Excel' onclick=\"window.location='xls/$arquivo_nome'\"> </td>";
			$resposta .= "</tr>";
			$resposta .= "</table>";
			echo $resposta;
		}

	} else {
		echo "<center>Nenhum OS encontrada.</center>";
	}

	$msg_erro = '';

}
else {
?>
	<div id="erro" style="display:none; width:700px;margin:auto;" class="msg_erro"><?=$msg_erro?></div>
	<script type="text/javascript">
		$("#erro").appendTo("#msg").fadeIn("slow");
	</script>
<?php
}

include "rodape.php"

?>
