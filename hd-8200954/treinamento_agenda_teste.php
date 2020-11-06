<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'ajax_cabecalho.php';

$termo_compromisso='Este é um termo de compromisso no qual, está sendo agendando para que o técnico aqui cadastrado pelo posto autorizado, a participar do treinamento aqui escolhido. Caso você não tenha certeza fica obrigado a clicar em NÃO ACEITO.
Clicando em ACEITO, você declara expressamente que o técnico cadastrado, está assumindo um compromisso para representar o posto autorizado, para participar do treinamento aqui agendado. Declara, por fim, conhecer e aceitar o Aviso Legal de Uso do sistema Assist Telecontrol.';

function validatemail($email=""){ 
    if (preg_match("/^[a-z]+([\._\-]?[a-z0-9\._-]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email)) { 
//validacao anterior [a-z0-9\._-]
		$valida = "1"; 
    } 
    else { 
        $valida = "0"; 
    } 
    return $valida; 
}



//--==== Cadastrar um técnico no treinamento =================================
include 'autentica_usuario.php';

if($_GET['ajax']=='sim' AND $_GET['acao']=='cadastrar') {

	$tecnico_nome         = trim($_GET["tecnico_nome"]);
	$tecnico_cpf          = trim($_GET["tecnico_cpf"]);
	$tecnico_rg           = trim($_GET["tecnico_rg"]);
	$tecnico_fone         = trim($_GET["tecnico_fone"]);
	$posto_email          = trim($_GET["posto_email"]);
	$treinamento          = trim($_GET["treinamento"]);
	$promotor             = trim($_GET["promotor"]);
	$hotel                = trim($_GET["hotel"]);
	$promotor_treinamento = trim($_GET["promotor_treinamento"]);
	$observacao			  = trim($_GET["observacao"]);
	
	if($login_fabrica==20){
		
		$sql2="select count(treinamento_posto) as qtd_incritos_posto from tbl_treinamento_posto where posto=$login_posto and treinamento=$treinamento";
		$res2 = pg_exec ($con,$sql2);
		$qtd_incritos_posto = trim(pg_result($res2,0,qtd_incritos_posto));
		$qtd_disponivel_vagas=2-$qtd_incritos_posto;
	
		if($qtd_disponivel_vagas<1){
			$msg_erro .="<center>Não existe vaga disponível</center>";
		}

	}
	
	if (strlen($tecnico_nome) == 0) $msg_erro .= "Favor informar o nome do técnico<br>"      ;
	if (strlen($tecnico_rg) == 0)   $msg_erro .= "Favor informar o RG do técnico <br>"        ;
	if (strlen($tecnico_cpf) == 0)  $msg_erro .= "Favor informar o CPF do técnico<br>"       ;
	if (strlen($tecnico_fone) == 0) $msg_erro .= "Favor informar o telefone de contato<br>"  ;
	if (strlen($posto_email) == 0)  $msg_erro .= "Favor informar o email do posto<br>"       ;
	if (strlen($tecnico_data_nascimento) == 0) $msg_erro .= "Favor informar a data de nascimento do técnico<br>"  ;
	if (strlen($treinamento) == 0)  $msg_erro .= "Favor informar o treinamento escolhido<br>";
	elseif (strlen($observacao) == 0)
	{
		$sql = "SELECT adicional FROM tbl_treinamento WHERE treinamento=$treinamento";
		@$res = pg_query($con, $sql);

		if($res)
		{
			$adicional = pg_result($res, 0, 0);
			if ($adicional)
			{
				$msg_erro .= "Favor informar $adicional<br>";
			}
		}
		else
		{
			$msg_erro .= "Favor informar o treinamento escolhido<br>";
		}
	}

	/*
	$msg_erro="tecnico_nome: $tecnico_nome.'<br>'";
	$msg_erro .="tecnico_cpf: $tecnico_cpf.'<br>'";
	$msg_erro .="tecnico_rg: $tecnico_rg.'<br>'";
	$msg_erro .="tecnico_fone: $tecnico_fone.'<br>'";
	$msg_erro .="treinamento: $treinamento.'<br>'";
	$msg_erro .="promotor: $promotor_treinamento.'<br>'";
	$msg_erro .="tecnico_data_nascimento: $tecnico_data_nascimento.'<br>'";
	*/

	if (!validatemail($posto_email))  $msg_erro .= "Email do POSTO inválido: $posto_email<br>";

	$tecnico_cpf = str_replace("-","",$tecnico_cpf);
	$tecnico_cpf = str_replace(".","",$tecnico_cpf);
	$tecnico_cpf = str_replace("/","",$tecnico_cpf);
	$tecnico_cpf = str_replace(" ","",$tecnico_cpf);
	$tecnico_cpf = trim(substr($tecnico_cpf,0,14));

	$aux_tecnico_nome = "'".$tecnico_nome."'";
	$aux_tecnico_cpf  = "'".$tecnico_cpf."'" ;
	$aux_tecnico_rg   = "'".$tecnico_rg."'"  ;
	$aux_tecnico_fone = "'".$tecnico_fone."'";
	$aux_promotor_treinamento = $promotor_treinamento;

	if(strlen($promotor)==0) $aux_promotor = "null";
	else                     $aux_promotor = "'".$promotor."'";

	if(strlen($hotel)==0){
		$hotel = "'f'";
	}else{
		$hotel = "'t'";			
	}

	$tecnico_data_nascimento = str_replace (" " , "" , $tecnico_data_nascimento);
	$tecnico_data_nascimento = str_replace ("-" , "" , $tecnico_data_nascimento);
	$tecnico_data_nascimento = str_replace ("/" , "" , $tecnico_data_nascimento);
	$tecnico_data_nascimento = str_replace ("." , "" , $tecnico_data_nascimento);

	if (strlen ($tecnico_data_nascimento) == 6) $tecnico_data_nascimento = substr ($tecnico_data_nascimento,0,4) . "20" . substr ($tecnico_data_nascimento,4,2);
	if (strlen ($tecnico_data_nascimento)   > 0) $tecnico_data_nascimento   = substr ($tecnico_data_nascimento,0,2)   . "/" . substr ($tecnico_data_nascimento,2,2)   . "/" . substr ($tecnico_data_nascimento,4,4);
	if (strlen ($tecnico_data_nascimento) < 10) $tecnico_data_nascimento = date ("d/m/Y");

	$x_tecnico_data_nascimento = substr ($tecnico_data_nascimento,6,4) . "-" . substr ($tecnico_data_nascimento,3,2) . "-" . substr ($tecnico_data_nascimento,0,2);

	if(strlen($x_tecnico_data_nascimento)>0 ){
		$sql ="SELECT date'$x_tecnico_data_nascimento' > (current_date-interval'18 year')";
		$res = pg_exec ($con,$sql);
		if(pg_result($res,0,0)=='t') $msg_erro.='NÃO É PERMITIDO A PARTICIPAÇÃO DE MENORES DE 18 ANOS NO TREINAMENTO BOSCH';
	}

	if (strlen($msg_erro) > 0) {
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $msg_erro;
	}else {
		$listar = "ok";
	}


	if ($listar == "ok") {

		$res = @pg_exec($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_posto SET email = '$posto_email' WHERE posto = $login_posto";
		$res = pg_exec ($con,$sql);

		//--==== Controle de Quantidade de vagas existentes no treinamento ======================================
		$sql = "SELECT  count(treinamento_posto) AS total_inscritos, 
				tbl_treinamento.vagas
			FROM tbl_treinamento 
			JOIN tbl_treinamento_posto USING(treinamento)
			WHERE tbl_treinamento.treinamento = $treinamento
			AND   tbl_treinamento_posto.ativo IS TRUE
			GROUP BY tbl_treinamento.vagas;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total_inscritos = trim(pg_result($res,0,total_inscritos))   ;
			$vagas           = trim(pg_result($res,0,vagas));
			if($total_inscritos >= $vagas) $msg_erro .= "Todas as Vagas estão preenchidas, procure uma nova data";
		}
/*		//--==== Controle de Quantidade de técnicos cadastrados por posto =======================================
		$sql = "SELECT * FROM tbl_treinamento_posto WHERE treinamento = $treinamento AND posto = $login_posto";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$msg_erro .= "Já existe um técnico com este treinamento agendado.";
		}
*/			

		$sql = "INSERT INTO tbl_treinamento_posto (
				tecnico_nome ,
				tecnico_rg   ,
				tecnico_cpf  ,
				tecnico_fone ,
				promotor     ,
				posto        ,
				hotel        ,
				treinamento  ,";
				if ($aux_promotor_treinamento) $sql .= " promotor_treinamento, ";
				$sql .= "
				tecnico_data_nascimento,
				observacao
			)VALUES(
				$aux_tecnico_nome,
				$aux_tecnico_rg  ,
				$aux_tecnico_cpf ,
				$aux_tecnico_fone,
				$aux_promotor    ,
				$login_posto     ,
				$hotel         ,
				'$treinamento'     ,";
				if ($aux_promotor_treinamento) $sql .= " $aux_promotor_treinamento, ";
				$sql .= "
				'$x_tecnico_data_nascimento',
				'$observacao'
			)";
		//echo nl2br($sql);
		$res = @pg_exec($con, $sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT CURRVAL ('seq_treinamento_posto')";
		$res = @pg_exec($con,$sql);
		$treinamento_posto =@ pg_result($res,0,0);
		

		$email = $posto_email;

		if($msg_erro==0){

			$chave1 = md5($login_posto);
			$chave2 = md5($treinamento_posto);

			$sql=  "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
			$res = pg_exec ($con,$sql);
			$nome = pg_result($res,0,nome);

			$sql=  "SELECT  titulo                            ,
					TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
					TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim   
					FROM tbl_treinamento WHERE treinamento = $treinamento";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				$titulo      = pg_result($res,0,titulo)     ;
				$data_inicio = pg_result($res,0,data_inicio);
				$data_fim    = pg_result($res,0,data_fim)   ;
			}

			//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
			
			$email_origem  = "verificacao@telecontrol.com.br";
			$email_destino = "$email";
			$assunto       = "Confirmação de Presença no Treinamento";

			$corpo.= "Titulo: $titulo <br>\n";
			$corpo.= "Data Inicío: $data_inicio<br> \n";
			$corpo.= "Data Termino: $data_fim <p>\n";
			
			$corpo.="<br>Você recebeu esse email para confirmar a inscrição do técnico.\n\n";
			$corpo.="<br>Nome: $tecnico_nome \n";
			$corpo.="<br>RG:$tecnico_rg \n";
			$corpo.="<br>CPF: $tecnico_cpf \n";
			$corpo.="<br>Telefone de Contato: $tecnico_fone \n\n";
			if($adicional) $corpo.="<br>$adicional: $observacao \n\n";
			$corpo.="<br>Email: $email\n\n";
			$corpo.="<br><br><a href='http://www.telecontrol.com.br/assist/treinamento_confirmacao.php?key1=$chave1&key2=$login_posto&key3=$chave2&key4=$treinamento_posto'>CLIQUE AQUI PARA CONFIRMAR PRESENÇA</a>.\n\n";
			$corpo.="<br><br><br>Telecontrol\n";
			$corpo.="<br>www.telecontrol.com.br\n";
			$corpo.="<br>_______________________________________________\n";
			$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";
	
	
			$body_top = "MIME-Version: 1.0\r\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
			$body_top .= "From: $email_origem\r\n";

			if ( @mail($email_destino, stripslashes($assunto), $corpo, $body_top ) ){
				$msg = "$email";
			}else{
				$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";

			}
			
			if ($aux_promotor_treinamento == '') $aux_promotor_treinamento = 0;
			$sql = "select nome, email
					from tbl_promotor_treinamento 
					where promotor_treinamento = $aux_promotor_treinamento";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$nome_promotor      = pg_result($res,0,nome)     ;
				$email_promotor      = pg_result($res,0,email)     ;
				if(strlen($email_promotor)>0){
					$sql = "select nome, codigo_posto 
							from tbl_posto 
							join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
							and tbl_posto_fabrica.fabrica = $login_fabrica
							where tbl_posto.posto = $login_posto";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						$nome_posto      = pg_result($res,0,nome)        ;
						$xcodigo_posto   = pg_result($res,0,codigo_posto);
						
						$corpo = "";

						$email_origem  = "verificacao@telecontrol.com.br";
						$email_destino = "$email_promotor";
						$assunto       = "Confirmação de Presença no Treinamento";
						$corpo.="<br>Caro Promotor,";
						$corpo.="<BR>Segue abaixo informações do posto e o treinamento solicitado\n<BR>";

						$corpo.= "Titulo: $titulo <br>\n";
						$corpo.= "Data Inicío: $data_inicio<br> \n";
						$corpo.= "Data Termino: $data_fim <p>\n";
						$corpo.="<BR>Posto: $xcodigo_posto - $nome_posto\n";
						$corpo.="<br>Nome: $tecnico_nome \n";
						$corpo.="<br>RG:$tecnico_rg \n";
						$corpo.="<br>CPF: $tecnico_cpf \n";
						$corpo.="<br>Telefone de Contato: $tecnico_fone \n\n";
						if($adicional) $corpo.="<br>$adicional: $observacao \n\n";
						$corpo.="<br>Email: $email\n\n";
						$corpo.="<br><br><br>Telecontrol\n";
						$corpo.="<br>www.telecontrol.com.br\n";
						$corpo.="<br>_______________________________________________\n";
						$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";
				
				
						$body_top = "MIME-Version: 1.0\r\n";
						$body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
						$body_top .= "From: $email_origem\r\n";

						if ( @mail($email_destino, stripslashes($assunto), $corpo, $body_top ) ){
							$msg = "$email";
						}else{
							$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";

						}
					}
				}
			
			}



		}

		if (strlen($msg_erro) == 0 ) {
			$res = @pg_exec ($con,"COMMIT TRANSACTION");
			echo "ok|<center><font size='4'color='#009900'><b>Treinamento Agendado com sucesso!</b></font><br><a href='javascript:mostrar_treinamento(\"dados\");'>Ver treinamentos</a></center>";
			exit;
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			echo  "2|<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s):</b><br> $msg_erro";
			exit;
		}

	}

	if (strlen($msg_erro) > 0) {
		echo "1|".$msg;
	}
	exit;

}













