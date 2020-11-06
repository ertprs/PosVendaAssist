<?
//conforme chamado 474 (fabricio -  britania) na hr em que eram buscada as informacoes da OS, estava buscando na forma antiga, ou seja, estava buscando informacoes do cliente na tbl_cliente, com o novo metodo as info do consumidor sao gravados direto na tbl_os, com isso hr que estava buscando info do cliente estava buscando no local errado -  Takashi 31/09/2006
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';

if($ajax=='tipo_atendimento'){
    $sql = "SELECT tipo_atendimento,km_google
            FROM tbl_tipo_atendimento
            WHERE tipo_atendimento = $id
            AND   fabrica          = $login_fabrica";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){

        $km_google = pg_fetch_result($res,0,km_google);
        if($km_google == 't'){
            echo "ok|sim";
        }else{
            echo "no|não";
        }
    exit;
    }
}

include 'funcoes.php';
include "../gMapsKeys.inc";

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);


if (strlen($_POST['os']) > 0){
	$os = trim($_POST['os']);
}

if (strlen($_GET['os']) > 0){
	$os = trim($_GET['os']);
}

if (strlen($_POST['sua_os']) > 0){
	$sua_os = trim($_POST['sua_os']);
}

if (strlen($_GET['sua_os']) > 0){
	$sua_os = trim($_GET['sua_os']);
}


/*======= Troca em Garantia =========*/

$btn_troca = strtolower ($_POST['btn_troca']);

