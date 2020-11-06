<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Content-Type: text/html; charset=iso-8859-1");

$fabrica_defeito_select = array(52, 136);

$ajax = $_GET['ajax'];

if (!strlen($ajax)) die;

$ajax_natureza = $_GET['natureza'];
$produto_referencia = $_GET['produto'];
$defeito = $_GET['defeito'];
$fale_conosco = $_GET['fale_conosco'];

function response($payload, $HttpCode=200, $contentType='text/html') {
	global $fabrica_defeito_select;

	if (!$payload and $HttpCode=200) die;

	if (is_string($payload)) {
		if (in_array($login_fabrica, $fabrica_defeito_select)) {
			$payload = "<option value=''>$payload</option>";
		} else {
			$payload = "<font size='1'>$payload</font>";
		}
	}

	if (is_array($payload)) {
		switch ($contentType) {
			case 'application/json':
				// Não deve ter nenhum valor 'null' ou 'false', pode usar o array_filter
				$payload = array_filter($payload, 'utf8_encode');
				$payload = json_encode($payload);
				$HttpEncoding = 'charset=utf-8';
			break;

			case 'text/csv':
				// código para CSV aqui
			break;

			default:
				$options = '<option value=""></option>';
				foreach ($payload as $value => $text) {
					$options .= createHtmlOption($value, $text);
				}
				$payload = $options;
				$HttpEncoding = 'charset=iso-8859-15';
			break;
		}
	}
	header("Content-Type: $contentType; $HttpEncoding");
	die($payload);
}

if (!strlen($produto_referencia))
	response("Selecione um produto");

