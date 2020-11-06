<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "CUSTO POR OS - NACIONAIS x IMPORTADOS";

flush()	;

	
$btn_finalizar = $_POST["btn_finalizar"];

if (strlen($btn_finalizar)>0) {
	$data_inicial = $_POST["data_inicial_01"];
	$data_final = $_POST["data_final_01"];
	$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
	$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

	if (strlen($_POST["data_inicial_01"]) == 0) {
		$erro = "Favor informar a data inicial para pesquisa<br>";
	}
		
	if (strlen($erro) == 0) {
		$data_inicial   = trim($_POST["data_inicial_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
			if(strlen($erro)>0)
				$erro = "Data inválida";
		}
		
		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}

	if (strlen($_POST["data_final_01"]) == 0) {
		$erro = "Data Inválida";
	}

	//Converte data para comparação
	$d_ini = explode ("/", $data_inicial);//tira a barra
	$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...
	
	$d_fim = explode ("/", $data_final);//tira a barra
	$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


	
	if($nova_data_inicial > $nova_data_final){
		$erro="Data Inválida";
	}

	if (strlen($erro) == 0) {
		$data_final   = trim($_POST["data_final_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
			if(strlen($erro)>0)
				$erro = "Data inválida";
		}
			
		if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
	}
}

include "cabecalho.php";

?>

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

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
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

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>

<style type="text/css">
<!--
.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
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

.subtitulo{

color: #7092BE
}
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

-->
</style>

<? include "javascript_calendario.php";  // adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>


<center>

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($erro) > 0){
?>
<table width="700px" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $erro; ?>
			
	</td>
</tr>
</table>

<?
}
?>

<table width="700px" align="center" class='formulario' border="0" cellspacing="0" cellpadding="2">
  <tr>
	<td colspan="6" class="titulo_tabela" >Parâmetros de Pesquisa</td>
  </tr>

  <tr>
	<td class="texto_avulso" colspan='6'>Este relatório considera a data de aprovação do extrato.</td>
   </tr>

  <tr align='left'>
  <td width='10%'>&nbsp;</td>
	<td align='left'>Data Inicial*</td>
	<td width='8%' >Data Final*</td>
	<td >Região</td>
</tr>
	<?
	$data_inicial = $_POST['data_inicial_01'];
	$data_final   = $_POST['data_final_01'];
	?>
<tr align='left	'>
<td width='10%'>&nbsp;</td>
	<td ><INPUT class='frm' size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''"></span>
	<!--
	&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário">
	-->
	</td>

	<td nowrap width='25%'>
		<input class='frm' size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''" />
	</td>
  
	<td >
		<select name="estado" size="1" class='frm'>
			<option value=""   <? if (strlen($estado) == 0) echo " selected "; ?>>TODOS OS ESTADOS</option>
			<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
			<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
			<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
			<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
			<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
			<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
			<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
			<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
			<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
			<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
			<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
			<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
			<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
			<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
			<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
			<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
			<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
			<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
			<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
			<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
			<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
			<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
			<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
			<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
			<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
			<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
			<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
		</select>
	</td>
  </tr>


  <tr>
    <input type='hidden' name='btn_finalizar' value='0'>
    <td colspan="4" class="table_line" style="text-align: center;">	&nbsp;<br>&nbsp;<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;"
 onclick="javascript: document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; "  alt='Clique AQUI para pesquisar'></td>
  </tr>
</table>

</FORM>



<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->

<?
flush()	;

	
//$btn_finalizar = $_POST["btn_finalizar"];

if (strlen($btn_finalizar)>0) {
/*	$data_inicial = $_POST["data_inicial_01"];
	$data_final = $_POST["data_final_01"];
	$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
	$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

	if (strlen($_POST["data_inicial_01"]) == 0) {
		$erro .= "Favor informar a data inicial para pesquisa<br>";
	}
		
	if (strlen($erro) == 0) {
		$data_inicial   = trim($_POST["data_inicial_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}
		
		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}

	if (strlen($_POST["data_final_01"]) == 0) {
		$erro .= "Favor informar a data final para pesquisa<br>";
	}

	if (strlen($erro) == 0) {
		$data_final   = trim($_POST["data_final_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}
			
		if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
	}*/
	
	if (strlen($erro) == 0) {
		$estado = $_POST['estado'];
		$condicao_1 = "1=1";
		if (strlen ($estado) > 0) $condicao_1 = "tbl_posto.estado = '$estado'";

		$sql2 = "SELECT tbl_extrato.extrato,tbl_os_extra.os, tbl_os_extra.custo_pecas
				INTO   TEMP tmp_extrato_$login_admin
				FROM tbl_extrato 
				JOIN  tbl_os_extra USING (extrato)
				WHERE tbl_extrato.fabrica = $login_fabrica
				  AND  tbl_extrato.aprovado BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59';

				CREATE INDEX tmp_extrato_extrato ON tmp_extrato_$login_admin(extrato);

				CREATE INDEX tmp_extrato_os      ON tmp_extrato_$login_admin(os);

				SELECT * FROM tmp_extrato_$login_admin;";

		$res2 = pg_exec ($con,$sql2);

		if (pg_numrows($res2) > 0) {
			$sql = "SELECT tbl_linha.nome AS linha, x.origem, x.pecas, x.mao_de_obra, x.qtde
					FROM tbl_linha
					JOIN (SELECT tbl_produto.linha, tbl_produto.origem,
							SUM (tbl_os.mao_de_obra)       AS mao_de_obra,
							SUM (tmp_extrato_$login_admin.custo_pecas) AS pecas,
							COUNT(tbl_os.os)               AS qtde
						FROM tbl_os
						JOIN tmp_extrato_$login_admin USING (os)
						JOIN tbl_produto  USING (produto)
						JOIN tbl_posto    ON    tbl_os.posto = tbl_posto.posto
						WHERE tbl_os.fabrica = $login_fabrica 
						AND   $condicao_1
						GROUP BY tbl_produto.linha, tbl_produto.origem				
					) x on x.linha = tbl_linha.linha
					ORDER BY tbl_linha.nome, x.origem";

			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<br />";
				echo "<table width='700px' border='0' cellspacing='0' cellpadding='2' align='center' class='formulario'> ";
				echo "	<tr class='titulo_coluna'>";
				echo "		<td height='15'><b>Linha</b></td>";
				echo "		<td height='15'><b>Origem</b></td>";
				echo "		<td height='15'><b>Peças</b></td>";
				echo "		<td height='15'><b>Mão-de-Obra</b></td>";
				echo "		<td height='15'><b>Total</b></td>";
				echo "		<td height='15'><b>Qtde OS</b></td>";
				echo "		<td height='15'><b>R$ / OS</b></td>";
				echo "	</tr>";


				$tot_pecas = 0 ;
				$tot_mao_de_obra = 0 ;
				$tot_qtde = 0 ;

				for($i = 0 ; $i < pg_numrows($res) ; $i++){
					$linha		= pg_result ($res,$i,linha);
					$origem		= pg_result ($res,$i,origem);
					$pecas		= pg_result ($res,$i,pecas);
					$mao_de_obra= pg_result ($res,$i,mao_de_obra);
					$qtde		= pg_result ($res,$i,qtde);

					$cor = "#F7F5F0"; 
					if ($i % 2 == 0) $cor = '#F1F4FA';

					$pecas = round ($pecas,2);
					$mao_de_obra = round ($mao_de_obra,2);

					echo "<tr>";
					echo "<td  bgcolor='$cor' align='left'>$linha</td>";
					echo "<td  bgcolor='$cor' align='left'>$origem</td>";
					echo "<td  bgcolor='$cor' align='right'>" . number_format ($pecas,2,",",".") . "</td>";
					echo "<td  bgcolor='$cor' align='right'>" . number_format ($mao_de_obra,2,",",".") . "</td>";
					echo "<td  bgcolor='$cor' align='right'>" . number_format ($pecas + $mao_de_obra,2,",",".") . "</td>";
					echo "<td  bgcolor='$cor' align='right'>" . number_format ($qtde,0,",",".") . "</td>";
					if ($qtde > 0) {
						echo "<td  bgcolor='$cor' align='right'>" . number_format (($pecas + $mao_de_obra) / $qtde,2,",",".") . "</td>";
					}else{
						echo "<td  bgcolor='$cor' align='center'>-</td>";
					}
					echo "</tr>";
				
					$tot_pecas       += $pecas ;
					$tot_mao_de_obra += $mao_de_obra ;
					$tot_qtde        += $qtde ;
				}
				echo "	<tr>";
				echo "		<td height='15' colspan='2'><b>TOTAIS</b></td>";
				echo "		<td height='15' align='right'><b>" . number_format ($tot_pecas,2,",",".") . "</b></td>";
				echo "		<td height='15' align='right'><b>" . number_format ($tot_mao_de_obra,2,",",".") . "</b></td>";
				echo "		<td height='15' align='right'><b>" . number_format ($tot_pecas + $tot_mao_de_obra,2,",",".") . "</b></td>";
				echo "		<td height='15' align='right'><b>" . number_format ($tot_qtde,0,",",".") . "</b></td>";
				if ($tot_qtde > 0) {
					echo "<td align='right'><b>" . number_format (($tot_pecas + $tot_mao_de_obra) / $tot_qtde,2,",",".") . "</b></td>";
				}else{
					echo "<td align='center'>-</td>";
				}
				echo "	</tr>";

				
			}
			else{
				echo "<tr><td><font style='font:bold 16px Arial; color:#596D9B'>Nenhum resultado encontrado.</td></tr>";
			}
		}
	else{
			echo "<tr><td><font style='font:bold 16px Arial; color:#596D9B'>Nenhum resultado encontrado.</td></tr>";
		}
		echo "</table>";
	}
}

?>

<p>

<? include "rodape.php" ?>