if ($btn_troca == "trocar") {
	$msg_erro = "";
	
	$sql = "BEGIN TRANSACTION";
	$res = pg_exec($con,$sql);

	$os                      = $_POST["os"];
	$troca_garantia_mao_obra = $_POST["troca_garantia_mao_obra"];
	$troca_garantia_mao_obra = str_replace(",",".",$troca_garantia_mao_obra);

	$troca_via_distribuidor = $_POST['troca_via_distribuidor'];
	if (strlen($troca_via_distribuidor) == 0) $troca_via_distribuidor = "f";

	$sql = "SELECT produto FROM tbl_os WHERE os = $os;";
	$res = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	$produto = pg_result($res,0,0);

// adicionado por Fabio - Altera o status para liberado da Assis. Tec. da Fábrica caso tenha intervencao.
	$sql = "SELECT status_os FROM tbl_os_status WHERE os=$os ORDER BY data DESC LIMIT 1";
	$res = pg_exec($con,$sql);
	$qtdex = pg_numrows($res);
	if ($qtdex>0){
		$statuss=pg_result($res,0,status_os);
		if ($statuss=='62' || $statuss=='65'){
			$sql = "INSERT INTO tbl_os_status
					(os,status_os,data,observacao) 
					VALUES ($os,64,current_timestamp,'Troca do Produto')";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql =  "UPDATE tbl_os_item
					SET servico_realizado=96
					WHERE os_item IN (
					SELECT os_item
					FROM tbl_os
					JOIN tbl_os_produto USING(os)
					JOIN tbl_os_item USING(os_produto)
					JOIN tbl_peca USING(peca)
					WHERE tbl_os.os=$os
					AND tbl_os.fabrica=$login_fabrica
					AND tbl_os_item.servico_realizado=20
					AND tbl_peca.retorna_conserto IS TRUE)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		
			$sql = "UPDATE tbl_os 
					SET solucao_os = 85,
						defeito_constatado=10224
					WHERE os=$os
					AND fabrica=$login_fabrica
					AND solucao_os IS NULL
					AND defeito_constatado IS NULL";
			$res = pg_exec($con,$sql); // trocar a solucao de 511 e 302 para XXX e XXX (Britania)
			$msg_erro .= pg_errormessage($con);
		}
	}

	//colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca
	$orientacao_sac = trim ($_POST['orient_sac']);
	$orientacao_sac = htmlentities ($orientacao_sac,ENT_QUOTES);
	$orientacao_sac = nl2br ($orientacao_sac);
	if (strlen ($orientacao_sac) == 0)
		$orientacao_sac  = "null";
	else
		$orientacao_sac  = "'" . $orientacao_sac . "'" ;
		
	$sql = "UPDATE  tbl_os_extra SET orientacao_sac = trim($orientacao_sac)
			WHERE tbl_os_extra.os = $os;";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);



//echo "$troca_garantia_mao_obra";
	$troca_garantia_produto = $_POST["troca_garantia_produto"];
	//resarcimento?? ressarcimento!!
	if ($troca_garantia_produto == "-1") {//resarcimento financeiro
		$sql = "UPDATE tbl_os SET 
				troca_garantia          = 't', 
				ressarcimento           = 't', 
				troca_garantia_admin    = $login_admin,
				data_fechamento         = CURRENT_DATE
				WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}else{
		$sql = "SELECT * FROM tbl_produto WHERE referencia = '$troca_garantia_produto';";
		$resProd = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (pg_numrows($resProd) == 0) {
			$msg_erro .= "Produto informado não encontrado";
		}
		$troca_produto   = pg_result ($resProd,0,produto);
		$troca_ipi       = pg_result ($resProd,0,ipi);
		$troca_descricao = pg_result ($resProd,0,descricao);

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT * FROM tbl_peca WHERE referencia = '$troca_garantia_produto';";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			
			if (pg_numrows($res) == 0) {
				if (strlen ($troca_ipi) == 0) $troca_ipi = 10;
				
				$sql =	"SELECT peca
						FROM tbl_peca
						WHERE fabrica         = $login_fabrica
						AND   referencia      = '$troca_garantia_produto'
						LIMIT 1;";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
				if (pg_numrows($res) > 0) {
					$peca = pg_result($res,0,0);
				}else{
					$sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, ipi, origem, produto_acabado) VALUES ($login_fabrica, '$troca_garantia_produto', '$troca_descricao' , $troca_ipi , 'NAC','t')" ;
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					
					$sql = "SELECT CURRVAL ('seq_peca')";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$peca = pg_result($res,0,0);
				}
				$sql = "INSERT INTO tbl_lista_basica (fabrica, produto,peca,qtde) VALUES ($login_fabrica, $produto, $peca, 1);" ;
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}else{
				$peca = pg_result($res,0,peca);
			}
		
			$sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $os";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			
			if (pg_numrows($res) == 0) {
				$sql = "INSERT INTO tbl_os_produto (os, produto) VALUES ($os, $produto);";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
				$sql = "SELECT CURRVAL ('seq_os_produto')";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
				$os_produto = pg_result($res,0,0);
			}else{
				$os_produto = pg_result($res,0,0);
			}
		
			$sql = "SELECT *
					FROM   tbl_os_item
					JOIN   tbl_servico_realizado USING (servico_realizado)
					JOIN   tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					WHERE  tbl_os_produto.os = $os
					AND    tbl_servico_realizado.troca_de_peca
					AND    tbl_os_item.pedido NOTNULL " ;
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if (pg_numrows($res) > 0) {
				$os_item = pg_result($res,0,os_item);
				$qtde    = pg_result($res,0,qtde);
				$pedido  = pg_result($res,0,pedido);
		
				$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde_cancelada + $qtde
						WHERE pedido = $pedido
						AND   pedido = tbl_pedido.pedido
						AND   tbl_pedido.exportado IS NULL
						AND   peca   = $peca;";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		
			$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_produto AND fabrica = $login_fabrica" ;
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if(pg_numrows($res) > 0){
				$servico_realizado = pg_result($res,0,0);
			}
			if(strlen($servico_realizado)==0) $msg_erro = "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!";

			if(strlen($msg_erro)==0){
				$sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin) VALUES ($os_produto, $peca, 1,$servico_realizado, $login_admin)";

				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_os SET 
						troca_garantia          = 't', 
						troca_garantia_admin    = $login_admin,
						data_fechamento         = CURRENT_DATE
						WHERE os = $os AND fabrica = $login_fabrica";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
				if(strlen($troca_garantia_mao_obra) > 0 ){
					$sql = "UPDATE tbl_os SET mao_de_obra = $troca_garantia_mao_obra WHERE os = $os AND fabrica = $login_fabrica";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				$sql = "SELECT * FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NULL";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
		
		if (strlen ($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		if($login_fabrica==24){
		$sql_email = "SELECT email, nome from tbl_posto where posto=$posto";	
		$res_email = pg_exec($con,$sql_mail);
		if(pg_numrows($res_email)>0){
			$email_posto = trim(pg_result($res_email,0,email));
			$xposto_nome = pg_result($res_email,0,nome);
			if(strlen($email_posto)>0){
				$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>"; 
				$destinatario = $email_posto; 
				$assunto      = "O fabricante Suggar abriu uma ordem de serviço para seu posto autorizado"; 
				$mensagem     = "Caro posto autorizado $xposto_nome,<BR>
				O fabricante Suggar abriu a ordem de serviço número $os para seu posto autorizado, por favor verificar.<BR><BR>
				Atenciosamente<BR> Dep. Assistência Técnica Suggar"; 
				$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n"; 
				mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
					
			}
		}


				
		
		}


		header("Location: $PHP_SELF?os=$os");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}



/*======= <PHP> FUNÇOES DOS BOTÕES DE AÇÃO =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == 'altera_km') {

	$os = $_POST['os'];

	$sql = "UPDATE tbl_os SET tipo_atendimento = " . $_POST['tipo_atendimento'] . " WHERE os = $os";
	$res = pg_query($con,$sql);

	$sql = "SELECT tipo_atendimento FROM tbl_os WHERE os = $os";
	$res = pg_query($con,$sql);
	
	$tipo_atendimento = pg_result($res,0,0);
	
	if(empty($tipo_atendimento))
		$tipo_atendimento = 0;

	//--==== OS de Instalação ============================================
    $automatico = "t";
    $obs_km = " OS Aguardando aprovação de Kilometragem. ";
    $km_auditoria = "FALSE";
    $sql = "SELECT tipo_atendimento,km_google
            FROM tbl_tipo_atendimento
            WHERE tipo_atendimento = $tipo_atendimento";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $km_google = pg_fetch_result($res,0,km_google);

        $obs_km="";
        if ($km_google == 't') {
            $qtde_km  = str_replace (",",".",$_POST['distancia_km']);
            $xqtde_km = (!empty($qtde_km)) ?$qtde_km : "0" ;
            $qtde_km = number_format($qtde_km,3,'.','');
            $qtde_km2 = number_format($_POST['distancia_km_conferencia'],3,'.','') ;

        
        	if ($qtde_km >= '100'){
        		$km_auditoria = "TRUE";
        		$obs_km= ($xqtde_km <>'0') ? " Cálculo Automático. " : null;
        	}


            if ($distancia_km_maps <> 'maps' AND ($qtde_km <> $qtde_km2 AND $qtde_km > 0) OR ($qtde_maior_100 == true) ) {

				$xqtde_km  = str_replace(".", ",", $qtde_km);
				$xqtde_km2 = str_replace(".", ",", $qtde_km2);
				// HD 699862
				$obs_km = " Alteração manual de km de $xqtde_km2 km para $xqtde_km km. ";
				
            }

			$sql = "UPDATE tbl_os SET qtde_km = ".$qtde_km."
			        WHERE fabrica = $login_fabrica AND os = $os;";

			$res = pg_query($con,$sql);

        }
        

    }

    if (strlen($qtde_km) == 0) {
        $qtde_km = "NULL";
        $km_auditoria = "FALSE";
    }
    
    if ($km_auditoria == "TRUE" && $km_google == 't') {
        $sql = "SELECT status_os
                FROM tbl_os_status
                WHERE os = $os
                AND status_os IN (98,99,100)
                ORDER BY data DESC
                LIMIT 1";
 
        $res = @pg_query ($con,$sql);
        if (pg_num_rows($res) > 0){
            $status_os  = pg_fetch_result ($res,0,status_os);
        }
        if (pg_num_rows($res) == 0 OR $status_os <> "98") {
            $sql = "INSERT INTO tbl_os_status (os,status_os,observacao,automatico) VALUES ($os,98,'$obs_km','$automatico')";
            $res = pg_query ($con,$sql);
        }
    }

}

if ($btn_acao == "continuar") {
	$msg_erro = "";
	
	if (strlen (trim ($sua_os)) == 0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	}else{
		$sua_os = "'" . $sua_os . "'" ;
	}

	// explode a sua_os
	$fOsRevenda = 0;
	$expSua_os = explode("-",$sua_os);
	$sql = "SELECT sua_os
			FROM   tbl_os_revenda
			WHERE  sua_os = $expSua_os[0]
			AND    fabrica      = $login_fabrica";

	$res = @pg_exec ($con,$sql);

	if (@pg_numrows ($res) != 0) {
		$fOsRevenda = 1;
	}
		$data_nf =trim($_POST['data_nf']);

	if (strlen($msg_erro) == 0){

		#------------ Atualiza Dados do Consumidor ----------
		$cidade = strtoupper(trim($_POST['consumidor_cidade']));
		$estado = strtoupper(trim($_POST['consumidor_estado']));

		if (strtoupper(trim($_POST['consumidor_revenda'])) == 'C') {
			if (strlen($estado) == 0) $msg_erro .= " Digite o estado do consumidor. <br>";
			if (strlen($cidade) == 0) $msg_erro .= " Digite a cidade do consumidor. <br>";
		}

		$nome	= trim ($_POST['consumidor_nome']) ;

		$cpf    = trim ($_POST['consumidor_cpf']) ;
		$cpf    = str_replace (".","",$cpf);
		$cpf    = str_replace ("-","",$cpf);
		$cpf    = str_replace ("/","",$cpf);
		$cpf    = str_replace (",","",$cpf);
		$cpf    = str_replace (" ","",$cpf);

		if (strlen($cpf) == 0) $xcpf = "null";
		else                   $xcpf = $cpf;

		if ($xcpf <> "null" and strlen($xcpf) <> 11 and strlen ($xcpf) <> 14) {
			$msg_erro = 'Tamanho do CPF/CNPJ do cliente inválido';
		}

		if (strlen($xcpf) > 0 and $xcpf <> "null") $xcpf = "'" . $xcpf . "'";

		$rg     = trim ($_POST['consumidor_rg']) ;

		if (strlen($rg) == 0) $rg = "null";
		else                  $rg = "'" . $rg . "'";

		$fone		= trim ($_POST['consumidor_fone']) ;
		$endereco	= trim ($_POST['consumidor_endereco']) ;
		if ($login_fabrica == 2 || $login_fabrica == 1) {
			if (strlen($endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br>";
		}
		$numero      = trim ($_POST['consumidor_numero']);
		$complemento = trim ($_POST['consumidor_complemento']) ;
		$bairro      = trim ($_POST['consumidor_bairro']) ;
		$cep         = trim ($_POST['consumidor_cep']) ;

		if ($login_fabrica == 1) {
			if (strlen($numero) == 0) $msg_erro .= " Digite o número do consumidor. <br>";
			if (strlen($bairro) == 0) $msg_erro .= " Digite o bairro do consumidor. <br>";
		}

		if (strlen($complemento) == 0) $complemento = "null";
		else                           $complemento = "'" . $complemento . "'";

//		if (strlen($cep) == 0) $cep = "null";
//		else                   $cep = "'" . $cep . "'";

		// verifica se está setado

		if($_POST['consumidor_contrato'] == 't' ) $contrato	= 't';
		else                                      $contrato	= 'f';

		$cep = str_replace (".","",$cep);
		$cep = str_replace ("-","",$cep);
		$cep = str_replace ("/","",$cep);
		$cep = str_replace (",","",$cep);
		$cep = str_replace (" ","",$cep);
		$cep = substr ($cep,0,8);

		if (strlen($cep) == 0) $cep = "null";
		else                   $cep = "'" . $cep . "'";

$monta_sql .= "2: $sql<br>$msg_erro<br><br>";

		if ($login_fabrica == 1 AND strlen ($cpf) == 0) {
			$cpf = 'null';
		}
	}


	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen (trim ($tipo_atendimento)) == 0) $tipo_atendimento = 'null';


	$posto_codigo = trim ($_POST['posto_codigo']);
	$posto_codigo = str_replace ("-","",$posto_codigo);
	$posto_codigo = str_replace (".","",$posto_codigo);
	$posto_codigo = str_replace ("/","",$posto_codigo);
	$posto_codigo = substr($posto_codigo,0,14);

	$res = pg_exec ($con,"SELECT * FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo'");
	$posto = @pg_result ($res,0,0);

	$data_abertura = trim($_POST['data_abertura']);
	$data_abertura = fnc_formata_data_pg($data_abertura);
	
	$consumidor_nome   = str_replace ("'","",$_POST['consumidor_nome']);
	$consumidor_cidade = str_replace ("'","",$_POST['consumidor_cidade']);
	$consumidor_estado = $_POST['consumidor_estado'];
	$consumidor_fone   = $_POST['consumidor_fone'];
	
	$consumidor_cpf = trim($_POST['consumidor_cpf']);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	$consumidor_cpf = trim (substr ($consumidor_cpf,0,14));
	
	if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
	else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";
	
	$consumidor_fone = strtoupper (trim ($_POST['consumidor_fone']));
	
	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace ("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace (".","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("/","",$revenda_cnpj);
	$revenda_cnpj = substr ($revenda_cnpj,0,14);
	
	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";
	
	$revenda_nome = str_replace ("'","",$_POST['revenda_nome']);
	$nota_fiscal  = $_POST['nota_fiscal'];

	if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";

	$data_nf      = trim($_POST['data_nf']);
	$data_nf      = fnc_formata_data_pg($data_nf);

	if ($data_nf == 'null' AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.";

	$produto_referencia = strtoupper (trim ($_POST['produto_referencia']));
//BOSCH -  regra: caso ele escolho um dois tipos de atendimento abaixo o produto vai ser  sempre os designados
if($login_fabrica ==20){
	if($tipo_atendimento==11){    //garantia de peças
		$produto_referencia='0000002';
	}
	if($tipo_atendimento==12){    //garantia de acessórios
		$produto_referencia='0000001';
	}
}
	$produto_referencia = str_replace ("-","",$produto_referencia);
	$produto_referencia = str_replace (" ","",$produto_referencia);
	$produto_referencia = str_replace ("/","",$produto_referencia);
	$produto_referencia = str_replace (".","",$produto_referencia);

	$produto_serie           = strtoupper (trim ($_POST['produto_serie']));
	$admin_paga_mao_de_obra = $_POST['admin_paga_mao_de_obra'];
	if ($admin_paga_mao_de_obra == 'admin_paga_mao_de_obra') 
		$admin_paga_mao_de_obra = 't';
	else
		$admin_paga_mao_de_obra = 't';
	$qtde_produtos           = strtoupper (trim ($_POST['qtde_produtos']));

	$aparencia_produto = strtoupper (trim ($_POST['aparencia_produto']));
	$acessorios        = strtoupper (trim ($_POST['acessorios']));

	$consumidor_revenda= str_replace ("'","",$_POST['consumidor_revenda']);

	$orientacao_sac    = trim ($_POST['orientacao_sac']);
	$orientacao_sac    = htmlentities ($orientacao_sac,ENT_QUOTES);
	$orientacao_sac    = nl2br ($orientacao_sac);

#	if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= "Tamanho do CPF/CNPJ do cliente inválido.";

#	if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= " Tamanho do CPF/CNPJ do cliente inválido.";

	if (strlen ($revenda_cnpj)   <> 0 and strlen ($revenda_cnpj)   <> 14) $msg_erro .= "Tamanho do CNPJ da revenda inválido.";


	if (strlen ($produto_referencia) == 0) $msg_erro .= " Digite o produto.";

	$xquem_abriu_chamado = trim($_POST['quem_abriu_chamado']);
	if (strlen($xquem_abriu_chamado) == 0) $xquem_abriu_chamado = 'null';
	else $xquem_abriu_chamado = "'".$xquem_abriu_chamado."'";

	$xobs = trim($_POST['obs']);
	if (strlen($xobs) == 0) $xobs = 'null';
	else                    $xobs = "'".$xobs.".";

	if ($login_fabrica == 7) $data_nf = $data_abertura;

	// Campos da Black & Decker
	if ($login_fabrica == 1) {
		if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $codigo_fabricacao = 'null';
		else $codigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

		if (strlen($_POST['satisfacao']) == 0) $satisfacao = "f";
		else                                   $satisfacao = "t";

		if (strlen($_POST['laudo_tecnico']) == 0) $laudo_tecnico = 'null';
		else                                      $laudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

		if ($satisfacao == 't' AND strlen($_POST['laudo_tecnico']) == 0) {
			$msg_erro .= " Digite o Laudo Técnico.";
		}
	}


	
	if (strlen (trim ($data_nf)) <> 12) {
		$data_nf = "null";
		$msg_erro .= " Digite a data de compra.";
	}

	if (strlen ($data_abertura) <> 12) {
		$msg_erro .= " Digite a data de abertura da OS.";
	}else{
		$cdata_abertura = str_replace("'","",$data_abertura);
	}
	
	if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";

	$consumidor_revenda = "C";
	// se é uma OS de revenda
	if ($fOsRevenda == 1){

		if (strlen ($nota_fiscal) == 0)       $nota_fiscal = "null";
		else                                  $nota_fiscal = "'" . $nota_fiscal . "'" ;

		if (strlen ($aparencia_produto) == 0) $aparencia_produto  = "null";
		else                                  $aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen ($acessorios) == 0)        $acessorios = "null";
		else                                  $acessorios = "'" . $acessorios . "'" ;

		if (strlen($consumidor_revenda) == 0) $msg_erro .= " Selecione consumidor ou revenda.";
		else                                  $xconsumidor_revenda = "'".$consumidor_revenda."'";

		if (strlen ($orientacao_sac) == 0)    $orientacao_sac  = "null";
		else                                  $orientacao_sac  = "'" . $orientacao_sac . "'" ;

	}else{

		if (strlen ($nota_fiscal) == 0)        $msg_erro = "Entre com o número da Nota Fiscal";
		else                                   $nota_fiscal = "'" . $nota_fiscal . "'" ;

		if (strlen ($aparencia_produto) == 0)  $aparencia_produto  = "null";
		else                                   $aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen ($acessorios) == 0)         $acessorios = "null";
		else                                   $acessorios = "'" . $acessorios . "'" ;

		if (strlen($consumidor_revenda) == 0)  $msg_erro .= " Selecione consumidor ou revenda.";
		else                                   $xconsumidor_revenda = "'".$consumidor_revenda."'";

		if (strlen ($orientacao_sac) == 0)     $orientacao_sac  = "null";
		else                                   $orientacao_sac  = "'" . $orientacao_sac . "'" ;

	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$produto = 0;
	$sql = "SELECT tbl_produto.produto
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER (tbl_produto.referencia_pesquisa) = UPPER ('$produto_referencia')
			AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		$msg_erro = "Produto $produto_referencia não cadastrado";
	}
	$produto = @pg_result ($res,0,0);

	if ($xtroca_faturada <> "'t'") { // verifica troca faturada para a Black

	// se não é uma OS de revenda, entra
	if ($fOsRevenda == 0){
		$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
		
		$res = @pg_exec ($con,$sql);
		
		if (@pg_numrows ($res) == 0) {
			$msg_erro = "Produto $produto_referencia sem garantia";
		}
		$garantia = trim(@pg_result($res,0,garantia));
		
		$sql = "SELECT ($data_nf::date + (($garantia || ' months')::interval))::date;";
		$res = @pg_exec ($con,$sql);
		
		if (@pg_numrows ($res) > 0) {
			$data_final_garantia = trim(pg_result($res,0,0));
		}
		
		if ($login_fabrica <> 3 and $login_fabrica <> 11 AND $login_fabrica <> 15 AND $login_fabrica <> 24) {
			if ($data_final_garantia < $cdata_abertura) {
				$msg_erro = "[ $data_nf ] - [ $data_final_garantia ] = [ $cdata_abertura ] Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
			}
		}
	}

	}
	if ($login_fabrica == 1) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.linha   = 198
				AND   tbl_produto.produto = $produto;";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			$xtipo_os_compressor = "10";
		}else{
			$xtipo_os_compressor = 'null';
		}
	}else{
		$xtipo_os_compressor = 'null';
	}
	$os_reincidente = "'f'";
	
	##### Verificação se o nº de série é reincidente para a Tectoy #####
	if ($login_fabrica == 6) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0);
		
		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0);
		
		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os             ,
					tbl_os.sua_os         ,
					tbl_os.data_digitacao ,
					tbl_os_extra.extrato
					FROM    tbl_os
					JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os.posto   = $posto ";
			if (strlen($os) > 0) $sql .= "AND     tbl_os.os     not in ($os) ";
			$sql .= "AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$xxxos      = trim(pg_result($res,0,os));
				$xxxsua_os  = trim(pg_result($res,0,sua_os));
				$xxxextrato = trim(pg_result($res,0,extrato));
				
				if (strlen($xxxextrato) == 0) {
					$msg_erro .= "Nº de Série $produto_serie digitado é reincidente.<br>
					Favor reabrir a ordem de serviço $xxxsua_os e acrescentar itens.";
				}else{
					$os_reincidente = "'t'";
				}
			}
		}
	}

	##### Verificação se o nº de série é reincidente para a Britânia #####
	if ($login_fabrica == 3 and 1 == 2) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0);
		
		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0);
		
		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao
					FROM    tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				$msg_erro .= "Nº de Série $produto_serie digitado é reincidente. Favor verificar.<br>
				Em caso de dúvida, entre em contato com a Fábrica.";
			}
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		if (strlen ($os) == 0) {
		/*================ INSERE NOVA OS =========================*/
			$sql = "INSERT INTO tbl_os (
						tipo_atendimento   ,
						posto              ,
						admin              ,
						fabrica            ,
						sua_os             ,
						data_abertura      ,
						cliente            ,
						revenda            ,
						consumidor_nome    ,
						consumidor_cpf     ,
						consumidor_cidade  ,
						consumidor_estado  ,
						consumidor_fone    ,
						revenda_cnpj       ,
						revenda_nome       ,
						nota_fiscal        ,
						data_nf            ,
						produto            ,
						serie              ,
						qtde_produtos      ,
						aparencia_produto  ,
						acessorios         ,
						obs                ,
						quem_abriu_chamado ,
						consumidor_revenda ,
						troca_faturada     ,
						os_reincidente ";
			
			if ($login_fabrica == 1) {
				$sql .=	",codigo_fabricacao ,
						satisfacao          ,
						tipo_os             ,
						laudo_tecnico       ";
			}
			
			$sql .= ") VALUES (
						$tipo_atendimento                                               ,
						$posto                                                          ,
						$login_admin                                                    ,
						$login_fabrica                                                  ,
						trim ($sua_os)                                                  ,
						$data_abertura                                                  ,
						(SELECT cliente FROM tbl_cliente WHERE cpf  = $xconsumidor_cpf) ,
						(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj)   ,
						trim ('$consumidor_nome')                                       ,
						trim ('$consumidor_cpf')                                        ,
						trim ('$consumidor_cidade')                                     ,
						trim ('$consumidor_estado')                                     ,
						trim ('$consumidor_fone')                                       ,
						trim ('$revenda_cnpj')                                          ,
						trim ('$revenda_nome')                                          ,
						trim ($nota_fiscal)                                             ,
						$data_nf                                                        ,
						$produto                                                        ,
						'$produto_serie'                                                ,
						$qtde_produtos                                                  ,
						trim ($aparencia_produto)                                       ,
						trim ($acessorios)                                              ,
						$xobs                                                           ,
						$xquem_abriu_chamado                                            ,
						'$consumidor_revenda'                                           ,
						$xtroca_faturada                                                ,
						$os_reincidente ";

			if ($login_fabrica == 1) {
				$sql .= ", $codigo_fabricacao ,
						'$satisfacao'         ,
						$xtipo_os_compressor  ,
						$laudo_tecnico        ";
			}

			$sql .= ");";
//if ($ip == "201.0.9.216") { echo nl2br($sql); exit; }
		}else{
			/*================ ALTERA OS =========================*/
			$sql = "UPDATE tbl_os SET
						tipo_atendimento   = $tipo_atendimento           ,
						posto              = $posto                      ,";
			if($login_fabrica<>6 and $login_fabrica<>11 and $login_fabrica<>15){//TAKASHI 01-11 - Angelica informou que OS aberta pelo posto paga um valor, os pelo admin outro valor. Qdo o admin atualiza qualquer informação grava o admin e na hora de calcular calcula como se fosse uma os de admin 
				$sql .=" admin              = $login_admin                ,";
			}
				$sql .=" fabrica            = $login_fabrica              ,
						sua_os             = trim($sua_os)               ,
						data_abertura      = $data_abertura              ,
						consumidor_nome    = trim('$consumidor_nome')    ,
						consumidor_cpf     = trim('$consumidor_cpf')     ,
						consumidor_fone    = trim('$consumidor_fone')    ,
						consumidor_estado  = trim('$consumidor_estado')  ,
						consumidor_cidade  = trim ('$consumidor_cidade')   ,
						revenda_nome       = trim('$revenda_nome')       ,
						nota_fiscal        = trim($nota_fiscal)          ,
						data_nf            = $data_nf                    ,
						produto            = $produto                    ,
						serie              = '$produto_serie'            ,
						qtde_produtos      = $qtde_produtos              ,
						aparencia_produto  = trim($aparencia_produto)    ,
						acessorios         = trim($acessorios)           ,
						quem_abriu_chamado = $xquem_abriu_chamado        ,
						obs                = $xobs                       ,
						troca_faturada     = $xtroca_faturada            ,
						os_reincidente     = $os_reincidente ";
			
			if ($login_fabrica == 1) {
				$sql .=	", codigo_fabricacao = $codigo_fabricacao ,
						satisfacao           = '$satisfacao'      ,
						tipo_os              = $xtipo_os_compressor,
						laudo_tecnico        = $laudo_tecnico     ";
			}

			$sql .= "WHERE os      = $os
					AND   fabrica = $login_fabrica";
		}
// $msg_debug = "<br>".$sql."<br>";
 //echo nl2br($sql); exit; 

		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if (strlen ($msg_erro) == 0) {
			if (strlen($os) == 0) {

				$res = pg_exec ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_result ($res,0,0);
				
				$sql = "UPDATE tbl_os SET consumidor_nome = tbl_cliente.nome WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.cliente = tbl_cliente.cliente";
				$res = @pg_exec ($con,$sql);
				
				$sql = "UPDATE tbl_os SET consumidor_cidade = tbl_cidade.nome , consumidor_estado = tbl_cidade.estado WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.consumidor_cidade IS NULL AND tbl_os.cliente = tbl_cliente.cliente AND tbl_cliente.cidade = tbl_cidade.cidade";
				$res = pg_exec ($con,$sql);

				if (strlen ($consumidor_endereco)    == 0) { $consumidor_endereco    = "null" ; }else{ $consumidor_endereco    = "'" . $consumidor_endereco    . "'" ; };
				if (strlen ($consumidor_numero)      == 0) { $consumidor_numero      = "null" ; }else{ $consumidor_numero      = "'" . $consumidor_numero      . "'" ; };
				if (strlen ($consumidor_complemento) == 0) { $consumidor_complemento = "null" ; }else{ $consumidor_complemento = "'" . $consumidor_complemento . "'" ; };
				if (strlen ($consumidor_bairro)      == 0) { $consumidor_bairro      = "null" ; }else{ $consumidor_bairro      = "'" . $consumidor_bairro      . "'" ; };
				if (strlen ($consumidor_cep)         == 0) { $consumidor_cep         = "null" ; }else{ $consumidor_cep         = "'" . $consumidor_cep         . "'" ; };
				if (strlen ($consumidor_cidade)      == 0) { $consumidor_cidade      = "null" ; }else{ $consumidor_cidade      = "'" . $consumidor_cidade      . "'" ; };
				if (strlen ($consumidor_estado)      == 0) { $consumidor_estado      = "null" ; }else{ $consumidor_estado      = "'" . $consumidor_estado      . "'" ; };

				$sql = "UPDATE tbl_os SET 
							consumidor_endereco    = $consumidor_endereco       , 
							consumidor_numero      = $consumidor_numero         , 
							consumidor_complemento = $consumidor_complemento    , 
							consumidor_bairro      = $consumidor_bairro         , 
							consumidor_cep         = $consumidor_cep            , 
							consumidor_cidade      = $consumidor_cidade         , 
							consumidor_estado      = $consumidor_estado
						WHERE tbl_os.os = $os ";
//echo $sql;
				$res = pg_exec ($con,$sql);

			}

			$sql      = "SELECT fn_valida_os($os, $login_fabrica)";
			$res      = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			#--------- grava OS_EXTRA ------------------
			if (strlen ($msg_erro) == 0) {
				$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
				$visita_por_km				= trim ($_POST['visita_por_km']);
				$hora_tecnica				= str_replace (",",".",trim ($_POST['hora_tecnica']));
				$regulagem_peso_padrao		= str_replace (",",".",trim ($_POST['regulagem_peso_padrao']));
				$certificado_conformidade	= str_replace (",",".",trim ($_POST['certificado_conformidade']));
				$valor_diaria				= str_replace (",",".",trim ($_POST['valor_diaria']));
				
				if (strlen ($taxa_visita)				== 0) $taxa_visita					= '0';
				if (strlen ($visita_por_km)				== 0) $visita_por_km				= 'f';
				if (strlen ($hora_tecnica)				== 0) $hora_tecnica					= '0';
				if (strlen ($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
				if (strlen ($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
				if (strlen ($valor_diaria)				== 0) $valor_diaria					= '0';
				
				$sql = "UPDATE  tbl_os_extra SET
								orientacao_sac          = trim($orientacao_sac)      ,
								taxa_visita              = $taxa_visita              ,
								visita_por_km            = '$visita_por_km'          ,
								hora_tecnica             = $hora_tecnica             ,
								regulagem_peso_padrao    = $regulagem_peso_padrao    ,
								certificado_conformidade = $certificado_conformidade ,
								valor_diaria             = $valor_diaria             ,
								admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra' ";
				
				if ($os_reincidente == "'t'") {
					$sql .= ", os_reincidente = $xxxos ";
				}
				
				$sql .= "WHERE tbl_os_extra.os = $os";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				
				if (strlen ($msg_erro) == 0) {
					$res = pg_exec ($con,"COMMIT TRANSACTION");
					
					header ("Location: os_item.php?os=$os");
					exit;
				}
			}
		}
	}
	if (strlen ($msg_erro) > 0) {
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = "Data da compra maior que a data da abertura da Ordem de Serviço.";
	
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura\"") > 0)
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os) > 0){

		if ($login_fabrica == 1) {
			$sql =	"SELECT sua_os
					FROM tbl_os
					WHERE os = $os;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if (@pg_numrows($res) == 1) {
				$sua_os = @pg_result($res,0,0);
				$sua_os_explode = explode("-", $sua_os);
				$xsua_os = $sua_os_explode[0];
			}
		}

		if ($login_fabrica == 3){
			$sql = "UPDATE tbl_os SET excluida = 't' , admin_excluida = $login_admin WHERE os = $os AND fabrica = $login_fabrica";
			$res = @pg_exec ($con,$sql);

			#158147 Paulo/Waldir desmarcar se for reincidente
			$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
			$res = pg_exec($con, $sql);

		}else{
			$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
			$res = @pg_exec ($con,$sql);
		}
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 AND $login_fabrica == 1) {
			$sqlPosto =	"SELECT tbl_posto.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											   AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_posto_fabrica.codigo_posto = '".trim($_POST['posto_codigo'])."'
						AND   tbl_posto_fabrica.fabrica      = $login_fabrica;";
			$resPosto = @pg_exec($con,$sqlPosto);
			if (@pg_numrows($res) == 1) {
				$xposto = pg_result($resPosto,0,0);
			}

			$sql =	"SELECT tbl_os.sua_os
					FROM tbl_os
					WHERE sua_os ILIKE '$xsua_os-%'
					AND   posto   = $xposto
					AND   fabrica = $login_fabrica;";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (@pg_numrows($res) == 0) {
				$sql = "DELETE FROM tbl_os_revenda
						WHERE  tbl_os_revenda.sua_os  = '$xsua_os'
						AND    tbl_os_revenda.fabrica = $login_fabrica
						AND    tbl_os_revenda.posto   = $xposto";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}

		if (strlen ($msg_erro) == 0) {
			header("Location: os_parametros.php");
			exit;
		}
	}
}

/*================ LE OS DA BASE DE DADOS =========================*/

if (strlen ($os) > 0) {
	$sql = "SELECT	tbl_os.os                                           ,
			tbl_os.tipo_atendimento                                     ,
			tbl_os.posto                                                ,
			tbl_posto.nome                             AS posto_nome    ,
			tbl_os.sua_os                                               ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
			tbl_os.produto                                              ,
			tbl_produto.referencia                                      ,
			tbl_produto.descricao                                       ,
			tbl_os.serie                                                ,
			tbl_os.qtde_produtos                                        ,
			tbl_os.cliente                                              ,
			tbl_os.consumidor_nome                                      ,
			tbl_posto_fabrica.contato_endereco       AS contato_endereco,
			tbl_posto_fabrica.contato_numero           AS contato_numero,
			tbl_posto_fabrica.contato_bairro           AS contato_bairro,
			tbl_posto_fabrica.contato_cidade           AS contato_cidade,
			tbl_posto_fabrica.contato_estado           AS contato_estado,
			tbl_posto_fabrica.contato_cep                               ,
			tbl_os.consumidor_cpf                                       ,
			tbl_os.consumidor_fone                                      ,
			tbl_os.consumidor_cidade                                    ,
			tbl_os.consumidor_estado                                    ,
			tbl_os.consumidor_cep                                       ,
			tbl_os.consumidor_endereco                                  ,
			tbl_os.consumidor_numero                                    ,
			tbl_os.consumidor_complemento                               ,
			tbl_os.consumidor_bairro                                    ,
			tbl_os.revenda                                              ,
			tbl_os.revenda_cnpj                                         ,
			tbl_os.revenda_nome                                         ,
			tbl_os.nota_fiscal                                          ,
			to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
			tbl_os.aparencia_produto                                    ,
			tbl_os_extra.orientacao_sac                                 ,
			tbl_os_extra.admin_paga_mao_de_obra                        ,
			tbl_os.acessorios                                           ,
			tbl_os.fabrica                                              ,
			tbl_os.quem_abriu_chamado                                   ,
			tbl_os.obs                                                  ,
			tbl_os.consumidor_revenda                                   ,
			tbl_os_extra.extrato                                        ,
			tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
			tbl_os.codigo_fabricacao                                    ,
			tbl_os.satisfacao                                           ,
			tbl_os.laudo_tecnico                                        ,
			tbl_os.troca_faturada                                       ,
			tbl_os.admin                                                ,
			tbl_os.troca_garantia
			FROM	tbl_os
			JOIN	tbl_produto          ON tbl_produto.produto       = tbl_os.produto
			JOIN	tbl_posto            ON tbl_posto.posto           = tbl_os.posto
			JOIN	tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_os.fabrica
			JOIN	tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
										AND tbl_fabrica.fabrica       = $login_fabrica
			LEFT JOIN	tbl_os_extra     ON tbl_os.os                 = tbl_os_extra.os
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$os			= pg_result ($res,0,os);
		$tipo_atendimento	= pg_result ($res,0,tipo_atendimento);
		$posto			= pg_result ($res,0,posto);
		$posto_nome		= pg_result ($res,0,posto_nome);
		$sua_os			= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$produto_referencia	= pg_result ($res,0,referencia);
		$produto_descricao	= pg_result ($res,0,descricao);
		$produto_serie		= pg_result ($res,0,serie);
		$qtde_produtos      = pg_result ($res,0,qtde_produtos);
		$cliente		= pg_result ($res,0,cliente);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cpf		= pg_result ($res,0,consumidor_cpf);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_cep			= trim (pg_result ($res,0,consumidor_cep));
		$consumidor_endereco	= trim (pg_result ($res,0,consumidor_endereco));
		$consumidor_numero		= trim (pg_result ($res,0,consumidor_numero));
		$consumidor_complemento	= trim (pg_result ($res,0,consumidor_complemento));
		$consumidor_bairro		= trim (pg_result ($res,0,consumidor_bairro));
		$consumidor_cidade		= pg_result ($res,0,consumidor_cidade);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
		
		/*DADOS DO POSTO PARA CALCULO KM*/
		$contato_endereco             = trim (pg_fetch_result ($res,0,contato_endereco));
		$contato_numero               = trim (pg_fetch_result ($res,0,contato_numero));
		$contato_bairro               = trim (pg_fetch_result ($res,0,contato_bairro));
		$contato_cidade               = pg_fetch_result ($res,0,contato_cidade);
		$contato_estado               = pg_fetch_result ($res,0,contato_estado);
		$contato_cep                  = pg_fetch_result ($res,0,contato_cep);
				
		$revenda		= pg_result ($res,0,revenda);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf		= pg_result ($res,0,data_nf);
		$aparencia_produto	= pg_result ($res,0,aparencia_produto);
		$acessorios		= pg_result ($res,0,acessorios);
		$fabrica		= pg_result ($res,0,fabrica);
		$posto_codigo		= pg_result ($res,0,posto_codigo);
		$extrato		= pg_result ($res,0,extrato);
		$quem_abriu_chamado	= pg_result ($res,0,quem_abriu_chamado);
		$obs			= pg_result ($res,0,obs);
		$consumidor_revenda 	= pg_result ($res,0,consumidor_revenda);
		$codigo_fabricacao	= pg_result ($res,0,codigo_fabricacao);
		$satisfacao		= pg_result ($res,0,satisfacao);
		$laudo_tecnico		= pg_result ($res,0,laudo_tecnico);
		$troca_faturada		= pg_result ($res,0,troca_faturada);
		$troca_garantia		= pg_result ($res,0,troca_garantia);
		$admin_os		= trim(pg_result ($res,0,admin));

		$orientacao_sac	= pg_result ($res,0,orientacao_sac);
		$orientacao_sac = html_entity_decode ($orientacao_sac,ENT_QUOTES);
		$orientacao_sac = str_replace ("<br />","",$orientacao_sac);

		$admin_paga_mao_de_obra = pg_result ($res,0,admin_paga_mao_de_obra);
		
		$sql =	"SELECT tbl_os_produto.produto ,
						tbl_os_item.pedido     
				FROM    tbl_os 
				JOIN    tbl_produto using (produto)
				JOIN    tbl_posto using (posto)
				JOIN    tbl_fabrica using (fabrica)
				JOIN    tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										  AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica 
				JOIN    tbl_os_produto USING (os)
				JOIN    tbl_os_item
				ON      tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE   tbl_os.os = $os
				AND     tbl_os.fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);

		if(pg_numrows($res) > 0){
			$produto = pg_result($res,0,produto);
			$pedido  = pg_result($res,0,pedido);
		}

		$sql = "SELECT * FROM tbl_os_extra WHERE os = $os";
		$res = pg_exec($con,$sql);
	
		if (pg_numrows($res) == 1) {
			$taxa_visita              = pg_result ($res,0,taxa_visita);
			$visita_por_km            = pg_result ($res,0,visita_por_km);
			$hora_tecnica             = pg_result ($res,0,hora_tecnica);
			$regulagem_peso_padrao    = pg_result ($res,0,regulagem_peso_padrao);
			$certificado_conformidade = pg_result ($res,0,certificado_conformidade);
			$valor_diaria             = pg_result ($res,0,valor_diaria);
		}
		
		//SELECIONA OS DADOS DO CLIENTE PRA JOGAR NA OS
		if (strlen($consumidor_cidade)==0){
		if (strlen($cpf) > 0 OR strlen($cliente) > 0 ) {
			$sql = "SELECT
					tbl_cliente.cliente,
					tbl_cliente.nome,
					tbl_cliente.endereco,
					tbl_cliente.numero,
					tbl_cliente.complemento,
					tbl_cliente.bairro,
					tbl_cliente.cep,
					tbl_cliente.rg,
					tbl_cliente.fone,
					tbl_cliente.contrato,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado
					FROM tbl_cliente
					LEFT JOIN tbl_cidade USING (cidade)
					WHERE 1 = 1";
			if (strlen($cpf) > 0) $sql .= " AND tbl_cliente.cpf = '$cpf'";
			if (strlen($cliente) > 0) $sql .= " AND tbl_cliente.cliente = '$cliente'";

			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
				$consumidor_cliente		= trim (pg_result ($res,0,cliente));
				$consumidor_fone		= trim (pg_result ($res,0,fone));
				$consumidor_nome		= trim (pg_result ($res,0,nome));
				$consumidor_endereco	= trim (pg_result ($res,0,endereco));
				$consumidor_numero		= trim (pg_result ($res,0,numero));
				$consumidor_complemento	= trim (pg_result ($res,0,complemento));
				$consumidor_bairro		= trim (pg_result ($res,0,bairro));
				$consumidor_cep			= trim (pg_result ($res,0,cep));
				$consumidor_rg			= trim (pg_result ($res,0,rg));
				$consumidor_cidade		= trim (pg_result ($res,0,cidade));
				$consumidor_estado		= trim (pg_result ($res,0,estado));
				$consumidor_contrato	= trim (pg_result ($res,0,contrato));
			}
		}	
	}
	}
}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) > 0 and $btn_troca <> "trocar") {
	$os                 = $_POST['os'];
	$tipo_atendimento   = $_POST['tipo_atendimento'];
	$sua_os             = $_POST['sua_os'];
	$data_abertura      = $_POST['data_abertura'];
	$cliente            = $_POST['cliente'];
	$consumidor_nome    = $_POST['consumidor_nome'];
	$consumidor_cpf     = $_POST['consumidor_cpf'];
	$consumidor_fone    = $_POST['consumidor_fone'];
	$revenda            = $_POST['revenda'];
	$revenda_cnpj       = $_POST['revenda_cnpj'];
	$revenda_nome       = $_POST['revenda_nome'];
	$nota_fiscal        = $_POST['nota_fiscal'];
	$data_nf            = $_POST['data_nf'];
	$produto_referencia = $_POST['produto_referencia'];
	$cor                = $_POST['cor'];
	$acessorios         = $_POST['acessorios'];
	$aparencia_produto  = $_POST['aparencia_produto'];
	$obs                = $_POST['obs'];
	$orientacao_sac     = $_POST['orientacao_sac'];
	$consumidor_revenda = $_POST['consumidor_revenda'];
	$qtde_produtos      = $_POST['qtde_produtos'];
	$produto_serie      = $_POST['produto_serie'];

	$codigo_fabricacao  = $_POST['codigo_fabricacao'];
	$satisfacao         = $_POST['satisfacao'];
	$laudo_tecnico      = $_POST['laudo_tecnico'];
	$troca_faturada     = $_POST['troca_faturada'];

	$quem_abriu_chamado       = $_POST['quem_abriu_chamado'];
	$taxa_visita              = $_POST['taxa_visita'];
	$visita_por_km            = $_POST['visita_por_km'];
	$hora_tecnica             = $_POST['hora_tecnica'];
	$regulagem_peso_padrao    = $_POST['regulagem_peso_padrao'];
	$certificado_conformidade = $_POST['certificado_conformidade'];
	$valor_diaria             = $_POST['valor_diaria'];

	$sql =	"SELECT descricao
			FROM    tbl_produto
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_produto.referencia = UPPER ('$produto_referencia')
			AND     tbl_linha.fabrica      = $login_fabrica
			AND     tbl_produto.ativo IS TRUE";
	$res = pg_exec ($con,$sql);
	$produto_descricao = @pg_result ($res,0,0);
}


