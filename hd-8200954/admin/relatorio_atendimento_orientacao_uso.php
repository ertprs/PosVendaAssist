<?php
	/**
	 *	@description Relatorio Pesquisa de Satisfação - HD 674943 e 720502
	 *  @author Brayan L. Rastelli.
	 **/
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "autentica_admin.php";
	$layout_menu = "callcenter";
	$title = "RELATÓRIO DE ATENDIMENTOS - ORIENTAÇÃO DE USO";
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
	tr th a {color:white !important;}
	tr th a:hover {color:blue !important;}
	
	div.formulario form p{ margin:0; padding:0; }
</style>

<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/datePicker.v1.js"></script>

<script type="text/javascript">
	$().ready(function(){
	
		Shadowbox.init();

		$( "#data_inicial" ).maskedinput("99/99/9999");
		$( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
		$( "#data_final" ).maskedinput("99/99/9999");
		$( "#data_final" ).datePicker({startDate : "01/01/2000"});	

	});

	function pesquisaProduto(produto,tipo){

		if (jQuery.trim(produto.value).length > 2){
			Shadowbox.open({
				content:	"produto_pesquisa_nv.php?"+tipo+"="+produto.value,
				player:	"iframe",
				title:		"Produto",
				width:	800,
				height:	500
			});
		}else{
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
			produto.focus();
		}
	}

	function retorna_produto(produto,referencia,descricao, numero_serie, posicao){
		gravaDados("produto_referencia_"+posicao,referencia);
		gravaDados("produto_descricao_"+posicao,descricao);
		gravaDados("produto_serie_"+posicao,numero_serie);
	}

	function retorna_dados_produto(referencia,descricao,produto,linha,nome_comercial,voltagem,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada){
		gravaDados('produto_referencia',referencia);
		gravaDados('produto_descricao',descricao);
	}

	function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}
	
</script>

<?php include "javascript_calendario.php";?>

<?php
	if ( isset($_POST['gerar']) ) {

		$cond = '';

		if($_POST["data_inicial"]) $data_inicial = trim ($_POST["data_inicial"]);
		if($_POST["data_final"]) $data_final = trim($_POST["data_final"]);

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
				
				$qtdeMeses =  " 1 month ";
				$xMeses = ' um mês.';
				
				if ($login_fabrica == 24) {
					$qtdeMeses =  " 18 months ";
					$xMeses = ' dezoito meses.';
				} 

				if (strtotime("$aux_data_inicial + $qtdeMeses") < strtotime($aux_data_final)) 
					$msg_erro = 'O intervalo entre as datas não pode ser maior que' . $xMeses;
			if(empty($msg_erro)) {
				$cond .= " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
			}
		}

		$referencia = trim ($_POST['produto_referencia']);
		$xatendente 	= (int) trim ($_POST['xatendente']);

		if ( !empty($referencia) ) {
			
			$sql = "SELECT produto
					FROM tbl_produto
					JOIN tbl_linha USING(linha)
					WHERE referencia = '$referencia'";

			$res = pg_query($con,$sql);

			if (pg_num_rows($res)) {
				$produto = pg_result($res,0,0);
				$cond .= " AND tbl_hd_chamado_extra.produto = $produto ";
			}
			else
				$msg_erro = 'Produto '.$referencia.' não Encontrado';

		}
		if ( !empty($xatendente) ) {

			$cond .= ' AND tbl_hd_chamado.atendente = ' . $xatendente;

		}

	}
?>

