<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';


if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

if (strlen ($btn_acao) > 0) {

	if($_POST['comentario'])      $comentario      = trim ($_POST['comentario']);
	if($_POST['titulo'])          $titulo          = trim ($_POST['titulo']);
	if($_POST['status'])          $status          = trim ($_POST['status']);
	if($_POST['categoria'])       $categoria       = trim ($_POST['categoria']);
	if($_POST['sequencia'])       $sequencia       = trim ($_POST['sequencia']);
	if($_POST['duracao'])         $duracao         = trim ($_POST['duracao']);
	if($_POST['interno'])         $interno         = trim ($_POST['interno']);
	if($_POST['prazo_horas'])     $prazo_horas     = trim ($_POST['prazo_horas']);
	if($_POST['prioridade'])      $prioridade      = trim ($_POST['prioridade']);
	if($_POST['exigir_resposta']) $exigir_resposta = trim ($_POST['exigir_resposta']);
	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	if(strlen($prioridade)==0) $prioridade=5;

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"BEGIN TRANSACTION");

		if(strlen($hd_chamado)==0){
			if (strlen($titulo) < 2) $msg_erro="Título muito pequeno";
			$sql =	"INSERT INTO tbl_hd_chamado (
						empregado                                                    ,
						fabrica_responsavel                                          ,
						titulo                                                       ,
						categoria                                                    ,
						status                                                       ,
						prioridade
					) VALUES (
						$login_empregado                                             ,
						$login_empresa                                               ,
						'$titulo'                                                    ,
						'$categoria'                                                 ,
						'Novo'                                                       ,
						$prioridade
					);";
			$res       = pg_exec ($con,$sql);
			$msg_erro   = pg_errormessage($con);
			$msg_erro   = substr($msg_erro,6);
			$res        = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado = pg_result ($res,0,0);
		}else{
			$sql = "SELECT categoria , status FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
			$res = pg_exec ($con,$sql);
			$categoria_anterior = pg_result ($res,0,categoria);
			$status_anterior    = pg_result ($res,0,status);

			#-------- De Análise para Execução -------
			if (strlen ($sequencia) == 0 AND $status == "Análise" AND $status_anterior == "Análise") {
				$msg_erro = "Escolha a seqüência da tarefa. Ou continua em análise, ou vai para Execução.";
			}
			if ($sequencia == "SEGUE" AND $status_anterior == "Análise") $status = "Execução" ;
			if ($status == "Execução" AND $status_anterior == "Análise" AND strlen ($duracao) == 0) {
				$msg_erro = "Informe o tempo de duração previsto para execução deste chamado.";
			}

			$duracao = str_replace (',','.',$duracao);
			if (strlen ($duracao) == 0) $duracao = "null";
		
			$prazo_horas = str_replace (',','.',$prazo_horas);
			if(strlen($prazo_horas)==0) $prazo_horas ="null";
			//if (strlen ($prazo_horas) == 0) $msg_erro = "Por favor de um prazo para o atendimento!";

			#-------- De Execução para Resolvido -------
			if (strlen ($sequencia) == 0 AND $status == "Execução" AND $status_anterior == "Execução") {
				$msg_erro = "Escolha a seqüência da tarefa. Ou continua em execução ou está resolvido.";
			}
			if ($sequencia == "SEGUE" AND $status_anterior == "Execução") $status = "Resolvido" ;
			if ($status == "Novo" AND $status_anterior == "Novo")         $status = "Análise";
		
			if (strlen ($exigir_resposta) > 0) $exigir_resposta = 't';
			else                               $exigir_resposta = 'f';
			if (strlen ($interno)         > 0) $xinterno        = 't';
			else                               $xinterno        = 'f';

			if (strlen($msg_erro) == 0){
				$sql =" UPDATE tbl_hd_chamado SET
							status           = '$status',
							titulo           = '$titulo',
							categoria        = '$categoria',
							duracao          = $duracao,
							exigir_resposta  = '$exigir_resposta',
							prioridade       = $prioridade,
							prazo_horas      = $prazo_horas
					WHERE hd_chamado = $hd_chamado";
				$res = pg_exec ($con,$sql);
			}
		}
		if(strlen($msg_erro)==0){
				if ($categoria <> $categoria_anterior) {
					$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, empregado) VALUES ($hd_chamado, 'Categoria Alterada de $categoria_anterior para $categoria',$login_empregado)";
					$res = pg_exec ($con,$sql);
				}
		
				if ($status == "Resolvido" AND $status_anterior == "Execução") {
					$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, empregado) VALUES ($hd_chamado, 'Chamado resolvido. Se você não concordar com a solução basta inserir novo comentário para reabrir o chamado.',$login_empregado)";
					$res = pg_exec ($con,$sql);
				}
		}
		if (strlen ($comentario) > 0) {
			$sql ="INSERT INTO tbl_hd_chamado_item (
						hd_chamado                                                   ,
						comentario                                                   ,
						empregado                                                    ,
						posto                                                        ,
						status_item                                                  ,
						interno
					) VALUES (
						$hd_chamado                                                  ,
						'$comentario'                                                ,
						$login_empregado                                             ,
						$login_loja                                                  ,
						'$status'                                                    ,
						'$xinterno'
					);";

			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
			$hd_chamado_item  = pg_result ($res,0,0);
		}

		$msg_erro = substr($msg_erro,6);

