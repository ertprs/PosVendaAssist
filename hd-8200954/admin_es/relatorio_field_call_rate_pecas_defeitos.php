<?
//alterado por takashi 20-09-06 nao batia valor de peças com produtos.. arquivo anterior... relatorio_field_call_rate_pecas_defeitos-ant_20-09-06.php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia,auditoria";

#$gera_automatico = trim($_GET["gera_automatico"]);

#if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
#}

#include "gera_relatorio_pararelo_include.php";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
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


// Criterio padrão
$_POST["criterio"] = "data_digitacao";
//////////////////

if ($btn_acao == 1) {
	if (strlen($msg_erro) == 0) {

		if (strlen(trim($_POST["data_inicial_01"])) > 0) $data_inicial_01 = trim($_POST["data_inicial_01"]);
		if (strlen(trim($_GET["data_inicial_01"])) > 0)  $data_inicial_01 = trim($_GET["data_inicial_01"]);


		if (strlen($data_inicial_01) == 0) {
			$msg_erro .= "Favor informar a data inicial para pesquisa<br>";
		}

		if (strlen($msg_erro) == 0) {
			$data_inicial = $data_inicial_01;
			$fnc          = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				//$msg_erro = pg_errormessage ($con) ;
				$msg_erro .= "Entre com uma data válida!";
			}

			if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
		}
	}

	if (strlen($msg_erro) == 0) {

		if (strlen(trim($_POST["data_final_01"])) > 0) $data_final_01 = trim($_POST["data_final_01"]);
		if (strlen(trim($_GET["data_final_01"])) > 0)  $data_final_01 = trim($_GET["data_final_01"]);


		if (strlen($data_final_01) == 0) {
			$msg_erro .= "Favor informar a data final para pesquisa<br>";
		}

		if (strlen($msg_erro) == 0) {
			$data_final   = $data_final_01;
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}

			if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
		}
	}

	if(strlen($aux_data_incial)>0 AND strlen($aux_data_final)>0){
		$sql = "select '$aux_data_final'::date - '$aux_data_inicial'::date ";
		$res = pg_exec($con,$sql);
		if(pg_result($res,0,0)>31){
			$msg_erro .= "Período não pode ser maior que 30 dias";
		}
	}

	if (strlen(trim($_POST["estado"])) > 0) $estado = trim($_POST["estado"]);
	if (strlen(trim($_GET["estado"])) > 0)  $estado = trim($_GET["estado"]);

	if (strlen(trim($_POST["pais"])) > 0) $pais = trim($_POST["pais"]);
	if (strlen(trim($_GET["pais"])) > 0)  $pais = trim($_GET["pais"]);

	$pais = $login_pais;

	if (strlen(trim($_POST["origem"])) > 0) $origem = trim($_POST["origem"]);
	if (strlen(trim($_GET["origem"])) > 0)  $origem = trim($_GET["origem"]);

	if (strlen(trim($_POST["codigo_posto"])) > 0) $codigo_posto = trim($_POST["codigo_posto"]);
	if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto = trim($_GET["codigo_posto"]);

	if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
	if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);

	if (strlen(trim($_POST["peca_descricao"])) > 0) $peca_descricao = trim($_POST["peca_descricao"]);    //hd 2003 TAKASHI
	if (strlen(trim($_GET["peca_descricao"])) > 0)  $peca_descricao = trim($_GET["peca_descricao"]);     //hd 2003 TAKASHI

	if (strlen(trim($_POST["peca_referencia"])) > 0) $peca_referencia = trim($_POST["peca_referencia"]); //hd 2003 TAKASHI
	if (strlen(trim($_GET["peca_referencia"])) > 0)  $peca_referencia = trim($_GET["peca_referencia"]);  //hd 2003 TAKASHI

	if (strlen(trim($_POST["tipo_os"])) > 0) $tipo_os = trim($_POST["tipo_os"]);
	if (strlen(trim($_GET["tipo_os"])) > 0)  $tipo_os = trim($_GET["tipo_os"]);

	if (strlen(trim($_POST["linha"])) > 0) $linha = trim($_POST["linha"]);
	if (strlen(trim($_GET["linha"])) > 0)  $linha = trim($_GET["linha"]);

	if (strlen(trim($_POST["produto"])) > 0) $produto = trim($_POST["produto"]);
	if (strlen(trim($_GET["produto"])) > 0)  $produto = trim($_GET["produto"]);

	if (strlen(trim($_POST["os_produto"])) > 0) $os_produto = trim($_POST["os_produto"]);
	if (strlen(trim($_GET["os_produto"])) > 0)  $os_produto = trim($_GET["os_produto"]);

	if (strlen(trim($_POST["troca"])) > 0) $troca = trim($_POST["troca"]);
	if (strlen(trim($_GET["troca"])) > 0)  $troca = trim($_GET["troca"]);

	if (strlen(trim($_POST["produto_referencia"])) > 0) $produto_referencia = trim($_POST["produto_referencia"]); // HD 2003 TAKASHI
	if (strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);  // HD 2003 TAKASHI

	if (strlen(trim($_POST["produto_descricao"])) > 0) $produto_descricao = trim($_POST["produto_descricao"]);    // HD 2003 TAKASHI
	if (strlen(trim($_GET["produto_descricao"])) > 0)  $produto_descricao = trim($_GET["produto_descricao"]);     // HD 2003 TAKASHI

	if (strlen(trim($_POST["pagamento"])) > 0) $pagamento = trim($_POST["pagamento"]);
	if (strlen(trim($_GET["pagamento"])) > 0)  $pagamento = trim($_GET["pagamento"]);


	if(strlen($estado) > 0){
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}

	if(strlen($peca_descricao)>0 and strlen($peca_referencia)>0){//hd 2003 TAKASHI
		$sql = "SELECT tbl_peca.peca 
				FROM tbl_peca 
				WHERE tbl_peca.fabrica = $login_fabrica 
				AND tbl_peca.referencia = '$peca_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$peca = pg_result($res,0,peca);
		}
	}

	if(strlen($linha) > 0){
		if ($login_fabrica <> 14) {
			$sqlX =	"SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica and linha = $linha;";
			$resX = pg_exec($con,$sqlX);
			if (pg_numrows($resX) == 1) {
				$linha_nome = trim(pg_result($resX,0,0));
			}
			$mostraMsgLinha = "<br>na Línea $linha_nome";
		}else{
			$sqlX =	"SELECT descricao FROM tbl_familia WHERE fabrica = $login_fabrica and familia = $linha;";
			$resX = pg_exec($con,$sqlX);
			if (pg_numrows($res) == 1) $linha_nome = trim(pg_result($resX,0,0));
			$mostraMsgLinha = "<br>na FAMILIA $linha_nome";
		}
		if (strlen($estado) > 0) $mostraMsgLinha .= " e ";
	}


	if (strlen($msg_erro) == 0 && $login_fabrica == 14) {
		if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto_fabrica.posto        ,
							tbl_posto_fabrica.codigo_posto ,
							tbl_posto.nome
					FROM tbl_posto
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
			if (strlen($posto_codigo) > 0)
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
			if (strlen($posto_nome) > 0)
				$sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%'";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = pg_result($res,0,posto);
				$posto_codigo = pg_result($res,0,codigo_posto);
				$posto_nome   = pg_result($res,0,nome);

				$mostraMsgPosto = "<br>no POSTO $posto_codigo - $posto_nome";
			}else{
				$msg_erro .= " Posto não encontrado<br>";
			}
		}
	}

	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){ // HD 2003 TAKASHI
		$sql = "SELECT produto 
			FROM  tbl_produto 
			JOIN  tbl_familia using(familia)
			WHERE tbl_familia.fabrica    = $login_fabrica
			AND   tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
		}
	}

	if (strlen($msg_erro) > 0) {
		$data_inicial = $data_inicial_01;
		$data_final   = $data_final_01;
		$msg_erro  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>".$msg_erro;
	}

}