//--==== Ver treinamentos cadastrados ========================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='ver') {
	$sql = "SELECT tbl_treinamento.treinamento                                            ,
			tbl_treinamento.titulo                                                ,
			tbl_treinamento.descricao                                             ,
			tbl_treinamento.ativo                                                 ,
			tbl_treinamento.vagas                                                 ,
			TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio      ,
			TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim         ,
			tbl_linha.nome                                    AS linha_nome       ,
			tbl_familia.descricao                             AS familia_descricao,
			(
				SELECT count(*) 
				FROM tbl_treinamento_posto 
				WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
				AND   tbl_treinamento_posto.ativo IS TRUE
			)                                                 AS qtde_postos      ,
			(
				SELECT count(*) 
				FROM tbl_treinamento_posto 
				WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
				AND   tbl_treinamento_posto.posto = $login_posto
				AND   tbl_treinamento_posto.ativo IS TRUE
			)                                                 AS qtde_inscritos
		FROM tbl_treinamento
		JOIN      tbl_admin   USING(admin)
		JOIN      tbl_linha   USING(linha)
		LEFT JOIN tbl_familia USING(familia)
		WHERE tbl_treinamento.fabrica = $login_fabrica
		AND   tbl_treinamento.ativo IS TRUE
		AND   tbl_treinamento.data_inicio >= CURRENT_DATE
		ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo
		" ;

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		$resposta  .=  '<table align="center" width="700" cellspacing="1" class="tabela">';
		$resposta  .=  "<TR class='titulo_coluna'  height='25'>";
		$resposta  .=  "<TD><b> </b></TD>";
		$resposta  .=  "<TD><b>Título</b></TD>";
		$resposta  .=  "<TD><b>Data Início</b></TD>";
		$resposta  .=  "<TD><b>Data Fim</b></TD>";
		$resposta  .=  "<TD><b>Linha</b></TD>";
		$resposta  .=  "<TD><b>Vagas Geral</b></TD>";
		$resposta  .=  "<TD><b>&nbsp;&nbsp;Situação&nbsp;&nbsp;</b></TD>";
		if ($login_fabrica==20){
			$resposta  .=  "<TD><b>Qtd.&nbsp;técnicos cadastrado</b></TD>";
			$resposta  .=  "<TD><b>Qtd.&nbsp;vagas disponíveis por posto</b></TD>";
		}
		$resposta  .=  "</TR>";

		for ($i=0; $i<pg_numrows($res); $i++){

			$treinamento       = trim(pg_result($res,$i,treinamento))      ;
			$titulo            = trim(pg_result($res,$i,titulo))           ;
			$descricao         = trim(pg_result($res,$i,descricao))        ;
			$ativo             = trim(pg_result($res,$i,ativo))            ;
			$data_inicio       = trim(pg_result($res,$i,data_inicio))      ;
			$data_fim          = trim(pg_result($res,$i,data_fim))         ;
			$linha_nome        = trim(pg_result($res,$i,linha_nome))       ;
			$familia_descricao = trim(pg_result($res,$i,familia_descricao));
			$vagas             = trim(pg_result($res,$i,vagas))            ;
			$vagas_postos      = trim(pg_result($res,$i,qtde_postos))      ;
			$vagas_ocupadas    = trim(pg_result($res,$i,qtde_inscritos))   ;
			
			if($login_fabrica == 20){
				$sql2="select count(treinamento_posto) as qtd_incritos_posto from tbl_treinamento_posto where posto=$login_posto and treinamento=$treinamento";
				$res2 = pg_exec ($con,$sql2);
				$qtd_incritos_posto = trim(pg_result($res2,0,qtd_incritos_posto));
				
				if($vagas_postos >= $vagas){
					$qtd_disponivel=0;
				}else{
					$qtd_disponivel=2-$qtd_incritos_posto;
				}
			}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			if($vagas_postos >= $vagas) $situacao = "<img src='admin/imagens_admin/status_vermelho.gif'> SEM VAGAS";
			else                        $situacao = "<img src='admin/imagens_admin/status_verde.gif'> HÁ VAGAS";

			if($vagas_ocupadas>0)$tem = "<img src='imagens/img_ok.gif'>";
			else                 $tem = "";

			$sobra = $vagas - $vagas_postos;
			
			$resposta  .=  "<TR bgcolor='$cor' class='Conteudo'>";
			$resposta  .=  "<TD align='center'>$tem </TD>";
			
				if($login_fabrica == 20){
					if ($qtd_disponivel>0){
						$resposta  .=  "<TD align='left'nowrap><a href='javascript:treinamento_formulario($treinamento)'>$titulo</a></TD>";
				}else{
						$resposta  .=  "<TD align='left'nowrap>$titulo</TD>";
				}
			}else{
				$resposta  .=  "<TD align='left'nowrap><a href='javascript:treinamento_formulario($treinamento)'>$titulo</a></TD>";
			}
			$resposta  .=  "<TD align='left'>$data_inicio</a></TD>";
			$resposta  .=  "<TD align='left'>$data_fim</TD>";
			$resposta  .=  "<TD align='left'>$linha_nome</TD>";
			$resposta  .=  "<TD align='center'>";
			if($login_fabrica == 20) $resposta  .=  $sobra;
			else                     $resposta  .=  $vagas;
			$resposta  .=  "</TD>";
			$resposta  .=  "<TD align='left'>$situacao</TD>";

			if ($login_fabrica==20){
				$resposta  .=  "<TD align='left'>$qtd_incritos_posto</TD>";
				$resposta  .=  "<TD align='left'>$qtd_disponivel</TD>";
			}
			$resposta  .=  "</TR>";
			
			$total = $total_os + $total;

		}
		$resposta .= " </TABLE>";
	}

		
	//--==== Ver técnicos cadastrados em treinamentos ============================
	$sql = "SELECT DISTINCT tbl_treinamento_posto.tecnico_nome                                           ,
			tbl_treinamento_posto.tecnico_cpf                                           ,
			tbl_treinamento_posto.ativo                                                 ,
			tbl_treinamento.titulo                                                      ,
			TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS data_inscricao,
			TO_CHAR(tbl_treinamento_posto.data_inscricao,'HH24:MI:SS') AS hora_inscricao

		FROM tbl_treinamento_posto
		JOIN tbl_treinamento using(treinamento)
		JOIN      tbl_posto USING(posto)
		JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_treinamento_posto.posto = $login_posto
		AND  tbl_treinamento.fabrica = $login_fabrica
		
		ORDER BY tbl_treinamento.titulo
