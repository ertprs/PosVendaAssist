<?php
/**
 * Relatório de dúvidas de produto do callcenter
 * HD 129655
 *
 * @since 2009 07 21
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "call_center";
$layout_menu       = "callcenter";
$title             = "RELATÓRIO DE ATENDIMENTO DE DÚVIDAS";
include 'autentica_admin.php';

/**
 * Converte data de BR para ISO (Banco)
 *
 * @param string $date dd/mm/aaaa
 * @return string|false aaaa-mm-dd
 */
function converte_data($date) {
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

/**
 * Abrevia a string e retorna a TAG html com a abreviação
 *
 * @param string $string A string a ser abreviada
 * @param int[optional] $limit Limite de caracteres da abreviação
 * @return string tag html com a abreviação e a string completa
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
function abbreviation($string, $limit = 20) {
	if ( strlen($string) <= $limit ) {
		return $string;
	}
	$limit_ = $limit + 3;
	$short  = substr($string,0,$limit_).'...';
	return "<abbr title=\"{$string}\">{$short}</abbr>";
}

$aDados   = array(); // Dúvidas agrupadas por produto e por situação (faq) $array[produto][faq]
$aTotais  = array(); // Totais de dúvidas por produto $array[produto]['total']
$msg_erro = '';
if ( count($_GET) > 0 ) {
	// !Validação dos dados de consulta
	$datas = array('data_ini','data_fim');
	foreach ($datas as $name) {
		if ( empty($_GET[$name]) ) { continue; }
		list($dia,$mes,$ano) = explode('/',$_GET[$name]);
		if ( checkdate($mes,$dia,$ano) ) {
			$_GET[$name] = $_GET[$name];
			continue;
		}
		// Data com problema
		$label = ( $name == 'data_ini' ) ? 'Data Inicial' : 'Data Final' ;
		$msg_erro .= "<p> <em>{$label}</em> é inválida. </p>";
	}
	if ( empty($_GET['data_ini']) && empty($_GET['data_fim']) ) {
		$msg_erro .= "<p> Por favor, especifique pelo menos uma das datas abaixo.</p>";
	}
	// !Consulta
	if ( strlen($msg_erro) == 0 ) {
		$sql_where = array();
		$check     = array('data_ini'=>'tbl_hd_chamado.data','data_fim'=>'tbl_hd_chamado.data','referencia'=>'tbl_produto.referencia');
		foreach ( $check as $name=>$col ) {
			$val = $_GET[$name];
			switch($name) {
				case 'data_ini':
					$op  = '>=';
					$val = converte_data($val).' 00:00:00';
					break;
				case 'data_fim';
					$op  = '<=';
					$val = converte_data($val).' 23:59:59';
					break;
				default:
					$op = '=';
					break;
			}
			if ( ! empty($_GET[$name]) ) {
				$val = strtoupper(pg_escape_string($val));
				$sql_where[] = " AND {$col} {$op} '{$val}'";
			}
		}
		$sql_where = implode(' ',$sql_where);
		$sql = "SELECT tbl_faq.faq, tbl_faq.produto, tbl_faq.situacao, tbl_produto.produto,
					   tbl_produto.descricao, tbl_produto.referencia
				FROM tbl_hd_chamado_faq
				INNER JOIN tbl_hd_chamado USING (hd_chamado)
				INNER JOIN tbl_faq USING (faq)
				INNER JOIN tbl_produto USING (produto)
				WHERE 1=1
				AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
				{$sql_where}";
		$res = pg_exec($con,$sql);
		if ( ! is_resource($res) ) {
			$msg_erro  = "<p> <strong>Erro na consulta</strong>: ".pg_errormessage($con).'</p>';
		}
		// Agrupamento de dúvidas por produto e situação
		while ($row = pg_fetch_assoc($res)) {
			$faq  = $row['faq'];
			$prod = $row['produto'];
			if ( ! isset($aDados[$prod][$faq]) ) {
				$aDados[$prod][$faq]          = $row;
				$aDados[$prod][$faq]['total'] = 0;
			}
			if ( ! isset($aTotais[$prod]) ) {
				$aTotais[$prod]          = $row;
				$aTotais[$prod]['total'] = 0 ;
			}
			$aDados[$prod][$faq]['total']++;
			$aTotais[$prod]['total']++;
		}
	}
}


?>
<?php include 'cabecalho.php'; ?>
<?php include 'javascript_calendario.php'; ?>
<? include "javascript_pesquisas.php" ?>
<script type="text/javascript" language="javascript">
$(function() {
	$('.datepicker').datePicker({startDate:'20/07/2009'}).maskedinput("99/99/9999");
	$('#img-enviar').click(function() {
		$('#form-duvidas').submit();
	});

	function pesquisa_popup_duvidas() {
		var referencia = $('#referencia').get(0);
		var descricao  = $('#descricao').get(0);
		var action     = $(this).parent().find('input').attr('id');
		fnc_pesquisa_produto (referencia,descricao,action);
	}
	$('#referencia,#descricao').blur(pesquisa_popup_duvidas);
	$('.lupa').click(pesquisa_popup_duvidas);

});
</script>
<style type="text/css" media="all">
#parametros {
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-weight: normal;
	font-size: 8pt;
	width: 500px;
	margin: 10px auto;
}
#parametros td {
	background-color: #D9E2EF;
}
thead td,th {
	color: white;
	background-color: #596D9B;
	text-align: center;
	font-family: Verdana,Arial,Helvetica,sans-serif;
	font-size: 8pt;
	font-weight: bold;
}
#parametros label {
	font-weight: bold;
	width: 100%;
	display: block;
	text-align: center;
}
.lupa {
	cursor: pointer;
}
.table_line,.azul tbody td {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}
.box {
	display: block;
	width: 350px;
	margin: 10px auto;
	padding: 5px;
	text-align: center;
}
.erro {
 border: 1px solid #CC3300;
 background-color: #F7503E;
 font-size:10px;
}
</style>

<?php if ( strlen($msg_erro) > 0 ): ?>
	<div class="box erro"> <?php echo $msg_erro; ?> </div>
<?php endif; ?>
<!-- !Parametros de consulta -->
<form id="form-duvidas" action="" method="GET">
<table id="parametros" border="0" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3">Parâmetros de busca do Relatório</th>
		</tr>
	</thead>
	<tbody>
		<tr> <td colspan="3">&nbsp;</td> </td>
		<tr>
			<td colspan="3">
				Informe alguns dos valores abaixo para obter o relatório desejado
			</td>
		</tr>
		<tr> <td colspan="3">&nbsp;</td> </td>
		<tr>
			<td  rowspan="2" width="100px"><label>Datas:</label></td>
			<td >Data Inicial</td>
			<td >Data Final</td>
		</tr>
		<tr>
			<td >
				<INPUT size="12" maxlength="10" TYPE="text" NAME="data_ini" id="data_ini" class="datepicker" value="<?php echo $_GET['data_ini']; ?>" />
			</td>
			<td >
				<INPUT size="12" maxlength="10" TYPE="text" NAME="data_fim" id="data_fim" class="datepicker" value="<?php echo $_GET['data_fim']; ?>" />
			</td>
		</tr>
		<tr>
			<td  rowspan="2"><label> Produto: </label></td>
			<td >Referência</td>
			<td >Descrição</td>
		</tr>
		<tr>
			<td  align="left">
				<input type="text" name="referencia" id="referencia" pesquisa="referencia" value="<?php echo $_GET['referencia']; ?>" />
				<img id="popup_prod" pesquisa="referencia" class="lupa" src="imagens/lupa.png" />
			</td>
			<td  align="left">
				<input type="text" id="descricao" name="descricao" pesquisa="descricao" value="<?php echo $_GET['descricao'] ?>" />
				<img id="popup_descr" pesquisa="descricao" class="lupa" src="imagens/lupa.png" />
			</td>
		</tr>
		<tr> <td colspan="3">&nbsp;</td> </td>
		<tr> <td colspan="3">&nbsp;</td> </td>
		<tr>
			<td colspan="3" align="center">
				<img alt="Preencha as opções e clique aqui para pesquisar" style="cursor: pointer;" id="img-enviar" src="imagens_admin/btn_pesquisar_400.gif"/>
			</td>
		</td>
	</tbody>
</table>
</form>

<?php if (  count($aDados) > 0 ): ?>
<!-- !Total de chamados por produto -->
<table class="azul" width="600px" align="center">
	<thead>
		<tr>
			<td colspan="2"> Total de chamados de dúvida por produto </td>
		</tr>
		<tr>
			<th> Produto </th>
			<th> Quantidade </th>
		</tr>
	<thead>
	<tbody>
		<?php foreach ($aTotais as $prod=>$row): ?>
			<tr>
				<td > <?php echo abbreviation($row['referencia'].'-'.$row['descricao']); ?> </td>
				<td> <?php echo $row['total']; ?> </td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<p> &nbsp; </p>

<!-- !Total de chamados agrupados por produto e dúvida -->
<table class="azul" width="600px" align="center">
	<thead>
		<tr>
			<td colspan="4"> Situações por Produtos </td>
		</tr>
		<tr>
			<th> Produto </th>
			<th> Situação </th>
			<th> Quantidade </th>
		</tr>
	<thead>
	<tbody>
		<?php $rowspan = true; ?>
		<?php foreach ($aDados as $prod=>$each): ?>
			<?php foreach ($each as $faq=>$row): ?>
				<tr>
					<?php if ( $rowspan ): ?>
						<td rowspan="<?php echo count($each) ?>"> <?php echo abbreviation($row['referencia'].'-'.$row['descricao']); ?> </td>
					<?php endif; ?>
					<td> <?php echo abbreviation($row['situacao']); ?> </td>
					<td> <?php echo $row['total']; ?> </td>
				</tr>
				<?php $rowspan = false; ?>
			<?php endforeach; ?>
			<?php $rowspan = true; ?>
		<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>

<?php include 'rodape.php'; ?>