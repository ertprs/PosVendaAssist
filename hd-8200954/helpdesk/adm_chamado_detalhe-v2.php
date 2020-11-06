<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
}


if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

if($_POST['btn_tranferir']) $btn_tranferir = trim ($_POST['btn_tranferir']);

if (strlen ($btn_tranferir) > 0) {
if($_POST['transfere'])           { $transfere         = trim ($_POST['transfere']);}
		$sql =" UPDATE tbl_hd_chamado 
				SET status = '$status' , 
					titulo = '$titulo',
					atendente = $transfere
				WHERE hd_chamado = $hd_chamado";
		$res = pg_exec ($con,$sql);
}




if (strlen ($btn_acao) > 0) {

	if($_POST['comentario'])          { $comentario      = trim ($_POST['comentario']);}
	if($_POST['titulo'])              { $titulo          = trim ($_POST['titulo']);}
	if($_POST['status'])              { $status          = trim ($_POST['status']);}
	if($_POST['transfere'])           { $transfere       = trim ($_POST['transfere']);}
	if($_POST['categoria'])           { $categoria       = trim ($_POST['categoria']);}
	if($_POST['sequencia'])           { $sequencia       = trim ($_POST['sequencia']);}
	if($_POST['duracao'])             { $duracao         = trim ($_POST['duracao']);}
	if($_POST['interno'])             { $interno         = trim ($_POST['interno']);}
	if($_POST['exigir_resposta'])     { $exigir_resposta = trim ($_POST['exigir_resposta']);}

	$sql = "SELECT categoria , status FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);
	$categoria_anterior = pg_result ($res,0,categoria);
	$status_anterior    = pg_result ($res,0,status);

	if (strlen($comentario) < 3)$msg_erro="Coment�rio muito pequeno";

	#-------- De An�lise para Execu��o -------
	if (strlen ($sequencia) == 0 AND $status == "An�lise" AND $status_anterior == "An�lise") {
		$msg_erro = "Escolha a seq��ncia da tarefa. Ou continua em an�lise, ou vai para Execu��o.";
	}
	if ($sequencia == "SEGUE" AND $status_anterior == "An�lise") $status = "Execu��o" ;
	if ($status == "Execu��o" AND $status_anterior == "An�lise" AND strlen ($duracao) == 0) {
		$msg_erro = "Informe o tempo de dura��o previsto para execu��o deste chamado.";
	}
	$duracao = str_replace (',','.',$duracao);
	if (strlen ($duracao) == 0) $duracao = "null";

	#-------- De Execu��o para Resolvido -------
	if (strlen ($sequencia) == 0 AND $status == "Execu��o" AND $status_anterior == "Execu��o") {
		$msg_erro = "Escolha a seq��ncia da tarefa. Ou continua em execu��o ou est� resolvido.";
	}
	if ($sequencia == "SEGUE" AND $status_anterior == "Execu��o") $status = "Resolvido" ;


	
	if ($status == "Novo" AND $status_anterior == "Novo") $status = "An�lise";

/*takashi colocou isso 30-05-07 2495 qdo solicitamos resposta do usu�rio e interagimos no chamado, setava como false novamente.. se der problema pode retirar*/
		$sql = "Select exigir_resposta from tbl_hd_chamado where hd_chamado=$hd_chamado";
		$res = pg_exec ($con,$sql);
		$xexigir_resposta = pg_result($res,0,0);

		//wellington colocou este if, pois este campo pode ser nulo e para chamados novos nao estava setando exigir resposta
		if (strlen($xexigir_resposta)==0) $xexigir_resposta = 'f';
/*takashi 30-05-07*/

	
	if (strlen ($exigir_resposta) > 0) $exigir_resposta = 't';
	else $exigir_resposta = 'f';
	
	if (strlen ($interno) > 0) $xinterno = 't';
	else $xinterno = 'f';
	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"BEGIN TRANSACTION");
		
		$sql =" UPDATE tbl_hd_chamado 
				SET status = '$status' , 
					titulo = '$titulo',
					categoria = '$categoria', ";
