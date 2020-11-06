<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
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
				$option .= "<option value=''> Nenhum técnico cadastrado </option>";
			}
		}else{
			$option .= "<option value=''> Nenhum técnico cadastrado </option>";
		}
	}
	echo $option;
	exit;
}

$termo_compromisso='Este é um termo de compromisso no qual, está sendo agendando para que o técnico aqui cadastrado pelo posto autorizado, a participar do treinamento aqui escolhido. Caso você não tenha certeza fica obrigado a clicar em NÃO ACEITO.
Clicando em ACEITO, você declara expressamente que o técnico cadastrado, está assumindo um compromisso para representar o posto autorizado, para participar do treinamento aqui agendado. Declara, por fim, conhecer e aceitar o Aviso Legal de Uso do sistema Assist Telecontrol.';
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
		if (strlen($tecnico_nome)            == 0) $msg_erro .= "Favor informar o nome do técnico<br>";
		if (strlen($tecnico_cpf)             == 0) $msg_erro .= "Favor informar o CPF do técnico<br>";
		if (strlen($tecnico_fone)            == 0) $msg_erro .= "Favor informar o telefone de contato<br>";
		if (strlen($posto_email)             == 0) $msg_erro .= "Favor informar o email do posto<br>";
		if (strlen($tecnico_data_nascimento) == 0) $msg_erro .= "Favor informar a data de nascimento do técnico<br>";
		if (strlen($tecnico_rg) == 0 and $login_fabrica != 117)
			$msg_erro .= "Favor informar o RG do técnico<br>";

		if (strlen($treinamento) == 0)
			$msg_erro .= "Favor informar o treinamento escolhido<br>";
		elseif (strlen($observacao) == 0)
		{
			$sql = "SELECT adicional FROM tbl_treinamento WHERE treinamento=$treinamento";
			@$res = pg_query($con, $sql);
			if($res)
			{
				$adicional = pg_fetch_result($res, 0, 0);
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

		if (strlen($posto_email)>0) {
			if (!valida_email($posto_email))  $msg_erro .= "Email do POSTO inválido: $posto_email<br>";
		}
		if (strlen($codigo_posto)>0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);
			$posto = trim(pg_fetch_result($res,0,0))   ;
		}
    }else{

    	if(strlen($posto_email)>0){
			if (!valida_email($posto_email))  $msg_erro .= "Email do POSTO inválido: $posto_email<br>";
		}
		if(strlen($codigo_posto)>0){
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);
			$posto = trim(pg_fetch_result($res,0,0))   ;
		}

        if (strlen($treinamento) == 0){
            $msg_erro .= "Favor informar o treinamento escolhido<br>";
        }elseif (strlen($observacao) == 0){
                $sql = "SELECT adicional FROM tbl_treinamento WHERE treinamento=$treinamento";
                @$res = pg_query($con, $sql);

                if($res) {
                    $adicional = pg_fetch_result($res, 0, 0);
                    if ($adicional) {
                        $msg_erro .= "Favor informar $adicional<br>";
                    }
                } else {
                    $msg_erro .= "Favor informar o treinamento escolhido<br>";
                }
        }

        if (strlen($tecnico_nome) == 0){
            $msg_erro .= "Favor informar o nome do técnico<br>"      ;
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

	$aux_tecnico_nome = "'".$tecnico_nome."'";
	$aux_tecnico_cpf  = "'".$tecnico_cpf."'" ;
	$aux_tecnico_rg   = "'".$tecnico_rg."'"  ;
	$aux_tecnico_fone = "'".$tecnico_fone."'";

	$tecnico_data_nascimento = preg_replace ("/\D/" , '', $tecnico_data_nascimento);

	if (strlen ($tecnico_data_nascimento) == 6) $tecnico_data_nascimento = substr ($tecnico_data_nascimento,0,4) . "20" . substr ($tecnico_data_nascimento,4,2);
	if (strlen ($tecnico_data_nascimento)   > 0) $tecnico_data_nascimento   = substr ($tecnico_data_nascimento,0,2)   . "/" . substr ($tecnico_data_nascimento,2,2)   . "/" . substr ($tecnico_data_nascimento,4,4);
	if (strlen ($tecnico_data_nascimento) < 10) $tecnico_data_nascimento = date ("d/m/Y");

	$x_tecnico_data_nascimento = substr ($tecnico_data_nascimento,6,4) . "-" . substr ($tecnico_data_nascimento,3,2) . "-" . substr ($tecnico_data_nascimento,0,2);

	if(strlen($x_tecnico_data_nascimento)>0 ){
		$sql ="SELECT date'$x_tecnico_data_nascimento' > (current_date-interval'18 year')";
		$res = pg_query ($con,$sql);
		if(pg_fetch_result($res,0,0)=='t') $msg_erro.='NÃO É PERMITIDO A PARTICIPAÇÃO DE MENORES DE 18 ANOS NO TREINAMENTO BOSCH';
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
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
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
			$expirou_prazo   = trim(pg_fetch_result($res,0,'vagas'));
			$vagas           = trim(pg_fetch_result($res,0,'vagas'));
			if($total_inscritos >= $vagas) $msg_erro .= "Todas as Vagas estão preenchidas, procure uma nova data";
		}
/*		//--==== Controle de Quantidade de técnicos cadastrados por posto =======================================
		$sql = "SELECT * FROM tbl_treinamento_posto WHERE treinamento = $treinamento AND posto = $login_posto";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$msg_erro .= "Já existe um técnico com este treinamento agendado.";
		}
*/

		// controle de data máxima de inscrição: o admin tem que confirmar no checkbox
		if ($treinamento_prazo_inscricao and $expirou_prazo and $admin_aut_fora_prazo != 't') {
			$msg_erro .= "Cadastro do técnico fora do prazo de inscrição!<br />Por favor, confirme que realmetne quer inscrever o técnico fora de prazo.";
		}

		$sql = "SELECT tecnico
                  FROM tbl_tecnico
                 WHERE fabrica  = $login_fabrica AND posto = $posto AND cpf = $aux_tecnico_cpf;";
		$resTecnico = pg_query($con,$sql);

		if (pg_num_rows($resTecnico) > 0) {
			$tecnico = pg_fetch_result($resTecnico,0,tecnico);
		} else {
			$sql = "INSERT INTO tbl_tecnico(fabrica,posto,nome,cpf,data_nascimento,telefone, rg)
				VALUES ($login_fabrica,$posto,$aux_tecnico_nome,$aux_tecnico_cpf,'$x_tecnico_data_nascimento',$aux_tecnico_fone,$aux_tecnico_rg)";
			$resTecnico = pg_query($con,$sql);

			$sql = "select tecnico from tbl_tecnico where fabrica  = $login_fabrica and posto = $posto and cpf = $aux_tecnico_cpf;";
			$resTecnico = pg_query($con,$sql);

			if(pg_num_rows($resTecnico) > 0){
				$tecnico = pg_fetch_result($resTecnico,0,tecnico);
			}else{
				$msg_erro .= 'Erro ao cadastrar técnico';
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
			'$observacao'
		)";

		if (strlen($msg_erro) == 0) {
			$res = pg_query($con, $sql);
			$msg_erro .= pg_last_error($con);
			$sql = "SELECT CURRVAL ('seq_treinamento_posto')";
			$res = pg_query($con,$sql);
			$treinamento_posto = pg_fetch_result($res,0,0);

			$email = $posto_email;

			if($msg_erro==0){
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

				if ($treinamento_prazo_inscricao) { // se a fábrica usa prazo de inscrição, avisar o posto
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
			echo "ok|<center><font size='4'color='#009900'><b>Treinamento Agendado com sucesso!</b></font><br><a href='javascript:mostrar_treinamento(\"dados\");'>Ver treinamentos</a></center>";
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
	$sql = "SELECT tbl_treinamento.treinamento,
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
						SELECT COUNT(*)
						FROM tbl_treinamento_posto
						WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
						AND   tbl_treinamento_posto.ativo IS TRUE
					)                                                 AS qtde_postos
			  FROM tbl_treinamento
			  JOIN tbl_admin   USING(admin)
			  JOIN tbl_linha   USING(linha)
		 LEFT JOIN tbl_familia USING(familia)
			 WHERE tbl_treinamento.fabrica = $login_fabrica
			   AND tbl_treinamento.ativo IS TRUE
		/*AND   tbl_treinamento.data_inicio >= CURRENT_DATE*/
			 ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo
		" ;
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$resposta  .=  "<table border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center' width='700px'>";
		$resposta  .=  "<tr class='titulo_coluna'>";
		$resposta  .=  "<td>Titulo</td>";
		$resposta  .=  "<td>Data Início</td>";
		$resposta  .=  "<td>Data Fim</td>";
		$resposta  .=  "<td>Linha</td>";
		$resposta  .=  "<td>Vagas</td>";
		$resposta  .=  "<td>Local</td>";
		$resposta  .=  "<td>Situação</td>";
		$resposta  .=  "</tr>";
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
			$descricao         = trim(pg_fetch_result($res,$i,'descricao'))        ;
			$ativo             = trim(pg_fetch_result($res,$i,'ativo'))            ;
			$data_inicio       = trim(pg_fetch_result($res,$i,'data_inicio'))      ;
			$data_fim          = trim(pg_fetch_result($res,$i,'data_fim'))         ;
			$linha_nome        = trim(pg_fetch_result($res,$i,'linha_nome'))       ;
			$familia_descricao = trim(pg_fetch_result($res,$i,'familia_descricao'));
			$vagas             = trim(pg_fetch_result($res,$i,'vagas'))            ;
			$vagas_postos      = trim(pg_fetch_result($res,$i,'qtde_postos'))      ;
			$local      	   = trim(pg_fetch_result($res,$i,'local'))      ;

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
		$resposta .= " </TABLE>";
	} else {
		$resposta = "<div class='Titulo' style='width:700px;text-align:center;margin:auto;'>Nenhum treinamento cadastrado!</div>";
	}

	echo "ok|".$resposta;
	exit;
}
if($_GET['ajax']=='sim' AND $_GET['acao']=='tecnico') {
	$treinamento  = trim($_GET["treinamento"]) ;
	$sql2 = "SELECT tbl_tecnico.nome as tecnico_nome      ,
            tbl_tecnico.cpf as tecnico_cpf       ,
            tbl_tecnico.rg as tecnico_rg       ,
            tbl_tecnico.telefone as tecnico_fone      ,
            confirma_inscricao,
            hotel             ,
            tbl_treinamento_posto.ativo
        FROM tbl_treinamento_posto left join tbl_tecnico on tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
        WHERE treinamento = $treinamento";

	$res2 = pg_query ($con,$sql2);
	if (pg_num_rows($res2) > 0) {
		$resposta  .= "<br><table width='700px' cellpadding='2' cellspacing='0' class='formulario'  align='center'>";
		$resposta  .= "<td colspan='7' class='subtitulo'>Técnicos Inscritos:</td>";
		$resposta  .= "<tr bgcolor='$cor'>";
		$resposta  .= "<td colspan='7'><b>O(s) seguinte(s) técnico(s) estão inscrito(s) no treinamento </b></td>";
		$resposta  .= "</tr>";
		$resposta  .= "<tr bgcolor='$cor'>";
		$resposta  .= "<td width='120'><b>Nome do Técnico</b></td>";
		if($login_fabrica != 117){
			$resposta  .= "<td width='40'><b>RG</b></td>";
		}
		$resposta  .= "<td width='40'><b>CPF</b></td>";
		$resposta  .= "<td width='40'><b>Telefone</b></td>";
		$resposta  .= "<td width='80'><b>Inscrito</b></td>";
		$resposta  .= "<td width='80'><b>Confirmado</b></td>";
		$resposta  .= "<td width='80'><b>Hotel</b></td>";
		for ($i=0; $i<pg_num_rows($res2); $i++){
			$tecnico_nome              = trim(pg_fetch_result($res2,$i,tecnico_nome))      ;
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
			$resposta  .= "<td>$hotel</td>";
			$resposta  .= "</tr>";
		}
		$resposta  .= "</table>";
	}else{
		$resposta  .= "<br><table width='700px' cellpadding='2' cellspacing='0' class='formulario'  align='center'>";
		$resposta  .= "<tr><td class='subtitulo'>Técnicos Inscritos:</td></tr>";
		$resposta  .= "<tr><td align='center'>Não há técnicos cadastrados neste treinamento.</td>";
		$resposta  .= "</tr>";
		$resposta  .= "</table>";
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
			  JOIN tbl_linha   USING(linha)
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
		$descricao         = trim(pg_fetch_result($res,0,'descricao'));
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

		if($vagas_postos >= $vagas) $situacao = "<img src='imagens_admin/status_vermelho.gif'> SEM VAGAS";
		else                       $situacao = "<img src='imagens_admin/status_verde.gif'> HÁ VAGAS";
		$resposta  .= "<FORM name='frm_relatorio' METHOD='POST' ACTION='$PHP_SELF '>";
		$resposta  .= "<input type='hidden' name='treinamento' id='treinamento' value='$treinamento'>";
		$resposta  .= "<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando' width='150'></div>";

		$resposta  .= "<table width='700px' class='formulario' cellpadding='3' cellspacing='1' align='center'>";
		$resposta  .= "<caption class='titulo_tabela'>Tema do Treinamento: $titulo</caption>";
		$resposta  .= "<tr>";
		$resposta  .= "<td valign='bottom'>";
		$resposta  .= "<table width='700px' border='0' class='formulario' cellpadding='2' cellspacing='0' align='center'>";
		$resposta  .= "<tr bgcolor='$cor'>";
		$resposta  .= "<td align='right'><b>Linha</td>";
		$resposta  .= "<td align='left'><span>$linha_nome</span></td>";
		$resposta  .= "<td align='right'><b>Família </b></td>";
		$resposta  .= "<td align='left'>$familia_descricao</td>";
		$resposta  .= "<td align='right'><b>Vagas:</b></td>";
		$resposta  .= "<td align='right'>";
		$resposta  .= ($login_fabrica == 20) ? $sobra : $vagas;
		$resposta  .= "</td>";
		$resposta  .= "</tr>";
		$resposta  .= "<tr bgcolor='$cor' >";
		$resposta  .= "<td align='right'><b>Data Início </b></td>";
		$resposta  .= "<td width='140' align='left'>$data_inicio</td>";
		$resposta  .= "<td align='right'><b>Data Término </b></td>";
		$resposta  .= "<td width='140' align='left'>$data_fim</td>";
		$resposta  .= "<td colspan='2' align='right'><b>$situacao</b></td>";
		$resposta  .= "</tr>";

		if ($treinamento_prazo_inscricao or $treinamento_vagas_min) {
			$resposta .= "<tr bgcolor='$cor'>";
			$cor_prazo = ($proximo_prazo_min_vagas) ? " style='color: darkorange'" : '';
			$cor_prazo = ($expirou_prazo) ? " style='color: darkred'" : $cor_prazo;
			$resposta .= "<td $cor_prazo align='right'><b>Prazo para a Inscrição</b></td>";
			$resposta .= "<td $cor_prazo colspan='2'>$prazo_inscricao</td>";
			$resposta .= "<td>&nbsp;</td>";
			$cor_vagas = ($vagas_postos > $vagas_min) ? 'black' : 'darkred';
			$resposta .= "<td align='right' style='color:$cor_vagas'><b>Nº mín. de vagas:</b></td>";
			$resposta .= "<td colspan='2' align='right' style='color:$cor_vagas'>$vagas_min</td>";
			$resposta  .= "</tr>";
		}

		$resposta  .= "<tr bgcolor='$cor'>";
		$resposta  .= "<td colspan='7' class='subtitulo'>Descrição:</td>";
		$resposta  .= "</tr>";
		$resposta  .= "<tr bgcolor='$cor'>";
		$resposta  .= "<td colspan='7'>".nl2br($descricao)."</td>";
		$resposta  .= "</tr>";
		$resposta  .= "<tr bgcolor='$cor'>";
		$resposta  .= "<td colspan='7'>&nbsp;</td>";
		$resposta  .= "</tr>";

		$resposta  .= "<tr bgcolor='$cor'>";
		$resposta  .= "<td colspan='7' class='subtitulo'>Local: <br>".nl2br($local."  ".$localizacao)."</td>";
		$resposta  .= "</tr>";
		$resposta  .= "</table>";
		//$resposta  .= "<center><a href='javascriptmostrar_treinamento(\"dados\");'>VER OUTROS TREINAMENTOS</a></center>"; HD268395
		//--====== Exibe todos os técnicos que estão cadastrados neste treinamento ====================
		$resposta  .= "<div id='tecnico'></div>";
		//--====== Ver erros que ocorreram no cadastro ==============================================
		$resposta  .= "<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>";
		//--====== Libera o formulário se houver vagas ===============================================
		if($vagas_postos < $vagas){

			$resposta  .= "<div id='cadastro'>";
			$resposta  .= "<table class='formulario' align='center' width='700px'><tr><td class='Conteudo' align='center'><b>A confirmação da inscrição será feita através do link enviado no email do posto.</b></td></tr></table>";
			$resposta  .= "<table width='100%' border='0' cellspacing='1' cellpadding='2' >";

			$resposta  .= "<tr bgcolor='$cor'>";
			$resposta  .= "<td colspan='7' class='subtitulo'>Dados do Técnico</td>";
			$resposta  .= "</tr>";

			$resposta  .= "<tr>";
			$resposta  .= "<td width='10'>&nbsp;</td>";

		if ($login_fabrica <> 20) {
			$resposta  .= "<td align='right'nowrap>Código Posto</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input class='Caixa' type='text' name='codigo_posto' size='10' value='$codigo_posto'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style='cursor:pointer' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')\"></A>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right'nowrap>Nome do Posto</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input class='Caixa' type='text' name='posto_nome' size='30' value='$posto_nome'>&nbsp;<img src='imagens_admin/btn_lupa.gif' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')\" style='cursor:pointer;'></A>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right'nowrap>Nome</td>";
			$resposta  .= "<td align='left' colspan='3'>";
			$resposta  .= "<input type='text' name='tecnico_nome' id='tecnico_nome' size='60' maxlength='100' class='Caixa' value=''>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";

			if($login_fabrica != 117){
				$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "<td align='right' nowrap valign='top'>RG</td>";
				$resposta  .= "<td align='left'>";
				$resposta  .= "<input type='text' name='tecnico_rg' id='tecnico_rg' size='15' maxlength='14' class='Caixa' value=''>";
				$resposta  .= "</td>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "</tr>";
			}
			$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'>CPF</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input type='text' name='tecnico_cpf' id='tecnico_cpf' size='15' maxlength='14' class='Caixa' value=''>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'> Data de Nascimento</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input type='text' name='tecnico_data_nascimento' id='tecnico_data_nascimento' size='10' maxlength='10' class='Caixa' value=''>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'>Telefone Contato</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input type='text' name='tecnico_fone' id='tecnico_fone' size='15' maxlength='14' class='Caixa' value=''>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";

		}else{
			$resposta  .= "<td align='right'nowrap>Código Posto</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input class='Caixa' type='text' id='codigo_posto' name='codigo_posto' size='10' value='$codigo_posto'  onBlur='listaTecnico()'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style='cursor:pointer' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')\"></A>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right'nowrap>Nome do Posto</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input class='Caixa' type='text' name='posto_nome' size='30' value='$posto_nome'>&nbsp;<img src='imagens_admin/btn_lupa.gif' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')\" style='cursor:pointer;'></A>";
			$resposta  .= "</td>";
			$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
                $resposta  .= "<td align='right'nowrap >Nome</td>";
                $resposta  .= "<td align='left' colspan='3'>";
                $resposta  .= "<select name='tecnico_nome' id='tecnico_nome' class='Caixa' >";
                $resposta  .= "</select>";
                $resposta  .= "</td>";
                $resposta  .= "<td width='10'>&nbsp;</td>";
            $resposta  .= "</tr>";
		}

			if ($adicional)
			{
				$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "<td align='right' nowrap valign='top'>$adicional</td>";
				$resposta  .= "<td align='left'>";
				$resposta  .= "<input type='text' name='observacao' id='observacao' size='80' maxlength='500' class='Caixa' value=''>";
				$resposta  .= "</td>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "</tr>";
			}
			if ($login_fabrica == 20) {
/*				$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "<td align='right' nowrap valign='top'>Promotor</td>";
				$resposta  .= "<td align='left'>";
				$resposta  .= "<input type='text' name='promotor' id='promotor' size='40' maxlength='80' class='Caixa' value=''>";
				$resposta  .= "</td>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "</tr>";
*/
				$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "<td align='right' nowrap valign='top'>Promotor </td>";
				$resposta  .= "<td align='left'>";
				$resposta  .= "<select name='promotor_treinamento' id='promotor_treinamento' class='Caixa'>";
				$resposta  .= "<option value=''></option>";
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
				$resposta  .= "";
				$resposta  .= "</select>";
				$resposta  .= "</td>";
				$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "</tr>";
			}
			if ($login_fabrica == 20){
				$resposta  .="<input type='hidden' name='hotel' id='hotel' value='f'>";
			}else{
				if (!in_array($login_fabrica, array(117))) {
					$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
					$resposta  .= "<td width='10'>&nbsp;</td>";

					$resposta  .= "<td align='right' nowrap valign='top'>Agendar HOTEL?</td>";
					$resposta  .= "<td align='left'>";
					$resposta  .= "<input type='checkbox' name='hotel' id='hotel'  class='Caixa' value='t'>";
					$resposta  .= "</td>";

					$resposta  .= "<td width='10'>&nbsp;</td>";
					$resposta  .= "</tr>";
				}
			}

			if ($treinamento_prazo_inscricao and $expirou_prazo) { // tem prazo e já superou o prazo
				$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
					$resposta  .= "<td width='10'>&nbsp;</td>";
					$resposta  .= "<td align='right' nowrap valign='top'>Cadastrar fora de prazo?</td>";
					$resposta  .= "<td align='left' valign='middle'>";
					$resposta  .= "<input type='checkbox' name='confirma_cadastro_fora_de_prazo' id='fora_de_prazo' class='Caixa' value='t'>";
					$resposta  .= "<span style='width: 500px'>O prazo máximo para a inscrição neste treinamento foi dia $prazo_inscricao.</span>";
					$resposta  .= "</td>";
					$resposta  .= "<td width='10'>&nbsp;</td>";
				$resposta  .= "</tr>";
			}

			$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'>Email do Posto</td>";
			$resposta  .= "<td align='left'>";
			$resposta  .= "<input type='text' name='posto_email' ='posto_email' size='50' maxlength='50' class='Caixa' value='$posto_email'> * <font size='1'><b>Este email é o email do POSTO AUTORIZADO</b></font>";
			$resposta  .= "</td>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr class='Conteudo' bgcolor='#D9E2EF'>";
			$resposta  .= "<td width='10'>&nbsp;</td>";
			$resposta  .= "<td align='right' nowrap valign='top'>Política de Treinamento</td>";
			$resposta  .= "<td align='left' colspan='3'>";
			$resposta  .= "<TEXTAREA name='compromisso' id='compromisso' ROWS='7' COLS='90' class='Caixa2' READONLY>$termo_compromisso</TEXTAREA>";
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
			$resposta  .= "<center><a href='javascript:mostrar_treinamento(\"dados\");'><img src='imagens/btn_voltarnovo.gif' /></a></center>";
		}
		$resposta  .= "</td>";
		$resposta  .= "</tr>";
		$resposta  .= "</table>";
		$resposta  .= "<script language='javascript'>mostrar_tecnico('$treinamento');</script>";
		$resposta  .= "</FORM>";
	}
	echo "ok|$resposta";
	exit;
}
$layout_menu = "tecnica";
$title = "TREINAMENTO";
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
	border-right: #6699cc 1px solid;
	border-top: #6699cc 1px solid;
	font: 8pt arial ;
	border-left: #6699cc 1px solid;
	border-bottom: #6699cc 1px solid;
	background-color: #ffffff;
}
.Caixa2{
	border-right: #6699cc 1px solid;
	border-top: #6699cc 1px solid;
	font: 7pt arial ;
	border-left: #6699cc 1px solid;
	border-bottom: #6699cc 1px solid;
	background-color: #ffffff;
}
.Botao1{
	border-right:  #6699cc 1px solid;
	border-top:    #6699cc 1px solid;
	border-left:   #6699cc 1px solid;
	border-bottom: #6699cc 1px solid;
	font:             10pt arial ;
	font-weight:      bold;
	color:            #009900;
	background-color: #eeeeee;
}
.Botao2{
	border-right:  #6699cc 1px solid;
	border-top:    #6699cc 1px solid;
	border-left:   #6699cc 1px solid;
	border-bottom: #6699cc 1px solid;
	font:             10pt arial;
	font-weight:      bold;
	color:            #990000;
	background-color: #eeeeee;
}
.Erro{
	border-right: #990000 1px solid;
	border-top: #990000 1px solid;
	font: 10pt arial ;
	color: #ffffff;
	border-left: #990000 1px solid;
	border-bottom: #990000 1px solid;
	background-color: #ff0000;
}
caption.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
	height: 2em;
	line-height: 2em;
	border-width: 0;
}
.formulario td span {
	display: inline-block;
	text-overflow: ellipsis;
	width: 160px;
	overflow: hidden;
	white-space: nowrap;
}
.formulario td span:hover{
	white-space: normal;
}
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
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.msg_sucesso{
	background-color: green;
	font: bold 16px "Arial";
	color: #FFFFFF;
	text-align:center;
}
</style>
<? include "javascript_calendario.php";?>

