<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_admin.php";
	include 'funcoes.php';

	$status_juridico = "158,159,160";
	$status_os = $_POST['status_os'];


	$q = strtolower($_GET["q"]);
	if (isset($_GET["q"])) {

		$tipo_busca = $_GET["busca"];
		if (strlen($q) > 2) {
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			if ($tipo_busca == "codigo") {
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			} else {
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {

				for ($i = 0; $i < pg_numrows($res); $i++) {
					$cnpj         = trim(pg_result($res, $i, 'cnpj'));
					$nome         = trim(pg_result($res, $i, 'nome'));
					$codigo_posto = trim(pg_result($res, $i, 'codigo_posto'));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
		exit;
	}

	if($_POST['ajax'] == 'ajax'){
		
		if($_POST['acao'] == 'aprova_reprova'){
			$motivo = utf8_decode($_POST['motivo']);
			$os 	= $_POST['os'];
			$tipo 	= $_POST['tipo'];
			$status = ($tipo == 'aprovacao') ? 159 : 160;
			$erro   = false; 

			if(strlen($motivo) > 2){
				pg_query ($con,"BEGIN TRANSACTION");

				$sql = "INSERT INTO tbl_os_status
							(os,status_os,data,observacao, admin)
						VALUES
							($os, $status, current_timestamp, '$motivo', $login_admin)";
				
				if(pg_query($con,$sql)){
					if($tipo == 'reprovacao'){
						$sql = "SELECT fn_os_excluida($os,$login_fabrica, $login_admin);";
						if(pg_query($con,$sql)){
							$return = 2;
						}else{
							$erro = true;
							$return = pg_last_error($con);
						}
					}else{
						$return = 1; //sucesso!!!
					}
				}else{
					$erro = true;
					$return = 0;
				}

				if(!$erro){
					$sql = "SELECT posto, sua_os FROM tbl_os WHERE os = {$os} LIMIT 1";
					$res = pg_query($con, $sql);
					extract(pg_fetch_array($res));
					$motivo = "OS {$sua_os} - {$motivo}";

					$sql = "INSERT INTO tbl_comunicado (
								descricao              ,
								mensagem               ,
								tipo                   ,
								fabrica                ,
								obrigatorio_site       ,
								posto                  ,
								ativo
							) VALUES (
								(SELECT descricao FROM tbl_status_os WHERE status_os IN({$status})),
								'$motivo',
								'Auditoria de OS' ,
								$login_fabrica    ,
								't'               ,
								$posto,
								't'
							);";
					if(!pg_query($con,$sql))
						$erro = true;
				}

				if(!$erro){
					echo $return;
					pg_query($con,"COMMIT TRANSACTION;");
				}else{
					if(empty($return))
						echo 0;
					else
						echo $return;
					pg_query($con,"ROLLBACK TRANSACTION;");
				}

			}
		}
		exit;
	}

	if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
		$os 			= trim($_POST["os"]);
		$data_inicial 	= trim($_POST["data_inicial"]);
		$data_final 	= trim($_POST["data_final"]);
		$posto_codigo 	= trim($_POST["posto_codigo"]);
		$msg_erro 		= null;


		$_table = $_POST["status_os"] == 160 ? "tbl_os_excluida" : "tbl_os";

		if(!empty($os)){
		 	$sql = "SELECT os, sua_os FROM tbl_os WHERE sua_os = '{$os}' AND fabrica = $login_fabrica LIMIT 1;";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res)){
				$Xos = " AND {$_table}.os = ".pg_fetch_result($res, 0);
			}else{
				$msg_erro = "Número da OS inválido!";
			}
		}

		if(empty($msg_erro) AND empty($os)){
			if (empty($data_inicial) OR empty($data_final)) {
				$msg_erro = "Data Inválida!";
			}else{
				list($di, $mi, $yi) = explode("/", $data_inicial);
				if(@!checkdate($mi,$di,$yi)) 
					$msg_erro = "Data Inválida!";

				list($df, $mf, $yf) = explode("/", $data_final);
				if(@!checkdate($mf,$df,$yf)) 
					$msg_erro = "Data Inválida!";

				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";

				if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_final) > strtotime('today')){
					$msg_erro = "Data Inválida!";
				}

 				if (strtotime($aux_data_inicial.' + 2 month') < strtotime($aux_data_final) ) {
                	$msg_erro = "O intervalo entre as datas não pode ser maior que 2 meses!";
       			}

       			if(empty($msg_erro)){
       				$sql_data2 .= " AND {$_table}.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";		
       			}
			}
		}

		if(empty($msg_erro) AND empty($os) AND !empty($posto_codigo)){
			if (!empty($posto_codigo)) {
				$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$posto_codigo' AND fabrica = $login_fabrica";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res)){
					$posto = pg_result($res, 0);
					$sql_add .= " AND {$_table}.posto = '$posto' ";
				}else{
					$msg_erro = "Código do posto inválido!";
				}
			}
		}
	}

	$admin_privilegios = "gerencia,call_center";

	$layout_menu = "callcenter";
	$title       = "Intervenção de OS Bloqueada (Jurídica)";

	include "cabecalho.php"; 