if($xexigir_resposta=='f')$sql .= " exigir_resposta = '$exigir_resposta', ";/*takashi colocou isso 30-05-07 2495 */
				$sql .= " duracao = $duracao
					
				WHERE hd_chamado = $hd_chamado";

		$res = pg_exec ($con,$sql);

		if ($categoria <> $categoria_anterior) {
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin) VALUES ($hd_chamado, 'Categoria Alterada de $categoria_anterior para $categoria',$login_admin)";
			$res = pg_exec ($con,$sql);
		}

		if ($status == "Resolvido" AND $status_anterior == "Execu��o") {
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin) VALUES ($hd_chamado, 'Chamado resolvido. Se voc� n�o concordar com a solu��o basta inserir novo coment�rio para reabrir o chamado.',$login_admin)";
			$res = pg_exec ($con,$sql);
		}

		if (strlen ($comentario) > 0) {
			$sql ="INSERT INTO tbl_hd_chamado_item (
						hd_chamado                                                   ,
						comentario                                                   ,
						admin                                                        ,
						status_item                                                  ,
						interno
					) VALUES (
						$hd_chamado                                                  ,
						'$comentario'                                                ,
						$login_admin                                                 ,
						'$status'                                                    ,
						'$xinterno'
					);";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			//--======================================================================
			$sql = "SELECT hd_chamado_atendente,
											hd_chamado
							FROM tbl_hd_chamado_atendente 
							WHERE admin = $login_admin 
							AND   data_termino IS NULL
							ORDER BY hd_chamado_atendente DESC LIMIT 1";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (pg_numrows($res) > 0) {
				$hd_chamado_atendente =  pg_result($res,0,hd_chamado_atendente);
				$hd_chamado_atual     = pg_result($res,0,hd_chamado);
			}

			if($hd_chamado_atual <> $hd_chamado){
				if(strlen($hd_chamado_atendente)>0){
					$sql = "UPDATE tbl_hd_chamado_atendente
									SET data_termino = CURRENT_TIMESTAMP
									WHERE hd_chamado_atendente = $hd_chamado_atendente
									AND   admin               =  $login_admin
									AND   data_termino IS NULL
									";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
				$sql = "INSERT INTO tbl_hd_chamado_atendente(
												hd_chamado ,
												admin      ,
												data_inicio
										)VALUES(
										$hd_chamado       ,
										$login_admin      ,
										CURRENT_TIMESTAMP
										)";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$sql="SELECT CURRVAL('seq_hd_chamado_atendente');";
				$res = pg_exec ($con,$sql);
				$hd_chamado_atendente =  pg_result($res,0,0);
			}
			if($status == 'Resolvido'){
				$sql = "UPDATE tbl_hd_chamado_atendente
					SET data_termino = CURRENT_TIMESTAMP
					WHERE admin                = $login_admin
					AND   hd_chamado           = $hd_chamado
					AND   hd_chamado_atendente = $hd_chamado_atendente";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql= "UPDATE tbl_controle_acesso_arquivo SET
					data_fim = CURRENT_DATE,
					hora_fim = CURRENT_TIME,
					status   = 'finalizado'
					WHERE hd_chamado = $hd_chamado";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

		}


		$msg_erro = substr($msg_erro,6);
		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro = 'N�o foi poss�vel Inserir o Chamado';
		}else{
			$res = @pg_exec($con,"COMMIT");
			if($status == 'Resolvido' OR $exigir_resposta == 't'){
				$sql="SELECT nome_completo,email,tbl_admin.admin 
							FROM tbl_admin 
							JOIN tbl_hd_chamado ON tbl_hd_chamado.admin = tbl_admin.admin 
							WHERE hd_chamado = $hd_chamado";
				$res = pg_exec ($con,$sql);
				$email                = pg_result($res,0,email);
				$nome                 = pg_result($res,0,nome_completo);
				$adm                  = pg_result($res,0,admin);
				
				$chave1=md5($hd_chamado);
				$chave2=md5($adm);
				$email_origem  = "suporte@telecontrol.com.br";
				$email_destino = $email;
				$assunto       = "Seu chamado n� $hd_chamado foi RESOLVIDO";
				$corpo.="<P align=left><STRONG>Nota: Este e-mail � gerado automaticamente. **** POR FAVOR 
						N�O RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> - 
						<STRONG>$titulo</STRONG>&nbsp; </P>
						<P align=left>$nome,</P>
						<P align=justify>Seu chamado foi&nbsp;<FONT 
						color=#006600><STRONG>resolvido</STRONG></FONT> pelo suporte Telecontrol, voc� 
						tem <U>3(tr�s) dias para concordar com a solu��o do chamado</U>. Caso 
						<STRONG>n�o haja manifesta��o</STRONG>&nbsp;ser� 
						considerado&nbsp;<STRONG>resolvido automaticamente. </STRONG>Caso 
						n�o concorde&nbsp;com a resolu��o do chamado <STRONG>insira um 
						coment�rio</STRONG> para <STRONG>reabrir o chamado</STRONG>.</P>
						<P align=justify>Se ap�s este prazo o problema/d�vida continuar, abra um novo 
						chamado.</P>
						<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
						<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br 
						</P>";

				if($exigir_resposta=='t' and $status<>'Resolvido' ){

					$assunto       = "Seu chamado n� $hd_chamado est� aguardando sua resposta";

					$corpo = "<P align=left><STRONG>Nota: Este e-mail � gerado automaticamente. **** POR FAVOR 
							N�O RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
							<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> - 
							<STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$nome,</P>

							<P align=justify>
							Precisamos de sua posi��o para continuarmos atendendo o chamado. 
							</P>
							<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br 
							</P>";
				}

				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";

				if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
					$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
				}else{
					$msg_erro = "N�o foi poss�vel enviar o email. Por favor entre em contato com a TELECONTROL.";
				}
			}
			header ("Location: adm_chamado_detalhe.php?hd_chamado=$hd_chamado");
		}
	}
}



