<?
//conforme chamado 474 (fabricio -  britania) na hr em que eram buscada as informacoes da OS, estava buscando na forma antiga, ou seja, estava buscando informacoes do cliente na tbl_cliente, com o novo metodo as info do consumidor sao gravados direto na tbl_os, com isso hr que estava buscando info do cliente estava buscando no local errado -  Takashi 31/09/2006
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';

include 'funcoes.php';

if($ajax=='tipo_atendimento'){
	$sql = "SELECT tipo_atendimento,km_google
			FROM tbl_tipo_atendimento 
			WHERE tipo_atendimento = $id
			AND   fabrica          = $login_fabrica";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){

		$km_google = pg_result($res,0,km_google);
		if($km_google == 't'){
			echo "ok|sim";
		}else{
			echo "no|não";
		}
	exit;
	}
}

# HD 33729 - Francisco Ambrozio (19/8/08)
#   Campos "Capacidade" e "Divisão" preenchidos por ajax.
if($_GET["ajax"]=="true" AND $_GET["buscaInformacoes"]=="true"){
	$referencia = trim($_GET["produto_referencia"]);
	$serie      = trim($_GET["serie"]);

	if(strlen($referencia)>0){
		$sql = "SELECT produto, capacidade, divisao
				FROM tbl_produto 
				JOIN tbl_linha USING(linha) 
				WHERE fabrica  = $login_fabrica 
				AND referencia ='$referencia'";
		$res = @pg_exec($con,$sql);

		if (pg_numrows($res)>0){
			$produto    = trim(pg_result($res,0,produto));
			$capacidade = trim(pg_result($res,0,capacidade));
			$divisao    = trim(pg_result($res,0,divisao));

			/*if(strlen($serie)>0) {
				$sql = "SELECT capacidade, divisao, versao
						FROM tbl_os 
						WHERE fabrica  = $login_fabrica 
						AND   posto    = $login_posto
						AND   produto  = $produto
						AND   serie    = '$serie' ;";
				$res = @pg_exec($con,$sql);
				if (pg_numrows($res)>0) {
					$capacidade = trim(pg_result($res,0,capacidade));
					$divisao    = trim(pg_result($res,0,divisao));
					$versao     = trim(pg_result($res,0,versao));
					echo "ok|$capacidade|$divisao|$versao";
					exit;
				}
			}*/
			echo "ok|$capacidade|$divisao|$versao";
			exit;
		}
	}
	echo "nao|nao";
	exit;
}

// HD 31188
if($_GET["ajax"]=="true" AND $_GET["buscaValores"]=="true"){
	$referencia = trim($_GET["produto_referencia"]);

	if(strlen($referencia)>0){
		$sql = "SELECT produto, capacidade, divisao
				FROM tbl_produto 
				JOIN tbl_linha USING(linha) 
				WHERE fabrica  = $login_fabrica 
				AND referencia ='$referencia'";
		$res = @pg_exec($con,$sql);

		if (pg_numrows($res)>0){
			$produto    = trim(pg_result($res,0,produto));

			$sql = "SELECT  taxa_visita,
							hora_tecnica,
							valor_diaria,
							valor_por_km_caminhao,
							valor_por_km_carro,
							regulagem_peso_padrao,
							certificado_conformidade
					FROM    tbl_familia_valores
					JOIN    tbl_produto USING(familia)
					WHERE   tbl_produto.produto = $produto";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				$taxa_visita              = number_format(trim(pg_result($res,0,taxa_visita)),2,',','.');
				$hora_tecnica             = number_format(trim(pg_result($res,0,hora_tecnica)),2,',','.');
				$valor_diaria             = number_format(trim(pg_result($res,0,valor_diaria)),2,',','.');
				$valor_por_km_caminhao    = number_format(trim(pg_result($res,0,valor_por_km_caminhao)),2,',','.');
				$valor_por_km_carro       = number_format(trim(pg_result($res,0,valor_por_km_carro)),2,',','.');
				$regulagem_peso_padrao    = number_format(trim(pg_result($res,0,regulagem_peso_padrao)),2,',','.');
				$certificado_conformidade = number_format(trim(pg_result($res,0,certificado_conformidade)),2,',','.');
				echo "ok|$taxa_visita|$hora_tecnica|$valor_diaria|$valor_por_km_carro|$valor_por_km_caminhao|$regulagem_peso_padrao|$certificado_conformidade";
				exit;
			}
			exit;
		}
	}
	echo "nao|nao";
	exit;
}


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
$nosso_ip = include("../nosso_ip.php");
if(($ip=='201.43.245.148' OR ($ip==$nosso_ip) OR $login_fabrica == 15) AND $login_fabrica == 15){
	if($_GET["os"]) header("Location: os_cadastro_latina.php?os=$os");
	else            header("Location: os_cadastro_latinatec_ajax.php");
	exit;
}

