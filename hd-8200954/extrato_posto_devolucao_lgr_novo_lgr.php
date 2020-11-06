<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin) {
    include __DIR__.'/admin/autentica_admin.php';
    if (isset($_REQUEST['posto'])) {
        $login_posto = $_REQUEST['posto'];
    }

    $admin_privilegios = "call_center"; 
    $display_none = "style='display: none;'";
    $alinha_form = "alinha_form";
} else {
    include "autentica_usuario.php";
}

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0){
    $extrato = trim($_POST['extrato']);
}

if ($login_fabrica == 158) {

        $sqlPostoUnidade = "
            SELECT distribuidor_sla
            FROM tbl_distribuidor_sla_posto
            WHERE fabrica = {$login_fabrica}
            AND posto = {$login_posto}
        ";
        $resPostoUnidade = pg_query($con, $sqlPostoUnidade);

        if (pg_num_rows($resPostoUnidade) > 0 ) {
            header("Location: lgr_os.php?extrato=".$_GET["extrato"]);
            exit;
        }
}

if ($login_fabrica == 177){
    header("Location: lgr_os.php?extrato=".$_GET["extrato"]);
}

$fabricas_usam_NCM = in_array($login_fabrica, array(91));

if ($login_posto==1537){ // provisorio
//  header("Location: extrato_posto.php");
//  exit();
}



if (!empty($extrato)) {
    $sql = "SELECT fabrica FROM tbl_extrato where fabrica = $login_fabrica AND extrato = $extrato";
    $res = @pg_query($con, $sql);
    if (@pg_num_rows($res) == 0) {
        header('Location: menu_inicial.php');
        exit;
    }
}

$msg_erro="";
$msg="";

$numero_nota=0;
$item_nota=0;
$numero_linhas=5000;
$numero_linhas_peca = 5000;
$numero_linhas_produto = 5000;
$ok_aceito="nao";
$tem_mais_itens='nao';
$contador=0;

if($_GET['ajax']=='sim') {

    $extrato = $_GET['extrato'];
    $nota_fiscal    = $_GET['nota'];

    $sql = "SELECT nota_fiscal from tbl_faturamento WHERE fabrica = $login_fabrica and distribuidor = $login_posto and nota_fiscal='$nota_fiscal'";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        echo "Erro";
    }

    else{
        $sql = "SELECT posto_fabrica from tbl_fabrica where fabrica = $login_fabrica";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res)>0) {
            $posto_fabrica = pg_fetch_result($res,0,0);
        }

        $total_nota = '0';
        $base_icms = '0';
        $valor_icms = '0';
        $base_ipi = '0';
        $valor_ipi = '0';
        $movimento = "RETORNAVEL";
        $cfop = '6949';

        if ($login_fabrica != 146) {
            $where_troca_garantia = " AND  tbl_os.troca_garantia  IS TRUE ";
        }

        $sqlProduto = "SELECT  DISTINCT
                            tbl_os.os                                                         ,
                            tbl_os.sua_os                                                     ,
                            TO_CHAR(tbl_os_troca.data,'DD/MM/YYYY') AS data_ressarcimento,
                            tbl_produto.produto                          AS produto           ,
                            tbl_produto.referencia                       AS produto_referencia,
                            tbl_produto.descricao                        AS produto_descricao ,
                            tbl_admin.login,
                            tbl_os.nota_fiscal
                FROM tbl_os
                JOIN tbl_os_troca USING(os)
                JOIN tbl_os_extra   USING(os)
                JOIN tbl_extrato    ON tbl_extrato.fabrica = tbl_os.fabrica and tbl_extrato.posto = tbl_os.posto and tbl_extrato.extrato = tbl_os_extra.extrato
                LEFT JOIN tbl_admin            ON tbl_os.troca_garantia_admin = tbl_admin.admin
                LEFT JOIN tbl_produto          ON tbl_os.produto              = tbl_produto.produto
                WHERE tbl_extrato.extrato   = $extrato
                AND  tbl_os.fabrica        = $login_fabrica
                AND  tbl_os.posto          = $login_posto
                AND  tbl_os_troca.ressarcimento   IS TRUE
                $where_troca_garantia";

            $resProduto = pg_query ($con,$sqlProduto);
            $msg_erro .= pg_errormessage($con);

            $qtde_produtos_ressarcimento = pg_num_rows ($resProduto);

            if (strlen($msg_erro)==0) {
                $resX = pg_query ($con,"BEGIN TRANSACTION");

                    if($login_fabrica == 158 and $garantia == 't'){
                        $campoGarantia = " , garantia ";
                        $valueGarantia = " , '$garantia' ";
                    }


                    $sql = "INSERT INTO tbl_faturamento
                        (fabrica, emissao,saida, posto, distribuidor, total_nota, nota_fiscal, serie, natureza, base_icms, valor_icms, base_ipi, valor_ipi,obs,cfop, movimento, $campoGarantia)
                        VALUES ($login_fabrica,current_date,current_date,$posto_fabrica,$login_posto,$total_nota,'$nota_fiscal','2','Simples Remessa', $base_icms, $valor_icms, $base_ipi, $valor_ipi, 'Devolução de Ressarcimento',$cfop,'$movimento' $valueGarantia)";

                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    $sqlZ = "SELECT CURRVAL ('seq_faturamento')";
                    $resZ = pg_query ($con,$sqlZ);
                    $faturamento_codigo = pg_fetch_result ($resZ,0,0);
                    $msg_erro .= pg_errormessage($con);

                    for ($x = 0 ; $x < $qtde_produtos_ressarcimento ; $x++) {

                        $produto_referencia = pg_fetch_result ($resProduto,$x,produto_referencia);
                        $nota_fiscal_origem = pg_fetch_result($resProduto, $x, "nota_fiscal");

                        $sql_peca = "SELECT peca FROM tbl_peca where fabrica = $login_fabrica and referencia = '$produto_referencia' and produto_acabado is true";

                        $res_peca = pg_query($con,$sql_peca);

                        if (pg_num_rows($res_peca)>0) {
                            $peca = pg_fetch_result($res_peca,0,0);
                        } else {
                            $msg_erro = "Peça não encontrada";
                        }

                        if (strlen($msg_erro)==0) {
                            if ($login_fabrica == 146) {
                                $sql = "INSERT INTO tbl_faturamento_item
                                        (faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, base_ipi, valor_ipi,nota_fiscal_origem,extrato_devolucao,devolucao_obrig)
                                        VALUES ($faturamento_codigo, $peca,1, 0, 0, 0, 0, 0, 0, 0,'$nota_fiscal_origem',$extrato, true)";
                            } else {
                                $sql = "INSERT INTO tbl_faturamento_item
                                        (faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, base_ipi, valor_ipi,nota_fiscal_origem,extrato_devolucao,devolucao_obrig)
                                        VALUES ($faturamento_codigo, $peca,1, 0, 0, 0, 0, 0, 0, 0,'$nota_fiscal',$extrato, true)";
                            }
                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);
                        }

                    }
                if (strlen($msg_erro)==0) {
                    $resX = pg_query ($con,"COMMIT TRANSACTION");
                }else{
                    $resX = pg_query ($con,"ROLLBACK TRANSACTION");
                }
            }
        }
    exit;
}


if ($login_e_distribuidor == 't' AND $extrato < 185731) {
    #header ("Location: new_extrato_distribuidor_retornaveis.php?extrato=$extrato");
    exit;
}

if ($extrato < 185731){# liberado para toda a rede Solicitado por Sergio Mauricio 31/08/2007 - Fabio
    if (array_search($login_posto, $postos_permitidos)==0){ //verifica se o posto tem permissao
        header("Location: extrato_posto.php");
        exit();
    }
}

if (strlen($extrato)==0){
    header("Location: extrato_posto.php");
}

$enviado = "nao";

$btn_acao = trim($_POST['botao_acao']);
if (strlen($btn_acao) > 0 AND $btn_acao == "digitou_qtde") {
//     if($telecontrol_distrib){
//         header("Location: extrato_lgr_devolucao_telecontrol.php?extrato=$extrato");
//     }

    $sql_update = "UPDATE tbl_extrato_lgr
            SET qtde_pedente_temp = null
            WHERE extrato=$extrato";
    $res_update = pg_query ($con,$sql_update);
    $msg_erro .= pg_errormessage($con);

    if($login_fabrica == 161) {
        if($_POST["qtde_linha_aux_peca"] || $_POST["qtde_linha_aux_produto"]) {
            $numero_linhas_peca = (int)$_POST["qtde_linha_aux_peca"];
            $numero_linhas_produto = (int)$_POST["qtde_linha_aux_produto"];
        }

        $post_devolucao_peca    = $_POST["post_devolucao_aux_peca"];
        $post_devolucao_produto = $_POST["post_devolucao_aux_produto"];

        if($_POST['enviado']) { 
            $enviado = "sim";
        }

    }else {
        $numero_linhas = (!empty($_POST['qtde_linha'])) ? trim($_POST['qtde_linha']) : trim($_POST['qtde_linha_produto']);
    }
    $qtde_pecas      = trim($_POST['qtde_pecas']);
    $pecas_pendentes = trim($_POST['pendentes']);
    

    if (isset($_POST['qtde_linha_produto']) && !empty($_POST['qtde_linha_produto'])) {
        $produto_informado = 'sim';
    }

    $resX = pg_query ($con,"BEGIN TRANSACTION");

    for($i=1;$i<=$qtde_pecas;$i++){

        $extrato_lgr = trim($_POST["item_$i"]);
        $peca_tem = trim($_POST["peca_tem_$i"]);
        $peca = trim($_POST["peca_$i"]);
        $qtde_pecas_devolvidas = trim($_POST["$extrato_lgr"]);

        if ($peca_tem>$qtde_pecas_devolvidas){
            $diminuiu='sim';
        }

        if (strlen($qtde_pecas_devolvidas)>0){
                $sql_update = "UPDATE tbl_extrato_lgr
                        SET qtde_pedente_temp = $qtde_pecas_devolvidas
                        WHERE extrato=$extrato
                        AND peca=$peca";
                $res_update = pg_query ($con,$sql_update);
                $msg_erro .= pg_errormessage($con);
        }
        else{
            //$msg_erro="Informe a quantidade de peças que serão devolvidas!";
        }

        if (strlen($msg_erro)>0) break;
    }

    if (strlen($msg_erro) == 0) {
        $resX = pg_query ($con,"COMMIT TRANSACTION");
    }else{

    }
}