//ROTINA DE UPLOAD DE ARQUIVO

		if (strlen ($msg_erro) == 0 and strlen($hd_chamado_item) > 0) {

			$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes) 

			if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

				if (strlen($msg_erro) == 0) {
					// Pega extensão do arquivo
					preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt){1}$/i", $arquivo["name"], $ext);
					$aux_extensao = "'".$ext[1]."'";
					
					$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));
					
					// Gera um nome único para a imagem
					$nome_anexo = "/www/assist/www/helpdesk/documentos/" . $hd_chamado_item."-".strtolower ($nome_sem_espaco);

					// Faz o upload da imagem
					if (strlen($msg_erro) == 0) {
						if (copy($arquivo["tmp_name"], $nome_anexo)) {
						}else{
							$msg_erro = "Arquivo não foi enviado!!!";
						}
					}//fim do upload da imagem
				}//fim da verificação de erro
			}//fim da verificação de existencia no apache
		}//fim de todo o upload



		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro = 'Não foi possível Inserir o Chamado';
		}else{
			$res = @pg_exec($con,"COMMIT");
			if($status=='Resolvido' OR $exigir_resposta == 't'){
				$sql="SELECT nome_completo,email,tbl_admin.admin FROM tbl_admin JOIN tbl_hd_chamado ON tbl_hd_chamado.admin = tbl_admin.admin WHERE hd_chamado = $hd_chamado";
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
						<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
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
							<P align=justify>Clique no link para acessar o seu chamado:<br> <a href='http://www.telecontrol.com.br/assist/index.php?id=$hd_chamado&key1=$chave1&id2=$adm&key2=$chave2'><u><b>Clique aqui para ver seu chamado</b></u></a>.</P>
							<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br 
							</P>";
				}

				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";


				if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) )
					$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
				else
					$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
			}
			header ("Location: $PHP_SELF?hd_chamado=$hd_chamado");
			
		}
	}
}



if(strlen($hd_chamado) > 0){

	$sql= " SELECT  tbl_hd_chamado.hd_chamado                            ,
			tbl_hd_chamado.empregado                             ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			tbl_hd_chamado.titulo                                ,
			tbl_hd_chamado.categoria                             ,
			tbl_hd_chamado.status                                ,
			tbl_hd_chamado.duracao                               ,
			tbl_hd_chamado.fabrica_responsavel                   ,
			tbl_hd_chamado.prazo_horas                           ,
			tbl_hd_chamado.prioridade                            ,
			tbl_hd_chamado.duracao
		FROM tbl_hd_chamado
		WHERE hd_chamado          = $hd_chamado
		AND   fabrica_responsavel = $login_empresa";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$empregado            = pg_result($res,0,empregado);
		$data                 = pg_result($res,0,data);
		$titulo               = pg_result($res,0,titulo);
		$categoria            = pg_result($res,0,categoria);
		$status               = pg_result($res,0,status);
		$duracao              = pg_result($res,0,duracao);
		$prazo_horas          = pg_result($res,0,prazo_horas);
		$fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
		$prioridade           = pg_result($res,0,prioridade);
		$duracao              = pg_result($res,0,duracao);

		$sql2 = "SELECT nome     ,
				email    ,
				fone 
			FROM tbl_empregado 
			JOIN tbl_posto ON tbl_posto.posto = tbl_empregado.posto_empregado 
			WHERE empregado = $empregado";
		$res2 = @pg_exec ($con,$sql2);
		$nome  = pg_result($res2,0,0);
		$email = pg_result($res2,0,1);
		$fone  = pg_result($res2,0,2);
	}else{
		$msg_erro="Chamado não encontrado";
	}
}