" ;

	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
  
		$resposta  .=  '<table align="center" width="700" cellspacing="1" class="tabela">';
		$resposta  .=  "<tr height='20'>";
		$resposta  .=  "<td colspan='7'class='texto_avulso' align='center'><br><img src='imagens/img_ok.gif'> <b>TREINAMENTO(S) JÁ AGENDADO(S) PELO POSTO</b><br /><br /></td>";
		$resposta  .=  "</tr>";
		$resposta  .=  "</table><br />";

		$resposta  .=  '<table align="center" width="700" cellspacing="1" class="tabela">';
		$resposta  .=  "<TR class='titulo_coluna' height='20'>";
		$resposta  .=  "<TD><b>Título</b></TD>";
		$resposta  .=  "<TD><b>Nome do Técnico</b></TD>";
		$resposta  .=  "<TD><b>CPF do Técnico</b></TD>";
		$resposta  .=  "<TD><b>Data de Inscrição</b></TD>";
		$resposta  .=  "<TD><b>Hora de Inscrição</b></TD>";
		$resposta  .=  "<TD><b>Ativo</b></TD>";
		$resposta  .=  "</TR>";

		for ($i=0; $i<pg_numrows($res); $i++){

			$tecnico_nome      = trim(pg_result($res,$i,tecnico_nome))  ;
			$tecnico_cpf       = trim(pg_result($res,$i,tecnico_cpf))   ;
			$data_inscricao    = trim(pg_result($res,$i,data_inscricao));
			$hora_inscricao    = trim(pg_result($res,$i,hora_inscricao));
			$titulo            = trim(pg_result($res,$i,titulo))        ;
			$ativo             = trim(pg_result($res,$i,ativo))         ;

			if($ativo == 't')  $ativo = "<img src='admin/imagens_admin/status_verde.gif'>";
			else               $ativo = "<img src='admin/imagens_admin/status_vermelho.gif'>";

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			$resposta  .=  "<TR bgcolor='$cor' >";
			$resposta  .=  "<TD align='left'>$titulo </TD>";
			$resposta  .=  "<TD align='left'nowrap>$tecnico_nome</TD>";
			$resposta  .=  "<TD align='left'>$tecnico_cpf</TD>";
			$resposta  .=  "<TD align='center'>$data_inscricao</TD>";
			$resposta  .=  "<TD align='center'>$hora_inscricao</TD>";
			$resposta  .=  "<TD align='center'>$ativo</TD>";
			$resposta  .=  "</TR>";

		}
		$resposta .= " </TABLE>";
	}else{
		$resposta .= "<b>Nenhum posto fez a inscrição de seu técnico para participar do treinamento</b>";
	}



  echo "ok|".$resposta;
	exit;

}















