<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';

	$admin_privilegios="call_center";
	if($_POST['btn_acao']){

		$btn_acao	 		= $_POST['btn_acao'];
		$data_inicial 		= $_POST['data_inicial'];
		$data_final 		= $_POST['data_final'];
		$codigo_posto 		= $_POST['codigo_posto'];
		$nome_posto 		= $_POST['nome_posto'];
		$produto_referencia = $_POST['produto_referencia'];
		$produto_descricao 	= $_POST['produto_descricao'];
		$os 				= $_POST['os'];
		$laudo_tecnico 		= $_POST['laudo_tecnico'];
		$solucao_consulta 	= $_POST['solucao'];

		if($os OR $laudo_tecnico){
			if($os){
				$sua_os = substr($os,5);
				$cond = " AND tbl_os.sua_os = '$sua_os' ";
			}

			if($laudo_tecnico){
				$cond .= " AND tbl_laudo_tecnico_os.ordem = $laudo_tecnico ";
			}
		}else{
			if(empty($data_inicial) OR empty($data_final)){
				$msg_erro = 'Data obrigatória';
			}else{
				list($d,$m,$y) = explode('/',$data_inicial);
				if(!checkdate($m,$d,$y)){
					$msg_erro = 'Data inicial inválida';
				}else{
					$aux_data_inicial = "$y-$m-$d";
				}

				list($d,$m,$y) = explode('/',$data_final);
				if(!checkdate($m,$d,$y)){
					$msg_erro = 'Data inicial inválida';
				}else{
					$aux_data_final = "$y-$m-$d";
				}

				if(strlen($msg_erro)==0){
			    	if (strtotime($aux_data_inicial.'+6 month') < strtotime($aux_data_final) ) {
			            $msg_erro = 'O intervalo entre as datas não pode ser maior que 6 meses';
			        }
			    }

			    if(strlen($msg_erro)==0){
			    	$cond = " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' and '$aux_data_final' ";

			    	if($codigo_posto){
			    		$sql = "SELECT tbl_posto.posto 
			    					FROM tbl_posto 
			    					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			    					WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
			    		$res = pg_query($con,$sql);
			    		if(pg_num_rows($res) > 0){
			    			$posto = pg_fetch_result($res,0,'posto');
			    			$cond .= " AND tbl_os.posto = $posto ";
			    		}else{
			    			$msg_erro = "Posto não encontrado";
			    		}
			    	}

			    	if($produto_referencia){
			    		$sql = "SELECT produto 
			    					FROM tbl_produto 
			    					WHERE fabrica_i = $login_fabrica
			    					AND referencia = '$produto_referencia'";
			    		$res = pg_query($con,$sql);
			    		if(pg_num_rows($res) > 0){
			    			$produto = pg_fetch_result($res,0,'produto');
			    			$cond .= " AND tbl_os.produto = $produto ";
			    		}else{
			    			$msg_erro = "Posto não encontrado";
			    		}
			    	}

			    	if($solucao_consulta AND $login_fabrica != 1){
			    		$cond .= " AND tbl_os.solucao_os = $solucao_consulta ";
			    	}
 	
 					if($solucao_consulta AND $login_fabrica == 1) {

 						if ($solucao_consulta == 'comlaudo') {

 							$cond .=  " AND tbl_laudo_tecnico_os.laudo_tecnico_os IS NOT NULL ";
 						}
 						else if ($solucao_consulta == 'semlaudo') {
 						
 							$cond .=  " AND tbl_laudo_tecnico_os.laudo_tecnico_os IS NULL "; 						}
 					}
			    }
			}
		}
	}

	$title = 'RELATÓRIO LAUDO TÉCNICO';
	$layout_menu = 'callcenter';
	include 'cabecalho.php';
?>

