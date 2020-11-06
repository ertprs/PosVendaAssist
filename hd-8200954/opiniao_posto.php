<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';
$msg_erro = "";
$msg_debug = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if (strlen($_GET['opiniao_posto']) > 0) $opiniao_posto = $_GET['opiniao_posto'];
if (strlen($_POST['opiniao_posto']) > 0) $opiniao_posto = $_POST['opiniao_posto'];

if($btn_acao == 'resposta_nao'){
    $opiniao_posto_pergunta = $_POST['pergunta'];
    $resposta = 'f';
    $sql = "INSERT INTO tbl_opiniao_posto_resposta (
        opiniao_posto_pergunta,
        posto                 ,
        resposta
    ) VALUES (
        $opiniao_posto_pergunta,
        $login_posto           ,
        '$resposta'
    )";


    $res = pg_exec ($con,$sql);

    echo "OK";
    exit;
}

if ($btn_acao == 'gravar'){
	if (strlen($resposta) > 0)
		$xresposta = "'".$resposta."'";

	if ($login_fabrica == 151) {

		$sqlQuestionario = "SELECT qtde_questionario 
		                      FROM tbl_opiniao_posto 
		                     WHERE fabrica = $login_fabrica 
		                       AND opiniao_posto = $opiniao_posto;";
		$resQuestionario  = pg_query($con, $sqlQuestionario);
		$qtdeQuestionario = pg_fetch_result($resQuestionario,0,'qtde_questionario');

		$sqlRespondido = "SELECT DISTINCT tbl_opiniao_posto_resposta.posto
				            FROM tbl_opiniao_posto_resposta
				            JOIN tbl_opiniao_posto_pergunta USING(opiniao_posto_pergunta)
				  	         WHERE tbl_opiniao_posto_pergunta.opiniao_posto = $opiniao_posto;";
		$resRespondido   = pg_query($con, $sqlRespondido);
		$qtdeRespondido  = pg_num_rows($resRespondido);
		if (!empty($qtdeRespondido) && ($qtdeRespondido >= $qtdeQuestionario)) {
			$msg_erro = "Questinário inativo, pois excedeu o limite de respostas.";
		}
	}
	//aqui valida se ja foi respondido
	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");
		for ($i = 0; $i < $qtde_perguntas; $i++){
			$resposta               = trim($_POST['resposta_'.$i]);
			$opiniao_posto_pergunta = trim($_POST['opiniao_posto_pergunta_'.$i]);
			$novo                   = trim($_POST['novo_'.$i]);

			if (strlen($resposta) == 0) {
				$msg_erro = "Escolha e/ou digite uma resposta.";
			}
			if (strlen($msg_erro) == 0) {

				if ($novo == 't') {
				## INSERE ##
				$sql = "INSERT INTO tbl_opiniao_posto_resposta (
							opiniao_posto_pergunta,
							posto                 ,
							resposta
						) VALUES (
							$opiniao_posto_pergunta,
							$login_posto           ,
							'$resposta'
						)";
				}else{
					## ALTERA ##
					$sql = "UPDATE tbl_opiniao_posto_resposta SET
								resposta    = '$resposta'
							WHERE opiniao_posto_pergunta   = '$opiniao_posto_pergunta'";
				}
				$res = pg_exec ($con,$sql);
			}

		}//FIM FOR
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: opiniao_posto.php");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}//fim gravar

/*================ LE DA BASE DE DADOS =========================*/

//$opiniao_posto = $_GET['opiniao_posto'];
//$opiniao_posto_resposta = $_GET['opiniao_posto_resposta'];
//echo $opiniao_posto_resposta;

//if (strlen ($opiniao_posto_resposta) > 0) {
	$sql = "SELECT *
			FROM tbl_opiniao_posto_resposta
			WHERE posto                  = $login_posto;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$opiniao_posto_pergunta = pg_result ($res,0,opiniao_posto_pergunta);
		$resposta               = pg_result ($res,0,resposta);
	}

