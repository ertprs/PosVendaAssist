<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include 'funcoes.php';

$btn_acao = ($_POST['btn_acao']) ? $_POST['btn_acao'] : null;

$ajax = $_GET['ajax'];

if($ajax == "excel"){
	$sql = "
		SELECT  tbl_posto.posto,
				tbl_posto.cnpj ,
				tbl_posto.nome,
				tbl_posto_fabrica.contato_estado AS estado,
				tbl_posto_fabrica.contato_cidade AS cidade,
				tbl_posto_fabrica.valor_km

		FROM    tbl_posto

		JOIN    tbl_posto_fabrica using (posto)

		WHERE   tbl_posto_fabrica.fabrica=$login_fabrica
		and tbl_posto_fabrica.credenciamento in ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
		$sql_alphabetic
		ORDER BY tbl_posto.estado, tbl_posto.cidade, tbl_posto.nome
	";

	$res      = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){

		$resultado = "<table width='700' align='center' border='0' class='tabela' cellpadding='1' cellspacing='1'>";

				$resultado .= "<tr bgcolor='#596d9b'>";
				$resultado .= "<td style='width:25%'>";
				$resultado .= "<font color='#FFFFFF'><b>CNPJ</b></font>";
				$resultado .= "</td>";
				$resultado .= "<td>";
				$resultado .= "<font color='#FFFFFF'><b>Nome Posto</b></font>";
				$resultado .= "</td>";
				$resultado .= "<td>";
				$resultado .= "<font color='#FFFFFF'><b>Cidade</b></font>";
				$resultado .= "</td>";
				$resultado .= "<td>";
				$resultado .= "<font color='#FFFFFF'><b>Estado</b></font>";
				$resultado .= "</td>";
				$resultado .= "<td>";
				$resultado .= "<font color='#FFFFFF'><b>Valor por Km</b></font>";
				$resultado .= "</td>";
				$resultado .= "</tr>";

			for ($i = 0; $i < pg_num_rows($res); $i++ ) {

				$posto            = pg_result($res,$i,'posto');
				$cnpj     		  = pg_result($res,$i,'cnpj');
				$nome_posto       = pg_result($res,$i,'nome');
				$cidade_posto     = pg_result($res,$i,'cidade');
				$estado_posto     = pg_result($res,$i,'estado');
				$valor_km_posto   = pg_result($res,$i,'valor_km');

				$valor_km_posto = str_replace(".",",",$valor_km_posto );

				$resultado .= "<tr>";
				$resultado .= "<td align='left' nowrap>" . $cnpj  . "&nbsp;</td>";
				$resultado .= "<td align='left' nowrap>" . $nome_posto    . "&nbsp;</td>";
				$resultado .= "<td align='left' nowrap>" . $cidade_posto  . "&nbsp;</td>";
				$resultado .= "<td align='left' nowrap>" . $estado_posto  . "&nbsp;</td>";
				$resultado .= "<td align='left' nowrap>" . $valor_km_posto . "&nbsp;</td>";
				$resultado .= "</tr>";
			}
		$resultado .= "</table>";

		$caminho = "xls/relatorio-valor-km-posto-{$login_fabbrica}-".date('T-m-d').".xls";
		$fp = fopen($caminho,"w");
		fwrite($fp,$resultado);
		fclose($fp);

		$link = "<img src='imagens/excel.png' width='30'> <br><input type='button' value='Download Excel' onclick=\"javascript: window.open('{$caminho}');\"> ";

		echo $link;

	}else{
		echo "Nenhum resultado encontrado";
	}

	exit;
}

