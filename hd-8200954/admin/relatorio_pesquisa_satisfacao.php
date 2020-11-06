<?php
	/**
	  *	 @description Relatorio Pesquisa de Satisfação - HD 674943 e 720502
	  *  @author Brayan L. Rastelli
	  */
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "autentica_admin.php";
	$layout_menu = "callcenter";
	$title = "RELATÓRIO DE PESQUISA DE SATISFAÇÃO";
	include "cabecalho.php";
?>

<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

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
	button.download { margin-top : 15px; }
	table.form tr td{
		padding:10px 30px 0 0;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	.texto_avulso{
	    font: 14px Arial; color: rgb(89, 109, 155);
	    background-color: #d9e2ef;
	    text-align: center;
	    width:700px;
	    margin: 10px auto;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}
	div.formulario table.form{
		padding:10px 0 10px 60px;
		text-align:left;
	}
	
	div.formulario form p{ margin:0; padding:0; }
</style>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script type="text/javascript">
	$().ready(function(){
	
		Shadowbox.init();
		
		$( "#data_inicial" ).mask("99/99/9999");
		$( "#data_inicial" ).datepick({startDate : "01/01/2000"});
		$( "#data_final" ).mask("99/99/9999");
		$( "#data_final" ).datepick({startDate : "01/01/2000"});	
	
	});
	
	function pesquisaPosto(campo,tipo){
			var campo = campo.value;

			if (jQuery.trim(campo).length > 2){
				Shadowbox.open({
					content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
					player:	"iframe",
					title:		"Pesquisa Posto",
					width:	800,
					height:	500
				});
			}else
				alert("Informar toda ou parte da informação para realizar a pesquisa!");
		}
		
		function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
			gravaDados('posto_codigo',codigo_posto);
			gravaDados('posto_nome',nome);
		}
		
		function gravaDados(name, valor){
			try{
				$("input[name="+name+"]").val(valor);
			} catch(err){
				return false;
			}
		}
	
</script>

<?php include "../js/js_css.php";?>

<div class="formulario" style="width:700px; margin:auto;">
	<div id="msg"></div>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm_os">
		<table cellspacing="1" align="center" class="form">
			<tr>
				<td>
					<label for="data_inicial">Data Inicial</label><br />
					<input type="text" name="data_inicial" id="data_inicial" class="frm" size="13" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
				</td>
				<td>
					<label for="data_final">Data Final</label><br />
					<input type="text" name="data_final" id="data_final" class="frm" size="13" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
				</td>
			</tr>
			<tr>
					<td>
						Código do Posto<br />
						<input class="frm" type="text" id="posto_codigo" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_os.posto_codigo, 'codigo');">
					</td>
					<td>
						Nome do Posto<br />
						<input class="frm" id="posto_nome" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>">
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_os.posto_nome, 'nome');">
					</td>
			</tr>
			<?php 
			
				function montaComboNota( $campo ) {
				
					echo '<select name="'.$campo.'" class="frm">';
					
					echo '<option value=""></option>';
					
					for ($i=1;$i <= 10; $i++) {
					
						$selected = ($_POST[$campo] == $i ) ? 'selected' : '';
					
						echo '<option value="'.$i.'" '.$selected.' >'.$i.'</option>';
					
					}
					
					echo '</select>';
				
				}
			if($login_fabrica <> 129){			
			?>
			<tr>
				<td>
					De <br />
					<?php montaComboNota('nota_inicial'); ?>
				</td>
				<td>
					Até <br />
					<?php montaComboNota('nota_final'); ?>
				</td>
			</tr>
			<?php
			}
			?>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="detalha_atendimento" id="detalha_atendimento" value="t" <?php if ($_POST['detalha_atendimento'] == 't') echo 'checked'; ?> />
						<label for="detalha_atendimento">Detalhar Atendimento</label>
					</td>
			</tr>
			<tr>
				<td colspan="2" style="padding-top:15px;" align="center">
					<input type="submit" name="gerar" value="Consultar" />
				</td>
			</tr>
		</table>
	</form>