if (strlen($btn_acao) > 0 AND $btn_acao == "digitou_as_notas") {

    $sql = "SELECT posto_fabrica from tbl_fabrica where fabrica = $login_fabrica";

    $res = pg_query($con,$sql);
    if (pg_num_rows($res)>0) {
        $posto_fabrica = pg_fetch_result($res,0,0);
    }

    $nota_consumidor = $_POST['envio_consumidor_nota_fiscal'];

    if(strlen($nota_consumidor) > 0){
	    $sql_nota_consumidor = "SELECT nota_fiscal from tbl_faturamento WHERE fabrica = $login_fabrica and distribuidor = $login_posto and nota_fiscal='$nota_consumidor'";

	    $res_nota_consumidor = pg_query($con,$sql_nota_consumidor);

	    if(pg_num_rows($res_nota_consumidor)>0) {
		$nota_fiscal = pg_fetch_result($res_nota_consumidor,0,0);
		$msg_erro .= "A Nota fiscal $nota_fiscal já foi cadastrada no sistema, por favor digite outra nota";
	    }
    }

    $qtde_pecas         = trim($_POST['qtde_pecas']);
    $numero_linhas      = trim($_POST['qtde_linha']);
    $numero_de_notas    = trim($_POST['numero_de_notas']);
    $numero_de_notas_tc = trim($_POST['numero_de_notas_tc']); # para a telecontrol
    $data_preenchimento = date("Y-m-d");
    $array_notas        = array();
    $array_notas_tc     = array();
    $resX               = pg_query ($con,"BEGIN TRANSACTION");

    for($i=0;$i<$numero_de_notas;$i++){
        $nota_fiscal = ($login_fabrica == 186) ? $_POST['extrato'] : trim($_POST["nota_fiscal_$i"]);

            $nota_fiscal = str_replace(".","",$nota_fiscal);
            $nota_fiscal = str_replace(",","",$nota_fiscal);
            $nota_fiscal = str_replace("-","",$nota_fiscal);
            $nota_fiscal = str_replace("/","",$nota_fiscal);

            $nota_fiscal = ltrim ($nota_fiscal, "0");

            if (strlen($nota_fiscal)==0){
                $msg_erro='Digite todas as notas fiscais!';
                break;
            }

            if (!is_numeric($nota_fiscal)) {
                $msg_erro .= "O número das notas fiscais devem ter somente números!";
            }

        if ($nota_fiscal==0) {
            $msg_erro .= "O número das notas fiscais devem ter somente números!";
        }

        $intervalo = '';
        if (in_array($login_fabrica, array(6))) {
            $intervalo = "and emissao > now() - interval '1 year'";
        }
        $sql_nota = "SELECT nota_fiscal from tbl_faturamento where nota_fiscal = '$nota_fiscal' and distribuidor = $login_posto and posto = $posto_fabrica and fabrica = $login_fabrica {$intervalo}";

        $res_nota = pg_query($con,$sql_nota);

        if(pg_num_rows($res_nota)>0) {
            $nota_fiscal = pg_fetch_result($res_nota,0,0);
            //$extrato_devolucao = pg_fetch_result($res_nota,0,1);
            $msg_erro .= "A Nota fiscal $nota_fiscal já foi utilizada no extrato $extrato_devolucao, por favor digite outra nota";
        }

        array_push($array_notas,$nota_fiscal);

        $campo_movimento = $i + 1;
        $total_nota = trim($_POST["id_nota_$i-total_nota"]);
        $base_icms  = trim($_POST["id_nota_$i-base_icms"]);
        $valor_icms = trim($_POST["id_nota_$i-valor_icms"]);
        $base_ipi   = trim($_POST["id_nota_$i-base_ipi"]);
        $valor_ipi  = trim($_POST["id_nota_$i-valor_ipi"]);
        $cfop       = trim($_POST["id_nota_$i-cfop"]);

        if(in_array($login_fabrica, array(101,125,153))){
            if(empty($cfop)){
                $cfop = $_POST["cfop"];
            }

            if(empty($base_icms)){
                $base_icms = 0;
            }

            if(empty($valor_icms)){
                $valor_icms = 0;
            }
            if(empty($base_ipi)){
                $base_ipi = 0;
            }
            if(empty($valor_ipi)){
                $valor_ipi = 0;
            }
        }

        if ($login_fabrica == '35') {
            $movimento = 'RETORNAVEL';
        } else {
            $movimento  = trim($_POST["id_nota_$campo_movimento-movimento"]);
        }

        // if($telecontrol_distrib){
        //     $distribuidor = $_POST["id_nota_$i-distribuidor"];
        // }else{
        //     $distribuidor = $login_posto;
        // }

        //$linha_nota = trim($_POST["id_nota_$i-linha"]);

        $qtde_peca_na_nota = trim($_POST["id_nota_$i-qtde_itens"]);

        $cfop = (strlen($cfop)>0) ? " '$cfop' " : " NULL ";

        if (strlen($msg_erro)==0){
            $sql = "INSERT INTO tbl_faturamento (
                    fabrica,
                    emissao,
                    saida,
                    posto,
                    distribuidor,
                    total_nota,
                    nota_fiscal,
                    serie,
                    natureza,
                    base_icms,
                    valor_icms,
                    base_ipi,
                    valor_ipi,
                    cfop,
                    movimento
                ) VALUES (
                    $login_fabrica,
                    '$data_preenchimento',
                    '$data_preenchimento',
                    $posto_fabrica,
                    $login_posto,
                    $total_nota,
                    '$nota_fiscal',
                    '2',
                    'Devolução de Garantia',
                    $base_icms,
                    $valor_icms,
                    $base_ipi,
                    $valor_ipi,
                    $cfop,
                    '$movimento'
                )";
            $res = pg_query ($con,$sql);

            $sql = "SELECT CURRVAL ('seq_faturamento')";
            $resZ = pg_query ($con,$sql);
            $faturamento_codigo = pg_fetch_result ($resZ,0,0);

            #echo "$faturamento_codigo - ";
            for($x=1;$x<=$qtde_peca_na_nota;$x++){                
                $lgr                = trim($_POST["id_item_LGR_$x-$i"]);
                $peca               = trim($_POST["id_item_peca_$x-$i"]);
                $peca_preco         = trim($_POST["id_item_preco_$x-$i"]);
                $peca_qtde_total_nf = trim($_POST["id_item_qtde_$x-$i"]);
                $peca_aliq_icms     = trim($_POST["id_item_icms_$x-$i"]);
                $peca_aliq_ipi      = trim($_POST["id_item_ipi_$x-$i"]);
                $peca_total_item    = trim($_POST["id_item_total_$x-$i"]);
                $os_item 	    = trim($_POST["id_os_item_$x-$i"]);

                $sql_update = " UPDATE  tbl_extrato_lgr
                                SET     qtde_nf = (
                                                    CASE WHEN qtde_nf IS NULL
                                                         THEN 0
                                                         ELSE qtde_nf
                                                    END
                                                  ) + $peca_qtde_total_nf
                                WHERE   extrato  = $extrato
                                AND     peca    = $peca";
                $res_update = pg_query ($con,$sql_update);
                $msg_erro .= pg_errormessage($con);

                if(in_array($login_fabrica, [120,201,175])){
                    $campos_nemmaq = "tbl_faturamento_item.valor_subs_trib, tbl_faturamento_item.base_subs_trib, tbl_faturamento_item.valor_icms_st, tbl_faturamento_item.base_st,";
                }

                $sql_nf = "SELECT
                                tbl_faturamento_item.faturamento_item,
                                tbl_faturamento.nota_fiscal,
                                tbl_faturamento_item.qtde,
                                tbl_faturamento_item.peca,
                                tbl_faturamento_item.preco,
                                tbl_faturamento_item.aliq_icms,
                                tbl_faturamento_item.aliq_ipi,
                                tbl_faturamento_item.base_icms,
                                tbl_faturamento_item.devolucao_obrig,
                                tbl_faturamento_item.valor_icms,
                                tbl_faturamento_item.linha,
                                tbl_faturamento_item.base_ipi,
                                tbl_faturamento_item.valor_ipi,
								tbl_faturamento_item.sequencia,
								tbl_faturamento_item.os,
                                $campos_nemmaq
								tbl_faturamento_item.os_item
                            FROM tbl_faturamento_item
                            JOIN tbl_faturamento      USING (faturamento)
                            JOIN tbl_peca USING(peca)
                            WHERE ";
                            if ($login_fabrica == 81 or $login_fabrica ==51 or $login_fabrica == 114 or $login_fabrica == 153 or $telecontrol_distrib) {
                                $sql_nf .= " (tbl_faturamento.fabrica = $login_fabrica or tbl_faturamento.fabrica =10) and tbl_peca.fabrica = $login_fabrica ";
                            } else {
                                $sql_nf .= " tbl_faturamento.fabrica = $login_fabrica ";
                            }

                            if ($login_fabrica == 91) {
                                $sql_nf .= " AND tbl_faturamento_item.aliq_ipi = $peca_aliq_ipi
                                            AND tbl_faturamento_item.aliq_icms =  $peca_aliq_icms";
                            }

							if(in_array($login_fabrica,array(145,153)) and !empty($os_item)){
								$sql_nf .= " AND tbl_faturamento_item.os_item = $os_item ";
							}

                            $sql_nf .= " AND   tbl_faturamento.posto   = $login_posto
                            AND   tbl_faturamento_item.extrato_devolucao = $extrato
                            AND   tbl_faturamento_item.peca=$peca
                            AND   tbl_faturamento_item.preco=$peca_preco
                            AND   (tbl_faturamento.distribuidor IS NULL or tbl_faturamento.distribuidor in(4311,376542))                            ORDER BY tbl_faturamento.nota_fiscal";
                $resNF = pg_query($con,$sql_nf);
                // echo nl2br($sql_nf); exit;
				if($telecontrol_distrib and pg_num_rows($resNF) == 0 ) {
					$msg_erro = "Houve um erro na hora de gravar nota, favor entrar em contato com Suporte Telecontrol";
				}
                $qtde_peca_inserir=0;
                $xpeca_base_icms = 0;

                for ($w = 0 ; $w < pg_num_rows ($resNF) ; $w++) {

                    if ($qtde_peca_inserir < $peca_qtde_total_nf && !in_array($login_fabrica, array(101))){

                        $faturamento_item= pg_fetch_result ($resNF,$w,faturamento_item);
                        $peca_nota       = pg_fetch_result ($resNF,$w,nota_fiscal);
                        $peca_qtde       = pg_fetch_result ($resNF,$w,qtde);
                        $peca_peca       = pg_fetch_result ($resNF,$w,peca);
                        $peca_preco      = pg_fetch_result ($resNF,$w,preco);
                        $peca_aliq_icms  = pg_fetch_result ($resNF,$w,aliq_icms);
                        $peca_base_icms  = pg_fetch_result ($resNF,$w,base_icms);
                        $devolucao_obrig  = pg_fetch_result ($resNF,$w,devolucao_obrig);
                        $peca_valor_icms = pg_fetch_result ($resNF,$w,valor_icms);
                        $peca_linha      = pg_fetch_result ($resNF,$w,linha);
                        $peca_aliq_ipi   = pg_fetch_result ($resNF,$w,aliq_ipi);
                        $peca_base_ipi   = pg_fetch_result ($resNF,$w,base_ipi);
                        $peca_valor_ipi  = pg_fetch_result ($resNF,$w,valor_ipi);
                        $sequencia       = pg_fetch_result ($resNF,$w,sequencia);
                        $os              = pg_fetch_result ($resNF,$w,os);
                        $os_item         = pg_fetch_result ($resNF,$w,os_item);

                        if(in_array($login_fabrica, [120,201,175])){
                            $base_subs_trib  = pg_fetch_result ($resNF,$w,base_subs_trib);
                            $valor_subs_trib = pg_fetch_result ($resNF,$w,valor_subs_trib); 

                            /*
                            if (empty($valor_icms_st)) {
                                $valor_icms_st = 0;
                            }

                            if (empty($base_st)) {
                                $base_st = 0;
                            }*/

                            if(strlen(trim($base_subs_trib))==0){
                                $base_subs_trib = 'null';
                            }

                            if(strlen(trim($valor_subs_trib))==0){
                                $valor_subs_trib = 'null';
                            }
                        }

                        if (strlen($peca_aliq_icms)==0) {
                            $peca_aliq_icms = '0';
                        }

                        if (strlen($peca_base_icms)==0) {
                            $peca_base_icms = '0';
                        }

                        if (strlen($peca_valor_icms)==0) {
                            $peca_valor_icms = '0';
                        }

                        if (strlen($peca_base_ipi)==0) {
                            $peca_base_ipi = '0';
                        }

                        if (strlen($peca_valor_ipi)==0) {
                            $peca_valor_ipi = '0';
                        }

                        if (strlen($peca_aliq_ipi)==0) {
                            $peca_aliq_ipi = '0';
                        }

                        $qtde_peca_inserir += $peca_qtde;

						if(empty($os)) { $os = "null";}
						if(empty($os_item)) { $os_item = "null";}

                        if ($qtde_peca_inserir >= $peca_qtde_total_nf and $login_fabrica <> 91){
                            $peca_base_icms  = 0;
                            $peca_valor_icms = 0;
                            $peca_base_ipi   = 0;
                            $peca_valor_ipi  = 0;
                         // $peca_qtde       = $peca_qtde-$qtde_peca_inserir;
                            $peca_qtde       = $peca_qtde - ($qtde_peca_inserir-$peca_qtde_total_nf);

                            if ($peca_aliq_icms>0){
                                if($login_fabrica == 91){
                                    $peca_base_icms += $xpeca_base_icms;
                                } else{
                                    $peca_base_icms = $peca_qtde_total_nf*$peca_preco;
                                }
                                $peca_valor_icms= $peca_qtde_total_nf*$peca_preco*$peca_aliq_icms/100;
                            }
                            if ($peca_aliq_ipi>0){
                                $peca_base_ipi = $peca_qtde_total_nf*$peca_preco;
                                $peca_valor_ipi= $peca_qtde_total_nf*$peca_preco*$peca_aliq_ipi/100;
                            }
                        }

						if(empty($devolucao_obrig)) $devolucao_obrig = "f";

                        if(in_array($login_fabrica, [120,201, 175])){
                            $sql = "INSERT INTO tbl_faturamento_item
                                (faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, base_ipi, valor_ipi,nota_fiscal_origem,extrato_devolucao,devolucao_obrig,os,os_item, base_subs_trib, valor_subs_trib)
                                VALUES ($faturamento_codigo, $peca,$peca_qtde, $peca_preco, $peca_aliq_icms, $peca_aliq_ipi, $peca_base_icms, $peca_valor_icms, $peca_base_ipi, $peca_valor_ipi,'$peca_nota',$extrato,'$devolucao_obrig',$os,$os_item, $base_subs_trib, $valor_subs_trib)";  
                        }else{
                            $sql = "INSERT INTO tbl_faturamento_item
                                (faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, base_ipi, valor_ipi,nota_fiscal_origem,extrato_devolucao,devolucao_obrig,os,os_item)
                                VALUES ($faturamento_codigo, $peca,$peca_qtde, $peca_preco, $peca_aliq_icms, $peca_aliq_ipi, $peca_base_icms, $peca_valor_icms, $peca_base_ipi, $peca_valor_ipi,'$peca_nota',$extrato,'$devolucao_obrig',$os,$os_item)";
                        }
                        $res = pg_query ($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }else{
                        break;
                    }
                }
            }

            if($login_fabrica == 153 || $login_fabrica == 101){
                /* TROCA DE PRODUTO */
                $qtde_total_troca_produto = $_POST["total_troca_produto"];

                for($x=1;$x<=$qtde_total_troca_produto;$x++){

                    $os             = trim($_POST["troca_produto_".$x."_os"]);
                    $peca           = trim($_POST["troca_produto_".$x."_peca"]);
                    $peca_preco     = trim($_POST["troca_produto_".$x."_preco"]);
                    $peca_qtde      = trim($_POST["troca_produto_".$x."_qtde"]);
                    $peca_aliq_icms = $valor_icms;
                    $peca_aliq_ipi  = trim($_POST["troca_produto_".$x."_ipi"]);
                    $peca_nota      = trim($_POST["troca_produto_".$x."_nf"]);

                    $sql_update = " UPDATE  tbl_extrato_lgr
                        SET qtde_nf = (
                                CASE WHEN qtde_nf IS NULL
                                    THEN 0
                                    ELSE qtde_nf
                                END
                            ) + 1
                        WHERE extrato  = $extrato
                            AND peca = $peca";
                    $res_update = pg_query ($con,$sql_update);

                    $campo_os_item = "";
                    $valor_os_item = "";

                    $sql_os_item = "SELECT 
                                        tbl_os_item.os_item 
                                    FROM tbl_os_item 
                                    INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_produto.os = {$os}
                                    WHERE 
                                        tbl_os_item.peca = {$peca}";
                    $res_os_item = pg_query($con, $sql_os_item);

                    if(pg_num_rows($res_os_item) > 0){

                        $os_item = pg_fetch_result($res_os_item, 0, "os_item");

                        $campo_os_item = ", os_item";
                        $valor_os_item = ", {$os_item}";

                    }

                    if (strlen($peca_preco)==0) {
                        $peca_preco = '0';
                    }

                    if (strlen($peca_aliq_icms)==0) {
                        $peca_aliq_icms = '0';
                    }

                    if (strlen($peca_base_icms)==0) {
                        $peca_base_icms = '0';
                    }

                    if (strlen($peca_valor_icms)==0) {
                        $peca_valor_icms = '0';
                    }

                    if (strlen($peca_base_ipi)==0) {
                        $peca_base_ipi = '0';
                    }

                    if (strlen($peca_valor_ipi)==0) {
                        $peca_valor_ipi = '0';
                    }

                    if (strlen($peca_aliq_ipi)==0) {
                        $peca_aliq_ipi = '0';
                    }

                    $sql = "INSERT INTO tbl_faturamento_item
                            (faturamento, peca, qtde,preco, aliq_icms, aliq_ipi, base_icms, valor_icms, base_ipi, valor_ipi,nota_fiscal_origem,extrato_devolucao,devolucao_obrig,os {$campo_os_item})
                            VALUES ($faturamento_codigo, $peca,$peca_qtde, $peca_preco, $peca_aliq_icms, $peca_aliq_ipi, $peca_base_icms, $peca_valor_icms, $peca_base_ipi, $peca_valor_ipi,'$peca_nota',$extrato,'t',{$os} {$valor_os_item})";
                    $res = pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                }
            }
        }
    }

    if($login_fabrica == 35){
        $sql = "UPDATE tbl_extrato SET admin_lgr = 9105 where extrato = $extrato and fabrica = $login_fabrica ";
        $res = pg_query($con, $sql);
    }

    if (strlen($msg_erro) == 0) {
        $sql_update = "UPDATE tbl_extrato_lgr
                SET qtde_pedente_temp = null
                WHERE extrato=$extrato";

        $res_update = pg_query ($con,$sql_update);
        $msg_erro .= pg_errormessage($con);
    }

    if (in_array($login_fabrica, array(24))) {
        $sqlA = "SELECT posto_bloqueio
                    FROM tbl_posto_bloqueio 
                    WHERE fabrica = {$login_fabrica} 
                        AND posto = {$login_posto} 
                        AND observacao = 'Extrato com mais de 60 dias sem fechamento'
                        AND desbloqueio is not true";
        $resA = pg_query($con,$sqlA);

        if (pg_num_rows($resA) > 0) {
            $posto_bloqueio = pg_fetch_result($resA, 0, posto_bloqueio);

            $sqlB = "UPDATE tbl_posto_bloqueio set
                            desbloqueio = 't',
                            resolvido = current_date
                        WHERE posto_bloqueio = {$posto_bloqueio};";
            $resB = pg_query($con,$sqlB);
        }

        if (pg_last_error($con) > 0) {
            $msg_erro .= " Erro ao Desbloquear o Posto! ";
        }
    }

    if (strlen($msg_erro) == 0) {
        if (count(array_unique($array_notas))<>$numero_de_notas){
            $msg_erro .= "Erro: não é permitido digitar número de notas iguais. Preencha novamente as notas.";
        }
    }

    if (strlen($msg_erro) == 0) {
        $resX = pg_query ($con,"COMMIT TRANSACTION");
        header("Location:extrato_posto_lgr_itens_novo.php?extrato=$extrato");
    }else{
        $resX = pg_query ($con,"ROLLBACK TRANSACTION");
    }
    $nota_fiscal = "";
}

$msg = "";

$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";
?>

<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script language='javascript'>

function gravaRessarcimento(nota,extrato) {
    if (nota.length>0) {
        url = "<? echo $PHP_SELF;?>?ajax=sim&nota="+nota+"&extrato="+extrato;
        requisicaoHTTP('GET',url, true , 'respostas');
    } else {
        alert('Digite o Número da Nota Fiscal');
    }
}

function respostas(campos) {
    if (campos == 'ok') {
        document.getElementById('div_msg').style.display = 'block';
        document.getElementById('div_msg').innerHTML = 'Nota Gravada com Sucesso';
    }
    if (campos == 'Erro')   {
        document.getElementById('div_msg').style.display = 'block';
        document.getElementById('div_msg').innerHTML = 'Nota já Cadastrada no Sistema';
    }
}

</script>

<style type="text/css">
.Tabela{
    border:1px solid #596D9B;
    background-color:#596D9B;
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

.menu_top2 {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: bold;
    border: 1px solid;
    color:#ffffff;
    background-color: red
}
.menu_top3 {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: bold;
    border: 1px solid;
    color:#ffffff;
    background-color: #FA8072
}

.table_line {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 12px;
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

.msgImposto {
    font-size: 14px;
    color: #FF0000;
    text-align: center;
}

.alinha_form {
    margin: 0 auto;
}

</style>

<script type="text/javascript">

function verificar(forrr){
    var theform = document.getElementById('frm_devolucao');
    var returnval=true;
    for (i=0; i<theform.elements.length; i++){
        if (theform.elements[i].type=="text"){
            if (theform.elements[i].value==""){ //if empty field
                alert("Por favor, informe todas as notas!");
                theform.botao_acao.value='';
                returnval=false;
                break;
            }
        }
    }
    return returnval;
}

</script>

<br><br>
<?php
if ($login_fabrica == 94) {
?>
<center>
<table width="75%" border="0" align="center" class="msgImposto">
    <tr>
        <td>Empresa <strong>OPTANTE PELO SIMPLES "NÃO"</strong> deve mencionar os impostos em seus respectivos campos. Estes valores devem ser mencionados no campo <strong>"DADOS ADICIONAIS"</strong></td>
    </tr>
</table>
</center>
<br />
<?php } ?>
<br />
<?

$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
                to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
                tbl_posto.nome ,
                tbl_posto_fabrica.codigo_posto
        FROM tbl_extrato
        JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
        JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
        WHERE tbl_extrato.extrato = $extrato ";
// echo nl2br($sql); exit;
$res = pg_query ($con,$sql);
$data    = pg_fetch_result ($res,0,data);
$periodo = pg_fetch_result ($res,0,periodo);
$nome    = pg_fetch_result ($res,0,nome);
$codigo  = pg_fetch_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='-1' face='arial'>$codigo - $nome</font>";

?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td <?=$display_none?> align='center' width='33%'><a href='<?=($login_fabrica == 101) ? 'os_extrato_new.php' : 'os_extrato_novo_lgr.php'?>'>Ver outro extrato</a></td>
</tr>
</table>

<div id='loading'></div>

<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="75%" border="0" align="center" class="error">
    <tr>
        <td><?echo $msg_erro ?></td>
    </tr>
</table>
<? } ?>

<br>

<?php if ($areaAdmin != true) { ?>

    <TABLE width="75%" align="center" border="0" cellspacing="0" cellpadding="2">
        <TR>
            <TD colspan="10" class="menu_top" ><div align="center" style='font-size:16px'>
            <b>
            ATENÇÃO
            </b></div></TD>
        </TR>
        <TR>
            <TD colspan='8' class="table_line" style='padding:10px'>
            As peças ou produtos não devolvidos neste extrato serão apresentadas na tela de consulta de pendências. Caso não sejam efetivadas as devoluções, os itens serão cobrados do posto autorizado.
        <br><br>
        <? //HD 15408
        if($login_fabrica == 35) {
            echo "<b style='font-size:12px;font-weight:bold'>Emitir a NF conforme espelho da tela a seguir. Lembrando que não é para destacar nenhum tipo de imposto, o valor total dos produtos é o mesmo do valor total da NF. Assim que a NF for emitida e embalado os volumes, não esquecer de solicitar o código de postagem através do link <a href='https://app.smartsheet.com/b/form?EQBCT=7d98ed15ce1e4fb0adc95e2fc61a18ce' target='_blank'>app.smartsheet.com</a> , preenchendo o formulário com as informações solicitadas.";
        }else{
            if($telecontrol_distrib) {
                echo "<b>Emitir a NF conforme espelho da tela a seguir. Lembrando que não é para destacar nenhum tipo de imposto, o valor total dos produtos é o mesmo do valor total da NF!<br>Para maiores informações, favor enviar um e-mail para rede.autorizada@telecontrol.com.br, ou entrar em contato pelo 0800 718 7825</b>";
            }else if($login_fabrica != 186){
                    echo "<b style='font-size:12px;font-weight:bold'>Emitir as NF de devolução nos mesmos valores e impostos, referenciando NF de origem, e postagem da NF de acordo com o cabeçalho de cada nota fiscal.";
            }
        }?>
         </b>   </TD>
        </TR>
    </table>
<?
}

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_query ($con,$sql);
$estado_origem = pg_fetch_result ($resX,0,estado);
$sqlLimit = "LIMIT 1";
$sql = "SELECT  tbl_faturamento_item.extrato_devolucao,
        tbl_faturamento.distribuidor,
        CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
        CASE WHEN ".($login_fabrica == 114 ? " tbl_faturamento.fabrica = 10 OR " : "")." tbl_faturamento.fabrica in (35,91,120,201,129,131,134,138, 139,140,141,144,157, 160,161) or tbl_faturamento.fabrica > 161 THEN
            tbl_faturamento_item.devolucao_obrig
        ELSE
            tbl_peca.devolucao_obrigatoria
        END AS devolucao_obrigatoria
    FROM    tbl_faturamento
    JOIN    tbl_faturamento_item USING (faturamento)
    JOIN    tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca 
    WHERE   tbl_faturamento_item.extrato_devolucao = $extrato
    AND     tbl_faturamento.posto             = $login_posto
    AND     tbl_faturamento.fabrica           ".(($login_fabrica == 114 or $login_fabrica == 153 or $telecontrol_distrib) ? " IN (10, $login_fabrica) " : " = $login_fabrica");
if($telecontrol_distrib) {
    $sql.="     AND     (tbl_faturamento.distribuidor IS NULL or tbl_faturamento.distribuidor  in (4311,376542) ) ";
}
    $sql.=" AND (
                    tbl_faturamento.cfop ilike '59%'
                or  tbl_faturamento.cfop ilike '69%'
                or  tbl_faturamento_item.cfop ilike '59%'
                or  tbl_faturamento_item.cfop ilike '69%'
                )
    ";
if(!$telecontrol_distrib){
    $sql .= $sqlLimit;
}

 //echo nl2br($sql);

$res = pg_query ($con,$sql);
$res_qtde = pg_num_rows ($res);


