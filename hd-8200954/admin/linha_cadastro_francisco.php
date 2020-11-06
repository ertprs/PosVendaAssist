<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
//ESTÁ EM TESTE PARA A TECTOY 27/09/06 TAKASHI
if($login_fabrica=='6'){
include "linha_cadastro_new.php";
exit;
}
include 'funcoes.php';


$res = pg_exec ($con,"SELECT pedido_via_distribuidor FROM tbl_fabrica WHERE fabrica = $login_fabrica");
$pedido_via_distribuidor = pg_result ($res,0,0);


if (strlen($_GET["linha"]) > 0)       $linha      = trim($_GET["linha"]);
if (strlen($_POST["linha"]) > 0)      $linha      = trim($_POST["linha"]);
if (strlen($_POST["multimarca"]) > 0) $multimarca = trim($_POST["multimarca"]);
if (strlen($_POST["btnacao"]) > 0)    $btnacao    = trim($_POST["btnacao"]);

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

if ($btnacao == "gravar" AND $login_fabrica==10) {
	if (strlen($_POST["codigo_linha"]) > 0) $aux_codigo_linha = "'" . trim($_POST["codigo_linha"]) . "'" ;
	else                                    $aux_codigo_linha = "null";
	if (strlen($_POST["nome"]) > 0)         $aux_nome         = "'". trim($_POST["nome"]) ."'";
	else                                    $msg_erro         = "Favor informar o nome da linha.";
	if (strlen($_POST["marca"]) > 0)        $aux_marca        = "'". trim($_POST["marca"]) ."'";
	else                                    $aux_marca        = "null";
	if (strlen($_POST["ativo"]) > 0)        $aux_ativo        = "TRUE";
	else                                    $aux_ativo        = "FALSE";
	
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
						mao_de_obra_adicional_distribuidor,
						ativo  ,
					) VALUES (
						$login_fabrica,
						$aux_codigo_linha,
						$aux_nome     ,
						$aux_marca    ,
						$aux_mao_de_obra_adicional_distribuidor,
						$aux_ativo    ,
					);";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE  tbl_linha SET
					codigo_linha = $aux_codigo_linha,
					nome         = $aux_nome,
					marca        = $aux_marca,
					ativo        = $aux_ativo,
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
				tbl_linha.mao_de_obra_adicional_distribuidor ,
				tbl_linha.ativo								 ,
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
		$ativo        = trim(pg_result($res,0,ativo));
		$mao_de_obra_adicional_distribuidor = trim(pg_result($res,0,mao_de_obra_adicional_distribuidor));
	}
}

$visual_black = "manutencao-admin";
$layout_menu = "cadastro";
$title = "Cadastro de Linha de Produto";
if(!isset($semcab))include 'cabecalho.php';
?>

<style type="text/css">
	.Label{
	font-family: Verdana;
	font-size: 10px;
	}
	.Titulo{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	}
	.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
	}
</style>

<form name="frm_linha" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">
<input type="hidden" name="linha" value="<? echo $linha ?>">
<input type="hidden" name="multimarca" value="<? echo $multimarca ?>">

<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellpadding="2" cellspacing="1" class="error" align='center'>
	<tr>
		<td><?echo $msg_erro;?></td>
	</tr>
</table>
<? } ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
		<tr  bgcolor="#596D9B" >
			<td align='left' colspan='4'><font size='2' color='#ffffff'>Cadastro de Linha</font></td>
		</tr>
		<tr class='Label'>
			<td nowrap >Código da Linha </td>
			<td><input type="text" name="codigo_linha" value="<? echo $codigo_linha ?>" size="10" maxlength="10" class="frm"></td>
			<td nowrap >Nome da Linha </td>
			<td><input type="text" name="nome" value="<? echo $nome ?>" size="30" maxlength="50" class="frm"></td>
		</tr>
			<?
			if ($multimarca == 't') {
				echo "<tr class='Label'>";
				echo "<td>Marca</td>";
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
				echo "</tr>";
			}
			 if ($pedido_via_distribuidor == 't') {
				echo "<tr class='Label'>";
				echo "<td>Mão-de-Obra adicional para Distribuidor</td>";
				echo "<td ALIGN='LEFT' class='line_list'>";
				echo "<input type='text' name='mao_de_obra_adicional_distribuidor' value='$mao_de_obra_adicional_distribuidor' size='10' maxlength='10' class='frm'>";
				echo "</td>";
				echo "</tr>";
			} ?>
		<tr class='Label'>
			<td nowrap >Ativo</td>
			<td colspan='3'><input type='checkbox' name='ativo' id='ativo' value='TRUE' <?if($ativo == 't') echo "CHECKED";?>></td>
		</tr>

		</table>
	</td>
