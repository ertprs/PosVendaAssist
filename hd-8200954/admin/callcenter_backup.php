<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
require '/var/www/assist/www/helpdesk.inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
// recebe as variaveis
if($_POST['chk_opt1'])  $chk1  = $_POST['chk_opt1'];
if($_POST['chk_opt2'])  $chk2  = $_POST['chk_opt2'];
if($_POST['chk_opt3'])  $chk3  = $_POST['chk_opt3'];
if($_POST['chk_opt4'])  $chk4  = $_POST['chk_opt4'];
if($_POST['chk_opt5'])  $chk5  = $_POST['chk_opt5'];
if($_POST['chk_opt6'])  $chk6  = $_POST['chk_opt6'];
if($_POST['chk_opt7'])  $chk7  = $_POST['chk_opt7'];
if($_POST['chk_opt8'])  $chk8  = $_POST['chk_opt8'];
if($_POST['chk_opt9'])  $chk9  = $_POST['chk_opt9'];
if($_POST['chk_opt10']) $chk10 = $_POST['chk_opt10'];
if($_POST['chk_opt11']) $chk11 = $_POST['chk_opt11'];
if($_POST['chk_opt12']) $chk12 = $_POST['chk_opt12'];
if($_POST['chk_opt13']) $chk13 = $_POST['chk_opt13'];
if($_POST['chk_opt14']) $chk14 = $_POST['chk_opt14'];
if($_POST['chk_opt15']) $chk15 = $_POST['chk_opt15'];
if($_POST['chk_opt16']) $chk16 = $_POST['chk_opt16'];
if($_POST['chk_opt17']) $chk17 = $_POST['chk_opt17'];
if($_POST['chk_opt18']) $chk18 = $_POST['chk_opt18'];
if($_POST['chk_opt19']) $chk19 = $_POST['chk_opt19'];

if($_GET['chk_opt1'])  $chk1  = $_GET['chk_opt1'];
if($_GET['chk_opt2'])  $chk2  = $_GET['chk_opt2'];
if($_GET['chk_opt3'])  $chk3  = $_GET['chk_opt3'];
if($_GET['chk_opt4'])  $chk4  = $_GET['chk_opt4'];
if($_GET['chk_opt5'])  $chk5  = $_GET['chk_opt5'];
if($_GET['chk_opt6'])  $chk6  = $_GET['chk_opt6'];
if($_GET['chk_opt7'])  $chk7  = $_GET['chk_opt7'];
if($_GET['chk_opt8'])  $chk8  = $_GET['chk_opt8'];
if($_GET['chk_opt9'])  $chk9  = $_GET['chk_opt9'];
if($_GET['chk_opt10']) $chk10 = $_GET['chk_opt10'];
if($_GET['chk_opt11']) $chk11 = $_GET['chk_opt11'];
if($_GET['chk_opt12']) $chk12 = $_GET['chk_opt12'];
if($_GET['chk_opt13']) $chk13 = $_GET['chk_opt13'];
if($_GET['chk_opt14']) $chk14 = $_GET['chk_opt14'];
if($_GET['chk_opt15']) $chk15 = $_GET['chk_opt15'];
if($_GET['chk_opt16']) $chk16 = $_GET['chk_opt16'];
if($_GET['chk_opt17']) $chk17 = $_GET['chk_opt17'];
if($_GET['chk_opt18']) $chk18 = $_GET['chk_opt18'];
if($_GET['chk_opt19']) $chk19 = $_GET['chk_opt19'];


