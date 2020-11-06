<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$title = "Cadastramento De Produtos";
unset($msg_erro);
$msg_erro = array();
if ( (strlen($_POST["listarTudo"]) > 0) && $_POST['gerar_excel'] ) { //703506
    if($login_fabrica == 20){
        $sql = "SELECT  tbl_produto.referencia,
                    tbl_produto.referencia_fabrica,
                    tbl_produto.voltagem  ,
                    tbl_produto.nome_comercial,
                    tbl_produto.origem,
                    tbl_produto.ipi,
                    tbl_produto.classificacao_fiscal,
                    tbl_produto.descricao ,
                    CASE WHEN tbl_produto.ativo IS TRUE THEN 'Ativo' ELSE 'Inativo' END AS ativo     ,
                    tbl_produto.uso_interno_ativo     ,
                    tbl_produto.locador   ,
                    tbl_familia.descricao AS familia,
                    tbl_linha.nome        AS linha
            FROM    tbl_produto
            JOIN    tbl_linha     USING (linha)
            LEFT JOIN tbl_familia USING (familia)
            WHERE   tbl_linha.fabrica = $login_fabrica 
            ORDER BY tbl_produto.descricao ";
    }else {
        $sql = "SELECT  tbl_produto.referencia,
                        tbl_produto.descricao ,
                        CASE WHEN tbl_produto.ativo IS TRUE THEN 'Ativo' ELSE 'Inativo' END AS ativo     ,
                        tbl_familia.descricao AS familia,
                        tbl_linha.nome        AS linha
            FROM    tbl_produto
            JOIN    tbl_linha     USING (linha)
            LEFT JOIN tbl_familia USING (familia)
            WHERE   tbl_linha.fabrica = $login_fabrica 
            ORDER BY tbl_produto.descricao ";
    }
    $resList = pg_query($con,$sql);

    if ( pg_num_rows($resList) > 0) {

        $file     = "xls/relatorio-produtos-{$login_fabrica}.xls";
        $fileTemp = "/tmp/relatorio-produtos-{$login_fabrica}.xls" ;
        $fp     = fopen($fileTemp,"w");
        if($login_fabrica == 20){
            $colspan = 11;
        }else{
            $colspan = 5;
        }
        $head = "<table border='1'>
                    <thead>
                        <tr >
                            <th bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' colspan='$colspan' >RELATÓRIO DOS PRODUTOS CADASTRADOS</th>
                        </tr>
                        <tr>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status Rede</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Linha</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Família</th>";
        if($login_fabrica == 20){
            $head .=       "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Origem</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Voltagem</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bar Tool(*)</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Comercial</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Classificação Fiscal</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>IPI</th>";
        }
        $head .=       "</tr>
                    </thead>
                    <tbody>";

        fwrite($fp, $head );

        for ( $i = 0; $i < pg_num_rows($resList); $i++ ) {
            $body = '<tr>
                        <td>' . pg_result($resList,$i,'ativo') . '</td>
                        <td>' . pg_result($resList,$i,'referencia') . '</td>
                        <td>' . pg_result($resList,$i,'descricao') . '</td>
                        <td>' . pg_result($resList,$i,'linha') . '</td>
                        <td>' . pg_result($resList,$i,'familia') . '</td>';
            if($login_fabrica == 20){
                $body .=   '<td>' . pg_result($resList,$i,'origem') . '</td>
                            <td>' . pg_result($resList,$i,'voltagem') . '</td>
                            <td>' . pg_result($resList,$i,'referencia_fabrica') . '</td>
                            <td>' . pg_result($resList,$i,'nome_comercial') . '</td>
                            <td>' . pg_result($resList,$i,'classificacao_fiscal') . '</td>
                            <td>' . pg_result($resList,$i,'ipi') . '</td>';
            }
            $body .=    '</tr>';
            

            fwrite($fp, $body);

        }

        fwrite($fp, '</tbody></table>');
        fclose($fp);
        if(file_exists($fileTemp)){
            system("mv $fileTemp $file");

            if(file_exists($file)){
                echo $file;    
            }
        }
        
        
        exit;
    }

}
if ($ajax == 'excluir') {

    $imagem      = basename($_GET['imagem']);
    $caminho_dir = "../imagens_produtos/$login_fabrica";

    if (!file_exists("$caminho_dir/media/$imagem"))
        die("A Imagem $imagem não existe!");

    $deletou = unlink("$caminho_dir/media/$imagem");
    $deletou = @unlink("$caminho_dir/pequena/$imagem"); //PDF não tem no pequena

    if ($deletou)
        die("Imagem excluída com sucesso!");

    die("Erro ao excluir a imagem!");

}
if($_GET['ajax'] and $_GET['servico']){
    $servico    = $_GET['servico'];
    $servico_id = $_GET['servico_id'];
    $produto    = $_GET['produto'];
    $valor_adicional      = $_GET['valor'];

    $sql = "SELECT fn_retira_especiais(upper('$servico'))";
    $res = pg_query($con,$sql);
    $servico = pg_fetch_result($res, 0, 0);

    $valores = array($servico => $valor);

    $sql = "SELECT DISTINCT valores_adicionais FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto";
    $res = pg_query($con,$sql);
    $valor = pg_fetch_result($res, 0, 'valores_adicionais');
    if(!empty($valor)){
        $valores_adicionais = json_decode($valor,true);

        if(!empty($servico_id)){    
            $sql = "SELECT fn_retira_especiais(upper('$servico_id'))";
            $res = pg_query($con,$sql);
            $servico_id = pg_fetch_result($res, 0, 0);

            $valores_adicionais[$servico_id] = $valor_adicional;
        }else{
            $valores_adicionais = array_merge($valores_adicionais, $valores);
        }      
    }else{
        $valores_adicionais = $valores;
    }

    
    $valores_adicionais = json_encode($valores_adicionais); 
  
    $sql = "UPDATE tbl_produto SET valores_adicionais = '$valores_adicionais' WHERE fabrica_i = $login_fabrica AND produto = $produto";
    $res = pg_query($con,$sql);

    if(pg_last_error($con)){
        echo pg_last_error($con);
    }else{
        $retorno = mostraDados($produto);
        $retorno = utf8_encode($retorno);
        echo "OK|$retorno";
    }

    exit;
}

if($_GET['ajax_exclui']){
    $servico    = $_GET['servico'];
    $produto    = $_GET['produto'];

    $sql = "SELECT DISTINCT valores_adicionais FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto";
    $res = pg_query($con,$sql);
    $valor = pg_fetch_result($res, 0, 'valores_adicionais');
    if(!empty($valor)){
        $valores_adicionais = json_decode($valor,true);
        unset($valores_adicionais[$servico]);
    }

    if(!count($valores_adicionais)){
        $valores_adicionais = "null";
    }else{
        $valores_adicionais = "'".json_encode($valores_adicionais)."'"; 
    }

    $sql = "UPDATE tbl_produto SET valores_adicionais = $valores_adicionais WHERE fabrica_i = $login_fabrica AND produto = $produto";
    $res = pg_query($con,$sql);

    if(pg_last_error($con)){
        echo pg_last_error($con);
    }else{
        $retorno = mostraDados($produto);
        $retorno = utf8_encode($retorno);
        echo "OK|$retorno";
    }

    exit;
}
include "cabecalho_new.php";

$plugins = array("autocomplete",
                "tooltip",
                 "shadowbox",
                 "dataTable",
                 "price_format"
            );

include ("plugin_loader.php");

$fabricaIntervaloSerie = array(15,45);

//$msg_erro = "";

function mostraDados($produto){
    global $con, $login_fabrica;
    $sql = "SELECT DISTINCT valores_adicionais, produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto AND valores_adicionais notnull";
    $res = pg_query($con,$sql);
    
    if(pg_num_rows($res) > 0){
        
        $retorno = "<table width='700' align='center' class='table table-striped table-bordered table-hover table-fixed scroll'>
                        <thead>
                            <tr class='titulo_coluna'>
                                <th>Serviço</th> <th>Valor</th> <th>Ação</th>
                            </tr>
                        </thead><tbody>";
        for($j = 0; $j < pg_num_rows($res); $j++){
            $valor = pg_fetch_result($res, $j, 'valores_adicionais');
            $valores_adicionais = json_decode($valor,true);
            foreach ($valores_adicionais as $key =>$value) {
                $servico = utf8_decode($key);
                $valor   = $value;

                $retorno .="<tr>
                                <td>
                                    <a href='javascript:void(0);' onclick='carregaDados(\"$servico\",\"$valor\")'>$servico</a>
                                </td> 
                                <td>$valor</td> 
                                <td align='center'><input type='button' value='Excluir' onclick='excluiRegistro(\"$servico\",\"$produto\")'></td>
                            </tr>";
            }
        }
        
        $retorno .= "</tbody></table>";

        return $retorno;
    }
}

if (strlen($_GET['msg']) > 10) { //HD 406404 - Obrigar ao navegador a carregar de novo a imagem.
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}

$acao    = trim($_REQUEST["acao"]);
$btnacao = trim($_REQUEST["btn_acao"]);

//HD 374998 - Usuários que podem alterar a liberação de produtos para diversos países (Bosch)
$prod_libera_br     = (in_array($login_admin, array(515,516)));
$prod_libera_outros = (pg_fetch_result(
						pg_query($con,
								"SELECT altera_pais_produto FROM tbl_admin WHERE admin = $login_admin"), 0) == 't');

if ($acao == "a" and strlen($produto) > 0) {
    $pais = $_GET["pais"];
    if (strlen($pais)) {
        $sql = "SELECT produto,pais FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
        $res = @pg_query ($con,$sql);
        if(pg_num_rows($res)>0){
            $sql = "DELETE FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
            $res = @pg_query ($con,$sql);
        }
    }
    
}


if ($acao == "atribui" and strlen($produto) > 0) {
    $pais            = $_GET["pais"];
    $garantia_pais    = $_GET["garantia_pais"];

	if (!empty($_GET['preco_pais'])) {
		$preco_pais = str_replace(',', '.', $_GET['preco_pais']);
	} else {
		$preco_pais = 'null';
	}

    if (strlen($garantia_pais) == 0) {
        $garantia_pais="null";
    }

    if (strlen($pais) > 0) {
        $sql = "SELECT produto,pais FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
        $res = @pg_query ($con,$sql);
        if (pg_num_rows($res) == 0) {
            $sql = "INSERT INTO tbl_produto_pais (produto,pais, garantia, valor) VALUES ($produto,'$pais', $garantia_pais, $preco_pais)";
            $res = pg_query ($con,$sql);
        }
    }
    
}