if ($res_qtde > 0 || $login_fabrica == 101 || $login_fabrica == 146 || $login_fabrica == 153) {
    if($telecontrol_distrib OR $login_fabrica == 101){
        $resultado = pg_fetch_all($res);
        foreach($resultado as $chave=>$val){
            $distribuidor[]         = $val["distribuidor"];
            $produto_acabado[]      = $val["produto_acabado"];
        }
        $extrato_devolucao  = $extrato;
        $distribuidor       = array_unique($distribuidor);
        $produto_acabado    = array_unique($produto_acabado);

        $devolucao = " RETORNO OBRIGATÓRIO ";
        $movimento = "RETORNAVEL";

        foreach($distribuidor as $var=>$distrib){
                $aux = 20682;
			$sqlEndereco = "
                SELECT  tbl_posto.posto     ,
                        tbl_posto.nome      ,
                        tbl_posto.endereco  ,
			tbl_posto.numero    ,
			tbl_posto.complemento,
                        tbl_posto.cidade    ,
                        tbl_posto.estado    ,
                        tbl_posto.cep       ,
                        tbl_posto.fone      ,
                        tbl_posto.cnpj      ,
                        tbl_posto.ie
                FROM    tbl_posto
                WHERE   tbl_posto.posto = $aux
            ";
            $resEndereco  = pg_query($con,$sqlEndereco);

            $razao[$distrib]        = pg_fetch_result($resEndereco,0,nome);
            $endereco[$distrib]     = pg_fetch_result($resEndereco,0,endereco);
	        $numero[$distrib]       = pg_fetch_result($resEndereco,0,numero);
	        $complemento[$distrib]  = pg_fetch_result($resEndereco,0,complemento);
            $cidade[$distrib]       = pg_fetch_result($resEndereco,0,cidade);
            $estado[$distrib]       = pg_fetch_result($resEndereco,0,estado);
            $cep[$distrib]          = pg_fetch_result($resEndereco,0,cep);
            $fone[$distrib]         = pg_fetch_result($resEndereco,0,fone);
            $cnpj[$distrib]         = pg_fetch_result($resEndereco,0,cnpj);
            $ie[$distrib]           = pg_fetch_result($resEndereco,0,ie);
	        $endereco[$distrib]     = $endereco[$distrib] . ', ' . $numero[$distrib] . ' ' . $complemento[$distrib];
        }

?>
        <form method='post' action='<?=$PHP_SELF?>' name='frm_devolucao' id='frm_devolucao'>
            <input type='hidden' name='notas_d' value=''>
            <input type='hidden' name='extrato' value='<?=$extrato?>'>
            <input type='hidden' id='botao_acao' name='botao_acao' value=''>
            <input type='hidden' name='id_nota_1-movimento'  value='<?=$movimento?>'>

            <br /><br />

<?
		if(in_array($login_fabrica, array(114,153))) {
			$campos = ", tbl_faturamento_item.os_item ";
		}

        if ($login_fabrica == 101 || $login_fabrica == 153){


            $extrato_troca_produto = false;

            $join_peca = "INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.ativo IS TRUE and produto_acabado";

            $campo_preco = ($login_fabrica == 101) ? 'tbl_pedido_item.preco AS peca_preco,' : 'tbl_os_item.preco AS peca_preco,';

            /* TROCA DE PRODUTO */
            $sql = "SELECT DISTINCT
                    tbl_os.os,
                    tbl_os.sua_os,
                    tbl_os_troca.ressarcimento,
                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                    tbl_faturamento.nota_fiscal,
                    tbl_peca.peca,
                    tbl_peca.referencia AS peca_referencia,
                    tbl_peca.descricao AS peca_descricao,
                    tbl_peca.ipi AS peca_ipi,
                    tbl_os_item.qtde AS peca_qtde,
                    $campo_preco
                    tbl_admin.login
                FROM tbl_os
                    JOIN tbl_os_extra     ON tbl_os_extra.os = tbl_os.os
                    JOIN tbl_os_troca     ON tbl_os_troca.os = tbl_os.os
                    LEFT JOIN tbl_admin   ON tbl_os.troca_garantia_admin = tbl_admin.admin
                    JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                        AND tbl_pedido_item.qtde_cancelada = 0
                        AND (tbl_pedido_item.qtde_faturada > 0 or tbl_pedido_item.qtde_faturada_distribuidor > 0)
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                    {$join_peca}
                    JOIN tbl_faturamento_item ON (tbl_faturamento_item.pedido = tbl_os_item.pedido or tbl_faturamento_item.os = tbl_os.os) 
                        And tbl_faturamento_item.peca = tbl_os_item.peca
                    JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                WHERE tbl_os_extra.extrato = $extrato
                    AND  tbl_os.fabrica        = $login_fabrica
					AND  tbl_os.posto          = $login_posto
                    AND tbl_os_troca.ressarcimento IS NOT TRUE";

            if ($login_fabrica == 101) {
                $sql .= " AND tbl_os.prateleira_box = 'troca_lgr'";
            }

            $resTrocaProduto = pg_query ($con,$sql);

            $array_notas_troca_produto = array();

            if(pg_num_rows($resTrocaProduto)){
                while($objeto_troca = pg_fetch_object($resTrocaProduto)){
                    $array_notas_troca_produto[] = $objeto_troca->nota_fiscal;

                    $array_troca_ressarcimento[] = array(
                        "os"              => $objeto_troca->os,
                        "sua_os"          => $objeto_troca->sua_os,
                        "ressarcimento"   => $objeto_troca->ressarcimento,
                        "data_fechamento" => $objeto_troca->data_fechamento,
                        "peca"            => $objeto_troca->peca,
                        "peca_"            => $objeto_troca->peca,
                        "peca_referencia" => $objeto_troca->peca_referencia,
                        "peca_descricao"  => $objeto_troca->peca_descricao,
                        "peca_ipi"        => $objeto_troca->peca_ipi,
                        "peca_qtde"       => $objeto_troca->peca_qtde,
                        "peca_preco"      => $objeto_troca->peca_preco,
                        "nota_fiscal"      => $objeto_troca->nota_fiscal,
                        "login"           => $objeto_troca->login
                    );
                }
            }

            /* RESSARCIMENTO */
            $sql = "SELECT
                    tbl_os.os,
                    tbl_os.sua_os,
                    tbl_os_troca.ressarcimento,
                    TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                    tbl_peca.peca,
                    tbl_peca.referencia AS peca_referencia,
                    tbl_peca.descricao AS peca_descricao,
                    tbl_peca.ipi AS peca_ipi,
                    tbl_admin.login
                FROM tbl_os
                    JOIN tbl_os_extra   ON tbl_os_extra.os = tbl_os.os
                    JOIN tbl_os_troca   ON tbl_os_troca.os = tbl_os.os
                    LEFT JOIN tbl_admin ON tbl_os.troca_garantia_admin = tbl_admin.admin
                    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_troca.peca
                WHERE tbl_os_extra.extrato = $extrato
                    AND tbl_os.fabrica = $login_fabrica
                    AND tbl_os.posto   = $login_posto
                    AND tbl_os_troca.ressarcimento IS TRUE
                    AND tbl_peca.produto_acabado IS TRUE
                    AND tbl_peca.referencia IS NOT NULL";
                // echo nl2br($sql);exit;
            $resRessarcimento = pg_query ($con,$sql);

            if(pg_num_rows($resRessarcimento) > 0){
                while($objeto_ressarcimento = pg_fetch_object($resRessarcimento)){
                    $array_troca_ressarcimento[] = array(
                        "os"              => $objeto_ressarcimento->os,
                        "sua_os"          => $objeto_ressarcimento->sua_os,
                        "ressarcimento"   => $objeto_ressarcimento->ressarcimento,
                        "data_fechamento" => $objeto_ressarcimento->data_fechamento,
                        "peca"            => $objeto_ressarcimento->peca,
                        "peca_referencia" => $objeto_ressarcimento->peca_referencia,
                        "peca_descricao"  => $objeto_ressarcimento->peca_descricao,
                        "peca_ipi"        => $objeto_ressarcimento->peca_ipi,
                        "peca_qtde"       => 1,
                        "peca_preco"      => 0,
                        "login"           => $objeto_ressarcimento->login
                    );
                }
            }

            $count_troca_ressarcimento = count($array_troca_ressarcimento);

            if($count_troca_ressarcimento>0){
                $extrato_troca_produto = true;

                $sql = "SELECT razao_social,
                        endereco,
                        cidade,
                        cep,
                        fone,
                        cnpj,
                        ie
                    FROM tbl_fabrica
                        WHERE fabrica = $login_fabrica";
                $resFabrica = pg_query($con,$sql);

                if(pg_num_rows($resFabrica) > 0){
                    $razao    = pg_fetch_result($resFabrica, 0, "razao_social");
                    $endereco = pg_fetch_result($resFabrica, 0, "endereco");
                    $cidade   = pg_fetch_result($resFabrica, 0, "cidade");
                    $estado   = "SP";
                    $cep      = pg_fetch_result($resFabrica, 0, "cep");
                    $fone     = pg_fetch_result($resFabrica, 0, "fone");
                    $cnpj     = pg_fetch_result($resFabrica, 0, "cnpj");
                    $ie       = pg_fetch_result($resFabrica, 0, "ie");
                }

                $natureza_operacao = "Simples Remessa";

                $cfop = "6949";
                $sql = "SELECT contato_estado FROM tbl_posto_fabrica
                    WHERE fabrica = $login_fabrica AND posto = $login_posto";
                $resW = pg_query($con,$sql);

                if (pg_num_rows($resW) > 0){
                    $estado_posto = strtoupper(trim(pg_fetch_result($resW,0,"contato_estado")));

                    if ($estado_posto=='SP'){
                        $cfop = "5949";
                    }
                }

                echo "<input type='hidden' name='troca_produto' value='$extrato'>\n";
                echo "<input type='hidden' name='cfop' value='$cfop'>\n";
                echo "<input type='hidden' name='total_troca_produto' value='$count_troca_ressarcimento'>\n";

                if(!in_array($login_fabrica, array(161)) || (in_array($login_fabrica, array(161))) && $peca_produto == "produto") {

                    echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";

                    echo "<tr align='left'  height='16'>\n";
                    echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
                    echo "<b>&nbsp;<b>PRODUTOS - RETORNO OBRIGATÓRIO</b><br>\n";
                    echo "</td>\n";
                    echo "</tr>\n";

                    echo "<tr>";
                    echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
                    echo "<td>CFOP <br> <b>$cfop</b> </td>";
                    echo "<td>Emissão <br> <b>$data</b> </td>";
                    echo "</tr>";
                    echo "</table>";

                    echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                    echo "<tr>";
                    echo "<td>Razão Social <br> <b>$razao</b> </td>";
                    echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
                    echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
                    echo "</tr>";
                    echo "</table>";


                    echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                    echo "<tr>";
                    echo "<td>Endereço <br> <b>$endereco </b> </td>";
                    echo "<td>Cidade <br> <b>$cidade</b> </td>";
                    echo "<td>Estado <br> <b>$estado</b> </td>";
                    echo "<td>CEP <br> <b>$cep</b> </td>";
                    echo "</tr>";
                    echo "</table>";

                    echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                    echo "<tr align='center'>";
                    echo "<td><b>Código</b></td>";
                    echo "<td><b>Descrição</b></td>";
                    echo "<td><b>Data</b></td>";
                    echo "<td><b>Responsavel</b></td>";
                    echo "<td><b>OS</b></td>";
                    echo "</tr>";

                    $x = 1;

                    for($i=0; $i<$count_troca_ressarcimento; $i++){
                        $os              = $array_troca_ressarcimento[$i]["os"];
                        $sua_os          = $array_troca_ressarcimento[$i]["sua_os"];
                        $ressarcimento   = $array_troca_ressarcimento[$i]["ressarcimento"];
                        $peca            = $array_troca_ressarcimento[$i]["peca"];
                        $peca_referencia = $array_troca_ressarcimento[$i]["peca_referencia"];
                        $peca_descricao  = $array_troca_ressarcimento[$i]["peca_descricao"];
                        $peca_ipi        = $array_troca_ressarcimento[$i]["peca_ipi"];
                        $peca_qtde       = $array_troca_ressarcimento[$i]["peca_qtde"];
                        $peca_preco      = $array_troca_ressarcimento[$i]["peca_preco"];
                        $data_fechamento = $array_troca_ressarcimento[$i]["data_fechamento"];
                        $quem_trocou     = $array_troca_ressarcimento[$i]["login"];
                        $nf_pst          = $array_troca_ressarcimento[$i]["nota_fiscal"];

                        if($ressarcimento == "t"){
                            $tipo_os = "Ressarcimento";
                        }else{
                            $tipo_os = "Troca de Produto";
                        }

                        echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
                        echo "<input type='hidden' name='troca_produto_".$x."_peca' value='$peca'>";
                        echo "<input type='hidden' name='troca_produto_".$x."_os' value='$os'>";
                        echo "<input type='hidden' name='troca_produto_".$x."_nf' value='$nf_pst'>";
                        echo "<input type='hidden' name='troca_produto_".$x."_ipi' value='$peca_ipi'>";
                        echo "<input type='hidden' name='troca_produto_".$x."_qtde' value='$peca_qtde'>";
                        echo "<input type='hidden' name='troca_produto_".$x."_preco' value='$peca_preco'>";
                        echo "<td align='left'>$peca_referencia</td>";
                        echo "<td align='left'>$peca_descricao (".$tipo_os.")</td>";
                        echo "<td align='left'>$data_fechamento</td>";
                        echo "<td align='right'>$quem_trocou</td>";
                        echo "<td align='right'>$sua_os</td>";
                        echo "</tr>";
                        $x++;
                    }
                    echo "</table>";
                }
            }
        }

        /* Produto Acabado */

        if($login_fabrica == 153){
            $sql_prod = "SELECT  tbl_peca.peca                                               ,
                            tbl_peca.referencia                                         ,
                            tbl_peca.descricao                                          ,
                            tbl_peca.ipi                                                ,
                            tbl_peca.ncm                                                ,
                            CASE WHEN tbl_peca.produto_acabado IS TRUE
                                 THEN 'TRUE'
                                 ELSE 'NOT TRUE'
                            END                                     AS produto_acabado  ,
                            tbl_peca.devolucao_obrigatoria                              ,
                            tbl_faturamento_item.aliq_icms                              ,
                            tbl_faturamento_item.aliq_ipi                               ,
                            tbl_faturamento_item.preco                                  ,
                            sum(tbl_faturamento_item.qtde)          AS qtde_real        ,
                            tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL
                                                        THEN 0
                                                        ELSE tbl_extrato_lgr.qtde_nf
                                                    END             AS qtde_total_item  ,
                            tbl_extrato_lgr.qtde_nf                 AS qtde_total_nf    ,
                            tbl_extrato_lgr.qtde_pedente_temp       AS qtde_pedente_temp,
                            tbl_extrato_lgr.extrato_lgr             AS extrato_lgr      ,
                            (
                                tbl_extrato_lgr.qtde_pedente_temp * tbl_faturamento_item.preco
                            )                                       AS total_item       ,
                            tbl_faturamento.cfop                                        ,
                            tbl_faturamento.distribuidor                                ,
                            SUM (tbl_faturamento_item.base_icms)    AS base_icms        ,
                            SUM (tbl_faturamento_item.valor_icms)   AS valor_icms       ,
                            SUM (tbl_faturamento_item.base_ipi)     AS base_ipi         ,
                            SUM (tbl_faturamento_item.valor_ipi)    AS valor_ipi
                            $campos
                    FROM    tbl_faturamento_item
                    $join
                    JOIN    tbl_peca        ON  tbl_peca.peca               = tbl_faturamento_item.peca
                    JOIN    tbl_faturamento ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                    JOIN    tbl_extrato_lgr ON  tbl_extrato_lgr.extrato     = tbl_faturamento_item.extrato_devolucao
                                            AND tbl_extrato_lgr.peca        = tbl_faturamento_item.peca
                                            AND (
                                                    tbl_faturamento_item.faturamento = tbl_extrato_lgr.faturamento
                                                OR  tbl_extrato_lgr.faturamento IS NULL
                                                )
                    WHERE   (
                                tbl_faturamento.fabrica = $login_fabrica
                            OR  tbl_faturamento.fabrica = 10
                            )
                    AND     tbl_peca.fabrica                        = $login_fabrica
                    AND     tbl_faturamento_item.extrato_devolucao  = $extrato
                    AND     tbl_faturamento.posto                   = $login_posto
                    AND     (tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END) > 0
                    AND     (
                                tbl_faturamento.cfop ILIKE '59%'
                            OR  tbl_faturamento.cfop ILIKE '69%'
                            OR  tbl_faturamento_item.cfop ILIKE '59%'
                            OR  tbl_faturamento_item.cfop ILIKE '69%'
                            )
                    AND     (
                                tbl_faturamento.distribuidor in (4311,376542)
                                OR tbl_faturamento.distribuidor IS NULL
                            )
                    AND     tbl_faturamento.emissao > '2005-10-01'
                    AND     tbl_faturamento.nota_fiscal IS NOT NULL
                    AND tbl_peca.produto_acabado IS TRUE
              GROUP BY      tbl_peca.peca,
                            tbl_peca.referencia,
                            tbl_peca.descricao,
                            tbl_peca.devolucao_obrigatoria,
                            tbl_peca.produto_acabado,
                            tbl_peca.ipi,
                            tbl_peca.ncm,
                            tbl_faturamento_item.aliq_icms,
                            tbl_faturamento_item.aliq_ipi,
                            tbl_faturamento_item.preco,
                            tbl_faturamento_item.devolucao_obrig,
                            tbl_faturamento.cfop,
                            tbl_faturamento.distribuidor,
                            tbl_faturamento.fabrica,
                            tbl_extrato_lgr.qtde,
                            total_item,
                            qtde_total_nf,
                            qtde_pedente_temp,
                            extrato_lgr,
                            tbl_peca.parametros_adicionais
                            $campos
              ORDER BY      tbl_faturamento.distribuidor,
                            tbl_peca.referencia
            ";

            $res_prod = pg_query($con, $sql_prod);

            $resProdutos = pg_fetch_all($res_prod);

            foreach($resProdutos as $valor){
                $contaTamanho++;
                $auxCnpj = $cnpj[$valor['distribuidor']];
                $auxCnpj = substr ($auxCnpj,0,2) . "." . substr ($auxCnpj,2,3) . "." . substr ($auxCnpj,5,3) . "/" . substr ($auxCnpj,8,4) . "-" . substr ($auxCnpj,12,2);

                $auxCep = $cep[$valor['distribuidor']];
                $auxCep = substr ($auxCep ,0,2) . "." . substr ($auxCep ,2,3) . "-" . substr ($auxCep ,5,3) ;

                if($endDistrib != $valor['distribuidor']){

                    if(strlen($endDistrib) > 0){
                        $contaTamanho--;
                        $total_geral = $total_nota + $total_valor_ipi;
                        $notas_fiscais = array();

                        $sql_nf = " SELECT  tbl_faturamento.nota_fiscal
                                    FROM    tbl_faturamento_item
                                    JOIN    tbl_faturamento      USING (faturamento)
                                    JOIN    tbl_peca             USING(peca)
                                    WHERE   (
                                                tbl_faturamento.fabrica = $login_fabrica
                                            OR  tbl_faturamento.fabrica = 10
                                            )
                                    AND     tbl_peca.fabrica                        = $login_fabrica
                                    AND     tbl_faturamento.posto                   = $login_posto
                                    AND     tbl_faturamento_item.extrato_devolucao  = $extrato
                                    AND     tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
                              ORDER BY      tbl_faturamento.nota_fiscal
                        ";
                        // echo nl2br($sql_nf);
                        $resNF = pg_query ($con,$sql_nf);
                        for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
                            array_push($notas_fiscais,pg_fetch_result ($resNF,$y,nota_fiscal));
                        }
                        $notas_fiscais = array_unique($notas_fiscais);

                        if($login_fabrica == 153){
                            foreach ($array_notas_troca_produto as $key => $value) {
                                array_push($notas_fiscais,$value);
                            }
                        }
    ?>
                    </tbody>
                </table>

                <table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px;border-color:#000;' width='75%' >
                    <tr>
                        <!-- 
                        <input type='hidden' name='id_nota_<?=$numero_nota?>-qtde_itens'    value='<?=$contaTamanho?>'>
                        <input type='hidden' name='id_nota_<?=$numero_nota?>-total_nota'    value='<?=$total_geral?>'>
                        <input type='hidden' name='id_nota_<?=$numero_nota?>-base_icms'     value='<?=$total_base_icms?>'>
                        <input type='hidden' name='id_nota_<?=$numero_nota?>-valor_icms'    value='<?=$total_valor_icms?>'>
                        <input type='hidden' name='id_nota_<?=$numero_nota?>-base_ipi'      value='<?=$total_base_ipi?>'>
                        <input type='hidden' name='id_nota_<?=$numero_nota?>-valor_ipi'     value='<?=$total_valor_ipi?>'>
                        <input type='hidden' name='id_nota_<?=$numero_nota?>-cfop'          value='<?=$valor['cfop']?>'>
                        <input type='hidden' name='id_nota_<?=$numero_nota?>-distribuidor'  value='<?=$valor['distribuidor']?>'> 
                        -->
                        <td>Base ICMS <br> <b><?=number_format ($total_base_icms,2,",",".")?></b> </td>
                        <td>Valor ICMS <br> <b><?=number_format ($total_valor_icms,2,",",".")?></b> </td>
                        <td>Base IPI <br> <b><?=number_format ($total_base_ipi,2,",",".")?></b> </td>
                        <td>Valor IPI <br> <b><?=number_format ($total_valor_ipi,2,",",".")?></b> </td>
                        <td>Total da Nota <br> <b><?=number_format ($total_geral,2,",",".")?></b> </td>
                    </tr>
                    <tr>
                        <td colspan="5">Referente às notas:<?=implode(",",$notas_fiscais)?></td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado
                            <br>
                            <img src='imagens/setona_h.gif' width='53' height='29' border='0' align='absmiddle'>Número da Nota:
                            <input type='text' name='nota_fiscal_<?=$numero_nota?>' size='10' maxlength='20' value='<?=$nota_fiscal?>' />
                        </td>
                    </tr>
                </table>
                <br />
    <?
                        $total_base_icms  = 0;
                        $total_valor_icms = 0;
                        $total_base_ipi   = 0;
                        $total_valor_ipi  = 0;
                        $total_nota       = 0;
                        $contaTamanho     = 1;
                        $numero_nota++;
                        $lista_pecas = array();
                    }

                    if($login_fabrica == 153 && $extrato_troca_produto){
                        $devolucao = " PEÇAS -".$devolucao;
                    }
    ?>

                <table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px;border-color:#000' width='75%' >
                    <tr align='left'  height='16'>
                        <td colspan='3' style='background-color:#E3E4E6;font-size:18px;font-weight:bold'>
                            PRODUTOS - RETORNO OBRIGATÓRIO
                        </td>
                    </tr>
                    <tr>
                        <td>Natureza <br> <b>Devolução de Garantia</b> </td>
                        <td>CFOP <br> <b> <?=$valor['cfop']?> </b> </td>
                        <td>Emissão <br> <b><?=$data?></b> </td>
                    </tr>
                </table>
                <?php
                if(($login_fabrica == 153 && !$extrato_troca_produto) || $login_fabrica != 153){
                ?>
                <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >
                    <tr>
                        <td>Razão Social <br> <b><?=$razao[$valor['distribuidor']]?></b> </td>
                        <td>CNPJ <br> <b><?=$auxCnpj?></b> </td>
                        <td>Inscrição Estadual <br> <b><?=$ie[$valor['distribuidor']]?></b> </td>
                    </tr>
                </table>
                <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >
                    <tr>
                        <td>Endereço <br> <b><?=$endereco[$valor['distribuidor']]?> </b> </td>
                        <td>Cidade <br> <b><?=$cidade[$valor['distribuidor']]?></b> </td>
                        <td>Estado <br> <b><?=$estado[$valor['distribuidor']]?></b> </td>
                        <td>CEP <br> <b><?=$auxCep?></b> </td>
                    </tr>
                </table>
                <?php
                }
                ?>
                <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%'>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Código</th>
                            <th>Descrição</th>
                            <?php
                            if(in_array($login_fabrica, array(114,153))){
                                echo "<th>Ref. OS</th>";
                            }
                            ?>
                            <th>Qtde.</th>
                            <th>Preço</th>
                            <th>Total</th>
                            <th>% ICMS</th>
                            <th>% IPI</th>
                        </tr>
                    </thead>
                    <tbody>
    <?
                    $endDistrib = $valor['distribuidor'];
                    $contador   = 1;
                }
                array_push($lista_pecas,$valor['peca']);
                $preco = number_format($valor['preco'],2,',','.');

                $mult = $valor['qtde_real'] * $valor['preco'];
                $total = number_format($mult,2,',','.');

                $aliq_icms = (strlen ($valor['aliq_icms'])== 0) ? 0 : $valor['aliq_icms'];
                $aliq_ipi  = (strlen ($valor['aliq_ipi']) == 0) ? 0 : $valor['aliq_ipi'];

                if ($aliq_icms == 0){
                    $base_icms = 0;
                    $valor_icms = 0;
                } else {
                    $base_icms = $mult;
                    $valor_icms = $mult * $aliq_icms / 100;
                }

                if ($aliq_ipi == 0){
                    $base_ipi = 0;
                    $valor_ipi = 0;
                } else {
                    $base_ipi = $mult;
                    $valor_ipi = $mult * $aliq_ipi / 100;
                }

                $total_base_icms  += $base_icms;
                $total_valor_icms += $valor_icms;
                $total_base_ipi   += $base_ipi;
                $total_valor_ipi  += $valor_ipi;
                $total_nota       += $mult;

                if(in_array($login_fabrica, array(114,153))){ // HD-2238440

                    $pecas = $valor['peca'];
                    $referenciaPeca = $valor['referencia'];
                    $os_item = $valor['os_item'];

                    $sqlOS = "SELECT tbl_faturamento_item.os AS numero_os
                        FROM tbl_faturamento_item
                        JOIN tbl_os ON tbl_os.os = tbl_faturamento_item.os AND tbl_os.fabrica = $login_fabrica
                        JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
                        JOIN tbl_os_item ON tbl_os_item.os_item = tbl_faturamento_item.os_item
                        WHERE tbl_faturamento_item.extrato_devolucao = $extrato_devolucao
                        AND tbl_peca.peca = $pecas
                        AND tbl_faturamento_item.os_item = $os_item
                        AND tbl_faturamento_item.os NOTNULL";
                    $resOS = pg_query($con, $sqlOS);

                    if(pg_num_rows($resOS) > 0 ){
                        $cont = pg_num_rows($resOS);

                        for ($i=0; $i < $cont ; $i++) {
                            $osNumero = pg_fetch_result($resOS, $i, 'numero_os');

                            $sqlPeca = "SELECT tbl_produto.referencia
                                    FROM tbl_produto
                                    JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
                                    WHERE tbl_os.os = $osNumero
                                    AND tbl_os.fabrica = $login_fabrica";
                            $resPeca = pg_query($con, $sqlPeca);

                            if(pg_num_rows($resPeca) > 0){
                                $ref_pecaOS = pg_fetch_result($resPeca, 0, 'referencia');
                            }

                            if(in_array($login_fabrica, array(114))){
                                if($ref_pecaOS <> $referenciaPeca){
                                    $color = "#ff9900";
                                //  $color2 = "#ff9900";
                                }else{
                                    $color = "#ffffff";
                                //  $color2 = "#E2E7E4";
                                }
                            }
                        }
                    }
                }

    ?>
                        <tr bgcolor='<?=$color?>' style='font-color:#000000 ; align:left ; font-size:10px'>
                            <td><?=$contador?></td>
                            <td>
                                <!-- 
                                <input type='hidden' name='id_item_LGR_<?=($contador)."-".$numero_nota?>' value='<?=$valor['extrato_lgr']?>'>
                                <input type='hidden' name='id_item_peca_<?=($contador)."-".$numero_nota?>' value='<?=$valor['peca']?>'>
                                <input type='hidden' name='id_item_preco_<?=($contador)."-".$numero_nota?>' value='<?=$valor['preco']?>'>
                                <input type='hidden' name='id_item_qtde_<?=($contador)."-".$numero_nota?>' value='<?=$valor['qtde_real']?>'>
                                <input type='hidden' name='id_os_item_<?=($contador)."-".$numero_nota?>' value='<?=$valor['os_item']?>'>
                                <input type='hidden' name='id_item_icms_<?=($contador)."-".$numero_nota?>' value='<?=$aliq_icms?>'>
                                <input type='hidden' name='id_item_ipi_<?=($contador)."-".$numero_nota?>' value='<?=$aliq_ipi?>'>
                                <input type='hidden' name='id_item_total_<?=($contador)."-".$numero_nota?>' value='<?=$mult?>'> -->
                                <?=$valor['referencia']?>
                            </td>
                            <td><?=$valor['descricao']?></td>
                            <?php
                            if(in_array($login_fabrica, array(114,153))){ // HD-2238440
                                if(strlen($cont) > 0){
                                    echo "<td align='left'>";
                                    for ($i=0; $i < $cont ; $i++) {
                                        $numero_os = pg_fetch_result($resOS, $i, 'numero_os');
                                        echo $numero_os.'<br />';
                                    }
                                    echo "</td>\n";
                                }else{
                                    echo "<td></td>";
                                }
                            }
                            ?>
                            <td><?=$valor['qtde_real']?></td>
                            <td><?=$preco?></td>
                            <td><?=$total?></td>
                            <td><?=$aliq_icms?></td>
                            <td><?=$aliq_ipi?></td>
                        </tr>
            <?php
                $contador++;

                echo "</tbody> </table> <br /> <br /> ";

            }

        }

        /* Fim- Produto Acabado */

        $condicao = "";

        if($login_fabrica == 153){
            $condicao = " AND tbl_peca.produto_acabado IS FALSE";
        }

        $campo_delonghi = $groupby_delonghi = '';
        if ($login_fabrica == 101) {
            $campo_delonghi   = ',tbl_faturamento_item.os';
            $groupby_delonghi = $campo_delonghi;
            $join_delonghi    = ' JOIN tbl_os ON tbl_os.os = tbl_faturamento_item.os JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os ';
            $condicao         = " AND tbl_os_extra.extrato = {$extrato} AND tbl_os.prateleira_box = 'troca_lgr'";
        }

        $sql = "SELECT  tbl_peca.peca                                               ,
                        tbl_peca.referencia                                         ,
                        tbl_peca.descricao                                          ,
                        tbl_peca.ipi                                                ,
                        tbl_peca.ncm                                                ,
                        CASE WHEN tbl_peca.produto_acabado IS TRUE
                             THEN 'TRUE'
                             ELSE 'NOT TRUE'
                        END                                     AS produto_acabado  ,
                        tbl_peca.devolucao_obrigatoria                              ,
                        tbl_faturamento_item.aliq_icms                              ,
                        tbl_faturamento_item.aliq_ipi                               ,
                        tbl_faturamento_item.preco                                  ,
                        sum(tbl_faturamento_item.qtde)          AS qtde_real        ,
                        tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL
                                                    THEN 0
                                                    ELSE tbl_extrato_lgr.qtde_nf
                                                END             AS qtde_total_item  ,
                        tbl_extrato_lgr.qtde_nf                 AS qtde_total_nf    ,
                        tbl_extrato_lgr.qtde_pedente_temp       AS qtde_pedente_temp,
                        tbl_extrato_lgr.extrato_lgr             AS extrato_lgr      ,
                        (
                            tbl_extrato_lgr.qtde_pedente_temp * tbl_faturamento_item.preco
                        )                                       AS total_item       ,
                        tbl_faturamento.cfop                                        ,
                        tbl_faturamento.distribuidor                                ,
                        SUM (tbl_faturamento_item.base_icms)    AS base_icms        ,
                        SUM (tbl_faturamento_item.valor_icms)   AS valor_icms       ,
                        SUM (tbl_faturamento_item.base_ipi)     AS base_ipi         ,
						SUM (tbl_faturamento_item.valor_ipi)    AS valor_ipi
						$campos
                        $campo_delonghi
                FROM    tbl_faturamento_item
                $join_delonghi
                JOIN    tbl_peca        ON  tbl_peca.peca               = tbl_faturamento_item.peca
                JOIN    tbl_faturamento ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                JOIN    tbl_extrato_lgr ON  tbl_extrato_lgr.extrato     = tbl_faturamento_item.extrato_devolucao
                                        AND tbl_extrato_lgr.peca        = tbl_faturamento_item.peca
                                        AND (
                                                tbl_faturamento_item.faturamento = tbl_extrato_lgr.faturamento
                                            OR  tbl_extrato_lgr.faturamento IS NULL
                                            )
                WHERE   (
                            tbl_faturamento.fabrica = $login_fabrica
                        OR  tbl_faturamento.fabrica = 10
                        )
                AND     tbl_peca.fabrica                        = $login_fabrica
                AND     tbl_faturamento_item.extrato_devolucao  = $extrato
                AND     tbl_faturamento.posto                   = $login_posto
                AND     (tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END) > 0
                AND     (
                            tbl_faturamento.cfop ILIKE '59%'
                        OR  tbl_faturamento.cfop ILIKE '69%'
                        OR  tbl_faturamento_item.cfop ILIKE '59%'
                        OR  tbl_faturamento_item.cfop ILIKE '69%'
                        )
                AND     (
                            tbl_faturamento.distribuidor in (4311,376542)
                            OR tbl_faturamento.distribuidor IS NULL
                        )
                AND     tbl_faturamento.emissao > '2005-10-01'
                AND     tbl_faturamento.nota_fiscal IS NOT NULL
                $condicao
          GROUP BY      tbl_peca.peca,
                        tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_peca.devolucao_obrigatoria,
                        tbl_peca.produto_acabado,
                        tbl_peca.ipi,
                        tbl_peca.ncm,
                        tbl_faturamento_item.aliq_icms,
                        tbl_faturamento_item.aliq_ipi,
                        tbl_faturamento_item.preco,
                        tbl_faturamento_item.devolucao_obrig,
                        tbl_faturamento.cfop,
                        tbl_faturamento.distribuidor,
                        tbl_faturamento.fabrica,
                        tbl_extrato_lgr.qtde,
                        total_item,
                        qtde_total_nf,
                        qtde_pedente_temp,
                        extrato_lgr,
                        tbl_peca.parametros_adicionais
						$campos
                        $groupby_delonghi
          ORDER BY      tbl_faturamento.distribuidor,
                        tbl_peca.referencia
        ";
        // echo (nl2br($sql));exit;
        $res = pg_query($con,$sql);
        $pecasResultados = pg_fetch_all($res);

        $endDistrib = "";
        $tamanho_total = pg_num_rows($pecasResultados);
        $contaTamanho = 0;
        $lista_pecas = array();

        foreach($pecasResultados as $valor){
            $contaTamanho++;
            $auxCnpj = $cnpj[$valor['distribuidor']];
            $auxCnpj = substr ($auxCnpj,0,2) . "." . substr ($auxCnpj,2,3) . "." . substr ($auxCnpj,5,3) . "/" . substr ($auxCnpj,8,4) . "-" . substr ($auxCnpj,12,2);

            $auxCep = $cep[$valor['distribuidor']];
            $auxCep = substr ($auxCep ,0,2) . "." . substr ($auxCep ,2,3) . "-" . substr ($auxCep ,5,3) ;

            if($endDistrib != $valor['distribuidor'] or $login_fabrica == 160 or $replica_einhell){

                if(strlen($endDistrib) > 0){
                    $contaTamanho--;
                    $total_geral = $total_nota + $total_valor_ipi;
                    $notas_fiscais = array();

                    $sql_nf = " SELECT  tbl_faturamento.nota_fiscal
                                FROM    tbl_faturamento_item
                                JOIN    tbl_faturamento      USING (faturamento)
                                JOIN    tbl_peca             USING(peca)
                                WHERE   (
                                            tbl_faturamento.fabrica = $login_fabrica
                                        OR  tbl_faturamento.fabrica = 10
                                        )
                                AND     tbl_peca.fabrica                        = $login_fabrica
                                AND     tbl_faturamento.posto                   = $login_posto
                                AND     tbl_faturamento_item.extrato_devolucao  = $extrato
                                AND     tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
                          ORDER BY      tbl_faturamento.nota_fiscal
                    ";
                    // echo nl2br($sql_nf);
                    $resNF = pg_query ($con,$sql_nf);
                    for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
                        array_push($notas_fiscais,pg_fetch_result ($resNF,$y,nota_fiscal));
                    }
                    $notas_fiscais = array_unique($notas_fiscais);

                    if($login_fabrica == 153){
                        foreach ($array_notas_troca_produto as $key => $value) {
                            array_push($notas_fiscais,$value);
                        }
                    }
?>
                </tbody>
            </table>

            <table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px;border-color:#000;' width='75%' >
                <tr>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-qtde_itens'    value='<?=$contaTamanho?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-total_nota'    value='<?=$total_geral?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-base_icms'     value='<?=$total_base_icms?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-valor_icms'    value='<?=$total_valor_icms?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-base_ipi'      value='<?=$total_base_ipi?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-valor_ipi'     value='<?=$total_valor_ipi?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-cfop'          value='<?=$valor['cfop']?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-distribuidor'  value='<?=$valor['distribuidor']?>'>
                    <td>Base ICMS <br> <b><?=number_format ($total_base_icms,2,",",".")?></b> </td>
                    <td>Valor ICMS <br> <b><?=number_format ($total_valor_icms,2,",",".")?></b> </td>
                    <td>Base IPI <br> <b><?=number_format ($total_base_ipi,2,",",".")?></b> </td>
                    <td>Valor IPI <br> <b><?=number_format ($total_valor_ipi,2,",",".")?></b> </td>
                    <td>Total da Nota <br> <b><?=number_format ($total_geral,2,",",".")?></b> </td>
                </tr>
                <tr>
                    <td colspan="5">Referente às notas:<?=implode(",",$notas_fiscais)?></td>
                </tr>
                <tr>
                    <td colspan="5">
                        <b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado
                        <br>
                        <img src='imagens/setona_h.gif' width='53' height='29' border='0' align='absmiddle'>Número da Nota:
                        <input type='text' name='nota_fiscal_<?=$numero_nota?>' size='10' maxlength='20' value='<?=$nota_fiscal?>' />
                    </td>
                </tr>
            </table>
            <br />
<?
                    $total_base_icms  = 0;
                    $total_valor_icms = 0;
                    $total_base_ipi   = 0;
                    $total_valor_ipi  = 0;
                    $total_nota       = 0;
                    $contaTamanho     = 1;
                    $numero_nota++;
                    $lista_pecas = array();
                }

                if($login_fabrica == 114){ // HD - 2238440
                    echo "<table border='0' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px; margin-bottom:2px;' width='75%' >\n";
                    echo "<tr height='10'>";
                    echo "<td width='14' height='10' bgcolor='#ff9900' class=''>&nbsp;</td>";
                    echo "<td align='left' class=''> <strong> Produto divergente da NFe </strong><br />Favor devolver produto que esta cadastrado na OS (Com defeito), porém emitir a NF baseada no produto faturado.</td>";
                    echo "</tr>";
                    echo "</table>";
                }

                if($login_fabrica == 153 && $extrato_troca_produto){
                    $devolucao = " PEÇAS -".$devolucao;
                }
?>

            <table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px;border-color:#000' width='75%' >
                <tr align='left'  height='16'>
                    <td colspan='3' style='background-color:#E3E4E6;font-size:18px;font-weight:bold'>
                        PEÇAS - RETORNO OBRIGATÓRIO
                    </td>
                </tr>
                <tr>
                    <td>Natureza <br> <b>Devolução de Garantia</b> </td>
                    <td>CFOP <br> <b> <?=$valor['cfop']?> </b> </td>
                    <td>Emissão <br> <b><?=$data?></b> </td>
                </tr>
            </table>
            <?php
            if(($login_fabrica == 153 && !$extrato_troca_produto) || $login_fabrica != 153){
            ?>
            <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >
                <tr>
                    <td>Razão Social <br> <b><?=$razao[$valor['distribuidor']]?></b> </td>
                    <td>CNPJ <br> <b><?=$auxCnpj?></b> </td>
                    <td>Inscrição Estadual <br> <b><?=$ie[$valor['distribuidor']]?></b> </td>
                </tr>
            </table>
            <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >
                <tr>
                    <td>Endereço <br> <b><?=$endereco[$valor['distribuidor']]?> </b> </td>
                    <td>Cidade <br> <b><?=$cidade[$valor['distribuidor']]?></b> </td>
                    <td>Estado <br> <b><?=$estado[$valor['distribuidor']]?></b> </td>
                    <td>CEP <br> <b><?=$auxCep?></b> </td>
                </tr>
            </table>
            <?php
            }
            ?>
            <table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%'>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Código</th>
                        <th>Descrição</th>
                        <?php
                        if(in_array($login_fabrica, array(114,153))){
                            echo "<th>Ref. OS</th>";
                        }
                        ?>
                        <th>Qtde.</th>
                        <th>Preço</th>
                        <th>Total</th>
                        <th>% ICMS</th>
                        <th>% IPI</th>
                    </tr>
                </thead>
                <tbody>
<?
                $endDistrib = $valor['distribuidor'];
                $contador   = 1;
            }
            array_push($lista_pecas,$valor['peca']);
            $preco = number_format($valor['preco'],2,',','.');

            $mult = $valor['qtde_real'] * $valor['preco'];
            $total = number_format($mult,2,',','.');

            $aliq_icms = (strlen ($valor['aliq_icms'])== 0) ? 0 : $valor['aliq_icms'];
            $aliq_ipi  = (strlen ($valor['aliq_ipi']) == 0) ? 0 : $valor['aliq_ipi'];

            if ($aliq_icms == 0){
                $base_icms = 0;
                $valor_icms = 0;
            } else {
                $base_icms = $mult;
                $valor_icms = $mult * $aliq_icms / 100;
            }

            if ($aliq_ipi == 0){
                $base_ipi = 0;
                $valor_ipi = 0;
            } else {
                $base_ipi = $mult;
                $valor_ipi = $mult * $aliq_ipi / 100;
            }

            $total_base_icms  += $base_icms;
            $total_valor_icms += $valor_icms;
            $total_base_ipi   += $base_ipi;
            $total_valor_ipi  += $valor_ipi;
            $total_nota       += $mult;

            if(in_array($login_fabrica, array(114,153))){ // HD-2238440

                $pecas = $valor['peca'];
                $referenciaPeca = $valor['referencia'];
                $os_item = $valor['os_item'];

                $sqlOS = "SELECT tbl_faturamento_item.os AS numero_os
                    FROM tbl_faturamento_item
                    JOIN tbl_os ON tbl_os.os = tbl_faturamento_item.os AND tbl_os.fabrica = $login_fabrica
                    JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
                    JOIN tbl_os_item ON tbl_os_item.os_item = tbl_faturamento_item.os_item
                    WHERE tbl_faturamento_item.extrato_devolucao = $extrato_devolucao
                    AND tbl_peca.peca = $pecas
                    AND tbl_faturamento_item.os_item = $os_item
                    AND tbl_faturamento_item.os NOTNULL";
                $resOS = pg_query($con, $sqlOS);

                if(pg_num_rows($resOS) > 0 ){
                    $cont = pg_num_rows($resOS);

                    for ($i=0; $i < $cont ; $i++) {
                        $osNumero = pg_fetch_result($resOS, $i, 'numero_os');

                        $sqlPeca = "SELECT tbl_produto.referencia
                                FROM tbl_produto
                                JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
                                WHERE tbl_os.os = $osNumero
                                AND tbl_os.fabrica = $login_fabrica";
                        $resPeca = pg_query($con, $sqlPeca);

                        if(pg_num_rows($resPeca) > 0){
                            $ref_pecaOS = pg_fetch_result($resPeca, 0, 'referencia');
                        }

                        if(in_array($login_fabrica, array(114))){
                            if($ref_pecaOS <> $referenciaPeca){
                                $color = "#ff9900";
                            //  $color2 = "#ff9900";
                            }else{
                                $color = "#ffffff";
                            //  $color2 = "#E2E7E4";
                            }
                        }
                    }
                }
            }

            if (in_array($login_fabrica, array(101))) {
                $contador = ($contador == 0) ? 1 : $contador;
                echo "<table style='display: none'>";
            }
?>
                    <tr bgcolor='<?=$color?>' style='font-color:#000000 ; align:left ; font-size:10px;'>
                        <td><?=$contador?></td>
                        <td>
                            <input type='hidden' name='id_item_LGR_<?=($contador)."-".$numero_nota?>' value='<?=$valor['extrato_lgr']?>'>
                            <input type='hidden' name='id_item_peca_<?=($contador)."-".$numero_nota?>' value='<?=$valor['peca']?>'>
                            <input type='hidden' name='id_item_preco_<?=($contador)."-".$numero_nota?>' value='<?=$valor['preco']?>'>
                            <input type='hidden' name='id_item_qtde_<?=($contador)."-".$numero_nota?>' value='<?=$valor['qtde_real']?>'>
			                <input type='hidden' name='id_os_item_<?=($contador)."-".$numero_nota?>' value='<?=$valor['os_item']?>'>
                            <input type='hidden' name='id_item_icms_<?=($contador)."-".$numero_nota?>' value='<?=$aliq_icms?>'>
                            <input type='hidden' name='id_item_ipi_<?=($contador)."-".$numero_nota?>' value='<?=$aliq_ipi?>'>
                            <input type='hidden' name='id_item_total_<?=($contador)."-".$numero_nota?>' value='<?=$mult?>'>
                            <?=$valor['referencia']?>
                        </td>
                    <?php if (!in_array($login_fabrica, array(101))) { ?>
                        <td><?=$valor['descricao']?></td>
                        <?php
                        if(in_array($login_fabrica, array(114,153))){ // HD-2238440
                            if(strlen($cont) > 0){
                                echo "<td align='left'>";
                                for ($i=0; $i < $cont ; $i++) {
                                    $numero_os = pg_fetch_result($resOS, $i, 'numero_os');
                                    echo $numero_os.'<br />';
                                }
                                echo "</td>\n";
                            }else{
                                echo "<td></td>";
                            }
                        }
                        ?>
                        <td><?=$valor['qtde_real']?></td>
                        <td><?=$preco?></td>
                        <td><?=$total?></td>
                        <td><?=$aliq_icms?></td>
                        <td><?=$aliq_ipi?></td>
                    <?php }else{ echo "</table>"; } ?>
                    </tr>
<?
            $contador++;

        }

        $total_geral = $total_nota + $total_valor_ipi;
        $notas_fiscais = array();

        $sql_nf = " SELECT  tbl_faturamento.nota_fiscal
                    FROM    tbl_faturamento_item
                    JOIN    tbl_faturamento      USING (faturamento)
                    JOIN    tbl_peca             USING(peca)
                    WHERE   (
                                tbl_faturamento.fabrica = $login_fabrica
                            OR  tbl_faturamento.fabrica = 10
                            )
                    AND     tbl_peca.fabrica                        = $login_fabrica 
                    AND     tbl_faturamento.posto                   = $login_posto
                    AND     tbl_faturamento_item.extrato_devolucao  = $extrato
                    AND     tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
              ORDER BY      tbl_faturamento.nota_fiscal
        ";
        // exit(nl2br($sql_nf));
        $resNF = pg_query ($con,$sql_nf);
        for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
            array_push($notas_fiscais,pg_fetch_result ($resNF,$y,nota_fiscal));
        }
        $notas_fiscais = array_unique($notas_fiscais);

        if($login_fabrica == 153){
            foreach ($array_notas_troca_produto as $key => $value) {
                array_push($notas_fiscais,$value);
            }
        }