if(strlen($hd_chamado) > 0){
	$sql = "UPDATE tbl_hd_chamado SET atendente = $login_admin WHERE hd_chamado = $hd_chamado AND atendente IS NULL";
	$res = pg_exec ($con,$sql);

	$sql= " SELECT tbl_hd_chamado.hd_chamado                             ,
					tbl_hd_chamado.admin                                 ,
					to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
					tbl_hd_chamado.titulo                                ,
					tbl_hd_chamado.categoria                             ,
					tbl_hd_chamado.status                                ,
					tbl_hd_chamado.duracao                               ,
					tbl_hd_chamado.atendente                             ,
					tbl_hd_chamado.fabrica_responsavel                   ,
					tbl_hd_chamado.prioridade                            ,
					tbl_hd_chamado.prazo_horas                           ,
					tbl_fabrica.nome   AS fabrica_nome                   ,
					tbl_admin.login                                      ,
					tbl_admin.nome_completo                              ,
					tbl_admin.fone                                       ,
					tbl_admin.email                                      ,
					atend.nome_completo AS atendente_nome
			FROM tbl_hd_chamado
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
			LEFT JOIN tbl_admin atend ON tbl_hd_chamado.atendente = atend.admin
			WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$admin                = pg_result($res,0,admin);
		$data                 = pg_result($res,0,data);
		$titulo               = pg_result($res,0,titulo);
		$categoria            = pg_result($res,0,categoria);
		$status               = pg_result($res,0,status);
		$duracao              = pg_result($res,0,duracao);
		$atendente            = pg_result($res,0,atendente);
		$atendente_nome       = pg_result($res,0,atendente_nome);
		$fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
		$nome                 = pg_result($res,0,nome_completo);
		$email                = pg_result($res,0,email);
		$prioridade           = pg_result($res,0,prioridade);
		$fone             = pg_result($res,0,fone);
		$nome_completo        = pg_result($res,0,nome_completo);
		$fabrica_nome         = pg_result($res,0,fabrica_nome);
		$login                = pg_result($res,0,login);
		$prazo_horas          = pg_result($res,0,prazo_horas);
	}else{
		$msg_erro="Chamado n�o encontrado";
	}
}



$TITULO = "ADM - Responder Chamado";

include "menu.php";
?>
<script type="text/javascript" src="js/ajax_busca.js"></script>
<script>
function recuperardados(hd_chamado) {
	var programa = document.frm_chamada.programa.value;
	if(programa.length > 4 ){
		var busca = new BUSCA();
		busca.Updater("ajax_listar_programa.php?digito="+programa+"&hd_chamado="+escape(hd_chamado),"conteudo","get","carregando os dados...");
	}
}
</script>




<table width = '750' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>

<form name='frm_chamada' action='<? echo $PHP_SELF ?>' method='post' >
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado?>'>