$TITULO = "ADM - Responder Chamado";
echo "$msg_erro";
include "menu.php";
?>
<script language='javascript' src='../ajax.js'></script>
<script>
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
			
var http_forn = new Array();

function gravar_atendente(formulatio) {
//	ref = trim(ref);
	var acao='cadastrar';

	var com = document.getElementById('lista_atendentes');
	com.innerHTML = "&nbsp;&nbsp;Aguarde...&nbsp;&nbsp;";

	url = "ajax_atendente.php?ajax=sim&acao="+acao;

	for( var i = 0 ; i < formulatio.length; i++ ){
		if ( formulatio.elements[i].type=='select-one' || formulatio.elements[i].type=='hidden'){
			url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
			
		}
	}
	//alert(url);//lista_atendentes
	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="ok"){
					com.innerHTML = response[1];
					formulatio.btn_atendente.value='GRAVAR';
					mostrar_interacao(response[1],'interacao_'+response[1]);
				}
				if (response[0]=="0"){
					// posto ja cadastrado
					alert(response[1]);
					formulatio.btn_atendente.value='GRAVAR';
				}

			}
		}
	}
	http_forn[curDateTime].send(null);
}
</script>

<table width = '750' align = 'center'  cellpadding='2' >

<form name='frm_chamada' action='<? echo $PHP_SELF ?>' method='post' enctype='multipart/form-data'>
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado?>'>

<tr>

	<td width="60" bgcolor="#CED8DE" class='HD' ><strong>&nbsp;Abertura </strong></td>
	<td bgcolor="#E5EAED" class='HD'><?= $data ?> </td>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Prioridade </strong></td>

	<td bgcolor="#E5EAED" class='HD'align='center' >
	<?
	if    ($prioridade==0) echo "<font color='#FF0000' ><img src='/assist/admin/imagens_admin/status_vermelho.gif'> <b>ALTA</b></font>";
	elseif($prioridade==5) echo "<font color='#006600' ><img src='/assist/admin/imagens_admin/status_verde.gif'> <b>NORMAL</b></font>";
	else echo "NORMAL";
	?>
	</td>

</tr>


