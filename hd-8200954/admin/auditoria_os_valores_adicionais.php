<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";

include "autentica_admin.php";
include 'funcoes.php';

if ($_GET['aprOS'] == 1) {

	$os = $_GET['idOS'];
	$valor_adicional_original    = str_replace("\\","",$_GET['valor_adicional_original']);
	$valor_adicional_modificado  = str_replace("\\","",$_GET['valor_adicional_modificado']);
	$valor_adicional_modificado2 = $valor_adicional_modificado;
	$status_os = 172;
	$obs = array();

	$res = pg_query($con,"BEGIN");
	if($valor_adicional_modificado != $valor_adicional_original){
		$valor_adicional_original 	= json_decode($valor_adicional_original,true);
		$valor_adicional_modificado = json_decode($valor_adicional_modificado,true);

		foreach ($valor_adicional_modificado as $key => $value) {
			foreach ($value as $chave => $valor) {

				if(!empty($valor) AND $valor != "0,00"){

					if($valor_adicional_original[0][$chave] AND $valor_adicional_original[0][$chave] != $valor){
						$obs[] = "Valor do serviço $chave foi alterado de {$valor_adicional_original[0][$chave]} para $valor";
						$valor_adicional_original[0][$chave] = $valor;
					}

					if($valor_adicional_original[1][$chave] AND $valor_adicional_original[1][$chave] != $valor){
						$obs[] = "Valor do serviço $chave foi alterado de {$valor_adicional_original[1][$chave]} para $valor";
						$valor_adicional_original[1][$chave] = $valor;
					}
				}else{
					if($valor_adicional_original[0][$chave]){
						unset($valor_adicional_original[0][$chave]);
					}

					if($valor_adicional_original[1][$chave]){
						unset($valor_adicional_original[1][$chave]);
					}

					$obs[] = "Valor do serviço $chave foi retirado da Ordem de Serviço";
				}
			}
		}
		$valor_adicional_original = json_encode($valor_adicional_original);


		$sql = "UPDATE tbl_os_campo_extra SET valores_adicionais = '$valor_adicional_original' WHERE os = $os";
		$res      = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if(count($obs) > 0){
		$observacao = implode("<br>", $obs);
	}else{
		$observacao = "OS liberada da auditoria de valores adicionais pelo fabricante";
	}

	$sql = "INSERT INTO tbl_os_status (
				os,
				status_os,
				admin,
				observacao,
				fabrica_status
			) VALUES (
				$os,
				$status_os,
				$login_admin,
				'$observacao',
				$login_fabrica
			)";

	$res      = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);


	$valor_adicional_modificado2 = json_decode($valor_adicional_modificado2,true);
	foreach ($valor_adicional_modificado2 as $key => $value) {
		foreach ($value as $valor) {
			$valor2 = str_replace(".", "", $valor);
			$valor2 = str_replace(",", ".", $valor2);
			$soma  = $soma + $valor2;
		}
	}

	$sql = "UPDATE tbl_os SET valores_adicionais = $soma WHERE os = $os";
	$res      = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT");
		echo "OK|OS Aprovada com Sucesso!";
	} else {
		$res = pg_query($con,"ROLLBACK");
		echo "NO|OS não Aprovada. Erro: $msg_erro";
	}

	exit;

}