//VALIDAÇÃO DO POSTO DA PESQUISA
if ($btn_acao == 'pesquisar'){

	$pesquisa_posto_cod    = strtoupper( trim($_POST['cod_posto']));
	$pesquisa_posto_nome   = strtoupper( $_POST['nome_posto'] );
	$pesquisa_posto_cidade = strtoupper( $_POST['cidade_posto'] );
	$pesquisa_posto_estado = strtoupper( $_POST['estado_posto'] );
	$tipo_pesquisa         = $_POST['tipo_pesquisa'];

	if ($tipo_pesquisa == "por_posto"){

		$pesquisa = 'posto';

		$sql = "SELECT
					posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica using(posto)
				WHERE tbl_posto_fabrica.fabrica=$login_fabrica
				AND tbl_posto_fabrica.codigo_posto='$pesquisa_posto_cod' ";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res)>0){

			$posto_pesquisa = pg_result($res,0,0);

		}else{

			$msg_erro = "Posto não Encontrado";

		}

	}else{

		$pesquisa = "estado";

	}

}

//GRAVA SE O POSTO NÃO TIVER KM OU ESTIVER ZERADA
if ($btn_acao == 'gravar'){

	$qtde_postos = $_POST['qtde_postos'];

	for ($i = 0; $i < $qtde_postos; $i++){

		//pega valores do post
		$valor_km_antigo = trim($_POST["valor_km_antigo_$i"]);
		$valor_km_novo   = trim($_POST["valor_km_novo_$i"]);
		$posto           = $_POST["posto_$i"];

		//SÓ IRÁ ATUALIZAR SE E SOMENTE SE O CAMPO FOR EDITADO E O VALOR NOVO FOR DIFERENTE DO ANTIGO.
		if ( ($valor_km_antigo <> $valor_km_novo) && empty($msg_erro) ){

			$valor_km = str_replace(",",".",$valor_km_novo);

			if ( empty($valor_km) ){
				$valor_km = 0;
			}

			if (empty($msg_erro)){

				$res = pg_query($con,"BEGIN TRANSACTION");

				//ATUALIZA tbl_posto_fabrica.valor_km PARA O POSTO ESCOLHIDO

				$sql = "UPDATE tbl_posto_fabrica
						SET valor_km=$valor_km
						WHERE posto=$posto
						AND fabrica=$login_fabrica";

				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

			}

		}else{

			continue;

		}

	}

	//SE NÃO HOUVER ERROS, ENTÃO ATUALIZA
	if ( empty($msg_erro) ){
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$_GET['sucesso'] = "s";
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}

}


/* INCLUDE DO CABECALHO */
$layout_menu = "cadastro";
$title = "Cadastro de Valores de KM";
include "cabecalho.php";
?>

<html>

<head>
<script type="text/javascript" src="js/jquery.js">              </script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"> </script>
<script type="text/javascript" src="js/jquery.maskmoney.js">    </script>

<script type="text/javascript" language="javascript">

//Jquery's  - inicio

$(function(){
	//Campos de Valores
	$(".money").maskMoney({symbol:"", decimal:",", thousands:'', precision:2, maxlength: 10});

});

//Jquery's  - fim


//função pesquisa posto

function fnc_pesquisa_posto(campo, campo2,campo3,campo4, tipo) {
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
		janela.cidade  = campo3;
		janela.estado  = campo4;

		janela.focus();
	}
	else {
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}


//função para validar e submeter form de pesquisa e cadastro