?>

<style type="text/css" media="screen">

	.status_checkpoint{
		width:15px;
		height:15px;
		margin:2px 5px;
		padding:0 5px;
		border:1px solid #666;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.espaco{
		padding-left:120px;
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
	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
		width: 498px;
		padding: 2px 0;
		margin: 0 auto;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	.titulo_coluna {
		background-color: #596D9B;
		color: #FFFFFF;
		font: bold 11px "Arial";
		text-align: center;
	}
	/*ELEMENTOS DE POSICIONAMENTO*/
	#container {
	  border: 0px;
	  padding:0px 0px 0px 0px;
	  margin:0px 0px 0px 0px;
	  background-color: white;
	}
	#tooltip{  
		background: #FF9999;
		border:2px solid #000;
		display:none;
		padding: 2px 4px;
		color: #003399;
	}
</style>
<style type="text/css">
	@import "../plugins/jquery/datepick/telecontrol.datepick.css";
</style>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script>
<script src="js/jquery-1.3.2.js" ></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<?php include "javascript_pesquisas_novo.php"; ?>
<script type="text/javascript">
	$(document).ready(function() {
		Shadowbox.init();

		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");		

		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

		function formatResult(row) {
			return row[2];
		}

		/* Busca pelo Código */
		$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#posto_codigo").result(function(event, data, formatted) {
			$("#posto_nome").val(data[1]) ;
		});

		/* Busca pelo Nome */
		$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#posto_codigo").val(data[2]) ;
		});


		$(".aprova_reprova").click(function(){
			var acao = $(this).attr('name');
			var os   = $(this).parent().parent().attr('title');

			var pergunta = (acao == 'aprovacao') ? "Deseja aprovar está OS?" : "Deseja reprovar está OS?";
			if(confirm(pergunta)){
				var motivo = (acao == 'aprovacao') ? "Informe o motivo da aprovação!" : "Informe o motivo da reprovação!";
				var motivo = prompt(motivo);

				if(motivo.length > 3 && motivo != 'undefined'){
					$.ajax({
						type:    'POST',
						url:     "<?php echo $_SERVER['PHP_SELF']; ?>",
						data:    "ajax=ajax&acao=aprova_reprova&os="+os+"&motivo="+motivo+"&tipo="+acao,
						success: function(data){
							if(data == 1){
								//$("#ln_"+os).html("&nbsp;");
								$("#ln_"+os).parent().fadeOut(300);
							}else if(data == 2){
								$("#ln_"+os).parent().fadeOut(300);
							}else{
								if(data.length > 4){
									//$("#ln_"+os).html("&nbsp;");
									alert(data);
								}
							}
						}
					});
				}else{
					alert("Motivo informado inválido!");
					return false;
				}
			}else{
				return false;
			}
		});	
	});

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
		gravaDados("posto_codigo",codigo_posto);
		gravaDados("posto_nome",nome);
}
</script>