<style type='text/css'>
	.msg_erro{
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

	table.tabela tr td{
	    font-family: verdana;
	    font-size: 11px;
	    border:1px solid #ACACAC;
		border-collapse: collapse;
	}

	.formulario{
	    background-color:#D9E2EF;
	    font:11px Arial;
	    text-align:left;
	}

	.toggle_os, .toggle_laudo{
		cursor:pointer;
	}

	.toggle_os:hover, .toggle_laudo:hover{
		background-color: #a1a1a1;
	}
</style>



<?php 
	include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist */
?>
<script language="javascript">
	
	$().ready(function() {

		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$( "#data_inicial" ).mask("99/99/9999");
		$( "#data_final" ).mask("99/99/9999");
			

		
		$('.toggle_os').bind('click', function(){
			var os = $(this).parent().attr('rel');
			window.open("os_press.php?os="+os);
		});

		$('.toggle_laudo').bind('click', function(){
			var laudo = $(this).attr('rel');
			var os = $(this).parent().attr('rel');
			window.open("gerar_laudo_tecnico.php?os="+os+"&laudo="+laudo);
		});

		Shadowbox.init();
	});

	function fnc_pesquisa_posto_novo(codigo, nome) {
		var codigo = jQuery.trim(codigo.value);
		var nome   = jQuery.trim(nome.value);
		if (codigo.length > 2 || nome.length > 2){   
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?os=&codigo=" + codigo + "&nome=" + nome,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else{
			alert("Preencha toda ou parte da informação para realizar a pesquisa!");
		}

	}

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados("codigo_posto",codigo_posto);
        gravaDados("nome_posto",nome);
    }

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
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
			produto.focus();
		}
	}

	function retorna_dados_produto(referencia,descricao,produto){
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_descricao",descricao);
	}

	function gravaDados(name, valor){
	    try {
	        $("input[name="+name+"]").val(valor);
	    } catch(err){
	        return false;
	    }
	}

</script>

<?php if(!empty($msg_erro)){ ?>
		<table align='center' width='700'>
			<tr class='msg_erro'><td><?=$msg_erro?></td></tr>
		</table>
<?php } ?>

<form name='frm_pesquisa' method='post'>
	<table align='center' width='700' class='formulario'>
		<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>

		<tr>
			<td width='100'>&nbsp;</td>
			<td>
				Data Inicial <br>
				<input type='text' name='data_inicial' id='data_inicial' size='15' value='<?=$data_inicial?>' class='frm'>
			</td>

			<td>
				Data Final <br>
				<input type='text' name='data_final' id='data_final' size='15' value='<?=$data_final?>' class='frm'>
			</td>
		</tr>

		<tr>
			<td width='100'>&nbsp;</td>
			<td>
				Código Posto <br>
				<input type='text' name='codigo_posto' id='codigo_posto' size='15' value='<?=$codigo_posto?>' class='frm'>
				&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_posto_novo(document.frm_pesquisa.codigo_posto,'')" style='cursor:pointer;'>
			</td>

			<td>
				Nome Posto <br>
				<input type='text' name='nome_posto' id='nome_posto' size='45' value='<?=$nome_posto?>' class='frm'>
				&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_posto_novo('',document.frm_pesquisa.nome_posto)" style='cursor:pointer;'>
			</td>
		</tr>

		<tr>
			<td width='100'>&nbsp;</td>
			<td>
				Referência Produto <br>
				<input type='text' name='produto_referencia' id='produto_referencia' size='15' value='<?=$produto_referencia?>' class='frm'>
				&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_pesquisa.produto_referencia,'referencia')" style='cursor:pointer;'>
			</td>

			<td>
				Descrição Produto <br>
				<input type='text' name='produto_descricao' id='produto_descricao' size='45' value='<?=$produto_descricao?>' class='frm'>
				&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_pesquisa.produto_descricao,'descricao')" style='cursor:pointer;'>
			</td>
		</tr>

		<tr>
			<td width='100'>&nbsp;</td>
			<td>
				Ordem de Serviço <br>
				<input type='text' name='os' id='os' size='15' value='<?=$os?>' class='frm'>
			</td>

			<td>
				Laudo Técnico <br>
				<input type='text' name='laudo_tecnico' id='laudo_tecnico' size='15' value='<?=$laudo_tecnico?>' class='frm'>
			</td>
		</tr>

		<tr>
			<td width='100'>&nbsp;</td>
			<?php $col = ($login_fabrica == 1) ? "1" : "2"; ?>
			<td colspan='<?=$col?>'>
				Solucao <br>
				<select name='solucao' class='frm'>
					<?php

					   if ($login_fabrica != 1) {
						$sqlL = "SELECT solucao,descricao 
										FROM tbl_solucao 
										WHERE fabrica = $login_fabrica 
										AND ativo IS TRUE 
										AND descricao like 'Troca satisfa%'";
						$resL = pg_query($con,$sqlL);

						if(pg_num_rows($resL) > 0){

							for($i = 0; $i < pg_num_rows($resL); $i++){
								$solucao = pg_fetch_result($resL,$i,'solucao');
								$desc_solucao = pg_fetch_result($resL,$i,'descricao');
								$selected = ($solucao == $solucao_consulta) ? "SELECTED" : "";

								echo "<option value='{$solucao}' {$selected}>{$desc_solucao}</solucao>";
							}
						}
					}
					else{ ?>
				 		 <option value='comlaudo' <?= ($_POST['solucao'] == "comlaudo" || !isset($_POST['solucao'])) ? "selected" : "" ?>>Com Laudo</option>
				 	 	 <option value='semlaudo' <?= ($_POST['solucao'] == "semlaudo") ? "selected" : "" ?>>Sem Laudo</option>				
						<?
					}	
					?>
				</select>
			</td>
			<?php if ($login_fabrica == 1) { 
					$check = (!empty($satisfacao_90)) ? "checked" : "";
          
			?>
					<td>
						<input type="checkbox" name="satisfacao_90" class='frm' <?=$check?>>
  						<label for="satisfacao_90">Troca Satisfação 90 dias</label>
  					</td>
					
			<?php } ?>
		</tr>

		<tr>
			<td colspan='3' align='center'>
				<input type='hidden' name='btn_acao' value=''>
				<input type='button' value='Pesquisar' onclick="javascript: if(document.frm_pesquisa.btn_acao.value == ''){document.frm_pesquisa.btn_acao.value = 'pesquisar';document.frm_pesquisa.submit();}else{alert('Consulta em processamento. Aguarde.');}">
			</td>
		</tr>

	</table>
