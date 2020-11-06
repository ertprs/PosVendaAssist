<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

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

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj		  = trim(pg_fetch_result($res,$i,cnpj));
				$nome		  = trim(pg_fetch_result($res,$i,nome));
				$nome 		  = $nome;
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

if($_GET['ajax']=='campo')
{
	try {
		$extrato = intval($_REQUEST["extrato"]);
		$tipo_nota = $_REQUEST["tipo_nota"];
		$nota_fiscal = intval($_REQUEST["nota_fiscal"]);
		$serie_nota_fiscal = $_REQUEST["serie_nota_fiscal"];
	    $data_nota = $_REQUEST["data_nota"];
		$simnao = $_REQUEST["simnao"];
		$faturamento = intval($_REQUEST["faturamento"]);

		// VALIDANDO EXTRATO
		$sql = "
		SELECT
		extrato

		FROM
		tbl_extrato

		WHERE
		extrato={$extrato}
		AND fabrica={$login_fabrica}
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) throw new Exception("Ocorreu um erro interno: extrato não encontrado");

		// VALIDANDO TIPO DE NOTA
		if ($tipo_nota != "Peca" && $tipo_nota != "Servico") throw new Exception("Ocorreu um erro interno: tipo de nota inválido");

		// VALIDANDO NOTA FISCAL
		if ($nota_fiscal == 0) throw new Exception("Preencha a nota fiscal");

		// VALIDANDO DATA DA NOTA FISCAL
		if (strlen($data_nota) > 0) {
	        list($d, $m, $y) = explode("/", $data_nota);
	        if(!checkdate($m,$d,$y)) throw new Exception("Data Inválida");
	        $data_nota = "{$y}-{$m}-{$d}";

	        if (strtotime($data_nota) > strtotime('today')) throw new Exception("Data Inválida");
	    }
	    else  {
	    	throw new Exception("Preencha a data da nota fiscal");
	    }

	    // VALIDANDO SIMNAO (OPTANTE SIMPLES NACIONAL)
	    if ($tipo_nota == "Peca" && $simnao != "t" && $simnao != "f") throw new Exception("Valor inválido para o campo 'Optante pelo Simples Nacional'");

	    // VALIDANDO FATURAMENTO
	    if ($faturamento > 0) {
	    	$sql = "
	    	SELECT
	    	faturamento

	    	FROM
	    	tbl_faturamento

	    	WHERE
	    	fabrica={$login_fabrica}
	    	AND extrato_devolucao={$extrato}
	    	AND faturamento={$faturamento}
	    	";
	    	@$res = pg_query($con, $sql);
			if (strlen(pg_last_error($con)) > 0) throw new Exception("Ocorreu um erro interno: faturamento inválido");
	    }
	    else if ($tipo_nota == "Peca") {
	    	throw new Exception("Ocorreu um erro interno: faturamento inválido");
	    }

	    // ATUALIZANDO BANCO DE DADOS
		@$res = pg_query($con, "BEGIN");
		if (strlen(pg_last_error($con)) > 0) throw new Exception("falha_atualizar");

		if($tipo_nota == "Servico")
		{
			$sql = "
			UPDATE tbl_extrato_extra SET
			nota_fiscal_mao_de_obra='{$nota_fiscal}',
			nota_fiscal_serie_mao_de_obra='{$serie_nota_fiscal}',
			emissao_mao_de_obra='{$data_nota}'

			WHERE
			extrato={$extrato}
			";
			$res = pg_query($con, $sql);
			if (strlen(pg_last_error($con)) > 0) throw new Exception("falha_atualizar");

			if($login_fabrica == 74)
			{
				$sql = pg_query ("UPDATE tbl_extrato_extra SET exportado=NULL WHERE extrato={$extrato}");
				if (strlen(pg_last_error($con)) > 0) throw new Exception("falha_atualizar");
			}
		}
		elseif($tipo_nota == "Peca")
		{
			$sql = "
			UPDATE tbl_extrato_devolucao SET
			optante_simples_nacional = '{$simnao}'

			WHERE
			extrato = {$extrato}
			";
			@$res = pg_query($sql);
			if (strlen(pg_last_error($con)) > 0) throw new Exception("falha_atualizar");

			$sql = "
			UPDATE tbl_faturamento SET
			nota_fiscal = '{$nota_fiscal}',
			serie = '{$serie_nota_fiscal}',
			emissao = '{$data_nota}'

			WHERE
			faturamento={$faturamento}
			";
			@$res = pg_query($sql);
			if (strlen(pg_last_error($con)) > 0) throw new Exception("falha_atualizar");

			if($login_fabrica == 74)
			{
				@$res = pg_query("UPDATE tbl_faturamento SET baixa=NULL WHERE faturamento={$faturamento}");
				if (strlen(pg_last_error($con)) > 0) throw new Exception("falha_atualizar");
			}
		}

		@$res = pg_query($con, "COMMIT");
		echo "ok|Dados atualizados com sucesso";

	}
	catch (Exception $e) {
		@$res = pg_query($con, "ROLLBACK");
		$erro = $e->getMessage() == "falha_atualizar" ? "Falha ao atualizar os dados" : $e->getMessage();
		echo "no|" . $erro;
	}

	exit;
}