<?php
	if(!empty($msg_erro)){
		echo "<div class='msg_erro'>{$msg_erro}</div>";
	}
?>
<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">
	<table align="center" class="formulario" width="500" border="0" cellspacing='1' cellpadding='4' >
		<thead>
			<tr class="titulo_tabela">
				<td colspan='4'>Parâmetros de Pesquisa</td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td width='50'>&nbsp;</td>
				<td width='200'>&nbsp;</td>
				<td width='200'>&nbsp;</td>
				<td width='50'>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2">
					Número da OS<br>
					<input type="text" name="os" id="os" size="15" maxlength="20" value="<?php echo $os ?>" class="frm" />
				</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					Data Inicial<br>
					<input type="text" name="data_inicial" id="data_inicial" size="15" maxlength="10" value="<? echo $data_inicial ?>" class="frm" />
				</td>
				<td>
					Data Final<br>
					<input type="text" name="data_final" id="data_final" size="15" maxlength="10" value="<? echo $data_final ?>" class="frm" />
				</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					Código Posto<br>
					<input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm" />&nbsp;
					<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código"  onclick="fnc_pesquisa_posto_2(document.frm_consulta.posto_codigo, 'codigo');" />
				</td>
				<td colspan='2'>
					Nome do Posto<br>
					<input type="text" name="posto_nome" id="posto_nome" size="20"  value="<? echo $posto_nome ?>" class="frm" />&nbsp;
					<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto_2(document.frm_consulta.posto_nome, 'nome');" />
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2">
					Status<br>
					<select class='frm' name='status_os' style='width: 160px;'>
					<?php 
						$sql = "SELECT status_os AS status, descricao FROM tbl_status_os WHERE status_os IN({$status_juridico});";
						$res = pg_exec($con, $sql);

						for ($i = 0; $i < pg_numrows($res); $i++) {
							extract(pg_fetch_array($res)); ?>
							<option value="<?php echo $status;?>" <? if ($status_os == $status) echo " selected ";?>><?php echo $descricao;?></option><?php
						}
					?>
					</select>
				</td>
				<td>&nbsp;</td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="4" style='text-align: center; padding: 20px;'>
					<input type='hidden' name='btn_acao' value=''>
					<input type='button' onclick="javascript: if ( document.frm_consulta.btn_acao.value == '' ) { document.frm_consulta.btn_acao.value='Pesquisar'; document.frm_consulta.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " value='Pesquisar'>
				</td>
			</tr>
		</tfoot>
	</table>
</form>