//}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen ($msg_erro) > 0) {
	$resposta		= $_POST['resposta_$i'];
}

$title       = traduz("opiniao.posto",$con,$cook_idioma);
$cabecalho   = "Opinião Posto";
$layout_menu = "tecnica";
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

.menu_top .pergunta {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #ffffff;
	background-color: #596d9b;
	padding: 5px;
}

.xcabecalho{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #ffffff;
	background-color: #596d9b;
	padding: 5px;
}

.xpergunta{
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	padding: 5px;
}

.xresposta{
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	padding: 5px;
	font-weight: normal;
}


.pesquisa {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}


.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
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
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.th-titulo{
	padding: 10px;font-size: 14px;
}
.td-conteudo{
	padding: 10px;
}
.ltd-conteudo{
	padding-left: 10px;
}
a{color:blue;}
</style>
<?php if ($msg_erro) {?>
<p>
<table width='650' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?php }?>
<p>

<?php if (in_array($login_fabrica, array(151))) {?>
<table class="border" width='700' align='center' border='0' cellpadding="2" cellspacing="2">
	<thead>
		<tr class="pesquisa">
			<th class="th-titulo">Data Envio</th>
			<th class="th-titulo tal">Questionário</th>
			<th class="th-titulo">Status</th>
		</tr>
	</thead>
	<body>
	<?php 
	
		$linhasDoPostoAtende = array();

		$sqlPostoLinha = "SELECT tbl_posto_linha.linha
							FROM tbl_posto_linha
							JOIN tbl_linha USING(linha)
						   WHERE tbl_posto_linha.posto = $login_posto
							 AND tbl_posto_linha.ativo is true
							 AND tbl_linha.fabrica = $login_fabrica;";
		$resPostoLinha = pg_query($con, $sqlPostoLinha);

		$linhasDoPosto = pg_fetch_all($resPostoLinha);
		foreach ($linhasDoPosto as $key => $rows) {
			$linhasDoPostoAtende[] = $rows["linha"];
		}

		$sql = "SELECT TO_CHAR(data_criacao, 'DD/MM/YYYY') AS data_criacao,
					    validade, 
		                cabecalho, 
				        linha,
				        ativo,
				        estado,
						opiniao_posto
				FROM tbl_opiniao_posto
				WHERE fabrica = $login_fabrica
				{$condEstado}
				AND ativo is true;";
		$res = pg_query($con, $sql);
			for ($i = 0; $i < pg_num_rows ($res); $i++) { 
				$cabecalho     = pg_fetch_result($res,$i,cabecalho);
				$opiniao_posto = pg_fetch_result($res,$i,opiniao_posto);
				$ativo         = pg_fetch_result($res,$i,ativo);
				$data_criacao  = pg_fetch_result($res,$i,data_criacao);
				$validade      = pg_fetch_result($res,$i,validade);
				$linha         = pg_fetch_result($res,$i,linha);
				$estado        = pg_fetch_result($res,$i,estado);

				if (!empty($linha) && !in_array($linha, $linhasDoPostoAtende)) {
					continue;
				}

				if (!empty($estado) && $estado <> $login_contato_estado) {
					continue;
				}


				$sqlResposta = "SELECT *
						          FROM tbl_opiniao_posto_resposta
						          JOIN tbl_opiniao_posto_pergunta USING(opiniao_posto_pergunta)
						  	     WHERE tbl_opiniao_posto_resposta.posto = $login_posto
						  	       AND tbl_opiniao_posto_pergunta.opiniao_posto = $opiniao_posto;";
				$resResposta = pg_query($con, $sqlResposta);
				$respondido  = (pg_num_rows($resResposta) > 0) ? true : false;
				if ($respondido) {
					$ativo = "<img src='admin/imagens/status_verde.png'> Respondido";
					$link  = '<a href="opiniao_posto.php?acao=ver-opiniao&opiniao_posto='.$opiniao_posto.'">'.$cabecalho.'</a>';
				} else {
					$ativo = "<img src='admin/imagens/status_vermelho.png'> Pendente";
					$link  = '<a href="opiniao_posto.php?opiniao_posto='.$opiniao_posto.'">'.$cabecalho.'</a>';
				}
				if (!empty($validade) && ($validade < date("Y-m-d"))) {
					$ativo = "<img src='admin/imagens/status_vermelho.png'> Inativo";
					$link  = '<a href="">'.$cabecalho.'</a>';
				}
					
		?>
		<tr bgcolor="<?php echo ($i % 2 == 0) ? '#eeeeee' : '';?>">
			<td class="td-conteudo tac"><?php echo $data_criacao;?></td>
			<td class="td-conteudo"><?php echo $link;?></td>
			<td class="td-conteudo tac"><?php echo $ativo;?></td>
		</tr>
		<?php }?>
	</body>
</table>
<?php }?>

