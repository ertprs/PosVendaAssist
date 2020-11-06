<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
include '../ajax_cabecalho.php';

$admin_privilegios = "info_tecnica";
include 'autentica_admin.php';

function valida_email($email="") {
    return preg_match("/^[a-z]+([\._\-]?[a-z0-9\._-]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email);
}

if ($login_fabrica == 20 and $_GET["lista_tecnico"]) {

	$lista_tecnico 	= $_GET["lista_tecnico"];
	$posto     		= $_GET["posto"];

	if (strlen($posto) > 0) {

		$sql = "SELECT posto
				  FROM tbl_posto_fabrica
				 WHERE fabrica = $login_fabrica
				   AND codigo_posto = '$posto'";

		$res = pg_query($con,$sql);
		if (pg_num_rows($res)>0) {
			$posto = pg_fetch_result($res,0, 'posto');

			$sql ="SELECT tecnico, nome
					 FROM tbl_tecnico
					WHERE tbl_tecnico.fabrica = $login_fabrica
					  AND tbl_tecnico.posto = $posto
					  AND tbl_tecnico.funcao = 'T' ";
			//echo $sql;
			$resD = pg_query($con,$sql) ;
			$row = pg_num_rows($resD);

			if ($row > 0) {

				for ($i = 0; $i < $row; $i++) {

					$tecnico = trim(pg_fetch_result($resD, $i, 'tecnico'));
					$tecnico_nome = trim(pg_fetch_result($resD, $i, 'nome'));

					$option .= "<option value='$tecnico'> $tecnico_nome </option>";
				}
			}else{
				$option .= "<option value=''> ".traduz('Nenhum técnico cadastrado')." </option>";
			}
		}else{
			$option .= "<option value=''> ".traduz('Nenhum técnico cadastrado')." </option>";
		}
	}
	echo $option;
	exit;
}

$termo_compromisso= traduz('Este é um termo de compromisso no qual, está sendo agendando para que o técnico aqui cadastrado pelo posto autorizado, a participar do treinamento aqui escolhido. Caso você não tenha certeza fica obrigado a clicar em NÃO ACEITO.
Clicando em ACEITO, você declara expressamente que o técnico cadastrado, está assumindo um compromisso para representar o posto autorizado, para participar do treinamento aqui agendado. Declara, por fim, conhecer e aceitar o Aviso Legal de Uso do sistema Assist Telecontrol.');

$termo_compromisso .= ($login_fabrica == 1)
    ? "\nEm conformidade com as nossas normas de segurança, é proibida a entrada na empresa trajando camisa regata, bermuda, boné, chinelo, sandália e sapato aberto. O TÉCNICO DEVERÁ UTILIZAR BOTA DE SEGURANÇA.\nVale lembrar que é proibida a captação de imagens nas dependências da fábrica."
    : "";
//--==== Cadastrar um técnico no treinamento =================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='cadastrar') {

	$tecnico_nome             = trim($_GET["tecnico_nome"]);
	//$tecnico         		  = trim($_GET["tecnico"]);
	$tecnico_cpf              = trim($_GET["tecnico_cpf"]);
	$tecnico_rg               = trim($_GET["tecnico_rg"]);
	$tecnico_fone             = trim($_GET["tecnico_fone"]);
	$posto_email              = trim($_GET["posto_email"]);
	$treinamento              = trim($_GET["treinamento"]);
	$promotor                 = trim($_GET["promotor"]);
	$promotor_treinamento     = trim($_GET["promotor_treinamento"]);
	$hotel                    = trim($_GET["hotel"]);
	$codigo_posto             = trim($_GET["codigo_posto"]);
	$posto_nome               = trim($_GET["posto_nome"])  ;
	$tecnico_data_nascimento  = trim($_GET["tecnico_data_nascimento"]);
	$observacao               = trim($_GET["observacao"]);
	$admin_aut_fora_prazo     = trim($_GET['confirma_cadastro_fora_de_prazo']);

	$tecnico_cpf = substr(preg_replace('/\D/', '', $tecnico_cpf), 0, 14);

	if ($login_fabrica <> 20) {
		if (strlen($tecnico_nome)            == 0) $msg_erro .= traduz("Favor informar o nome do técnico")."<br>";

		if($login_fabrica <> 138){
			if (strlen($tecnico_cpf)             == 0) $msg_erro .= traduz("Favor informar o CPF do técnico")."<br>";
			if (strlen($tecnico_data_nascimento) == 0) $msg_erro .= traduz("Favor informar a data de nascimento do t")."écnico<br>";
		}
		if (strlen($tecnico_fone)            == 0) $msg_erro .= traduz("Favor informar o telefone de contato")."<br>";
		if (strlen($posto_email)             == 0) $msg_erro .= traduz("Favor informar o email do posto")."<br>";

		if (strlen($tecnico_rg) == 0 and $login_fabrica != 117){
			$msg_erro .= traduz("Favor informar o RG do técnico")."<br>";
		}
		if (strlen($treinamento) == 0)
			$msg_erro .= traduz("Favor informar o treinamento escolhido")."<br>";
		elseif (strlen($observacao) == 0)
		{
			$sql = "SELECT adicional FROM tbl_treinamento WHERE treinamento=$treinamento";
			@$res = pg_query($con, $sql);
			if($res)
			{
				$adicional = pg_fetch_result($res, 0, 0);
				if ($adicional && $login_fabrica != 148)
				{
					$msg_erro .= traduz("Favor informar $adicional")."<br>";
				}
			}
			else
			{
				$msg_erro .= traduz("Favor informar o treinamento escolhido")."<br>";
			}
		}

		if (strlen($posto_email)>0) {
			if (!valida_email($posto_email))  $msg_erro .= "Email do POSTO inválido: $posto_email<br>";
		}
		if (strlen($codigo_posto)>0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) {
				$posto = trim(pg_fetch_result($res,0,0));
			} else {
				$msg_erro .= traduz("Posto não encontrado ")."<br>";
			}

		}
    }else{

    	if(strlen($posto_email)>0){
			if (!valida_email($posto_email))  $msg_erro .= traduz("Email do POSTO inválido: $posto_email")."<br>";
		}
		if(strlen($codigo_posto)>0){
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);
			$posto = trim(pg_fetch_result($res,0,0))   ;
		}

        if (strlen($treinamento) == 0){
            $msg_erro .= traduz("Favor informar o treinamento escolhido")."<br>";
        }elseif (strlen($observacao) == 0){
                $sql = "SELECT adicional FROM tbl_treinamento WHERE treinamento=$treinamento";
                @$res = pg_query($con, $sql);

                if($res) {
                    $adicional = pg_fetch_result($res, 0, 0);
                    if ($adicional) {
                        $msg_erro .= traduz("Favor informar $adicional")."<br>";
                    }
                } else {
                    $msg_erro .= traduz("Favor informar o treinamento escolhido")."<br>";
                }
        }

        if (strlen($tecnico_nome) == 0){
            $msg_erro .= traduz("Favor informar o nome do técnico")."<br>"      ;
        }else{
			$sql = "SELECT 	tbl_tecnico.nome,
							tbl_tecnico.tecnico,
							tbl_tecnico.rg,
							tbl_tecnico.cpf,
							tbl_tecnico.telefone,
							TO_CHAR(data_nascimento, 'DD/MM/YYYY') AS data_nascimento
					FROM tbl_tecnico
					WHERE posto = $posto AND fabrica = $login_fabrica AND funcao = 'T' AND tecnico_cpf = $tecnico_cpf";
            $res = @pg_query($con,$sql);
            //$msg_erro.= $sql;

			if (@pg_num_rows($res) > 0) {
				for($i=0;$i < @pg_numrows($res);$i++){
					$tecnico      = trim(@pg_fetch_result($res,$i,'tecnico'));
					$tecnico_nome = trim(@pg_fetch_result($res,$i,'nome'));
					$tecnico_rg   = trim(@pg_fetch_result($res,$i,'rg'));
					$tecnico_cpf  = trim(@pg_fetch_result($res,$i,'cpf'));
					$tecnico_fone = trim(@pg_fetch_result($res,$i,'telefone'));
					$tecnico_data_nascimento = trim(@pg_fetch_result($res,$i,'data_nascimento'));
				}
			}
		}
	}

	//////////////////////////////////////////////////////////////
	$tecnico_cpf = substr(preg_replace('/\D/', '', $tecnico_cpf), 0, 14);

	if(!validaCPF($tecnico_cpf) && $login_fabrica != 138){
        $msg_erro .= "O CPF do técnico não é válido<br />";
    }

	$aux_tecnico_nome = $tecnico_nome;
	if(strlen($tecnico_cpf) > 0){
		$aux_tecnico_cpf  = "'".$tecnico_cpf."'" ;
	}

	$aux_tecnico_rg   = "'".$tecnico_rg."'"  ;
	$aux_tecnico_fone = "'".$tecnico_fone."'";

	$tecnico_data_nascimento = preg_replace ("/\D/" , '', $tecnico_data_nascimento);

	if (strlen ($tecnico_data_nascimento) == 6) $tecnico_data_nascimento = substr ($tecnico_data_nascimento,0,4) . "20" . substr ($tecnico_data_nascimento,4,2);
	if (strlen ($tecnico_data_nascimento)   > 0) $tecnico_data_nascimento   = substr ($tecnico_data_nascimento,0,2)   . "/" . substr ($tecnico_data_nascimento,2,2)   . "/" . substr ($tecnico_data_nascimento,4,4);
	if (strlen ($tecnico_data_nascimento) < 10) $tecnico_data_nascimento = date ("d/m/Y");

	$x_tecnico_data_nascimento = substr ($tecnico_data_nascimento,6,4) . "-" . substr ($tecnico_data_nascimento,3,2) . "-" . substr ($tecnico_data_nascimento,0,2);

	if($login_fabrica <> 138){
		if(strlen($x_tecnico_data_nascimento)>0 ){
			$sql ="SELECT date'$x_tecnico_data_nascimento' > (current_date-interval'18 year')";
			$res = pg_query ($con,$sql);
			if(pg_fetch_result($res,0,0)=='t') $msg_erro.= traduz('NÃO É PERMITIDO A PARTICIPAÇÃO DE MENORES DE 18 ANOS NOS TREINAMENTOS');
		}
	}
	if(strlen($aux_tecnico_cpf)>0 OR strlen($tecnico_nome) > 0){

		if(strlen($aux_tecnico_cpf) > 0){
            $cond_tecnico = "AND tbl_tecnico.cpf = $aux_tecnico_cpf ";
        }else{
            $cond_tecnico = " AND tbl_tecnico.nome ILIKE '%$tecnico_nome%'";
        }

        $sql = "SELECT tbl_tecnico.nome
        		FROM tbl_treinamento
        		JOIN tbl_treinamento_posto USING(treinamento)
        		JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
        		WHERE tbl_treinamento.treinamento = $treinamento
        		AND tbl_treinamento.fabrica = $login_fabrica
        		$cond_tecnico";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0 && $login_fabrica != 138){
            $msg_erro .= traduz("Já existe um técnico cadastrado para este treinamento com o CPF informado")."<br>";
        }
    }

	$aux_promotor = (strlen($promotor)==0) ? "null" : "'".$promotor."'";
	$aux_promotor_treinamento = (strlen($promotor_treinamento)==0) ? "null" : "'".$promotor_treinamento."'";
	$aux_posto = (strlen($posto)==0) ? "null" : "'".$posto."'";

	if ($login_fabrica <> 20){
		$hotel = (strlen($hotel)==0) ? "'f'" : "'t'";
	}else{
		$hotel = "'".$hotel."'";
	}
	if (strlen($msg_erro) > 0) {
		$msg  = "<b>".traduz('Foi(foram) detectado(s) o(s) seguinte(s) erro(s): ')."</b><br>";
		$msg .= $msg_erro;
	} else
	   	$listar = "ok";

	if ($listar == "ok") {
		$res = @pg_query($con,"BEGIN TRANSACTION");
		//--==== Controle de Quantidade de vagas existentes no treinamento ======================================
		$sql = "SELECT COUNT(treinamento_posto) AS total_inscritos,
					   prazo_inscricao IS NOT NULL AND prazo_inscricao > CURRENT_DATE AS expirou_prazo,
					   tbl_treinamento.vagas
				  FROM tbl_treinamento
				  JOIN tbl_treinamento_posto USING(treinamento)
				 WHERE tbl_treinamento.treinamento = $treinamento
				   AND tbl_treinamento_posto.ativo IS TRUE
				 GROUP BY tbl_treinamento.vagas,prazo_inscricao;";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			$total_inscritos = trim(pg_fetch_result($res,0,'total_inscritos'));
			$expirou_prazo   = trim(pg_fetch_result($res,0,'expirou_prazo'));
			$vagas           = trim(pg_fetch_result($res,0,'vagas'));
			if($total_inscritos >= $vagas) $msg_erro .= traduz("Todas as Vagas estão preenchidas, procure uma nova data");
		}
