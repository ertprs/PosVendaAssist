<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	$layout_menu = "gerencia";
	$title = "RELATÓRIO DE OS FINALIZADAS SEM EXTRATO";
	include 'cabecalho.php';

	if ( isset ( $_POST['enviar'] ) ) {

		$data_inicial 	= $_POST["data_inicial"];
    	$data_final 	= $_POST["data_final"];
		$descricao_posto = $_POST['descricao_posto'];
    	$codigo_posto   = addslashes($_POST['codigo_posto']);

    	try {

    		if (empty($_POST['codigo_posto'])) {
    			throw new Exception("Selecione o Posto");
    		}

    		if (!empty($data_inicial) and !empty($data_final)) {
    			/* Inicio validação de datas */

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

		        if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final) && empty($msg_erro) ) {
		        	throw new Exception('O intervalo entre as datas não pode ser maior que 1 mês');
				}

			    $cond[] = "tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";

			    /* Fim validação de datas */
    		}

		    if ( empty($msg_erro) && !empty($codigo_posto) ) {

			    $sql = "SELECT posto
			    		FROM tbl_posto_fabrica
			    		WHERE fabrica = $login_fabrica
			    		AND codigo_posto = '$codigo_posto'";

			    $res = pg_query($con, $sql);

			    if (pg_num_rows($res) == 0) {
			    	throw new Exception("Posto não encontrado");
			    }

			    $cond[] = 'tbl_os.posto = ' . pg_result($res,0,0);

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
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<?php include "javascript_calendario.php";?>

<div id="wrapper">

	<?php if (!empty($msg_erro)) : ?>

		<div class="msg_erro"><?=$msg_erro?></div>

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
						<input type="text" name="descricao_posto" id="descricao_posto" class="frm" value="<?=$descricao_posto?>" />
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm.descricao_posto, 'nome');">
					</td>

				</tr>

				<tr>

					<td>
						<label for="data_inicial">Data Inicial</label>
						<input type="text" name="data_inicial" id="data_inicial" class="frm input" value="<?=$data_inicial?>" />
					</td>

					<td>
						<label for="data_final">Data Final</label>
						<input type="text" name="data_final" id="data_final" class="frm input" value="<?=$data_final?>" />
					</td>

				</tr>

				<tr><td align="center" colspan="2"><input type="submit" name="enviar" id="enviar" value="Pesquisar" /></td></tr>

			</table>

		</form>

	</div>

	<div style="clear:both; overflow:hidden;">&nbsp;</div>

	<?php
		if ( isset( $_POST['enviar'] ) && empty($msg_erro) ) :

			$sql = "SELECT
						tbl_os.os,
						tbl_posto_fabrica.codigo_posto || tbl_os.sua_os AS sua_os,
						tbl_posto.nome,
						TO_CHAR (tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
						TO_CHAR (tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
						tbl_os.pecas,
						tbl_os.mao_de_obra
					FROM tbl_os
					JOIN tbl_posto USING(posto)
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
					JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.extrato IS NULL
					WHERE tbl_os.fabrica = $login_fabrica
						AND finalizada IS NOT NULL
						AND excluida IS NOT TRUE
						AND data_fechamento IS NOT NULL
						AND " . implode (" AND ", $cond) .
					"ORDER BY data_digitacao, tbl_posto.nome";

			$res = pg_query($con, $sql);

			if ( pg_num_rows($res) ) {

	?>
				<table class="tabela" border="0" cellpadding="2" cellspacing="1" width="700">

					<thead>
						<tr class="titulo_coluna">
							<th>OS</th>
							<th>Posto</th>
							<th>Data Digitação</th>
							<th>Data de Fechamento</th>
							<th>Peças</th>
							<th>Mão-de-obra</th>
						</tr>
					</thead>
					<tbody>

						<?php
							$total = 0;
							for ($i = 0; $i < pg_num_rows($res); $i++) :

								$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
								$os = pg_result($res,$i,'os');
								$sua_os = pg_result($res,$i, 'sua_os');
								$pecas = pg_fetch_result($res, $i, 'pecas');
								$mao_de_obra    = pg_result($res,$i,'mao_de_obra');
                                $total          += $pecas + $mao_de_obra;
						?>

							<tr bgcolor="<?=$cor?>">
								<td><a href="os_press.php?os=<?=$os?>" target="_blank"><?=$sua_os?></a></td>
								<td align="left"> <?=pg_result($res,$i,'nome')?></td>
								<td><?=pg_result($res,$i,'data_digitacao')?></td>
								<td><?=pg_result($res,$i, 'data_fechamento')?></td>
								<td align="right"><?=number_format(pg_result($res,$i,'pecas'), 2, ',', '.')?></td>
								<td align="right"><?=number_format(pg_result($res,$i,'mao_de_obra'), 2, ',', '.')?></td>
							</tr>

						<?php endfor; ?>

						<tr class="titulo_coluna" >
                                                        <td colspan="5" align="center">TOTAL</td>
                                                        <td><?=number_format($total, 2, ',', '.')?></td>
                                                </tr>

					</tbody>
				</table>

		<?php } else echo 'Nenhum resultado encontrado para esta pesquisa'; ?>

	<?php endif; ?>

</div>

<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
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

		$("#data_inicial, #data_final").maskedinput("99/99/9999");
		$("#data_inicial, #data_final").datePicker({startDate : "01/01/2000"});

	});

</script>

<?php include 'rodape.php'; ?>
