<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
$res = pg_exec ($con,"SELECT pedido_via_distribuidor FROM tbl_fabrica WHERE fabrica = $login_fabrica");
$pedido_via_distribuidor = pg_result ($res,0,0);

if (strlen($_GET["linha"]) > 0)  $linha = trim($_GET["linha"]);
if (strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);
if (strlen($_POST["multimarca"]) > 0) $multimarca = trim($_POST["multimarca"]);
if (strlen($_POST["btnacao"]) > 0) $btnacao = trim($_POST["btnacao"]);

if ($btnacao == "deletar" and strlen($linha) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	$sql = "DELETE FROM tbl_linha
			WHERE  tbl_linha.fabrica = $login_fabrica
			AND    tbl_linha.linha   = $linha;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strpos ($msg_erro,'linha_fk') > 0)              $msg_erro = "Esta linha já possui produtos cadastrados, e não pode ser excluida";
	if (strpos ($msg_erro,'tbl_defeito_reclamado') > 0) $msg_erro = "Esta linha já possui 'Defeitos Reclamados' cadastrados, e não pode ser excluida";
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$linha        = $_POST["linha"];
		$codigo_linha = $_POST["codigo_linha"];
		$nome         = $_POST["nome"];
		$marca        = $_POST["marca"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}
if ($btnacao == "gravar") {
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["codigo_linha"]) > 0) {
			$aux_codigo_linha = "'" . trim($_POST["codigo_linha"]) . "'" ;
		}else{
			$aux_codigo_linha = "null";
		}
	}
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["nome"]) > 0) {
			$aux_nome = "'". trim($_POST["nome"]) ."'";
		}else{
			$msg_erro = "Favor informar o nome da linha.";
		}
	}
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["marca"]) > 0) {
			$aux_marca = "'". trim($_POST["marca"]) ."'";
		}else{
			$aux_marca = "null";
		}
	}

	/*if (strlen($msg_erro) == 0) {
		if (strlen($_POST["marca"]) > 0) {
			$aux_marca = "'". trim($_POST["marca"]) ."'";
		}else{
			if (strlen($multimarca) > 0) {
				$msg_erro = "Selecione a marca para esta linha.";
			}else{
				$aux_marca = "null";
			}
		}
	}*/
	
	if (strlen($msg_erro) == 0) {
		$mao_de_obra_adicional_distribuidor = trim ($_POST['mao_de_obra_adicional_distribuidor']);
		$aux_mao_de_obra_adicional_distribuidor = $mao_de_obra_adicional_distribuidor;
		if (strlen ($aux_mao_de_obra_adicional_distribuidor) == 0) {
			if ($pedido_via_distribuidor == 't') {
				$aux_mao_de_obra_adicional_distribuidor = 0 ;
			}else{
				$aux_mao_de_obra_adicional_distribuidor = 'null' ;
			}
		}
		$aux_mao_de_obra_adicional_distribuidor = str_replace (",",".",$aux_mao_de_obra_adicional_distribuidor);

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($linha) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_linha (
						fabrica,
						codigo_linha,
						nome   ,
						marca  ,
						mao_de_obra_adicional_distribuidor
					) VALUES (
						$login_fabrica,
						$aux_codigo_linha,
						$aux_nome     ,
						$aux_marca    ,
						$aux_mao_de_obra_adicional_distribuidor
					);";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE  tbl_linha SET
							codigo_linha = $aux_codigo_linha,
							nome         = $aux_nome,
							marca        = $aux_marca,
							mao_de_obra_adicional_distribuidor = $aux_mao_de_obra_adicional_distribuidor
					WHERE   tbl_linha.fabrica =	$login_fabrica
					AND     tbl_linha.linha   = $linha;";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$linha        = $_POST["linha"];
		$codigo_linha = $_POST["codigo_linha"];
		$nome         = $_POST["nome"];
		$marca        = $_POST["marca"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


###CARREGA REGISTRO
if (strlen($linha) > 0) {
	$sql =	"SELECT tbl_linha.linha                              ,
					tbl_linha.codigo_linha                       ,
					tbl_linha.nome                               ,
					tbl_linha.marca                              ,
					tbl_linha.mao_de_obra_adicional_distribuidor 
			FROM      tbl_linha
			LEFT JOIN tbl_marca on tbl_marca.marca = tbl_linha.marca
			WHERE     tbl_linha.fabrica = $login_fabrica
			AND       tbl_linha.linha   = $linha;";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$linha        = trim(pg_result($res,0,linha));
		$codigo_linha = trim(pg_result($res,0,codigo_linha));
		$nome         = trim(pg_result($res,0,nome));
		$marca        = trim(pg_result($res,0,marca));
		$mao_de_obra_adicional_distribuidor = trim(pg_result($res,0,mao_de_obra_adicional_distribuidor));
	}
}

$visual_black = "manutencao-admin";
$layout_menu = "cadastro";
$title = "Cadastro de Linha de Produto";
include 'cabecalho.php';
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff;
}
.titulo {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}
</style>