if($_GET['ajax']=='excluir')
{
	try{
		$extrato = $_GET['extrato'];
		$faturamento = $_GET['faturamento'];
		$tipo_nota = $_GET['tipo_nota'];

		$res = pg_query($con, "BEGIN");
		if($tipo_nota == "Peca"){
			$sql = "UPDATE tbl_extrato_devolucao SET
					nota_fiscal 		= null,
					total_nota 			= null,
					base_icms 			= null,
					valor_icms 			= null,
					base_ipi 			= null,
					valor_ipi 			= null,
					serie 				= null,
					data_nf_envio		= null,
					data_nf_recebida 	= null,
					exportado 			= null,
					optante_simples_nacional = null
					WHERE extrato = $extrato";
			$res = pg_query($con,$sql);
			if (strlen(pg_last_error($con)) > 0) throw new Exception("falha_excluir");

			$sql = "UPDATE tbl_faturamento SET fabrica = 0 WHERE  faturamento IN (SELECT faturamento FROM tbl_faturamento WHERE extrato_devolucao = $extrato AND fabrica = $login_fabrica)";
			$res = pg_query($con,$sql);
			if (strlen(pg_last_error($con)) > 0) throw new Exception("falha_excluir");

		}else{
			$sql = "UPDATE tbl_extrato_extra SET
					nota_fiscal_mao_de_obra		= null,
					emissao_mao_de_obra    		= null,
					nota_fiscal_serie_mao_de_obra 	= null,
					exportado = null
					WHERE extrato = $extrato";
			$res = pg_query($con,$sql);
			if (strlen(pg_last_error($con)) > 0) throw new Exception("falha_excluir");
		}

		$res = pg_query($con, "COMMIT");
		echo "ok|Faturamento excluido com sucesso";

	}catch(Exception $e){
		echo pg_last_error($con);
		@$res = pg_query($con, "ROLLBACK");
		$erro = $e->getMessage() == "falha_excluir" ? "Falha ao excluir faturamento" : $e->getMessage();
		echo "no|" . $erro;
	}

	exit;
}

