<?php
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
					$cnpj = trim(pg_result($res,$i,'cnpj'));
					$nome = trim(pg_result($res,$i,'nome'));
					$codigo_posto = trim(pg_result($res,$i,'codigo_posto'));
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
		echo "<script>alert('Baixas efetuadas com sucesso'); window.location.href = 'relatorio_ressarcimento.php'; </script>";
	}
}

if (strlen($_POST['btn_acao'])>0) {
	$posto = trim($_POST['codigo_posto']);
	$posto_descricao = trim($_POST['posto_nome']);
	$os = trim($_POST['os']);

	if (strlen($os)>0) {
		$sql_os = " AND os = $os";
	}

	if (strlen($posto)>0) {
		$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

		if (pg_num_rows($res)>0) {
			$posto = pg_result($res,0,0);
			$sql_posto = " AND tbl_posto_fabrica.posto = $posto ";
		}else{
			$msg_erro = 'Posto não Encontrado';
		}
	}
	
	if (strlen($posto_descricao)>0){
		$sql = "SELECT posto FROM tbl_posto_fabrica join tbl_posto using(posto) where tbl_posto.nome = '$posto_descricao' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

		if (pg_num_rows($res)>0) {
			$posto = pg_result($res,0,0);
			$sql_posto = " AND tbl_posto_fabrica.posto = $posto ";
		}else{
			$msg_erro = 'Posto não Encontrado';
		}
	}
}

$layout_menu = "callcenter";
$title = "Relatório Atualizacao de Postos";
include "cabecalho.php";

include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 
include "javascript_pesquisas.php";
?>
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
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});
})

</script>
<style>
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
    border-collapse: collapse;
    border:1px solid #596d9b;
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
}
.espaco{
	padding-left:130px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
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
</style>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
	<div align="center" class="texto_avulso">Relatório de Atualização de Dados dos Postos, data base: 09/06/2010 09:36</div>
	<br>
	<?php if(strlen($msg_erro) > 0):?>
		<div align="center">
			<div style="width:700px" class="msg_erro"><?php echo $msg_erro;?></div>
		</div>
	<?php endif;?>
	<table align="center" class="formulario" width="700" border="0">
		<caption class="titulo_tabela">Parâmetros de Pesquisa</caption>

		<tbody>
			<tr>
				<td width="100">&nbsp;</td>
				<td align="left">
					Código Posto<br/>
					<input type="text" name="codigo_posto" id="codigo_posto" size="20" value="<? echo $codigo_posto ?>" class="frm">
					<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="fnc_pesquisa_posto(document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome,'codigo');">
				</td>
				<td align="left">
					Nome do Posto<br/>
					<input type="text" name="posto_nome" id="posto_nome" size="40" value="<? echo $posto_nome ?>" class="frm">
					<img src="imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="fnc_pesquisa_posto(document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome,'nome');">
				</td>
				<td width="10">&nbsp;</td>
			</tr>
		</tbody>

		<tr>
			<td colspan="4" align="center">
				<br/>
				<input type="hidden" name="btn_acao" value="">
				<input type="button" value="Pesquisar" onclick="if (document.frm_relatorio.btn_acao.value == '') { document.frm_relatorio.btn_acao.value='Pesquisar'; document.frm_relatorio.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
				<br>
				<br>
			</td>
		</tr>
	</table>
	<?php
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
			
			echo '<table align="center" width="700" cellspacing="1" class="tabela">';
			echo '<tr class="titulo_coluna">';
			echo "<td>Código Posto</td>";
			echo "<td>Nome do Posto</td>";
			echo "<td>Cidade/UF</td>";
			echo "<td>Telefone</td>";
			echo "<td>Atualizou?</td>";
			echo "</tr>";

			for ($i=0; $i < pg_numrows($res); $i++) {
				
				$codigo_posto = pg_result($res,$i,'codigo_posto');
				$nome = pg_result($res,$i,'nome');
				$cidade = pg_result($res,$i,'cidade');
				$uf = pg_result($res,$i,'estado');
				$atualizacao = pg_result($res,$i,'atualizacao');
				$telefone = pg_result($res,$i,'contato_fone_comercial');
				
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
	?>
</form>
<br />
<? include "rodape.php" ?>