$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Cadastro de Ordem de Serviço - ADMIN"; 

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'callcenter';

include "cabecalho.php";
?>

<!--=============== <FUNÇÕES> ================================!-->


<? include "javascript_pesquisas.php" ?>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script src="http://maps.google.com/maps?file=api&v=2&key=<?=$gAPI_key?>" type="text/javascript"></script>
<script language="JavaScript">

function verifica_atendimento() {
    /*Verificacao para existencia de componente - HD 22891 */
	
    if (document.getElementById('div_mapa')){
        var ref = $('#tipo_atendimento').val();
		$.get('<?=$PHP_SELF?>',
			  {'ajax': 'tipo_atendimento',
			   'id'  : ref},
			  function(responseText) {
                    var response = responseText.split("|");
                    if (response[0]=="ok"){
                        document.getElementById('div_mapa').style.visibility = "visible";
                        document.getElementById('div_mapa').style.position = 'static';
                    }else{
                        document.getElementById('div_mapa').style.visibility = "hidden";
                        document.getElementById('div_mapa').style.position = 'absolute';
                    }
			  });
	}
}

function compara(campo1,campo2){
    var num1 = campo1.value.replace(".",",");
    var num2 = campo2.value.replace(".",",");
    
    if(num1!=num2){
        document.getElementById('div_mapa_msg').style.visibility = "visible";
        document.getElementById('div_mapa_msg').innerHTML = 'A distância percorrida pelo técnico estará sujeito a auditoria';
    }
    else{
        document.getElementById('div_mapa_msg').style.visibility = "visible";
        document.getElementById('div_mapa_msg').innerHTML='Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>';
    }
}
var map;
function initialize(busca_por){
	// Carrega o Google Maps
	if (GBrowserIsCompatible()) {
	
		var pt1 = document.getElementById("contato_cep").value;
		var pt2 = document.getElementById("consumidor_cep").value;
		
		map = new GMap2(document.getElementById("mapa"));
		map.setCenter(new GLatLng(-25.429722,-49.271944), 11)

		// Cria o objeto de roteamento
		 var dir = new GDirections(map);

		//hd 40389		

		pt1 = pt1.replace('-','');
		pt2 = pt2.replace('-','');
		
		
		if (pt1.length != 8 || pt2.length !=8) {
			//alert ('CEP inválido');
			busca_por = 'endereco';
		}else{
			pt1 = pt1.substr(0,5) + '-' + pt1.substr(5,3);
			pt2 = pt2.substr(0,5) + '-' + pt2.substr(5,3);
		}

		if (busca_por == 'endereco'){
			//alert('por endereco');
			//var pt1 = document.getElementById("ponto1").value
			var pt1 = document.getElementById("contato_endereco").value + ", "+ document.getElementById("contato_numero").value + " " + document.getElementById("contato_bairro").value + " " + document.getElementById("contato_cidade").value + " " + document.getElementById("contato_estado").value;

			var pt2 = document.getElementById("consumidor_endereco").value + ", "+ document.getElementById("consumidor_numero").value + " " + document.getElementById("consumidor_bairro").value + " " + document.getElementById("consumidor_cidade").value + " " + document.getElementById("consumidor_estado").value;
		}
		//document.getElementById("ponto1").value = pt1 +"CONSUMIDOR: "+pt2;

		//alert (pt1);
		//alert (pt2);


		document.getElementById('div_end_posto').innerHTML= '<B>Endereço do posto: </b><u>'+ pt1+'</u>';

		 // Carrega os pontos dados os endereços
		dir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});
		// O evento load do GDirections é executado quando chega o resultado do geocoding.
		GEvent.addListener(dir,"load", function() {
			//alert('entrou...');
			for (var i=0; i<dir.getNumRoutes(); i++) {
					var route = dir.getRoute(i);
					var dist = route.getDistance()
					var x = dist.meters*2/1000;
					var y = x.toString().replace(".",",");
					document.getElementById('distancia_km_conferencia').value = x;
					document.getElementById('distancia_km').value             = y;
					document.getElementById('distancia_km_maps').value        ='maps';
					document.getElementById('div_mapa_msg').innerHTML='Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>';
			 }
		});
		GEvent.addListener(dir,"error", function() {
			//alert('Nao encontrou ou deu erro');
			initialize('endereco');
		});
	}
}