function fnc_submit(tipo){


	var cod_posto;
	var nome_posto;
	var tipo_pesquisa;
	var estado;

	tipo_pesquisa = "";

	if (tipo == 'gravar'){

		document.frm_lista_pesquisa.btn_acao.value = 'gravar' ;
		document.frm_lista_pesquisa.submit();

	}else if (tipo == 'pesquisa'){

		if ( $(":checked").val() == "por_posto" ){

			tipo_pesquisa = "por_posto";

		}else if ( $(":checked").val() == "por_estado" ) {

			tipo_pesquisa = "por_estado";

		}

		if (tipo_pesquisa == "por_posto"){

			//VERIFICA SE CAMPOS ESTÃO PRENCHIDOS - COD POSTO - NOME POSTO
			cod_posto  = $("#cod_posto").val();
			nome_posto = $("#nome_posto").val();

			if ( cod_posto.length > 0 && nome_posto.length > 0 ){

				document.frm_pesquisa.btn_acao.value = 'pesquisar' ;
				document.frm_pesquisa.submit()

			}else{

				if ( cod_posto.length == 0 && nome_posto.length == 0   ){

					$("#erro_msg_").html('Preencha o Código e Nome do Posto');

				}else if (cod_posto.length == 0){

					$("#erro_msg_").html('Preencha o Código do Posto');

				}else if ( nome_posto.length == 0 ){

					$("#erro_msg_").html('Preencha o nome do Posto');

				}

				$("#tbl_erro_msg").show();

			}

		}else if (tipo_pesquisa == "por_estado"){

			estado = $("#estado_posto").val();

			if ( estado.length > 0 ){

				document.frm_pesquisa.btn_acao.value = 'pesquisar' ;
				document.frm_pesquisa.submit()

			}else{

				if ( estado.length == 0 ){

					$("#erro_msg_").html('Escolha um estado para efetuar a pesquisa');

				}

				$("#tbl_erro_msg").show();

			}


		}

	}

}

function geraExcel(){
	$.ajax({
		url:"<?php echo $PHP_SELF; ?>?ajax=excel",
		cache:false,
		success:function(retorno){

			$("#excel").html(retorno);

		}
	});
}


</script>


<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial" !important;
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
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.alphabetic{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
	margin-right:5px;
	padding:5px;
}

.alphabetic a{
	color:#fff;
}

.selected{
	background-color:#AC9847;
	font: bold 15px Arial;
	text-decoration:underline;
}

</style>

</head>

<body>


<?

$display_msg_erro = ($msg_erro) ? "display:true" : "display:none";

?>

<br />
<?// TABELA DE MSG DE ERRO?>
<table class='msg_erro' id="tbl_erro_msg" style="<?php echo $display_msg_erro?>" width="700px" align='center'>
	<tr>
		<td id="erro_msg_"><? echo ($msg_erro) ? $msg_erro : ''; ?></td>
	</tr>
</table>

<? // TABELA DE MSG DE SUCESSO
if ($_GET['sucesso']){
?>

<table class='sucesso' id="tbl_sucesso" width="700px" align='center'>
	<tr>
		<td>Gravado com Sucesso!</td>
	</tr>
</table>

<?
}


if ( $_POST['btn_acao'] == 'pesquisar' || empty($_GET['sucesso']) ){

	$cod_posto     =  $_POST['cod_posto'];
	$nome_posto    =  $_POST['nome_posto'];
	$cidade_posto  =  $_POST['cidade_posto'];
	$estado_posto  =  $_POST['estado_posto'];
	$tipo_pesquisa =  $_POST['tipo_pesquisa'];


}
$checked_t_p = ($tipo_pesquisa == "por_posto" || empty($tipo_pesquisa) )  ? "checked" : null;
$checked_t_e = ($tipo_pesquisa == "por_estado") ? "checked" : null;

