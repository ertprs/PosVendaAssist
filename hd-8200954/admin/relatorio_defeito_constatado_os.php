<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
$admin_privilegios="gerencia";
include "autentica_admin.php";


##### Função para exibe os Estados #####
function selectUF($selUF=""){
	$cfgUf = array("","AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PI","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO");
	if($selUF == "") $selUF = $cfgUf[0];

	$totalUF = count($cfgUf) - 1;
	for($currentUF=0; $currentUF <= $totalUF; $currentUF++){
		echo "                      <option value=\"$cfgUf[$currentUF]\"";
		if($selUF == $cfgUf[$currentUF]) print(" selected");
		echo ">$cfgUf[$currentUF]</option>\n";
	}
}


$situacao =$_POST["situacao"];
$chk_data =$_POST["chk_data"];
$data_inicial =$_POST["data_inicial"];
$data_final =$_POST["data_final"];
$chk_produto =$_POST["chk_produto"];
$produto_referencia =$_POST["produto_referencia"];
$produto_nome = $_POST["produto_nome"];
$aux_produto_referencia =$_POST["produto_referencia"];
$aux_produto_nome = $_POST["produto_nome"];
$chk_defeito_reclamado = $_POST["chk_defeito_reclamado"];
$aux_defeito_reclamado = $_POST["defeito_reclamado"];
$defeito_reclamado_descricao = $_POST["defeito_reclamado_descricao"];
$chk_defeito_constatado = $_POST["chk_defeito_constatado"];
$aux_defeito_constatado = $_POST["defeito_constatado"];
$chk_familia = $_POST["chk_familia"];
$aux_familia = $_POST["familia"];
$x_data_inicial = fnc_formata_data_pg($data_inicial);
$x_data_final = fnc_formata_data_pg($data_final);

$layout_menu = "gerencia";
$title = "Relatório de Defeito Constatado por Ordens de Serviços";

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

function CarregaDefeito (campo, tipo) {
	if (tipo == "reclamado") {
		RemoveDefeito ("defeito_reclamado");
		document.all.FrameDefeito.src = "carrega_defeitos.php?reclamado_familia=" + campo.value + "&tipo=reclamado";
	}
	if (tipo == "constatado") {
		RemoveDefeito ("defeito_constatado");
		document.all.FrameDefeito.src = "carrega_defeitos.php?constatado_familia=" + campo.value + "&tipo=constatado";
	}
}

function RemoveDefeito (objeto) {
	var tamanho = document.frm_consulta[objeto].length;
	while (tamanho > 0) {
		document.frm_consulta[objeto].remove(tamanho-1);
		tamanho--;
	}
}

function AdicionaDefeito (texto, valor, objeto) {
	linha = document.createElement("option");
	linha.text = texto;
	linha.value = valor;
	document.frm_consulta[objeto].add(linha);
}
</script>

<style type="text/css">
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
</style>

<? include "javascript_pesquisas.php" ?>
<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>
<br>

<form name="frm_consulta" method="post" action="<? $PHP_SELF ?>">

<table width="500" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="table_line">
		<td colspan="5" class='Titulo' background='imagens_admin/azul.gif'>Selecione os parâmetros para a pesquisa</td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><br><center><img src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_consulta.submit();" style="cursor: pointer;" alt="Preencha as opções e clique aqui para pesquisar"></center></td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="table_line">
		<td>&nbsp;</td>
		<td colspan="2">Situação da OS</TD>
		<td>
			<select name="situacao" size="1" class="frm">
				<option value="" selected>Todas</option>
				<option value="IS NULL">Em Aberto</option>
				<option value="NOTNULL">Fechadas</option>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>

	<tr class="table_line">
		<td>&nbsp;</td>
		<td><input type="checkbox" name="chk_data" value="1" class="frm" <?if(strlen($chk_data) > 0 ) echo "CHECKED";?>> Entre Datas</td>
		<td align='left' nowrap>Data Inicial<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';"></td>
		<td nowrap>Data Final<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';"></td>
		<td>&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="table_line">
		<td>&nbsp;</TD>
		<td><input type="checkbox" name="chk_produto" value="1" class="frm" <?if(strlen($chk_produto) > 0 ) echo "CHECKED";?>> Produto</td>
		<td>Referência</td>
		<td>Descrição</td>
		<td>Voltagem</TD>
	</tr>
	<tr class="table_line">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><input type="text" name="produto_referencia" value="<? echo $aux_produto_referencia;?>" size="8" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_nome,'referencia',document.frm_consulta.produto_voltagem)" <? } ?> class="frm"> <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_nome,'referencia',document.frm_consulta.produto_voltagem)" ></td>
		<td><input type="text" name="produto_nome" value="<? echo $aux_produto_nome;?>" size="18" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_nome,'descricao',document.frm_consulta.produto_voltagem)" <? } ?> class="frm"> <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_nome,'descricao',document.frm_consulta.produto_voltagem)" ></td>
		<td><input type="text" name="produto_voltagem" size="7" class="frm"></td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>
		<tr class="table_line">
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_defeito_reclamado" value="1" class="frm" <?if(strlen($chk_defeito_reclamado) > 0 ) echo "CHECKED";?>> Defeito Reclamado</td>
		<td colspan="2">
			Selecione o Defeito<br>
		<?
		$sql ="SELECT defeito_reclamado, descricao from tbl_defeito_reclamado where fabrica=$login_fabrica and ativo='t' order by descricao";
		$res = pg_exec ($con,$sql);
		echo "<select name='defeito_reclamado' id='defeito_reclamado' class='frm' style='width: 215px;' onchange=\"javascript: document.getElementById('defeito_reclamado_descricao').value=''\">";
		echo "<option value='0'></option>";
			for ($y = 0 ; $y < pg_numrows($res) ; $y++){
				$defeito_reclamado          = trim(pg_result($res,$y,defeito_reclamado));
				$descricao = trim(pg_result($res,$y,descricao));
				echo "<option value='$defeito_reclamado'";  if ($defeito_reclamado == $aux_defeito_reclamado) echo " SELECTED "; echo ">$descricao</option>";
			}