</div>

<?php 
	if ( isset($_POST['gerar']) ) { // requisicao de relatorio
	
		if($_POST["data_inicial"]) $data_inicial = trim ($_POST["data_inicial"]);
		if($_POST["data_final"]) $data_final = trim($_POST["data_final"]);

		$nota_inicial 	= $_POST['nota_inicial'];
		$nota_final 	= $_POST['nota_final'];

		$detalha_atendimento = $_POST['detalha_atendimento'];

		if( empty($data_inicial) OR empty($data_final) )
			$msg_erro = "Data Inválida";
			
		if(strlen($msg_erro)==0) {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mi,$di,$yi) || !checkdate($mf,$df,$yf)) 
				$msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0) {
			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";
		
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial))
				$msg_erro = "Data Inválida.";
			if(strlen($msg_erro)==0)
				if (strtotime("$aux_data_inicial + 1 month" ) < strtotime($aux_data_final)) 
					$msg_erro = 'O intervalo entre as datas não pode ser maior que um mês.';
			if(empty($msg_erro)) {
				$cond_data = " AND data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			}
		}
		
		$codigo_posto 	= trim ($_POST['posto_codigo']);
		$nome_posto	=	trim ($_POST['posto_nome']);
		
		if ( !empty ($codigo_posto) || !empty($nome_posto) ) { // HD 720502
		
			$cond = '';
			$cond =  (!empty($codigo_posto) ) ? " AND tbl_posto_fabrica.codigo_posto =  '$codigo_posto' " : '';
			$cond =  (!empty($nome_posto) && empty($codigo_posto) ) ? " AND tbl_posto.nome LIKE  '$nome_posto' " : $cond; 
			
			$sql = "SELECT posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica USING(posto)
						WHERE fabrica = $login_fabrica
						$cond";
						
			$res = pg_query($con,$sql);
			
			$total = pg_num_rows($res);
			
			if ( $total > 0 )  {
			
				$posto = pg_result($res,0,0);
				$cond_posto = ' AND tbl_hd_chamado_extra.posto =  ' . $posto;
			
			}
			else {
			
				$msg_erro = "Posto não Encontrado";
				
			}
		
		}
		/* Essa condicao deve ficar por ultimo na query*/
		if ( !empty ($nota_inicial) && empty($nota_final) )
			$nota_final = 10;
		if ( !empty ($nota_final) && empty($nota_inicial) )
			$nota_inicial = 1;
		
		if ( !empty($nota_inicial) && !empty($nota_final) ) {

			if ( $nota_final < $nota_inicial ) {
				
				$msg_erro = 'A nota inicial deve ser inferior à nota final.';

			}
			
			$cond_nota = "HAVING AVG( nota ) BETWEEN $nota_inicial AND $nota_final";

			$sql = "SELECT tbl_hd_chamado_extra.posto 
						FROM tbl_hd_chamado
						JOIN tbl_resposta USING(hd_chamado)
						JOIN tbl_hd_chamado_extra USING(hd_chamado)
						WHERE
						tbl_hd_chamado.fabrica = $login_fabrica
						$cond_posto
						GROUP BY tbl_hd_chamado_extra.posto 
						$cond_nota";
						
				$res = pg_query($con,$sql);
				
				if ( pg_num_rows($res) == 0) {
					
					$msg_erro = "Nenhum registro entre o limite de notas escolhido";
				
				}
				
				$array_postos_media = array();
				
				for ($i = 0; $i< pg_num_rows($res); $i++) {
				
					$posto = pg_result($res,$i,'posto');
					
					if (!empty($posto))
						$array_postos_media[] = $posto;
				
				}
				
				$array_postos_media = implode(', ',$array_postos_media);
				
				if ( empty($cond_posto) && !empty($array_postos_media) ) {
				
					$cond_posto = " AND tbl_hd_chamado_extra.posto IN ($array_postos_media) ";
				
				}

		}
		
		if( empty($msg_erro)) {

			$avg_nota = "";
			$campo_nota_avg = " AVG(nota) as nota, ";
			if ($login_fabrica == 85) {
				$campo_nota_avg = " CASE WHEN nota IS NULL 
										THEN 
											AVG(txt_resposta::INT)
										ELSE 
											AVG(nota)
									END AS nota, ";
				$avg_nota = ", nota";
			}
		
			$link_xls = "xls/relatorio_pesquisa_satisfacao_$login_fabrica_" . date("d-m-y") . '.xls';
			if (file_exists($link_xls))
				exec("rm -f $link_xls");
			if ( is_writable("xls/") ) 
				$file = fopen($link_xls, 'a+');
			else
				echo 'Sem Permissão de escrita';
				
			ob_start();
		
			/**
			 *  @description Media dos fatores dos postos/posto 
			 */
			
			if ( !empty ($codigo_posto) || !empty($nome_posto) ) {
		
				$sql = "SELECT $campo_nota_avg 
							tbl_tipo_pergunta.descricao as fator, 
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome
							FROM tbl_resposta 
							JOIN tbl_hd_chamado USING(hd_chamado) 
							JOIN tbl_hd_chamado_extra USING(hd_chamado) 
							JOIN tbl_posto ON tbl_posto.posto = tbl_hd_chamado_extra.posto
							JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_hd_chamado.fabrica
							JOIN tbl_pergunta USING(pergunta) 
							JOIN tbl_tipo_pergunta USING(tipo_pergunta) 
							WHERE tbl_hd_chamado.fabrica = $login_fabrica
							$cond_posto
							$cond_data
							GROUP BY tbl_tipo_pergunta.descricao,tbl_posto_fabrica.codigo_posto, tbl_posto.nome $avg_nota
							ORDER BY tbl_posto.nome";
				
				$res = pg_query($con,$sql);
				
				$total_fatores = 0;
				
				for ($i = 0; $i < pg_num_rows($res); $i++) {
				
					if ($i == 0)  {
					
						echo '<br />
								<table class="tabela" cellspacing="1" align="center" style="min-width:700px;">
									<tr>
										<td class="titulo_tabela" colspan="4">MÉDIAS DOS FATORES</td>
									</tr>
									<tr class="titulo_coluna">
										<th>Código Posto</th>
										<th>Descrição Posto</th>
										<th>Fator</th>
										<th align="right">Média</th>
									</tr>';
					
					}
					
					$cod_posto 		= pg_result($res,$i,'codigo_posto');
					$nome_posto 	= pg_result($res,$i,'nome');
					$nota				= pg_result($res,$i,'nota');
					$fator				= pg_result($res,$i,'fator');
					
					$total_fatores += $nota;
					
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					
					echo '<tr bgcolor="'.$cor.'">
								<td>'.$cod_posto.'</td>
								<td align="center">&nbsp;'.$nome_posto.'</td>
								<td align="center">&nbsp;'.$fator.'</td>
								<td align="right">'.number_format( $nota, 2, ',' , '.' ).'</td>
							</tr>';
				
				}
				
				if ( pg_num_rows($res) ) {
						$total_fatores = $total_fatores / $i;
						echo '<tr class="titulo_coluna">
									<td colspan="3"><b>Total Geral das Médias dos Fatores</b></td>
									<td align="right">'.number_format( $total_fatores, 2, ',', '.') .'</td>
								</tr>';
					echo '</table><br />';
				}
				
				/* Fim media dos fatores */
				
				/* Media dos Requisitos */
				
				$sql = "SELECT $campo_nota_avg tbl_pergunta.descricao
							FROM tbl_resposta
							JOIN tbl_pergunta USING(pergunta)
							JOIN tbl_hd_chamado USING(hd_chamado)
							JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
							WHERE tbl_pergunta.fabrica = $login_fabrica
							$cond_data
							$cond_posto
							GROUP BY tbl_pergunta.descricao $avg_nota
							ORDER BY tbl_pergunta.descricao";
							
				
				$res = pg_query($con,$sql);
				
				$total_media_requisitos = 0;
				
				for ( $i = 0; $i < pg_num_rows($res); $i++ ) {
				
					$pergunta_descricao  = pg_result($res,$i,'descricao');
					$nota						= pg_result($res,$i,'nota');
					
					$total_media_requisitos += $nota;
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					
					if ($i == 0) {
					
						echo '<table class="tabela" cellspacing="1" align="center" style="width:700px;">
									<tr>
										<td class="titulo_tabela" colspan="2">MÉDIAS DOS REQUISITOS</td>
									</tr>
									<tr class="titulo_coluna">
										<th>Requisito</th>
										<th>Média</th>
									</tr>';
					
					}
					
					echo '<tr bgcolor="'.$cor.'">
								<td align="left">'.$pergunta_descricao.'</td>
								<td align="right">'.number_format($nota,2,',','').'</td>
							</tr>';
				
				}
				
				if (pg_num_rows($res)) {
					$total_media_requisitos = $total_media_requisitos / $i;
					echo '<tr class="titulo_coluna">
								<td><b>Total Geral das Médias dos Requisitos</b></td>
								<td align="right">'.number_format( $total_media_requisitos, 2, ',', '.') .'</td>
							</tr>';
					echo '</table>';
				}
				/* Fim media dos requisitos */
				
			}
			
			/** 
			 *  @description Media geral do(s) postos(s), somente quando nao pesquisar por posto especifico 
			 */
			
				$sql = "SELECT tbl_posto.nome, 
							tbl_posto_fabrica.codigo_posto, 
							$campo_nota_avg
							COUNT(*) AS total_pesquisado
							FROM tbl_resposta 
							JOIN tbl_hd_chamado_extra USING(hd_chamado) 
							JOIN tbl_hd_chamado USING(hd_chamado)
							JOIN tbl_posto ON tbl_posto.posto = tbl_hd_chamado_extra.posto
							JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_hd_chamado.fabrica
							WHERE tbl_hd_chamado.fabrica = $login_fabrica
							$cond_data
							$cond_posto
							GROUP BY tbl_posto.nome,tbl_posto_fabrica.codigo_posto $avg_nota
							$cond_nota";
				
				$res = pg_query($con,$sql);
				
				for ($i = 0; $i < pg_num_rows($res); $i++) {
				
					if ( $i == 0) {
					
						echo '<br />
								<table class="tabela" cellspacing="1" align="center" style="min-width:700px;">
									<tr class="titulo_tabela">
										<td colspan="4">MÉDIAS GERAIS DOS POSTOS</td>
									</tr>
									<tr class="titulo_coluna">
										<th>Cod Posto</th>
										<th>Nome do Posto</th>
										<th>Qtde de Pesquisas</th>
										<th align="right">Média Geral</th>
									 </tr>';
					
					}
					
					$codigo = pg_result($res,$i,'codigo_posto');
					$nome	= pg_result($res,$i,'nome');
					$nota	= pg_result($res,$i,'nota');
					$total	= pg_result($res,$i,'total_pesquisado');
					
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					
					echo '<tr bgcolor="'.$cor.'">
								<td>'.$codigo.'</td>
								<td align="center">'.$nome.'</td>
								<td align="center">'.$total.'</td>
								<td align="right">'.number_format( $nota, 2, ',' , '.' ).'</td>
							</tr>';
				
				}
				
				if ( pg_num_rows($res) ){
					echo '</table>';
				}
				
			
			
			/* Fim Media geral do(s) postos(s) */
			
			/** 
			  *  @description Relatorio geral, nota por fator 
			  *  @todo acertar campos, mostrar todos somente quando for ticado o detalhar
			  */
			if ( $detalha_atendimento == 't' ) {

				$campo_nota = " tbl_resposta.nota ";
				if ($login_fabrica == 85) {
					$campo_nota = " CASE WHEN tbl_resposta.nota IS NULL 
										THEN 
											tbl_resposta.txt_resposta::INT
										ELSE 
											tbl_resposta.nota
									END AS nota";
				}
				
				$sql = "SELECT 
							tbl_hd_chamado.hd_chamado, 
							tbl_admin.nome_completo,
							tbl_produto.referencia,
							tbl_produto.descricao AS descricao_produto,
							tbl_posto.nome,
							tbl_posto_fabrica.codigo_posto,
							tbl_tipo_pergunta.descricao AS fator,
							tbl_pergunta.descricao AS pergunta,
							$campo_nota
						FROM 
							tbl_pergunta
							JOIN tbl_resposta USING(pergunta)
							JOIN tbl_tipo_pergunta ON tbl_pergunta.tipo_pergunta = tbl_tipo_pergunta.tipo_pergunta
							JOIN tbl_hd_chamado USING(hd_chamado)
							JOIN tbl_hd_chamado_extra USING(hd_chamado)
							LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_hd_chamado_extra.posto
							LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_hd_chamado_extra.posto AND tbl_posto_fabrica.fabrica = tbl_hd_chamado.fabrica
							JOIN tbl_produto USING(produto)
							JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
						WHERE 
							tbl_tipo_pergunta.fabrica = $login_fabrica
							AND tbl_hd_chamado.fabrica = $login_fabrica
							$cond_data
							$cond_posto
						ORDER BY tbl_hd_chamado.hd_chamado DESC";
				//echo nl2br($sql); die;
				$res = pg_query($con,$sql);
				
				if(pg_num_rows($res)) {
					
					for($i = 0; $i < pg_num_rows($res); $i++) {
					
						$atendente 	= pg_result($res, $i, 'nome_completo');
						$hd_chamado	= pg_result($res, $i, 'hd_chamado');
						$referencia = pg_result($res, $i, 'referencia');
						$nome_prod	= pg_result($res, $i, 'descricao_produto');
						$fator		= pg_result($res, $i, 'fator');
						$pergunta	= pg_result($res, $i, 'pergunta');
						$nota		= pg_result($res, $i, 'nota');
						$cod_posto = pg_result($res, $i, 'codigo_posto');
						$nome_posto = pg_result($res, $i, 'nome');
						
						$total_geral += $nota;
						
						if ( $i == 0 ){
						
							echo '<br />
							<table class="tabela" cellspacing="1" align="center">
								<tr class="titulo_coluna">
									<th>Protocolo</th>
									<th>Atendente</th>
									<th>Cod Posto</th>
									<th>Nome do Posto</th>
									<th>Referência</th>
									<th>Produto</th>
									<th>Fator</th>
									<th>Requisito</th>
									<th>Nota</th>
								</tr>';
						
						}
						
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						
						echo '<tr bgcolor="'.$cor.'">
								<td><a href="callcenter_interativo_new.php?callcenter='.$hd_chamado.'" target="_blank">'.$hd_chamado.'</a></td>
								<td>'.$atendente.'</td>
								<td>&nbsp;'.$cod_posto.'</td>
								<td>&nbsp;'.$nome_posto.'</td>
								<td>'.$referencia.'</td>
								<td>'.$nome_prod.'</td>
								<td>'.$fator.'</td>
								<td>'.$pergunta.'</td>
								<td>'.$nota.'</td>
							  </tr>';

					}
					
					if(pg_num_rows($res)) {
						echo '</table>';
					}
				}

			}
			
			$dados_relatorio = ob_get_contents();
			fwrite($file,$dados_relatorio);
			
		}
		else { // Erro de validacao

			echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>';

		}
		
	} // fim request
?>

<?php

	if ( isset ($file) && !empty($dados_relatorio) ) {
		echo "<button class='download' onclick=\"window.open('$link_xls') \">Download XLS</button>";
		fclose($file);
	}
	else if(empty($msg_erro) && isset($_POST['gerar']) && empty($dados_relatorio))
		echo "Não foram encontrados resultados para essa pesquisa";
?>
<script type="text/javascript">
	<?php if ( !empty($msg_erro) ){ ?>
			$("#erro").appendTo("#msg").fadeIn("slow");
	<?php } ?>
</script>
<?php include 'rodape.php'; ?>
