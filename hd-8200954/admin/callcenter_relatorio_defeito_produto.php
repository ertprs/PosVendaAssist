<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];
    $status_chamado     = $_POST['status_chamado'];
	$status             = $_POST['status'];

    if($login_fabrica == 101){
        $origem = $_POST["origem"];
    }

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
    $cond_3 = " 1 = 1 ";

    // if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
    //     $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
    //     $xdata_inicial = str_replace("'","",$xdata_inicial);
    // }else{
    //     $msg_erro["msg"][]    ="Data Inválida";
    //     $msg_erro["campos"][] = "data_inicial";
    // }

    // if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
    //     $xdata_final =  fnc_formata_data_pg(trim($data_final));
    //     $xdata_final = str_replace("'","",$xdata_final);
    // }else{
    //     $msg_erro["msg"][]    ="Data Inválida";
    //     $msg_erro["campos"][] = "data_final";
    // }

    if (!strlen($data_inicial) or !strlen($data_final)) {
        $msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
        $msg_erro["campos"][] = "data";
    } else {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = traduz("Data Inválida");
            $msg_erro["campos"][] = "data";
        } else {
            $xdata_inicial = "{$yi}-{$mi}-{$di}";
            $xdata_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($xdata_final) < strtotime($xdata_inicial)) {
                $msg_erro["msg"][]    = traduz("Data Final não pode ser menor que a Data Inicial");
                $msg_erro["campos"][] = "data";
            }
        }
    }


	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$produto = pg_fetch_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}
    if(strlen($status)>0){
        $cond_3 = " tbl_hd_chamado.status = '$status'  ";
    }
	if(strlen($status_chamado)>0){
        switch($status_chamado){
            case 'andamento':
                $cond_4 .= "
                AND tbl_hd_chamado.status IN (
                                                        'AGUARDANDO AUTORIZADA',
                                                        'AGUARDANDO CLIENTE',
                                                        'AGUARDANDO FABRICA',
                                                        'AGUARDANDO JURIDICO',
                                                        'AGUARDANDO LAUDO',
                                                        'AGUARDANDO PEDIDO SAA',
                                                        'AGUARDANDO REVENDA',
                                                        'CONTATAR AUTORIZADA',
                                                        'CONTATAR CLIENTE',
                                                        'CONTATAR REVENDA',
                                                        'ENVIADO EMAIL PARA AGENDAMENT',
                                                        'PECAS ENVIADAS PELA FABRICA',
                                                        'PEDIDO IMPLANTADO',
                                                        'TROCA AUTORIZADA PELA FABRICA',
                                                        'TROCA ENCAMINHADA',
                                                        'TROCA SOLICITACAO',
                                                        'VISITA AGENDADA',
                                                        'VISITA REAGENDADA'
                                                        )  ";
            break;
            case 'informacoes':
                $cond_4 .= "
                AND tbl_hd_chamado.status IN ('PROTOCOLO DE INFORMACAO')  ";
            break;
            case 'finalizado':
                $cond_4 .= "
                AND UPPER(tbl_hd_chamado.status) IN ('RESOLVIDO')  ";
            break;
            default:
                $cond_4 .= "
                AND    1=1";
            break;
        }
	}

    if($login_fabrica == 101 and strlen(trim($origem))>0){
        $cond_origem = "and tbl_hd_chamado_extra.origem = '$origem' ";
    }

    if (!count($msg_erro["msg"])) {
    	$sql = "SELECT  DISTINCT
                        tbl_hd_chamado.categoria as natureza
                FROM    tbl_hd_chamado
                JOIN    tbl_hd_chamado_extra using(hd_chamado)
                WHERE   fabrica_responsavel     = $login_fabrica
                AND     tbl_hd_chamado.status   <>'Cancelado'
                AND     tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
                AND     $cond_1
                AND     $cond_2
                        $cond_4
                        $cond_origem
            ";
        $resSubmit = pg_query($con,$sql);
        if(pg_num_rows($resSubmit)>0){
            for($y=0;pg_num_rows($resSubmit)>$y;$y++){
                $natureza = pg_fetch_result($resSubmit,$y,natureza);
                $sqln = " select descricao from tbl_natureza where nome ='$natureza';";
                $resn = pg_query($con,$sqln);
                if (pg_num_rows($resn) > 0 ) {
                    $nat = pg_fetch_result($resn,0,0);
                }else{
                    $nat = strtoupper($natureza);
                }
                $array_natureza[$natureza] = array('natureza'=> $nat);

                if($login_fabrica == 74){
                    $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
                }

                if ($login_fabrica == 35 && $_POST['gerar_excel']) {
                    $distinct = '';
                    $camposAtendimento = ",tbl_hd_chamado.hd_chamado,
                                          tbl_hd_chamado.titulo,
                                          to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data_abertura,
                                            ( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS data_interacao,
                                                tbl_admin.login,
                                          tbl_hd_chamado.categoria as natureza_chamado  
                                          ";
                } else {
                    $distinct = 'DISTINCT';
                }

                $xsql = "   SELECT  *
                            FROM    (
                                        SELECT  $distinct
                                                CASE WHEN tbl_produto.produto IS NULL
                                                     THEN '0'
                                                     ELSE tbl_produto.produto
                                                END                       AS produto  ,
                                                CASE WHEN tbl_produto.referencia IS NULL
                                                     THEN 'Sem Produto'
                                                     ELSE tbl_produto.referencia
                                                END                                 AS referencia       ,
                                                CASE WHEN tbl_produto.descricao IS NULL
                                                     THEN 'Sem Produto'
                                                     ELSE tbl_produto.descricao
                                                END                                 AS descricao        ,
                                                CASE WHEN tbl_hd_chamado_extra.defeito_reclamado IS NULL
                                                     THEN '0'
                                                     ELSE tbl_hd_chamado_extra.defeito_reclamado
                                                END                                 AS defeito_reclamado,
                                                CASE WHEN tbl_defeito_reclamado.descricao IS NULL
                                                     THEN 'Sem Defeito'
                                                     ELSE tbl_defeito_reclamado.descricao
                                                END                                 AS defeito_descricao
                                                $camposAtendimento
                                        FROM    tbl_hd_chamado
                                        JOIN    tbl_hd_chamado_extra using(hd_chamado)
                                   LEFT JOIN    tbl_produto             ON tbl_produto.produto                      = tbl_hd_chamado_extra.produto
                                   LEFT JOIN tbl_admin on tbl_hd_chamado.atendente = tbl_admin.admin
                                   LEFT JOIN    tbl_familia             ON tbl_produto.familia                      = tbl_familia.familia
                                   LEFT JOIN    tbl_defeito_reclamado   ON tbl_defeito_reclamado.defeito_reclamado  = tbl_hd_chamado_extra.defeito_reclamado
                                        WHERE   tbl_hd_chamado.status               <> 'Cancelado'
                                        AND     tbl_hd_chamado.categoria            = '$natureza'
                                        AND     tbl_hd_chamado.fabrica_responsavel  = $login_fabrica
                                        AND     tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
                                        and  tbl_hd_chamado.status<>'Cancelado'
                                        AND     $cond_1
                                        AND     $cond_2
                                                $cond_4
                                                $cond_admin_fale_conosco
                                                $cond_origem
                                    ) produtos,
                                    (
                                        SELECT  COUNT(*)                            AS qtde,
                                                tbl_hd_chamado_extra.produto,
                                                CASE WHEN tbl_hd_chamado_extra.defeito_reclamado IS NULL
                                                     THEN '0'
                                                     ELSE tbl_hd_chamado_extra.defeito_reclamado
                                                END                                 AS defeito_reclamado
                                        FROM    tbl_hd_chamado
                                        JOIN    tbl_hd_chamado_extra using(hd_chamado)
                                   LEFT JOIN    tbl_produto             ON tbl_produto.produto                      = tbl_hd_chamado_extra.produto
                                   LEFT JOIN    tbl_familia             ON tbl_produto.familia                      = tbl_familia.familia
                                   LEFT JOIN    tbl_defeito_reclamado   ON tbl_defeito_reclamado.defeito_reclamado  = tbl_hd_chamado_extra.defeito_reclamado
                                        WHERE   tbl_hd_chamado.status               <> 'Cancelado'
                                        AND     tbl_hd_chamado.categoria            = '$natureza'
                                        AND     tbl_hd_chamado.fabrica = $login_fabrica
                                        AND     tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
                                        and  tbl_hd_chamado.status<>'Cancelado'
                                        AND     $cond_1
                                        AND     $cond_2
                                                $cond_4
                                                $cond_admin_fale_conosco
                                                $cond_origem
                                  GROUP BY      tbl_hd_chamado_extra.produto,
                                                tbl_hd_chamado_extra.defeito_reclamado
                                    ) qtde
                            WHERE   produtos.produto          = qtde.produto
                            AND     produtos.defeito_reclamado  = qtde.defeito_reclamado

                      ORDER BY      produtos.descricao,
                                    qtde.qtde;
                ";

				if($login_fabrica == 52) {
					$xsql = str_replace('extra','item',$xsql);
				}

                $xres = pg_query($con,$xsql);
                for($x=0;pg_num_rows($xres)>$x;$x++){

                    if ($login_fabrica == 35 && $_POST['gerar_excel']) {
                        $array_natureza[$natureza]['dados'][$x] = array(
                                                                'produto'           => pg_fetch_result($xres,$x,produto)          ,
                                                                'referencia'        => pg_fetch_result($xres,$x,referencia)       ,
                                                                'descricao'         => pg_fetch_result($xres,$x,descricao)        ,
                                                                'defeito_reclamado' => pg_fetch_result($xres,$x,defeito_reclamado),
                                                                'defeito_descricao' => pg_fetch_result($xres,$x,defeito_descricao),
                                                                'qtde'              => pg_fetch_result($xres,$x,qtde)               ,
                                                                'protocolo'         => pg_fetch_result($xres,$x,hd_chamado)          ,
                                                                'assunto'        => pg_fetch_result($xres,$x,titulo)                   , 
                                                                'data_abertura'        => pg_fetch_result($xres,$x,data_abertura)      ,
                                                                'data_interacao'        => pg_fetch_result($xres,$x,data_interacao)    ,
                                                                'natureza_chamado'        => pg_fetch_result($xres,$x,natureza_chamado),
                                                                'login'        => pg_fetch_result($xres,$x,login)    
                                                            ) ;
                    } else {

                        $array_natureza[$natureza]['dados'][$x] = array(
                                                                'produto'           => pg_fetch_result($xres,$x,produto)          ,
                                                                'referencia'        => pg_fetch_result($xres,$x,referencia)       ,
                                                                'descricao'         => pg_fetch_result($xres,$x,descricao)        ,
                                                                'defeito_reclamado' => pg_fetch_result($xres,$x,defeito_reclamado),
                                                                'defeito_descricao' => pg_fetch_result($xres,$x,defeito_descricao),
                                                                'qtde'              => pg_fetch_result($xres,$x,qtde)
                                                            ) ;
                    }
                }
            }
        }

    }

    if ($_POST["gerar_excel"]) {

        if (pg_num_rows($resSubmit) > 0) {
            $colspan = $login_fabrica == 35 ? 8 : 4;
            $data = date("d-m-Y-H:i");

            $fileName = "callcenter_relatorio_defeito_produto-{$data}.xls";

            $file = fopen("/tmp/{$fileName}", "w");
            $thead = "
                <table border='1'>
                    <thead>
                        <tr>
                            <th colspan='$colspan' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >".traduz('
                                RELATÓRIO DE RECLAMAÇÕES X PRODUTO')."
                            </th>
                        </tr>
            ";
            fwrite($file, $thead);
            foreach($array_natureza as $natureza =>$array_dados){

                $tbody .="
                        <tr>
                            <tr class='titulo_tabela'>
                            <th class='tac' colspan='$colspan' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >";
                                if($natureza == 'troca_produto') $tbody .= traduz("Troca de Produto"); else $tbody .= $array_dados['natureza'];
                $tbody .= "
                            </th>
                        </tr>";

                if($login_fabrica == 35){
                    $tbody .="<tr>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Protocolo')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Assunto')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Abertura')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Fechamento')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Natureza')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Sku')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Defeito Reclamado')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Atendente')."</th>
                        </tr>";
                }else{
                    $tbody .="<tr>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Referência Produto')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Descrição Produto')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Defeito Reclamado')."</th>
                        <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Qtde')."</th>
                    </tr>";
                }

                $tbody .="</thead>
                    <tbody>
            ";

                foreach($array_dados['dados'] as $chave=>$valor){
                    $tbody .= "
                        <tr>
                    ";
                    if ($login_fabrica == 35) {
                            $tbody .= "
                    <td class='tac'>
                        ".$valor['protocolo']."
                    </td>
                    <td class='tac'>
                        ".$valor['assunto']."
                    </td>
                    <td class='tac'>
                        ".$valor['data_abertura']."
                    </td>
                    <td class='tac'>
                        ".$valor['data_interacao']."
                    </td>
                    <td class='tac'>
                        ".$valor['natureza_chamado']."
                    </td>
                    <td class='tac'>
                        ".$valor['referencia']."
                    </td>
                    <td class='tac'>
                        ".$valor['defeito_descricao']."
                    </td>
                            ";
                    } else {
                            $tbody .= "
                    <td class='tac'>".$valor['referencia']."</td>
                    <td class='tac'>".$valor['descricao']."</td>
                    <td class='tac'>".$valor['defeito_descricao']."</td>
                    <td class='tac'>".$valor['qtde']."</td>
                            ";
                    }

                    if ($login_fabrica == 35) {
                       $tbody .= "<td class='tac'>".$valor['login']."</td>";
                    }

                $tbody .= "</tr >
                ";
                }
                $tbody .= "</tbody>";
            }
            $tbody .= "</table>";
            fwrite($file, $tbody);
            fclose($file);

            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
        }

        exit;
    }
}
$layout_menu = "callcenter";
$title = traduz("RELATÓRIO DE RECLAMAÇÕES X PRODUTO");

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
	});
	function retorna_produto (retorno) {
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    }