<?
if (strlen ($hd_chamado) > 0) {
/*	echo "<tr>";
	echo "<td colspan='4' align='center' class = 'Titulo2' height='30'><strong>Chamado n�. $hd_chamado </strong></td>";
	echo "</tr>";*/
}
?>


<tr>
	<td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px '><strong>&nbsp;Login </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<?= $login ?> </td>
	<td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Abertura </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $data ?> </td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Prioridade </strong></td>

	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'align='center' >
	<?
	if    ($prioridade==0) echo "<font color='#FF0000' ><img src='/assist/admin/imagens_admin/status_vermelho.gif'> <b>ALTA</b></font>";
	elseif($prioridade==5) echo "<font color='#006600' ><img src='/assist/admin/imagens_admin/status_verde.gif'> <b>NORMAL</b></font>";
	else echo "NORMAL";
	?>
	</td>

</tr>




<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px;'><strong>&nbsp;T�tulo </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' ><input type='text' size='30' name='titulo' value='<?= $titulo ?>' class='caixa'> </td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Categoria </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' >
		<select name="categoria" size="1" >
		<option value='Alteracao'      <? if($categoria=='Alteracao')      echo ' SELECTED '?> >Altera��o de Dados</option>
		<option value='Mudanca'        <? if($categoria=='Mudanca')        echo ' SELECTED '?> >Mudan�a em Tela ou Processo</option>
		<option value='Melhoria'       <? if($categoria=='Melhoria')       echo ' SELECTED '?> >Sugest�o de Melhorias</option>
		<option value='Novo'           <? if($categoria=='Novo')           echo ' SELECTED '?> >Novo Programa ou Processo</option>
		<option value='Erro'           <? if($categoria=='Erro')           echo ' SELECTED '?> >Erro em programa</option>
		</select>
	</td>
	<td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' rowspan='2'colspan='2'align='center' valign='middle'>CHAMADO N�<br><h1><?=$hd_chamado?></h1></td>

</tr>


<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Nome </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='3'>&nbsp;<input type='text' size='50' name='nome' value='<?= $nome ?>' <? if (strlen ($hd_chamado) > 0) echo " readonly " ?> class='caixa'></td>


	
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Email </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<input type='text' size='30' name='email' value='<?= $email ?>' <? if (strlen ($hd_chamado) > 0) echo " readonly " ?> class='caixa'></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Fone </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>&nbsp;<input type='text' size='25' name='fone' value='<?= $fone ?>' <? if (strlen ($hd_chamado) > 0) echo " readonly " ?> class='caixa'></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Prazo </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'align='center' ><?=$prazo_horas?> Hrs</td>
</tr>

</table>


<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
		tbl_hd_chamado_item.comentario                               ,
		tbl_hd_chamado_item.interno                                  ,
		tbl_admin.nome_completo                            AS autor  ,
		tbl_hd_chamado_item.status_item
		FROM tbl_hd_chamado_item 
		JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
		WHERE hd_chamado = $hd_chamado
		ORDER BY hd_chamado_item";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	echo "<table width = '750' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='5' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Intera��es</b></td>";
	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td ><strong><font size='2'>N�</font></strong></td>";
	echo "<td nowrap><strong><font size='2'>Data e Hora</font></strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
	echo "<td ><strong><font size='2'>  Coment�rio </strong></font></td>";
	echo "<td ><strong><font size='2'> Anexo </strong></font></td>";
	echo "<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><font size='2'><strong>Autor </strong></font></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$x=$i+1;
		$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
		$data_interacao  = pg_result($res,$i,data);
		$autor           = pg_result($res,$i,autor);
		$item_comentario = pg_result($res,$i,comentario);
		$status_item     = pg_result($res,$i,status_item);
		$interno         = pg_result($res,$i,interno);
		
		if ($interno == 't'){
			if($cor == '#FFFFCC') $cor = '#FFFFEE';
			else                  $cor = '#FFFFCC';
		}else{
			$cor='#ffffff';
			if ($i % 2 == 0)     $cor = '#F2F7FF';
		}
		if ($status_item == 'Resolvido')$cor2 = '#82FFA2';

		if($interno == 't'){
			echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor'>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "<td colspan='5' align='center'><b>Chamado interno</b></td>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9' align='bottom'></td>";
			echo "</tr>";
		}

		echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap width='50'>$x </td>";
		echo "<td nowrap>$data_interacao </td>";
		echo "<td ><font size='1'>" . nl2br(str_replace($filtro,"", $item_comentario)) . "</td>";

		echo "<td>";
		$dir = "documentos/";
		$dh  = opendir($dir);