</tr>
</table>


<center>
<input type='hidden' name='btnacao' value=''>

<? if ($login_fabrica==10){ ?>
	<img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_linha.btnacao.value == '' ) { document.frm_linha.btnacao.value='gravar' ; document.frm_linha.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" style="cursor: pointer;">
	<img border="0" src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_linha.btnacao.value == '' ) { document.frm_linha.btnacao.value='deletar' ; document.frm_linha.submit() } else { alert ('Aguarde submissão') }" alt="Apagar Linha" style="cursor: pointer;">
	<img border="0" src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_linha.btnacao.value == '' ) { document.frm_linha.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos" style="cursor: pointer;">
	</center>
<?}else{
	echo "<p>A Manutenção das Linhas é feito pelo Suporte Telecontrol</p>";
}
?>

</form>


<?
if(strlen($linha) > 0) {

	$sql =	"SELECT   tbl_linha.codigo_linha AS codigo_linha ,
					  tbl_linha.nome         AS nome_linha   ,
					  tbl_produto.produto                    ,
					  tbl_produto.referencia                 ,
					  tbl_produto.descricao                  ,
					  tbl_produto.ativo
			FROM      tbl_produto
			LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE     tbl_linha.fabrica = $login_fabrica
			AND       tbl_produto.linha = $linha
			ORDER BY  tbl_produto.descricao;";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0){
		echo "<table width='500' border='0' cellpadding='2' cellspacing='1' class='titulo'  align='center' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
		echo "<tr bgcolor='#D9E2EF'>";
		echo "<td colspan='3'><b>Produtos da Linha ".@pg_result($res,0,nome_linha)."</b></td>";
		echo "</tr>";

		for ($i = 0 ; $i < @pg_numrows($res) ; $i++){
			$produto       = trim(@pg_result($res,$i,produto));
			$referencia    = trim(@pg_result($res,$i,referencia));
			$descricao     = trim(@pg_result($res,$i,descricao));
			$ativo         = trim(@pg_result($res,$i,ativo));
			if($ativo=='t') $ativo = "<img src='imagens/status_verde.gif'> Ativo";
			else            $ativo = "<img src='imagens/status_vermelho.gif'> Inativo";

			$cor = ($i % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

			echo "<tr bgcolor='$cor' class='Label'>";
			echo "<td width='100' align='left'>$referencia</td>";
			echo "<td align='left'><a href='produto_cadastro.php?produto=$produto'>$descricao</a></td>";
			echo "<td width='60' align='left'>$ativo</td>";
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
				  tbl_linha.ativo                        ,
				  tbl_marca.nome         AS nome_marca   
		FROM      tbl_linha
		LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_linha.marca
		WHERE     tbl_linha.fabrica = $login_fabrica
		ORDER BY  tbl_linha.ativo , tbl_marca.nome,tbl_linha.nome;";
$res = @pg_exec ($con,$sql);

echo "<table width='500' border='0' cellpadding='2' cellspacing='1' class='titulo' align='center' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td colspan='3'><b>RELAÇÃO DAS LINHAS CADASTRADAS</b></td>";

if ($multimarca == 't') {
	echo "<td><center>MARCA</center></td>";
}

echo "</tr>";

for ($i = 0 ; $i < pg_numrows($res) ; $i++){
	$linha          = trim(pg_result($res,$i,linha));
	$codigo_linha   = trim(pg_result($res,$i,codigo_linha));
	$nome_linha     = trim(pg_result($res,$i,nome_linha));
	$marca          = trim(pg_result($res,$i,marca));
	$ativo          = trim(pg_result($res,$i,ativo));
	$nome_marca     = trim(pg_result($res,$i,nome_marca));

	$cor = ($i % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

	if($ativo=='t') $ativo = "<img src='imagens/status_verde.gif'> Ativo";
	else            $ativo = "<img src='imagens/status_vermelho.gif'> Inativo";

	echo "<tr bgcolor='$cor' class='Label'>";

	echo "<td align='left' width='100'>$codigo_linha</td>";
	echo "<td align='left'><a href='$PHP_SELF?linha=$linha";if(isset($semcab))echo "&semcab=yes";echo " '>$nome_linha</a></td>";
	echo "<td align='left' width='60'>$ativo</td>";

	if ($multimarca == 't') {
		echo "<td>$nome_marca</td>";
	}

	echo "</tr>";
}
echo "</table>";

echo "<br>";

if(!isset($semcab))include "rodape.php";
?>