if($gerar_pedido=='ok'){
	$sql = "BEGIN TRANSACTION";
	$res = pg_exec($con,$sql);

	$sql = "UPDATE tbl_os_troca SET gerar_pedido = TRUE WHERE os = $os";
	$res = @pg_exec($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if(strlen($msg_erro)==0){
		$sql = "COMMIT TRANSACTION";
		$res = pg_exec($con,$sql);
		header("Location: os_press.php?os=$os");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_result ($res,0,pedir_defeito_reclamado_descricao);


$btn_cancelar = strtolower ($_POST['cancelar']);
if ($btn_cancelar == "cancelar") {
	$os                  = $_POST["os"];
	$motivo_cancelamento = trim($_POST["motivo_cancelamento"]);

	if(strlen($motivo_cancelamento)==0) $msg_erro = "Por favor digite o motivo do cancelamento da OS";
	if(strlen($msg_erro)==0){
		$sql = "SELECT DISTINCT pedido 
				FROM tbl_os
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item    USING(os_produto)
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.os      = $os
				AND   tbl_os_item.pedido IS NOT NULL";
		$res1 = @pg_exec($con,$sql);
		if(pg_numrows($res1)>0){
			for($i=0;$i<pg_numrows($res1);$i++){
				$pedido = pg_result($res1,$i,0);
				$sql = "SELECT  PI.pedido_item,
						PI.qtde      ,
						PC.peca      ,
						PC.referencia,
						PC.descricao ,
						OP.os        ,
						PE.posto     ,
						PE.distribuidor
					FROM    tbl_pedido       PE
					JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
					JOIN    tbl_peca         PC ON PC.peca       = PI.peca
					LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
					LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
					WHERE   PI.pedido      = $pedido
					AND     PE.fabrica     = $login_fabrica
					AND     PE.exportado   IS NULL";
				$res2 = pg_exec($con,$sql);
				if(pg_numrows($res2)>0){
						$peca  = pg_result($res2,0,peca);
						$qtde  = pg_result($res2,0,qtde);
						$posto = pg_result($res2,0,posto);
						$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde WHERE pedido_item = $cancelar;";
						$res = pg_exec ($con,$sql);
						$sql = "INSERT INTO tbl_pedido_cancelado (
									pedido,
									posto,
									fabrica,
									os,
									peca,
									qtde,
									motivo,
									data
								)VALUES(
									$pedido,
									$posto,
									$login_fabrica,
									$os,
									$peca,
									$qtde,
									'$motivo_cancelamento',
									current_date
								);";
						$res = pg_exec ($con,$sql);
				}else{ 
					if($login_fabrica <> 45) $msg_erro= "OS não pode ser cancelada porque o pedido já foi exportado!";
				}
			}
		}
		if(strlen($msg_erro)==0){
			$sql = "BEGIN TRANSACTION";
			$res = pg_exec($con,$sql);
			$sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,15,'$motivo_cancelamento');";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$sql = "UPDATE tbl_os SET excluida = TRUE WHERE os = $os";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			#158147 Paulo/Waldir desmarcar se for reincidente
			$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
			$res = pg_exec($con, $sql);

			if(strlen($msg_erro)==0){
				$sql = "COMMIT TRANSACTION";
				$res = pg_exec($con,$sql);
				header("Location: os_press.php?os=$os");
				exit;
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}

}


/*======= Troca em Garantia =========*/

$btn_troca = strtolower ($_POST['btn_troca']);

if ($btn_troca == "trocar") {
	$msg_erro = "";
	
	$sql = "BEGIN TRANSACTION";
	$res = pg_exec($con,$sql);

	$os                      = $_POST["os"];
	$observacao_pedido       = $_POST["observacao_pedido"];
	$qtde_itens              = $_POST["qtde_itens"];

	//HD 6559
	if (strlen(trim($observacao_pedido))==0) {
		$msg_erro = "Informe uma observação para nota fiscal.";
		//echo "\n\n".$observacao_pedido."\n\n";
	}

	$troca_garantia_mao_obra = $_POST["troca_garantia_mao_obra"];
	$troca_garantia_mao_obra = str_replace(",",".",$troca_garantia_mao_obra);

	$troca_via_distribuidor = $_POST['troca_via_distribuidor'];
	if (strlen($troca_via_distribuidor) == 0) $troca_via_distribuidor = "f";
	//hd17603
	$sql = "UPDATE tbl_os SET data_fechamento = NULL,finalizada=null WHERE os = $os AND fabrica = $login_fabrica ";
	$res = pg_exec($con,$sql);
	$msg_erro .= pg_errormessage($con);


	$sql = "SELECT os_troca,peca,os FROM tbl_os_troca WHERE os = $os AND pedido IS NULL ";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0){
		$troca_efetuada =  pg_result($res,0,os_troca);
		$troca_os       =  pg_result($res,0,os);
		$troca_peca     =  pg_result($res,0,peca);

		$sql = "DELETE FROM tbl_os_troca WHERE os_troca = $troca_efetuada";
		$sql = "UPDATE tbl_os_troca SET os = 4836000 WHERE os_troca = $troca_efetuada";
		$res = pg_exec ($con,$sql);

		// HD 13229
		if(strlen($troca_peca) > 0) {
			$sql = "DELETE FROM tbl_os_item WHERE os_item IN (SELECT os_item FROM tbl_os_item JOIN tbl_os_produto USING(os_produto) WHERE os=$troca_os and peca = $troca_peca)";

			$res = pg_exec ($con,$sql);
		}
	}

	if (strlen($qtde_itens)==0){
		$qtde_itens = 0;
	}

	for ($i=0; $i<$qtde_itens; $i++){
		$os_item_check = $_POST["os_item_".$i];
		if (strlen($os_item_check)>0){
			$sql = "UPDATE tbl_os_item SET originou_troca = 't' WHERE os_item = $os_item_check ";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

	$sql = "SELECT produto,sua_os,posto FROM tbl_os WHERE os = $os;";
	$res = @pg_exec($con,$sql);
	$msg_erro .= pg_errormessage($con);
	
	$produto = pg_result($res,0,produto);
	$sua_os  = pg_result($res,0,sua_os);
	$posto   = pg_result($res,0,posto);

// adicionado por Fabio - Altera o status para liberado da Assis. Tec. da Fábrica caso tenha intervencao.
	$sql = "SELECT status_os FROM tbl_os_status WHERE os=$os AND status_os IN (62,64,65,72,73,87,88) ORDER BY data DESC LIMIT 1";
	$res = pg_exec($con,$sql);
	$qtdex = pg_numrows($res);
	if ($qtdex>0){
		$statuss=pg_result($res,0,status_os);
		if ($statuss=='62' || $statuss=='65' || $statuss=='72' || $statuss=='88'){

			$proximo_status = "64";

			if ( $statuss == "72"){
				$proximo_status = "73";
			}
			if ( $statuss == "87"){
				$proximo_status = "88";
			}

			$sql = "INSERT INTO tbl_os_status
					(os,status_os,data,observacao,admin) 
					VALUES ($os,$proximo_status,current_timestamp,'OS Liberada',$login_admin)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if ($login_fabrica==3) {
				$id_servico_realizado			= 20;
				$id_servico_realizado_ajuste	= 96;
				$id_solucao_os					= 85;
				$defeito_constatado				= 10224;
			}
			if ($login_fabrica==1) {
				$id_servico_realizado			= 62;
				$id_servico_realizado_ajuste	= 64;
			}
			if ($login_fabrica==25) {
				$id_servico_realizado			= 625;
				$id_servico_realizado_ajuste	= 628;
				$id_solucao_os					= 210;
				$defeito_constatado				= 10536;
			}
			if ($login_fabrica==45) {
				$id_servico_realizado			= 638;
				$id_servico_realizado_ajuste	= 639;
				$id_solucao_os					= 397;
				$defeito_constatado				= 11250;
			}

			if (strlen($id_servico_realizado_ajuste)>0 AND strlen($id_servico_realizado)>0){
				$sql =  "UPDATE tbl_os_item
						SET servico_realizado = $id_servico_realizado_ajuste
						WHERE os_item IN (
							SELECT os_item
							FROM tbl_os
							JOIN tbl_os_produto USING(os)
							JOIN tbl_os_item USING(os_produto)
							JOIN tbl_peca USING(peca)
							WHERE tbl_os.os       = $os
							AND tbl_os.fabrica    = $login_fabrica
							AND tbl_os_item.servico_realizado = $id_servico_realizado
							AND tbl_os_item.pedido IS NULL
							AND tbl_peca.retorna_conserto IS TRUE
						)";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		
			if (strlen($defeito_constatado)>0 AND strlen($id_solucao_os)>0){
				$sql = "UPDATE tbl_os 
						SET solucao_os         = $id_solucao_os,
							defeito_constatado = $defeito_constatado
						WHERE os       = $os
						AND fabrica    = $login_fabrica
						AND solucao_os IS NULL
						AND defeito_constatado IS NULL";
				$res = pg_exec($con,$sql); 
				$msg_erro .= pg_errormessage($con);
			}
		}
	}

	//colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca
	$orientacao_sac = trim ($_POST['orient_sac']);
	$orientacao_sac = htmlentities ($orientacao_sac,ENT_QUOTES);
	$orientacao_sac = nl2br ($orientacao_sac);
	if (strlen ($orientacao_sac) == 0)
		$orientacao_sac  = "null";
	else
		$orientacao_sac  =  $orientacao_sac  ;
	
	//hd 11083 7/1/2008
	if($login_fabrica == 3){
		if (strlen(trim($orientacao_sac))>0 AND trim($orientacao_sac)!='null'){
			$orientacao_sac =  date("d/m/Y H:i")." - ".$orientacao_sac; 
			$sql = "UPDATE  tbl_os_extra SET 
						orientacao_sac =  CASE WHEN orientacao_sac IS NULL OR orientacao_sac = 'null' THEN '' ELSE orientacao_sac || ' \n' END || trim('$orientacao_sac') ,
					WHERE tbl_os_extra.os = $os;";
		}

	}else{
		$sql = "UPDATE  tbl_os_extra SET orientacao_sac = trim('$orientacao_sac')
		WHERE tbl_os_extra.os = $os;";
	}

	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);




	$troca_garantia_produto = $_POST["troca_garantia_produto"];

	if ($troca_garantia_produto == "-1") {//resarcimento financeiro

		$sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if($login_fabrica == 3 AND pg_numrows($res)==1 ) {
			$sql = "UPDATE tbl_os SET 
					troca_garantia          = 't', 
					ressarcimento           = 't', 
					troca_garantia_admin    = $login_admin
					WHERE os = $os AND fabrica = $login_fabrica";
		}else{
			if($login_fabrica == 3){
				// HD 18558, 24198
				$sql = "UPDATE tbl_os SET 
					troca_garantia          = 't', 
					ressarcimento           = 't', 
					troca_garantia_admin    = $login_admin,
					data_conserto           = CURRENT_TIMESTAMP,
					data_fechamento         = CURRENT_DATE,
					finalizada              = CURRENT_TIMESTAMP
					WHERE os = $os AND fabrica = $login_fabrica";
			}else{
				$sql = "UPDATE tbl_os SET 
					troca_garantia          = 't', 
					ressarcimento           = 't', 
					troca_garantia_admin    = $login_admin,
					data_fechamento         = CURRENT_DATE,
					finalizada              = CURRENT_TIMESTAMP
					WHERE os = $os AND fabrica = $login_fabrica";
			}
		}
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "UPDATE tbl_os_extra SET 
				obs_nf                     = '$observacao_pedido'
				WHERE os = $os";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);


				//--== Novo Procedimento para Troca | Raphael Giovanini ===========

		if( strlen($_POST["causa_troca"])          == 0 ) $msg_erro .= "Escolha a causa da troca";
		else                                              $causa_troca = $_POST["causa_troca"];
		if( strlen($_POST["setor"])                == 0 ) $msg_erro .= "Selecione o setor responsável";
		else                                              $setor = $_POST["setor"];
		if( strlen($_POST["situacao_atendimento"]) == 0 ) $msg_erro .= "<br>Selecione a situação do atendimento";
		else                                              $situacao_atendimento = $_POST["situacao_atendimento"];
		if( strlen($_POST["gerar_pedido"])         == 0 ) $gerar_pedido = "'f'";
		else                                              $gerar_pedido = "'t'";
		if($_POST["envio_consumidor"]=='t')               $envio_consumidor = " 't' ";
		else                                              $envio_consumidor = " 'f' ";

		$ri = $_POST["ri"];

		if (( $setor=='Procon' OR $setor=='SAP' ) AND(strlen($ri)=="null")) $msg_erro .= "<br>Obrigatório o preenchimento do RI";

		if( strlen($_POST["ri"])                   == 0 ) $ri = "null";
		else                                              $ri = "'".$_POST["ri"]."'";

		$modalidade_transporte = $_POST["modalidade_transporte"];
		if(strlen($modalidade_transporte)==0)$xmodalidade_transporte = "''";
		if($login_fabrica==3){
			if(strlen($modalidade_transporte)==0) $msg_erro = "É obrigatório a escolha da modalidade de transporte";
			else $xmodalidade_transporte = "'$modalidade_transporte'";
		}

		if(strlen($msg_erro) == 0 ){
			$sql = "INSERT INTO tbl_os_troca (
						setor                 ,
						situacao_atendimento  ,
						os                    ,
						admin                 ,
						observacao            ,
						causa_troca           ,
						gerar_pedido          ,
						ressarcimento         ,
						envio_consumidor      ,
						modalidade_transporte ,
						ri                    ,
						fabric
					)VALUES(
						'$setor'                ,
						$situacao_atendimento   ,
						$os                     ,
						$login_admin            ,
						'$observacao_pedido'    ,
						$causa_troca            ,
						$gerar_pedido           ,
						TRUE                    ,
						$envio_consumidor       ,
						$xmodalidade_transporte ,
						$ri                     ,
						$login_fabrica
					)";
			$res = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		# HD 11631
		if ($login_fabrica==3 AND strlen($msg_erro)==0){
			 $sql = "INSERT INTO tbl_comunicado (
						descricao              ,
						mensagem               ,
						tipo                   ,
						fabrica                ,
						obrigatorio_os_produto ,
						obrigatorio_site       ,
						posto                  ,
						ativo                  
					) VALUES (
						'OS $sua_os - Ressarcimento Financeiro',
						'A Fábrica irá fazer o ressarcimento financeiro do produto da OS $sua_os',
						'OS Ressarcimento Financeiro',
						$login_fabrica,
						'f' ,
						't',
						$posto,
						't'
					);";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

	}else{
		$sql = "SELECT * FROM tbl_produto JOIN tbl_familia using(familia) WHERE produto = '$troca_garantia_produto' AND fabrica = $login_fabrica;";
		$resProd = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (@pg_numrows($resProd) == 0) {
			$msg_erro .= "Produto informado não encontrado";
		}else{
			$troca_produto    = @pg_result ($resProd,0,produto);
			$troca_ipi        = @pg_result ($resProd,0,ipi);
			$troca_referencia = @pg_result ($resProd,0,referencia);
			$troca_descricao  = @pg_result ($resProd,0,descricao);
		}
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT * FROM tbl_peca WHERE referencia = '$troca_referencia' and fabrica = $login_fabrica;";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			
			if (pg_numrows($res) == 0) {
				if (strlen ($troca_ipi) == 0) $troca_ipi = 10;

				$sql =	"SELECT peca
						FROM tbl_peca
						WHERE fabrica    = $login_fabrica
						AND   referencia = '$troca_garantia_produto'
						LIMIT 1;";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
				if (pg_numrows($res) > 0) {
					$peca = pg_result($res,0,0);
				}else{
					$sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, ipi, origem, produto_acabado) VALUES ($login_fabrica, '$troca_referencia', '$troca_descricao' , $troca_ipi , 'NAC','t')" ;
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
		
			$sql = "
				SELECT *
				FROM   tbl_os_item
				JOIN   tbl_servico_realizado USING (servico_realizado)
				JOIN   tbl_os_produto        ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE  tbl_os_produto.os = $os
				AND    tbl_servico_realizado.troca_de_peca
				AND    tbl_os_item.pedido NOTNULL " ;
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if ( pg_numrows($res) > 0 ) {
				for($w = 0 ; $w < pg_numrows($res) ; $w++ ) {
					$os_item = pg_result($res,$w,os_item);
					$qtde    = pg_result($res,$w,qtde);
					$pedido  = pg_result($res,$w,pedido);
					$pecaxx  = pg_result($res,$w,peca);

					//Verifica se está faturado, se esta embarcado devolve para estoque e cancela pedido para os itens da OS

					$sql = "SELECT DISTINCT 
							tbl_pedido.pedido,
							tbl_peca.peca,
							tbl_peca.descricao,
							tbl_peca.referencia,
							tbl_pedido_item.qtde,
							tbl_pedido_item.pedido_item,
							tbl_pedido.exportado,
							tbl_pedido.posto,
							tbl_os_item.os_item
						FROM tbl_pedido 
						JOIN tbl_pedido_item USING(pedido)
						JOIN tbl_peca        USING(peca)
						JOIN tbl_os_item     ON tbl_os_item.pedido        = tbl_pedido_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca
						JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						WHERE tbl_pedido.pedido       = $pedido
						AND   tbl_peca.fabrica        = $login_fabrica
						AND   tbl_os_produto.os       = $os
						AND   tbl_pedido_item.peca    = $pecaxx
						AND   tbl_pedido.distribuidor = 4311 ";
					$res_dis = @pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if (@pg_numrows($res_dis) > 0) {
						for($x=0;$x<@pg_numrows($res_dis);$x++){

							$pedido_pedido          = pg_result($res_dis,$x,pedido);
							$pedido_peca            = pg_result($res_dis,$x,peca);
							$pedido_item            = pg_result($res_dis,$x,pedido_item);
							$pedido_qtde            = pg_result($res_dis,$x,qtde);
							$pedido_peca_referencia = pg_result($res_dis,$x,referencia);
							$pedido_peca_descricao  = pg_result($res_dis,$x,descricao);
							$pedido_posto           = pg_result($res_dis,$x,posto);
							$pedido_os_item         = pg_result($res_dis,$x,os_item);

							if($pedido_posto==4311) $troca_distribuidor = "TRUE";

							$sql = "
								SELECT DISTINCT tbl_embarque.embarque
								FROM tbl_embarque 
								JOIN tbl_embarque_item USING(embarque)
								WHERE pedido_item = $pedido_item 
								AND   os_item     = $pedido_os_item 
								AND   faturar IS NOT NULL";

							$res_x1 = @pg_exec($con,$sql);
							$tem_faturamento = @pg_numrows($res_x1);
							if($tem_faturamento>0) {
								$troca_distribuidor = "TRUE";
								$troca_faturado     = "TRUE";
							}

							$pecas_canceladas .= "$pedido_peca_referencia - $pedido_peca_descricao ($pedido_qtde UN.),";

							$sql2 = "SELECT fn_pedido_cancela_garantia(4311,$login_fabrica,$pedido_pedido,$pedido_peca,$pedido_os_item,'Troca de Produto',$login_admin); ";

							$res_x2 = pg_exec($con,$sql2);

							$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>"; 
							$destinatario = "raphael@britania.com.br"; 

							$assunto      = "Troca - Cancelamento de Pedido de Peça do Fabricante"; 
							$mensagem     = "$os trocada";
							$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n"; 
							mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);

						}
					}
					//Cancela a peça que ainda não teve o seu pedido exportado //Raphael Giovanini
					$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde_cancelada + $qtde
						WHERE pedido = $pedido
						AND   pedido = tbl_pedido.pedido
						AND   peca   = $pecaxx
						AND   tbl_pedido.exportado IS NULL ;";
					$res3 = @pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}

			$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_produto AND fabrica = $login_fabrica" ;
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if(pg_numrows($res) > 0){
				$servico_realizado = pg_result($res,0,0);
			}
			if(strlen($servico_realizado)==0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!";

			if(strlen($msg_erro)==0){
				$sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin) VALUES ($os_produto, $peca, 1,$servico_realizado, $login_admin)";

				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				
				if(($login_fabrica == 3 or $login_fabrica==45 or $login_fabrica==35 OR $login_fabrica==25) AND pg_numrows($res)==1 ) {
					$sql = "UPDATE tbl_os SET 
							troca_garantia          = 't', 
							troca_garantia_admin    = $login_admin
							WHERE os = $os AND fabrica = $login_fabrica";
				}else{
					if($login_fabrica == 3){
						$sql = "UPDATE tbl_os SET 
							troca_garantia          = 't', 
							troca_garantia_admin    = $login_admin,
							data_conserto           = CURRENT_TIMESTAMP
							WHERE os = $os AND fabrica = $login_fabrica";
					}else{
						$sql = "UPDATE tbl_os SET 
							troca_garantia          = 't', 
							troca_garantia_admin    = $login_admin,
							data_fechamento         = CURRENT_DATE
							WHERE os = $os AND fabrica = $login_fabrica";
						}
				}
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_os_extra SET 
						obs_nf                     = '$observacao_pedido'
						WHERE os = $os;";

				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);


				if(strlen($troca_garantia_mao_obra) > 0 ){
					$sql = "UPDATE tbl_os SET mao_de_obra = $troca_garantia_mao_obra WHERE os = $os AND fabrica = $login_fabrica";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				$sql = "SELECT * FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NULL";
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);


				//--== Novo Procedimento para Troca | Raphael Giovanini ===========

				if( strlen($_POST["causa_troca"])          == 0 ) $msg_erro .= "Escolha a causa da troca";
				else                                              $causa_troca = $_POST["causa_troca"];
				if( strlen($_POST["setor"])                == 0 ) $msg_erro .= "Selecione o setor responsável";
				else                                              $setor = $_POST["setor"];
				if( strlen($_POST["situacao_atendimento"]) == 0 ) $msg_erro .= "<br>Selecione a situação do atendimento";
				else                                              $situacao_atendimento = $_POST["situacao_atendimento"];
				if( strlen($_POST["gerar_pedido"])         == 0 ) $gerar_pedido = "'f'";
				else                                              $gerar_pedido = "'t'";
				if($_POST["envio_consumidor"]=='t')               $envio_consumidor = " 't' ";
				else                                              $envio_consumidor = " 'f' ";


				$ri = $_POST["ri"];

				if (( $setor=='Procon' OR $setor=='SAP' ) AND(strlen($ri)=="null"))
					$msg_erro .= "<br>Obrigatório o preenchimento do RI";

				if( strlen($_POST["ri"])                   == 0 ) $ri = "null";
				else                                              $ri = "'".$_POST["ri"]."'";

				$modalidade_transporte = $_POST["modalidade_transporte"];
				if(strlen($modalidade_transporte)==0)$xmodalidade_transporte = "''";
				if($login_fabrica==3){
					if(strlen($modalidade_transporte)==0) $msg_erro = "É obrigatório a escolha da modalidade de transporte";
					else $xmodalidade_transporte = "'$modalidade_transporte'";
				}

				if(strlen($msg_erro) == 0 ){
					$sql = "INSERT INTO tbl_os_troca (
								setor               ,
								situacao_atendimento,
								os                  ,
								admin               ,
								peca                ,
								observacao          ,
								causa_troca         ,
								gerar_pedido        ,
								envio_consumidor    ,
								ri                  ,
								fabric              ,
								modalidade_transporte
							)VALUES(
								'$setor'             ,
								$situacao_atendimento,
								$os                  ,
								$login_admin         ,
								$peca                ,
								'$observacao_pedido' ,
								$causa_troca         ,
								$gerar_pedido        ,
								$envio_consumidor    ,
								$ri                  ,
								$login_fabrica       ,
								$xmodalidade_transporte
							)";
					$res = @pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				#Raphael
				if(strlen($msg_erro) == 0 ){
					if ($login_fabrica==25){
						$sql = "SELECT fn_pedido_troca($os,$login_fabrica)";
						$res = @pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}

				# HD 11631
				if ($login_fabrica==3 AND strlen($msg_erro)==0){
					 $sql = "INSERT INTO tbl_comunicado (
								descricao              ,
								mensagem               ,
								tipo                   ,
								fabrica                ,
								obrigatorio_os_produto ,
								obrigatorio_site       ,
								posto                  ,
								ativo                  
							) VALUES (
								'OS $sua_os - Troca de Produto',
								'A Fábrica irá fazer a troca do produto da OS $sua_os',
								'OS Troca de Produto',
								$login_fabrica,
								'f' ,
								't',
								$posto,
								't'
							);";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}
		
		if (strlen ($msg_erro) == 0) {
			if($login_fabrica<>3){//hd 18558 - OS troca não pode ser finalizada.
				$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");

		if($login_fabrica == 3 AND isset($troca_distribuidor)){
			$sql = "SELECT sua_os FROM tbl_os WHERE os=$os";
			$res = @pg_exec($con,$sql);
			$pr_sua_os = @pg_result($res,0,0);

			$remetente    = "Telecontrol <telecontrol@telecontrol.com.br>"; 
			$destinatario = "ronaldo@telecontrol.com.br"; 
			$destinatario2 = "raphael@telecontrol.com.br"; 
			$assunto      = "Troca - Cancelamento de Pedido de Peça do Distribuidor";
			if($troca_faturado<>'TRUE'){
				$mensagem_distribuidor =  "At. Responsável,<br><br>O produto $pr_referencia - $pr_descricao da <a href='http://posvenda.telecontrol.com.br/assist/os_press.php?os=$os'>OS $pr_sua_os</a> foi trocado pelo produto $troca_referencia - $troca_descricao
				<br>A(s) peça(s) $pecas_canceladas do pedido $pedido foram canceladas automaticamente pelo sistema de Troca<br>
				<br><br>Telecontrol Networking";
			}else{
				$mensagem_distribuidor =  "At. Responsável,<br><br>O produto $pr_referencia - $pr_descricao da <a href='http://posvenda.telecontrol.com.br/assist/os_press.php?os=$os'>OS $pr_sua_os</a> foi trocado pelo produto $troca_referencia - $troca_descricao
				<br>A(s) peça(s) $pecas_canceladas do pedido $pedido  nãoforam canceladas automaticamente pelo sistema de Troca, porque já foram enviadas para o posto<br>
				<br><br>Telecontrol Networking";

			}
	
			$headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n"; 
			if(strlen($mensagem_distribuidor)>0) mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem_distribuidor),$headers);
			if(strlen($mensagem_distribuidor)>0) mail($destinatario2, utf8_encode($assunto), utf8_encode($mensagem_distribuidor), $headers);
		}

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

		header("Location: $PHP_SELF?os=$os&ok=s");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}



/*======= <PHP> FUN?OES DOS BOT?ES DE A??O =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "continuar") {
	$msg_erro = "";

	$imprimir_os = $_POST["imprimir_os"];
	
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
			WHERE  sua_os = '$expSua_os[0]'
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
		$nome	= trim ($_POST['consumidor_nome']) ;

		if (strtoupper(trim($_POST['consumidor_revenda'])) == 'C') {
			if (strlen($estado) == 0 AND $login_fabrica != 7) {
				$msg_erro .= " Digite o estado do consumidor. <br>";
			}else{
				$estado = ' NULL ' ;
			}

			if (strlen($cidade) == 0 AND $login_fabrica != 7) {
				$msg_erro .= " Digite a cidade do consumidor. <br>";
			}else{
				$cidade = ' NULL ' ;
			}

			if (strlen($nome) == 0 AND $login_fabrica != 7)   {
				$msg_erro .= " Digite o nome do consumidor. <br>";
			}else{
				$nome = ' NULL ' ;
			}
		}


		if($login_fabrica==1){
			if (strlen(trim($_POST['fisica_juridica'])) == 0) {
				$msg_erro = "Escolha o Tipo Consumidor.<BR> ";
			}else{
				$xfisica_juridica = "'".($_POST['fisica_juridica'])."'";
			}
		}else{
			$xfisica_juridica = "null";
		}

		$cpf    = trim ($_POST['consumidor_cpf']) ;
		$cpf    = str_replace (".","",$cpf);
		$cpf    = str_replace ("-","",$cpf);
		$cpf    = str_replace ("/","",$cpf);
		$cpf    = str_replace (",","",$cpf);
		$cpf    = str_replace (" ","",$cpf);

		if (strlen($cpf) == 0) $xcpf = "null";
		else                   $xcpf = $cpf;

		/* retirado - hd 3552
		if ($xcpf <> "null" and strlen($xcpf) <> 11 and strlen ($xcpf) <> 14) {
			$msg_erro = 'Tamanho do CPF/CNPJ do cliente inv?lido';
		}
		*/

		if (strlen($xcpf) > 0 and $xcpf <> "null") $xcpf = "'" . $xcpf . "'";

		$rg     = trim ($_POST['consumidor_rg']) ;

		if (strlen($rg) == 0) $rg = "null";
		else                  $rg = "'" . $rg . "'";

		$fone			= trim ($_POST['consumidor_fone']) ;
		$fone_celular	= trim ($_POST['consumidor_celular']) ;
		$fone_comercial	= trim ($_POST['consumidor_fone_comercial']) ;

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

		// verifica se est? setado

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
		$consumidor_email       = trim ($_POST['consumidor_email']) ;

		// HD 18051
		if(strlen($consumidor_email) ==0 ){
			if($login_fabrica ==1){
				$msg_erro .="Digite o email de contato. <br>";
			}else{
				$consumidor_email = "";
			}
		}else{
			$consumidor_email = trim($_POST['consumidor_email']);
		}

	$tipo_atendimento = $_POST['tipo_atendimento'];

	if (strlen (trim ($tipo_atendimento)) == 0) {
		$tipo_atendimento = 'null';
		if ($login_fabrica == 7){
			$msg_erro .= " A natureza é obrigatória.";
		}
	}


	$segmento_atuacao = $_POST['segmento_atuacao'];
	if (strlen (trim ($segmento_atuacao)) == 0) $segmento_atuacao = 'null';

	if($tipo_atendimento=='15' or $tipo_atendimento=='16'){
		if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $msg_erro = 'Digite autorização cortesia.';
		else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
	}else{
		if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $autorizacao_cortesia = 'null';
		else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
	}

	//--==== OS de Instalção ============================================
	$km_auditoria = "FALSE";
	$sql = "SELECT tipo_atendimento,km_google
			FROM tbl_tipo_atendimento 
			WHERE tipo_atendimento = $tipo_atendimento";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$km_google = pg_result($res,0,km_google);

		if($km_google == 't'){
			$qtde_km  = str_replace (",",".",$_POST['distancia_km']); ;
			$qtde_km = number_format($qtde_km,3,'.','');
			$qtde_km2 = number_format($_POST['distancia_km_conferencia'],3,'.','') ;
			if($distancia_km_maps<>'maps' AND ($qtde_km <> $qtde_km2 AND $qtde_km > 0)){
				$km_auditoria = "TRUE";
			}else{
				//HD: 24813 - PARA
				if($login_fabrica ==50 AND $qtde_km> 50){
					$km_auditoria = "TRUE";
				}
				if($login_fabrica ==30 AND $qtde_km> 200){
					$km_auditoria = "TRUE";
				}
			}
		}else{
			if($login_fabrica <> 19) $qtde_produtos = 1;
		}
	}
	if(strlen($qtde_km)==0){
		$qtde_km = "NULL";
		$km_auditoria = "FALSE";
	}
	//$msg_erro = "$qtde_km $km_auditoria $qtde_km2";
	//--================================================================




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
	$consumidor_celular = $_POST['consumidor_celular'];
	$consumidor_fone_comercial = $_POST['consumidor_fone_comercial'];

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

	// HD 17851
	if($login_fabrica ==1 and strlen($revenda_cnpj) == 0){
		$msg_erro.="Digite o cnpj da revenda<br>";
	}
	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";

	// HD 17851
	if($login_fabrica ==1 and strlen($_POST['revenda_nome']) == 0){
		$msg_erro.="Digite o nome da revenda<br>";
	}

	
	$revenda_nome = str_replace ("'","",$_POST['revenda_nome']);
	$nota_fiscal  = $_POST['nota_fiscal'];

	if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";

	$data_nf      = trim($_POST['data_nf']);
	$data_nf      = fnc_formata_data_pg($data_nf);

	if ($data_nf == 'null' AND $xtroca_faturada <> 't' and $login_fabrica<>7 and $login_fabrica<>24) {
		$msg_erro .= " Digite a data de compra.";
	}

	$produto_referencia = strtoupper (trim ($_POST['produto_referencia']));
	//BOSCH -  regra: caso ele escolho um dois tipos de atendimento abaixo o produto vai ser  sempre os designados
	if($login_fabrica ==20){
		if($tipo_atendimento==11){    //garantia de pe?as
			$produto_referencia='0000002';
		}
		if($tipo_atendimento==12){    //garantia de acess?rios
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
		$admin_paga_mao_de_obra = 'f';
	$qtde_produtos           = strtoupper (trim ($_POST['qtde_produtos']));

	$aparencia_produto = strtoupper (trim ($_POST['aparencia_produto']));
	$acessorios        = strtoupper (trim ($_POST['acessorios']));

	$consumidor_revenda= str_replace ("'","",$_POST['consumidor_revenda']);

	$orientacao_sac    = trim ($_POST['orientacao_sac']);
	$orientacao_sac    = htmlentities ($orientacao_sac,ENT_QUOTES);
	$orientacao_sac    = nl2br ($orientacao_sac);

#	if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= "Tamanho do CPF/CNPJ do cliente inv?lido.";

#	if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= " Tamanho do CPF/CNPJ do cliente inv?lido.";

	if(strlen($posto)>0){
		$sql = "select pais from tbl_posto where posto =$posto";

		$res = pg_exec ($con,$sql) ;

		$pais = pg_result ($res, 0, pais);
	}

	/*IGOR HD 2935 - Quando pais for diferente de Brasil não tem CNPJ (bosch)*/
	if($pais == "BR"){
		if (strlen ($revenda_cnpj)   <> 0 and strlen ($revenda_cnpj)   <> 14) $msg_erro .= "Tamanho do CNPJ da revenda inválido.";
	}else{
		if (strlen ($revenda_cnpj)   == 0 )
			$msg_erro .= "Tamanho do CNPJ da revenda inválido.";
	}


	if (strlen ($produto_referencia) == 0) {
		if ($login_fabrica <> 7){
			$msg_erro .= " Digite o produto.";
		}
	}

	$xquem_abriu_chamado = trim($_POST['quem_abriu_chamado']);

	if (strlen($xquem_abriu_chamado) == 0) {
		$xquem_abriu_chamado = 'null';
		if ($login_fabrica == 7){
			$msg_erro .= "Digite quem abriu o Chamado.";
		}
	}else{
		$xquem_abriu_chamado = "'".$xquem_abriu_chamado."'";
	}

	$xobs = trim($_POST['obs']);
	if (strlen($xobs) == 0) $xobs = 'null';
	else                    $xobs = "'".$xobs."'";

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

	//HD 33095 13/08/2008
	if (strlen(trim($_POST['capacidade'])) == 0) $xproduto_capacidade = 'null';
	else                                         $xproduto_capacidade = "'".trim($_POST['capacidade'])."'";

	if (strlen(trim($_POST['divisao'])) == 0) $xdivisao = 'null';
	else                                      $xdivisao = "'".trim($_POST['divisao'])."'";

	$defeito_reclamado = trim ($_POST['defeito_reclamado']);

	if (strlen ($defeito_reclamado) == 0) {
		$defeito_reclamado = "null";
	}
	
	if (($login_fabrica ==35 or $login_fabrica==28) AND $defeito_reclamado == '0') {
		$msg_erro .= "Selecione o defeito reclamado.<BR>";
	}

	if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0){
		$xdefeito_reclamado_descricao = 'null';
	}else{
		$xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";
	}
	$defeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);
	
	if (($login_fabrica == 35 OR $login_fabrica==28 OR $login_fabrica==30 OR $login_fabrica==50) AND $pedir_defeito_reclamado_descricao == 't' AND ($defeito_reclamado_descricao == 'null' OR strlen($defeito_reclamado_descricao) == 0)){
		$msg_erro = "Digite o defeito reclamado.<BR>";
	}
	
	if (strlen (trim ($data_nf)) <> 12 and $login_fabrica<>7 and $login_fabrica<>24) {
		$data_nf = "null";
		$msg_erro .= " Digite a data de compra.";
	}

	if (strlen ($data_abertura) <> 12) {
		$msg_erro .= " Digite a data de abertura da OS.";
	}else{
		$cdata_abertura = str_replace("'","",$data_abertura);
	}
	
	if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";


	// se ? uma OS de revenda
	if ($fOsRevenda == 1){

		if (strlen ($nota_fiscal) == 0){
			$nota_fiscal = "null";
			//$msg_erro = "Entre com o n?mero da Nota Fiscal";	
		}else
			$nota_fiscal = "'" . $nota_fiscal . "'" ;

		if (strlen ($aparencia_produto) == 0)
			$aparencia_produto  = "null";
		else
			$aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen ($acessorios) == 0)
			$acessorios = "null";
		else
			$acessorios = "'" . $acessorios . "'" ;

		if (strlen($consumidor_revenda) == 0)
			$msg_erro .= " Selecione consumidor ou revenda.";
		else
			$xconsumidor_revenda = "'".$consumidor_revenda."'";

		if (strlen ($orientacao_sac) == 0)
			$orientacao_sac  = "null";
		else
			$orientacao_sac  = $orientacao_sac ;

	}else{
	
		if (strlen ($nota_fiscal) == 0 and $login_fabrica<>7 and $login_fabrica<>24){ 
			//$nota_fiscal = "null";
			$msg_erro = "Entre com o número da Nota Fiscal";
		}
		else
			$nota_fiscal = "'" . $nota_fiscal . "'" ;

		if (strlen ($aparencia_produto) == 0) 
			$aparencia_produto  = "null";
		else
			$aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen ($acessorios) == 0) 
			$acessorios = "null";
		else
			$acessorios = "'" . $acessorios . "'" ;

		if (strlen($consumidor_revenda) == 0)
			$msg_erro .= " Selecione consumidor ou revenda.";
		else
			$xconsumidor_revenda = "'".$consumidor_revenda."'";

		if (strlen ($orientacao_sac) == 0) 
			$orientacao_sac  = "null";
		else
			$orientacao_sac  =  $orientacao_sac  ;

	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$produto = 0;

	#HD 32668
	if (strlen($produto_referencia) > 0 OR $login_fabrica <> 7 ){
		$sql = "SELECT tbl_produto.produto
				FROM   tbl_produto
				JOIN   tbl_linha USING (linha)
				WHERE  UPPER (tbl_produto.referencia_pesquisa) = UPPER ('$produto_referencia')
				AND    tbl_linha.fabrica      = $login_fabrica ";

			// IGOR HD 3576 - Se a OS estiver cadastrada, permitir o pedido de peça.
			if($login_fabrica == 3 and strlen($os)> 0) {
				//echo "teste";
				//$msg_erro = "";
			}else{
				$sql .= "AND    tbl_produto.ativo IS TRUE";
			}
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 0) {
				if($login_fabrica == 3 and strlen($os)> 0) {
				//echo "teste";
				//$msg_erro = "";
			}else{
				if (strlen($produto_referencia)>0){
					$msg_erro = "Produto $produto_referencia não cadastrado";
				}else{
					$produto = " null ";
				}
			}
		}else{
			$produto = @pg_result ($res,0,0);
		}
	}else{
		$produto = " null ";
	}
	

	if ($xtroca_faturada <> "'t'") { // verifica troca faturada para a Black

		// se não é uma OS de revenda, entra
		if ($fOsRevenda == 0){
			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
			
			$res = @pg_exec ($con,$sql);
			
			if (@pg_numrows ($res) == 0) {
				//HD 3576 - Validar o produto somente na abertura da OS
				if($login_fabrica == 3 and strlen($os)> 0) {
					//$msg_erro = "";
				}else{
					if ($login_fabrica <> 7){
						$msg_erro = "Produto $produto_referencia sem garantia";
					}
				}
			}else{
				$garantia = trim(@pg_result($res,0,garantia));
			}
			
			if (strlen($garantia)>0){
				$sql = "SELECT ($data_nf::date + (($garantia || ' months')::interval))::date;";
				$res = @pg_exec ($con,$sql);
				
				if (@pg_numrows ($res) > 0) {
					$data_final_garantia = trim(pg_result($res,0,0));
				}
				// HD 23616
				if ($login_fabrica <> 3 and $login_fabrica <> 7 AND $login_fabrica <> 11 AND $login_fabrica <> 24  and $login_fabrica <> 6 and $login_fabrica <> 35  AND $login_fabrica <> 30) {
					if ($data_final_garantia < $cdata_abertura) {
						$msg_erro = "[ $data_nf ] - [ $data_final_garantia ] = [ $cdata_abertura ] Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
					}
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
	
	##### Verifica??o se o n? de s?rie ? reincidente para a Tectoy #####
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

	##### Verifica??o se o n? de s?rie ? reincidente para a Brit?nia #####
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

	if (strlen ($msg_erro) == 0 ) {


		if (strlen ($os) == 0) {
		/*================ INSERE NOVA OS =========================*/
			$sql = "INSERT INTO tbl_os (
						tipo_atendimento   ,
						segmento_atuacao   ,
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
						consumidor_celular ,
						consumidor_fone_comercial ,
						consumidor_email   ,
						revenda_cnpj       ,
						revenda_nome       ,
						nota_fiscal        ,
						data_nf            ,
						produto            ,
						serie              ,
						qtde_produtos      ,
						aparencia_produto  ,
						acessorios         ,
						defeito_reclamado_descricao,
						defeito_reclamado  ,
						obs                ,
						quem_abriu_chamado ,
						consumidor_revenda ,
						troca_faturada     ,
						os_reincidente     ,
						qtde_km            ,
						autorizacao_cortesia,
						capacidade         ,
						divisao           ";
			
			if ($login_fabrica == 1) {
				$sql .=	",codigo_fabricacao ,
						satisfacao          ,
						tipo_os             ,
						laudo_tecnico       ,
						fisica_juridica";
			}
			
			$sql .= ") VALUES (
						$tipo_atendimento                                               ,
						$segmento_atuacao                                               ,
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
						trim ('$consumidor_celular')                                    ,
						trim ('$consumidor_fone_comercial')                             ,
						trim ('$consumidor_email')                                      ,
						trim ('$revenda_cnpj')                                          ,
						trim ('$revenda_nome')                                          ,
						trim ($nota_fiscal)                                             ,
						$data_nf                                                        ,
						$produto                                                        ,
						'$produto_serie'                                                ,
						$qtde_produtos                                                  ,
						trim ($aparencia_produto)                                       ,
						trim ($acessorios)                                              ,
						$xdefeito_reclamado_descricao                                   ,
						$defeito_reclamado                                              ,
						$xobs                                                           ,
						$xquem_abriu_chamado                                            ,
						'$consumidor_revenda'                                           ,
						$xtroca_faturada                                                ,
						$os_reincidente                                                 ,
						$qtde_km                                                        ,
						$autorizacao_cortesia                                           ,
						$xproduto_capacidade                                            ,
						$xdivisao                                                      ";

			if ($login_fabrica == 1) {
				$sql .= ", $codigo_fabricacao ,
						'$satisfacao'         ,
						$xtipo_os_compressor  ,
						$laudo_tecnico        ,
						$xfisica_juridica";
			}

			$sql .= ");";
//if ($ip == "201.0.9.216") { echo nl2br($sql); exit; }

		}else{
			//hd17966
			if($login_fabrica==45){
				$sql = "SELECT finalizada,data_fechamento 
						FROM   tbl_os
						JOIN   tbl_os_extra USING(os)
						WHERE  fabrica = $login_fabrica
						AND    os      = $os
						AND    extrato         IS     NULL
						AND    finalizada      IS NOT NULL
						AND    data_fechamento IS NOT NULL";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)>0){
					$voltar_finalizada = pg_result($res,0,0);
					$voltar_fechamento = pg_result($res,0,1);
					$sql = "UPDATE tbl_os SET data_fechamento = NULL , finalizada = NULL 
							WHERE os      = $os
							AND   fabrica = $login_fabrica";
					$res = pg_exec ($con,$sql);
				}
			}
			/*================ ALTERA OS =========================*/
			$sql = "UPDATE tbl_os SET
						tipo_atendimento   = $tipo_atendimento           ,
						segmento_atuacao   = $segmento_atuacao           ,
						posto              = $posto                      ,";
			if($login_fabrica<>3 and $login_fabrica<>6 and $login_fabrica<>11 and $login_fabrica<>24){//TAKASHI 01-11 - Angelica informou que OS aberta pelo posto paga um valor, os pelo admin outro valor. Qdo o admin atualiza qualquer informa??o grava o admin e na hora de calcular calcula como se fosse uma os de admin 
				$sql .=" admin              = $login_admin                ,";
			}
				$sql .=" fabrica            = $login_fabrica              ,
						sua_os             = trim($sua_os)               ,
						data_abertura      = $data_abertura              ,
						consumidor_nome    = trim('$consumidor_nome')    ,
						consumidor_cpf     = trim('$consumidor_cpf')     ,
						consumidor_fone    = trim('$consumidor_fone')    ,
						consumidor_celular = trim('$consumidor_celular')    ,
						consumidor_fone_comercial = trim('$consumidor_fone_comercial') ,
						consumidor_endereco= trim('$consumidor_endereco'),
						consumidor_numero  = trim('$consumidor_numero'),
						consumidor_complemento= trim('$consumidor_complemento'),
						consumidor_bairro  = trim('$consumidor_bairro'),
						consumidor_cep     = trim('$consumidor_cep'),
						consumidor_estado  = trim('$consumidor_estado'),
						consumidor_cidade  = trim('$consumidor_cidade'),
						consumidor_email   = trim('$consumidor_email') ,
						cliente            = (SELECT cliente FROM tbl_cliente WHERE cpf  = $xconsumidor_cpf) ,
						revenda_cnpj       = trim('$revenda_cnpj')       ,
						revenda_nome       = trim('$revenda_nome')       ,
						nota_fiscal        = trim($nota_fiscal)          ,
						data_nf            = $data_nf                    ,
						produto            = $produto                    ,
						serie              = '$produto_serie'            ,
						qtde_produtos      = $qtde_produtos              ,
						aparencia_produto  = trim($aparencia_produto)    ,
						acessorios         = trim($acessorios)           ,
						defeito_reclamado_descricao = $xdefeito_reclamado_descricao,
						defeito_reclamado  = $defeito_reclamado          ,
						quem_abriu_chamado = $xquem_abriu_chamado        ,
						obs                = $xobs                     ,
						consumidor_revenda = '$consumidor_revenda'       ,
						troca_faturada     = $xtroca_faturada            ,
						os_reincidente     = $os_reincidente             ,
						autorizacao_cortesia = $autorizacao_cortesia     ,
						qtde_km            = $qtde_km                    ,
						capacidade         = $xproduto_capacidade              ,
						divisao            = $xdivisao                         ,
						revenda            = (SELECT revenda FROM tbl_revenda WHERE cnpj = trim('$revenda_cnpj') limit 1 )";

			
			if ($login_fabrica == 1) {
				$sql .=	", codigo_fabricacao = $codigo_fabricacao ,
						satisfacao           = '$satisfacao'      ,
						tipo_os              = $xtipo_os_compressor,
						laudo_tecnico        = $laudo_tecnico     ,
						fisica_juridica      = $xfisica_juridica";
			}

			$sql .= " WHERE os      = $os
					AND   fabrica = $login_fabrica";
		}
// $msg_debug = "<br>".$sql."<br>";
//echo nl2br($sql); 

		$res = pg_exec ($con,$sql);
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
				$msg_erro = pg_errormessage($con);
			}

			if(strlen($msg_erro)==0){
				//HD 23041 - Rotina de vários defeitos para uma única OS.
				if($login_fabrica==19) {
					// HD 28155
					if ($tipo_atendimento <> 6){
						$numero_vezes = 100;
						$array_integridade = array();
						for ($i=0;$i<$numero_vezes;$i++) {
							$int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);
							if ( $i <> $int_reclamado and strlen($int_reclamado) >0){
								array_push($array_integridade,$int_reclamado);
							}
							if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
							if (strlen($int_reclamado)==0) continue;

							$aux_defeito_reclamado = $int_reclamado;

					

							$sql = "SELECT defeito_constatado_reclamado
									FROM tbl_os_defeito_reclamado_constatado
									WHERE os                = $os
									AND   defeito_reclamado = $aux_defeito_reclamado";
							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							if(@pg_numrows($res)==0){
								$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
											os,
											defeito_reclamado,
											fabrica
										)VALUES(
											$os,
											$aux_defeito_reclamado,
											$login_fabrica
										)
								";
								$res = @pg_exec ($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}
						}
						
						// HD 33303
						$lista_defeitos = implode($array_integridade,",");
						$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado 
								WHERE os = $os
								AND   defeito_reclamado NOT IN ($lista_defeitos) ";
						$res = @pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
						//o defeito reclamado recebe o primeiro defeito constatado.
						//verifica se já tem defeito cadastrado 

						$sqld = "SELECT * 
							FROM tbl_os_defeito_reclamado_constatado 
							WHERE os = $os";
						$res = @pg_exec ($con,$sqld);
						$msg_erro .= pg_errormessage($con);
						
						if(@pg_numrows($res)==0){
							$msg_erro = "Quando lançar o defeito reclamado é necessário clicar em adicionar defeito.";
						}
					}
					if ($tipo_atendimento == 6 and $defeito_reclamado <> 0){
						$numero_vezes = 100;
						$array_integridade = array();
						for ($i=0;$i<$numero_vezes;$i++) {
							$int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);
							if ( $i <> $int_reclamado and strlen($int_reclamado) >0){
								array_push($array_integridade,$int_reclamado);
							}
							if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
							if (strlen($int_reclamado)==0) continue;

							$aux_defeito_reclamado = $int_reclamado;

							$sql = "SELECT defeito_constatado_reclamado
									FROM tbl_os_defeito_reclamado_constatado
									WHERE os                = $os
									AND   defeito_reclamado = $aux_defeito_reclamado";
							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							if(@pg_numrows($res)==0){
								$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
											os,
											defeito_reclamado,
											fabrica
										)VALUES(
											$os,
											$aux_defeito_reclamado,
											$login_fabrica
										)
								";
								$res = @pg_exec ($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}
						}
						// HD 33303
						$lista_defeitos = implode($array_integridade,",");
						$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado 
								WHERE os = $os
								AND   defeito_reclamado NOT IN ($lista_defeitos) ";
						$res = @pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);

						//o defeito reclamado recebe o primeiro defeito constatado.
						//verifica se já tem defeito cadastrado 
						$sqld = "SELECT * 
							FROM tbl_os_defeito_reclamado_constatado 
							WHERE os = $os";
						$res = @pg_exec ($con,$sqld);
						$msg_erro .= pg_errormessage($con);
						
						if(@pg_numrows($res)==0){
							$msg_erro = "Quando lançar o defeito reclamado é necessário clicar em adicionar defeito.";
						}
					}
				}
			}

			if (strlen ($msg_erro) == 0) {
				$sql      = "SELECT fn_valida_os($os, $login_fabrica)";
				$res      = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			#--------- grava OS_EXTRA ------------------
			if (strlen ($msg_erro) == 0) {
				$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
				$visita_por_km				= trim ($_POST['visita_por_km']);
				$valor_por_km				= str_replace (",",".",trim ($_POST['valor_por_km']));

				$hora_tecnica				= str_replace (",",".",trim ($_POST['hora_tecnica']));

				$regulagem_peso_padrao		= str_replace (".","",trim ($_POST['regulagem_peso_padrao']));
				$regulagem_peso_padrao		= str_replace (",",".",$regulagem_peso_padrao);

				$certificado_conformidade	= str_replace (".","",trim ($_POST['certificado_conformidade']));
				$certificado_conformidade	= str_replace (",",".",$certificado_conformidade);

				$valor_diaria				= str_replace (".","",trim ($_POST['valor_diaria']));
				$valor_diaria				= str_replace (",",".",$valor_diaria);

				$condicao					= trim ($_POST['condicao']);

				if(strlen($condicao)==0){
					$xcondicao = 'null';
					$xtabela   = 'null';
				}else{
					$xcondicao = $condicao;

					$sql = "SELECT tabela 
							FROM tbl_condicao 
							WHERE fabrica = $login_fabrica 
							AND condicao = $condicao; ";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
					
					if (pg_numrows($res) > 0) {
						$xtabela = pg_result($res,0,tabela);
					}
					if (strlen($xtabela)==0){
						$xtabela = "null";
					}
				}
				
				if (strlen ($taxa_visita)				== 0) $taxa_visita					= '0';
				if (strlen ($visita_por_km)				== 0) $visita_por_km				= 'f';
				if (strlen ($valor_por_km)				== 0) $valor_por_km					= '0';
				if (strlen ($hora_tecnica)				== 0) $hora_tecnica					= '0';
				if (strlen ($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
				if (strlen ($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
				if (strlen ($valor_diaria)				== 0) $valor_diaria					= '0';
				
				$cobrar_deslocamento		= trim ($_POST['cobrar_deslocamento']);
				$cobrar_hora_diaria			= trim ($_POST['cobrar_hora_diaria']);

				$desconto_deslocamento		= str_replace (",",".",trim ($_POST['desconto_deslocamento']));
				$desconto_hora_tecnica		= str_replace (",",".",trim ($_POST['desconto_hora_tecnica']));
				$desconto_diaria			= str_replace (",",".",trim ($_POST['desconto_diaria']));
				$desconto_regulagem			= str_replace (",",".",trim ($_POST['desconto_regulagem']));
				$desconto_certificado		= str_replace (",",".",trim ($_POST['desconto_certificado']));
				$desconto_peca				= str_replace (",",".",trim ($_POST['desconto_peca']));

				$cobrar_regulagem			= trim ($_POST['cobrar_regulagem']);
				$cobrar_certificado			= trim ($_POST['cobrar_certificado']);
				
				$sqlt ="SELECT tipo_posto, consumidor_revenda, os_numero
						FROM tbl_os
						JOIN tbl_posto_fabrica USING(posto)
						WHERE tbl_os.os = $os
						AND   tbl_posto_fabrica.fabrica = $login_fabrica";
				$rest = pg_exec($con,$sqlt);
				$tipo_posto         = pg_result($rest,0,tipo_posto);
				$consumidor_revenda = pg_result($rest,0,consumidor_revenda);
				$os_numero          = pg_result($rest,0,os_numero);

				if (strtoupper($consumidor_revenda) == 'R' and $login_fabrica == 7){
					$os_manutencao = 't';
				}

				if ($tipo_posto == 215 or $tipo_posto == 214){
					if ($desconto_deslocamento>7){
						$msg_erro .= "O desconto máximo permitido para deslocamento é 7%.<br>";
					}
					if ($desconto_hora_tecnica>7){
						$msg_erro .= "O desconto máximo permitido para hora técnica é 7%.<br>";
					}
					if ($desconto_diaria>7){
						$msg_erro .= "O desconto máximo permitido para diára é 7%.<br>";
					}
					if ($desconto_regulagem>7){
						$msg_erro .= "O desconto máximo permitido para regulagem é 7%.<br>";
					}
					if ($desconto_certificado>7){
						$msg_erro .= "O desconto máximo permitido para o certificado é 7%.<br>";
					}
				}

				if (strlen($veiculo)==0){
					$xveiculo = "NULL";
				}else{
					$xveiculo = "'$veiculo'";
					if ($veiculo == 'carro'){
						$valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_carro']));
					}
					if ($veiculo == 'caminhao'){
						$valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_caminhao']));
					}
				}

				if (strlen($valor_por_km)>0){
					$xvalor_por_km = $valor_por_km;
					$xvisita_por_km = "'t'";
				}else{
					$xvalor_por_km = "0";
					$xvisita_por_km = "'f'";
				}

				if (strlen($taxa_visita)>0){
					$xtaxa_visita = $taxa_visita;
				}else{
					$xtaxa_visita = '0';
				}

				/* HD 29838 */
				if ($tipo_atendimento == 63){
					$cobrar_deslocamento = 'isento';
				}

				if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento)==0){
					$xvisita_por_km = "'f'";
					$xvalor_por_km = "0";
					$xtaxa_visita = '0';
					$xveiculo = "NULL";
				}elseif ($cobrar_deslocamento == 'valor_por_km'){
					$xvisita_por_km = "'t'";
					$xtaxa_visita = '0';
				}elseif ($cobrar_deslocamento == 'taxa_visita'){
					$xvisita_por_km = "'f'";
					$xvalor_por_km = "0";
				}

				if(strlen($valor_diaria) > 0){
					$xvalor_diaria = $valor_diaria;
				}else{
					$xvalor_diaria = '0';
				}

				if(strlen($hora_tecnica) > 0){
					$xhora_tecnica = $hora_tecnica;
				}else{
					$xhora_tecnica = '0';
				}

				if ($cobrar_hora_diaria == 'isento' OR strlen($cobrar_hora_diaria)==0){
					$xhora_tecnica = '0';
					$xvalor_diaria = '0';
				}elseif ($cobrar_hora_diaria == 'diaria'){
					$xhora_tecnica = '0';
				}elseif ($cobrar_hora_diaria == 'hora'){
					$xvalor_diaria = '0';
				}

				if(strlen($regulagem_peso_padrao) > 0 and $cobrar_regulagem == 't'){
					$xregulagem_peso_padrao = $regulagem_peso_padrao;
				}else{
					$xregulagem_peso_padrao = '0';
				}
				
				if(strlen($certificado_conformidade) > 0 and $cobrar_certificado == 't'){
					$xcertificado_conformidade = $certificado_conformidade;
				}else{
					$xcertificado_conformidade = "0";
				}

				/* Descontos */
				if(strlen($desconto_deslocamento) > 0){
					$desconto_deslocamento = $desconto_deslocamento;
				}else{
					$desconto_deslocamento = '0';
				}

				if(strlen($desconto_hora_tecnica) > 0){
					$desconto_hora_tecnica = $desconto_hora_tecnica;
				}else{
					$desconto_hora_tecnica = '0';
				}

				if(strlen($desconto_diaria) > 0){
					$desconto_diaria = $desconto_diaria;
				}else{
					$desconto_diaria = '0';
				}

				if(strlen($desconto_regulagem) > 0){
					$desconto_regulagem = $desconto_regulagem;
				}else{
					$desconto_regulagem = '0';
				}

				if(strlen($desconto_certificado) > 0){
					$desconto_certificado = $desconto_certificado;
				}else{
					$desconto_certificado = '0';
				}

				if(strlen($desconto_peca) > 0){
					$desconto_peca = $desconto_peca;
				}else{
					$desconto_peca = '0';
				}

				//hd 11083 7/1/2008
				if($login_fabrica == 3){
					if (strlen(trim($orientacao_sac))>0 AND trim($orientacao_sac)!='null' ){
						$orientacao_sac =  date("d/m/Y H:i")." - ".$orientacao_sac; 
						$sql = "UPDATE  tbl_os_extra SET
							orientacao_sac          =  CASE WHEN orientacao_sac IS NULL OR orientacao_sac = 'null' THEN '' ELSE orientacao_sac || ' \n' END || trim('$orientacao_sac') ,
							taxa_visita              = $xtaxa_visita                               ,
							visita_por_km            = $xvisita_por_km                             ,
							hora_tecnica             = $xhora_tecnica                              ,
							regulagem_peso_padrao    = $xregulagem_peso_padrao                     ,
							certificado_conformidade = $xcertificado_conformidade                  ,
							valor_diaria             = $xvalor_diaria                              ,
							admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra' ";
					}
				}else{
					$sql = "UPDATE  tbl_os_extra SET
						orientacao_sac           = trim('$orientacao_sac')    ,
						admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra'  ";
				}

				if ($os_reincidente == "'t'") {
					$sql .= ", os_reincidente = $xxxos ";
				}
				
				$sql .= "WHERE tbl_os_extra.os = $os";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if ($login_fabrica <> 3){

					if( $login_fabrica == 7 and strlen($condicao)>0) {
						$sql = "UPDATE tbl_os SET 
										condicao = $condicao
								WHERE os      = $os
								AND   fabrica = $login_fabrica";
						$res = pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

					if ($os_manutencao == 't' and strlen($os_numero)>0){
						$sql = "UPDATE tbl_os_revenda SET 
									condicao = $condicao
								WHERE os_revenda = $os_numero ";
						$res = pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);

						$sql = "UPDATE tbl_os SET 
									condicao = $xcondicao,
									tabela   = $xtabela
								WHERE os_numero  = $os_numero 

								AND   fabrica    = $login_fabrica";
						$res = pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					/* ATUALIZACAO: Outros Serviços */
					$sql = "UPDATE tbl_os_extra SET
									certificado_conformidade    = $xcertificado_conformidade,
									desconto_certificado        = $desconto_certificado,
									desconto_peca               = $desconto_peca
							WHERE os = $os ";
					$res = @pg_exec($con,$sql);
					$msg_erro = @pg_errormessage($con);

					/* ATUALIZACAO: Deslocamento e Mao de Obra (do técnico) */
					if ($os_manutencao == 't'){
						$sql = "UPDATE tbl_os_revenda SET

									/* DESLOCAMENTO */
									taxa_visita                 = $xtaxa_visita,
									visita_por_km               = $xvisita_por_km,
									valor_por_km                = $xvalor_por_km,
									veiculo                     = $xveiculo,

									/* MAO-DE-OBRA */
									hora_tecnica                = $xhora_tecnica,
									valor_diaria                = $xvalor_diaria,

									/* OUTROS SERVIÇOS */
									regulagem_peso_padrao       = $xregulagem_peso_padrao,
									/*desconto_regulagem        = $desconto_regulagem, (nao é usado mais desconto, se precisar de desconto tem que criar o campo)*/

									/* DESCONTOS */
									desconto_deslocamento       = $desconto_deslocamento,
									desconto_hora_tecnica       = $desconto_hora_tecnica,
									desconto_diaria             = $desconto_diaria

								WHERE os_revenda = $os_numero ";
					}else{
						$sql = "UPDATE tbl_os_extra SET

									/* DESLOCAMENTO */
									taxa_visita                 = $xtaxa_visita,
									visita_por_km               = $xvisita_por_km,
									valor_por_km                = $xvalor_por_km,
									veiculo                     = $xveiculo,

									/* MAO-DE-OBRA */
									hora_tecnica                = $xhora_tecnica,
									valor_diaria                = $xvalor_diaria,

									/* OUTROS SERVIÇOS */
									regulagem_peso_padrao       = $xregulagem_peso_padrao,
									desconto_regulagem          = $desconto_regulagem,

									/* DESCONTOS */
									desconto_deslocamento       = $desconto_deslocamento,
									desconto_hora_tecnica       = $desconto_hora_tecnica,
									desconto_diaria             = $desconto_diaria
								WHERE os = $os ";
					}
					$res = @pg_exec($con,$sql);
					$msg_erro = @pg_errormessage($con);
				}

				if($login_fabrica==45 and strlen($voltar_fechamento)>0 AND strlen($voltar_finalizada)>0) {
					$sql = "UPDATE tbl_os SET data_fechamento = '$voltar_fechamento' , finalizada = '$voltar_finalizada' 
							WHERE os      = $os
							AND   fabrica = $login_fabrica";
					$res = pg_exec ($con,$sql);
				}
				// HD 23217
				if(strlen($msg_erro) ==0 AND $login_fabrica==1){
					if(strlen($os) >0){
						$sql="SELECT fn_valida_os_reincidente ($os,$login_fabrica)";
						$res = pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}

				// HD 32726
				if(strlen($msg_erro) ==0 AND $login_fabrica==7){
					if(strlen($os) >0){
						$sql="SELECT fn_calcula_os_filizola ($os,$login_fabrica)";
						$res = pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}

				if (strlen ($msg_erro) == 0) {
					$res = pg_exec ($con,"COMMIT TRANSACTION");

					if ($imprimir_os == "imprimir") {
						header ("Location: os_item.php?os=$os&imprimir=1");
						exit;
					}

					if ($login_fabrica == 7){
						#HD 25608
						$sql = "SELECT os
								FROM tbl_os
								WHERE fabrica = $login_fabrica
								AND   os       = $os
								AND   produto IS NULL";
						$res = pg_exec ($con,$sql);
						if (pg_numrows($res) > 0) {
							header ("Location: os_press.php?os=$os");
							exit;
						}
					}
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
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitaÃ§Ã£o da OS no sistema (data de hoje).";

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
			#158147 Paulo/Waldir desmarcar se for reincidente
			$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
			$res = pg_exec($con, $sql);
			$res = @pg_exec ($con,$sql);
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

	$sql = "SELECT  tbl_os.os                                           ,
					tbl_os.tipo_atendimento                                     ,
					tbl_os.segmento_atuacao                                     ,
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
					tbl_os.consumidor_cpf                                       ,
					tbl_os.consumidor_fone                                      ,
					tbl_os.consumidor_celular                                   ,
					tbl_os.consumidor_fone_comercial                            ,
					tbl_os.consumidor_cidade                                    ,
					tbl_os.consumidor_estado                                    ,
					tbl_os.consumidor_cep                                       ,
					tbl_os.consumidor_endereco                                  ,
					tbl_os.consumidor_numero                                    ,
					tbl_os.consumidor_complemento                               ,
					tbl_os.consumidor_bairro                                    ,
					tbl_os.consumidor_email                                     ,
					tbl_os.revenda                                              ,
					tbl_os.revenda_cnpj                                         ,
					tbl_os.revenda_nome                                         ,
					tbl_os.nota_fiscal                                          ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
					tbl_os.aparencia_produto                                    ,
					tbl_os_extra.orientacao_sac                                 ,
					tbl_os_extra.admin_paga_mao_de_obra                         ,
					tbl_os_extra.obs_nf                     AS observacao_pedido,
					tbl_os.acessorios                                           ,
					tbl_os.fabrica                                              ,
					tbl_os.quem_abriu_chamado                                   ,
					tbl_os.obs                                                  ,
					tbl_os.consumidor_revenda                                   ,
					tbl_os.condicao                                             ,
					tbl_os_extra.extrato                                        ,
					tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
					tbl_posto_fabrica.contato_endereco       AS contato_endereco,
					tbl_posto_fabrica.contato_numero           AS contato_numero,
					tbl_posto_fabrica.contato_bairro           AS contato_bairro,
					tbl_posto_fabrica.contato_cidade           AS contato_cidade,
					tbl_posto_fabrica.contato_estado           AS contato_estado,
					tbl_os.codigo_fabricacao                                    ,
					tbl_os.satisfacao                                           ,
					tbl_os.laudo_tecnico                                        ,
					tbl_os.troca_faturada                                       ,
					tbl_os.admin                                                ,
					tbl_os.troca_garantia                                       ,
					tbl_os. autorizacao_cortesia                                ,
					tbl_os.defeito_reclamado                                    ,
					tbl_os.defeito_reclamado_descricao                          ,
					tbl_os.fisica_juridica                                      ,
					tbl_os.quem_abriu_chamado                                   ,
					tbl_os.capacidade                 AS produto_capacidade     ,
					tbl_os.versao                     AS versao                 ,
					tbl_os.divisao                    AS divisao                ,
					tbl_os_extra.taxa_visita                                    ,
					tbl_os_extra.visita_por_km                                  ,
					tbl_os_extra.valor_por_km                                   ,
					tbl_os_extra.hora_tecnica                                   ,
					tbl_os_extra.regulagem_peso_padrao                          ,
					tbl_os_extra.certificado_conformidade                       ,
					tbl_os_extra.valor_diaria                                   ,
					tbl_os_extra.veiculo                                        ,
					tbl_os_extra.desconto_deslocamento                          ,
					tbl_os_extra.desconto_hora_tecnica                          ,
					tbl_os_extra.desconto_diaria                                ,
					tbl_os_extra.desconto_regulagem                             ,
					tbl_os_extra.desconto_certificado                           ,
					tbl_os_extra.desconto_peca
			FROM	tbl_os
			LEFT JOIN	tbl_produto          ON tbl_produto.produto       = tbl_os.produto
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
		$os                              = pg_result ($res,0,os);
		$tipo_atendimento                = pg_result ($res,0,tipo_atendimento);
		$segmento_atuacao                = pg_result ($res,0,segmento_atuacao);
		$posto                           = pg_result ($res,0,posto);
		$posto_nome                      = pg_result ($res,0,posto_nome);
		$sua_os                          = pg_result ($res,0,sua_os);
		$data_abertura                   = pg_result ($res,0,data_abertura);
		$produto_referencia              = pg_result ($res,0,referencia);
		$produto_descricao               = pg_result ($res,0,descricao);
		$produto_serie                   = pg_result ($res,0,serie);
		$qtde_produtos                   = pg_result ($res,0,qtde_produtos);
		$cliente                         = pg_result ($res,0,cliente);
		$consumidor_nome                 = pg_result ($res,0,consumidor_nome);
		$consumidor_cpf                  = pg_result ($res,0,consumidor_cpf);
		$consumidor_fone                 = pg_result ($res,0,consumidor_fone);
		$consumidor_celular              = pg_result ($res,0,consumidor_celular);//15091
		$consumidor_fone_comercial       = pg_result ($res,0,consumidor_fone_comercial);
		$consumidor_cep                  = trim (pg_result ($res,0,consumidor_cep));
		$consumidor_endereco             = trim (pg_result ($res,0,consumidor_endereco));
		$consumidor_numero               = trim (pg_result ($res,0,consumidor_numero));
		$consumidor_complemento          = trim (pg_result ($res,0,consumidor_complemento));
		$consumidor_bairro               = trim (pg_result ($res,0,consumidor_bairro));
		$consumidor_cidade               = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado               = pg_result ($res,0,consumidor_estado);
		$consumidor_email                = pg_result ($res,0,consumidor_email);
		$fisica_juridica                 = pg_result ($res,0,fisica_juridica);

		$revenda                     = pg_result ($res,0,revenda);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$fabrica                     = pg_result ($res,0,fabrica);
		$posto_codigo                = pg_result ($res,0,posto_codigo);
		/*DADOS DO POSTO PARA CALCULO KM*/
		$contato_endereco             = trim (pg_result ($res,0,contato_endereco));
		$contato_numero               = trim (pg_result ($res,0,contato_numero));
		$contato_bairro               = trim (pg_result ($res,0,contato_bairro));
		$contato_cidade               = pg_result ($res,0,contato_cidade);
		$contato_estado               = pg_result ($res,0,contato_estado);

		$condicao                    = pg_result ($res,0,condicao);
		$extrato                     = pg_result ($res,0,extrato);
		$quem_abriu_chamado          = pg_result ($res,0,quem_abriu_chamado);
		$obs                         = pg_result ($res,0,obs);
		$observacao_pedido           = pg_result ($res,0,observacao_pedido);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
		$satisfacao                  = pg_result ($res,0,satisfacao);
		$laudo_tecnico               = pg_result ($res,0,laudo_tecnico);
		$troca_faturada              = pg_result ($res,0,troca_faturada);
		$troca_garantia              = pg_result ($res,0,troca_garantia);
		$admin_os                    = trim(pg_result ($res,0,admin));
		$autorizacao_cortesia        = pg_result ($res,0, autorizacao_cortesia); 
		
		$versao				= pg_result ($res,0,versao);
		$divisao			= pg_result ($res,0,divisao);
		$produto_capacidade	= pg_result ($res,0,produto_capacidade);
		$taxa_visita		= pg_result ($res,0,taxa_visita);
		$visita_por_km		= pg_result ($res,0,visita_por_km);
		$valor_por_km		= pg_result ($res,0,valor_por_km);
		$hora_tecnica		= pg_result ($res,0,hora_tecnica);
		$regulagem_peso_padrao	= pg_result ($res,0,regulagem_peso_padrao);
		$certificado_conformidade= pg_result ($res,0,certificado_conformidade);
		$valor_diaria			= pg_result ($res,0,valor_diaria);
		$veiculo				= pg_result ($res,0,veiculo);
		$desconto_deslocamento	= pg_result ($res,0,desconto_deslocamento);
		$desconto_hora_tecnica	= pg_result ($res,0,desconto_hora_tecnica);
		$desconto_diaria		= pg_result ($res,0,desconto_diaria);
		$desconto_regulagem		= pg_result ($res,0,desconto_regulagem);
		$desconto_certificado	= pg_result ($res,0,desconto_certificado);
		$desconto_peca			= pg_result ($res,0,desconto_peca);

		$orientacao_sac	= pg_result ($res,0,orientacao_sac);
		$orientacao_sac = html_entity_decode ($orientacao_sac,ENT_QUOTES);
		$orientacao_sac = str_replace ("<br />","",$orientacao_sac);
		$orientacao_sac = str_replace ("|","\n",$orientacao_sac);
		
		$admin_paga_mao_de_obra = pg_result ($res,0,admin_paga_mao_de_obra);

		if ($login_fabrica == 7 AND strlen($desconto_peca)==0 AND strlen($consumidor_cpf) > 0) {
			$sql = "SELECT  tbl_posto_consumidor.contrato,
							tbl_posto_consumidor.desconto_peca
					FROM   tbl_posto_consumidor
					JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_consumidor.posto AND tbl_posto_consumidor.fabrica = $login_fabrica
					WHERE  tbl_posto.cnpj = '$consumidor_cpf' ";
			$res2 = pg_exec ($con,$sql);
			if (pg_numrows ($res2) > 0 ) {
				$contrato      = trim(pg_result($res2,0,contrato));
				$desconto_peca = trim(pg_result($res2,0,desconto_peca));

				if ($contrato != 't'){
					$desconto_peca = "0";
				}
			}
		}

		if ($consumidor_revenda == 'R'){
			$sql = "SELECT os_manutencao
					FROM tbl_os
					LEFT JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os.os_numero 
											AND tbl_os_revenda.posto = tbl_os.posto
					WHERE  tbl_os.os = $os
					AND    tbl_os.fabrica = $login_fabrica
					";
			$resRevenda = pg_exec ($con,$sql);
			if (pg_numrows ($resRevenda) > 0 ) {
				$os_manutencao = pg_result ($resRevenda,0,os_manutencao);
			}
		}
		
		if ($os_manutencao == 't'){
			$sql = "SELECT  tbl_os_revenda.taxa_visita,
							tbl_os_revenda.visita_por_km,
							tbl_os_revenda.valor_por_km,
							tbl_os_revenda.veiculo,
							tbl_os_revenda.hora_tecnica,
							tbl_os_revenda.valor_diaria,
							tbl_os_revenda.qtde_horas,
							tbl_os_revenda.regulagem_peso_padrao,
							tbl_os_revenda.desconto_deslocamento,
							tbl_os_revenda.desconto_hora_tecnica,
							tbl_os_revenda.desconto_diaria
							/*tbl_os_revenda.desconto_regulagem*/

					FROM   tbl_os
					JOIN   tbl_os_revenda        ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
					WHERE  tbl_os.os = $os
					AND    tbl_os.fabrica = $login_fabrica
					";

			$res2 = pg_exec ($con,$sql);
			if (pg_numrows ($res2) > 0 ) {

				$valor_por_km_caminhao    = trim(pg_result($res2,0,valor_por_km));
				$valor_por_km_carro       = trim(pg_result($res2,0,valor_por_km));
				$valor_por_km             = trim(pg_result($res2,0,valor_por_km));
				$veiculo                  = trim(pg_result($res2,0,veiculo));
				$taxa_visita              = trim(pg_result($res2,0,taxa_visita));
				$hora_tecnica             = trim(pg_result($res2,0,hora_tecnica));
				$valor_diaria             = trim(pg_result($res2,0,valor_diaria));

				$regulagem_peso_padrao    = trim(pg_result($res2,0,regulagem_peso_padrao));

				$desconto_deslocamento	= pg_result ($res2,0,desconto_deslocamento);
				$desconto_hora_tecnica	= pg_result ($res2,0,desconto_hora_tecnica);
				$desconto_diaria		= pg_result ($res2,0,desconto_diaria);
				#$desconto_regulagem	= pg_result ($res2,0,desconto_regulagem);
			}
		}

		if ($regulagem_peso_padrao > 0){
			$cobrar_regulagem = 't';
		}

		if ($certificado_conformidade > 0){
			$cobrar_certificado = 't';
		}

		if ($valor_diaria == 0 AND $hora_tecnica == 0){
			$cobrar_hora_diaria = "isento";
		}
		if ($valor_diaria > 0 AND $hora_tecnica == 0){
			$cobrar_hora_diaria = "diaria";
		}
		if ($valor_diaria == 0 AND $hora_tecnica > 0){
			$cobrar_hora_diaria = "hora";
		}
		
		if ($valor_por_km == 0 AND $taxa_visita == 0){
			$cobrar_deslocamento = "isento";
		}
		if ($valor_por_km > 0 AND $taxa_visita == 0){
			$cobrar_deslocamento = "valor_por_km";
		}
		if ($valor_por_km == 0 AND $taxa_visita > 0){
			$cobrar_deslocamento = "taxa_visita";
		}

		//HD 12606
		$defeito_reclamado_descricao = pg_result($res,0,defeito_reclamado_descricao);
		if($login_fabrica==11 or $login_fabrica==19) $defeito_reclamado = pg_result($res,0,defeito_reclamado);

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


		if ($os_manutencao != 't' or 1==1){
			$sql = "SELECT  tbl_familia_valores.taxa_visita,
							tbl_familia_valores.hora_tecnica,
							tbl_familia_valores.valor_diaria,
							tbl_familia_valores.valor_por_km_caminhao,
							tbl_familia_valores.valor_por_km_carro,
							tbl_familia_valores.regulagem_peso_padrao,
							tbl_familia_valores.certificado_conformidade
					FROM    tbl_os
					JOIN    tbl_produto         USING(produto)
					JOIN    tbl_familia_valores USING(familia)
					WHERE   tbl_os.os = $os
					AND     tbl_os.fabrica = $login_fabrica ";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {

				if ($cobrar_deslocamento  == 'taxa_visita'){
					$valor_por_km_caminhao    = trim(pg_result($res,0,valor_por_km_caminhao));
					$valor_por_km_carro       = trim(pg_result($res,0,valor_por_km_carro));
				}

				if ($cobrar_deslocamento  == 'valor_por_km'){
					$taxa_visita                  = trim(pg_result($res,0,taxa_visita));
					if ($veiculo == 'carro'){
						$valor_por_km_caminhao    = trim(pg_result($res,0,valor_por_km_caminhao));
						$valor_por_km_carro       = $valor_por_km;
					}
					if ($veiculo == 'caminhao'){
						$valor_por_km_carro       = trim(pg_result($res,0,valor_por_km_carro));
						$valor_por_km_caminhao    = $valor_por_km;
					}
				}

				if ($cobrar_hora_diaria == "diaria"){
					$hora_tecnica             = trim(pg_result($res,0,hora_tecnica));
				}
				if ($cobrar_hora_diaria == "hora"){
					$valor_diaria             = trim(pg_result($res,0,valor_diaria));
				}
				if ($cobrar_regulagem != "t"){
					$regulagem_peso_padrao    = trim(pg_result($res,0,regulagem_peso_padrao));
				}
				if ($cobrar_certificado != "t"){
					$certificado_conformidade = trim(pg_result($res,0,certificado_conformidade));
				}
			}
		}
	}
}


/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) > 0 and $btn_troca <> "trocar") {
	$os                 = $_POST['os'];
	$tipo_atendimento   = $_POST['tipo_atendimento'];
	$segmento_atuacao   = $_POST['segmento_atuacao'];
	$sua_os             = $_POST['sua_os'];
	$data_abertura      = $_POST['data_abertura'];
	$cliente            = $_POST['cliente'];
	$consumidor_nome    = $_POST['consumidor_nome'];
	$consumidor_cpf     = $_POST['consumidor_cpf'];
	$consumidor_fone    = $_POST['consumidor_fone'];
	$consumidor_celular = $_POST['consumidor_celular'];
	$consumidor_fone_comercial = $_POST['consumidor_fone_comercial'];
	$consumidor_email   = $_POST['consumidor_email'];
	$fisica_juridica    = $_POST['fisica_juridica'];

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
	$observacao_pedido  = $_POST['observacao_pedido'];
	$orientacao_sac     = $_POST['orientacao_sac'];
	$consumidor_revenda = $_POST['consumidor_revenda'];
	$qtde_produtos      = $_POST['qtde_produtos'];
	$produto_serie      = $_POST['produto_serie'];
	$autorizacao_cortesia = $_POST['autorizacao_cortesia'];

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

/* PASSA PAR?METRO PARA O CABE?ALHO (n?o esquecer ===========*/

/* $title = Aparece no sub-menu e no t?tulo do Browser ===== */
$title = "Cadastro de Ordem de Serviço - ADMIN"; 

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'callcenter';
include "cabecalho.php";
?>

<!--=============== <FUN??ES> ================================!-->


<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">
// valida numero de serie
function mostraEsconde(){
	$("div[@rel=div_ajuda]").toggle();
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


var http4 = new Array();
function fn_verifica_garantia(){
	var produto_descricao  = document.getElementById('produto_descricao').value;
	var produto_referencia = document.getElementById('produto_referencia').value;
	var serie              = document.getElementById('produto_serie').value;
	var campo              = document.getElementById('div_estendida');
	var curDateTime = new Date();
	http4[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&origem=os_cadastro&garantia=tue&produto_nome=" + produto_descricao + "&produto_referencia=" + produto_referencia+"&serie="+serie+"&data="+curDateTime;
	http4[curDateTime].open('get',url);
	
	http4[curDateTime].onreadystatechange = function(){
		if(http4[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http4[curDateTime].readyState == 4){
			if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){
				//**
				alert(http4[curDateTime].responseText);
				var results = http4[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
				
			}
		}
	}
	http4[curDateTime].send(null);
}


var http_forn = new Array();

function verifica_atendimento(tipo_atendimento) {
	
	/*Verificacao para existencia de componente - HD 22891 */
	if (document.getElementById('div_mapa')){
		var ref = document.getElementById(tipo_atendimento).value;
		url = "<?=$PHP_SELF?>?ajax=tipo_atendimento&id="+ref;
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
						document.getElementById('div_mapa').style.visibility = "visible";
						document.getElementById('div_mapa').style.position = 'static';
					}else{
						document.getElementById('div_mapa').style.visibility = "hidden";
						document.getElementById('div_mapa').style.position = 'absolute';
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}
}






//------------------------------

function VerificaSuaOS (sua_os){
	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}
}
function fazer_troca(){
	gerar_pedido = document.getElementById('gerar_pedido'); 
	if(gerar_pedido.checked) alert('Esta troca irá gerar pedido!');
	else                     alert('Esta troca NÃO irá gerar pedido!');
	if (confirm ('Confirma Troca')) {
		document.frm_troca.btn_troca.value='trocar';
		document.frm_troca.submit(); 
	}
}

function cancelar_os(){
	if (confirm ('Cancelar esta OS?')) {
		document.frm_cancelar.cancelar.value='cancelar';
		document.frm_cancelar.submit(); 
	}
}


// ========= Fun??o PESQUISA DE POSTO POR C?DIGO OU NOME ========= //

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


// ========= Fun??o PESQUISA DE POSTO POR C?DIGO OU NOME ========= //

function fnc_pesquisa_posto_km (campo, campo2, tipo) {

	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_km.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;

		janela.contato_endereco= document.frm_os.contato_endereco;


		janela.contato_numero  = document.frm_os.contato_numero  ;
		janela.contato_bairro  = document.frm_os.contato_bairro  ;
		janela.contato_cidade  = document.frm_os.contato_cidade  ;
		janela.contato_estado  = document.frm_os.contato_estado  ;


		if ("<? echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.frm_os.sua_os;
		}else{
			janela.proximo = document.frm_os.data_abertura;
		}
		janela.focus();
	}
}


// ========= Fun??o PESQUISA DE PRODUTO POR REFER?NCIA OU DESCRI??O ========= //

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
		janela.proximo      = document.frm_os.produto_descricao;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}
}

// ========= Fun??o PESQUISA DE CONSUMIDOR POR NOME OU CPF ========= //

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

// ========= Fun??o PESQUISA DE REVENDA POR NOME OU CNPJ ========= //

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

/* ============= Fun??o FORMATA CNPJ =============================
Nome da Fun??o : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digita??o
		Par?m.: cnpj (numero), form (nome do form)
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


/* ============= Fun??o FORMATA CPF =============================
Nome da Fun??o : formata_cpf (cpf, form)
		Formata o Campo de CPF a medida que ocorre a digita??o
		Par?m.: cpf (numero), form (nome do form)
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



/* ========== Fun??o AJUSTA CAMPO DE DATAS =========================
Nome da Fun??o : ajustar_data (input, evento)
		Ajusta a formata??o da M?scara de DATAS a medida que ocorre
		a digita??o do texto.
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

<? if($login_fabrica == 1) { /* HD 18051*/?>
	function char(nota_fiscal){
		try{var element = nota_fiscal.which	}catch(er){};
		try{var element = event.keyCode	}catch(er){};
		if (String.fromCharCode(element).search(/[0-9]/gi) == -1)
		return false
	}
	window.onload = function(){
		document.getElementById('nota_fiscal').onkeypress = char;
	}
<? } ?>

function fnc_num_serie_confirma(valor) {

	if(valor  =='sim'){
		document.getElementById('revenda_nome').readOnly =true;	
		document.getElementById('revenda_cnpj').readOnly =true;	
		document.getElementById('revenda_fone').readOnly =true;	
		document.getElementById('revenda_cidade').readOnly =true;	
		document.getElementById('revenda_estado').readOnly =true;	
		document.getElementById('revenda_endereco').readOnly =true;	
		document.getElementById('revenda_numero').readOnly =true;	
		document.getElementById('revenda_complemento').readOnly =true;	
		document.getElementById('revenda_bairro').readOnly =true;	
		document.getElementById('revenda_cep').readOnly =true;	
	}else{
		document.getElementById('revenda_nome').readOnly =false;	
		document.getElementById('revenda_cnpj').readOnly =false;	
		document.getElementById('revenda_fone').readOnly =false;	
		document.getElementById('revenda_cidade').readOnly =false;	
		document.getElementById('revenda_estado').readOnly =false;	
		document.getElementById('revenda_endereco').readOnly =false;	
		document.getElementById('revenda_numero').readOnly =false;	
		document.getElementById('revenda_complemento').readOnly =false;	
		document.getElementById('revenda_bairro').readOnly =false;	
		document.getElementById('revenda_cep').readOnly =false;	
	}
}

function fnc_pesquisa_numero_serie (campo, tipo) {
	var url = "";
	
	if (tipo == "produto_serie") {
		url = "pesquisa_numero_serie.php?produto_serie=" + campo.value + "&tipo=produto_serie";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_numero_serie.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
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

	janela.txt_nome			= document.frm_os.txt_revenda_nome;
	janela.txt_cnpj			= document.frm_os.txt_revenda_cnpj;
	janela.txt_fone			= document.frm_os.txt_revenda_fone;
	janela.txt_cidade		= document.frm_os.txt_revenda_cidade;
	janela.txt_estado		= document.frm_os.txt_revenda_estado;
	janela.txt_endereco		= document.frm_os.txt_revenda_endereco;
	janela.txt_numero		= document.frm_os.txt_revenda_numero;
	janela.txt_complemento	= document.frm_os.txt_revenda_complemento;
	janela.txt_bairro		= document.frm_os.txt_revenda_bairro;
	janela.txt_cep			= document.frm_os.txt_revenda_cep;

	janela.txt_data_venda	= document.frm_os.txt_data_venda;

	//PRODUTO
	janela.produto_referencia = document.frm_os.produto_referencia;
	janela.produto_descricao  = document.frm_os.produto_descricao;
	janela.produto_voltagem	  = document.frm_os.produto_voltagem;
	janela.focus();
}



</script>

<!--========================= AJAX==================================.-->
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script type="text/javascript" src="../js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript" src="../js/jquery.corner.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput.js"></script>

<script language='javascript' >

	function atualizaValorKM(campo){
		if (campo.value == 'carro'){
			$('input[name=valor_por_km]').val( $('input[name=valor_por_km_carro]').val() );
		}
		if (campo.value == 'caminhao'){
			$('input[name=valor_por_km]').val( $('input[name=valor_por_km_caminhao]').val() );
		}
	}

	function atualizaCobraHoraDiaria(campo){
		if (campo.value == 'isento'){
			$('div[name=div_hora]').css('display','none');
			$('div[name=div_diaria]').css('display','none');
			$('div[name=div_desconto_hora_diaria]').css('display','none');
			$('input[name=hora_tecnica]').attr('disabled','disabled');
			$('input[name=valor_diaria]').attr('disabled','disabled');
		}
		if (campo.value == 'hora'){
			$('div[name=div_hora]').css('display','');
			$('div[name=div_diaria]').css('display','none');
			$('div[name=div_desconto_hora_diaria]').css('display','');
			$('#hora_tecnica').removeAttr("disabled") 
			$('#valor_diaria').attr('disabled','disabled');
		}
		if (campo.value == 'diaria'){
			$('div[name=div_hora]').css('display','none');
			$('div[name=div_diaria]').css('display','');
			$('div[name=div_desconto_hora_diaria]').css('display','');
			$('#hora_tecnica').attr('disabled','disabled');
			$('#valor_diaria').removeAttr("disabled") 
		}
	}

	function atualizaCobraDeslocamento(campo){
		if (campo.value == 'isento'){
			$('div[name=div_valor_por_km]').css('display','none');
			$('div[name=div_taxa_visita]').css('display','none');
			$('div[name=div_desconto_deslocamento]').css('display','none');
			$('input[name=valor_por_km]').attr('disabled','disabled');
			$('input[name=taxa_visita]').attr('disabled','disabled');
		}
		if (campo.value == 'valor_por_km'){
			$('div[name=div_valor_por_km]').css('display','');
			$('div[name=div_taxa_visita]').css('display','none');
			$('div[name=div_desconto_deslocamento]').css('display','');
			$('input[name=valor_por_km]').removeAttr("disabled") 
			$('input[name=taxa_visita]').attr('disabled','disabled');

			$('input[name=veiculo]').each(function (){
				if (this.checked){
					atualizaValorKM(this);
				}
			});
		}
		if (campo.value == 'taxa_visita'){
			$('div[name=div_valor_por_km]').css('display','none');
			$('div[name=div_taxa_visita]').css('display','');
			$('div[name=div_desconto_deslocamento]').css('display','');
			$('input[name=valor_por_km]').attr('disabled','disabled');
			$('input[name=taxa_visita]').removeAttr("disabled") 
		}
	}

	
	var http5 = new Array();
	var http6 = new Array();

	function busca_valores(){
		referencia   = $("input[@name='produto_referencia']").val();

		if (referencia.length > 0) {
			var curDateTime = new Date();
			http5[curDateTime] = createRequestObject();
			url = "<?=$PHP_SELF?>?ajax=true&buscaValores=true&produto_referencia="+referencia+'&data='+curDateTime;
			http5[curDateTime].open('get',url);
			
			http5[curDateTime].onreadystatechange = function(){
				if (http5[curDateTime].readyState == 4){
					if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304){
						var results = http5[curDateTime].responseText.split("|");

						if (results[0] == 'ok') {
							$('input[name=taxa_visita]').val(results[1]);
							$('#taxa_visita').html(results[1]);
							$('input[name=hora_tecnica]').val(results[2]);
							$('#hora_tecnica').html(results[2]);
							$('input[name=valor_diaria]').val(results[3]);
							$('#valor_diaria').html(results[3]);
							$('input[name=valor_por_km_carro]').val(results[4]);
							$('#valor_por_km_carro').html('R$ '+results[4]);
							$('input[name=valor_por_km_caminhao]').val(results[5]);
							$('#valor_por_km_caminhao').html('R$ '+results[5]);
							$('input[name=regulagem_peso_padrao]').val(results[6]);
							$('#regulagem_peso_padrao').html(results[6]);
							$('input[name=certificado_conformidade]').val(results[7]);
							$('#certificado_conformidade').html(results[7]);

							$('input[name=veiculo]').each(function (){
								if (this.checked){
									atualizaValorKM(this);
								}
							});
						}
					}
				}
			}
			http5[curDateTime].send(null);
		}
	}

	$(document).ready(function(){
		$("input[@rel='data']").maskedinput("99/99/9999");
		$("input[@rel='fone']").maskedinput("(99) 9999-9999");
		$("input[@rel='cnpj']").maskedinput("99.999.999/9999-99");
		$(".content").corner("dog 10px");
	});

	function verificaProduto(produto,serie){
		referencia   = produto.value;
		numero_serie = serie.value;

		if (referencia.length > 0 || numero_serie.length > 0) {
			var curDateTime = new Date();
			http6[curDateTime] = createRequestObject();
			url = "<?=$PHP_SELF?>?ajax=true&buscaInformacoes=true&produto_referencia="+referencia+"&serie="+numero_serie+'&data='+curDateTime;
			http6[curDateTime].open('get',url);
			
			http6[curDateTime].onreadystatechange = function(){
				if (http6[curDateTime].readyState == 4){
					if (http6[curDateTime].status == 200 || http4[curDateTime].status == 304){
						var results = http6[curDateTime].responseText.split("|");
						if (results[0] == 'ok') {
							if (document.getElementById('produto_capacidade')){
								document.getElementById('produto_capacidade').value = results[1];
							}
							if (document.getElementById('divisao')){
								document.getElementById('divisao').value            = results[2];
							}
							if (document.getElementById('versao')){
								document.getElementById('versao').value             = results[3];
							}
						}else{
							if (document.getElementById('produto_capacidade')){
								document.getElementById('produto_capacidade').value='';
							}
							if (document.getElementById('divisao')){
								document.getElementById('divisao').value='';
							}
							if (document.getElementById('versao')){
								document.getElementById('versao').value='';
							}
						}
					}
				}
			}
			http6[curDateTime].send(null);
		}
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

	function verificaValorPorKm(campo){
		if (campo.checked){
			$('div[name=div_valor_por_km]').css('display','');
			$('div[name=div_taxa_visita]').css('display','none');
			$('input[name=taxa_visita]').attr("disabled", true);
		}else{
			$('div[name=div_valor_por_km]').css('display','none');
			$('div[name=div_taxa_visita]').css('display','');
			$('input[name=taxa_visita]').removeAttr("disabled");
		}
		$("input[@name='veiculo']").each( function (){
			if (this.checked){
				atualizaValorKM( this );
			}
		});
	}


	function listaProduto(valor,marca) {
	//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
		catch(e) {
			try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
			catch(ex) { 
				try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser nao tem recursos para uso do Ajax"); ajax = null;}
			}
		}
		if(ajax) {
			//deixa apenas o elemento 1 no option, os outros sÃ£o excluÃ­dos
			window.document.frm_troca.troca_garantia_produto.options.length = 1;
		
			//opcoes Ã© o nome do campo combo
			idOpcao  = document.getElementById("opcoes");

			ajax.open("GET", "ajax_produto_familia.php?familia="+valor+"&marca="+marca, true);
//			alert("ajax_produto_familia.php?familia="+valor);
			ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			ajax.onreadystatechange = function() {
				if(ajax.readyState == 1) {
					idOpcao.innerHTML = "Carregando...!";
				}//enquanto estiver processando...emite a msg
				if(ajax.readyState == 4 ) {
					if(ajax.responseXML) {
						montaCombo(ajax.responseXML);//após ser processado-chama função
					}else {
						//caso nÃ£o seja um arquivo XML emite a mensagem abaixo
						idOpcao.innerHTML = "Selecione a familia";
					}
				}
			}
		//passa o cÃ³digo do produto escolhido
		var params = "linha="+valor;
		ajax.send(null);
		}
	}

	function montaCombo(obj){

		var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
		if(dataArray.length > 0) {//total de elementos contidos na tag cidade
			for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
				var item = dataArray[i];
				//contÃ©udo dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
				var nome      =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
				idOpcao.innerHTML = "Selecione o produto";
				//cria um novo option dinamicamente  
				var novo = document.createElement("option");
				//			echo "<option value='-1' >RESSARCIMENTO FINANCEIRO</option>";

				novo.setAttribute("id", "opcoes"); //atribui um ID a esse elemento
				novo.value = codigo;               //atribui um valor
				novo.text  = nome;                 //atribui um texto
				window.document.frm_troca.troca_garantia_produto.options.add(novo);//adiciona o novo elemento
			}

		} else { 
			//idOpcao.innerHTML = "Selecione a família";//caso o XML volte vazio, printa a mensagem abaixo
			idOpcao.innerHTML = "Nenhum produto";
		}
	}



//ajax defeito_reclamado
function listaDefeitos(valor) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
		}
	}
//se tiver suporte ajax
	if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos
	document.forms[0].defeito_reclamado.options.length = 1;
	//opcoes é o nome do campo combo
	idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_produto.php?produto_referencia="+valor, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaComboDefeitoReclamado(ajax.responseXML);//após ser processado-chama fun
			} else {idOpcao.innerHTML = "Selecione o produto";//caso não seja um arquivo XML emite a mensagem abaixo
					}
		}
	}
	//passa o código do produto escolhido
	var params = "produto_referencia="+valor;
	ajax.send(null);
	}
}

