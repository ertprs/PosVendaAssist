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
		//if($login_admin ==568)	echo "sql-1 $sql<br>";
		$res = pg_exec ($con,$sql);
}

if(strlen($hd_chamado)>0){
	$sql =" select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) from tbl_hd_chamado_atendente where hd_chamado = $hd_chamado;";
	//if($login_admin ==568)	echo "sql-2 $sql<br>";
	$res = pg_exec($con, $sql);
	if(pg_numrows($res)>0)
	$horas= pg_result ($res,0,0);	
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
	//if($login_admin ==568)	echo "sql-4 $sql<br>";
	$res = pg_exec ($con,$sql);
	$categoria_anterior = pg_result ($res,0,categoria);
	$status_anterior    = pg_result ($res,0,status);

	if (strlen($comentario) < 3)$msg_erro="Comentário muito pequeno";

	#-------- De Análise para Execução -------
	if (strlen ($sequencia) == 0 AND $status == "Análise" AND $status_anterior == "Análise") {
		$msg_erro = "Escolha a seqüência da tarefa. Ou continua em análise, ou vai para Execução.";
	}
	if ($sequencia == "SEGUE" AND $status_anterior == "Análise") $status = "Execução" ;

	if ($sequencia == "AGUARDANDO" AND $status_anterior == "Análise") $status = "Aguard.Execução" ;

	if (($status == "Execução" OR $status == "Aguard.Execução") AND $status_anterior == "Análise" AND strlen ($duracao) == 0) {
		$msg_erro = "Informe o tempo de duração previsto para execução deste chamado.";
	}

	$duracao = str_replace (',','.',$duracao);
	if (strlen ($duracao) == 0) $duracao = "null";

	#-------- De Execução para Resolvido -------
	if (strlen ($sequencia) == 0 AND $status == "Execução" AND $status_anterior == "Execução") {
		$msg_erro = "Escolha a seqüência da tarefa. Ou continua em execução ou está resolvido.";
	}

	if ($sequencia == "SEGUE" AND $status_anterior == "Execução") $status = "Resolvido" ;

	if ($sequencia == "SEGUE" AND $status_anterior == "Aguard.Execução") $status = "Execução" ;
	
	if ($status == "Novo" AND $status_anterior == "Novo") $status = "Análise";

/*takashi colocou isso 30-05-07 2495 qdo solicitamos resposta do usuário e interagimos no chamado, setava como false novamente.. se der problema pode retirar*/
		$sql = "Select exigir_resposta from tbl_hd_chamado where hd_chamado=$hd_chamado";
		//if($login_admin ==568)	echo "sql-5 $sql<br>";
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
		//if($login_admin ==568)	echo "sql-begin;<br>";
		//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";

		//if($login_admin ==568)	echo "sql-6 $sql<br>";
		$res = pg_exec ($con,$sql);
		

		$sql =" UPDATE tbl_hd_chamado 
				SET status = '$status' , 
					titulo = '$titulo',
					atendente = $transfere,
					categoria = '$categoria', ";
if($xexigir_resposta=='f')$sql .= " exigir_resposta = '$exigir_resposta', ";/*takashi colocou isso 30-05-07 2495 */
				$sql .= " duracao = $duracao
					
				WHERE hd_chamado = $hd_chamado";
		//if($login_admin ==568)	echo "sql-7 $sql<br>";
		$res = pg_exec ($con,$sql);

		if ($categoria <> $categoria_anterior) {
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, 'Categoria Alterada de $categoria_anterior para <b> $categoria </b>',$login_admin, 't')";
			//if($login_admin ==568)	echo "sql-8 $sql<br>";
			$res = pg_exec ($con,$sql);
		}

		if ($status == "Resolvido" AND $status_anterior == "Execução") {
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin) VALUES ($hd_chamado, 'Chamado resolvido. Se você não concordar com a solução basta inserir novo comentário para reabrir o chamado.',$login_admin)";
			//if($login_admin ==568)	echo "sql-9 $sql<br>";
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
			//if($login_admin ==568)	echo "sql-10 $sql<br>";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			//--======================================================================
			$sql = "SELECT hd_chamado_atendente,
							hd_chamado
							FROM tbl_hd_chamado_atendente 
							WHERE admin = $login_admin 
							AND   data_termino IS NULL
							ORDER BY hd_chamado_atendente DESC LIMIT 1";
			//if($login_admin ==568)	echo "sql-11 $sql<br>";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (pg_numrows($res) > 0) {
				$hd_chamado_atendente =  pg_result($res,0,hd_chamado_atendente);
				$hd_chamado_atual     = pg_result($res,0,hd_chamado);
			}

			if($hd_chamado_atual <> $hd_chamado){

				//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
				$sql =" UPDATE tbl_hd_chamado_item
						SET termino = current_timestamp
						WHERE hd_chamado_item in(SELECT hd_chamado_item
									 FROM tbl_hd_chamado_item
									 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
										AND termino IS NULL
									 ORDER BY hd_chamado_item desc
									 LIMIT 1 );";

			//if($login_admin ==568)	echo "sql-12 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				
				if(strlen($hd_chamado_atendente)>0){
					$sql = "UPDATE tbl_hd_chamado_atendente
									SET data_termino = CURRENT_TIMESTAMP
									WHERE hd_chamado_atendente = $hd_chamado_atendente
									AND   admin               =  $login_admin
									AND   data_termino IS NULL
									";
		//if($login_admin ==568)	echo "sql-13 $sql<br>";
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
		//if($login_admin ==568)	echo "sql-14 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$sql="SELECT CURRVAL('seq_hd_chamado_atendente');";
						//if($login_admin ==568)	echo "sql- select curval seq_hd_chaamado_atend<br>";
				$res = pg_exec ($con,$sql);
				$hd_chamado_atendente =  pg_result($res,0,0);
			}
			if($status == 'Resolvido'){
				//EXECUTA A ROTINA PARA SETAR O TERMINO NO HD_CHAMADO_ITEM
				$sql =" UPDATE tbl_hd_chamado_item
						SET termino = current_timestamp
						WHERE hd_chamado_item in(SELECT hd_chamado_item
									 FROM tbl_hd_chamado_item
									 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
										AND termino IS NULL
									 ORDER BY hd_chamado_item desc
									 LIMIT 1 );";
		//if($login_admin ==568)	echo "sql-15 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql = "UPDATE tbl_hd_chamado_atendente
					SET data_termino = CURRENT_TIMESTAMP
					WHERE admin                = $login_admin
					AND   hd_chamado           = $hd_chamado
					AND   hd_chamado_atendente = $hd_chamado_atendente";
		//if($login_admin ==568)	echo "sql-16 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sql= "UPDATE tbl_controle_acesso_arquivo SET
					data_fim = CURRENT_DATE,
					hora_fim = CURRENT_TIME,
					status   = 'finalizado'
					WHERE hd_chamado = $hd_chamado";
		//if($login_admin ==568)	echo "sql-17 $sql<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

		}


		$msg_erro = substr($msg_erro,6);
		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		//if($login_admin ==568)	echo "sql-rollback<br>";
			$msg_erro = 'Não foi possível Inserir o Chamado';
		}else{
			$res = @pg_exec($con,"COMMIT");
					//if($login_admin ==568)	echo "sql- commit<br>";
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
				$assunto       = "Seu chamado n° $hd_chamado foi RESOLVIDO";
				$corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR 
						NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> - 
						<STRONG>$titulo</STRONG>&nbsp; </P>
						<P align=left>$nome,</P>
						<P align=justify>Seu chamado foi&nbsp;<FONT 
						color=#006600><STRONG>resolvido</STRONG></FONT> pelo suporte Telecontrol, você 
						tem <U>3(três) dias para concordar com a solução do chamado</U>. Caso 
						<STRONG>não haja manifestação</STRONG>&nbsp;será 
						considerado&nbsp;<STRONG>resolvido automaticamente. </STRONG>Caso 
						não concorde&nbsp;com a resolução do chamado <STRONG>insira um 
						comentário</STRONG> para <STRONG>reabrir o chamado</STRONG>.</P>
						<P align=justify>Se após este prazo o problema/dúvida continuar, abra um novo 
						chamado.</P>
						<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/index_ajax.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
						<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br 
						</P>";

				if($exigir_resposta=='t' and $status<>'Resolvido' ){

					$assunto       = "Seu chamado n° $hd_chamado está aguardando sua resposta";

					$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR 
							NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
							<P align=justify>Chamado&nbsp;<STRONG>$hd_chamado</STRONG> - 
							<STRONG>$titulo</STRONG>&nbsp; </P>
							<P align=left>$nome,</P>

							<P align=justify>
							Precisamos de sua posição para continuarmos atendendo o chamado. 
							</P>
							<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/index_ajax.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2&ajax=sim&acao=validar'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
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
					$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
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
					to_char (tbl_hd_chamado.previsao_termino,'DD/MM/YYYY HH24:MI') AS previsao_termino,
					to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM/YYYY HH24:MI') AS previsao_termino_interna,
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
		$previsao_termino     = pg_result($res,0,previsao_termino);
		$previsao_termino_interna= pg_result($res,0,previsao_termino_interna);
		
	}else{
		$msg_erro="Chamado não encontrado";
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
	echo "<td colspan='4' align='center' class = 'Titulo2' height='30'><strong>Chamado nº. $hd_chamado </strong></td>";
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
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px;'><strong>&nbsp;Título </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='3'><input type='text' size='30' name='titulo' value='<?= $titulo ?>' class='caixa'> </td>
	<td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' rowspan='2'colspan='2'align='center' valign='middle'>CHAMADO N°<br><h1><?=$hd_chamado?></h1><BR><b><?=$horas?> trabalhadas</td>

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

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Previsão Interno </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><? echo $previsao_termino_interna ?></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Previsão Término</strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><? echo $previsao_termino ?></td>
</tr>

</table>


<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
		to_char((tbl_hd_chamado_item.TERMINO -tbl_hd_chamado_item.DATA), 'HH24:MI') AS tempo_trabalho,
		tbl_hd_chamado_item.comentario                               ,
		tbl_hd_chamado_item.interno                                  ,
		tbl_admin.nome_completo                            AS autor  ,
		(select to_char(sum(termino - data),'HH24:MI') from tbl_hd_chamado_item where hd_chamado_item = tbl_hd_chamado_item.hd_chamado_item) as a,
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
	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Interações</b></td>";
	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td ><strong><font size='2'>Nº</font></strong></td>";
	echo "<td nowrap><strong><font size='2'>Data e Hora</font></strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
	echo "<td nowrap><strong><font size='2'>Tmp Trab.</font></strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
	echo "<td ><strong><font size='2'>  Comentário </strong></font></td>";
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
		$tempo_trabalho= pg_result($res,$i,tempo_trabalho);
		
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
			echo "<td colspan='6' align='center'><b>Chamado interno</b></td>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9' align='bottom'></td>";
			echo "</tr>";
		}

		echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap width='50'>$x </td>";
		echo "<td nowrap>$data_interacao </td>";
		echo "<td nowrap>$tempo_trabalho</td>";
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
			echo "<td colspan='6' align='center'><b>Chamado foi resolvido nesta interação</b></td>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9' align='bottom'></td>";
			echo "</tr>";
		}

	}	
	
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>";
}