<?php if (isset($_GET['opiniao_posto']) && $_GET['acao'] == "ver-opiniao" && in_array($login_fabrica, array(151))) {?>

<table class="border" width='700' align='center' border='0' cellpadding="2" cellspacing="2">
	<?php 
		$opiniao_posto = $_GET["opiniao_posto"];
		$sql = "SELECT cabecalho,
					   opiniao_posto
			      FROM tbl_opiniao_posto
			     WHERE fabrica = $login_fabrica
				   AND opiniao_posto = {$opiniao_posto}
			       AND ativo IS TRUE;";
	    $res = pg_query($con,$sql);

	    if (pg_num_rows ($res) == 0) {
			echo "<tr class='menu_top '>
					<td align='left' class='pergunta'>Registro não encontrado.</td>
				  </tr>";

	    } else {

			$cabecalho     = pg_result($res,0,cabecalho);
			$opiniao_posto = pg_result($res,0,opiniao_posto);

			echo "<tr class='menu_top '>
				<td align='center' class='xcabecalho'>{$cabecalho}</td>
			  </tr>";

		
			$sql = "SELECT tbl_opiniao_posto_pergunta.opiniao_posto_pergunta,
						   tbl_opiniao_posto_pergunta.pergunta,
						   tbl_opiniao_posto_pergunta.tipo_resposta ,
						   tbl_opiniao_posto_pergunta.ordem
					  FROM tbl_opiniao_posto_pergunta
					  JOIN tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto
					 WHERE tbl_opiniao_posto.fabrica = $login_fabrica
					   AND tbl_opiniao_posto_pergunta.opiniao_posto = ".$opiniao_posto."
					   AND tbl_opiniao_posto.ativo IS TRUE
				  ORDER BY tbl_opiniao_posto_pergunta.ordem;";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0){
				for ($i = 0; $i < pg_num_rows($res); $i++) {

					$pergunta               = pg_fetch_result($res,$i,pergunta);
					$tipo_resposta          = pg_fetch_result($res,$i,tipo_resposta);
					$opiniao_posto_pergunta = pg_fetch_result($res,$i,opiniao_posto_pergunta);

					$sql = "SELECT *
							  FROM tbl_opiniao_posto_resposta
							 WHERE posto = $login_posto
							   AND opiniao_posto_pergunta = $opiniao_posto_pergunta";
					$res2 = pg_query($con,$sql);

					if (pg_num_rows($res2) > 0) {
						$opiniao_posto_pergunta = pg_fetch_result($res2, 0, opiniao_posto_pergunta);
						$resposta               = pg_fetch_result($res2, 0, resposta);
					} 
					echo "<tr class='menu_top '>
							<td align='left' class='xpergunta'><b>".($i+1).") ".$pergunta."</b> <br />
						  ";

					if ($tipo_resposta == 'F') {
						if ($resposta == 'muito satisfeito') {
							$xresposta = strtoupper(traduz("muito.satisfeito",$con,$cook_idioma));
						} elseif ($resposta == 'satisfeito') {
							$xresposta = strtoupper(traduz("satisfeito",$con,$cook_idioma));
						} elseif ($resposta == 'nem satisfeito nem insatisfeito') {
							$xresposta = strtoupper(traduz("nem.satisfeito.nem.insatisfeito",$con,$cook_idioma));
						} elseif ($resposta == 'insatisfeito') {
							$xresposta = strtoupper(traduz("insatisfeito",$con,$cook_idioma));
						} elseif ($resposta == 'muito insatisfeito') {
							$xresposta = strtoupper(traduz("muito.insatisfeito",$con,$cook_idioma));
						}
					} elseif ($tipo_resposta == 'T') {
						$xresposta = $resposta;
					} elseif ($tipo_resposta == 'S'){
						if ($resposta == 't') {
							$xresposta = traduz("sim",$con,$cook_idioma);
						} else {
							$xresposta = traduz("nao",$con,$cook_idioma);
						}
					} elseif ($tipo_resposta == 'P') {
						if ($resposta == 'muito progresso') {
							$xresposta = strtoupper(traduz("muito.progresso",$con,$cook_idioma));
						} elseif ($resposta == 'melhorou') {
							$xresposta = strtoupper(traduz("melhorou",$con,$cook_idioma));
						} elseif ($resposta == 'permaneceu') {
							$xresposta = strtoupper(traduz("permaneceu.igual",$con,$cook_idioma));
						} elseif ($resposta == 'piorou') {
							$xresposta = strtoupper(traduz("piorou",$con,$cook_idioma));
						} 
					}
					echo "
							<p class='xresposta'>".$xresposta ."</p>
							</td>
						  </tr>";
				}
			}
		}
	?>