function montaComboDefeitoReclamado(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
	for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
		 var item = dataArray[i];
		//contéudo dos campos no arquivo XML
		var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
		var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
		idOpcao.innerHTML = "Selecione o defeito";
		//cria um novo option dinamicamente  
		var novo = document.createElement("option");
		novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
		novo.value = codigo;		//atribui um valor
		novo.text  = nome;//atribui um texto
		document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
		}
	} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}


</script>

<script type="text/javascript">
	/* Função mostra o campo quando muda o select(combo)*/
	function MudaCampo(campo){
		//alert(campo.value);
		if (campo.value== '15' || campo.value== '16' ) {
			document.getElementById('autorizacao_cortesia').style.display='inline';
		}else{
			document.getElementById('autorizacao_cortesia').style.display='none';
		}
	}


	/******************* INTEGRIDADE ***************************/

		function adicionaIntegridade() {

		if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}

		var tbl = document.getElementById('tbl_integridade');
		var lastRow = tbl.rows.length;
		var iteration = lastRow;


		if (iteration>0){
			document.getElementById('tbl_integridade').style.display = "inline";
		}


		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		// COLUNA 1 - LINHA
		var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_defeito_reclamado_' + iteration);
		el.setAttribute('id', 'integridade_defeito_reclamado_' + iteration);
		el.setAttribute('value',document.getElementById('defeito_reclamado').value);
		celula.appendChild(el);


		linha.appendChild(celula);

		// coluna 3 - DEFEITO RECLAMADO
		//var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
		//celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
		//linha.appendChild(celula);


		// coluna 6 - botacao
		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade(this);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);

		//document.getElementById('solucao').selectedIndex=0;
	}

	function removerIntegridade(iidd){
		var tbl = document.getElementById('tbl_integridade');
		tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);

	}

	function criaCelula(texto) {
		var celula = document.createElement('td');
		var textoNode = document.createTextNode(texto);
		celula.appendChild(textoNode);
		return celula;
	}

