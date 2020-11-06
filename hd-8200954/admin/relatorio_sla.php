<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';


$btn_acao    = trim($_POST["btn_acao"]);

$layout_menu = "callcenter";
$title = "Relatório SLA";

$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto.cnpj = '$q' ";
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



include "cabecalho.php";

?>

<style type="text/css">


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


<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

		$("input[@rel='data_nf']").maskedinput("99/99/9999");
	});
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>


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
		$("#posto_codigo").val(data[0]) ;
		//alert(data[2]);
	});

});
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.cliente		= document.frm_pesquisa.consumidor_cliente;
			janela.nome			= document.frm_pesquisa.consumidor_nome;
			janela.cpf			= document.frm_pesquisa.consumidor_cpf;
			janela.rg			= document.frm_pesquisa.consumidor_rg;
			janela.cidade		= document.frm_pesquisa.consumidor_cidade;
			janela.estado		= document.frm_pesquisa.consumidor_estado;
			janela.fone			= document.frm_pesquisa.consumidor_fone;
			janela.endereco		= document.frm_pesquisa.consumidor_endereco;
			janela.numero		= document.frm_pesquisa.consumidor_numero;
			janela.complemento	= document.frm_pesquisa.consumidor_complemento;
			janela.bairro		= document.frm_pesquisa.consumidor_bairro;
			janela.cep			= document.frm_pesquisa.consumidor_cep;
			janela.proximo		= document.frm_pesquisa.revenda_nome;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}
function fnc_pesquisa_contrato (campo,campo2, tipo) {

	var url = "";

	if (tipo == "numero_contrato" ) {
		var xcampo = campo;
		url = "pesquisa_contrato.php?numero_contrato=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (tipo == "contrato_descricao" ) {
		var xcampo = campo2;
		url = "pesquisa_contrato.php?contrato_descricao=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (xcampo != "") {
		if (xcampo.value.length >= 2) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.contrato			= document.frm_pesquisa.contrato;
			janela.numero_contrato		= campo;
			janela.contrato_descricao	= campo2;
			janela.focus();
		}else{
			alert("Digite pelo menos 2 caracteres para efetuar a pesquisa");
		}
	}
}


function fnc_pesquisa_grupo (campo,campo2, tipo) {

	var url = "";

	if (tipo == "nome_grupo" ) {
		var xcampo = campo;
		url = "pesquisa_grupo.php?nome_grupo=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (tipo == "grupo_descricao" ) {
		var xcampo = campo2;
		url = "pesquisa_grupo.php?grupo_descricao=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (xcampo != "") {
		if (xcampo.value.length >= 2) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.grupo_empresa	= document.frm_pesquisa.grupo_empresa;
			janela.nome_grupo		= campo;
			janela.grupo_descricao	= campo2;
			janela.focus();
		}else{
			alert("Digite pelo menos 2 caracteres para efetuar a pesquisa");
		}
	}
}

</script>

<? include "javascript_pesquisas.php";

if($btn_acao == 'Pesquisar'){

	$data_inicial     = trim($_POST['data_inicial']);
	$data_final       = trim($_POST['data_final']);
	$tipo_posto       = trim($_POST['tipo_posto']);
	$tipo_atendimento = trim($_POST['tipo_atendimento']);
	$consumidor_cpf   = trim($_POST['consumidor_cpf']);
	$consumidor_cpf_radical   = trim($_POST['consumidor_cpf_radical']);
	$numero_contrato  = trim($_POST['numero_contrato']); //USAR NÚMERO CONTRATO, POIS PELO ID DAVA PROBLEMAS AO APAGAR O CAMPO TEXT, AINDA FICAVA O ID NO HIDEN
	$nome_grupo       = trim($_POST['nome_grupo']);      //USAR NOME GRUPO, POIS PELO ID DAVA PROBLEMAS AO APAGAR O CAMPO TEXT, AINDA FICAVA O ID NO HIDEN E CONSULTAVA COM ESSE PARAMETRO
	$posto_codigo     = trim($_POST['posto_codigo']);


	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}

}

if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}

?>


<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Relatório SLA</caption>

<TBODY>
<TR>
	<TD>Data Inicial<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm"></TD>
	<TD>Data Final<br><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm"></TD>