function VerificaSuaOS (sua_os){
	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}
}

function vermapa(){
	document.getElementById("mapa").style.visibility="visible";
	document.getElementById("mapa2").style.visibility="visible";
}
function escondermapa(){
	document.getElementById("mapa").style.visibility="hidden";
	document.getElementById("mapa2").style.visibility="hidden";
}

// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		if ("<? echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.frm_os.sua_os;
		}else{
			janela.proximo = document.frm_os.data_abertura;
		}
		janela.focus();
	}
}

// ========= Função PESQUISA DE PRODUTO POR REFERÊNCIA OU DESCRIÇÃO ========= //

function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.produto_serie;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}
}

// ========= Função PESQUISA DE CONSUMIDOR POR NOME OU CPF ========= //

function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.cliente		= document.frm_os.consumidor_cliente;
			janela.nome			= document.frm_os.consumidor_nome;
			janela.cpf			= document.frm_os.consumidor_cpf;
			janela.rg			= document.frm_os.consumidor_rg;
			janela.cidade		= document.frm_os.consumidor_cidade;
			janela.estado		= document.frm_os.consumidor_estado;
			janela.fone			= document.frm_os.consumidor_fone;
			janela.endereco		= document.frm_os.consumidor_endereco;
			janela.numero		= document.frm_os.consumidor_numero;
			janela.complemento	= document.frm_os.consumidor_complemento;
			janela.bairro		= document.frm_os.consumidor_bairro;
			janela.cep			= document.frm_os.consumidor_cep;
			janela.proximo		= document.frm_os.revenda_nome;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

