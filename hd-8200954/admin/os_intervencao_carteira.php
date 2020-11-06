<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

if ($login_fabrica!=3){
	header("Location: menu_callcenter.php");
	exit();
}

if ($login_fabrica==3) {
	$id_servico_realizado = 20;
	$id_servico_realizado_ajuste = 96;
}

if(strlen($id_servico_realizado)==0){ # padrao BRITANIA
	$id_servico_realizado=20;
	$id_servico_realizado_ajuste = 96;
}

$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

function converte_data($date)
{
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

if (strlen(trim($_GET['os']))>0)	$os=trim($_GET['os']);
else								$os=trim($_POST['os']);

if (strlen(trim($_GET['retirar_intervencao']))>0){
	$retirar_intervencao = trim($_GET['retirar_intervencao']);
}

if (isset($_GET['msg_erro']) && strlen(trim($_GET['msg_erro']))>0)	$msg_erro=trim($_GET['msg_erro']);
if (isset($_GET['msg']) && strlen(trim($_GET['msg']))>0)			$msg=trim($_GET['msg']);

$str_filtro = "&btnacao=filtrar";
$ordem = "nome";
$consumidor_revenda = 'todas';

if (strlen(trim($_GET['janela']))>0 AND trim($_GET['janela'])=="sim"){
		$os   = trim($_GET['os']);
		$tipo = trim($_GET['tipo']);
	
		$sql =  "SELECT tbl_os.os                                                        ,
					tbl_os.sua_os                                                     ,
					LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')   AS data_nf          ,
					current_date - tbl_os.data_abertura as dias_aberto,
					tbl_os.data_abertura   AS abertura_os       ,
					tbl_os.serie                                                      ,
					tbl_os.consumidor_nome                                            ,
					tbl_posto_fabrica.codigo_posto                                    ,
					tbl_posto.nome                              AS posto_nome         ,
					tbl_posto.fone                              AS posto_fone         ,
					tbl_produto.referencia                      AS produto_referencia ,
					tbl_produto.descricao                       AS produto_descricao  ,
					tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (67,68,70) ORDER BY data DESC LIMIT 1) AS reincindente,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (116,117) ORDER BY data DESC LIMIT 1) AS status_descricao,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (116,117) ORDER BY data DESC LIMIT 1) AS status_os,
					(SELECT data FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (116,117) ORDER BY data DESC LIMIT 1) AS status_pedido,
					(SELECT TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (116,117) ORDER BY data DESC LIMIT 1) AS status_data2
				FROM tbl_os
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto 
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.os=$os";
		$res = pg_exec($con,$sql);
		$total=pg_numrows($res);

		if ($total>0){
			$os                 = trim(pg_result($res,0,os));
			$sua_os             = trim(pg_result($res,0,sua_os));
			$data_nf            = trim(pg_result($res,0,data_nf));
			$digitacao          = trim(pg_result($res,0,digitacao));
			$abertura           = trim(pg_result($res,0,abertura));
			$serie              = trim(pg_result($res,0,serie));
			$consumidor_nome    = trim(pg_result($res,0,consumidor_nome));
			$codigo_posto       = trim(pg_result($res,0,codigo_posto));
			$posto_nome         = trim(pg_result($res,0,posto_nome));
			$posto_fone         = trim(pg_result($res,0,posto_fone));
			
			$produto_referencia = trim(pg_result($res,0,produto_referencia));
			$produto_descricao  = trim(pg_result($res,0,produto_descricao));
			$posto_fone         = substr(trim(pg_result($res,0,posto_fone)),0,17);
			$status_os          = trim(pg_result($res,0,status_os));
			$status_descricao   = trim(pg_result($res,0,status_descricao));
			$dias_abertura      = trim(pg_result($res,0,dias_aberto));

			echo "<html><head><title>";
			echo "Intervenção";
			echo "</title></head><body>";
			echo "<style>body{padding:2px;margin:0px;font-size:10px;font-family:Verdana,Tahoma,Arial}.frm {BORDER: '#888888 1px solid';FONT-WEIGHT: 'bold'; FONT-SIZE: '8pt'; BACKGROUND-COLOR: '#f0f0f0';}</style>";
			echo "<form name='frm_form' method='post' action='$PHP_SELF'>";
			echo "<input name='os' value='$os' type='hidden'>";
			echo "<input type='hidden' name='btn_tipo' value='$tipo'>";
			echo "<div>";
			echo "<h4 style='width:100%;background-color:#596D9B;color:white;text-align:center;padding:5px;margin:0px'>INTERVENÇÃO</h4>\n";

			if($tipo=='cancelar'){
				echo "<h4 style='font-size:12px;width:100%;background-color:#EF4B4B;color:white;text-align:center;padding:3px;margin:0px'>CANCELAR PEDIDO</h4>\n";
			}

			if($tipo=='autorizar'){
				echo "<h4 style='font-size:12px;width:100%;background-color:#34BC3F;color:white;text-align:center;padding:3px;margin:0px'>AUTORIZAR PEDIDO</h4>\n";
			}

			echo "<h4 style='font-size:12px;width:100%;background-color:#E1E1E1;margin:0px;'>Posto</h4>";
			echo "<br>Código: $codigo_posto - $posto_nome\n";
			echo "<br>Telefone: $posto_fone\n";
			echo "<br>";
			echo "<br><h4 style='font-size:12px;width:100%;background-color:#E1E1E1;margin:0px;'>Ordem de Serviço</h4>";
			echo "<br>Número OS <b>$sua_os</b>\n";
			echo "<br>Data Abertura: <b>$abertura</b>\n";
			echo "<br>";
			echo "Data da Nota Fiscal: <b>$data_nf</b> \n";
			echo "<br>";
			echo "<br><h4 style='font-size:12px;width:100%;background-color:#E1E1E1;margin:0px;'>Produto</h4>";
			echo "<br>";
			echo "Referência: $produto_referencia - $produto_descricao \n";

			$sql_peca = "SELECT		tbl_os_item.os_item,
									tbl_peca.troca_obrigatoria       AS troca_obrigatoria,
									tbl_peca.intervencao_carteira    AS intervencao_carteira,
									tbl_peca.referencia              AS referencia,
									tbl_peca.descricao               AS descricao,
									tbl_peca.peca AS peca,
									tbl_os_item.servico_realizado AS servico_realizado
						FROM tbl_os_produto
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_peca USING(peca)
						WHERE tbl_os_produto.os=$os";
			$res_peca = pg_exec($con,$sql_peca);
			$resultado = pg_numrows($res_peca);
			if ($resultado>0) {
				echo "<br>";
				echo "<br><h4 style='font-size:12px;width:100%;background-color:#E1E1E1;margin:0px;'>Peças</h4>";
				for($j=0;$j<$resultado;$j++){
					$peca_referencia         = trim(pg_result($res_peca,$j,referencia));
					$peca_descricao          = trim(pg_result($res_peca,$j,descricao));
					$intervencao_carteira    = trim(pg_result($res_peca,$j,intervencao_carteira));
					$servico_realizado       = trim(pg_result($res_peca,$j,servico_realizado));

					if ($intervencao_carteira=='t') {
						$intervencao_carteira=" <b>*</b> ";
					} else {
						$intervencao_carteira="";
					}

					if ($servico_realizado==$id_servico_realizado){
						$servico_realizado="<b style='color:gray;font-size:9px;font-weight:normal'>(Troca de Peça)</b>";
					}else{
						$servico_realizado="<b style='color:gray;font-size:9px;font-weight:normal'>(não gera pedido)</b>";
					}
					echo "<br>$intervencao_carteira $peca_referencia - $peca_descricao $servico_realizado $bloqueada_garantia \n";
				}
				echo "<br><b style='color:gray;font-size:9px;font-weight:normal'>* Peças com intervenção de Carteira</b>";
			}
			$sql = "SELECT status_os,to_char(data,'DD/MM/YYYY') as data,observacao,admin FROM tbl_os_status WHERE os=$os AND status_os IN (116,117) ORDER BY data DESC LIMIT 1";
			$res = pg_exec($con,$sql);
			$total=pg_numrows($res);
			if ($total>0){
				$st_os   = trim(pg_result($res,0,status_os));
				$st_data = trim(pg_result($res,0,data));
				$st_obs  = trim(pg_result($res,0,observacao));
				$st_admin= trim(pg_result($res,0,admin));
			}

			if ($tipo=="cancelar"){
				$msg_titulo="Justificativa do Cancelamento";
			}else{
				$msg_titulo="Justificativa da Autorização";
			}

			echo "<br>";
			echo "<br><b style='width:100%;background-color:#A9C1E0;text-align:center'>$msg_titulo</b>";
			echo "<br>";
			echo "<textarea NAME='justificativa' style='width:100%'rows='5' class='frm' maxlength='40'></textarea>";
			echo "<br>";
			echo "<input type='hidden' name='btn_acao' value=''>";
			echo "<center><img src='imagens/btn_gravar.gif' onclick=\"javascript: 
							if (document.frm_form.justificativa.value != '' ){ 
								if (document.frm_form.btn_acao.value == '' ) {
									if (confirm('Deseja continuar?')) {
										document.frm_form.btn_acao.value='gravar' ;
										document.frm_form.submit();
									} 
								}
								else { 
									alert ('Aguarde submissão');
								}
							}
							else{
								alert('Digite a justificativa!');
							}
							\" ALT='Gravar' border='0' style='cursor:pointer;'></center>";
			echo "</div>";
			echo "</form>";
			echo "</body></html>";
	exit();
	}
}

