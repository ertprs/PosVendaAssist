<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
$admin_privilegios = "auditoria";
include_once 'autentica_admin.php';
include_once 'funcoes.php';

if ($login_fabrica == 117) {
    include_once('carrega_macro_familia.php');
}
$layout_menu = 'auditoria';
$title = traduz('RELATÓRIO - POSTOS NÃO USANDO');
include_once 'cabecalho_new.php';
$plugins = array(
	'datepicker',
	'mask',
	'dataTable',
	'select2'
);

include 'plugin_loader.php';
?>

<script language="JavaScript">
	function enviaEmail(status) {
		var url = "";
		url = "<? echo $PHP_SELF;?>?email=true&status=" + status ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
	}
	$(function()
	{
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$('#utilizacao').select2();
	});
				
</script>
<body>
<form name="frm_pesquisa" method="POST"  align="center" class="form-search form-inline tc_formulario">
	<div class="titulo_tabela "><?=traduz("Parâmetros de Pesquisa")?></div>
	<br>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group ">
				<label class="control-label" for="data_inicial"><?=traduz("Data Inicial")?></label>
				<div class="controls controls-row">
					<div class="span4">
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class="span12" value="<?=$_POST['data_inicial']?>" autocomplete="off">
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group ">
				<label class="control-label" for="data_final"><?=traduz("Data Final")?></label>
				<div class="controls controls-row">
					<div class="span4">
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class="span12" value="<?=$_POST['data_final']?>" autocomplete="off">
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group ">
				<label class="control-label" for="data_inicial"><?=traduz("Status:")?></label>
				<div class="controls controls-row">
					<input type="radio" name="status" value="CREDENCIADO" <?php echo ($_POST['status'] == 'CREDENCIADO') ? 'checked="true"' : '' ; ?>> <?=traduz("Credenciado")?>
					<input type="radio" name="status" value="DESCREDENCIADO" <?php echo ($_POST['status'] == 'DESCREDENCIADO') ? 'checked="true"' : '' ; ?>> <?=traduz("Descredenciado")?> <br>
					<input type="radio" name="status" value="EM CREDENCIAMENTO" <?php echo ($_POST['status'] == 'EM CREDENCIAMENTO') ? 'checked="true"' : '' ; ?>> <?=traduz("Em descredenciamento")?>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group ">
			    <label class="control-label" for="data_inicial"><?=traduz("Tipo Posto")?></label>
            	<div class="controls controls-row">
            	<? $sql_posto = "SELECT tipo_posto, descricao 
            					FROM tbl_tipo_posto 
            					WHERE fabrica = $login_fabrica
            					AND ativo = 't'";
				$res_posto = pg_query($con, $sql_posto); ?>
					<select name="tipo_posto" id="tipo_posto"> 		
						<option value=""><?=traduz("ESCOLHA")?></option> 
						<? for ($x = 0 ; $x < pg_num_rows($res_posto) ; $x++){
							$aux_linha = trim(pg_fetch_result($res_posto, $x, tipo_posto));
							$aux_nome = trim(pg_fetch_result($res_posto, $x, descricao));
							if ($aux_linha == $_POST['tipo_posto']) {
								$selected = "SELECTED";
							}
							else {
								$selected = "";
							}?>

							<option value='<?=$aux_linha?>' <?=$selected?>><?=$aux_nome?></option> <?
						} ?>
					</select>
				</div>
			</div>
		</div>
					<div class="span2"></div>
	</div>