if($_POST["data_inicial_01"])		$data_inicial_01      = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01        = trim($_POST["data_final_01"]);
if($_POST['codigo_posto'])			$codigo_posto         = trim($_POST['codigo_posto']);
if($_POST["produto_referencia"])	$produto_referencia   = trim($_POST["produto_referencia"]);
if($_POST["produto_nome"])			$produto_nome         = trim($_POST["produto_nome"]);
if($_POST["numero_serie"])			$numero_serie         = trim($_POST["numero_serie"]);
if($_POST["nome_consumidor"])		$nome_consumidor      = trim($_POST["nome_consumidor"]);
if($_POST["cpf_consumidor"])		$cpf_consumidor       = trim($_POST["cpf_consumidor"]);
if($_POST["cidade"])				$cidade               = trim($_POST["cidade"]);
if($_POST["uf"])					$uf                   = trim($_POST["uf"]);
if($_POST["numero_os"])				$numero_os            = trim($_POST["numero_os"]);
if($_POST["nota_fiscal"])			$nota_fiscal          = trim($_POST["nota_fiscal"]);
if($_POST["nota_fiscal"])			$nota_fiscal          = trim($_POST["nota_fiscal"]);
if($_POST["callcenter"])			$callcenter           = trim($_POST["callcenter"]);
if($_POST["situacao"])				$situacao             = trim($_POST["situacao"]);
if($_POST["cep"])					$cep                  = trim($_POST["cep"]);
if($_POST["fone"])					$fone                 = trim($_POST["fone"]);
if($_POST["codigo_cliente_admin"])	$codigo_cliente_admin = trim($_POST["codigo_cliente_admin"]);


if($_GET["data_inicial_01"])		$data_inicial_01      = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01        = trim($_GET["data_final_01"]);
if($_GET['codigo_posto'])			$codigo_posto         = trim($_GET['codigo_posto']);
if($_GET["produto_referencia"])		$produto_referencia   = trim($_GET["produto_referencia"]);
if($_GET["numero_serie"])			$numero_serie         = trim($_GET["numero_serie"]);
if($_GET["nome_consumidor"])		$nome_consumidor      = trim($_GET["nome_consumidor"]);
if($_GET["cpf_consumidor"])			$cpf_consumidor       = trim($_GET["cpf_consumidor"]);
if($_GET["cidade"])					$cidade               = trim($_GET["cidade"]);
if($_GET["uf"])						$uf                   = trim($_GET["uf"]);
if($_GET["numero_os"])				$numero_os            = trim($_GET["numero_os"]);
if($_GET["nota_fiscal"])			$nota_fiscal          = trim($_GET["nota_fiscal"]);
if($_GET["callcenter"])				$callcenter           = trim($_GET["callcenter"]);
if($_GET["situacao"])				$situacao             = trim($_GET["situacao"]);
if($_GET["cep"])					$cep                  = trim($_GET["cep"]);
if($_GET["fone"])					$fone                 = trim($_GET["fone"]);
if($_GET["codigo_cliente_admin"])	$codigo_cliente_admin = trim($_GET["codigo_cliente_admin"]);


$produto_referencia = str_replace ("." , "" , $produto_referencia);
$produto_referencia = str_replace ("-" , "" , $produto_referencia);
$produto_referencia = str_replace ("/" , "" , $produto_referencia);
$produto_referencia = str_replace (" " , "" , $produto_referencia);

# HD 58801
$por_atendente = $_GET["por_atendente"];
if ($por_atendente == 1){
	$atendente = $_GET["atendente"];
	if (strlen($atendente)>0){
		$sqlCondAt = "AND tbl_hd_chamado.atendente = $atendente";
	}
}


if(strlen($chk1) == 0 AND strlen($chk2) == 0 AND strlen($chk3) == 0 AND strlen($chk4) == 0 AND strlen($callcenter) == 0){
	$data_inicial = $_GET["data_inicial"];
	$data_final   = $_GET["data_final"];
	if (strlen($data_final) > 0 AND strlen($data_inicial) > 0 and $data_final <> "dd/mm/aaaa" and  $data_inicial <> "dd/mm/aaaa") {
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
		$xdata_inicial = "$xdata_inicial 00:00:00";

		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
		$xdata_final   =  "$xdata_final 23:59:59";


	}else $msg_erro = "Selecione a data para fazer a pesquisa";
}
$layout_menu = "callcenter";
$title = "Relação de Atendimentos Lançados";

include "cabecalho.php";

