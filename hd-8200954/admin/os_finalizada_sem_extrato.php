<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	$layout_menu = "financeiro";
	$title = "OS FINALIZADAS SEM EXTRATO";
	include 'cabecalho.php';

	$aux_data_inicial = null;
	$aux_data_final   = null;

	if (isset($_POST['gravar'])) {

		$posto_total = (int) $_POST['posto_total'];
		$aux_data_inicial = $_POST['data_inicial'];
		$aux_data_final   = $_POST['data_final'];

		if ($posto_total > 0 and !empty($aux_data_inicial) and !empty($aux_data_final)) {
			$log_erro    = array();
			$log_sucesso = array();

			for ($i=0; $i < $posto_total; $i++) { 
				$res = pg_query($con,"BEGIN TRANSACTION");

				$posto = (int) $_POST['posto_' . $i];
				$checked = $_POST['item_' . $i];

				if ($posto and $checked == 'checked') {
					$sql = "INSERT INTO tbl_extrato (posto, fabrica, avulso, total) VALUES ($posto, $login_fabrica, 0, 0) returning protocolo";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
					$protocolo = pg_fetch_result($res, 0, 'protocolo');

					$sql = "SELECT CURRVAL ('seq_extrato')";
					$res = pg_query($con, $sql);
					$msg_erro.= pg_last_error($con);
					$extrato  = pg_fetch_result($res, 0, 0);

					if ($extrato) {
						$sql = "UPDATE tbl_os_extra SET extrato = $extrato
								FROM tbl_os
								WHERE tbl_os.os = tbl_os_extra.os AND tbl_os_extra.extrato IS NULL 
								AND tbl_os.fabrica = $login_fabrica AND tbl_os.posto = $posto 
								AND finalizada IS NOT NULL 
								AND data_fechamento IS NOT NULL 
								AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
						$res = pg_query($con, $sql);
						$msg_erro.= pg_last_error($con);

						$sql = "SELECT fn_calcula_extrato ($login_fabrica, $extrato);";
						$res = pg_query($con, $sql);
						$msg_erro.= pg_last_error($con);

						$sql = "SELECT codigo_posto, nome FROM tbl_posto_fabrica 
								JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
								AND tbl_posto.posto = $posto";
						$qry = pg_query($con, $sql);

						if (empty($msg_erro)) {
							$sql1 = "select count(os) from tbl_os_extra where extrato = $extrato";
							$res1 = pg_query($con, $sql1);

							if (pg_num_rows($res1) == 0) {
								$log_erro[] = "Erro ao gerar extrato, favor repetir o procedimento!";
								$res = pg_query($con, "ROLLBACK TRANSACTION");
							} elseif (pg_result($res1, 0, 0) == 0) {
								$log_erro[] = "Extrato sem nenhuma Ordem de Serviço, favor repetir o procedimento!";
								$res = pg_query($con, "ROLLBACK TRANSACTION");
							} else {
								$res = pg_query($con, "COMMIT TRANSACTION");

								$sql = "SELECT pecas, 
												mao_de_obra, 
												avulso, 
												total, 
												TO_CHAR (data_geracao, 'DD/MM/YYYY') AS data_geracao 
											FROM tbl_extrato WHERE extrato = $extrato";
								$qext = pg_query($con, $sql);

								$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
								$pecas = pg_fetch_result($qext, 0, 'pecas');
								$mob = pg_fetch_result($qext, 0, 'mao_de_obra');
								$avulso = pg_fetch_result($qext, 0, 'avulso');
								$total = pg_fetch_result($qext, 0, 'total');

								$log_linha = '<tr bgcolor="' . $cor . '">';
								$log_linha.= '<td>' . pg_fetch_result($qry, 0, 'codigo_posto') . '</td>';
								$log_linha.= '<td>' . pg_fetch_result($qry, 0, 'nome') . '</td>';
								$log_linha.= '<td><a href="extrato_consulta_os.php?extrato=' . $extrato . '" target="_blank">' . $protocolo . '</a></td>';
								$log_linha.= '<td>' . pg_fetch_result($qext, 0, 'data_geracao') . '</td>';
								$log_linha.= '<td>' . number_format($pecas, 2, ',', '.') . '</td>';
								$log_linha.= '<td>' . number_format($mob, 2, ',', '.') . '</td>';
								$log_linha.= '<td>' . number_format($avulso, 2, ',', '.') . '</td>';
								$log_linha.= '<td>' . number_format($total, 2, ',', '.') . '</td>';

								$log_sucesso[] = $log_linha;
							}
						} else {
							$res = pg_query($con,"ROLLBACK TRANSACTION");
							$log_erro[] = 'Erro ao gerar extrato para o posto ' . pg_fetch_result($qry, 0, 'codigo_posto') . ' - ' . pg_fetch_result($qry, 0, 'nome') . ': ' . $msg_erro;
						}
					}
				}
			}
		}

		$data_inicial = null;
		$data_final   = null;

	}

	if ( isset ( $_POST['enviar'] ) ) {

		$data_inicial 	= $_POST["data_inicial"];
    	$data_final 	= $_POST["data_final"];
		$descricao_posto = $_POST['descricao_posto'];
    	$codigo_posto   = addslashes($_POST['codigo_posto']);

    	try {

			/* Inicio validação de datas */

			$bCheckdate = false;

			if (!empty($data_inicial) or !empty($data_final)) {
				$bCheckdate = true;
			}

			if (true === $bCheckdate) {
				list($di, $mi, $yi) = explode("/", $data_inicial);
				list($df, $mf, $yf) = explode("/", $data_final);

				if( !checkdate($mf,$df,$yf) || !checkdate($mi,$di,$yi) ) {

					throw new Exception('Data Inválida');

				}

		        $aux_data_inicial = "$yi-$mi-$di";
		        $aux_data_final = "$yf-$mf-$df";

		        if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_final) > strtotime('today')){
			        throw new Exception('Data Inválida');
			    }

		        if (strtotime($aux_data_inicial.'+3 year') < strtotime($aux_data_final) && empty($msg_erro) ) {
		        	throw new Exception('O intervalo entre as datas não pode ser maior que 3 anos');
				}
			} else {
				date_default_timezone_set('America/Sao_Paulo');

				$aux_data_final = date('Y-m-d');

				$oDate = new DateTime($hoje);
				$oDate->sub(new DateInterval('P3Y'));

				$aux_data_inicial = $oDate->format('Y-m-d');
			}

		    $cond[] = "tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";

		    /* Fim validação de datas */

		    if ( empty($msg_erro) && !empty($codigo_posto) ) {

			    $sql = "SELECT posto
			    		FROM tbl_posto_fabrica
			    		WHERE fabrica = $login_fabrica
			    		AND codigo_posto = '$codigo_posto'";

			    $res = pg_query($con, $sql);

			    if (pg_num_rows($res) == 0) {
			    	throw new Exception("Posto não encontrado");
			    }

			    $posto = pg_fetch_result($res, 0, 'posto');

			    $cond[] = 'tbl_os.posto = ' . $posto;

			}

		} catch( Exception $e) {

			$msg_erro = $e->getMessage();

		}

	}