<!-- <script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script> -->
<script language='javascript' src='../ajax.js'></script>
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
	var acao='cadastrar';/* INICIO HD 3331 RAPHAEL/IGOR- SEGUNDO RAPHAEL, ERA SOH COPIAR O QUE ESTAVA NO ARQUIVO DO POSTO E COLAR AQUI */

        /*RETIRADO
        if (formulatio.elements[i].type=='text' || formulatio.elements[i].type=='select-one' || formulatio.elements[i].type=='textarea' || formulatio.elements[i].type=='hidden' || formulatio.elements[i].type=='checkbox'){
            url = url+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);

        }*/

	url = "treinamento_agenda.php?ajax=sim&acao="+acao;
	console.log(formulatio);
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
	var curDateTime = new Date();

	$.ajax({
		type: 'GET',
		url: 'treinamento_agenda.php',
		data: "ajax=sim&acao="+acao+"&data="+curDateTime,
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
					$("#tecnico_data_nascimento").maskedinput("99/99/9999");
					//$("#tecnico_fone").maskedinput("(99)99999-9999");
					$("#tecnico_fone").maskedinput("(99) 9999-9999?9");
					$("#tecnico_cpf").maskedinput("999-999-999-99");

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


<? include "javascript_pesquisas.php";
	include "javascript_calendario.php";
?>

<? include "javascript_pesquisas.php" ?>
<?
echo "<div id='dados'></div>";
echo "<script language='javascript'>mostrar_treinamento('dados');</script>";
include "rodape.php"
?>
