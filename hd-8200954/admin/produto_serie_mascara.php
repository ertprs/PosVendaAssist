<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

if($login_fabrica == 99){
	header ("Location: produto_serie_mascara_2.php");
	exit;
}

if(isset($_GET['excluir']) && $_GET['excluir'] == "sim"){

	$linha 		= $_GET['linha'];
	$familia 	= $_GET['familia'];
	$produto 	= $_GET['produto'];
	$referencia = $_GET['referencia'];
	$mascara 	= $_GET['mascara'];

	if(!empty($linha)){
		$desc = "Linha ".$linha;
		$sql = "DELETE FROM tbl_produto_valida_serie WHERE linha = $linha AND mascara = '$mascara' AND fabrica = $login_fabrica";
	}

	if(!empty($familia)){
		$desc = "Família ".$familia;
		$sql = "DELETE FROM tbl_produto_valida_serie WHERE familia = $familia AND mascara = '$mascara' AND fabrica = $login_fabrica";
	}

	if(!empty($produto)){
		$desc = "Produto ".$referencia;
		$sql = "DELETE FROM tbl_produto_valida_serie WHERE produto = $produto AND mascara = '$mascara' AND fabrica = $login_fabrica";
	}

	$res = pg_query($con, $sql);

	if(pg_affected_rows($res) > 0){
		$msg = "Mascara {$mascara} - {$desc} excluida com Sucesso!";
	}

	header ("Location: $PHP_SELF?msg={$msg}");
	exit;

}

$btnacao = trim($_REQUEST["btnacao"]);

if(strlen($_POST['produto'])>0) $produto = $_POST['produto'];
else                            $produto = $_GET['produto'];

if(strlen($_POST['mascara'])>0) $mascara = $_POST['mascara'];
else                            $mascara = $_GET['mascara'];

$referencia = $_POST['referencia'];
$descricao  = $_POST['descricao'];
$mascara2   = $_POST['mascara2'];

if ($btnacao == "deletar") {

	if(empty($produto)){

			$sqlR = "SELECT tbl_produto.produto
					   FROM tbl_produto
				  LEFT JOIN tbl_produto_valida_serie USING(produto)
					   JOIN tbl_linha                USING(linha)
					  WHERE tbl_linha.fabrica      = $login_fabrica
						AND tbl_produto.referencia = '$referencia'
				;";
			#echo nl2br($sqlR);
			$resR = pg_query($con, $sqlR);

			if(pg_num_rows($resR)>0){
			$produto = pg_fetch_result($resR,0,produto);
			}

		}

	if(!empty($produto)){
		$sql = "DELETE FROM tbl_produto_valida_serie WHERE produto = $produto and mascara= '$mascara'";
			#echo nl2br($sql);
			$res = pg_query ($con,$sql);
			header ("Location: $PHP_SELF?msg=Removido com Sucesso");
			exit;
		}
}