if ($btnacao == "deletar" and strlen($produto) > 0) {

    $res = pg_query ($con,"BEGIN TRANSACTION");
    
    $sql = "UPDATE tbl_produto SET
                  ativo = false
            WHERE tbl_produto.produto = $produto";
            
    $res = pg_query ($con,$sql);
    if(pg_last_error($con)){
        $msg_erro["msg"][] = pg_last_error($con);
    }
    if (count($msg_erro["msg"]) == 1) {
        ###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
        $res = pg_query ($con,"COMMIT TRANSACTION");

        $marca                    = "";
        $produto                  = "";
        $linha                    = "";
        $familia                  = "";
        $descricao                = "";
        $referencia               = "";
        $voltagem                 = "";
        $garantia                 = "";
        $garantia_horas           = "";
        $preco                    = "";
        $mao_de_obra              = "";
        $mao_de_obra_admin        = "";
        $mao_de_obra_troca        = "";
        $valor_troca_gas          = "";
        $nome_comercial           = "";
        $classificacao_fiscal     = "";
        $ipi                      = "";
        $radical_serie            = "";
        $radical_serie2           = "";
        $radical_serie3           = "";
        $radical_serie4           = "";
        $radical_serie5           = "";
        $radical_serie6           = "";
        $numero_serie_obrigatorio = "";
        $produto_principal        = "";
        $locador                  = "";
        $ativo                    = "";
        $uso_interno_ativo        = "";
        $referencia_fabrica       = "";
        $code_convention          = "";
        $abre_os                  = "";
        $aviso_email              = "";
        $troca_obrigatoria        = "";
        $produto_critico          = "";
        $intervencao_tecnica      = "";
        $origem                   = "";
        $qtd_etiqueta_os          = "";
        $lista_troca              = "";
        $produto_fornecedor       = "";
        $valor_troca              = "";
        $troca_garantia           = "";
        $troca_faturada           = "";
        $observacao               = "";
        $serie_in                 = "";
        $serie_out                = "";
        $apagar_serie             = "";
        
    }else{
        ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
        $marca                    = $_POST["marca"];
        $produto                  = $_POST["produto"];
        $linha                    = $_POST["linha"];
        $familia                  = $_POST["familia"];
        $descricao                = $_POST["descricao"];
        $referencia               = $_POST["referencia"];
        $voltagem                 = $_POST["voltagem"];
        $garantia                 = $_POST["garantia"];
		$garantia_horas           = $_POST["garantia_horas"];
        $preco                    = $_POST["preco"];
        $mao_de_obra              = $_POST["mao_de_obra"];
        $mao_de_obra_admin        = $_POST["mao_de_obra_admin"];
        $mao_de_obra_troca        = $_POST["mao_de_obra_troca"];
        $valor_troca_gas          = $_POST["valor_troca_gas"];
        $nome_comercial           = $_POST["nome_comercial"];
        $classificacao_fiscal     = $_POST["classificacao_fiscal"];
        $ipi                      = $_POST["ipi"];
        $radical_serie            = $_POST["radical_serie"];
        $radical_serie2           = $_POST["radical_serie2"];
        $radical_serie3           = $_POST["radical_serie3"];
        $radical_serie4           = $_POST["radical_serie4"];
        $radical_serie5           = $_POST["radical_serie5"];
        $radical_serie6           = $_POST["radical_serie6"];
        $numero_serie_obrigatorio = $_POST["numero_serie_obrigatorio"][0];
        $produto_principal        = $_POST["produto_principal"][0];
        $locador                  = $_POST["locador"][0];
        $ativo                    = $_POST["ativo"];
        $uso_interno_ativo        = $_POST["uso_interno_ativo"];
        $referencia_fabrica       = trim($_POST["referencia_fabrica"]);
        $code_convention          = $_POST["code_convention"];
        $abre_os                  = $_POST["abre_os"];
        $aviso_email              = $_POST["aviso_email"];
        $troca_obrigatoria        = $_POST["troca_obrigatoria"][0];
        $produto_critico          = $_POST["produto_critico"][0];
        $intervencao_tecnica      = $_POST["intervencao_tecnica"];
        $origem                   = $_POST["origem"];
        $qtd_etiqueta_os          = $_POST["qtd_etiqueta_os"];
        $lista_troca              = $_POST["lista_troca"][0];
        $produto_fornecedor       = $_POST["produto_fornecedor"];
        $valor_troca              = $_POST["valor_troca"];
        $troca_garantia           = $_POST["troca_garantia"][0];
        $troca_faturada           = $_POST["troca_faturada"][0];
        $observacao               = $_POST["observacao"];
		$serie_in                 = $_POST["serie_in"];
		$serie_out                = $_POST["serie_out"];
		$apagar_serie             = $_POST["apagar_serie"];

        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
}

if ($btnacao == "gravar") {
    $produto = $_POST["produto"];
    if (strlen($_POST["linha"]) > 0){
        $aux_linha = "'". trim($_POST["linha"]) ."'";
    }else{
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "linha";
    }

    $aux_familia = (strlen($_POST["familia"]) > 0) ? "'". trim($_POST["familia"]) ."'" : "null";
    if($aux_familia =="null"){
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
            $msg_erro["campos"][]   = "familia";
    }
    if (strlen($_POST["mao_de_obra"]) > 0)            $aux_mao_de_obra = "'". trim($_POST["mao_de_obra"]) ."'";
    else{
		if($login_fabrica != 96){
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
            $msg_erro["campos"][]   = "mao_de_obra";
		}
		else{
			$aux_mao_de_obra = "'0'";
		}
	}

    if (strlen($_POST["mao_de_obra_admin"]) > 0)    $aux_mao_de_obra_admin = "'". trim($_POST["mao_de_obra_admin"]) ."'";
    else                                            $aux_mao_de_obra_admin = "null";

    if (strlen($_POST["mao_de_obra_troca"]) > 0)    $aux_mao_de_obra_troca = "'". trim($_POST["mao_de_obra_troca"]) ."'";
    else                                            $aux_mao_de_obra_troca = "null";

    if (strlen($_POST["origem"]) > 0)                $aux_origem = "'". trim($_POST["origem"]) ."'";
    else                                            $aux_origem = "null";

    if (strlen($_POST["mao_de_obra_admin"]) > 0) {
        $aux_mao_de_obra_admin = "'". trim($_POST["mao_de_obra_admin"]) ."'";
    } else {
        if ($login_fabrica == 1) {
            #hd 230831 a fabiola não quer que seja igual e não tem quem explique para ela....
            $aux_mao_de_obra_admin = "'"."0"."'";
        } else {
            $aux_mao_de_obra_admin = $aux_mao_de_obra;
        }
    }

    if (strlen($_POST["valor_troca_gas"]) > 0)        $aux_valor_troca_gas= "'". trim($_POST["valor_troca_gas"]) ."'";
    else                                            $aux_valor_troca_gas= "'0'";

    if (strlen($_POST["preco"]) > 0)                $aux_preco = "'". trim($_POST["preco"]) ."'";
    else                                            $aux_preco = "null";

    if (strlen($_POST["garantia"]) > 0){
        $aux_garantia = "'". trim($_POST["garantia"]) ."'";
    }else{
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "garantia";
    }
    if ($login_fabrica <> 20) {
        if (strlen($_POST["descricao"]) > 0){
            $aux_descricao = "'". trim($_POST["descricao"]) ."'";
        }else{
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
            $msg_erro["campos"][]   = "descricao";
        }
    } else {
        if (strlen($_POST['descricao']) > 0) {
            $aux_descricao = "'".$_POST['descricao']."'";
        } else if (strlen($_POST['descricao_idioma']) > 0) {
            $aux_descricao = "'".$_POST['descricao_idioma']."'";
        } else {
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
            $msg_erro["campos"][]   = "descricao";
            $msg_erro["campos"][]   = "descricao_idioma";
            
        }
    }

    $aux_descricao = str_replace("'",'', $aux_descricao);
    $aux_descricao = str_replace('"','', $aux_descricao);
    $aux_descricao = str_replace('\\','', $aux_descricao);
    $aux_descricao = "'$aux_descricao'";

    if (strlen($_POST["referencia"]) > 0){
        $aux_referencia = "'". trim($_POST["referencia"]) ."'";
    }else{
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "referencia";
    }

    if (in_array($login_fabrica,array(1,20,24,80,96))) {
        if (strlen($_POST["referencia_fabrica"]) > 0)    $aux_referencia_fabrica = "'". trim($_POST["referencia_fabrica"]) ."'";
        else{
			if($login_fabrica == 96){
                $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][]   = "referencia_fabrica";
				
			}else{
                $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][]   = "referencia_fabrica";
			}
		}
    }else{
        $aux_referencia_fabrica = "'". trim($_POST["referencia"]) ."'";
    }

    if (strlen($_POST["code_convention"]) > 0)        $aux_code_convention = "'". trim($_POST["code_convention"]) ."'";
    else                                            $aux_code_convention = "null";

    $aux_voltagem = (strlen($_POST["voltagem"]) > 0) ? "'". trim($_POST["voltagem"]) ."'" : "null";

    #HD 22873
    if (strlen($_POST["capacidade"]) > 0)            $aux_capacidade = trim($_POST["capacidade"]);
    else                                            $aux_capacidade = "null";

    #HD 22873
    if (strlen($_POST["divisao"]) > 0)                $aux_divisao = "'". trim($_POST["divisao"]) ."'";
    else                                            $aux_divisao = "null";

    if (strlen($_POST["ativo"]) > 0){
        $aux_ativo = "'". trim($_POST["ativo"]) ."'";
    }else{
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "ativo";
        
    }

    if (strlen($_POST["uso_interno_ativo"]) > 0){
        $aux_uso_interno_ativo = "'". trim($_POST["uso_interno_ativo"]) ."'";
    }else{
        $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][]   = "uso_interno_ativo";
    }

    if (strlen($_POST["produto_fornecedor"]) > 0)    $aux_produto_fornecedor = "'". trim($_POST["produto_fornecedor"]) ."'";
    else                                            $aux_produto_fornecedor = "null";

    if (strlen($_POST["nome_comercial"]) > 0){
        $aux_nome_comercial = trim($_POST["nome_comercial"]) ;
        $aux_nome_comercial = str_replace("'",'', $aux_nome_comercial);
        $aux_nome_comercial = str_replace('"','', $aux_nome_comercial);
        $aux_nome_comercial = str_replace('\\','', $aux_nome_comercial);
    }        
    else{
       $aux_nome_comercial = "null"; 
    }                                            

    

    if (strlen($_POST["classificacao_fiscal"]) > 0){
        $aux_classificacao_fiscal = trim($_POST["classificacao_fiscal"]) ;
        $aux_classificacao_fiscal = str_replace("'",'', $aux_classificacao_fiscal);
        $aux_classificacao_fiscal = str_replace('"','', $aux_classificacao_fiscal);
        $aux_classificacao_fiscal = str_replace('\\','', $aux_classificacao_fiscal);
    }else{
        $aux_classificacao_fiscal = "null";
    }                                            
    

    if (strlen($_POST["ipi"]) > 0 AND is_numeric($_POST['ipi']))                    $aux_ipi = trim($_POST["ipi"]) ;
    else                                            $aux_ipi = "null";

    if (strlen($_POST["radical_serie"]) > 0)        $aux_radical_serie = "'".trim($_POST["radical_serie"])."'";
    else                                            $aux_radical_serie = 'null';

    if (strlen($_POST["radical_serie2"]) > 0)        $aux_radical_serie2 = "'".trim($_POST["radical_serie2"])."'";
    else                                            $aux_radical_serie2 = 'null';

    if (strlen($_POST["radical_serie3"]) > 0)        $aux_radical_serie3 = "'".trim($_POST["radical_serie3"])."'";
    else                                            $aux_radical_serie3 = 'null';

    if (strlen($_POST["radical_serie4"]) > 0)        $aux_radical_serie4 = "'".trim($_POST["radical_serie4"])."'";
    else                                            $aux_radical_serie4 = 'null';

    if (strlen($_POST["radical_serie5"]) > 0)        $aux_radical_serie5 = "'".trim($_POST["radical_serie5"])."'";
    else                                            $aux_radical_serie5 = 'null';

    if (strlen($_POST["radical_serie6"]) > 0)        $aux_radical_serie6 = "'".trim($_POST["radical_serie6"])."'";
    else                                            $aux_radical_serie6= 'null';

    if (strlen($_POST["qtd_etiqueta_os"]) > 0 AND is_numeric($_POST["qtd_etiqueta_os"]))        $aux_qtd_etiqueta_os = trim($_POST["qtd_etiqueta_os"]) ;
    else                                            $aux_qtd_etiqueta_os = "null";

    if (strlen($_POST["marca"]) > 0)                $aux_marca = trim($_POST["marca"]) ;
    else                                            $aux_marca = "null";
    //$radical_serie = trim ($_POST ['radical_serie']);

    if (strlen($_POST["valor_troca"]) > 0)    $aux_valor_troca = trim($_POST["valor_troca"]) ; //hd 7474 TAKASHI 23/11/07
    else                                    $aux_valor_troca = "null";

    if (strlen($_POST["observacao"]) > 0)    $observacao = trim($_POST["observacao"]) ; //113180
    else                                    $observacao = "null";

    $sistema_operacional = (strlen($_POST["sistema_operacional"]) > 0) ? trim($_POST["sistema_operacional"]) : "null";

    $serie_inicial = strtoupper($_POST["serie_inicial"]);
    $serie_final   = strtoupper($_POST["serie_final"]);
    $validar_serie = isset($_POST["validar_serie"]) ? 't' : 'f';//HD 256659

    $troca_garantia = $_POST["troca_garantia"][0];
    $aux_troca_garantia = $troca_garantia;

    if (strlen($troca_garantia) == 0) {
        $aux_troca_garantia = "f";
    }

    $troca_faturada = $_POST["troca_faturada"][0];
    $aux_troca_faturada = $troca_faturada;

    if (strlen($troca_faturada) == 0) {
        $aux_troca_faturada = "f";
    }

    if (strlen($_POST['link_img']) > 0) {
        $aux_link_img   = ($link_img=$_POST['link_img']);
    }

    $produto_critico         = $_POST['produto_critico'][0];
    $aux_produto_critico     = $produto_critico;

    $troca_obrigatoria       = $_POST['troca_obrigatoria'][0];
    $aux_troca_obrigatoria   = $troca_obrigatoria;

    $intervencao_tecnica     = $_POST['intervencao_tecnica'][0];
    $aux_intervencao_tecnica = $intervencao_tecnica;

    $numero_serie_obrigatorio     = $_POST['numero_serie_obrigatorio'][0];
    $aux_numero_serie_obrigatorio = $numero_serie_obrigatorio;

    if (strlen($numero_serie_obrigatorio) == 0) $aux_numero_serie_obrigatorio = "f";
    if (strlen($troca_obrigatoria) == 0)        $aux_troca_obrigatoria = "f";
    if (strlen($produto_critico) == 0)          $aux_produto_critico = "f";
    if (strlen($intervencao_tecnica) == 0)      $aux_intervencao_tecnica = "f";

    $produto_principal = $_POST['produto_principal'][0];
    $aux_produto_principal = $produto_principal;
    if (strlen($produto_principal) == 0) $aux_produto_principal = "f";

    $aux_locador = trim($_POST["locador"][0]);
    if (strlen($aux_locador) == 0) $aux_locador = "f";

    $lista_troca = trim($_POST["lista_troca"][0]);
    $aux_lista_troca = (strlen($lista_troca) == 0) ? "FALSE" : "TRUE";

    if ($login_fabrica == 14 or $login_fabrica == 66 or $login_fabrica == 11) {
        $aux_abre_os = (strlen(trim($_POST["abre_os"])) > 0) ?  trim($_POST["abre_os"]) : 'f';

        if ($login_fabrica <> 11) {
             $aux_aviso_email = trim($_POST["aviso_email"]);
             $retorno_produto = ($aux_aviso_email == 'f')  ? "'" . date("Y-m-d H:i:s") . "'" : "null";
        } else {
             $aux_aviso_email = 't';
             $retorno_produto = "null";
        }
       
    }else{
        $aux_abre_os     = 't';
        $aux_aviso_email = 't';
        $retorno_produto = "null";
    }

    # HD 50627
    $link_img = $_POST["link_img"];

    #HD 16207
    if ($login_fabrica<>1 and $login_fabrica <> 96){
        if (strlen($produto) > 0) {
            $sql_referencia = " AND tbl_produto.produto <> $produto ";
        }

      $sql = "SELECT produto,referencia,descricao
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE tbl_linha.fabrica      = $login_fabrica
                AND   tbl_produto.referencia = $aux_referencia
                $sql_referencia
                ";
        $res = pg_query ($con,$sql);
        if (@pg_num_rows($res) > 0){
            $msg_erro["msg"][] = "Já existe um produto cadastrado com esta referência.";
        }
    }

    if ($troca_obrigatoria == 't' and $produto_critico =='t') {
        $msg_erro["msg"][] = "Troca obrigatoria e produto critico não podem estar selecionados para o mesmo produto";
    }

	//$aux_garantia_horas = !empty($garantia_horas) ? $garantia_horas : '';

	if ($login_fabrica == 87){
		$aux_garantia_horas = $garantia_horas;
	} else {
	$aux_garantia_horas = 0;
	}

	if($login_fabrica == 42){
		$aux_classe = $_POST['classe_produto'];
	}

    if ($login_fabrica == 42) {
        $entrega_tecnica = $_POST["entrega_tecnica"];

        if ($entrega_tecnica <> "t") {
            $entrega_tecnica = "f";
        }
    }

	if (count($msg_erro["msg"]) == 0) {
        $res = pg_query ($con,"BEGIN TRANSACTION");
		// echo $produto;
		// exit;


        if (strlen($produto) == 0) {
            if ($login_fabrica == 42) {
                $sql_entrega_tecnica_c = ", entrega_tecnica";
                $sql_entrega_tecnica_v = ", '$entrega_tecnica'";
            }

            ###INSERE NOVO REGISTRO
            $sql = "INSERT INTO tbl_produto (
                        linha                    ,
                        familia                  ,
                        descricao                ,
                        voltagem                 ,
                        referencia               ,
                        garantia                 ,
						garantia_horas           ,
                        preco                    ,
                        mao_de_obra              ,
                        mao_de_obra_admin        ,
                        mao_de_obra_troca        ,
                        valor_troca_gas          ,
                        ativo                    ,
                        uso_interno_ativo        ,
                        /*off_line                 ,*/
                        nome_comercial           ,
                        classificacao_fiscal     ,
                        ipi                      ,
                        radical_serie            ,
                        radical_serie2           ,
                        radical_serie3           ,
                        radical_serie4           ,
                        radical_serie5           ,
                        radical_serie6           ,
                        numero_serie_obrigatorio ,
                        produto_principal        ,
                        locador                  ,
                        referencia_fabrica       ,
                        code_convention          ,
                        abre_os                  ,
                        aviso_email              ,
                        admin                    ,
                        data_atualizacao         ,
                        troca_obrigatoria        ,
                        produto_critico          ,
                        intervencao_tecnica      ,
                        origem                   ,
                        retorno_produto          ,
                        lista_troca              ,
                        qtd_etiqueta_os          ,
                        valor_troca              ,
                        troca_garantia           ,
                        troca_faturada           ,
                        marca                    ,
                        produto_fornecedor       ,
                        capacidade               ,
                        divisao                  ,
                        imagem                   ,
                        observacao               ,
                        sistema_operacional      ,
                        serie_inicial            ,
                        serie_final              ,
                        validar_serie
                        $sql_entrega_tecnica_c
                    ) VALUES (
                        $aux_linha                       ,
                        $aux_familia                     ,
                        $aux_descricao                   ,
                        $aux_voltagem                    ,
                        $aux_referencia                  ,
                        $aux_garantia                    ,
						$aux_garantia_horas              ,
                        fnc_limpa_moeda($aux_preco)      ,
                        fnc_limpa_moeda($aux_mao_de_obra),
                        fnc_limpa_moeda($aux_mao_de_obra_admin),
                        fnc_limpa_moeda($aux_mao_de_obra_troca),
                        fnc_limpa_moeda($aux_valor_troca_gas),
                        $aux_ativo                       ,
                        $aux_uso_interno_ativo          ,
                        /*$aux_off_line                    ,*/
                        '$nome_comercial'                ,
                        '$classificacao_fiscal'          ,
                        $aux_ipi                         ,
                        $aux_radical_serie               ,
                        $aux_radical_serie2              ,
                        $aux_radical_serie3              ,
                        $aux_radical_serie4              ,
                        $aux_radical_serie5              ,
                        $aux_radical_serie6              ,
                        '$aux_numero_serie_obrigatorio'  ,
                        '$aux_produto_principal'         ,
                        '$aux_locador'                   ,
                        $aux_referencia_fabrica          ,
                        $aux_code_convention             ,
                        '$aux_abre_os'                   ,
                        '$aux_aviso_email'               ,
                        $login_admin                     ,
                        current_timestamp                ,
                        '$aux_troca_obrigatoria'         ,
                        '$aux_produto_critico'           ,
                        '$aux_intervencao_tecnica'       ,
                        $aux_origem                      ,
                        $retorno_produto                 ,
                        $aux_lista_troca                 ,
                        $aux_qtd_etiqueta_os             ,
                        $aux_valor_troca                 ,
                        '$aux_troca_garantia'            ,
                        '$aux_troca_faturada'            ,
                        $aux_marca                       ,
                        $aux_produto_fornecedor          ,
                        $aux_capacidade                  ,
                        $aux_divisao                     ,
                        '$link_img'                      ,
                        '$observacao'                    ,
                        $sistema_operacional             ,
                        '$serie_inicial'                 ,
                        '$serie_final'                   ,
                        '$validar_serie'
                        $sql_entrega_tecnica_v
                    ) RETURNING produto;";


			#echo nl2br ($sql);exit;

            $res = pg_query ($con,$sql);

            $produto = pg_fetch_result($res, 0, "produto");

			#HD 335150 INICIO
			if (in_array($login_fabrica,$fabricaIntervaloSerie)){

				$sql = "select currval('seq_produto') AS produto;";
				$res = pg_query($con,$sql);

				$produto_aux = trim(pg_fetch_result($res,0,produto));
				$totalSerie = count($_POST['produto_serie_in_out']);

				for($i=0;$i<$totalSerie;$i++){

					$serie_in = $_POST['serie_in'][$i];
					$serie_out = $_POST['serie_out'][$i];
					$produto_serie_in_out = $_POST['produto_serie_in_out'][$i];

					if(strlen($produto_serie_in_out) == 0){

						$sql_serie_in_out = "
							INSERT INTO tbl_produto_serie
							(
								fabrica      ,
								produto      ,
								serie_inicial,
								serie_final
							) VALUES (
								$login_fabrica,
								$produto_aux  ,
								'$serie_in'     ,
								'$serie_out'
							);
						";

						$res_serie = pg_query ($con, $sql_serie_in_out);
					}

				}

			}

            /* ----------------------- INICIO - IGOR HD 2846 -------------------------*/

			// Atribuir o país como sendo o Brasil quando for 515 | Andre Ribeiro, 514 | Mara, 516 | Daniel
             if($login_fabrica == 20 and ($login_admin == 514 or $login_admin == 515 or $login_admin == 516 or $login_admin == 1550 or $login_admin== 3128)) {
				$sql = "select currval('seq_produto') as produto;";
//                    $res = pg_query ($con,$sql);

				$produto = trim(pg_fetch_result($res,0,produto));
				//$sql = "SELECT produto,pais FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
				//$res = @pg_query ($con,$sql);
				if(strlen($produto)>0){
					$sql = "INSERT INTO tbl_produto_pais (produto,pais) VALUES ($produto,'BR')";
//                        $res = pg_query ($con,$sql);
				}

            }
            /* ----------------------- FIM - IGOR HD 2846 -------------------------*/
			
			if($login_fabrica == 42){
				$sql = "select currval('seq_produto') as produto;";
				$res = pg_query ($con,$sql);
				$produto = trim(pg_fetch_result($res,0,produto));
				
				if(!empty($aux_classe)){
					$sql = "INSERT INTO tbl_classe_produto(classe,produto) VALUES ($aux_classe,$produto)";
					$res = pg_query ($con,$sql);
				}
			}

			$erroBd = pg_errormessage($con);
		}else{
            if ($login_fabrica == 42) {
                $sql_entrega_tecnica = ", entrega_tecnica = '$entrega_tecnica'";
            }

            ###ALTERA REGISTRO
            $sql = "UPDATE tbl_produto SET
                            linha                    = $aux_linha                             ,
                            familia                  = $aux_familia                           ,
                            descricao                = $aux_descricao                         ,
                            voltagem                 = $aux_voltagem                          ,
                            referencia               = $aux_referencia                        ,
                            garantia                 = $aux_garantia                          ,
							garantia_horas           = $aux_garantia_horas                    ,
                            preco                    = fnc_limpa_moeda($aux_preco)            ,
                            mao_de_obra              = fnc_limpa_moeda($aux_mao_de_obra)      ,
                            mao_de_obra_admin        = fnc_limpa_moeda($aux_mao_de_obra_admin),
							mao_de_obra_ajuste       = fnc_limpa_moeda($aux_mao_de_obra_admin),
                            mao_de_obra_troca        = fnc_limpa_moeda($aux_mao_de_obra_troca),
                            valor_troca_gas          = fnc_limpa_moeda($aux_valor_troca_gas)  ,
                            ativo                    = $aux_ativo                             ,
                            uso_interno_ativo        = $aux_uso_interno_ativo                 ,
                            /*off_line                 = $aux_off_line                        ,*/
                            nome_comercial           = '$nome_comercial'                      ,
                            classificacao_fiscal     = '$classificacao_fiscal'                ,
                            ipi                      = $aux_ipi                               ,
                            radical_serie            = $aux_radical_serie                     ,
                            radical_serie2           = $aux_radical_serie2                    ,
                            radical_serie3           = $aux_radical_serie3                    ,
                            radical_serie4           = $aux_radical_serie4                    ,
                            radical_serie5           = $aux_radical_serie5                    ,
                            radical_serie6           = $aux_radical_serie6                    ,
                            numero_serie_obrigatorio = '$aux_numero_serie_obrigatorio'        ,
                            produto_principal        = '$aux_produto_principal'               ,
                            locador                  = '$aux_locador'                         ,
                            referencia_fabrica       = $aux_referencia_fabrica                ,
                            code_convention          = $aux_code_convention                   ,
                            abre_os                  = '$aux_abre_os'                         ,
                            aviso_email              = '$aux_aviso_email'                     ,
                            admin                    = $login_admin                           ,
                            data_atualizacao         = current_timestamp                      ,
                            troca_obrigatoria        = '$aux_troca_obrigatoria'               ,
                            produto_critico          = '$aux_produto_critico'                 ,
                            intervencao_tecnica      = '$aux_intervencao_tecnica'             ,
                            origem                   = $aux_origem                            ,
                            retorno_produto          = $retorno_produto                       ,
                            lista_troca              = $aux_lista_troca                       ,
                            qtd_etiqueta_os          = $aux_qtd_etiqueta_os                   ,
                            valor_troca              = $aux_valor_troca                       ,
                            troca_garantia           = '$aux_troca_garantia'                  ,
                            troca_faturada           = '$aux_troca_faturada'                  ,
                            marca                    = $aux_marca                             ,
                            produto_fornecedor       = $aux_produto_fornecedor                ,
                            capacidade               = $aux_capacidade                        ,
                            divisao                  = $aux_divisao                           ,
                            imagem                   = '$link_img'                            ,
                            observacao               = '$observacao'                          ,
                            sistema_operacional      = $sistema_operacional                   ,
                            serie_inicial            = '$serie_inicial'                       ,
                            serie_final              = '$serie_final'                         ,
                            validar_serie            = '$validar_serie'
                            $sql_entrega_tecnica
                    FROM tbl_linha
                    WHERE  tbl_produto.linha         = tbl_linha.linha
                    AND    tbl_linha.fabrica         = $login_fabrica
                    AND    tbl_produto.produto       = $produto;";
			//echo nl2br ($sql);
            $res = @pg_query ($con,$sql);

			#HD 335150 INICIO
			if(in_array($login_fabrica,$fabricaIntervaloSerie)){

				$linhas = $_POST['qtde_itens'];

				for($i=0;$i < $linhas; $i++){
                    
					$serie_in = trim($_POST['serie_in_'.$i]);
					$serie_out = trim($_POST['serie_out_'.$i]);
					$produto_serie_in_out = $_POST['produto_serie_in_out_'.$i];
					$excluir = $_POST['apagar_serie_'.$i];
                    echo "ProdSérieInOut: $produto_serie_in_out --- SerieIn: $serie_in --- SerieOut: $serie_out --- Ecluir: $excluir <br> ";
					if ($excluir == 'excluir'){
						$sql_delete = " DELETE FROM tbl_produto_serie
										WHERE tbl_produto_serie.produto_serie = $produto_serie_in_out
										and tbl_produto_serie.produto = $produto
						";
						$res = pg_query($con,$sql_delete);
					}

                    if($login_fabrica == 15 and empty($excluir) and !empty($serie_in) and !empty($serie_out)){ //882760
                        
                        $serie_in_first  = substr($serie_in, 0,1);
                        $serie_out_first = substr($serie_out, 0,1); 
                        $ano_serie_in    = substr($serie_in, 3,1);
                        $ano_serie_out   = substr($serie_out, 3,1);
                        $serie_in_lote   = substr($serie_in, 4,5);
                        $serie_out_lote  = substr($serie_out, 4,5);
                        $ano_atual = date('Y');
                        $ano_letra_inicial = 'A';
                        $anos_verificar = array();

                        // Série In Lote
                        if ( (in_array($serie_in_first, array(1,4)) and strlen($serie_in_lote) < 4) or substr($serie_in_lote, 0,1) == 0 ){
                            
                            $msg_erro["msg"][] = "Número de Série $serie_in Inválido <br> ";

                        }elseif( (in_array($serie_in_first, array(9)) and strlen($serie_in_lote) < 3 ) or substr($serie_in_lote, 0,1) == 0 ){
                            
                            $msg_erro["msg"][] = "Número de Série $serie_in Inválido <br>";

                        }

                        //Série Out Lote
                        if ( (in_array($serie_out_first, array(1,4)) and strlen($serie_out_lote) < 4 ) or substr($serie_out_lote, 0,1) ==  0 ){
                           
                            $msg_erro["msg"][] = "Número de Série $serie_out Inválido <br>";

                        }elseif( (in_array($serie_out_first, array(9)) and strlen($serie_out_lote) < 3) or substr($serie_out_lote, 0,1) == 0 ){
                            
                            $msg_erro["msg"][] = "Número de Série $serie_out Inválido <br>";

                        }
                       
                        //Anos
                        $qtde_de_anos_for = $ano_atual - 1994;
                        for ($x=1; $x <= $qtde_de_anos_for; $x++) { 
                            $anos_verificar[$x] = $ano_letra_inicial++;
                        }
                        if (!in_array($ano_serie_in, $anos_verificar)){
                            $msg_erro["msg"][] = "Número de Série $serie_in Inválido <br>";
                        } 

                        if( !in_array($ano_serie_out, $anos_verificar) ){
                          
                            $msg_erro["msg"][] = "Número de Série $serie_out Inválido <br>";    

                        }

                    }

					if (strlen($produto_serie_in_out) > 0 && strlen($excluir) == 0 && (count($msg_erro["msg"])==0) ){
						$sql_serie_in_out = "
							UPDATE tbl_produto_serie SET
								fabrica       = $login_fabrica  ,
								produto       = $produto        ,
								serie_inicial = '$serie_in'     ,
								serie_final   = '$serie_out'
							WHERE	tbl_produto_serie.produto = $produto
							AND		tbl_produto_serie.produto_serie = $produto_serie_in_out";
						$res_serie = pg_query ($con, $sql_serie_in_out);
					}

					if(strlen($produto_serie_in_out) == 0 && count($msg_erro["msg"])==0 && strlen($serie_in) > 0){

						$sql_serie_in_out = "
							INSERT INTO tbl_produto_serie
							(
								fabrica      ,
								produto      ,
								serie_inicial,
								serie_final
							) VALUES (
								$login_fabrica,
								$produto ,
								'$serie_in'     ,
								'$serie_out'
							);
						";

						$res_serie = pg_query ($con, $sql_serie_in_out);

					}

                }

			}

			if($login_fabrica == 42){
				$sql = "SELECT produto FROM tbl_classe_produto WHERE produto = $produto";
				$res = pg_query ($con,$sql);
				if(pg_numrows($res) > 0){
					if(empty($aux_classe)){
						$sql = "DELETE FROM tbl_classe_produto WHERE produto = $produto";
						$res = pg_query ($con,$sql);
					} else {
						$sql = "UPDATE tbl_classe_produto SET classe = $aux_classe WHERE produto = $produto";
						$res = pg_query ($con,$sql);
					}
				}else if(!empty($aux_classe)){
					$sql = "INSERT INTO tbl_classe_produto(classe,produto) VALUES ($aux_classe,$produto)";
					$res = pg_query ($con,$sql);
				}
			}

        }

        //--=== TRADUÇÂO DOS PRODUTOS =========================================
        $idioma           = $_POST["idioma"];
        $idioma_novo      = $_POST["idioma_novo"];
        $descricao_idioma = $_POST["descricao_idioma"];

        if (strlen($idioma) == 2 AND strlen($descricao_idioma) > 0) {

            if (strlen($idioma_novo) == 2) {

                $sql = "INSERT INTO tbl_produto_idioma (produto,
                                                        descricao,
                                                        idioma
                                              ) VALUES ($produto,
                                                        '$descricao_idioma',
                                                        '$idioma')";

            } else {

                $sql = "UPDATE tbl_produto_idioma SET descricao = '$descricao_idioma'
                         WHERE produto = $produto
                           AND idioma  = '$idioma'";

            }
            $res      = @pg_query ($con,$sql);
            $msg_erro["msg"][]= pg_errormessage($con);

        }

        $qtde_item = $_POST['qtde_item'];

        for ($i = 0; $i < $qtde_item; $i++) {

            $referencia_opcao    = $_POST["referencia_opcao_".$i];
            $descricao_opcao     = $_POST["descricao_opcao_".$i];
            $produto_opcao       = $_POST["produto_opcao_".$i];
            $produto_troca_opcao = $_POST["produto_troca_opcao_".$i];
            $voltagem_opcao      = $_POST["voltagem_opcao_".$i];
            $kit_opcao           = $_POST["kit_opcao_".$i];

            if (strlen($voltagem_opcao) == 0 and strlen($referencia_opcao) > 0) {
                $msg_erro["msg"][]= "Informe a voltagem para o produto de opção troca $referencia_opcao. Clique na lupa para pesquisar.";
                $erro_linha = "erro_linha" . $i;
                $$erro_linha = 1 ;
                break;
            }

            if (count($msg_erro) ==0 and strlen($referencia_opcao)) {

                $sql = "SELECT produto
                          FROM tbl_produto
                          JOIN tbl_linha using(linha)
                         WHERE referencia = '$referencia_opcao'
                           AND voltagem   = '$voltagem_opcao'
                           AND fabrica    = $login_fabrica
                         LIMIT 1";

                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0) {
                    $msg_erro["msg"][] = "Produto com referência $referencia_opcao e voltagem $voltagem_opcao não encontrado.";
                } else {
                    $produto_opcao = pg_fetch_result($res,0,0);
                }

            }

            if (count($msg_erro) ==0) {

                if (strlen($produto_troca_opcao) > 0) {

                    if (strlen($referencia_opcao) == 0) {

                        $sql = "DELETE FROM tbl_produto_troca_opcao
                                WHERE produto_troca_opcao = $produto_troca_opcao";

                        $res = pg_query($con, $sql);

                        if (strlen( pg_errormessage($con) ) > 0) {
                            $msg_erro["msg"][]= pg_errormessage($con) ;
                        }

                    } else {

                        $sql = "UPDATE tbl_produto_troca_opcao SET
                                    produto_opcao = $produto_opcao,
                                    kit = $kit_opcao
                                WHERE produto_troca_opcao = $produto_troca_opcao";

                        $res = pg_query($con, $sql);

                        if (strlen(pg_errormessage($con) ) > 0) {
                            $msg_erro["msg"][]= pg_errormessage($con);
                        }

                    }

                } else {

                    if (strlen($referencia_opcao)>0) {
                        $sql = "INSERT INTO tbl_produto_troca_opcao (produto, produto_opcao, kit)
                                VALUES ($produto, $produto_opcao, $kit_opcao)";
                        $res = pg_query($con, $sql);
                        if (strlen( pg_errormessage($con) ) > 0) {
                            $msg_erro["msg"][]= pg_errormessage($con) ;
                        }
                    }

                }

            }

        }

		if ($login_fabrica == 3) {//HD 325481

			for ($i = 0; $i < $total_mascara; $i++) {

				if (isset($_POST['mascara_'.$i])) {

					$mascara = trim($_POST['mascara_'.$i]);

					if (!empty($mascara)) {

						$sql = "SELECT * FROM tbl_produto_valida_serie WHERE fabrica = $login_fabrica AND produto = $produto AND mascara = '$mascara';";
						$res = pg_exec($con,$sql);
						$tot = pg_num_rows($res);

						if ($tot == 0) {
							$sql = "INSERT INTO tbl_produto_valida_serie (fabrica, produto, mascara) values ($login_fabrica, $produto, '$mascara')";
							$res = pg_exec($con,$sql);
						}

					}

				}

			}

		}//HD 325481
        
        if (count($msg_erro["msg"]) ==0) {
            $res = pg_query ($con,"COMMIT TRANSACTION");
            $msg = "Gravado com Sucesso";

            unset($_POST);
        } else {

            ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
            $produto                    = $_POST["produto"];
            $marca                      = $_POST["marca"];
            $linha                      = $_POST["linha"];
            $familia                    = $_POST["familia"];
            $descricao                  = $_POST["descricao"];
            $referencia                 = $_POST["referencia"];
            $voltagem                   = $_POST["voltagem"];
            $garantia                   = $_POST["garantia"];
			$garantia_horas             = $_POST["garantia_horas"];
            $preco                      = $_POST["preco"];
            $mao_de_obra                = $_POST["mao_de_obra"];
            $mao_de_obra_admin          = $_POST["mao_de_obra_admin"];
            $mao_de_obra_troca          = $_POST["mao_de_obra_troca"];
            $valor_troca_gas            = $_POST["valor_troca_gas"];
            $ativo                      = $_POST["ativo"];
            $uso_interno_ativo          = $_POST["uso_interno_ativo"];
            $nome_comercial             = $_POST["nome_comercial"];
            $classificacao_fiscal       = $_POST["classificacao_fiscal"];
            $ipi                        = $_POST["ipi"];
            $radical_serie              = $_POST["radical_serie"];
            $radical_serie2             = $_POST["radical_serie2"];
            $radical_serie3             = $_POST["radical_serie3"];
            $radical_serie4             = $_POST["radical_serie4"];
            $radical_serie5             = $_POST["radical_serie5"];
            $radical_serie6             = $_POST["radical_serie6"];
            $numero_serie_obrigatorio   = $_POST["numero_serie_obrigatorio"][0];
            $troca_obrigatoria          = $_POST["troca_obrigatoria"][0];
            $produto_critico            = $_POST["produto_critico"][0];
            $intervencao_tecnica        = $_POST["intervencao_tecnica"];
            $produto_principal          = $_POST["produto_principal"][0];
            $locador                    = $_POST["locador"][0];
            $referencia_fabrica         = trim($_POST["referencia_fabrica"]);
            $code_convention            = $_POST["code_convention"];
            $abre_os                    = $_POST["abre_os"];
            $origem                     = $_POST["origem"];
            $produto_fornecedor         = $_POST["produto_fornecedor"];
            $aviso_email                = $_POST["aviso_email"];
            $qtd_etiqueta_os            = $_POST["qtd_etiqueta_os"];
            $lista_troca                = $_POST["lista_troca"][0];
            $valor_troca                = $_POST["valor_troca"];
            $troca_garantia             = $_POST["troca_garantia"][0];
            $troca_faturada             = $_POST["troca_faturada"][0];
            $sistema_operacional        = $_POST['sistema_operacional'];
            $serie_inicial              = $_POST['serie_inicial'];
            $serie_final                = $_POST['serie_final'];
			$aux_classe                 = $_POST['classe_produto'];
            $entrega_tecnica            = $_POST["entrega_tecnica"];

            if (strpos ($erroBd,"duplicate key violates unique constraint \"tbl_produto_referencia_pesquisa\"") > 0)
                $msg_erro["msg"][] = "Referência para esta linha de produtos já existe e não pode ser duplicada.";

            if (strpos ($erroBd,"duplicate key violates unique constraint \"tbl_produto_unico\"") > 0)
                $msg_erro["msg"][] = "Referência para esta linha de produtos já existe e não pode ser duplicada.";
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        }

    }//fim if msg erro

}
//  HD 96953 - Fotos de produtos para a Britânia
if (($login_fabrica == 3 or $login_fabrica > 80) && (count($msg_erro["msg"]) ==0) ) {
        $imgType = '';
    function open_image ($file) {
        global $imgType;
        $img_data = getimagesize($file);
        switch($img_data["mime"]){
            case "image/jpeg":
                $im = imagecreatefromjpeg($file); //jpeg file
                $imgType = 'jpg';
            break;
            case "image/gif":
                $im = imagecreatefromgif($file); //gif file
                $imgType = 'gif';
            break;
            case "image/png":
                $im = imagecreatefrompng($file); //png file
                $imgType = 'png';
            break;
            case "image/bmp":
                $im = imagecreatefromwbmp($file); //png file
                $imgType = 'bmp';
            break;
            default:
            $im=false;
            break;
        }
        return $im;
    }

    function save_image($file, $dest_path, $format='jpeg') {
        // Grava a imagem no $dest_path, no formato $format
        switch($format){
            case "bmp":
            case "jpg":
            case "jpeg":
                $ret = imagejpeg($file, $dest_path, 80); // Salva em formato JPeG
            break;
            case "gif":
                $ret = imagegif($file, $dest_path); // Salva como GIF
            break;
            case "png":
                $ret = imagepng($file, $dest_path, 2); // Salva como PNG, compressão nível 2
            break;
            default:
            $ret = false;
            break;
        }
        return $ret;
    }

    function reduz_imagem($img, $max_x, $max_y, $nome_foto, $formato = 'jpg') {
        list($original_x, $original_y) = getimagesize($img);    //pega o tamanho da imagem

        // se a largura for maior que altura
        if($original_x > $original_y) {
           $porcentagem = (100 * $max_x) / $original_x;
        }
        else {
           $porcentagem = (100 * $max_y) / $original_y;
        }

        $tamanho_x    = $original_x * ($porcentagem / 100);
        $tamanho_y    = $original_y * ($porcentagem / 100);
        $image_p    = imagecreatetruecolor($tamanho_x, $tamanho_y);
        $image      = open_image($img);

        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $original_x, $original_y);
        return save_image($image_p, $nome_foto, $formato);
    }

    if (isset($_FILES['arquivo'])) {

        $a_foto = $_FILES["arquivo"];

        if ($a_foto['tmp_name'] <> '' and
            $a_foto['name'] != '' and
            $a_foto['error'] == UPLOAD_ERR_OK and
            $a_foto['size'] < 1048576) { //HD 404604 - Rogério da Wanke pediu para liberar até 1Mb.
            $Destino  = "../imagens_produtos/$login_fabrica/media/";
            $DestinoP = "../imagens_produtos/$login_fabrica/pequena/";
            $Nome     = $a_foto['name'];
            $Tamanho  = $a_foto['size'];
            $Tipo     = $a_foto['type'];
            $Tmpname  = $a_foto['tmp_name'];

            if (preg_match('/^image\/(x-ms-bmp|bmp|x-bmp|pjpeg|jpeg|png|x-png|gif|jpg)$/', $Tipo)) {
                if (!is_uploaded_file($Tmpname)) {
                    $msg_erro["msg"][] = "Não foi possível efetuar o upload.";
                } else {
                    $ext = substr($Nome, strrpos($Nome, ".")+1);
                    if (strlen($Nome) == 0 and $ext <> "") {
                        $ext = $Nome;
                    }

                    list($void, $tipo_MIME) = explode('/', $Tipo);
                    $Caminho_foto   = $Destino . "$produto.jpg";
                    $Caminho_thumb  = $DestinoP. "$produto.jpg";

                    // Salvar o arquivo de imagem se já existir
                    $arq_ant = glob("$Destino$produto.{gif,GIF,png,PNG,jpg,JPG}", GLOB_BRACE);
                    //echo '<code>' . print_r($arq_ant) . '</code>';
                    if (count($arq_ant)) {
                        $img_anterior = $Destino . 'temp_' . basename($arq_ant[0]);
                        $thumb_anterior = $DestinoP . 'temp_' . basename($arq_ant[0]);
                        rename($arq_ant[0], $img_anterior);
                        rename(str_replace('media', 'pequena', $arq_ant[0]), $thumb_anterior);
                        $excl_ant = true;
                        //echo "<p>Imagem anterior: $img_anterior</p>";
                    }

                    /* Estas linhas são para ajustar a extensão do arquivo ao formato,
                     * mas, por enquanto, o formato destino vai ser sempre JPG...
                     * Comentando código.
                     $path_info = pathinfo($Caminho_foto);
                     $nome      = $path_info['dirname'] . '/' . $path_info['filename'];
                     $ext       = $path_info['extension'];

                     if ($ext != $tipo_MIME) { // Muda a extensão do arquivo de acordo com o conteúdo
                     $ext = ($tipo_MIME  == 'jpeg' or $tipo_MIME == 'bmp') ? 'jpg' : $tipo_MIME;
                     }
                     echo "\nExtensão: $ext\n<br />\n";
                     echo $Caminho_foto = $nome . '.' . $ext;
                     */
                    reduz_imagem($Tmpname, 400,    300, $Caminho_foto);
                    if (!file_exists($Caminho_foto)) {
                        $msg_erro["msg"][] = 'Não foi possível adicionar a imagem, formato não reconhecido.';
                        if ($excl_ant) {
                            rename($img_anterior, $arq_ant[0]); //Voltar o arquivo de imagem que já existia
                            rename($thumb_anterior, str_replace('media', 'pequena', $arq_ant[0]));
                        }
                    } else {
                    reduz_imagem($Tmpname, 80,    60,  $Caminho_thumb);
                        if ($excl_ant) {
                            unlink($img_anterior); //Excluir definitivamente o arquivo anterior, se existia
                            unlink($thumb_anterior); //Excluir definitivamente o arquivo anterior, se existia
                        }
                    }
                }

            } else {
                $msg_erro["msg"][] = "O formato do arquivo $Nome não é permitido!<br>";
            }

        } else {
            switch ($a_foto['error']) {
                case 0: if ($a_foto['name'] != '' and $a_foto['tmp_name'] == '') $msg_erro["msg"][] = 'Erro ao processar o arquivo ' . $a_foto['name'] . '.<br>'; break;
                case 1: $msg_erro["msg"][] = "O tamanho do arquivo é maior do que o permitido (2Mb)!<br>"; break;
                case 2: $msg_erro["msg"][] = "O tamanho do arquivo é maior do que o permitido (1Mb)!<br>"; break;
                case 3: $msg_erro["msg"][] = 'O arquivo não foi enviado completo, tente novamente.<br>'; break;
                case 4: if ($a_foto['name'] != '') $msg_erro["msg"][] = 'Arquivo não recebido.<br>'; break;
                default: $msg_erro["msg"][] = 'Erro interno. Tente novamente ou contate com a Telecontrol.<br>';
            }
        }
        /*if ($msg_erro) exit("<p>$msg_erro</p></body></html>");*/
    }
}
###CARREGA REGISTRO
$produto = $_GET['produto'];