?>			</select><br>OU<br><input type='text' name='defeito_reclamado_descricao' size ='30' value='' onblur="javascript: if(this.value.length > 0){ document.getElementById('defeito_reclamado').value='' ; }" id='defeito_reclamado_descricao'>
		</td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="table_line">
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_defeito_constatado" value="1" class="frm" <?if(strlen($chk_defeito_constatado) > 0 ) echo "CHECKED";?>> Defeito Constatado</td>
		<td colspan="2">
			Defeito<br>
		<?
		$sql ="SELECT defeito_constatado, descricao from tbl_defeito_constatado where fabrica=$login_fabrica and ativo='t' order by descricao";
		$res = pg_exec ($con,$sql);
		echo "<select name='defeito_constatado' class='frm' style='width: 209px;'>";
		echo "<option value='0'></option>";
			for ($y = 0 ; $y < pg_numrows($res) ; $y++){
				$defeito_constatado          = trim(pg_result($res,$y,defeito_constatado));
				$descricao = trim(pg_result($res,$y,descricao));
				echo "<option value='$defeito_constatado'";  if ($defeito_constatado == $aux_defeito_constatado) echo " SELECTED "; echo ">$descricao - $defeito_constatado</option>";
			}
?>			</select>

		</td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="table_line">
		<td>&nbsp;</td>
		<td colspan="2"><input type="checkbox" name="chk_familia" value="1" class="frm" <?if(strlen($chk_familia) > 0 ) echo "CHECKED";?>> Família</td>
		<td colspan="2">
			Família<br>
			<select name="familia" size="1" class="frm" style="width: 209px">
			<option value=""></option>
			<?
			$sql =	"SELECT  tbl_familia.familia   ,
							 tbl_familia.descricao
					FROM     tbl_familia
					WHERE    tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			$res_familia = pg_exec($con,$sql);

			if (pg_numrows($res_familia) > 0) {
				for ($i = 0 ; $i < pg_numrows($res_familia) ; $i++) {
					$familia           = pg_result($res_familia,$i,familia);
					$familia_descricao = pg_result($res_familia,$i,descricao);
					echo "<option value='$familia'";
					if ($familia == $aux_familia) echo " SELECTED ";
					echo ">" . substr($familia_descricao, 0, 23) . "</option>\n";
				}
			}
			?>
			</select>
		</td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>

	<tr class="table_line">
		<td colspan="5"><center><img border="0" src="imagens_admin/btn_pesquisar_400.gif" onclick="document.frm_consulta.submit();" style="cursor: pointer;" alt="Preencha as opções e clique aqui para pesquisar"></center></td>
	</tr>
	<tr class="table_line">
		<td colspan="5"><hr color="#EEEEEE"></td>
	</tr>
</table>

<iframe style="visibility: hidden; position: absolute;" id="FrameDefeito"></iframe>

</form>
<BR>
<?