if ( (isset($_POST['btnacao']) > 0 && $_POST['btnacao'] == 'filtrar') || (isset($_GET['btnacao']) > 0 && $_GET['btnacao'] == 'filtrar') ) {

	if (strlen(trim($_POST['ordem'])) > 0)	$ordem = trim($_POST['ordem']);
	else									$ordem = trim($_GET["ordem"]);

	if (strlen($ordem)>0){
		if ($ordem=='nome'){
			$sql_ordem = " order by tbl_posto.nome ASC";
		}
		if ($ordem=='data_abertura'){
			$sql_ordem = " order by tbl_os.data_abertura ASC";
		}
		if ($ordem=='data_pedido'){
			$sql_ordem = " order by status_pedido ASC ";
		}
		$str_filtro .= "&ordem=$ordem";
	}


	if (strlen(trim($_POST['consumidor_revenda'])) > 0)	$consumidor_revenda = trim($_POST['consumidor_revenda']);
	else												$consumidor_revenda = trim($_GET["consumidor_revenda"]);

	if (strlen($consumidor_revenda)>0){
		if ($consumidor_revenda=='todas'){
			$sql_consumidor_revenda = " ";
		}
		if ($consumidor_revenda=='revenda'){
			$sql_consumidor_revenda = " AND tbl_os.consumidor_revenda = 'R' ";
		}
		if ($consumidor_revenda=='consumidor'){
			$sql_consumidor_revenda = " AND tbl_os.consumidor_revenda = 'C' ";
		}
		$str_filtro .= "&consumidor_revenda=$consumidor_revenda";
	}



	if (strlen(trim($_POST['regiao'])) > 0)					$regiao				= trim($_POST['regiao']);
	else													$regiao				= trim($_GET["regiao"]);
	if (strlen(trim($_POST['posto_codigo'])) > 0)			$posto_codigo		= trim($_POST['posto_codigo']);
	else													$posto_codigo		= trim($_GET["posto_codigo"]);
	if (strlen(trim($_POST['posto_nome'])) > 0)				$posto_nome			= trim($_POST['posto_nome']);
	else													$posto_nome			= strtoupper(trim($_GET["posto_nome"]));
	if (strlen(trim($_POST['referencia'])) > 0)				$referencia			= trim($_POST['referencia']);
	else													$referencia			= trim($_GET["referencia"]);
	if (strlen(trim($_POST['descricao'])) > 0)				$descricao			= trim($_POST['descricao']);
	else													$descricao			= trim($_GET["descricao"]);
	if (strlen(trim($_POST['produto_referencia'])) > 0)		$produto_referencia = trim($_POST['produto_referencia']);
	else													$produto_referencia = trim($_GET["produto_referencia"]);
	if (strlen(trim($_POST['produto_descricao'])) > 0)		$produto_descricao	= trim($_POST['produto_descricao']);
	else													$produto_descricao	= trim($_GET["produto_descricao"]);
	if (strlen(trim($_POST['peca_referencia'])) > 0)		$peca_referencia	= trim($_POST['peca_referencia']);
	else													$peca_referencia	= trim($_GET["peca_referencia"]);

	if (strlen(trim($_POST['peca_descricao'])) > 0)			$peca_descricao		= trim($_POST['peca_descricao']);
	else													$peca_descricao		= trim($_GET["peca_descricao"]);

	$estados = "";
	if ($regiao){
		switch ($regiao){
			case "1": $estados = "'RS','MG','MT','RN','AP','RR'";
					break;
			case "2": $estados = "'PR','BA','CE','PE','ES','PA','RO','TO'";
					break;
			case "3": $estados = "'SC','RJ','GO','DF','MS','PI','AL'";
					break;
			case "4": $estados = "'SP','MA','SE','AM','PB','AC','EX'";
					break;
		}
		if (strlen($estados)>0){
			$sql_estado = " AND tbl_posto_fabrica.contato_estado IN (".$estados.")";
		}
	}

	if (strlen($peca_referencia)>0 OR strlen($peca_descricao)>0){
		if (strlen($peca_referencia)>0){
			$sql_adicional_2 = " AND tbl_peca.referencia = '$peca_referencia' ";
		}else{
			$sql_adicional_2 = " AND tbl_peca.descricao like '%$peca_descricao%' ";
		}

		$sql = "SELECT  tbl_peca.referencia as ref, tbl_peca.descricao as desc, tbl_peca.peca as peca
			FROM tbl_peca
			WHERE tbl_peca.fabrica=$login_fabrica
			$sql_adicional_2";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)>0){
			$peca_referencia = pg_result ($res,0,ref);
			$peca_descricao  = pg_result ($res,0,desc);
			$peca  = pg_result ($res,0,peca);
			$sql_adicional_2 = " AND tbl_peca.peca = $peca";
			$str_filtro .= "&peca_referencia=$peca_referencia&peca_descricao=$peca_descricao";
		}
	}

	if (strlen($produto_referencia)>0 OR strlen($produto_descricao)>0){
		if (strlen($produto_referencia)>0){
				$sql_adicional_3 = " AND tbl_produto.referencia = '$produto_referencia' ";
		}else {
				$sql_adicional_3 = " AND tbl_produto.descricao like '%$produto_descricao%' ";
		}

		$sql = "SELECT  tbl_produto.referencia as ref, tbl_produto.descricao as desc, tbl_produto.produto as produto
			FROM tbl_produto
			JOIN tbl_familia USING(familia)
			WHERE tbl_familia.fabrica=$login_fabrica
			$sql_adicional_3";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)>0){
			$produto_referencia = pg_result ($res,0,ref);
			$produto_descricao  = pg_result ($res,0,desc);
			$produto  = pg_result ($res,0,produto);
			$sql_adicional_3 = " AND tbl_produto.produto = $produto";
			$str_filtro .= "&produto_referencia=$produto_referencia&produto_descricao=$produto_descricao";
		}
	}

	if (strlen($posto_codigo)>0 OR strlen($posto_nome)>0){

		if (strlen($posto_codigo)>0 OR strlen($posto_nome)>0){
			if (strlen($posto_codigo)>0){
				$sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
			}else{
				$sql_adicional = " AND tbl_posto.nome like '%$posto_nome%' ";
			}
		}
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto as cod, tbl_posto.nome as nome, tbl_posto.posto as posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica=$login_fabrica
				$sql_adicional";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res)>0){
			$posto_codigo = pg_result ($res,0,cod);
			$posto_nome  = pg_result ($res,0,nome);
			$posto  = pg_result ($res,0,posto);
			$sql_adicional = " AND tbl_posto.posto = $posto";
			$str_filtro .= "&posto_codigo=$posto_codigo&posto_nome=$posto_nome";
		}
	}
}