if (strlen($produto) > 0) {

    $sql = "SELECT  tbl_produto.produto                            ,
                    tbl_produto.linha                              ,
                    tbl_produto.familia                            ,
                    tbl_produto.descricao                          ,
                    tbl_produto.voltagem                           ,
                    tbl_produto.referencia                         ,
                    tbl_produto.garantia                           ,
					tbl_produto.garantia_horas                     ,
                    tbl_produto.preco                              ,
                    tbl_produto.mao_de_obra                        ,
                    tbl_produto.mao_de_obra_admin                  ,
					tbl_produto.mao_de_obra_ajuste                 ,
                    tbl_produto.mao_de_obra_troca                  ,
                    tbl_produto.valor_troca_gas                    ,
                    tbl_produto.ativo                              ,
                    tbl_produto.uso_interno_ativo                  ,
                    tbl_produto.nome_comercial                     ,
                    tbl_produto.classificacao_fiscal               ,
                    tbl_produto.ipi                                ,
                    tbl_produto.origem                             ,
                    tbl_produto.radical_serie                      ,
                    tbl_produto.radical_serie2                     ,
                    tbl_produto.radical_serie3                     ,
                    tbl_produto.radical_serie4                     ,
                    tbl_produto.radical_serie5                     ,
                    tbl_produto.radical_serie6                     ,
                    tbl_produto.numero_serie_obrigatorio           ,
                    tbl_produto.produto_principal                  ,
                    tbl_produto.locador                            ,
                    tbl_produto.referencia_fabrica                 ,
                    tbl_produto.code_convention                    ,
                    tbl_produto.abre_os                            ,
                    tbl_produto.aviso_email                        ,
                    tbl_produto.troca_obrigatoria                  ,
                    tbl_produto.produto_critico                    ,
                    tbl_produto.intervencao_tecnica                ,
                    tbl_produto.qtd_etiqueta_os                    ,
                    tbl_produto.lista_troca                        ,
                    tbl_admin.login                                ,
                    tbl_marca.marca                                ,
                    tbl_produto.valor_troca                        ,
                    tbl_produto.troca_garantia                     ,
                    tbl_produto.troca_faturada                     ,
                    tbl_produto.produto_fornecedor                 ,
                    to_char(tbl_produto.data_atualizacao, 'DD/MM/YYYY HH24:MI') AS data_atualizacao,
                    tbl_produto.capacidade                         ,
                    tbl_produto.divisao                            ,
                    tbl_produto.observacao                         ,
                    tbl_produto.imagem                             ,
                    tbl_produto.sistema_operacional                ,
                    tbl_produto.serie_inicial                      ,
                    tbl_produto.serie_final                        ,
                    tbl_produto.validar_serie                      ,
					tbl_classe_produto.classe,
                    tbl_produto.entrega_tecnica

            FROM    tbl_produto
            JOIN    tbl_linha ON tbl_linha.linha = tbl_produto.linha

            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_produto.admin
            LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
			LEFT JOIN tbl_classe_produto ON tbl_produto.produto = tbl_classe_produto.produto
            WHERE   tbl_linha.fabrica   = $login_fabrica
            AND     tbl_produto.produto = $produto;";

    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {

        $_RESULT["produto"]                  = trim(pg_fetch_result($res,0,'produto'));
        $_RESULT["linha"]                    = trim(pg_fetch_result($res,0,'linha'));
        $_RESULT["familia"]                  = trim(pg_fetch_result($res,0,'familia'));
        $_RESULT["descricao"]                = trim(pg_fetch_result($res,0,'descricao'));
        $_RESULT["referencia"]               = trim(pg_fetch_result($res,0,'referencia'));
        $_RESULT["voltagem"]                 = trim(pg_fetch_result($res,0,'voltagem'));
        $_RESULT["garantia"]                 = trim(pg_fetch_result($res,0,'garantia'));
		$_RESULT["garantia_horas"]           = trim(pg_fetch_result($res,0,'garantia_horas'));
        $_RESULT["preco"]                    = trim(pg_fetch_result($res,0,'preco'));
        $_RESULT["mao_de_obra"]              = trim(pg_fetch_result($res,0,'mao_de_obra'));
		if ($login_fabrica == 101) {
			$_RESULT["mao_de_obra_admin"]        = trim(pg_fetch_result($res,0,'mao_de_obra_ajuste'));
		}
		else {
			$_RESULT["mao_de_obra_admin"]        = trim(pg_fetch_result($res,0,'mao_de_obra_admin'));
		}
        $_RESULT["mao_de_obra_troca"]        = trim(pg_fetch_result($res,0,'mao_de_obra_troca'));
        $_RESULT["valor_troca_gas"]          = trim(pg_fetch_result($res,0,'valor_troca_gas'));
        $_RESULT["ativo"]                    = trim(pg_fetch_result($res,0,'ativo'));
        $_RESULT["uso_interno_ativo"]        = trim(pg_fetch_result($res,0,'uso_interno_ativo'));
        $_RESULT["nome_comercial"]           = trim(pg_fetch_result($res,0,'nome_comercial'));
        $_RESULT["classificacao_fiscal"]     = trim(pg_fetch_result($res,0,'classificacao_fiscal'));
        $_RESULT["ipi"]                      = trim(pg_fetch_result($res,0,'ipi'));
        $_RESULT["radical_serie"]            = trim(pg_fetch_result($res,0,'radical_serie'));
        $_RESULT["radical_serie2"]           = trim(pg_fetch_result($res,0,'radical_serie2'));
        $_RESULT["radical_serie3"]           = trim(pg_fetch_result($res,0,'radical_serie3'));
        $_RESULT["radical_serie4"]           = trim(pg_fetch_result($res,0,'radical_serie4'));
        $_RESULT["radical_serie5"]           = trim(pg_fetch_result($res,0,'radical_serie5'));
        $_RESULT["radical_serie6"]           = trim(pg_fetch_result($res,0,'radical_serie6'));
        $_RESULT["numero_serie_obrigatorio"] = trim(pg_fetch_result($res,0,'numero_serie_obrigatorio'));
        $_RESULT["produto_principal"]        = trim(pg_fetch_result($res,0,'produto_principal'));
        $_RESULT["locador"]                  = trim(pg_fetch_result($res,0,'locador'));
        $_RESULT["referencia_fabrica"]       = trim(pg_fetch_result($res,0,'referencia_fabrica'));
		if($login_fabrica == 96)
			$_RESULT["modelo"] = $_RESULT["referencia_fabrica"];
        $_RESULT["code_convention"]          = trim(pg_fetch_result($res,0,'code_convention'));
        $_RESULT["abre_os"]                  = trim(pg_fetch_result($res,0,'abre_os'));
        $_RESULT["aviso_email"]              = trim(pg_fetch_result($res,0,'aviso_email'));
        $_RESULT["origem"]                   = trim(pg_fetch_result($res,0,'origem'));
        $_RESULT["produto_fornecedor"]       = trim(pg_fetch_result($res,0,'produto_fornecedor'));
        $_RESULT["admin"]                    = trim(pg_fetch_result($res,0,'login'));
        $_RESULT["data_atualizacao"]         = trim(pg_fetch_result($res,0,'data_atualizacao'));
        $_RESULT["troca_obrigatoria"]        = trim(pg_fetch_result($res,0,'troca_obrigatoria'));
        $_RESULT["produto_critico"]          = trim(pg_fetch_result($res,0,'produto_critico'));
        $_RESULT["intervencao_tecnica"]      = trim(pg_fetch_result($res,0,'intervencao_tecnica'));
        $_RESULT["qtd_etiqueta_os"]          = trim(pg_fetch_result($res,0,'qtd_etiqueta_os'));
        $_RESULT["lista_troca"]              = trim(pg_fetch_result($res,0,'lista_troca'));
        $_RESULT["marca"]                    = trim(pg_fetch_result($res,0,'marca'));
        $_RESULT["valor_troca"]              = trim(pg_fetch_result($res,0,'valor_troca'));
        $_RESULT["troca_garantia"]           = trim(pg_fetch_result($res,0,'troca_garantia'));
        $_RESULT["troca_faturada"]           = trim(pg_fetch_result($res,0,'troca_faturada'));
        $_RESULT["capacidade"]               = trim(pg_fetch_result($res,0,'capacidade'));
        $_RESULT["divisao"]                  = trim(pg_fetch_result($res,0,'divisao'));
        $_RESULT["observacao"]               = trim(pg_fetch_result($res,0,'observacao'));
        $_RESULT["link_img"]                 = trim(pg_fetch_result($res,0,'imagem'));
        $_RESULT["sistema_operacional"]      = trim(pg_fetch_result($res,0,'sistema_operacional'));
        $_RESULT["serie_inicial"]            = trim(pg_fetch_result($res,0,'serie_inicial'));
        $_RESULT["serie_final"]              = trim(pg_fetch_result($res,0,'serie_final'));
        $_RESULT["validar_serie"]            = trim(pg_fetch_result($res,0,'validar_serie'));
		$_RESULT["aux_classe"]               = trim(pg_fetch_result($res,0,'classe'));
        if ($login_fabrica == 42) {
            $_RESULT["entrega_tecnica"] = pg_fetch_result($res, 0, "entrega_tecnica");
        }
		// $serie_in                 = trim(pg_fetch_result($res,0,'serie_in'));
		// $serie_out                = trim(pg_fetch_result($res,0,'serie_out'));
    }

}



