<?php
include "dbconfig.php";
include 'includes/dbconnect-inc.php';

$admin_privilegios = "*,financeiro,gerencia,call_center";
$layout_menu       = "callcenter";

include "autentica_admin.php";

$title = "RELATÓRIO AMBEV";

include "cabecalho.php";

if ($_POST["btn_acao"] == "pesquisar") {
	$data_inicial = $_POST["data_inicial"];
	$data_final   = $_POST["data_final"];

	$resolvido 	  = $_POST["resolvido"];

	if($resolvido == 't'){
		$complemento_sql = "AND tbl_hd_chamado.status = 'Resolvido'";
	}


	if ($_POST['garantia_estendida_ambev'] != "") {
		$garantia_estendida_ambev = $_POST['garantia_estendida_ambev'];
	} else {
		$garantia_estendida_ambev = "";
	}

	if (!strlen($data_final) || !strlen($data_final)) {
		$msg_erro[] = "Digite a data inicial e data final";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
			$msg_erro[] = "Data inválida";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro[] = "Data final não pode ser menor que a data inicial";
			} else {
				$sql = "SELECT '{$aux_data_inicial}'::date + interval '6 months' > '{$aux_data_final}'";
				$res = pg_query($con, $sql);

				$periodo = pg_fetch_result($res, 0, 0);

				if($periodo == "f") {
					$msg_erro[] = "Período não pode ser maior que 6 Meses";
				}
			}
		}
	}

	if (!isset($msg_erro)) {
		$sql = "SELECT
					tbl_os.os,
					tbl_hd_chamado_extra.array_campos_adicionais,
					tbl_produto.referencia AS modelo,
					tbl_hd_chamado_extra.serie AS rg,
					tbl_hd_chamado.status AS status,
					tbl_hd_chamado.hd_chamado AS id_os,
					TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YYYY') AS dt_abertura,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado AS uf,
					tbl_hd_chamado_extra.cpf AS cnpj,
					tbl_hd_chamado_extra.fone AS telefone,
					tbl_hd_chamado_extra.nome,
					tbl_posto.nome AS sta,
					CASE WHEN (tbl_os.data_nf + INTERVAL '24 MONTHS') < current_timestamp THEN
						'false'
					ELSE
						'true'
					END AS garantia,
					tbl_os.defeito_reclamado_descricao AS sintoma1
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra
					ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
				JOIN tbl_produto
					ON tbl_produto.produto = tbl_hd_chamado_extra.produto
					AND tbl_produto.fabrica_i = {$login_fabrica}
				LEFT JOIN tbl_os
					ON tbl_os.os = tbl_hd_chamado_extra.os
					AND tbl_os.fabrica = {$login_fabrica}
				JOIN tbl_cidade
					ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
				LEFT JOIN tbl_posto_fabrica
					ON tbl_posto_fabrica.posto = tbl_os.posto
					AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				LEFT JOIN tbl_posto
					ON tbl_posto.posto = tbl_posto_fabrica.posto
				LEFT JOIN tbl_cliente_admin
					ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin
					AND tbl_cliente_admin.fabrica = {$login_fabrica}
					AND tbl_cliente_admin.codigo = 'ambev'
				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
				$complemento_sql
				AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND tbl_hd_chamado_extra.array_campos_adicionais ILIKE '%\"atendimento_ambev\":\"t\"%'";
		$res = pg_query($con, $sql);
	}
}
?>