</script>

<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a exist?ncia de uma OS com o mesmo n?mero e em
		caso positivo passa a mensagem para o usu?rio.
=============================================================== -->
<? 
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<!-- ============= <HTML> COME?A FORMATA??O ===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	//if ($ip=="201.43.201.204") echo "teste";	

	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6, strlen($msg_erro)-6);
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
<style>
.Conteudo{
	font-family: Verdana;
	font-size: 10px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

fieldset.valores , fieldset.valores div{
	padding: 0.2em;
	font-size:10px;
	width:225px;
}

fieldset.valores label {
	float:left;
	width:43%;
	margin-right:0.2em;
	padding-top:0.2em;
	text-align:right;
}

fieldset.valores span {
	font-size:11px;
	font-weight:bold;
}

</style>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	
	<td valign="top" align="left">
		
		<?
		if (strlen ($msg_erro) > 0) {
//if ($ip == '201.0.9.216') echo $monta_sql;
			//echo $msg_erro;

			$consumidor_cidade		= $_POST['consumidor_cidade'];
			$consumidor_estado		= $_POST['consumidor_estado'];
			$consumidor_email		= trim ($_POST['consumidor_email']) ;
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
		<!-- ------------- Formul?rio ----------------- -->
	
		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="os" value="<? echo $os ?>">
		<? if (strlen($pedido) > 0) { ?>
			<input class="frm" type="hidden" name="produto_referencia" id="produto_referencia" value="<? echo $produto_referencia ?>">
			<input class="frm" type="hidden" name="produto_descricao"  id="produto_descricao" value="<? echo $produto_descricao ?>">
		<?}?>
		

		<p>



		<? if ($login_fabrica == 19 OR $login_fabrica == 20 OR $login_fabrica == 30 OR $login_fabrica == 50 OR $login_fabrica == 7) { ?>
		<div style='border: #D3BE96 1px solid;
				background-color: #FCF0D8;
				font-family: Arial;
				font-size:   9pt;
				color:#333333;' class='CaixaMensagem' width='400'>
		<center>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">
		<? if ($login_fabrica==7) { ?>
			Natureza
		<?}else{?>
			Tipo de Atendimento
		<?}?>
		<select name="tipo_atendimento" id="tipo_atendimento" size="1" class="frm" 
			<? if ($login_fabrica==20) { 
					echo "onChange='MudaCampo(this)'"; 
				}else{
					if ($login_fabrica==30 OR $login_fabrica==50) { 
						?> onchange="javascript:verifica_atendimento('tipo_atendimento')"; <?
					}
				};?>>
			<option selected></option>
			<?

			//IGOR  - HD 2909  | Garantía de repuesto - Não tem | Garantía de accesorios - Não tem | Garantía de reparación - Não tem
			$wr = "";
			if($login_fabrica == 20 ){
				if(strlen($posto)>0){
					$sql = "select pais from tbl_posto where posto =$posto";

					$res = pg_exec ($con,$sql) ;

					$pais = pg_result ($res, 0, pais);

					if($pais == "PE"){
						$wr = "AND tbl_tipo_atendimento.tipo_atendimento NOT IN(11, 12, 14) ";

					}
				}
			}

			/*HD:22505- COLORMAQ - Tipo atendimento de deslocamento só aparece se o posto tem km cadastrado(maior que 0)*/
			$sql_deslocamento = " ";
			if($login_fabrica==50){
				/*$sql_deslocamento = " 	AND tipo_atendimento NOT IN (
											SELECT 
												CASE WHEN valor_km > 0
													Then 0
													Else 55
											END as tipo_atendimento
											FROM tbl_posto_fabrica 
											WHERE fabrica = $login_fabrica
												AND posto = $posto
										) ";*/
			}


			$sql = "SELECT * 
					FROM tbl_tipo_atendimento 
					WHERE fabrica = $login_fabrica 
					AND   ativo IS TRUE 
					$wr
					$sql_deslocamento
					ORDER BY tipo_atendimento";
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
/*
			echo "&nbsp;&nbsp;&nbsp;&nbsp;Segmento de atuação <select name='segmento_atuacao' size='1' class='frm'>";
			echo "<option selected></option>";

			$sql = "SELECT * 
				FROM tbl_segmento_atuacao
				WHERE fabrica = $login_fabrica 
				ORDER BY descricao";
			$res = pg_exec ($con,$sql) ;

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				$descricao_segmento = pg_result ($res,$i,descricao);
				$x_segmento_atuacao = pg_result ($res,$i,segmento_atuacao);

				//--=== Tradução para outras linguas ============================= Raphael HD:1356
				
				$sql_idioma = "SELECT * FROM tbl_segmento_atuacao_idioma WHERE segmento_atuacao = $x_segmento_atuacao AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				
				if (@pg_numrows($res_idioma) >0) $descricao_segmento  = trim(@pg_result($res_idioma,0,descricao));

				
				//--=== Tradução para outras linguas ================================================
				
				echo "<option ";
				if ($segmento_atuacao == $x_segmento_atuacao ) echo " selected ";
				echo " value='$x_segmento_atuacao'>" ;
				echo $descricao_segmento  ;
				echo "</option>\n";
			}
			echo "</select>";
*/

			echo "<br><b><FONT SIZE='' COLOR='#FF9900'>";
			if($sistema_lingua) echo "En caso de garantía  de piezas o acesorios no es necesario inserir el producto en la OS";else echo "Nos casos de Garantia de Peças ou  Acessórios não é necessário lançar o Produto na OS.";
			echo "</FONT></b><br>";	
		}
		?>
		</font>
		<? } 


		echo "</div>";

		if($login_fabrica == 20){
		//alterado gustavo HD 5909
		/*#####################################*/
		if($tipo_atendimento==15 or $tipo_atendimento==16) $mostrar = "display:inline";
		else                                               $mostrar = "display:none";

		echo "<div id='autorizacao_cortesia'
				style='$mostrar; 
				border: #D3BE96 1px solid;
				background-color: #FCF0D8;
				font-family: Arial;
				font-size:   9pt;
				text-align: left;
				color:#333333;' class='CaixaMensagem' width='400'>";
			echo "<TABLE  width='710'>";
				echo "<TR>";
					echo "<TD>";
					echo "<b><FONT SIZE='' COLOR='#FF9900'>";
					if($sistema_lingua) echo "En el caso de comerciales o técnicos cortesía está obligado a informar el nombre de la persona que aprobó y la fecha de su aprobación.";else echo "Nos casos de Cortesia comercial ou técnica é obrigatório informar o nome da pessoa que a aprovou e data da aprovação.";
					echo "</FONT></b><br>";	
					echo "</TD>";
				echo "</TR>";
				echo "<TR>";
					echo "<TD>";echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>";
					if($sistema_lingua) echo "Autorización Cortesía";else echo "Autorização Cortesia";
					echo "&nbsp;<INPUT TYPE='text' NAME='autorizacao_cortesia' value='$autorizacao_cortesia' size='40'>";
					echo "</font>";
					echo "</TD>";
				echo "</TR>";
			echo "</TABLE>";
		echo "</div>";
		/*#####################################*/
		}
		?>


		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<? if ($login_fabrica == 6){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<input class="frm" type="text" name="produto_serie" id="produto_serie" size="20" maxlength="20" value="<? echo $produto_serie ?>" >
				&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os')"  style='cursor: pointer'>
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
					<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" 
						<?
							if (($login_fabrica == 5) or ($login_fabrica == 15)) { ?> 
								onblur="fnc_pesquisa_posto2(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')" 
						<?	} ?>>&nbsp;

					<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' 
						<?
						/*HD 2159-24601- PESQUISA DIFERENTE PARA CALCULO KM*/
						if (($login_fabrica == 30) or ($login_fabrica == 50)) { ?> 
							onclick="javascript: fnc_pesquisa_posto_km(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"
						<?}else{ ?>
							onclick="javascript: fnc_pesquisa_posto2(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"
						<?}?>
					style="cursor:pointer" >
					</A>
					<input type='hidden' name='contato_endereco' id='contato_endereco' value='<?echo $contato_endereco;?>'>
					<input type='hidden' name='contato_numero' id='contato_numero' value='<?echo $contato_numero;?>'>
					<input type='hidden' name='contato_bairro' id='contato_bairro' value='<?echo $contato_bairro;?>'>
					<input type='hidden' name='contato_cidade' id='contato_cidade' value='<?echo $contato_cidade;?>'>
					<input type='hidden' name='contato_estado' id='contato_estado' value='<?echo $contato_estado;?>'>
			</td>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Posto</font>
				<br>
					<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>"
					<? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>  
					<? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" 	<? } ?>>&nbsp;
					<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' 
						<?
						/*HD 2159-24601- PESQUISA DIFERENTE PARA CALCULO KM*/
						if (($login_fabrica == 30) or ($login_fabrica == 50)) { ?> 
							onclick="javascript: fnc_pesquisa_posto_km(document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" 
						<?}else{ ?>
							onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" 
						<?}?>
					style="cursor:pointer;"></A>
			</td>

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
						<?
						if ($login_fabrica == 5) {echo "maxlength='6'  ";} else { echo "maxlength='20'  ";}  
						?> 
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
			if (trim (strlen ($data_abertura)) == 0 AND ($login_fabrica == 7 OR $login_fabrica == 50)) {
				$data_abertura = $hoje;
			}
			
			?>
			<? if ($login_fabrica == 50 ){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<? if ($login_fabrica == 35){?>
					PO#
				<?}else{?>
					N. Série.
				<?}?></font>
				<br>
				<input class="frm" type="text" name="produto_serie" id="produto_serie" size="15" <? if ($login_fabrica == 35){?> maxlength="12" <?}else{?> maxlength="20" <?}?> value="<? echo $produto_serie ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
				<? if($login_fabrica == 25){ ?>
				&nbsp;<INPUT TYPE="button" onClick='javascritp:fn_verifica_garantia();' name='Verificar' value='Verificar'>
				<? } ?>
				<? if($login_fabrica==50){ ?>
					<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_numero_serie (document.frm_os.produto_serie, "produto_serie")' style='cursor: pointer'>
				<? } ?>


			</td>
			<? }?>


			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
				<br>
				<input name="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura ?>" type="text" class="frm" tabindex="0" ><br><font face='arial' size='1'>Ex.: 25/11/2007</font><br>
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
				<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>"
				<? if($login_fabrica==50){?>onChange="javascript:this.value=this.value.toUpperCase();"<?}?> 
				<? if ($login_fabrica == 5) { ?>  onblur="fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')" <? } ?>
				<?if($login_fabrica==7) {?> onblur="busca_valores(); verificaProduto(document.frm_os.produto_referencia,this)"; <?} ?>
				>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')">
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
				<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" 
				<? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> 
				<? if (($login_fabrica == 5) or ($login_fabrica == 15)) { ?> onblur="fnc_pesquisa_produto2(document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')" <? } ?>
				<?if($login_fabrica==7) {?> onblur="busca_valores(); verificaProduto(document.frm_os.produto_referencia,this)"; <?} ?>
				>&nbsp;<img src='imagens/btn_buscar5.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript:fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')"></A>
				<?	}	?>
			</td>
			<? if ($login_fabrica <> 6 AND $login_fabrica <> 50 ){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<? if ($login_fabrica == 35){?>
					PO#
				<?}else{?>
					N. Série.
				<?}?></font>
				<br>
				<input class="frm" type="text" name="produto_serie" id="produto_serie" size="15" <? if ($login_fabrica == 35){?> maxlength="12" <?}else{?> maxlength="20" <?}?> value="<? echo $produto_serie ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
				<? if($login_fabrica == 25){ ?>
				&nbsp;<INPUT TYPE="button" onClick='javascritp:fn_verifica_garantia();' name='Verificar' value='Verificar'>
				<? } ?>
				<? if($login_fabrica==50){ ?>
					<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_numero_serie (document.frm_os.produto_serie, "produto_serie")' style='cursor: pointer'>
				<? } ?>


			</td>
			<? }?>
				<? 
				if ($login_fabrica==11) {
					if ( (strlen($admin_os) > 0 and strlen($os) > 0) or (strlen($os)==0)) {
						echo "<td>&nbsp;&nbsp;<BR><input type='checkbox' name='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra'";
						if ($admin_paga_mao_de_obra == 't') echo "checked";
						echo "> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Pagar Mão-de-Obra</font></td>";
					}
			} ?>
		</tr>
		<? if ($login_fabrica == 7) {?>
		<tr>
			<td nowrap>
			</td>
			<td nowrap  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Capacidade</font>
				<br>
				<? if (strlen($produto_capacidade)>0){
					echo "<INPUT TYPE='hidden' name='capacidade' class='frm' id='capacidade' value='$produto_capacidade'>";
					echo "<INPUT TYPE='text' VALUE='$produto_capacidade' class='frm' SIZE='9' onClick=\"alert('Não é possível alterar a capacidade')\" disabled>";
				}else{?>
					<INPUT TYPE="text" NAME="capacidade" class='frm' id='produto_capacidade' VALUE="<?=$produto_capacidade?>" SIZE='9' MAXLENGTH='9'>
				<?}?>
			</td>
			<td nowrap  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Divisão</font>
				<br>
				<? if (strlen($produto_divisao)>0){
					echo "<input type='hidden' name='divisao' class='frm' value='$produto_divisao'>";
					echo "<INPUT TYPE='text' VALUE='$produto_divisao' class='frm' SIZE='9' onClick=\"alert('Não é possível alterar a divisão')\" disabled>";
				}else{?>
					<INPUT TYPE="text" NAME="divisao" class='frm' id='divisao' VALUE="<?=$divisao?>" SIZE='9' MAXLENGTH='9'>
				<?}?>
			</td>
		</tr>
		<? } ?>
		</table>



			<?
				//hbtech 3/3/2008 14824
				if($login_fabrica == 25){
			?>
				<div id='div_estendida' style='text-align:center'>
					<?if(strlen($produto_serie)>0){
							include "conexao_hbtech.php";

							$sql = "SELECT	idNumeroSerie  ,
											idGarantia     ,
											revenda        ,
											cnpj
									FROM numero_serie 
									WHERE numero = '$produto_serie'";
							echo $sql;
							$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());
						
							if(mysql_num_rows($res)>0){
								$idNumeroSerie = mysql_result($res,0,idNumeroSerie);
								$idGarantia    = mysql_result($res,0,idGarantia);
								$es_revenda       = mysql_result($res,0,revenda);
								$es_cnpj          = mysql_result($res,0,cnpj);

								if(strlen($idGarantia)==0){
									echo "Número de série não encontrado nas vendas";
							
								}
							}
						}
					?>
				</div>
				<? 
				}//fim hbtech
				?>
		<? if ($login_fabrica == 7 or $login_fabrica == 28 or $login_fabrica == 35 or $login_fabrica == 19 or $login_fabrica == 50 or $login_fabrica == 30 or $login_fabrica ==59){ ?>

			<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr valign='top'>
				<td valign='top' align='left'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font>
				<br>
				<?
				if($pedir_defeito_reclamado_descricao == 't'){
						if($login_fabrica==50){ 
							$onchange= "onChange=\"javascript: this.value=this.value.toUpperCase();\"";
						}
					echo "<INPUT TYPE='text' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='50' $onchange>";
				}else{
					echo "<select name='defeito_reclamado' id='defeito_reclamado' style='width: 220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' >";
					echo "<option id='opcoes' value='0'></option>";
					echo "</select>";
					echo "</td>";
				}
				?>
			</tr>
			</table>
			<?
		}elseif ($login_fabrica==11){
				echo "<input type='hidden' name='defeito_reclamado' value='$defeito_reclamado'>";
		}

		if($login_fabrica==19){
			echo "<center><font size='-2'>Para gravar a OS é necessário adicionar os defeitos reclamados, basta clicar em ADICIONAR DEFEITOS</font></center>";
			echo "<center><input type='button' onclick=\"javascript: adicionaIntegridade()\" value='Adicionar Defeito' name='btn_adicionar'></center><br>";
			echo "
			<table style=' border:#485989 1px solid; background-color: #e6eef7;font-size:12px;display:none' align='center' width='400' border='0' id='tbl_integridade' cellspacing='3' cellpadding='3'>
			<thead>
			<tr bgcolor='#596D9B' style='color:#FFFFFF;'>
			<td align='center' nowrap><b>Defeito Reclamado</b></td>
			<td align='center' nowrap><b>Defeito Constatado</b></td>
			<td align='center'><b>Ações</b></td>
			</tr>
			</thead>
			<tbody>";
			if(strlen($os)>0){
				$sql_cons = "SELECT distinct
								tbl_defeito_reclamado.defeito_reclamado ,
								tbl_defeito_reclamado.descricao  AS dr_descricao   ,
								tbl_defeito_reclamado.codigo     AS dr_codigo      
						FROM tbl_os_defeito_reclamado_constatado
						JOIN tbl_defeito_reclamado USING(defeito_reclamado)
						LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado=tbl_os_defeito_reclamado_constatado.defeito_constatado
						WHERE os = $os";
				$res_dr = pg_exec($con, $sql_cons);
				if(pg_numrows($res_dr) > 0){
					for($x=0;$x<pg_numrows($res_dr);$x++){
						$dr_defeito_reclamado = pg_result($res_dr,$x,defeito_reclamado);
						$dr_descricao         = pg_result($res_dr,$x,dr_descricao);
						$dr_codigo            = pg_result($res_dr,$x,dr_codigo);
						$aa = $x+1;
						echo "<tr>";
						echo "<td nowrap><font size='1'><input type='hidden' name='integridade_defeito_reclamado_$aa' value='$dr_defeito_reclamado'>$dr_codigo-$dr_descricao</font></td>";
						// HD 33303
						echo "<td align='left' nowrap>";
						if(strlen($dr_defeito_reclamado) >0){
							$sql_dc="SELECT 
										tbl_defeito_constatado.descricao         ,
										tbl_defeito_constatado.codigo
								FROM tbl_os_defeito_reclamado_constatado
								LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
								WHERE os = $os
								and   tbl_os_defeito_reclamado_constatado.defeito_reclamado = $dr_defeito_reclamado";
							$res_dc = pg_exec($con, $sql_dc);
							if(pg_numrows($res_dc) > 0){
								for($y=0;$y<pg_numrows($res_dc);$y++){
									$dc_descricao = pg_result($res_dc,$y,descricao);
									$dc_codigo    = pg_result($res_dc,$y,codigo);
									if(strlen($dc_descricao) >0 ){
										echo "<font size='-2'>$dc_descricao</font><br>";
									}
								}
							}
						}
						echo "</td>";
						echo "<td align='center'><input type='button' onclick='removerIntegridade(this);' value='Excluir'></td>";
						echo "</tr>";
					}
					echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";
				}
			}
			echo "</tbody></table>";
		}
		?>



		<? if ($login_fabrica == 1) { ?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabrição</font>
				<br>
				<input  name ="codigo_fabricacao" class ="frm" type ="text" size ="13" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de FabricaÃ§Ã£o.');">
			</td>
			<td nowrap><?// HD15589?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">30 dias Satisfação DeWALT/Porter Cable</font>
				<br>
				<input name ="satisfacao" class ="frm" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Laudo técnico</font>
				<br>
				<input  name ="laudo_tecnico" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo t?cnico.');">
			</td>
		</tr>
		</table>
		<? } ?>

		<hr>

		<input type="hidden" name="consumidor_cliente">