if ($_GET['repOS'] == 1) {

	$os        = $_GET['idOS'];
	$posto     = $_GET['posto'];
	$motivo    = $_GET['motivo'];
	$status_os = 173;

	$res = pg_query($con,"BEGIN");

    $sql = "UPDATE tbl_os_campo_extra SET valores_adicionais = NULL WHERE os = $os";
    $res = pg_exec($con,$sql);

	$sql = "INSERT INTO tbl_os_status (
				os,
				status_os,
				admin,
				observacao,
				fabrica_status
			) VALUES (
				$os,
				$status_os,
				$login_admin,
				'OS reprovada da auditoria de valores adicionais pelo fabricante',
				$login_fabrica
			)";

	$res      = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	$os = (!empty($sua_os)) ? $sua_os : $os;

	$mensagem = "A OS : ".$os." foi reprovada da intervenção de valores adicionais <br> <b>Justificativa :</b> ".$motivo;

	$sql = "INSERT INTO tbl_comunicado (
				mensagem         ,
				tipo             ,
				fabrica          ,
				obrigatorio_site ,
				descricao        ,
				posto            ,
				ativo
			) VALUES (
				'$mensagem',
				'Comunicado',
				$login_fabrica,
				't',
				'Reprovação Intervenção de Valores Adicionais',
				$posto,
				't'
			)";

	$res       = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT");
		echo "OK|OS Reprovada com Sucesso!";
	} else {
		$res = pg_query($con,"ROLLBACK");
		echo "NO|OS n&atilde;o Exclu&iacute;da. <br>Erro: $msg_erro";
	}

	exit;

}

$btn_acao = $_POST['btn_acao'];

if ($btn_acao == "Pesquisar") {

	$os              = $_POST['os'];
	$posto_codigo    = $_POST['posto_codigo'];
	$posto_descricao = $_POST['posto_descricao'];
	$data_inicial    = $_POST['data_inicial'];
	$data_final      = $_POST['data_final'];
	$estado          = $_POST['estado'];

	if (!empty($os)) {

		$campo = (strpos($os,'-') ) ? 'sua_os' : 'os';
		$os    = $campo == 'sua_os' ? "'$os'" : $os;

		$sql = "SELECT os FROM tbl_os where $campo = $os AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if (pg_numrows($res) == 0) {
			$msg_erro = "OS não Cadastrada";
		} else {
			$condOS = " AND tbl_os.$campo = $os";
		}

	} else {

		if (!empty($posto_codigo)) {

			$sql = "SELECT posto from tbl_posto_fabrica WHERE codigo_posto = '$posto_codigo' and fabrica = $login_fabrica";
			$res = pg_query($con,$sql);

			if(pg_numrows($res) == 0){
				$msg_erro = "Posto não Encontrado";
			} else {
				$posto = pg_result($res,0,posto);
				$condPosto = " AND tbl_os.posto = $posto";
			}

		}

		if (!empty($estado)) {
			$condEstado = "  AND tbl_posto.estado = '$estado' ";

		}

		if (!empty($data_inicial) && !empty($data_final)) {

			list($di, $mi, $yi) = explode("/", $data_inicial);

			if (!checkdate($mi,$di,$yi)) {
				$msg_erro = "Data Inválida";
			}

			list($df, $mf, $yf) = explode("/", $data_final);

			if (!checkdate($mf,$df,$yf)){
				$msg_erro = "Data Inválida";
			}

			if (strlen($msg_erro) == 0) {

				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";

				if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
					$msg_erro = "Data Inválida";
				}

			}

			$condOS .= " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";

		}

	}

}

$os          = str_replace("'","",$os);
$layout_menu = "auditoria";

$title = "AUDITORIA DE OS ABERTA COM VALORES ADICIONAIS";

include "cabecalho.php";?>

<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

#relatorio thead tr th {

	cursor: pointer;

}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.espaco{
	padding: 0 0 0 100px;
}

caption{
	height:25px;
	vertical-align:center;
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

#msg{
	margin: auto;
	width:700px;
}
</style><?php