if ($login_fabrica == 175){
	$sql = "SELECT linha
			  FROM tbl_produto
			 WHERE referencia = '{$produto_referencia}'
			   AND fabrica_i  = {$login_fabrica}
			 LIMIT 1";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$linha = pg_fetch_result($res,0,0);
		if (strlen($linha) == 0) {
			response("Produto sem linha Cadastrada");
		}
		$cond_1 = "AND tbl_produto.linha = {$linha}";
	}
}else{
	$sql = "SELECT familia
			  FROM tbl_produto
			 WHERE referencia = '{$produto_referencia}'
			   AND fabrica_i  = {$login_fabrica}
			 LIMIT 1";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$familia = pg_fetch_result($res,0,0);
		if (strlen($familia) == 0) {
			response("Produto sem família Cadastrada");
		}
		$cond_1 = "AND tbl_produto.familia = {$familia}";
	}
}
$sql = '';
switch ($login_fabrica) {
	case 25:
		$sql = "SELECT DISTINCT
			           tbl_defeito_reclamado.defeito_reclamado,
			           tbl_defeito_reclamado.descricao
			      FROM tbl_defeito_reclamado
			      JOIN tbl_produto ON tbl_defeito_reclamado.linha   = tbl_produto.linha
			                      AND tbl_defeito_reclamado.familia = tbl_produto.familia
			     WHERE tbl_defeito_reclamado.fabrica = {$login_fabrica}
			       AND tbl_defeito_reclamado.ativo  IS TRUE
			       {$cond_1}
			     ORDER BY tbl_defeito_reclamado.descricao";
	break;

	case 50: 
		$sql = "SELECT 
				    tbl_diagnostico.defeito_reclamado, 
				    tbl_defeito_reclamado.descricao 
				FROM tbl_diagnostico 
				INNER JOIN tbl_produto ON tbl_produto.familia = tbl_diagnostico.familia 
				INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado 
				WHERE  
				    tbl_produto.fabrica_i = {$login_fabrica} 
				    AND tbl_diagnostico.fabrica = {$login_fabrica} 
				    AND tbl_defeito_reclamado.fabrica = {$login_fabrica} 
				    AND tbl_defeito_reclamado.ativo IS TRUE 
				    AND tbl_diagnostico.ativo IS TRUE 
				    {$cond_1} 
				ORDER BY tbl_defeito_reclamado.descricao ASC";
	break;

	case 101: 
		$sql = "SELECT 
				    tbl_diagnostico.defeito_reclamado, 
				    tbl_defeito_reclamado.descricao 
				FROM tbl_diagnostico 
				INNER JOIN tbl_produto ON tbl_produto.familia = tbl_diagnostico.familia 
				INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado 
				WHERE  
				    tbl_produto.fabrica_i = {$login_fabrica} 
				    AND tbl_diagnostico.fabrica = {$login_fabrica} 
				    AND tbl_defeito_reclamado.fabrica = {$login_fabrica} 
				    AND tbl_defeito_reclamado.ativo IS TRUE 
				    AND tbl_diagnostico.ativo IS TRUE 
				    {$cond_1} 
				ORDER BY tbl_defeito_reclamado.descricao ASC";
	break;

	case 52:
		$sql = "SELECT DISTINCT
			           tbl_defeito_reclamado.defeito_reclamado,
			           tbl_defeito_reclamado.descricao
			      FROM tbl_defeito_reclamado
			      JOIN tbl_diagnostico USING(defeito_reclamado, fabrica)
			      JOIN tbl_produto     ON tbl_diagnostico.familia = tbl_produto.familia AND tbl_produto.fabrica_i=$login_fabrica
			     WHERE tbl_defeito_reclamado.fabrica = $login_fabrica
			       {$cond_1}
			       AND tbl_defeito_reclamado.ativo IS TRUE
			  ORDER BY tbl_defeito_reclamado.descricao";
	break;

	case 139:
		$sql = "SELECT DISTINCT
			           tbl_defeito_reclamado.defeito_reclamado,
			           tbl_defeito_reclamado.descricao
			      FROM tbl_defeito_reclamado
			      JOIN tbl_diagnostico USING(defeito_reclamado, fabrica)
			      JOIN tbl_produto     ON tbl_diagnostico.familia = tbl_produto.familia AND tbl_produto.fabrica_i=$login_fabrica
			     WHERE tbl_defeito_reclamado.fabrica = $login_fabrica
			       {$cond_1}
			       AND tbl_defeito_reclamado.ativo IS TRUE
			  ORDER BY tbl_defeito_reclamado.descricao";
	break;
	case 175:
		$sql = "SELECT DISTINCT
			           tbl_defeito_reclamado.defeito_reclamado,
			           tbl_defeito_reclamado.descricao
			      FROM tbl_defeito_reclamado
			      JOIN tbl_diagnostico USING(defeito_reclamado, fabrica)
			      JOIN tbl_produto     ON tbl_diagnostico.linha = tbl_produto.linha AND tbl_produto.fabrica_i=$login_fabrica
			     WHERE tbl_defeito_reclamado.fabrica = $login_fabrica
			       {$cond_1}
			       AND tbl_defeito_reclamado.ativo IS TRUE
			  ORDER BY tbl_defeito_reclamado.descricao";
	break;

	case 136:
		$sql = "SELECT DISTINCT
			           tbl_defeito_reclamado.defeito_reclamado,
			           tbl_defeito_reclamado.descricao
			      FROM tbl_diagnostico_produto
			      JOIN tbl_diagnostico       USING(diagnostico, fabrica)
			      JOIN tbl_defeito_reclamado USING(defeito_reclamado, fabrica)
			      JOIN tbl_produto           USING(produto)
			     WHERE fabrica = $login_fabrica
			       {$cond_1}
			       AND tbl_defeito_reclamado.ativo IS TRUE
			       AND tbl_diagnostico.ativo       IS TRUE
			       AND tbl_produto.referencia = '$produto_referencia'
			    UNION\n			    ";
		// O fato de não ter `break` é intencionado!

	default:
		$sql.= "SELECT DR.defeito_reclamado,
			           DR.descricao
			      FROM tbl_diagnostico DI
			      JOIN tbl_defeito_reclamado DR ON DR.defeito_reclamado = DI.defeito_reclamado
			      JOIN tbl_produto              ON tbl_produto.familia  = DI.familia AND tbl_produto.fabrica_i=$login_fabrica
			     WHERE DI.fabrica = $login_fabrica
			       AND DI.ativo IS TRUE
			       $cond_1
			    UNION
			    SELECT FDR.defeito_reclamado,
			           DR.descricao
			      FROM tbl_familia_defeito_reclamado FDR
			      JOIN tbl_defeito_reclamado DR
			            ON DR.defeito_reclamado = FDR.defeito_reclamado
			           AND DR.fabrica = $login_fabrica
			     ORDER BY descricao";
	break;
}

$drData = pg_fetch_pairs($con, $sql);

if ($drData === false or !count($drData)) {
	response("Nenhuma informação encontrada");
}

if ($login_fabrica == 52) {
	response($drData);
}

// Resto de fabricantes: monta a tabela do bloco completo
//HD-3282875 Adicionada fábrica 50
if (in_array($login_fabrica, array(11,15,30,50,74,81,90,101,114,115,116,117,120,201,122,123,125,128,129)) || isset($novaTelaOs) || $login_fabrica > 130) { //HD 763537
	$selHtml = array2select(
		'defeito_reclamado', 'defeito_reclamado',
		$drData, $defeito,
		'', 'Selecione o Defeito', true
	);
	$selHtml = "<td>\n\t$selHtml</td>";
} else {
	$selHtml = '';
	$x = 0;
	foreach ($drData as $defeito_reclamado => $descricao) {
		$selHtml .= "
		<td align='left'>
			<label>
				<input type='radio' name='defeito_reclamado' value='$defeito_reclamado' />
				<font size='1'>$descricao</font>
			</label>
		</td>";
		$setHtml .= ($x++ % 3 == 0) ? '</tr><tr>' : '';
	}
}

die("
	<table width='100%' border='0' align='center' cellpadding='0' cellspacing='2'>
	  <tr>
	    $selHtml
	  </tr>
	</table>
");

