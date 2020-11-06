<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="auditoria";
include "autentica_admin.php";

include "funcoes.php";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	
	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}

if($_POST['baixar']) {

	$qtde = $_POST['qtde'];
	
	$res_os = pg_exec($con,"BEGIN TRANSACTION");
	for ($i=0; $i < $qtde; $i++) {
		
		$selecao = $_POST['selecao_'.$i];

		if (strlen($selecao)>0) {

			$selecionou = 'sim';

			$doc			= $_POST['comprovante_'.$i];
			$data_pagamento	= $_POST['data_pagamento_'.$i];
			$valor			= $_POST['valor_'.$i];
			$hd_chamado     = $_POST['hd_chamado_'.$i];
			if (strlen($doc)==0) {
				$linha = $i+1;
				$msg_erro2 .= 'Digite o número do comprovante na linha: '.$linha."<br>";
				$linha_erro = $i;
			}

			if (strlen($data_pagamento)==0) {
				$linha = $i+1;
				$msg_erro2 .= 'Digite a data de pagamento linha: '.$linha."<br>";
				$linha_erro = $i;
			} else {
				$data_pagamento2 = $data_pagamento;
				$data_pagamento = formata_data($data_pagamento);
			}

			if (strlen($valor)==0) {
				$linha = $i+1;
				$msg_erro2 .= 'Digite o valor: '.$linha."<br>";
				$linha_erro = $i;
			} else {
				 $valor = number_format($valor,2,'.','.');
			}

			if (strlen($msg_erro2)==0) {
				$sql = "UPDATE tbl_hd_chamado_troca set valor_corrigido = $valor, data_pagamento = '$data_pagamento', admin_ressarcimento = $login_admin where hd_chamado = $hd_chamado";
				$res = pg_exec($con,$sql);
				$msg_erro2 .= pg_errormessage($con);

				$sqlins = "INSERT INTO tbl_hd_chamado_item (
							hd_chamado,
							comentario,
							admin,
							status_item )
							VALUES (
							$hd_chamado,
							'O ressarcimento foi efetivado:<br> <b>Numero comprovante:</b>$doc<br><b>Valor:</b>$valor<br><b>Data do Pagamento:</b>$data_pagamento2',
							$login_admin,
							'Resolvido'
							)";
					$resins = pg_exec($con,$sqlins);
					$msg_erro2 .= pg_errormessage($con);

					$sql = "UPDATE tbl_hd_chamado set status = 'Resolvido' where hd_chamado = $hd_chamado";
					$res = pg_exec($con,$sql);
					$msg_erro2 .= pg_errormessage($con);
				
			}
		}
	}
	
	if (strlen($selecionou)==0) {
		$msg_erro2 .= 'Nenhum registro foi selecionado '."<br>";
		$linha_erro = 'nao';
	}

	if (strlen($msg_erro2)>0) {
		$res_os = pg_exec($con,"rollback");
		$_POST['btn_acao'] = 'Pesquisar';
	} else {
		$res_os = pg_exec($con,"commit");
		echo "<script>alert('Baixas efetuadas com sucesso'); window.location = 'relatorio_ressarcimento.php'; </script>";
	}
}

$layout_menu = "gerencia";
$title = "Relatório de devolução obrigatória";
include "cabecalho.php";

?>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>

<script language='javascript'>

	$(function(){
		$('.mask_date').datePicker({startDate:'01/01/2000'}).maskedinput("99/99/9999");
		$('input[id*=data]').datePicker({startDate:'01/01/2000'}).maskedinput("99/99/9999");
	});



$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
			
	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#nome_posto").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#nome_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome_posto").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});
})

</script>

<FORM NAME="frm_pesquisa" METHOD="POST" ACTION="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<CAPTION>Relatório de devolução obrigatória</CAPTION>

<TBODY>
	<TR>
		<TD>Código Posto<BR/>
		<INPUT TYPE="TEXT" NAME="codigo_posto" ID="codigo_posto" SIZE="15" VALUE="<? echo $codigo_posto ?>" CLASS="frm">
		</TD>
		<TD>Nome do Posto<BR/>
		<INPUT TYPE="TEXT" NAME="nome_posto" ID="nome_posto" SIZE="40" VALUE="<? echo $nome_posto ?>" CLASS="frm">
		</TD>
	</TR>
</TBODY>

<TR>
	<TD COLSPAN="2" ALIGN="CENTER">
		<BR/>
		<INPUT TYPE="HIDDEN" NAME="btn_acao" VALUE="">
		<IMG SRC="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" STYLE="cursor:pointer " ALT='Clique AQUI para pesquisar'>
	</TD>
</TR>
</TABLE>