if (trim($_POST['btn_tipo'])=="cancelar" && strlen($os) > 0  ) {

	$os  			=trim($_POST['os']);
	$justificativa	=trim($_POST['justificativa']);
	if (strlen($justificativa)>0){
		$justificativa = "Justificativa: $justificativa";
	}

	$sql = "SELECT sua_os,
					posto
				FROM tbl_os
				WHERE os=$os";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res)>0){
		$sua_os = trim(pg_result($res,0,sua_os));
		$posto = trim(pg_result($res,0,posto));
	}

	$sql = "SELECT status_os,
				to_char(data,'DD/MM/YYYY') as data,
				observacao,admin 
				FROM tbl_os_status 
				WHERE os=$os 
				AND status_os IN (116,117) 
				ORDER BY tbl_os_status.data 
				DESC LIMIT 1";
	$res = pg_exec($con,$sql);
	$total=pg_numrows($res);
	if ($total>0){
		$st_os   = trim(pg_result($res,0,status_os));
		$st_data = trim(pg_result($res,0,data));
		$st_obs  = trim(pg_result($res,0,observacao));
		$st_admin= trim(pg_result($res,0,admin));
		if ($st_os=='64'){
			echo "<html><head><title='Intervenção'></head><body>";
			echo "OS LIBERADA!!!<br><br>id OS: $os<br>EM: $st_data<br>Observacao: $st_obs<br>Admin: $st_admin";
			echo "<script language='javascript'>";
			echo "opener.document.location = '$PHP_SELF';";
			echo "setTimeout('window.close()',5000);";
			echo "</script>";
			echo "</body>";
			echo "</html>";
			exit();
		}
	}
	$res = @pg_exec($con,"BEGIN TRANSACTION");


	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin) 
			VALUES ($os,117,current_timestamp,'Pedido de Peças Cancelado. $justificativa',$login_admin)";
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro)==0){
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
					'Pedido de Peças CANCELADO'           ,
					'O pedido das peças referente a OS $sua_os foi <b>cancelado</b> pela fábrica. <br><br>$justificativa',
					'Pedido de Peças',
					$login_fabrica,
					'f' ,
					't',
					$posto,
					't'
				);";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro)==0){
		$sql =  "UPDATE tbl_os_item SET
						servico_realizado          = $id_servico_realizado_ajuste,
						admin                      = $login_admin                ,
						liberacao_pedido           = FALSE                       ,
						liberacao_pedido_analisado = FALSE                       ,
						data_liberacao_pedido      = NULL
				 WHERE os_item IN (
					SELECT os_item
					FROM tbl_os
					JOIN tbl_os_produto USING(os)
					JOIN tbl_os_item    USING(os_produto)
					JOIN tbl_peca       USING(peca)
					WHERE tbl_os.os                     = $os
					AND   tbl_os.fabrica                = $login_fabrica
					AND   tbl_os_item.servico_realizado = $id_servico_realizado
					AND   tbl_os_item.pedido IS NULL
					AND   tbl_peca.intervencao_carteira IS TRUE
				)";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro)>0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}else {
			$res = @pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "Pedido de peças da OS $sua_os foi cancelado! A OS foi liberada para o posto";
		}
	}

	echo "<html><head><title='Intervenção'></head><body>";
	echo "<script language='javascript'>";
	echo "opener.document.frm_consulta.btnacao.value='filtrar';";
	echo "opener.document.frm_consulta.submit();";

	if (strlen($msg_erro)>0){
		echo "Erro: $msg_erro<br>Faça o processo novamente.";
	}else{
		echo "this.close();";
	}
	echo "</script>";
	echo "</body>";
	echo "</html>";
	exit();
}