</TR>
<TR>
	<TD colspan='2'>Tipo de Posto<br>
		<select name='tipo_posto' size='1' class='frm'>
		<?
			if (strlen($tipo_posto)==0){
				echo "<option SELECTED></option>";
			}else{
				echo "<option ></option>";
			}

			$sql = "SELECT *
					FROM   tbl_tipo_posto
					WHERE  tbl_tipo_posto.fabrica = $login_fabrica
					AND tbl_tipo_posto.ativo = 't'
					ORDER BY tbl_tipo_posto.descricao";
			$res = pg_exec ($con,$sql);
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					echo "<option value='" . pg_result ($res,$i,tipo_posto) . "' ";
					if ($tipo_posto == pg_result ($res,$i,tipo_posto)) echo " selected ";
					echo ">";
					echo pg_result ($res,$i,descricao);
			echo "</option>";
			}
		?>
		</select>
	</TD>
</TR>
<TR>
	<TD colspan='2'>Natureza<br>
		<select name='tipo_atendimento' size='1' class='frm'>
		<?
			if (strlen($tipo_atendimento)==0){
				echo "<option SELECTED></option>";
			}else{
				echo "<option ></option>";
			}

			$sql = "SELECT *
					FROM tbl_tipo_atendimento
					WHERE fabrica = $login_fabrica
					AND   ativo IS TRUE
					ORDER BY tipo_atendimento";
			$res = pg_exec ($con,$sql) ;
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option ";
				if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
				echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'" ;
				echo " > ";
				echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
				echo "</option>\n";
			}
			?>
		</select>
	</TD>
</TR>
<TR>
	<TD colspan= '2'>Radical de CPF/CNPJ<br><input class="frm" type="text" name="consumidor_cpf_radical"   size="10" maxlength="9" value="<? echo $consumidor_cpf_radical ?>"  onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on'; ">&nbsp;</TD>
</TR>

<TR>

	<TD>CPF/CNPJ Cliente<br><input class="frm" type="text" name="consumidor_cpf"   size="17" maxlength="18" value="<? echo $consumidor_cpf ?>"  onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on'; ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_pesquisa.consumidor_cpf,"cpf")'  style='cursor: pointer'></TD>
	<TD>Nome Cliente<br><input class="frm" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>" onblur = "this.className='frm';" onfocus ="this.className='frm-on';">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_pesquisa.consumidor_nome, "nome")'  style='cursor: pointer'>

	<input type='hidden' name='consumidor_cliente'>
	<input type='hidden' name='consumidor_rg'>
	<input type='hidden' name='consumidor_cidade'>
	<input type='hidden' name='consumidor_estado'>
	<input type='hidden' name='consumidor_fone'>
	<input type='hidden' name='consumidor_endereco'>
	<input type='hidden' name='consumidor_numero'>
	<input type='hidden' name='consumidor_complemento'>
	<input type='hidden' name='consumidor_bairro'>
	<input type='hidden' name='consumidor_cep'>
	<input type='hidden' name='revenda_nome'>
	</TD>
</TR>

<TR>
	<TD nowrap >
	Contrato<br>
		<input type="hidden" name="contrato" value="">
		<input class="frm" type="text" name="numero_contrato" value="<?echo $numero_contrato;?>" size='10'><a href="javascript: fnc_pesquisa_contrato (document.frm_pesquisa.numero_contrato,document.frm_pesquisa.contrato_descricao,'numero_contrato')"><IMG SRC="imagens/lupa.png" ></a>&nbsp;
	<TD nowrap >Descrição <br >
		<input class="frm" type="text" name="contrato_descricao" value="<?echo $contrato_descricao;?>" size='30'><a href="javascript: fnc_pesquisa_contrato (document.frm_pesquisa.numero_contrato,document
		.frm_pesquisa.contrato_descricao,'contrato_descricao')"><IMG SRC="imagens/lupa.png" ></a>
	</td>
</TR>
<TR>
	<TD nowrap >
	Grupo Empresa
<br>
		<input type="hidden" name="grupo_empresa" value="">
		<input class="frm" type="text" name="nome_grupo" value="<?echo $nome_grupo;?>" size='10'><a href="javascript: fnc_pesquisa_grupo (document.frm_pesquisa.nome_grupo,document.frm_pesquisa.grupo_descricao,'nome_grupo')"><IMG SRC="imagens/lupa.png" ></a>&nbsp;
	<TD nowrap >Descrição <br >
		<input class="frm" type="text" name="grupo_descricao" value="<?echo $grupo_descricao;?>" size='30'><a href="javascript: fnc_pesquisa_grupo (document.frm_pesquisa.nome_grupo,document.frm_pesquisa.grupo_descricao,'grupo_descricao')"><IMG SRC="imagens/lupa.png" ></a>
	</TD>