?>

<style type="text/css">
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
	/* Mensagens de erro */
	.msg_erro{
	        background-color:#FF0000;
	        font: bold 14px "Arial";
	        color:#FFFFFF;
	        text-align:center;
	}

	.formulario{
	        background-color:#D9E2EF;
	        font:11px Arial;
	        text-align:left;
	}

	.formulario > form > table {
		width:300px;
		margin:auto;
	}

	table.tabela tr td{
	        font-family: verdana;
	        font-size: 11px;
	        border-collapse: collapse;
	        border:1px solid #596d9b;
	}

	label {display:block;}

	.sucesso{
	    background-color:#008000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}
	.texto_avulso{
	    font: 14px Arial; color: rgb(89, 109, 155);
	    background-color: #d9e2ef;
	    text-align: center;
	    width:700px;
	    margin: 0 auto;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}
	.subtitulo{
	    background-color: #7092BE;
	    font:bold 11px Arial;
	    color: #FFFFFF;
	}

	#wrapper {
		width:700px;
		margin:auto;
	}

	input.input{
		width:80px;
	}

</style>

<?php include "javascript_calendario.php";?>

<?php include "../js/js_css.php"; ?>

<script type="text/javascript">
	var checkflag = "false";
	function SelecionaTodos(field) {
		if (checkflag == "false") {
			for (i = 0; i < field.length; i++) {
				field[i].checked = true;
			}
			checkflag = "true";
			return true;
		} else {
			for (i = 0; i < field.length; i++) {
				field[i].checked = false;
			}
			checkflag = "false";
			return true;
		}
	}

	function openOS(posto, dinicial, dfinal) {
		var url = "posto_os_finalizada_sem_extrato.php?posto=" + posto + "&data_inicial=" + dinicial + "&data_final=" + dfinal;
		window.open (url, "oss", "height=640,width=1040,scrollbars=1");
	}