if (trim($_POST['btn_tipo'])=="autorizar" && strlen($os) > 0) {
	$os  			=trim($_POST['os']);
	$justificativa	=trim($_POST['justificativa']);
	if (strlen($justificativa)>0){
		$justificativa = "Justificativa: $justificativa";
	}else{
		$msg_erro = "Informe a justificativa!";
	}
	$sql = "SELECT status_os,to_char(data,'DD/MM/YYYY') as data,observacao,admin FROM tbl_os_status WHERE os=$os AND status_os IN (116,117) ORDER BY tbl_os_status.data DESC LIMIT 1";
	echo nl2br($sql);
	$res = pg_exec($con,$sql);
	$total=pg_numrows($res);
	if ($total>0){
		$st_os   = trim(pg_result($res,0,status_os));
		$st_data = trim(pg_result($res,0,data));
		$st_obs  = trim(pg_result($res,0,observacao));
		$st_admin= trim(pg_result($res,0,admin));
		if ($st_os=='64'){
			echo "<html><head><title='Intervenção'></head><body>";
			echo "OS LIBERADA!!!<br><br>id OS: $os<br>EM: $st_data<br>Observacao: $st_obs<br>Admin: $st_admin";
			echo "<script language='javascript'>";
			echo "opener.document.location = '$PHP_SELF';";
			echo "setTimeout('window.close()',3000);";
			echo "</script>";
			echo "</body>";
			echo "</html>";
			exit();
		}
	}

	$res = @pg_exec($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin) 
			VALUES ($os,117,current_timestamp,'Pedido de Peças Autorizado Pela Fábrica $justificativa',$login_admin)";
	echo nl2br($sql);
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}else {
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Pedido de peças da OS $sua_os foi autorizado. A OS foi liberada para o posto";
	}
	echo "<html><head><title='Intervenção'></head><body>";
	echo "<script language='javascript'>";
	echo "opener.document.frm_consulta.btnacao.value='filtrar';";
	echo "opener.document.frm_consulta.submit();";
	echo "this.close();";
	echo "</script>";
	echo "<br>";
	echo "</body>";
	echo "</html>";
	exit();
}

