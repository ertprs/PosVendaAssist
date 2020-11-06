<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="callcenter";
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

$layout_menu = "callcenter";
$title = "Relatório Atualizacao de Postos";
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

<CAPTION>Relatório de Atualização de Dados dos Postos, data base: 09/06/2010 09:36</CAPTION>

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
			$sql = "SELECT	tbl_posto.nome,
							tbl_posto.cidade,
							tbl_posto.estado,
							tbl_posto_fabrica.atualizacao,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto_fabrica.contato_fone_comercial
						FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE 1 = 1 $sql_posto order by atualizacao";
			
			$res = pg_exec($con,$sql);
			//echo(nl2br($sql));
			if (pg_num_rows($res)>0) {
				echo"<br><br>";			
				if (strlen($msg_erro2)>0) {
					echo($msg_erro2); echo "<br>";
				}
				
				echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
				echo "<tr>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Codigo Posto</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>NOME Posto</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Cidade/UF</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Telefone</strong></font></td>";
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Atualizou?</strong></font></td>";
				echo "</tr>";

			 for ($i=0; $i < pg_numrows($res); $i++) {
				
				$codigo_posto = pg_result($res,$i,codigo_posto);
				$nome = pg_result($res,$i,nome);
				$cidade = pg_result($res,$i,cidade);
				$uf = pg_result($res,$i,estado);
				$atualizacao = pg_result($res,$i,atualizacao);
				$telefone = pg_result($res,$i,contato_fone_comercial);
				
				$sqlRes = "SELECT CASE WHEN '$atualizacao' <= '2010-06-09 09:36:39.548903' THEN 'não' ELSE 'sim' END";

				$resRes = pg_exec($con,$sqlRes);

				if(pg_num_rows($resRes)>0) {
					$resposta = pg_result($resRes,0,0);
				}

				$cores++;
				$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
				
				
				echo"<input type='hidden' name='hd_chamado_$i' value='$hd_chamado'>";
				echo"<tr bgcolor='$cor'>";
				echo"<td>$codigo_posto</td>";
				echo"<td>$nome</td>";
				echo"<td>$cidade-$uf</td>";
				echo"<td>$telefone</td>";
				echo"<td>$resposta</td>";
			 }
			echo"</table>";
			}
		}
	}
?>
</form>