if (strlen ($hd_chamado) > 0) {
?>
<table width = '750' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px' rowspan='3'><strong>&nbsp;Status </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' rowspan='3'>
		<select name="status" size="1">
		<!--<option value=''></option>-->
		<option value='Novo'      <? if($status=='Novo')      echo ' SELECTED '?> >Novo</option>
		<option value='Análise'   <? if($status=='Análise')   echo ' SELECTED '?> >Análise</option>
		<option value='Execução'  <? if($status=='Execução')  echo ' SELECTED '?> >Execução</option>
		<option value='Aguard.Execução'  <? if($status=='Aguard.Execução')  echo ' SELECTED '?> >Aguard.Execução</option>
		<option value='Aprovação' <? if($status=='Aprovação') echo ' SELECTED '?> >Aprovação</option>
		<option value='Cancelado' <? if($status=='Cancelado') echo ' SELECTED '?> >Cancelado</option>
		<option value='Resolvido' <? if($status=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
		</select>
	</td>

<?

//ATENDENTE DO CHAMADO
echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Atendente</strong></td>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='center' >";
$sql = "SELECT  *
		FROM    tbl_admin
		WHERE   tbl_admin.fabrica = 10
		ORDER BY tbl_admin.nome_completo;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<select class='frm' style='width: 200px;' name='transfere'>\n";
	echo "<option value=''>- ESCOLHA -</option>\n";

	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$aux_admin = trim(pg_result($res,$x,admin));
		$aux_nome_completo  = trim(pg_result($res,$x,nome_completo));

		echo "<option value='$aux_admin'"; if ($atendente == $aux_admin) echo " SELECTED "; echo "> $aux_nome_completo</option>\n";
	}
	
	echo "</select>\n";
}
echo "</td>";
/*	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Analista </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>= $atendente_nome </td>*/


	if ($status == "Análise") {
		echo "<td  bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' rowspan='2'colspan='4' align='center' valign='middle'>CHAMADO N°<br><h1>$hd_chamado</h1></td>";
	}else{
		echo "<td  bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' rowspan='2'colspan='2' align='center' valign='middle'>CHAMADO N°<br><h1>$hd_chamado</h1></td>";
	}