echo '<link rel="stylesheet" type="text/css" href="../css/ebano.css" media="screen">';
?>
<!--  -->

<!-- <script src="js/jquery-1.8.3.min.js"></script> -->
<script type="text/javascript"    src="js/thickbox.js"></script>
<script type="text/javascript"    src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript"    src="js/jquery.maskmoney.js"></script>
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>


<script language="JavaScript">

	$(function(){
            setupZoom();
			$('input[name=garantia]').numeric();
			$('input[name=garantia_horas]').numeric();
			$('input[name=mao_de_obra_admin]').numeric({ allow : ',.' });
			$('input[name=mao_de_obra]').numeric({ allow : ',.' });
            $('input[name=mao_de_obra_troca]').numeric({ allow : ',.' });
			$('input[name=qtd_etiqueta_os]').numeric();
            $('input[name=ipi]').numeric({ allow : ',.' });

			$('input[name=preco_pais]').numeric({ allow: ',.' });

			$('.versao').alpha();

            $("input[name=valor]").maskMoney({showSymbol:"", symbol:"", decimal:",", precision:2, thousands:".",maxlength:10});

		$('img.excluir[id^=prod]').click(function() {
			var foto       = $(this).prev().find('img');
			var imagem     = foto.attr('src').replace(/\?.*/, '');
			var referencia = $('input:text[name=referencia]').val();
			//console.log(imagem);

			if (confirm('Excluir a imagem do produto '+referencia+ "?\nATENÇÃO: Se você fez alguma alteração no cadastro do produto, não será salva. Se for o caso, grave primeiro e exclua a imagem após gravar.")) {
				$.get(
				window.location.pathname,
				{
					ajax:     'excluir',
					'imagem': imagem,
				},
				function(data) {
					alert(data);
					if (data.indexOf('com')>2)
						window.location.href=window.location.pathname+'?produto=<?=$produto?>';
					}
				)
			}
		});

        $("input[name^=radical_serie],input[name='classificacao_fiscal'],input[name='nome_comercial']").blur(function(){
            var valor = $(this).val();
            valor = valor.replace(/\'/g, "");
            valor = valor.replace(/\"/g,"");
            valor = valor.replace(/\\/g,"");
            $(this).val(valor);
        })

        $.autocompleteLoad(Array("produto"));
	});
    
    function gravaDados(){
        var servico         = $("input[name=servico]").val();
        var servico_id      = $("input[name=servico_id]").val();
        var produto_id      = $("input[name=produto_id]").val();
        var valor           = $("input[name=valor]").val();
	if(servico) {
		$.ajax({
		    url: "<?php echo $_SERVER['PHP_SELF']; ?>?ajax=sim&servico_id="+servico_id+"&produto="+produto_id+"&servico="+servico+"&valor="+valor,
		    cache: false,
		    success: function(data) {

			retorno = data.split('|');

			if (retorno[0]=="OK") {
                $("input[name=servico]").val("").html("");
                $("input[name=valor]").val("").html("");
			    $("#resultado").html(retorno[1]);
			    
			} else {
			    alert(retorno[0]);
			}
		    }
		});
	}else{
		alert('Serviço não preenchido');
	}
    }

    function excluiRegistro(servico,produto){
        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?ajax_exclui=sim&servico="+servico+"&produto="+produto,
            cache: false,
            success: function(data) {

                retorno = data.split('|');

                if (retorno[0]=="OK") {
                    $("#resultado").html(retorno[1]);
                    
                } else {
                    alert(retorno[0]);
                }
            }
        });
    }

    function carregaDados(servico,valor){
        $("input[name=servico]").val(servico);
        $("input[name=servico]").attr("readonly","readonly");

        $("input[name=servico_id]").val(servico);
        $("input[name=valor]").val(valor);

    }

    function checarNumero(campo) {
        var num = campo.value.replace(",",".");
        campo.value = parseFloat(num).toFixed(2);
        if (campo.value=='NaN') {
            campo.value='';
        }
    }

    function somenteMaiusculaSemAcento(obj) {
		com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
		sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';

		resultado='';

		for(i=0; i<obj.value.length; i++) {
			if (com_acento.indexOf(obj.value.substr(i,1))>=0) {
				resultado += sem_acento.substr(com_acento.indexOf(obj.value.substr(i,1)),1);
			}
			else {
				resultado += obj.value.substr(i,1);
			}
		}

		resultado = resultado.toUpperCase();

		re = /[^\w|\s]/g;
		obj.value = resultado.replace(re, "");
	}

    function checarNumeroInteiro(campo){
        campo.value = parseInt(campo.value);
        if (campo.value=='NaN') {
            campo.value='';
        }
    }



    function fnc_pesquisa_produto (campo, tipo) {

        if (campo.value != "") {
            var url = "";

            url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=0, left=0");
            janela.retorno = "<? echo $PHP_SELF ?>";
            janela.referencia= document.frm_produto.referencia;
            janela.descricao = document.frm_produto.descricao;
            janela.linha     = document.frm_produto.linha;
            janela.familia   = document.frm_produto.familia;
            janela.focus();
        }else
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
    }

    function qtdeLinhas(campo) {
        var linha = 0;
        if (campo.value > 0){
            $(".tabela_item tr").each( function (){
                linha = parseInt( $(this).attr("rel") );
                if (linha  +1 > campo.value) {
                    $(this).css('display','none');
                }else{
                    $(this).css('display','');
                }
            });
        }
    }

    function fnc_pesquisa_produto_opcao (campo, campo2, campo3, campo4, tipo) {
        if (tipo == "referencia" ) {
            var xcampo = campo;
        }

        if (tipo == "descricao" ) {
            var xcampo = campo2;
        }

        if (xcampo.value != "") {
            var url = "";
            url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=sim" ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
            janela.referencia    = campo;
            janela.descricao    = campo2;
            janela.produto        = campo3;
            janela.voltagem        = campo4;
            janela.focus();
        }

		else
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
    }

	function validaForm(){
		<?php if (in_array($login_fabrica,$fabricaIntervaloSerie)):?>

    			if(validaSerie()){
                    return true;
    				// document.frm_produto.btnacao.value='gravar' ;
    				// document.frm_produto.submit();
    			}

		<?php else:?>
            return true;
			// document.frm_produto.btnacao.value='gravar' ;
			// document.frm_produto.submit();
		<?php endif;?>
	}

	<?php if(in_array($login_fabrica,$fabricaIntervaloSerie)):?>
		var totals = 0;
		function adiciona(){
			totals++;
			tbl = document.getElementById("table_serie_in_out");

            var linha = parseInt($('#qtde_itens').val());
            

			var novaLinha = tbl.insertRow(-1);

			var novaCelula;
			if(totals%2==0) cl = "#F7F5F0";
			else cl = "#F1F4FA";

            <?php if ($login_fabrica == 15) { ?>
                var maxLength = 10;
            <?
            } else { ?>
                var maxLength = 30;
            <?
            }
             ?>

			novaCelula = novaLinha.insertCell(0);
			novaCelula.align = "center";
			novaCelula.height= "25px";
			novaCelula.style.backgroundColor = cl
			novaCelula.innerHTML = "<input type='hidden' name='produto_serie_in_out_"+linha+"'><input class='frm serie' rel='"+linha+"' id='serie_in_"+linha+"' type='text' name='serie_in_"+linha+"' size='15' maxlength='"+maxLength+"' onKeyUp='somenteMaiusculaSemAcento(this);'>";

			novaCelula = novaLinha.insertCell(1);
			novaCelula.align = "center";
			novaCelula.height= "25px";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "<input class='frm serie' rel='"+linha+"' id='serie_out_"+linha+"' type='text' name='serie_out_"+linha+"' size='15' maxlength='"+maxLength+"' onKeyUp='somenteMaiusculaSemAcento(this);'>";

			novaCelula = novaLinha.insertCell(2);
			novaCelula.align = "center";
			novaCelula.height= "25px";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "&nbsp;";

            linha++;
            $('#qtde_itens').val(linha);

		}

		function indiceAlfabeto(letra){
			var str = "AB";//CDEFGHIJKLMNOPQRSTUVWXYZ";
			var total = str.length;
			for(var i=0; i<total; i++){
				if(str.charAt(i) == letra){
					return i;
					break;
				}
			}
		}

		<?if ($login_fabrica == 15){?>
			//Verifica se o numero de série corresponde
			//A Expressão Regular para o número de Série da Latinatec corresponde aos dados
			//1 => Fábricante: pode ser 1, 4 ou 9
			//2 => Versão: letra
			//3 => Mês: letra: A = JAN; B = FEV; C = MAR...
			//4 => Ano: letra: A = 1995; B = 1995; ... Q = 2011
			//5 ao 8 => Sequencial numérico, sempre maior que 1000

			function validaSerie(){

				//Retorno já verdadeiro, caso não tenha uma série correta, retorna falso
				var retorno = true;
				var sa_in = ""; //Serie anterior de entrada
				var sa_out = ""; //Serie anterior de saída
				var s_in = ""; //Serie de entrada
				var s_out = ""; //Serie de saída
				var ident; //id da linha
                var serie = new RegExp();

				var serie_inicial = $("#serie_inicial").val();
				var serie_final = $("#serie_final").val();


                //Percorre todas as séries pra ver se estão válidas
                $('.serie').each(function(){

                    ident = $(this).attr('rel');
                    s_in = $('#serie_in_'+ident).val();
                    s_out = $('#serie_out_'+ident).val();

                    if (s_in.substr(0,1) == 1 || s_in.substr(0,1) == 4){
                        serie = new RegExp('[14]['+serie_inicial+'-'+serie_final+'][A-L][A-Z][1-9][0-9]{3,5}');
                    }else if(s_in.substr(0,1) == 9){
        				serie = new RegExp('[9]['+serie_inicial+'-'+serie_final+'][A-L][A-Z][1-9][0-9]{2,5}');
                    }

                    if (s_out.substr(0,1) == 1 || s_out.substr(0,1) == 4){
                        serie = new RegExp('[14]['+serie_inicial+'-'+serie_final+'][A-L][A-Z][1-9][0-9]{3,5}');
                    }else if(s_out.substr(0,1) == 9){
                        serie = new RegExp('[9]['+serie_inicial+'-'+serie_final+'][A-L][A-Z][1-9][0-9]{2,5}');
                    }


                    //Recebe em formato de RegEx o filtro de series
					if(s_in.length > 0 && s_out.length > 0 && !$('#apagar_serie_'+ident).is(':checked')){
						if(s_out.length == 0){

							if (!$('#serie_in_'+ident).val().match(serie)){
								retorno = false;
								return false;
							}

						}else if(s_in.substr(1,1) == s_out.substr(1,1)){

							if (!$(this).val().match(serie)){
								retorno = false;
								return false;
							}

						}else{
                            if (s_in.substr(0,1) != '9' && s_out.substr(0,1) != '9'){

                                retorno = false;
                                return false;

                            } 
						}

                        if(s_in.substr(0,1) == s_out.substr(0,1)){

                            if (!$(this).val().match(serie)){
                                retorno = false;
                                return false;
                            }

                        }else{
                            retorno = false;
                            return false;
                        } 

					}

					sa_in = s_in;
					sa_out = s_out;

				});

				if(retorno == false){
					$('#msg_erro').css('display','');
					$('.sucesso').css('display','none');
					$('#msg_erro').html('<td colspan="6">Número de série inválido</td>');
					$(document).scrollTop('slow');
				}
				return retorno;

			}


		<?
		}


		if ($login_fabrica == 45){?>

			// HD 410420
			// NUMERO DE SÉRIE NKS
			// 1º => Linha (será composto por dois dígitos EX: PC)
			// 2° => Modelo (será composto por quatro dígitos numéricos)
			// 3º => Voltagem do produto (será composto por uma letra)
			// 4º => fabricante (composto por uma letra)
			// 5º => Ano de fabricação (será composto por uma letra, que representará o ano)
			// 6º => lote do fabricante (composto por dois dígitos EX 01;02...)
			// 7º => Reservado para informação de montagem (nacional ou importado)
			// 8º => Seqüência numérica iniciando sempre do 00001(será composto por cinco ou seis dígitos)


			// PC2303CAA01X00001
			// 1) PC-
			// 2) 2303 -
			// 3) C
			// 4) A
			// 5) A
			// 6) 01
			// 7) X
			// 8) 00001


			function validaSerie(){

				//Retorno já verdadeiro, caso não tenha uma série correta, retorna falso
				var retorno = true;

				var s_in = ""; //Serie de entrada
				var s_out = ""; //Serie de saída
				var ident; //id da linha
				var codigo_serie;

				var codigo_familia = $('#codigo_validacao_serie').val();
				var serie_inicial = $("#serie_inicial").val();
				var serie_final = $("#serie_final").val();


				//Recebe em formato de RegEx o filtro de series
				var serie = new RegExp('[A-Z]{2}[0-9]{4}[A-C][A-Z][A-Z][0-9]{2}[A-Z][0-9]{5,6}$');

				//Percorre todas as séries pra ver se estão válidas
				$('.serie').each(function(){

					ident = $(this).attr('rel');
					s_in = $('#serie_in_'+ident).val();
					s_out = $('#serie_out_'+ident).val();

					if(s_in.length > 0 && !$('#apagar_serie_'+ident).is(':checked')){

						codigo_serie = s_in.substring(0,2);

						if(codigo_familia.indexOf(codigo_serie) == -1){
							retorno = false;
							return false;
						}

						if(s_in.substr(0,11) == s_out.substr(0,11) && retorno != false){

							if (!$(this).val().match(serie)){
								retorno = false;
								return false;
							}else{

								t_pos5_s_in = (s_in.substr(8,1).charCodeAt(0) + 1944);
								t_pos5_s_out = (s_in.substr(8,1).charCodeAt(0) + 1944);
								hoje = new Date();
								ano = hoje.getFullYear();
								if (t_pos5_s_in > ano || t_pos5_s_out > ano){
									retorno = false;
									return false;
								}

								//validar NUMERO FINAL < INICIAL

								t_pos8_s_in = (s_in.substr(12,5));
								t_pos8_s_out = (s_out.substr(12,5));
								if (t_pos8_s_in > t_pos8_s_out){
									retorno = false;
									return false;
								}

							}

						}else{
							retorno = false;
							return false;
						}
					}



				});

				if(retorno == false){
					$('#msg_erro').css('display','');
					$('.sucesso').css('display','none');
					$('#msg_erro').html('<td colspan="6">Número de série inválido</td>');
					$('body').animate({scrollTop:0});
				}

				return retorno;

			}

			function changeTypeCode(familia){
				var valor = $(familia).val();
				var codigo = $( "select[name='familia'] > option:selected" ).attr("rel");
				$('#codigo_validacao_serie').val(codigo);
			}
	<?php
		}

	endif;?>

	//HD 325481
	function inserirLinhaSerie() {

		var total  = parseInt(document.getElementById('total_mascara').value);
		// var cor    = (total % 2) ? '#F7F5F0' : '#F1F4FA';
		var td1    = document.createElement('td');
		var td2    = document.createElement('td');
		var tr     = document.createElement('tr');
		var input1 = document.createElement('input');
		var input2 = document.createElement('input');

		input1.setAttribute('type', 'text');
		input1.setAttribute('name', 'mascara_'+total);
		input1.setAttribute('id', 'mascara_'+total);
		input1.setAttribute('size', '60');

		input2.setAttribute('type', 'button');
        input2.setAttribute('class', 'btn btn-danger');
		input2.setAttribute('name', 'btn_excluir_'+total);
		input2.setAttribute('id', 'btn_excluir_'+total);
		input2.setAttribute('value', 'Excluir');

		// tr.setAttribute('bgColor', cor);
		tr.setAttribute('align', 'center');
		tr.setAttribute('id', 'mascara_serie_' + total);

		td1.appendChild(input1);
		td2.appendChild(input2);

		tr.appendChild(td1);
		tr.appendChild(td2);

		document.getElementById('tabela_mascara').appendChild(tr);

		$('#mascara_'+total).keypress(function(event) {
			return validaMascaraSerie(event);
		});

		$('#mascara_'+total).keyup(function() {
			this.value = this.value.toUpperCase();
		});


		$('#btn_excluir_'+total).click(function() {
			excluirMascara(total);
		});

		document.getElementById('total_mascara').value = total + 1;

	}

	function validaMascaraSerie(e) {

		var tecla = e.keyCode ? e.keyCode : e.which ? e.which : e.charCode;
		var str   = String.fromCharCode(tecla);
		var reg   = new RegExp('[LlNn]');

		//{"8" : "BACKSPACE", "9" : "TAB", "35" : "END", "36" : "HOME", "37" : "<-", "39" : "->", "46" : "DEL"}
		if (tecla == 8 || tecla == 9 || tecla == 35 || tecla == 36 || tecla == 37 || tecla == 39 || tecla == 46) return true;

		return reg.test(str);

	}

	function excluirMascara(id) {

		if (confirm('Deseja realmente Excluir esta Máscara?')) {

			$.ajax({
				url: 'ajax_mascara_serie.php',
				type: 'POST',
				data: {
					mascara : document.getElementById('mascara_'+id).value,
					produto : document.getElementById('produto').value
				},
				success: function(ret) {

					if (ret == 1) {
						document.getElementById('tabela_mascara').removeChild(document.getElementById('mascara_serie_'+id));
					} else {
						alert('Aconteceu um erro ao excluir o registro!');
					}

				}
			});

		}

	}

    function verificaDescricaoES(es, pt) {
        if (pt.length == 0 && es.length > 0) {
            $("input[name=descricao]").val(es);
        }
    }