?>
<!-- FORMULÁRIO DE PESQUISA - INICIO -->
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<table align='center' class="formulario" width="700px" cellpadding='0' cellspacing='0'>
	<tr>
		<td class='titulo_tabela'> Formulário de Pesquisa </td>
	</tr>

	<tr>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td>

			<table align="center" width="600px">
				<tr>
					<td>
						Código do Posto
					</td>

					<td>
						Nome do Posto
					</td>
				</tr>

				<tr>
					<td>

						<input type="text" class='frm' id="cod_posto" name="cod_posto" value="<?= $cod_posto?>"/>

						<img align="absmiddle" onclick="javascript: fnc_pesquisa_posto(document.frm_pesquisa.cod_posto,document.frm_pesquisa.nome_posto,document.frm_pesquisa.cidade_posto,document.frm_pesquisa.estado_posto,'codigo')" style="cursor:pointer" src="imagens/lupa.png">

					</td>

					<td>

						<input type="text" class='frm' id="nome_posto" name="nome_posto" value="<?= $nome_posto?>" />

						<img align="absmiddle" onclick="javascript: fnc_pesquisa_posto(document.frm_pesquisa.cod_posto,document.frm_pesquisa.nome_posto,document.frm_pesquisa.cidade_posto,document.frm_pesquisa.estado_posto,'nome')" style="cursor:pointer" src="imagens/lupa.png">

					</td>
				</tr>

				<tr>
					<td>
						Cidade
					</td>

					<td>
						Estado
					</td>
				</tr>

				<tr>
					<td>
						<input type="text" class='frm' id='cidade_posto' name='cidade_posto' readonly="readonly" value="<?= $cidade_posto?>" />
					</td>

					<td>

						<select id="estado_posto" name="estado_posto" class="frm">

							<option value=""   <? if (strlen($estado_posto) == 0) echo " selected "; ?>></option>
							<option value="AC" <? if ($estado_posto == "AC") echo " selected "; ?>>AC</option>
							<option value="AL" <? if ($estado_posto == "AL") echo " selected "; ?>>AL</option>
							<option value="AM" <? if ($estado_posto == "AM") echo " selected "; ?>>AM</option>
							<option value="AP" <? if ($estado_posto == "AP") echo " selected "; ?>>AP</option>
							<option value="BA" <? if ($estado_posto == "BA") echo " selected "; ?>>BA</option>
							<option value="CE" <? if ($estado_posto == "CE") echo " selected "; ?>>CE</option>
							<option value="DF" <? if ($estado_posto == "DF") echo " selected "; ?>>DF</option>
							<option value="ES" <? if ($estado_posto == "ES") echo " selected "; ?>>ES</option>
							<option value="GO" <? if ($estado_posto == "GO") echo " selected "; ?>>GO</option>
							<option value="MA" <? if ($estado_posto == "MA") echo " selected "; ?>>MA</option>
							<option value="MG" <? if ($estado_posto == "MG") echo " selected "; ?>>MG</option>
							<option value="MS" <? if ($estado_posto == "MS") echo " selected "; ?>>MS</option>
							<option value="MT" <? if ($estado_posto == "MT") echo " selected "; ?>>MT</option>
							<option value="PA" <? if ($estado_posto == "PA") echo " selected "; ?>>PA </option>
							<option value="PB" <? if ($estado_posto == "PB") echo " selected "; ?>>PB</option>
							<option value="PE" <? if ($estado_posto == "PE") echo " selected "; ?>>PE</option>
							<option value="PI" <? if ($estado_posto == "PI") echo " selected "; ?>>PI</option>
							<option value="PR" <? if ($estado_posto == "PR") echo " selected "; ?>>PR</option>
							<option value="RJ" <? if ($estado_posto == "RJ") echo " selected "; ?>>RJ</option>
							<option value="RN" <? if ($estado_posto == "RN") echo " selected "; ?>>RN</option>
							<option value="RO" <? if ($estado_posto == "RO") echo " selected "; ?>>RO</option>
							<option value="RR" <? if ($estado_posto == "RR") echo " selected "; ?>>RR</option>
							<option value="RS" <? if ($estado_posto == "RS") echo " selected "; ?>>RS</option>
							<option value="SC" <? if ($estado_posto == "SC") echo " selected "; ?>>SC</option>
							<option value="SE" <? if ($estado_posto == "SE") echo " selected "; ?>>SE</option>
							<option value="SP" <? if ($estado_posto == "SP") echo " selected "; ?>>SP</option>
							<option value="TO" <? if ($estado_posto == "TO") echo " selected "; ?>>TO</option>

						</select>
					</td>
				</tr>

				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>

				<tr>
					<td colspan="1">

						<fieldset style='width:150px'>
							<legend>Tipo da Pesquisa</legend>

							<input type="radio" id="tipo_pesquisa"  name="tipo_pesquisa"  value="por_posto"   <? echo $checked_t_p ?> /> &nbsp;
							<label for="tipo_pesquisa">Por Posto</label>

							<br />
							<br />

							<input type="radio" id="tipo_pesquisa2" name="tipo_pesquisa" value="por_estado"   <? echo $checked_t_e ?> /> &nbsp;
							<label for="tipo_pesquisa2">Por Estado</label>
						</fieldset>

					</td>
				</tr>

				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>

			</table>

		</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td align="center">

			<input type='hidden' name="btn_acao" />

			<input type="button" value="Pesquisar" style="cursor:pointer;font:12px Arial" onclick="fnc_submit('pesquisa')" />
			<input type="button" value="Limpar" style="cursor:pointer;font:12px Arial" onclick="window.location='<?$PHP_SELF?>?#'" />

		</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
	</tr>