if($_GET['ajax']=='sim')
{
	try {
		$codigo_posto = $_GET["codigo_posto"];
		$extrato = intval($_GET["extrato"]);
		$nota_fiscal = intval($_GET["nota_fiscal"]);
		$data_extrato_inicial = $_GET["data_inicial"];
	    $data_extrato_final = $_GET["data_final"];
		$data_nota_fiscal_inicial = $_GET["data_nf_inicial"];
	    $data_nota_fiscal_final = $_GET["data_nf_final"];

	    // VALIDAcÃO POSTO
	    if (strlen($codigo_posto) > 0) {
	    	$sql = "
	    	SELECT
	    	posto

	    	FROM
	    	tbl_posto_fabrica

	    	WHERE
	    	fabrica={$login_fabrica}
	    	AND codigo_posto='{$codigo_posto}'
	    	";
	    	$res = pg_query($con, $sql);
	    	if (pg_num_rows($res) == 0) throw new Exception("Posto inválido");

	    	$posto = intval(pg_result($res, 0, "posto"));
	    	$cond_posto = "AND tbl_extrato.posto={$posto}";
	    }
	    else {
	    	$posto = 0;
	    }

	    // VALIDAcÃO EXTRATO
	    if ($extrato > 0) {
	    	$sql = "
	    	SELECT
	    	extrato

	    	FROM
	    	tbl_extrato

	    	WHERE
	    	fabrica={$login_fabrica}
	    	AND extrato={$extrato}
	    	";
	    	$res = pg_query($con, $sql);
	    	if (pg_num_rows($res) == 0) throw new Exception("Extrato não encontrato");

	    	$cond_extrato = "AND tbl_extrato.extrato={$extrato}";
	    }

	    if ($nota_fiscal > 0) {
	    	$cond_nota_fiscal_servico = "AND tbl_extrato_extra.nota_fiscal_mao_de_obra='{$nota_fiscal}'";
	    	$cond_nota_fiscal_peca = "AND tbl_faturamento.nota_fiscal='{$nota_fiscal}'";
	    }

	    // VALIDAcÃO DATA EXTRATO INICIAL E FINAL
	    if (strlen($data_extrato_inicial) > 0) {
	        list($d, $m, $y) = explode("/", $data_extrato_inicial);
	        if(!checkdate($m,$d,$y)) throw new Exception("Data Inválida");
	        $data_extrato_inicial = "{$y}-{$m}-{$d}";

	        if (strlen($data_extrato_final) > 0) {
		        list($d, $m, $y) = explode("/", $data_extrato_final);
		        if(!checkdate($m,$d,$y)) throw new Exception("Data Inválida");
		        $data_extrato_final = "{$y}-{$m}-{$d}";

		        if(strtotime($data_extrato_final) < strtotime($data_extrato_inicial) or strtotime($data_extrato_final) > strtotime('today')){
		            throw new Exception("Data Inválida");
		        }

			    if (strtotime($data_extrato_inicial.'+1 month') < strtotime($data_extrato_final) ) {
			        throw new Exception('O intervalo entre as datas não pode ser maior que 1 mês');
			    }

		        $cond_data_extrato = "AND tbl_extrato.data_geracao BETWEEN '{$data_extrato_inicial} 00:00:00' AND '{$data_extrato_final} 23:59:59'";
	        }
	        else {
	        	throw new Exception("Preencha datas inicial e final");
	        }
	    }
	    else if (strlen($data_extrato_final) > 0) {
	    	throw new Exception("Preencha datas inicial e final");
	    }

	    // VALIDAcÃO DATA NOTA INICIAL E FINAL
	    if (strlen($data_nota_fiscal_inicial) > 0) {
	        list($d, $m, $y) = explode("/", $data_nota_fiscal_inicial);
	        if(!checkdate($m,$d,$y)) throw new Exception("Data Inválida");
	        $data_nota_fiscal_inicial = "{$y}-{$m}-{$d}";

	        if (strlen($data_nota_fiscal_final) > 0) {
		        list($d, $m, $y) = explode("/", $data_nota_fiscal_final);
		        if(!checkdate($m,$d,$y)) throw new Exception("Data Inválida");
		        $data_nota_fiscal_final = "{$y}-{$m}-{$d}";

		        if(strtotime($data_nota_fiscal_final) < strtotime($data_nota_fiscal_inicial) or strtotime($data_nota_fiscal_final) > strtotime('today')){
		            throw new Exception("Data Inválida");
		        }

			    if (strtotime($data_nota_fiscal_inicial.'+1 month') < strtotime($data_nota_fiscal_final) ) {
		            throw new Exception('O intervalo entre as datas não pode ser maior que 1 mês');
			    }

		        $cond_data_nota_fiscal_servico = "AND tbl_extrato_extra.emissao_mao_de_obra BETWEEN '{$data_nota_fiscal_inicial} 00:00:00' AND '{$data_nota_fiscal_final} 23:59:59'";
		        $cond_data_nota_fiscal_peca = "AND tbl_faturamento.emissao BETWEEN '{$data_nota_fiscal_inicial} 00:00:00' AND '{$data_nota_fiscal_final} 23:59:59'";
	        }
	        else {
	        	throw new Exception("Preencha datas inicial e final");
	        }
	    }
	    else if (strlen($data_nota_fiscal_final) > 0) {
	    	throw new Exception("Preencha datas inicial e final");
	    }

	    // VALIDANDO FILTROS MÍNIMOS
	    if ($posto == 0 && strlen($data_extrato_inicial) == 0 && strlen($data_nota_fiscal_inicial) == 0 && $extrato == 0 && $nota_fiscal == 0) {
	    	throw new Exception("Preencha pelo menos um filtro para a pesquisa");
	    }

	    // EXECUTANDO CONSULTA
		$sql = "
		SELECT
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_extrato.extrato,
		TO_CHAR(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao,
		'Serviço' AS tipo_nota,
		'Servico' as tipo_nota_hidden,
		tbl_extrato_extra.nota_fiscal_mao_de_obra AS nota_fiscal,
		tbl_extrato_extra.nota_fiscal_serie_mao_de_obra AS serie_nota_fiscal,
		TO_CHAR(tbl_extrato_extra.emissao_mao_de_obra, 'DD/MM/YYYY') AS emissao_nota_fiscal,
		NULL AS optante_simples_nacional,
		NULL AS faturamento

		FROM
		tbl_extrato
		JOIN tbl_extrato_extra ON tbl_extrato.extrato=tbl_extrato_extra.extrato
		JOIN tbl_posto_fabrica ON tbl_extrato.posto=tbl_posto_fabrica.posto AND tbl_extrato.fabrica=tbl_posto_fabrica.fabrica
		JOIN tbl_posto ON tbl_extrato.posto=tbl_posto.posto

		WHERE
		tbl_extrato.fabrica={$login_fabrica}
		AND tbl_extrato_extra.nota_fiscal_mao_de_obra IS NOT NULL
		{$cond_posto}
		{$cond_extrato}
		{$cond_data_extrato}
		{$cond_nota_fiscal_servico}
		{$cond_data_nota_fiscal_servico}

		UNION

		SELECT
		tbl_posto_fabrica.codigo_posto,
		tbl_posto.nome,
		tbl_extrato.extrato,
		TO_CHAR(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao,
		'Peça' AS tipo_nota,
		'Peca' as tipo_nota_hidden,
		tbl_faturamento.nota_fiscal,
		tbl_faturamento.serie AS serie_nota_fiscal,
		TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao_nota_fiscal,
		tbl_extrato_devolucao.optante_simples_nacional,
		tbl_faturamento.faturamento

		FROM
		tbl_extrato
		JOIN tbl_extrato_devolucao ON tbl_extrato.extrato=tbl_extrato_devolucao.extrato
		JOIN tbl_faturamento ON tbl_extrato.extrato=tbl_faturamento.extrato_devolucao
		JOIN tbl_posto_fabrica ON tbl_extrato.posto=tbl_posto_fabrica.posto AND tbl_extrato.fabrica=tbl_posto_fabrica.fabrica
		JOIN tbl_posto ON tbl_extrato.posto=tbl_posto.posto

		WHERE
		tbl_extrato.fabrica={$login_fabrica}
		AND tbl_faturamento.fabrica={$login_fabrica}
		{$cond_posto}
		{$cond_extrato}
		{$cond_data_extrato}
		{$cond_nota_fiscal_peca}
		{$cond_data_nota_fiscal_peca}
		";
		$res = pg_query ($con, $sql);

		if (pg_num_rows($res) > 0)
		{
			$total = 0;

			$resposta  .=  "<table border='0' width='700' cellpadding='2' cellspacing='1' class='tabela'>";
			$resposta  .=  "<TR class='Titulo'  background='imagens_admin/azul.gif' height='25'>";
			$resposta  .=  "<th>Nome do Posto</th>";
			$resposta  .=  "<th>Extrato</th>";
			$resposta  .=  "<th>Data Extrato</th>";
			$resposta  .=  "<th>Tipo de Nota</th>";
			$resposta  .=  "<th>Nota Fiscal</th>";
			$resposta  .=  "<th>Série Nota Fiscal</th>";
			$resposta  .=  "<th width='100'>Data Nota Fiscal</th>";
			if($login_fabrica == 74){
				$resposta  .=  "<th>Optante pelo SN</th>";
				$resposta  .=  "<th>% ICMS</th>";
			}
			$resposta  .=  "<th>Acão</th>";
			$resposta  .=  "</TR>";

			$excel  .=  "<table border='1'>";
			$excel  .=  "<TR>";
			$excel  .=  "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Nome do Posto</b></font></th>";
			$excel  .=  "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Extrato</b></font></th>";
			$excel  .=  "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Data Extrato</b></font></th>";
			$excel  .=  "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Tipo de Nota</b></font></th>";
			$excel  .=  "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Nota Fiscal</b></font></th>";
			$excel  .=  "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Série Nota Fiscal</b></font></th>";
			$excel  .=  "<th bgcolor='#5A6D9C' align='center'><font color='#FFFFFF'><b>Data Nota Fiscal</b></font></th>";
			$excel  .=  "</TR>";

			$extrato_anterior = "";
			$tipo_nota_anterior = "";
			for ($i=0; $i<pg_num_rows($res); $i++)
			{
				$nome    		  		  = trim(pg_fetch_result($res,$i,'nome'));
				$extrato         		  = trim(pg_fetch_result($res,$i,'extrato'));
				$data_extrato        	  = trim(pg_fetch_result($res,$i,'data_geracao'));
				$tipo_nota_mostra  		  = trim(pg_fetch_result($res,$i,'tipo_nota'));
				$tipo_nota		    	  = trim(pg_fetch_result($res,$i,'tipo_nota_hidden'));
				$nota_fiscal        	  = trim(pg_fetch_result($res,$i,'nota_fiscal'));
				$serie_nota_fiscal  	  = trim(pg_fetch_result($res,$i,'serie_nota_fiscal')) ;
				$data_nota       		  = trim(pg_fetch_result($res,$i,'emissao_nota_fiscal'));
				$optante_simples_nacional = trim(pg_fetch_result($res,$i,'optante_simples_nacional'));
				$faturamento			  = trim(pg_fetch_result($res,$i,'faturamento'));

				$cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';

				$resposta  .=  "<TR bgcolor='$cor'class='Conteudo' $style rel='{$extrato}_{$tipo_nota}'>";
				$resposta  .=  "<TD align='center'>$nome</TD>";
				$resposta  .=  "<TD align='left' nowrap><a href='extrato_consulta_os.php?extrato={$extrato}' target='_blank'>$extrato</a></TD>";
				$resposta  .=  "<TD align='center'>$data_extrato</TD>";
				$resposta  .=  "<TD align='center'>$tipo_nota_mostra</TD>";
				$resposta  .=  "<TD align='center'><input type='text' name='nota_fiscal' id='nota_fiscal' size='8' value='$nota_fiscal' class='Caixa'></TD>";
				$resposta  .=  "<TD align='center'><input type='text' name='serie_nota_fiscal' id='serie_nota_fiscal' size='4' value='$serie_nota_fiscal' class='Caixa'></TD>";
				$resposta  .=  "<TD align='center'><input type='text' name='data_nota' id='data_nota' size='10' value='$data_nota' class='Caixa' class='frm'></TD>";
				$resposta  .=  "<input type='hidden' name='extrato' id='extrato' value='$extrato'>";
				$resposta  .=  "<input type='hidden' name='tipo_nota' id='tipo_nota' value='$tipo_nota'>";
				$resposta  .=  "<input type='hidden' name='faturamento' id='faturamento' value='$faturamento'>";
				//$resposta  .=  "<input type='hidden' name='simnao' id='simnao' value='$simnao'>";
				if ($tipo_nota == "Peca")
				{
					$simchecked = $optante_simples_nacional == "t" ? "checked" : "";
					$naochecked = $optante_simples_nacional == "f" ? "checked" : "";
					$resposta  .=  "<TD align='center' nowrap><input class='frm' type='radio' name='simnao{$i}' value='t' id='simnao' {$simchecked}>Sim
					<input type='radio' name='simnao{$i}' value='f' id='simnao' {$naochecked}>Não</TD>";
				}
				else
				{
					if($login_fabrica == 74){
						$resposta  .=  "<TD align='right'> -- </TD>";
					}
				}


					$resposta .= "<TD align='center'>";
					if (strlen($faturamento) > 0){
						$sqlIcms = "SELECT aliq_icms FROM tbl_faturamento_item WHERE faturamento = $faturamento LIMIT 1";
						$resIcms = pg_query($con,$sqlIcms);
						$resposta .= pg_result($resIcms,0,0);
					}
					$resposta .= "</TD>";

				$resposta  .=  "<TD align='center' nowrap><input type='button' name='atualizar_campo' value='Atualizar' id='atualizar_campo' class='Caixa frm btn_atualizar' style='cursor:pointer;'>";

				if(($login_fabrica == 74 OR $login_fabrica == 120 or $login_fabrica == 201) AND ($extrato_anterior != $extrato OR $tipo_nota_anterior != $tipo_nota)){
					$resposta  .=  "<input type='button' name='atualizar_campo' value='Excluir' id='excluir_campo' class='Caixa frm btn_excluir' onclick=\"excluirFaturamento('$faturamento',$extrato,'$tipo_nota');\" style='cursor:pointer;'>";
				}
				$extrato_anterior = $extrato;
				$tipo_nota_anterior = $tipo_nota;

				$resposta .= "</TD>";
				$resposta  .=  "</TR>";

				$excel  .=  "<TR>";
				$excel  .=  "<TD align='center'>$nome</TD>";
				$excel  .=  "<TD align='center'>$extrato</TD>";
				$excel  .=  "<TD align='center'>$data_extrato</TD>";
				$excel  .=  "<TD align='center'>$tipo_nota_mostra</TD>";
				$excel  .=  "<TD align='center'>$nota_fiscal</TD>";
				$excel  .=  "<TD align='center'>$serie_nota_fiscal</TD>";
				$excel  .=  "<TD align='center'>$data_nota</TD>";
				$excel  .=  "</TR>";
			}
			$resposta .= " </TABLE>";

			$resposta .=  "<br>";
			$resposta .=  "<hr width='600'>";
			$resposta .=  "<br>";

			$excel .= " </TABLE>";
			if($login_fabrica == 120 or $login_fabrica == 201){
				$arquivo = "xls/relatorio-nota-extrato-$login_fabrica-".date('Y-m-d').".xls";
				$fp = fopen($arquivo,"w");
				fwrite($fp,$excel);
				fclose($fp);

				$resposta .= "<input type='button' onclick=\"window.open('$arquivo')\" value='Download Excel'>";
			}


		}else{
			$resposta .= "<br>";
			$resposta .= "<b>Nenhum resultado encontrado<br>";
			$resposta .= "</b>";
		}
		$listar = "";

		$resposta = $resposta;
		echo "ok|".$resposta;
	}
	catch (Exception $e) {
		echo "no|".$e->getMessage();
	}
	exit;
}

$layout_menu = "financeiro";
$title = "MANUTENÇÃO DE NOTAS FISCAS DE EXTRATO";

include "cabecalho.php";

?>
<style>
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
	font-size: 10px;
	font-weight: normal;
}

.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}