if ($btnacao == "gravar") {
	for($x=0; $x<strlen($mascara); $x++){
		$value = $mascara[$x];
		if($login_fabrica == 3) {
			$value = strtoupper($value);
			if (($value <>'L' AND $value <> 'N') OR !ctype_alpha($mascara)){
				$msg_erro = "A mascara de número de série só pode conter L para letras e N para números";
			}
	}
		if($login_fabrica == 14) {
			if (($value <>'l' AND $value <> 'n' and $value <> 'q') and ctype_lower($value)){
				$msg_erro = "A mascara de número de série com letras minúsculas só pode conter l para aceitar qualquer letra, n para aceitar qualquer número e q para aceitar qualquer letra ou número";
			}
		}
		}

	for($z=0; $z<strlen($mascara2); $z++){
		$value = $mascara2[$z];
		if($login_fabrica == 3) {
			$value = strtoupper($value);
			if (($value <>'L' AND $value <> 'N') OR !ctype_alpha($mascara)){
				$msg_erro = "A mascara de número de série só pode conter L para letras e N para números";
			}
		}
		if($login_fabrica == 14) {
			if (($value <>'l' AND $value <> 'n') and ctype_lower($value)){
				$msg_erro = "A mascara de número de série com letras minúsculas só pode conter l para aceitar qualquer letra e n para aceitar qualquer número";
			}
			}
		}

		if ($login_fabrica == 140) {
			$xreferencia = "'" . $referencia . "'";
			$xdescricao  = "'" . $descricao . "'";
		} else {
			if(strlen($referencia)>0) $xreferencia = "'" . $referencia . "'";
			else                      $msg_erro    = "Informe a Referência do Produto";

			if(strlen($descricao)>0) $xdescricao = "'" . $descricao . "'";
			else                     $msg_erro   = "Informe a Descricão do Produto";
		}

		if(strlen($mascara)>0) $xmascara = "'" . $mascara . "'";
		else                   $msg_erro = "Informe a Mascara para o número de série";

		if(strlen($mascara2)>0) $xmascara2 = "'" . $mascara2 . "'";
		else                    $xmascara2 = "null";

		if($login_fabrica == 140){
			$xlinha = $_POST['linha'];
			$xfamilia = $_POST['familia'];
		}else{
			$xlinha = "'null'";
			$xfamilia = "'null'";
		}

		if(strlen($msg_erro)==0){

			if($login_fabrica == 140){

				if(!empty($referencia)){
					$cond_produto = " AND tbl_produto.referencia = '$referencia' ";
				}

				if(!empty($xlinha)){

					$sql_pl = "SELECT produto FROM tbl_produto 
						JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica 
						WHERE tbl_produto.fabrica_i = $login_fabrica AND tbl_produto.linha = $xlinha $cond_produto";
					$res_mascara = pg_query($con, $sql_pl);

				}

				if(!empty($xfamilia)){

					$sql_pf = "SELECT produto FROM tbl_produto 
						JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica 
						WHERE tbl_produto.fabrica_i = $login_fabrica AND tbl_produto.familia = $xfamilia $cond_produto";
					$res_mascara = pg_query($con, $sql_pf);

				}

				if(empty($referencia) && $login_fabrica == 140){

					$sql_produto = "SELECT produto FROM tbl_produto WHERE tbl_produto.fabrica_i = $login_fabrica";
					$res_mascara = pg_query($con, $sql_produto);

				}

				if(empty($xlinha) && empty($xfamilia)){

					$sqlR = "SELECT tbl_produto.produto
						FROM tbl_produto
						LEFT JOIN tbl_produto_valida_serie ON tbl_produto_valida_serie.produto = tbl_produto.produto 
						JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha 
						WHERE tbl_linha.fabrica      = $login_fabrica
						AND   tbl_produto.referencia = $xreferencia ";
					$res_mascara = pg_query($con, $sqlR);

				}

				if(pg_num_rows($res_mascara) > 0){

					for ($i = 0; $i < pg_num_rows($res_mascara); $i++) { 

						$produto = pg_fetch_result($res_mascara, $i, 'produto');
						if(!empty($xfamilia) OR !empty($xlinha)){
							$campo_desc = (empty($xfamilia)) ? "linha," : "familia,";
							$campo_valor = (empty($xfamilia)) ? $xlinha."," : $xfamilia.",";
						}

						$sql = "INSERT INTO tbl_produto_valida_serie 
							(
								fabrica ,
								produto ,
								$campo_desc 
								mascara
							) VALUES (
								$login_fabrica,
								$produto ,
								$campo_valor 
								$xmascara
							)";
						$res = pg_query ($con,$sql);

					}

				}

			}else{

				$sqlR = "SELECT tbl_produto.produto
					FROM tbl_produto
					LEFT JOIN tbl_produto_valida_serie ON tbl_produto_valida_serie.produto = tbl_produto.produto 
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha 
					WHERE tbl_linha.fabrica      = $login_fabrica
					AND   tbl_produto.referencia = $xreferencia ";
				$resR = pg_query($con, $sqlR);

				if(pg_num_rows($resR)>0){
					$produto         = pg_fetch_result($resR,0,produto);
				}

				$sql = " SELECT produto
					FROM tbl_produto_valida_serie
					WHERE produto = $produto
					AND   fabrica = $login_fabrica
					AND   mascara in ($xmascara,$xmascara2) ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$produto_mascara      = pg_fetch_result($res,0,produto);
				}

				if (strlen($produto_mascara) == 0) {
					//NOVO REGISTRO
					$sql = "INSERT INTO tbl_produto_valida_serie (
						fabrica ,
						produto ,
						mascara
					) VALUES (
						$login_fabrica,
						$produto      ,
						$xmascara
					);";

					if(strlen($xmascara2)>0 AND $xmascara2<>"null"){
						$sql .= "INSERT INTO tbl_produto_valida_serie (
							fabrica ,
							produto ,
							mascara
						) VALUES (
							$login_fabrica ,
							$produto       ,
							$xmascara2
						);";
					}

				}

				$res = pg_query ($con,$sql);

			}

			header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
			exit;
		}
}

