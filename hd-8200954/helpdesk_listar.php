<?php
/**
 * P�gina de listagem de chamado de HelpDesk para os postos autorizados
 *
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "helpdesk.inc.php";

if (in_array($login_fabrica, array(160))) {
	header("Location: helpdesk_posto_autorizado_listar.php");
	exit;	
}

if(isset($_POST['tipo_solicitacao'])){
	$tipo_solicitacao = $_POST['tipo_solicitacao'];

	$no_fabrica = 0;

	foreach ($categorias as $key => $value) {
		if($key == $tipo_solicitacao){
			$fabricas = $value["no_fabrica"];
			foreach ($fabricas as $key1 => $valueFabrica) {
				if($valueFabrica == $login_fabrica){
					$no_fabrica++;
				}
			}
		}
	}

	if($no_fabrica > 0){

		$msg_no_fabrica = "<p align='center' style='color: red; font: 16px arial;'>Essa op��o n�o pertence a Fabrica! Por favor escolha novamente a op��o!</p>";

	}

}

if(strlen($msg_no_fabrica) == 0){
	if ( in_array($login_fabrica, array(11,42,172)) ) {
		$aChamados = hdBuscarChamados(array("tbl_hd_chamado.status <> 'Interno'", "tbl_posto.posto = {$login_posto}"));
	} else {
		if($login_fabrica == 1){
			$aChamadosSAC = hdBuscarChamadosSAC(array("tbl_posto.posto = {$login_posto}", "tbl_hd_chamado.categoria = 'servico_atendimeto_sac'"));
			$aChamadosSAP = hdBuscarChamadosSAP(array("tbl_posto.posto = {$login_posto}", "tbl_hd_chamado.categoria != 'servico_atendimeto_sac'"));
		}else{
			$aChamados = hdBuscarChamados(array("tbl_posto.posto = {$login_posto}"));
		}
	}
}

$title = 'Listagem de Chamados para F�brica';
include 'cabecalho.php';
?>
<style>
table {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
}
table .titulo { text-align: center; font-weight: bold; color: #FFFFFF; background-color: #596D9B; background-image: url(admin/imagens_admin/azul.gif); }
table .conteudo { font-weight: normal; }

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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.col_esquerda{
	padding-left:80px;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>
<link href="plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" type="text/css">
<link href="plugins/shadowbox/shadowbox.css" rel="stylesheet" type="text/css">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src="plugins/shadowbox/shadowbox.js"></script>
<script language="JavaScript">

	$(document).ready(function() {
		Shadowbox.init();
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

	});

	function historicoChamado(hd_chamado){
		window.open("hd_chamado_historico.php?hd_chamado="+hd_chamado,"Hist�rio Chamdos", "status=no, width=800, height=600");
	}

	function retorna_dados_produto (produto, linha, descricao, nome_comercial, voltagem, referencia, referencia_fabrica, garantia, mobra, ativo, off_line, capacidade, valor_troca, troca_garantia, troca_faturada, referencia_antiga, troca_obrigatoria, posicao) {
		gravaDados("produto_referencia", referencia);
		gravaDados("produto_descricao", descricao);
	}
</script>

<?php
include "javascript_pesquisas_novo.php";
?>

<?php

if($login_fabrica == 1){

	$sql_chamados_sac_48horas = "SELECT hd_chamado, protocolo_cliente, data, current_date - date(data) AS dias FROM tbl_hd_chamado WHERE categoria = 'servico_atendimeto_sac' AND fabrica = {$login_fabrica} AND posto = {$login_posto} AND status = 'Ag. Posto'";
	$res_chamados_sac_48horas = pg_query($con, $sql_chamados_sac_48horas);

	$chamados_48horas = 0;

	if(pg_num_rows($res_chamados_sac_48horas) > 0){
		for ($i = 0; $i < pg_num_rows($res_chamados_sac_48horas); $i++) {
			$hd_chamado = pg_fetch_result($res_chamados_sac_48horas, $i, "hd_chamado");
			$protocolo_cliente = pg_fetch_result($res_chamados_sac_48horas, $i, "protocolo_cliente");
			$data = pg_fetch_result($res_chamados_sac_48horas, $i, "data");
			$dias = pg_fetch_result($res_chamados_sac_48horas, $i, "dias");
			if($dias >= 2){
				$chamados_sac[] = (strlen($protocolo_cliente) > 0) ? $protocolo_cliente : $hd_chamado;
				$chamados_48horas++;
			}
		}
	}

    $chamadosComAvaliacaoPendente =
    hdBuscarChamados(array(
        " tbl_posto.posto = {$login_posto} ",
        ' (tbl_hd_chamado_extra.array_campos_adicionais IS NULL OR NOT(tbl_hd_chamado_extra.array_campos_adicionais ~E\'"avaliacao_pontuacao":"?[0-9]{1,2}"?\')) ',
        ' (SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.status_item <> \'\' ORDER BY data DESC LIMIT 1) not in (\'Em Acomp. Encerra\',\'Em Acomp. Pendente\', \'Em Acomp.\', \'Aberto\', \'Ag. Posto\') ',
		" tbl_hd_chamado.status IN ('Ag. Posto', 'Resolvido') ",
		" tbl_hd_chamado.categoria <> 'servico_atendimeto_sac'",
		" tbl_hd_chamado.data >= '2018-04-02 00:00:00'"
	), null, true);
	
    if(!empty($chamadosComAvaliacaoPendente)){
?>
    <div style="width:80%; margin: 5px; padding:1px;background-color:#FFDE59;">
        <p style="font-size: 12px; color: #000; font-weight: bold;">
            Existem chamados pendentes de Avalia��o.
        </p>
        <p style="font-size: 12px; color: #000; font-weight: bold;">
            N�o ser� poss�vel abrir novos chamados enquanto as avalia��es n�o forem realizadas.
        </p>
    </div>
    <div>
        <table width="80%">
            <thead>
                <tr>
                    <th class="titulo" colspan="10" style="text-align:center;" >
                        Chamados com Avalia��o Pendente
                    </th>
                </tr>
                <tr class="titulo">
			        <th> Chamado </th>
			        <th> Abertura </th>
			        <th> Fechamento </th>
			        <th> Hist�rico </th>
			        <th>Tempo Atendimento Parcial</th>
			        <th>Tempo Atendimento Total</th>
			        <th> Tipo Solicita��o </th>
			        <th> Atendente </th>
			        <th> Status </th>
			        <th> &nbsp; </th>
    			</tr>
            </thead>
            <tbody>
                <?php
                    $hdChamadoAvPendente = array();
                    foreach($chamadosComAvaliacaoPendente as $chamado) {
                        $corZebrado = $corZebrado == '#F1F4FA'?'#91C8FF':'#F1F4FA';
                        $tempoChamado = traduzTempoAtendimento($chamado);
                        $hdChamadoAvPendente[] = $chamado['hd_chamado'];
                ?>
                <tr class="conteudo" align="center" bgcolor="<?php echo $corZebrado; ?>">
                    <td>
                        <?php echo $chamado['hd_chamado']; ?>
                    </td>
                    <td>
                        <?php echo $chamado['data']; ?>
                    </td>
                    <td>
                        <?php echo $chamado['data_resolvido']; ?>
                    </td>
                    <td nowrap>
                        <img src="admin/imagens_admin/status_azul.gif" />
                        <a href="javascript: void(0);" onclick="historicoChamado('<?php echo $chamado['hd_chamado'] ?>')">Hist�rico</a>
                    </td>
                    <td>
                        <?php echo $tempoChamado['parcial']; ?>
                    </td>
                    <td>
                        <?php echo $tempoChamado['total']; ?>
                    </td>
                    <td>
                        <?php echo ucfirst(traduzCategoria($chamado['categoria'])); ?>
                    </td>
                    <td>
                        <?php echo $chamado['atendente_ultimo_login']; ?>
                    </td>
                    <td>
                        <img src="admin/imagens_admin/status_laranja.png" />
                    </td>
                    <td>

                        <a href="helpdesk_cadastrar.php?hd_chamado=<?php echo $chamado['hd_chamado'] ?>#avaliacao" target="_blank">
                            <!--<img src="imagens/btn_consulta.gif" alt="Consultar Chamado" />-->
                            <button>Avaliar</button>
                        </a>
                    </td>
                </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
    </div>
<?php
    }
}
?>

<p> &nbsp; </p>
<div align="center">

<?php if(strlen(trim($msg_erro))>0) {?>
	<table width="700" align="center" class="msg_erro">
		<tr><td><?php echo $msg_erro; ?></td></tr>
	</table>
<?php } ?>
	<table width="700" align="center" >
		<tr><td><?php echo $msg_no_fabrica; ?></td></tr>
	</table>

	<form name="frm_consulta" method="post">
		<table width="700" align="center" class="formulario">
			<caption class="titulo_tabela">Par�metros de Pesquisa</caption>
			<tr><td>&nbsp;</td></tr>
			<tr>
				<td class="col_esquerda">
					N� do Chamado <br>
					<input type="text" size="8" name="hd_chamado" class="frm">
				</td>
			</tr>

			<tr><td>&nbsp;</td></tr>
			<tr>
				<td class="col_esquerda">
					Data Inicial <br>
					<input type="text" size="12" name="data_inicial" id="data_inicial" class="frm">
				</td>

				<td>
					Data Final <br>
					<input type="text" size="12" name="data_final" id="data_final" class="frm">
				</td>
			</tr>

			<tr><td>&nbsp;</td></tr>
			<tr>
				<td class="col_esquerda">
					Status <br>
					<select name="status" class="frm">
						<option value=""></option>
						<?php
							$sql = "SELECT DISTINCT status
										FROM tbl_hd_chamado
										WHERE fabrica_responsavel = $login_fabrica
										".(( in_array($login_fabrica, array(11,42,172)) ) ? "AND status <> 'Interno'" : "")."
										ORDER BY status";
							$res = pg_query($con,$sql);

							if(pg_num_rows($res) > 0){
								for($i = 0; $i < pg_num_rows($res); $i++){
									$staus_desc = pg_result($res,$i,'status');
									$staus_value = pg_result($res,$i,'status');

									switch($staus_desc) {
										case ('Ag. Posto') :   $staus_desc    = "Aguardando Posto"; break;
										case ('Ag. F�brica') : $staus_desc ="Aguardando F�brica"; break;
										case ('Em Acomp.') : $staus_desc ="Em Acompanhamento"; break;
										case ('Resp.Conslusiva') : $staus_desc ="Resposta Conclusiva"; break;
									}
									echo "<option value='$staus_value'>$staus_desc</option>";
								}
							}
						?>
					</select>
				</td>

				<td>
					Tipo de solicita��o <br>
					<select name="tipo_solicitacao" class="frm">
						<option value=''></option>  <?
						
						/*HD-6065678*/
						if ($login_fabrica == 1) {
							$categorias["nova_duvida_pecas"]["descricao"]   = "D�vida sobre pe�as";
							$categorias["nova_duvida_pedido"]["descricao"]  = "D�vidas sobre Pedido";
							$categorias["nova_duvida_produto"]["descricao"] = "D�vidas sobre produtos";
							$categorias["nova_erro_fecha_os"]["descricao"]  = "Problemas no fechamento da O.S.";
							$categorias["advertencia"]["descricao"] = "Advert�ncia";
						}
						foreach ($categorias as $categoria => $config) {
							if ($config['no_fabrica']) {
								if (in_array($login_fabrica, $config['no_fabrica'])) {
									continue;
								}
							}

							echo CreateHTMLOption($categoria, $config['descricao'], $_POST['categoria']);
						} ?>
					</select>
				</td>
			</tr>
			<?php
			if ($login_fabrica == 3) {
			?>
				<tr>
					<td class="col_esquerda" nowrap>
						Produto Refer�ncia<br />
						<input type="text" name="produto_referencia" id="produto_referencia" value="<?=$produto_referencia?>" />
						<img src="imagens/lupa.png" onclick="fnc_pesquisa_produto('', document.getElementById('produto_referencia'));" />
					</td>
					<td nowrap>
						Produto Descri��o<br />
						<input type="text" name="produto_descricao" id="produto_descricao" value="<?=$produto_descricao?>" />
						<img src="imagens/lupa.png" onclick="fnc_pesquisa_produto(document.getElementById('produto_descricao'), '');" />
					</td>
				</tr>
				<tr>
					<td class="col_esquerda">
						OS<br />
						<input type="text" name="os" value="<?=$os?>" />
					</td>
					<td>
						N�mero de S�rie<br />
						<input type="text" name="numero_serie" value="<?=$numero_serie?>" />
					</td>
				</tr>
			<?php
			}
			?>

			<tr><td>&nbsp;</td></tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" value="Pesquisar">
				</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
		</table>
	</form>

	<br>
	<table width="700" align="center" style="font-size:10px;">
		<tr style="font-size:12px;">
			<?php
			if ($login_fabrica <> 3) {
			?>
				<td><b>Status Hist�rico</b></td>
			<?php
			}
			?>
			<td><b>Status Chamado</b></td>
		</tr>
		<tr>
			<?php
			if ($login_fabrica <> 3) {
			?>
				<td>
					<img src='admin/imagens_admin/status_amarelo.gif'>&nbsp;EM ACOMPANHAMENTO
				</td>
			<?php
			}
			?>
			<td>
				<img src='admin/imagens_admin/status_verde.gif'>&nbsp;RESOLVIDO
			</td>
		</td>
		<tr>
			<?php
			if ($login_fabrica <> 3) {
			?>
				<td>
					<img src='admin/imagens_admin/status_vermelho.gif'>&nbsp;EM ACOMPANHAMENTO COM MAIS DE 120 HORAS SEM INTERA��O
				</td>
			<?php
			}
			?>
			<td>
				<img src='admin/imagens_admin/status_vermelho.gif'>&nbsp;CANCELADO
			</td>
		</td>
		<tr>
			<?php
			if ($login_fabrica <> 3) {
			?>
				<td>
					<img src='admin/imagens_admin/status_azul.gif'>&nbsp;RESPOSTA CONCLUSIVA
				</td>
			<?php
			}
			?>
			<td>
				<img src='admin/imagens_admin/status_preto.gif'>&nbsp;AGUARDANDO F�BRICA
			</td>
		</tr>

		<tr>
			<?php
			if ($login_fabrica <> 3) {
			?>
				<td>
					&nbsp;
				</td>
			<?php
			}
			?>
			<td>
				<img src='admin/imagens_admin/status_laranja.png'>&nbsp;AGUARDANDO POSTO
			</td>
		</tr>
	</table>

	<table width="80%" >
		<thead>
			<tr>
				<td colspan="10" style="text-align: center;">
                <?php
                    if($login_fabrica != 1 || (count($chamadosComAvaliacaoPendente) == 0 && $chamados_48horas == 0)){
                ?>
    			        <input type="button" value="Cadastrar Novo Chamado" onclick="javascript:window.open('helpdesk_cadastrar.php')">
                        <br /> <br />
                <?php
                    }
                    if($chamados_48horas > 0 && $login_fabrica == 1){
                    	$s = (count($chamados_sac) > 1) ? "s" : "";
                    	$chamados_sac = (count($chamados_sac) > 1) ? implode(",", $chamados_sac) : $chamados_sac[0];
                    	?>
                    		<p style="color: red; font-size: 16px; font-weight: bold;">Para abrir um novo atendimento, dever� responder o<?=$s?> atendimento<?=$s?> <?=$chamados_sac?></p>
                    		<br />
                    	<?php
                    }
                ?>
				</td>
			</tr>
		</thead>
	</table>

	<?php
	if( $login_fabrica == 1 AND count($aChamadosSAC) > 0 ){
		/* Chamados SAC */
		?>
		<table width="80%" >
			<thead>
				<tr class="titulo">
					<th colspan="10" align="center" style="padding-top: 10px; padding-bottom: 10px;"> Chamados SAC </th>
				</tr>
				<tr class="titulo">
					<th> Chamado </th>
					<th> Abertura </th>
					<th> Fechamento </th>
					<?php
					if ($login_fabrica <> 3) {
					?>
						<th> Hist�rico </th>
					<?php
					}
					?>
					<th>Tempo Atendimento Parcial</th>
					<th>Tempo Atendimento Total</th>
					<th> Tipo Solicita��o </th>
					<th> Atendente </th>
					<th> Status </th>
					<th> &nbsp; </th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($aChamadosSAC as $i=>$linha): ?>
				<tr class="conteudo" align="center" bgcolor="<?php echo ($i%2)?'#91C8FF':'#F1F4FA'; ?>">
					<td nowrap>
						<?php

							if($login_fabrica == 1 && $linha["categoria"] == "servico_atendimeto_sac" && strlen($linha["protocolo_cliente"]) > 0){
								echo $linha["protocolo_cliente"];
							}else{
								if(!empty($linha['hd_chamado_anterior'])){
									echo hdChamadoAnterior($linha['hd_chamado'],$linha['hd_chamado_anterior']);
								}
								else if ($login_fabrica == 3 && !empty($linha["seu_hd"])){
									echo $linha['seu_hd'];
								}
								else{
									echo $linha['hd_chamado'];
								}
							}
						?>
					</td>
					<td> <?php echo $linha['data']; ?> </td>
					<?

					if(strlen($linha['status']) > 0){
						switch($linha['status']) {
							case ('Ag. Posto') :   $status    = "Aguardando Posto"; break;
							case ('Ag. F�brica') : $status ="Aguardando F�brica"; break;
							default:             $status = $linha['status'];
						}
					}

	                list($tempo_atendimento[0],$tempo_atendimento_total) = array_values(traduzTempoAtendimento($linha));
	                $categoria = traduzCategoria($linha['categoria']);

					$sqlResp = "SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = ".$linha['hd_chamado']." AND admin IS NOT NULL ORDER BY hd_chamado_item DESC LIMIT 1";
					$resResp = pg_query($con,$sqlResp);
					if(pg_num_rows($resResp) > 0){

						$resposta_tipo = pg_result($resResp,0,0);
						switch($resposta_tipo) {
							case 'Em Acomp.' :
							case 'encerrar_acomp': $resposta_tipo ="Em Acompanhamento"; break;
							case 'Resp.Conclusiva' :
							case 'Resolvido Posto':
							case 'Resolvido':
								$resposta_tipo ="Resposta Conclusiva";
							break;
						}
						list($ultima_interacao,$restante) = explode(' ',$linha['data_ultima_interacao']);

						if(strtotime($ultima_interacao.'+5 days') < strtotime('today') AND $resposta_tipo == "Em Acompanhamento"){
							$resposta_tipo = "EM ACOMPANHAMENTO5";
						}
					}

					?>
					<td> <?php echo $linha['data_resolvido']; ?> </td>
					<?php
					if ($login_fabrica <> 3) {
					?>
						<td nowrap>
							<? if(pg_num_rows($resResp) > 0){ ?>
								<img src="admin/imagens_admin/<?php echo $status_array_helpdesk[strtoupper($resposta_tipo)];?>">&nbsp;
								<a href="javascript: void(0);" onclick="historicoChamado('<?php echo $linha['hd_chamado']; ?>')">Hist�rico</a>
							<? } ?>
						</td>
					<?php
					}
					?>
					<td> <?php echo $tempo_atendimento[0]; ?></td>
					<td> <?php echo $tempo_atendimento_total; ?></td>
					<td> <?php echo ucfirst($categoria); ?> </td>
					<td> <?php echo $linha['atendente_ultimo_login']; ?> </td>
					<td>
						<img src="admin/imagens_admin/<?php echo $status_array_helpdesk[strtoupper($status)];?>">
					</td>
					<td> <a href="helpdesk_cadastrar.php?hd_chamado=<?php echo $linha['hd_chamado']; ?>"><img src="imagens/btn_consulta.gif" alt="Consultar Chamado" /></a> </td>
				</tr>
				<?
					$sql = " SELECT
								to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY HH24:MI:SS') as data,
								admin
							FROM tbl_hd_chamado_item
							WHERE hd_chamado = ".$linha['hd_chamado']."
							AND   interno IS NOT TRUE
							ORDER BY data DESC limit 1";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						if(strlen(pg_fetch_result($res,0,admin)) > 0) {
							echo "<tr class='conteudo' align='center' bgcolor='#FFFFFF'>";
							echo "<td  style = 'text-align:left;color:#000099' colspan='3' >Help Desk ".$linha['hd_chamado']." respondido em ".pg_fetch_result($res,0,data)."</td>";
							echo "</tr>";
						}
					}
				?>
				<?php endforeach; ?>
			</tbody>
		</table>

		<br /> <br /> <br />

		<?php
	}

    if ( ($login_fabrica == 1 AND count($aChamadosSAP) > 0) OR $login_fabrica <> 1 ) { ?>
	<table width="80%" >
		<thead>
			<?php
			if($login_fabrica == 1){
				?>
				<tr class="titulo">
					<th colspan="10" align="center" style="padding-top: 10px; padding-bottom: 10px;"> Chamados SAP </th>
				</tr>
				<?php
			}
			?>
			<tr class="titulo">
				<th> Chamado </th>
				<th> Abertura </th>
				<th> Fechamento </th>
				<?php
				if ($login_fabrica <> 3) {
				?>
					<th> Hist�rico </th>
				<?php
				}
				?>
				<th>Tempo Atendimento Parcial</th>
				<th>Tempo Atendimento Total</th>
				<th> Tipo Solicita��o </th>
				<th> Atendente </th>
				<th> Status </th>
				<th> &nbsp; </th>
			</tr>
		</thead>
		<tbody>
			<?php
			if($login_fabrica == 1){
				$aChamados = $aChamadosSAP;
			}
			?>
			<?php
            if(empty($msg_erro)){
             foreach ($aChamados as $i=>$linha): ?>
            
			<?php
                if (in_array($linha['hd_chamado'], $hdChamadoAvPendente)) {
                    continue;
                }
            ?>
            <tr class="conteudo" align="center" bgcolor="<?php echo ($i%2)?'#91C8FF':'#F1F4FA'; ?>">
				<td nowrap>
					<?php

						if($login_fabrica == 1 && $linha["categoria"] == "servico_atendimeto_sac" && strlen($linha["protocolo_cliente"]) > 0){
							echo $linha["protocolo_cliente"];
						}else{
							if(!empty($linha['hd_chamado_anterior'])){
								echo hdChamadoAnterior($linha['hd_chamado'],$linha['hd_chamado_anterior']);
							}
							else if ($login_fabrica == 3 && !empty($linha["seu_hd"])){
								echo $linha['seu_hd'];
							}
							else{
								echo $linha['hd_chamado'];
							}
						}
					?>
				</td>
				<td> <?php echo $linha['data']; ?> </td>
				<?
				if ($login_fabrica == 1) { ?>
					<td> <?php echo $linha['data_resolvido']; ?> </td>
				<?php
				}

				if(strlen($linha['status']) > 0){
					switch($linha['status']) {
						case ('Ag. Posto') :   $status    = "Aguardando Posto"; break;
						case ('Ag. F�brica') : $status ="Aguardando F�brica"; break;
						default:             $status = $linha['status'];
					}
				}

                list($tempo_atendimento[0],$tempo_atendimento_total) = array_values(traduzTempoAtendimento($linha));
                $categoria = traduzCategoria($linha['categoria']);
				if ($login_fabrica <> 3) {
				$sqlResp = "SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = ".$linha['hd_chamado']." AND admin IS NOT NULL ORDER BY hd_chamado_item DESC LIMIT 1";
				$resResp = pg_query($con,$sqlResp);
				if(pg_num_rows($resResp) > 0){

					$resposta_tipo = pg_result($resResp,0,0);
					switch($resposta_tipo) {
						case '' :
						case 'Em Acomp.' :
						case 'encerrar_acomp': $resposta_tipo ="Em Acompanhamento"; break;
						case 'Resp.Conclusiva' :
						case 'Resolvido Posto':
						case 'Resolvido':
							$resposta_tipo ="Resposta Conclusiva";
						break;
					}
					list($ultima_interacao,$restante) = explode(' ',$linha['data_ultima_interacao']);

					if(strtotime($ultima_interacao.'+5 days') < strtotime('today') AND $resposta_tipo == "Em Acompanhamento"){
						$resposta_tipo = "EM ACOMPANHAMENTO5";
					}
				}

				?>
					<td nowrap>
						<? if(pg_num_rows($resResp) > 0){ ?>
							<img src="admin/imagens_admin/<?php echo $status_array_helpdesk[strtoupper($resposta_tipo)];?>">&nbsp;
							<a href="javascript: void(0);" onclick="historicoChamado('<?php echo $linha['hd_chamado']; ?>')">Hist�rico</a>
						<? } ?>
					</td>
				<?php
				}
				if ($login_fabrica != 1) { ?>
					<td> <?php echo $linha['data_resolvido']; ?> </td>
				<?php 
				}
				?>
				<td> <?php echo $tempo_atendimento[0]; ?></td>
				<td> <?php echo $tempo_atendimento_total; ?></td>
				<td> <?php echo ucfirst($categoria); ?> </td>
				<td> <?php echo $linha['atendente_ultimo_login']; ?> </td>
				<td>
					<img src="admin/imagens_admin/<?php echo $status_array_helpdesk[strtoupper($status)];?>">
				</td>
				<td> <a href="helpdesk_cadastrar.php?hd_chamado=<?php echo $linha['hd_chamado']; ?>"><img src="imagens/btn_consulta.gif" alt="Consultar Chamado" /></a> </td>
			</tr>
			<?
				$sql = " SELECT
							to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY HH24:MI:SS') as data,
							admin
						FROM tbl_hd_chamado_item
						WHERE hd_chamado = ".$linha['hd_chamado']."
						AND   interno IS NOT TRUE
						ORDER BY data DESC limit 1";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					if(strlen(pg_fetch_result($res,0,admin)) > 0) {
						echo "<tr class='conteudo' align='center' bgcolor='#FFFFFF'>";
						echo "<td  style = 'text-align:left;color:#000099' colspan='3' >Help Desk ".$linha['hd_chamado']." respondido em ".pg_fetch_result($res,0,data)."</td>";
						echo "</tr>";
					}
				}
			?>
			<?php endforeach; 
            }
            ?>

        
		</tbody>
	</table>
    <?php
    }

    if ($login_fabrica == 1 AND count($aChamadosSAP) ==0 AND count($aChamadosSAC) == 0 ) {?>
        <table width="80%" >
        <thead>
            <tr class="titulo">
                <th colspan="10" align="center" style="padding-top: 10px; padding-bottom: 10px;"> Nenhum chamado aguardando posto. </th>
                </tr>
        </thead>
    </table>
    <?
    }
    ?>

