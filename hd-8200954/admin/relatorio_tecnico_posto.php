<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';

	$layout_menu = "callcenter";
	$title = "Relatório de Pesquisa";

	include "cabecalho.php";

	$btn_acao = @$_POST['btn_acao'];
	if(!empty($btn_acao)){
		$posto_codigo = $_POST['posto_codigo'];
		$posto_nome   = $_POST['posto_nome'];

		if(!empty($posto_codigo)){
			$sql = "SELECT 
							tbl_posto.posto, 
							tbl_posto_fabrica.codigo_posto, 
							tbl_posto.nome 
						FROM tbl_posto_fabrica 
							JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto 
						WHERE tbl_posto_fabrica.codigo_posto = '{$posto_codigo}' AND tbl_posto_fabrica.fabrica = {$login_fabrica};";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) == 1){
				$posto = pg_fetch_result($res, 0, 'posto');
				$posto_codigo = pg_fetch_result($res, 0, 'codigo_posto');
				$posto_nome = pg_fetch_result($res, 0, 'nome');
			}else{
				$msg_erro = "Posto não encontrado!";
			}
		}
	}
?>
<style type="text/css">
	.menu_top {
	    text-align: center;
	    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	    font-size: 9px;
	    font-weight: bold;
	    border: 1px solid;
	    color:#ffffff;
	    background-color: #596D9B;
	}

	.border {
		border: 1px solid #ced7e7;
	}

	.table_line {
	    text-align: left;
	    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	    font-size: 9px;
	    font-weight: normal;
	    border: 0px solid;
	    background-color: #D9E2EF;
	}

	.table_line2 {
	    text-align: left;
	    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	    font-size: 9px;
	    font-weight: normal;
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
		margin: 0 auto;
		width: 700px;
		padding: 2px 0;
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

	.tabela{
		margin: 20px; auto;

	}
</style>

	<form method='POST' name='frm_posto' action='<?php echo $PHP_SELF; ?>'>
		<?php 
			if(!empty($msg_erro))
				echo "<div class='msg_erro'>{$msg_erro}</div>";
		?>
		<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario'>
			<tr class='titulo_tabela'>
				<td width='175px' colspan='4'><?php echo $title; ?></td>
			</tr>
			<tr>
				<td width='100px'>&nbsp;</td>
				<td width='200px'>&nbsp;</td>
				<td width='300px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					Código do Posto<br />
					<input class="frm" type="text" id="posto_codigo" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">
					<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_posto.posto_codigo, 'codigo');">
				</td>
				<td>
					Nome do Posto<br />
					<input class="frm" id="posto_nome" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>">
					<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_posto.posto_nome, 'nome');">
				</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td colspan='4' style='padding: 20px; text-align: center'>
					<input type='submit' value=' Pesquisar ' name='btn_acao' />
				</td>
			</tr>
		</table>	
	</form>

	<?php
		if(!empty($btn_acao) AND empty($msg_erro)){
			$xls = "";
			
			$posto = !empty($posto) ? " AND tbl_tecnico.posto = {$posto} " : "";		

			$sql = "SELECT
							tbl_tecnico.nome 								 AS nome_tecnico	,
							tbl_tecnico.cpf 													,
							to_char(tbl_tecnico.data_admissao, 'dd/mm/yyyy') AS data_admissao	,
							tbl_tecnico.formacao 												,
							to_char(tbl_tecnico.data_conclusao, 'dd/mm/yyyy') AS data_conclusao ,
							tbl_tecnico.email 													,
							tbl_tecnico.telefone 												,
							tbl_tecnico.ramal 													,
							tbl_tecnico.ativo 													,
							tbl_tecnico.linhas 													,
							tbl_posto_fabrica.codigo_posto										,
							tbl_posto.nome 								
						FROM tbl_tecnico
							JOIN tbl_posto ON tbl_posto.posto = tbl_tecnico.posto
							JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						WHERE tbl_tecnico.ativo {$posto}
							AND tbl_tecnico.fabrica = {$login_fabrica}
						ORDER BY tbl_posto.posto, tbl_tecnico.nome;";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res)){ 
				$arquivo_nome = "relatorio_tecnico_posto-$login_fabrica-$ano-$mes.xls";
				$arquivo_nome.= ($login_fabrica == 59) ? 'xls':'txt';

				$path             = 'xls/';
				$path_tmp         = '/tmp/assist/';		

			    $sql_linha = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome ASC;";
				$res_linha = pg_query($con, $sql_linha);
				$total_linha = pg_num_rows($res_linha);
				
				$titulo_coluna = " style='background-color:#596d9b; font: bold 11px \"Arial\";color:#FFFFFF; text-align:center;' ";

				$xls .= "<table class='tabela' cellpadding='1' cellpadding='1' border='0'>"; 
					$xls .= "<tr>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;Código do Posto&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;Nome do Posto&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;Nome do Técnico&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;CPF&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;Data de Admissão&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;Formação&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;Data de Conclusão&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;E-mail&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;Telefone&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;Ramal&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} rowspan='2' nowrap>&nbsp;Status&nbsp;</td>";
						$xls .= "<td {$titulo_coluna} colspan='{$total_linha}' nowrap>Linhas que o técnico atende</td>";
					$xls .= "</tr>";
					$xls .= "<tr>";
						for($i = 0; $i < $total_linha; $i++){
							$nome  = pg_fetch_result($res_linha, $i, 'nome');
							$linha = pg_fetch_result($res_linha, $i, 'linha');

							$linha_fabrica[$i] = $linha;

							$xls .= "<td {$titulo_coluna} nowrap>&nbsp;{$nome}&nbsp;</td>";
						}
					$xls .= "</tr>";

					function verificaLinhaTecnico($linha = null, $linhas = null){
						if($linha == null OR $linhas == null)
							return false;

						$linhas = explode(",",preg_replace("/{|}/", "", $linhas));

						
						return in_array($linha, $linhas) ? " SIM " : "";
					}

					for($i = 0; $i < pg_num_rows($res); $i++){
						extract(pg_fetch_array($res));
						$ativo = ($ativo == true) ? "Ativo" : "Inativo";
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

						$dados_style = " style = 'background-color: {$cor}; padding: 0 3px; border-bottom: 1px solid #999;' ";

						$xls .= "<tr >";
							$xls .= "<td {$dados_style} nowrap>{$codigo_posto}</td>";
							$xls .= "<td {$dados_style} nowrap>{$nome}</td>";
							$xls .= "<td {$dados_style} nowrap>{$nome_tecnico}</td>";
							$xls .= "<td {$dados_style} nowrap>{$cpf}</td>";
							$xls .= "<td {$dados_style} nowrap>{$data_admissao}</td>";
							$xls .= "<td {$dados_style} nowrap>{$formacao}</td>";
							$xls .= "<td {$dados_style} nowrap>{$data_conclusao}</td>";
							$xls .= "<td {$dados_style} nowrap>{$email}</td>";
							$xls .= "<td {$dados_style} nowrap>{$telefone}</td>";
							$xls .= "<td {$dados_style} nowrap>{$ramal}</td>";
							$xls .= "<td {$dados_style} nowrap>{$ativo}</td>";

							foreach ($linha_fabrica as $linha) {
								$xls .= "<td {$dados_style} nowrap>".verificaLinhaTecnico($linha, $linhas)."</td>";	
							}
							
						$xls .= "</tr>";
					}

				$xls .= "</table>"; 

				echo $xls;

				if(!empty($xls)){
					$file = "xls/relatorio_tecnico_posto_$login_admin.xls";
					$arquivo = fopen($file, "w+");
					fwrite($arquivo, $xls);
					fclose($arquivo);
					
					if(file_exists($file)){
						echo "<br>";
						echo "<a href='{$file}' target='_blank' style='font-size: 14px;'>
								<img src='imagens/excell.gif'> Clique aqui para download do relatório em Excel
							  </a>";
						echo "<br><br>";
						echo "<script>window.location='{$file}';</script>";	
					}
				}

			}

		}
	?>
	<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
	<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
	<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
	<script type="text/javascript">
		$().ready(function(){
			Shadowbox.init();
		});
		
		function pesquisaPosto(campo,tipo){
				var campo = campo.value;

				if (jQuery.trim(campo).length > 2){
					Shadowbox.open({
						content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
						player:		"iframe",
						title:		"Pesquisa Posto",
						width:		800,
						height:		500
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
<?php
	include "rodape.php";
?>