//include 'javascript_calendario_new.php';
?>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script type="text/javascript"    src="js/jquery.price_format.1.7.min.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<link type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<script language='javascript'>
$().ready(function() {

	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

	$('#data_inicial').datepick({startdate : '01/01/2000'});
	$('#data_final').datepick({startDate : '01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");

	$("input[rel=valor]").priceFormat({
        prefix: '',
        centsSeparator: ',',
        thousandsSeparator: '.'
    });

});

function SomenteNumero(e){
    var tecla=(window.event)?event.keyCode:e.which;
    if((tecla > 47 && tecla < 58) || tecla == 45 || tecla == 17 || tecla == 118 || tecla == 86 || tecla == 9) return true;
    else{
    if (tecla != 8) return false;
    else return true;
    }
}

function fnc_pesquisa_posto2(campo, campo2, tipo) {

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

        if ("<? echo $pedir_sua_os; ?>" == "t") {
            janela.proximo = document.frm_os.sua_os;
        } else {
            janela.proximo = document.frm_os.data_digitacao;
        }

        janela.focus();

    } else {
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }

}

function aprovaOS(os) {

	if (confirm('Deseja APROVAR esta Ordem de Serviço?')) {
		var valor_adicional_original = $("input[name=valor_adicional_original_"+os+"]").val();
		var valor_adicional_modificado = $("input[name=valor_adicional_modificado_"+os+"]").val();

		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?aprOS=1&idOS="+os+"&valor_adicional_original="+valor_adicional_original+"&valor_adicional_modificado="+valor_adicional_modificado,
			cache: false,
			success: function(data) {

				retorno = data.split('|');

				if (retorno[0]=="OK") {
					$("#msg").addClass("sucesso").html(retorno[1]).show();
					setTimeout(
								function(){
									$("#msg").hide();
								},3000);

					$('#'+os).hide().remove();
					$("#linha_valores_"+os).hide().remove();
				} else {
					$("#msg").addClass("msg_erro").html(retorno[1]).show();
				}

			}

		});

	}

}

function abreMotivo(os) {

	$("#linha_motivo_"+os).toggle();

}

function abreValores(os) {

	$("#linha_valores_"+os).toggle();

}

function reprovaOS(os,posto) {

	var motivo = $("#motivo_"+os).val();

	if (motivo == "") {
		alert("Informe uma justificativa");
	} else {

		if (confirm('Deseja REPROVAR esta Ordem de Serviço?')) {

			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?repOS=1&idOS="+os+"&posto="+posto+"&motivo="+motivo,
				cache: false,
				success: function(data) {

					retorno = data.split('|');

					if (retorno[0]=="OK") {
						$("#msg").addClass("sucesso").html(retorno[1]).show();
						setTimeout(
								function(){
									$("#msg").hide();
								},3000);
						$("#linha_motivo_"+os).hide().remove();
						$("#linha_valores_"+os).hide().remove();
						$('#'+os).hide('slow').remove();

					} else {
						$("#msg").addClass("msg_erro").html(retorno[1]).show();
					}

				}
			});

		}

	}

}

function alteraValorAdicional(campo){
    $(campo).parent("td").parent("tr").find("input[name^=custo_adicional]:checked").val(campo.value);
    $("input[name^=custo_adicional]").change();
}

$(function(){
   $(document).delegate("input[rel=servico],input[rel=valor]","blur",function(){
        var json = {};
        var array = new Array();
        var grupo2 = {};
        var os = $(this).attr("class");

        $("input[name^=servico_"+os+"_]").each(function(){
            if( $.trim($(this).val()).length > 0 ){
                var rel = $(this).val().toString();
                grupo2[rel] = $(this).parent("td").parent("tr").find("input[name^=valor_"+os+"_]").val();
            }
        });
        array.push(grupo2);

        json = array;

        $("input[name=valor_adicional_modificado_"+os+"]").val(JSON.stringify(json));
   });
});
</script>

<div class='texto_avulso'> Este Relatório considera a data de Abertura das OS </div> <br /><?php

if (strlen($msg_erro) > 0) {?>
	<table align='center' width='700' class='msg_erro'>
		<tr><td><? echo $msg_erro; ?> </td></tr>
	</table><?php
}?>

<div id="msg"></div>