$msg = $_GET['msg'];

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE MÁSCARA DE NÚMERO DE SÉRIE";
include 'cabecalho.php';

?>

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


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}


table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{
	padding-left:150px;
}
</style>

<script type="text/javascript">
	function fnc_pesquisa_produto (campo, tipo) {
		if (campo.value != "") {
			var url = "";
			url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&campo=" + campo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=0, left=0");
			janela.retorno = "<? echo $PHP_SELF ?>";
			janela.referencia= document.frm_produto.referencia;
			janela.descricao = document.frm_produto.descricao;
			janela.linha     = document.frm_produto.linha;
			janela.familia   = document.frm_produto.familia;
			janela.focus();
		}
	}

	$(document).ready(function(){
		$('select[name=linha]').change(function(){
			$('select[name=familia]').val('');
		});

		$('select[name=familia]').change(function(){
			$('select[name=linha]').val('');
		});
	});
</script>

<?
//CARREGA REGISTRO
if(strlen($produto)>0){
	$sql = "SELECT tbl_produto.produto               ,
			tbl_produto.referencia                   ,
			tbl_produto.descricao                    ,
			tbl_produto_valida_serie.linha           ,
			tbl_produto_valida_serie.familia         ,
				   tbl_produto_valida_serie.mascara
			  FROM tbl_produto
			JOIN tbl_produto_valida_serie ON tbl_produto_valida_serie.produto = tbl_produto.produto 
			JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha 
			 WHERE tbl_linha.fabrica   = $login_fabrica
			   AND tbl_produto.produto = $produto";
	if(strlen($mascara) > 0) {
		$sql .=" AND tbl_produto_valida_serie.mascara= '$mascara' ";
	}
	$res = pg_query($con, $sql);

	if(pg_num_rows($res)>0){
		$produto    = pg_fetch_result($res,0,produto);
		$referencia = pg_fetch_result($res,0,referencia);
		$descricao_produto  = pg_fetch_result($res,0,descricao);
		$mascara    = pg_fetch_result($res,0,mascara);

		if($login_fabrica == 140){
			$xlinha  	= pg_fetch_result($res,0,linha);
			$xfamilia    = pg_fetch_result($res,0,familia);
		}

	}
}


if($login_fabrica == 14) { ?>
<center>
<div style="text-align:center">
<h2 style='font-size: 20px'>Regra para cadastrar Máscara de Número de Série</h2>
<ol type="1" start="1" style="text-align:center;display: block;border-color: #1937D9;background-color: #D9E2EF;font-size:15px;margin: 0 auto;width: 70%">
				<li>Letras Maiúsculas - Aceita apenas letras cadastradas</li>
				<li>Números - Aceita apenas números cadastrados</li>
				<li>Letra l(Minúscula) - Aceita qualquer letra</li>
				<li>Letra n(Minúscula) - Aceita qualquer número</li>
				<li>Letra q(Minúscula) - Aceita qualquer letra e qualquer número</li>
</ol>
</div>
</center>
<? } ?>