// ========= Função PESQUISA DE REVENDA POR NOME OU CNPJ ========= //

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_os.revenda_nome;
			janela.cnpj			= document.frm_os.revenda_cnpj;
			janela.fone			= document.frm_os.revenda_fone;
			janela.cidade		= document.frm_os.revenda_cidade;
			janela.estado		= document.frm_os.revenda_estado;
			janela.endereco		= document.frm_os.revenda_endereco;
			janela.numero		= document.frm_os.revenda_numero;
			janela.complemento	= document.frm_os.revenda_complemento;
			janela.bairro		= document.frm_os.revenda_bairro;
			janela.cep			= document.frm_os.revenda_cep;
			janela.email		= document.frm_os.revenda_email;
			janela.proximo		= document.frm_os.nota_fiscal;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;
		
		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}


/* ============= Função FORMATA CPF =============================
Nome da Função : formata_cpf (cpf, form)
		Formata o Campo de CPF a medida que ocorre a digitação
		Parâm.: cpf (numero), form (nome do form)
=================================================================*/
function formata_cpf(cpf, form){
	var mycpf = '';
		mycpf = mycpf + cpf;
		myrecord = "consumidor_cpf";
		myform = form;
		
		if (mycpf.length == 3){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 7){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 11){
			mycpf = mycpf + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
}



/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8; 
	var DEL=  46; 
	var FRENTE=  39; 
	var TRAS=  37; 
	var key; 
	var tecla; 
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true; 
			}
		if ( tecla == 13) return false; 
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla); 
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}
</script>