/*		//--==== Controle de Quantidade de técnicos cadastrados por posto =======================================
		$sql = "SELECT * FROM tbl_treinamento_posto WHERE treinamento = $treinamento AND posto = $login_posto";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$msg_erro .= "Já existe um técnico com este treinamento agendado.";
		}
*/

		// controle de data máxima de inscrição: o admin tem que confirmar no checkbox
		if ($treinamento_prazo_inscricao and $expirou_prazo == 't' and $admin_aut_fora_prazo != 't') {
			$msg_erro .= traduz("Cadastro do técnico fora do prazo de inscrição!<br />Por favor, confirme que realmente quer inscrever o técnico fora de prazo.");
		}

		if(strlen($aux_tecnico_cpf) > 0){
			$cond_cpf = " AND cpf = $aux_tecnico_cpf ";
		}else{
			$cond_cpf = " AND nome ILIKE '%$tecnico_nome%' ";
		}

		$sql = "SELECT tecnico
                  FROM tbl_tecnico
                 WHERE fabrica  = $login_fabrica AND posto = $posto $cond_cpf";
		$resTecnico = pg_query($con,$sql);

		if (pg_num_rows($resTecnico) > 0) {
			$tecnico = pg_fetch_result($resTecnico,0,tecnico);
		} else {
			if(strlen($aux_tecnico_cpf) == 0){
				$aux_tecnico_cpf = 'null';
			}
			$aux_tecnico_nome = pg_escape_literal($con,$aux_tecnico_nome);
			if ($login_fabrica == 1) {
				$aux_tecnico_nome = utf8_encode($aux_tecnico_nome);				
			}
            $campos_black = ($login_fabrica == 1) ? "celular,email" : "telefone";

            if ($login_fabrica == 1) {
                $aux_tecnico_fone .= ",'$posto_email'";
            }

			$sql = "INSERT INTO tbl_tecnico(fabrica,posto,nome,cpf,data_nascimento,$campos_black, rg)
				VALUES ($login_fabrica,$posto,$aux_tecnico_nome,$aux_tecnico_cpf,'$x_tecnico_data_nascimento',$aux_tecnico_fone,$aux_tecnico_rg)";
			$resTecnico = pg_query($con,$sql);

			$sql = "select tecnico from tbl_tecnico where fabrica  = $login_fabrica and posto = $posto $cond_cpf;";
			$resTecnico = pg_query($con,$sql);

			if(pg_num_rows($resTecnico) > 0){
				$tecnico = pg_fetch_result($resTecnico,0,tecnico);
			}else{
				$msg_erro .= traduz('Erro ao cadastrar técnico');
			}
		}

		$sql = "INSERT INTO tbl_treinamento_posto (
			tecnico ,
			promotor     ,
			posto        ,
			hotel        ,
			treinamento  ,
			admin        ,
			promotor_treinamento,
			observacao
		)VALUES(
			$tecnico 		 ,
			$aux_promotor    ,
			$posto           ,
			$hotel           ,
			$treinamento     ,
			$login_admin     ,
			$aux_promotor_treinamento,
			".pg_escape_literal($con, $observacao)."
		)";

		if (strlen($msg_erro) == 0) {
			$res = pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);
			$sql = "SELECT CURRVAL ('seq_treinamento_posto')";
			$res = pg_query($con,$sql);
			$treinamento_posto = pg_fetch_result($res,0,0);

			$email = $posto_email;

			if($msg_erro==0){
				if ($login_fabrica == 148) {
					include_once '../class/communicator.class.php';
					$sql = "select linha from tbl_treinamento where treinamento = {$_GET['treinamento']} and fabrica = {$login_fabrica}";
					$res = @pg_exec ($con,$sql);
					$linha  = trim(pg_result($res,0,linha));
					$email = null;
					switch ($linha) {
						#Linha de Produtos: Outros Produtos / Sistema de Energia:
					case 874:
					case 873:
						$email = ['jose_simonato@yanmar.com'];
						break;
						#Linha de Produtos: Construção Civil:
					case 875:
						$email = ['pedro_ferreira@yanmar.com'];
						break;  
						#Linha de Produtos: Marítimo:
					case 877:  
						$email = ['geison_faccio@yanmar.com'];
						break;
						#Linha de Produtos: Agrícolas:
					case 876:   
						$email = ['isabela_florencio@yanmar.com'];
						break;
					}

					$assunto = "Confirmação de Presença do Posto em Treinamento";

					$corpoFabrica .= "Titulo: $titulo <br>\n";
					$corpoFabrica.= "Data Inicío: $data_inicio<br> \n";
					$corpoFabrica.= "Data Termino: $data_fim <p>\n";

					$corpoFabrica.="<br>Você recebeu esse email para confirmar a inscrição do técnico.\n\n";
					$corpoFabrica.="<br>Nome: $tecnico_nome \n";
					$corpoFabrica.="<br>RG:$tecnico_rg \n";
					$corpoFabrica.="<br>CPF: $tecnico_cpf \n";
					$corpoFabrica.="<br>Telefone de Contato: $tecnico_fone \n";
					$corpoFabrica.="<br>E-mail: $posto_email \n";

					$mailTc = new TcComm($externalId);
					$res = $mailTc->sendMail(
						$email,
						$assunto,
						$corpoFabrica,
						$externalEmail
					);

	            }
			$email = $posto_email;
				$chave1 = md5($posto);
				$chave2 = md5($treinamento_posto);
				$sql=  "SELECT  titulo,
								TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
								TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
								TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao
						  FROM  tbl_treinamento
						 WHERE  treinamento = $treinamento";
				$res = pg_query ($con,$sql);

				if (pg_num_rows($res) > 0) {
					$titulo          = pg_fetch_result($res,0,'titulo');
					if (mb_check_encoding($titulo, 'UTF-8')) {
						$titulo = utf8_decode($titulo);
					}
					$data_inicio     = pg_fetch_result($res,0,'data_inicio');
					$data_fim        = pg_fetch_result($res,0,'data_fim');
					$vagas_min       = pg_fetch_result($res,0,'vagas_min');
					$prazo_inscricao = pg_fetch_result($res,0,'prazo_inscricao');
				}

				//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
				$email_origem  = "verificacao@telecontrol.com.br";
				$email_destino = "$email";
				$assunto       = "Confirmação de Presença no Treinamento";
				$corpo.= "Titulo $titulo <br>\n";
				$corpo.= "Data Início $data_inicio<br> \n";
				$corpo.= "Data Término $data_fim <p>\n";

				if ($treinamento_prazo_inscricao && $login_fabrica != 148) { // se a fábrica usa prazo de inscrição, avisar o posto
					$corpo .= "<p>Lembramos que o prazo para <b>confirmar a inscrição</b> é até <strong>$prazo_inscricao</strong>: depois desta data, se a inscrição não foi confirmada, a mesma será cancelada.<p/>";
				}

				$corpo.="<br>Você recebeu esse email para confirmar a inscrição do técnico.\n\n";
				$corpo.="<br>Nome $tecnico_nome \n";
				if($login_fabrica != 117){
					$corpo.="<br>RG$tecnico_rg \n";
				}
				$corpo.="<br>CPF $tecnico_cpf \n";
				$corpo.="<br>Telefone de Contato $tecnico_fone \n\n";
				if($adicional) $corpo.="<br>$adicional $observacao \n\n";
				$corpo.="<br>Email $email\n\n";
				$corpo.="<br><br><a href='http://posvenda.telecontrol.com.br/assist/treinamento_confirmacao.php?key1=$chave1&key2=$posto&key3=$chave2&key4=$treinamento_posto'>CLIQUE AQUI PARA CONFIRMAR PRESENÇA</a>\n\n";
				$corpo.="<br>Caso o link acima esteja com problema copie e cole este link em seu navegador: http://posvenda.telecontrol.com.br/assist/treinamento_confirmacao.php?key1=$chave1&key2=$posto&key3=$chave2&key4=$treinamento_posto\n\n";
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
				$sql = "select nome, email
						from tbl_promotor_treinamento
						where promotor_treinamento = $aux_promotor_treinamento";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					$nome_promotor      = pg_fetch_result($res,0,nome)     ;
					$email_promotor      = pg_fetch_result($res,0,email)     ;
					if(strlen($email_promotor)>0){
						$sql = "select nome, codigo_posto
								from tbl_posto
								join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
								and tbl_posto_fabrica.fabrica = $login_fabrica
								where tbl_posto.posto = $posto";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res)>0){
							$nome_posto      = pg_fetch_result($res,0,nome)        ;
							$xcodigo_posto   = pg_fetch_result($res,0,codigo_posto);

							$corpo = "";
							$email_origem  = "verificacao@telecontrol.com.br";
							$email_destino = "$email_promotor";
							$assunto       = "Confirmação de Presença no Treinamento";
							$corpo.="<br>Caro Promotor,";
							$corpo.="<BR>Segue abaixo informações do posto e o treinamento solicitado\n<BR>";
							$corpo.= "Titulo $titulo <br>\n";
							$corpo.= "Data Início $data_inicio<br> \n";
							$corpo.= "Data Término $data_fim <p>\n";

							if ($treinamento_prazo_inscricao) { // se a fábrica usa prazo de inscrição, avisar o posto
								$corpo .= "<p>Lembramos que o prazo para <b>confirmar a inscrição</b> é até <strong>$prazo_inscricao</strong>: depois desta data, se a inscrição não foi confirmada, a mesma será cancelada.<p/>";
							}

							$corpo.="<BR>Posto $xcodigo_posto - $nome_posto\n";
							$corpo.="<br>Nome $tecnico_nome \n";
							if($login_fabrica != 117){
								$corpo.="<br>RG$tecnico_rg \n";
							}
							$corpo.="<br>CPF $tecnico_cpf \n";
							$corpo.="<br>Telefone de Contato $tecnico_fone \n\n";
							if($adicional) $corpo.="<br>$adicional $observacao \n\n";
							$corpo.="<br>Email $email\n\n";
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
		}

		if (strlen($msg_erro) == 0 ) {
			$res = @pg_query ($con,"COMMIT TRANSACTION");
			echo "ok|<center><font size='4'color='#009900'><b>".traduz('Treinamento Agendado com sucesso!')."</b></font><br><a href='javascript:mostrar_treinamento(\"dados\");'>".traduz('Ver treinamentos')."</a></center>";
			exit;
		}else{
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
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
if ($_GET['ajax']=='sim' AND $_GET['acao']=='ver') {

	$data_inicial = $_GET['data_inicial'];
	$data_final = $_GET['data_final'];
	$titulo = utf8_decode($_GET['titulo']);

	if ((strlen($data_inicial) > 0 && strlen($data_final) > 0) OR strlen($titulo) > 0) {
		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {

			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);

			if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
				$msg_erro  = "Data Inválida";
			} else {
				$aux_data_inicial = "{$yi}-{$mi}-{$di}";
				$aux_data_final   = "{$yf}-{$mf}-{$df}";

				if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
					$msg_erro  = "Data Final não pode ser menor que a Data Inicial";
				}else{
					$cond = " AND tbl_treinamento.data_inicio::DATE BETWEEN '{$aux_data_inicial}' and '{$aux_data_final}'";
				}
			}
		}

		if(strlen($titulo) > 0){
			$cond .= " AND tbl_treinamento.titulo ILIKE '{$titulo}'";
		}

		if(strlen($msg_erro) > 0){
			$erro = "<div class='alert alert-error'><h4>{$msg_erro}</h4></div>";
		}
	}else{
		$cond = " AND tbl_treinamento.data_inicio::DATE >= CURRENT_DATE";
	}

	$sql = "
        SELECT  tbl_treinamento.treinamento,
                tbl_treinamento.titulo,
                tbl_treinamento.descricao,
                tbl_treinamento.ativo,
                tbl_treinamento.vagas,
                tbl_treinamento.local,
                tbl_treinamento.cidade,
                TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
                TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim,
                tbl_linha.nome                                    AS linha_nome,
                tbl_familia.descricao                             AS familia_descricao,
                (
                    SELECT  COUNT(*)
                    FROM    tbl_treinamento_posto
                    WHERE   tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                    AND     tbl_treinamento_posto.ativo IS TRUE
                )                                                 AS qtde_postos
        FROM    tbl_treinamento
        JOIN    tbl_admin   USING(admin)
   LEFT JOIN    tbl_linha   USING(linha)
   LEFT JOIN    tbl_familia USING(familia)
        WHERE   tbl_treinamento.fabrica = $login_fabrica
	AND     tbl_treinamento.ativo IS TRUE
	$cond
  ORDER BY      tbl_treinamento.data_inicio,tbl_treinamento.titulo
  " ;
	$res = pg_query ($con,$sql);

	if($login_fabrica == 42){
		$resposta  .= "<script>$(function(){ $('#data_inicial').datepicker({dateFormat:'dd/mm/yy'}).mask('99/99/9999'); $('#data_final').datepicker({dateFormat:'dd/mm/yy'}).mask('99/99/9999');})</script>
				{$erro}
				<form class='form-search form-inline tc_formulario'>
				<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
				<br>
				<div class='row-fluid'>
				     <div class='span2'></div>
				     <div class='span4'>
					 <div class='control-group'>
					     <label class='control-label' for='data_inicial'>Data Inicial</label>
					     <div class='controls controls-row'>
						 <div class='span5'>
						     <input size='12' maxlength='10' type='text' name='data_inicial' id='data_inicial' value='{$data_inicial}' class='span12' >
						 </div>
					     </div>
					 </div>
				     </div>
				     <div class='span4'>
					 <div class='control-group'>
					     <label class='control-label' for='data_final'>Data Final</label>
					     <div class='controls controls-row'>                                                                                                                                                        
						 <div class='span5'>
						     <input size='12' maxlength='10' type='text' name='data_final' id='data_final' value='{$data_final}' class='span12'>
						 </div>
					     </div>
					 </div>
				     </div>
				     <div class='span2'></div>
				 </div>
				<div class='row-fluid'>
				 <div class='span2'></div>
				 <div class='span4'>
				     <div class='control-group'>
					 <label class='control-label' for='titulo'>Título</label>
					 <div class='controls controls-row'>
					     <div class='span5'>
						 <input size='12' type='text' name='titulo' id='titulo' value='{$titulo}' >
					     </div>
					 </div>
				     </div>
				 </div>
				 <div class='span2'></div>
				</div>
				<p><br/>
				    <button class='btn' id='btn_acao' type='button'  onclick='mostrar_treinamento(\"dados\")'>Pesquisar</button>
				    <input type='hidden' id='btn_click' name='btn_acao' value='' />
				</p><br/>
			</form>";
	}

	if (pg_num_rows($res) > 0) {
		$resposta  .=  "<table id='resultado_agendamento' class='table table-striped table-bordered table-hover table-fixed'>";
		$resposta  .=  "<thead>";
		$resposta  .=  "<tr class='titulo_coluna'>";
		$resposta  .=  "<td>Titulo</td>";
		$resposta  .=  "<td>Data Início</td>";
		$resposta  .=  "<td>Data Fim</td>";
		$resposta  .=  "<td>Linha</td>";
		$resposta  .=  "<td>Vagas</td>";
		$resposta  .=  "<td>Local</td>";
		$resposta  .=  "<td>Situação</td>";
		$resposta  .=  "</tr>";
		$resposta  .=  "</thead>";
		$resposta  .=  "<tbody>";
		for ($i=0; $i<pg_num_rows($res); $i++){
			if(strlen(pg_fetch_result($res,$i,cidade)) > 0){

                $cidade = pg_fetch_result($res,$i,cidade);
                $sql = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";
                $res_cidade = pg_query($con,$sql);
                if(pg_num_rows($res_cidade) > 0){
                    $cidade = pg_fetch_result($res_cidade,0,cidade);
                    $nome_cidade = pg_fetch_result($res_cidade,0,nome);
                    $estado_cidade = pg_fetch_result($res_cidade,0,estado);
                    $localizacao = ", ".$nome_cidade." - ".$estado_cidade;
                }else{
                    $localizacao = "";
                }

            }else{
                $localizacao = "";
            }

			$treinamento       = trim(pg_fetch_result($res,$i,'treinamento'))      ;
			$titulo            = trim(pg_fetch_result($res,$i,'titulo'))           ;
			if (mb_check_encoding($titulo, 'UTF-8')) {
                $titulo = utf8_decode($titulo);
            }
			$descricao         = trim(pg_fetch_result($res,$i,'descricao'))        ;
			$ativo             = trim(pg_fetch_result($res,$i,'ativo'))            ;
			$data_inicio       = trim(pg_fetch_result($res,$i,'data_inicio'))      ;
			$data_fim          = trim(pg_fetch_result($res,$i,'data_fim'))         ;
			$linha_nome        = trim(pg_fetch_result($res,$i,'linha_nome'))       ;
			if (empty($linha_nome) && $login_fabrica == 1) {
				unset($nomes_linha); 
				$sql_linha = "SELECT parametros_adicionais->'linha' AS linha FROM tbl_treinamento WHERE treinamento = $treinamento AND fabrica = $login_fabrica";
				$res_linha= pg_query($con, $sql_linha);
				if (pg_num_rows($res_linha) > 0) {
					$linhas = json_decode(pg_fetch_result($res_linha, 0, 'linha'));
					foreach ($linhas as $l) {
						$sql_linha_nome = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica AND linha = $l";
						$res_linha_nome = pg_query($con, $sql_linha_nome);
						if (pg_num_rows($res_linha_nome) > 0) {
							$nomes_linha[] = pg_fetch_result($res_linha_nome, 0, 'nome');

						}
					}
				}
				$linha_nome = implode(",", $nomes_linha);
			}
			$familia_descricao = trim(pg_fetch_result($res,$i,'familia_descricao'));
			$vagas             = trim(pg_fetch_result($res,$i,'vagas'))            ;
			$vagas_postos      = trim(pg_fetch_result($res,$i,'qtde_postos'))      ;
			$local      	   = trim(pg_fetch_result($res,$i,'local'))      ;
			if (mb_check_encoding($local, 'UTF-8')) {
    	        $local = utf8_decode($local);
            }

			$cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';
			$situacao = ($vagas_postos >= $vagas) ? "<img src='imagens_admin/status_vermelho.gif'> SEM VAGAS" : "<img src='imagens_admin/status_verde.gif'> HÁ VAGAS";
			$sobra = $vagas - $vagas_postos;
			$resposta  .=  "<TR bgcolor='$cor'>";
			$resposta  .=  "<TD align='left'nowrap><a href='javascript:treinamento_formulario($treinamento)'>$titulo</a></TD>";
			$resposta  .=  "<TD align='left'>$data_inicio</a></TD>";
			$resposta  .=  "<TD align='left'>$data_fim</TD>";
			$resposta  .=  "<TD align='left'>$linha_nome</TD>";
			$resposta  .=  "<TD align='right'>";
			$resposta  .= ($login_fabrica == 20) ? $sobra : $vagas;
			$resposta  .=  "</TD>";
			$resposta  .=  "<TD align='left'>$local $localizacao</TD>";
			$resposta  .=  "<TD align='left'>$situacao</TD>";
			$resposta  .=  "</TR>";

			$total = $total_os + $total;
		}
		$resposta  .=  "</tbody>";
		$resposta .= " </TABLE>";
	} else {
		$resposta = "<div class='Titulo' style='width:700px;text-align:center;margin:auto;'>Nenhum treinamento cadastrado!</div>";
	}

	echo "ok|".$resposta;
	exit;
}
if($_GET['ajax']=='sim' AND $_GET['acao']=='tecnico') {
	$treinamento  = trim($_GET["treinamento"]) ;
	if($login_fabrica == 138 || $login_fabrica == 117){ //HD-3261932
		$cond_tecnico = " AND tbl_treinamento_posto.tecnico IS NOT NULL ";
	}
    $sql2 = "
        SELECT  tbl_tecnico.nome            AS tecnico_nome      ,
                tbl_tecnico.cpf             AS tecnico_cpf       ,
                tbl_tecnico.rg              AS tecnico_rg       ,
            CASE WHEN fabrica = 1
                 THEN tbl_tecnico.celular
                 ELSE tbl_tecnico.telefone
            END                             AS tecnico_fone      ,
            confirma_inscricao,
            hotel             ,
            tbl_treinamento_posto.treinamento_posto,
            tbl_treinamento_posto.ativo
        FROM tbl_treinamento_posto left join tbl_tecnico on tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
        WHERE treinamento = $treinamento
        $cond_tecnico
        ";

	$res2 = pg_query ($con,$sql2);
	if (pg_num_rows($res2) > 0) {
		$resposta  .= "<div class='titulo_tabela '>Técnicos Inscritos:</div>";
		$resposta  .= "<br><table class='table table-striped table-bordered table-hover table-fixed'>";
		$resposta  .= "<thead>";
		$resposta  .= "<tr>";
		$resposta  .= "<td colspan='7'><b>O(s) seguinte(s) técnico(s) estão inscrito(s) no treinamento </b></td>";
		$resposta  .= "</tr>";
		$resposta  .= "<tr class='titulo_coluna'>";
		$resposta  .= "<td width='120'><b>Nome do Técnico</b></td>";
		if($login_fabrica != 117){
			$resposta  .= "<td width='40'><b>RG</b></td>";
		}
		$resposta  .= "<td width='40'><b>CPF</b></td>";
		$resposta  .= "<td width='40'><b>".(($login_fabrica == 1) ? "Celular" : "Telefone")."</b></td>";
		$resposta  .= "<td width='80'><b>Inscrito</b></td>";
		$resposta  .= "<td width='80'><b>Confirmado</b></td>";
		if (!in_array($login_fabrica, [1,148])) {
            $resposta  .= "<td width='80'><b>Hotel</b></td>";
        }
		$resposta  .= "</tr>";
		$resposta  .= "</thead>";
		for ($i=0; $i<pg_num_rows($res2); $i++){
			$tecnico_nome              = trim(pg_fetch_result($res2,$i,tecnico_nome))      ;
			if (mb_check_encoding($tecnico_nome, 'UTF-8')) {
				$tecnico_nome = utf8_decode($tecnico_nome);
			}
			$treinamento_posto         = trim(pg_fetch_result($res2,$i,treinamento_posto))      ;
			$tecnico_rg                = trim(pg_fetch_result($res2,$i,tecnico_rg))        ;
			$tecnico_cpf               = trim(pg_fetch_result($res2,$i,tecnico_cpf))       ;
			$tecnico_fone              = trim(pg_fetch_result($res2,$i,tecnico_fone))      ;
			$confirma                  = trim(pg_fetch_result($res2,$i,confirma_inscricao));
			$ativo                     = trim(pg_fetch_result($res2,$i,ativo))             ;
			$hotel                     = trim(pg_fetch_result($res2,$i,hotel))             ;
			if($ativo =='f')    $ativo    = "<img src='imagens_admin/status_vermelho.gif'> Cancelado";
			else                $ativo    = "<img src='imagens_admin/status_verde.gif'> Sim"         ;
			if($confirma =='f') $confirma = "<img src='imagens_admin/status_vermelho.gif'> Não"      ;
			else                $confirma = "<img src='imagens_admin/status_verde.gif'> Sim"         ;
			if($hotel =='f')    $hotel    = "<img src='imagens_admin/status_vermelho.gif'> Não"      ;
			else                $hotel    = "<img src='imagens_admin/status_verde.gif'> Sim"         ;
			$resposta  .= "<tr bgcolor='$cor'class='Caixa'>";
			$resposta  .= "<td align='left'>$tecnico_nome</td>";
			if($login_fabrica != 117){
				$resposta  .= "<td>$tecnico_rg</td>";
			}
			$resposta  .= "<td>$tecnico_cpf</td>";
			$resposta  .= "<td>$tecnico_fone</td>";
			$resposta  .= "<td>$ativo</td>";
			$resposta  .= "<td>$confirma</td>";
            if (!in_array($login_fabrica, [1,148])) {
                $resposta  .= "<td>$hotel</td>";
            }
			$resposta  .= "</tr>";
		}
		$resposta  .= "</table>";
	}else{
		$resposta  .= "<div class='titulo_tabela '>Técnicos Inscritos:</div>";
		$resposta  .= "<table class='table table-striped table-bordered table-hover table-fixed'>";
		$resposta  .= "<tr><td align='center'>Não há técnicos cadastrados neste treinamento.</td>";
		$resposta  .= "</tr>";
		$resposta  .= "</table>";
	}

	if ($count > 50) {
	?>
		<script>
			$.dataTableLoad({ table: "#resultado_agendamento" });
		</script>
	<?php
	}


	echo "ok|".$resposta;
	exit;
}
//--==== Formulário de cadastro de treinamento ===============================
if($_GET['ajax']=='sim' AND $_GET['acao']=='formulario') {
	$treinamento  = trim($_GET["treinamento"]) ;
	$sql = "SELECT tbl_treinamento.treinamento,
				   tbl_treinamento.titulo,
				   tbl_treinamento.descricao,
				   tbl_treinamento.vagas,
				   tbl_treinamento.vagas_min,
				   tbl_treinamento.local,
				   tbl_treinamento.cidade,
				   TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
				   TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
				   TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao,
				   prazo_inscricao > CURRENT_DATE                        AS expirou_prazo,
				   prazo_inscricao > CURRENT_DATE - INTERVAL '3 DAYS'    AS proximo_fim_prazo,
				   tbl_admin.nome_completo,
				   tbl_linha.nome                                        AS linha_nome,
				   tbl_familia.descricao                                 AS familia_descricao,
				   (SELECT COUNT(*)
						  FROM tbl_treinamento_posto
						 WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
						   AND tbl_treinamento_posto.ativo IS TRUE)      AS qtde_postos,
				   tbl_treinamento.adicional
			  FROM tbl_treinamento
			  JOIN tbl_admin   USING(admin)
		LEFT  JOIN tbl_linha   USING(linha)
        LEFT JOIN tbl_familia USING(familia)
			 WHERE tbl_treinamento.fabrica     = $login_fabrica
			   AND tbl_treinamento.treinamento = $treinamento
			   AND tbl_treinamento.ativo IS TRUE
			 ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo";
	$res = pg_query ($con,$sql);

	$sobra = $vagas - $vagas_postos;

	if (pg_num_rows($res) > 0) {
		$treinamento       = trim(pg_fetch_result($res,0,'treinamento'));
		$titulo            = trim(pg_fetch_result($res,0,'titulo'));
		if (mb_check_encoding($titulo, 'UTF-8')) {
			$titulo = utf8_decode($titulo);
		}
		$descricao         = trim(pg_fetch_result($res,0,'descricao'));
		if (mb_check_encoding($descricao, 'UTF-8')) {
			$descricao = utf8_decode($descricao);
		}
		$vagas             = trim(pg_fetch_result($res,0,'vagas'));
		$vagas_min         = trim(pg_fetch_result($res,0,'vagas_min'));
		$data_inicio       = trim(pg_fetch_result($res,0,'data_inicio'));
		$data_fim          = trim(pg_fetch_result($res,0,'data_fim'));
		$prazo_inscricao   = trim(pg_fetch_result($res,0,'prazo_inscricao'));
		$nome_completo     = trim(pg_fetch_result($res,0,'nome_completo'));
		$linha_nome        = trim(pg_fetch_result($res,0,'linha_nome'));
		$familia_descricao = trim(pg_fetch_result($res,0,'familia_descricao'));
		$vagas_postos      = trim(pg_fetch_result($res,0,'qtde_postos'));
		$adicional         = trim(pg_fetch_result($res,0,'adicional'));
		$local             = trim(pg_fetch_result($res,0,'local'));
		if (mb_check_encoding($local, 'UTF-8')) {
			$local = utf8_decode($local);
		}
		$expirou_prazo     = (pg_fetch_result($res,0,'expirou_prazo') == 't');
		$proximo_prazo_min_vagas = (pg_fetch_result($res,0,'proximo_fim_prazo') == 't');

		if (strlen(pg_fetch_result($res,0,cidade)) > 0) {

            $localizacao = '';
            $cidade      = pg_fetch_result($res,0,cidade);
            $sql         = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";
            $res_cidade  = pg_query($con,$sql);

            if(pg_num_rows($res_cidade) > 0){
                $cidade        = pg_fetch_result($res_cidade,0,'cidade');
                $nome_cidade   = pg_fetch_result($res_cidade,0,'nome');
                $estado_cidade = pg_fetch_result($res_cidade,0,'estado');
                $localizacao   = ", ".$nome_cidade." - ".$estado_cidade;
            }
        }

		if($vagas_postos >= $vagas) $situacao = "<img src='imagens_admin/status_vermelho.gif'> ".traduz('SEM VAGAS')."";
		else
		$situacao = "<img src='imagens_admin/status_verde.gif'> ".traduz('HÁ VAGAS')."";

		//--====== Ver erros que ocorreram no cadastro ==============================================
		$resposta  .= "<div id='erro' style='visibility:hidden;opacity:.85;' class='alert alert-error'></div>";
		//--====== Libera o formulário se houver vagas ===============================================

		$resposta  .= "<FORM name='frm_relatorio' METHOD='POST' ACTION='$PHP_SELF '  class='tc_formulario'>";
		$resposta  .= "<input type='hidden' name='treinamento' id='treinamento' value='$treinamento'>";

		$vaga =  ($login_fabrica == 20) ? $sobra : $vagas;

		$resposta  .= "<div class='titulo_tabela '>".traduz('Tema do Treinamento').": $titulo</div>";
		$resposta  .= "<div class='row-fluid'>
						<div class='span2'></div>
									<div class='span3'>
										 <b>".traduz('Linha')."</b> $linha_nome
									</div>
									<div class='span3'style='text-align:center'>
									   <b>".traduz('Família')."</b> $familia_descricao
									</div>
									<div class='span2' style='text-align:right'>
									<b>".traduz('Vagas')."</b> $vaga
									</div>
						<div class='span2'></div>
					</div>";
		$resposta  .= "<div class='row-fluid'>
						<div class='span2'></div>
							<div class='span3'>
								 <b>".traduz('Data Início')." </b> $data_inicio
							</div>
							<div class='span3'style='text-align:center'>
							   <b>".traduz('Data Término')." </b> $data_fim
							</div>
							<div class='span2' style='text-align:right'>
							<b>$situacao</b>
							</div>
						<div class='span2'></div>
					</div>";
		if ($treinamento_prazo_inscricao or $treinamento_vagas_min) {
		//if (1==1) {
			$cor_prazo = ($proximo_prazo_min_vagas) ? " style='color: darkorange'" : '';
			$cor_prazo = ($expirou_prazo) ? " style='color: darkred'" : $cor_prazo;
			$cor_vagas = ($vagas_postos > $vagas_min) ? 'black' : 'darkred';
			$resposta  .= "<div class='row-fluid'>
							<div class='span2'></div>
								<div class='span4'>
									 <b>".traduz('Prazo para a Inscrição')."</b> $prazo_inscricao
								</div>
								<div class='span4' style='text-align:right; color:$cor_vagas'>
								<b>".traduz('Nº mín. de vagas').":</b> $vagas_min
								</div>
							<div class='span2'></div>
						</div>";
		}

		$resposta  .= "<div class='titulo_tabela '>".traduz('Descrição').":</div>";
		$resposta  .= "<div class='row-fluid'>
							<div class='span2'></div>
								<div class='span8' style='text-align:center'>
									".nl2br($descricao)."
								</div>
							<div class='span2'></div>
						</div>";
		if ($login_fabrica != 148) {
			$resposta  .= "<div class='titulo_tabela '>".traduz('Local').":</div>";
			$resposta  .= "<div class='row-fluid'>
							<div class='span2'></div>
								<div class='span8' style='text-align:center'>
									".nl2br($local."  ".$localizacao)."
								</div>
							<div class='span2'></div>
						</div>";
		}
		
		//$resposta  .= "<center><a href='javascriptmostrar_treinamento(\"dados\");'>VER OUTROS TREINAMENTOS</a></center>"; HD268395
		//--====== Exibe todos os técnicos que estão cadastrados neste treinamento ====================
		$resposta  .= "<div id='tecnico'></div>";


		if($vagas_postos < $vagas){
		//if($vagas_postos == $vagas){
			$resposta  .= "<div id='cadastro'>";
			$resposta  .= "<div class='titulo_tabela '>".traduz('Dados do Técnico').":</div>";

			if ($login_fabrica <> 20) {
				$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group' id='codigo_posto_campos'>
												<label class='control-label' for='data_final'>".traduz('Código Posto')."</label>
												<div class='controls controls-row'>
													<div class='span7 input-append'>
														<div class='controls controls-row'>
															<h5 class='asteristico'>*</h5>
															<input type='text' name='codigo_posto' id='codigo_posto' class='span12' value=$codigo_posto>
															<span class='add-on' rel='lupa'><i class='icon-search' ></i></span>
															<input type='hidden' name='lupa_config' tipo='posto' parametro='codigo' />
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class='span4'>
											<div class='control-group' id='descricao_posto_campo'>
												<label class='control-label' for='data_final'>".traduz('Nome do Posto')."</label>
												<div class='controls controls-row'>
													<div class='span12 input-append'>
														<div class='controls controls-row'>
															<h5 class='asteristico'>*</h5>
															<input type='text' name='descricao_posto' id='descricao_posto' class='span12' value=$descricao_posto>
															<span class='add-on' rel='lupa'><i class='icon-search' ></i></span>
															<input type='hidden' name='lupa_config' tipo='posto' parametro='nome' />
														</div>
													</div>
												</div>
											</div>
										</div>
									<div class='span2'></div>
								</div>";
				$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group' id='tecnico_nome_campo'>
												<label class='control-label' for='data_final'>".traduz('Nome')."</label>
												<div class='controls controls-row'>
													<h5 class='asteristico'>*</h5>
													<input type='text' name='tecnico_nome' id='tecnico_nome' size='60' maxlength='100' class='span12' value=''>
												</div>
											</div>
										</div>";
						if($login_fabrica != 117){
							$resposta  .= "<div class='span4'>
												<div class='control-group' id='tecnico_rg_campo'>
													<label class='control-label' for='data_final'>RG</label>
													<div class='controls controls-row'>
														<h5 class='asteristico'>*</h5>
														<input type='text' name='tecnico_rg' id='tecnico_rg' size='15' maxlength='14' class='span12' value=''>
													</div>
												</div>
											</div>
										<div class='span2'></div>";
						}
						$resposta  .= "</div>";

						$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group' id='tecnico_cpf_campo'>
												<label class='control-label' for='data_final'>CPF</label>
												<div class='controls controls-row'>";
												if($login_fabrica <> 138){
													$resposta .= "<h5 class='asteristico'>*</h5>";
												}
													$resposta .="<input type='text' name='tecnico_cpf' id='tecnico_cpf' size='15' maxlength='14' class='span12' value=''>
												</div>
											</div>
										</div>
										<div class='span4'>
											<div class='control-group' id='data_nasc_campo'>
												<label class='control-label' for='data_final'>".traduz('Data de Nascimento')."</label>
												<div class='controls controls-row'>";
												if($login_fabrica <> 138){
													$resposta .= "<h5 class='asteristico'>*</h5>";
												}
													$resposta .="<input type='text' name='tecnico_data_nascimento' id='tecnico_data_nascimento' size='10' maxlength='10' class='span12' value=''>
												</div>
											</div>
										</div>
									<div class='span2'></div>
								</div>";
						$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group' id='contato_telefone_campo'>
												<label class='control-label' for='data_final'>".traduz('Celular Técnico')."</label>
												<div class='controls controls-row'>
												<h5 class='asteristico'>*</h5>
													<input type='text' name='tecnico_fone' id='tecnico_fone' size='15' maxlength='14' class='span12' value=''>
												</div>
											</div>
										</div>
									<div class='span2'></div>
								</div>";

			}else{
				$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group>
												<label class='control-label' for='data_final'>".traduz('Código Posto')."</label>
												<div class='controls controls-row'>
													<div class='span7 input-append'>
														<input type='text' name='codigo_posto' id='codigo_posto' class='span12' value=$codigo_posto onBlur='listaTecnico()'>
														<span class='add-on' rel='lupa'><i class='icon-search' ></i></span>
														<input type='hidden' name='lupa_config' tipo='posto' parametro='codigo' />
													</div>
												</div>
											</div>
										</div>
										<div class='span4'>
											<div class='control-group>
												<label class='control-label' for='data_final'>".traduz('Nome do Posto')."</label>
												<div class='controls controls-row'>
													<div class='span12 input-append'>
														<input type='text' name='descricao_posto' id='descricao_posto' class='span12' value=$descricao_posto>
														<span class='add-on' rel='lupa'><i class='icon-search' ></i></span>
														<input type='hidden' name='lupa_config' tipo='posto' parametro='nome' />
													</div>
												</div>
											</div>
										</div>
									<div class='span2'></div>
								</div>";
				$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group' id='tecnico_nome_campo'>
												<label class='control-label' for='data_final'>".traduz('Nome')."</label>
												<div class='controls controls-row'>
													<select name='tecnico_nome' id='tecnico_nome' class='Caixa' >
	                								</select>
												</div>
											</div>
										</div>
									<div class='span2'></div>
								</div>";

			}
			if ($adicional && $login_fabrica != 148){
				$resposta .= "<div class='row-fluid'>
										<div class='span2'></div>
										<div class='span4'>
											<div class='control-group>
												<label class='control-label' for='data_final'>$adicional</label>
												<div class='controls controls-row'>
													<input type='text' name='observacao' id='observacao' size='80' maxlength='500' class='span12' value=''>
												</div>
											</div>
										</div>
									</div>";
			}

			if($login_fabrica == 20){
				$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group>
												<label class='control-label' for='data_final'>".traduz('Promotor')."</label>
												<div class='controls controls-row'>
													<select name='promotor_treinamento' id='promotor_treinamento' class='span12'>
													<option value=''></option>";
														$sql = "SELECT * FROM tbl_promotor_treinamento WHERE fabrica = $login_fabrica and ativo='t' order by nome";
														$res = pg_query ($con,$sql);
														if (pg_num_rows($res) > 0) {
															for ($i=0;$i < pg_num_rows($res);$i++) {
																$promotor_treinamento = trim(pg_fetch_result($res,$i,'promotor_treinamento'));
																$nome                 = trim(pg_fetch_result($res,$i,'nome'));
																$email                = trim(pg_fetch_result($res,$i,'email'));
																$regiao               = trim(pg_fetch_result($res,$i,'regiao'));

																$resposta  .= "<option value='$promotor_treinamento'>$nome</option>";
															}
														}
	                								$resposta .="</select>
												</div>
											</div>
										</div>
									<div class='span2'></div>
								</div>";

				$resposta  .= "<input type='hidden' name='hotel' id='hotel' value='f'>";



			}else{
				if (!in_array($login_fabrica, array(1,117,138,148))) {
					$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group>
												<label class='control-label' for='data_final'>
												<input type='checkbox' name='hotel' id='hotel'  class='Caixa' value='t'>
												".traduz('Agenda Hotel')."</label>
											</div>
										</div>
									<div class='span2'></div>
								</div>";
				}
			}

			if ($treinamento_prazo_inscricao and $expirou_prazo) { // tem prazo e já superou o prazo
					$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group>
												<label class='control-label' for='data_final'>
												<input type='checkbox' name='confirma_cadastro_fora_de_prazo' id='fora_de_prazo' value='t'>
												Cadastrar fora de prazo?</label>
												<span style='width: 500px'>".traduz('O prazo máximo para a inscrição neste treinamento foi dia $prazo_inscricao.')."</span>
											</div>
										</div>
									<div class='span2'></div>
								</div>";

			}

			if ($login_fabrica == 1) {
			$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group' id='posto_email_campo'>
												<label class='control-label' for='data_final'>".traduz('Email do Técnico')."</label>
												<div class='controls controls-row'>
													<h5 class='asteristico'>*</h5>
													<input type='text' name='posto_email' id='posto_email' size='50' maxlength='50' class='span12' value='$posto_email'>
												</div>
											</div>
										</div>
									<div class='span2'></div>
								</div>";

			} else {

			$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span4'>
											<div class='control-group' id='posto_email_campo'>
												<label class='control-label' for='data_final'>".traduz('Email do Posto')."</label>
												<div class='controls controls-row'>
													<h5 class='asteristico'>*</h5>
													<input type='text' name='posto_email' id='posto_email' size='50' maxlength='50' class='span12' value='$posto_email'>
												</div>
												<span style='font-size:11px; font-weight:bold; line-height:15px''>".traduz('Este email é o email do POSTO AUTORIZADO.')."</span>
											</div>
										</div>
									<div class='span2'></div>
								</div>";
			}
			$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span8'>
											<div class='control-group>
												<label class='control-label' for='data_final'>".traduz('Termo de Compromisso')."</label>
												<TEXTAREA name='compromisso' id='compromisso' ROWS='7'  class='span12' READONLY>$termo_compromisso</TEXTAREA>
											</div>
										</div>
									<div class='span2'></div>
								</div>";
			$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span8'>
											<div class='control-group' style='font-weight:bold; text-align:center'>
												".traduz('A confirmação da inscrição será feita através do link enviado no email do posto.')."
											</div>
										</div>
									<div class='span2'></div>
								</div>";
			if (in_array($login_fabrica, array(138))) {
			$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span8'>
											<div class='control-group' style='text-align:center'>
												<button class='btn btn-info btn-upload-tecnico' type='button'>".traduz('Cadastro de Técnico em lote')."</button>
											</div>
										</div>
									<div class='span2'></div>
								</div>";
			}
			$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span8'>
											<div class='control-group' style='text-align:center'>
												<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='".traduz('ACEITO')."' onClick=\"gravar_treinamento(this.form)\" class='btn'>
												<INPUT TYPE='button' name='bt_cad_forn2' id='bt_cad_forn2' value='".traduz('NÃO ACEITO')."' onClick='javascript:mostrar_treinamento(\"dados\");' class='btn'>
											</div>
										</div>
									<div class='span2'></div>
								</div>";

		}else{
			$resposta  .= "<div class='row-fluid'>
									<div class='span2'></div>
										<div class='span8'>
											<div class='control-group' style='font-weight:bold; text-align:center'>
												<a href='javascript:mostrar_treinamento(\"dados\");'><img src='imagens/btn_voltarnovo.gif' /></a>
											</div>
										</div>
									<div class='span2'></div>
								</div>";
		}
		$resposta  .= "<script language='javascript'>mostrar_tecnico('$treinamento');</script>";
		$resposta  .= "</FORM>";




	}

	echo "ok|$resposta";
	exit;
}
$layout_menu = "tecnica";
$title = traduz("TREINAMENTO");
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>