$justific = $_POST['justific'];
if(strlen($justific)==0){
	$justific="Pedido de Peças Autorizado Pela Fábrica";
}

$autorizar    = $_POST['autorizar'];
$autorizar_os = $_POST['autorizar_os'];
if(strlen($os) ==0){
	$autorizar_os = $_GET['autorizar_os'];
}

if (strlen($_GET['trocar']) > 0 && strlen($os) > 0  ) {
	$sua_os=trim($_GET['trocar']);

	if (strlen($sua_os)>0){
		header("Location: os_cadastro.php?os=$os");
		exit();
	}
	header("Location: os_cadastro.php?os=$os");
	exit;
/*
	$res = @pg_exec($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin) 
			VALUES ($os,64,current_timestamp,'Troca do Produto',$login_admin)";
	$res = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro)>0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		header("Location: $PHP_SELF?msg_erro=$msg_erro");
		exit();
	}
	else {
		//$res = @pg_exec ($con,"COMMIT TRANSACTION");
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		header("Location: os_cadastro.php?os=$os$str_filtro");
		exit();
	}
*/
}

$layout_menu = "callcenter";
$title = "OS's com intervenção da Fábrica";
include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:9px;
	height:12px;
}
.botao2{
	border:1px solid #666;
	font-size:9px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:9px;
	height:16px;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.justificativa{
	font-size: 10px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language="javascript">

function fnc_pesquisa_peca_lista(peca_referencia, peca_descricao, tipo) {
	var url = "";
	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?peca=" + peca_referencia.value + "&tipo=" + tipo + "&exibe=/assist/admin/os_intervencao_fabio.php";
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?descricao=" + peca_descricao.value + "&tipo=" + tipo + "&exibe=/assist/admin/os_intervencao_fabio.php";
	}
	if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco		= document.frm_consulta.preco_null;
		janela.focus();
	}else{
		alert("Digite pelo menos 3 caracteres!");
	}
}

function fnc_pesquisa_peca_2 (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
}

function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			}
		else{
			style2.style.display = "block";
		}
 	}
}

function fnc_pesquisa_posto(campo, campo2, tipo) {
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
		janela.focus();
	}
}
function fnc_pesquisa_produto2 (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1" ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.produto		= document.frm_consulta.produto;
		janela.focus();
	}
}


function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}

function fnc_reparar(os) {
	var url = "<? echo $PHP_SELF ?>?janela=sim&tipo=reparar&os="+os;
	janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=300, height=500, top=18, left=0");
	janela_aut.focus();
}


function fnc_cancelar(os) {
	var url = "<? echo $PHP_SELF ?>?janela=sim&tipo=cancelar&os="+os;
	janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=300, height=500, top=18, left=0");
	janela_aut.focus();
}

function fnc_autorizar(os) {
	var url = "<? echo $PHP_SELF ?>?janela=sim&tipo=autorizar&os="+os;
	janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=300, height=500, top=18, left=0");
	janela_aut.focus();
}

function autorizar_os(os,formulario,sua_os){
	eval("var form = document."+formulario);
	var just = prompt('Informe a justificativa da autorização (opcional)','');
	if ( just==null){ 
		return false;
	}
	form.autorizar.value=sua_os;
	form.justific.value=just;
	if (confirm('Deseja continuar?\n\nOS: '+sua_os)){
		form.submit();
	}
}
</script>
<br>

