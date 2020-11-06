<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$peca_referencia    = $_POST['peca_referencia'];
	$peca_descricao     = $_POST['peca_descricao'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$linha              = $_POST['linha'];
	$familia 			= $_POST['familia'];
	$tipo_data 			= $_POST['tipo_data'];

	switch ($tipo_data) {
		case 'abertura':
			$campo_data = "tbl_os.data_abertura";
			break;
		case 'fechamento':
			$campo_data = "tbl_os.data_fechamento";
			break;

		default:
			$campo_data = "tbl_os.data_abertura";
			break;
	}


	if($login_fabrica == 127){
		$cond_pesquisa_produto = "AND (
		                  	(UPPER(referencia) = UPPER('{$produto_referencia}')))";
	}else{
		$cond_pesquisa_produto = "AND (
                  	(UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )";
	}


	if($login_fabrica == 127){
		$cond_pesquisa_peca = "AND (
                    (UPPER(referencia) = UPPER('{$peca_referencia}')))";
	}else{
		$cond_pesquisa_peca = "AND (
                    (UPPER(referencia) = UPPER('{$peca_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$peca_descricao}'))
                )";
	}

	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				$cond_pesquisa_produto
				";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto";
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

	if (strlen($peca_referencia) > 0 or strlen($peca_descricao) > 0){
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE fabrica = {$login_fabrica}
				$cond_pesquisa_peca";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			$peca = pg_fetch_result($res, 0, "peca");
		}
	}

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (strlen($linha) > 0) {
		$sql = "SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND linha = {$linha}";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Linha não encontrada";
			$msg_erro["campos"][] = "linha";
		}
	}

	if (strlen($familia)) {
		$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Familia não encontrada";
			$msg_erro["campos"][] = "familia";
		}
	}

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($produto)){
			$cond_produto = " AND tbl_produto.produto = '{$produto}' ";
		}

		if (!empty($peca)){
			$cond_peca = " AND tbl_os.os IN (SELECT tbl_os_produto.os
						  FROM tbl_os_item
						  JOIN tbl_os_produto USING(os_produto)
						  JOIN tbl_peca USING(peca)
						  WHERE fabrica_i = {$login_fabrica}
						  AND tbl_os_produto.os = tbl_os.os
						  AND tbl_peca.peca = '{$peca}' LIMIT 1) ";
		}

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		if ($linha) {
			$cond_linha = " AND tbl_produto.linha = {$linha} ";
		}

		if ($familia) {
			$cond_familia = " AND tbl_produto.familia = {$familia} ";
		}

		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

		/*$sql = "SELECT
					tbl_os.os                                    AS os,
					tbl_os.sua_os                               AS sua_os,
					tbl_os.posto                                AS posto,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')AS data_fechamento,
					tbl_os.produto,
					tbl_os.serie,
					tbl_produto.referencia as referencia_produto,
					tbl_produto.descricao as produto_descricao,
	                                tbl_hd_chamado_extra.hd_chamado,
					tbl_peca.referencia as referencia_peca,
					tbl_peca.peca, tbl_peca.descricao as peca_descricao,
					tbl_defeito_constatado.descricao as defeito,
					tbl_servico_realizado.descricao as servico
				INTO TEMP TMP_PRODUTO_PECA
				FROM tbl_os
				LEFT JOIN tbl_hd_chamado_extra USING(os)
				JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i=tbl_os.fabrica
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				LEFT JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				LEFT JOIN tbl_defeito_constatado    ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
				LEFT JOIN tbl_servico_realizado    ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				LEFT JOIN tbl_peca       ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica=$login_fabrica
				WHERE tbl_os.fabrica                   = $login_fabrica
				AND   tbl_os.data_abertura between '$aux_data_inicial' and '$aux_data_final'
				$cond_posto
				$cond_linha
				$cond_familia;

				CREATE INDEX tmp_produto_peca_os on tmp_produto_peca(os);

				SELECT DISTINCT
					tmp_produto_peca.os,
					tmp_produto_peca.sua_os,
					tmp_produto_peca.referencia_produto,
					tmp_produto_peca.produto_descricao,
					tmp_produto_peca.referencia_peca,
					tmp_produto_peca.peca_descricao,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
					tmp_produto_peca.hd_chamado,
					tmp_produto_peca.data_abertura,
					tmp_produto_peca.data_fechamento,
					tmp_produto_peca.serie,
					tmp_produto_peca.defeito,
					tmp_produto_peca.servico
				FROM tmp_produto_peca
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_produto_peca.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_posto ON tbl_posto.posto = tmp_produto_peca.posto
				WHERE 1 = 1
				$cond_produto
				$cond_peca
				$cond_3
				ORDER BY tmp_produto_peca.data_abertura,tmp_produto_peca.os,
				tmp_produto_peca.referencia_produto,tmp_produto_peca.referencia_peca
				{$limit};";*/
		if($login_fabrica == 114){
			$col_data_nf = ", tbl_os.data_nf ";
			$col_campo_selo = ",tbl_os_extra.selo ";
			$join_tbl_os_extra = " JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os ";

		}
		$sql = "SELECT
					tbl_os.os,
					tbl_os.sua_os,
					tbl_os.data_abertura AS dt_abertura,
					TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
					tbl_hd_chamado_extra.hd_chamado,
					'<b>' || tbl_produto.referencia || '</b> - ' || tbl_produto.descricao AS produto,
					tbl_os.serie,
					tbl_produto.oem,
					tbl_defeito_constatado.descricao AS defeito,
					tbl_os.defeito_reclamado_descricao AS defeito_reclamado
					$col_data_nf
					$col_campo_selo
				FROM tbl_os
				LEFT JOIN tbl_hd_chamado_extra USING(os)
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
				{$join_tbl_os_extra}
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND   tbl_os.excluida IS NOT TRUE
				AND {$campo_data} BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
				{$cond_posto}
				{$cond_linha}
				{$cond_familia}
				{$cond_produto}
				{$cond_peca}
				{$limit}";

		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_os_atendimento-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='11' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE OS X ATENDIMENTOS
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abertura</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Fechamento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Faturamento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Dias Aberto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendimento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</th>";
			if($login_fabrica == 114){
				$thead .=	"<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data NF</th>";
				$thead .=	"<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo de Atendimento</th>";
			}
			$thead .=		"<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Reclamdo</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Analisado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Peça</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Serviço</th>
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$os                 = pg_fetch_result($resSubmit, $i, 'os');
				$sua_os             = pg_fetch_result($resSubmit, $i, 'sua_os');
				$data_abertura      = pg_fetch_result($resSubmit, $i, 'data_abertura');
				$dt_abertura      = pg_fetch_result($resSubmit, $i, 'dt_abertura');
				$data_fechamento    = pg_fetch_result($resSubmit, $i, 'data_fechamento');
				$hd_chamado         = pg_fetch_result($resSubmit, $i, 'hd_chamado');
				$produto            = pg_fetch_result($resSubmit, $i, 'produto');
				$serie              = pg_fetch_result($resSubmit, $i, 'serie');
				$defeito            = pg_fetch_result($resSubmit, $i, 'defeito');
				$defeito_reclamado  = pg_fetch_result($resSubmit, $i, 'defeito_reclamado');
				if($login_fabrica == 114 ){
					$selo       = pg_fetch_result($resSubmit, $i, 'selo');
					$data_nf    = pg_fetch_result($resSubmit, $i, 'data_nf');
					$oem        = pg_fetch_result($resSubmit, $i, 'oem');
					$data_nf 	= date("d/m/Y",strtotime($data_nf));
					$oem        = ($selo == "selo anexado") ? "Atendimento Garantia Estendida" : "";
				}else{
					$data_nf = "";
					$oem = "";
				}

				$sql = "SELECT DISTINCT '<b>' || tbl_peca.referencia || '</b> - ' || tbl_peca.descricao AS peca,
								tbl_servico_realizado.descricao AS servico,
								to_char(emissao,'DD/MM/YYYY') as emissao
								FROM tbl_os_item
								JOIN tbl_os_produto USING(os_produto)
								JOIN tbl_peca USING(peca)
								LEFT JOIN tbl_servico_realizado USING(servico_realizado)
								LEFT JOIN tbl_faturamento_item ON (tbl_faturamento_item.pedido_item = tbl_os_item.pedido_item or (tbl_faturamento_item.os = $os and tbl_faturamento_item.peca = tbl_os_item.peca)) and tbl_faturamento_item.pedido is not null
								LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento and (tbl_faturamento.fabrica = $login_fabrica OR tbl_faturamento.fabrica = 10)
								WHERE fabrica_i = {$login_fabrica}
								AND tbl_os_produto.os = {$os}";
				$res = pg_query($con, $sql);

				unset($result, $pecas, $servicos,$emissao,$dias_aberto);

				if(pg_num_rows($res)==0){
					$body .="
							<tr>
								<td nowrap align='center' valign='top'>{$sua_os}</td>
								<td nowrap align='center' valign='top'>{$data_abertura}</td>
								<td nowrap align='center' valign='top'>{$data_fechamento}</td>
								<td nowrap align='center' valign='top'></td>
								<td nowrap align='center' valign='top'>{$dias_aberto}</td>
								<td nowrap align='center' valign='top'>{$hd_chamado}</td>
								<td nowrap align='left' valign='top'>{$produto}</td>
								<td nowrap align='center' valign='top'>{$serie}</td>";
						if($login_fabrica == 114){
							$body .="<td nowrap align='center' valign='top'>{$data_nf}</td>";
							$body .="<td nowrap align='center' valign='top'>{$oem}</td>";
						}
						$body .="<td nowrap align='left' valign='top'>{$defeito_reclamado}</td>
								<td nowrap align='left' valign='top'>{$defeito}</td>
								<td nowrap align='left'></td>
								<td nowrap align='left'></td>
							</tr>";
				}else{
					while ($result = pg_fetch_array($res)) {


						$pecas    = $result['peca'];
						$servicos = $result['servico'];
						$emissao = $result['emissao'];
						if(!empty($emissao)) {
							$sql = "SELECT '$emissao'::date - '$data_abertura'::date";
							$resAux = pg_query($con,$sql);
							if(pg_num_rows($resAux) > 0 ) {
								$dias_aberto = pg_fetch_result($resAux,0,0);
							}
						}


						$body .="
							<tr>
								<td nowrap align='center' valign='top'>{$sua_os}</td>
								<td nowrap align='center' valign='top'>{$data_abertura}</td>
								<td nowrap align='center' valign='top'>{$data_fechamento}</td>
								<td nowrap align='center' valign='top'>".$emissao."</td>
								<td nowrap align='center' valign='top'>{$dias_aberto}</td>
								<td nowrap align='center' valign='top'>{$hd_chamado}</td>
								<td nowrap align='left' valign='top'>{$produto}</td>
								<td nowrap align='center' valign='top'>{$serie}</td>";
						if($login_fabrica == 114){
							$body .="<td nowrap align='center' valign='top'>{$data_nf}</td>";
							$body .="<td nowrap align='center' valign='top'>{$oem}</td>";
						}
						$body .="<td nowrap align='left' valign='top'>{$defeito_reclamado}</td>
								<td nowrap align='left' valign='top'>{$defeito}</td>
								<td nowrap align='left'>".$pecas."</td>
								<td nowrap align='left'>".$servicos."</td>
							</tr>";
					}
				}

			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='9' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
						</tr>
					</tbody>
				</table>
			");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}

		exit;
	}
}
if($_GET['mostra']== 1){
    $os_hist = $_GET['os_hist'];
    $sql_status = " SELECT  os_status,
                            status_os,
                            observacao,
                            tbl_admin.login AS login,
                            to_char(data, 'DD/MM/YYYY')   as data_status,
                            tbl_os_status.admin,
                            tbl_status_os.descricao
                    FROM    tbl_os_status
                    JOIN    tbl_status_os using(status_os)
                LEFT JOIN    tbl_admin USING(admin)
                    WHERE   os                              = $os_hist
                    AND     tbl_os_status.fabrica_status    = $login_fabrica
                ORDER BY      data DESC";
    $res_status = pg_query($con,$sql_status);
    $resultado = pg_num_rows($res_status);
    if ($resultado>0){
        echo "<BR>\n";
        echo "<TABLE width='800px' border='0' cellspacing='1' cellpadding='0' align='center' style='background-color: #485989;border: 1px solid #D2E4FC;font-family:Helvetica,Arial,sans-serif; font-size:13px;'>\n";
        echo "<thead>";
        echo "<TR style='color: #FFF;'>\n";
        echo "<th colspan='7' align='center'>&nbsp;HISTÓRICO DE INTERVENÇÃO da OS: $os_hist</TD>\n";
        echo "</TR>\n";
        echo "<TR style='background: none repeat scroll 0 0 #CED7E7;font-size: 7pt;'>\n";
        echo "<th>DATA</th>\n";
        echo "<th>TIPO/STATUS</th>\n";
        echo "<th>JUSTIFICATIVA</th>\n";
        echo "<th>ADMIN</th>\n";
        echo "</TR>";
        echo "</thead>";
        echo "<tbody>";

        for ($j=0;$j<$resultado;$j++){
            $os_status          = trim(pg_fetch_result($res_status,$j,os_status));
            $status_os          = trim(pg_fetch_result($res_status,$j,status_os));
            $status_observacao  = trim(pg_fetch_result($res_status,$j,observacao));
            $status_admin       = trim(pg_fetch_result($res_status,$j,login));
            $status_data        = trim(pg_fetch_result($res_status,$j,data_status));
            $status_admin2      = trim(pg_fetch_result($res_status,$j,admin));
            $descricao          = trim(pg_fetch_result($res_status,$j,descricao));

            if (($status_os==72 OR  $status_os==64) AND strlen($status_observacao)>0){
                $status_observacao = strstr($status_observacao,"Justificativa:");
                $status_observacao = str_replace("Justificativa:","",$status_observacao);
            }

            $status_observacao = trim($status_observacao);

            if (strlen($status_observacao)==0 AND $status_os==73) $status_observacao="Autorizado";
            if (strlen($status_observacao)==0 AND $status_os==72) $status_observacao="-";

            if (strlen($status_admin)>0){
                $status_admin = " $status_admin";
                if ($login_fabrica==11){
                    $status_observacao = trim(pg_fetch_result($res_status,$j,observacao));
                }
            }else{
                $status_admin = "Autom&aacute;tico";
            }

            echo "<TR style='background: none repeat scroll 0 0 #F4F7FB;font-size: 10px;'>\n";

            echo "<TD class='tac'><b>$status_data</b></TD>\n";
            echo "<TD class='tac'>$descricao</TD>\n";
            echo "<TD class='tal' > &nbsp; $status_observacao</TD>\n";
            echo "<TD class='tac'>$status_admin</TD>\n";
            echo "</TR>\n";
        }
        echo "</TABLE>\n";
        exit;
    }else{
        echo "<p style='color: #485989;border: 1px solid #D2E4FC;font-family:Helvetica,Arial,sans-serif; font-size:16px;'>Não Há Intervenções para a OS $os_hist</p>";
        exit;
    }
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS x Atendimentos";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});
<?
if($login_fabrica == 114){
?>
        function intervencao_shadow(os){
            Shadowbox.open({
                content: "<?=$PHP_SELF?>?mostra=1&os_hist="+os,
                player: "iframe",
                title:  "Histórico de Intervenção",
                width:  800,
                height: 500
            });
        }
<?
}
?>

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>

			<div class='span4'>
				 <label class="radio">
			        <input type="radio" name="tipo_data" value="abertura" checked>
			        Data Abertura
			    </label>
			</div>
			<div class='span4'>
			    <label class="radio">
			        <input type="radio" name="tipo_data" value="fechamento" <?php if($tipo_data == "fechamento") echo "checked"; ?> >
			        Data Fechamento
			    </label>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_referencia'>Ref. Peças</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_descricao'>Descrição Peça</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Linha</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="linha" id="linha">
								<option value=""></option>
								<?php
								$sql = "SELECT linha, nome
										FROM tbl_linha
										WHERE fabrica = $login_fabrica
										AND ativo";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

										<?php echo $key['nome']?>

									</option>
								<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'>Familia</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="familia" id="familia">
								<option value=""></option>
								<?php

									$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo order by descricao";
									$res = pg_query($con,$sql);
									foreach (pg_fetch_all($res) as $key) {

										$selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;

									?>
										<option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
											<?php echo $key['descricao']?>
										</option>


									<?php
									}

								?>
							</select>
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
		</div>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";

			if (pg_num_rows($resSubmit) > 500) {
				$count = 500;
				?>
				<div id='registro_max'>
					<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
				</div>
			<?php
			} else {
				$count = pg_num_rows($resSubmit);
			}
		?>
			<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>OS</th>
						<th>Abertura</th>
						<th>Fechamento</th>
                        <th>Data Faturamento</th>
                        <th>Dias Aberto</th>
						<th>Atendimento</th>
						<th>Produto</th>
						<th>Série</th>
					<? if($login_fabrica == 114){ ?>
                        <th>Data NF</th>
						<th>Histórico Intervenção</th>
						<th>Tipo de Atendimento</th>
					<? } ?>
						<th>Defeito Reclamado</th>
						<th>Defeito Analisado</th>
						<th>Peça</th>
						<th>Serviço</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {
						$os                 = pg_fetch_result($resSubmit, $i, 'os');
						$sua_os             = pg_fetch_result($resSubmit, $i, 'sua_os');
						$hd_chamado         = pg_fetch_result($resSubmit, $i, 'hd_chamado');
						$produto            = pg_fetch_result($resSubmit, $i, 'produto');
						$serie              = pg_fetch_result($resSubmit, $i, 'serie');
						$data_abertura      = pg_fetch_result($resSubmit, $i, 'data_abertura');
						$dt_abertura      = pg_fetch_result($resSubmit, $i, 'dt_abertura');
						$data_fechamento    = pg_fetch_result($resSubmit, $i, 'data_fechamento');
						$defeito            = pg_fetch_result($resSubmit, $i, 'defeito');
						$defeito_reclamdo   = pg_fetch_result($resSubmit, $i, 'defeito_reclamado');
						if($login_fabrica == 114){
							$data_nf    = pg_fetch_result($resSubmit, $i, 'data_nf');
							$selo  		= pg_fetch_result($resSubmit, $i, 'selo');
							$oem        = pg_fetch_result($resSubmit, $i, 'oem');
							$data_nf 	= date("d/m/Y",strtotime($data_nf));
							$oem        = ($selo == "selo anexado") ? "Atendimento Garantia Estendida" : "";
						}else{
							$data_nf = "";
							$oem = "";
						}

						$sql = "SELECT DISTINCT '<b>' || tbl_peca.referencia || '</b> - ' || tbl_peca.descricao AS peca,
								tbl_servico_realizado.descricao AS servico,
								to_char(emissao,'DD/MM/YYYY') as emissao
								FROM tbl_os_item
								JOIN tbl_os_produto USING(os_produto)
								JOIN tbl_peca USING(peca)
								LEFT JOIN tbl_servico_realizado USING(servico_realizado)
								LEFT JOIN tbl_faturamento_item ON (tbl_faturamento_item.pedido_item = tbl_os_item.pedido_item or (tbl_faturamento_item.os = $os and tbl_faturamento_item.peca = tbl_os_item.peca)) and tbl_faturamento_item.pedido is not null
								LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento and (tbl_faturamento.fabrica = $login_fabrica OR tbl_faturamento.fabrica = 10)
								WHERE fabrica_i = {$login_fabrica}
								AND tbl_os_produto.os = {$os}";

						$res = pg_query($con, $sql);

						unset($result, $pecas, $servicos,$emissao,$dias_aberto);
						if(pg_num_rows($res) == 0){
							$body = "<tr>
										<td class='tac' style='vertical-align: middle;'><a href='os_press.php?os={$os}' target='_blank' >{$sua_os}</a></td>
										<td class='tac' style='vertical-align: middle;'>{$data_abertura}</td>
										<td class='tac' style='vertical-align: middle;'>{$data_fechamento}</td>
										<td class='tac' style='vertical-align: middle;'></td>
										<td class='tac' style='vertical-align: middle;'></td>
										<td class='tac' style='vertical-align: middle;'><a href='callcenter_interativo_new.php?callcenter={$hd_chamado}' target='_blank' >{$hd_chamado}</a></td>
										<td class='tal' style='vertical-align: middle;'>{$produto}</td>
										<td class='tac' style='vertical-align: middle;'>{$serie}</td>";
							if($login_fabrica == 114){
	                            $body .="<td class='tac' style='vertical-align: middle;'>{$data_nf}</td>";
								$body .="<td class='tac' style='vertical-align: middle;'><a onclick='javascript:intervencao_shadow($os);' style='cursor:pointer;'>Ver Histórico</a></td>";
								$body .="<td class='tac' style='vertical-align: middle;'>{$oem}</td>";
							}
							$body .="   <td class='tal' style='vertical-align: middle;'>{$defeito_reclamdo}</td>
										<td class='tal' style='vertical-align: middle;'>{$defeito}</td>
										<td class='tal' style='vertical-align: middle;'></td>
										<td class='tal' style='vertical-align: middle;'></td>
									</tr>";
							echo $body;
						}else{
							while ($result = pg_fetch_array($res)) {
								$pecas    = $result['peca'];
								$servicos = $result['servico'];
								$emissao = $result['emissao'];




								if(!empty($emissao)) {
									$sql = "SELECT '$emissao'::date - '$data_abertura'::date";
									$resAux = pg_query($con,$sql);
									if(pg_num_rows($resAux) > 0 ) {
										$dias_aberto = pg_fetch_result($resAux,0,0);
									}
								}

								$body = "<tr>
										<td class='tac' style='vertical-align: middle;'><a href='os_press.php?os={$os}' target='_blank' >{$sua_os}</a></td>
										<td class='tac' style='vertical-align: middle;'>{$data_abertura}</td>
										<td class='tac' style='vertical-align: middle;'>{$data_fechamento}</td>
										<td class='tac' style='vertical-align: middle;'>".$emissao."</td>
										<td class='tac' style='vertical-align: middle;'>{$dias_aberto}</td>
										<td class='tac' style='vertical-align: middle;'><a href='callcenter_interativo_new.php?callcenter={$hd_chamado}' target='_blank' >{$hd_chamado}</a></td>
										<td class='tal' style='vertical-align: middle;'>{$produto}</td>
										<td class='tac' style='vertical-align: middle;'>{$serie}</td>";
								if($login_fabrica == 114){
		                            $body .="<td class='tac' style='vertical-align: middle;'>{$data_nf}</td>";
									$body .="<td class='tac' style='vertical-align: middle;'><a onclick='javascript:intervencao_shadow($os);' style='cursor:pointer;'>Ver Histórico</a></td>";
									$body .="<td class='tac' style='vertical-align: middle;'>{$oem}</td>";
								}
								$body .="   <td class='tal' style='vertical-align: middle;'>{$defeito_reclamdo}</td>
											<td class='tal' style='vertical-align: middle;'>{$defeito}</td>
											<td class='tal' style='vertical-align: middle;'>".$pecas."</td>
											<td class='tal' style='vertical-align: middle;'>".$servicos."</td>
										</tr>";
								echo $body;
							}
						}

					}
					?>
				</tbody>
			</table>

			<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_os_atendimento" });
				</script>
			<?php
			}
			?>

			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
		<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
		}
	}



include 'rodape.php';?>
