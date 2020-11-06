<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


if (strlen($_POST["btn_acao"]) > 0)  $btn_acao  = trim($_POST["btn_acao"]);

$deletar = $_GET['excluir'];

if (strlen ($deletar) > 0) {
	$sql = "DELETE FROM tbl_callcenter_pergunta
			WHERE  callcenter_pergunta = $deletar
			AND    fabrica             = $login_fabrica;";

	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) { 
		$msg_erro = pg_errormessage ($con);
		header("Location: $PHP_SELF?callcenter_pergunta=$deletar");
	} else {
		header("Location: $PHP_SELF");
	}
	exit;
}


if ($btn_acao == "gravar") {
	$callcenter_pergunta = trim($_POST["callcenter_pergunta"]);
	$codigo              = trim($_POST["codigo"]);
	$pergunta            = trim($_POST["pergunta"]);

	if(strlen($codigo)==0)   $msg_erro .= traduz("Digite o código da pergunta que será substituida<br>");
	if(strlen($pergunta)==0) $msg_erro .= traduz("Digite a pergunta que você deseja substituir<br>");
	if ($msg_erro == traduz('Digite a pergunta que você deseja substituir<br>') or $msg_erro == traduz('Digite o código da pergunta que será substituida<br>')) {
		$controlgrup = "control-group error";
	}else{
		$controlgrup = "control-group";
	}

	if(strlen($callcenter_pergunta)==0){
		$sql = "SELECT callcenter_pergunta
				FROM   tbl_callcenter_pergunta
				WHERE  fabrica = $login_fabrica
				AND    codigo  = '$codigo'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0) $msg_erro .= traduz("Já existe uma pergunta com o código %<br>", null, null, [$codigo]);
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($callcenter_pergunta) == 0) {
			$sql = "INSERT INTO tbl_callcenter_pergunta (
						fabrica ,
						codigo  ,
						pergunta
					) VALUES (
						$login_fabrica,
						'$codigo'     ,
						'$pergunta'
					);";
		}else{
			$sql = "UPDATE tbl_callcenter_pergunta SET
						pergunta = '$pergunta'
					WHERE  fabrica             = $login_fabrica
					AND    callcenter_pergunta = $callcenter_pergunta;";
		}
		
		$res = @pg_exec ($con,$sql);
		$msg_erro = @pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			if (strlen($callcenter_pergunta) == 0) {
				$res = pg_exec ($con,"SELECT CURRVAL ('seq_callcenter_pergunta')");
				$callcenter_pergunta = pg_result ($res,0,0);
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$callcenter_pergunta = $_GET["callcenter_pergunta"];
if (strlen($callcenter_pergunta) > 0) {
	$sql = "SELECT  callcenter_pergunta,
					codigo             ,
					pergunta
			FROM    tbl_callcenter_pergunta
			WHERE   callcenter_pergunta = $callcenter_pergunta
			AND     fabrica             = $login_fabrica;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$callcenter_pergunta = trim(pg_result($res,0,callcenter_pergunta));
		$codigo              = trim(pg_result($res,0,codigo));
		$pergunta            = trim(pg_result($res,0,pergunta));
	}
}

$title = traduz("CADASTRO DE PERGUNTA DO CALLCENTER");
$layout_menu = "callcenter";
include 'cabecalho_new.php';

// Mensagem de erro
if (strlen($msg_erro) > 0) echo "<p><div class='alert alert-error'><h4>".$msg_erro."</h4></div></p>";