<? 
	$dias = 5;
	/*HD: 93726*/
	if($login_fabrica ==3){
		$dias = 3;
	}

	#HD 14331
	echo "<div style='background-color:#FCDB8F;width:600px;margin:0 auto;text-align:center;padding:2px 10px 2px 10px;'>";
	echo "<p style='text-align:left;padding:0px;'><b>ATENÇÃO: </b>As OSs em intervenção serão desconsideradas da INTERVENÇÃO automaticamente pelo sistema se não forem analisadas no prazo de $dias dias!</p>";
	echo "<p style='text-align:left'>TELECONTROL</p>";
	echo "</div>";
	echo "<br>";
?>


<?
if(strlen($msg_erro)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg_erro</td>";
	echo "</tr>";
	echo "</table><br>";
}

if(strlen($msg)>0){
	echo "<center><b style='font-size:12px;border:1px solid #999;padding:10px;background-color:#dfdfdf'>$msg</b></center><br>";
}

echo "<FORM METHOD='POST' NAME='frm_consulta' ACTION=\"$PHP_SELF\">";
?>
<input type="hidden" name="preco_null" value="">
<input type='hidden' name='btnacao'>
<TABLE width="500" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="5" class="menu_top"><div align="center"><b>Pesquisa</b></div></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD width="80" rowspan="2" class="table_line">Posto</TD>
	<TD class="table_line">Código do Posto</TD>
	<TD class="table_line">Nome do Posto</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left" nowrap>
		<input type="text" name="posto_codigo" size="10" maxlength="20" value="<? echo $posto_codigo ?>" class="frm">
		<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'codigo')">
	</TD>
	<TD class="table_line" style="text-align: left;" nowrap>
		<input type="text" name="posto_nome" size="25" maxlength="50"  value="<?echo $posto_nome?>" class="frm">
		<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'nome')">
	</TD>
	<TD width="19" class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD width="80" rowspan="2" class="table_line">Região</TD>
	<TD class="table_line"></TD>
	<TD class="table_line"></TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left" nowrap colspan='2'>
		<select name='regiao' class='frm'>
			<option value='' ></option>
			<option value='1' <? echo ($regiao=='1')?"selected":""; ?>>Região 1</option>
			<option value='2' <? echo ($regiao=='2')?"selected":""; ?>>Região 2</option>
			<option value='3' <? echo ($regiao=='3')?"selected":""; ?>>Região 3</option>
			<option value='4' <? echo ($regiao=='4')?"selected":""; ?>>Região 4</option>

		</select>
	</TD>
	<TD width="19" class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD            class="table_line">OS:</TD>
	<TD class="table_line" colspan=2>
		<INPUT TYPE="radio" NAME="consumidor_revenda" value="todas" <? if ($consumidor_revenda=='todas') echo 'checked'; ?> > Todas&nbsp;&nbsp;&nbsp;
		<INPUT TYPE="radio" NAME="consumidor_revenda" value="revenda" <? if ($consumidor_revenda=='revenda') echo 'checked'; ?> > OS de Revenda&nbsp;&nbsp;&nbsp;
		<INPUT TYPE="radio" NAME="consumidor_revenda" value="consumidor" <? if ($consumidor_revenda=='consumidor') echo 'checked'; ?> > OS de Consumidor
	</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD            class="table_line">Ordenar por:</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="radio" NAME="ordem" value="data_abertura" <? if ($ordem=='data_abertura') echo 'checked'; ?> > Data da Abertura&nbsp;&nbsp;&nbsp;<INPUT TYPE="radio" NAME="ordem" value="nome" <? if ($ordem=='nome') echo 'checked'; ?> > Nome do Posto&nbsp;&nbsp;&nbsp;<INPUT TYPE="radio" NAME="ordem" value="data_pedido" <? if ($ordem=='data_pedido') echo 'checked'; ?> > Data Pedido</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>

<TR>
	<TD colspan="5" class="table_line" style="text-align: center;"><br>
	<img src="imagens_admin/btn_filtrar.gif" onclick="javascript: document.frm_consulta.btnacao.value='filtrar' ; document.frm_consulta.submit() " ALT="Filtrar extratos" border='0' style="cursor:pointer;">
	</TD>
</TR>
</TABLE>
</form>