<br>
<form method="post" action="<? echo $PHP_SELF; ?>" name="frm_produto">
<table width="700" cellpadding='4' cellspacing="4" align="center" class="formulario" border="0">
	<?php if(strlen($msg_erro)>0){ ?>
			<tr class='msg_erro'>
				<td colspan='2'><?php echo $msg_erro; ?> </td>
			</tr>
	<?php } ?>

	<?php if(strlen($msg)>0){ ?>
			<tr class='sucesso'>
				<td colspan='2'><?php echo $msg; ?> </td>
			</tr>
	<?php } ?>

	<tr class='titulo_tabela'><td colspan="2">Cadastro</td></tr>

	<?php if($login_fabrica == 140){ ?>

		<tr>
			<td class="espaco">Linha</td>
			<td>Família</td>
		</tr>

		<tr>
			<td class="espaco">
				
				<select name="linha" id="linha">
					<option value="0"></option>
			<?php
				$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo";
				$res = pg_query($con, $sql);

						if(pg_num_rows($res) > 0){
							for ($i=0; $i < pg_num_rows($res); $i++) { 
								$linha = pg_fetch_result($res, $i, 'linha');
								$descricao = pg_fetch_result($res, $i, 'nome');
								$selected = ($xlinha == $linha) ? "selected" : "";
								echo "<option value='{$linha}' {$selected}>{$descricao}</option>";
							}
						}

			?>
				</select>

			</td>
			<td>

				<select name="familia" id="familia">
					<option value="0"></option>
					<?php
						$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo";
						$res = pg_query($con, $sql);

						if(pg_num_rows($res) > 0){
							for ($i=0; $i < pg_num_rows($res); $i++) { 
								$familia = pg_fetch_result($res, $i, 'familia');
								$descricao = pg_fetch_result($res, $i, 'descricao');
								$selected = ($xfamilia == $familia) ? "selected" : "";
								echo "<option value='{$familia}' {$selected}>{$descricao}</option>";
							}
						}

					?>
				</select>

			</td>
		</tr>

	<?php } ?>

	<tr>
		<td class='espaco' width="130">Referência</td>
		<td>Descrição</td>
	</tr>
	<tr>
		<td nowrap class='espaco'>
			<input type="hidden" name="produto" value="<?=$produto?>" />
			<input type="text" class="frm" name="referencia" value="<? echo $referencia; ?>" size="12" maxlength="20">
			<a  href='#'><img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_produto (document.frm_produto.referencia, 'referencia')"></a>
		</td>
		<td nowrap>
			<input type="text" class="frm" size="40" name="descricao" value="<? echo $descricao_produto; ?>" maxlength="50" >
			<a href='#'><img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_produto (document.frm_produto.descricao, 'descricao')"></a>
		</td>
	</tr>

	<tr>
		<td colspan='2' align="center">Máscara</td>
	</tr>
	<?
	if(strlen($produto)==0){
	?>
	<tr>
		<td colspan='2' align="center">
			<acronym title="Ex: LLNNNNNNLNNNL">
				<strong>Ex: LLNNNNNNLNNNL</strong> <input type="text" name="mascara" value="<? echo $mascara; ?>" size="20" maxlength="20" class="frm" style="text-transform: uppercase;">
			</acronym>
			<?php if(!in_array($login_fabrica,[72,140])){ ?>
				<br>
				<acronym title="Ex: LLNNNNNNLNNNL">
					<strong>Ex: LLNNNNNNLNNNL</strong> <input type="text" name="mascara2" value="<? echo $mascara2; ?>" size="20" maxlength="20" class="frm">
				</acronym>
			<?php } ?>
		</td>
	</tr>
	<? }else{
		$sqlP = "SELECT tbl_produto_valida_serie.mascara
				FROM tbl_produto_valida_serie
				WHERE tbl_produto_valida_serie.produto = $produto";
		if(strlen($_GET['mascara']) > 0) {
			$sqlP .=" AND tbl_produto_valida_serie.mascara= '$mascara' ";
		}
		$resP = pg_query($con, $sqlP);

		if(pg_num_rows($resP)>0){
			echo "<tr>";
				echo "<td align='center' colspan='2'>";
			for($z=0; $z<pg_num_rows($resP); $z++){
				$mascara = pg_fetch_result($resP,$z,mascara);
		?>
					<acronym title="Ex: LLNNNNNNLNNNL">
						<input type="text" name="mascara" value="<? echo $mascara; ?>" size="20" maxlength="20">
					</acronym>
					<br>
		<? }
			if($login_fabrica == 3) {
				if(pg_num_rows($resP)==1){?>
					<acronym title="Ex: LLNNNNNNLNNNL">
						<input type="text" name="mascara2" value="" size="20" maxlength="20">
					</acronym>
		<?		}
			}
				echo "</td>";
			echo "</tr>";
		}
	}?>
	<tr>
		<td colspan="2" align="center">
			<input type='hidden' name='btnacao' value=''>

			<input type="button" value="Gravar" onclick="javascript: if (document.frm_produto.btnacao.value == '' ) { document.frm_produto.btnacao.value='gravar' ; document.frm_produto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário"  border='0' style="cursor:pointer;">

			<input type="button" value="Apagar" ONCLICK="javascript: if (document.frm_produto.btnacao.value == '' ) { document.frm_produto.btnacao.value='deletar' ; document.frm_produto.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar" border='0' style="cursor:pointer;">
		</td>
	</tr>