$layout_menu = "gerencia";
$title = "REPORTES - FIELD CALL-RATE : PIEZAS ";

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

function AbreDefeitoPeca(peca,data_inicial,data_final,estado,pais,origem, posto, pagamento){
	janela = window.open("relatorio_field_call_rate_defeitos2.php?peca=" + peca
+ "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&estado=" +
estado+"&pais=" + pais+"&origem=" + origem +"&posto=" + posto + "&pagamento="+pagamento+"&tipo_os= <?echo $tipo_os;?>"
,"peca",'resizable=1,scrollbars=yes,width=750,height=450,top=50,left=50');
	janela.focus();
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
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
-->
</style>


<script type="text/javascript" src="../admin/js/jquery-latest.pack.js"></script>
<link rel="stylesheet" type="text/css" href="../admin/js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="../admin/js/datePicker.v1.js"></script>
<script type="text/javascript" src="../admin/js/jquery.maskedinput.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='../admin/js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="../admin/js/jquery.autocomplete.css" />
<script type='text/javascript' src='../admin/js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='../admin/js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
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


<?
	#if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	#	include "gera_relatorio_pararelo.php";
	#}

	#if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	#	include "gera_relatorio_pararelo_verifica.php";
	#}

?>

<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg_erro) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg_erro ?>

	</td>