//		echo "$hd_chamado_item";
		while (false !== ($filename = readdir($dh))) {
			if (strpos($filename,"$hd_chamado_item") !== false){
			//echo "$filename\n\n";
				$po = strlen($hd_chamado_item);
				if(substr($filename, 0,$po)==$hd_chamado_item){

					echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=documentos/$filename target='blank'><img src='imagem/clips.gif' border='0'>Baixar</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
				}
				
			}
		}
		echo "</td>";
		echo "<td nowrap >$autor</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9' align='bottom'></td>";
		echo "</tr>";
		if ($status_item == 'Resolvido'){
			echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor2'>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "<td colspan='5' align='center'><b>Chamado foi resolvido nesta intera��o</b></td>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9' align='bottom'></td>";
			echo "</tr>";
		}

	}	
	
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='5' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>";
}

if (strlen ($hd_chamado) > 0) {
?>
<table width = '750' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Status </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<select name="status" size="1">
		<!--<option value=''></option>-->
		<option value='Novo'      <? if($status=='Novo')      echo ' SELECTED '?> >Novo</option>
		<option value='An�lise'   <? if($status=='An�lise')   echo ' SELECTED '?> >An�lise</option>
		<option value='Execu��o'  <? if($status=='Execu��o')  echo ' SELECTED '?> >Execu��o</option>
		<option value='Aprova��o' <? if($status=='Aprova��o') echo ' SELECTED '?> >Aprova��o</option>
		<option value='Cancelado' <? if($status=='Cancelado') echo ' SELECTED '?> >Cancelado</option>
		<option value='Resolvido' <? if($status=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
		</select>
	</td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Analista </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $atendente_nome ?></td>
	<td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' rowspan='2'colspan='2'align='center' valign='middle'>CHAMADO N�<br><h1><?=$hd_chamado?></h1></td>

</tr>
<? } ?>


<?
if ($status == "An�lise") {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Seq��ncia </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua em An�lise
		<br>
		<input type='radio' name='sequencia' value='SEGUE'>Vai para Execu��o

	</td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Dura��o </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
	&nbsp;
	<input type='text' size='5' name='duracao' value='<?= $duracao ?>'class='caixa' >
	<br>
	<font size='-2' color='#333333'>Em hora decimal. <br>Ex.: Uma hora e meia = 1,5</font>
	</td>
</tr>
<? } ?>


<?
if ($status == "Execu��o") {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Seq��ncia </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='3'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua em Execu��o
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type='radio' name='sequencia' value='SEGUE'>Resolvido

	</td>

</tr>
<? } 

echo "</table>";


echo "<table width = '750' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
echo "<tr>";

/*--=== CAMPO DE COMENT�RIO ====================================================--*/
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='3' align='center'>";
echo "<textarea name='comentario' cols='60' rows='6' wrap='VIRTUAL'>$comentario</textarea><br>";
echo "<input type='checkbox' name='exigir_resposta' value='t'>Exigir resposta do usu�rio";
echo "<input type='checkbox' name='interno' value='t'>Chamado Interno";
echo "</td>";

/*--=== ARQUIVOS DO SISTEMA ===================================================--*/
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='3' align='left' valign='top'width='300'>";
echo "Solicite o arquivo:<br><input name='programa' id='programa'value='' class='caixa' size='37' onKeyUp = 'recuperardados($hd_chamado)' onblur='this.value=\"\"'><br>";
echo "<div id='conteudo' class='Chamados2' style='position: absolute;opacity:.80;'>Digite no m�nimo <br>4 caracteres</div>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "<center><input type='submit' name='btn_acao' value='Responder Chamado'>";
echo "</center>";


/*--=== ARQUIVOS SOLICITADOS ==================================================--*/
echo "<br><DIV class='exibe' id='dados' value='1' align='center'><font size='1'>Por favor aguarde um momento, carregando os dados...<br><img src='../imagens/carregar_os.gif'></DIV>";
echo "<script language='javascript'>Exibir('dados','','','');</script>";


echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";

echo "</form>";


?>



<? include "rodape.php" ?>