</script>
<script language='JavaScript'>
    $(function (){ 
        $("#btnPopover").popover();  
        $("#btnPopover2").popover();

        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this), Array("produtoId", "posicao", "voltagemForm") );
        });
    });

    function retorna_produto(json){

        if(json.id !== undefined){
            window.location = "produto_cadastro.php?produto="+json.id;
        }

        if(json.posicao !== undefined){
            $("#referencia_opcao_"+json.posicao).val(json.referencia);
            $("#descricao_opcao_"+json.posicao).val(json.descricao);
            $("#voltagem_opcao_"+json.posicao).val(json.voltagemForm);
                
        }
    }
   
</script>

<?php $onsubmit = (in_array($login_fabrica,$fabricaIntervaloSerie)) ? 'onsubmit="return validaSerie()"' : null;

if ((count($msg_erro["msg"]) > 0) ) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<? if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) { ?>
    <div class="alert alert-success">
        <h4><? echo $msg; ?></h4>
    </div>
<? } ?>
<?
// Campos do Formulario
// labels que mudam conforme a fábrica
if ($login_fabrica == 14 or $login_fabrica == 66){
   $lbl_mao_de_obra = "ASTEC"; 
}else{
   $lbl_mao_de_obra = "Posto";
}
$label_mao_de_obra = "M. Obra ".$lbl_mao_de_obra;