</tr>
</table>

<br>
<?
}
?>

<br>

<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Consulta</caption>

<TBODY>
<TR>
	<TD>Fecha Inicial<br><INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>"></TD>
	<TD>Fecha Final<br><INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" ></TD>
</TR>

<? if ($login_fabrica == 14) { ?>
<TR>
	<td colspan = '2'>
		Por familia<br>
		<?
		$sql =	"SELECT *
				FROM tbl_familia
				WHERE fabrica = $login_fabrica
				ORDER BY descricao;";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			echo "<select name='linha' size='1' class='frm' >";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$aux_linha = trim(pg_result($res,$i,familia));
				$aux_nome  = trim(pg_result($res,$i,descricao));
				echo "<option value='$aux_linha'";
				if ($linha == $aux_linha) echo " selected";
				echo ">$aux_nome</option>";
			}
			echo "</select>";
		}
		?>
	</td>
</TR>
<? }else{ ?>
<TR>
	<td colspan = '2'>
	Línea<br>
	<?
		$w = "";
		// HD 2670 - IGOR - PARA A TECTOY, NÃO MOSTRAR A LINHA GERAL, QUE VAI SER EXCLUIDA
		if($login_fabrica==6){
			$w = " AND linha<>39 ";
		}

		$sql =	"SELECT *
				FROM tbl_linha
				WHERE fabrica = $login_fabrica
				$w
				ORDER BY nome;";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			echo "<select name='linha' size='1' class='frm' >";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$aux_linha = trim(pg_result($res,$i,linha));
				$aux_nome  = trim(pg_result($res,$i,nome));
				echo "<option value='$aux_linha'";
				if ($linha == $aux_linha) echo " selected";
				echo ">$aux_nome</option>";
			}
			echo "</select>";
		}
	?>
	</td>
</TR>
<? } ?>
<?if($login_fabrica==3 OR $login_fabrica == 15){?>
<tr>
	<td colspan = '2'>
	Por marca<br>
			<?
			$sql = "SELECT  *
					FROM    tbl_marca
					WHERE   tbl_marca.fabrica = $login_fabrica 
					ORDER BY tbl_marca.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='marca' class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_marca = trim(pg_result($res,$x,marca));
					$aux_nome  = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_marca'"; 
					if ($marca == $aux_marca){
						echo " SELECTED "; 
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
          	?>
	</TD>
</TR>
<?}?>

<? if($login_fabrica==20){?>
<TR>
	<TD>Cód. de Pieza<br><input class='frm' type="text" name="peca_referencia" value="<? echo $referencia_de ?>" size="15" maxlength="20">&nbsp;<img src="imagens/btn_lupa.gif" onclick='fnc_pesquisa_peca (document.frm_pesquisa.peca_referencia, document.frm_pesquisa.peca_descricao,"referencia")' style='cursor:pointer'></TD>
	<TD>Descripción de Pieza<br><input class='frm' type="text" name="peca_descricao" value="<? echo $descricao_de ?>" size="15" maxlength="50">&nbsp;<img src="imagens/btn_lupa.gif" onclick='fnc_pesquisa_peca (document.frm_pesquisa.peca_referencia, document.frm_pesquisa.peca_descricao,"descricao")' style='cursor:pointer'></TD>
</TR>
<? } ?>



<? if($login_fabrica==24){ ?>
	<TR>
	<TD colspan = '2'> 
		Por tipo<br>
		<select name="tipo_os" size="1" class='frm'>
			<option value=""></option>
			<option value="C">Consumidor</option>
			<option value="R">Revenda</option>
		</select>
	</TD>
</TR>
<? } ?>

<?  
	if($login_fabrica <> 14){?>
		<tr>
			<td>
				Cód. Servicio<br>
				<input type="text" name="codigo_posto" id="posto_codigo" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
				<img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
			</td>
			<td align='left'>
				Nombre del Servicio<br>
				<input type="text" name="posto_nome" id="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens/btn_lupa.gif" style="cursor: hand;" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
			</td>
		</tr>
	<?}?>
	<tr>
		<td >Cód. Herramienta<br>
		<input type="text" name="produto_referencia" size="10" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" > 
		<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">
		</td>
		<td>Descripción de la Herramienta<br>
		<input type="text" name="produto_descricao" size="25" class='frm' value="<? echo $produto_descricao ?>" >
		<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
		</td>
	</tr>
<? if ($login_fabrica == 14) { ?>
<TR>
	<TD nowrap>
			Código do Posto<br>
			<input type="text" class='frm' name="posto_codigo" id="posto_codigo" size="10" value="<?echo $posto_codigo?>">
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'codigo')" alt="Clique aqui para pesquisar os postos pelo Código" style="cursor: hand;">
	</TD>
	<TD nowrap>
			Razão Social do Posto<br>
			<input type="text" class='frm' name="posto_nome" id="posto_nome" size="25" value="<?echo $posto_nome?>">
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'nome')" alt="Clique aqui para pesquisar os postos pela Razão Social" style="cursor: hand;">
	</TD>