<? 
//		<input type="hidden" name="consumidor_cep">
?>
		<input type="hidden" name="consumidor_rg">

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>  <? if ($login_fabrica == 5) { ?> onblur=" fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, 'nome'); displayText('&nbsp;');" <? } ?> onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';  displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
			</td>
			<? if($login_fabrica == 1){?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo Consumidor</font>
				<br>
				<SELECT NAME="fisica_juridica">
					<OPTION></OPTION>
					<OPTION VALUE="F">Pessoa Física</OPTION>
					<OPTION VALUE="J">Pessoa Jurídica</OPTION>
				</SELECT>
			</td>
			<?}?>
			<td> 
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">C.P.F. Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_cpf"   size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,'cpf'); this.className='frm'; displayText('&nbsp;');" <? } ?> onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';  displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e tra?os.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
				<br>
				<? // HD 31122 ?>
				<input class="frm" type="text" name="consumidor_fone" rel='fone' size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CEP</font>
				<br>
				<input class="frm" type="text" name="consumidor_cep"   size="8" maxlength="8" value="<? echo $consumidor_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');">
			</td>
		</tr>
		</table>

		<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font></td>

	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font></td>

	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Compl.</font></td>

	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font></td>

	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font></td>

	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font></td>
</tr>

<tr>
	<td>
		<input class="frm" type="text" name="consumidor_endereco"   size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endere?o do consumidor.');">
	</td>

	<td>
		<input class="frm" type="text" name="consumidor_numero"   size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endere?o do consumidor.');">
	</td>

	<td>
		<input class="frm" type="text" name="consumidor_complemento"   size="15" maxlength="30" value="<? echo $consumidor_complemento ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endere?o do consumidor.');">
	</td>

	<td>
		<input class="frm" type="text" name="consumidor_bairro"   size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
	</td>

	<td>
		<input class="frm" type="text" name="consumidor_cidade"   size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');">
	</td>

	<td>
		<input class="frm" type="text" name="consumidor_estado"   size="2" maxlength="2" value="<? echo $consumidor_estado ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');">
	</td>