/*
echo "sit:$situacao<BR>";
echo "data: $chk_data - $x_data_inicial - $x_data_final<BR>";
echo "prod: $chk_produto - $produto_referencia des: $produto_nome<BR>";
echo "consta: $chk_defeito_constatado - $defeito_constatado<BR>";
echo "familia $chk_familia - $familia<BR>";*/
if ((strlen($chk_data)>0) OR (strlen($chk_produto)>0) or (strlen($chk_defeito_constatado)>0) or(strlen($chk_familia)>0) or strlen($chk_defeito_reclamado) > 0){
	if(strlen($chk_produto)>0){
		$sql="SELECT DISTINCT produto
				FROM tbl_produto JOIN tbl_linha USING(linha)
				WHERE referencia='$produto_referencia'
				AND   fabrica = $login_fabrica";
		$res = @pg_exec ($con,$sql);
		$cod_produto   = pg_result ($res,0,produto) ;
	}

	$sql = "SELECT 	tbl_os.os,
					tbl_os.sua_os,
					tbl_defeito_constatado.descricao as defeito_constatado,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura       ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento     ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI:SS') AS finalizada     ,
					tbl_posto_fabrica.codigo_posto                     AS codigo_posto   ,
					tbl_posto.nome                                     AS posto_nome     ,
					tbl_os.produto                                                       ,
					tbl_os.serie                                                         ,
					tbl_produto.familia                                                  ,
					tbl_produto.referencia_pesquisa                    AS referencia     ,
					tbl_servico_realizado.descricao       AS servico_realizado_descricao ,
					tbl_produto.descricao     as produto_descricao                       ,
					case when tbl_os.defeito_reclamado IS NOT NULL then tbl_defeito_reclamado.descricao else tbl_os.defeito_reclamado_descricao end as defeito_reclamado_descricao
			FROM tbl_os
			JOIN	tbl_produto         ON  tbl_os.produto = tbl_produto.produto
			JOIN	tbl_posto           ON  tbl_os.posto   = tbl_posto.posto
			JOIN	tbl_posto_fabrica   ON  tbl_posto.posto= tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica";
			if ($login_fabrica == 14) {
			$sql .= " JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os.solucao_os ";
			}
			$sql .= "JOIN tbl_defeito_constatado  on tbl_os.defeito_constatado =  tbl_defeito_constatado.defeito_constatado
			LEFT JOIN tbl_defeito_reclamado  on tbl_os.defeito_reclamado =  tbl_defeito_reclamado.defeito_reclamado
			WHERE tbl_os.fabrica = $login_fabrica ";
				if(strlen($situacao)>0){ $sql .=" and tbl_os.data_fechamento $situacao ";}
				if(strlen($chk_data)>0){ $sql .=" AND (data_abertura::date BETWEEN $x_data_inicial AND $x_data_final)  ";}
				if(strlen($chk_produto)>0){ $sql .=" AND tbl_os.produto=$cod_produto ";}
				if(strlen($chk_defeito_reclamado)>0){
					if(strlen($defeito_reclamado) > 0) {
						$sql .=" AND tbl_os.defeito_reclamado= $aux_defeito_reclamado ";
					}elseif(strlen($defeito_reclamado_descricao) > 0) {
						$sql .=" AND tbl_os.defeito_reclamado_descricao ilike  '$defeito_reclamado_descricao' AND tbl_os.defeito_reclamado_descricao IS NOT NULL ";
					}
				}
				if(strlen($chk_defeito_constatado)>0){ $sql .=" AND tbl_os.defeito_constatado= $aux_defeito_constatado ";}
				if(strlen($chk_familia)>0){ $sql .=" AND tbl_produto.familia=$aux_familia ";}
				$sql .="order by tbl_defeito_constatado.descricao,tbl_os.produto, tbl_os.data_abertura, tbl_os.data_fechamento";
	$res = pg_exec ($con,$sql);
			//echo "<BR>$sql";
	if(pg_numrows($res) > 0) {
		echo "<font size=1>";
		echo "Foram encontrados "; echo pg_numrows($res); echo " resultado(s)";
		echo "</font>";
		echo "<table width='700' border='0' bgcolor='#485989' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 10px'>";
		echo "<tr height='25'>";
		echo "<td><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Abertura</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Fechamento</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Série</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Defeito Reclamado</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Defeito Constatado</B></font></td>";
		if ($login_fabrica == 14) {
		echo "<td><font color='#FFFFFF'><B>Solução</B></font></td>";
		}
		echo "</tr>";
		for ($y = 0 ; $y < pg_numrows($res) ; $y++){
			$os           		= trim(pg_result($res,$y,os));
			$sua_os           	= trim(pg_result($res,$y,sua_os));
			$defeito_constatado = trim(pg_result($res,$y,defeito_constatado));
			$abertura 			= trim(pg_result($res,$y,abertura));
			$fechamento 		= trim(pg_result($res,$y,fechamento));
			$finalizada 		= trim(pg_result($res,$y,finalizada));
			$codigo_posto 		= trim(pg_result($res,$y,codigo_posto));
			$posto_nome 		= trim(pg_result($res,$y,posto_nome));
			$produto_descricao  = trim(pg_result($res,$y,produto_descricao));
			$serie              = trim(pg_result($res,$y,serie));
			$defeito_reclamado_descricao = trim(pg_result($res,$y,defeito_reclamado_descricao));
			$servico_realizado_descricao = trim(pg_result($res,$y,servico_realizado_descricao));

			$cor = ($y % 2 == 0) ? "#FFFFFF": '#f4f7fb';
			echo "<tr bgcolor='$cor'>";
			echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</A></td>";
			echo "<td align='left'>$codigo_posto - $posto_nome</td>";
			echo "<td>$abertura</td>";
			echo "<td>$fechamento</td>";
			echo "<td>$produto_descricao</td>";
			echo "<td>$serie</td>";
			echo "<td>$defeito_reclamado_descricao</td>";
			echo "<td>$defeito_constatado</td>";
			if ($login_fabrica == 14) {
			echo "<td>$servico_realizado_descricao</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<center>Nenhum Resultado Encontrado</center>";
	}
}else{
		echo "<center>Selecione os parametros para consulta</center>";
}
		?>


<br>

<? include "rodape.php" ?>