if($_GET['ajax']=='sim' AND $_GET['acao']=='tecnico') {

	$treinamento  = trim($_GET["treinamento"]) ;
	$sql2 = "SELECT tecnico_nome      ,
			tecnico_rg        ,
			tecnico_cpf       ,
			tecnico_fone      ,
			confirma_inscricao,
			hotel             ,
			ativo
		FROM tbl_treinamento_posto WHERE treinamento = $treinamento AND  posto = $login_posto";

	$res2 = pg_exec ($con,$sql2);

	if (pg_numrows($res2) > 0) {

		$resposta  .= '<br><table align="center" width="700" cellspacing="1" class="tabela">';
		$resposta  .= "<tr bgcolor='$cor' class='texto_avulso'>";
		$resposta  .= "<td colspan='7'><b>O(s) seguinte(s) técnico(s) estão inscrito(s) no treinamento: </b></td>";
		$resposta  .= "</tr>";

		$resposta  .= "<tr class='titulo_coluna'>";
		$resposta  .= "<td><b>Nome do Técnico</b></td>";
		$resposta  .= "<td><b>RG</b></td>";
		$resposta  .= "<td><b>CPF</b></td>";
		$resposta  .= "<td><b>Telefone</b></td>";
		$resposta  .= "<td><b>Inscrito</b></td>";
		$resposta  .= "<td><b>Confirmado</b></td>";
		$resposta  .= "<td><b>Hotel</b></td>";

		for ($i=0; $i<pg_numrows($res2); $i++){

			$tecnico_nome  = trim(pg_result($res2,$i,tecnico_nome))      ;
			$tecnico_rg    = trim(pg_result($res2,$i,tecnico_rg))        ;
			$tecnico_cpf   = trim(pg_result($res2,$i,tecnico_cpf))       ;
			$tecnico_fone  = trim(pg_result($res2,$i,tecnico_fone))      ;
			$confirma      = trim(pg_result($res2,$i,confirma_inscricao));
			$ativo         = trim(pg_result($res2,$i,ativo))             ;
			$hotel         = trim(pg_result($res2,$i,hotel))             ;

			if($ativo =='f')    $ativo    = "<img src='admin/imagens_admin/status_vermelho.gif'> Cancelado";
			else                $ativo    = "<img src='admin/imagens_admin/status_verde.gif'> Sim"         ;
			if($confirma =='f') $confirma = "<img src='admin/imagens_admin/status_vermelho.gif'> Não"      ;
			else                $confirma = "<img src='admin/imagens_admin/status_verde.gif'> Sim"         ;
			if($hotel =='f')    $hotel    = "<img src='admin/imagens_admin/status_vermelho.gif'> Não"      ;
			else                $hotel    = "<img src='admin/imagens_admin/status_verde.gif'> Sim"         ;

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			$resposta  .= "<tr bgcolor='$cor'>";
			$resposta  .= "<td>$tecnico_nome</td>";
			$resposta  .= "<td>$tecnico_rg</td>";
			$resposta  .= "<td>$tecnico_cpf</td>";
			$resposta  .= "<td>$tecnico_fone</td>";
			$resposta  .= "<td>$ativo</td>";
			$resposta  .= "<td>$confirma</td>";
			$resposta  .= "<td>$hotel</td>";
			$resposta  .= "</tr>";
		}
		$resposta  .= "</table>";
	}else{
		$resposta  .= "Não há técnicos do seu posto cadastrado neste treinamento.";
	}
	echo "ok|".$resposta;
	exit;
}