</script>

<div id="wrapper">

	<?php if (!empty($msg_erro)) : ?>

		<div class="msg_erro"><?=$msg_erro?></div>

	<?php endif; ?>

	<?php if (!empty($log_erro)) : ?>

		<div class="msg_erro">
			<?php 
			echo 'Erros na geração de extratos:<br/><br/>';
			echo implode('<br/>', $log_erro);
			?>
		</div>

	<?php endif; ?>

	<div class="formulario">

		<div class="titulo_tabela">Parâmetros de Pesquisa</div>

		<form action="<?=$PHP_SELF?>" method="POST" name="frm">

			<table>

				<tr>

					<td>
						<label for="codigo_posto">Cod Posto</label>
						<input type="text" name="codigo_posto" id="codigo_posto" class="frm input" value="<?=$codigo_posto?>" />
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm.codigo_posto, 'codigo');">
					</td>

					<td>
						<label for="descricao_posto">Nome do Posto</label>
						<input type="text" name="descricao_posto" id="descricao_posto" class="frm" value="<?=$descricao_posto?>" style="width: 120px;" />
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm.descricao_posto, 'nome');">
					</td>

				</tr>

				<tr>

					<td>
						<label for="data_inicial">Data Inicial</label>
						<input type="text" name="data_inicial" id="data_inicial" class="frm date" value="<?=$data_inicial?>" style="width: 100px;" />
					</td>

					<td>
						<label for="data_final">Data Final</label>
						<input type="text" name="data_final" id="data_final" class="frm date" value="<?=$data_final?>" style="width: 100px;" />
					</td>

				</tr>

				<tr><td align="center" colspan="2"><input type="submit" name="enviar" id="enviar" value="Pesquisar" /></td></tr>

			</table>

		</form>

	</div>

	<div style="clear:both; overflow:hidden;">&nbsp;</div>

	<?php
		if ( isset( $_POST['enviar'] ) && empty($msg_erro) ) :

			$num_rows = NULL;

            $sql = "
                    SELECT
                    	count(tbl_os.os) as t_os,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.posto,
                        tbl_posto.nome,
                        tbl_posto.estado,
                        tbl_posto_fabrica.credenciamento,
                        tbl_tipo_posto.descricao as tipo_posto,
                        sum (tbl_os.mao_de_obra) as mao_de_obra,
                        sum (tbl_os.pecas) as pecas
                    FROM tbl_os JOIN tbl_posto USING(posto)
                    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                    JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.extrato IS NULL
                    JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = $login_fabrica
                    WHERE tbl_os.fabrica = $login_fabrica
                    AND finalizada IS NOT NULL
                    AND excluida IS NOT TRUE
                    AND data_fechamento IS NOT NULL
                    AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
                    AND " . implode (" AND ", $cond) . "
                    GROUP BY tbl_posto_fabrica.codigo_posto,
                    tbl_posto.posto,
                    tbl_posto.nome,
                    tbl_posto.estado,
                    tbl_posto_fabrica.credenciamento,
                    tbl_tipo_posto.descricao
                    HAVING (sum(tbl_os.mao_de_obra) + sum (tbl_os.pecas)) < 70
                    ORDER BY tbl_posto.nome
            ";

            $res = pg_query($con, $sql);
            $num_rows = pg_num_rows($res);

			if ( $num_rows ) {

	?>
				<form name="frm_extrato" method="post">
				<table class="tabela" border="0" cellpadding="2" cellspacing="1" width="700">

					<thead>
						<tr class="titulo_coluna">
							<th>
								<input type='checkbox' class='frm' name='marcar' id='item' value='tudo' title='Selecione ou desmarque todos' onClick='SelecionaTodos(this.form.item);'>
							</th>
							<th>Código</th>
							<th>Posto</th>							
							<th>UF</th>
							<th>Credenciamento</th>
							<th>Tipo</th>				
							<th>Qtde OS</th>
							<th>Peças</th>
							<th>Mão-de-obra</th>
							<th>Total</th>
						</tr>
					</thead>
					<tbody>

						<?php
							$total = 0;
							for ($i = 0; $i < $num_rows; $i++) :

								$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

                                $posto       = pg_fetch_result($res, $i, 'posto');
								$mao_de_obra = pg_fetch_result($res,$i,'mao_de_obra');
								$pecas       = pg_fetch_result($res, $i, 'pecas');
								$total_os    = $mao_de_obra + $pecas;
								$total      += $total_os;
						?>

							<tr bgcolor="<?=$cor?>">
								<td>
									<input type="checkbox" name="item_<?php echo $i ?>" id="item" value="checked" />
									<input type="hidden" name="posto_<?php echo $i ?>" value="<?php echo $posto ?>" />
								</td>
								<td align="left"> <?=pg_result($res,$i,'codigo_posto')?></td>
								<td align="left"> <?=pg_result($res,$i,'nome')?></td>
								<td align="left"> <?=pg_result($res,$i,'estado')?></td>
								<td align="left"> <?=pg_result($res,$i,'credenciamento')?></td>
								<td align="left"> <?=pg_result($res,$i,'tipo_posto')?></td>
								<td align="center" style="cursor: pointer;" onClick="openOS('<?php echo $posto ?>', '<?php echo $aux_data_inicial ?>', '<?php echo $aux_data_final ?>')">
									<?=pg_fetch_result($res, $i, 't_os')?>
								</td>
								<td align="right"><?=number_format($pecas, 2, ',', '.')?></td>
								<td align="right"><?=number_format($mao_de_obra, 2, ',', '.')?></td>
								<td align="right"><?=number_format($total_os, 2, ',', '.')?></td>
							</tr>

						<?php endfor; ?>

						<tr class="titulo_coluna" >
                            <td colspan="9" align="right">TOTAL</td>
                            <td><?=number_format($total, 2, ',', '.')?></td>
                        </tr>

					</tbody>
				</table><br/>

				<div style="text-align: center">
					<input type="hidden" name="posto_total" value="<?php echo $num_rows ?>">
					<input type="hidden" name="data_inicial" value="<?php echo $aux_data_inicial ?>">
					<input type="hidden" name="data_final" value="<?php echo $aux_data_final ?>">
					<input type="submit" name="gravar" value="Gerar Extratos">
				</div>


				</form>

		<?php } else echo 'Nenhum resultado encontrado para esta pesquisa'; ?>

	<?php endif; ?>

	<?php if (!empty($log_sucesso)): ?>
		<strong>Extratos gerados com sucesso:</strong><br/><br/>

		<table class="tabela" border="0" cellpadding="2" cellspacing="1" width="700">
			<thead>
				<tr class="titulo_coluna">
					<th>Código</th>
					<th>Posto</th>
					<th>Protoclo</th>
					<th>Data</th>
					<th>Peças</th>
					<th>Mão-de-obra</th>
					<th>Avulso</th>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php echo implode('', $log_sucesso) ?>
			</tbody>
		</table>
	<?php endif; ?>

</div>

<script type="text/javascript">

	function pesquisaPosto(campo,tipo){
        var campo = campo.value;

        if (jQuery.trim(campo).length > 2){
                Shadowbox.open({
                        content:        "posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
                        player: "iframe",
                        title:          "Pesquisa Posto",
                        width:  800,
                        height: 500
                });
        }else
                alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }

    function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados('codigo_posto',codigo_posto);
        gravaDados('descricao_posto',nome);
    }

    function gravaDados(name, valor){
        try{
                $("input[name="+name+"]").val(valor);
        } catch(err){
                return false;
        }
    }

	$(function() {

		Shadowbox.init();

		$(".date").datepick();
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");

	});

</script>

<?php include 'rodape.php'; ?>