<? 

	if (strlen($_POST['btn_acao'])>0) {
		
		if ($_POST['data_inicial']) {
			$data_inicial = formata_data($_POST['data_inicial']);
		}

		if ($_POST['data_final']) {
			$data_final = formata_data($_POST['data_final']);
		}


		$posto			= trim($_POST['codigo_posto']);
		$os				= trim($_POST['os']);
	
		if (strlen($os)>0) {
			$sql_os = " AND os = $os";
		}

		if (strlen($posto)>0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
			if (pg_num_rows($res)>0) {
				$posto = pg_result($res,0,0);
				$sql_posto = " AND tbl_posto_fabrica.posto = $posto ";
			}
		}

		if (strlen($msg_erro)==0) {
			$sql = "SELECT	faturamento,
							nota_fiscal,
							emissao,
							nome,
							codigo_posto,
							tbl_faturamento.distribuidor,
							tbl_faturamento_item.peca,
							descricao,
							tbl_faturamento_item.pedido,
							os,
							TO_CHAR(conferencia::date,'DD/MM/YYYY') AS conferencia,
							laudo_tecnico,
							TO_CHAR(data_scrap,'DD/MM/YYYY') AS data_scrap,
							tbl_faturamento.posto,
							tbl_faturamento_item.extrato_devolucao
						FROM tbl_faturamento 
						JOIN tbl_faturamento_item USING(faturamento)
						JOIN tbl_peca USING(peca)
						JOIN tbl_posto USING(posto)
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_os_extra USING(os)
						WHERE (tbl_faturamento.fabrica = $login_fabrica or tbl_faturamento.fabrica = 10)
						AND tbl_peca.fabrica = $login_fabrica
						AND tbl_peca.devolucao_obrigatoria is true
						AND tbl_faturamento.distribuidor = 4311
						$sql_posto
						AND tbl_faturamento_item.extrato_devolucao is not null order by conferencia;";

			$res = pg_exec($con,$sql);
			//echo $sql;
			if (pg_num_rows($res)>0) {
				echo"<br><br>";			
				if (strlen($msg_erro2)>0) {
					echo($msg_erro2); echo "<br>";
				}
				
				echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
				echo "<tr>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>OS</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>NF DEVOLUÇÃO</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>POSTO</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>PEÇA/PRODUTO</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DATA CONFERÊNCIA</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DATA SCRAP</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>LAUDO TÉCNICO</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>EXTRATO</strong></font></td>";
				echo "</tr>";

			 for ($i=0; $i < pg_numrows($res); $i++) {
				
				#$nota_fiscal = pg_result($res,$i,nota_fiscal);
				$codigo_posto = pg_result($res,$i,codigo_posto);
				$nome = pg_result($res,$i,nome);
				$laudo_tecnico = pg_result($res,$i,laudo_tecnico);
				$peca = pg_result($res,$i,peca);
				$descricao = pg_result($res,$i,descricao);
				$conferencia = pg_result($res,$i,conferencia);
				$os = pg_result($res,$i,os);
				$data_scrap = pg_result($res,$i,data_scrap);
				$extrato = pg_result($res,$i,extrato_devolucao);
				$posto = pg_result($res,$i,posto);
				

				$sql_devolveu = "SELECT	nota_fiscal, 
										TO_CHAR(conferencia::date,'DD/MM/YYYY') AS conferencia
								FROM tbl_faturamento 
									JOIN tbl_faturamento_item using(faturamento)
								WHERE tbl_faturamento_item.peca = $peca 
								AND tbl_faturamento_item.extrato_devolucao = $extrato
								AND tbl_faturamento.posto = 4311
								AND tbl_faturamento.distribuidor = $posto";

				$res_devolveu = pg_exec($con,$sql_devolveu);
				
				if (pg_num_rows($res_devolveu)>0) {
					$nota_fical_d = pg_result($res_devolveu,0,0);
					$conferencia_d = pg_result($res_devolveu,0,1);
				}

				$cores++;
				$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
				
				
				echo"<input type='hidden' name='hd_chamado_$i' value='$hd_chamado'>";
				echo"<tr bgcolor='$cor'>";
				echo"<td><a href='os_press.php?os=$os'>$os</a></td>";
				echo"<td>$nota_fical_d</td>";
				echo"<td align='left'>$nome</td>";
				echo"<td align='left'>$descricao</td>";
				echo"<td>$conferencia_d</td>";
				echo"<td>$data_scrap</td>";
				echo"<td>$laudo_tecnico</td>";
				echo"<td>$extrato</td>";
			 

			 	$nota_fical_d = "";
				$conferencia_d = "";

			 echo "</tr>";

		
				echo "</tr>";
			}
		echo"</table>";
		}
	}
	}
?>
</form>