if ($login_fabrica == 14 or $login_fabrica == 66){
    $lbl_mao_de_obra_admin = "LAI";
}else{
    $lbl_mao_de_obra_admin = "Admin";
}
$label_mao_de_obra_admin = "M.Obra ".$lbl_mao_de_obra_admin;


// Combos que são montados a partir do BD ou seus options se diferenciam por fábrica
/*
* LINHA
*/
$sql = "SELECT  *
        FROM    tbl_linha
        WHERE   tbl_linha.fabrica = $login_fabrica
        AND     tbl_linha.ativo = TRUE
        ORDER BY tbl_linha.nome;";
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
    $options_linha = array();
    for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
        $aux_linha = trim(pg_fetch_result($res,$x,linha));
        $aux_nome  = trim(pg_fetch_result($res,$x,nome));
        $options_linha[$aux_linha] = $aux_nome;
    }
}
/*
* Família
*/
$sql = "SELECT  *
        FROM    tbl_familia
        WHERE   tbl_familia.fabrica = $login_fabrica
        AND     tbl_familia.ativo = TRUE
        ORDER BY tbl_familia.descricao;";
    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        $options_familia = array();
        for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
            $aux_familia        = trim(pg_fetch_result($res,$x,familia));
            $aux_descricao      = trim(pg_fetch_result($res,$x,descricao));
            $aux_codigo         = trim(pg_fetch_result($res,$x,codigo_validacao_serie));
            $aux_codigo_familia = trim(pg_fetch_result($res,$x,codigo_familia));

            if ($_RESULT["familia"] == $aux_familia){
                $selected = 'selected="selected"';
                $codigo_selecionado = (empty($aux_codigo)) ? $aux_codigo_familia : $aux_codigo;
                $_RESULT["codigo_validacao_serie"] = $aux_codigo;
            }else{
                $selected = null;
            }

            $codigo_serie =  (empty($aux_codigo)) ? $aux_codigo_familia : $aux_codigo;
            

            if($login_fabrica == 45){
                $options_familia[$aux_familia]["label"] = $aux_descricao;
                $options_familia[$aux_familia]["extra"] = array("rel" => $aux_codigo);

            }else{
                $options_familia[$aux_familia] = $aux_descricao;    
            }
        }
    }

    if($login_fabrica == 45){
        $familia_extra = array("onchange" => "changeTypeCode(this)");
    }
/*
* Voltagem
*/
$options_voltagem = array();
$options_voltagem["12 V"] = "12 V";
if ($login_fabrica <> 1) {
    $options_voltagem["110 V"] = "110 V";
}
$options_voltagem["127 V"] = "127 V";
$options_voltagem["220 V"] = "220 V";
$options_voltagem["230 V"] = "230 V";
$options_voltagem["Bivolt"] = "Bivolt";
$options_voltagem["Bivolt Aut"] = "Bivolt Aut";
$options_voltagem["Bateria"] = "Bateria";
$options_voltagem["Pilha"] = "Pilha";
if($login_fabrica == 15) { // HD 75711
    $options_voltagem["Full Range"] = "Full Range";
}
if($login_fabrica == 1) { // HD 75711
    $options_voltagem["SEM"] = "SEM";   
}
$hiddens = array(
   "produto"
);
if($login_fabrica == 20){
    $hiddens["idioma_novo"] = array("value" => "ES");//fabrica 20
}
if($login_fabrica == 3){
    $hiddens["idioma_novo"] = array("value" => "EN");
    $hiddens["idioma"]      = array("value" => "EN");
    
}
if($login_fabrica == 3 || $login_fabrica > 80){
    $hiddens["MAX_FILE_SIZE"] = array("value" => 1048576);
}
if($login_fabrica == 1){
    $hiddens[] = "qtde_itens";
}
if($inf_valores_adicionais){
    $hiddens[] = "servico_id";
    $hiddens["produto_id"] = array("value" => $produto);
}

$inputs = array(
    
    "referencia" => array(
        "id" => "produto_referencia",
        "type" => "input/text",
        "label" => "Referência",
        "span" => 4,
        "width" =>6,
        "maxlength" => 20,
        "lupa" => array(
            "name" => "lupa",
            "tipo" => "produto",
            "parametro" => "referencia",
            "extra" => array(
                "produtoId" => "true"
            )
        ),
        "required" => true
    ),


    "descricao" => array(
        "id" => "produto_descricao",
        "type" => "input/text",
        "label" => "Descrição",
        "span" => 4,
        "maxlength" => 80,
        "lupa" => array(
            "name" => "lupa",
            "tipo" => "produto",
            "parametro" => "descricao",
            "extra" => array(
                "produtoId" => "true"
            )
        ),
        "required" => true
    ),

    "garantia" => array(
        "type" => "input/text",
        "label" => "Garantia",
        "span" => 3,
        "inptc" => 6,
        "maxlength" => 2,
        "required" => true
    ),
    "mao_de_obra" => array(
        "type" => "input/text",
        "label" => $label_mao_de_obra,
        "span" => 3,
        "inptc" => 8,
        "maxlength" => 14,
        "required" => true,
        "extra" => array(
            "price" => "true"
        )
    ),
    "mao_de_obra_admin" => array(
        "type" => "input/text",
        "label" => $label_mao_de_obra_admin,
        "span" => 2,
        "inptc" => 8,
        "maxlength" => 14,
        "extra" => array(
            "price" => "true"
        )
    ),
    "linha" => array(
        "type" => "select",
        "span" => 4,
        "width" => 10,  
        "label" => "Linha",
        "options" => $options_linha,
        "required" =>true
    ),
    "familia" => array(
        "type" => "select",
        "label" => "Família",
        "span" => 4,
        "width" => 10,
        "extra" =>$familia_extra,
        "options" => $options_familia,
        "required" =>true
    ),
    "origem" => array(
        "type" => "select",
        "label" => "Origem",
        "span" => 2,
        "width" => 12,
        "options" => array(
            "Nac" => "Nacional",
            "Imp" => "Importado",
            "USA" => "Importado USA",
            "Asi" => "Importado Asia"
        )
    ),
    "voltagem" => array(
        "type" => "select",
        "label" => "Voltagem",
        "span" => 2,
        "width" => 12,
        "options" => $options_voltagem
    ),
    "ativo" => array(
        "type" => "select",
        "label" => "Status Rede",
        "span" => 2,
        "width" => 12,
        "options" => array(
            "t" => "Ativo",
            "f" => "Inativo"
        ),
        "required" => true
    ),
    "uso_interno_ativo" => array(
        "type" => "select",
        "label" => "Status Uso Interno",
        "span" => 2,
        "width" => 12,
        "options" => array(
            "t" => "Ativo",
            "f" => "Inativo"
        ),
        "popover" => array(
            "id" => "btnPopover",
            "msg"   => "Produto visível somente pelo Posto Interno, apesar de inativo para a rede!" 
        ),
        "required" => true
    ),
    "nome_comercial" => array(
        "type" => "input/text",
        "label" => "Nome Comercial",
        "span" => 4,
        "maxlength" => 20 
    ),
    "classificacao_fiscal" => array(
        "type" => "input/text",
        "label" => "Classificação Fiscal",
        "span" => 2,
        "width" => 12,
        "maxlength" => 20
    ),
    "ipi" => array(
        "type" => "input/text",
        "label" => "I.P.I.",
        "span" => 1,
        "inptc" => 6,
        "maxlength" => 3
    )
    
);

if($login_fabrica == 96){
    $inputs["modelo"] = array(
        "type" => "input/text",
        "label" => "Modelo",
        "span" => 4,
        "width" => 7,
        "maxlength" => 20,
        "lupa" => array(
            "name" => "lupa",
            "tipo" => "produto",
            "parametro" => "referencia",
            "extra" => array(
                "modelo" => "true"
            )
        )
    );
}


if($login_fabrica == 19 or $login_fabrica == 96){
    $inputs["preco"] = array(
        "type" => "input/text",
        "label" => "Preço",
        "span" => 4,
        "inptc" => 7,
        "maxlength" => 14,
        "extra" => array(
            "price" => "true"
        )
    );
}
if($login_fabrica == 87){
   $inputs["garantia_horas"] = array(
        "type" => "input/text",
        "label" => "Garantia Horas",
        "span" => 4,
        "inptc" => 3,
        "maxlength" => 14
        
    ); 
}

if($login_fabrica == 3){
    $inputs["valor_troca_gas"] = array(
        "type" => "input/text",
        "label" => "Valor Recarga de Gás",
        "span" => 4,
        "width" => 6,
        "maxlength" => 14,
        "extra" => array(
            "price" => "true"
        )
    );

    $inputs["produto_fornecedor"] = array(
        "type" => "select",
        "label" => "Fornecedor do Produto",
        "span" => 4,
        "width" => 6,
        "options" => array()
    );
    $sql = "SELECT  *
            FROM    tbl_produto_fornecedor
            WHERE   tbl_produto_fornecedor.fabrica = $login_fabrica
            ORDER BY tbl_produto_fornecedor.nome;";
    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
            $aux_produto_fornecedor = trim(pg_fetch_result($res,$x,produto_fornecedor));
            $aux_nome               = trim(pg_fetch_result($res,$x,nome));
            $inputs["produto_fornecedor"]["options"][$aux_produto_fornecedor] = $aux_nome; 
        }
    }

    if(strlen($produto)>0){
        $msgDescFornecedor = "";
        $sql = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto";
        $res2 = pg_query ($con,$sql);

        if (pg_num_rows($res2) > 0) {
            $produto                  = trim(pg_fetch_result($res2,0,produto));
            $idioma                   = trim(pg_fetch_result($res2,0,idioma));
            $descricao_idioma         = trim(pg_fetch_result($res2,0,descricao));
        }else{
            $msgDescFornecedor = "Não existe descrição para esse produto do fornecedor, preencha o campo abaixo para inserir uma.";
        }

        $inputs["descricao_idioma"] = array(
            "type" => "input/text",
            "label" => "Descrição do Fornecedor:",
            "span" => 4,
            "inptc" => 3,
            "maxlength" => 20
            
        );

        if(strlen($msgDescFornecedor) > 0){
            $inputs["descricao_idioma"]["popover"] = array(
                "id" => "descIdioma",
                "msg" => $msgDescFornecedor
            );
        }
    }

    $inputs["radical_serie"] = array(
            "type" => "input/text",
            "label" => "Radical N. Série 1",
            "span" => 2,
            "width" => 12,
            "maxlength" => 10
            
        );
    $inputs["radical_serie2"] = array(
            "type" => "input/text",
            "label" => "Radical N. Série 2",
            "span" => 2,
            "width" => 12,
            "maxlength" => 10
            
        );
    $inputs["radical_serie3"] = array(
            "type" => "input/text",
            "label" => "Radical N. Série 3",
            "span" => 2,
            "width" => 12,
            "maxlength" => 10
            
        );
    $inputs["radical_serie4"] = array(
            "type" => "input/text",
            "label" => "Radical N. Série 4",
            "span" => 2,
            "width" => 12,
            "maxlength" => 10
            
        );
    $inputs["radical_serie5"] = array(
            "type" => "input/text",
            "label" => "Radical N. Série 5",
            "span" => 2,
            "width" => 12,
            "maxlength" => 10
            
        );
    $inputs["radical_serie6"] = array(
            "type" => "input/text",
            "label" => "Radical N. Série 6",
            "span" => 2,
            "width" => 12,
            "maxlength" => 10
            
        );

}