</div>

<?php include 'rodape.php'; ?>
<?php

function traduzCategoria($chaveCategoria){
    global $login_fabrica;
    global $categorias;


	if(array_key_exists($chaveCategoria,$categorias))
		return ($categorias[$chaveCategoria]['descricao']);

    switch($chaveCategoria) {
	    case ('atualiza_cadastro') :           return "Atualiza��o de cadastro";
	    case ('digitacao_fechamento') :        return "Digita��o e/ou fechamento de OS's";
	    case ('utilizacao_do_site') :          return "D�vidas na utiliza��o do site";
	    case ('duvida_troca') :                return "D�vidas na troca de produto";
	    case ('duvida_produto') :              return ($login_fabrica == 42) ? 'Suporte T�cnico' : 'D�vida t�cnica sobre o produto';
	    case ('duvida_revenda') :              return "D�vidas sobre atendimento � revenda";
	    case ('falha_no_site') :               return ($login_fabrica == 42) ? 'Falha no site Telecontrol' : 'Falha no site';
	    case ('manifestacao_sac') :            return "Manifesta��o sobre o SAC";
	    case ('pendencias_de_pecas') :         return ($login_fabrica == 42) ? 'Pend�ncia de pe�as / Pedidos de pe�as' : 'Pend�ncias de pe�as com a f�brica';
	    case ('pend_pecas_dist') :             return "Pend�ncias de pe�as com o distribuidor";
	    case ('outros') :                      return "Outros";
	    case ('comunicar_procon') :            return "Comunicar PROCON ou Casos Judiciais";
	    case ('solicita_informacao_tecnica') : return "Solicita��o de Informa��o T�cnica";
	    case ('treinamento_makita') :          return "Treinamentos Makita";
        case ('sugestao_critica') :            return "Sugestao, Cr�ticas, Reclama��es ou Elogios";
        case ('nova_duvida_pecas') :           return "D�vida sobre pe�as";
        case ('nova_duvida_pedido') :          return "D�vidas sobre Pedido";
        case ('nova_duvida_produto') :         return "D�vidas sobre produtos";
        case ('nova_erro_fecha_os') :          return "Problemas no fechamento da O.S.";
	    case ('pagamento_antecipado') :        return "Pagamento Antecipado";
        default:
            return $chaveCategoria;
    }
}