<div class="formulario" style="width:700px; margin:auto;">
	<div id="msg"></div>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm">
		<table cellspacing="1" align="center" class="form">
			<tr>
				<td style="min-width:120px;">
					<label for="data_inicial">Data Inicial</label><br />
					<input type="text" name="data_inicial" id="data_inicial" class="frm" size="13" value="<?=isset($_POST['data_inicial'])?$_POST['data_inicial'] : '' ?>" />
				</td>
				<td style="min-width:120px;">
					<label for="data_final">Data Final</label><br />
					<input type="text" name="data_final" id="data_final" class="frm" size="13" value="<?=isset($_POST['data_final'])?$_POST['data_final'] : ''?>"/>
				</td>
			</tr>
			<tr>
				<td>
					Referência Produto<br><input type="text" name="produto_referencia" id="produto_referencia" value="<?php echo $produto_referencia;?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm.produto_referencia,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'>
					&nbsp;&nbsp;&nbsp;
				</td>

				<td>
					Descrição Produto&nbsp;<br><input type="text" name="produto_descricao" id="produto_descricao" value="<?php echo $produto_descricao;?>" size="30" maxlength="50" class='frm'>&nbsp;<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'>
				</td>
			</tr>
			<tr>
				<td>
					Atendente<br />
					<select name="xatendente" style='width:80px; font-size:9px' class="frm" >
					 <option value=''></option>
					<?	$sql = "SELECT admin, login
								from tbl_admin
								where fabrica = $login_fabrica
								and ativo is true
								and (privilegios like '%call_center%' or privilegios like '*') order by login";
						$res = pg_exec($con,$sql);
						if(pg_numrows($res)>0){
							for($i=0;pg_numrows($res)>$i;$i++){
								$atendente = pg_result($res,$i,admin);
								$atendente_nome = pg_result($res,$i,login);
								echo "<option value='$atendente'";
									if($xatendente == $atendente) {
											echo "selected";
										}
								echo ">$atendente_nome</option>";
							}
						}
					?>
					</select>
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
	if ( isset($_POST['gerar']) && empty ($msg_erro) ) { // requisicao de relatorio
		
		$link_xls = "xls/relatorio_atendimento_orientacao_uso_$login_fabrica_" . date("d-m-y") . '.xls';
		if (file_exists($link_xls))
			exec("rm -f $link_xls");
		if ( is_writable("xls/") ) 
			$file = fopen($link_xls, 'a+');
		else
			echo 'Sem Permissão de escrita';

		$campos_header = array('tbl_admin.nome_completo','COUNT(tbl_hd_chamado.hd_chamado) as total');
		$result_fields = array('nome_completo' => 'Atendente', 'total' => 'Produto Reclamação', 'perc' => '%','orientacao' => 'Orientação', 'perc_orientacao' => '%');
		$group_by = 'nome_completo';

		$campos_add = 'tbl_hd_chamado.atendente, ';


		$sql = "SELECT ( SELECT COUNT(tbl_hd_chamado.hd_chamado)
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra USING(hd_chamado)
				JOIN tbl_produto USING(produto)
				JOIN tbl_admin ON tbl_admin.admin = atendente
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado.categoria = 'produto_reclamacao'
				$cond ),
				( SELECT COUNT(tbl_hd_chamado.hd_chamado)
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra USING(hd_chamado)
				JOIN tbl_produto USING(produto)
				JOIN tbl_admin ON tbl_admin.admin = atendente
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado.categoria = 'produto_reclamacao'
				$cond and orientacao_uso) ";

		$res = pg_query($con,$sql);

		$total = pg_result($res,0,0);
		$total_orientacao = pg_result($res,0,1);
			
		ob_start();
		
		$sql = "SELECT $campos_add ".implode(', ', $campos_header)."
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra USING(hd_chamado)
				JOIN tbl_produto USING(produto)
				JOIN tbl_admin ON tbl_admin.admin = atendente
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado.categoria = 'produto_reclamacao'
				$cond					
				GROUP BY $campos_add $group_by
				ORDER BY $group_by";
		//echo nl2br ($sql); 
		$res = pg_query($con,$sql);
		
		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$admin = pg_result($res,$i,'atendente');

			$url_params = "tela=2&data_inicial=$aux_data_inicial&data_final=$aux_data_final&produto=$produto&atendente=$admin";
			
			if ( $i == 0) {

				echo '<br />
					  <table class="tabela" cellspacing="1" align="center" style="min-width:700px;">
					  	<tr class="titulo_coluna">';
				
				foreach ($result_fields as $v) {

					echo '<th>'.$v.'</th>';
				
				}

				echo '</tr>';
			
			}
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			echo '<tr bgcolor="'.$cor.'">';

			foreach ($result_fields as $k => $v) {
			
				if ($k == 'total') {
					$total_linha = pg_result($res,$i,$k);
				}

				switch($k) {
					
					case 'perc' : 
						$value = number_format( $total_linha * 100 / $total, 2, ',','' ); 
						break;
					case 'orientacao': 
						if ( $campos_add ) {
							$where = " AND tbl_hd_chamado.atendente = $admin";
						}
						$sql = "SELECT COUNT(tbl_hd_chamado.hd_chamado)
								FROM tbl_hd_chamado 
								JOIN tbl_hd_chamado_extra USING (hd_chamado)
								WHERE fabrica = $login_fabrica
								$where
								AND orientacao_uso IS TRUE
								$cond";
						$res2 = pg_query($con,$sql);
						$tot_orientacao = pg_result($res2,0,0);
						$value = $tot_orientacao == 0 ? '' : $tot_orientacao;
						break;
					case 'perc_orientacao' : 
						if ($total_orientacao != 0) {
							$value = $tot_orientacao * 100 / $total ;
							$value = number_format($value,2);
							$pc_total_orientacao += $value;
						}
						else
							$value = 0;
						$value = $value == 0 ? '' : $value;
						break;

					default: $value = pg_result($res,$i,$k);

				}

				$align = in_array( $k, array('total','perc','orientacao','perc_orientacao') ) ? 'center' : 'left';

				if ( in_array($k, array('total','orientacao')) ) {

					if ($k == 'orientacao')
						$link = '<a href="relatorio_atendimento_orientacao_uso_popup.php?'.$url_params.'&orientacao=t" target="_blank">'.$value.'</a>';
					else
						$link = '<a href="relatorio_atendimento_orientacao_uso_popup.php?'.$url_params.'" target="_blank">'.$value.'</a>';
					
					echo '<td align="'.$align.'">
							'.$link.'
						  </td>';

				}
				else {
					echo '<td align="'.$align.'">'.$value.'</td>';
				}
			}

			echo '</tr>';
			
		}
		if (pg_num_rows($res)) {
			$url_params = "tela=2&data_inicial=$aux_data_inicial&data_final=$aux_data_final&produto=$produto&atendente=$xatendente";
			echo '<tr class="titulo_coluna">
					<th colspan="'. (count($result_fields) - 4).'">Total</th>
					<th><a href="relatorio_atendimento_orientacao_uso_popup.php?'.$url_params.'" target="_blank">'.$total.'</a></th>
					<th>100%</th>
					<th align="center"><a href="relatorio_atendimento_orientacao_uso_popup.php?'.$url_params.'&orientacao=t" target="_blank">'.$total_orientacao.'</a></th>
					<th>'.$pc_total_orientacao.'</th>
				  </tr>
				</table>';
		}
		
		$dados_relatorio = ob_get_contents();
		
		fwrite($file,$dados_relatorio);
		
	}
	else if ( isset($_POST['gerar']) ) { // Erro de validacao

		echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>';

	}
?>

<?php

	if ( isset ($file) && !empty($dados_relatorio) ) {
		echo "<button class='download' onclick=\"window.open('$link_xls') \">Download XLS</button>";
		fclose($file);
	}
	else if(empty($msg_erro) && isset($_POST['gerar']) )
		echo "Não foram encontrados resultados para essa pesquisa";
?>
<script type="text/javascript">
	<?php if ( !empty($msg_erro) ){ ?>
			$("#erro").appendTo("#msg").fadeIn("slow");
	<?php } ?>
</script>
<?php include 'rodape.php'; ?>