?>
</tr>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Categoria </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<select name="categoria" size="1" >
		<option></option>
		<option value='Ajax' <? if($categoria=='Ajax') echo ' SELECTED '?> >Ajax, JavaScript</option>
		<option value='Design' <? if($categoria=='Design') echo ' SELECTED '?> >Design</option>
		<option value='Implantação' <? if($categoria=='Implantação') echo ' SELECTED '?> >Implantação</option>
		<option value='Integração' <? if($categoria=='Integração') echo ' SELECTED '?> >Integração (ODBC, Perl)</option>
		<option value='Linux' <? if($categoria=='Linux') echo ' SELECTED '?> >Linux, Hardware, Data-Center</option>
		<option value='Novos' <? if($categoria=='Novos') echo ' SELECTED '?> >Novos Projetos</option>
		<option value='SQL' <? if($categoria=='SQL') echo ' SELECTED '?> >Otimização de SQL e Views</option>
		<option value='PHP' <? if($categoria=='PHP') echo ' SELECTED '?> >PHP</option>
		<option value='PL' <? if($categoria=='PL') echo ' SELECTED '?> >PL/PgSQL, functions e triggers</option>
		<option value='Postgres' <? if($categoria=='Postgres') echo ' SELECTED '?> >Postgres</option>
		<option value='Suporte Telefone' <? if($categoria=='Suporte Telefone') echo ' SELECTED '?> >Suporte Telefone</option>

		</select>
	</td>
