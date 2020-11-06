<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

//HD6481 - Tectoy
//HD 17677 HBFLEX - 04/06/2008
//HD 30492 - colormaq
if ( !in_array($login_fabrica, array(2,11,25,50,172)) ) {
	echo "<h1><center>Fechamento de Extrato realizado pela TELECONTROL</center></h1>";
	exit;
}

// Lista de OS do extrato (AJAX)
if (isset($_GET['lista_os'])) {
	include_once '../helpdesk/mlg_funciones.php';
	$posto = (int)$_GET['posto'];
	$corte = is_date($_GET['data_limite']);
    if (in_array($login_fabrica,array(11,172))) {
    	$corte2 = "AND    OS.data_fechamento::date BETWEEN ('$corte 00:00:00'::date - INTERVAL '1 year') AND '$corte 00:00:00'::date ";
    }else{
    	$corte2 = "AND OS.data_fechamento   <= '$corte'";
    }

	if (!$corte)
		die('Data limite não informada!');

	pg_query($con, "SET DateStyle TO SQL, European");

	$sql = <<<SQL_LISTA_OS
        SELECT OS.os, sua_os,
               data_abertura,
               data_fechamento,
               COALESCE(OS.mao_de_obra, 0.0) AS mo
          FROM tbl_os AS OS
          JOIN tbl_os_extra ON OS.os = tbl_os_extra.os
                           AND tbl_os_extra.i_fabrica = OS.fabrica
         WHERE OS.posto             =  $posto
           AND OS.fabrica           =  $login_fabrica
           AND OS.finalizada        IS NOT NULL
           $corte2
           AND OS.excluida          IS NOT TRUE
           AND tbl_os_extra.extrato IS NULL
SQL_LISTA_OS;

	if ($login_fabrica == 50)
		$cond_colormaq ="
           AND tbl_os.os NOT IN (
               SELECT interv.os
                 FROM (
                   SELECT ultima.os, (
                     SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1
                   ) AS ultimo_status
                     FROM (
                       SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (98,99,100,68,70,115)
                     ) ultima
                 ) interv
                WHERE interv.ultimo_status IN (98,68,70,115)
               )";
	$res = pg_query($con, $sql . " ORDER BY data_abertura");

	if ($_serverEnvironment == 'development' and !is_resource($res)) {
		pecho(pg_last_error($con));
		pre_echo($sql, "Erro na consulta, tente novamente daqui uns segundos.");
	}

	if (!pg_num_rows($res)) {
		die('Sem resultados.');
	}

	$tabela = array(
		'attrs' => array(
			'tableAttrs' => 'class="tabela2"',
			'headerAttrs' => 'class="titulo_coluna"'
		)
	);

	$resData = pg_fetch_all($res);

	foreach ($resData as $row) {
		$tabela[] = array(
			// 'OS' => createHTMLLink("os_press.php?os={$row['os']}", $row['sua_os'], 'target="blank"'),
			'OS' => sprintf("<a href='os_press.php?os=%s' target='blank'>%s</a>", $row['os'], $row['sua_os']),
			'Data de Abertura'   => $row['data_abertura'],
			'Data de Fechamento' => $row['data_fechamento'],
			'M. de Obra' => number_format($row['mo'],2,',','.')
		);
	}

	echo <<<HTML
		<link type="text/css" href="css/css.css" rel='stylesheet' />
		<link type="text/css" href="js/blue/style.css" rel="stylesheet" id="" media="print, projection, screen" />
		<style type="text/css">
		.tabela2 {
			margin: 5% auto;
			width: 80%;
		}
		.tabela2 td {text-align: center;}
		.tabela2 td:last-of-type {
			text-align: right;
		}
		</style>
HTML;

	die(array2table($tabela));
}

// Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
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


$msg_erro = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET ["acao"]) > 0) $acao = strtoupper($_GET ["acao"]);


$btn_extrato = $_POST['btn_extrato'];