</table>

<a href="opiniao_posto.php" style="margin-top: 10px;" class="btn">Voltar</a>

<?php }?>

<?php if ($login_fabrica <> 151 || (isset($_GET['opiniao_posto']) && !isset($_GET['acao'])  && in_array($login_fabrica, array(151)))) {?>

<form name="frm_opiniao_posto" method="post" action="<? echo $PHP_SELF ?>?opiniao_posto=<? echo $_GET['opiniao_posto']; ?>">
<input class="frm" type="hidden" name="opiniao_posto" value="<? echo $opiniao_posto; ?>">

<?php 
	$cond       = "";

	if (in_array($login_fabrica, array(151))) {
		$cond = " AND opiniao_posto=".$_GET['opiniao_posto'];
		$condLinha  = "";
		$condEstado = "";
	}
	$sql = "SELECT tbl_opiniao_posto_resposta.opiniao_posto_resposta,
					tbl_opiniao_posto_resposta.opiniao_posto_pergunta,
					tbl_opiniao_posto_resposta.posto,
					tbl_opiniao_posto_resposta.resposta,
					tbl_opiniao_posto_pergunta.opiniao_posto,
					tbl_opiniao_posto.fabrica,
					tbl_opiniao_posto.ativo
			FROM tbl_opiniao_posto_resposta
			JOIN tbl_opiniao_posto_pergunta ON tbl_opiniao_posto_resposta.opiniao_posto_pergunta = tbl_opiniao_posto_pergunta.opiniao_posto_pergunta
			JOIN tbl_opiniao_posto ON tbl_opiniao_posto_pergunta.opiniao_posto = tbl_opiniao_posto.opiniao_posto
			WHERE posto = $login_posto
			AND ativo is true
			{$cond}
			AND fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql);

    //echo nl2br($sql);

	$respondido = 0 ;
    if (pg_numrows ($res) > 0) {
        if($login_fabrica == 1){
            $complementoMsg = ", aguarde o redirecionamento!";
        }
		?>
		<div style="margin:auto;width:700px" class='sucesso'>
        Questionário Respondido com Sucesso <?php echo $complementoMsg;?>
		</div>
		<?
		$respondido = 1 ;

	}