<?php if ($login_fabrica == 117){ ?>		
	<div class="row-fluid">
		<div class="span2"></div>	
		<div class="span4">
				<div class="control-group ">
					    <label class="control-label" for="data_inicial"><?=traduz("Linha")?></label>
                    	<div class="controls controls-row">
                    	<? $sql_linha = "SELECT DISTINCT
											tbl_macro_linha.macro_linha AS linha,
											tbl_macro_linha.descricao AS nome
									  FROM tbl_macro_linha
									  JOIN tbl_macro_linha_fabrica USING(macro_linha)
            						  WHERE tbl_macro_linha_fabrica.fabrica = {$login_fabrica} 
            						  ORDER BY tbl_macro_linha.descricao";
							$res_linha = pg_query($con, $sql_linha); ?>
							<select name="macro_linha" id="macro_linha"> 		
								<option value=""><?=traduz("ESCOLHA")?></option> 
								<? for ($x = 0 ; $x < pg_num_rows($res_linha) ; $x++){
									$aux_linha = trim(pg_fetch_result($res_linha, $x, linha));
									$aux_nome = trim(pg_fetch_result($res_linha, $x, nome));
									if ($linha == $aux_linha) {
										$selected = "SELECTED";
									}
									else {
										$selected = "";
									}?>

									<option value='<?=$aux_linha?>' <?=$selected?>><?=$aux_nome?></option> <?
								} ?>
							</select> 					
						</div>
				</div>
			</div>

		<div class="span4">
			<div class="control-group ">
				<label class="control-label" for="data_inicial"><?=traduz("Macro Família")?></label>
				<div class="controls controls-row">
					<select id='linha' name='linha' class='frm'>\n";
							
					</select>
				</div>
			</div>
		</div>

						<div class="span2"></div>
	</div>

	<div class="row-fluid">
		<div class="span2"></div>
			<div class="span4">
				<div class="control-group ">
					    <label class="control-label" for="data_inicial"><?=traduz("Família")?></label>
                    	<div class="controls controls-row">
							<select id='familia' name='familia' class='frm'>
								
							</select>					
						</div>
				</div>
			</div>
	</div>
<?php }else{ ?>
	<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class="control-group ">
					    <label class="control-label" for="data_inicial"><?=traduz("Linha")?></label>
                    	<div class="controls controls-row">
                    	<? $sql_linha = "SELECT DISTINCT
											tbl_linha.linha,
											tbl_linha.nome
									  FROM tbl_linha
									  WHERE tbl_linha.fabrica = $login_fabrica
									  ORDER BY tbl_linha.nome ";
						$res_linha = pg_query($con, $sql_linha); ?>
							<select name="linha" id="linha"> 		
								<option value=""><?=traduz("ESCOLHA")?></option> 
								<? for ($x = 0 ; $x < pg_num_rows($res_linha) ; $x++){
									$aux_linha = trim(pg_fetch_result($res_linha, $x, linha));
									$aux_nome = trim(pg_fetch_result($res_linha, $x, nome));
									if ($aux_linha == $_POST['linha']) {
										$selected = "SELECTED";
									}
									else {
										$selected = "";
									}?>

									<option value='<?=$aux_linha?>' <?=$selected?>><?=$aux_nome?></option> <?
								} ?>
							</select> 					
						</div>
				</div>
			</div>
				<div class="span4">
					<div class="control-group ">
						<label class="control-label" for="data_inicial"><?=traduz("Familia")?></label>
						<div class="controls controls-row">
						<?
							$sql = "SELECT  *
									FROM    tbl_familia
									WHERE   tbl_familia.fabrica = $login_fabrica
									ORDER BY tbl_familia.descricao;";
							$res = pg_query($con,$sql);

							if (pg_num_rows($res) > 0) {
								echo "<select name='familia' class='frm'>\n";
								echo "<option value=''>".traduz("ESCOLHA")."</option>\n";
								for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
									$aux_familia   = trim(pg_fetch_result($res,$x,familia));
									$aux_descricao = trim(pg_fetch_result($res,$x,descricao));

									echo "<option value='$aux_familia'";
									if ($aux_familia == $_POST['familia']){
										echo " SELECTED ";
										$mostraMsgLinha = "<br>".traduz("da FAMÍLIA")."$aux_descricao";
									}
									echo ">$aux_descricao</option>\n";
								}
								echo "</select>\n&nbsp;";
							}
						?>
						</div>
					</div>
			</div>

						<div class="span2"></div>
	</div>
<?php } ?>

	<?php if( $telecontrol_distrib == 't' ) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group '>
					<label class='control-label' for='categoria_posto'>Utilização</label>
					<div class='controls controls-row'>
						<select id='utilizacao' name="utilizacao[]" multiple='multiple' class="frm">
		                    <option value="">ESCOLHA</option>
		                    <option value="os" <?= $utilizacao == 'os' ? 'selected' : null ?>> OSs </option>
		                    <option value="pedidos_faturados" <?= $utilizacao == 'pedidos_faturados' ? 'selected' : null ?>> Pedidos Faturados </option>
	             		</select>
					</div>
				</div>
			</div>
			<div class="span6"></div>
		</div>
	<?php } ?>

	<input type="hidden" id="btn_click" name="btn_acao" value=""><br>
	<div class="row-fluid">
        <!-- margem -->
        <div class="span4"></div>

        <div class="span4">
            <div class="control-group">
                <div class="controls controls-row tac">
                    <button type="submit" class="btn" value="Gravar" alt=<?=traduz("Gravar formulário")?>> <?=traduz("Filtrar")?></button>
                </div>
            </div>
        </div>

        <!-- margem -->
        <div class="span4"> </div>
    </div>
	<input type="hidden" name="token_form" class="token_form" value="TOKEN">