//--==== Formulário de cadastro de treinamento ===============================
if($_GET['ajax']=='sim' AND $_GET['acao']=='formulario') {

	$treinamento  = trim($_GET["treinamento"]) ;

	$sql = "SELECT email FROM tbl_posto WHERE posto = $login_posto";
	$res = pg_exec ($con,$sql);
	$posto_email = trim(pg_result($res,0,email));

	$sql = "SELECT tbl_treinamento.treinamento                                            ,
			tbl_treinamento.titulo                                                ,
			tbl_treinamento.descricao                                             ,
			tbl_treinamento.vagas                                    ,
			TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio      ,
			TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim         ,
			tbl_admin.nome_completo                                               ,
			tbl_linha.nome                                    AS linha_nome       ,
			tbl_familia.descricao                             AS familia_descricao,
			(
				SELECT count(*) 
				FROM tbl_treinamento_posto 
				WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
				AND   tbl_treinamento_posto.ativo IS TRUE
			)                                                 AS qtde_postos,
			tbl_treinamento.adicional
		FROM tbl_treinamento
		JOIN      tbl_admin   USING(admin)
		JOIN      tbl_linha   USING(linha)
		LEFT JOIN tbl_familia USING(familia)
		WHERE tbl_treinamento.fabrica     = $login_fabrica
		AND   tbl_treinamento.treinamento = $treinamento
		AND   tbl_treinamento.ativo IS TRUE
		ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {

		$treinamento       = trim(pg_result($res,0,treinamento))       ;
		$titulo            = trim(pg_result($res,0,titulo))            ;
		$descricao         = trim(pg_result($res,0,descricao))         ;
		$vagas             = trim(pg_result($res,0,vagas))             ;
		$data_inicio       = trim(pg_result($res,0,data_inicio))       ;
		$data_fim          = trim(pg_result($res,0,data_fim))          ;
		$nome_completo     = trim(pg_result($res,0,nome_completo))     ;
		$linha_nome        = trim(pg_result($res,0,linha_nome))        ;
		$familia_descricao = trim(pg_result($res,0,familia_descricao)) ;
		$vagas_postos      = trim(pg_result($res,0,qtde_postos))       ;
		$adicional         = trim(pg_result($res,0,adicional))         ;

		if($login_fabrica==20){
			$sql2="select count(treinamento_posto) as qtd_incritos_posto from tbl_treinamento_posto where posto=$login_posto and treinamento=$treinamento";
			$res2 = pg_exec ($con,$sql2);
			$qtd_incritos_posto = trim(pg_result($res2,0,qtd_incritos_posto));
			
			if($vagas_postos >= $vagas){
				$qtd_disponivel=0;
			}else{
				$qtd_disponivel_vagas=2-$qtd_incritos_posto;
			}
		}

		if($vagas_postos >= $vagas) $situacao = "<img src='admin/imagens_admin/status_vermelho.gif'> SEM VAGAS";
		else                       $situacao = "<img src='admin/imagens_admin/status_verde.gif'> HÁ VAGAS";

		$resposta  .= "<FORM name='frm_relatorio' METHOD='POST' ACTION='$PHP_SELF '>";
		$resposta  .= "<input type='hidden' name='treinamento' id='treinamento' value='$treinamento'>";
		$resposta  .= "<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando' width='150'></div>";
		
		$resposta  .= '<table align="center" width="700" cellspacing="1" class="tabela">';
		$resposta  .= "<tr class='titulo_coluna'>";
		$resposta  .= "<td'><b>Tema do Treinamento: $titulo</b></td>";
		$resposta  .= "</tr>";
		$resposta  .= "<tr>";
		$resposta  .= "<td bgcolor='#DBE5F5' valign='bottom'>";

		$resposta  .= '<table align="center" width="700" cellspacing="1" class="tabela">';

		$resposta  .= "<tr>";
		$resposta  .= "<td><b>Linha: </b></td>";
		$resposta  .= "<td>$linha_nome</td>";
		$resposta  .= "<td><b>Família: </b></td>";
		$resposta  .= "<td>$familia_descricao</td>";
		$sobra = $vagas - $vagas_postos;
		if($login_fabrica == 20) $tot_vagas  .= $sobra;
		else                     $tot_vagas  .= $vagas;
		$resposta  .= "<td><b>Vagas: $tot_vagas</b></td>";
		if($login_fabrica == 20) $resposta  .= "<td><b>Vagas disponível: $qtd_disponivel_vagas</b></td>";
		$resposta  .= "</tr>";

		$resposta  .= "<tr bgcolor='$cor'>";
		$resposta  .= "<td><b>Data Inicio: </b></td>";
		$resposta  .= "<td width='140'>$data_inicio</td>";
		$resposta  .= "<td><b>Data Termino: </b></td>";
		$resposta  .= "<td width='140'>$data_fim</td>";

		$resposta  .= "<td colspan='2'><b>$situacao</b></td>";
		$resposta  .= "</tr>";

		$resposta  .= "<tr>";
		$resposta  .= "<td colspan='7'><b>Descrição: </b><br>".nl2br($descricao)."</td>";
		$resposta  .= "</tr>";
		$resposta  .= "</table>";

		$resposta  .= "<center><a href='javascript:mostrar_treinamento(\"dados\");'>VER OUTROS TREINAMENTOS</a></center>";

		//--====== Exibe todos os técnicos que estão cadastrados neste treinamento ====================
		$resposta  .= "<div id='tecnico'></div>";


		//--====== Ver erros que ocorreram no cadastro ==============================================
		$resposta  .= "<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>";

		//--====== Libera o formulário se houver vagas ===============================================
		if($vagas_postos < $vagas){
			
			$resposta  .= "<div id='cadastro'>";
			$resposta  .= '<table align="center" width="700" cellspacing="1" class="tabela">'."<tr><td class='texto_avulso' align='center'><b>A confirmação da inscrição será feita através do link enviado no email do posto.</b></td></tr></table>";
			$resposta  .= '<table align="center" width="700" cellspacing="1" class="tabela">';
			$resposta  .= "<tr  bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right'>Nome do Técnico</td>";
			$resposta  .= "<td align='left' colspan='3'>";
			$resposta  .= "<input type='text' name='tecnico_nome' id='tecnico_nome' size='60' maxlength='100' class='Caixa' value=''>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'>RG do Técnico</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input type='text' name='tecnico_rg' id='tecnico_rg' size='15' maxlength='14' class='Caixa' value=''>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'>CPF do Técnico</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input type='text' name='tecnico_cpf' id='tecnico_cpf' size='15' maxlength='14' class='Caixa' value=''>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'> Dt. Nascimento do Técnico</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input type='text' name='tecnico_data_nascimento' id='tecnico_data_nascimento' size='10' maxlength='10' class='Caixa' value=''>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'>Telefone Contato</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input type='text' name='tecnico_fone' id='tecnico_fone' size='15' maxlength='14' class='Caixa' value=''>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";

			if ($adicional)
			{
				$resposta  .= "<tr bgcolor='#D9E2EF'>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "<td align='right' nowrap valign='top'>$adicional</td>";
				$resposta  .= "<td align='left'>";
				$resposta  .= "<input type='text' name='observacao' id='observacao' size='60' maxlength='200' class='Caixa' value=''>";
				$resposta  .= "</td>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "</tr>";
			}

			if($login_fabrica == 20){
/*
				$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "<td align='right' nowrap valign='top'>Promotor</td>";
				$resposta  .= "<td align='left'>";
				$resposta  .= "<input type='text' name='promotor' id='promotor' size='40' maxlength='80' class='Caixa' value=''>";
				$resposta  .= "</td>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "</tr>";
*/
				$resposta  .= "<tr bgcolor='#D9E2EF'>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "<td align='right' nowrap valign='top'>Promotor</td>";
				$resposta  .= "<td align='left'>";
				$resposta  .= "<select name='promotor_treinamento' id='promotor_treinamento' class='Caixa'>";
				$resposta  .= "<option value=''></option>";
				$sql = "SELECT escritorio_regional FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
				$res = @pg_exec ($con,$sql);
				$escritorio_regional = trim(@pg_result($res,$i,escritorio_regional));
				

				$sql = "SELECT * FROM tbl_promotor_treinamento WHERE fabrica = $login_fabrica AND escritorio_regional = '$escritorio_regional' order by nome";
				$res = @pg_exec ($con,$sql);
				if (@pg_numrows($res) > 0) {
					for($i=0;$i < @pg_numrows($res);$i++){
						$promotor_treinamento = trim(pg_result($res,$i,promotor_treinamento));
						$nome                 = trim(pg_result($res,$i,nome));
						$email                = trim(pg_result($res,$i,email));
						$regiao               = trim(pg_result($res,$i,regiao));
						
						$resposta  .= "<option value='$promotor_treinamento'>$nome</option>";
					}
				}

				$resposta  .= "";
				$resposta  .= "</select>";
				$resposta  .= "</td>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "</tr>";

			}

			if ($login_fabrica == 20){

				$resposta  .="<input type='hidden' name='hotel' id='hotel' value='f'>";

			}else{	
				$resposta  .= "<tr bgcolor='#D9E2EF'>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "<td align='right' nowrap valign='top'>Agendar Hotel?</td>";
				$resposta  .= "<td align='left'>";
				$resposta  .= "<input type='checkbox' name='hotel' id='hotel'  class='Caixa' value='t'>";
				$resposta  .= "</td>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "</tr>";

			}
			$resposta  .= "<tr bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'>Email do Posto</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input type='text' name='posto_email' ='posto_email'size='35' maxlength='50' class='Caixa' value='$posto_email'> * <font size='1'><b>Este e-mail é o e-mail do Posto Autorizado</b></font>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'>Política de Treinamento</td>";
			$resposta  .= "<td align='left' colspan='3'>";
			$resposta  .= "<textarea name='compromisso' id='compromisso' rows='7' cols='90' class='Caixa2' readonly>$termo_compromisso</textarea>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "</table><br>";
			$resposta  .= "<center>";
			$resposta  .= "<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='ACEITO' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_treinamento(this.form);}\" class='Botao1'> ";
			$resposta  .= "<INPUT TYPE='button' name='bt_cad_forn2' id='bt_cad_forn2' value='NÃO ACEITO' onClick='javascript:mostrar_treinamento(\"dados\");' class='Botao2'>";
			$resposta  .= "</center>";
			
			$resposta  .= "</div>";

		}else{
			$resposta  .= "<div align='center' class='msg_error'>Todas as vagas deste treinamento estão preenchidas!<br><a href='javascript:mostrar_treinamento(\"dados\");'>Ver agenda</a></div>";
		}
		$resposta  .= "</td>";
		$resposta  .= "</tr>";
		$resposta  .= "</table>";

		$resposta  .= "<script language='javascript'>mostrar_tecnico('$treinamento');</script>";

		$resposta  .= "</form>";

	}
	echo "ok|$resposta";
	exit;
}