</table>

</form>
<!-- FORMULÁRIO DE PESQUISA - FIM -->
<?

//LISTAGEM DE POSTOS - INICIO
if (empty($_POST['btn_acao']) || $pesquisa == 'estado' || $_GET['sucesso']) {

	if (empty($_GET['i'])) {

		$inicial = 'A';

	} else {

		$inicial = $_GET['i'];

	}


	$sql_alphabetic = "AND upper(tbl_posto.nome) LIKE '$inicial%'";
}

if ( ($pesquisa && empty($msg_erro)) or $_GET['uf'] or $_GET['sucesso'] ){

	if ($pesquisa == "posto"){

		$sql_pesquisa = " AND tbl_posto.posto=$posto_pesquisa";

	}else if ($pesquisa == "estado" || $_GET['uf']){

		if ($_GET['uf']){
			$pesquisa_posto_estado = $_GET['uf'];
		}
		$sql_pesquisa = " AND tbl_posto.estado='$pesquisa_posto_estado' ORDER BY tbl_posto.nome";

	}

	$sql = "
		SELECT  tbl_posto.posto,
				tbl_posto_fabrica.codigo_posto ,
				tbl_posto.nome,
				tbl_posto_fabrica.contato_estado as estado,
				tbl_posto_fabrica.contato_cidade as cidade,
				tbl_posto_fabrica.valor_km

		FROM    tbl_posto

		JOIN    tbl_posto_fabrica using (posto)

		WHERE   tbl_posto_fabrica.fabrica=$login_fabrica
		and tbl_posto_fabrica.credenciamento in ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
		$sql_alphabetic
		$sql_pesquisa
		ORDER BY tbl_posto.estado, tbl_posto.cidade, tbl_posto.nome
	";

}else{

	$sql = "
		SELECT  tbl_posto.posto,
				tbl_posto_fabrica.codigo_posto ,
				tbl_posto.nome,
				tbl_posto_fabrica.contato_estado as estado,
				tbl_posto_fabrica.contato_cidade as cidade,
				tbl_posto_fabrica.valor_km

		FROM    tbl_posto

		JOIN    tbl_posto_fabrica using (posto)

		WHERE   tbl_posto_fabrica.fabrica=$login_fabrica
		and tbl_posto_fabrica.credenciamento in ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
		$sql_alphabetic
		ORDER BY tbl_posto.estado, tbl_posto.cidade, tbl_posto.nome
	";

}