<?
if (strlen ($hd_chamado) > 0) {
?>
<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Status </strong></td>
	<td bgcolor="#E5EAED" class='HD'>
		<select name="status" size="1">
		<!--<option value=''></option>-->
		<option value='Novo'      <? if($status=='Novo')      echo ' SELECTED '?> >Novo</option>
		<option value='Análise'   <? if($status=='Análise')   echo ' SELECTED '?> >Análise</option>
		<option value='Execução'  <? if($status=='Execução')  echo ' SELECTED '?> >Execução</option>
		<option value='Aprovação' <? if($status=='Aprovação') echo ' SELECTED '?> >Aprovação</option>
		<option value='Cancelado' <? if($status=='Cancelado') echo ' SELECTED '?> >Cancelado</option>
		<option value='Resolvido' <? if($status=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
		</select>
	</td>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Analista </strong></td>
	<td bgcolor="#E5EAED" class='HD'><?= $atendente_nome ?></td>
	<td  bgcolor="#E5EAED" class='HD' rowspan='2'colspan='2'align='center' valign='middle'>CHAMADO N°<br><h1><?=$hd_chamado?></h1></td>
</tr>
<? } ?>


<?
if ($status == "Análise") {
?>
<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Seqüência </strong></td>
	<td bgcolor="#E5EAED" class='HD'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua em Análise
		<br>
		<input type='radio' name='sequencia' value='SEGUE'>Vai para Execução
	</td>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Duração </strong></td>
	<td             bgcolor="#E5EAED" class='HD'>
	&nbsp;
	<input type='text' size='5' name='duracao' value='<?= $duracao ?>' >
	<br>
	<font size='-2' color='#333333'>Em hora decimal. <br>Ex.: Uma hora e meia = 1,5</font>
	</td>
</tr>
<? } ?>


<?
if ($status == "Execução") {
?>
<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Seqüência </strong></td>
	<td bgcolor="#E5EAED" class='HD' colspan='3'>
		<input type='radio' name='sequencia' value='CONTINUA'>Continua em Execução
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type='radio' name='sequencia' value='SEGUE'>Resolvido

	</td>
</tr>
<? } ?>

<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Título </strong></td>
	<td bgcolor="#E5EAED" class='HD'>&nbsp;<input type='text' size='30' name='titulo' value='<?= $titulo ?>' class='Caixa'> </td>
	
	
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Categoria </strong></td>
	<td bgcolor="#E5EAED" class='HD'>&nbsp;
		<select name="categoria" size="1" >
		<option value='Venda'      <? if($categoria=='Venda')      echo ' SELECTED '?> >Venda</option>
		<option value='Serviço'    <? if($categoria=='Serviço')    echo ' SELECTED '?> >Serviço</option>
		<option value='Locação'    <? if($categoria=='Locação')    echo ' SELECTED '?> >Locação</option>
		<option value='Satisfação' <? if($categoria=='Satisfação') echo ' SELECTED '?> >Satisfação</option>
		<option value='Outros'     <? if($categoria=='Erro')       echo ' SELECTED '?> >Outros</option>
		</select>
	</td>
</tr>
<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Nome </strong></td>
	<td bgcolor="#E5EAED" class='HD' colspan='3'>&nbsp;<input type='text' size='50' name='nome' value='<?= $nome ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> class='Caixa'></td>
</tr>

<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Email </strong></td>
	<td bgcolor="#E5EAED" class='HD'>&nbsp;<input type='text' size='28' name='email' value='<?= $email ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> class='Caixa'></td>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Fone </strong></td>
	<td bgcolor="#E5EAED" class='HD'>&nbsp;<input type='text' size='20' name='fone' value='<?=$fone ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> class='Caixa'></td>
</tr>

</table>


<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
				tbl_hd_chamado_item.comentario                            ,
				tbl_hd_chamado_item.interno                               ,
				tbl_empregado.empregado,
				tbl_hd_chamado_item.status_item
		FROM tbl_hd_chamado_item 
		JOIN tbl_empregado USING(empregado)
		WHERE hd_chamado = $hd_chamado
		ORDER BY hd_chamado_item";

$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	echo "<table width = '750' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='5' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Interações</b></td>";
	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td ><strong><font size='2'>Nº</font></strong></td>";
	echo "<td nowrap><strong><font size='2'>Data e Hora</font></strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
	echo "<td ><strong><font size='2'>  Coment&aacute;rio </strong></font></td>";
	echo "<td ><strong><font size='2'> Anexo </strong></font></td>";
	echo "<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><font size='2'><strong>Autor </strong></font></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$x=$i+1;
		$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
		$data_interacao  = pg_result($res,$i,data);
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

		echo "<tr  style='font-family: arial ; font-size: 11px' height='25' bgcolor='$cor'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap width='50'>$x </td>";
		echo "<td nowrap>$data_interacao </td>";
		echo "<td >" . nl2br ($item_comentario) . "</td>";

		echo "<td>";
		$dir = "documentos/";
		$dh  = opendir($dir);

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
			echo "<td colspan='5' align='center'><b>Chamado foi resolvido nesta interação</b></td>";
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

echo "<center>";

echo "<b><font face='arial' color='#666666'>Resposta ao chamado</font></b>";
echo "<br>";
echo "<table cellpadding='0'border='0' width='750'><tr>";
echo "<td align='left'>";

//resposta do chamado
echo "<table width = '525' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px' boder='2'>";

echo "<tr>";

//--== CAMPO DO COMENTÁRIO ==========================================--\\
echo "<td bgcolor='#E5EAED' class='HD'  align='center' rowspan='2'>";
echo "<textarea name='comentario' id='comentario'cols='53' rows='10' wrap='VIRTUAL' class='Caixa'>$comentario</textarea><br>";
echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";
//--=================================================================--\\

//--== TIPOS DE RESPOSTAS ===========================================--\\
echo "<input type='checkbox' name='exigir_resposta' value='1' class='Caixa'> Exigir resposta do usuário ";
echo "<input type='checkbox' name='interno' value='t' class='Caixa'> Chamado Interno ";
echo "</td>";
//--=================================================================--\\

echo "	<td bgcolor='#CED8DE' class='HD' height='10' align='center'width='60' ><strong>Prioridade </strong></td>";

echo "</tr>";
echo "<tr>";

echo "<td bgcolor='#E5EAED' class='HD'  align='right' >";

echo "Alta<INPUT TYPE='radio' NAME='prioridade' value='0' ";  if($prioridade=='0') echo "CHECKED"; echo "><br>";
echo "Normal<INPUT TYPE='radio' NAME='prioridade' value='5' ";if($prioridade=='5') echo "CHECKED";echo ">";
echo "</td>";
echo "</tr>";


//--== INSERIR ARQUIVO ==============================================--\\
echo "<tr>";
echo "<td bgcolor='#CED8DE' class='HD' colspan='2'><strong>&nbsp;Arquivo</strong> <input type='file' name='arquivo' size='50' class='frm'></td>";
echo "<tr><td colspan='4' bgcolor='#E5EAED' class='HD'  align='center'><input type='submit' name='btn_acao' value='Atendimento'></td>";
echo "</tr>";
//--=================================================================--\\


echo "</table>";


echo "</td><td valign='top'>";


//--== TABELA DE ATENDENTES =========================================--\\


$sql = "SELECT empregado,
		nome 
	FROM tbl_empregado 
	JOIN tbl_pessoa USING(pessoa)
	WHERE tbl_empregado.empresa = $login_empresa;";
$res = pg_exec ($con,$sql);


if (pg_numrows($res) > 0) {

	echo "<table width = '225' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
	echo "<tr>";
	echo "<td bgcolor='#CED8DE' class='HD' colspan='3' align='center'><strong>ATENDENTES </strong></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td bgcolor='#E5EAED' class='HD'  align='left' >&nbsp;";
	if (pg_numrows($res) > 0) {
		echo "<select class='Caixa' style='width: 150px;' name='atendente'>\n";
		echo "<option value=''>- ESCOLHA -</option>\n";
	
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_empregado = trim(pg_result($res,$x,empregado));
			$aux_nome_completo  = trim(pg_result($res,$x,nome));
	
			echo "<option value='$aux_empregado'"; if ($atendente == $aux_empregado) echo " SELECTED "; echo "> $aux_nome_completo</option>\n";
		}
		
		echo "</select>";
		echo "<br><input type='button' name='btn_acao' id='btn_atendente' value='Gravar' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_atendente(this.form);}\">\n";
	}
	echo "<p><div id='lista_atendentes'>";
	$sql = "SELECT * FROM tbl_hd_chamado_atendimento WHERE hd_chamado = $hd_chamado";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i=0; $i<pg_numrows($res); $i++){
			$empregado  = pg_result($res,$i,empregado);
			$sql2 = "SELECT nome     ,
					email
				FROM tbl_empregado 
				JOIN tbl_pessoa USING(pessoa)
				WHERE empregado = $empregado";
			$res2 = @pg_exec ($con,$sql2);
			$nome  = pg_result($res2,0,0);
			$nome_abreviado = explode (' ',$nome);
			$nome_abreviado = $nome_abreviado[0];
	
			echo "<b>$nome_abreviado</b><br>";
		}
	}else echo "Nenhum antendente cadastrado";
	
	echo "</div>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";


}



echo "</td></tr></table>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "</center>";
echo "</form>";



?>

<? include "rodape.php" ?>