</TR>

	<?
echo "<TR >\n";
echo "	<TD >";
echo "CNPJ Posto<BR>";
echo "		<input type='text' name='posto_codigo' id='posto_codigo' size='18' value='$posto_codigo' class='frm'>";//&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_nome,document.frm_pesquisa.posto_codigo,'cnpj')\">";

echo "</TD ><TD >Razão Social Posto<BR>";
echo "		<input type='text' name='posto_nome' id='posto_nome' size='45' value='$posto_nome' class='frm'>";//&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_nome,document.frm_pesquisa.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "<TR>\n";
?>


</tbody>
<TR>
	<TD colspan="2">
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
	</TD>
</TR>
</table>
</form>


<?
if ((strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) OR strlen($pedido) > 0 ) {

	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql_data = " AND data_hora_chegada_cliente_consulta BETWEEN '$xdata_inicial' AND '$xdata_final' ";
	}

	if (strlen($tipo_atendimento) > 0 ) {
		$sql_tipo_atendimento = " AND tbl_os.tipo_atendimento = $tipo_atendimento ";
	}

	if (strlen($tipo_posto) > 0 ) {
		$sql_tipo_posto = " AND tbl_posto_fabrica.tipo_posto = $tipo_posto ";
	}


	if (strlen($numero_contrato) > 0 ) {
		$sql_contrato = " AND tbl_os.consumidor_cpf in(
																SELECT  tbl_posto.cnpj        AS cpf
																FROM  tbl_posto
																JOIN  tbl_posto_consumidor USING(posto)
																JOIN  tbl_contrato         USING(contrato)
																WHERE  tbl_contrato.numero_contrato = '$numero_contrato'
																AND   tbl_posto_consumidor.fabrica = $login_fabrica
															) ";
	}

	if (strlen($nome_grupo) > 0 ) {
		$sql_grupo_empresa= " AND tbl_os.consumidor_cpf in(
																SELECT  tbl_posto.cnpj        AS cpf
																FROM  tbl_posto
																JOIN  tbl_posto_consumidor USING(posto)
																JOIN  tbl_grupo_empresa    USING(grupo_empresa)
																WHERE  tbl_grupo_empresa.nome_grupo= '$nome_grupo'
																AND   tbl_posto_consumidor.fabrica = $login_fabrica
															) ";
	}


	if (strlen($consumidor_cpf_radical) > 0 ) {
		$sql_consumidor_cpf_radical = " AND tbl_os.consumidor_cpf in(
																SELECT  tbl_posto.cnpj        AS cpf
																FROM  tbl_posto
																JOIN  tbl_posto_consumidor USING(posto)
																WHERE  tbl_posto.cnpj      ILIKE '$consumidor_cpf_radical%'
																AND   tbl_posto_consumidor.fabrica = $login_fabrica
																ORDER BY tbl_posto.nome
															) ";
	}

	if (strlen($consumidor_cpf) > 0 ) {
		$sql_consumidor_cpf = " AND tbl_os.consumidor_cpf = '$consumidor_cpf' ";
	}

	if (strlen($posto_codigo) > 0 ) {
		$sql = "SELECT posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE fabrica = $login_fabrica
					AND tbl_posto.cnpj = '$posto_codigo' ";

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			$sql_posto = " AND tbl_os.posto = ". trim(pg_result($res,0,posto));
		}
	}


	$sql = "SELECT *
			FROM (
					(
						SELECT	tbl_os.os                                        AS os,
								tbl_os.sua_os                                    AS sua_os,
								tbl_os.consumidor_cpf                            AS consumidor_cpf,
								trim(tbl_os.consumidor_nome)                     AS consumidor_nome,
								tbl_tipo_atendimento.descricao                   AS tipo_atedimento,
								tbl_posto_fabrica.codigo_posto                   AS codigo_posto,
								tbl_posto.nome                                   AS nome_posto,
								tbl_os_revenda.os_manutencao                     AS os_manutencao,
								TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')       AS data_abertura,
								TO_CHAR(tbl_os.data_abertura,'YYYY-MM-DD')       AS data_abertura2,
								TO_CHAR(tbl_os.hora_abertura,'HH24:MI')          AS hora_abertura,

								(	SELECT TO_CHAR(hora_chegada_cliente,'DD/MM/YYYY')
									FROM tbl_os_visita
									WHERE tbl_os_visita.os = tbl_os.os
									ORDER BY tbl_os_visita.hora_chegada_cliente ASC LIMIT 1)    AS data_hora_chegada_cliente,

								(	SELECT TO_CHAR(hora_chegada_cliente,'YYYY-MM-DD')
									FROM tbl_os_visita
									WHERE tbl_os_visita.os = tbl_os.os
									ORDER BY tbl_os_visita.hora_chegada_cliente ASC LIMIT 1)    AS data_hora_chegada_cliente2,


								(	SELECT TO_CHAR(hora_chegada_cliente,'HH24:MI')
									FROM tbl_os_visita
									WHERE tbl_os_visita.os = tbl_os.os
									ORDER BY tbl_os_visita.hora_chegada_cliente ASC LIMIT 1)    AS hora_chegada_cliente,

								(	SELECT hora_chegada_cliente
									FROM tbl_os_visita
									WHERE tbl_os_visita.os = tbl_os.os
									ORDER BY tbl_os_visita.hora_chegada_cliente ASC LIMIT 1)    AS data_hora_chegada_cliente_consulta

						FROM tbl_os
						JOIN tbl_os_extra         USING(os)
						JOIN tbl_tipo_atendimento USING(tipo_atendimento)
						JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_os.posto     AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_posto            ON tbl_posto.posto   = tbl_os.posto
						LEFT JOIN tbl_os_revenda  ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.posto <> 6359
						/*Sem classificacao não aparecia*/
						AND (tbl_os_extra.classificacao_os <> 5 /*cancelada*/ or tbl_os_extra.classificacao_os IS NULL )

						AND tbl_os.excluida          IS NOT TRUE
						AND tbl_os_revenda.fabrica   IS NULL
						$sql_consumidor_cpf
						$sql_consumidor_cpf_radical
						$sql_contrato
						$sql_grupo_empresa
						$sql_tipo_posto
						$sql_tipo_atendimento
						$sql_posto

					) UNION (
						SELECT	DISTINCT
								tbl_os_revenda.os_revenda                        AS os,
								tbl_os_revenda.os_revenda::text                  AS sua_os,
								tbl_os.consumidor_cpf                            AS consumidor_cpf,
								trim(tbl_os.consumidor_nome)                     AS consumidor_nome,
								tbl_tipo_atendimento.descricao                   AS tipo_atedimento,
								tbl_posto_fabrica.codigo_posto                   AS codigo_posto,
								tbl_posto.nome                                   AS nome_posto,
								tbl_os_revenda.os_manutencao                     AS os_manutencao,
								TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')       AS data_abertura,
								TO_CHAR(tbl_os.data_abertura,'YYYY-MM-DD')       AS data_abertura2,
								TO_CHAR(tbl_os.hora_abertura,'HH24:MI')          AS hora_abertura,
								(	SELECT TO_CHAR(hora_chegada_cliente,'DD/MM/YYYY')
									FROM tbl_os_visita
									WHERE tbl_os_visita.os_revenda = tbl_os_revenda.os_revenda
									ORDER BY tbl_os_visita.hora_chegada_cliente ASC LIMIT 1)    AS data_hora_chegada_cliente,

								(	SELECT TO_CHAR(hora_chegada_cliente,'YYYY-MM-DD')
									FROM tbl_os_visita
									WHERE tbl_os_visita.os_revenda = tbl_os_revenda.os_revenda
									ORDER BY tbl_os_visita.hora_chegada_cliente ASC LIMIT 1)    AS data_hora_chegada_cliente2,

								(	SELECT TO_CHAR(hora_chegada_cliente,'HH24:MI')
									FROM tbl_os_visita
									WHERE tbl_os_visita.os_revenda = tbl_os_revenda.os_revenda
									ORDER BY tbl_os_visita.hora_chegada_cliente ASC LIMIT 1)    AS hora_chegada_cliente,

								(	SELECT hora_chegada_cliente
									FROM tbl_os_visita
									WHERE tbl_os_visita.os_revenda = tbl_os_revenda.os_revenda
									ORDER BY tbl_os_visita.hora_chegada_cliente ASC LIMIT 1)    AS data_hora_chegada_cliente_consulta

						FROM tbl_os
						JOIN tbl_os_extra         USING(os)
						JOIN tbl_tipo_atendimento USING(tipo_atendimento)
						JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_os.posto     AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_posto            ON tbl_posto.posto   = tbl_os.posto
						JOIN tbl_os_revenda       ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto AND tbl_os_revenda.fabrica = $login_fabrica
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.posto <> 6359
						/*Sem classificacao não aparecia*/
						AND (tbl_os_extra.classificacao_os <> 5 /*cancelada*/ or tbl_os_extra.classificacao_os IS NULL )

						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os_revenda.os_manutencao IS TRUE
						$sql_consumidor_cpf
						$sql_contrato
						$sql_grupo_empresa
						$sql_consumidor_cpf_radical
						$sql_tipo_posto
						$sql_tipo_atendimento
						$sql_posto

					)
				)
			todas_os
			WHERE data_hora_chegada_cliente_consulta is not null
				$sql_data

			ORDER BY consumidor_nome
			";