function traduzTempoAtendimento($chamado){

    $tempoAtendimento = array('parcial'=>'','total'=>'');
    list($tempoAtendimento['parcial']) = explode('.',str_replace('day','dia',$chamado['tempo_atendimento']));
    if(empty($chamado['duracao'])){
        if(in_array($chamado['ultima_resposta'],array('encerrar_acomp'))){
            return $tempoAtendimento;
        }
        list($dataAbertura) = explode('.',$chamado['data_abertura']);
        $tempoParcial = strtotime('now') - strtotime($dataAbertura);
        $tempoAtendimento['parcial'] = calculaHorasAtendimento($tempoParcial);
        return $tempoAtendimento;
    }
    if(in_array($chamado['ultima_resposta'],array('Resolvido Posto','Resolvido','encerrar_acomp'))){
        $tempoAtendimento['total'] = calculaHorasAtendimento($chamado['duracao']);
        $tempoAtendimentp['parcial'] = $tempoAtendimento['total'];
        return $tempoAtendimento;
    }
    if($chamado['ultima_resposta'] != 'Resp.Conclusiva'){
        list($dataAbertura) = explode('.',$chamado['data_abertura']);
        $tempoParcial = strtotime('now') - strtotime($dataAbertura);
        $tempoParcial += $chamado['duracao'];
        $tempoAtendimento['parcial'] = calculaHorasAtendimento($tempoParcial);
    }
    return $tempoAtendimento;
}


