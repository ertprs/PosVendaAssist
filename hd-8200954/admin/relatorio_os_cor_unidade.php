<?

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	include "funcoes.php";

	$layout_menu = "Gerencia";
	$title = "RELATÓRIO DE OS POR COR DA UNIDADE";

	include "cabecalho.php";
	include "javascript_calendario.php";
	include "javascript_pesquisas.php";

	if ($_POST['Pesquisar'] == 'Pesquisar')
	{
		$data_inicial = $_POST['data_inicial'];
		$data_final   = $_POST['data_final'];
		$unidade_cor  = $_POST['unidade_cor'];

		if (empty($data_inicial) or empty($data_final))
		{
			$msg_erro .= "ERRO: Preencha os Campos Data <br>";
		}
		if (empty($unidade_cor))
		{
			$msg_erro .= "ERRO: Selecione um Tipo de Cor da Unidade <br>";
		}

		if (empty($msg_erro))
		{
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf) or !checkdate($mi,$di,$yi)) 
			{
				$msg_erro .= "ERRO: Data Inválida <br>";
			}

			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final   = "$yf-$mf-$df";

			if($aux_data_final < $aux_data_inicial and empty($msg_erro))
			{
                $msg_erro .= "ERRO: Data Inicial Maior que Data Final <br>";
			}
			else
			{
				$xdata_inicial = $aux_data_inicial." 00:00:00";
				$xdata_final   = $aux_data_final." 23:59:59";
			}

			if(!empty($aux_data_inicial) && !empty($aux_data_final) && empty($msg_erro))
			{
				$sql = "SELECT '$aux_data_inicial'::date + interval '1 months' > '$aux_data_final'";
				$res = pg_query($con,$sql);
				$periodo = pg_fetch_result($res,0,0);
				if($periodo == 'f')
					$msg_erro .= "ERRO: Data Inválida - Período Maior que um Mês <br>";
			}
		}

		if (empty($msg_erro))
		{

			if ($unidade_cor == 'amarelo')
			{
				$sql_cor = "AND tbl_os_campo_extra.cor_produto = 'amarelo' GROUP BY tbl_os_campo_extra.cor_produto";
			}
			else if ($unidade_cor == 'preto')
			{
				$sql_cor = "AND tbl_os_campo_extra.cor_produto = 'preto' GROUP BY tbl_os_campo_extra.cor_produto";
			}
			else if ($unidade_cor == 'todos')
			{
				$sql_cor = "GROUP BY tbl_os_campo_extra.cor_produto";
			}

			$sql = "SELECT
					tbl_os_campo_extra.cor_produto,
					count(*) AS unidades
					FROM tbl_os
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
					JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
					JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
					JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
					$sql_cor
					";
			$res       = pg_query($con,$sql);
			$msg_erro .= pg_last_error();
			$rows      = pg_num_rows($res);

			if ($rows == 1)
			{
				if ($unidade_cor == 'amarelo')
				{
					$unidade_amarela = pg_result($res,0,'unidades');
				}
				else if ($unidade_cor == 'preto')
				{
					$unidade_preta = pg_result($res,0,'unidades');
					
				}
			}
			else if ($rows > 1)
			{
				if (pg_result($res,0,'cor_produto') == 'amarelo')
				{
					$unidade_amarela = pg_result($res,0,'unidades');
					$unidade_preta = pg_result($res,1,'unidades');
				}
				else
				{
					$unidade_preta = pg_result($res,0,'unidades');
					$unidade_amarela = pg_result($res,1,'unidades');
				}
				
			}
			else
			{
				$msg_erro .= "Não foi encontrada nenhuma OS com esses parâmetros !";
			}
		}
	}