if($login_fabrica == 5){
    $inputs["link_img"] = array(
            "type" => "input/text",
            "label" => "Link para imagem",
            "title" => "O caminho deve ser digitado por completo",
            "span" => 4,
            "inptc" => 7,
            "maxlength" => 255
            
        );    
}
if($login_fabrica == 20){
    $inputs["observacao"] = array(
            "type" => "textarea",
            "label" => "Comentário",
            "span" => 4,
            "cols" => 90,
            "cols" => 4
            
        );    
}
if($login_fabrica == 45){
    $inputs["codigo_validacao_serie"] = array(
            "type" => "input/text",
            "label" => "Código Família",
            "span" => 4,
            "width" => 6,
            "maxlength" => 20,
            "readonly" =>true
            
        ); 
}

if($login_fabrica == 7){
    $inputs["capacidade"] = array(
            "type" => "input/text",
            "label" => "Capacidade",
            "span" => 4,
            "inptc" => 6,
            "maxlength" => 9
            
            
        );    
    $inputs["divisao"] = array(
            "type" => "input/text",
            "label" => "Divisão",
            "span" => 4,
            "inptc" => 6,
            "maxlength" => 20            
        );
}

if($login_fabrica == 1){
    $inputs["code_convention"] = array(
            "type" => "input/text",
            "label" => "Code Convention",
            "span" => 4,
            "width" => 5,
            "maxlength" => 20            
        );

    $inputs["valor_troca"] = array(
            "type" => "input/text",
            "label" => "Troca Faturada (valor)",
            "span" => 4,
            "width" => 6,
            "maxlength" => 14,
            "extra" => array(
                'onblur' => "checarNumero(this)",
                "price" => "true"
            )
        );

  
}
if($login_fabrica == 43){
    $inputs["sistema_operacional"] = array(
        "type" => "select",
        "span" => 4,
        "width" => 10,
        "label" => "Sistema Operacional",
        "options" => array(
            "1" => "Windows",
            "2" => "Linux",
            "3" => "Apple"
        )
    );
}
if($login_fabrica == 42){
    $inputs["classe_produto"] = array(
        "type" => "select",
        "label" => "Classe",
        "span" => 4,
        "width" => 6,
        "options" => array()
    );

    $sql = "SELECT classe, nome FROM tbl_classe WHERE fabrica = $login_fabrica ORDER BY nome";
    $res = pg_query($con,$sql);
    if(pg_numrows($res) > 0){
        for($x = 0; $x < pg_numrows($res); $x++){
            $classe      = pg_result($res,$x,'classe');
            $nome_classe = pg_result($res,$x,'nome');
            $inputs["classe_produto"]["options"][$classe] = $nome_classe;
        }
    }
}

if($login_fabrica == 15){
     $inputs["serie_inicial"] = array(
            "type" => "input/text",
            "label" => "Versão Inicial",
            "class" => "versao",
            "span" => 4,
            "width"=>6,
            "maxlength" => 20
        );

     $inputs["serie_final"] = array(
            "type" => "input/text",
            "label" => "Versão Final",
            "class" => "versao",
            "span" =>4,
            "width"=>6,
            "maxlength" => 20
        );
}
if ($login_fabrica != 3 && $login_fabrica != 86) {
    $inputs["radical_serie"] = array(
            "type" => "input/text",
            "label" => "Radical Série",
            "span" => 4,
            "width" => 10,
            "maxlength" => 10
            
        );    
}
if($login_fabrica == 35 || $login_fabrica == 72){
    if ($login_fabrica == 14 or $login_fabrica == 66){
        $lbl = "LAI"; 
    }else{
        $lbl = "Admin";
    }
       $inputs["radical_serie"] = array(
            "type" => "input/text",
            "label" => "M. Obra Troca ".$lbl,
            "span" => 4,
            "width" => 6,
            "maxlength" => 14,
            "extra" => array(
                "price" => "true"
            )
        );
}
if(($login_fabrica ==3 or $login_fabrica == 30 or $login_fabrica >80)  and  !in_array($login_fabrica,array(122,114,124,))){
   $inputs["marca"] = array(
        "type" => "select",
        "label" => "Marca",
        "span" => 4,
        "width" => 12,
        "options" => array()
    );

    $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica and visivel is true order by nome";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        for($i=0;pg_num_rows($res)>$i;$i++){
            $xmarca = pg_fetch_result($res,$i,marca);
            $xnome  = pg_fetch_result($res,$i,nome);

            $inputs["marca"]["options"][$xmarca] = $xnome;
        }
    }
}

if(in_array($login_fabrica,array(1,20,24,80,96))){
    if ($login_fabrica == 96) { 
        $lbl = "Nome Comercial";

     } else if($login_fabrica == 1 || $login_fabrica == 80){
        $lbl = "Referência Interna";

     } else if($login_fabrica == 20){
        $lbl = "Bar Tool";

     }else if($login_fabrica == 24){
        $lbl = "Referência Única";

     } 
    $inputs["referencia_fabrica"] = array(
            "type" => "input/text",
            "label" => $lbl,
            "span" => 4,
            "width" => 6,
            "maxlength" => 20,
            "required" => true
        );
}
if(!in_array($login_fabrica,array(122,81,114,124,123))){
    $inputs["qtd_etiqueta_os"] = array(
            "type" => "input/text",
            "label" => "Qtd Etiqueta",
            "span" => 4,
            "inptc" => 7,
            "maxlength" => 3,
            "popover" => array(
                "id" => "btnPopover2",
                "msg" => "Quantidade de etiquetas a serem impressas na Ordem de Serviço caso seja maior que 5 etiquetas."
            )
        );
}
if($login_fabrica == 14 or $login_fabrica == 66 or $login_fabrica == 11){
    $inputs["abre_os"] = array(
        "label"    => "Permitido Abrir OS",
        "span" =>4,
        "type"     => "radio",
        "radios"  => array(
            "t" => "Sim",
            "f" => "Não"
        ),
        "required" =>true
    );
}
if($login_fabrica == 14 or $login_fabrica == 66){
    $inputs["aviso_email"] = array(
        "label"    => "Receber E-mail",
        "span" => 4,
        "type"     => "radio",
        "radios"  => array(
            "t" => "Sim",
            "f" => "Não"
        )
    );
}
//checkbox
$arrCheckbox = array("lista_troca" => array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Lista Troca"
        )
    ),
    "numero_serie_obrigatorio" => array(
        "type" => "checkbox",
        "span" => 3,
        "title" =>"Nº Série Obrigatório",
        "checks" => array(
            "t" => "Nº Série Obrigatório"
        )
    ),
    "produto_principal" => array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Produto Principal"
        )
    ),
    "troca_obrigatoria" => array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Troca Obrigatória"
        )
    )
);

if($login_fabrica==35){
    $arrCheckbox["produto_critico"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Produto Crítico"
        )
    );
}

if($login_fabrica == 1){

    $arrCheckbox["locador"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Locador"
            )
        );

    $arrCheckbox["troca_garantia"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Troca Garantia"
        )
    );
    $arrCheckbox["troca_faturada"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Troca Faturada"
        )
    );
}

if($login_fabrica == 42){
    $arrCheckbox["entrega_tecnica"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Entrega Técnica"
        )
        );
}

if($login_fabrica == 3 or $login_fabrica==11 or $login_fabrica==14 or $login_fabrica==86){
    $arrCheckbox["intervencao_tecnica"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Intervenção Técnica"
        )
    );
} 

if($login_fabrica == 3 || $login_fabrica == 45){
    $arrCheckbox["validar_serie"] =  array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Validar Série"
        )
    );
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_produto" method="post" enctype='multipart/form-data' action="<? $PHP_SELF ?>" <?php echo $onsubmit;?> class='form-search form-inline tc_formulario'>
<?if(strlen($produto) > 0){
    ?><div class="titulo_tabela">Alterando cadastro</div><?
}else{
    ?><div class="titulo_tabela">Cadastro</div><?
}?>

        <br/>
<?
    echo montaForm($inputs, $hiddens); ?>
    
    
     <? echo montaForm($arrCheckbox, null);?>
    
     <?//hd 21461
    if ($login_fabrica == 1) {
        $qtde_item = 100;
        $qtde_item_visiveis = 5;
        $qtde_linhas = 0;


        if (strlen($produto) > 0) {
            $sql = "SELECT  tbl_produto.referencia                     ,
                            tbl_produto.descricao                      ,
                            tbl_produto.produto                        ,
                            tbl_produto_troca_opcao.produto_troca_opcao,
                            tbl_produto.voltagem                       ,
                            tbl_produto_troca_opcao.kit
                    FROM tbl_produto
                    JOIN tbl_produto_troca_opcao ON tbl_produto.produto = tbl_produto_troca_opcao.produto_opcao
                    AND  tbl_produto_troca_opcao.produto = $produto
                    ORDER by tbl_produto_troca_opcao.kit, tbl_produto.descricao, tbl_produto.voltagem";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $qtde_linhas = pg_num_rows($res);
            }
        }?>

        <input type='hidden' name='qtde_item' value="<? echo $qtde_item; ?>">

        <br><br>
        <table class='tabela_item table table-striped table-bordered table-hover table-fixed'>
            <thead>
                <tr class='titulo_tabela'>
                    <td colspan='5' class="tac">
                    <b>Pode ser trocado por:</b>
                    </td>
                </tr>
            </thead>
            <!-- //HD #145639 - INSTRUÇÕES -->
            <tr>
                <td colspan='5'>
                <b>KITs:</b> podem ser criados KITs para trocar um produto por vários. Para isto, selecione na coluna KIT o mesmo número para agrupar vários produtos. No momento da troca os produtos com o mesmo número de KIT serão agrupados para seleção.
                </td>
            </tr>
            
            <tr>
                
                <td colspan='5' >
                    <div class="control-group pull-right ">
                        <label>Qtde linhas</label>
                            <div class="controls controls-row ">
                                <select onChange='qtdeLinhas(this)' class='span2'>
                                    <option value='5'>5 Linhas</option>
                                    <option value='10'>10 Linhas</option>
                                    <option value='15'>15 Linhas</option>
                                    <option value='30'>30 Linhas</option>
                                    <option value='50'>50 Linhas</option>
                                    <option value='100'>100 Linhas</option>
                                </select>      
                            </div>
                    </div>
                </td>
            </tr>
            <thead>
            <!-- //HD #145639 - NOME DAS COLUNAS -->
            <tr class='titulo_coluna'>
                <th style="width:2px;"></th>
                <th>Código Produto</th>
                <th>Nome Produto</th>
                <th>Voltagem</th>
                <th>KIT</th>
            </tr>
            </thead>

            <?for ($i=0; $i<$qtde_item; $i++) {
                $referencia_opcao    = "";
                $descricao_opcao     = "";
                $produto_opcao       = "";
                $produto_troca_opcao = "";
                $voltagem_opcao = "";

                if ($i<$qtde_linhas){
                    $referencia_opcao    = pg_fetch_result($res,$i,referencia);
                    $descricao_opcao     = pg_fetch_result($res,$i,descricao);
                    $produto_opcao       = pg_fetch_result($res,$i,produto);
                    $produto_troca_opcao = pg_fetch_result($res,$i,produto_troca_opcao);
                    $voltagem_opcao      = pg_fetch_result($res,$i,voltagem);
                    $kit_opcao           = pg_fetch_result($res,$i,kit);

                    $erro_linha = "erro_linha" . $i;
                    $erro_linha = $$erro_linha;
                    $cor_erro = "#FFFFFF";
                    if ($erro_linha == 1) $cor_erro = "#FF9999";
                }else{ 
                    $kit_opcao = "";
                }

                $ocultar_linha = "";
                if ($i+1 > $qtde_item_visiveis and $i+1 > $qtde_linhas){
                    $ocultar_linha = " style='display:none' ";
                }?>

            <tr <? echo $ocultar_linha?> rel="<? echo $i;?>" bgcolor="<? echo $cor_erro;?>">
                <td style="text-align:center;"><input type='hidden' name="produto_opcao_<? echo $i; ?>" rel='produtos' value="<? echo $produto_opcao;?>">
                    <input type='hidden' name="produto_troca_opcao_<? echo $i;?>"    rel='produtos' value="<? echo $produto_troca_opcao;?>">
                    <input type='hidden' name="voltagem_troca_opcao_<? echo $i;?>"   rel='produtos' value=''> <? echo $i+1;?></td>
                    <td style="text-align:center;">
                        <div class="input-append">
                            <input class='span2' type='text' id="referencia_opcao_<? echo $i;?>" name="referencia_opcao_<? echo $i;?>" rel='produtos' maxlength='30' value="<? echo $referencia_opcao;?>" onchange="javascript: document.frm_produto.voltagem_opcao_<? echo $i;?>.value = '' ">
                            <span class="add-on" rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" posicao="<? echo $i;?>" voltagemForm="true" tipo="produto" parametro="referencia" />                             
                        </div>
                    </td>
                    <td style="text-align:center;">

                        <div class="input-append">
                            <input type='text' class="span4"  id="descricao_opcao_<? echo $i; ?>" name="descricao_opcao_<? echo $i; ?>" rel='produtos' maxlength='50' value="<? echo $descricao_opcao?>" onchange="javascript: document.frm_produto.voltagem_opcao_<? echo $i?>.value = '' ">
                            <span class="add-on" rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" posicao="<? echo $i;?>" voltagemForm="true" tipo="produto" parametro="descricao" />
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <input class='inptc6' type='text' id='voltagem_opcao_<? echo $i;?>' name='voltagem_opcao_<? echo $i;?>' rel='produtos' value="<? echo $voltagem_opcao?>" readonly>
                    </td>

<!--                    //HD #145639 - COLUNA DE KITS -->
                    <?
                        $n_kit_opcoes = 20;
                    ?>
                    
                    <td style="text-align:center;">
                        <select class='inptc9' name="kit_opcao_<? echo $i;?>" >
                        <option value='0'>--</option>

                    <?  

                        for($k = 1; $k <=$n_kit_opcoes; $k++) {
                            if ($kit_opcao == $k){
                                $selected = "selected";
                            }else{
                                $selected = "";
                            }?>
                        <option <?=$selected?>  value="<? echo $k; ?>" ><? echo $k?></option>
                    <? }?>

                    
                        </select>
                    </td>
                    
                </tr>
            <?}?>
        </table>
    <?}
     // HD 96953 - Adicionar imagem do produto para a Britânia
    if ($login_fabrica==3 or $login_fabrica>80) {

        $imagem_produto = $produto.'.jpg';
        if (strlen($imagem_produto)>0) {
            $imagem     = "imagens_produtos/$login_fabrica/pequena/$imagem_produto";
            $msg_imagem = "Anexar imagem do produto:";
            if (file_exists("../$imagem")) {
                $tag_imagem = "<a href='../".str_replace("pequena", "media", $imagem)."' ><img src='../$imagem?bypass=" . md5(mt_rand(100,999)) . "' title='Clique para ver a imagem' valign='middle' class='thickbox' height='60'></a>\n<img src='../imagens/excluir_loja.gif' class='excluir' id='prod$produto?>' style='cursor:pointer' alt='Excluir Imagem' title='Excluir imagem' />";
                        
                $msg_imagem = "Mudar imagem:";
            }

    ?>
    
        <div class="row-fluid">
            <div class="span2"></div>
                <div class="span4">
                    <div class="control-group">
                        <label><?=$msg_imagem?></label>
                        <div class="controls controls-row">
                            <input type='hidden' name='MAX_FILE_SIZE' value='1048576'><?=$tag_imagem?>
                            <input title='Selecione o arquivo com a foto do produto'
                        type='file' name='arquivo' size='18'
                        class="multi {accept:'jpg|gif|png', max:'1', STRING: {remove:'Remover',selected:'Selecionado: <?=$file?>', denied:'Tipo de arquivo inválido: <?=$ext?>!'}}">
                        </div>
                    </div>
                </div>
            <div class="span2"></div>
        </div>
      <?}
    }
     if ($login_fabrica == 3 && !empty($produto)) {//HD 325481?>        
    <br/>
        
            <table class="table table-striped table-bordered table-hover table-fixed">
            <thead>
                <tr>
                    <th class="titulo_tabela" colspan="3">Máscara</th>
                </tr>
            </thead>
            <tbody id="tabela_mascara">
            <?php

                $sql_mask = "SELECT * FROM tbl_produto_valida_serie WHERE fabrica = $login_fabrica AND produto = $produto;";
                $res_mask = pg_query($sql_mask);
                $tot_mask = pg_num_rows($res_mask);

                for ($i = 0; $i < $tot_mask; $i++) {?>
                    
                    <tr id="mascara_serie_<?echo $i; ?>">
                        <td><input type="text" name="mascara_<?echo $i; ?>" id="mascara_<? echo $i; ?>" value="<? echo pg_result($res_mask, $i, 'mascara')?>" readonly="readonly" /></td>
                        <td><input type="button" class="btn btn-danger" name="btn_excluir_<? echo $i; ?>" value="Excluir" onclick="excluirMascara(<? echo $i; ?>)" /></td>
                    </tr>
               <? }?>

            </tbody>
        </table>
  
         <div class="row-fluid">
            <!-- margem -->
            <div class="span2"></div>

            <div class="span8">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <input type="hidden" name="total_mascara" id="total_mascara" value="<?=$i?>" />
                        <input type="button" class="btn" name="btn_inserir" value="Inserir" onclick="inserirLinhaSerie()" />
                    </div>
                </div>
            </div>
            <!-- margem -->
            <div class="span2"></div>
        </div>
    <? }
    if($login_fabrica == 20){?>

        <table class='tabela_item table table-striped table-bordered table-hover table-fixed'>
                <thead>
                    <tr class='titulo_tabela'>
                        <td colspan='3' class="tac">
                        <b>Tradução de Produto</b>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <?
                        if (strlen($produto) > 0) {
                          $sql = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto";
                          $res2 = pg_query ($con,$sql);
                        }

                        if (pg_num_rows($res2) > 0) {
                            $produto                  = trim(pg_fetch_result($res2,0,produto));
                            $idioma                   = trim(pg_fetch_result($res2,0,idioma));
                            $descricao_idioma         = trim(pg_fetch_result($res2,0,descricao));
                        }else{?>
                            <tr>
                                <td colspan='3'>
                                    <b>Atenção</b>: Não existe descrição para esse produto em outro idioma, preencha o campo abaixo para inserir uma.<br>
                                    <input type='hidden' name='idioma_novo' value='ES'>
                                </td>
                            </tr>
                        <?}?>
                        <tr>
                            <td width="100px">
                                <div class="control-group">
                                    <label class="control-label"><b>Espanhol</b> <br/> Descrição:</label>
                                    <div class="controls controls-row">
                                        <input  type='text' name='descricao_idioma' value='<?=$descricao_idioma?>' maxlength='50' onblur='verificaDescricaoES($(this).val(), $("input[name=descricao]").val())'>
                                    </div>
                                </div>
                            </td>
                            <? if (strlen($produto) > 0) {
                                        $sql = "SELECT pais, garantia, valor FROM tbl_produto_pais WHERE produto = $produto";
                                        $res2 = pg_query ($con,$sql);
                          }
                        if (pg_num_rows($res2) > 0) {?>
                            <td width='500px'>
                                <div class="row-fluid">
                                    <div class="span12">
                                        <div class="control-group ">
                                            <div class="controls controls-row tac">
                                                <label class="label label-info">Máquina liberada para</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row-fluid">
                                    
                                    <div class="span3">
                                        <div class="control-group tac">
                                            <label class="control-label"> <b>País </b> </label>
                                        </div>
                                    </div>

                                    <div class="span3">
                                        <div class="control-group tac">
                                            <label class="control-label"><b>Garantia</b> </label>
                                        </div>
                                    </div>

                                    <div class="span3">
                                        <div class="control-group tac">
                                            <label class="control-label"><strong>Preço</strong> </label>
                                        </div>
                                    </div>

                                    <?if ($prod_libera_br or $prod_libera_outros) {?>
                                        <div class="span3">
                                            <div class="control-group tac">
                                                <label class="control-label"><strong><b>Ação</b></strong> </label>
                                            </div>
                                        </div>
                                     <?}?>
                                </div>
                                 

                             <? for($i = 0 ; $i < pg_num_rows($res2) ; $i++ ){
                                    $pais                  = trim(pg_fetch_result($res2,$i,pais));
                                    $garantia_pais         = trim(pg_fetch_result($res2,$i,garantia));
                                    $preco_pais = number_format(pg_fetch_result($res2, $i, 'valor'), 2, ',', '');
                            ?>
                                    <div class="row-fluid">
                                    
                                        <div class="span3">
                                            <div class="control-group tac">
                                                <label class="control-label"> <?=$pais?> </label>
                                            </div>
                                        </div>

                                        <div class="span3">
                                            <div class="control-group tac">
                                                <label class="control-label"><?=$garantia_pais?> </label>
                                            </div>
                                        </div>

                                        <div class="span3">
                                            <div class="control-group tac">
                                                <label class="control-label"><?=$preco_pais?> </label>
                                            </div>
                                        </div>
                                    
                                   
                                    <? //MLG 2011-05-06 - HD 374998
                                    if (($pais=='BR' and $prod_libera_br) or $prod_libera_outros){?>
                                        <div class="span3">
                                            <div class="control-group tac">
                                                <label class="control-label"> <a href="javascript: if (confirm('Deseja excluir?')) {window.location='<?="$PHP_SELF?produto=$produto&acao=a&pais=$pais"?>} ">Excluir</a> </label>
                                            </div>
                                        </div>
                                  <?}?>
                                    
                                    </div>
                              <?}?> 
                            </td>
                    <? } ?>
            <?//MLG 2011-05-06 - HD 374998
            if ($prod_libera_br or $prod_libera_outros) {?>
                         <td>

                         <?
                            $msg_libera = ($prod_libera_br) ? 'Liberar o produto para o' : 'Escolha o país';
                            $cond_libera= ($prod_libera_br) ? " WHERE pais = 'BR'" : '';
                         ?>
                            <div class="row-fluid">
                                <div class="span12">
                                    <div class="control-group">
                                        <label class="control-label"><b><?=$msg_libera?></b></label>
                                        <div class="controls controls-row">
                                            <select name='produto_pais' id='produto_pais'>
                                            <? $sql = "SELECT '<option value=\"'||pais||'\">'||nome||'</option>' AS opcao_pais
                                                        FROM tbl_pais $cond_libera
                                                        ORDER BY nome";
                                                $res = pg_query($con, $sql);
                                                if(pg_num_rows($res)>0){

                                                    for($x=0; $x<pg_num_rows($res); $x++){
                                                        echo pg_fetch_result($res, $x, 0);
                                                    }
                                                }?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row-fluid">
                                <div class="span12">
                                    <div class="control-group ">
                                        <label class="control-label"><b>Garantia do País</b></label>
                                        <div class="controls controls-row">    
                                            <input  type='text' class='frm' name='garantia_pais' value='' size='15' id='garantia_pais' maxlength='50'>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row-fluid">
                                <div class="span12">
                                    <div class="control-group ">
                                        <label class="control-label"><b>Preço</b></label>
                                        <div class="controls controls-row">
                                            <input type="text" class="inptc8"  name="preco_pais" value=""id="preco_pais" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row-fluid">
                                <div class="span12">
                                    <div class="control-group ">
                                        <div class="controls controls-row">    
                                            <input type='button'  name='btn_produto_pais' value = 'Atribui Pais'onclick="javascript:window.location='<?="$PHP_SELF?produto=$produto&acao=atribui&pais='+document.getElementById('produto_pais').value+'&garantia_pais='+document.getElementById('garantia_pais').value+'&preco_pais='+document.getElementById('preco_pais').value"?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                         </td>
          <?}?>
                        </tr>
                </tbody>            
        </table>
  
    <?}?>
    <input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
        <div class="row-fluid">
            <!-- margem -->
            <div class="span4"></div>

            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <? if (strlen($produto) > 0){
                            $onclick = "onclick=\"if (confirm('Você irá atualizar um produto! Confirma esta ação? Caso deseje apenas inserir um novo, cancele a operação, limpe as informações da tela e insira o produto!')) { if(validaForm()){ submitForm($(this).parents('form'),'gravar');} }\" ";
                        }else{
                            $onclick = "onclick=\"if (document.frm_produto.btn_acao.value == '' ) { if(validaForm()){ submitForm($(this).parents('form'),'gravar');} } else { alert ('Aguarde submissão'); } return false;\" ";
                        }?>

                        <button type="button" class="btn" value="Gravar" alt="Gravar formulário" <?php echo $onclick;?> > Gravar</button>

                        <? if (strlen($produto) > 0){?>
                            <button type="button" class="btn btn-warning" value="Limpar" onclick="javascript:  window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos">Limpar</button>
                        <?}?>
                    </div>
                </div>
            </div>

            <!-- margem -->
            <div class="span4"></div>
        </div>