<form name='frm_pesquisa' method='post' action='<? echo $PHP_SELF; ?>'>
	<table align='center' width='700' class='formulario'>
		<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td class='espaco'>
				Nº OS <br />
				<input type='text' name='os' id='os' value='<?= $os; ?>' size='15' class="frm" onkeypress='return SomenteNumero(event)' />
			</td>
		</tr>
		<tr>
			<td class='espaco'>
				Cod Posto <br />
				<input type="text" name="posto_codigo" id="posto_codigo" class="frm" value="<?php echo $posto_codigo; ?>" size="10" maxlength="30" />&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto2 (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_descricao,'codigo')">
			</td>

			<td>
				Nome Posto <br />
				<input type="text" name="posto_descricao" id="posto_descricao" class="frm" value="<?php echo $posto_descricao; ?>" size="50" maxlength="50" />&nbsp;
				<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto2 (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_descricao,'nome')">
			</td>
		</tr>

		<tr>
			<td class='espaco'>
				Data Inicial <br />
				<input type='text' name='data_inicial' id='data_inicial' size='12' value='<?= $data_inicial; ?>' class="frm">
			</td>

			<td>
				Data Final <br />
				<input type='text' name='data_final' id='data_final' size='12' value='<?= $data_final; ?>' class="frm">
			</td>
		</tr>

		<?
		if(in_array($login_fabrica, array(151))){
		?>
		<tr>
		    <td class='espaco'>Estado<br />
		        <select name="estado" class="frm">
		            <option value="">&nbsp;</option>
		<?
		    foreach($array_estados() as $est=>$nome){
		?>
		            <option value="<?=$est?>" <?=($est == $estado) ? "selected" : "" ;?>><?=$nome?></option>
		<?
		    }
		?>
		        </select>
		    </td>
		</tr>
		<?
		}
		?>
		<tr>
			<td colspan='2' align='center' style='padding:20px 0 10px 0;'>
				<input type='hidden' name='btn_acao' value=''>
				<input type="button" value="Pesquisar" onclick="if (document.frm_pesquisa.btn_acao.value == '') { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer;" />
			</td>
		</tr>
	</table>
</form>
<br /><?php