</tr>
<tr class="top">
	<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Email</font>
		<BR>
		<input class="frm" type="text" name="consumidor_email"  size="30" maxlength="50" value="<? echo $consumidor_email ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
	</td>
<? if($login_fabrica==3){?>
	<td>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Celular</font>
		<br>
		<input class="frm" type="text" name="consumidor_celular"   size="15" maxlength="20" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
	</td>
	<td>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Comercial</font>
		<br>
		<input class="frm" type="text" name="consumidor_fone_comercial"   size="15" maxlength="20" value="<? echo $consumidor_fone_comercial ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
	</td>
<?}?>
</tr>
</table>

<?


if( /*HD21590 - O POSTO DEVE ESTAR CARREGADO, SENÃO VAI APRESENTAR ERRO NA BUSCA EM BRANCO*/
	(strlen($posto)> 0) AND 
	(
		($login_fabrica==1 AND $posto==6359) OR 
		$login_fabrica==30 OR 
		($login_fabrica==15 AND $posto==6359) OR 
		$login_fabrica==56 OR 
		$login_fabrica==57 OR
		$login_fabrica==50 OR
		$login_fabrica==46
	)
	){
	//--== Calculo de Distância com Google MAPS =========================================
	
	$sql_posto = "SELECT contato_endereco AS endereco,
						 contato_numero   AS numero  ,
						 contato_bairro   AS bairro  ,
						 contato_cidade   AS cidade  ,
						 contato_estado   AS estado
					FROM tbl_posto_fabrica 
					WHERE posto   = $posto
					AND   fabrica = $login_fabrica ";

	$res_posto = pg_exec($con,$sql_posto);
	if(pg_numrows($res_posto)>0) {
		$endereco_posto = pg_result($res_posto,0,endereco).', '.pg_result($res_posto,0,numero).' '.pg_result($res_posto,0,bairro).' '.pg_result($res_posto,0,cidade).' '.pg_result($res_posto,0,estado);
		if(strlen($distancia_km)==0)$distancia_km=0;
	}

	if(strlen($tipo_atendimento)>0){
		$sql = "SELECT tipo_atendimento,km_google
				FROM tbl_tipo_atendimento 
				WHERE tipo_atendimento = $tipo_atendimento";
		$resa = pg_exec($con,$sql);
		if(pg_numrows($resa)>0){
			$km_google = pg_result($resa,0,km_google);
		}
	}
}
?>
<div id="mapa2" style=" width:500px; height:10px;visibility:hidden;position:absolute; ">
<a href='javascript:escondermapa();'>Fechar Mapa</a>
</div><br>
<div id="mapa" style=" width:500px; height:300px;visibility:hidden;position:absolute;border: 1px #FF0000 solid; "></div>
<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAA4k5ZzVjDVAWrCyj3hmFzTxR_fGCUxdSNOqIGjCnpXy7SRGDdcRTb85b5W8d9rUg4N-hhOItnZScQwQ" type="text/javascript"></script>
<script language="javascript">
var map;
function initialize(){
	// Carrega o Google Maps
	if (GBrowserIsCompatible()) {
		map = new GMap2(document.getElementById("mapa"));
		map.setCenter(new GLatLng(-25.429722,-49.271944), 11)

		// Cria o objeto de roteamento
		 var dir = new GDirections(map);
		 //var pt1 = document.getElementById("ponto1").value
		 var pt1 = document.getElementById("contato_endereco").value + ", "+ document.getElementById("contato_numero").value + " " + document.getElementById("contato_bairro").value + " " + document.getElementById("contato_cidade").value + " " + document.getElementById("contato_estado").value;

		 var pt2 = document.getElementById("consumidor_endereco").value + ", "+ document.getElementById("consumidor_numero").value + " " + document.getElementById("consumidor_bairro").value + " " + document.getElementById("consumidor_cidade").value + " " + document.getElementById("consumidor_estado").value;

		//document.getElementById("ponto1").value = pt1 +"CONSUMIDOR: "+pt2;

		document.getElementById('div_end_posto').innerHTML= '<B>Endereço do posto: </b><u>'+ pt1+'</u>';

		 // Carrega os pontos dados os endereços
		dir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});
		// O evento load do GDirections é executado quando chega o resultado do geocoding.
		GEvent.addListener(dir,"load", function() {
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
	}
}
function compara(campo1,campo2){
	var num1 = campo1.value.replace(".",",");
	var num2 = campo2.value.replace(".",",");
	if(num1!=num2){
		document.getElementById('div_mapa_msg').style.visibility = "visible";
		document.getElementById('div_mapa_msg').innerHTML = 'A distância percorrida pelo técnico estará sujeito a auditoria';
	}else{
		document.getElementById('div_mapa_msg').style.visibility = "visible";
		document.getElementById('div_mapa_msg').innerHTML='Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>';
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

</script>

<div id='div_mapa' style='background:#efefef;border:#999999 1px solid;font-size:10px;padding:5px;<?if($km_google<>'t') echo "visibility:hidden;position:absolute;";?>' >
<b>Para Calcular a distância percorrida pelo técnico para execução do serviço(ida e volta):<br>
Preencha todos os campos de endereço acima ou preencha o campo de distância</b>
<br><br>
<input  type="hidden" id="ponto1" value="<?=$endereco_posto?>" >
<input  type="hidden" id="distancia_km_maps"  value="" >
<input type='hidden' name='distancia_km_conferencia' id='distancia_km_conferencia' value='<?=$distancia_km_conferencia?>'>

Distância: <input type='text' name='distancia_km' id='distancia_km' value='<?=$distancia_km?>' size='5' onchange="javascript:compara(distancia_km,distancia_km_conferencia)"> Km
<input  type="button" onclick="initialize()" value="Calcular Distância" size='5' >
<div id='div_mapa_msg' style='color:#FF0000'></div>
<br>
<div id='div_end_posto' style='color:#000000'>
<B>Endereço do posto:</b> 
<u>
	<?if(strlen($posto)>0){?>
		<?=$endereco_posto?>
	<?}?>
</u>
</div>
<br>&nbsp;
</div>



<p>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td  class="top">Informações sobre a Revenda</td>
</tr>
</table>
		
		<hr>


<?
		if ($login_fabrica == 50) {
?>
		<table  width='700' border="0" cellspacing="5" cellpadding="0">
			<tr valign='top'>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
					<br>
					<input class="frm" type="text" name="txt_revenda_nome" id="txt_revenda_nome" size="50" maxlength="50" value="" readonly>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
					<br>
					<input class="frm" type="text" name="txt_revenda_cnpj" id="txt_revenda_cnpj" size="20" maxlength="18" id="txt_revenda_cnpj" value="" readonly>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
				<br>
					<input class="frm" type="text" name="txt_revenda_fone" id="txt_revenda_fone" size="15" maxlength="15"  rel='fone' value="" readonly>
				</td>
				<td>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
					<br>
					<input class="frm" type="text" name="txt_revenda_cep" id="txt_revenda_cep"  size="10" maxlength="10" value="" readonly>
				</td>
			</tr>
		</table>

		<table  width='700'  border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cód. EAN</font>
				<br>
				<input class="frm" type="text" name="txt_cod_ean" id="txt_cod_ean" size="30" maxlength="50" value="" readonly>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data do Faturamento</font>
				<br>
				<input class="frm" type="text" name="txt_data_venda" id="txt_data_venda" size="12" maxlength="10" value="" readonly>

				
				<input class="frm" type="hidden" name="txt_revenda_endereco"    id="txt_revenda_endereco" value="">
				<input class="frm" type="hidden" name="txt_revenda_numero"      id="txt_revenda_numero" value="">
				<input class="frm" type="hidden" name="txt_revenda_complemento" id="txt_revenda_complemento"  value="">
				<input class="frm" type="hidden" name="txt_revenda_bairro"      id="txt_revenda_bairro"  value="">
				<input class="frm" type="hidden" name="txt_revenda_cidade"      id="txt_revenda_cidade" value="">
				<input class="frm" type="hidden" name="txt_revenda_estado"      id="txt_revenda_estado"  value="" >

				<input class="frm" type="hidden" name="produto_voltagem"      id="produto_voltagem"  value="" >

			</td>
		</tr>
		</table>

		<table  width='700'  border="0" cellspacing="5" cellpadding="0">
			<tr valign='top'>
				<td>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif" color='red'>
					AS INFORMAÇÕES AUTOMÁTICAS QUE ESTÃO ACIMA SÃO AS MESMAS DA NOTA FISCAL DO CONSUMIDOR?
					</font>
				</td>
				<td>
					<input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('sim');" value="sim"> Sim
				</td>
				<td>
					<input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('nao');" value="nao"> Não
				</td>

			</tr>
		</table>

<?
		}
?>

		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign="top">
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> <? if ($login_fabrica == 5) { ?>  onblur="fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')" <? } ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
				<br>
				<?		if($login_fabrica ==45){ // HD 31076
							$maxlength = "14";
						}else{
							$maxlength = "8";
						}
				?>
				<input class="frm" type="text" name="nota_fiscal"  size="8"  maxlength="<? echo $maxlength; ?>" id="nota_fiscal" value="<? echo $nota_fiscal ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
				<br>
				<input class="frm" type="text" name="data_nf"    size="12" maxlength="10" value="<? echo $data_nf ?>" tabindex="0" ><br><font face='arial' size='1'>Ex.: 25/11/2007</font>
			</td>
		</tr>
		</table>

		<table width="100%" border="0" cellspacing="5" cellpadding="2">
		<tr>
			<? if ($login_fabrica == 7 AND $os_manutencao == 't') { # HD 32143 ?>
					<input type='hidden' name="consumidor_revenda" value='<? if (strlen($consumidor_revenda)==0 or strlen($os)==0) {echo 'C';}else{echo $consumidor_revenda;}?>'>
			<?}else{?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>&nbsp;<input type="radio" name="consumidor_revenda" value='C' <? if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked"; ?> <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
			</td>
			
			<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">ou</font></td>

			<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Revenda</font>&nbsp;<input type="radio" name="consumidor_revenda" value='R' <? if ($consumidor_revenda == 'R') echo " checked"; ?>>&nbsp;&nbsp;</td>
			<? } ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Aparência do Produto</font>
				<br>
				<? if ($login_fabrica == 20) {
					echo "<select name='aparencia_produto' size='1'>";
					echo "<option value=''></option>";

					echo "<option value='NEW' ";
					if ($aparencia_produto == "NEW") echo " selected ";
					echo "> Bom Estado </option>";

					echo "<option value='USL' ";
					if ($aparencia_produto == "USL") echo " selected ";
					echo "> Uso intenso </option>";

					echo "<option value='USN' ";
					if ($aparencia_produto == "USN") echo " selected ";
					echo "> Uso Normal </option>";

					echo "<option value='USH' ";
					if ($aparencia_produto == "USH") echo " selected ";
					echo "> Uso Pesado </option>";

					echo "<option value='ABU' ";
					if ($aparencia_produto == "ABU") echo " selected ";
					echo "> Uso Abusivo </option>";

					echo "<option value='ORI' ";
					if ($aparencia_produto == "ORI") echo " selected ";
					echo "> Original, sem uso </option>";

					echo "<option value='PCK' ";
					if ($aparencia_produto == "PCK") echo " selected ";
					echo "> Embalagem </option>";

					echo "</select>";
				}else if($login_fabrica==50){
					echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onChange=\"javascript: this.value=this.value.toUpperCase();\" onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar?ncia externa do aparelho deixado no balc?o.');\">";
				}else{
					echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar?ncia externa do aparelho deixado no balc?o.');\">";
				}
				?>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Acessórios</font>
				<br>
				<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acess?rios deixados junto ao produto.');">
			</td>
<? if ($login_fabrica == 1 AND 1==2) { # retirado por Fabio a pedido da Lilian - 28/12/2007 - Cadastro de troca somente na OS TROCA?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Troca faturada</font><br>
				<input class="frm" type="checkbox" name="troca_faturada" value="t"<? if ($troca_faturada == 't') echo " checked";?>>
			</td>
<? } ?>
		</tr>

		</table>
		
		<p>

		<center>
		<? if ($login_fabrica == 3){ #Chamado 11083 - Fabio ?>
			<? if($orientacao_sac!="null" AND strlen($orientacao_sac)>0) { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				Orientações do SAC ao Posto Autorizado
				</font>
				<br>
				<textarea name='orientacao_sac_anterior' rows='4' cols='50' readonly='readonly' style='background-color:#FBFBFB;border:1px solid #4D4D4D'><? echo trim($orientacao_sac); ?></textarea>
				<br />
			<? } ?>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">
			Adicionar Orientação do SAC ao Posto Autorizado
			</font>
			<br/>
			<textarea name='orientacao_sac' rows='4' cols='50'></textarea>
		<? }else{?>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">
			Orientações do SAC ao Posto Autorizado
			</font>
			<br>
			<textarea name='orientacao_sac' rows='4' cols='50'><? if($orientacao_sac!="null") echo trim($orientacao_sac); ?></textarea>
		<? } ?>
		<br />
		</center>

<!--		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
		<hr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Apar?ncia do Produto</font>
				<br>
				<input class="frm" type="text" name="aparencia_produto" size="35" value="<? echo $aparencia_produto ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Acess?rios</font>
				<br>
				<input class="frm" type="text" name="acessorios" size="35" value="<? echo $acessorios ?>" >
			</td>
		</tr>
		</table>-->

		<?
		if ($login_fabrica <> 7) {
			echo "<input class='frm' type='hidden' name='obs' size='50' value='$obs'>";
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
		<tr style='font-size:10px' valign='top'>
			<td valign='top'>
				<fieldset class='valores' style='height:140px;'>
				<legend>Deslocamento</legend>
					<div>
					<label for="cobrar_deslocamento">Isento:</label>
					<input type='radio' name='cobrar_deslocamento' value='isento' onClick='atualizaCobraDeslocamento(this)' <? if (strtolower($cobrar_deslocamento) == 'isento') echo "checked";?>>
					<br>
					<label for="cobrar_deslocamento">Por Km:</label>
					<input type='radio' name='cobrar_deslocamento' value='valor_por_km' <? if ($cobrar_deslocamento == 'valor_por_km') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
					<br />
					<label for="cobrar_deslocamento">Taxa de Visita:</label>
					<input type='radio' name='cobrar_deslocamento' value='taxa_visita' <? if ($cobrar_deslocamento == 'taxa_visita') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
					<br />
					</div>

					<div name='div_taxa_visita' <? if ($cobrar_deslocamento != 'taxa_visita') echo " style='display:none' "?>>
						<label for="taxa_visita">Valor:</label>
						<input type='text' name='taxa_visita' value='<? echo number_format($taxa_visita ,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br />
					</div>

					<div <? if ($cobrar_deslocamento != 'valor_por_km' or strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_valor_por_km'>
						<label for="veiculo">Carro:</label>
						<input type='radio' name='veiculo' value='carro' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) != 'caminhao') echo "checked";?>>
						<input type='text' name='valor_por_km_carro' value='<? echo number_format($valor_por_km_carro,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' >
						<br>
						<label for="veiculo">Caminhão:</label>
						<input type='radio' name='veiculo' value='caminhao' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) == 'caminhao') echo "checked";?> >
						<input type='text' name='valor_por_km_caminhao' class='frm' value='<? echo number_format($valor_por_km_caminhao,2,',','.') ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<input type='hidden' name='valor_por_km' value='<? echo $valor_por_km ?>'>
					</div>