.Mes{
	font-size: 9px;
}

.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}

.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}

.Carregando{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
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

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:solid #596d9b;
	border-width:1px 0 1px 0;
}
</style>

<? include "../js/js_css.php"; ?>

<script language="javascript" src="js/effects.explode.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.alerts.css" />
<script language="javascript" src="js/jquery.alerts.js"></script>
<script language='javascript'>
function Exibir() {
	$('#dados').html('');
	$('#dados_resultado').html('');

	var data_inicial = document.frm_relatorio.data_inicial.value;
	var data_final = document.frm_relatorio.data_final.value;
	var data_nf_inicial = document.frm_relatorio.data_nf_inicial.value;
	var data_nf_final = document.frm_relatorio.data_nf_final.value;
	var codigo_posto = document.frm_relatorio.codigo_posto.value;
	var nota_fiscal = document.frm_relatorio.nota_fiscal.value;
	var extrato = document.frm_relatorio.extrato.value;

	var curDateTime = new Date();
	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'data_inicial='+data_inicial+'&data_final='+data_final+'&data_nf_inicial='+data_nf_inicial+'&data_nf_final='+data_nf_final+'&codigo_posto='+codigo_posto+'&nota_fiscal='+nota_fiscal+'&extrato='+extrato+'&ajax=sim&nocache='+curDateTime,
		beforeSend: function(){
			$('#consultar').effect('bounce').hide('');
			$('#dados').html("Carregando...").attr("class", "Carregando");
		},
		complete: function(resposta){
			results = resposta.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				$('#consultar').show('');
				if (results[0] == 'ok') {
					$('#dados').html('');
					$('#dados_resultado').html(results[1]);
					atualizar_campo();
				}
				if (results[0] == 'no') {
					$('#dados').html(results[1]).attr("class", "msg_erro");
				}
			}
		}
	});
}