$layout_menu = "";
$title = "Treinamento";

include "cabecalho.php";

?>
<style>
.Titulo {
	text-align: center;
	font-family: Verdana;
	font-size: 14px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Titulo2 {
	text-align: center;
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 8pt;
	font-weight: normal;
}
.Conteudo2 {
	font-family: Arial;
	font-size: 10pt;
}

.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Caixa2{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 7pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Botao1{
	BORDER-RIGHT:  #6699CC 1px solid; 
	BORDER-TOP:    #6699CC 1px solid; 
	BORDER-LEFT:   #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	FONT:             10pt Arial ;
	FONT-WEIGHT:      bold;
	COLOR:            #009900;
	BACKGROUND-COLOR: #EEEEEE;
}
.Botao2{
	BORDER-RIGHT:  #6699CC 1px solid; 
	BORDER-TOP:    #6699CC 1px solid; 
	BORDER-LEFT:   #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	FONT:             10pt Arial;
	FONT-WEIGHT:      bold;
	COLOR:            #990000;
	BACKGROUND-COLOR: #EEEEEE;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
</style>

<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
	background-color: #596D9B;
	color: white;
	font: normal normal bold 11px/normal Arial;
	text-align: center;
}
input[type=button]{
	cursor:pointer;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
.littleFont{
    font:bold 11px Arial;
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
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='ajax.js'></script>

<script language='javascript'>

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

//FUNï¿½O USADA PARA ATUALISAR, INSERIR E ALTERAR		
function gravar_treinamento(formulatio) {
//	ref = trim(ref);
	var acao='cadastrar';

	url = "treinamento_agenda_teste.php?ajax=sim&acao="+acao;
	for( var i = 0 ; i < formulatio.length; i++ ){
		if (formulatio.elements[i].type !='button'){
			if(formulatio.elements[i].type=='radio' || formulatio.elements[i].type=='checkbox'){
				if(formulatio.elements[i].checked == true){
					url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
				}
			}else{
				url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
			}
		}
	}

	var com = document.getElementById('erro');
	var com2 = document.getElementById('cadastro');

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

					formulatio.bt_cad_forn.value='ACEITO';
					com2.innerHTML = response[1];

					for( var i = 0 ; i < formulatio.length; i++ ){
						if (formulatio.elements[i].type=='text'){
							formulatio.elements[i].value = "";
						}
						if (formulatio.elements[i].type=='hidden'){
							mostrar_tecnico(formulatio.elements[i].value);
						}
					}

					com.style.visibility = "hidden";
					

				}
				if (response[0]=="0"){
					// posto ja cadastrado
					com.innerHTML = response[1];
					com.style.visibility = "visible";
					formulatio.bt_cad_forn.value='ACEITO';
				}
				if (response[0]=="1"){
					// dados incompletos
					com.innerHTML = response[1];
					com.style.visibility = "visible";
					formulatio.bt_cad_forn.value='ACEITO';
				}
				if (response[0]=="2"){
					// erro inesperado
					com.innerHTML = response[1];
					com.style.visibility = "visible";
					formulatio.bt_cad_forn.value='ACEITO';
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}


function mostrar_treinamento(componente) {
	var com = document.getElementById(componente);
	var acao='ver';

	url = "treinamento_agenda_teste.php?ajax=sim&acao="+acao;

	com.innerHTML   ="Carregando<br><img src='imagens/carregar2.gif'>";

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
					com.innerHTML   = response[1];
				}
				if (response[0]=="0"){
					// posto ja cadastrado
					alert(response[1]);
				}
				if (response[0]=="1"){
					// dados incompletos
					alert("Campos incompletos:\n\n"+response[1]);
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}


function mostrar_tecnico(treinamento) {
	
	var acao='tecnico';

	url = "treinamento_agenda_teste.php?ajax=sim&acao="+acao+"&treinamento="+treinamento;

	var com = document.getElementById('tecnico');
	com.innerHTML   ="Carregando<br><img src='imagens/carregar2.gif'>";

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
					com.innerHTML   = response[1];
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
function treinamento_formulario(treinamento) {
	
	var acao='formulario';

	url = "treinamento_agenda_teste.php?ajax=sim&acao="+acao+"&treinamento="+treinamento;

	var com = document.getElementById('dados');
	com.innerHTML   ="Carregando<br><img src='imagens/carregar2.gif'>";

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
					com.innerHTML   = response[1];
					mostrar_tecnico(treinamento);
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
</script>



<? include "javascript_pesquisas.php" ?>


<?
echo "<div id='dados'></div>";
echo "<script language='javascript'>mostrar_treinamento('dados');</script>";

include "rodape.php" 

?>