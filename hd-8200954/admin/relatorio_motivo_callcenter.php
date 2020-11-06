<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="call_center";
	include 'autentica_admin.php';
	include 'funcoes.php';

	if(isset($_GET["action"])) {
		$action = $_GET["action"];

		switch ($action) {
			case 'busca_familia':
				$linha 		  = explode("+", $_GET["linha"]);
				$linha 		  = (int) $linha[0];
				$post_familia = !empty($_GET["familia"]) ? explode("+", $_GET["familia"]) : $_GET["familia"];
				$post_familia = trim(is_array($post_familia) ? $post_familia[0] : $post_familia);

				$sql = "SELECT tbl_diagnostico.familia, tbl_familia.descricao 
						FROM tbl_diagnostico
						JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia
										AND tbl_familia.fabrica = tbl_diagnostico.fabrica
						WHERE tbl_diagnostico.fabrica = $login_fabrica 
						AND   tbl_diagnostico.linha   = $linha
						GROUP BY 1, 2
						ORDER BY tbl_familia.descricao";

				$res = pg_query($con, $sql);

				print "<option value=''>ESCOLHA</option>\n";

				while($familia = pg_fetch_object($res)):
					$selected = !empty($post_familia) ? ($post_familia == $familia->familia ? "SELECTED" : "") : "";

					print "<option value='$familia->familia+$familia->descricao' $selected>" . utf8_encode($familia->descricao) . "</option>\n";
				endwhile;
			break;

			case 'busca_produto':
				$linha   	  = explode("+", $_GET["linha"]);
				$linha   	  = (int) $linha[0];
				$familia 	  = explode("+", $_GET["familia"]);
				$familia 	  = (int) $familia[0];
				$post_produto = $_GET["produto"];

				$sql = "SELECT produto, descricao, referencia FROM tbl_produto 
						WHERE fabrica_i = $login_fabrica AND linha = $linha AND familia = $familia
						ORDER BY referencia";

				$res = pg_query($con, $sql);

				print "<option value=''>ESCOLHA</option>\n";

				while($produto = pg_fetch_object($res)):
					$selected = !empty($post_produto) ? ($post_produto == $produto->produto ? "SELECTED" : "") : "";
					print "<option value='$produto->produto' $selected>" . utf8_encode("$produto->referencia - $produto->descricao") . "</option>\n";
				endwhile;
			break;
		}

		exit;
	}	

	$btn_acao = $_POST['btn_acao'];

	if( $btn_acao == "pesquisar" )
	{
		$post_familia = !empty($_REQUEST["familia"]) ? explode("+", $_REQUEST["familia"]) : $_REQUEST["familia"];
		$post_familia = is_array($post_familia) ? $post_familia[0] : $post_familia;
		$post_produto = $_REQUEST["produto"];
		$data_inicial = $_REQUEST['data_inicial'];
		$data_final   = $_REQUEST['data_final'];
		$codigo_posto = $_REQUEST['codigo_posto'];
		$posto_nome   = $_REQUEST['posto_nome'];
		$estado       = $_REQUEST['estado'];
		$motivo       = $_REQUEST['hd_motivo_ligacao'];
		$status       = $_REQUEST['status'];
		$produto_referencia = $_REQUEST['produto_referencia'];
		$produto_descricao  = $_REQUEST['produto_descricao'];

		
		$filtro_utilizado = "<b>Data inicial:</b> $data_inicial - <b>Data final:</b> $data_final <br>";

		/* HD 961085 - Lenoxx, adicionar combo de Linha e Família */
		if( $login_fabrica == 11 or $login_fabrica == 172)
		{
			$linha_       = explode('+', $_REQUEST['linha']); /* trazendo o id + a descrição */
			$linha        = $linha_[0];   /* id da linha */
			$nome_linha   = $linha_[1];  /* nome da linha */

			$familia_     = explode('+', $_REQUEST['familia']);
			$familia      = $familia_[0];  /* id da familia */
			$nome_familia = $familia_[1]; /* nome da familia */
		}
		/* fim - HD 961085 - Lenoxx, adicionar combo de Linha e Família */

		if(empty($data_inicial) OR empty($data_final)){
			$msg_erro = "Data Inválida";
		}

		if(empty($msg_erro)){
        	list($di, $mi, $yi) = explode("/", $data_inicial);
        	if(!checkdate($mi,$di,$yi)) 
            	$msg_erro = "Data inicial inválida";
		}
		
		if(empty($msg_erro)){
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf)) 
				$msg_erro = "Data final inválida";
		}

		if(empty($msg_erro)){
			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final   = "$yf-$mf-$df";
		  
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
				$msg_erro = "Data inicial maior do que a data final";
			}

			if(strtotime($aux_data_final) > strtotime('today')){
				$msg_erro = "Data final maior do que data atual";
			}
		}

		if(empty($msg_erro)){
			if (strtotime($aux_data_inicial.'+1 year') < strtotime($aux_data_final) ) {
				$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 ano';
			}
		}
		
		if(empty($msg_erro)){
			$cond = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";

			$url = "relatorio_motivo_callcenter_detalhe.php?data_inicial=$aux_data_inicial&data_final=$aux_data_final";
		}

		if(empty($msg_erro) AND !empty($codigo_posto)){
			$sql = "SELECT posto, nome_fantasia FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			if( pg_num_rows($res) > 0 ){
				$posto             = pg_result($res, 0,'posto');
				$nome_fantasia     = pg_result($res, 0,'nome_fantasia');
				$cond             .= " AND tbl_hd_chamado_extra.posto = $posto ";
				$url              .= "&posto=$posto";
				$filtro_utilizado .= "<b>Posto: </b> $nome_fantasia <br>";
			}else{
				$msg_erro          = "Posto não encontrado";
			}
		}

		if(empty($msg_erro)){
			if(!empty($estado)){
				$cond .= " AND tbl_cidade.estado = '$estado' ";
				$join .= " JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade";
				$url  .= "&estado=$estado";
			}

			if(!empty($motivo)){
				$cond .= " AND tbl_hd_chamado_extra.hd_motivo_ligacao = $motivo ";
				$url  .= "&motivo=$motivo";
			}

			if(!empty($status)){
				$cond .= " AND tbl_hd_chamado.status = '$status' ";
				$url  .= "&status=$status";
			}

			/* HD 961085 - Lenoxx, adicionado mais 2 filtros, linha e família */
			if( $login_fabrica == 11 or $login_fabrica == 172)
			{
				if( !empty($linha) or !empty($familia) ){
					$join .= " LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto ";
				}

				if( !empty($linha) ){
					$cond             .= " AND tbl_produto.linha = '$linha' ";
					$url              .= "&linha=$linha";
					$filtro_utilizado .= "<br><b>Linha:</b> $nome_linha ";
				}

				if( !empty($familia) ){
					$cond             .= " AND tbl_produto.familia = '$familia' ";
					$url              .= "&familia=$familia";
					$filtro_utilizado .= " <b>Família:</b> $nome_familia ";
				}
			}
			/* fim - HD 961085 - Lenoxx, adicionado mais 2 filtros, linha e família */
		}
	
		if(strlen($produto_referencia)>0){
			$sql = "SELECT produto from tbl_produto JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND fabrica = {$login_fabrica} where referencia='$produto_referencia' limit 1";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$produto = pg_result($res,0,0);
				$cond_1 = " AND  tbl_hd_chamado_extra.produto = $produto ";
				$url .= "&produto=$produto";
			}
		} 

		if(strlen($post_produto)):
			$produto = $post_produto;
			$cond_1 = " AND  tbl_hd_chamado_extra.produto = $produto ";
			$url .= "&produto=$produto";
		endif;
	}

	$title       = "Relatório de Motivos Call Center";
	$layout_menu = 'callcenter';
	include 'cabecalho.php';
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

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.col_left{
	padding-left: 100px;
}
</style>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<link rel="stylesheet" type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" media="all">