function atualizar_campo() {
	$(".btn_atualizar").each(function(){

		$(this).parent().parent().find("input[name='data_nota']").datepick({startDate:'01/01/2000'});
		$(this).parent().parent().find("input[name='data_nota']").mask("99/99/9999");

		$(this).click(function(){
			$('#dados').html("Carregando...").attr("class", "Carregando");
			var extrato = $(this).parent().parent().find("input[id='extrato']").val();
			var tipo_nota = $(this).parent().parent().find("input[id='tipo_nota']").val();
			var nota_fiscal = $(this).parent().parent().find("input[id='nota_fiscal']").val();
			var serie_nota_fiscal = $(this).parent().parent().find("input[id='serie_nota_fiscal']").val();
			var data_nota = $(this).parent().parent().find("input[id='data_nota']").val();
			var simnao = $(this).parent().parent().find("input[id='simnao']:checked").val();
			var faturamento = $(this).parent().parent().find("input[id='faturamento']").val();

			if (extrato == undefined || tipo_nota == undefined || extrato.length == 0 || tipo_nota.length == 0) {
				alert("Ocorreu um erro interno, tente novamente, se persistir contate suporte.");
				return false;
			}

			if (nota_fiscal.length == 0 || data_nota.length == 0) {
				alert("Os dados da nota fiscal não podem ficar em branco");
				return false;
			}

			if (tipo_nota == 'Peca' && (simnao == undefined || simnao.length == 0))
			{
				alert('Escolha sim ou não no campo Optante');
				return false;
			}

			$.ajax({
				type: "GET",
				url: "<?=$PHP_SELF?>",
				data: 'extrato='+extrato+'&nota_fiscal='+nota_fiscal+'&serie_nota_fiscal='+serie_nota_fiscal+'&data_nota='+data_nota+'&simnao='+simnao+'&tipo_nota='+tipo_nota+'&faturamento='+faturamento+'&ajax=campo',
				complete: function(resposta)
				{
					results = resposta.responseText.split("|");
					switch (results[0]) {
						case "no":
							$('#dados').html(results[1]).attr("class", "msg_erro");
						break;

						case "ok":
							$('#dados').html(results[1]).attr("class", "msg_sucesso");
						break;
					}
					if (typeof (results[0]) != 'undefined') {


					}
				}
			});
		});
	});
}