$res      = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
		flush();
		echo "<br />";

		if($login_fabrica == 15){
			echo "<span id='excel'><input type='button' value='Relatório Excel' onclick='geraExcel()'></span> <br><br>";
		}

		if (!$_POST['btn_acao'] || $pesquisa == 'estado' || $_GET['uf'] || $_GET['sucesso']){

			$arrABC    = range('A', 'Z');
			$arrLetras = array();

			$estado_where = ($_GET['uf'] || $pesquisa == 'estado') ? "AND tbl_posto.estado='$pesquisa_posto_estado' " : "";

			$prepare = pg_prepare($con, "query_qtde", " SELECT count(tbl_posto.posto)
														FROM tbl_posto
														JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
														WHERE tbl_posto_fabrica.fabrica = 15
														and tbl_posto_fabrica.credenciamento in ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
														$estado_where
														AND UPPER(tbl_posto.nome) like $1");

			foreach ($arrABC as $letra) {
				$result = pg_execute($con, "query_qtde", array("$letra%"));
				$qtde   = pg_fetch_result($result, 0, 0);

				if ($qtde > 0) {
					$arrLetras[] = $letra;

				}
			}

			unset($arrABC);

			echo '<div style="text-align: center; margin-bottom: 20px;">';
			foreach ($arrLetras as $l) {

				if ($inicial == $l) {
					$class_selected = 'selected';
				}else{
					$class_selected = '';
				}

				//SE PESQUISAR POR ESTADO, VAI PASSAR POR PARAMETRO NA LETRA A UF
				$params = ($pesquisa == 'estado') ? "i=".$l."&uf=".$pesquisa_posto_estado : "i=".$l ;

				echo '<a href="?' . $params . '">';
					echo "<span class='alphabetic $class_selected'>".$l . '</span>';
				echo '</a>';
			}

			echo '</div>';

			unset($arrLetras);

		}

		echo "<form name='frm_lista_pesquisa' method='post' action='".$PHP_SELF."' >";

			echo "<table width='700' align='center' border='0' class='tabela' cellpadding='1' cellspacing='1'>";

				echo "<tr class='titulo_coluna'>";
					echo "<td style='width:25%'>";
						echo "Cód. Posto";
					echo "</td>";
					echo "<td>";
						echo "Nome Posto";
					echo "</td>";
					echo "<td>";
						echo "Cidade";
					echo "</td>";
					echo "<td>";
						echo "Estado";
					echo "</td>";
					echo "<td>";
						echo "Valor por Km";
					echo "</td>";
				echo "</tr>";

			$num = 0;

			for ($i = 0; $i < pg_num_rows($res); $i++ ) {

				$posto            = pg_result($res,$i,'posto');
				$codigo_posto     = pg_result($res,$i,'codigo_posto');
				$nome_posto       = pg_result($res,$i,'nome');
				$cidade_posto     = pg_result($res,$i,'cidade');
				$estado_posto     = pg_result($res,$i,'estado');
				$valor_km_posto   = pg_result($res,$i,'valor_km');

				$valor_km_posto = str_replace(".",",",$valor_km_posto );

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr style='background-color: $cor'>";
					echo "<td align='left' nowrap>" . $codigo_posto  . "&nbsp;</td>";

					echo "<td align='left' nowrap>" . $nome_posto    . "&nbsp;</td>";

					echo "<td align='left' nowrap>" . $cidade_posto  . "&nbsp;</td>";

					echo "<td align='left' nowrap>" . $estado_posto  . "&nbsp;</td>";

					echo "<td align='center' nowrap>";

						echo "<input type=\"hidden\" name=\"posto_$i\" value=\"$posto\" />";
						echo "<input type='hidden' name='valor_km_antigo_$i' value='$valor_km_posto' />";
						echo "<input type='text' value='$valor_km_posto' name='valor_km_novo_$i' class='money frm' size='10' style='text-align:right;' align='center' />";

					echo "</td>";


				echo "</tr>";

				$num++;
			}

			echo "<tr>";

				echo "<td colspan='5' align='center' class='titulo_tabela'>";

					echo "<br />
							<input type=\"hidden\" name=\"qtde_postos\" value=\"$num\" />
							<input type=\"hidden\" name=\"btn_acao\" value=\"\" />
							<input type=\"button\" value=\"GRAVAR\" onclick=\"fnc_submit('gravar')\" />
						 <br />&nbsp;";

				echo "</td>";

			echo "<tr>";

		echo "</table>";

	echo "</form>";


}else{?>

	<center> Não foram encontrados resultados para a sua pesquisa </center>

<?
}

//LISTAGEM DE POSTOS - FIM




include "rodape.php"; ?>
</body>

</html>