<style type="text/css">
	@import "../plugins/jquery/datepick/telecontrol.datepick.css";

	.Titulo {
		text-align: center;
		font-family: Arial;
		font-size: 11px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #485989;
	}

	.Erro {
		text-align: center;
		font-family: Arial;
		font-size: 12px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #FF0000;
	}

	.Conteudo {
		text-align: left;
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
	}

	.Conteudo2 {
		text-align: center;
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
	}

	.Caixa{
		BORDER-RIGHT: #6699CC 1px solid;
		BORDER-TOP: #6699CC 1px solid;
		FONT: 8pt Arial ;
		BORDER-LEFT: #6699CC 1px solid;
		BORDER-BOTTOM: #6699CC 1px solid;
		BACKGROUND-COLOR: #FFFFFF
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
		text-align:left;
	}

	.subtitulo{

		background-color: #7092BE;
		font:bold 11px Arial;
		color: #FFFFFF;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
</style>

<script src="js/jquery-1.3.2.js" ></script>
<script src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src="js/jquery.maskedinput2.js"></script>

<script>
	$(function(){
		$("#data_inicial, #data_final").datepick({startDate:"01/01/2000"}).maskedinput("99/99/9999");
	});
</script>

<?php
if (isset($msg_erro)) {
?>
	<table style="width: 700px; margin: 0 auto;" border="0" >
		<tr>
			<td class="msg_erro" ><?=implode("<br />", $msg_erro)?></td>
		</tr>
	</table>
<?php
}
?>

<form name="frm_relatorio" method="POST" >
	<table class="formulario" border="0" style="width: 700px; margin: 0 auto;" >
		<thead>
			<tr class="titulo_tabela" >
				<td colspan="4" >
					Parâmetros de Pesquisa
				</td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="5" align="center" style="color:red">Caso não selecionado o campo Resolvido traremos os chamados com todos os status.</td>
			</tr>
			<tr>
				<td style="width: 50px;">&nbsp;</td>
				<td>Data Inicial</td>
				<td>Data Final</td>
			</tr>
			<tr>
				<td style="width: 150px;">&nbsp;</td>
				<td style='width:240px'>
					<input type="text" name="data_inicial" id="data_inicial" class="frm" style="width: 100px;" value="<?=$data_inicial?>" >
				</td>
				<td>
					<input type="text" name="data_final" id="data_final" class="frm" style="width: 100px;" value="<?=$data_final?>" >
				</td>
			</tr>
			<? if ($login_fabrica == 85) { ?>
			<tr>
				<td style="width: 50px;">&nbsp;</td>
				<td>
					<strong>Somente com Garantia Estendida:</strong>
					&nbsp;
					<input type="checkbox" name="garantia_estendida_ambev" value="t" <?= ($garantia_estendida_ambev == "t") ? "CHECKED" : "" ?> />
				</td>
				<td>
					<strong>Resolvido:</strong>
					&nbsp;
					<input type="checkbox" name="resolvido" value="t" <?= ($resolvido == "t") ? "CHECKED" : "" ?> />
				</td>
			</tr>
			<? } ?>
			<tr><td coslpan="3">&nbsp;</td></tr>
			<tr>
				<td colspan="3" style="text-align: center;" >
					<input type="hidden" name="btn_acao" value="" />
					<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" onclick="$('input[name=btn_acao]').val('pesquisar'); $(this).parents('form').submit();" >
				</td>
			</tr>
			<tr><td coslpan="3">&nbsp;</td></tr>
		</tbody>
	</table>
</form>

<?php
if ($_POST["btn_acao"] == "pesquisar" && !isset($msg_erro)) {

	if ($login_fabrica == 85) {
		foreach(pg_fetch_all($res) as $key) {
			$array_campos_adicionais = json_decode(utf8_encode(stripslashes($key['array_campos_adicionais'])));
			if ($array_campos_adicionais->garantia_estendida_ambev == "t") {
				$array_os[] = $key['id_os'];
			}
		}
	}

	$data = date("d-m-Y-H-i");
	$file = fopen("/tmp/relatorio_ambev_{$data}.xls", "w");
	fwrite($file, "<table>
		<thead>
			<tr>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					MODELO
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					RG
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					STATUS
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					DT_FABRICACAO
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					FABRICANTE
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					ID_ATENDIMENTO
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					ID_OS
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					DT_ABERTURA
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					CIDADE
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					UF
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					RAZAO
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					FATANSIA
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					CNPJ/CPF
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					TELEFONE
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					NOME
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					STA
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					DT_ENCERRAMENTO
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					GARANTIA
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					OBSERVAÇÃO
				</td>
                <td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
                    DEFEITO CONSTATADO
                </td>
                <td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
                    SOLUÇÃO
                </td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 1
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 2
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 3
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 4
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 5
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 6
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 7
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 8
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 9
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					SINTOMA 10
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 1
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 2
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 3
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 4
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 5
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 6
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 7
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 8
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 9
				</td>
				<td bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>
					PEÇA 10
				</td>
			</tr>
		</thead>
		<tbody>
	");
?>
	<br />

	<table class="tabela" border="0" style="margin: 0 auto;" >
		<thead>
			<tr class="titulo_coluna" >
				<td>
					MODELO
				</td>
				<td>
					RG
				</td>
				<td>
					STATUS
				</td>
				<td>
					DT_FABRICACAO
				</td>
				<td>
					FABRICANTE
				</td>
				<td>
					ID_ATENDIMENTO
				</td>
				<td>
					ID_OS
				</td>
				<td>
					DT_ABERTURA
				</td>
				<td>
					CIDADE
				</td>
				<td>
					UF
				</td>
				<td>
					RAZAO
				</td>
				<td>
					FATANSIA
				</td>
				<td>
					CNPJ/CPF
				</td>
				<td>
					TELEFONE
				</td>
				<td>
					NOME
				</td>
				<td>
					STA
				</td>
				<td>
					DT_ENCERRAMENTO
				</td>
				<td>
					GARANTIA
				</td>
                <td>
                    OBSERVAÇÃO
                </td>
                <td>
                    DEFEITO CONSTATADO
                </td>
				<td>
					SOLUÇÃO
				</td>
				<td nowrap>
					SINTOMA 1
				</td>
				<td nowrap>
					SINTOMA 2
				</td>
				<td nowrap>
					SINTOMA 3
				</td>
				<td nowrap>
					SINTOMA 4
				</td>
				<td nowrap>
					SINTOMA 5
				</td>
				<td nowrap>
					SINTOMA 6
				</td>
				<td nowrap>
					SINTOMA 7
				</td>
				<td nowrap>
					SINTOMA 8
				</td>
				<td nowrap>
					SINTOMA 9
				</td>
				<td nowrap>
					SINTOMA 10
				</td>
				<td nowrap>
					PEÇA 1
				</td>
				<td nowrap>
					PEÇA 2
				</td>
				<td nowrap>
					PEÇA 3
				</td>
				<td nowrap>
					PEÇA 4
				</td>
				<td nowrap>
					PEÇA 5
				</td>
				<td nowrap>
					PEÇA 6
				</td>
				<td nowrap>
					PEÇA 7
				</td>
				<td nowrap>
					PEÇA 8
				</td>
				<td nowrap>
					PEÇA 9
				</td>
				<td nowrap>
					PEÇA 10
				</td>
			</tr>
		</thead>
		<tbody>
			<?php

			for ($i = 0; $i < pg_num_rows($res); $i++) {
				unset($sintoma, $peca);

				$array_campos_adicionais = pg_fetch_result($res, $i, "array_campos_adicionais");
				$array_campos_adicionais = json_decode($array_campos_adicionais, true);
				$consumidor_cpf_cnpj     = $array_campos_adicionais["consumidor_cpf_cnpj"];

				$modelo          = pg_fetch_result($res, $i, "modelo");
				$status          = pg_fetch_result($res, $i, "status");
				$rg              = pg_fetch_result($res, $i, "rg");
				$dt_fabricacao   = $array_campos_adicionais["data_fabricacao_ambev"];
				$fabricante      = "GELOPAR";
				$id_os           = pg_fetch_result($res, $i, "id_os");
				$dt_abertura     = pg_fetch_result($res, $i, "dt_abertura");
				$cidade          = pg_fetch_result($res, $i, "cidade");
				$uf              = pg_fetch_result($res, $i, "uf");
				$razao           = "&nbsp;";
				$fantasia        = "&nbsp;";
				$cnpj            = pg_fetch_result($res, $i, "cnpj");
				$telefone        = pg_fetch_result($res, $i, "telefone");
				$nome            = $array_campos_adicionais["atendimento_ambev_nome"];
				$sta             = pg_fetch_result($res, $i, "sta");
				$dt_encerramento = $array_campos_adicionais["data_encerramento_ambev"];
				$garantia        = pg_fetch_result($res, $i, "garantia");
				($garantia == true) ? $garantia = "Sim" : $garantia = "Não";
				$observacao      = $array_campos_adicionais["codigo_ambev"];
				$sintomas[1]     = pg_fetch_result($res, $i, "sintoma1");
				$sintomas[2]     = "&nbsp;";
				$sintomas[3]     = "&nbsp;";
				$sintomas[4]     = "&nbsp;";
				$sintomas[5]     = "&nbsp;";
				$sintomas[6]     = "&nbsp;";
				$sintomas[7]     = "&nbsp;";
				$sintomas[8]     = "&nbsp;";
				$sintomas[9]     = "&nbsp;";
				$sintomas[10]    = "&nbsp;";
				$pecas[1]        = "&nbsp;";
				$pecas[2]        = "&nbsp;";
				$pecas[3]        = "&nbsp;";
				$pecas[4]        = "&nbsp;";
				$pecas[5]        = "&nbsp;";
				$pecas[6]        = "&nbsp;";
				$pecas[7]        = "&nbsp;";
				$pecas[8]        = "&nbsp;";
				$pecas[9]        = "&nbsp;";
				$pecas[10]       = "&nbsp;";

				if ($consumidor_cpf_cnpj == "R") {
					$fantasia = $array_campos_adicionais["nome_fantasia"];
					$razao    = pg_fetch_result($res, $i, "nome");
				}

				$os = pg_fetch_result($res, $i, "os");
			
				if (strlen($os) > 0){
					$sqlDefSolucao = "
                			    SELECT  tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
		                            tbl_solucao.descricao AS solucao_descricao
                			    FROM    tbl_os_defeito_reclamado_constatado
			                    JOIN    tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado=tbl_defeito_constatado.defeito_constatado
			                    JOIN    tbl_solucao ON tbl_os_defeito_reclamado_constatado.solucao=tbl_solucao.solucao
				                WHERE       tbl_os_defeito_reclamado_constatado.os = $os
					";

					$resDefSolucao = pg_query($con,$sqlDefSolucao);

					$defeito = pg_fetch_all_columns($resDefSolucao,0);
                			$solucao = pg_fetch_all_columns($resDefSolucao,1);

			                $mostra_defeito = implode(", ",$defeito);
					$mostra_solucao = implode(", ",$solucao);

					$sql_peca = "SELECT tbl_peca.referencia || ' - ' || tbl_peca.descricao AS referencia
							 FROM tbl_os_item
							 JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
							 JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
							 WHERE tbl_os_produto.os = {$os}";
					$res_peca = pg_query($con, $sql_peca);

					if (pg_num_rows($res_peca)) {
						for ($k = 0; $k < pg_num_rows($res_peca); $k++) {
							$pecas[$k + 1] = pg_fetch_result($res_peca, $k, "referencia");
						}
					}
				}
				if ($login_fabrica == 85) {
					if ($garantia_estendida_ambev == "t") {

						if (in_array($id_os, $array_os)) {
							fwrite($file, "<tr>
								<td>
									{$modelo}
								</td>
								<td>
									{$rg}
								</td>
								<td>
									{$status}
								</td>
								<td>
									{$dt_fabricacao}
								</td>
								<td>
									{$fabricante}
								</td>
								<td>
									{$id_os}
								</td>
								<td>
									{$os}
								</td>
								<td>
									{$dt_abertura}
								</td>
								<td>
									{$cidade}
								</td>
								<td>
									{$uf}
								</td>
								<td>
									{$razao}
								</td>
								<td>
									{$fantasia}
								</td>
								<td>
									{$cnpj}
								</td>
								<td>
									{$telefone}
								</td>
								<td>
									{$nome}
								</td>
								<td>
									{$sta}
								</td>
								<td>
									{$dt_encerramento}
								</td>
								<td>
									{$garantia}
								</td>
			                    <td>
			                        {$observacao}
			                    </td>
			                    <td>
			                        {$mostra_defeito}
			                    </td>
								<td>
									{$mostra_solucao}
								</td>
							");

							echo "<tr>
								<td>
									{$modelo}
								</td>
								<td>
									{$rg}
								</td>
								<td>
									{$status}
								</td>
								<td>
									{$dt_fabricacao}
								</td>
								<td>
									{$fabricante}
								</td>
								<td>
									{$id_os}
								</td>
								<td>
									{$os}
								</td>
								<td>
									{$dt_abertura}
								</td>
								<td>
									{$cidade}
								</td>
								<td>
									{$uf}
								</td>
								<td>
									{$razao}
								</td>
								<td>
									{$fantasia}
								</td>
								<td>
									{$cnpj}
								</td>
								<td>
									{$telefone}
								</td>
								<td>
									{$nome}
								</td>
								<td>
									{$sta}
								</td>
								<td>
									{$dt_encerramento}
								</td>
								<td>
									{$garantia}
								</td>
								<td>
									{$observacao}
								</td>
								<td>
			                        {$mostra_defeito}
			                    </td>
			                    <td>
			                        {$mostra_solucao}
			                    </td>";

								foreach ($sintomas as $sintoma) {
									fwrite($file, "<td>{$sintoma}</td>");
									echo "<td>{$sintoma}</td>";
								}

								foreach ($pecas as $peca) {
									fwrite($file, "<td>{$peca}</td>");
									echo "<td>{$peca}</td>";
								}

							fwrite($file, "</tr>");
							echo "</tr>";
						}
					} else {
						if (!in_array($id_os, $array_os)) {
							fwrite($file, "<tr>
								<td>
									{$modelo}
								</td>
								<td>
									{$rg}
								</td>
								<td>
									{$status}
								</td>
								<td>
									{$dt_fabricacao}
								</td>
								<td>
									{$fabricante}
								</td>
								<td>
									{$id_os}
								</td>
								<td>
									{$os}
								</td>
								<td>
									{$dt_abertura}
								</td>
								<td>
									{$cidade}
								</td>
								<td>
									{$uf}
								</td>
								<td>
									{$razao}
								</td>
								<td>
									{$fantasia}
								</td>
								<td>
									{$cnpj}
								</td>
								<td>
									{$telefone}
								</td>
								<td>
									{$nome}
								</td>
								<td>
									{$sta}
								</td>
								<td>
									{$dt_encerramento}
								</td>
								<td>
									{$garantia}
								</td>
			                    <td>
			                        {$observacao}
			                    </td>
			                    <td>
			                        {$mostra_defeito}
			                    </td>
								<td>
									{$mostra_solucao}
								</td>
							");

							echo "<tr>
								<td>
									{$modelo}
								</td>
								<td>
									{$rg}
								</td>
								<td>
									{$status}
								</td>
								<td>
									{$dt_fabricacao}
								</td>
								<td>
									{$fabricante}
								</td>
								<td>
									{$id_os}
								</td>
								<td>
									{$os}
								</td>
								<td>
									{$dt_abertura}
								</td>
								<td>
									{$cidade}
								</td>
								<td>
									{$uf}
								</td>
								<td>
									{$razao}
								</td>
								<td>
									{$fantasia}
								</td>
								<td>
									{$cnpj}
								</td>
								<td>
									{$telefone}
								</td>
								<td>
									{$nome}
								</td>
								<td>
									{$sta}
								</td>
								<td>
									{$dt_encerramento}
								</td>
								<td>
									{$garantia}
								</td>
								<td>
									{$observacao}
								</td>
								<td>
			                        {$mostra_defeito}
			                    </td>
			                    <td>
			                        {$mostra_solucao}
			                    </td>";

								foreach ($sintomas as $sintoma) {
									fwrite($file, "<td>{$sintoma}</td>");
									echo "<td>{$sintoma}</td>";
								}

								foreach ($pecas as $peca) {
									fwrite($file, "<td>{$peca}</td>");
									echo "<td>{$peca}</td>";
								}

							fwrite($file, "</tr>");
							echo "</tr>";
						}
					}
				} else {
					fwrite($file, "<tr>
						<td>
							{$modelo}
						</td>
						<td>
							{$rg}
						</td>
						<td>
							{$status}
						</td>
						<td>
							{$dt_fabricacao}
						</td>
						<td>
							{$fabricante}
						</td>
						<td>
							{$id_os}
						</td>
						<td>
							{$os}
						</td>
						<td>
							{$dt_abertura}
						</td>
						<td>
							{$cidade}
						</td>
						<td>
							{$uf}
						</td>
						<td>
							{$razao}
						</td>
						<td>
							{$fantasia}
						</td>
						<td>
							{$cnpj}
						</td>
						<td>
							{$telefone}
						</td>
						<td>
							{$nome}
						</td>
						<td>
							{$sta}
						</td>
						<td>
							{$dt_encerramento}
						</td>
						<td>
							{$garantia}
						</td>
	                    <td>
	                        {$observacao}
	                    </td>
	                    <td>
	                        {$mostra_defeito}
	                    </td>
						<td>
							{$mostra_solucao}
						</td>
					");

					echo "<tr>
						<td>
							{$modelo}
						</td>
						<td>
							{$rg}
						</td>
						<td>
							{$status}
						</td>
						<td>
							{$dt_fabricacao}
						</td>
						<td>
							{$fabricante}
						</td>
						<td>
							{$id_os}
						</td>
						<td>
							{$os}
						</td>
						<td>
							{$dt_abertura}
						</td>
						<td>
							{$cidade}
						</td>
						<td>
							{$uf}
						</td>
						<td>
							{$razao}
						</td>
						<td>
							{$fantasia}
						</td>
						<td>
							{$cnpj}
						</td>
						<td>
							{$telefone}
						</td>
						<td>
							{$nome}
						</td>
						<td>
							{$sta}
						</td>
						<td>
							{$dt_encerramento}
						</td>
						<td>
							{$garantia}
						</td>
						<td>
							{$observacao}
						</td>
						<td>
	                        {$mostra_defeito}
	                    </td>
	                    <td>
	                        {$mostra_solucao}
	                    </td>";

						foreach ($sintomas as $sintoma) {
							fwrite($file, "<td>{$sintoma}</td>");
							echo "<td>{$sintoma}</td>";
						}

						foreach ($pecas as $peca) {
							fwrite($file, "<td>{$peca}</td>");
							echo "<td>{$peca}</td>";
						}

					fwrite($file, "</tr>");
					echo "</tr>";
				}
			}
			?>
		</tbody>
	</table>

	<br />
<?php
	fwrite($file, "</tbody></table>");

	system("mv /tmp/relatorio_ambev_{$data}.xls xls/relatorio_ambev_{$data}.xls");

	echo "<h2 style='color: #88B3DD; text-align: center; font-size: 16px;'><a href='xls/relatorio_ambev_{$data}.xls' target='_blank'>Baixar arquivo EXCEL</a></h2>";
}

include "rodape.php";
?>