</TR>

<? } ?>

	<TR>
		<TD colspan = '2'>
		<input type='checkbox' name='troca' value='t' <? if (strlen($troca) > 0) echo "checked"; ?>><? if($login_fabrica==20) echo "Accesorios"; else echo "Troca de Peça";?></TD>
	</TR>
<? if($login_fabrica==20){?>
	<TR>
		<TD colspan = '2'><input type='checkbox' name='pagamento' value='t' <? if (strlen($pagamento) > 0) echo "checked"; ?>> Por fecha de pago</TD>
	</TR>
<?}?>
	<TR>
		<TD colspan="2">
			<input type='hidden' name='btn_acao' value=''>
			<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer ">
		</TD>
	</TR>
</TABLE>

</FORM>

</DIV>

<?
flush();

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {

	if(strlen($pagamento)>0){
		$data_consulta = " tbl_extrato_extra.exportado ";
	}else{
		$data_consulta = "tbl_extrato.data_geracao";
	}

	//hd 9926 Paulo estava so para fabrica 20
	if(strlen($codigo_posto)>0){
		$sql = "SELECT  posto
			FROM    tbl_posto_fabrica 
			WHERE fabrica = $login_fabrica and codigo_posto = '$codigo_posto';";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$posto = trim(pg_result($res,0,posto));
		}
	}


	if (strlen ($posto)    > 0) $cond_1 = " AND tbl_extrato.posto         = $posto ";
	if (strlen ($tipo_os)  > 0) $cond_2 = " AND tbl_os.consumidor_revenda = '$tipo_os' ";
	if (strlen ($pais)     > 0) $cond_3 = " AND tbl_posto.pais            = '$pais' ";

	if (strlen ($origem)     > 0) $cond_origem = " AND tbl_produto.origem    = '$origem' ";

	if (strlen($estado)    > 0) $cond_4 = " AND tbl_posto.estado          = '$estado' ";
	if (strlen($linha) > 0) {
		$cond_5 = "";
		if ($login_fabrica == 14) $cond_5 .= " AND tbl_produto.familia = $linha ";
		else                      $cond_5 .= " AND tbl_produto.linha = $linha ";
	}
	if ($login_fabrica ==20){
		$cond_6 = "";
		if (strlen($troca) > 0) $cond_6 .= " AND tbl_peca.acessorio IS TRUE ";
		if (strlen($peca)  > 0) $cond_6 .= " AND tbl_peca.peca = $peca "; //hd 2003 TAKASHI
	}
	else {
		if (strlen($troca) > 0) $cond_6 .= " AND tbl_servico_realizado.troca_de_peca IS TRUE ";
	}
	if(strlen($produto)>0) $cond_7 = " AND tbl_os.produto = $produto ";
	if(strlen($marca)>0) $cond_8 = " AND tbl_produto.marca   = $marca ";

	$descricao_idioma = "";
	$join_idioma      = "";
	$group_by_idioma  = "";

	if($login_fabrica == 20 and $pais !='BR'){
		$descricao_idioma = " tbl_peca_idioma.descricao as descricao_espanhol,";
		$join_idioma      =" LEFT JOIN tbl_peca_idioma ON tbl_peca.peca = tbl_peca_idioma.peca and tbl_peca_idioma.idioma='ES' ";
		$group_by_idioma  = " ,tbl_peca_idioma.descricao ";
	}
	if (strlen ($pais)     > 0){
		$sql = "SELECT posto 
				INTO TEMP temp_pais_posto_$login_admin 
				FROM tbl_posto 
				JOIN tbl_posto_fabrica USING (posto) 
				WHERE fabrica = $login_fabrica 
					AND pais = '$pais';
	
				CREATE INDEX temp_pais_posto_POSTO_$login_admin ON temp_pais_posto_$login_admin(posto);
			";
		$res = pg_exec($con,$sql);
		$join_pais = "JOIN temp_pais_posto_$login_admin PO ON PO.posto = tbl_extrato.posto";
	}