<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript" src="js/highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<script type="text/javascript">
	$().ready(function()
	{
		Shadowbox.init();

		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
	
	//PESQUISA POSTO - 
	function pesquisaPosto(campo,tipo){
		var campo = campo.value;

		if( jQuery.trim(campo).length > 2 ){
			Shadowbox.open({
				content : "posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player  : "iframe",
				title   : "Pesquisa Posto",
				width   : 800,
				height  : 500
			});
		}else
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}
		
	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
		gravaDados('codigo_posto',codigo_posto);
		gravaDados('posto_nome',nome);
	}

	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

	function detalheMotivo(motivo){
		janela = window.open('<?php echo $url;?>&motivo_aux='+motivo,"Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
		janela.focus();
	}
</script>

<?php if( !empty($msg_erro) ){ ?>
	<table align="center" width="700" class="msg_erro">
		<tr><td><?php echo $msg_erro; ?></td></tr>
	</table>
<?php } ?>

<? include "javascript_pesquisas.php" ?>
<form name="frm_consulta" method="post">
	<table align="center" class="formulario" width="700" border="0">

		<caption class="titulo_tabela">Parâmetros de Pesquisa</caption>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td class="col_left">
				Data Inicial <br>
				<input type="text" name="data_inicial" id="data_inicial" size="13" value="<?php echo $data_inicial;?>" class="frm">
			</td>

			<td colspan="2">
				Data Final <br>
				<input type="text" name="data_final" id="data_final" size="13" value="<?php echo $data_final;?>" class="frm">
			</td>
		</tr>
		
		<tr><td colspan="3">&nbsp;</td></tr>

		<!-- HD 961085 - Lenoxx, adicionar combo de Linha e Família -->
		<tr>
			<td class="col_left">Linha</td>
			<td >Família</td>
			<?if($login_fabrica == 11 or $login_fabrica == 172):?>
				<td>
					Produto
				</td>
			<?endif;?>
		</tr>
		<tr>
			<td class="col_left">
			<?php
				$sql = "SELECT * 
						FROM    tbl_linha
						WHERE   tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_linha.nome;";
				$res = pg_query($con, $sql);

				if( pg_num_rows($res) > 0 )
				{
					echo "<select name='linha' class='frm' style='width:120px;'>\n";
					echo "<option value=''>ESCOLHA</option>\n";

					for( $x = 0 ; $x < pg_num_rows($res); $x++ )
					{
						$aux_linha = trim(pg_fetch_result($res, $x, 'linha'));
						$aux_nome  = trim(pg_fetch_result($res, $x, 'nome'));

						echo "<option value='$aux_linha+$aux_nome'";
						if( $linha == $aux_linha )
						{
							echo " SELECTED ";
							$mostraMsgLinha = "<br /> da LINHA $aux_nome";
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n&nbsp;";
				}
			?>
			</td>
			<td>
			<?php
				$sql = "SELECT * 
						FROM  tbl_familia
						WHERE tbl_familia.fabrica = $login_fabrica
						ORDER BY tbl_familia.descricao;";
				$res = pg_query($con, $sql);

				if( pg_num_rows($res) > 0 )
				{
					echo "<select name='familia' class='frm' style='width:120px;'>\n";
					echo "<option value=''>ESCOLHA</option>\n";
					
					if($login_fabrica != 11 and $login_fabrica != 172):
						for( $x = 0; $x < pg_num_rows($res); $x++ )
						{
							$aux_familia   = trim(pg_fetch_result($res, $x, 'familia'));
							$aux_descricao = trim(pg_fetch_result($res, $x, 'descricao'));

							echo "<option value='$aux_familia+$aux_descricao'";
							if( $familia == $aux_familia )
							{
								echo " SELECTED ";
								$mostraMsgLinha = "<br /> da FAMÍLIA $aux_descricao";
							}
							echo ">$aux_descricao</option>\n";
						}
					endif;
					echo "</select>\n&nbsp;";
				}
			?>
			</td>
			<?if($login_fabrica == 11 or $login_fabrica == 172):?>
				<td>
					<select name="produto" id="produto" class='frm' style="width: 120px">
						<option value=''>ESCOLHA</option>
					</select>
				</td>
			<?endif;?>
		</tr>
		<!-- fim - HD 961085 - Lenoxx, adicionar combo de Linha e Família -->

		<tr><td colspan="3">&nbsp;</td></tr>

		<tr>
			<td class="col_left">
				Código Posto <br>
				<input type="text" name="codigo_posto" id="codigo_posto" size="13" value="<?php echo $codigo_posto;?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_consulta.codigo_posto, 'codigo')">
			</td>

			<td colspan="3">
				Nome Posto <br>
				<input type="text" name="posto_nome" id="posto_nome" size="35" value="<?php echo $posto_nome;?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_consulta.posto_nome, 'nome')">
			</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
			<? if($login_fabrica != 11 and $login_fabrica != 172): ?>
			<td class="col_left">
			Ref. Produto<br/>
			<input type="text" name="produto_referencia" size="12" class='frm' maxlength="20" value="<? echo $produto_referencia ?>" > 
			<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia')">
			</td>
			<td colspan='3'>Descrição<br/>
				<input type="text" name="produto_descricao" size="30" class='frm' value="<? echo $produto_descricao ?>" >
		<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao')">
			</td>
			<?endif;?>
	</tr>

		<tr><td colspan="3">&nbsp;</td></tr>

		<tr>
			<td class="col_left">
				Estado <br>
				<?php
					  $array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
					  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
					  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
					  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
					  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
					  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
					  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
					  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
					?>
					<select name="estado" class="frm" id="estado">
						<option value=""></option>
						<?php
							foreach ($array_estado as $k => $v) {
								echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
							}
						?>
					</select>
			</td>
			
			<td>
				Motivo <br>
				<select name='hd_motivo_ligacao' id='hd_motivo_ligacao' class="frm">
				<option value=''></option><?php
				$sqlLigacao = "SELECT hd_motivo_ligacao, descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica AND ativo IS TRUE";
				$resLigacao = pg_query($con,$sqlLigacao);
					for ($i = 0; $i < pg_num_rows($resLigacao); $i++) {
						$hd_motivo_ligacao_aux = pg_result($resLigacao,$i,'hd_motivo_ligacao');
						$motivo_ligacao    = pg_result($resLigacao,$i,'descricao');
						echo " <option value='".$hd_motivo_ligacao_aux."' ".($hd_motivo_ligacao_aux == $hd_motivo_ligacao ? "selected='selected'" : '').">$motivo_ligacao</option>";
						
					}?>
				</select>
			</td>

			<td>
				Status <br>
				<select name="status" class="frm">
					<option value=""></option>
					<option value="Aberto" <?php echo ($status == "Aberto") ? "SELECTED" : "";?>>Aberto</option>
					<option value="Análise" <?php echo ($status == "Análise") ? "SELECTED" : "";?>>Análise</option>
					<option value="Cancelado" <?php echo ($status == "Cancelado") ? "SELECTED" : "";?>>Cancelado</option>
					<option value="Resolvido" <?php echo ($status == "Resolvido") ? "SELECTED" : "";?>>Resolvido</option>
				</select>
			</td>
		</tr>

		<tr><td colspan="3">&nbsp;</td></tr>

		<tr>
			<td colspan="3" align="center">
				<input type="hidden" name="btn_acao" id="btn_acao" value="">

				<input type="button" value="Pesquisar" onclick="javascript: if(document.frm_consulta.btn_acao.value ==''){document.frm_consulta.btn_acao.value='pesquisar'; document.frm_consulta.submit();} else{alert('Consulta em andamento, aguarde');}">

			</td>
		</tr>
		
		<tr><td colspan="3">&nbsp;</td></tr>

</table>
</form>
<br><br>

<?php
	if( $btn_acao == "pesquisar" AND empty($msg_erro) ){
		$sql = "SELECT  COUNT(tbl_hd_chamado) AS qtde,
						tbl_hd_motivo_ligacao.descricao AS motivo_ligacao,
						tbl_hd_motivo_ligacao.hd_motivo_ligacao
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
					JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
					$join
					WHERE tbl_hd_chamado.fabrica = $login_fabrica
					$cond
					$cond_1
					GROUP BY tbl_hd_motivo_ligacao.descricao, tbl_hd_motivo_ligacao.hd_motivo_ligacao;";
		$res = pg_query($con,$sql);
		
		#echo nl2br($sql);
		$total = pg_num_rows($res);

		if( $total > 0 ){
			$registros = pg_fetch_all($res);

			foreach( $registros as $registro ){
				$total_registros += $registro['qtde'];
			}
		?>
			<table align='center' width='700' class='tabela'>
				<caption class='titulo_tabela'>Relatório de Motivos Call Center</caption>
				<tr class='titulo_coluna'>
					<th>Motivo</th>
					<th>Qtde</th>
					<th>Porcentagem(%)</th>
				</tr>
		<?php
			for( $i = 0; $i < $total; $i++ ){
				$motivo_ligacao     = pg_result($res, $i, 'motivo_ligacao');
				$hd_motivo_ligacao  = pg_result($res, $i, 'hd_motivo_ligacao');
				$qtde               = pg_result($res, $i, 'qtde');
				$porcentagem        = ($qtde * 100) / $total_registros;
				$total_porcentagem += $porcentagem;

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$porcentagem        = number_format($porcentagem, 2, ',', '.');
				$grafico           .= "['$motivo_ligacao - $porcentagem%', $porcentagem], ";
		?>
				<tr bgcolor='<?php echo $cor; ?>'>
					<td align='left'><a href='javascript: void(0);' onclick="detalheMotivo('<?php echo $hd_motivo_ligacao;?>');"><?php echo $motivo_ligacao;?></a></td>
					<td width="50"><?php echo $qtde; ?></td>
					<td width="50"><?php echo $porcentagem; ?></td>
				</tr>
		<?php
			}
		?>
				<tr class="titulo_coluna">
					<td>Total</td>
					<td><?php echo $total_registros; ?></td>
					<td><?php echo number_format($total_porcentagem, 2, ',', '.'); ?></td>
				</tr>
			</table>

			<br /><br />
			<div id="container" style="width: 1000px; height: 460px; margin: 0 auto;"></div>
<?php
		} else {
			echo "<center>Nenhum resultado encontrado</center>";
		}
	}
?>

<script type="text/javascript" charset="LATIN-1">
	var chart;
	var count = 0;

	$().ready(function()
	{
        <?php if ($login_fabrica == 11 or $login_fabrica == 172): ?>
			$("select[name=linha]").change(function() {

				if(!count) {
					familia = "<?=$post_familia?>";
				} else {
					familia = "";
				}

				$.get("<?=$PHP_SELF?>",
					  	{
						   	action: "busca_familia",
							linha: $(this, "option:selected").val(),
							familia: familia
						},
						function(response) {
							$("select[name=familia]").html(response);
							$("select[name=familia]").change();
					  	}
				);
			});

			$("select[name=familia]").change(function() {

				if(!count) {
					produto = "<?=$post_produto?>";
				} else {
					produto = "";
				}

				$.get("<?=$PHP_SELF?>",
					   	{
						   	action: "busca_produto",
							linha: $("select[name=linha] option:selected").val(),
							familia: $(this, "option:selected").val(),
							produto: produto
						},
						function(response) {
							$("select[name=produto]").html(response);
						}
				);

				count++;
			});

			if(jQuery.trim("<?=$linha?>") != '') {
				$("select[name=linha]").change();
			}
        <?php endif ?>

		chart = new Highcharts.Chart({
			chart: {
				renderTo: 'container',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				margin: [30, 0, 0, 250]
			},
			title: {
				text: 'Relatório de Motivos Call Center'
			},
			tooltip: {
				formatter: function(){
					return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
				}
			},
			plotOptions: {
				pie: {
					allowPointSelect: true,
					cursor: 'pointer',
					dataLabels: {
						enabled: true
					},
					showInLegend: true
				}
			},
			legend: {
				layout: 'vertical',
				align: 'left',
				x: 0,
				verticalAlign: 'top',
				y: 0,
				floating: false,
				backgroundColor: '#FFFFFF',
				borderColor: '#CCC',
				borderWidth: 1,
				shadow: false
			},
			series: [{
				type: 'pie',
				name: 'Relatório de Motivos Call Center',
				data: [<?php echo $grafico; ?>]
			}],
			/* HD 961085 - Lenoxx, mostrando os Filtros da pesquisa realizada */
			subtitle: {
			    text          : '<b>Filtros da pesquisa:<br> </b> <?php echo $filtro_utilizado; ?>',
			    floating      : true,
			    align         : 'center',
			    verticalAlign : 'bottom',
			    y : -30 
			}
			/* fim - HD 961085 - Lenoxx, mostrando os Filtros da pesquisa realizada */
		});
	});

	<?php if( !empty($msg_erro) ){ ?>
		$("#erro").appendTo("#msg").fadeIn("slow");
	<?php } ?>	
</script>

<?php include 'rodape.php'; ?>