</form>
<br />
<?php
	if($btn_acao AND empty($msg_erro)){
		//$cond_extra = " AND (tbl_laudo_tecnico_os.os NOTNULL or tbl_os.laudo_tecnico notnull)";

		if ($login_fabrica == 1) {
			$campos_sql = "
			    , tbl_laudo_tecnico_os.titulo
			    , tbl_laudo_tecnico_os.observacao
			    , tbl_produto.referencia_fabrica
			    , tbl_produto.valores_adicionais
			";

			$marca_produto = " AND tbl_produto.marca IN (237,238) ";

			if ($_POST['satisfacao_90']) {
				$satisfacao_90 = $_POST['satisfacao_90'];
				$camp = ",	tbl_os_campo_extra.campos_adicionais
						 ,	tbl_os.consumidor_cidade
						 ,	tbl_os.consumidor_estado
						 ,	tbl_cidade.nome AS revenda_cidade
						 ,	tbl_cidade.estado AS revenda_estado";

				$join_extra = " JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
								LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os 
								LEFT JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
								LEFT JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade";

				$cond_extra = " AND tbl_os.satisfacao IS TRUE";
			}
	   		
		}
			else{
				$cond_extra = " AND (tbl_laudo_tecnico_os.os NOTNULL or tbl_os.laudo_tecnico notnull)";
			}

 
		$sql = "SELECT tbl_os.os,
					   tbl_os.sua_os,
					   tbl_os.nota_fiscal,
					   to_char(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf,
					   to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
					   tbl_posto_fabrica.codigo_posto,
					   tbl_posto.nome AS posto_nome,
					   tbl_produto.referencia,
					   tbl_produto.descricao,
					   tbl_laudo_tecnico_os.laudo_tecnico_os,
					   tbl_laudo_tecnico_os.ordem,
					   tbl_os.laudo_tecnico
					   $campos_sql
					   $camp
					FROM tbl_os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
					LEFT JOIN tbl_laudo_tecnico_os ON tbl_os.os = tbl_laudo_tecnico_os.os AND tbl_laudo_tecnico_os.fabrica = $login_fabrica
					$join_extra
					WHERE tbl_os.fabrica = $login_fabrica  
					AND tbl_os.data_abertura - tbl_os.data_nf <= 90
					$cond_extra
					$cond
					$marca_produto";
		 	
		if($solucao_consulta AND $login_fabrica == 1){
			 if(!empty($laudo_tecnico)){
			 	$sql = " WITH laudo_tecnico_os AS (
 						    	SELECT 
    							laudo_tecnico_os,
    							ordem,
    							titulo,
    							observacao,
    							os,
    							fabrica
  								FROM tbl_laudo_tecnico_os
  								WHERE fabrica = $login_fabrica 
  								{$cond}
						   ) SELECT tbl_os.os,
							   tbl_os.sua_os,
							   tbl_os.nota_fiscal,
							   to_char(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf,
							   to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
							   tbl_posto_fabrica.codigo_posto,
							   tbl_posto.nome AS posto_nome,
							   tbl_produto.referencia,
							   tbl_produto.descricao,
							   tbl_laudo_tecnico_os.laudo_tecnico_os,
							   tbl_laudo_tecnico_os.ordem,
							   tbl_os.laudo_tecnico
							   $campos_sql
							   $camp
							FROM tbl_os
							JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
							JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
							JOIN laudo_tecnico_os as tbl_laudo_tecnico_os ON tbl_os.os = tbl_laudo_tecnico_os.os 
							AND tbl_laudo_tecnico_os.fabrica = $login_fabrica	
							$join_extra
							WHERE tbl_os.fabrica = $login_fabrica
							AND tbl_os.data_abertura - tbl_os.data_nf <= 90";
			 }		       
	 	}		

		//echo nl2br($sql); exit;
		$res = pg_query($con,$sql);


		if(pg_num_rows($res) > 0 ){
			ob_start();
			$resultado = "<table class='tabela' align='center'>
							<tr class='titulo_coluna'>
								<th>OS</th>
								<th>Referência</th>
								<th>Descrição</th>
								<th>Código Posto</th>
								<th>Posto</th>
								<th>Laudo</th>
								<th>Nota Fiscal</th>
								<th>Data Compra</th>
								<th>Data Abertura</th>";

								/*HD - 4288909*/
								if ($login_fabrica == 1) {
									$resultado .= "<th>Motivo Troca</th>";
									$resultado .= "<th colspan=2>Peça Faltante</th>";
									$resultado .= "<th>Referência Interna</th>";
									$resultado .= "<th>Custo do Produto</th>";

									if ($_POST['satisfacao_90']) { 
										$resultado .= "<th>Revenda Estado</th>";
										$resultado .= "<th>Revenda Cidade</th>";
										$resultado .= "<th>Consumidor Estado</th>";
										$resultado .= "<th>Cidade Consumidor</th>";
										$resultado .= "<th>Chave de Acesso</th>";
										$resultado .= "<th>Número da AD</th>";
										$resultado .= "<th>Número da Coleta</th>";
									}

								}

						  	$resultado .= "</tr>";
			for ($i=0; $i < pg_num_rows($res); $i++) { 
				$os 				= pg_fetch_result($res, $i, 'os');
				$sua_os 			= pg_fetch_result($res, $i, 'sua_os');
				$nota_fiscal 		= pg_fetch_result($res, $i, 'nota_fiscal');
				$data_nf 			= pg_fetch_result($res, $i, 'data_nf');
				$data_abertura 		= pg_fetch_result($res, $i, 'data_abertura');
				$codigo_posto 		= pg_fetch_result($res, $i, 'codigo_posto');
				$posto_nome 		= pg_fetch_result($res, $i, 'posto_nome');
				$referencia 		= pg_fetch_result($res, $i, 'referencia');
				$descricao 			= pg_fetch_result($res, $i, 'descricao');
				$laudo_tecnico_os 	= pg_fetch_result($res, $i, 'laudo_tecnico_os');
				$laudo_tecnico 	= pg_fetch_result($res, $i, 'laudo_tecnico');
				$ordem 				= pg_fetch_result($res, $i, 'ordem');

				if ($login_fabrica == 1 && $_POST['satisfacao_90']) {
					$revenda_estado    = pg_fetch_result($res, $i, 'revenda_estado');
					$revenda_cidade    = pg_fetch_result($res, $i, 'revenda_cidade');
					$consumidor_estado = pg_fetch_result($res, $i, 'consumidor_estado');
					$consumidor_cidade = pg_fetch_result($res, $i, 'consumidor_cidade');
					$campos_adicionais = json_decode(pg_fetch_result($res, $i, 'campos_adicionais'), true);
					$chave_nf      = $campos_adicionais['nfe_bd'];
					$numero_ad     = $campos_adicionais['numero_ad'];
					$numero_coleta = $campos_adicionais['numero_coleta'];
				}

				if(empty($ordem) and !empty($laudo_tecnico)) $ordem = $laudo_tecnico;

				$cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";


				$resultado .= "<tr rel='{$os}' bgcolor='$cor'>
									<td class='toggle_os'>{$codigo_posto}{$sua_os}</td>
									<td nowrap>{$referencia}</td>
									<td nowrap>{$descricao}</td>
									<td nowrap>{$codigo_posto}</td>
									<td nowrap>{$posto_nome}</td>
									<td class='toggle_laudo' rel='{$laudo_tecnico_os}'>{$ordem}</td>
									<td>{$nota_fiscal}</td>
									<td>{$data_nf}</td>
									<td>{$data_abertura}</td>";

				/*HD - 4288909*/
				if ($login_fabrica == 1) {
					$titulo 			= trim(pg_fetch_result($res, $i, 'titulo'));
					unset($observacao);

					switch ($titulo) {
						case 'Falta de peça na AT':
							$observacao = trim(pg_fetch_result($res, $i, 'observacao'));
							$observacao = explode("<br>", $observacao);
						break;

						case 'Produto sem lista básica':
							$observacao = "";
						break;

						case 'Peça não consta na lista básica':
							$auxiliar = trim(pg_fetch_result($res, $i, 'observacao'));
							if (strlen($auxiliar) <= 1) {
								$observacao = "";
							} else {
								if (strripos($auxiliar, ';') === false) {
									$auxiliar     = str_replace("-", "|", $auxiliar);
									$observacao[] = $auxiliar;
								}
							}
						break;
						
						default:
							$observacao = "";
						break;
					}

					$referencia_fabrica = trim(pg_fetch_result($res, $i, 'referencia_fabrica'));
					$valores_adicionais = json_decode(trim(pg_fetch_result($res, $i, 'valores_adicionais')), true);
					
					$medioCR       = "R$ " . str_replace(".", ",", $valores_adicionais["medioCR"]);
					$peca_faltando = "";

					$resultado .= "
						<td nowrap>$titulo</td>
					";

					if (is_array($observacao)) {
						$peca    = array();
						$produto = array();
						foreach ($observacao as $obs) {
							if (!empty($obs)) {
								$peca_produto = explode("|", $obs);

								$peca[]       = $peca_produto[0];
								$produto[]    = $peca_produto[1];

								unset($peca_produto);
							}
						}
						
						$resultado .= "
							<td align='left' nowrap>". implode("<br>", $peca)    ."</td>
							<td align='left' nowrap>". implode("<br>", $produto) ."</td>
						";
					} else {
						$resultado .= "<td align='left' nowrap>$observacao</td><td></td>";
					}

					$resultado .= "
						<td nowrap>$referencia_fabrica</td>
						<td nowrap>$medioCR</td>
					";

					if ($_POST['satisfacao_90']) {
						$resultado .= "
										<td nowrap>$revenda_estado</td>
										<td nowrap>$revenda_cidade</td>
										<td nowrap>$consumidor_estado</td>
										<td nowrap>$consumidor_cidade</td>
										<td nowrap>$chave_nf</td>
										<td nowrap>$numero_ad</td>
										<td nowrap>$numero_coleta</td>
									  ";
					}


				}

				$resultado .= "</tr>";
			}
			$resultado .= "</table>";

			echo $resultado;

			$excel = ob_get_contents();
			$excel = str_replace("class='tabela'", "border='1'", $excel);
			$caminho = "xls/relatorio-laudo-tecnico-$login_fabrica-".date('Y-m-d').".xls";
			$fp = fopen($caminho, "w");
			fwrite($fp, $excel);
			fclose($fp);
			echo"<table width='200' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>";
				echo"<tr>";
					echo "<td align='left' valign='absmiddle'><a href='$caminho' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a></td>";
				echo "</tr>";
			echo "</table>";

		}else{
			echo "<center>Nenhum resultado encontrado</center>";
		}

	}
include "rodape.php";
?>
