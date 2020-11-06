<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios	="gerencia";
$layout_menu 		= "gerencia";
$title 				= "PRODUTOS AGUARDANDO REMANUFATURA/EXPEDIÇÃO";

include "cabecalho_new.php";

$sql = "SELECT tbl_os.os, tbl_familia.familia, tbl_tipo_posto.posto_interno, tbl_os.data_abertura
	INTO TEMP tmp_os_aberta_$login_admin
	FROM tbl_os
	JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
	JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = $login_fabrica
	JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
	JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = $login_fabrica AND tbl_tipo_posto.posto_interno
	WHERE tbl_os.fabrica = $login_fabrica
	AND tbl_os.data_fechamento IS NULL
	AND tbl_os.finalizada IS NULL
	AND tbl_os.data_conserto IS NOT NULL
	AND tbl_os.excluida IS NOT TRUE";
$res = pg_query($con,$sql);

$sql = "SELECT familia, upper(descricao) AS descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo";
$resFamilia = pg_query($con,$sql);
$totalFamilia = pg_num_rows($resFamilia);
$contFamilia = (pg_num_rows($resFamilia) / 2);
$contFamilia = ( (pg_num_rows($resFamilia) % 2) > 0) ? (int) $contFamilia + 1 : $contFamilia;

?>

<style>

#painel_os > table{
	margin: 0 auto;
}

table {
	border-collapse: collapse;
}

td.tabela_resultado {
	vertical-align: top;
}

th.titulo_coluna {
	background-color: #596D9B;
	color: #FFFFFF;
}

th.espaco, td.espaco {
	border: 0px;
	width: 30px;
}

td.logo {
	border: 0px;
	font-weight: bold;
	font-size: 12px;
}

td.logo > img {
	width: 80px;
	height: 80px;

}

td.pf {
	border-right: 1px solid #000;
	border-left: 1px solid #000;
	border-bottom: 0px;
	border-top: 0px;
	width: 50px;
	height: 30px;
	text-align: center;
	font-weight: bold;
	font-size: 15px;
}

td.pj {
	border-right: 1px solid #000;
	border-left: 1px solid #000;
	border-bottom: 0px;
	border-top: 0px;
	width: 50px;
	height: 30px;
	text-align: center;
	font-weight: bold;
	font-size: 15px;
}

td.mais_antigo {
	border-right: 1px solid #000;
	border-left: 1px solid #000;
	border-bottom: 0px;
	border-top: 0px;
	height: 30px;
	text-align: center;
	font-weight: bold;
	font-size: 15px; 
}

td.totaliza{
	border: 1px solid #000;
	width:  90px;
	height: 60px;
	text-align: center;
	font-weight: bold;
	font-size: 20px;
}

td.total{
	width: 180px;
}

td.seta{
	width:  100px;
	height: 60px;
	text-align: center;
}

td.reparo{
	background-color: #C0C0C0;
	border: 1px solid #000;
	width: 360px;
	height: 60px;
	font-size: 18px;
	text-align: center;
	font-weight: bold;
}

td.espaco2 {
	border: 0px;
	width: 30px;
}

td.espaco3 {
	border: 0px;
	width: 10px;
}

.legenda{
	border-right: 1px solid #000;
	border-left: 1px solid #000;
	border-bottom: 1px solid #000;
	border-top: 1px solid #000;
	height: 30px;
	text-align: center;
	font-weight: bold;
}

</style>

<script>

$(function() {
	$("table").each(function() {
		$(this).find("td.pf").first().css({ "border-top": "1px solid #000" });
		$(this).find("td.pj").first().css({ "border-top": "1px solid #000" });
		$(this).find("td.mais_antigo").first().css({ "border-top": "1px solid #000" });

		$(this).find("td.pf").last().css({ "border-bottom": "1px solid #000" });
		$(this).find("td.pj").last().css({ "border-bottom": "1px solid #000" });
		$(this).find("td.mais_antigo").last().css({ "border-bottom": "1px solid #000" });
	});

	setTimeout(function(){ location.reload(); }, 600000);
});

</script>
</div>