<form name="frm_linha" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="linha" value="<? echo $linha ?>">
<input type="hidden" name="multimarca" value="<? echo $multimarca ?>">

<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellpadding="2" cellspacing="1" class="error" align='center'>
	<tr>
		<td><?echo $msg_erro;?></td>
	</tr>
</table>
<? } ?>
<table width="600" border="0" cellpadding="2" cellspacing="1" align='center'>
	<tr  bgcolor="#596D9B" >
		<td align='left'><font size='2' color='#ffffff'>Cadastro de Linha</font></td>
	</tr>
</table>
<table width="600" border="0" bgcolor='#D9E2EF' cellpadding="2" cellspacing="1" class="titulo"  align='center'>

	<tr>
		<td>Código da Linha</td>

		<td>Nome da Linha</td>

		<? if ($multimarca == 't') { ?>
		<td>Marca</td>
		<? } ?>

		<? if ($pedido_via_distribuidor == 't') { ?>
		<td>Mão-de-Obra adicional para Distribuidor</td>
		<? } ?>

	</tr>
	<tr>
		<td>
			<input type="text" name="codigo_linha" value="<? echo $codigo_linha ?>" size="10" maxlength="10" class="frm">
		</td>
		<td>
			<input type="text" name="nome" value="<? echo $nome ?>" size="40" maxlength="50" class="frm">
		</td>
		<?
		if ($multimarca == 't') {
			echo "<td ALIGN='LEFT' class='line_list'>";

			$sql = "SELECT  tbl_marca.marca              ,
							tbl_marca.nome AS nome_marca
					FROM    tbl_marca
					WHERE   tbl_marca.fabrica = $login_fabrica
					ORDER BY tbl_marca.nome;";
			$res = @pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select class='frm' style='width: 280px;' name='marca' class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_marca      = trim(pg_result($res,$x,marca));
					$aux_nome_marca = trim(pg_result($res,$x,nome_marca));
					
					echo "<option value='$aux_marca'"; if ($marca == $aux_marca) echo " SELECTED "; echo ">$aux_nome_marca</option>\n";
				}
				echo "</select>\n";
			}
			echo "</td>";
		}

		if ($pedido_via_distribuidor == 't') {
			echo "<td ALIGN='LEFT'>";
			echo "<input type='text' name='mao_de_obra_adicional_distribuidor' value='$mao_de_obra_adicional_distribuidor' size='10' maxlength='10' class='frm'>";
			echo "</td>";
		}
		?>
	</tr>
</table>

<br>