<?
if ($btnacao=='filtrar'){

	//if (pg_numrows($res) > 0) {
	##### LEGENDAS - INÍCIO #####
	echo "<div name='leg' align='left' style='padding-left:10px'>";
	echo "<br><b style='border:1px solid #666666;background-color:#D7FFE1'>&nbsp; &nbsp;&nbsp;</b>&nbsp; <b> OS Reincidente</b>";
	echo "<br><b style='border:1px solid #666666;background-color:#91C8FF'>&nbsp; &nbsp;&nbsp;</b>&nbsp; <b> OS Aberta a mais de 25 dias</b>";
	echo "</div>";

	$sql =  "SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
			SELECT 
			ultima.os, 
			(SELECT status_os FROM tbl_os_status WHERE status_os IN (116,117) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (116,117) ) ultima
			) interv
			WHERE interv.ultimo_status IN (116);
			
			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);
		";

	$res_status = pg_exec($con,$sql);

	$sql =  "SELECT DISTINCT tbl_os.os                                                        ,
				tbl_os.sua_os                                                     ,
				LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
				current_date - tbl_os.data_abertura as dias_aberto,
				tbl_os.data_abertura   AS abertura_os       ,
				tbl_os.serie                                                      ,
				tbl_os.consumidor_nome                                            ,
				tbl_os.admin                                                      ,
				tbl_posto_fabrica.codigo_posto                                    ,
				tbl_posto.nome                              AS posto_nome         ,
				tbl_posto.fone                              AS posto_fone         ,
				tbl_produto.referencia                      AS produto_referencia ,
				tbl_produto.descricao                       AS produto_descricao  ,
				tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (67,68,70) ORDER BY data DESC LIMIT 1) AS reincindente,
				(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (116,117) ORDER BY data DESC LIMIT 1) AS status_descricao,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (116,117) ORDER BY data DESC LIMIT 1) AS status_os,
				(SELECT data FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (116,117) ORDER BY data DESC LIMIT 1) AS status_pedido
			FROM  tmp_interv_$login_admin X
			JOIN  tbl_os             ON tbl_os.os               = X.os
			JOIN tbl_posto           ON tbl_posto.posto         = tbl_os.posto
			JOIN tbl_posto_fabrica   ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto         ON tbl_produto.produto     = tbl_os.produto 
			LEFT JOIN tbl_os_retorno ON tbl_os_retorno.os       = tbl_os.os
			WHERE tbl_os.fabrica  = $login_fabrica
			AND   tbl_os.excluida IS NOT TRUE 
			$sql_estado
			$sql_consumidor_revenda
			$sql_adicional
			$sql_ordem ";

	$res = pg_exec($con,$sql);
	$total=pg_numrows($res);
	$achou=0;

	echo "<br>";
	echo "<center><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#485989' width='98%'>";
	echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
	echo "<td width='70'>OS</td>";
	echo "<td>AB</td>";
	echo "<td>PEDIDO</td>";
	echo "<td>POSTO</td>";
	echo "<td width='75'>FONE POSTO</td>";
	echo "<td width='75'>PRODUTO</td>";
	echo "<td>QTDE<br>PEÇAS</td>";
	echo "<td colspan='5'>AÇÕES</td></tr>";

	for ($i = 0 ; $i < $total ; $i++) {
	
		$os                 = trim(pg_result($res,$i,os));
		$sua_os             = trim(pg_result($res,$i,sua_os));
		$digitacao          = trim(pg_result($res,$i,digitacao));
		$abertura           = trim(pg_result($res,$i,abertura));
		$serie              = trim(pg_result($res,$i,serie));
		$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
		$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result($res,$i,posto_nome));
		$produto_referencia = trim(pg_result($res,$i,produto_referencia));
		$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
		$produto_troca_obrigatoria   = trim(pg_result($res,$i,troca_obrigatoria));
		$posto_fone         = substr(trim(pg_result($res,$i,posto_fone)),0,17);
		$status_os          = trim(pg_result($res,$i,status_os));
		$status_descricao   = trim(pg_result($res,$i,status_descricao));

		$os_reincidente      = trim(pg_result($res,$i,reincindente));
		$dias_abertura       = trim(pg_result($res,$i,dias_aberto));

		$sql_status  = "SELECT TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_os_status WHERE tbl_os_status.os= $os ORDER BY tbl_os_status.data DESC LIMIT 1";
		$res_status = pg_exec($con,$sql_status);
		$data_pedido = trim(pg_result($res_status,0,0));

		$achou=1;

		if ($i % 2 == 0) $cor   = "#F1F4FA";
		else 			 $cor   = "#F7F5F0";

		if ($dias_abertura>24){
			$cor = "#91C8FF";
		}

		if ($os_reincidente==67 || $os_reincidente==68 || $os_reincidente==70){
			$cor = "#D7FFE1";
		}

		if ($status_os == "116") $cor = "#D7FFE1";

		$pecas = "";
		$peca  = "";
		$sql_peca = "SELECT  tbl_os_item.os_item,
						tbl_peca.troca_obrigatoria    AS troca_obrigatoria,
						tbl_peca.intervencao_carteira AS intervencao_carteira,
						tbl_peca.referencia           AS referencia,
						tbl_peca.descricao            AS descricao,
						tbl_peca.peca AS peca
					FROM tbl_os_produto
					JOIN tbl_os_item USING(os_produto)
					JOIN tbl_peca USING(peca)
					WHERE tbl_os_produto.os=$os
					";

		$res_peca = pg_exec($con,$sql_peca);
		$resultado = pg_numrows($res_peca);
		$quantas_pecas = $resultado;
		if ($resultado>0){
			$peca_troca_obrigatoria		= trim(pg_result($res_peca,0,troca_obrigatoria));
			$peca						= trim(pg_result($res_peca,0,peca));
			for($j=0;$j<$resultado;$j++){
				$peca_referencia       = trim(pg_result($res_peca,$j,referencia));
				$peca_descricao       = trim(pg_result($res_peca,$j,descricao));
				$pecas .= $peca_referencia." - ".$peca_descricao.'\n';
			}
		}

		if (strlen($sua_os) == 0) $sua_os = $os;

		echo "<tr class='Conteudo' height='20' bgcolor='$cor' align='left'  >";
		echo "<input type='hidden' name='justific' value=''>";
		echo "<input type='hidden' name='autorizar' value=''>";
		echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
		echo "<td nowrap >$abertura</td>";
		echo "<td nowrap >$data_pedido</td>";
		echo "<td nowrap title='$codigo_posto $posto_nome'>$codigo_posto ".substr($posto_nome,0,15)."</td>";
		echo "<td nowrap>$posto_fone</td>";
		echo "<td nowrap title='Referência: $produto_referencia \nDescrição: $produto_descricao'>".substr($produto_referencia." ".$produto_descricao,0,20)."</td>";
		echo "<td nowrap title='Quantidade de peças: $quantas_pecas' align='center'><a href=\"javascript: alert('$pecas')\">$quantas_pecas</a></td>";

		if ($status_os=="116"){
			$colspan="";
			if ($produto_troca_obrigatoria=='t' || $peca_troca_obrigatoria=='t') {
				$colspan="colspan='4'";
			}

			if ( $produto_troca_obrigatoria=='t' || $peca_troca_obrigatoria=='t' || 1==1){
				echo "<td align='center' $colspan style='font-size:9px' nowrap>";
				echo "<img src='imagens/btn_trocar.gif' ALT='Efetuar a troca do Produto' border='0' style='cursor:pointer;' onClick=\"javascript: if (confirm('Deseja realizar a troca deste produto pela Fábrica? Esta OS será liberada')) window.open('os_cadastro.php?os=$os'); return false; ;\">";
				echo "</td>\n";
			}
			if ($produto_troca_obrigatoria!='t' && $peca_troca_obrigatoria!='t' && strlen($pecas)>0){
				echo "<td align='center' style='font-size:9px' nowrap>";
				echo "<a href='os_item.php?os=$os' target='_blank'><img src='imagens/btn_alterar_cinza.gif' ALT='Alterar OS' border='0' style='cursor:pointer;'></a>";
				echo "</td>\n";
			}

			echo "<td align='center' style='font-size:9px' nowrap>";
			if ($login_fabrica==11 or 1==1){
				echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça' i border='0' style='cursor:pointer;' 
				onClick=\"javascript: fnc_autorizar($os);\">";
			}elseif($login_fabrica==3){
				echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' i border='0' style='cursor:pointer;' onClick=\"javascript:autorizar_os($os,'frm_$os',$sua_os);\" >";
			}else{
				echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' i border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Autorizar pedido de peça? Esta OS será liberada e a solicitação de peça para esta OS será autorizada'))  document.location='$PHP_SELF?os=$os&autorizar=$sua_os$str_filtro';\">";
			}
			echo "</td>\n";

			if ($produto_troca_obrigatoria!='t' && $peca_troca_obrigatoria!='t' && strlen($pecas)>0){
				echo "<td align='center' style='font-size:9px' nowrap>";
				echo "<img src='imagens/btn_cancelar.gif' ALT='Cancelar Troca de Peça' border='0' style='cursor:pointer;' onClick=\"javascript: fnc_cancelar($os);\">";
				echo "</td>\n";
			}
		}
		$mostrar="none";

		echo "</tr>";

		$justificativa = trim(str_replace("Reparo do produto deve ser feito pela fábrica","",$status_descricao));
		$justificativa = trim(str_replace("Peça da O.S. com intervenção da fábrica.","",$justificativa));
		if (strlen($justificativa)>0){
			echo "<tr class='justificativa' bgcolor='$cor'>";
			echo "<td colspan='14' align='left'>";
			echo "<img src='imagens/setinha_linha4.gif'>&nbsp;&nbsp; <i  style='color:#5B5B5B'>$justificativa </i>";
			echo "</td>";
			echo "</tr>";
		}
	}

	if ($achou==0){
		echo "<tr class='Conteudo' height='20' bgcolor='#FFFFCC' align='left'>
			<td colspan='13' style='padding:10px'>NENHUMA OS COM INTERVENÇÃO DE CARTEIRA</td>
			</tr>";
	}
	
	echo "</table></center>";

	if ($achou>0 AND $i>0){
		echo "<p style='text-align:center'>$i OS(s) em Intervenção</p>";
	}

}
?>
<br><br><br>



<? 
// envia email teste para avisar
/*$email_origem  = "fabio@telecontrol.com.br";
$email_destino = "fabio@telecontrol.com.br";
$assunto       = "OS INTERVENCAO";
$corpo.="<br>OS: $os \n";
$body_top = "--Message-Boundary\n";
$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
$body_top .= "Content-transfer-encoding: 7BIT\n";
$body_top .= "Content-description: Mail message body\n\n";
@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem); */
// fim

include "rodape.php" 

?>