$xpergunta = array();
$xpergunta[1] = traduz("APRESENTAÇÃO
			Fabricante, José, bom dia.
			O Sr.(a) já fez algum contato com a Fábrica ? ");
$xpergunta[2]  = traduz("Qual o produto comprado?");
$xpergunta[3]  = traduz("em que posso ajudá-lo? ");
$xpergunta[4]  = traduz("Confirmar ou perguntar a reclamação.
			Qual é a sua reclamação SR.(a)?
			ou
			O Sr.(a) diz que...., correto? ");
$xpergunta[5]  = traduz("Confirmar ou perguntar a reclamação.
			Qual é a sua reclamação SR.(a)?
			ou
			O Sr.(a) diz que...., correto? ");
$xpergunta[6]  = traduz("Confirmar ou perguntar a reclamação.
			Qual é a sua reclamação SR.(a)?
			ou
			O Sr.(a) diz que...., correto?");
$xpergunta[7]  = traduz("Confirmar ou perguntar a dúvida.
			Qual é a sua dúvida SR.(a)?
			ou
			A dúvida do Sr.(a) é sobre como...., correto?");
$xpergunta[8]  = traduz("Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.");
$xpergunta[9] = traduz("Qual o Posto mais próximo do consumidor?");
$xpergunta[10] = traduz("Confirmar ou perguntar a reclamação.
			Qual é a reclamação feita no Procon pelo SR.(a)?
			ou
			O Sr.(a), correto? ");
$xpergunta[11] = traduz("Informar dados da Revenda.
			Qual são os dados da Revenda? ");


?> 
<table class='table table-striped table-bordered table-hover table-large'>
	<thead>
		<tr class='titulo_tabela'>
			<th><?=traduz('Código da Frase')?></th>
			<th><?=traduz('Aba')?></th>
			<th><?=traduz('Frase')?></th>
		</tr>
	</thead>
	<tbody>
	<tr>
		<td>1</td>
		<td><?=traduz('Nenhuma')?></td>
		<td><?=$xpergunta[1]?></td>
	</tr>
	<tr>
		<td>2</td>
		<td><?=traduz('Nenhuma')?></td>
		<td><?=$xpergunta[2]?></td>
	</tr>

	<tr>
		<td>3</td>
		<td><?=traduz('Nenhuma')?></td>
		<td><?=$xpergunta[3]?></td>
	</tr>
	<tr>
		<td>4</td>
		<td><?=traduz('Produto/Defeito')?></td>
		<td><?=$xpergunta[4]?></td>
	</tr>
	<tr>
		<td>5</td>
		<td><?=traduz('Reclamação/Empresa')?></td>
		<td><?=$xpergunta[5]?></td>
	</tr>
	<tr>
		<td>6</td>
		<td><?=traduz('Reclamação/At')?></td>
		<td><?=$xpergunta[6]?></td>
	</tr>
	<tr>
		<td>7</td>
		<td><?=traduz('Dúvida Produto')?></td>
		<td><?=$xpergunta[7]?></td>
	</tr>
	<tr>
		<td>8</td>
		<td><?=traduz('Sugestão')?></td>
		<td><?=$xpergunta[8]?></td>
	</tr>
	<?
	if ($login_fabrica != 15) {
	?>
		<tr>
			<td>9</td>
			<td><?=traduz('At. Próximo')?></td>
			<td><?=$xpergunta[9]?></td>
		</tr>
	<?
	}
	?>
	<tr >
		<td>10</td>
		<td><?=traduz('Procon/Jec')?></td>
		<td><?=$xpergunta[10]?></td>
	</tr>
	<tr >
		<td>11</td>
		<td><?=traduz('Onde Comprar')?></td>
		<td><?=$xpergunta[11]?></td>
	</tr>
	</tbody>
</table>

<br />
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<form name="frm_situacao" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">
<input type="hidden" name="callcenter_pergunta" value="<? echo $callcenter_pergunta ?>">
<div class="titulo_tabela"><?=traduz('Cadastro de Perguntas do Callcenter')?></div>
<br />
<div class="row-fluid">
	<div class='span2'></div>
	<div class="span3">
		<label class='control-label' for='codigo'><?=traduz('Código')?></label>
		<div class='controls controls-row'>
			<div class='span12'>
				<select name='codigo' id='codigo' class='span4'>
					<option value='1' <?if($codigo=='1') echo " SELECTED ";?>>1</option>
					<option value='2' <?if($codigo=='2') echo " SELECTED ";?>>2</option>
					<option value='3' <?if($codigo=='3') echo " SELECTED ";?>>3</option>
					<option value='4' <?if($codigo=='4') echo " SELECTED ";?>>4</option>
					<option value='5' <?if($codigo=='5') echo " SELECTED ";?>>5</option>
					<option value='6' <?if($codigo=='6') echo " SELECTED ";?>>6</option>
					<option value='7' <?if($codigo=='7') echo " SELECTED ";?>>7</option>
					<option value='8' <?if($codigo=='8') echo " SELECTED ";?>>8</option>
					<?
						if ($login_fabrica != 15) {
					?>
					<option value='9' <?if($codigo=='9') echo " SELECTED ";?>>9</option>
					<?
						}
					?>
					<option value='10' <?if($codigo=='10') echo " SELECTED ";?>>10</option>
					<option value='11' <?if($codigo=='11') echo " SELECTED ";?>>11</option>
				</select>
			</div>
		</div>
	</div>
	<div class="span5">
		<div class="<? echo $controlgrup ?>">
			<label class='control-label' for='endereco'><?=traduz('Pergunta')?></label>
			<div class='controls controls-row'>
				<div class='span12'>
					<h5 class='asteristico'>*</h5>
					<TEXTAREA rows='3' cols='80' name="pergunta" class='span12'><? echo $pergunta ?></TEXTAREA>
				</div>
			</div>
		</div>
	</div>
	<div class='span2'></div>
</div>
<br />
<br />
<br />
<center>


<button class="btn btn" style="cursor: pointer;" onclick="javascript: if (document.frm_situacao.btn_acao.value == '' ) { document.frm_situacao.btn_acao.value='gravar' ; document.frm_situacao.submit() } else { alert ('<?=traduz("Aguarde submissão")?>') }" ALT='<?=traduz("Gravar formulário")?>' border='0'><?=traduz('Gravar')?></button>
<input type='hidden' name='btn_acao' value=''>

</center>
<br />
</form>

<? 

if ($login_fabrica == 15) {
	$sql = "SELECT * 
			FROM tbl_callcenter_pergunta
			WHERE fabrica = $login_fabrica
			AND codigo::int != 9
			ORDER BY codigo::int ASC";
} 
else 
{
	$sql = "SELECT * 
			FROM tbl_callcenter_pergunta
			WHERE fabrica = $login_fabrica
			ORDER BY codigo::int ASC";
}

$res = pg_exec($con, $sql);

if(pg_numrows($res)>0){ ?>
	<div id="DataTables_Table_0_wrapper" class="dataTables_wrapper form-inline" role="grid" style="width: 850px;">
	<table id="tabela_alt" class='table table-striped table-bordered table-hover table-large'>
	<thead>
	<tr class='titulo_coluna'>
	<th><?=traduz('Código')?></th>
	<th><?=traduz('Pergunta')?></th>
	<th><?=traduz('Pergunta Original')?></th>
	<th><?=traduz('Ação')?></th>
	</tr>

	</thead>
	<tbody>
	<?
	for($i=0;$i<pg_numrows($res);$i++){
		$callcenter_pergunta = pg_result($res,$i,callcenter_pergunta);
		$codigo              = pg_result($res,$i,codigo);
		$pergunta            = pg_result($res,$i,pergunta);

		  ( $i %2 == 0 )  ? $cor = '#F1F4FA' : $cor = '#F7F5F0';
		  
		echo "<tr bgcolor='$cor'>";
		echo "<td><a href='$PHP_SELF?callcenter_pergunta=$callcenter_pergunta'>$codigo</a></td>";
		echo "<td><a href='$PHP_SELF?callcenter_pergunta=$callcenter_pergunta'>$pergunta</a></td>";
		echo "<td>".$xpergunta[$codigo]."</td>";
		echo "<td><a class='btn btn-danger' href='$PHP_SELF?excluir=$callcenter_pergunta'>".traduz("Deletar")."</a></td>";
		echo "</tr>";
	}
	?>
	</tbody>
	</table>
	</div>
	<?
} 
include "rodape.php";
?>