?>
                </tbody>
            </table>
            <table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px;border-color:#000;' width='75%' >
                <tr>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-qtde_itens' value='<?=$contaTamanho?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-total_nota' value='<?=$total_geral?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-base_icms'  value='<?=$total_base_icms?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-valor_icms' value='<?=$total_valor_icms?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-base_ipi'   value='<?=$total_base_ipi?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-valor_ipi'  value='<?=$total_valor_ipi?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-cfop'       value='<?=$valor['cfop']?>'>
                    <input type='hidden' name='id_nota_<?=$numero_nota?>-distribuidor'  value='<?=$valor['distribuidor']?>'>
                    <td>Base ICMS <br> <b><?=number_format ($total_base_icms,2,",",".")?></b> </td>
                    <td>Valor ICMS <br> <b><?=number_format ($total_valor_icms,2,",",".")?></b> </td>
                    <td>Base IPI <br> <b><?=number_format ($total_base_ipi,2,",",".")?></b> </td>
                    <td>Valor IPI <br> <b><?=number_format ($total_valor_ipi,2,",",".")?></b> </td>
                    <td>Total da Nota <br> <b><?=number_format ($total_geral,2,",",".")?></b> </td>
                </tr>
                <tr>
                    <td colspan="5">Referente às notas:<?=implode(",",$notas_fiscais)?></td>
                </tr>

                <?php

                if($login_fabrica == 153){

                ?>

                </table>

                <br /> <br />

                <table border="1" cellspacing="0" cellpadding="3" border-color="#000" style="border-collapse:collapse;font-size:12px" width="75%">

                <?php

                }

                ?>

                <tr>
                    <td colspan="5">
                        <b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado
                        <br>
                        <img src='imagens/setona_h.gif' width='53' height='29' border='0' align='absmiddle'>Número da Nota:
                        <input type='text' name='nota_fiscal_<?=$numero_nota?>' size='10' maxlength='20' value='<?=$nota_fiscal?>' />
                    </td>
                </tr>
            </table>
    
            <input type='hidden' name='qtde_linha' value='<?=$numero_linhas?>'>
            <input type='hidden' name='numero_de_notas' value='<?=$numero_nota+1?>'>
            <input type='hidden' name='numero_de_notas_tc' value='<?=$numero_nota_tc?>'>

            <b>Preencha TODAS as notas acima e clique no botão abaixo para confirmar!</b>
            <br><br>
            <input type='button' value='Confirmar notas de devolução' name='gravar' onclick="javascript:
                if (document.frm_devolucao.botao_acao.value=='digitou_as_notas') {
                    alert('Aguarde Submissão');
                }else{
                    if(confirm('Deseja continuar? As notas de devolução não poderão ser alteradas!')){
                        if (verificar('frm_devolucao')){
                            document.frm_devolucao.botao_acao.value='digitou_as_notas';
                            document.frm_devolucao.submit();
                        }
                    }
                }
            ">
            <br>
        </form>