</table>
</form>
<br />
<?

if($login_fabrica == 140){

	$sql_arr = array(
		/* Linha */
		"SELECT DISTINCT tbl_produto_valida_serie.linha,
		tbl_linha.nome,
		tbl_produto_valida_serie.mascara
		INTO TEMP tmp_mascara_linha
		FROM tbl_produto_valida_serie
		JOIN tbl_linha ON tbl_linha.linha = tbl_produto_valida_serie.linha 
		WHERE tbl_linha.fabrica   = 140
		AND tbl_produto_valida_serie.linha notnull
		AND tbl_produto_valida_serie.familia isnull",
		/* Família */
		"SELECT DISTINCT tbl_produto_valida_serie.familia,
		tbl_familia.descricao,
		tbl_produto_valida_serie.mascara
		INTO TEMP tmp_mascara_familia
		FROM tbl_produto_valida_serie
		JOIN tbl_familia ON tbl_familia.familia = tbl_produto_valida_serie.familia 
		WHERE tbl_familia.fabrica   = 140
		AND tbl_produto_valida_serie.linha isnull
		AND tbl_produto_valida_serie.familia notnull",
		/* Produto */
		"SELECT DISTINCT tbl_produto.produto     ,
		tbl_produto.referencia                   ,
		tbl_produto.descricao                    ,
		tbl_produto_valida_serie.mascara
		INTO TEMP tmp_mascara_produto
		FROM tbl_produto
		JOIN tbl_produto_valida_serie ON tbl_produto_valida_serie.produto = tbl_produto.produto 
		WHERE tbl_produto.fabrica_i   = 140
		AND tbl_produto_valida_serie.linha isnull
		AND tbl_produto_valida_serie.familia isnull"
	);

	for($i = 0; $i < 4; $i++){

		$sql = $sql_arr[$i];
		$res = pg_query($con, $sql);

		switch ($i) {
			case 1:
				$sql = "SELECT * FROM tmp_mascara_linha";
				break;
			case 2:
				$sql = "SELECT * FROM tmp_mascara_familia";
				break;
			case 3:
				$sql = "SELECT * FROM tmp_mascara_produto";
				break;
		}

		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){

			switch ($i) {
				case 1:
					echo "<br /> Mascara de Série por Linha";
					break;
				case 2:
					echo "<br /> Mascara de Série por Família";
					break;
				case 3:
					echo "<br /> Mascara de Série por Produto";
					break;
			}

			echo "<table width='750' cellpadding='4' cellspacing='1' align='center' class='tabela'>";
				echo "<tr class='titulo_coluna'>";

					switch ($i) {
						case 1:
							echo "<td>Linha</td>";
							echo "<td>Descrição</td>";
							echo "<td>Mascara</td>";
							echo "<td>Opção</td>";
							break;

						case 2:
							echo "<td>Família</td>";
							echo "<td>Descrição</td>";
							echo "<td>Mascara</td>";
							echo "<td>Opção</td>";
							break;

						case 3:
							// echo "<td>Produto</td>";
							echo "<td>Referência</td>";
							echo "<td>Descrição</td>";
							echo "<td>Mascara</td>";
							echo "<td>Opção</td>";
							break;

					}

				echo "</tr>";

				for($j = 0; $j < pg_num_rows($res); $j++){

					switch ($i) {
						case 1:
							$linha    	= pg_fetch_result($res,$j,linha);
							$descricao  = pg_fetch_result($res,$j,nome);
							$mascara    = pg_fetch_result($res,$j,mascara);
							$link_excluir = "<a href='".$_SERVER['PHP_SELF']."?excluir=sim&linha={$linha}&mascara={$mascara}'>Excluir</a>";
							break;

						case 2:
							$familia    = pg_fetch_result($res,$j,familia);
							$descricao  = pg_fetch_result($res,$j,descricao);
							$mascara    = pg_fetch_result($res,$j,mascara);
							$link_excluir = "<a href='".$_SERVER['PHP_SELF']."?excluir=sim&familia={$familia}&mascara={$mascara}'>Excluir</a>";
							break;

						case 3:
							$produto    = pg_fetch_result($res,$j,produto);
							$referencia = pg_fetch_result($res,$j,referencia);
							$descricao  = pg_fetch_result($res,$j,descricao);
							$mascara    = pg_fetch_result($res,$j,mascara);
							$link_excluir = "<a href='".$_SERVER['PHP_SELF']."?excluir=sim&produto={$produto}&mascara={$mascara}&referencia={$referencia}'>Excluir</a>";
							break;
					}

					$cor = 	($j% 2) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
							
					switch ($i) {
						case 1:
							echo "<td>{$linha}</td>";
							echo "<td>{$descricao}</td>";
							echo "<td>{$mascara}</td>";
							echo "<td>{$link_excluir}</td>";
							break;

						case 2:
							echo "<td>{$familia}</td>";
							echo "<td>{$descricao}</td>";
							echo "<td>{$mascara}</td>";
							echo "<td>{$link_excluir}</td>";
							break;

						case 3:
							// echo "<td>{$produto}</td>";
							echo "<td>{$referencia}</td>";
							echo "<td align='left'>{$descricao}</td>";
							echo "<td>{$mascara}</td>";
							echo "<td>{$link_excluir}</td>";
							break;

					}

					echo "</tr>";
				}
			echo "</table>";
		}

	}

}else{

	$sql = "SELECT DISTINCT tbl_produto.produto,
				tbl_produto.referencia     ,
				tbl_produto.descricao      ,
				tbl_produto.ativo          
		FROM tbl_produto
		JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha 
		JOIN tbl_produto_valida_serie ON tbl_produto.produto = tbl_produto_valida_serie.produto 
		WHERE tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_produto.referencia";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res)>0){

		echo "<table width='750' cellpadding='4' cellspacing='1' align='center' class='tabela' >";
			echo "<tr class='titulo_coluna'>";
				echo "<td>Status</td>";
				echo "<td>Referência</td>";
				echo "<td>Descrição</td>";
				echo "<td colspan='100%'>Mascara</td>";
			echo "</tr>";

			for($i=0; $i<pg_num_rows($res); $i++){
				$produto    = pg_fetch_result($res,$i,produto);
				$referencia = pg_fetch_result($res,$i,referencia);
				$descricao  = pg_fetch_result($res,$i,descricao);
				$ativo      = pg_fetch_result($res,$i,ativo);
				$cor = ($i%2) ? "#F7F5F0" : "#F1F4FA";
				echo "<tr bgcolor='$cor'>";
					echo "<td align='center'>";
					echo ($ativo <> 't') ?"<img src='imagens_admin/status_vermelho.gif' border='0' alt='Inativo'>" : "<img src='imagens_admin/status_verde.gif' border='0' alt='Ativo'>";
					echo "</td>";
					echo "<td><a href='$PHP_SELF?produto=$produto'>$referencia</a></td>";
					echo "<td align='left'><a href='$PHP_SELF?produto=$produto'>$descricao</a></td>";

					$sqlM = "SELECT tbl_produto_valida_serie.mascara
							FROM tbl_produto_valida_serie
							WHERE tbl_produto_valida_serie.produto = $produto
							and   fabrica = $login_fabrica
							ORDER BY mascara";
					$resM = pg_query($con, $sqlM);
					
					if(pg_num_rows($resM)>0){
						for($x=0; $x<pg_num_rows($resM); $x++){
							$mascara = pg_fetch_result($resM,$x,mascara);
							if($x+1 == pg_num_rows($resM)){
								echo "<td colspan='100%'>";
							}
							else{
								echo "<td>";
							}
							echo "<a href='$PHP_SELF?produto=$produto&mascara=$mascara'>";
							echo $mascara;
							echo "</a>";
							echo "</td>";

						}
					}
						


				echo "</tr>";
			}
		echo "</table>";
	}

}

include "rodape.php";
?>