function excluirFaturamento(faturamento, extrato,tipo_nota){

	$('#dados').html("Aguarde, processando...").attr("class", "Carregando");
	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'extrato='+extrato+'&tipo_nota='+tipo_nota+'&faturamento='+faturamento+'&ajax=excluir',
		complete: function(resposta)
		{
			results = resposta.responseText.split("|");
			switch (results[0]) {
				case "no":
					$('#dados').html(results[1]).attr("class", "msg_erro");
				break;

				case "ok":
					$('#dados').html(results[1]).attr("class", "msg_sucesso");
					$("tr[rel="+extrato+"_"+tipo_nota+"]").remove();
				break;
			}
			if (typeof (results[0]) != 'undefined') {


			}
		}
	});

}
</script>

<? include "javascript_pesquisas.php" ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
		$('#data_nf_inicial').datepick({startDate:'01/01/2000'});
		$('#data_nf_final').datepick({startDate:'01/01/2000'});
		$("#data_nf_inicial").mask("99/99/9999");
		$("#data_nf_final").mask("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<!--
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
-->
<script language="JavaScript">
$().ready(function() {
	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
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
		$("#codigo_posto").val(data[0]) ;
	});
});
</script>

<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

	<table width='700px' class='formulario' cellpadding='0' cellspacing='0' align='center'>
		<tr>
			<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
		</tr>

		<tr>
			<td valign='bottom'>
				<table width='100%' border='0' cellspacing='1' cellpadding='2' >
					<tr>
						<td width="10">&nbsp;</td>
						<td align='left' nowrap>Cod Posto<br>
							<input class='frm' type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" class="Caixa">
							<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
						</td>
						<td align='left' nowrap>Nome do Posto<br>
							<input  class='frm' type="text" name="posto_nome" id="posto_nome" size="30"  value="<? echo $posto_nome ?>" class="Caixa">
							<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
						</td>
						<td width="10">&nbsp;</td>
					</tr>

					<tr>
						<td width="100">&nbsp;</td>
						<td align='left'>Data Extrato Inicial<br>
							<input class='frm' type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
						</td>
						<td align='left'>Data Extrato Final<br>
							<input class='frm' type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
						</td>
						<td width="10">&nbsp;</td>
					</tr>

					<tr>
						<td width="100">&nbsp;</td>
						<td align='left'>Data Nota Fiscal Inicial<br>
							<input class='frm' type="text" name="data_nf_inicial" id="data_nf_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_nf_inicial) > 0) echo $data_nf_inicial; ?>" >
						</td>
						<td align='left'>Data Nota Fiscal Final<br>
							<input class='frm' type="text" name="data_nf_final" id="data_nf_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_nf_final) > 0) echo $data_nf_final;  ?>" >
						</td>
						<td width="10">&nbsp;</td>
					</tr>

					<tr>
						<td width="100">&nbsp;</td>
						<td align='left'>Extrato<br>
							<input class='frm' type="text" name="extrato" id="extrato" size="12" class='Caixa' value="<? if (strlen($extrato) > 0) echo $extrato; ?>" >
						</td>
						<td align='left'>Nota Fiscal<br>
							<input class='frm' type="text" name="nota_fiscal" id="nota_fiscal" size="12" class='Caixa' value="<? if (strlen($nota_fiscal) > 0) echo $nota_fiscal;  ?>" >
						</td>
						<td width="10">&nbsp;</td>
					</tr>
				</table>
				<br />
				<input type="submit" name="consultar" onclick="javascript:Exibir(); return false;" style="cursor:pointer " value='Pesquisa' id='consultar'>
			</td>
		</tr>
	</table>
	<br>
	<table width='700px' cellpadding='0' class="msg_erro" cellspacing='0' align='center'>
		<tr>
			<td>
				<div id='dados'></div>
			</td>
		</tr>
	</table>
</form>

<table>
	<tr>
		<td>
			<div id='erro' style='position: absolute; top: 150px; left: 80px;opacity:.85;visibility:hidden;' class='Erro'></div>
			<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
		</td>
	</tr>
</table>

<table align='center' >
	<tr>
		<td>
			<? echo "<div id='dados_resultado'></div>"; ?>
		</td>
	</tr>
<table>
<p>
<? include "rodape.php" ?>