<script language='javascript'>

$(function() {
	Shadowbox.init();
	//$("#tecnico_data_nascimento").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$(document).on('blur', '#codigo_posto', function(){
		if($(this).val() != ''){
			$("#codigo_posto_campos").removeClass("error");
		}
	});

	$(document).on('blur', '#descricao_posto', function(){
		if($(this).val() != ''){
			$("#descricao_posto_campo").removeClass("error");
		}
	});

	$(document).on('blur', '#tecnico_nome', function(){
		if($(this).val() != ''){
			$("#tecnico_nome_campo").removeClass("error");
		}
	});

	$(document).on('blur', '#tecnico_cpf', function(){
		if($(this).val() != ''){
			$("#tecnico_cpf_campo").removeClass("error");
		}
	});

	$(document).on('blur', '#tecnico_data_nascimento', function(){
		if($(this).val() != ''){
			//$("#tecnico_data_nascimento").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
			$("#data_nasc_campo").removeClass("error");
		}
	});

	$(document).on('blur', '#tecnico_fone', function(){
		if($(this).val() != ''){
			$("#contato_telefone_campo").removeClass("error");
		}
	});

	$(document).on('blur', '#posto_email', function(){
		if($(this).val() != ''){
			$("#posto_email_campo").removeClass("error");
		}
	});

	$(document).on('blur', '#tecnico_rg', function(){
		if($(this).val() != ''){
			$("#tecnico_rg_campo").removeClass("error");
		}
	});



	$(document).on('click', 'span[rel=lupa]', function(){
		$.lupa($(this));
	});

	$(document).on('click', '.btn-upload-tecnico', function(){
		var login_fabrica = '<?php echo $login_fabrica;?>';
		var login_admin = '<?php echo $login_admin;?>';
	    Shadowbox.open({
	        content: "upload_tecnico.php?treinamento="+$("#treinamento").val()+"&login_fabrica="+login_fabrica+"&login_admin="+login_admin,
	        player: "iframe",
	        width:  1024,
	        height: 300
	    });

	});


});