<?if  (1==2){ #HD 32483 ?>
					<div <? if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_desconto_deslocamento'>
						<label>Desconto:</label>
						<input type='text' name='desconto_deslocamento' value="<? echo $desconto_deslocamento ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
					</div>
<?}?>
				</fieldset>
			</td>
			<td>
				<fieldset class='valores' style='height:140px;'>
					<legend>Mão de Obra</legend>
					<div>
					<label for="cobrar_hora_diaria">Diária:</label>
					<input type='radio' name='cobrar_hora_diaria' value='diaria' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'diaria') echo "checked";?>>
					<br>
					<label for="cobrar_hora_diaria">Hora Técnica:</label>
					<input type='radio' name='cobrar_hora_diaria' value='hora' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'hora') echo "checked";?>>
					<br>
					</div>
					<div <? if ($cobrar_hora_diaria != 'hora') echo " style='display:none' " ?> name='div_hora'>
						<label>Valor:</label>
						<input type='text' name='hora_tecnica' value='<? echo number_format($hora_tecnica,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br>
<?/*						<!--<br>
						<label>Desconto:</label>
						<input type='text' name='desconto_hora_tecnica' value="<? echo $desconto_hora_tecnica ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %-->
*/?>
					</div>
					<div <? if ($cobrar_hora_diaria != 'diaria') echo " style='display:none' " ?> name='div_diaria'>
						<label>Valor:</label>
						<input type='text' name='valor_diaria' value="<? echo number_format($valor_diaria,2,',','.') ?>" class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br>