if (!empty($btn_acao) && empty($msg_erro)) {
	$condStatus = "171,172,173";
	$status = "171";
	$sql = "SELECT interv_reinc.os INTO temp tmp_status
		FROM (
			  SELECT
				  ultima_reinc.os,
					(SELECT status_os
						 FROM tbl_os_status
						 WHERE fabrica_status = $login_fabrica
						 AND tbl_os_status.os = ultima_reinc.os AND status_os IN ($condStatus) order by data desc LIMIT 1) AS ultimo_reinc_status
				  FROM (SELECT DISTINCT os
				   FROM tbl_os_status
				JOIN tbl_os USING(os)
				WHERE fabrica_status = $login_fabrica
				$condOS
				$condPosto
				AND tbl_os.finalizada IS NULL
			AND status_os IN ($condStatus) ) ultima_reinc
			) interv_reinc
		WHERE interv_reinc.ultimo_reinc_status IN ($status);

	SELECT distinct tbl_os.os, tbl_os.sua_os                                    ,
			   TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS  data_digitacao  ,
			   tbl_posto_fabrica.posto                                          ,
			   tbl_posto_fabrica.codigo_posto                                   ,
			   tbl_posto.nome                                                   ,
			   tbl_produto.descricao                                            ,
			   tbl_produto.referencia                                           ,
			   tbl_os_campo_extra.valores_adicionais
			FROM tbl_os
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto USING(produto)
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os
			LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.os IN(SELECT os FROM tmp_status)";


	$sql .= $condPosto." ".$condOS." ".$condEstado;

	$res   = pg_exec($con,$sql);
	$total = pg_numrows($res);

	if ($total > 0) {?>

		<table align='center' class='tabela' id="relatorio" cellspacing='1'>

			<thead>
				<tr class="titulo_coluna">
					<th>OS</th>
					<th>Data Abertura</th>
					<th>Posto</th>
					<th>Produto</th>
					<th>Valores</th>
					<th colspan='2'>Ação</th>
				</tr>
			</thead>

			<tbody>

			<?php

			for ($i = 0; $i < $total; $i++) {

				$os           			= pg_result($res, $i, 'os');
				$sua_os		  			= pg_result($res, $i, 'sua_os');
				$digitacao    			= pg_result($res, $i, 'data_digitacao');
				$posto        			= pg_result($res, $i, 'posto');
				$codigo_posto 			= pg_result($res, $i, 'codigo_posto');
				$nome_posto   			= pg_result($res, $i, 'nome');
				$produto      			= pg_result($res, $i, 'descricao');
				$referencia   			= pg_result($res, $i, 'referencia');
				$valores_adicionais   	= pg_result($res, $i, 'valores_adicionais');

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				?>

				<tr bgcolor='<? echo $cor; ?>' id='<? echo $os; ?>'>
					<td><a href='os_press.php?os=<?=$os;?>' target='_blank'><?php echo $os; ?></a></td>
					<td><? echo $digitacao; ?></td>
					<td align='left'><? echo $codigo_posto." - ".$nome_posto; ?></td>
					<td align='left'><? echo $referencia." - ".$produto; ?></td>
					<td><input type='button' value='Ver valores' id='valores_<?=$os;?>' onclick='abreValores(<?=$os;?>);'></td>
					<td><input type='button' value='Aprovar' id='aprova_<?=$os;?>' onclick='aprovaOS(<?=$os;?>);'></td>
					<td><input type='button' value='Reprovar' id='reprova_<? echo $os; ?>' onclick='abreMotivo(<?=$os;?>);'></td>
				</tr>
				<tr style='display:none;' id='linha_motivo_<?=$os;?>'>
					<td colspan='7'>
						Justificativa: <input type='text' name='motivo_<?=$os;?>' id='motivo_<?=$os;?>' class='frm' size='80'> &nbsp;
						<input type='button' value='Gravar' onclick='reprovaOS(<?=$os;?>, <?=$posto;?>);' />
					</td>
				</tr>

				<tr style='display:none;' id='linha_valores_<?=$os;?>'>
					<td colspan='7'>
						<?php
							$sqlCustoAdicional = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = $os";
							$resCustoAdicional = pg_query($con,$sqlCustoAdicional);

							if(pg_num_rows($resCustoAdicional) > 0){

								$custos_adicionais = pg_fetch_result($resCustoAdicional,0,'valores_adicionais');




						?>
								<input type='hidden' value='<?=$custos_adicionais?>' name='valor_adicional_original_<?=$os?>'>
								<input type='hidden' value='<?=$custos_adicionais?>' name='valor_adicional_modificado_<?=$os?>'>
								<table width='500' align='center' class='tabela'>
									<caption class='titulo_tabela'>Custos adicionais</caption>
									<tr class='titulo_coluna'>
										<td>Serviço</td>
										<td>Valor</tr>
									</tr>
						<?php
								$j = 0;
								$custos_adicionais = json_decode($custos_adicionais,true);
								foreach ($custos_adicionais as $key => $value) {
									foreach ($value as $chave => $valor) {
										$cor2 = ($j % 2) ? "#F7F5F0" : "#F1F4FA";
						?>
										<tr bgcolor='<?=$cor2?>'>
											<td align='left'> <input type='text' class='<?=$os?>' name='servico_<?=$os?>_<?=$j?>' rel='servico' value='<?=$chave?>' class='frm' size='40' readonly="readonly"> </td>
											<td align='center'> <input type='text' class='<?=$os?>' name='valor_<?=$os?>_<?=$j?>' rel='valor' value='<?=$valor?>' class='frm' size='7'> </td>
										</tr>
						<?php
										$j++;
									}
								}
						?>
								</table> <br>
						<?php

							}
						?>
					</td>
				</tr>

				<?php

			}

		echo '</tbody></table>';

	} else {
		echo "<center>Nenhum Resultado Encontrado</center>";
	}

}

include "rodape.php"; ?>