<?
    } else {
        $qtde_for = (in_array($login_fabrica,array(6,90,120,201,161)) or $telecontrol_distrib) ? 3 : 2;

        echo "<form method='post' action='$PHP_SELF' name='frm_devolucao' id='frm_devolucao'>";
        echo "<input type='hidden' name='notas_d' value=''>";
        echo "<input type='hidden' name='extrato' value='$extrato'>";
        echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>\n";
        if($login_fabrica == 161 ) {
            echo "<input type='hidden' id='auxiliar' name='auxiliar' value=''>\n";
        }

        $contador = 0;

        $join = "JOIN tbl_peca on tbl_faturamento_item.peca = tbl_peca.peca";

        for ($xx = 1; $xx < $qtde_for; $xx++) {
            $extrato_devolucao = $extrato;
            switch ($xx) {
                case 1:
                    if($login_fabrica == 90){
                        $devolucao = " RETORNO DE PEÇAS CRÍTICAS ";
                    }else{
                        if($login_fabrica == 161) {
                            $devolucao = " RETORNO OBRIGATÓRIO PEÇAS ";
                            $tem_peca = "sim";
                            $tem_produto = "nao";
                        }else {
                            $devolucao = " RETORNO OBRIGATÓRIO ";
                        }
                    }
                    $movimento = "RETORNAVEL";

                    if(in_array($login_fabrica,array(91,114,120,201,129,131,138 , 134,139,140,141,144,157,160)) or $login_fabrica > 161){
                        $condicao_2 = " AND (tbl_peca.produto_acabado IS TRUE  OR tbl_faturamento_item.devolucao_obrig = 't')";
                    }else{
                        if($login_fabrica == 6){
                            $devolucaoEstoqueFabrica = "\"devolucao_estoque_fabrica\":\"t\"";
                            $condicao_2 = " AND (tbl_peca.produto_acabado IS TRUE  OR (tbl_peca.devolucao_obrigatoria = 't' AND (tbl_peca.parametros_adicionais is null or parametros_adicionais !~* '$devolucaoEstoqueFabrica')))";
                        }elseif($telecontrol_distrib or in_array($login_fabrica, [35,161])){
                            $condicao_2 = " AND (tbl_peca.produto_acabado IS NOT TRUE  AND tbl_faturamento_item.devolucao_obrig = 't')";
                        }else{
                            $condicao_2 = " AND (tbl_peca.produto_acabado IS TRUE  OR tbl_peca.devolucao_obrigatoria = 't')";
                        }
                    }
                    break;
                case 2:
                    if($login_fabrica == 90){
                        $devolucao = " RETORNO DE PEÇAS NÃO CRÍTICAS ";
                    }elseif($telecontrol_distrib or $login_fabrica == 161) {
                            $devolucao = " RETORNO OBRIGATÓRIO - PRODUTOS ";
                    }else{
                        $devolucao = " NÃO RETORNÁVEIS ";
                    }
                    $movimento = "NAO_RETOR.";
                    if(in_array($login_fabrica,array(35,91,114,120,201,129,131,134,140,141,144))){
                        $condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE  AND tbl_faturamento_item.devolucao_obrig <> 't' ";
                    }elseif($telecontrol_distrib OR $login_fabrica == 161){
                        $condicao_2 = " AND tbl_peca.produto_acabado  ";
						if($telecontrol_distrib) {
                        $join = " join tbl_os ON tbl_faturamento_item.os = tbl_os.os
                                    join tbl_produto ON tbl_produto.produto = tbl_os.produto
                                    join tbl_peca ON  tbl_peca.referencia = tbl_produto.referencia and tbl_peca.fabrica = $login_fabrica
";
						}
                    }else{
                            $condicao_2 = " AND tbl_peca.produto_acabado IS NOT TRUE  AND tbl_peca.devolucao_obrigatoria = 'f' ";
                    }
                    if($login_fabrica == 6){
                        $devolucao = " RETORNO ESTOQUE FÁBRICA ";
                        $devolucaoEstoqueFabrica = "\"devolucao_estoque_fabrica\":\"t\"";
                        $condicao_2 = " AND tbl_peca.parametros_adicionais like '%".$devolucaoEstoqueFabrica."%' ";
                        $condicao_2 .= " AND tbl_peca.devolucao_obrigatoria = 'f' ";
                    }
                    break;
            }

            echo "<input type='hidden' name='id_nota_$xx-movimento'  value='$movimento'>\n";

            $sqlFabrica = "SELECT   tbl_posto.nome,
                                    tbl_posto.endereco,
                                    tbl_posto.numero,
                                    tbl_posto.cidade,
                                    tbl_posto.estado,
                                    tbl_posto.cep,
                                    tbl_posto.fone,
                                    tbl_posto.cnpj,
                                    tbl_posto.ie
                            FROM tbl_posto
                            JOIN tbl_fabrica ON tbl_fabrica.posto_fabrica = tbl_posto.posto
                            WHERE tbl_fabrica.fabrica = $login_fabrica";
            $resFabrica = pg_query($con, $sqlFabrica);
            #echo $sqlFabrica;exit;
            if(pg_num_rows($resFabrica) > 0){
                $razao    = pg_fetch_result($resFabrica,0,nome);
                $endereco = pg_fetch_result($resFabrica,0,endereco);
                $numero   = pg_fetch_result($resFabrica,0,numero);
                $cidade   = pg_fetch_result($resFabrica,0,cidade);
                $estado   = pg_fetch_result($resFabrica,0,estado);
                $cep      = pg_fetch_result($resFabrica,0,cep);
                $fone     = pg_fetch_result($resFabrica,0,fone);
                $cnpj     = pg_fetch_result($resFabrica,0,cnpj);
                $ie       = pg_fetch_result($resFabrica,0,ie);

                $endereco = $endereco.' - '.$numero;
            }else{
                $sqlFabrica = "SELECT * from tbl_fabrica where fabrica = $login_fabrica";
                $resFabrica = pg_query($con,$sqlFabrica);

                //HD43448
                $razao    = pg_fetch_result($resFabrica,0,razao_social);
                $endereco = pg_fetch_result($resFabrica,0,endereco);
                $cidade   = pg_fetch_result($resFabrica,0,cidade);
                $estado   = pg_fetch_result($resFabrica,0,estado);
                $cep      = pg_fetch_result($resFabrica,0,cep);
                $fone     = pg_fetch_result($resFabrica,0,fone);
                $cnpj     = pg_fetch_result($resFabrica,0,cnpj);
                $ie       = pg_fetch_result($resFabrica,0,ie);
            }
            $distribuidor = "null";
            $condicao_1 = " AND (tbl_faturamento.distribuidor IS NULL or tbl_faturamento.distribuidor = 4311 )";

            $cabecalho  = "<br><br>\n";
            $cabecalho .= "<table class='$alinha_form' border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";

            $cabecalho .= "<tr align='left'  height='16'>\n";
            $cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
            $cabecalho .= "<b>&nbsp;<b>$devolucao </b><br>\n";
            $cabecalho .= "</td>\n";
            $cabecalho .= "</tr>\n";

            #$c = "AAAA ICFOP23432423FCFOP EEEEE";
            # modify email addess and link with this:
            #$l="CORRETO";
            #$c=ereg_replace("ICFOP([?])*FCFOP",$l,$c);

            if($login_fabrica == 186){
                $cabecalho .= "</table>\n";
            }else{
                $cabecalho .= "<tr>\n";
                if ($login_fabrica == 90) {
                $cabecalho .= "<td>Natureza <br> <b>Remessa para reposição em garantia</b> </td>\n";
                } else if ($login_fabrica == 50) {
                $cabecalho .= "<td>Natureza <br> <b>Retorno de remessa para troca</b> </td>\n";
                } else {
                $cabecalho .= "<td>Natureza <br> <b>Devolução de Garantia</b> </td>\n";
                }
                $cabecalho .= "<td>CFOP <br> <b> (CFOP) </b> </td>\n";
                $cabecalho .= "<td>Emissão <br> <b>$data</b> </td>\n";
                $cabecalho .= "</tr>\n";
                $cabecalho .= "</table>\n";

                $cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
                $cabecalho .= "<table class='$alinha_form' border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
                $cabecalho .= "<tr>\n";
                $cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
                $cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
                $cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
                $cabecalho .= "</tr>\n";
                $cabecalho .= "</table>\n";

                $cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
                $cabecalho .= "<table class='$alinha_form' border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
                $cabecalho .= "<tr>\n";
                $cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
                $cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
                $cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
                $cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
                $cabecalho .= "</tr>\n";
                $cabecalho .= "</table>\n";
            }

            $topo ="";
            $topo .= "<table class='$alinha_form' border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' id='tbl_pecas_$i'>\n";
            $topo .=  "<thead>\n";

            if($login_fabrica == 161){
                if($numero_linhas_peca == 5000 || $numero_linhas_produto == 5000){
                    $aux_num_linhas = "sim";
                }else {
                    $aux_num_linhas = "nao";
                }
            }else{
                if($numero_linhas == 5000){
                    $aux_num_linhas = "sim";
                }else{
                    $aux_num_linhas = "nao";
                }
            }

            if ($aux_num_linhas == "sim"){
                $colspan = 5;
                if($login_fabrica == 35){
                    $colspan = 5;
                }
                if ($login_fabrica == 50) {
                    $colspan = 8;
                }
                if (in_array($login_fabrica,array(165,186))) {
                    $colspan = 6;
                }
                if($login_fabrica == 120 or $login_fabrica == 201){
                    $colspan = 15;
                }

                if(in_array($login_fabrica, array(101,158,161))){
                    $colspan = 5;
                }

                $topo .=  "<tr align='left'>\n";
                $topo .=  "<td bgcolor='#E3E4E6' align='center' colspan='$colspan' style='font-size:18px'>\n";
                $topo .=  "<b>&nbsp;<b>$devolucao </b><br>\n";
                $topo .=  "</td>\n";
                $topo .=  "</tr>\n";
            }

            $topo .=  "<tr align='center'>\n";
            $topo .=  "<td><b>Item</b></td>\n";
            $topo .=  "<td><b>Código</b></td>\n";
            $topo .=  "<td><b>Descrição</b></td>\n";
            if (in_array($login_fabrica,array(165,186))) {
                $topo .= "<td><b>OS</b></td>\n";
            }
            if($fabricas_usam_NCM){
                $topo .=  "<td><b>NCM</b></td>\n";
            }
            $topo .=  "<td><b>Qtde.</b></td>\n";

            if ($aux_num_linhas == "sim" && $login_fabrica != 50){
				if($login_fabrica <> 94) {
					$topo .=  "<td><b>Qtde. Devolução</b></td>\n";
				}
                if (in_array($login_fabrica, array(120,201))) {
                    $topo .=  "<td><b>Preço</b></td>\n";
                    $topo .=  "<td><b>Total</b></td>\n";
                    $topo .=  "<td><b>% ICMS</b></td>\n";
                    $topo .=  "<td><b>% IPI</b></td>\n";
                    $topo .=  "<td><b>Valor IPI</b></td>\n";
                    $topo .=  "<td><b>Valor ICMS</b></td>\n";
                    $topo .=  "<td><b>Valor Base Sub. ICMS</b></td>\n";
                    $topo .=  "<td><b>Valor base IPI</b></td>\n";
                    $topo .=  "<td><b>Valor base ICMS</b></td>\n";
                    $topo .=  "<td><b>Valor Sub ICMS</b></td>\n";
                }
            }
            else{
                $topo .=  "<td><b>Preço</b></td>\n";
                $topo .=  "<td><b>Total</b></td>\n";
                $topo .=  "<td><b>% ICMS</b></td>\n";
                $topo .=  "<td><b>% IPI</b></td>\n";
                if ($login_fabrica == 175) {
                    $topo .=  "<td><b>ST</b></td>\n";
                }

                if (in_array($login_fabrica, array(120,201))) {
                    $topo .=  "<td><b>Valor IPI</b></td>\n";
                    $topo .=  "<td><b>Valor ICMS</b></td>\n";
                    $topo .=  "<td><b>Valor Base Sub. ICMS</b></td>\n";
                    $topo .=  "<td><b>Valor base IPI</b></td>\n";
                    $topo .=  "<td><b>Valor base ICMS</b></td>\n";
                    $topo .=  "<td><b>Valor Sub ICMS</b></td>\n";
                }
            }


            $topo .=  "</tr>\n";
            $topo .=  "</thead>\n";

            if($login_fabrica == 81 or $login_fabrica == 51  or $login_fabrica == 153) {
                $sql_adicional_peca = ($numero_linhas!=5000) ? " AND tbl_extrato_lgr.qtde_pedente_temp>0 " : "";
            }

            if ( !in_array($login_fabrica, array(35,120,201)) ) {
                $condOsItem = " tbl_faturamento_item.os_item,";
            }

            if (in_array($login_fabrica,array(161,165,186))){
                $condOs = " tbl_faturamento_item.os,";
            }

            if($login_fabrica == 120 or $login_fabrica == 201){
                $campo_os_newmaq = " tbl_faturamento_item.valor_subs_trib, tbl_faturamento_item.base_subs_trib, tbl_faturamento_item.valor_icms_st, tbl_faturamento_item.base_st, ";
            }

            if ($login_fabrica == 175) {
                $campo_os_newmaq = "tbl_faturamento_item.valor_subs_trib, tbl_faturamento_item.base_subs_trib,";   
            }

            $sql = "SELECT
                    $campo_os_newmaq
                    tbl_peca.peca,
                    tbl_peca.referencia,
                    tbl_peca.descricao,
                    tbl_peca.ipi,
                    tbl_peca.ncm,
                    CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
                    CASE WHEN (tbl_faturamento.fabrica = 91 or tbl_faturamento.fabrica = 161) THEN
                        tbl_faturamento_item.devolucao_obrig
                    ELSE
                        tbl_peca.devolucao_obrigatoria
                    END AS devolucao_obrigatoria,
                    tbl_faturamento_item.aliq_icms,
                    tbl_faturamento_item.aliq_ipi,
                    tbl_faturamento_item.preco,
                    {$condOsItem}
                    {$condOs}
                    sum(tbl_faturamento_item.qtde) as qtde_real,
                    tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END AS qtde_total_item,
                    tbl_extrato_lgr.qtde_nf AS qtde_total_nf,
                    tbl_extrato_lgr.qtde_pedente_temp AS qtde_pedente_temp,
                    tbl_extrato_lgr.extrato_lgr AS extrato_lgr,
                    (tbl_extrato_lgr.qtde_pedente_temp * tbl_faturamento_item.preco) AS total_item,
                    substr(tbl_faturamento.cfop,1,4) as cfop,
                    SUM (tbl_faturamento_item.base_icms) AS base_icms,
                    SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
                    SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
                    SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi ";

            if($login_fabrica == 6){
                $sql .= " , tbl_peca.parametros_adicionais ";

            }

            $sql .= "FROM tbl_faturamento_item
                    $join
                    JOIN tbl_faturamento      USING (faturamento)
                    JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato=tbl_faturamento_item.extrato_devolucao AND tbl_extrato_lgr.peca=tbl_faturamento_item.peca and (tbl_faturamento_item.faturamento = tbl_extrato_lgr.faturamento or tbl_extrato_lgr.faturamento isnull)
                    WHERE ";

                    if ($telecontrol_distrib) {
                            $sql .= " (tbl_faturamento.fabrica = $login_fabrica or tbl_faturamento.fabrica = 10) and tbl_peca.fabrica = $login_fabrica ";
                    } else {
                        $sql .= " tbl_faturamento.fabrica = $login_fabrica ";
                    }

                    $sql .= " AND   tbl_faturamento_item.extrato_devolucao = $extrato
                    AND   tbl_faturamento.posto=$login_posto
                    AND (tbl_extrato_lgr.qtde - CASE WHEN tbl_extrato_lgr.qtde_nf IS NULL THEN 0 ELSE tbl_extrato_lgr.qtde_nf END)>0
                    AND (tbl_faturamento.cfop ilike '59%' or tbl_faturamento.cfop ilike '69%' or tbl_faturamento_item.cfop ilike '59%' or tbl_faturamento_item.cfop ilike '69%')
                    $condicao_1
                    $condicao_2
                    $sql_adicional_peca
                    AND   tbl_faturamento.emissao > '2005-10-01'
                    GROUP BY
                        $campo_os_newmaq
                        tbl_peca.peca,
                        tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_peca.devolucao_obrigatoria,
                        tbl_peca.produto_acabado,
                        tbl_peca.ipi,
                        tbl_peca.ncm,
                        tbl_faturamento_item.aliq_icms,
                        tbl_faturamento_item.aliq_ipi,
                        tbl_faturamento_item.preco,
                        {$condOsItem}
                        {$condOs}
                        tbl_faturamento_item.devolucao_obrig,
                        substr(tbl_faturamento.cfop,1,4),
                        tbl_faturamento.fabrica,
                        tbl_extrato_lgr.qtde,
                        total_item,
                        qtde_total_nf,
                        qtde_pedente_temp,
                        extrato_lgr,
                        tbl_peca.parametros_adicionais
                    ORDER BY tbl_peca.referencia";
            $notas_fiscais=array();
            $qtde_peca=0;
            $resX = pg_query ($con,$sql);

    //      if (pg_num_rows ($resX)==0) continue;

            $total_base_icms  = 0;
            $total_valor_icms = 0;
            $total_base_ipi   = 0;
            $total_valor_ipi  = 0;
            $total_nota       = 0;
            $total_peca       = 0;
            $aliq_final       = 0;
            $peca_ant="";
            $lgr_ant="";
            $x_cabecalho = "";
            $qtde_acumulada=0;
            $lista_pecas = array();

            if(in_array($login_fabrica, array(161))){
                $z = 0;
            }else {
                $z=($z > 0) ? $z : 0;    
            }
            
            $total_qtde = pg_num_rows ($resX);

            $total_valor_st = 0;
            $total_valor_st_base = 0;

			for ($x = 0 ; $x < $total_qtde ; $x++) {

				if ($login_fabrica == 161 and $x> 0) $x_cabecalho = "sim";
                $tem_mais_itens='sim';
                $contador++;
                $item_nota++;
                $z++;

                $peca                = pg_fetch_result ($resX,$x,peca);
                $peca_referencia     = pg_fetch_result ($resX,$x,referencia);
                $peca_descricao      = pg_fetch_result ($resX,$x,descricao);
                $peca_preco          = pg_fetch_result ($resX,$x,preco);
                $ncm                 = pg_fetch_result ($resX,$x,ncm);
                $qtde_real           = pg_fetch_result ($resX,$x,qtde_real);
                $qtde_total_item     = pg_fetch_result ($resX,$x,qtde_total_item);
                $qtde_total_nf       = pg_fetch_result ($resX,$x,qtde_total_nf);
                $qtde_pedente_temp   = pg_fetch_result ($resX,$x,qtde_pedente_temp);
                $qtde_pedente_temp_AUX= pg_fetch_result ($resX,$x,qtde_pedente_temp);
    //          $qtde_restatante     = pg_fetch_result ($resX,$x,qtde_restatante);
                $extrato_lgr         = pg_fetch_result ($resX,$x,extrato_lgr);
                $total_item          = pg_fetch_result ($resX,$x,total_item);
                $base_icms           = pg_fetch_result ($resX,$x,base_icms);
                $valor_icms          = pg_fetch_result ($resX,$x,valor_icms);
                $aliq_icms           = pg_fetch_result ($resX,$x,aliq_icms);
                $base_ipi            = pg_fetch_result ($resX,$x,base_ipi);
                $aliq_ipi            = pg_fetch_result ($resX,$x,aliq_ipi);
                $valor_ipi           = pg_fetch_result ($resX,$x,valor_ipi);
                $ipi                 = pg_fetch_result ($resX,$x,ipi);
                $cfop                = pg_fetch_result ($resX,$x,cfop);
                $peca_produto_acabado= pg_fetch_result ($resX,$x,produto_acabado);
                $os_item	         = pg_fetch_result($resX,$x,os_item);
                $peca_devolucao_obrigatoria= pg_fetch_result ($resX,$x,devolucao_obrigatoria);

                if($login_fabrica == 6){
                    $parametros_adicionais = json_decode(pg_fetch_result($resX, $x, "parametros_adicionais"));
                }

                if($login_fabrica == 120 or $login_fabrica == 201){
                    $base_subs_trib      = pg_result ($resX,$x,base_subs_trib);
                    $valor_subs_trib     = pg_result ($resX,$x,valor_subs_trib);


                    if (empty($base_subs_trib)) {
                        $base_subs_trib = 0;
                    }
                    if (empty($valor_subs_trib)) {
                        $valor_subs_trib = 0;
                    }
                }

                if($login_fabrica == 175){
                    $base_subs_trib = 0;
                    $valor_subs_trib = 0;
                    $base_subs_trib  = (empty(pg_fetch_result ($resX,$x,'base_subs_trib'))) ? 0 : pg_fetch_result ($resX,$x,'base_subs_trib');
                    $valor_subs_trib = (empty(pg_fetch_result ($resX,$x,'valor_subs_trib'))) ? 0 : pg_fetch_result ($resX,$x,'valor_subs_trib'); 
                }

                if (in_array($login_fabrica,array(165,186))) {
                    $os = pg_fetch_result($resX, $x, os);
                }
                    if ($qtde_pedente_temp > $qtde_real && $aux_num_linhas == "nao"){
                        $qtde_pedente_temp = $qtde_real;
                    }



                    if (!empty($os_item) && $login_fabrica == 94) {
                        $sqlNf = "SELECT
                                    SUM(fi.qtde) as qtde
                                  FROM tbl_faturamento_item fi 
                                  JOIN tbl_faturamento using (faturamento) 
                                  JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato_lgr = $extrato_lgr
                                  WHERE fi.extrato_devolucao = $extrato
                                  AND fi.peca = $peca
                                  AND fi.os_item = $os_item
                                  AND tbl_faturamento.posto = $login_posto
                                  AND tbl_faturamento.fabrica = $login_fabrica";
                        $resNf = pg_query($con, $sqlNf);

                        $qtde_nota_fiscal = pg_fetch_result($resNf, 0, 'qtde'); 
                    } else {
                        $qtde_nota_fiscal = "";
                    }


                    if ($peca_ant==$peca and $lgr_ant == $extrato_lgr and ($login_fabrica != 94 || (!empty($qtde_nota_fiscal && $qtde_nota_fiscal == $qtde_total_nf)))) {
                        if ($aux_num_linhas == "sim"){
                            $peca_ant=$peca;
                            $contador--;
                            continue;
                        }
                        if ($peca_ok==1){
                            $peca_ant=$peca;
                            $contador--;
                            $item_nota--;
                            $z--;
                            continue;
                        }
                    }
                    if ($peca_ant!=$peca and $lgr_ant != $extrato_lgr){
                        $qtde_acumulada = $qtde_real;
                        $peca_ok = 0;
                    }else{
                        $qtde_acumulada += $qtde_real;
                        if ($qtde_acumulada >= $qtde_pedente_temp_AUX and $qtde_pedente_temp_AUX > 0){
                            #William, não sei p q está aqui, mas tirei, 18/11/2014
                            #$qtde_real = $qtde_pedente_temp_AUX - ($qtde_acumulada - $qtde_real);
                            #$qtde_real = ($qtde_real < 0) ?  $qtde_real * -1 : $qtde_real;
                            $peca_ok = 1;
                        }
                    }

                    $peca_ant=$peca;
                    $lgr_ant=$extrato_lgr;

                    if (strlen($qtde_pedente_temp)==0){
                        $qtde_pedente_temp=$qtde_total_item;
                    }

                    array_push($lista_pecas,$peca);

                    $total_item  = $peca_preco * $qtde_real;

                    if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

                    if ($aliq_icms==0){
                        $base_icms=0;
                        $valor_icms=0;
                    } else {
                        if($login_fabrica <> 91){
                            $base_icms=$total_item;
                            $valor_icms = $total_item * $aliq_icms / 100;
                        }
                    }

                    if (! in_array($login_fabrica, array(90,91,94,120,201,175))) {
                        if ($peca_produto_acabado == 'NOT TRUE'){ # se for peca, IPI = 0
                            $aliq_ipi = 0;
                        }
                    }

                    if (strlen($aliq_ipi)==0) $aliq_ipi=0;

                    if ($aliq_ipi==0)   {
                        $base_ipi=0;
                        $valor_ipi=0;
                    } else {
                        if($login_fabrica == 91){
                            $total_item = $base_icms;
                        }else{
                            $base_ipi=$total_item;
                            $valor_ipi = $total_item*$aliq_ipi/100;
                        }
                    }

                    if($login_fabrica == 35) { # HD 1831641
                        $base_icms           = 0 ;
                        $valor_icms          = 0 ;
                        $aliq_icms           = 0 ;
                        $base_ipi            = 0 ;
                        $aliq_ipi            = 0 ;
                        $valor_ipi           = 0 ;
                    }

                    $total_base_icms  += $base_icms;
                    $total_valor_icms += $valor_icms;
                    $total_base_ipi   += $base_ipi;
                    $total_valor_ipi  += $valor_ipi;
                    $total_nota       += $total_item;

                    if ($login_fabrica == 120 or $login_fabrica == 201) {
                        $total_peca += $total_item;
                        $base_subs_trib *= $qtde_real;
                        $valor_subs_trib *= $qtde_real;
                        $total_valor_icms_st += $base_subs_trib;
                        $total_base_st       += $valor_subs_trib; 
                    }

                    if ($login_fabrica == 175) {
                        $total_valor_st += ($valor_subs_trib * $qtde_real);
                        $total_valor_st_base += $base_subs_trib;
                    }


                    if($login_fabrica != 90)
                        $cfop = "6949";
                    $sql = "SELECT contato_estado
                        FROM tbl_posto_fabrica
                        WHERE fabrica = $login_fabrica
                        AND posto = $login_posto";
                    $resW = pg_query ($con,$sql);
                    if (pg_num_rows ($resW)>0){
                        $estado_posto = strtoupper(trim(pg_fetch_result($resW,0,contato_estado)));
						if($estado == $estado_posto){
                            $cfop = "5949";
						}
					}

                    if ($x == 0 && (($login_fabrica == 161 && (($devolucao == $post_devolucao_peca || $devolucao == $post_devolucao_produto) || $enviado == "nao")) || ($login_fabrica != 161 && empty($x_cabecalho)))) {
                        if ($aux_num_linhas == "nao"){
                            /* HD 40994 */
                            $x_cabecalho = str_replace("(CFOP)",substr($cfop,0,4),$cabecalho);
                            echo $x_cabecalho;
                        }
                        if($xx == 2){
                            $tem_peca    = "nao";
                            $tem_produto = "sim";
                        }
                        echo $topo;
                    }

                    if(($login_fabrica == 161 && (($devolucao == $post_devolucao_peca || $devolucao == $post_devolucao_produto) || $enviado == "nao")) || ($login_fabrica != 161 )) {

                        echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
                        echo "<td align='center'>";
                        echo $contador;
                        echo "</td>";
                        echo "<td align='left'>";
                        echo "$peca_referencia";
                        echo "<input type='hidden' name='id_item_LGR_$item_nota-$numero_nota' value='$extrato_lgr'>\n";
                        echo "<input type='hidden' name='id_item_peca_$item_nota-$numero_nota' value='$peca'>\n";
                        echo "<input type='hidden' name='id_item_preco_$item_nota-$numero_nota' value='$peca_preco'>\n";
                        echo "<input type='hidden' name='id_item_qtde_$item_nota-$numero_nota' value='$qtde_real'>\n";
                        echo "<input type='hidden' name='id_item_icms_$item_nota-$numero_nota' value='$aliq_icms'>\n";
                        echo "<input type='hidden' name='id_os_item_$item_nota-$numero_nota' value='$os_item'>\n";
                        echo "<input type='hidden' name='id_item_ipi_$item_nota-$numero_nota' value='$aliq_ipi'>\n";
                        echo "<input type='hidden' name='id_item_total_$item_nota-$numero_nota' value='$total_item'>\n";
                        echo "</td>\n";
                        echo "<td align='left'>$peca_descricao</td>\n";

                        if (in_array($login_fabrica,array(165,186))) {
                            echo "<td align='center'>".$os."</td>";
                        }

                        if($fabricas_usam_NCM){
                            echo "<td align='left'>$ncm</td>\n";
                        }

                        /*HD-4135980*/
                        if (in_array($login_fabrica, array(24, 50, 91, 161))) {
                            $readonly = 'readonly';
                        }

                        if ($aux_num_linhas == "sim"){
                            echo "<td align='center'>
                                $qtde_real
                                <input type='hidden' name='item_$contador' value='$extrato_lgr'>\n
                                <input type='hidden' name='peca_tem_$contador' value='$qtde_total_item'>\n
                                <input type='hidden' name='peca_$contador' value='$peca'>\n";
                                if($login_fabrica == 50){
                                    echo "<input style='text-align:right' type='hidden' name='$extrato_lgr' value='$qtde_pedente_temp'>";
                                    echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
                                    echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
                                    echo "<td align='right'>$aliq_icms</td>\n";
                                    echo "<td align='right'>$aliq_ipi</td>\n";
                                }
                                echo "</td>\n";
                                if($login_fabrica <> 50 and $login_fabrica <> 94){
                                    echo "<td align='center' bgcolor='#FAE7A5'>\n
                                <input style='text-align:right' type='text' size='4' maxlength='4' name='$extrato_lgr' value='$qtde_pedente_temp' onblur='javascript:if (this.value > $qtde_total_item || this.value==\"\" ) {alert(\"Quantidade superior!\");this.value=\"$qtde_total_item\"}' $readonly>\n
                                </td>\n";
                                }
                                if (in_array($login_fabrica, array(120,201))) {
                                    echo "<td align='right' nowrap>".number_format ($peca_preco,2,",",".") . "</td>\n";
                                    echo "<td align='right' nowrap>".number_format ($total_item,2,",",".") . "</td>\n";
                                    echo "<td align='right'>$aliq_icms</td>\n";
                                    echo "<td align='right'>$aliq_ipi</td>\n";
                                    echo "<td align='right'>".number_format ($valor_ipi,2,",",".") . "</td>\n";
                                    echo "<td align='right'>".number_format ($valor_icms,2,",",".") . "</td>\n";
                                    echo "<td align='right'>".number_format ($base_subs_trib,2,",",".") . "</td>\n";
                                    echo "<td align='right'>".number_format ($base_ipi,2,",",".") . "</td>\n";
                                    echo "<td align='right'>".number_format ($base_icms,2,",",".") . "</td>\n";
                                    echo "<td align='right'>".number_format ($valor_subs_trib,2,",",".") . "</td>\n";
                                }

                        } else {
                            echo "<td align='center'>$qtde_real</td>\n";
    						echo "<td align='right' nowrap>".number_format ($peca_preco,2,",",".") . "</td>\n";
    						echo "<td align='right' nowrap>".number_format ($total_item,2,",",".") . "</td>\n";
    						echo "<td align='right'>$aliq_icms</td>\n";
    						echo "<td align='right'>$aliq_ipi</td>\n";
                            if ($login_fabrica == 175) {
                                echo "<td align='right'>$valor_subs_trib</td>\n";
                            }

                            if (in_array($login_fabrica, array(120,201))) {
                                echo "<td align='right'>".number_format ($valor_ipi,2,",",".") . "</td>\n";
                                echo "<td align='right'>".number_format ($valor_icms,2,",",".") . "</td>\n";
                                echo "<td align='right'>".number_format ($base_subs_trib,2,",",".") . "</td>\n";
                                echo "<td align='right'>".number_format ($base_ipi,2,",",".") . "</td>\n";
                                echo "<td align='right'>".number_format ($base_icms,2,",",".") . "</td>\n";
                                echo "<td align='right'>".number_format ($valor_subs_trib,2,",",".") . "</td>\n";
                            }
                        }

                        echo "</tr>\n";

                        if ($aux_num_linhas == "nao"){
                            if($login_fabrica == 161) {
                                if($devolucao == " RETORNO OBRIGATÓRIO PEÇAS "){
                                    $aux_linhas = $numero_linhas_peca;
                                }else{
                                    $aux_linhas = $numero_linhas_produto;
                                }
                            }else{
                                $aux_linhas = $numero_linhas;    
                            }
                            if ($z%$aux_linhas == 0 && $z > 0 && ($x + 1 < $total_qtde)) {

                                $total_geral=$total_nota+$total_valor_ipi;
                                echo "</table>\n";
                                echo "<table class='$alinha_form' border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
                                echo "<tr>\n";
                                echo "<td>Base ICMS <br> <b> ".number_format ($total_base_icms,2,",",".") . " </b> </td>\n";
                                echo "<td>Valor ICMS <br> <b> ".number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";

                                if ($login_fabrica == 90){ //HD 724855

                                    echo "<td colspan='2'>";
                                    echo "  <table width='100%' align='center'>
                                                <tr>
                                                    <td align='center' width='60%'>
                                                        <label style='font:12px Arial'>Valor Total dos Produtos</label> <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b>
                                                    </td>
                                                    <td align='center' width='10%'>+</td>
                                                    <td align='center' width='30%'>
                                                        Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b>
                                                    </td>
                                                </tr>
                                            </table>";
                                    echo "</td>";

                                }else{

                                    echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>\n";
                                    echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>\n";

                                    if ($login_fabrica == 175) {
                                        echo "<td>Base ST <br> <b> " . number_format ($total_valor_st_base,2,",",".") . " </b> </td>\n";
                                        echo "<td>Valor ST <br> <b> " . number_format ($total_valor_st,2,",",".") . " </b> </td>\n";                                        
                                        echo "<td>Total da Nota<br> <b> " . number_format ($total_geral+$total_valor_st,2,",",".") . " </b> </td>\n";
                                    }

                                    if ($login_fabrica == 120 or $login_fabrica == 201) {
                                        $total_geral += $total_base_st;

                                        echo "<td>Valor Base Sub ICMS  <br> <b>".number_format ($total_valor_icms_st,2,",",".")."</b></td>";
                                        echo "<td>Valor Sub. ICMS<br> <b>".number_format ($total_base_st,2,",",".")."</b></td>";
                                        $total_base_st       = 0;
                                         echo "<td>Total dos Produtos<br> <b> " . number_format ($total_peca,2,",",".") . " </b> </td>\n";
                                         $total_valor_icms_st = 0;
                                         $total_peca = 0;
                                    }

                                }

                                if ($login_fabrica != 175) {
                                    echo "<td>Total da Nota<br> <b> " . number_format ($total_geral,2,",",".") . " </b> </td>\n";
                                }
                                echo "</tr>\n";

                                if (count ($lista_pecas) >0){
                                    $notas_fiscais = array();

                                    if (in_array($login_fabrica, array(51,81,114,153))) {
                                        $sql_nf = "
                                            SELECT tbl_faturamento.nota_fiscal
                                            FROM tbl_faturamento_item
                                            JOIN tbl_faturamento      USING (faturamento)
                                            JOIN tbl_peca             USING(peca)
                                            WHERE (tbl_faturamento.fabrica = $login_fabrica OR tbl_faturamento.fabrica = 10)
                                            AND tbl_peca.fabrica = $login_fabrica
                                            AND   tbl_faturamento.posto   = $login_posto
                                            AND   tbl_faturamento_item.extrato_devolucao = $extrato
                                            AND tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
                                            ORDER BY tbl_faturamento.nota_fiscal;
                                        ";
                                    } else {
                                        $sql_nf = "SELECT tbl_faturamento.nota_fiscal
                                                FROM tbl_faturamento_item
                                                JOIN tbl_faturamento      USING (faturamento)
                                                WHERE tbl_faturamento.fabrica = $login_fabrica
                                                AND   tbl_faturamento.posto   = $login_posto
                                                AND   tbl_faturamento_item.extrato_devolucao = $extrato
                                                AND tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
                                                ORDER BY tbl_faturamento.nota_fiscal";
                                    }
                                    //echo nl2br($sql_nf);
                                    $resNF = pg_query ($con,$sql_nf);
                                    for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
                                        array_push($notas_fiscais,pg_fetch_result ($resNF,$y,nota_fiscal));
                                    }
                                    $notas_fiscais = array_unique($notas_fiscais);

                                    if($login_fabrica == 153){
                                        foreach ($array_notas_troca_produto as $key => $value) {
                                            array_push($notas_fiscais,$value);
                                        }
                                    }

                                    if (count($notas_fiscais)>0){
                                        echo "<tfoot>";
                                        echo "<tr>";
                                        echo "<td colspan='8'> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</td>";
                                        echo "</tr>";
                                        echo "</tfoot>";
                                    }
                                }
                                $notas_fiscais=array();
                                $lista_pecas = array();
                                $qtde_peca="";
                                echo "</table>\n";
                                if (strlen ($nota_fiscal)==0) {
                                    echo "<table class='$alinha_form' border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                                    echo "<tr>";
                                    echo "<td>";
                                    echo "\n<br>";
                                    echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
                                    echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
                                    echo "<input type='hidden' name='id_nota_$numero_nota-base_icms'  value='$total_base_icms'>\n";
                                    echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
                                    echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi'   value='$total_base_ipi'>\n";
                                    echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi'  value='$total_valor_ipi'>\n";
                                    echo "<input type='hidden' name='id_nota_$numero_nota-cfop'       value='$cfop'>\n";
                                    echo "<center>";
                                    echo "<b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal1</b><br>Este número não poderá ser alterado<br>";
                                    echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='20' value='$nota_fiscal'>";
                                    echo "<br><br>";
                                    echo "</td>";
                                    echo "</tr>";
                                    echo "</table>";
                                    $numero_nota++;
                                }else{
                                    if (strlen ($nota_fiscal) >0){
                                        echo "<table class='$alinha_form' border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
                                        echo "<tr>\n";
                                        echo "<td><h1><center>Nota de Devolução $nota_fiscal</center></h1></td>\n";
                                        echo "</tr>";
                                        echo "</table>";
                                    }
                                }
                                /* HD 40994 */
                                $x_cabecalho = str_replace("(CFOP)",substr($cfop,0,4),$cabecalho);
                                echo $x_cabecalho;
                                echo $topo;

                                $total_peca = 0;
                                $total_base_icms  = 0;
                                $total_valor_icms = 0;
                                $total_base_ipi   = 0;
                                $total_valor_ipi  = 0;
                                $total_nota       = 0;
                                $item_nota=0;
                                $total_valor_st_base = 0;
                                $total_valor_st = 0;
                            }
                        }
                    }
                    flush();
            }
            if (count ($lista_pecas) >0){
                $notas_fiscais = array();
                if ($login_fabrica == 81 or $login_fabrica == 51 or $login_fabrica == 114 or $login_fabrica == 153) {
                    $sql_nf = "SELECT tbl_faturamento.nota_fiscal
                            FROM tbl_faturamento_item
                            JOIN tbl_faturamento      USING (faturamento)
                            JOIN tbl_peca USING(peca)
                            WHERE (tbl_faturamento.fabrica = $login_fabrica or tbl_faturamento.fabrica = 10)
                            AND tbl_peca.fabrica = $login_fabrica
                            AND   tbl_faturamento.posto   = $login_posto
                            AND   tbl_faturamento_item.extrato_devolucao = $extrato
                            AND tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
                            ORDER BY tbl_faturamento.nota_fiscal";
                } else {
                    $sql_nf = "SELECT tbl_faturamento.nota_fiscal
                            FROM tbl_faturamento_item
                            JOIN tbl_faturamento      USING (faturamento)
                            WHERE tbl_faturamento.fabrica = $login_fabrica
                            AND   tbl_faturamento.posto   = $login_posto
                            AND   tbl_faturamento_item.extrato_devolucao = $extrato
                            AND tbl_faturamento_item.peca IN (".implode(",",$lista_pecas).")
                            ORDER BY tbl_faturamento.nota_fiscal";
                }
                //echo nl2br($sql_nf);//exit;
                $resNF = pg_query ($con,$sql_nf);
                for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
                    array_push($notas_fiscais,pg_fetch_result ($resNF,$y,nota_fiscal));
                }
                $notas_fiscais = array_unique($notas_fiscais);
            }

            if ($login_fabrica == 142) {
                $notaFiscalSemDevolucao = array();

                foreach ($notas_fiscais as $nota_fiscal_sd) {
                    $notaFiscalSemDevolucao[] = "'{$nota_fiscal_sd}'";
                }


                $sqlPecaSemDevolucao = "
                    SELECT
                        tbl_peca.referencia,
                        tbl_peca.descricao,
                        tbl_faturamento_item.qtde,
                        tbl_faturamento_item.preco,
                        COALESCE(tbl_faturamento_item.aliq_icms, 0) AS aliq_icms,
                        COALESCE(tbl_faturamento_item.aliq_ipi, 0) AS aliq_ipi,
                        COALESCE(tbl_faturamento_item.base_icms, 0) AS base_icms,
                        COALESCE(tbl_faturamento_item.valor_icms, 0) AS valor_icms,
                        COALESCE(tbl_faturamento_item.base_ipi, 0) AS base_ipi,
                        COALESCE(tbl_faturamento_item.valor_ipi, 0) AS valor_ipi
                    FROM tbl_os_item
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                    INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.os_item = tbl_os_item.os_item
                    INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica} AND tbl_faturamento.posto = {$login_posto}
                    INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                    WHERE tbl_faturamento_item.extrato_devolucao IS NULL
                    AND tbl_faturamento.nota_fiscal IN (".implode(", ", $notaFiscalSemDevolucao).")
                ";
                $resPecaSemDevolucao = pg_query($con, $sqlPecaSemDevolucao);

                if (pg_num_rows($resPecaSemDevolucao) > 0) {
                    while ($pecaSemDevolucao = pg_fetch_object($resPecaSemDevolucao)) {
                        if ($numero_linhas == 5000) {
                            echo "
                                <tr style='background-color: #FFF; color: #000; text-align: left; font-size: 10px;' >
                                    <td style='text-align: center;' >".++$contador."</td>
                                    <td>{$pecaSemDevolucao->referencia}</td>
                                    <td>{$pecaSemDevolucao->descricao}</td>
                                    <td style='text-align: center;' >{$pecaSemDevolucao->qtde}</td>
                                    <td style='color: #FF0000; text-align: center; font-weight: bold;' >devolução não obrigatória</td>
                                </tr>
                            ";
                        } else {
                            $total_base_icms  += $pecaSemDevolucao->base_icms;
                            $total_valor_icms += $pecaSemDevolucao->valor_icms;
                            $total_base_ipi   += $pecaSemDevolucao->base_ipi;
                            $total_nota       += ($pecaSemDevolucao->preco * $pecaSemDevolucao->qtde);

                            if ($pecaSemDevolucao->valor_ipi > 0) {
                                $valor_ipi = ($pecaSemDevolucao->preco * $pecaSemDevolucao->qtde) * ($aliq_ipi / 100);
                                $total_valor_ipi += $valor_ipi;
                            }

                            echo "
                                <tr style='background-color: #FFF; color: #000; text-align: left; font-size: 10px;' >
                                    <td style='text-align: center;' >".++$contador."</td>
                                    <td>{$pecaSemDevolucao->referencia}</td>
                                    <td>{$pecaSemDevolucao->descricao}<br /><span style='color: #FF0000; text-align: center; font-weight: bold;' >devolução não obrigatória</span></td>
                                    <td style='text-align: center;' >{$pecaSemDevolucao->qtde}</td>
                                    <td style='text-align: right;' >".number_format($pecaSemDevolucao->preco, 2, ",", ".")."</td>
                                    <td style='text-align: right;' >".number_format(($pecaSemDevolucao->preco * $pecaSemDevolucao->qtde), 2, ",", ".")."</td>
                                    <td style='text-align: right;' >{$pecaSemDevolucao->aliq_icms}</td>
                                    <td style='text-align: right;' >{$pecaSemDevolucao->aliq_ipi}</td>
                                </tr>
                            ";
                        }
                    }
                }
            }

            if (count ($lista_pecas) >0){

                if($login_fabrica == 153){
                    foreach ($array_notas_troca_produto as $key => $value) {
                        array_push($notas_fiscais,$value);
                    }
                }

                if (($login_fabrica != 161 && count($notas_fiscais)>0) || ($login_fabrica == 161 && (($devolucao == $post_devolucao_peca || $devolucao == $post_devolucao_produto) || $enviado == "nao") && count($notas_fiscais)>0)) {
                    $span_ref = ($login_fabrica == 175) ? 9 : 8;
                    if (in_array($login_fabrica, array(120,201))) {
                        $span_ref = 15;
                    }
                    echo "<tfoot>";
		    if($login_fabrica != 186){
			    echo "<tr>";
			    echo "<td colspan='{$span_ref}'><strong> Referente a suas NFs. " . implode(", ",$notas_fiscais) . "</strong></td>";
			    echo "</tr>";
		    }
                    echo "</tfoot>";
                }
            }

            echo "</table>\n";
            if (in_array($login_fabrica, array(161)) && $aux_num_linhas == "sim" && $z !== 0) {
                if ($devolucao == " RETORNO OBRIGATÓRIO PEÇAS ") {
                    $peca_produto = "aux_peca";
                }else {
                    $peca_produto = "aux_produto";
                }
                $post_devolucao = $devolucao;


            if($tem_peca == "sim" || $tem_produto == "sim") {
                if ($login_fabrica == 161 && isset($_GET['listagem']) && $_GET['listagem'] == 'true') {
                    continue;
                }
            ?>
                <br>
                <input type='hidden' name='enviado' value='sim'>
                <input type='hidden' name='post_devolucao_<?php echo $peca_produto; ?>' value='<?=$post_devolucao;?>'>
                <input type='hidden' name='<?=$peca_produto;?>' value='<?=$peca_produto;?>'>
                <IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>
                <b style='font-size:12px'>
                Informar a quantidade de linhas no formulário de Nota Fiscal do Posto Autorizado:
                <input type='text' size='5' maxlength='3' value='' name='qtde_linha_<?php echo $peca_produto; ?>'>
                <br>
                Essa informação definirá a quantidade de NFs que o posto autorizado deverá emitir e enviar
                <br><br>
            <? }               
            }

            if ($login_fabrica == 142 && pg_num_rows($resPecaSemDevolucao) > 0) {
            ?>
                <div style="width: 75%px; margin: 0 auto; background-color: #FF0000;" >
                    <h4 style="color: #FFFFFF;" >
                        As peças marcadas como DEVOLUÇÃO NÃO OBRIGATÓRIA não precisam ser devolvidas, porém precisam constar na nota fiscal de devolução para fins fiscais.
                    </h4>
                </div>
            <?php
            }
    //      $total_valor_icms = $total_base_icms * $aliq_final / 100;
            if (($aux_num_linhas == "nao" && count ($lista_pecas) >0 && $login_fabrica != 161) || ($aux_num_linhas == "nao" && count ($lista_pecas) >0 && $login_fabrica == 161 && (($devolucao == $post_devolucao_peca || $devolucao == $post_devolucao_produto) || $enviado == "nao"))) {

                if($login_fabrica != 186){
                    echo "<table class='$alinha_form' border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                    echo "<tr>";
                    echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
                    echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
                    echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
                    echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
                    if ($login_fabrica == 175) {
                        echo "<td>Base ST <br> <b> " . number_format ($total_valor_st_base,2,",",".") . " </b> </td>";
                        echo "<td>Valor ST <br> <b> " . number_format ($total_valor_st,2,",",".") . " </b> </td>";
                        echo "<td>Total da Nota<br> <b> " . number_format ($total_nota+$total_valor_ipi+$total_valor_st,2,",",".") . " </b> </td>";
                    }
                    if ($login_fabrica == 120 or $login_fabrica == 201) {
                        $total_nota += $total_base_st;

                        echo "<td>Valor Base Sub ICMS  <br> <b>".number_format ($total_valor_icms_st,2,",",".")."</b></td>";
                        echo "<td>Valor Sub. ICMS<br> <b>".number_format ($total_base_st,2,",",".")."</b></td>";
                        $total_valor_icms_st = 0;
                        $total_base_st       = 0;

                        echo "<td>Total dos Produtos<br> <b> " . number_format ($total_peca,2,",",".") . " </b> </td>\n";
                        $total_peca = 0;
                    }
                    if ($login_fabrica != 175) {
                        echo "<td>Total da Nota<br> <b> " . number_format ($total_nota+$total_valor_ipi,2,",",".") . " </b> </td>";
                    }
                    echo "</tr>";
                    echo "</table>";
                }
            }

            if (($aux_num_linhas == "nao" && strlen ($nota_fiscal) == 0 && count ($lista_pecas) >0 && $login_fabrica != 161) || ($aux_num_linhas == "nao" && strlen ($nota_fiscal) == 0 && count ($lista_pecas) >0 && $login_fabrica == 161 && (($devolucao == $post_devolucao_peca || $devolucao == $post_devolucao_produto) || $enviado == "nao"))) {

                $total_geral=$total_nota+$total_valor_ipi;

                echo "<table $display_none border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                echo "<tr>";
                echo "<td>";
                echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
                echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
                echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
                echo "<input type='hidden' name='id_nota_$numero_nota-base_icms' value='$total_base_icms'>\n";
                echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
                echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi' value='$total_base_ipi'>\n";
                echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi' value='$total_valor_ipi'>\n";
                echo "<input type='hidden' name='id_nota_$numero_nota-cfop'      value='$cfop'>\n";
                echo "<center>";

        		if($login_fabrica != 186){
                	echo "<b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";
                	echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='20' value='$nota_fiscal'>";
        		}

                echo "</td>";
                echo "</tr>";
                echo "</table>";

                $item_nota=0;
                $numero_nota++;
            }else{
                if (strlen ($nota_fiscal)>0){
                    echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >\n";
                    echo "<tr>\n";
                    echo "<td><h1><center>Nota de Devolução $nota_fiscal</center></h1></td>\n";
                    echo "</tr>";
                    echo "</table>";
                }
            }
            $total_base_icms  = 0;
            $total_valor_icms = 0;
            $total_base_ipi   = 0;
            $total_valor_ipi  = 0;
            $total_nota       = 0;
            $total_peca = 0;
        }

        if(in_array($login_fabrica, array(161)) && $enviado == "nao" && !isset($_GET['listagem'])) {  
            if($tem_peca == "sim" && $tem_produto == "sim") {?>
                <input type='button' id='fechar' value='Gerar Nota Fiscal de Devolução' name='gravar' onclick="javascript:
                if(document.frm_devolucao.qtde_linha_aux_peca.value=='' || document.frm_devolucao.qtde_linha_aux_peca.value=='0'){
                    alert('Informe a quantidade de peças!');
                }else{
                    if(document.frm_devolucao.qtde_linha_aux_produto.value=='' || document.frm_devolucao.qtde_linha_aux_produto.value=='0'){
                        alert('Informe a quantidade de produtos!');
                    }else{
                        document.frm_devolucao.botao_acao.value='digitou_qtde';
                        this.form.submit();
                    }
                }"><br><br>
                <? }else if($tem_peca == "sim" && $tem_produto == "nao"){ ?>
                    <input type='button' id='fechar' value='Gerar Nota Fiscal de Devolução' name='gravar' onclick="javascript:
                    if(document.frm_devolucao.qtde_linha_aux_peca.value=='' || document.frm_devolucao.qtde_linha_aux_peca.value=='0'){
                        alert('Informe a quantidade de peças!');
                    }else{
                        document.frm_devolucao.botao_acao.value='digitou_qtde';
                        this.form.submit();
                    }"><br><br>
                <? }else if($tem_peca == "nao" && $tem_produto == "sim"){ ?>
                    <input type='button' id='fechar' value='Gerar Nota Fiscal de Devolução' name='gravar' onclick="javascript:
                    if(document.frm_devolucao.qtde_linha_aux_produto.value=='' || document.frm_devolucao.qtde_linha_aux_produto.value=='0'){
                        alert('Informe a quantidade de produtos!');
                    }else{
                        document.frm_devolucao.botao_acao.value='digitou_qtde';
                        this.form.submit();
                    }"><br><br>
                <? }
        }
        ### Produtos Ressarcidos ###
        /*HD: 126697 - Quando o posto não tem faturamento, não estrava na impressão da nota de ressarcimento*/
        if(in_array($login_fabrica, array(24,50,146))) { # HD 390021
            $sql = "SELECT posto_fabrica
                    FROM tbl_fabrica
                    WHERE fabrica = $login_fabrica ";

            $res2 = pg_query ($con,$sql);
            $posto_da_fabrica = pg_fetch_result ($res2,0,0);

            $sql_nf = "SELECT  distinct faturamento                                 ,
                        tbl_faturamento_item.extrato_devolucao               ,
                        nota_fiscal
            FROM  tbl_faturamento_item
            JOIN tbl_faturamento USING(faturamento)
            WHERE posto in ($posto_da_fabrica)
            AND   distribuidor      = $login_posto
            AND   fabrica           = $login_fabrica
            AND   tbl_faturamento_item.extrato_devolucao = $extrato
            AND   obs = 'Devolução de Ressarcimento'
            ORDER BY faturamento ASC";

            $res_nf = pg_query($con,$sql_nf);

            if (pg_num_rows($res_nf) > 0) {
                $nota_fiscal_ressarcimento = pg_fetch_result($res_nf,0,2);
            }

            if ($numero_linhas<>5000 or $res_qtde ==0 ){
                #Tirei as partes de faturamento - Fabio - 31-03-2008
                if ($login_fabrica != 146) {
                    $where_troca_garantia = " AND  tbl_os.troca_garantia  IS TRUE ";
                }

                $sql = "SELECT  DISTINCT
                                tbl_os.os                                                         ,
                                tbl_os.sua_os                                                     ,
                                TO_CHAR(tbl_os_troca.data,'DD/MM/YYYY') AS data_ressarcimento,
                                tbl_produto.produto                          AS produto           ,
                                tbl_produto.referencia                       AS produto_referencia,
                                tbl_produto.descricao                        AS produto_descricao ,
                                tbl_admin.login
                    FROM tbl_os
                    JOIN tbl_os_troca USING(os)
                    JOIN tbl_os_extra   USING(os)
                    JOIN tbl_extrato    ON tbl_extrato.fabrica = tbl_os.fabrica and tbl_extrato.posto = tbl_os.posto
                    and tbl_os_extra.extrato = tbl_extrato.extrato
                    LEFT JOIN tbl_admin            ON tbl_os.troca_garantia_admin = tbl_admin.admin
                    LEFT JOIN tbl_produto          ON tbl_os.produto              = tbl_produto.produto
                    WHERE tbl_extrato.extrato   = $extrato
                    AND  tbl_os.fabrica        = $login_fabrica
                    AND  tbl_os.posto          = $login_posto
                    AND  tbl_os_troca.ressarcimento   IS TRUE
                    $where_troca_garantia
                    ";
                $resX = pg_query ($con,$sql);
                //echo nl2br($sql);
                $qtde_produtos_ressarcimento = pg_num_rows ($resX);
                if($qtde_produtos_ressarcimento>0){

                    //HD43448
                    $razao    = "$razao";
                    $endereco = "$endereco";
                    $cidade   = "$cidade";
                    $estado   = "$estado";
                    $cep      = "$cep";
                    $cnpj     = "$cnpj";
                    $ie       = "$ie";

                    $natureza_operacao = "Simples Remessa";

                    # HD 13354
                    $cfop = "6949";
                    $sql = "SELECT contato_estado
                            FROM tbl_posto_fabrica
                            WHERE fabrica = $login_fabrica
                            AND posto = $login_posto";
                    $resW = pg_query ($con,$sql);
                    if (pg_num_rows ($resW)>0){
                        $estado_posto = strtoupper(trim(pg_fetch_result($resW,0,contato_estado)));
						if($estado == $estado_posto){
                            $cfop = "5949";
						}
                    }

                    echo "<input type='hidden' name='ressarcimento' value='$extrato'>\n";

                    echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";

                    echo "<tr align='left'  height='16'>\n";
                    echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
                    echo "<b>&nbsp;<b>RESSARCIMENTO FINANCEIRO -  RETORNO OBRIGATÓRIO </b><br>\n";
                    echo "</td>\n";
                    echo "</tr>\n";

                    echo "<tr>";
                    echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
                    echo "<td>CFOP <br> <b>$cfop</b> </td>";
                    echo "<td>Emissão <br> <b>$data</b> </td>";
                    echo "</tr>";
                    echo "</table>";

                    echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                    echo "<tr>";
                    echo "<td>Razão Social <br> <b>$razao</b> </td>";
                    echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
                    echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
                    echo "</tr>";
                    echo "</table>";


                    echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                    echo "<tr>";
                    echo "<td>Endereço <br> <b>$endereco </b> </td>";
                    echo "<td>Cidade <br> <b>$cidade</b> </td>";
                    echo "<td>Estado <br> <b>$estado</b> </td>";
                    echo "<td>CEP <br> <b>$cep</b> </td>";
                    echo "</tr>";
                    echo "</table>";

                    echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                    echo "<tr align='center' style='font-weight:bold'>";
                    echo "<td>Código</td>";
                    echo "<td>Descrição</td>";
                    echo "<td>Ressarcimento</td>";
                    echo "<td>Responsável</td>";
                    echo "<td>OS</td>";
                    echo "</tr>";

                    for ($x = 0 ; $x < $qtde_produtos_ressarcimento; $x++) {

                        $os                 = pg_fetch_result ($resX,$x,os);
                        $sua_os             = pg_fetch_result ($resX,$x,sua_os);
                        $produto            = pg_fetch_result ($resX,$x,produto);
                        $produto_referencia = pg_fetch_result ($resX,$x,produto_referencia);
                        $produto_descricao  = pg_fetch_result ($resX,$x,produto_descricao);
                        $data_ressarcimento = pg_fetch_result ($resX,$x,data_ressarcimento);
                        $quem_trocou        = pg_fetch_result ($resX,$x,login);

                        echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
                        echo "<input type='hidden' name='ressarcimento_produto_".$x."' value='$produto'>";
                        echo "<input type='hidden' name='ressarcimento_os_".$x."' value='$os'>";
                        echo "<td align='left'>$produto_referencia </td>";
                        echo "<td align='left'>$produto_descricao</td>";
                        echo "<td align='left'>$data_ressarcimento</td>";
                        echo "<td align='right'>$quem_trocou</td>";
                        echo "<td align='right'>$sua_os</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "<input type='hidden' name='qtde_produtos_ressarcimento' value='$qtde_produtos_ressarcimento'>";
                    echo "<input type='hidden' name='ressarcimento_natureza' value='$natureza_operacao'>";
                    echo "<input type='hidden' name='ressarcimento_cfop' value='$cfop'>";

                    echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                    echo "<tr>";
                    echo "<td>";
                    echo "<center>";
                    echo "<b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";

                    echo "<br><div style='margin:0 auto; text-align:center;color:#D90000;font-weight:bold'>* Para o preenchimento da Nota Fiscal de Simples Remessa, utilizar o mesmo valor da Nota Fiscal de compra do consumidor</div>";

                    if (strlen($nota_fiscal_ressarcimento)==0) {
                        echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='ressarcimento_nota_fiscal' size='10' maxlength='6' value='$nota_fiscal_ressarcimento' onblur='gravaRessarcimento(this.value,$extrato)'>";
                    } else {
                        echo "<h1><center>Nota de Devolução $nota_fiscal_ressarcimento</center></h1>";
                    }
                    echo "</td>";
                    echo "<tr>
                    <td align='center'><div id='div_msg' style='display:none; border: 1px solid #949494;background-color: #F1F0E7;width:180px;'></div>
                    </td>
                    </tr>";
                    echo "</td>";
                    echo "</tr>";
                    echo "</table>";
                }
            }
        }


        ### Produtos Trocado enviado DIRETO ao CONSUMIDOR ###
        # HD 13316
        if ($aux_num_linhas == "nao") {
            $sql = "SELECT  tbl_os.os                                                         ,
                            tbl_os.sua_os                                                     ,
                            TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
                            tbl_produto.produto                          AS produto           ,
                            tbl_produto.referencia                       AS produto_referencia,
                            tbl_produto.descricao                        AS produto_descricao ,
                            tbl_admin.login
                FROM tbl_os
                JOIN tbl_os_extra   ON tbl_os_extra.os = tbl_os.os
                JOIN tbl_os_troca   ON tbl_os_troca.os = tbl_os.os
                LEFT JOIN tbl_admin            ON tbl_os.troca_garantia_admin = tbl_admin.admin
                LEFT JOIN tbl_produto          ON tbl_os.produto              = tbl_produto.produto
                LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.os     = tbl_os.os
                LEFT JOIN tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                WHERE tbl_os_extra.extrato = $extrato
                AND  tbl_os.fabrica        = $login_fabrica
                AND  tbl_os.posto          = $login_posto
                AND  tbl_os.ressarcimento  IS NOT TRUE
                AND  tbl_os_troca.envio_consumidor IS TRUE
                AND  (tbl_faturamento_item.faturamento_item IS NULL OR (tbl_faturamento.cancelada IS NOT NULL AND tbl_faturamento.distribuidor = $login_posto)) ";
                //echo nl2br($sql);exit;
            $resX = pg_query ($con,$sql);
            $qtde_produtos_envio_consumidor = pg_num_rows ($resX);
            if($qtde_produtos_envio_consumidor>0){

                $sql_data_geracao_extrato = "SELECT data_geracao::date FROM tbl_extrato WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
                $res_data_geracao_extrato = pg_query($con, $sql_data_geracao_extrato);

                $data_geracao_extrato = pg_fetch_result($res_data_geracao_extrato, 0, "data_geracao");

                //HD43448
                if(strtotime($data_geracao_extrato) >= strtotime("2017-03-01")){

                    $razao    = "BRITANIA ELETRONICOS SA";
                    $endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
                    $cidade   = "Joinville";
                    $estado   = "SC";
                    $cep      = "89239-270";
                    $fone     = "(41) 2102-7700";
                    $cnpj     = "07019308000128";
                    $ie       = "254.861.660";

                }else{

                    $razao    = "BRITANIA ELETRODOMESTICOS LTDA";
                    $endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
                    $cidade   = "Joinville";
                    $estado   = "SC";
                    $cep      = "89239270";
                    $fone     = "(41) 2102-7700";
                    $cnpj     = "76492701000742";
                    $ie       = "254.861.652";

                }

                $natureza_operacao = "Simples Remessa";

                # HD 13354
                $cfop = "6949";
                $sql = "SELECT contato_estado
                        FROM tbl_posto_fabrica
                        WHERE fabrica = $login_fabrica
                        AND posto = $login_posto";
                $resW = pg_query ($con,$sql);
                if (pg_num_rows ($resW)>0){
                    $estado_posto = strtoupper(trim(pg_fetch_result($resW,0,contato_estado)));
                    if ($estado_posto=='SP'){
                        $cfop = "5949";
                    }
                }

                echo "<input type='hidden' name='envio_consumidor' value='$extrato'>\n";

                echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";

                echo "<tr align='left'  height='16'>\n";
                echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
                echo "<b>&nbsp;<b>PRODUTOS - RETORNO OBRIGATÓRIO</b><br>\n";
                echo "</td>\n";
                echo "</tr>\n";

                echo "<tr>";
                echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
                echo "<td>CFOP <br> <b>$cfop</b> </td>";
                echo "<td>Emissão <br> <b>$data</b> </td>";
                echo "</tr>";
                echo "</table>";

                echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                echo "<tr>";
                echo "<td>Razão Social <br> <b>$razao</b> </td>";
                echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
                echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
                echo "</tr>";
                echo "</table>";


                echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                echo "<tr>";
                echo "<td>Endereço <br> <b>$endereco </b> </td>";
                echo "<td>Cidade <br> <b>$cidade</b> </td>";
                echo "<td>Estado <br> <b>$estado</b> </td>";
                echo "<td>CEP <br> <b>$cep</b> </td>";
                echo "</tr>";
                echo "</table>";

                echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                echo "<tr align='center'>";
                echo "<td><b>Código</b></td>";
                echo "<td><b>Descrição</b></td>";
                echo "<td><b>Data</b></td>";
                echo "<td><b>Responsavel</b></td>";
                echo "<td><b>OS</b></td>";
                echo "</tr>";

                for ($x = 0 ; $x < $qtde_produtos_envio_consumidor ; $x++) {

                    $os                 = pg_fetch_result ($resX,$x,os);
                    $sua_os             = pg_fetch_result ($resX,$x,sua_os);
                    $produto            = pg_fetch_result ($resX,$x,produto);
                    $produto_referencia = pg_fetch_result ($resX,$x,produto_referencia);
                    $produto_descricao  = pg_fetch_result ($resX,$x,produto_descricao);
                    $data_fechamento    = pg_fetch_result ($resX,$x,data_fechamento);
                    $quem_trocou        = pg_fetch_result ($resX,$x,login);

                    echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
                    echo "<input type='hidden' name='envio_consumidor_produto_".$x."' value='$produto'>";
                    echo "<input type='hidden' name='envio_consumidor_os_".$x."' value='$os'>";
                    echo "<td align='left'>$produto_referencia</td>";
                    echo "<td align='left'>$produto_descricao</td>";
                    echo "<td align='left'>$data_fechamento</td>";
                    echo "<td align='right'>$quem_trocou</td>";
                    echo "<td align='right'>$sua_os</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<input type='hidden' name='qtde_produtos_envio_consumidor' value='$qtde_produtos_envio_consumidor'>";
                echo "<input type='hidden' name='envio_consumidor_natureza' value='$natureza_operacao'>";
                echo "<input type='hidden' name='envio_consumidor_cfop' value='$cfop'>";

                echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='75%' >";
                echo "<tr>";
                echo "<td>";
                echo "<center>";
                echo "<b>Preencha esta Nota de Devolução e informe o número da Nota Fiscal</b><br>Este número não poderá ser alterado<br>";

                echo "<br><div style='margin:0 auto; text-align:center;color:#D90000;font-weight:bold'>* Para o preenchimento da Nota Fiscal de Simples Remessa, utilizar o mesmo valor da Nota Fiscal de compra do consumidor</div>";

                echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='envio_consumidor_nota_fiscal' size='10' maxlength='6' value='$nota_fiscal'>";
                echo "</td>";
                echo "</tr>";
                echo "</table>";
            }
        }

        if($aux_num_linhas == "sim") {
            # HD 145909
            $sql_res = " SELECT count(DISTINCT tbl_os.os)
                            FROM tbl_os
                            JOIN tbl_os_extra   USING(os)
                            JOIN tbl_extrato    ON tbl_extrato.fabrica = tbl_os.fabrica and tbl_extrato.posto = tbl_os.posto
                            WHERE to_char(tbl_os.finalizada,'MMYYYY') = to_char((tbl_extrato.data_geracao - interval '1 month'),'MMYYYY')
                            AND  tbl_os.fabrica        = $login_fabrica
                            AND  tbl_os.posto          = $login_posto
                            AND  tbl_extrato.extrato   = $extrato
                            AND  tbl_os.ressarcimento   IS TRUE
                            AND  tbl_os.troca_garantia  IS TRUE";
            $res_res = pg_query($con,$sql_res);
            $ressarcimento_qtde = pg_num_rows($res_res);
            $tem_mais_itens = ($ressarcimento_qtde > 0) ? "sim" : $tem_mais_itens;
        }

        /*HD: 126697 - Quando o posto não tem faturamento, não estrava na impressão da nota de ressarcimento*/
        if ($aux_num_linhas == "sim" && ($res_qtde >0 || ($ressarcimento_qtde > 0 && $login_fabrica != 146))){
            if ($tem_mais_itens=='nao' AND $jah_digitado_tc>0){
                if($login_fabrica == 3){
                    #echo "<b>Não há mais peças para devolução.<br><br>";
                }else{
                    echo "<b>Não há mais peças para devolução.<br><br>";
                }
                echo "<a href='extrato_posto_devolucao_lgr_itens_novo.php?extrato=$extrato'>Clique aqui para consultar as notas de devolução</a></b>";
            }else{
                if ($pecas_pendentes=='sim'){
                ?>
                    <input type='hidden' name='pendentes' value='sim'>
                <?
                }
                if (!in_array($login_fabrica, array(161))) {
                ?>
                <br>
                <input type='hidden' name='qtde_pecas' value='<?=$contador;?>'>
                <IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>
                <b style='font-size:12px'>
                Informar a quantidade de linhas no formulário <?=($login_fabrica == 186) ? "" : "de Nota Fiscal"?> do Posto Autorizado:
                <input type='text' size='5' maxlength='3' value='' name='qtde_linha' id="qtde_linha_form">
		<?php if($login_fabrica != 186){ ?>
                <br>
                Essa informação definirá a quantidade de NFs que o posto autorizado deverá emitir e enviar
		<?php } ?>
                <br><br>
                <input type='button' id='fechar' value='Gerar <?=($login_fabrica == 186) ? "" : "Nota Fiscal de"?> Devolução' name='gravar' onclick="javascript:
                if(document.frm_devolucao.qtde_linha.value=='' || document.frm_devolucao.qtde_linha.value=='0'){
                    alert('Informe a quantidade de itens!!');
                }else{
                    if (document.frm_devolucao.botao_acao.value=='digitou_qtde'){
                        alert('Aguarde submissão');
                    }else{
                        document.frm_devolucao.botao_acao.value='digitou_qtde';
                        this.form.submit();
                    }
                }"><br><br>
            <?
                }
            }
        }else{
        ?>

             <br><br><br>
            <input type='hidden' name='qtde_linha' value='<?=$numero_linhas;?>'>
            <input type='hidden' name='numero_de_notas' value='<?=$numero_nota?>'>
            <input type='hidden' name='numero_de_notas_tc' value='<?=$numero_nota_tc?>'>

    	    <?php if($login_fabrica != 186){ ?>
    		    <b <?=$display_none?> >Preencha TODAS as notas acima e clique no botão abaixo para confirmar!</b>
    		    <br><br>
    	    <?php } 
    		$value_btn = ($login_fabrica == 186) ? 'Confirmar devolução' : 'Confirmar notas de devolução';
    	    ?>
            <input <?=$display_none?> type='button' value='<?=$value_btn?>' name='gravar' onclick="javascript:

                if (document.frm_devolucao.botao_acao.value=='digitou_as_notas') {
                    alert('Aguarde Submissão');
                }else{
                    if(confirm('Deseja continuar? As notas de devolução não poderão ser alteradas!')){
                        if (verificar('frm_devolucao')){
                            document.frm_devolucao.botao_acao.value='digitou_as_notas';
                <?
                if($login_fabrica == 50){
                ?>
                            alert('Favor imprimir o espelho da nota fiscal e encaminhar junto com a NF');
                <?
                }
                ?>
                            document.frm_devolucao.submit();
                        }
                    }
                }
                ">
                <br>
                <br><br><input <?=$display_none?> type='button' value='Voltar a Tela Anterior' name='gravar' onclick="javascript:
                    if(confirm('Deseja voltar?')) window.location='<?=$PHP_SELF?>?extrato=<?=$extrato?>';">
        <?
        }

        if ($areaAdmin) { ?>
            <input type='hidden' value='' name='qtde_linha' id="qtde_linha_admin">
        <?php } 

        if ($login_fabrica == 175 && $areaAdmin) {
            echo "<input type='hidden' id='exibe_nota' name='exibe_nota' value='".$_POST['exibe_nota']."'>";
            echo "<input type='hidden' id='posto' name='posto' value='".$login_posto."'>";
        }

        echo "</form>";
    }
}else{
    if ($login_fabrica == 161 && isset($_GET['listagem']) && $_GET['listagem'] == 'true') {
        echo "<h1><center> Sem peças para Devolução </center></h1>";
    }else{
        echo "<h1><center> Extrato de Mão-de-obra Liberado. Recarregue a página. </center></h1>";
    }
}

if ($login_fabrica == 175) {
?>
    <script type="text/javascript">
        $(document).ready(function() {
            let admin = '<?=$areaAdmin?>'
            if (($("#exibe_nota").val() == "" || $("#exibe_nota").val() == undefined) && admin == true) {
                $("#botao_acao").val('digitou_qtde')
             //   $("input[name=qtde_linha]").val('999') voltar se for tirar pro posto
                $("#qtde_linha_admin").val('999')
                $("#exibe_nota").val("sim")
                $("#frm_devolucao").submit()
            }
        })
    </script>

<?php } ?>

<? include "rodape.php"; ?>