?>






<?
if(strlen($msg_erro) == 0){
	$cond1 = " 1 = 1 ";
	$cond2 = " 1 = 1 ";
	$cond3 = " 1 = 1 ";
	$cond4 = " 1 = 1 ";
	$cond5 = " 1 = 1 ";
	$cond6 = " 1 = 1 ";
	$cond7 = " 1 = 1 ";
	$cond8 = " 1 = 1 ";
	$cond9 = " 1 = 1 ";
	$cond10 = " 1 = 1 ";
	$cond11 = " 1 = 1 ";
	$cond12 = " 1 = 1 ";
	$cond13 = " 1 = 1 ";
	$cond14 = " 1 = 1 ";
	$cond15 = " 1 = 1 ";
	$cond16 = " 1 = 1 ";
	$cond17 = " 1 = 1 ";
	$cond18 = " 2 = 2 "; // providencia
	$cond19 = " 3 = 3 "; // data providencia
	$cond20 = " 4 = 4 "; // estado (Região)
	$cond21 = " 5 = 5 "; // pré -os 
	$cond22 = " 6 = 6 "; // pré -os 

	if ($situacao=="PENDENTES"){
		$cond1 = " tbl_hd_chamado.status <> 'Resolvido'";
	}
	if ($situacao=="SOLUCIONADOS"){
		$cond1 = " tbl_hd_chamado.status = 'Resolvido'";
	}


	if(strlen($chk1) > 0){
		//dia atual
		$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
		$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

		$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
		$resX = pg_exec ($con,$sqlX);
		#  $dia_hoje_final = pg_result ($resX,0,0);

		$cond1 = " tbl_hd_chamado.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final' ";
	}

	if(strlen($chk2) > 0) {
		// dia anterior
		$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
		$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

		$cond2 =" tbl_hd_chamado.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final' ";
	}

	if(strlen($chk3) > 0){
		// última semana
		$sqlX = "SELECT to_char (current_date , 'D')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

		$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

		$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

		$cond3 =" tbl_hd_chamado.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final' ";

	}

	if(strlen($chk4) > 0){
		// do mês
		$mes_inicial = trim(date("Y")."-".date("m")."-01");
		$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

		$cond4 = " tbl_hd_chamado.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59' ";

	}

	if(strlen($chk1) == 0 AND strlen($chk2) == 0 AND strlen($chk3) == 0 AND strlen($chk4) == 0 AND strlen($callcenter) == 0){
		$cond5 = "  tbl_hd_chamado.data BETWEEN '$xdata_inicial' AND '$xdata_final'  ";
	}


	if(strlen($chk6) > 0){
		// codigo do posto
		if (strlen($codigo_posto) > 0){
			$cond6 = " tbl_posto_fabrica.codigo_posto = '". $codigo_posto."' ";
		}
	}

	if(strlen($chk7) > 0){
		// referencia do produto
		if ($produto_referencia) {
			$sql = "Select produto from tbl_produto where referencia_pesquisa = '$produto_referencia' ";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$produto = pg_result($res,0,0);
				$cond7 = " tbl_hd_chamado_extra.produto = $produto ";
			}

		}
	}

	if(strlen($chk8) > 0){
		// numero de serie do produto
		if ($numero_serie) {
			$cond8 = " tbl_hd_chamado_extra.serie = '$numero_serie' ";
		}
	}

	if(strlen($chk9) > 0){
		// nome_consumidor
		if ($nome_consumidor){
			//$monta_sql .= "$xsql tbl_cliente.nome ilike '%".$cliente."%' ";
			$cond9 = "  tbl_hd_chamado_extra.nome ILIKE '%".$nome_consumidor."%' ";
		}
	}

	if(strlen($chk10) > 0){
		// cpf_consumidor
		if ($cpf_consumidor){
			$cond10 = " tbl_hd_chamado_extra.cpf LIKE '". $cpf_consumidor."%' ";
		}
	}


	if(strlen($chk13) > 0){
		// numero_os
		if ($numero_os){
			$cond13 = " tbl_os.sua_os ILIKE '".$numero_os."%' ";
		}
	}

	if(strlen($chk14) > 0){
		// nota fiscal
		if ($nota_fiscal){
			$cond14 = " tbl_hd_chamado_extra.nota_fiscal ILIKE '".$nota_fiscal."%' ";
		}
	}

	if(strlen($chk15) > 0){
		// nota fiscal
		if ($callcenter){
			$cond15 = " tbl_hd_chamado.hd_chamado = $callcenter";
		}
	}

	if(strlen($chk16) > 0){
		// nota fiscal
		if ($fone){
			$cond16 = " tbl_hd_chamado_extra.fone ILIKE '".$fone."%' ";
		}
	}

	if(strlen($chk17) > 0){
		// nota fiscal
		if ($cep){
			$cond17 = " tbl_hd_chamado_extra.cep ILIKE '".$cep."%' ";;
		}
	}

	if(strlen($chk18) > 0){
	// pré-os
		$cond21 = " tbl_hd_chamado_extra.abre_os is true ";
	}

	if(strlen($chk19) > 0){
		// CLIENTE_ADMIN
		if (strlen($codigo_cliente_admin) > 0){
			$cond22 = " tbl_cliente_admin.codigo = '". $codigo_cliente_admin."' ";
		}
	}

	 if ( $login_fabrica == 5 ) {
		 // providencia --------------
		 $providencia_chk = ( isset($_POST['providencia_chk']) ) ? $_POST['providencia_chk'] : $_GET['providencia_chk'];
		 if ( isset($providencia_chk) && ! empty($providencia_chk) ) {
		 	$providencia = ( isset($_POST['providencia']) ) ? $_POST['providencia'] : $_GET['providencia'];
		 	$providencia = ( ! empty($providencia) ) ? pg_escape_string($providencia) : null ;
		 	$cond18      = ( ! empty($providencia) ) ? ' tbl_hd_chamado_extra.hd_situacao = '.$providencia : $cond18 ;
		 }
		 unset($providencia_chk,$providencia);
		 // data providencia ---------
		 $providencia_data_chk = ( isset($_POST['providencia_data_chk']) ) ? $_POST['providencia_data_chk'] : $_GET['providencia_data_chk'];
		 if ( isset($providencia_data_chk) && ! empty($providencia_data_chk) ) {
		 	$providencia_data = ( isset($_POST['providencia_data']) ) ? $_POST['providencia_data'] : $_GET['providencia_data'];
		 	$providencia_data = ( ! empty($providencia_data) ) ? pg_escape_string(fnc_formata_data_pg($providencia_data)) : null ;
		 	$cond19		      = ( ! empty($providencia_data) ) ? ' tbl_hd_chamado.previsao_termino = '.$providencia_data : $cond19 ;
		 }
		 // estado -------------------
		 $estado_chk = ( isset($_POST['regiao_chk']) ) ? $_POST['regiao_chk'] : $_GET['regiao_chk'];
		 if ( isset($estado_chk) && ! empty($estado_chk) ) {
		 	$estado  = ( isset($_POST['regiao']) ) ? $_POST['regiao'] : $_GET['regiao'];
		 	$estados = array();
		 	switch ( strtoupper($estado) ) {
		 		case 'SUL':
		 			$aTmp    = array('PR','SC','RS');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 			break;
		 		case 'SP': case 'SP-CAPITAL': case 'SP-INTERIOR':
		 			$estados[] = 'SP';
		 			break;
		 		case 'RJ': case 'PE': case 'BA': case 'MG':
		 			$estados[] = pg_escape_string($estado);
		 		case 'BR-NEES':
		 			$aTmp    = array('AL','BA','CE','MA','PB','PE','PI','RN','SE','ES');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 			break;
		 		case 'BR-NCO':
		 			$aTmp    = array('AC','AP','AM','PA','RR','RO','TO','GO','MT','MS','DF');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 			break;
		 		default:
		 			$cond20 = ' 1=1 ';
		 			break;
		 	}
		 	if ( count($estados) > 0 ) {
		 		$estados_string = implode("','",$estados);
		 		$cond20         = " tbl_cidade.estado IN ('{$estados_string}') ";
		 	}
		 }
	 }
	
	// BTN_NOVA BUSCA
	echo "<TABLE width='600' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='callcenter_backup_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

	if ($login_fabrica == 52) {
		$JOIN_ITEM = "LEFT JOIN tbl_hd_chamado_item on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado and tbl_hd_chamado_item.produto is not null
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_item.produto";
		$campo_os = " tbl_hd_chamado_item.os as os_item, ";
	}
	else {
		$JOIN_ITEM = " LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto ";
	}

	$sql = "SELECT tbl_hd_chamado_extra.hd_chamado,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.celular ,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_produto.voltagem,
					tbl_hd_chamado_extra.nota_fiscal,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.reclamado,
					tbl_hd_chamado_extra.sua_os,
					tbl_posto_fabrica.codigo_posto as codigo_posto,
					tbl_posto.nome as posto_nome,
					tbl_hd_chamado.categoria as natureza_operacao
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			$JOIN_ITEM
			LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao
			LEFT JOIN tbl_cidade on tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
			LEFT JOIN tbl_posto_fabrica on tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
			and tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
			LEFT JOIN tbl_cliente_admin on tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND $cond1
			AND $cond2
			AND $cond3
			AND $cond4
			AND $cond5
			AND $cond6
			AND $cond7
			AND $cond8
			AND $cond9
			AND $cond10
			AND $cond11
			AND $cond12
			AND $cond13
			AND $cond14
			AND $cond15
			AND $cond16
			AND $cond17 
			AND $cond18
			AND $cond19
			AND $cond20
			AND $cond21
			AND $cond22
			$sqlCondAt";

	$sql .= " ORDER BY tbl_hd_chamado.hd_chamado DESC ";
	$sql       = str_replace('__STATUS__',$cond_status,$sql);
	//echo $sql;

	$res  = @pg_exec($con,$sql);

	// ##### PAGINACAO ##### //
	$data_csv = date('dmy');
	echo `rm /tmp/assist/backup-callcenter-$login_fabrica.csv`;		

	$fp = fopen ("/tmp/assist/backup-callcenter-$login_fabrica.html","w");
	fputs($fp,'CHAMADO;');
	fputs($fp,'DATA;');
	fputs($fp,'NOME;');
	fputs($fp,'ENDERECO;');
	fputs($fp,'BAIRRO;');
	fputs($fp,'COMPLEMENTO;');
	fputs($fp,'CIDADE;');
	fputs($fp,'ESTADO;');
	fputs($fp,'CEP;');
	fputs($fp,'FONE1;');
	fputs($fp,'FONE2;');
	fputs($fp,'CELULAR;');
	fputs($fp,'COD. PRODUTO;');
	fputs($fp,'DESC. PRODUTO;');
	fputs($fp,'VOLTAGEM;');
	fputs($fp,'NOTA FISCAL;');
	fputs($fp,'DATA NF;');
	fputs($fp,'COD POSTO;');
	fputs($fp,'NATUREZA;');
	fputs($fp,'NOME POSTO;');
	fputs($fp,'CAMPO MEMO;');
	fputs($fp,$sua_os.'OS;');
	fputs($fp,$sua_os.'INTERACAO;');
	fputs($fp,$sua_os."\n");
	if (@pg_numrows($res) == 0) {
		echo "<TABLE width='600' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
	} else {
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			$callcenter         = trim(pg_result ($res,$i,hd_chamado));
			$data               = trim(pg_result ($res,$i,data));
			$sua_os             = trim(pg_result ($res,$i,sua_os));
			if (strlen($campo_os)>0) {
				$os_item                 = trim(pg_result ($res,$i,os_item));
			}
			$posto_nome         = trim(pg_result ($res,$i,posto_nome));
			$produto_nome       = trim(pg_result ($res,$i,produto_nome));
			$produto_referencia = trim(pg_result ($res,$i,produto_referencia));
			$nome_cliente       = trim(pg_result ($res,$i,nome));
			$cpf                = trim(pg_result ($res,$i,cpf));
			$endereco           = trim(pg_result ($res,$i,endereco));
			$numero             = trim(pg_result ($res,$i,numero));
			$complemento        = trim(pg_result ($res,$i,complemento));
			$bairro             = trim(pg_result ($res,$i,bairro));
			$cep                = trim(pg_result ($res,$i,cep));
			$cidade_nome        = trim(pg_result ($res,$i,cidade_nome));
			$estado             = trim(pg_result ($res,$i,estado));
			$fone               = trim(pg_result ($res,$i,fone));
			$fone2              = trim(pg_result ($res,$i,fone2));
			$celular            = trim(pg_result ($res,$i,celular));
			$produto_referencia = trim(pg_result ($res,$i,produto_referencia));
			$produto_nome       = trim(pg_result ($res,$i,produto_nome));
			$voltagem           = trim(pg_result ($res,$i,voltagem));
			$nota_fiscal        = trim(pg_result ($res,$i,nota_fiscal));
			$data_nf            = trim(pg_result ($res,$i,data_nf));
			$reclamado          = trim(pg_result ($res,$i,reclamado));
			$sua_os             = trim(pg_result ($res,$i,sua_os));
			$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result ($res,$i,posto_nome));
			$natureza_operacao  = trim(pg_result ($res,$i,natureza_operacao));
			switch ($natureza_operacao) {
				case "reclamacao_produto":
					$natureza_operacao = "Produto/Defeito";
					break;
				case 'reclamacao_empresa':
					$natureza_operacao = "Recl. Empresa";
					break;
				case 'reclamacao_at':
					$natureza_operacao = "Recl. A.T.";
					break;
				case 'duvida_produto':
					$natureza_operacao = "Dúvida Prod.";
					break;
				case 'sugestao':
					$natureza_operacao = "Sugestão";
					break;
				case 'onde_comprar':
					$natureza_operacao = "Onde Comprar";
					break;
				case 'ressarcimento':
					$natureza_operacao = "Ressarcimento";
					break;
				case 'sedex_reverso':
					$natureza_operacao = "Sedex Reverso";
					break;
			}


			if(strlen($atendente) >0){
				$sqlx="SELECT login from tbl_admin where admin=$atendente";
				$resx=pg_exec($con,$sqlx);
				$atendente          = strtoupper(trim(pg_result ($resx,0,login)));
			}
			
			if($login_fabrica == 5){
				# HD 58801
				$sqlx = "SELECT login from tbl_admin where admin=$admin";
				$resx = pg_exec($con,$sqlx);
				$atendente = strtoupper(trim(pg_result ($resx,0,login)));
			}

			if(strlen($admin) >0){
				$sqlx="SELECT login from tbl_admin where admin=$admin";
				$resx=pg_exec($con,$sqlx);
				$admin          = strtoupper(trim(pg_result ($resx,0,login)));
			}
			
			$cor = "#F7F5F0";
			$btn = 'amarelo';
			if ($i % 2 == 0)
			{
				$cor = '#F1F4FA';
				$btn = 'azul';
			}

			$programaphp = "callcenter_interativo_new.php";

			if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

			if($login_fabrica ==3 and strlen($os)> 0){
				$sqlx="SELECT sua_os
						FROM tbl_os
						WHERE os = $os";
				$resx=pg_exec($con,$sqlx);
				$sua_os = trim(pg_result ($resx,0,sua_os));
			}