if($login_fabrica ==14)$sql_14 = " AND tbl_extrato.liberado IS NOT NULL ";

	$sql = "
		SELECT os 
		INTO TEMP temp_fcrposto_$login_admin
		FROM tbl_os_extra
		JOIN tbl_extrato       ON tbl_os_extra.extrato = tbl_extrato.extrato 
		JOIN tbl_extrato_extra ON tbl_extrato.extrato  = tbl_extrato_extra.extrato
		$join_pais
		WHERE $data_consulta BETWEEN '$aux_data_inicial' AND '$aux_data_final'
		$sql_14
		AND tbl_extrato.fabrica = $login_fabrica
		$cond_1;

		CREATE INDEX temp_fcrposto_OS_$login_admin on temp_fcrposto_$login_admin(os);

		SELECT  SUM(tbl_os_item.qtde) AS ocorrencia,
					tbl_peca.peca      ,
					tbl_peca.referencia,
					$descricao_idioma 
					tbl_peca.descricao
		FROM    tbl_os
		JOIN    tbl_produto     ON tbl_os.produto         = tbl_produto.produto
		JOIN    tbl_os_produto  ON tbl_os_produto.os      = tbl_os.os
		JOIN    tbl_os_item     ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		JOIN    tbl_peca        ON tbl_os_item.peca       = tbl_peca.peca
		JOIN    temp_fcrposto_$login_admin fcr ON fcr.os = tbl_os.os
		$join_idioma 
		JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
		LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
		WHERE tbl_os.fabrica = $login_fabrica
		$cond_2
		$cond_3
		$cond_4
		$cond_5
		$cond_6
		$cond_7
		$cond_8
		$cond_origem
		GROUP BY tbl_peca.peca, 
			 tbl_peca.descricao, 
			 tbl_peca.referencia
			 $group_by_idioma 
		ORDER BY ocorrencia DESC";



/*
	$sql = "SELECT  SUM(tbl_os_item.qtde) AS ocorrencia   ,
					tbl_peca.peca      ,
					tbl_peca.referencia,
					$descricao_idioma 
					tbl_peca.descricao
			FROM    tbl_os
			JOIN    tbl_os_produto  ON tbl_os_produto.os      = tbl_os.os
			JOIN    tbl_os_item     ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN    tbl_peca        ON tbl_os_item.peca       = tbl_peca.peca 
			$join_idioma ";


if($login_fabrica==14){ $sql .=" 
			join tbl_os_extra on tbl_os.os=tbl_os_extra.os
			join tbl_extrato       USING(extrato)"; 
}
if($login_fabrica == 20 AND strlen($pagamento)>0){
	$sql .=" JOIN tbl_os_extra on tbl_os.os=tbl_os_extra.os
			JOIN tbl_extrato       USING(extrato)
			JOIN tbl_extrato_extra USING(extrato) ";
}
$sql .= " LEFT JOIN tbl_os_status ON tbl_os_status.os       = tbl_os.os ";
	//HD 3195  - JOIN na tbl_posto, pois agora que tem diferença de pais deve-se selecionar através dos postos(tbl_posto.pais)
	if($login_fabrica == 20){
		$sql .=" JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto and $cond_3 ";
	}else{
		if (strlen($estado) > 0) $sql .= "JOIN tbl_posto             ON tbl_posto.posto                         = tbl_os.posto ";
	}
	if (strlen($linha) > 0)  $sql .= "JOIN tbl_produto           ON tbl_produto.produto                     = tbl_os.produto ";
	if (strlen($troca) > 0)  $sql .= "JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os.solucao_os ";
if($login_fabrica==14){ $sql .=" WHERE tbl_extrato.data_geracao BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";}else{
	$sql .= " WHERE   $data_consulta BETWEEN '$aux_data_inicial' AND '$aux_data_final'";}
	$sql .= " AND     tbl_os.fabrica = $login_fabrica and $cond_5
			AND    (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL) ";

	if (strlen($estado) > 0) $sql .= " AND tbl_posto.estado = '$estado' ";
	if (strlen($linha) > 0) {
		if ($login_fabrica == 14) $sql .= " AND tbl_produto.familia = $linha ";
		else                      $sql .= " AND tbl_produto.linha = $linha ";
	}
	if (strlen($posto) > 0) $sql .= " AND tbl_os.posto = $posto ";
	if ($login_fabrica ==20){
		if (strlen($troca) > 0) $sql .= " AND tbl_peca.acessorio IS TRUE ";
		if (strlen($peca)  > 0) $sql .= " AND tbl_peca.peca = $peca "; //hd 2003 TAKASHI
	}
	else { 
		if (strlen($troca) > 0) $sql .= " AND tbl_servico_realizado.troca_de_peca IS TRUE ";
	}

	if($login_fabrica ==20 ){
		$sql .= "and $cond_6";
	}

	$sql .=	"GROUP BY tbl_peca.peca, 
				tbl_peca.descricao, 
				tbl_peca.referencia
				$group_by_idioma 
			ORDER BY ocorrencia DESC";
*/
//echo nl2br($sql); exit;