function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}



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
	var codigo_posto_campo = $("#codigo_posto").val();
	var descricao_posto_campo = $("#descricao_posto").val();
	var tecnico_nome_campo = $("#tecnico_nome").val();
    var tecnico_cpf_campo = $("#tecnico_cpf").val();
    var data_nasc_campo = $("#tecnico_data_nascimento").val();
    var telefone_contato_campo = $("#tecnico_fone").val();
    var posto_email_campo = $("#posto_email").val();
	var tecnico_rg_campo = $("#tecnico_rg").val();

    var msg_erro = false;

    if(codigo_posto_campo == ''){
		msg_erro = true;
		$("#codigo_posto_campos").addClass('error');
	}

    if(descricao_posto_campo == ''){
		msg_erro = true;
		$("#descricao_posto_campo").addClass('error');
	}

	if(descricao_posto_campo == ''){
		msg_erro = true;
		$("#tecnico_nome_campo").addClass('error');
	}

	if(descricao_posto_campo == ''){
		msg_erro = true;
		$("#tecnico_rg_campo").addClass('error');
	}

	<?php
		if($login_fabrica <> 138){
	?>
		if(data_nasc_campo == ''){
			msg_erro = true;
			$("#data_nasc_campo").addClass('error');
		}

		if(tecnico_cpf_campo == ''){
			msg_erro = true;
			$("#tecnico_cpf_campo").addClass('error');
		}
	<?php
		}
	?>

	if(telefone_contato_campo == ''){
		msg_erro = true;
		$("#contato_telefone_campo").addClass('error');
	}

	<?php if($login_fabrica == 1) { ?>
		if(telefone_contato_campo != '') {
			if (telefone_contato_campo.length <= 14) {
				$("#contato_telefone_campo").addClass('error');
			}
		}
	<? } ?>

	if(posto_email_campo == ''){
		msg_erro = true;
		$("#posto_email_campo").addClass('error');
	}

	if(msg_erro == true){
		$("#erro").text("Preencha os campos obrigatórios.").show();
		return;
	}

	var acao='cadastrar';/* INICIO HD 3331 RAPHAEL/IGOR- SEGUNDO RAPHAEL, ERA SOH COPIAR O QUE ESTAVA NO ARQUIVO DO POSTO E COLAR AQUI */

        /*RETIRADO
        if (formulatio.elements[i].type=='text' || formulatio.elements[i].type=='select-one' || formulatio.elements[i].type=='textarea' || formulatio.elements[i].type=='hidden' || formulatio.elements[i].type=='checkbox'){
            url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);

        }*/
	url = "treinamento_agenda.php?ajax=sim&acao="+acao;
	// var e = document.getElementById("tecnico_nome");
    // var itemSelecionado = e.options[e.selectedIndex].value;
    // alert(itemSelecionado);

	for( var i = 0 ; i < formulatio.length; i++ ){
		/* INICIO HD 3331 RAPHAEL/IGOR- SEGUNDO RAPHAEL, ERA SOH COPIAR O QUE ESTAVA NO ARQUIVO DO POSTO E COLAR AQUI */
		if (formulatio.elements[i].type !='button'){
			if(formulatio.elements[i].type=='radio' || formulatio.elements[i].type=='checkbox'){
				if(formulatio.elements[i].checked == true){
					url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
				}
			}else{
				url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
			}
		}
		/* INICIO HD 3331 RAPHAEL/IGOR- SEGUNDO RAPHAEL, ERA SOH COPIAR O QUE ESTAVA NO ARQUIVO DO POSTO E COLAR AQUI */

		/*RETIRADO
		if (formulatio.elements[i].type=='text' || formulatio.elements[i].type=='select-one' || formulatio.elements[i].type=='textarea' || formulatio.elements[i].type=='hidden' || formulatio.elements[i].type=='checkbox'){
			url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);

		}*/
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

					$(document).scrollTop(0);
				}
				if (response[0]=="1"){
					// dados incompletos
					com.innerHTML = response[1];
					com.style.visibility = "visible";
					formulatio.bt_cad_forn.value ='ACEITO';

					$(document).scrollTop(0);
				}
				if (response[0]=="2"){
					// erro inesperado
					com.innerHTML = response[1];
					com.style.visibility = "visible";
					formulatio.bt_cad_forn.value='ACEITO';

					$(document).scrollTop(0);
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
function mostrar_treinamento(componente) {
	var com = document.getElementById(componente);
	var acao='ver';
	var curDateTime = new Date();
	var data_inicial = $("#data_inicial").val();
	var data_final   = $("#data_final").val();
	var titulo = $("#titulo").val();

	$.ajax({
		type: 'GET',
		url: 'treinamento_agenda.php',
		data: {"ajax": "sim", "acao":acao,"data":curDateTime,"data_inicial":data_inicial,"data_final":data_final,"titulo":titulo},
		beforeSend: function(){
			$(com).html("Carregando<br><img src='imagens/carregar2.gif'>")
		},
		complete: function(resposta){
			var response = resposta.responseText.split("|");
			if (response[0]=="ok"){
				$(com).html(response[1]);
			}
		}
	})
}

function mostrar_tecnico(treinamento) {
	var acao='tecnico';
	url = "treinamento_agenda.php?ajax=sim&acao="+acao+"&treinamento="+treinamento;
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
	url = "treinamento_agenda.php?ajax=sim&acao="+acao+"&treinamento="+treinamento;
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
					$("#tecnico_data_nascimento").datepicker({dateFormat: "dd/mm/yy" }).mask("99/99/9999");
					$("#tecnico_cpf").mask("999.999.999-99");
					$('#tecnico_fone').mask('(99) 99999-9999');

					$(document).on('blur', '#tecnico_fone', function(){
						if($(this).val().length > 14){
					      $('#tecnico_fone').mask('(99) 99999-9999');
					   } else {
					      $('#tecnico_fone').mask('(99) 9999-99999');
					   }
					});
					mostrar_tecnico(treinamento);
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}
	if (tipo == "nome" ) {
		var xcampo = campo2;
	}
	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		$("#codigo_posto").focus();
	}
}
function char(i){
	try{var element = i.which	}catch(er){};
	try{var element = event.keyCode	}catch(er){};
	if (String.fromCharCode(element).search(/[0-9]|[/]/gi) == -1)
	return false
}

function listaTecnico() {
	var posto = $("#codigo_posto").val();
	//alert("teste2"+posto+"");
	$.ajax({
		url:"treinamento_agenda.php",
		type:"GET",
		data:{
			lista_tecnico:1,
			posto:posto
		},
		complete: function(data){
			$("#tecnico_nome").html(data.responseText);
		}

	});
	}

</script>

<?
echo "<div id='dados'></div>";
echo "<script language='javascript'>mostrar_treinamento('dados');</script>";
include "rodape.php"
?>