</tr>
<? } ?>


<?
if ($status == "Análise") {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Seqüência </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua em Análise
		<br>
		<input type='radio' name='sequencia' value='AGUARDANDO'>Aguard.Execução
		<br>
		<input type='radio' name='sequencia' value='SEGUE'>Vai para Execução
	</td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='2'><strong>&nbsp;Duração </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
	&nbsp;
	<input type='text' size='5' name='duracao' value='<?= $duracao ?>'class='caixa' >
	<br>
	<font size='-2' color='#333333'>Em hora decimal. <br>Ex.: Uma hora e meia = 1,5</font>
	</td>
</tr>
<? } ?>

<?
if ($status == "Aguard.Execução") {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Seqüência </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='3'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua Aguard.Execução
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type='radio' name='sequencia' value='SEGUE'>Vai para Execução

	</td>

</tr>
<? } 

if ($status == "Execução") {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Seqüência </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='3'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua em Execução
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type='radio' name='sequencia' value='SEGUE'>Resolvido

	</td>

</tr>
<? } 

echo "</table>";


echo "<table width = '750' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
echo "<tr>";

/*--=== CAMPO DE COMENTÁRIO ====================================================--*/
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='3' align='center'>";
echo "<textarea name='comentario' cols='60' rows='6' wrap='VIRTUAL'>$comentario</textarea><br>";
echo "<input type='checkbox' name='exigir_resposta' value='t'>Exigir resposta do usuário";
echo "<input type='checkbox' name='interno' value='t'>Chamado Interno";
echo "</td>";

/*--=== ARQUIVOS DO SISTEMA ===================================================--*/
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px' colspan='3' align='left' valign='top'width='300'>";
echo "Solicite o arquivo:<br><input name='programa' id='programa'value='' class='caixa' size='37' onKeyUp = 'recuperardados($hd_chamado)' onblur='this.value=\"\"'><br>";
echo "<div id='conteudo' class='Chamados2' style='position: absolute;opacity:.80;'>Digite no mínimo <br>4 caracteres</div>";
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