<!--========================= AJAX==================================.-->
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>


<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a existência de uma OS com o mesmo número e em
		caso positivo passa a mensagem para o usuário.
=============================================================== -->
<? 
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro; 
?>
	</td>
</tr>
</table>

<? } 
echo $msg_debug ;
?>

<?
$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res = pg_exec ($con,$sql);
$hoje = pg_result ($res,0,0);
?>


<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	
	<td valign="top" align="left">
		
		<?
		if (strlen ($msg_erro) > 0) {
//if ($ip == '201.0.9.216') echo $monta_sql;
			//echo $msg_erro;

			$consumidor_cidade		= $_POST['consumidor_cidade'];
			$consumidor_estado		= $_POST['consumidor_estado'];

			$consumidor_nome		= trim ($_POST['consumidor_nome']) ;
			$consumidor_fone		= trim ($_POST['consumidor_fone']) ;
			$consumidor_endereco	= trim ($_POST['consumidor_endereco']) ;
			$consumidor_numero		= trim ($_POST['consumidor_numero']) ;
			$consumidor_complemento	= trim ($_POST['consumidor_complemento']) ;
			$consumidor_bairro		= trim ($_POST['consumidor_bairro']) ;
			$consumidor_cep			= trim ($_POST['consumidor_cep']) ;
			$consumidor_rg			= trim ($_POST['consumidor_rg']) ;
			
		}
		?>
		<!-- ------------- Formulário ----------------- -->
	
		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="os" value="<? echo $os ?>">
		<? if (strlen($pedido) > 0) { ?>
			<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia ?>">
			<input class="frm" type="hidden" name="produto_descricao" value="<? echo $produto_descricao ?>">
		<?}?>
		

		<p>



		<? if ($login_fabrica == 19 OR $login_fabrica == 20) { ?>
		<center>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">
		Tipo de Atendimento

		<select name="tipo_atendimento" size="1" class="frm">
			<option <? if (strlen ($tipo_atendimento) == 0) echo " selected " ?> ></option>
			<?
			$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
			$res = pg_exec ($con,$sql) ;

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option ";
				if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
				echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'" ;
				echo " > ";
				echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
				echo "</option>\n";
			}
			?>
		</select>
		
		<?
		if($login_fabrica == 20){
			echo "<br><b><FONT SIZE='' COLOR='#FF9900'>Nos casos de Garantia de Peças ou  Acessórios não é necessário lançar o Produto na OS.</FONT></b>";	
		}
		?>
		</font>
		<? } ?>



		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<? if ($login_fabrica == 6){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="20" maxlength="20" value="<? echo $produto_serie ?>" >
				&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os')"  style='cursor: pointer'></A>
				<script>
				<!--
				function fnc_pesquisa_produto_serie (campo,form) {
					if (campo.value != "") {
						var url = "";
						url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
						janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
						janela.focus();
					}
				}
				-->
				</script>
			</td>
			<? } ?>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do Posto</font>
				<br>
						<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" <?