<?php

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
	$sql = "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
				SELECT	ultima.os,
						(	
							SELECT status_os 
							FROM tbl_os_status
							JOIN {$_table} USING(os)
							WHERE status_os IN ($status_juridico) 
							AND tbl_os_status.os = ultima.os 
							AND tbl_os_status.fabrica_status = {$_table}.fabrica
							AND {$_table}.fabrica = $login_fabrica
							AND tbl_os_status.extrato IS NULL
							$sql_add2
							$sql_add
							$sql_data
							$sql_data2
							$sql_data_fechamento
							$sql_defeito_constatado
							$Xos
							ORDER BY os_status DESC 
							LIMIT 1
						) AS ultimo_status
				FROM (
						SELECT DISTINCT os 
						FROM tbl_os_status 
						JOIN {$_table} USING(os)
						WHERE status_os IN ($status_juridico) 
						AND tbl_os_status.fabrica_status = {$_table}.fabrica
						AND {$_table}.fabrica = $login_fabrica
						$sql_add
						$sql_data
						$sql_data2
						$sql_data_fechamento
						$sql_defeito_constatado
						$Xos
				) ultima
			) interv
			WHERE interv.ultimo_status IN ($status_os);

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			SELECT	{$_table}.os                                                   ,
					{$_table}.serie                                                ,
					{$_table}.sua_os                                               ,
					{$_table}.consumidor_nome                                      ,
					{$_table}.consumidor_fone                                      ,
					TO_CHAR({$_table}.data_abertura,'DD/MM/YYYY')  AS data_abertura,
					TO_CHAR({$_table}.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
					TO_CHAR({$_table}.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					{$_table}.nota_fiscal                                          ,
					TO_CHAR({$_table}.data_nf,'DD/MM/YYYY')              AS data_nf,
					{$_table}.fabrica                                              ,
					{$_table}.posto                                              ,
					{$_table}.consumidor_nome                                      ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto.estado                   AS posto_estado          ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_email       AS posto_email        ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					tbl_os_extra.os_reincidente                                 ,
					(SELECT status_os FROM tbl_os_status WHERE {$_table}.os = tbl_os_status.os AND status_os IN ($status_juridico) AND tbl_os_status.extrato IS NULL ORDER BY data DESC LIMIT 1) AS status_os";
				
	$sql .= " FROM tmp_interv_$login_admin X
				JOIN {$_table}            ON {$_table}.os           = X.os
				JOIN tbl_os_extra      ON {$_table}.os           = tbl_os_extra.os 
				$join_os_status 
				JOIN tbl_produto       ON tbl_produto.produto = {$_table}.produto
				JOIN tbl_posto         ON {$_table}.posto        = tbl_posto.posto
				JOIN tbl_posto_fabrica ON {$_table}.posto        = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			 WHERE {$_table}.fabrica = $login_fabrica 
				AND  tbl_os_extra.extrato IS NULL 
				$sql_add 
				$sql_data  
				$sql_data2 
			 GROUP BY {$_table}.os, 
				{$_table}.serie ,
				{$_table}.sua_os ,
				{$_table}.consumidor_nome ,
				{$_table}.consumidor_fone ,
				data_abertura,
				data_fechamento,
				data_digitacao,
				{$_table}.nota_fiscal ,
				data_nf,
				{$_table}.fabrica ,
				{$_table}.posto ,
				posto_nome ,
				posto_estado,
				tbl_posto_fabrica.codigo_posto ,
				posto_email ,
				produto_referencia ,
				produto_descricao ,
				tbl_produto.voltagem ,
				tbl_os_extra.os_reincidente
			ORDER BY tbl_posto.nome, {$_table}.os";
	//echo nl2br($sql);
	$res  = pg_query($con,$sql);
	echo pg_last_error($con);

	if (pg_numrows($res) > 0) {

		echo "<br><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";
			echo "<input type='hidden' name='data_inicial' 				value='$data_inicial' />";
			echo "<input type='hidden' name='data_final'   				value='$data_final' />";
			echo "<input type='hidden' name='aprova'       				value='$aprova' />";
			echo "<input type='hidden' name='posto_codigo'				value='$posto_codigo' />";
			echo "<input type='hidden' name='filtro_check_estado'		value='$filtro_check_estado' />";
			echo "<input type='hidden' name='filtro_pesquisa_estado'	value='$filtro_pesquisa_estado' />";
			echo "<input type='hidden' name='posto_nome'   				value='$posto_nome' />";
			echo "<input type='hidden' name='check_data_fechamento'  	value='$check_data_fechamento' />";
			echo "<input type='hidden' name='check_defeito_constatado'	value='$check_defeito_constatado' />";
			echo '<table align="center" width="1200px" cellspacing="1" class="tabela">';
				echo "<tr>";
					echo "<td align='left' colspan='9' style='border: none'>Este relatório considera a data de digitação da OS</td>";
				echo "</tr>";
				echo "<tr class='titulo_tabela'>";
					echo "<td>OS</td>";
					echo "<td>Série</td>";
					echo "<td>Data Abertura</td>";
					echo "<td width='20'>Posto</td>";
					echo "<td width='20'>UF</td>";
					echo "<td>Nota Fiscal</td>";
					echo "<td>Consum.</td>";
					echo "<td>Produto</td>";
					if(in_array('158', explode(',', $status_os))){
						echo "<td>Ações</td>";
						$acoes = true;
					}
				echo "</tr>";

				for ($x = 0; $x < pg_num_rows($res); $x++) {
					$os						= pg_result($res, $x, 'os');
					$serie					= pg_result($res, $x, 'serie');
					$data_abertura			= pg_result($res, $x, 'data_abertura');
					$data_fechamento		= pg_result($res, $x, 'data_fechamento');
					$sua_os					= pg_result($res, $x, 'sua_os');
					$codigo_posto			= pg_result($res, $x, 'codigo_posto');
					$posto					= pg_result($res, $x, 'posto');
					$posto_nome				= pg_result($res, $x, 'posto_nome');
					$posto_email			= pg_result($res, $x, 'posto_email');
					$posto_estado			= pg_result($res, $x, 'posto_estado');
					$nota_fiscal			= pg_result($res, $x, 'nota_fiscal');
					$data_nf				= pg_result($res, $x, 'data_nf');
					$consumidor_nome		= pg_result($res, $x, 'consumidor_nome');
					$consumidor_revenda     = pg_result($res, $x, 'consumidor_revenda');
					$revenda_nome           = pg_result($res, $x, 'revenda_nome');
					$consumidor_fone		= pg_result($res, $x, 'consumidor_fone');
					$produto_referencia		= pg_result($res, $x, 'produto_referencia');
					$produto_descricao		= pg_result($res, $x, 'produto_descricao');
					$produto_voltagem		= pg_result($res, $x, 'voltagem');
					$data_digitacao			= pg_result($res, $x, 'data_digitacao');
					$data_abertura			= pg_result($res, $x, 'data_abertura');
					$status					= pg_result($res, $x, 'status_os');
					$status_observacao		= pg_result($res, $x, 'status_observacao');
					$status_descricao		= pg_result($res, $x, 'status_descricao');
					$os_reincidente			= pg_result($res, $x, 'os_reincidente');
					$obs_reincidencia		= pg_result($res, $x, 'obs_reincidencia');
					$defeito_constatado		= pg_result($res, $x, 'defeito_constatado');
					$defeito_reclamado		= pg_result($res, $x, 'defeito_reclamado');
					if (empty($sua_os))
						$sua_os = $os;

					$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
					echo "<tr bgcolor='$cor' id='linha_$x' title='$os'>";
					$link = $status_os == 160 ? "os_consulta_excluida.php?os=$os" : "os_press.php?os=$os";

						echo "<td><a href='{$link}' target='_blank'>$sua_os</a> </td>";
						echo "<td>{$serie}</td>";
						echo "<td>$data_abertura</td>";
						echo "<td align='left' title='".$codigo_posto." - ".$posto_nome."' nowrap>".substr($posto_nome,0,20) ."...</td>";
						echo "<td>$posto_estado</td>";
						echo "<td>$nota_fiscal</td>";
						echo "<td align='left'>$consumidor_nome</td>";
						echo "<td align='left' title='Produto: $produto_referencia - $produto_descricao' style='cursor:help'>".substr($produto_descricao,0,20)."</td>";
						if($acoes){
							echo "<td align='center' nowrap id='ln_$os'>";
								if($status == 158){
									echo "<input type='button' name='aprovacao'  class='aprova_reprova' value=' Aprovar '  />&nbsp;";
									echo "<input type='button' name='reprovacao' class='aprova_reprova' value=' Reprovar ' />";
								}else{
									echo "&nbsp;";
								}
							echo "</td>";
						}
					echo "</tr>";
				}
			echo "</table>";
			echo "<input type='hidden' name='motivo_recusa' id='motivo_recusa'>";
			echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</form>";

	} else {
		echo "<div align='center' style='font:bold 14px Arial'>Nenhuma OS Encontrada.</div>";
	}
}
echo "<br /><br /><br />";
include "rodape.php" ?>
</div>