//if ($ip=="189.47.44.88") echo "sql: $sql";
//exit;

	$res = pg_exec ($con,$sql);

	#echo "<p>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</p>";

	if (pg_numrows($res) > 0) {
		echo "<br>";

		echo "<b>Resultado de busca entre los días  $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";

		echo "<br><br>";
		echo "<center><div style='width:750px;'>";
		echo"<TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<thead>";
		echo "<TR  height='25'>";
		echo "<TD width='120' height='15' class='table_line'><b>Referencia</b></TD>";
		echo "<TD width='55%' height='15' class='table_line'><b>Pieza</b></TD>";
		echo "<TD width='120' height='15' class='table_line'><b>Ocurrencia</b></TD>";
		echo "<TD width='50' height='15' class='table_line'><b><center>%</center></b></TD>";
		echo "</TR>";
		echo "</thead>";

		$total = 0;
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}
		echo "<tbody>";
		for ($i = 0; $i < pg_numrows($res); $i++){
			flush();
			$peca       = trim(pg_result($res,$i,peca));
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao  = trim(pg_result($res,$i,descricao));

			if($login_fabrica == 20 and $pais !='BR'){
				$descricao_es  = trim(pg_result($res,$i,descricao_espanhol));

				if(strlen($descricao_es)==0){
					$descricao_es  = "<font color = 'red'>Peça sem Tradução</font>";
				}
			}
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));

			if ($total_ocorrencia > 0) {
				$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			}

			$cor = '2';
			if ($i % 2 == 0) $cor = '1';

			echo "<TR class='bgTRConteudo$cor'>";
			echo "<TD align='left'><a href='javascript:AbreDefeitoPeca(\"$peca\",\"$aux_data_inicial\",\"$aux_data_final\",\"$estado\",\"$pais\",\"$origem\",\"$posto\",\"$pagamento\");'>$referencia</a></TD>";

			if($login_fabrica == 20 and $pais != 'BR') echo "<TD class='Conteudo' align='left' nowrap>$descricao_es</TD>";
			else                                       echo "<TD class='Conteudo' align='left' nowrap>$descricao</TD>";

			echo "<TD align='center'>$ocorrencia</TD>";
			echo "<TD align='right' title='%'>". number_format($porcentagem,2,".",".") ."</TD>";
			echo "</TR>";

			$total = $ocorrencia + $total;
		}
		echo "</tbody>";
		echo "<tr><td colspan='2'><font size='2'><b><CENTER>PIEZAS COM FALLAS </b></td><td colspan='2' align='center'><font size='2' color='009900'><b>$total</b></td></tr>";
		echo "</TABLE><br clear=both></div>";

	}else{

		echo "<br>";

		echo "<b>Ningún resultado encuentrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";
	}
} 

?>

<? include "rodape.php" ?>