?>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="0">
<?php

	$sql = "SELECT	cabecalho        ,
					opiniao_posto
			FROM	tbl_opiniao_posto
			WHERE	fabrica = $login_fabrica
			{$cond}
			{$condLinha}
			{$condEstado}
			AND ativo is true;";
	$res = pg_exec($con,$sql);

	if (pg_numrows ($res) == 0) exit ;

	$cabecalho     = pg_result($res,0,cabecalho);
	$opiniao_posto = pg_result($res,0,opiniao_posto);

?>
	<TR>
		<TD class="pesquisa" colspan='2'><div align="center" class="th-titulo"><b><?echo $cabecalho?></b></div></TD>
	</TR>
	<tr class="menu_top">
		<td align='left'>&nbsp;</td>
	</tr>
<?
	$sql = "SELECT	tbl_opiniao_posto_pergunta.opiniao_posto_pergunta,
					tbl_opiniao_posto_pergunta.pergunta              ,
					tbl_opiniao_posto_pergunta.tipo_resposta         ,
					tbl_opiniao_posto_pergunta.ordem
			FROM	tbl_opiniao_posto_pergunta
			JOIN    tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto
			WHERE	tbl_opiniao_posto.fabrica                = $login_fabrica
			AND     tbl_opiniao_posto_pergunta.opiniao_posto = $opiniao_posto
			AND     tbl_opiniao_posto.ativo is true
			ORDER BY tbl_opiniao_posto_pergunta.ordem;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		for ($i = 0; $i < pg_numrows($res); $i++){

			$pergunta               = pg_result($res,$i,pergunta);
			$tipo_resposta          = pg_result($res,$i,tipo_resposta);
			$opiniao_posto_pergunta = pg_result($res,$i,opiniao_posto_pergunta);

			$sql = "SELECT	*
					FROM	tbl_opiniao_posto_resposta
					WHERE	posto                  = $login_posto
					AND		opiniao_posto_pergunta = $opiniao_posto_pergunta";
			$res2 = pg_exec($con,$sql);

			if (pg_numrows($res2) > 0){
				$opiniao_posto_pergunta = pg_result ($res2,0,opiniao_posto_pergunta);
				$resposta               = pg_result ($res2,0,resposta);
				echo "<input class='frm' type='hidden' name='novo_$i' value='f'>";
			}else{
				echo "<input class='frm' type='hidden' name='novo_$i' value='t'>";
			}

			echo "<input class='frm' type='hidden' name='opiniao_posto_pergunta_$i' value='$opiniao_posto_pergunta'>";

			echo "<tr class='menu_top'>";
			echo "<td align='left' class='td-conteudo'>";
			echo $i+1;
			echo ") $pergunta</td>";
			echo "</tr>";
			echo "<tr class='menu_top'>";
			echo "<td align='left'>&nbsp;</td>";
			echo "</tr>";

			if ($tipo_resposta == 'F'){?>
				<tr class="menu_top">
					<td align='left' class="ltd-conteudo"><INPUT TYPE="radio" NAME="resposta_<?echo $i?>" VALUE = 'muito satisfeito' <?if ($resposta == 'muito satisfeito') echo "checked";?> <? if ($respondido == 1) echo " disabled " ?> >&nbsp;<? echo strtoupper(traduz("muito.satisfeito",$con,$cook_idioma));?></td>
				</tr>
				<tr class="menu_top">
					<td align='left' class="ltd-conteudo"><INPUT TYPE="radio" NAME="resposta_<?echo $i?>" VALUE = 'satisfeito' <?if ($resposta == 'satisfeito') echo "checked";?> <? if ($respondido == 1) echo " disabled " ?> >&nbsp;<? echo strtoupper(traduz("satisfeito",$con,$cook_idioma));?></td>
				</tr>
				<tr class="menu_top">
					<td align='left' class="ltd-conteudo"><INPUT TYPE="radio" NAME="resposta_<?echo $i?>" VALUE = 'nem satisfeito nem insatisfeito' <?if ($resposta == 'nem satisfeito nem insatisfeito') echo "checked";?> <? if ($respondido == 1) echo " disabled " ?> >&nbsp;<? echo strtoupper(traduz("nem.satisfeito.nem.insatisfeito",$con,$cook_idioma));?></td>
				</tr>
				<tr class="menu_top">
					<td align='left' class="ltd-conteudo"><INPUT TYPE="radio" NAME="resposta_<?echo $i?>" VALUE = 'insatisfeito' <?if ($resposta == 'insatisfeito') echo "checked";?> <? if ($respondido == 1) echo " disabled " ?> >&nbsp;<? echo strtoupper(traduz("insatisfeito",$con,$cook_idioma));?></td>
				</tr>
				<tr class="menu_top">
					<td align='left' class="ltd-conteudo"><INPUT TYPE="radio" NAME="resposta_<?echo $i?>" VALUE = 'muito insatisfeito' <?if ($resposta == 'muito insatisfeito') echo "checked";?> <? if ($respondido == 1) echo " disabled " ?> >&nbsp;<? echo strtoupper(traduz("muito.insatisfeito",$con,$cook_idioma));?></td>
				</tr>
				<tr class="menu_top">
					<td align='left'>&nbsp;</td>
				</tr>
				<tr class="menu_top">
					<td align='left'>&nbsp;</td>
				</tr>

			<?}	else if ($tipo_resposta == 'T'){?>
				<tr class="menu_top">
					<td align='left' class="td-conteudo"><TEXTAREA NAME="resposta_<?echo $i?>" ROWS="4" COLS="80" <? if ($respondido == 1) echo " disabled " ?> ><?echo $resposta?></TEXTAREA></td>
				</tr>
				<tr class="menu_top">
					<td align='left'>&nbsp;</td>
				</tr>

			<?}else if ($tipo_resposta == 'S'){?>
				<tr class="menu_top">
					<td align='left' class="td-conteudo">
					<input type="radio" name="resposta_<?echo $i?>" value="t" <? if ($resposta == 't') echo " checked"; ?> <? if ($respondido == 1) echo " disabled " ?> >
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?fecho("sim",$con,$cook_idioma);?></font>&nbsp;&nbsp;
					<input type="radio" name="resposta_<?echo $i?>" value="f" <? if ($resposta == 'f') echo " checked"; ?> <? if ($respondido == 1) echo " disabled " ?> >
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?fecho("nao",$con,$cook_idioma);?></font>
					</td>
				</tr>
				<tr class="menu_top">
					<td align='left'>&nbsp;</td>
				</tr>
			<?}else if($tipo_resposta == 'P'){?>
				<tr class="menu_top">
					<td align='left' class="td-conteudo"><INPUT TYPE="radio" NAME="resposta_<?echo $i?>" VALUE = 'muito progresso' <?if ($resposta == 'muito progresso') echo "checked";?> <? if ($respondido == 1) echo " disabled " ?> >&nbsp;<?echo strtoupper(traduz("muito.progresso",$con,$cook_idioma));?></td>
				</tr>
				<tr class="menu_top">
					<td align='left' class="td-conteudo"><INPUT TYPE="radio" NAME="resposta_<?echo $i?>" VALUE = 'melhorou' <?if ($resposta == 'melhorou') echo "checked";?> <? if ($respondido == 1) echo " disabled " ?> >&nbsp;<?echo strtoupper(traduz("melhorou",$con,$cook_idioma));?></td>
				</tr>
				<tr class="menu_top">
					<td align='left' class="td-conteudo"><INPUT TYPE="radio" NAME="resposta_<?echo $i?>" VALUE = 'permaneceu igual' <?if ($resposta == 'permaneceu igual') echo "checked";?> <? if ($respondido == 1) echo " disabled " ?> >&nbsp;<?echo strtoupper(traduz("permaneceu.igual",$con,$cook_idioma));?></td>
				</tr>
				<tr class="menu_top">
					<td align='left' class="td-conteudo"><INPUT TYPE="radio" NAME="resposta_<?echo $i?>" VALUE = 'piorou' <?if ($resposta == 'piorou') echo "checked";?> <? if ($respondido == 1) echo " disabled " ?> >&nbsp;<?echo strtoupper(traduz("piorou",$con,$cook_idioma));?></td>
				</tr>
				<tr class="menu_top">
					<td align='left'>&nbsp;</td>
				</tr>
				<tr class="menu_top">
					<td align='left'>&nbsp;</td>
				</tr>

			<?}
		}// fim for
		echo "<input class='frm' type='hidden' name='qtde_perguntas' value='$i'>";
	}//fim if

    if ($respondido == 0) {
        if($login_fabrica == 1){
            $verificacao = "if(beforeSubmit() == false){ return false; }";
        }
		echo "<input type='hidden' name='btn_acao' value=''>";
		echo "<TR class='menu_top'>";
		echo "<TD align='center'><a href='#'><IMG SRC='imagens/btn_gravar.gif' ONCLICK=\"javascript: if (document.frm_opiniao_posto.btn_acao.value == '' ) { ".$verificacao." document.frm_opiniao_posto.btn_acao.value='gravar' ; document.frm_opiniao_posto.submit() } else { alert ('".traduz("aguarde.submissao",$con,$cook_idioma)."') }\" ALT='Gravar Opinião' border='0' style='cursor: pointer'></a></TD>";

		echo "</TR>";
		echo "<TR class='menu_top'><TD align='center'>&nbsp;</TD></TR>";
    }

    if($login_fabrica == 1){
?>
<tr class="menu_top">
    <td>
      <button type="button" id="btn_save_primary">Salvar</button>
    </td>
</tr>
<?php
    }