<div id="painel_os">
	<table>
		<tr>
			<td class="tabela_resultado">
				<table>

					<thead>

						<tr>
							<th class="titulo_coluna">Marcas</th>
							<th class="espaco">&nbsp;</th>
							<th class="titulo_coluna">Posto Interno</th>
							<th class="espaco">&nbsp;</th>
							<th class="titulo_coluna">MAIS ANTIGO</th>
						</tr>

						<tr>
							<td colspan="7">&nbsp;</td>
						</tr>

					</thead>

					<tbody>
						<?php
						for($i = 0; $i < $contFamilia; $i++){

							$familia = pg_fetch_result($resFamilia,$i,'familia');
							$nome_familia = pg_fetch_result($resFamilia,$i,'descricao');
							
							$sql = "SELECT count(os) AS total_pj
				                                FROM tmp_os_aberta_$login_admin
				                                WHERE familia = $familia";
				                        $resPJ = pg_query($con,$sql);
							$total_pj = pg_fetch_result($resPJ,0,'total_pj');
							$totalizador_pj += $total_pj;

							$sql = "SELECT to_char(data_abertura,'DD/MM/YY') AS data_abertura,
										   data_abertura AS tempo_espera
								FROM tmp_os_aberta_$login_admin
								WHERE familia = $familia
								AND posto_interno IS TRUE
								ORDER BY tempo_espera
								LIMIT 1";
							$resDT = pg_query($con,$sql);
							$data_interno  = pg_fetch_result($resDT,0,'data_abertura');
							$tempo_espera_pa_interno  = pg_fetch_result($resDT,0,'tempo_espera');

							if(empty($data_interno)){
								$data_interno = "OK";
							}

							echo "<tr>
									<td class='logo'>{$nome_familia}</td>
									<td class='espaco'>&nbsp;</td>
									<td class='pj'>{$total_pj}</td>
									<td class='espaco'>&nbsp;</td>
									<td class='mais_antigo'>{$data_interno}</td>
							      </tr>";
						}
						?>

					</tbody>

				</table>
			</td>
			<td class="tabela_resultado" style="padding-left: 30px;">
				<table>

					<thead>

						<tr>
							<th class="titulo_coluna">Marcas</th>
							<th class="espaco">&nbsp;</th>
							<th class="titulo_coluna">Posto Interno</th>
							<th class="espaco">&nbsp;</th>
							<th class="titulo_coluna">MAIS ANTIGO</th>
						</tr>

						<tr>
							<td colspan="7">&nbsp;</td>
						</tr>

					</thead>

					<tbody>
						<?php
						for($j = $i; $j < pg_num_rows($resFamilia); $j++){

							$familia = pg_fetch_result($resFamilia,$j,'familia');
							$nome_familia = pg_fetch_result($resFamilia,$j,'descricao');
							
							$sql = "SELECT count(os) AS total_pj
				                                FROM tmp_os_aberta_$login_admin
				                                WHERE familia = $familia";
				                        $resPJ = pg_query($con,$sql);
							$total_pj = pg_fetch_result($resPJ,0,'total_pj');
							$totalizador_pj += $total_pj;

							$sql = "SELECT to_char(data_abertura,'DD/MM/YY') AS data_abertura,
										   data_abertura AS tempo_espera
								FROM tmp_os_aberta_$login_admin
								WHERE familia = $familia
								AND posto_interno IS TRUE
								ORDER BY tempo_espera
								LIMIT 1";
							$resDT = pg_query($con,$sql);
							$data_interno  = pg_fetch_result($resDT,0,'data_abertura');
							$tempo_espera_pa_interno  = pg_fetch_result($resDT,0,'tempo_espera');

							if(empty($data_interno)){
								$data_interno = "OK";
							}

							echo "<tr>
									<td class='logo'>{$nome_familia}</td>
									<td class='espaco'>&nbsp;</td>
									<td class='pj'>{$total_pj}</td>
									<td class='espaco'>&nbsp;</td>
									<td class='mais_antigo'>{$data_interno}</td>
							      </tr>";
						}
						?>

					</tbody>

				</table>
			</td>

		</tr>	
	</table>

	

	<table>
		<tr><td colspan='9'>&nbsp;</td></t>
		<tr>
			<td class='reparo'>TOTAL GERAL</td>
			<td class="espaco2">&nbsp;</td>
			<td class='totaliza total'><?=$totalizador_pj?></td>
		</tr>
	</table>
</div>
<?php
include "rodape.php"; 
?>