?>
	<script type="text/javascript">

		$().ready(function(){
			$("#data_inicial").datePicker({startDate:"01/01/2000"});
			$("#data_final").datePicker({startDate:"01/01/2000"});
			$("#data_inicial").maskedinput("99/99/9999");
			$("#data_final").maskedinput("99/99/9999");
		});

		function resultado(unidade_cor)
		{
			if (unidade_cor == "amarelo")
			{
				var unidade_cor_qtde = "<?=$unidade_amarela?>"; 
			}
			if (unidade_cor == "preto")
			{
				var unidade_cor_qtde = "<?=$unidade_preta?>";
			}

			if (unidade_cor_qtde > 0)
			{
				$('#resultado').hide();

				$.ajax({
					type: "POST",
					url : "ajax_relatorio_unidade_cor.php",
					data: "unidade_cor="+unidade_cor+"&data_inicial=<?=$xdata_inicial?>&data_final=<?=$xdata_final?>&fabrica=<?=$login_fabrica?>",
					success: function(data)
					{
						$('#resultado').html(data);
						$('#resultado').slideDown('slow');
					}
				});
			}
			else
			{
				if (unidade_cor == "amarelo")
				{
					alert("Não há OS's que possuem unidades \"amarelas\" para serem mostradas");
				}
				if (unidade_cor == "preto")
				{
					alert("Não há OS's que possuem unidades \"pretas\" para serem mostradas");
				}
			}
		}
	</script>

	<style type='text/css'>
		.formulario
		{
			background-color:#D9E2EF;
			font:11px "Arial";
			text-align:left;
		}
		.titulo_tabela
		{
			background-color:#596d9b;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		.subtitulo
		{
			background-color: #7092BE;
			font:bold 11px "Arial";
			color: #FFFFFF;
		}
		table.tabela tr td
		{
			font-family: "verdana";
			font-size: 9px;
			border-collapse: collapse;
			border:1px solid #596d9b;
		}
		.msg_erro
		{
			background-color:#FF0000;
			font: bold 16px "Arial";
			color:#FFFFFF;
			text-align:center;
			width: 700px;
		}
		.yellow
		{
			background-color: #FFAE00;
			width: 28px;
			height: 20px;
			color: black;
			text-align: center;
			cursor: pointer;
		}
		.black
		{
			background-color: #1E1E1E;
			width: 28px;
			height: 20px;
			color: white;
			text-align: center;
			cursor: pointer;
		}
		.todos
		{
			background: -moz-linear-gradient(left, #ffae00 50%, #1e1e1e 0%);
			background: -webkit-gradient(linear, left top, right top, color-stop(50%,#ffae00), color-stop(0%,#1e1e1e));
			background: -webkit-linear-gradient(left, #ffae00 50%,#1e1e1e 0%);
			background: -o-linear-gradient(left, #ffae00 50%,#1e1e1e 0%);
			background: -ms-linear-gradient(left, #ffae00 50%,#1e1e1e 0%);
			background: linear-gradient(left, #ffae00 50%,#1e1e1e 0%);
			filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffae00', endColorstr='#1e1e1e',GradientType=1 );
			width: 28px;
			height: 20px;
			color: white;
			text-align: center;
			cursor: pointer;
		}
	</style>

	<center>
		<div class="msg_erro" <? if(!empty($msg_erro)) echo "style='display: block;'"; else echo "style='display: none;'"; ?>>
			<?
				echo $msg_erro;
			?>
		</div>
	</center>
	<form name='frm_pesquisa' id='frm_pesquisa' method='POST' style="margin: 0 auto; width: 700px; background-color:#D9E2EF;">
		<table align="center" class="formulario" width="700" border="0" style='border-collapse: collapse;'>
			<tr class="titulo_tabela">
				<td>
					Parâmetros de Pesquisa
				</td>
			</tr>
		</table>
		<table border='0' width='500' align='center' class="formulario">
			<tr>
				<td align='left' colspan='2'>
					Data Inicial
				</td>
				<td align='left' colspan='2'>
					Data Final
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<input type="text" name="data_inicial" id="data_inicial" size="13" value="<?=$data_inicial?>">
				</td>
				<td colspan='2'>
					<input type="text" name="data_final" id="data_final" size="13" value="<?=$data_final?>">
				</td>
			</tr>
			<tr>
				<td class='yellow'>
					<input type='radio' name='unidade_cor' id='unidade_cor' value='amarelo' <? if($unidade_cor == 'amarelo') echo "CHECKED"; ?>>
				</td>
				<td align='left' nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Unidades Amarelas</font>
				</td>
			</tr>
			<tr>
				<td class='black'>
					<input type='radio' name='unidade_cor' id='unidade_cor' value='preto' <? if($unidade_cor == 'preto') echo "CHECKED"; ?>>
				</td>
				<td align='left' nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Unidades Pretas</font>
				</td>
			</tr>
			<tr>
				<td class='todos'>
					<input type='radio' name='unidade_cor' id='unidade_cor' value='todos' <? if(!in_array($unidade_cor, array('amarelo','preto'))) echo "CHECKED"; ?>>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Todos</font>
				</td>
			</tr>
			<tr>
				<td colspan='4' align='center'>
					<input type="submit" value="Pesquisar" name='Pesquisar' id='Pesquisar'>
				</td>
			</tr>
		</table>
	</form>
	<center>
	<div class='formulario' style='width: 700px; !important' id='pesquisa'>
		<table align="center" class="formulario" width="245" border="0">
			<tr>
				<th colspan='5' align='center'>
					Pesquisando por <? echo $unidade_cor ?>
				</th>
			</tr>
			<tr>
				<td colspan='5' align='center'>
					<font color='red' size="2"><b>Clique na caixa para ver as OS's !</b></font>
				</td>
			</tr>
			<tr>
				<td class='yellow' onclick="resultado('amarelo')" >
					<? 
						if ($unidade_amarela > 0)
						{
							echo "<b>" . $unidade_amarela . "</b>";
						}
						else
						{
							echo "<font color='#7A7A7A'>0</font>";
						}
					?>
				</td>
				<td align='left' nowrap width='38px'>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Unidades Amarelas</font>
				</td>
				<td class='black' onclick="resultado('preto')" >
					<? 
						if ($unidade_preta > 0)
						{
							echo "<b>" . $unidade_preta . "</b>";
						}
						else
						{
							echo "<font color='#7A7A7A'>0</font>";
						}
					?>
				</td>
				<td align='left' nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Unidades Pretas</font>
				</td>
			</tr>
		</table>
	</div>
	</center>
	<center>
	<div style='display: none;' id='resultado'>
	</div>
	</center>

<?

	include "rodape.php" 

?>