</form>


<?php
if (strlen($produto) > 0) {
    if (strlen($admin) > 0 and strlen($data_atualizacao) > 0) { ?>

        <div class="alert">
            <strong>ÚLTIMA ATUALIZAÇÃO: </strong><?echo $admin ." - ". $data_atualizacao;?>
        </div>

 <? }
}?>

</div>
</div><?php
// SE FOR INTELBRÁS OU MAXCOM HD 40530
if (($login_fabrica == 14 or $login_fabrica == 66) and strlen($produto) > 0) {
    $sql = "SELECT  tbl_produto.produto   ,
                    tbl_produto.referencia,
                    tbl_produto.descricao
            FROM    tbl_subproduto
            JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_filho
            JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
            WHERE   tbl_linha.fabrica          = $login_fabrica
            AND     tbl_subproduto.produto_pai = $produto
            ORDER BY tbl_produto.descricao;";

    $res0 = @pg_query ($con,$sql);

    if (pg_num_rows($res0) > 0) {?>
        <div class="container">
            <table class="tabela_item table table-striped table-bordered table-hover">
                <thead>
                    <tr align='center'>
                        <th colspan="3" class="titulo_tabela">PRODUTOS / SUBPRODUTOS RELACIONADOS</th>
                    </tr>
                    <tr class="titulo_coluna" align='center'>
                        <th >REFERÊNCIA</th>
                        <th >DESCRIÇÃO</th>
                        <th >LISTA BÁSICA</th>
                    </tr>
                </thead>
                <tbody>
                <? for ($y = 0; $y < @pg_num_rows($res0); $y++) {

                    $produto    = trim(pg_fetch_result($res0,$y,produto));
                    $referencia = trim(pg_fetch_result($res0,$y,referencia));
                    $descricao  = trim(pg_fetch_result($res0,$y,descricao));?>

                    <tr>
                        <td width='15%' align='center' nowrap><a href='$PHP_SELF?produto=$produto'><?=$referencia?></a></td>
                        <td width='85%' align='left' nowrap><a href='$PHP_SELF?produto=$produto'><?=$descricao?></a></td>
                        <td width='85%' align='center' nowrap><a href="lbm_cadastro.php?produto=<?=$produto?>" target='_blank'><img src='imagens/btn_lista.gif' border=0/></a></td>
                    </tr>
             <? } ?> 
                </tbody>
            </table>
        </div>
        <br>
<?  }
}

if ((strlen($produto) > 0 AND $ip == '201.0.9.216')) {?>
    <div class="container">
        <a href='preco_cadastro_produto_lbm.php?produto=<?=$produto?>&btn_acao=listar'>CLIQUE AQUI PARA LISTAR A TABELA DE PREÇOS </a><br><br>
    </div>
<?}?>

<? if ($login_fabrica == 3) {?>

    <div class="container">
        <div class="row-fluid">
            <div class="span12">
                <div class="control-group ">
                    <div class="controls controls-row tac">
                        <a href='produto_consulta_parametro.php' target='_blank'>CLIQUE AQUI PARA CONSULTAR FILTRANDO DE ACORDO COM O TIPO (EX.: TROCA OBRIGATÓRIA)</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="container">
    <div class="row-fluid">
        <div class="span12">
            <div class="control-group">
                <div class="controls controls-row  tac">
                    <button type='button' class="btn" onclick="window.location='<?echo $PHP_SELF;?>?listartudo=1'">Listar Todos os Produtos</button>
                </div>
            </div>
        </div>
    </div>            
</div><br/>
<?php

$listartudo = $_GET['listartudo'];

if ($listartudo == 1) {

    $sql = "SELECT  tbl_produto.referencia,
                    tbl_produto.voltagem  ,
                    tbl_produto.produto   ,
                    tbl_produto.descricao ,
                    tbl_produto.ativo     ,
                    tbl_produto.uso_interno_ativo     ,
                    tbl_produto.locador   ,
                    tbl_produto.referencia_fabrica,
                    tbl_familia.descricao AS familia,
                    tbl_linha.nome        AS linha
            FROM    tbl_produto
            JOIN    tbl_linha     USING (linha)
            LEFT JOIN tbl_familia USING (familia)
            WHERE   tbl_linha.fabrica = $login_fabrica ";

    if (in_array($login_fabrica, array(35, 81, 114))) {
                $sql .= " ORDER BY
                    tbl_produto.descricao ASC
                ";
    } else {
        $sql .=    " ORDER BY    tbl_linha.nome ASC,
                                 tbl_produto.descricao ASC
                     ";
    }
    $sql .= " limit 500";
    $res = @pg_query ($con,$sql);
    ?>
    <div id='registro_max'>
        <h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
    </div>  
     <table id="listagemProduto" style="margin: 0 auto;" class="tabela_item table table-striped table-bordered table-hover table-large">
                    <thead>
                        <tr class='titulo_coluna'>
                            <th nowrap>Status Rede</th>
                            <th nowrap>Status Interno</th>
                            <th>Referência</th>
                            <th>Descrição</th>

                        <?if ($login_fabrica == 1 or $login_fabrica == 80) { // HD  90109 ?>

                            <th class='tac' >Voltagem</th>
                            <th class='tac' nowrap >Referência Interna</th>

                        <?}?>
                        
                        <th class='tac' >Família</th>
                        <th class='tac' >Linha</th>

                        <?if ($login_fabrica == 1) { // HD 90109?>
                            <th class='tac' >Locador</th>
                        <?}?>

                        </tr>
                    </thead>

                <tbody>
    <?php
    $count = pg_num_rows($res);
    for ($i = 0; $i < $count; $i++) {
		?>
           
                    <tr>
                        <td class='tac'>
                        <?if (pg_fetch_result($res,$i,ativo) <> 't') {?>
                             <img src='imagens/status_vermelho.png' border='0' align="center" title='Inativo' alt='Inativo'>
                        <?}else{?>
                             <img src='imagens/status_verde.png' border='0' title='Ativo' alt='Ativo'> 
                        <?}?>
                        </td>
                        <td class='tac'>
                        <?if (pg_fetch_result($res,$i,uso_interno_ativo) <> 't'){?>
                            <img src='imagens/status_vermelho.png' border='0' title='Inativo' alt='Inativo'> 
                        <?}else{?>
                             <img src='imagens/status_verde.png' border='0' title='Ativo' alt='Ativo'> 
                        <?}?>
                        </td>

                        <td align='left' nowrap>
                        <? echo pg_fetch_result($res,$i,referencia);

                		if (!in_array($login_fabrica, array(1, 80, 81, 114))) {
                			 if (strlen(pg_fetch_result($res,$i,voltagem)) > 0) 
                                echo " / ". pg_fetch_result($res,$i,voltagem);
                        }?>

                        </td>

                        <td align='left' nowrap>
                        
                            <a href='<?="$PHP_SELF?produto=" . pg_fetch_result($res,$i,produto)?>'>
                                <?=pg_fetch_result($res,$i,descricao)?>
                            </a>
                        </td>
                        <?if ($login_fabrica == 1 or $login_fabrica == 80) { // HD 90109?>
                        <td align='left' nowrap>
                            <? echo pg_fetch_result($res,$i,voltagem);?>
                        </td>

                        <td align='left' nowrap>
                            <? echo pg_fetch_result($res,$i,referencia_fabrica);?>
                        </td>
                        <?}?>

                        
                        <td align='left' nowrap>
                            <?echo pg_fetch_result($res,$i,familia);?>
                        </td>

                        <td align='left' nowrap>
                            <? echo pg_fetch_result($res,$i,linha);
                                

                            ?>
                        </td>

                       <? if ($login_fabrica == 1) { // HD 90109?>
                        <td class='tac'>
                        <?if (pg_fetch_result($res,$i,locador) <> 't'){
                            echo "Não";
                        }else{
                            echo "Sim";
                        }?>
                        </td>
                    <?}?>
                </tr>
            <?}?>
            </tbody>
        </table>
        <?php
            if ($count > 50) {
            ?>
                <script>
                    $.dataTableLoad({
                        table : "#listagemProduto"
                    });
                </script>
            <?php
            }?>
<br/>      
    
    <div id='gerar_excel' class="btn_excel">
        <input type="hidden" id="jsonPOST" value='<?=json_encode(array("listarTudo" => "1"))?>' />
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Gerar Arquivo Excel</span>
    </div>
    
<?}?>

<?php include "rodape.php"; ?>