//hd 30492 - incluido colormaq
if (strlen ($btn_extrato) > 0 && ( in_array($login_fabrica, array(11,25,50,172)) )) {
	$qtde_extrato = $_POST['qtde_extrato'];

	$data_limite = $_POST['data_limite'];

	for ($i = 0 ; $i < $qtde_extrato ; $i++) {
		$posto = $_POST['gerar_' . $i];
		if (strlen ($posto) > 0) {
			$res = pg_exec($con,"BEGIN TRANSACTION");

			$sql = "UPDATE tbl_posto_fabrica SET extrato_programado = '$data_limite'
						WHERE posto = $posto
						AND fabrica = $login_fabrica;";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	//echo "$sql";
	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}


$layout_menu = "financeiro";
$title = "Pré Fechamento de Extrato";

include "cabecalho.php";

//hd 15622
//if ($login_fabrica==11) {
//	echo "<BR><BR><BR><BR><BR><CENTER>Programa em manutenção, aguarde alguns instantes.</CENTER>";
//	exit;
//}

?>

<style type="text/css">
	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
	}
	.Conteudo {
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
	}
	#sb-body {background: white url()!important;}
	.tabela2 tbody td.lista_os {
		padding-right: 1ex;
		font-weight: bold;
		color: darkblue;
		cursor: pointer;
	}
	td.lista_os:hover {text-decoration: underline;}
</style>

<?php include "../js/js_css.php"; ?>

<script type="text/javascript" charset="utf-8">
	
$(function() {
	Shadowbox.init({loading: '', modal: true, onLoad: false});
});
	function toQueryString(obj, sep) {
		if (!typeof obj === 'object')
			return '';
		var r=[],
			joinStr = sep || '&';

		for (var k in obj)
			r.push(encodeURIComponent(k)+'='+encodeURIComponent(obj[k]));
		return r.join(joinStr);
	}

	function fnc_pesquisa_posto (campo, campo2, tipo) {
		if (tipo == "nome" ) {
			var xcampo = campo;
		}

		if (tipo == "cnpj" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url        = "";
			url            = "posto_pesquisa.php?transp=f&campo=" + xcampo.value + "&tipo=" + tipo;
			janela         = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
			janela.retorno = "<? echo $PHP_SELF ?>";
			janela.nome    = campo;
			janela.cnpj    = campo2;
			janela.focus();
		}
	}

	$(function(){
		$('#data_limite_01a').datepick({startDate:'01/01/2000'});
		$("#data_limite_01a").mask("99/99/9999");

		function formatItem(row) {
			return row[0] + " - " + row[1];
		}

		function formatResult(row) {
			return row[0];
		}

		/* Busca pelo Código */
		$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
			minChars: 5,
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

		$(":checkbox").click(function() {
			var color = $(this).is(':checked') ? '#eeeeee' : '#ffffff';
			$(this).parents('tr').attr({bgColor: color});
		}) ;

		$(".lista_os").click(function() {
			var querydata = {
				lista_os: 'listar_os',
				posto: $(this).data('posto'),
				data_limite: $("#data_limite_01a").val()
			};
			Shadowbox.open({
				content: document.location.pathname + '?' + toQueryString(querydata),
				player: "iframe",
				title:  "OS do extrato",
				width:  800,
				height: 500
			});
		});
	});
</script>

<? if (strlen($msg_erro) > 0){ ?>
<br>
<table width="420" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><? echo $msg_erro; ?></td>
	</tr>
</table>
<? } ?>


<?

$data_limite = $_POST['data_limite_01'];

?>
<form method="post" action="<?echo $PHP_SELF?>" name="FormExtrato">
<table width="500" border="0" cellpadding="2" cellspacing="0" align="center">

<input type="hidden" name="btn_acao">
	<tr class="Titulo">
		<td height="30" COLSPAN='3'>Informe a Data Limite do Fechamento das OS para geração dos extratos.</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width='35%'></td>
		<td align='left'>
			Data Limite<br>
			<input type="text" name="data_limite_01" id="data_limite_01a" size="13" maxlength="10" value="<? if (strlen($data_limite) > 0) echo $data_limite;?>"  class="frm">
		</td>
		<td width='35%'></td>
	</tr>
	<?
echo "<TR  class='Conteudo' bgcolor='#D9E2EF'>\n";
echo "	<TD  ALIGN='center' nowrap COLSPAN='3'>";
echo "CNPJ";
echo "		<input type='text' name='posto_codigo' id='posto_codigo' size='15' value='$posto_codigo' class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.FormExtrato.posto_nome,document.FormExtrato.posto_codigo,'cnpj')\">";

echo "&nbsp;&nbsp;Razão Social ";
echo "<input type='text' name='posto_nome' id='posto_nome' size='30' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.FormExtrato.posto_nome,document.FormExtrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "<TR>\n";
?>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td align="center" COLSPAN='3'><img src="imagens_admin/btn_pesquisar_400.gif" onClick="javascript: document.FormExtrato.btn_acao.value='BUSCAR'; document.FormExtrato.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>
</form>

<?
$btn_acao     = trim($_POST['btn_acao']);
$data_limite  = trim($_POST['data_limite_01']);
$posto_codigo = trim($_POST['posto_codigo']);
$posto_nome   = trim($_POST['posto_nome']);

if (strlen ($btn_acao) > 0) {

	if ($data_limite == "dd/mm/aaaa"){
		$msg_erro .= "Digite a data limite";
	}
	if (strlen($data_limite)==0){
		$msg_erro .= "Digite a data limite";
	}
	$data_limite = str_replace("-", "", $data_limite);
	$data_limite = str_replace("_", "", $data_limite);
	$data_limite = str_replace(".", "", $data_limite);
	$data_limite = str_replace(",", "", $data_limite);
	$data_limite = str_replace("/", "", $data_limite);

	if (strlen($data_limite)>0){
		$data_limite2 = substr ($data_limite,4,4) . "-" . substr ($data_limite,2,2) . "-" . substr ($data_limite,0,2)." 23:59:59";
			if( in_array($login_fabrica, array(11,172)) ){
				$sql_data = "AND    tbl_os.data_fechamento::date BETWEEN ('$data_limite2'::date - INTERVAL '1 year') AND '$data_limite2'::date ";
			}else{
				$sql_data = "AND    tbl_os.data_fechamento <= '$data_limite2' ";
			}
	}
	$cond_0 = '';
	if (strlen($posto_codigo)>0) {
		$posto_codigo = str_replace("-", "", $posto_codigo);
		$posto_codigo = str_replace("_", "", $posto_codigo);
		$posto_codigo = str_replace(".", "", $posto_codigo);
		$posto_codigo = str_replace(",", "", $posto_codigo);
		$posto_codigo = str_replace("/", "", $posto_codigo);

		$sql = "Select posto from tbl_posto where cnpj='$posto_codigo'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,0);
			$cond_0 = "AND tbl_os.posto           = $posto ";
		}
	}else{
		//hd 30492 - incluido colormaq
		if( in_array($login_fabrica, array(11,25,50,172)) ){
			$msg_erro = "Por favor, digite o Posto.";
		}
	}

	//HD 30492
	$cond_colormaq = '';
	if ($login_fabrica == 50) {
		$cond_colormaq = "AND tbl_os.os NOT IN (
								SELECT interv.os
								FROM (
									SELECT ultima.os,
										   (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
									FROM (
										SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (98,99,100,68,70,115) ) ultima
								) interv
								WHERE interv.ultimo_status IN (98,68,70,115)
							) ";
	}

	if (strlen($msg_erro)==0) {
		$sql = "SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto_fabrica.extrato_programado,
						tbl_posto.posto,
						tbl_posto.nome,
						os.qtde,
						os.mo
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN  (SELECT tbl_os.posto, COUNT(tbl_os.os) AS qtde, SUM(tbl_os.mao_de_obra) AS mo
						 FROM tbl_os
						 JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.i_fabrica = tbl_os.fabrica
						WHERE tbl_os.finalizada      IS NOT NULL
						  AND tbl_os.data_fechamento IS NOT NULL
						  $sql_data
						  AND tbl_os.excluida        IS NOT TRUE
						  AND tbl_os_extra.extrato   IS NULL
						  AND tbl_os.fabrica          = $login_fabrica
						  $cond_0
						  $cond_colormaq
						GROUP BY tbl_os.posto
				) os ON tbl_posto.posto = os.posto
				ORDER BY tbl_posto.nome";
		$res = pg_query($con, $sql);

		$rows = pg_num_rows($res);
		$data_limite = substr($data_limite2,0,10);

		if ($rows>0) {
			$i = 0;
			while ($row = pg_fetch_row($res)) {
				list($posto_codigo, $extrato_programado, $posto, $posto_nome, $qtde_extrato, $mo) = $row;
				$tdExtrato = $extrato_programado ? 'Agendado' : "<input id='chk_$i' type='checkbox' name='gerar_$i' value='$posto'>";
				$mo = number_format($mo, 2, ',', '.');
				$tbody .= "
					<tr id='linha_$i' align='center'>
						<td align='center'>$tdExtrato</td>
						<td>$posto_codigo</td>
						<td>$posto_nome</td>
						<td data-posto='$posto' class='lista_os' align='right'>$qtde_extrato</td>
						<td align='right'> R$ $mo</td>
					</tr>";
				$i++;
			}
			$tbody .= "
					<tr style='background:#D9E2EF'>
						<td colspan='5' align='center'>
							<input type='hidden' name='qtde_extrato' value='$rows'>
							<input type='hidden' name='data_limite' value='$data_limite'>
							<input type='submit' name='btn_extrato' value='Gerar Extratos'>
						</td>
					</tr>
			"; ?>
			<p>&nbsp;</p>
			<form method='POST' name="frm_extrato">
				<table width="700" align='center' class='tabela2'>
					<thead>
						<tr class='titulo_tabela'>
							<td>Agendar</td>
							<td>Código</td>
							<td>Razão</td>
							<td>Qtde. OS</td>
							<td>Valor Total</td>
						</tr>
					</thead>
					<tbody>
						<?=$tbody?>
					</tbody>
				</table>
			</form>

<?php
			if ($rows == 0) {
				echo "<h2>Nenhum resultado encontrado</h2>";
			}
		}

		if (strlen($msg_erro)>0) {
			echo "<h2 style='background-color:red;color:white'>$msg_erro</h2>";
		}
	}
}
?>
<br>

<script>
</script>
<?php
include "rodape.php";