function AbreCallcenter(data_inicial,data_final,produto,natureza,status,reclamado,origem){
    if(reclamado == 0){
        reclamado == null;
    }
    janela = window.open("callcenter_relatorio_defeito_produto_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+"&natureza="+natureza+"&status="+status+"&reclamado="+reclamado+"&origem="+origem, "Callcenter",'scrollbars=yes,width=1000,height=450,top=315,left=0');
	janela.focus();
}

</script>


<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios ')?></b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
    <br/>
	<div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
	<div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='natureza_chamado'><?php echo ( $login_fabrica == 101) ? traduz("Natureza/Motivo de Contato"): traduz("Natureza")?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="natureza_chamado" id="natureza_chamado">
                            <option value=""></option>
                            <?php
                            $sqlx = "SELECT nome,
                                            descricao
                                    FROM    tbl_natureza
                                    WHERE fabrica=$login_fabrica
                                    AND ativo = 't'
                                    ORDER BY nome";

                            $resx = pg_query($con,$sqlx);

                            foreach (pg_fetch_all($resx) as $key) {
                                $selected_natureza = ( isset($natureza_chamado) and ($natureza_chamado == $key['nome']) ) ? "SELECTED" : '' ;

                            ?>
                                <option value="<?php echo $key['nome']?>" <?php echo $selected_natureza ?> >

                                    <?php echo $key['descricao']?>

                                </option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                </div>
            </div>
        </div>
        <?php if($login_fabrica == 101){?>
    <div class='span4'>
            <div class='control-group '>
                <label class='control-label' for='xorigin'><?=traduz('Origem')?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="origem" id="xorigem">
                            <option value=''><?=traduz('Escolha')?></option>
                            <option value='Telefone' <?PHP if ($origem == 'Telefone') { echo "Selected";}?>><?=traduz('Telefone')?></option>
                            <?php if( $login_fabrica == 101 ){?>
                                <option value='ecommerce' <?PHP if ($origem == 'ecommerce') { echo "Selected";}?>>E-Commerce </option>
                            <?php } ?>
                            <option value='Email' <?PHP if ($origem == 'Email') { echo "Selected";}?>>E-mail</option>
                            <option value='whatsapp' <?PHP if ($origem == 'whatsapp'){ echo "Selected";}?>>WhatsApp</option>
                            <option value='facebook' <?PHP if ($origem == 'facebook') { echo "Selected";}?>>Facebook</option>
                            <option value='reclame_aqui' <?PHP if ($origem == 'reclame_aqui') { echo "Selected";}?>>Reclame Aqui </option>
                            <option value='procon' <?PHP if ($origem == 'procon') { echo "Selected";}?>>Procon </option>
                            <option value='jec' <?PHP if ($origem == 'jec') { echo "Selected";}?>>JEC </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
<?php } ?>
        <div class='span2'></div>
    </div>

<?php
if($login_fabrica == 74){
?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group '>
                <label class='control-label' for='status_chamado'><?=traduz('Status')?></label>
                <div class='controls controls-rowforeach($array_natureza as $natureza =>$array_dados){'>
                    <div class='span4'>
                         <select name="status_chamado" id="status_chamado">

                            <option value='todos' <? if($status_chamado == 'todos') echo 'selected';?>>Todos</option>
                            <option value='andamento' <? if($status_chamado == 'andamento') echo 'selected';?>><?=traduz('Em Andamento')?></option>
                            <option value='informacoes' <? if($status_chamado == 'informacoes') echo 'selected';?>><?=traduz('Informações')?></option>
                            <option value='finalizado' <? if($status_chamado == 'finalizado') echo 'selected';?>>'<?=traduz('Finalizado')?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
<?
}
?>
        <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
<br />

<?
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {

?>

<?
    if($login_fabrica == 35){
        $colspan = 3;
    }else{
        $colspan = 5;
    }
    foreach($array_natureza as $natureza =>$array_dados){

?>
    <table id="callcenter_relatorio_defeito_produto" class='table table-striped table-bordered table-hover table-fixed callcenter_relatorio_defeito_produto' >
    <thead>
        <tr class='titulo_tabela'>
            <th class="tac" colspan="<?=$colspan?>" >
                <?php if($natureza == 'troca_produto') echo traduz("Troca de Produto"); else echo $array_dados['natureza']; ?>
            </th>
        </tr>
        <tr class='titulo_coluna'>
            <?php
                if($login_fabrica == 35){
            ?>
                <th><?=traduz('Referência Produto')?></th>
                <th><?=traduz('Qtde')?></th>
            <?php
                }else{
            ?>
                <th><?=traduz('Referência Produto')?></th>
                <th><?=traduz('Descrição Produto')?></th>
                <th><?=traduz('Defeito Reclamado')?></th>
                <th><?=traduz('Qtde')?></th>
            <?php
                }
            ?>
                <th><?=traduz('Ações')?></th>
        </tr>
    </thead>
    <tbody>
<?
        foreach($array_dados['dados'] as $chave=>$valor){

            $qtde = $valor['qtde'];
            $qtde = intval($qtde);
?>
        <tr>
<?
            if ($login_fabrica == 35) {//HD 219923
?>
                <td class="tac">
                    <a href='javascript:void();' title='<?=$valor['descricao']?>'><?=$valor['referencia']?></a>
                </td>
<?
            } else {
?>
                <td class=""><?=$valor['referencia']?></td>
                <td class=""><?=$valor['descricao']?></td>
                <td class=""><?=$valor['defeito_descricao']?></td>
<?
            }
?>
            <td class='tac'>
                <? echo $valor['qtde'];?>
            </td>
            <td class='tac'>
                <a href="javascript: AbreCallcenter('<?=$xdata_inicial?>','<?=$xdata_final?>','<?=$valor['produto']?>','<?=$natureza?>','<?=$status_chamado?>',<?=$valor['defeito_reclamado']?>, '<?=$origem?>')"><button class='btn btn-small' type="button"><?=traduz('Visualizar')?></button></a>
            </td>
        </tr >

<?

        }
?>
</tbody>
</table>
<br/><br/>
<?php

    }
?>

<script>
    $.dataTableLoad({ table: "#callcenter_relatorio_defeito_produto"});
</script>
<br />

<?
    $jsonPOST = excelPostToJson($_POST);
?>

<div id='gerar_excel' class="btn_excel">
    <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
    <span><img src='imagens/excel.png' /></span>
    <span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
</div>
<?
    }else{
?>
<div class="container">
    <div class="alert">
            <h4><?=traduz('Nenhum resultado encontrado')?></h4>
    </div>
</div>
<?
    }
}
?>

<p>

<? include "rodape.php" ?>