<?/*						<!--						<br>
						<label>Desconto:</label>
						<input type='text' name='desconto_diaria' value="<? echo $desconto_diaria ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
					</div>
				</fieldset>
			</td>
			<td>
				<fieldset class='valores' style='height:140px;'>
					<legend>Outros Serviços</legend>
					<div>
						<label>Regulagem:</label>
						<input type="checkbox" name="cobrar_regulagem" value="t" <? if ($cobrar_regulagem=='t') echo "checked" ?>>
						<br />
						<label>Valor:</label>
						<input type="text" name="regulagem_peso_padrao" value="<? echo number_format($regulagem_peso_padrao,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br />
<?/*						<!--						<br />
						<label>Desconto:</label>
						<input type='text' name='desconto_regulagem' value="<? echo $desconto_regulagem ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
						<br />
-->
*/?>
						<br />
						<label>Certificado:</label>
						<input type="checkbox" name="cobrar_certificado" value="t" <? if ($cobrar_certificado=='t') echo "checked" ?>>
						<br />
						<label>Valor:</label>
						<input type="text" name="certificado_conformidade" value="<? echo number_format($certificado_conformidade,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br>
<?/*						<!--						<br />
						<label>Desconto:</label>
						<input type='text' name='desconto_certificado' value="<? echo $desconto_certificado ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
						</div>
				</fieldset>
			</td>
		</tr>
		<tr style='font-size:10px' valign='top'>
			<td nowrap  valign='top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>% DESCONTO PEÇAS</font><BR>
				<input type='text' name='desconto_peca' class='frm' value='<?=$desconto_peca?>' size='10' maxlength='5'>
			</td>
			<td nowrap  valign='top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Condição de Pagamento</font><BR>
				<SELECT NAME='condicao' style='width:150px'>
				<OPTION VALUE=''></OPTION>
				<?
				$sql = " SELECT condicao,
								codigo_condicao, 
								descricao
						FROM tbl_condicao
						WHERE fabrica = $login_fabrica 
						AND visivel is true
						ORDER BY codigo_condicao ";
				$res = pg_exec ($con,$sql) ;
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option ";
					if ($condicao== pg_result ($res,$i,condicao) ) echo " selected ";
					echo " value='" . pg_result ($res,$i,condicao) . "'>" ;
					echo pg_result ($res,$i,descricao) ;
					echo "</option>";
				}
				?>
				</SELECT>
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

		<?
		if ($login_fabrica == 7) {
		echo "<input type='checkbox' name='imprimir_os' id='imprimir_os' value='imprimir'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Imprimir OS</font>";
		}
		?>

		<input type="hidden" name="btn_acao" value="">
<?
if (strlen ($os) > 0) {
?>
		<img src='imagens/btn_alterarcinza.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ;  document.frm_os.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Alterar os itens da Ordem de Serviço" border='0'>
		<img src='imagens_admin/btn_apagar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Apagar a Ordem de Serviço" border='0'>
<?
}else{
?>
		<img src='imagens/btn_continuar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Continuar com Ordem de Serviço" border='0'>
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

<?
if(strlen($os) > 0) {
	if($login_fabrica == 11 OR $login_fabrica == 45){
/*		echo "<form method='post' name='frm_cancelar' action='$PHP_SELF?os=$os'>";
		echo "<table width='600' align='center' border='2' cellspacing='0' bgcolor='#F7D7D7' style='' class=''>";
		echo "<input type='hidden' name='os' value='$os'>";
		echo "<input type='hidden' name='cancelar' value=''>";
		echo "<tr>";
		echo "<td align='center' style='color: #F7D7D7'> ";
		echo "<font color='#3300CC' size='+1'> <b>Cancelar OS?</b> </font> ";
			echo "<table border='0' cellspacing='0' width='600'>";
			echo "<tr bgcolor='#F7D7D7' class='Conteudo'>";
			echo "<td align='left'><b>Motivo:</b></td>";
			echo "<td align='left'><textarea name='motivo_cancelamento' cols='100' rows='3' class='Caixa'>$motivo_cancelamento</textarea></td>";
			echo "</tr>";
			echo "</table>";
		echo "<input type='button' value='Cancelar' name='btn_cancelar' id='btn_cancelar' onclick=\"javascript: cancelar_os();\">";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
*/
		echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#FFDDDD' style='border:#660000 1px solid;' >";
		echo "<tr>";
		echo "<td align='center' style='color: #ffffff'> ";
		echo "<a href='os_cancelar.php?os=$os' style='font-size:12px'>Cancelar esta OS e informar o motivo</a>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "<center><br>";
	}





	$sql = "SELECT os_troca FROM tbl_os_troca WHERE os = $os AND pedido IS NULL ";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res)>0) {
		$troca_efetuada =  pg_result($res,0,os_troca);
		echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#E8EEFF' style='border:#3300CC 1px solid;' >";
		echo "<tr>";
		echo "<td align='center' > ";
		echo "<a href='$PHP_SELF?os=$os' style='font-size:12px'>Produto já trocado!<br> Clique aqui para trocar o produto novamente! </A>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	$xsql = "SELECT  os_troca    ,
					gerar_pedido ,
					referencia   ,
					descricao    ,
					ressarcimento
			FROM   tbl_os_troca
			LEFT JOIN   tbl_peca USING(peca)
			WHERE  os = $os
			AND    gerar_pedido IS NOT TRUE";
	$xres = pg_exec ($con,$xsql);
	if(pg_numrows($xres)>0) {
		$troca_efetuada      =  pg_result($xres,0,os_troca);
		$troca_referencia    =  pg_result($xres,0,referencia);
		$troca_descricao     =  pg_result($xres,0,descricao);
		$troca_ressarcimento = pg_result($xres,0,ressarcimento);
		echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#E8EEFF' style='border:#3300CC 1px solid;' >";
		echo "<tr>";
		if($troca_ressarcimento=='t') echo "<td align='center' ><h1>Ressarcimento Financeiro</h1> ";
		else                          echo "<td align='center' ><h1>Troca pelo produto: $troca_referencia - $troca_descricao</h1> ";
		echo "<a href='$PHP_SELF?os=$os&gerar_pedido=ok' style='font-size:12px'>Esta troca não irá gerar pedido!<br> Clique aqui para que esta troca gere pedido </A>";
		echo "</td>";
		echo "</tr>";
		echo "</table><br>";

	}


	if (($troca_garantia == 't' AND !isset($troca_efetuada))OR $ok=='s') {
		echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#3366FF' style='' class=''>";
		echo "<tr>";
		echo "<td align='center' style='color: #ffffff'> ";
		echo "<font color='#ffffff' size='+1'> <b> Produto já trocado </b> </font> </a> ";
		echo "</td>";
		echo "</tr>";
		echo "</table>";

	}else{

		if ($login_fabrica <> 7 and $login_fabrica <> 50 and $login_fabrica <> 51){
?>
		<form method='post' name='frm_troca' action='<? echo $PHP_SELF ?>'>
		<input type='hidden' name='os' value='<? echo $os ?>'>
		<!-- colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca -->
		<input type='hidden' name='orient_sac' value=''>

		<table width='500' align='center' border='2' cellspacing='0' bgcolor='#9DB6FF' style='' class=''>
		<tr>
		<td align='center' style='color: #3300cc'> 

		<font color='#3300CC' size='+1'> <b> Trocar Produto em Garantia </b>  <?=$total1?></font> </a> 
		<br>Responsável pela troca: <b><?=$login_login?></b> | OS <b><?=$sua_os?></b>

		<? 
		if ($login_fabrica == 3 OR $login_fabrica == 45 or $login_fabrica==35 OR $login_fabrica == 11 OR $login_fabrica==25) {
			echo "<table border='0' cellspacing='0' width='600'>";
			echo "<tr bgcolor='#668CFF' class='Conteudo'>";
			echo "<td align='left'><b>Marca do Produto</td>";
			echo "<td align='left'>";
			$sql = "SELECT  tbl_marca.*
				FROM      tbl_os 
				JOIN      tbl_produto USING(produto)
				JOIN      tbl_marca ON tbl_produto.marca = tbl_marca.marca
				WHERE     tbl_marca.fabrica = $login_fabrica
				AND       tbl_os.os = $os
				ORDER BY tbl_marca.nome;";
			
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_marca     = trim(pg_result($res,$x,marca));
					$aux_descricao = trim(pg_result($res,$x,nome));

					echo "<input type='hidden' name='marca' id='marca' value='$aux_marca'> <b><font color='#990000'>$aux_descricao</font></b><br>";
				}
			}else $aux_marca = '001';
			echo "</td>";
			echo "</tr>";
			echo "<tr bgcolor='#668CFF' class='Conteudo'>";
			echo "<td align='left'><b>Família do Produto</td>";
			echo "<td align='left'>";
			$sql = "SELECT  *
					FROM    tbl_familia
					WHERE   tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select class='Caixa' style='width: 400px;' name='familia_troca' id='familia_troca' onChange='listaProduto(this.value,\"$aux_marca\");'>\n";
				echo "<option value=''>ESCOLHA</option>\n";

				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_familia   = trim(pg_result($res,$x,familia));
					$aux_descricao = trim(pg_result($res,$x,descricao));

					echo "<option value='$aux_familia'>$aux_descricao</option>\n";
				}
				echo "</select>\n";
			}
			echo "</td>";
			echo "</tr>";

			echo "<tr bgcolor='#668CFF' class='Conteudo'>";
			echo "<td align='left'><b>Trocar pelo produto:</td>";
			echo "<td align='left'>";
			echo "<select name='troca_garantia_produto'  class='Caixa' style='width: 400px;' >";
			echo "<option id='opcoes' value=''></option>";
			echo "</select>";
			echo "</td>";
			echo "</tr>";

//			echo "<option value='-1' >RESSARCIMENTO FINANCEIRO</option>";
			echo "<tr bgcolor='#668CFF' class='Conteudo'>";
			echo "<td align='left'><b>Causa da Troca</td>";
			echo "<td align='left'>";
			$sql = "SELECT  tbl_causa_troca.causa_troca,
							tbl_causa_troca.codigo     ,
							tbl_causa_troca.descricao
					FROM tbl_causa_troca
					WHERE tbl_causa_troca.fabrica = $login_fabrica
					AND tbl_causa_troca.ativo     IS TRUE
					ORDER BY tbl_causa_troca.codigo,tbl_causa_troca.descricao";
			$resTroca = pg_exec ($con,$sql);
			echo "<select name='causa_troca' size='1' class='Caixa' style='width: 400px;'>";
			echo "<option value='' ></option>";
			for ($i = 0 ; $i < pg_numrows($resTroca) ; $i++) {
				echo "<option value='" . pg_result ($resTroca,$i,causa_troca) . "'";
				echo ">" . pg_result ($resTroca,$i,codigo) . " - " . pg_result ($resTroca,$i,descricao) . "</option>";
			}
			echo "</select>";
			echo "</td>";
			echo "</tr>";

			echo "<tr bgcolor='#668CFF' class='Conteudo'>";
			echo "<td align='left'><b>Número de Registro</td>";
			echo "<td align='left'><input type='text' name='ri' value='$ri' size='10' maxlength='10' class='Caixa'>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<b>Observação para nota fiscal</b><br><textarea name='observacao_pedido' cols='100' rows='3' class='Caixa'></textarea><br>";

			$sql = "SELECT tbl_os_item.os_item,
							tbl_peca.referencia,
							tbl_peca.descricao,
							tbl_os_item.qtde
					FROM tbl_os_item
					JOIN tbl_os_produto USING (os_produto)
					JOIN tbl_peca       USING (peca)
					WHERE os = $os";
			$resTroca = pg_exec ($con,$sql);
			$qtde_itens = pg_numrows($resTroca);
			echo "<input type='hidden' name='qtde_itens' value='$qtde_itens'>";
			if(pg_numrows($resTroca)>0) {
				echo "<br><table border='0' cellspacing='0' width='600'>";
				echo "<tr bgcolor='#668CFF' class='Conteudo'>";
				echo "<td></td>";
				echo "<td align='left'> <b>Referência</td>";
				echo "<td align='left'> <b>Peça</td>";
				echo "<td align='left'> <b>Qtde</td>";
				echo "</tr>";
				for ($i = 0 ; $i < pg_numrows($resTroca) ; $i++) {
					$os_item         = pg_result ($resTroca,$i,os_item) ;
					$peca_referencia = pg_result ($resTroca,$i,referencia) ;
					$peca_descricao  = pg_result ($resTroca,$i,descricao) ;
					$peca_qtde       = pg_result ($resTroca,$i,qtde)      ;
					if($cor == "#D7E1FF") $cor = '#F0F4FF';
					else                  $cor = '#D7E1FF';
					echo "<tr bgcolor='$cor' class='Conteudo'>";
					echo "<td align='left'> <input type='checkbox' value='$os_item' name='os_item_$i'></td>";
					echo "<td align='left'> $peca_referencia</td>";
					echo "<td align='left'> $peca_descricao</td>";
					echo "<td align='left'> $peca_qtde</td>";
					echo "</tr>";
				}

				echo "<tr class='Conteudo'>";
				echo "<td align='left' colspan='4'>&nbsp;<img src='imagens/seta_checkbox.gif'> Se o motivo da troca foi peça, selecione a peça que originou a troca</td>";

				echo "<tr bgcolor='#FFA6A8' class='Conteudo'>";
				echo "<td align='center' colspan='4'><u> Em caso de troca TODAS as peças acima serão canceladas</td>";
				echo "</tr>";

				echo "</table><br>";
			}
			echo "<br><table border='0' cellspacing='0' width='600'>";
			echo "<tr bgcolor='#668CFF' class='Conteudo'>";
			echo "<td align='left'><b>Setor Responsável</td>";
			echo "<td align='left'><b>Situação do Atendimento</td>";
			echo "</tr>";
			echo "<tr bgcolor='#F0F4FF' class='Conteudo'>";
			echo "<td align='left'>";
			echo "<input type='radio' name='setor' value='Revenda'> Revenda<br>";
			echo "<input type='radio' name='setor' value='Carteira'> Carteira<br>";
			echo "<input type='radio' name='setor' value='SAC'> SAC<br>";
			echo "<input type='radio' name='setor' value='Procon'> Procon<br>";
			echo "<input type='radio' name='setor' value='SAP'> SAP<br>";
			echo "</td>";
			echo "<td align='left' valign='top'>";
			echo "<input type='radio' name='situacao_atendimento' value='0'> Produto em garantia<br>";
			echo "<input type='radio' name='situacao_atendimento' value='50'> Faturado 50%<br>";
			echo "<input type='radio' name='situacao_atendimento' value='100'> Faturado 100%<br>";
			echo "<hr width='95%'><input type='checkbox' name='gerar_pedido' id='gerar_pedido' value='t'> Gerar pedido";
			echo "</td>";
			echo "</tr>";
			if($login_fabrica==3){
				echo "<tr bgcolor='#668CFF' class='Conteudo'>";
				echo "<td align='left' ><b>Destino</td>";
				echo "<td align='left' ><b>Modalidade de Transporte</td>";
				echo "</tr>";
				echo "<tr bgcolor='#F0F4FF' class='Conteudo'>";
				echo "<td align='left' valign='top'>";
				echo "<input type='radio' name='envio_consumidor' value='t'> direto ao consumidor<br>";
				echo "<input type='radio' name='envio_consumidor' value='f' ";
				if(strlen($envio_consumidor)==0) echo " CHECKED ";
				echo "> para o posto<br>";
				echo "</td>";

				echo "<td align='left'>";
				echo "<input type='radio' name='modalidade_transporte' value='urgente'";
				echo "> RI Urgente<br>";
				echo "<input type='radio' name='modalidade_transporte' value='normal' ";
				echo "> RI Normal<br>";
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";

		}else{
			echo "<p><b>Trocar pelo Produto</b> ";
			echo "<input type='text' name='troca_garantia_produto' size='10' maxlength='10' value='$troca_garantia_produto'>" ;
			echo "<br>";
			if($login_fabrica==20) echo "<b>Valor para Troca</b>";
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
		if($login_fabrica==3 OR $login_fabrica==45 or $login_fabrica==35 OR $login_fabrica==25){
			echo "<input type='button' value='Trocar' onclick=\"javascript: fazer_troca(); \">";
		}else{
			echo "<input type='button' value='Trocar' onclick=\"javascript:
			if (confirm ('Confirma Troca') == true ) {
				document.frm_troca.btn_troca.value='trocar';
				document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value ;
				document.frm_troca.submit(); 
			} \">";
		
		}



		?>
		</td>
		</tr>
		</table>

		</form>

<?		} 
	}
}
?>

<p>

<? include "rodape.php";?>