?>


</TABLE>
<?php
}
    if($login_fabrica == 1){
?>
        <script>
        beforeSubmit = function(){
            var textAreas = document.getElementsByTagName('textarea');
            for(i=0;i<textAreas.length;i++){
                if(textAreas[i].value.trim() == ""){
                    alert("Responda todas perguntas");
                    return false;
                }
            }
        }
        var form = document.getElementsByName('frm_opiniao_posto');

        form = form[0];
        var trs = form.getElementsByTagName('tr');

        as = trs[7];

        for(i=5; i < trs.length -1 ;i++){
               trs[i].setAttribute("style","display:none;")        
        }


        document.getElementById("btn_save_primary").addEventListener("click",function(){
                 
            var cks = document.getElementsByName("resposta_0");
            var hiddenPergunta = document.getElementsByName("opiniao_posto_pergunta_0");
            var pergunta = hiddenPergunta[0].getAttribute("value");

            if(cks[0].checked){
                for(j=5; j < trs.length ;j++){
                    trs[j].setAttribute("style","")        
                                                  }
            }else{    
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (xhttp.readyState == 4 && xhttp.status == 200) {
                        window.location = "./login.php";             
                    }
                };
                xhttp.open("POST", "./opiniao_posto.php", true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhttp.send("btn_acao=resposta_nao&pergunta="+pergunta);

                alert("Sua resposta esta sendo enviada, por favor aguarde o redirecionamento automático para confirmar sua resposta");
            }

            document.getElementById("btn_save_primary").setAttribute("style","display: none;")
        });

        if(document.getElementsByClassName("sucesso").length > 0){

            setTimeout(function(){
                window.location = "./login.php";  
            },2000);
        }
        </script>
<?php
    }
?>
<? include "rodape.php"; ?>