if (($login_fabrica == 5) or ($login_fabrica == 15)) { ?> onblur="fnc_pesquisa_posto2
(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif'
border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2
(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"></A>
			</td>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Posto</font>
				<br>
				<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" style="cursor:pointer;"></A>
			</td>
			<?php if ( !empty($os) ) { ?>
				<td>
				
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo Atendimento</font><br />
					<select name="tipo_atendimento" id='tipo_atendimento' class='frm' style='width:220px;' onChange="verifica_atendimento('tipo_atendimento');">
						
						<option></option>
						
						<?php 
						
							$sql = "SELECT *
			                        FROM tbl_tipo_atendimento
			                        WHERE fabrica           = $login_fabrica
			                        AND   ativo            IS TRUE $sql_add1
			                        ORDER BY tipo_atendimento";
			                        
			                $res = pg_query($con,$sql);
			                
			                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
								$codigo = str_pad(pg_fetch_result($res, $i, 'codigo'), 2, '0', STR_PAD_LEFT);
								$desc = pg_fetch_result($res, $i, 'descricao');
								$tipo_at= pg_fetch_result($res, $i, 'tipo_atendimento');
						
								$txt_option = $desc;
								$opt_sel = ($tipo_atendimento == $tipo_at) ? ' SELECTED':'';
						
								echo "<option value='$tipo_at'$opt_sel>$txt_option</option>";
							} 
						
						?>
						
					</select>		
				
				</td>
			<?php } ?>

		</tr>
		</table>

		<hr>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign="top">
			<td nowrap>
				<? if ($pedir_sua_os == 't') { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
				<br>
				<input  name     ="sua_os" 
						class    ="frm" 
						type     ="text" 
						size     ="20" 
						maxlength="20"  
						value    ="<? echo $sua_os ?>" 
						onblur   ="VerificaSuaOS(this); this.className='frm'; displayText('&nbsp;');" 
						onfocus  ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
				<?
				} else { 
					echo "&nbsp;";
					if (strlen($sua_os) > 0) {
						echo "<input type='hidden' name='sua_os' value='$sua_os'>";
					}else{
						echo "<input type='hidden' name='sua_os'>";
					}
				}
				?>
			</td>

			<?
			if (trim (strlen ($data_abertura)) == 0 AND $login_fabrica == 7) {
				$data_abertura = $hoje;
			}
			
			?>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
				<br>
				<input name="data_abertura" size="12" maxlength="10"value="<? echo $data_abertura ?>" type="text" class="frm" tabindex="0" ><br><font face='arial' size='1'>Ex.: 25/10/2004</font><br>
			</td>

			<? if ($login_fabrica == 19) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
				<br>
				<input name="qtde_produtos" size="2" maxlength="3"value="<? echo $qtde_produtos ?>" type="text" class="frm" tabindex="0" >
			</td>
			<? } ?>

			<td nowrap>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font>";
				}
				?>
				<br>
				<?	if (strlen($pedido) > 0) { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_referencia ?></b>
				</font>
				<?	}else{	?>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')">
				<?	}	?>
			</td>
			<td nowrap>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font>";
				}
				?>
				<br>
				<?	if (strlen($pedido) > 0) { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_descricao ?></b>
				</font>
				<?	}else{	?>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>"
						<? if (($login_fabrica == 5) or ($login_fabrica == 15)) { ?> onblur="fnc_pesquisa_produto2
(document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')" <? } ?>>&nbsp;<img
src='imagens/btn_buscar5.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript:
fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')"></A>
				<?	}	?>
			</td>
			<? if ($login_fabrica <> 6){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série.</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="15" maxlength="20" value="<? echo $produto_serie ?>" >
			</td>
			<? } ?>

			<? if ($login_fabrica==11) {
					if ( (strlen($admin_os) > 0 and strlen($os) > 0) or (strlen($os)==0)) {
						echo "<td>&nbsp;&nbsp;<BR><input type='checkbox' name='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra'";
						if ($admin_paga_mao_de_obra == 't') echo "checked";
						echo "> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Pagar Mão-de-Obra</font></td>";
					}
			} ?>
		</tr>
		</table>

		<? if ($login_fabrica == 1) { ?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font>
				<br>
				<input  name ="codigo_fabricacao" class ="frm" type ="text" size ="13" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');">
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">30 dias Satisfação DeWALT</font>
				<br>
				<input name ="satisfacao" class ="frm" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Laudo técnico</font>
				<br>
				<input  name ="laudo_tecnico" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo técnico.');">
			</td>
		</tr>
		</table>
		<? } ?>



		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_rg">

		<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
		<tr class="top">
			<td  class="top">Informações sobre o Cliente</td>
		</tr>
		</table>
				
		<hr>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';  displayText('&nbsp;Insira aqui o nome do Cliente.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone</font>
				<br>
				<input class="frm" type="text" name="consumidor_fone"   size="15" maxlength="50" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o telefone do consumidor.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CEP</font><br />
				<input class="frm" type="text" name="consumidor_cep" id="consumidor_cep"   size="10" maxlength="8" value="<? echo $consumidor_cep ?>">
				
				<input type='hidden' name='contato_endereco' id='contato_endereco' value='<?echo $contato_endereco;?>'>
				<input type='hidden' name='contato_numero' id='contato_numero' value='<?echo $contato_numero;?>'>
				<input type='hidden' name='contato_cep' id='contato_cep' value='<?echo $contato_cep;?>'>
				<input type='hidden' name='contato_bairro' id='contato_bairro' value='<?echo $contato_bairro;?>'>
				<input type='hidden' name='contato_cidade' id='contato_cidade' value='<?echo $contato_cidade;?>'>
				<input type='hidden' name='contato_estado' id='contato_estado' value='<?echo $contato_estado;?>'>
				
			</td>
		</tr>
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font><br />
				<input class="frm" type="text" name="consumidor_endereco"   size="15" maxlength="50" value="<? echo $consumidor_endereco ?>">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font><br />
				<input class="frm" type="text" name="consumidor_numero"   size="4" maxlength="10" value="<? echo $consumidor_numero ?>">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font><br />
				<input class="frm" type="text" name="consumidor_bairro"   size="15" maxlength="40" value="<? echo $consumidor_estado ?>">
			</td>
		</tr>
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font><br />
				<input class="frm" type="text" name="consumidor_complemento"   size="15" maxlength="20" value="<? echo $consumidor_complemento ?>">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font>
				<br>
				<input class="frm" type="text" name="consumidor_cidade"   size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font>
				<br>
				<input class="frm" type="text" name="consumidor_estado"   size="2" maxlength="2" value="<? echo $consumidor_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');">
			</td>

		</tr>
		</table>

<p>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td  class="top">Informações sobre a Revenda</td>
</tr>
</table>
		
		<hr>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign="top">
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
				<br>
				<input class="frm" type="text" name="nota_fiscal"  size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
				<br>
				<input class="frm" type="text" name="data_nf"    size="12" maxlength="10" value="<? echo $data_nf ?>" tabindex="0" ><br><font face='arial' size='1'>Ex.: 25/10/2004</font>
			</td>
		</tr>
		</table>




<!--		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
		<hr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Aparência do Produto</font>
				<br>
				<input class="frm" type="text" name="aparencia_produto" size="35" value="<? echo $aparencia_produto ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Acessórios</font>
				<br>
				<input class="frm" type="text" name="acessorios" size="35" value="<? echo $acessorios ?>" >
			</td>
		</tr>
		</table>-->

		<?
		if ($login_fabrica <> 7) {
			echo "<!-- ";
		}
		?>
		

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
		<hr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Chamado aberto por</font>
				<br>
				<input class="frm" type="text" name="quem_abriu_chamado" size="20" maxlength="30" value="<? echo $quem_abriu_chamado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Nome do funcionário do cliente que abriu este chamado.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
				<br>
				<input class="frm" type="text" name="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;ObservaçÕes e dados adicionais desta OS.');">
			</td>
		</tr>
		</table>

		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Taxa de Visita</font>
				<br>
				<input class="frm" type="text" name="taxa_visita" size="8" maxlength="10" value="<? echo $taxa_visita ?>" >
				&nbsp;
				<input class="frm" type='checkbox' name='visita_por_km' value='t' <? if ($visita_por_km == 't') echo " checked " ?> >Km
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Hora Técnica</font>
				<br>
				<input class="frm" type="text" name="hora_tecnica" size="8" maxlength='10' value="<? echo $hora_tecnica ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Regulagem</font>
				<br>
				<input class="frm" type="text" name="regulagem_peso_padrao" size="8" maxlength='10' value="<? echo $regulagem_peso_padrao ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Certificado</font>
				<br>
				<input class="frm" type="text" name="certificado_conformidade" size="8" maxlength='10' value="<? echo $certificado_conformidade ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Diária</font>
				<br>
				<input class="frm" type="text" name="valor_diaria" size="8" maxlength='10' value="<? echo $valor_diaria ?>" >
			</td>
		</tr>
		</table>

		<?
		if ($login_fabrica <> 7) {
			echo " --> ";
		}
		?>
	
	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
<?
if (strlen ($os) > 0) {
?>
		<img src='imagens/btn_alterarcinza.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ;  document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Alterar os itens da Ordem de Serviço" border='0'>
		<img src='imagens_admin/btn_apagar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }" ALT="Apagar a Ordem de Serviço" border='0'>
<?
}else{
?>
		<img src='imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0'>
<?
}
?>
	</td>
</tr>
</table>

<input type='hidden' name = 'revenda_fone'>
<input type='hidden' name = 'revenda_cidade'>
<input type='hidden' name = 'revenda_estado'>
<input type='hidden' name = 'revenda_endereco'>
<input type='hidden' name = 'revenda_numero'>
<input type='hidden' name = 'revenda_complemento'>
<input type='hidden' name = 'revenda_bairro'>
<input type='hidden' name = 'revenda_cep'>
<input type='hidden' name = 'revenda_email'>

</form>


<p>

<div id='div_mapa' style='background:#efefef;border:#999999 1px solid;font-size:10px;padding:5px;<?if( empty($os) ) echo "visibility:hidden;position:absolute;";?>' >
<b>Para Calcular a distância percorrida pelo técnico para execução do serviço(ida e volta):<br />
Preencha todos os campos de endereço acima ou preencha o campo de distância</b><br /><br />

<?php

	$sql = "SELECT qtde_km
			FROM tbl_os
			WHERE os = $os
			AND fabrica = $login_fabrica";
			
	$res = pg_query($con,$sql);
	
	$distancia_km = @pg_result($res,0,0);
	
	if ( $distancia_km_conferencia == '' )
		$distancia_km_conferencia = $distancia_km;

?>

<form action="<?=$PHP_SELF?>" method="POST" name="km">
<input type='hidden' name='os' value='<? echo $os ?>'>
<input type="hidden" id="ponto1" name="ponto1" value="<?=$endereco_posto?>" />
<input type='hidden' id='coordPosto' value='<?=$coord_posto?>' />
<input type="hidden" id="cep_posto"value="<?=$cep_posto?>" />
<input type="hidden" id="distancia_km_maps"  value="" />
<input type='hidden' name='distancia_km_conferencia' id='distancia_km_conferencia' value='<?=$distancia_km_conferencia?>'>

Distância: <input type='text' name='distancia_km' id='distancia_km' value='<?=$distancia_km?>' size='8' onchange="javascript:compara(distancia_km,distancia_km_conferencia)"> Km
<input  type="button" id='btn_calcula_distancia' onclick="initialize('')" value="Calcular Distância" size='5' ><div id='div_mapa_msg' style='color:#FF0000'></div>
<br />
<div id='div_end_posto' style='color:#000000'>
<B>Endereço do posto:</b>
<u>
	<?if(strlen($posto)>0){?>
		<?=$endereco_posto?>
	<?}?>
</u>
</div>
<br />&nbsp;
<input type="hidden" name="btn_acao" value="" />
<input type="hidden" name="tipo_atendimento" value="" />
<input type='button' value='Alterar' onclick="javascript: if (confirm ('Confirma Alteração de KM?') == true ) {document.km.tipo_atendimento.value=document.frm_os.tipo_atendimento.value ;document.km.btn_acao.value='altera_km';document.km.submit(); }">

</form>

</div>
<div style="width:500px; margin:auto;">
<div id="mapa2" style=" width:500px; margin:auto; height:10px;visibility:hidden;position:absolute; ">
<a href='javascript:escondermapa();'>Fechar Mapa</a>
</div><br>
<div id="mapa" style=" width:500px; margin:auto; overflow:hidden; height:300px;visibility:hidden;position:absolute;border: 1px #FF0000 solid; "></div>
</div>
<?
if(strlen($os) > 0) {
	if ($troca_garantia == 't') {
		echo "<table width='400' align='center' border='2' cellspacing='0' bgcolor='#3366FF' style='' class=''>";
		echo "<tr>";
		echo "<td align='center' style='color: #ffffff'> ";
		echo "<font color='#ffffff' size='+1'> <b> Produto já trocado </b> </font> </a> ";
		echo "</td>";
		echo "</tr>";
		echo "</table>";

	}else{
?>
		<table width='400' align='center' border='0' cellspacing='0' bgcolor='0' style='' class=''>
		<?php if($login_fabrica != 15){ ?>
		<form method='post' name='frm_troca' action='<? echo $PHP_SELF ?>'>
		<input type='hidden' name='os' value='<? echo $os ?>'>
		<!-- colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca -->
		<input type='hidden' name='orient_sac' value=''>
		<tr>
		<td align='center' style='color: #3300cc'> 

		<font color='#3300CC' size='+1'> <b> Trocar Produto em Garantia </b> </font> </a> 
		<p>
		<b>Trocar pelo Produto</b> 
		<? 
		if ($login_fabrica == 3) {
			$sql = "SELECT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.produto
					FROM tbl_produto
					WHERE tbl_produto.familia = (
						SELECT tbl_produto.familia 
						FROM tbl_produto
						JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_os.os = $os
					)
					AND tbl_produto.ativo
					ORDER BY tbl_produto.referencia";
			$resTroca = pg_exec ($con,$sql);

			echo "<select name='troca_garantia_produto' size='1'>";
			echo "<option value='' ></option>";
			for ($i = 0 ; $i < pg_numrows($resTroca) ; $i++) {
				echo "<option value='" . pg_result ($resTroca,$i,referencia) . "'";
				if ($troca_garantia_produto_seq == pg_result ($resTroca,$i,produto) ) echo " selected";
				echo ">" . pg_result ($resTroca,$i,referencia) . " - " . pg_result ($resTroca,$i,descricao) . "</option>";
			}
			echo "<option value='-1' >RESSARCIMENTO FINANCEIRO</option>";
			echo "</select>";

		}else{
			echo "<input type='text' name='troca_garantia_produto' size='10' maxlength='10' value='$troca_garantia_produto'>" ;
			echo "<br>";
			if($login_fabrica==20)echo "<b>Valor para Troca</b>";
			else echo "<b>Mão-de-Obra para Troca</b>";
			echo" <input type='text' name='troca_garantia_mao_obra' size='5' maxlength='10' value='$troca_garantia_mao_obra'>";
			echo "<br>";
			echo "(deixe em branco para pagar valor padrão)";
			echo "<br>";
			echo "<input type='radio' name='troca_via_distribuidor' value='f' ";
			if ($troca_via_distribuidor == 'f') echo " checked " ;
			echo "> Troca Direta ";
			echo "&nbsp;&nbsp;&nbsp;";
			echo "<input type='radio' name='troca_via_distribuidor' value='t' ";
			if ($troca_via_distribuidor == 't') echo " checked " ;
			echo "> Via Distribuidor";
			echo "<br>";
		}
		echo "<p>";
		echo "<input type='hidden' name='btn_troca' value=''>";
		//colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca
		//colocado "document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value"
		echo "<input type='button' value='Trocar' onclick=\"javascript: if (confirm ('Confirma Troca') == true ) {document.frm_troca.btn_troca.value='trocar';document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value ; document.frm_troca.submit(); } \">";
		?>
		</td>
		</tr>
		</table>

		</form>
		<?php } ?>
<?	} 
}
?>




<p>

<? include "rodape.php";?>