// ! respostas do chamado
$_hd_chamado = $callcenter ;
$aRespostas = hdBuscarRespostas($_hd_chamado); // funcao declarada em 'assist/www/heldesk.inc.php'

if (count($aRespostas) > 0) {

	foreach ($aRespostas as $iResposta=>$aResposta):
		$interacao = "Resposta ".($iResposta + 1);
		$interacao.=" - Por ";
		$interacao.=( ! empty($aResposta['atendente']) ) ? $aResposta['atendente'] : $aResposta['posto_nome'] . $aResposta['data'];
		if ( $aResposta['interno'] == 't' ): 
			$interacao .= " - Chamado Interno ";
		endif;
		if ( in_array($aResposta['status_item'],array('Cancelado','Resolvido')) ): 
			$interacao .= " - ".$aResposta['status_item']; 
		endif;
		$interacao.=" - ".nl2br($aResposta['comentario']);
		fputs($fp,$callcenter.';');
		fputs($fp,$data.';');
		fputs($fp,$nome_cliente.';');
		fputs($fp,$endereco.', '.$numero.';');
		fputs($fp,$bairro.';');
		fputs($fp,$complemento.';');
		fputs($fp,$cidade_nome.';');
		fputs($fp,$estado.';');
		fputs($fp,$cep.';');
		fputs($fp,$fone.';');
		fputs($fp,$fone2.';');
		fputs($fp,$celular.';');
		fputs($fp,$produto_referencia.';');
		fputs($fp,$produto_nome.';');
		fputs($fp,$voltagem.';');
		fputs($fp,$nota_fiscal.';');
		fputs($fp,$data_nf.';');
		fputs($fp,$codigo_posto.';');
		fputs($fp,$posto_nome.';');
		fputs($fp,$natureza_operacao.';');
		fputs($fp,str_replace(chr(13)," ",str_replace(chr(10),' ',$reclamado)).';');
		fputs($fp,$sua_os.';');
		fputs($fp,str_replace(chr(13)," ",str_replace(chr(10)," ",str_replace(";",",",str_replace("&nbsp;"," ",str_replace("\n",' ',strip_tags($interacao)))))).';');
		fputs($fp,"\n");
	endforeach;
	unset($aRespostas,$iResposta,$aResposta,$_hd_chamado);
} else {
	fputs($fp,$callcenter.';');
	fputs($fp,$data.';');
	fputs($fp,$nome_cliente.';');
	fputs($fp,$endereco.', '.$numero.';');
	fputs($fp,$bairro.';');
	fputs($fp,$complemento.';');
	fputs($fp,$cidade_nome.';');
	fputs($fp,$estado.';');
	fputs($fp,$cep.';');
	fputs($fp,$fone.';');
	fputs($fp,$fone2.';');
	fputs($fp,$celular.';');
	fputs($fp,$produto_referencia.';');
	fputs($fp,$produto_nome.';');
	fputs($fp,$voltagem.';');
	fputs($fp,$nota_fiscal.';');
	fputs($fp,$data_nf.';');
	fputs($fp,$codigo_posto.';');
	fputs($fp,$posto_nome.';');
	fputs($fp,$natureza_operacao.';');
	fputs($fp,str_replace(chr(13)," ",str_replace(chr(10),' ',$reclamado)).';');
	fputs($fp,$sua_os.';');
	fputs($fp,';');
	fputs($fp,"\n");
}



/*
Produto / Defeito
Descrição do Campo memo
Interações
Reclamação da Empresa
Descrição do Campo memo
Interações
Reclamação da A.T. ( Posto )
Código do Posto
Nome
Número da OS
Descrição do Campo memo
*/
		}
	}
}else{
	echo "<font color='#FF0000' size='3'>$msg_erro</font>";
}

echo `mv -f /tmp/assist/backup-callcenter-$login_fabrica.html /www/assist/www/admin/xls/backup-callcenter-$login_fabrica-$data_csv.csv`;
echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
echo"<tr>";
echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>RELATÓRIO DE CALLCENTER<BR>Clique aqui para fazer o </font><a href='xls/backup-callcenter-$login_fabrica-$data_csv.csv'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em CSV</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
echo "</tr>";
echo "</table>";		


include "rodape.php";
?>