<center>
<input type='hidden' name='btnacao' value=''>
<img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_linha.btnacao.value == '' ) { document.frm_linha.btnacao.value='gravar' ; document.frm_linha.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" style="cursor: pointer;">
<img border="0" src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_linha.btnacao.value == '' ) { document.frm_linha.btnacao.value='deletar' ; document.frm_linha.submit() } else { alert ('Aguarde submissão') }" alt="Apagar Linha" style="cursor: pointer;">
<img border="0" src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_linha.btnacao.value == '' ) { document.frm_linha.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos" style="cursor: pointer;">
</center>

</form>


<?
if(strlen($linha) > 0) {

	$sql =	"SELECT   tbl_linha.codigo_linha AS codigo_linha ,
					  tbl_linha.nome         AS nome_linha   ,
					  tbl_produto.produto                    ,
					  tbl_produto.referencia                 ,
					  tbl_produto.descricao                  
			FROM      tbl_produto
			LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE     tbl_linha.fabrica = $login_fabrica
			AND       tbl_produto.linha = $linha
			ORDER BY  tbl_produto.descricao;";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0){
		echo "<table width='400' border='0' cellpadding='2' cellspacing='1' class='titulo'  align='center'>";
		echo "<tr bgcolor='#D9E2EF'>";
		echo "<td colspan='2'><center>Produtos da Linha ".@pg_result($res,0,nome_linha)."</center></td>";
		echo "</tr>";

		for ($i = 0 ; $i < @pg_numrows($res) ; $i++){
			$produto       = trim(@pg_result($res,$i,produto));
			$referencia    = trim(@pg_result($res,$i,referencia));
			$descricao     = trim(@pg_result($res,$i,descricao));

			$cor = ($i % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>";
			
			echo "<td width='20%'>$referencia</td>";
			echo "<td width='80%'><a href='produto_cadastro.php?produto=$produto'>$descricao</a></td>";
			
			echo "</tr>";
		}
		echo "</table>";
	}else if (pg_numrows($res) == 0){
		echo "<font size='2' face='Verdana, Tahoma, Arial' color='#D9E2EF'><b>ESTA LINHA NÃO POSSUI PRODUTOS CADASTRADOS<b></font>";
	}
}
?>

<br>

<?
$sql = "SELECT    tbl_linha.linha                        ,
				  tbl_linha.codigo_linha AS codigo_linha ,
				  tbl_linha.nome         AS nome_linha   ,
				  tbl_linha.marca                        ,
				  tbl_marca.nome         AS nome_marca   
		FROM      tbl_linha
		LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_linha.marca
		WHERE     tbl_linha.fabrica = $login_fabrica
		ORDER BY  tbl_marca.nome, tbl_linha.nome;";
$res = @pg_exec ($con,$sql);

echo "<table width='400' border='0' cellpadding='2' cellspacing='1' class='titulo' align='center'>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td colspan='2'><center>RELAÇÃO DAS LINHAS CADASTRADAS</center></td>";

if ($multimarca == 't') {
	echo "<td><center>MARCA</center></td>";
}

echo "</tr>";

for ($i = 0 ; $i < pg_numrows($res) ; $i++){
	$linha          = trim(pg_result($res,$i,linha));
	$codigo_linha   = trim(pg_result($res,$i,codigo_linha));
	$nome_linha     = trim(pg_result($res,$i,nome_linha));
	$marca          = trim(pg_result($res,$i,marca));
	$nome_marca     = trim(pg_result($res,$i,nome_marca));

	$cor = ($i % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

	echo "<tr bgcolor='$cor'>";

	echo "<td>$codigo_linha</td>";
	echo "<td><a href='$PHP_SELF?linha=$linha'>$nome_linha</a></td>";

	if ($multimarca == 't') {
		echo "<td>$nome_marca</td>";
	}

	echo "</tr>";
}
echo "</table>";


echo "<BR><BR><BR>";
echo "<table width='500' border='0' cellspacing='2' cellpadding='3' align='center' style='font-family: verdana; font-size: 12px'>";
echo "<TR>";
echo "<TD align='center'>
<a href='linha_cadastro-tk.php'>Linha</a><BR>
<a href='familia_cadastro-tk.php'>Familia</a><BR>
<a href='defeito_reclamado_cadastro-tk.php'>Defeito Reclamado</a><BR>
<a href='defeito_constatado_cadastro-tk.php'>Defeito Constatado</a><BR>
<a href='solucao-tk.php'>Solução</a><BR>
<a href='relacionamento_diagnostico-tk.php'>Diagnostico</a><BR>";
echo "</TD>";
echo "</TR>";
echo "</TABLE>";

echo "<br>";

include "rodape.php";
?>