<?php
	$jsonPOST = excelPostToJson($_REQUEST);
	$jsonPOST = utf8_decode($jsonPOST);
?>
	<input type="hidden" id="jsonPOST_excel" value='<?=$jsonPOST?>' />
</form>
</body>


<?php

$status = $_GET['status'];
$email  = $_GET['email'];
if(strlen($email)==0) $email  = $_POST['email'];

if($email =='true'){ // HD 42311
	if(strlen($_POST['btn_mail']) > 0) {
		$titulo   = trim($_POST['titulo']);
		$conteudo = trim($_POST['conteudo']);
		if(strlen($conteudo)==0) {
			$msg_erro = traduz("Por favor, digite o conteúdo do E-mail");
		}

		if(strlen($titulo)==0) {
			$msg_erro = traduz("Por favor, digite o assunto do E-mail");
		}
		$status   = trim($_POST['status']);
		if ($status != '') {
			$cond = " AND tbl_posto_fabrica.credenciamento = '$status' ";
		}
		if(strlen($msg_erro) == 0) {
			$sql ="SELECT
						tbl_posto_fabrica.contato_email,
						tbl_posto.nome
					FROM tbl_posto
					JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					$cond
					AND tbl_posto.posto NOT IN (
						SELECT tbl_posto.posto
						FROM tbl_os
						JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_posto on tbl_posto.posto = tbl_os.posto
						WHERE tbl_os.fabrica = $login_fabrica
						$cond ";
			if($login_fabrica == 19){
				$sql .= " AND os_fechada='t' ";
			}
			$sql .= " GROUP BY tbl_posto.posto
					)
					GROUP BY
					tbl_posto_fabrica.contato_email,
					tbl_posto.nome
					ORDER BY
					tbl_posto.nome";
			$res = pg_exec ($con,$sql);
			if(pg_numrows($res) > 0){
				for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {

					if($i % 20 ==0 ) {
						sleep(10);
					}
					$contato_email = trim(pg_result($res,$i,contato_email));
					$nome          = pg_result($res,$i,nome);

					$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
					$destinatario = $nome ." <".$contato_email."> ";
					$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
					if(mail($destinatario, utf8_encode($titulo), utf8_encode($conteudo), $headers)) {
						$msg_erro= traduz("Email enviado com sucesso");
					};
				}
			}
		}
	}
	if(strlen($msg_erro) >0){
		echo "<table border='0' cellpadding='0' cellspacing='1' align='center' class='formulario' width = '700px'>";
		echo "<tr>";
		echo "<td valign='middle' align='center' class='error'>";
		echo $msg_erro;
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	echo "<form name='frm_mail' method='post' action='$PHP_SELF'>";
	echo "<div id='mensagem'>";
	echo "</div>";
	echo "<table width = '700px'><tr><td>";
	echo "<input type='hidden' name='email' value='true'>";
	echo "<input type='hidden' name='status' value='$status'>";
	echo traduz("Assunto")."</td><td><input type='text' size='40' name='titulo' value='$titulo'>";
	echo "</td></tr>";
	echo "<tr><td valign='top'>";
	echo traduz("Mensagem")."</td><td> <textarea name='conteudo' ROWS='10' COLS='48' class='input' value='$conteudo'></textarea>";
	echo "</td></tr>";
	echo "<tr><td align='center' colspan='100%'>";
	echo "<input type='hidden' name='btn_mail' value=''>";

	echo "<input type='button' name='btn_acao' value='".traduz("Enviar E-MAIL")."' onclick=\"javascript: if (document.frm_mail.btn_mail.value == '' ) { document.frm_mail.btn_mail.value='continuar' ;  document.frm_mail.submit(); document.getElementById('mensagem').innerHTML='".traduz("Por favor, não feche esta janela até aparecer a mensagem que foram enviados e-mails com sucesso.")."'; } else { alert ('".traduz("Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.")."') }\" >";

	echo "</td></tr></table>";

	echo "</form>";
	exit;
}

$layout_menu = "auditoria";
$titulo = traduz("Postos que NÃO estão utilizando o sistema");
$title = traduz("POSTOS QUE NÃO ESTÃO UTILIZANDO O SISTEMA");



$cond .= " AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
if ($_POST['status']) {
	$status = $_POST['status'];
	if ($status != '') {
		$cond = " AND tbl_posto_fabrica.credenciamento = '$status'";
	}
}
if ($_POST['tipo_posto']) {
	$tipo_posto = $_POST['tipo_posto'];
	if ($tipo_posto != '') {
		$cond .= " AND tbl_posto_fabrica.tipo_posto = $tipo_posto";
	}
}

if ($_POST['familia']) {
	$familia = $_POST['familia'];
	if ($familia != '') {
		$joinSub = " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto ";
		$condSub .= " AND tbl_produto.familia = $familia";
	}
}
if ($_POST['linha']) {
	$familia = $_POST['linha'];
	if ($familia != '') {
		$joinSub = " JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto ";
		$condSub .= " AND tbl_produto.linha = $linha";
	}
}

if( $utilizacao = $_POST['utilizacao'] ){
	foreach($utilizacao as $k => $v) {
	 	if( $v == 'os' ){
			$cond .= " AND tbl_posto_fabrica.digita_os IS TRUE";
		}
		if( $v == 'pedidos_faturados' ){
			$cond .= " AND tbl_posto_fabrica.pedido_faturado IS TRUE";
		}
	} 
}
	
	//HD38584
if (in_array($login_fabrica,array(50,51,86))) {
	$status = $_POST['status'];
	if ($status != '') {
		$cond = " AND tbl_posto_fabrica.credenciamento = '$status'";
	}
	echo "<br>";
	$form=array(
		'status' => array(
			'span' =>4,
			'label' => 'Status: ',
			'type' => 'select',
			'width' => 15,
			'options' => array(
				'CREDENCIADO'=>'CREDENCIADO',
				'DESCREDENCIADO'=>'DESCREDENCIADO',
				'EM CREDENCIAMENTO'=>'EM CREDENCIAMENTO',
				'EM DESCREDENCIAMENTO'=>'EM DESCREDENCIAMENTO',
			),
			'extra'=> array('onchange'=>'javascript: document.status.submit()')
		)
	);
	echo "<form action='$PHP_SELF' method='POST' name='status'>";
	echo montaForm($form,null);
	echo "</form>";
}

if($login_fabrica == '45' or $login_fabrica == 74){//HD 21829 16/6/2008
	$group_nks = "tbl_posto_fabrica.credenciamento,";
}

$sql = " WITH usando AS (
			SELECT DISTINCT( tbl_os.posto )
			FROM tbl_os
			$joinSub
			$condSub
			WHERE tbl_os.fabrica = $login_fabrica
			";

			if($login_fabrica == 19){
						$sql .= " and os_fechada='t' ";
			}
			if ($_POST['data_inicial'] && $_POST['data_final']) {
				$data_inicial = date_format(date_create_from_format( 'd/m/Y', $_POST['data_inicial']), 'Y-m-d');
				$data_final   = date_format(date_create_from_format( 'd/m/Y', $_POST['data_final']), 'Y-m-d');

				if ($data_inicial != '' && $data_final != '') {
					if ($login_fabrica == 117){
						$datas_between = "AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'";
					}else{
						$sql .= " and data_abertura between '$data_inicial' and '$data_final' " ;
					}
				}
			}			   
	
		$sql .= " ),
				nao_usando AS ( 
					SELECT DISTINCT( tbl_posto_fabrica.posto ), 
		    	   			tbl_posto_fabrica.codigo_posto,
				   			tbl_posto_fabrica.contato_cidade AS cidade,
				   			tbl_posto_fabrica.contato_estado AS estado,
				   			tbl_posto_fabrica.contato_email,
				   			tbl_posto_fabrica.contato_fone_residencial,
				   			tbl_posto_fabrica.contato_cel,
				   			tbl_posto_fabrica.data_input,
							tbl_posto.nome, 
							tbl_posto.cnpj,
							( SELECT os FROM tbl_os WHERE posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = $login_fabrica ORDER BY os DESC LIMIT 1 ) AS ultima_os
					FROM tbl_posto_fabrica
					JOIN tbl_posto ON (tbl_posto.posto = tbl_posto_fabrica.posto)
					WHERE fabrica = $login_fabrica
					$cond
					$join_nks
					AND tbl_posto_fabrica.posto NOT IN (SELECT posto FROM usando) 
				)

				SELECT 	*
				FROM nao_usando
				ORDER BY nome;";

if ($login_fabrica == 117){
	$drops = "DROP TABLE IF EXISTS p;
              DROP TABLE IF EXISTS o;";
    $qry = pg_query($con, $drops);

	$sql_p = "SELECT tbl_posto_fabrica.posto,
			       tbl_posto_fabrica.codigo_posto,
			       tbl_posto_fabrica.contato_cidade as cidade,
			       tbl_posto_fabrica.contato_estado as estado,
			       tbl_posto_fabrica.contato_email, 
			       tbl_posto_fabrica.data_input,
			       tbl_posto.nome,
			       tbl_posto.cnpj
			into temp p 
			FROM tbl_posto_fabrica 
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
			$cond";
	$res_p = pg_query($con, $sql_p);

	$sql_o = "SELECT DISTINCT ON (posto) 
				     tbl_os.posto,
				     tbl_os.data_abertura 
			  into temp o 
			  FROM tbl_os
			  JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			  $condSub
			  WHERE tbl_os.fabrica = $login_fabrica 
			  $datas_between";
	$res_o = pg_query($con, $sql_o);

	$sql = "SELECT p.*, 
                  ( SELECT data_abertura 
                    FROM tbl_os  
                    JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                    $condSub
                    WHERE fabrica = $login_fabrica 
                    AND posto = p.posto 
                    ORDER BY data_abertura DESC LIMIT 1) AS ultima_os
            FROM p 
            WHERE posto NOT IN (SELECT posto FROM o)";
}

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$total = pg_numrows($res);

	if ($login_fabrica == 50 or $login_fabrica ==51) {
		echo $status;
	}

	$styleGrid = $telecontrol_distrib == 't' ? "style='margin-left:-130px'" : null;
	$colspan = $telecontrol_distrib == 't' ? '10' : '7';

	echo "<div id='dvData' name='dvData' {$styleGrid}>";
	echo "<BR><table id='tabela_listagem' class='table table-striped table-bordered table-hover table-large' >";
	echo "<thead>";
	echo "<tr class='titulo_coluna'>";
	echo "<td colspan='{$colspan}' class='tac'><b>".traduz("Postos que não lançaram OS")."</b></td>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td><B>".traduz("CNPJ")."</B>";
	echo "</td>";
	echo "<td><B>".traduz("Código")."</B>";
	echo "</td>";

	if ($login_fabrica == 15) {
		echo "<td><B>".traduz("e-mail")."</B></td>";
	}

	if($login_fabrica ==50){
		echo "<td><B>".traduz("Qtd.OS Aberta")."</B>";
		echo "</td>";
	}

	echo "<td class='tac'><B>".traduz("Nome")."</B></td>";
	if( $telecontrol_distrib == 't' ){
		echo "<td><B>E-mail</B></td>";
			echo "<td><B>Telefone</B></td>";
		echo "<td><B>Telefone 2</B></td>";
	}
	echo "<td class='tac'><B>".traduz("Cidade")."</B>";
	echo "</td>";
	echo "<td><B>".traduz("Estado")."</B>";
	echo "</td>";
	echo "<td><B>".traduz("Credenciamento")."</B>";
	echo "</td>";
	echo "<td><B>".traduz("última OS aberta")."</B>";
	echo "</td>";
	echo "</tr>";
	echo "</thead>";
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$cnpj           = trim(pg_result($res,$i,cnpj));
		$cidade         = trim(pg_result($res,$i,cidade));
		$estado         = trim(pg_result($res,$i,estado));
		$nome           = trim(pg_result($res,$i,nome));
		$email          = trim(pg_result($res,$i,contato_email));
		$telefone 		= trim(pg_result($res,$i,contato_fone_residencial));
		$telefone2   	= trim(pg_result($res,$i,contato_cel));
		$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
		$posto          = trim(pg_result($res,$i,posto));
		$data_input     = trim(pg_result($res,$i,data_input));
		$data           = trim(pg_result($res,$i,data));
		$ultima_os		= trim(pg_result($res,$i,ultima_os));
		if (!empty($ultima_os)) {
			$sql_ultima_os = "SELECT data_abertura AS ultima_os FROM tbl_os WHERE os = $ultima_os AND fabrica = $login_fabrica";
			$res_ultima_os = pg_query($con, $sql_ultima_os);
			$ultima_os = pg_fetch_result($res_ultima_os, 0, 'ultima_os');
		}
		/*$posto          = trim(pg_result($res,$i,posto));*/

		$credenciamento = date_create(($data_input) ? $data_input : $data);
	
		echo "<tr>";
		echo "<td>$cnpj";
		echo "</td>";
		echo "<td>$codigo_posto";

		if($login_fabrica == 50 ){
			$sqlx = "
					SELECT count(tbl_os.os) as qtd_os
					FROM tbl_os
					JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto
					AND  tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto on tbl_posto.posto = tbl_os.posto
					WHERE tbl_os.fabrica = $login_fabrica and tbl_os.posto = $posto
					$cond
					$join_nks";

			$resx = pg_exec ($con,$sqlx);

			if (pg_numrows($resx) > 0) {
				$count         = trim(pg_result($resx,0,qtd_os));
				echo "<td>";
				echo "$count";
				echo "</td>";
			}
		}

		echo "</td>";

		if ($login_fabrica == 15) {
			echo "<td align='left'>$email";
			echo "</td>";
		}

		echo "<td align='left'>$nome";
		echo "</td>";

		if( $telecontrol_distrib == 't' ){
			echo "<td align='left'> {$email} </td>";
			echo "<td align='left'> {$telefone} </td>";
			echo "<td align='left'> {$telefone2} </td>";
		}

		echo "<td align='left'>$cidade";
		echo "</td>";
		echo "<td>$estado";
		echo "</td>";
		echo "<td>";
		echo date_format($credenciamento, 'd/m/Y');
		echo "</td>";
		if ($login_fabrica == 117){
			$ultima_os = empty($ultima_os) ? "" : date_format(date_create(($ultima_os)), 'd/m/Y');
			echo "<td>";
			echo $ultima_os;
			echo "</td>";	
		}else{
			echo "<td>";
			echo ($ultima_os) ? date_format(date_create(($ultima_os)), 'd/m/Y') : traduz("Nenhuma OS aberta");
			echo "</td>";
		}
		echo "</tr>";
	}


	echo "<tfoot>";
	echo "<tr>";
	echo "<td colspan='100%' class='titulo_coluna tac'>";
	echo traduz("Total de " ). $total .traduz(" postos");
	echo "</td>";
	echo "</tr>";
	echo "</tfoot>";

	if($login_fabrica==50) { // HD 42311
		echo "<tr bgcolor='#FFFFFF'><td colspan='100%' align='right'><br><input type='button' onClick=\"javascript: enviaEmail('$status');\" value='".traduz("Enviar E-mail")."'></td></tr>";
	}

	echo "</table><BR><BR>";
} else {
	/*echo "<BR><BR><table border='0' cellpadding='4' cellspacing='1' align='center' style='font-family: verdana; font-size: 11px'>";
	echo "<tr>";
	echo "<td>Todos os Postos estão utilizando o Sistema.";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";*/
	echo "<div class='alert'>";
	echo "<h4>".traduz("Nenhum resultado encontrado")."</h4>";
	echo "</div>";
}
$icon_excel  = "imagens/icon_csv.png";
$label_excel = traduz("Gerar Arquivo CSV");
$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
$resposta .="<tr>";
$resposta .= "<td id='gerar_excel' align='center' style='cursor: pointer; border: 0; font: bold 14px Arial;'><a style='text-decoration: none; '><img src='$icon_excel' height='40px' width='40px' align='absmiddle' >&nbsp;&nbsp;&nbsp;<span class='txt'>$label_excel</span></a></td>";
$resposta .= "</tr>";
$resposta .= "</table>";
echo $resposta;
?>
<script type="text/javascript">
	$('#gerar_excel').on('click', function(){
		param = '';
		var json = $.parseJSON($("#jsonPOST_excel").val());

		if(json.data_inicial != ''){
			param += '&inicial='+ json.data_inicial;
		}
		if(json.data_final != ''){
			param += '&final='+ json.data_final;
		}
		if(json.status != ''){
			param += '&status='+ json.status;
		}
		if(json.tipo_posto != ''){
			param += '&tipo_posto='+ json.tipo_posto;
		}
		if(json.linha != ''){
			param += '&linha='+ json.linha
		}
		if(json.familia != ''){
			param += '&familia='+ json.familia
		}
		if(json.utilizacao != ''){
			param += '&utilizacao='+ json.utilizacao
		}

		window.open("relatorio_postos_nao_usando.php?gerar_excel=true"+ param);
	});
	$.dataTableLoad({ table: "#tabela_listagem" });
</script>
<?php
include_once "rodape.php";
?>