//if($login_admin = 568) echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";

		echo "<table width='800' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Consumidor</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Natureza</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Abertura de Chamado</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Início do Atendimento</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>SLA</B></font></td>";
		echo "</tr>";

		$cores = '';

		for ($x=0; $x<pg_numrows($res);$x++){

			$os							= pg_result($res, $x, os);
			$sua_os						= pg_result($res, $x, sua_os);
			$consumidor_cpf				= pg_result($res, $x, consumidor_cpf);
			$consumidor_nome			= pg_result($res, $x, consumidor_nome);
			$tipo_atedimento			= pg_result($res, $x, tipo_atedimento);
			$codigo_posto				= pg_result($res, $x, codigo_posto);
			$nome_posto					= pg_result($res, $x, nome_posto);
			$os_manutencao				= pg_result($res, $x, os_manutencao);
			$data_abertura				= pg_result($res, $x, data_abertura);
			$data_abertura2				= pg_result($res, $x, data_abertura2);
			$hora_abertura				= pg_result($res, $x, hora_abertura);

			$data_hora_chegada_cliente  = pg_result($res, $x, data_hora_chegada_cliente);
			$data_hora_chegada_cliente2  = pg_result($res, $x, data_hora_chegada_cliente2);
			$hora_chegada_cliente       = pg_result($res, $x, hora_chegada_cliente);

			$inicio_abertura     = $data_abertura2.' '.$hora_abertura;
			$inicio_atendimento  = $data_hora_chegada_cliente2.' '.$hora_chegada_cliente;

			/*FUNÇÃO USADA PARA PROCURAR OS DIAS UTEIS ENTRE DUAS DATAS E RETORNAR EM MINUTOS*/
			$sql = "SELECT fn_dias_uteis_horas('$inicio_abertura'::timestamp, '$inicio_atendimento'::timestamp);";

			$horas   = 0;
			$minutos = 0;
			if(strlen(trim($inicio_abertura))>0  and strlen(trim($inicio_atendimento))>0){
				$res_horas = pg_exec ($con,$sql);

				if(pg_numrows ($res_horas)> 0){
					$retorno_horas = pg_result ($res_horas,0,0);

					if($retorno_horas > 60){
						$xretorno_horas = ($retorno_horas /60);

						$h1 = explode(".", $xretorno_horas );
						$horas = $h1[0];
						$minutos= $retorno_horas  - ($horas*60);

						//$h1 = $h1[0].".".substr($h1[1], 0, 2);
					}else{
						$horas       = 0;
						$minutos= $retorno_horas ;
					}

					//echo "<br>os>$os - tot: $retorno_horas  - div 60: $xretorno_horas - horas: $horas - minutos: $minutos  - h1: $h1";

						//$retorno_horas = number_format(($retorno_horas /60), 2)." horas";

						//number_format ($tota_geral,2,",",".")

				}
			}

			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

			$inicio =  $data_abertura.' '.$hora_abertura;;
			$fim    =  $data_hora_chegada_cliente .' '.$hora_chegada_cliente ;

			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td style='font-size: 9px; font-family: verdana' nowrap >";
			if ($os_manutencao == 't'){
				echo "<a href='os_print_manutencao.php?os_manutencao=$os'  target='_blank'>".$sua_os."</a>";
			}else{
				echo "<a href='os_press.php?os=$os'  target='_blank'>".$sua_os."</a>";
			}
			echo "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana'>".$consumidor_nome. "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$tipo_atedimento."</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>". $codigo_posto ." - $nome_posto</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>". $inicio ."</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>". $fim ."</td>";
			echo "<td align='right' style='font-size: 9px; font-family: verdana' nowrap>$horas h $minutos min. </td>";
			echo "</tr>";
		}

		echo "<tr>";
		echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> &nbsp; </td>";
		echo "</tr>";
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "</form>";
	}else{
		echo "<center>Nenhum resultado encontrado.</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>