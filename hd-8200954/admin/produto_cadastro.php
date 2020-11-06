<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'cadastros';
 
include 'autentica_admin.php';
include 'funcoes.php';

include_once __DIR__ . '/../class/AuditorLog.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . '../class/json.class.php';

$produto = $_GET['produto'];
$excluir_imagem = $_GET['excluir_imagem'];

if ($login_fabrica == 158) {
    if ($_serverEnvironment == "production") {
        $chave_persys = "12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9";
    }else{
        $chave_persys = "4716427000141-dc3442c4774e4edc44dfcc7bf4d90447";
    }
}

if ($login_fabrica == 117) {
    include_once('carrega_macro_familia.php');
}

if(isset($excluir_imagem) && $excluir_imagem == true){

    include_once 'class/aws/s3_config.php';
    include_once S3CLASS;

    $s3_delete = new AmazonTC('produto', $login_fabrica);
    $anexo = $s3_delete->getObjectList($produto);
    $anexo = basename($anexo[0]);
    $s3_delete->deleteObject($anexo);

    $msg = traduz('Imagem Excluida com Sucesso');

}

$layout_menu = 'cadastro';
$title = traduz('Cadastro De Produtos');
unset($msg_erro);
$msg_erro = array();

if ( (strlen($_REQUEST['listarTudo']) > 0) && $_POST['gerar_excel'] ) { //703506

    if($login_fabrica == 1){
        $campo_sql = "tbl_marca.nome as nome_marca,";
        $complemento_sql = "left join tbl_marca on tbl_marca.marca = tbl_produto.marca and tbl_marca.fabrica = $login_fabrica";
    }

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
        $sql = " SELECT   tbl_produto.referencia,
                                    tbl_produto.descricao ,
                                    tbl_linha.nome        AS linha,
                                    tbl_familia.descricao AS familia,
                                    tbl_produto.referencia_fabrica,
                                    tbl_produto.voltagem,
                                    tbl_produto.garantia,
                                    tbl_produto.produto,
                    $campo_sql
                    tbl_produto.mao_de_obra,
                                    CASE WHEN tbl_produto.ativo             IS TRUE THEN 'Ativo' ELSE 'Inativo' END AS ativo,
                                    CASE WHEN tbl_produto.uso_interno_ativo IS TRUE THEN 'Ativo' ELSE 'Inativo' END AS status_interno,
                                    CASE WHEN tbl_produto.produto_critico   IS TRUE THEN 'Sim'   ELSE 'Não'     END AS produto_critico,
                                    CASE WHEN tbl_produto.locador           IS TRUE THEN 'Sim'   ELSE 'Não'     END AS locador,
                                    (SELECT CASE WHEN COUNT(*) > 0
                                        THEN 'Sim'   ELSE 'Não'
                                        END AS pecas
                                        FROM tbl_lista_basica AS lbm
                                        WHERE lbm.produto = tbl_produto.produto
                                        AND lbm.fabrica = $login_fabrica) AS tem_lbm,
                                    (SELECT CASE WHEN COUNT(comunicado) > 0
                                        THEN 'Sim' ELSE 'Não'
                                        END AS comunicados
                                        FROM tbl_comunicado
                                        LEFT JOIN tbl_comunicado_produto using(comunicado)
                                        WHERE tipo = 'Vista Explodida'
                                        AND (tbl_comunicado.produto = tbl_produto.produto
                                            OR tbl_comunicado_produto.produto = tbl_produto.produto)
                                        AND fabrica = $login_fabrica) AS tem_vista
                        FROM tbl_produto
                            JOIN tbl_linha   USING (linha)
                            LEFT JOIN tbl_familia USING (familia)
                            $complemento_sql
                        WHERE tbl_linha.fabrica = $login_fabrica
                        ORDER BY tbl_produto.descricao ";
    }
    $resList = pg_query($con,$sql);

    if ( pg_num_rows($resList) > 0) {

        $file     = "xls/relatorio-produtos-{$login_fabrica}.xls";
        $fileTemp = "/tmp/relatorio-produtos-{$login_fabrica}.xls" ;
        $fp     = fopen($fileTemp,'w');
        switch($login_fabrica) {
            case   1: $colspan=  9; break;
            case 104: $colspan= 9; break;
            case  20: $colspan= 11; break;
            case 52: $colspan=  7; break;
            default: $colspan= 5;
        }

        if ($login_fabrica != 52) {
            $head = "
                <table border='1'>
                    <thead>
                        <tr bgcolor='#596D9B'>

                            <th><font color='#FFFFFF'>".traduz("Status Rede")."</font></th>";
                            if($login_fabrica == 1){
                                $head .= "\t\t\t\t\t\t<th><font color='#FFFFFF'>".traduz("Status interno")."</font></th>\n";
                            }
                            $head .= "
                            <th><font color='#FFFFFF'>".traduz("Referência")."</font></th>
                            <th><font color='#FFFFFF'>".traduz("Descrição")."</font></th>\n";
                            if($login_fabrica == 1) {
                                $head .= "<th><font color='#FFFFFF'>".traduz("Voltagem")."</font></th>
                                <th><font color='#FFFFFF'>".traduz("Referencia interna")."</font></th>";
                            }
                            $head .= "<th><font color='#FFFFFF'>".traduz("Linha")."</font></th>";
                            if($login_fabrica == 1){
                                $head .= "<th><font color='#FFFFFF'>".traduz("Marca")."</font></th>";
                            }
                            $head .= "<th><font color='#FFFFFF'>".traduz("Garantia(meses)")."</font></th>";
                            $head .= "<th><font color='#FFFFFF'>".traduz("Mão de Obra")."</font></th>";
                            if ($login_fabrica != 189) {
                                $head .= "<th><font color='#FFFFFF'>".traduz("Família")."</font></th>";
                            }
                            if ($login_fabrica == 20) {
                                $head .=       "<th><font color='#FFFFFF'>".traduz("Origem")."</font></th>
                                <th><font color='#FFFFFF'>".traduz("Voltagem")."</font></th>
                                <th><font color='#FFFFFF'>".traduz("Bar Tool(*)")."</font></th>
                                <th><font color='#FFFFFF'>".traduz("Nome Comercial")."</font></th>
                                <th><font color='#FFFFFF'>".traduz("Classificação Fiscal")."</font></th>
                                <th><font color='#FFFFFF'>".traduz("IPI")."</font></th>";
			                }
                        if ($login_fabrica == 42) {
                                            $head .= "<th><font color='#FFFFFF'>".traduz("Classe")."</font></th>";
                                            $head .= "<th><font color='#FFFFFF'>".traduz("Classe Entrega Técnica")."</font></th>";
                                            $head .= "<th><font color='#FFFFFF'>".traduz("MO Classe")."</font></th>";
                        }
                        if ($login_fabrica == 104) {
                                            $head .= "<th><font color='#FFFFFF'>".traduz("Lista Básica")."</font></th>";
                                            $head .= "<th><font color='#FFFFFF'>".traduz("Vista Expl.")."</font></th>";
                                            $head .= "<th><font color='#FFFFFF'>".traduz("Produto Aud")."</font></th>";
                        }
                        if($telecontrol_distrib){
                            $head .= "<th><font color='#FFFFFF'>".traduz("Data da Última Manutenção na Lista Básica")."</font></th>";
                            $head .= "<th><font color='#FFFFFF'>".traduz("Operador")."</font></th>";
                        }

                                        $head .=       "</tr>
                                </thead>
                                <tbody>";
                    }
                            if ($login_fabrica == 42) {
                                $head .= "<th><font color='#FFFFFF'>".traduz("Classe")."</font></th>";
                                $head .= "<th><font color='#FFFFFF'>".traduz("Classe Entrega Técnica")."</font></th>";
                                $head .= "<th><font color='#FFFFFF'>".traduz("MO Classe")."</font></th>";
                            }
                            if ($login_fabrica == 104) {
                                $head .= "<th><font color='#FFFFFF'>".traduz("Lista Básica")."</font></th>";
                                $head .= "<th><font color='#FFFFFF'>".traduz("Vista Expl.")."</font></th>";
                                $head .= "<th><font color='#FFFFFF'>".traduz("Produto Aud")."</font></th>";
                            }
                            $head .=       "</tr>
                    </thead>
                    <tbody>";

            fwrite($fp, $head );

            for ( $i = 0; $i < pg_num_rows($resList); $i++ ) {
                if ($login_fabrica == 42) {
                    $xproduto = pg_fetch_result($resList, $i, 'produto');

                    $sqlClass = "SELECT tbl_classe.nome, tbl_classe.mao_de_obra, tbl_classe_produto.produto FROM tbl_classe JOIN tbl_classe_produto ON tbl_classe_produto.classe = tbl_classe.classe AND tbl_classe.entrega_tecnica = 'f' AND tbl_classe_produto.produto = {$xproduto}";

                    $sqlAuxClasse = "SELECT tbl_classe.nome FROM tbl_classe JOIN tbl_classe_produto ON tbl_classe_produto.classe = tbl_classe.classe AND tbl_classe.entrega_tecnica = 't' AND tbl_classe_produto.produto = {$xproduto}";

                    $classeProduto = pg_query($con,$sqlClass);
                    $classeAux = pg_query($con, $sqlAuxClasse);
                }

                $body = '<tr>
                    <td align="center">' . pg_fetch_result($resList,$i,'ativo') . '</td>';
                if($login_fabrica == 1){
                    $body .= '<td>' . pg_fetch_result($resList,$i,'status_interno') . '</td>';
                }
                $body .= '
                    <td>' . pg_fetch_result($resList,$i,'referencia') . '</td>
                    <td>' . pg_fetch_result($resList,$i,'descricao') . '</td>';

                if($login_fabrica == 1){
                    $body .= '<td>' . pg_fetch_result($resList,$i,'voltagem') . '</td>';
                    $body .= '<td>' . pg_fetch_result($resList,$i,'referencia_fabrica') . '</td>';
                }

                $body .= '<td>' . pg_fetch_result($resList,$i,'linha') . '</td>';

                if($login_fabrica == 1){
                    $body .= '<td>' . pg_fetch_result($resList,$i,'nome_marca') . '</td>';
                }

                $body .= '<td>' . pg_fetch_result($resList,$i,'garantia') . '</td>';
                $body .= '<td>' . number_format(pg_fetch_result($resList,$i,'mao_de_obra'),0) . '</td>';
                if ($login_fabrica != 189) {
                    $body .= '<td>' . pg_fetch_result($resList,$i,'familia') . '</td>';
                }
                if($login_fabrica == 20){
                    $body .=   '<td>' . pg_fetch_result($resList,$i,'origem') . '</td>
                                    <td>' . pg_fetch_result($resList,$i,'voltagem') . '</td>
                                    <td>' . pg_fetch_result($resList,$i,'referencia_fabrica') . '</td>
                                    <td>' . pg_fetch_result($resList,$i,'nome_comercial') . '</td>
                                    <td>' . pg_fetch_result($resList,$i,'classificacao_fiscal') . '</td>
                                    <td>' . pg_fetch_result($resList,$i,'ipi') . '</td>';

                }

                if($login_fabrica == 42){
                    $body .=   '<td>' . pg_fetch_result($resList,$i,'voltagem') . '</td>
                                <td>' . pg_fetch_result($resList,$i,'referencia_fabrica') . '</td>';
                }


                if($login_fabrica == 1 || $login_fabrica == 42){
                    $body .= '<td>' . pg_fetch_result($resList,$i,'locador') . '</td>';
                }

                if($login_fabrica == 42){
                    $body .= '<td>' . pg_fetch_result($classeProduto, 0, 'nome') . '</td> 
                    <td>' . pg_fetch_result($classeAux, 0, 'nome') . '</td> 
                    <td>' . pg_fetch_result($classeProduto, 0, 'mao_de_obra') . '</td>';
                } 

                if ($login_fabrica == 104) {
                    $body .= '<td align="center">' . pg_fetch_result($resList, $i, 'tem_lbm')   . '</td>';
                    $body .= '<td align="center">' . pg_fetch_result($resList, $i, 'tem_vista') . '</td>';
                    $body .= '<td align="center">' . pg_fetch_result($resList, $i, 'produto_critico') . '</td>'; 
                }

                if ($telecontrol_distrib) {
                    $xproduto = pg_fetch_result($resList, $i, 'produto');
                    $sql_lista_basica = "SELECT tbl_lista_basica.data_alteracao, tbl_admin.nome_completo as admin from tbl_lista_basica 
                    join tbl_admin on (tbl_lista_basica.admin = tbl_admin.admin) where tbl_lista_basica.produto = $xproduto 
                    and tbl_lista_basica.fabrica = $login_fabrica order by tbl_lista_basica.data_alteracao DESC";

                    $res_lista_basica = pg_query($con, $sql_lista_basica);
                    $data =  pg_fetch_result($res_lista_basica, 'data_alteracao');
                    $data_final = ($data != '') ? (new DateTime($data))->format('d/m/Y H:i') : '';
                    $body .= '<td align="center">' . $data_final . '</td>';
                    $body .= '<td align="center">' . pg_fetch_result($res_lista_basica, 'admin') . '</td>';
                }


                $body .=    '</tr>';
                fwrite($fp, $body);

            }
        }else{
            $head = "
                <table border='1'>
                    <thead>
                        <tr bgcolor='#596D9B'>
                            <th><font color='#FFFFFF'>".traduz("Referência")."</font></th>
                            <th><font color='#FFFFFF'>".traduz("Descrição")."</font></th>
                            <th><font color='#FFFFFF'>".traduz("Garantia")."</font></th>
                            <th><font color='#FFFFFF'>".traduz("Família")."</font></th>
                            <th><font color='#FFFFFF'>".traduz("Linha")."</font></th>
                            <th><font color='#FFFFFF'>".traduz("Status Rede")."</font></th>
                            <th><font color='#FFFFFF'>".traduz("Status interno")."</font></th>
                        </tr>
                    </thead>
                    <tbody>";
            fwrite($fp, $head );

            for ( $i = 0; $i < pg_num_rows($resList); $i++ ) {
                $body =  '<tr>';
                    $body .= '<td>' . pg_fetch_result($resList,$i,'referencia') . '</td>';
                    $body .= '<td>' . pg_fetch_result($resList,$i,'descricao') . '</td>';
                    $body .= '<td>' . pg_fetch_result($resList,$i,'garantia') . '</td>';
                    $body .= '<td>' . pg_fetch_result($resList,$i,'familia') . '</td>';
                    $body .= '<td>' . pg_fetch_result($resList,$i,'linha') . '</td>';
                    $body .= '<td align="center">' . pg_fetch_result($resList,$i,'ativo') . '</td>';
                    $body .= '<td>' . pg_fetch_result($resList,$i,'status_interno') . '</td>';
                $body .=    '</tr>';
                fwrite($fp, $body);
            }
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


if(isset($_POST["gerar_excel_gerencial"])){

    $sql = "SELECT
                tbl_produto.referencia,
                tbl_produto.descricao,
                tbl_produto.garantia,
                tbl_produto.produto,
                tbl_produto.mao_de_obra_admin,
                tbl_produto.classificacao_fiscal,
                tbl_produto.ipi,
                tbl_linha.nome as linha,
                tbl_familia.descricao as familia,
                tbl_produto.origem,
                tbl_produto.voltagem,
                tbl_produto.nome_comercial,
                tbl_produto.code_convention,
                tbl_produto.valor_troca as valor_troca_faturada,
                tbl_marca.nome as marca,
                tbl_produto.referencia_fabrica,
                tbl_produto.lista_troca,
                tbl_produto.troca_faturada,
                tbl_produto.troca_garantia,
                tbl_produto.troca_obrigatoria,
                tbl_produto.produto_principal,
                tbl_produto.numero_serie_obrigatorio,
                tbl_produto.locador,
                tbl_produto.mao_de_obra,
                tbl_produto.ativo as status_rede,
                tbl_produto.uso_interno_ativo,
                tbl_produto.qtd_etiqueta_os,
                TO_CHAR(tbl_produto.data_input, 'DD/MM/YYYY') AS data_cadastro,
                tbl_produto.parametros_adicionais
            FROM tbl_produto
            join tbl_linha on tbl_linha.linha = tbl_produto.linha and tbl_linha.fabrica = $login_fabrica
            JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia and tbl_familia.fabrica = $login_fabrica
            LEFT JOIN tbl_marca on tbl_marca.marca = tbl_produto.marca and tbl_marca.fabrica = $login_fabrica
            WHERE tbl_produto.fabrica_i = $login_fabrica
            ORDER BY descricao ";

            $res = pg_query($con, $sql);
            if(pg_num_rows($res)>0){

                $file     = "xls/relatorio-gerencial-{$login_fabrica}.xls";
                $fileTemp = "/tmp/relatorio-gerencial-{$login_fabrica}.xls" ;
                $fp     = fopen($fileTemp,'w');

                $head = "<table border='1'>
                        <thead>
                            <tr>
                                <th bgcolor='#D9E2EF' colspan='$colspan'><font color='#333333'>".traduz("RELATÓRIO GERENCIAL")."</font></th>
                            </tr>";
                $head .= "<tr bgcolor='#596D9B'>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("REFERÊNCIA")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("DESCRIÇÃO")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("GARANTIA")."</font></th>";
                    if (!in_array($login_fabrica, [151, 177, 178, 183,190,191,195])) {
                        $head .= "<th><font color='#FFFFFF'>".traduz("M. OBRA POSTO")."</font></th>";
                        $head .= "<th><font color='#FFFFFF'>".traduz(">M.OBRA ADMIN")."</font></th>";
                    }
                    if (in_array($login_fabrica, [190])) {
                        $head .= "<th><font color='#FFFFFF'>".traduz("M. OBRA POSTO")."</font></th>";
                        $head .= "<th><font color='#FFFFFF'>".traduz("M.OBRA TREINAMENTO")."</font></th>";
                    }
                    $head .= "<th><font color='#FFFFFF'>".traduz("CLASSIFICAÇÃO FISCAL")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("IPI")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("LINHA")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("FAMILIA")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("ORIGEM")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("VOLTAGEM")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("STATUS REDE")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("STATUS USO INTERNO")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("NOME COMERCIAL")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("CODE CONVENTION")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("VALOR TROCA FATURADA")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("MARCA")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("REFERENCIA INTERNA")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("QTD ETIQUETA")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("LISTA TROCA")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("Nº SÉRIE OBRIGATÓRIO")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("PRODUTO PRINCIPAL")."</font></th>";
        		    if (in_array($login_fabrica, [151])){
        			    $head .= "<th><font-color='#FFFFFF'>".traduz("TROCA DIRETA")."</font></th>";
        		    }
                    if (in_array($login_fabrica, [195])){
                        $head .= "<th><font-color='#FFFFFF'>".traduz("POTÊNCIA")."</font></th>";
                        $head .= "<th><font-color='#FFFFFF'>".traduz("VAZÃO")."</font></th>";
                        $head .= "<th><font-color='#FFFFFF'>".traduz("PRESSÃO")."</font></th>";
                        $head .= "<th><font-color='#FFFFFF'>".traduz("CORRENTE")."</font></th>";
                    }
                    $head .= "<th><font color='#FFFFFF'>".traduz("TROCA OBRIGATORIO")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("LOCADOR ")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("TROCA GARANTIA ")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("TROCA FATURADA")."</font></th>";
                    $head .= "<th><font color='#FFFFFF'>".traduz("TROCA")."</font></th>";
                    if ($login_fabrica == '1') {
                        $head .= "<th><font color='#FFFFFF'>".traduz("DATA CADASTRO")."</font></th>";
                        $head .= "<th><font color='#FFFFFF'>".traduz("DATA DESCONTINUADO")."</font></th>";
                    }
                $head .= "</tr>";

                $head .= "</thead>";
                $head .= "<tbody>";

                fwrite($fp, $head);

                for($i=0; $i<pg_num_rows($res);$i++){
                    $referencia                 = pg_fetch_result($res, $i, 'referencia');
                    $descricao                  = pg_fetch_result($res, $i, 'descricao');
                    $garantia                   = pg_fetch_result($res, $i, 'garantia');
                    $produto                    = pg_fetch_result($res, $i, 'produto');
                    $mao_de_obra                = pg_fetch_result($res, $i, 'mao_de_obra');
                    $mao_de_obra_admin          = pg_fetch_result($res, $i, 'mao_de_obra_admin');
                    $classificacao_fiscal       = pg_fetch_result($res, $i, 'classificacao_fiscal');
                    $ipi                        = pg_fetch_result($res, $i, 'ipi');
                    $linha                      = pg_fetch_result($res, $i, 'linha');
                    $familia                    = pg_fetch_result($res, $i, 'familia');
                    $origem                     = pg_fetch_result($res, $i, 'origem');
                    $voltagem                   = pg_fetch_result($res, $i, 'voltagem');
                    $status_rede                = (pg_fetch_result($res, $i, 'status_rede') == 't')? "Sim" : "Não";
                    $uso_interno_ativo          = (pg_fetch_result($res, $i, 'uso_interno_ativo') == 't')? "Sim" : "Não";
                    $nome_comercial             = pg_fetch_result($res, $i, 'nome_comercial');
                    $code_convention            = pg_fetch_result($res, $i, 'code_convention');
                    $valor_troca_faturada       = pg_fetch_result($res, $i, 'valor_troca_faturada');
                    $marca                      = pg_fetch_result($res, $i, 'marca');
                    $referencia_fabrica         = pg_fetch_result($res, $i, 'referencia_fabrica');
                    $lista_troca                = (pg_fetch_result($res, $i, 'lista_troca') == 't')? "Sim" : "Não";
                    $numero_serie_obrigatorio   = (pg_fetch_result($res, $i, 'numero_serie_obrigatorio') == 't')? "Sim" : "Não";
                    $produto_principal          = (pg_fetch_result($res, $i, 'produto_principal') == 't')? "Sim" : "Não";
                    $troca_obrigatoria          = (pg_fetch_result($res, $i, 'troca_obrigatoria') == 't')? "Sim" : "Não";
                    $locador                    = (pg_fetch_result($res, $i, 'locador') == 't')? "Sim" : "Não";
                    $troca_garantia             = (pg_fetch_result($res, $i, 'troca_garantia') == 't')? "Sim" : "Não";
                    $troca_faturada             = (pg_fetch_result($res, $i, 'troca_faturada') == 't')? "Sim" : "Não";
                    $data_cadastro = pg_fetch_result($res, $i, 'data_cadastro');

                    if ($login_fabrica == 1) {
                        $parametros_adicionais =   json_decode(pg_fetch_result($res, $i, 'parametros_adicionais'), true);
                        $data_descontinuado = $parametros_adicionais['data_descontinuado'];
                    }

					if (in_array($login_fabrica, [151])) {
                        $parametros_adicionais = json_decode(pg_fetch_result($res, $i, 'parametros_adicionais'), true);
                        $troca_direta = $parametros_adicionais['troca_direta'];
                        $troca_direta = (!empty($troca_direta) AND $troca_direta == 't') ? "Sim" : "Não";
                    }

					if (in_array($login_fabrica, [187,188])) {
                        $parametros_adicionais = json_decode(pg_fetch_result($res, $i, 'parametros_adicionais'), true);
                        $ressarcimento_obrigatoria = $parametros_adicionais['ressarcimento_obrigatoria'];
                        $ressarcimento_obrigatoria = (!empty($ressarcimento_obrigatoria) AND $ressarcimento_obrigatoria == 't') ? "Sim" : "Não";
                    }

                    if (in_array($login_fabrica, [195])) {
                        $parametros_adicionais = json_decode(pg_fetch_result($res, $i, 'parametros_adicionais'), true);
                        $xpotencia = $parametros_adicionais['potencia'];
                        $xvazao = $parametros_adicionais['vazao'];
                        $xpressao = $parametros_adicionais['pressao'];
                        $xcorrente = $parametros_adicionais['corrente'];
                    }


                    $sqlp = " SELECT * from tbl_produto_troca_opcao where produto = $produto";
                    $resp = pg_query($con,$sqlp);
                    if(pg_num_rows($resp) > 0 ){
                        $produto_troca_opcao = "kit";
                    }else{
                        $produto_troca_opcao = "";
                    }

                    $body .= '<tr>';
                          $body .=   "<td>$referencia</td>";
                          $body .=   "<td>$descricao</td>";
                          $body .=   "<td>$garantia</td>";
                        if (!in_array($login_fabrica, [151, 177, 178, 183,191,195])) {
                              $body .=   "<td>$mao_de_obra</td>";
                              $body .=   "<td>$mao_de_obra_admin</td>";
                        }
                      $body .=   "<td>$classificacao_fiscal</td>";
                          $body .=   "<td>$ipi</td>";
                          $body .=   "<td>$linha</td>";
                          $body .=   "<td>$familia</td>";
                          $body .=   "<td>$origem</td>";
                          $body .=   "<td>$voltagem</td>";
                          $body .=   "<td>$status_rede</td>";
                          $body .=   "<td>$uso_interno_ativo</td>";
                          $body .=   "<td>$nome_comercial</td>";
                          $body .=   "<td>$code_convention</td>";
                          $body .=   "<td>$valor_troca_faturada</td>";
                          $body .=   "<td>$marca</td>";
                          $body .=   "<td>$referencia_fabrica</td>";
                          $body .=   "<td>$qtd_etiqueta_os</td>";
                          $body .=   "<td>$lista_troca</td>";
                          $body .=   "<td>$numero_serie_obrigatorio</td>";
                          $body .=   "<td>$produto_principal</td>";
                          $body .=   "<td>$troca_obrigatoria</td>";
			              if (in_array($login_fabrica, [151])) {
                              $body .=   "<td>$troca_direta</td>";
                          }
                          if (in_array($login_fabrica, [195])) {
                              $body .=   "<td>$xpotencia</td>";
                              $body .=   "<td>$xvazao</td>";
                              $body .=   "<td>$xpressao</td>";
                              $body .=   "<td>$xcorrente</td>";
                          }
                          $body .=   "<td>$locador</td>";
                          $body .=   "<td>$troca_garantia</td>";
                          $body .=   "<td>$troca_faturada</td>";
                          $body .=   "<td>$produto_troca_opcao</td>";
                          if ($login_fabrica == '1') {
                              $body .= "<td>{$data_cadastro}</td>";
                              $body .= "<td>{$data_descontinuado}</td>";
                          }
                    $body .= '</tr>';
                }
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

if (isset($_POST["gerar_excel_kit"])) { /*HD - 4357126*/
    $sql = "
        SELECT tbl_produto.referencia,
        tbl_produto.referencia_fabrica,
        tbl_produto.descricao,
        CASE WHEN tbl_produto.troca_garantia IS TRUE THEN 'SIM' ELSE 'NÃO' END AS troca_garantia,
        CASE WHEN tbl_produto.troca_faturada IS TRUE THEN 'SIM' ELSE 'NÃO' END AS troca_faturada,
        tbl_produto_troca_opcao.kit,
        produto_troca.referencia AS referencia_trocada,
        produto_troca.referencia_fabrica AS referencia_fabrica_trocada,
        produto_troca.descricao AS descricao_trocada
        FROM tbl_produto
        JOIN tbl_produto_troca_opcao ON tbl_produto.produto = tbl_produto_troca_opcao.produto
        JOIN tbl_produto produto_troca ON tbl_produto_troca_opcao.produto_opcao = produto_troca.produto AND produto_troca.fabrica_i = $login_fabrica
        WHERE tbl_produto.fabrica_i = $login_fabrica
        GROUP BY tbl_produto.referencia,
        tbl_produto.referencia_fabrica,
        tbl_produto.descricao,
        tbl_produto.troca_garantia,
        tbl_produto.troca_faturada,
        tbl_produto_troca_opcao.kit,
        produto_troca.referencia,
        produto_troca.referencia_fabrica,
        produto_troca.descricao
    ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $file     = "xls/relatorio-kit-{$login_fabrica}.csv";
        $fileTemp = "/tmp/relatorio-kit-{$login_fabrica}.csv" ;
        $fp     = fopen($fileTemp,'w');

        $head = array(
            traduz("REF. TELECONTROL"),
            traduz("REF. INTERNA"),
            traduz("DESCRIÇÃO DO PRODUTO "),
            traduz("TROCA GAR "),
            traduz("TROCA FAT"),
            traduz("KIT"),
            traduz("REF. TELECONTROL"),
            traduz("REF. INTERNA"),
            traduz("DESCRIÇÃO DO PRODUTO")
        );

        fwrite($fp, implode(";", $head));

        for ($z = 0; $z < pg_num_rows($res); $z++) { 
            if ($z == 0) {
                $tbody = "\n";
            } else {
                $tbody = "";
            }

            $tbody .= pg_fetch_result($res, $z, 'referencia'). ";";
            $tbody .= pg_fetch_result($res, $z, 'referencia_fabrica'). ";";
            $tbody .= pg_fetch_result($res, $z, 'descricao'). ";";
            $tbody .= pg_fetch_result($res, $z, 'troca_garantia'). ";";
            $tbody .= pg_fetch_result($res, $z, 'troca_faturada'). ";";

            $aux_kit = pg_fetch_result($res, $z, 'kit');

            if ($aux_kit == "" || $aux_kit == "0" || empty($aux_kit)) {
                $aux_kit = "";
            } 

            $tbody .= $aux_kit . ";";
            $tbody .= pg_fetch_result($res, $z, 'referencia_trocada'). ";";
            $tbody .= pg_fetch_result($res, $z, 'referencia_fabrica_trocada'). ";";
            $tbody .= pg_fetch_result($res, $z, 'descricao_trocada'). "\n";

            fwrite($fp, $tbody);
        }

        fclose($fp);
        if(file_exists($fileTemp)){
            system("mv $fileTemp $file");

            if(file_exists($file)){
                echo $file;
            }
        }
    }

    exit;
}



// Valores adicionais
function mostraDados($produto){
    global $con, $login_fabrica;
    if(!empty($produto)) {

        $sql = "SELECT DISTINCT valores_adicionais, produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto AND valores_adicionais notnull";

        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){

        $retorno = "";
        for($j = 0; $j < pg_num_rows($res); $j++){
            $valor = pg_fetch_result($res, $j, 'valores_adicionais');
            $valores_adicionais = json_decode(utf8_encode($valor),true);
            foreach ($valores_adicionais as $key =>$value) {
            $servico = utf8_decode($key);
            $valor   = $value;

            $retorno .="<tr>
                    <td>
                        <a href='javascript:void(0);' onclick='carregaDados(\"$servico\",\"$valor\")'>$servico</a>
                    </td>
                    <td class='tac'>$valor</td>
                    <td class='tac'><input type='button' class='btn btn-danger' value='".traduz("Excluir")."' onclick='excluiRegistro(\"$servico\",\"$produto\")'></td>
                    </tr>";
            }
        }
        return $retorno;
        }
    } else {
        return null;
    }
}

function fabrica_produto_i($produto) {
    global $con;

    $sql_fab_produto = "SELECT fabrica_i FROM tbl_produto WHERE produto = $produto";
    $res_fab_produto = pg_query($con, $sql_fab_produto);
    $fab_produto = pg_fetch_result($res_fab_produto, 0, 'fabrica_i');

    return $fab_produto;
}


if($_GET['ajax'] and $_GET['servico']){
    $servico    = $_GET['servico'];
    $servico_id = $_GET['servico_id'];
    $produto    = $_GET['produto'];
    $valor_adicional      = $_GET['valor'];

    $sql = "SELECT upper(fn_retira_especiais('$servico'))";
    $res = pg_query($con,$sql);
    $servico = pg_fetch_result($res, 0, 0);

    $valores = array($servico => $valor_adicional);

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

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosTabela('tbl_produto', array('produto'=>$produto, 'fabrica_i'=>$login_fabrica));

    $valores_adicionais = json_encode($valores_adicionais);
    $sql = "UPDATE tbl_produto SET
                valores_adicionais = '$valores_adicionais',
                data_atualizacao   = current_timestamp
            WHERE fabrica_i = $login_fabrica
                AND produto = $produto";
    $res = pg_query($con,$sql);

    if(pg_last_error($con)){
        echo pg_last_error($con);
    }else{
        $auditorLog->retornaDadosTabela()
                   ->enviarLog('update', "tbl_produto", $login_fabrica."*".$produto);

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

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosTabela('tbl_produto', array('produto'=>$produto, 'fabrica_i'=>$login_fabrica));

    $sql = "UPDATE tbl_produto SET valores_adicionais = $valores_adicionais, data_atualizacao   = current_timestamp WHERE fabrica_i = $login_fabrica AND produto = $produto";
    $res = pg_query($con,$sql);

    if(pg_last_error($con)){
        echo pg_last_error($con);
    }else{
        $auditorLog->retornaDadosTabela()
                   ->enviarLog('update', "tbl_produto", $login_fabrica."*".$produto);

        $retorno = mostraDados($produto);
        $retorno = utf8_encode($retorno);
        echo "OK|$retorno";
    }

    exit;
}

if ($ajax == 'excluir') {

    $imagem      = basename($_GET['imagem']);
    $caminho_dir = "../imagens_produtos/$login_fabrica";

    if (!file_exists("$caminho_dir/media/$imagem"))
        die(traduz("A Imagem $imagem não existe!"));

    $deletou = unlink("$caminho_dir/media/$imagem");
    $deletou = @unlink("$caminho_dir/pequena/$imagem"); //PDF não tem no pequena

    if ($deletou)
        die(traduz("Imagem excluída com sucesso!"));

    die(traduz("Erro ao excluir a imagem!"));

}

if($_GET['ajax_voltagem']){
    $produto=$_GET['produto'] ;
    $voltagem=$_GET['voltagem'] ;
    if(!empty($produto)) {
        $sql = "SELECT voltagem FROM tbl_produto WHERE produto = $produto";
        $res = pg_query($con,$sql);
        $voltage = pg_fetch_result($res,0,0);
	}elseif(!empty($voltagem)){
		$voltage = $voltagem;
	}
    $sql = "SELECT DISTINCT length(voltagem), upper(voltagem) AS voltagem FROM tbl_produto WHERE fabrica_i = $login_fabrica and length(trim(voltagem)) > 0  order by length(voltagem) ,voltagem ";
    $res = pg_query($con,$sql);
    $result = pg_fetch_all($res);
    echo "<option value=''> </option>";
    for($i=0;$i<pg_num_rows($res);$i++){
        $voltagem = pg_fetch_result($res, $i, 'voltagem');
        echo "<option value='$voltagem'";
        echo ($voltagem == $voltage) ? " SELECTED ":"";
        echo ">$voltagem</option>\n";
    }
    exit;
}
include "cabecalho_new.php";

$plugins = array("autocomplete",
                "tooltip",
                 "shadowbox",
                 "dataTable",
                 "multiselect",
                 "price_format",
                 "mask",
                 "datepicker",
                 "select2"
            );

include ("plugin_loader.php");

$fabricaIntervaloSerie = array(15,45);


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
        $res = pg_query ($con,$sql);
        if(pg_num_rows($res)>0){
            $sql = "DELETE FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
            $res = pg_query ($con,$sql);
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
        $res = pg_query ($con,$sql);
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
	if (in_array($login_fabrica, [151])) {
            $troca_direta         = "";
        }
        $reparo_na_fabrica        = "";
        $insumo                   = "";
        $produto_critico          = "";
        $intervencao_tecnica      = "";
        $origem                   = "";
        $qtd_etiqueta_os          = "";
        $lista_troca              = "";
        $produto_fornecedor       = "";
        $valor_troca              = "";
        $troca_b_garantia         = "";
        $troca_b_faturada         = "";
        //$inibir_lista_basica      = "";
        $observacao               = "";
        $serie_in                 = "";
        $serie_out                = "";
        $apagar_serie             = "";
        $entrega_tecnica          = "";


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
	if (in_array($login_fabrica, [151])) {
            $troca_direta         = $_POST['troca_direta'][0];
        }
        $reparo_na_fabrica        = $_POST["reparo_na_fabrica"][0];
        $insumo                   = $_POST["insumo"][0];
        $produto_critico          = $_POST["produto_critico"][0];
        $intervencao_tecnica      = $_POST["intervencao_tecnica"];
        $origem                   = $_POST["origem"];
        $qtd_etiqueta_os          = $_POST["qtd_etiqueta_os"];
        if(in_array($login_fabrica, [19])) {
            $validacao_cadastro   = $_POST["validacao_cadastro"];
        }
        $lista_troca              = $_POST["lista_troca"][0];
        $produto_fornecedor       = $_POST["produto_fornecedor"];
        $valor_troca              = $_POST["valor_troca"];
        $troca_b_garantia         = $_POST["troca_b_garantia"][0];
        $troca_b_faturada         = $_POST["troca_b_faturada"][0];
        //$inibir_lista_basica      = $_POST["inibir_lista_basica"][0];
        $observacao               = $_POST["observacao"];
        $serie_in                 = $_POST["serie_in"];
        $serie_out                = $_POST["serie_out"];
        $apagar_serie             = $_POST["apagar_serie"];
        $categoria                  = $_POST["categoria"];
        $entrega_tecnica          = $_POST["entrega_tecnica"][0];
        $deslocamento_km          = $_POST["deslocamento_km"][0];
        $pecas_reposicao          = $_POST['pecas_reposicao'][0];
        if (in_array($login_fabrica, [177])) {
            $lote                 = $_POST['lote'][0];
		}
		if (in_array($login_fabrica, [187,188])) {
            $ressarcimento_obrigatoria                 = $_POST['ressarcimento_obrigatoria'][0];
        }
        $ean          = $_POST['ean'];
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
}

if ($btnacao == "gravar") {
    $produto = $_POST["produto"];

    if ($login_fabrica == 19) {

        $parametros_adicionais = [];

        if (!empty($_POST['data_fabricacao'])) {
            $data_fabricacao = $_POST['data_fabricacao'];
            $parametros_adicionais = array("data_fabricacao" => $data_fabricacao);
        }
        
        if (!empty($_POST['garantia2'])) {
            $parametros_adicionais['garantia2'] = $_POST['garantia2'];
        }

        if (!empty($_POST['garantia3'])) {
            $parametros_adicionais['garantia3'] = $_POST['garantia3'];
        }

        $parametros_adicionais = json_encode($parametros_adicionais);

        $campo_paramentros_adicionais = ", parametros_adicionais";
        $value_paramentros_adicionais = ", '$parametros_adicionais'";
    }

    if (in_array($login_fabrica, [195])) {

        $potencia  = $_POST["potencia"];
        $vazao  = $_POST["vazao"];
        $pressao  = $_POST["pressao"];
        $corrente  = $_POST["corrente"];

        if (strlen($potencia) == 0) {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][]   = "potencia";
        }
        if (strlen($vazao) == 0) {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][]   = "vazao";
        }
        if (strlen($pressao) == 0) {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][]   = "pressao";
        }
        if (strlen($corrente) == 0) {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][]   = "corrente";
        }
    }

    if (strlen($_POST["linha"]) > 0){
        $aux_linha = "'". trim($_POST["linha"]) ."'";
    }else{
        $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
        $msg_erro["campos"][]   = "linha";
    }

    $aux_familia = (strlen($_POST["familia"]) > 0) ? "'". trim($_POST["familia"]) ."'" : "null";
    if($aux_familia =="null" &&  !in_array($login_fabrica, array(189))) {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][]   = "familia";
    }

    if (strlen($_POST["mao_de_obra"]) > 0)            $aux_mao_de_obra = "'". trim($_POST["mao_de_obra"]) ."'";
    else{
        if(!in_array($login_fabrica, array(96,151,162,164,167,178,183,189,191,195,203))){
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
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
        if (!in_array($login_fabrica, array(189))) {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][]   = "garantia";
        } else {
            $aux_garantia = "0";
        }
    }

    if ($login_fabrica <> 20) {
        if (strlen($_POST["descricao"]) > 0){
            $aux_descricao = "'". trim($_POST["descricao"]) ."'";
        }else{
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][]   = "descricao";
        }
    } else {
        if (strlen($_POST['descricao']) > 0) {
            $aux_descricao = "'".$_POST['descricao']."'";
        } else if (strlen($_POST['descricao_idioma']) > 0) {
            $aux_descricao = "'".$_POST['descricao_idioma']."'";
        } else {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
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
        $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
        $msg_erro["campos"][]   = "referencia";
    }

    if (in_array($login_fabrica,array(1,20,24,80,96))) {
        if (strlen($_POST["referencia_fabrica"]) > 0) {
           $aux_referencia_fabrica = "'". trim($_POST["referencia_fabrica"]) ."'";
        } else {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][]   = "referencia_fabrica";
        }
    } else {
        if (in_array($login_fabrica,array(171))) {
            $aux_referencia_fabrica = "'". trim($_POST["referencia_fabrica"]) ."'";
        } else {
            $aux_referencia_fabrica = "'". trim($_POST["referencia"]) ."'";
        }
    }

    if ($login_fabrica == 20 ) {
        $aux_categoria = $_POST["categoria"];

        if (strlen($aux_categoria) == 0) {
            $aux_categoria = "null";
        }
    }else{
        $aux_categoria = "null";
    }

    if(strlen($_POST['validacao_cadastro']) > 0){
	$validacao_cadastro = $_POST['validacao_cadastro'];

	list($d,$m,$y) = explode("/",$validacao_cadastro);

	if (!checkdate($m, $d, $y)) { 
		$msg_erro["msg"]["obg"] = "Data de validação de cadastro inválida";
	}else{
		$aux_data_validacao = "'$y-$m-$d'";
	}

    }else{
	$aux_data_validacao = "null";
    }

    if (in_array($login_fabrica, [11,172])) {
        unset($fabricas);
        if (isset($_POST['fabrica_produto'])) {
            foreach ($_POST['fabrica_produto'] as $fb) {
                if ($fb == "A") {
                    $fabricas[] = 11;
                } else if ($fb == "P") {
                    $fabricas[] = 172;
                }
            }
        } else {
            $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
        }
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
        $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
        $msg_erro["campos"][]   = "ativo";

    }

    if (strlen($_POST["uso_interno_ativo"]) > 0){
        $aux_uso_interno_ativo = "'". trim($_POST["uso_interno_ativo"]) ."'";
    }else{
        $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
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
   
    if($login_fabrica == 178){
        $marcas = $_POST["marca"];
        $aux_marca = 'null';
        
        if (empty($marcas)){
            $msg_erro["msg"]["obg"] = "Preencha os campos obrigatórios";
            $msg_erro["campos"][]   = "marca";
        }
    }else{
        if (strlen($_POST["marca"]) > 0)                $aux_marca = trim($_POST["marca"]) ;
        else                                            $aux_marca = "null";
    }

    $pecas_reposicao = ($_POST['pecas_reposicao'][0] == 't') ? 't' : 'f';

    if ($login_fabrica == 15) { // HD 2398343
        $aux_radical_serie2 = strtoupper($aux_radical_serie2);
        $aux_radical_serie3 = strtoupper($aux_radical_serie3);
    }


    if (in_array($login_fabrica, array(131,146)) && empty($_POST["marca"])) {
        $msg_erro["msg"]["obg"] = traduz("Preencha os campos obrigatórios");
        $msg_erro["campos"][]   = "marca";
    }

    //$radical_serie = trim ($_POST ['radical_serie']);

    if (strlen($_POST["valor_troca"]) > 0)    $aux_valor_troca = "'".trim($_POST["valor_troca"])."'" ; //hd 7474 TAKASHI 23/11/07
    else                                    $aux_valor_troca = "null";

    if (strlen($_POST["observacao"]) > 0)    $observacao = trim($_POST["observacao"]) ; //113180
    else                                    $observacao = "null";

    $sistema_operacional = (strlen($_POST["sistema_operacional"]) > 0) ? trim($_POST["sistema_operacional"]) : "null";

    $serie_inicial = strtoupper($_POST["serie_inicial"]);
    $serie_final   = strtoupper($_POST["serie_final"]);
    $validar_serie = isset($_POST["validar_serie"]) ? 't' : 'f';//HD 256659

    if (in_array($login_fabrica, [3])) {
        $ativacao_automatica = isset($_POST["ativacao_automatica"]) ? 't' : 'f';
    }

    $troca_b_garantia = $_POST["troca_b_garantia"][0];
    $aux_troca_b_garantia = $troca_b_garantia;

    if (strlen($troca_b_garantia) == 0) {
        $aux_troca_b_garantia = "f";
    }

    $troca_b_faturada = $_POST["troca_b_faturada"][0];
    $aux_troca_b_faturada = $troca_b_faturada;

    if (strlen($troca_b_faturada) == 0) {
        $aux_troca_b_faturada = "f";
    }

    // $inibir_lista_basica = $_POST["inibir_lista_basica"][0];
    // $aux_inibir_lista_basica = $inibir_lista_basica;

    // if (strlen($inibir_lista_basica) == 0) {
    //     $aux_inibir_lista_basica = "f";
    // }

    if (strlen($_POST['link_img']) > 0) {
        $aux_link_img   = ($link_img=$_POST['link_img']);
    }

    $produto_critico         = $_POST['produto_critico'][0];
    $aux_produto_critico     = $produto_critico;

    $analise_obrigatoria = $_POST['analise_obrigatoria'][0];

    $troca_obrigatoria       = $_POST['troca_obrigatoria'][0];
    $aux_troca_obrigatoria   = $troca_obrigatoria;

    if($login_fabrica == 153){ //hd_chamado=2717074
        $reparo_na_fabrica       = $_POST['reparo_na_fabrica'][0];
        if ($reparo_na_fabrica != "t") {
            $reparo_na_fabrica = "f";
        }
        $reparo_na_fabrica       = json_encode(array("reparo_na_fabrica" => $reparo_na_fabrica));
        $reparo_na_fabrica       = str_replace("\\", "\\\\", $reparo_na_fabrica);
        $aux_reparo_na_fabrica   = $reparo_na_fabrica;
    }

    if($login_fabrica == 161){
        $insumo = ($_POST["insumo"][0] == "t") ? "t" : "f";

        $parametros_adicionais = array("insumo" => $insumo);
        $parametros_adicionais = json_encode($parametros_adicionais);
    }

    if(in_array($login_fabrica, [167, 203])){
        $suprimento = $_POST["suprimento"][0];
        $parametros_adicionais = array("suprimento" => $suprimento);
        $parametros_adicionais = json_encode($parametros_adicionais);
    }

    if (in_array($login_fabrica, [151]) && !empty($_POST['troca_direta'])) {
        $troca_direta = $_POST['troca_direta'][0];
        $aux_troca_direta = $troca_direta;
    }

	if (in_array($login_fabrica, [187,188]) && !empty($_POST['ressarcimento_obrigatoria'])) {
        $ressarcimento_obrigatoria = $_POST['ressarcimento_obrigatoria'][0];
        $aux_ressarcimento_obrigatoria = $ressarcimento_obrigatoria;
    }


    $intervencao_tecnica     = $_POST['intervencao_tecnica'][0];
    $aux_intervencao_tecnica = $intervencao_tecnica;

    $numero_serie_obrigatorio     = $_POST['numero_serie_obrigatorio'][0];
    $aux_numero_serie_obrigatorio = $numero_serie_obrigatorio;

    if (strlen($numero_serie_obrigatorio) == 0) $aux_numero_serie_obrigatorio = "f";
    if (strlen($troca_obrigatoria) == 0)        $aux_troca_obrigatoria = "f";
    if (strlen($produto_critico) == 0)          $aux_produto_critico = "f";
    if (strlen($intervencao_tecnica) == 0)      $aux_intervencao_tecnica = "f";
    if (strlen($troca_direta) == 0 && in_array($login_fabrica, [151])) $aux_troca_direta = "f";

    $produto_principal = $_POST['produto_principal'][0];
    $aux_produto_principal = $produto_principal;
    if (strlen($produto_principal) == 0) $aux_produto_principal = "f";

    $aux_locador = trim($_POST["locador"][0]);
    if (strlen($aux_locador) == 0) $aux_locador = "f";

    $lista_troca = trim($_POST["lista_troca"][0]);
    $aux_lista_troca = (strlen($lista_troca) == 0) ? "FALSE" : "TRUE";

    if($login_fabrica == 178){
        $fora_linha = trim($_POST["fora_linha"][0]);
        $aux_fora_linha = (strlen($fora_linha) == 0) ? "FALSE" : "TRUE";
    }

    if ($login_fabrica == 194){
        $percentual_tolerante = $_POST["percentual_tolerante"];
    }

    if($login_fabrica == 138){
        $multiplas_os       = $_POST["multiplas_os"][0];
        $aux_multiplas_os   = (strlen($multiplas_os) == 0) ? "FALSE" : "TRUE";

        $parametros_adicionais = array("multiplas_os" => $aux_multiplas_os);

        $parametros_adicionais = json_encode($parametros_adicionais);
    }

    if (in_array($login_fabrica, array(152,180,181,182))) {
         //     "os" => "O.S.",
         //    "equip" => "Equipamento",
         //    "hora" => "Hora",
        $entrega_por =  trim($_POST["entrega_por"]) ;
       if(strlen($entrega_por)==0){
            $entrega_por = "null";

        }

    }
    if ($login_fabrica == 14 or $login_fabrica == 66 or $login_fabrica == 11 or $login_fabrica == 172) {
        $aux_abre_os = (strlen(trim($_POST["abre_os"])) > 0) ?  trim($_POST["abre_os"]) : 'f';

        if ($login_fabrica <> 11 and $login_fabrica <> 172) {
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

    $fab = $login_fabrica;
    if (in_array($login_fabrica, [11,172])) {
        $fab = fabrica_produto_i($produto);
    }

    if ($login_fabrica<>1 and $login_fabrica <> 96){
        if (strlen($produto) > 0) {
            $sql_referencia = " AND tbl_produto.produto <> $produto AND tbl_produto.ativo IS TRUE";
        }

        $sql = "SELECT produto,referencia,descricao
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE tbl_linha.fabrica      = $fab
                AND tbl_produto.fabrica_i = $fab
        AND   tbl_produto.referencia = $aux_referencia
                $sql_referencia
                ";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0){
            $msg_erro["msg"][] = traduz("Já existe um produto cadastrado com esta referência.");
        }
    }

    if($login_fabrica <> 104){
        if ($troca_obrigatoria == 't' and $produto_critico =='t') {
            $msg_erro["msg"][] = traduz("Troca obrigatoria e produto critico não podem estar selecionados para o mesmo produto");
        }
    }
    //$aux_garantia_horas = !empty($garantia_horas) ? $garantia_horas : '';

    if ($login_fabrica == 87){
        $aux_garantia_horas = $garantia_horas;
    } else if ($login_fabrica == 193) {
        $aux_garantia_horas = ($_POST['hora_tecnica'][0] == 't' && !empty($_POST['valor_hora_tecnica'])) ? str_replace(",", ".", addslashes($_POST['valor_hora_tecnica'])) : 0;
    } else {
        $aux_garantia_horas = 0;
    }

    if($login_fabrica == 42){
        $aux_classe = $_POST['classe_produto'];
        $aux_classe_entrega_tecnica = $_POST['classe_produto_entrega_tecnica'];
    }

    if (in_array($login_fabrica,array(42,142,152,180,181,182))) {
        $entrega_tecnica = trim($_POST["entrega_tecnica"][0]);
        if ($entrega_tecnica <> "t") {
            $entrega_tecnica = "f";
        }
        if((in_array($login_fabrica, array(152,180,181,182))) and $entrega_tecnica == "t" and $entrega_por == "null") {
            $msg_erro["msg"][] = traduz("Selecionar tipo de entrega técnica do produto");
        }
    }

    if (in_array($login_fabrica, array(142))) {
        $deslocamento_km = trim($_POST["deslocamento_km"][0]);
        if ($deslocamento_km <> "t") {
            $deslocamento_km = "f";
        }

        $valores_adicionais = json_encode(array("deslocamento_km" => $deslocamento_km));

    }


    if($login_fabrica == 114){

        $exigir_selo = $_POST["exigir_selo"][0];

        $exigir_selo = (strlen($exigir_selo) == 0) ? "FALSE" : "TRUE";

        $insert_exigir_selo_1 = ",oem ";
        $insert_exigir_selo_2 = ",$exigir_selo ";

        $update_exigir_selo = ",oem = $exigir_selo ";

    }

    if (($usaProdutoGenerico || in_array($login_fabrica, array(169,170))) && isset($_POST['descricao_produto'])) {
        $observacao = $_POST['descricao_produto'];
    }

    if (in_array($login_fabrica, array(169,170))) {
        $parametros_adicionais = array();

        if (!empty($_POST['e_ticket'])) {
            $parametros_adicionais["eticket"] = 'true';
        }

        $garantia_estendida = (int) $_POST["garantia_estendida"];
        $parametros_adicionais["garantia_estendida"] = $garantia_estendida;

        $parametros_adicionais = "'".json_encode($parametros_adicionais)."'";

        $campo_paramentros_adicionais = ", parametros_adicionais";
        $value_paramentros_adicionais = ", $parametros_adicionais";

        $update_parametros_adicionais = ", parametros_adicionais = $parametros_adicionais";
    }

    if(in_array($login_fabrica, array(11,172))){

        $parametros_adicionais = array();

        $parametros_adicionais["codigo_interno"]             = $_POST["codigo_interno"];
        $parametros_adicionais["codigo_interno_obrigatorio"] = ($_POST["codigo_interno_obrigatorio"][0] == "t") ? "t" : "f";

        $parametros_adicionais = "'".json_encode($parametros_adicionais)."'";

        $campo_paramentros_adicionais = ", parametros_adicionais";
        $value_paramentros_adicionais = ", $parametros_adicionais";

        $update_parametros_adicionais = ", parametros_adicionais = $parametros_adicionais";
    }

    if(in_array($login_fabrica, array(1)) && !empty($_POST['data_descontinuado'])){

        $parametros_adicionais = array();

        $parametros_adicionais["data_descontinuado"] = $_POST["data_descontinuado"];

        $parametros_adicionais = "'".json_encode($parametros_adicionais)."'";

        $campo_paramentros_adicionais = ", parametros_adicionais";
        $value_paramentros_adicionais = ", $parametros_adicionais";

        $update_parametros_adicionais = ", parametros_adicionais = $parametros_adicionais";

    }
    if (count($msg_erro["msg"]) == 0) {
        if (strlen($produto) == 0) {
            $auditorLog = new AuditorLog('insert');

            $tpAuditor = "insert";
        } else {
            $auditorLog = new AuditorLog();
            $auditorLog->retornaDadosTabela('tbl_produto', array('produto'=>$produto, 'fabrica_i'=>$login_fabrica));

            $tpAuditor = "update";
        }

        $res = pg_query ($con,"BEGIN TRANSACTION");
        $cod_produto_insert = 0;

        if (strlen($produto) == 0) {

            if (in_array($login_fabrica,array(42,142,152,180,181,182))) {
                if(in_array($login_fabrica,array(152,180,181,182))) {
                    $aux_code_convention = " '$entrega_por' ";
                    $sql_entrega_tecnica_c = ", entrega_tecnica  ";
                    $valores_adicionais_c = ", valores_adicionais";
                    $sql_entrega_tecnica_v = ", '$entrega_tecnica' ";
                    $valores_adicionais_v = ", '$valores_adicionais'";
                }else{
                    $sql_entrega_tecnica_c = ", entrega_tecnica";
                    $valores_adicionais_c = ", valores_adicionais";
                    $sql_entrega_tecnica_v = ", '$entrega_tecnica'";
                    $valores_adicionais_v = ", '$valores_adicionais'";
                }
            }

            if ($login_fabrica == 3) {
                $valor_add_campo = ", parametros_adicionais";
                $valores_add  = json_encode(array("ativacao_automatica" => $ativacao_automatica));
                $campos_add   = ", '{$valores_add}'";
            }

            if($login_fabrica == 153){ //hd_chamado=2717074
                $sql_reparo = ", parametros_adicionais";
                $sql_reparo_value = ", '$reparo_na_fabrica' ";
            }

            if ( in_array($login_fabrica, [173]) ) {
                $sql_reparo = ", codigo_barra";
                $sql_reparo_value = ", '{$_POST['ean']}' ";
            }

            if(in_array($login_fabrica, [138,161,167,203])){
                $campo_paramentros_adicionais = ", parametros_adicionais";
                $value_paramentros_adicionais = ", '$parametros_adicionais'";
            }

            if ($login_fabrica == 148) {
                $pecas_reposicao = json_encode(array('pecas_reposicao' => $pecas_reposicao));

                $campo_paramentros_adicionais = ", parametros_adicionais";
                $value_paramentros_adicionais = ", '$pecas_reposicao'";
            }

            if ($login_fabrica == 171){
    
                if (!is_object($parametros_adc) || empty($parametros_adc)){
                    $parametros_adc = new Json($parametros_adc);
                }   
                if (isset( $_POST['apresentacao'])) {
                    $parametros_adc->apresentacao = $_POST['apresentacao'];
                }
                if (isset( $_POST['descricao_detalhada'])) {
                    $parametros_adc->descricao_detalhada = $_POST['descricao_detalhada'];
                }
                if (isset( $_POST['marca_detalhada'])) {
                    $parametros_adc->marca_detalhada = $_POST['marca_detalhada'];
                }
                if (isset( $_POST['emb'])) {
                    $parametros_adc->emb = $_POST['emb'];
                }
                if (isset( $_POST['unidade'])) {
                    $parametros_adc->unidade = $_POST['unidade'];
                }
                if (isset( $_POST['categoria'])) {
                    $parametros_adc->categoria = $_POST['categoria'];
                }
                if (isset( $_POST['ncm'])) {
                    $parametros_adc->ncm = $_POST['ncm'];
                }
                if (isset( $_POST['ii'])) {
                    $parametros_adc->ii = $_POST['ii'];
                }
                if (isset( $_POST['alt'])) {
                    $parametros_adc->alt = $_POST['alt'];
                }
                if (isset( $_POST['larg'])) {
                    $parametros_adc->larg = $_POST['larg'];
                }
                if (isset( $_POST['comp'])) {
                    $parametros_adc->comp = $_POST['comp'];
                }
                if (isset( $_POST['peso'])) {
                    $parametros_adc->peso = $_POST['peso'];
                }
                if (isset( $_POST['cod_barra'])) {
                    $parametros_adc->cod_barra = $_POST['cod_barra'];
                }
                if (isset( $_POST['custo_cip'])) {
                    $parametros_adc->custo_cip = $_POST['custo_cip'];
                } 

                $campo_paramentros_adicionais = ", parametros_adicionais";
                $value_paramentros_adicionais = ", '$parametros_adc'";     
            }

            if (in_array($login_fabrica, [177])) {

                if (!is_object($parametros_adc) || empty($parametros_adc)){
                    $parametros_adc = new Json($parametros_adc);
                }   

                if (strlen($_POST['lote'][0]) == 0) {
                    $aux_lote = "f";
                } else {
                    $aux_lote = "t";
                }               

                $parametros_adc->lote         = $aux_lote;
                $parametros_adc->peso         = $_POST['peso'];

                $campo_paramentros_adicionais = ", parametros_adicionais";
                $value_paramentros_adicionais = ", '$parametros_adc'";
            }

            if (in_array($login_fabrica, [151])) {
                $campo_parametros_adicionais_json = ", parametros_adicionais";
                $value_parametros_adicionais_json = ["troca_direta" => $aux_troca_direta];
                $value_parametros_adicionais_json = ", '" . json_encode($value_parametros_adicionais_json) . "'";
            }

			if (in_array($login_fabrica, [187,188])) {
                $campo_parametros_adicionais_json = ", parametros_adicionais";
                $value_parametros_adicionais_json = ["ressarcimento_obrigatoria" => $aux_ressarcimento_obrigatoria];
                $value_parametros_adicionais_json = ", '" . json_encode($value_parametros_adicionais_json) . "'";
			}

            if (in_array($login_fabrica, [178])) {  
                if (count($marcas)>0) {
                    $parametros_adic['marcas'] = implode(",",$marcas);                   
                }                
                if ($aux_fora_linha == 'TRUE') {
                    $parametros_adic['fora_linha'] = true;                    
                }
                $parametros_adic = json_encode($parametros_adic);
                $campo_paramentros_adicionais = ", parametros_adicionais";
                $value_paramentros_adicionais = ", '$parametros_adic'";    
            }

            if (in_array($login_fabrica, [193])) {
                $parametros_adic              = [];
                $parametros_adic['lancamento'] = (isset($_POST['lancamento']) && $_POST['lancamento'][0] == 't') ? true : false;
                $parametros_adic              = json_encode($parametros_adic);
                $campo_paramentros_adicionais = ", parametros_adicionais";
                $value_paramentros_adicionais = ", '$parametros_adic'";       
            }

            if ($login_fabrica == 194){
                $parametros_adic["percentual_tolerante"] = $percentual_tolerante;

                $parametros_adic = json_encode($parametros_adic);
                $campo_paramentros_adicionais = ", parametros_adicionais";
                $value_paramentros_adicionais = ", '$parametros_adic'"; 
            }

            if (in_array($login_fabrica, [35])) {

                $analise_obrigatoria = ($analise_obrigatoria == "t");

                $campo_paramentros_adicionais = ", parametros_adicionais";
                $value_parametros_adicionais_json = ["analise_obrigatoria" => $analise_obrigatoria];
                $value_paramentros_adicionais = ", '" . json_encode($value_parametros_adicionais_json) . "'";
            }

            if (in_array($login_fabrica, [195])) {
                $campo_paramentros_adicionais = ", parametros_adicionais";
                $value_parametros_adicionais_json = [
                                                        "potencia" => $potencia,
                                                        "vazao" => $vazao,
                                                        "pressao" => $pressao,
                                                        "corrente" => $corrente
                                                    ];
                $value_paramentros_adicionais = ", '" . json_encode($value_parametros_adicionais_json) . "'";
            }

            if (in_array($login_fabrica, [11,172]) && count($fabricas) > 0) {

                foreach ($fabricas as $fab_i) {
                    if ($fab_i != $login_fabrica) {
                        $linha_id = str_replace("'", "", $aux_linha);
                        $linha_id = trim($linha_id);

                        $sql_nova_linha = " SELECT linha 
                                            FROM tbl_linha 
                                            WHERE nome = (
                                                          SELECT nome 
                                                          FROM tbl_linha 
                                                          WHERE linha = $linha_id 
                                                          AND fabrica = $login_fabrica
                                                         ) 
                                            AND fabrica = $fab_i";
                        $res_nova_linha = pg_query($con, $sql_nova_linha);
                        if (pg_num_rows($res_nova_linha) > 0) {
                            $nova_linha = pg_fetch_result($res_nova_linha, 0, 'linha');
                        } else {
                            continue;
                        }

                        $aux_linha = "'".$nova_linha."'";

                        $familia_id = str_replace("'", "", $aux_familia);
                        $familia_id = trim($familia_id);

                        $sql_nova_familia = "   SELECT familia 
                                                FROM tbl_familia 
                                                WHERE descricao = (
                                                                    SELECT descricao 
                                                                    FROM tbl_familia 
                                                                    WHERE familia = $familia_id 
                                                                    AND fabrica = $login_fabrica
                                                                  ) 
                                                AND fabrica = $fab_i";
                        $res_nova_familia = pg_query($con, $sql_nova_familia);
                        if (pg_num_rows($res_nova_familia) > 0) {
                            $nova_familia = pg_fetch_result($res_nova_familia, 0, 'familia');
                        } else {
                            continue;
                        }
                        $aux_familia = "'".$nova_familia."'";
                    }
                        
                    $sql = "
                        INSERT INTO tbl_produto (
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
                            nome_comercial           ,
                            classificacao_fiscal     ,
                            ipi                      ,
                            fabrica_i                ,
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
                            categoria                ,
                            validar_serie
                            $sql_entrega_tecnica_c
                            $valores_adicionais_c
                            $insert_exigir_selo_1
                            $sql_reparo
                            $campo_paramentros_adicionais
                            $valor_add_campo
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
                            $aux_uso_interno_ativo           ,
                            '$nome_comercial'                ,
                            '$classificacao_fiscal'          ,
                            $aux_ipi                         ,
                            $fab_i                           ,
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
                            fnc_limpa_moeda($aux_valor_troca),
                            '$aux_troca_b_garantia'          ,
                            '$aux_troca_b_faturada'          ,
                            $aux_marca                       ,
                            $aux_produto_fornecedor          ,
                            $aux_capacidade                  ,
                            $aux_divisao                     ,
                            '$link_img'                      ,
                            '$observacao'                    ,
                            $sistema_operacional             ,
                            '$serie_inicial'                 ,
                            '$serie_final'                   ,
                            $aux_categoria                   ,
                            '$validar_serie'
                            $sql_entrega_tecnica_v
                            $valores_adicionais_v
                            $insert_exigir_selo_2
                            $sql_reparo_value
                            $value_paramentros_adicionais
                            $campos_add
                        ) RETURNING produto;
                    ";

                    $res = pg_query($con,$sql);
                    $erro = pg_last_error();
                    if(strlen($erro) > 0) {
                        $msg_erro['msg'][]= $erro;
                    }else{ 
                        $produto = pg_fetch_result($res, 0, "produto");
                    }
                    $cod_produto_insert = $aux_referencia;

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
                    $erroBd = pg_errormessage($con);
                }
            } else {
                ###INSERE NOVO REGISTRO
                $sql = "
                    INSERT INTO tbl_produto (                        
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
                        categoria                ,
			validar_serie		 ,
			data_validacao
                        $sql_entrega_tecnica_c
                        $valores_adicionais_c
                        $insert_exigir_selo_1
                        $sql_reparo
                        $campo_paramentros_adicionais
                        $valor_add_campo
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
                        fnc_limpa_moeda($aux_valor_troca),
                        '$aux_troca_b_garantia'            ,
                        '$aux_troca_b_faturada'            ,
                        $aux_marca                       ,
                        $aux_produto_fornecedor          ,
                        $aux_capacidade                  ,
                        $aux_divisao                     ,
                        '$link_img'                      ,
                        '$observacao'                    ,
                        $sistema_operacional             ,
                        '$serie_inicial'                 ,
                        '$serie_final'                   ,
                        $aux_categoria                   ,
			'$validar_serie'		 ,
			$aux_data_validacao
                        $sql_entrega_tecnica_v
                        $valores_adicionais_v
                        $insert_exigir_selo_2
                        $sql_reparo_value
                        $value_paramentros_adicionais
                        $campos_add
                    ) RETURNING produto;
                ";
                //die(nl2br($sql));
    			$res = pg_query($con,$sql);
    			$erro = pg_last_error();
    			if(strlen($erro) > 0) {
    				$msg_erro['msg'][]= $erro;
    			}else{ 
    				$produto = pg_fetch_result($res, 0, "produto");
    			}
    			$cod_produto_insert = $aux_referencia;   

                if(in_array($login_fabrica, [169,170]) AND $aux_familia != null){
                    //Função está no arquivo funcoes.php
                    if (!gravaValoresAdicionaisProduto($produto,$aux_familia)) {
                        $msg_erro['msg'][]= "Erro ao gravar a Família.";
                    }

                }

                #HD-6637419
                // pesquisa por produtos com a referencia semelhante
                if( $login_fabrica == 3 ){
                    // Trata o termo para pesquisa
                    $referenciaTratada = substr($referencia, 0, -1);
                    // Query que busca pela referencia do produto
                    $pgRes = pg_query($con, "SELECT DISTINCT comunicado FROM tbl_produto
                            INNER JOIN tbl_comunicado_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
                            WHERE tbl_produto.produto IN ( SELECT produto FROM tbl_produto WHERE referencia ILIKE '{$referenciaTratada}_' AND fabrica_i = $login_fabrica )");

                    $listaDeComunicados = pg_fetch_all($pgRes);

                    if( $listaDeComunicados ){
                        $listaDeComunicadosTratada = [];
                        foreach ($listaDeComunicados as $key => $value) {
                            $listaDeComunicadosTratada[] = $value['comunicado'];
                        }

                        $functionImplode = 'implode';
                        $pgRes = pg_query($con, "SELECT tbl_comunicado.comunicado, TO_CHAR(tbl_comunicado.data, 'DD/MM/YYYY') as data FROM tbl_comunicado WHERE tbl_comunicado.comunicado IN ({$functionImplode(',', $listaDeComunicadosTratada)}) ORDER BY tbl_comunicado.data DESC LIMIT 1");

                        $comunicadoAtual = pg_fetch_result($pgRes, 0, 'comunicado');
                        $dataAtual = pg_fetch_result($pgRes, 0, 'data');

                        pg_query($con, "INSERT INTO tbl_comunicado_produto (comunicado, produto) VALUES ({$comunicadoAtual}, {$produto})");
                    }else{
                        $pgRes = pg_query($con, "SELECT comunicado FROM tbl_comunicado WHERE tbl_comunicado.produto IN ( SELECT produto FROM tbl_produto WHERE referencia ILIKE '{$referenciaTratada}_' AND fabrica_i = $login_fabrica ) ORDER BY tbl_comunicado.data DESC LIMIT 1");
                        
                        $comunicadoAtual = pg_fetch_result($pgRes, 0, 'comunicado');

                        if( $comunicadoAtual ){
                            $pgRes = pg_query("SELECT produto FROM tbl_comunicado WHERE comunicado = {$comunicadoAtual}");

                            $produtoDoComunicadoAtual = pg_fetch_result($pgRes, 0, 'produto');

                            pg_query("UPDATE tbl_comunicado SET produto = NULL WHERE comunicado = {$comunicadoAtual}");

                            pg_query("INSERT INTO tbl_comunicado_produto (comunicado, produto) VALUES ({$comunicadoAtual}, {$produtoDoComunicadoAtual})");
                            pg_query("INSERT INTO tbl_comunicado_produto (comunicado, produto) VALUES ({$comunicadoAtual}, {$produto})");
                        }
                    }
                }

                #HD 335150 INICIO
                if (in_array($login_fabrica,$fabricaIntervaloSerie)){

                    $sql = "SELECT CURRVAL('seq_produto') AS produto;";
                    $res = pg_query($con,$sql);
                    $produto_aux = trim(pg_fetch_result($res, 0, produto));
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
                    $sql = "SELECT CURRVAL('seq_produto') AS produto;";
                        // $res = pg_query ($con,$sql);

                    $produto = trim(pg_fetch_result($res,0,produto));
                    //$sql = "SELECT produto,pais FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
                    //$res = pg_query ($con,$sql);
                    if(strlen($produto)>0){
                        $sql = "INSERT INTO tbl_produto_pais (produto,pais) VALUES ($produto,'BR')";
                        // $res = pg_query ($con,$sql);
                    }

                }
                /* ----------------------- FIM - IGOR HD 2846 -------------------------*/

                if($login_fabrica == 42){
                    $sql = "SELECT CURRVAL('seq_produto') AS produto;";
                    $res = pg_query ($con,$sql);
                    $produto = trim(pg_fetch_result($res, 0, produto));

                    if(!empty($aux_classe)){
                        $sql = "INSERT INTO tbl_classe_produto (classe,produto) VALUES ($aux_classe,$produto)";
                        $res = pg_query ($con,$sql);
                    }

                    if(!empty($aux_classe_entrega_tecnica)){
                        $sql = "INSERT INTO tbl_classe_produto (classe,produto) VALUES ($aux_classe_entrega_tecnica,$produto)";
                        $res = pg_query ($con,$sql);
                    }
                }

                $erroBd = pg_errormessage($con);
            }

        }else{

            if ($login_fabrica == 19) {
                $data_fabricacao = $_POST['data_fabricacao'];
                $parametros_adicionais = array("data_fabricacao" => $data_fabricacao);

                if (!empty($_POST['garantia2'])) {
                    $parametros_adicionais['garantia2'] = $_POST['garantia2'];
                }

                if (!empty($_POST['garantia3'])) {
                    $parametros_adicionais['garantia3'] = $_POST['garantia3'];
                }

                $parametros_adicionais = json_encode($parametros_adicionais);

                $update_parametros_adicionais = ", parametros_adicionais = '$parametros_adicionais'";
            }

            if (in_array($login_fabrica, [173])) {
                $ean = $_POST['ean'];

                $update_parametros_adicionais = ", codigo_barra = '$ean'";
            }

            if (in_array($login_fabrica,array(42,142,152,180,181,182))) {
                if(in_array($login_fabrica,array(152,180,181,182))) {
                    $aux_code_convention = " '$entrega_por' ";
                }

                $sql_entrega_tecnica = ", entrega_tecnica = '$entrega_tecnica'";
                $sql_valores_adicionais = ", valores_adicionais = '$valores_adicionais'";
            }

            if($login_fabrica == 153){ //hd_chamado=2717074
                $sql_reparo_update = ", parametros_adicionais = '$reparo_na_fabrica' ";
            }

            if(in_array($login_fabrica, [138,161,167,203])){
                $update_parametros_adicionais = ", parametros_adicionais = '$parametros_adicionais'";
            }

            if ($login_fabrica == 148) {
                $pecas_reposicao = json_encode(array('pecas_reposicao' => $pecas_reposicao));
                $update_parametros_adicionais = ", parametros_adicionais = '$pecas_reposicao'";
            }

            if ($login_fabrica == 171){
                if (!is_object($value_paramentros_adicionais) || empty($value_paramentros_adicionais)){
                    $value_paramentros_adicionais = new Json($value_paramentros_adicionais);
                }   
                if (isset( $_POST['apresentacao'])) {
                    $value_paramentros_adicionais->apresentacao = $_POST['apresentacao'];
                }
                if (isset( $_POST['descricao_detalhada'])) {
                    $value_paramentros_adicionais->descricao_detalhada = $_POST['descricao_detalhada'];
                }
                if (isset( $_POST['marca_detalhada'])) {
                    $value_paramentros_adicionais->marca_detalhada = $_POST['marca_detalhada'];
                }
                if (isset( $_POST['emb'])) {
                    $value_paramentros_adicionais->emb = $_POST['emb'];
                }
                if (isset( $_POST['unidade'])) {
                    $value_paramentros_adicionais->unidade = $_POST['unidade'];
                }
                if (isset( $_POST['categoria'])) {
                    $value_paramentros_adicionais->categoria = $_POST['categoria'];
                }
                if (isset( $_POST['ncm'])) {
                    $value_paramentros_adicionais->ncm = $_POST['ncm'];
                }
                if (isset( $_POST['ii'])) {
                    $value_paramentros_adicionais->ii = $_POST['ii'];
                }
                if (isset( $_POST['alt'])) {
                    $value_paramentros_adicionais->alt = $_POST['alt'];
                }
                if (isset( $_POST['larg'])) {
                    $parametros_adc->larg = $_POST['larg'];
                }
                if (isset( $_POST['comp'])) {
                    $value_paramentros_adicionais->comp = $_POST['comp'];
                }
                if (isset( $_POST['peso'])) {
                    $value_paramentros_adicionais->peso = $_POST['peso'];
                }
                if (isset( $_POST['cod_barra'])) {
                    $value_paramentros_adicionais->cod_barra = $_POST['cod_barra'];
                }
                if (isset( $_POST['custo_cip'])) {
                    $value_paramentros_adicionais->custo_cip = $_POST['custo_cip'];
                }    
                $update_parametros_adicionais .= ", parametros_adicionais = '$value_paramentros_adicionais'";
            }

            if (in_array($login_fabrica, [177])) {

                if (!is_object($value_paramentros_adicionais) || empty($value_paramentros_adicionais)){
                    $value_paramentros_adicionais = new Json($value_paramentros_adicionais);
                }  

                if (strlen($_POST['lote'][0]) == 0) {
                    $aux_lote = "f";
                } else {
                    $aux_lote = "t";
                }    

                // pegando os valores adicionais, já cadastrados.
                $sql_param = "SELECT parametros_adicionais FROM tbl_produto WHERE produto={$produto} AND referencia={$aux_referencia}";
                $res_param = pg_query($con, $sql_param);
                if (pg_num_rows($res_param) > 0) {
                    $parametros_adicionais_res            = json_decode(pg_fetch_result($res_param, 0, 'parametros_adicionais'), true); 

                    $parametros_adicionais_res['lote']    = $aux_lote;
                    $parametros_adicionais_res['peso']    = $_POST['peso'];

                    $value_paramentros_adicionais         = json_encode($parametros_adicionais_res);
                } else {
                    $value_paramentros_adicionais->lote   = $aux_lote;
                }
                
                $update_parametros_adicionais      .= ", parametros_adicionais = '$value_paramentros_adicionais'";
            }

            if (in_array($login_fabrica, [35])) {

                $analise_obrigatoria = ($analise_obrigatoria == "t");

                $sql_param = "SELECT parametros_adicionais 
                              FROM tbl_produto 
                              WHERE produto={$produto}";
                $res_param = pg_query($con, $sql_param);

                $parametrosAdicionaisCad = json_decode(pg_fetch_result($res_param, 0, 'parametros_adicionais'), true);
                $parametrosAdicionaisCad["analise_obrigatoria"] = $analise_obrigatoria;
                $parametrosAdicionaisCad = json_encode($parametrosAdicionaisCad);

                $update_parametros_adicionais = ", parametros_adicionais = '{$parametrosAdicionaisCad}'";
            }

            if ($login_fabrica == 3) {
                $valores_add  = json_encode(array("ativacao_automatica" => $ativacao_automatica));
                $campos_add   = ", parametros_adicionais = '{$valores_add}'";
            }
            if (in_array($login_fabrica, [178])) { 
                $parametros_adic = ""; 
                if (count($marcas)>0) {
                    $parametros_adic['marcas'] = implode(",",$marcas);                    
                }
                if ($aux_fora_linha == 'TRUE') {
                    $parametros_adic['fora_linha'] = true;                    
                }
                $parametros_adic = json_encode($parametros_adic);
                $update_parametros_adicionais      .= ", parametros_adicionais = '$parametros_adic'";   
            }

            if (in_array($login_fabrica, [193])) {
                $parametros_adic               = [];
                $parametros_adic['lancamento']  = (isset($_POST['lancamento']) && $_POST['lancamento'][0] == 't') ? true : false;
                $parametros_adic               = json_encode($parametros_adic);
                $update_parametros_adicionais .= ", parametros_adicionais = '$parametros_adic'";   
            }

			if (in_array($login_fabrica, [151,187,188,194])) {
                $sql_parametros_produto = "
                    SELECT 
                        parametros_adicionais
                    FROM tbl_produto
                    WHERE produto = {$produto}
                    AND fabrica_i = {$login_fabrica};
                ";
                $r_parametros_produto = pg_query($con, $sql_parametros_produto);
                $r_parametros_produto = pg_fetch_result($r_parametros_produto, 0, 'parametros_adicionais');

				if($login_fabrica == 151) {
					if (strlen($r_parametros_produto) > 0 && !empty($r_parametros_adicionais)) {
						$parametros_produto = json_decode($r_parametros_produto, true);
						$parametros_produto["troca_direta"] = $aux_troca_direta;
					}

					$parametros_produto["troca_direta"] = $aux_troca_direta;
				}else if ($login_fabrica == 194){
                    if (strlen($r_parametros_produto) > 0 && !empty($r_parametros_adicionais)) {
                        $parametros_produto = json_decode($r_parametros_produto, true);
                    }
                    $parametros_produto["percentual_tolerante"] = $percentual_tolerante;
                }else{
					if (strlen($r_parametros_produto) > 0 && !empty($r_parametros_adicionais)) {
						$parametros_produto = json_decode($r_parametros_produto, true);
						$parametros_produto["ressarcimento_obrigatoria"] = $aux_ressarcimento_obrigatoria;
					}

					$parametros_produto["ressarcimento_obrigatoria"] = $aux_ressarcimento_obrigatoria;
				}
                $update_parametros_adicionais = ", parametros_adicionais = '" . json_encode($parametros_produto) . "'";
            }

            if (in_array($login_fabrica, [195])) {
                $value_parametros_adicionais_json = [
                                                        "potencia" => $potencia,
                                                        "vazao" => $vazao,
                                                        "pressao" => $pressao,
                                                        "corrente" => $corrente
                                                    ];
                $parametros_adic               = json_encode($value_parametros_adicionais_json);
                $update_parametros_adicionais .= ", parametros_adicionais = '$parametros_adic'";   
            }



            if (in_array($login_fabrica, [11,172]) && count($fabricas) > 0) {
    
                $fab_i = fabrica_produto_i($produto);

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
                                valor_troca              = fnc_limpa_moeda($aux_valor_troca)      ,
                                troca_garantia           = '$aux_troca_b_garantia'                  ,
                                troca_faturada           = '$aux_troca_b_faturada'                  ,
                                marca                    = $aux_marca                             ,
                                produto_fornecedor       = $aux_produto_fornecedor                ,
                                capacidade               = $aux_capacidade                        ,
                                divisao                  = $aux_divisao                           ,
                                imagem                   = '$link_img'                            ,
                                observacao               = '$observacao'                          ,
                                sistema_operacional      = $sistema_operacional                   ,
                                serie_inicial            = '$serie_inicial'                       ,
                                serie_final              = '$serie_final'                         ,
                                categoria                = $aux_categoria                         ,
                                validar_serie            = '$validar_serie'
                                $sql_entrega_tecnica
                                $sql_valores_adicionais
                                $update_exigir_selo
                                $sql_reparo_update
                                $update_parametros_adicionais
                                $update_produto_generico
                                $campos_add

                        FROM tbl_linha
                        WHERE  tbl_produto.linha         = tbl_linha.linha
                        AND    tbl_linha.fabrica         = $fab_i
                        AND    tbl_produto.produto       = $produto;";

                $res = pg_query ($con,$sql);
                $erro = pg_last_error();
                
                if(strlen($erro) > 0) {
                    $msg_erro['msg'][]= $erro;
                }
            } else {

                if(in_array($login_fabrica, [169,170])){
                    $sql = "SELECT familia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
                    $res = pg_query($con,$sql);
                    $familia_atual = pg_fetch_result($res, 0, 'familia');
                }

                ###ALTERA REGISTRO
                $sql = "UPDATE tbl_produto SET
                                data_validacao		 = $aux_data_validacao 			  ,
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
                                valor_troca              = fnc_limpa_moeda($aux_valor_troca)      ,
                                troca_garantia           = '$aux_troca_b_garantia'                  ,
                                troca_faturada           = '$aux_troca_b_faturada'                  ,
                                marca                    = $aux_marca                             ,
                                produto_fornecedor       = $aux_produto_fornecedor                ,
                                capacidade               = $aux_capacidade                        ,
                                divisao                  = $aux_divisao                           ,
                                imagem                   = '$link_img'                            ,
                                observacao               = '$observacao'                          ,
                                sistema_operacional      = $sistema_operacional                   ,
                                serie_inicial            = '$serie_inicial'                       ,
                                serie_final              = '$serie_final'                         ,
                                categoria                = $aux_categoria                         ,
                                validar_serie            = '$validar_serie'
                                $sql_entrega_tecnica
                                $sql_valores_adicionais
                                $update_exigir_selo
                                $sql_reparo_update
                                $update_parametros_adicionais
                                $update_produto_generico
                                $campos_add

                        FROM tbl_linha
                        WHERE  tbl_produto.linha         = tbl_linha.linha
                        AND    tbl_linha.fabrica         = $login_fabrica
                        AND    tbl_produto.produto       = $produto;";

                //die(nl2br($sql));
                $res = pg_query ($con,$sql);
                $erro = pg_last_error();
                if(strlen($erro) > 0) {
                    $msg_erro['msg'][]= $erro;
                }

                if(in_array($login_fabrica, [169,170])){
                    $nova_familia = str_replace("'", "", $aux_familia);

                    if($familia_atual <> $nova_familia){
                        //Função está no arquivo funcoes.php
                        if(!gravaValoresAdicionaisProduto($produto,$familia_atual,$nova_familia)){
                            $msg_erro['msg'][] = "Erro ao alterar a Família.";
                        }
                    }
                }

                #HD 335150 INICIO
                if(in_array($login_fabrica,$fabricaIntervaloSerie)){

                    $linhas = $_POST['qtde_itens'];

                    for($i=0;$i < $linhas; $i++){

                        $serie_in = trim($_POST['serie_in_'.$i]);
                        $serie_out = trim($_POST['serie_out_'.$i]);
                        $produto_serie_in_out = $_POST['produto_serie_in_out_'.$i];
                        $excluir = $_POST['apagar_serie_'.$i];
                        //echo "ProdSérieInOut: $produto_serie_in_out --- SerieIn: $serie_in --- SerieOut: $serie_out --- Ecluir: $excluir <br> ";
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

                                $msg_erro["msg"][] = traduz("Número de Série $serie_in Inválido <br> ");

                            }elseif( (in_array($serie_in_first, array(9)) and strlen($serie_in_lote) < 3 ) or substr($serie_in_lote, 0,1) == 0 ){

                                $msg_erro["msg"][] = traduz("Número de Série $serie_in Inválido <br>");

                            }

                            //Série Out Lote
                            if ( (in_array($serie_out_first, array(1,4)) and strlen($serie_out_lote) < 4 ) or substr($serie_out_lote, 0,1) ==  0 ){

                                $msg_erro["msg"][] = traduz("Número de Série $serie_out Inválido <br>");

                            }elseif( (in_array($serie_out_first, array(9)) and strlen($serie_out_lote) < 3) or substr($serie_out_lote, 0,1) == 0 ){

                                $msg_erro["msg"][] = traduz("Número de Série $serie_out Inválido <br>");

                            }

                            //Anos
                            $qtde_de_anos_for = $ano_atual - 1994;
                            for ($x=1; $x <= $qtde_de_anos_for; $x++) {
                                $anos_verificar[$x] = $ano_letra_inicial++;
                            }
                            if (!in_array($ano_serie_in, $anos_verificar)){
                                $msg_erro["msg"][] = traduz("Número de Série $serie_in Inválido <br>");
                            }

                            if( !in_array($ano_serie_out, $anos_verificar) ){

                                $msg_erro["msg"][] = traduz("Número de Série $serie_out Inválido <br>");

                            }

                        }

                        if (strlen($produto_serie_in_out) > 0 && strlen($excluir) == 0 && (count($msg_erro["msg"])==0) ){
                            $sql_serie_in_out = "
                                UPDATE tbl_produto_serie SET
                                    fabrica       = $login_fabrica  ,
                                    produto       = $produto        ,
                                    serie_inicial = '$serie_in'     ,
                                    serie_final   = '$serie_out'
                                WHERE   tbl_produto_serie.produto = $produto
                                AND     tbl_produto_serie.produto_serie = $produto_serie_in_out";
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

                    if(pg_num_rows($res) > 0){

                        if(empty($aux_classe) && empty($aux_classe_entrega_tecnica)){
                            $sql = "DELETE FROM tbl_classe_produto WHERE produto = $produto";
                            $res = pg_query ($con,$sql);
                        } else {

                            $sql = "SELECT classe FROM tbl_classe_produto WHERE produto = $produto";
                            $res = pg_query($con, $sql);

                            if(pg_num_rows($res) > 0){

                                for($i = 0; $i < pg_num_rows($res); $i++){

                                    $classe = pg_fetch_result($res, $i, "classe");

                                    $sql_classe = "SELECT entrega_tecnica FROM tbl_classe WHERE classe = {$classe}";
                                    $res_classe = pg_query($con, $sql_classe);

                                    $entrega_tecnica = pg_fetch_result($res_classe, 0, "entrega_tecnica");

                                    if($entrega_tecnica == "f"){
                                        $classe_aux_produto = $classe;
                                    }else{
                                        $classe_aux_produto_entrega_tecnica = $classe;
                                    }

                                }

                            }

                            /* Classe */

                            if(!empty($aux_classe) && !empty($classe_aux_produto)){

                                $sql = "UPDATE tbl_classe_produto SET classe = $aux_classe WHERE produto = $produto AND classe = $classe_aux_produto";
                                $res = pg_query ($con,$sql);
                            }

                            if(!empty($aux_classe) && empty($classe_aux_produto)){
                                $sql = "INSERT INTO tbl_classe_produto (classe,produto) VALUES ($aux_classe,$produto)";
                                $res = pg_query ($con,$sql);
                            }

                            if(empty($aux_classe) && !empty($classe_aux_produto)){
                                $sql = "DELETE FROM tbl_classe_produto WHERE produto = $produto AND classe = $classe_aux_produto";
                                $res = pg_query ($con,$sql);
                            }

                            /* Classe Entrega Técnica */

                            if(!empty($aux_classe_entrega_tecnica) && !empty($classe_aux_produto_entrega_tecnica)){
                                $sql = "UPDATE tbl_classe_produto SET classe = $aux_classe_entrega_tecnica WHERE produto = $produto AND classe = $classe_aux_produto_entrega_tecnica";
                                $res = pg_query ($con,$sql);
                            }

                            if(!empty($aux_classe_entrega_tecnica) && empty($classe_aux_produto_entrega_tecnica)){
                                $sql = "INSERT INTO tbl_classe_produto (classe,produto) VALUES ($aux_classe_entrega_tecnica,$produto)";
                                $res = pg_query ($con,$sql);
                            }

                            if(empty($aux_classe_entrega_tecnica) && !empty($classe_aux_produto_entrega_tecnica)){
                                $sql = "DELETE FROM tbl_classe_produto WHERE produto = $produto AND classe = $classe_aux_produto_entrega_tecnica";
                                $res = pg_query ($con,$sql);
                            }

                        }

                    }else{

                        if(!empty($aux_classe)){
                            $sql = "INSERT INTO tbl_classe_produto (classe,produto) VALUES ($aux_classe,$produto)";
                            $res = pg_query ($con,$sql);
                        }

                        if(!empty($aux_classe_entrega_tecnica)){
                            $sql = "INSERT INTO tbl_classe_produto (classe,produto) VALUES ($aux_classe_entrega_tecnica,$produto)";
                            $res = pg_query ($con,$sql);
                        }

                    }
                }
            }
        }

        //--=== TRADUÇÃO DOS PRODUTOS =========================================
        $idioma           = $_POST["idioma"];
        $idioma_novo      = $_POST["idioma_novo"];
        $descricao_idioma = $_POST["descricao_idioma"];

        if (strlen($idioma) == 2) {

            $sql = "SELECT produto FROM tbl_produto_idioma WHERE produto = $produto";

            $res = pg_query($con,$sql);

            if (pg_numrows($res)==0){

                $sql = "INSERT INTO tbl_produto_idioma (produto,
                    descricao,
                    idioma
                ) VALUES ($produto,
                    '$descricao_idioma',
                    '$idioma')";
            }else{
                $sql = "UPDATE tbl_produto_idioma SET descricao = '$descricao_idioma'
                    WHERE produto = $produto
                    AND idioma = '$idioma'";
            }

            $res      = pg_query($con,$sql);

            /* $msg_erro["msg"][]= pg_errormessage($con);

            if(count($msg_erro)){
                print_r($msg_erro); exit;
            } */

        }

        $qtde_item = $_POST['qtde_item'];

        $AuditorProdOpcao = new AuditorLog();
        $AuditorProdOpcao->setMultiple(100);

        $erro_aud = 0;

		$auditor_to = array();
		if(!empty($produto)) {
			$sql_to ="select array_to_string(array_agg(referencia || ' - ' || descricao || ' KIT: ' || kit), ' , ')  from tbl_produto_troca_opcao join tbl_produto on tbl_produto.produto = produto_opcao where tbl_produto_troca_opcao.produto = $produto";
			$res_to = pg_query($con,$sql_to);
			if(pg_num_rows($res_to) > 0) {
				$auditor_to['antes']['produto_troca'] = pg_fetch_result($res_to,0,0);
			}else{
				$auditor_to['antes']['produto_troca'] = '';
			}
		}
        for ($i = 0; $i < $qtde_item; $i++) {

            $referencia_opcao    = $_POST["referencia_opcao_".$i];
            $descricao_opcao     = $_POST["descricao_opcao_".$i];
            $produto_opcao       = $_POST["produto_opcao_".$i];
            $produto_troca_opcao = $_POST["produto_troca_opcao_".$i];
            $voltagem_opcao      = $_POST["voltagem_opcao_".$i];
            $kit_opcao           = $_POST["kit_opcao_".$i];

            if (strlen($voltagem_opcao) == 0 and strlen($referencia_opcao) > 0) {
                $msg_erro["msg"][]= traduz("Informe a voltagem para o produto de opção troca $referencia_opcao. Clique na lupa para pesquisar.");
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
                    $msg_erro["msg"][] = traduz("Produto com referência $referencia_opcao e voltagem $voltagem_opcao não encontrado.");
                } else {
                    $produto_opcao = pg_fetch_result($res,0,0);
                }

            }

            if (count($msg_erro) ==0) {

                if (strlen($produto_troca_opcao) > 0) {

                    if (strlen($referencia_opcao) == 0) {

                        $sql = "
                            DELETE FROM tbl_produto_troca_opcao
                            WHERE produto_troca_opcao = {$produto_troca_opcao};
                        ";

                        $res = pg_query($con, $sql);

                        if (strlen( pg_errormessage($con) ) > 0) {
                            $msg_erro["msg"][] = pg_errormessage($con) ;
                        }

                    } else {

                        $sql = "
                            UPDATE tbl_produto_troca_opcao
                            SET produto_opcao = {$produto_opcao}, kit = {$kit_opcao}
                            WHERE produto_troca_opcao = {$produto_troca_opcao};
                        ";

                        $res = pg_query($con, $sql);

                        if (strlen(pg_errormessage($con) ) > 0) {
                            $msg_erro["msg"][]= pg_errormessage($con);
                        }

                    }

                } else {

                    if (strlen($referencia_opcao)>0) {
                        $sql = "
                            INSERT INTO tbl_produto_troca_opcao (produto, produto_opcao, kit)
                            VALUES ({$produto}, {$produto_opcao}, {$kit_opcao});
                        ";

                        $res = pg_query($con, $sql);

                        if (strlen( pg_errormessage($con) ) > 0) {
                            $msg_erro["msg"][]= pg_errormessage($con) ;
                        }

                    }

                }

            }

        }

		if(!empty($produto)) {
			$res_to = pg_query($con,$sql_to);
			if(pg_num_rows($res_to) > 0) {
				$auditor_to['depois']['produto_troca'] = pg_fetch_result($res_to,0,0);
			}else{
				$auditor_to['depois']['produto_troca'] = '';
			}
		}

        if (in_array($login_fabrica, array(3))) {//HD 325481

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

        // Elgin cadastro de Preço
        if (in_array($login_fabrica, array(117)) AND $aux_preco != "null" ) {
            $sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tabela_garantia IS TRUE AND ativa IS TRUE";
            $resTabela = pg_query($con,$sql);

            $sql = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = $aux_referencia";
            $resPeca = pg_query($con,$sql);

            if(pg_num_rows($resPeca) > 0){
                $peca = pg_fetch_result($resPeca,0,'peca');

                for($x = 0; $x < pg_num_rows($resTabela); $x++){
                    $tabela = pg_fetch_result($resTabela,0,'tabela');

                    $sql = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
                    $resX = pg_query($con,$sql);

                    if(pg_num_rows($resX) == 0){
                        $sql = "INSERT INTO tbl_tabela_item(peca,tabela,preco) VALUES($peca,$tabela, fnc_limpa_moeda($aux_preco))";
                    }else{
                        $sql = "UPDATE tbl_tabela_item SET preco = fnc_limpa_moeda($aux_preco) WHERE peca = $peca AND tabela = $tabela";
                    }
                    $resS = pg_query($con,$sql);
                }
            }
            if (pg_last_error()) {
                $msg_erro["msg"] = traduz("Erro ao inserir preço do produto!");
            }
        }

        if (in_array($login_fabrica,array(158)) && count($msg_erro["msg"])==0) {
            $authorizationKey = $chave_persys;

            /* PEGANDO O CÓDIGO DA FAMILIA */
            $sql = "SELECT codigo_familia FROM tbl_familia WHERE familia = ".str_replace("'", "", $aux_familia);
            $res = pg_query($con, $sql);
            $return = pg_fetch_assoc($res);
            $cod_categoria = $return['codigo_familia'];
            $ch = curl_init();

            if ($cod_produto_insert !== 0) { //INSERT
                $url = 'recurso/equipamento';
                $row['codigo'] = str_replace("'", "", $cod_produto_insert);
                $row['equipamento'] = utf8_encode(str_replace("'", "", $aux_descricao));
                $row['medida'] = array("id" => '301');
                $row['statusModel']  = '1';
                if (str_replace("'", "", $aux_ativo) == 'f') {
                    $campos['statusModel'] = '0';
                }

                $json = json_encode($row);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
            }else{ //UPDATE
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, 'http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/'.str_replace("'", "", $aux_referencia));
                curl_setopt($ch2, CURLOPT_HEADER, FALSE);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    "Authorizationv2: $authorizationKey"
                ));
                $equipamento = curl_exec($ch2);
        $equipamento = json_decode($equipamento, true);

        if (empty($equipamento['id'])) {
            $cod_produto_insert = $aux_referencia;
            $url = 'recurso/equipamento';
            $row['codigo'] = str_replace("'", "", $aux_referencia);
            $row['equipamento'] = utf8_encode(str_replace("'", "", $aux_descricao));
            $row['medida'] = array("id" => '301');
            $row['statusModel']  = '1';
            if (str_replace("'", "", $aux_ativo) == 'f') {
                $campos['statusModel'] = '0';
            }
            $json = json_encode($row);
            curl_setopt($ch, CURLOPT_POST, TRUE);
                    curl_setopt($ch, CURLOPT_HEADER, FALSE);
        } else {
                    $url = 'recurso/equipamento/'.$equipamento['id'];
                    $campos['equipamento']  = utf8_encode(str_replace("'", "", $aux_descricao));
            $campos['statusModel']  = '1';

                    if (str_replace("'", "", $aux_ativo) == 'f') {
                        $campos['statusModel'] = '0';
            }

                    $json = json_encode($campos);

                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }
            }

            curl_setopt($ch, CURLOPT_URL, 'http://telecontrol.eprodutiva.com.br/api/'.$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorizationv2: $authorizationKey"
    ));

        $result = curl_exec($ch);
            if (empty($result)) {
                $msg_erro["msg"][] = traduz('Erro ao tentar atualizar o aplicativo móvel!');
            }else{
                if (empty($cod_produto_insert)) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/'.$equipamento['id']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Content-Type: application/json",
                        "Authorizationv2: $authorizationKey"
                    ));
                    $result = curl_exec($ch);
                    $result = json_decode($result, true);

                    $inserido = 0;
                    foreach ($result['categorias'] as $array_categoria) {
                        if($array_categoria['categoria']['codigo'] !== trim($cod_categoria)){
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, 'http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/'.$equipamento['id'].'/categoria/'.$array_categoria['categoria']['id']);

                            unset($campos);
                            $campos['statusModel'] = "0";
                            $json = json_encode($campos);

                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                "Content-Type: application/json",
                                "Authorizationv2: $authorizationKey"
                            ));
                            $result = curl_exec($ch);
                            curl_close($ch);

                            if (!$result) {
                                $msg_erro["msg"][] = traduz('Erro ao tentar atualizar o aplicativo móvel!');
                                break;
                            }
                        }else{
                            $inserido = 1;
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, 'http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/'.$equipamento['id'].'/categoria/'.$array_categoria['categoria']['id']);

                            unset($campos);
                            $campos['statusModel'] = "1";
                            $json = json_encode($campos);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                "Content-Type: application/json",
                                "Authorizationv2: $authorizationKey"
                            ));
                            $result = curl_exec($ch);
                            if (!$result) {
                                $msg_erro["msg"][] = traduz('Erro ao tentar atualizar o aplicativo móvel!');
                                break;
                            }
                        }
                    }
                }
                if ($cod_produto_insert !== 0 ||($inserido !== 1 && count($msg_erro["msg"])==0)) {
                    if ($cod_produto_insert !== 0) {
                        $url = 'http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/'.str_replace("'", "", $cod_produto_insert).'/categorias';
                    }else{
                        $url = 'http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/'.$equipamento['id'].'/categorias';
                    }

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);

                    unset($campos);
                    $campos['categoria'] = array('codigo' => trim($cod_categoria));
                    $json = json_encode($campos);

                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Content-Type: application/json",
                        "Authorizationv2: $authorizationKey"
                    ));
                    $result = curl_exec($ch);

                    if (!$result) {
                        $msg_erro["msg"][] = traduz('Erro ao tentar atualizar o aplicativo móvel!');
                    }
                }
            }
        }


        if (count($msg_erro["msg"]) ==0) {

            $res = pg_query ($con,"COMMIT TRANSACTION");
            $msg = "Gravado com Sucesso";
            if ($tpAuditor == "insert") {
                $auditorLog->retornaDadosTabela('tbl_produto', array('produto'=>$produto, 'fabrica_i'=>$login_fabrica))
                       ->enviarLog('insert', "tbl_produto", $login_fabrica."*".$produto);
            } else {
                $auditorLog->retornaDadosTabela()
                       ->enviarLog('update', "tbl_produto", $login_fabrica."*".$produto, "", "", $auditor_to);
            }

            unset($observacao);
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
            $reparo_na_fabrica          = $_POST['reparo_na_fabrica'][0];
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
            if(in_array($login_fabrica, [19])){
                $validacao_cadastro     = $_POST["validacao_cadastro"];
            }
            $lista_troca                = $_POST["lista_troca"][0];
            $valor_troca                = $_POST["valor_troca"];
            $troca_b_garantia           = $_POST["troca_b_garantia"][0];
            $troca_b_faturada           = $_POST["troca_b_faturada"][0];
            //$inibir_lista_basica        = $_POST["inibir_lista_basica"][0];
            $sistema_operacional        = $_POST['sistema_operacional'];
            $serie_inicial              = $_POST['serie_inicial'];
            $serie_final                = $_POST['serie_final'];
            $aux_classe                 = $_POST['classe_produto'];
            $aux_classe_entrega_tecnica = $_POST['classe_produto_entrega_tecnica'];
            $aux_categoria              = $_POST['categoria'];
            $entrega_tecnica            = $_POST["entrega_tecnica"];

            if (in_array($login_fabrica, array(171))){
                $apresentacao             = $_POST["apresentacao"];
                $descricao_detalhada      = $_POST["descricao_detalhada"];
                $marca_detalhada          = $_POST["marca_detalhada"];
                $unidade                  = $_POST["unidade"];
                $emb                      = $_POST["emb"];
                $categoria                = $_POST["categoria"];
                $ncm                      = $_POST["ncm"];
                $ii                       = $_POST["ii"];
                $alt                      = $_POST["alt"];
                $comp                     = $_POST["comp"];
                $peso                     = $_POST["peso"];
                $cod_barra                = $_POST["cod_barra"];
                $custo_cip                = $_POST["custo_cip"];
                $larg                     = $_POST["larg"];
            } else if (in_array($login_fabrica, [177])) {
                $peso                     = $_POST["peso"];
            }

            if ($usaProdutoGenerico || in_array($login_fabrica, array(169,170))) {
                $observacao             = $_POST["descricao_produto"];
            }

            if (strpos ($erroBd,"duplicate key violates unique constraint \"tbl_produto_referencia_pesquisa\"") > 0)
                $msg_erro["msg"][] = traduz("Referência para esta linha de produtos já existe e não pode ser duplicada.");

            if (strpos ($erroBd,"duplicate key violates unique constraint \"tbl_produto_unico\"") > 0)
                $msg_erro["msg"][] = traduz("Referência para esta linha de produtos já existe e não pode ser duplicada.");
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        }

    }//fim if msg erro

}
//  HD 96953 - Fotos de produtos para a Britânia
if (($login_fabrica == 3 or ($login_fabrica > 80 && $login_fabrica < 138)) && (count($msg_erro["msg"]) ==0) ) {
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

        $a_foto  = $_FILES["arquivo"];
        $produto = $_REQUEST["produto"];

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

            if (in_array($Tipo, array("image/gif", "image/jpg", "image/jpeg", "image/png", "image/bmp", "gif", "jpg", "jpeg", "png", "bmp")) /* preg_match('/^image\/(x-ms-bmp|bmp|x-bmp|pjpeg|jpeg|png|x-png|gif|jpg|jpeg|JPEG)$/', $Tipo) */) {
                if (!is_uploaded_file($Tmpname)) {
                    $msg_erro["msg"][] = traduz("Não foi possível efetuar o upload.");
                } else {
                    $ext = substr($Nome, strrpos($Nome, ".")+1);
                    if (strlen($Nome) == 0 and $ext <> "") {
                        $ext = $Nome;
                    }

                    list($void, $tipo_MIME) = explode('/', $Tipo);
                    $Caminho_foto   = $Destino . "$produto.jpg";
                    $Caminho_thumb  = $DestinoP. "$produto.jpg";

                    // Salvar o arquivo de imagem se já existir
                    $arq_ant = glob("$Destino$produto.{gif,GIF,png,PNG,jpg,JPG, jpeg, JPEG}", GLOB_BRACE);
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
                        $msg_erro["msg"][] = traduz('Não foi possível adicionar a imagem, formato não reconhecido.');
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
                $msg_erro["msg"][] = traduz("O formato do arquivo $Nome não é permitido!<br>");
            }

        } else {
            switch ($a_foto['error']) {
                case 0: if ($a_foto['name'] != '' and $a_foto['tmp_name'] == '') $msg_erro["msg"][] = traduz('Erro ao processar o arquivo ') . $a_foto['name'] . '.<br>'; break;
                case 1: $msg_erro["msg"][] = traduz("O tamanho do arquivo é maior do que o permitido (2Mb)!<br>"); break;
                case 2: $msg_erro["msg"][] = traduz("O tamanho do arquivo é maior do que o permitido (1Mb)!<br>"); break;
                case 3: $msg_erro["msg"][] = traduz('O arquivo não foi enviado completo, tente novamente.<br>'); break;
                case 4: if ($a_foto['name'] != '') $msg_erro["msg"][] = traduz('Arquivo não recebido.<br>'); break;
                default: $msg_erro["msg"][] = traduz('Erro interno. Tente novamente ou contate com a Telecontrol.<br>');
            }
        }
        /*if ($msg_erro) exit("<p>$msg_erro</p></body></html>");*/
    }
}

if($_FILES["arquivo"]["size"] > 0 && $login_fabrica >= 138){

    $a_foto = $_FILES["arquivo"];
    $produto = (isset($_GET["produto"])) ? $_GET["produto"] : $produto;

    if(!empty($produto)){

        include_once 'class/aws/s3_config.php';

        include_once S3CLASS;

        $s3 = new AmazonTC('produto', $login_fabrica);

        $tipo  = strtolower(preg_replace("/.+\./", "", $a_foto["name"]));

        $nome  = $a_foto["name"];

        $tipo = ($tipo == "jpeg") ? "jpg" : $tipo;

        if(in_array($tipo, array("jpg", "jpeg", "png", "bmp"))){

            $s3->upload($produto, $_FILES["arquivo"]);

        }else{

            $msg_erro["msg"][] = traduz("O formato do arquivo $nome não é permitido!<br>");

        }

    }

}

###CARREGA REGISTRO
$produto = $_GET['produto'];

if (strlen($produto) > 0) {

    $cond_fabrica = " tbl_linha.fabrica = $login_fabrica ";

    if (in_array($login_fabrica, [11,172])) {
        $cond_fabrica = " tbl_linha.fabrica in (11,172) ";
    }

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
                    TO_CHAR(tbl_produto.data_input, 'DD/MM/YYYY') AS data_cadastro,
                    tbl_produto.capacidade                         ,
                    tbl_produto.divisao                            ,
                    tbl_produto.observacao                         ,
                    tbl_produto.imagem                             ,
                    tbl_produto.sistema_operacional                ,
                    tbl_produto.serie_inicial                      ,
                    tbl_produto.serie_final                        ,
                    tbl_produto.validar_serie                      ,
                    tbl_produto.valores_adicionais                 ,
                    tbl_produto.oem                                ,
                    tbl_produto.entrega_tecnica                    ,
                    tbl_produto.categoria                          ,
                    tbl_produto.parametros_adicionais              ,
                    tbl_produto.codigo_barra                       ,
                    to_char(tbl_produto.data_validacao,'DD/MM/YYYY') AS data_validacao
            FROM    tbl_produto
            JOIN    tbl_linha ON tbl_linha.linha = tbl_produto.linha

            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_produto.admin
            LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
            WHERE   $cond_fabrica
            AND     tbl_produto.produto = $produto;";

    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        $_RESULT["produto"]                  = trim(pg_fetch_result($res,0,'produto'));
        $_RESULT["linha"]                    = trim(pg_fetch_result($res,0,'linha'));
        $_RESULT["familia"]                  = trim(pg_fetch_result($res,0,'familia'));
        $_RESULT["descricao"]                = trim(pg_fetch_result($res,0,'descricao'));
        $_RESULT["referencia"]               = trim(pg_fetch_result($res,0,'referencia'));
        $_RESULT["voltagem"]                 = strtoupper(trim(pg_fetch_result($res,0,'voltagem')));
        $_RESULT["garantia"]                 = trim(pg_fetch_result($res,0,'garantia'));
        $_RESULT["garantia_horas"]           = trim(pg_fetch_result($res,0,'garantia_horas'));
        $_RESULT["preco"]                    = number_format(trim(pg_fetch_result($res,0,'preco')),2,",",".");
        $_RESULT["mao_de_obra"]              = number_format(trim(pg_fetch_result($res,0,'mao_de_obra')),2,",",".");
        if ($login_fabrica == 101) {
            $_RESULT["mao_de_obra_admin"]        = number_format(trim(pg_fetch_result($res,0,'mao_de_obra_ajuste')),2,",",".");
        }
        else {
            $_RESULT["mao_de_obra_admin"]        = number_format(trim(pg_fetch_result($res,0,'mao_de_obra_admin')),2,",",".");
        }
        $_RESULT["mao_de_obra_troca"]        = number_format(trim(pg_fetch_result($res,0,'mao_de_obra_troca')),2,",",".");
        $_RESULT["valor_troca_gas"]          = number_format(trim(pg_fetch_result($res,0,'valor_troca_gas')),2,",",".");
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
        $_RESULT["origem"]                   = strtoupper(trim(pg_fetch_result($res,0,'origem')));
        $_RESULT["produto_fornecedor"]       = trim(pg_fetch_result($res,0,'produto_fornecedor'));
        $_RESULT["admin"]                    = trim(pg_fetch_result($res,0,'login'));
        $_RESULT["data_atualizacao"]         = trim(pg_fetch_result($res,0,'data_atualizacao'));
        $_RESULT["troca_obrigatoria"]        = trim(pg_fetch_result($res,0,'troca_obrigatoria'));
        $_RESULT["produto_critico"]          = trim(pg_fetch_result($res,0,'produto_critico'));
        $_RESULT["intervencao_tecnica"]      = trim(pg_fetch_result($res,0,'intervencao_tecnica'));
        $_RESULT["qtd_etiqueta_os"]          = trim(pg_fetch_result($res,0,'qtd_etiqueta_os'));
        if($login_fabrica == 19){
            echo $_RESULT["validacao_cadastro"]   = pg_fetch_result($res,0,'data_validacao');
        }
        $_RESULT["lista_troca"]              = trim(pg_fetch_result($res,0,'lista_troca'));
        if($login_fabrica != 178){
            $_RESULT["marca"]                = trim(pg_fetch_result($res,0,'marca'));
        }
        $_RESULT["valor_troca"]              = number_format(trim(pg_fetch_result($res,0,'valor_troca')),2,",",".");
        $_RESULT["troca_b_garantia"]         = trim(pg_fetch_result($res,0,'troca_garantia'));
        $_RESULT["troca_b_faturada"]         = trim(pg_fetch_result($res,0,'troca_faturada'));
        //$_RESULT["inibir_lista_basica"]    = trim(pg_fetch_result($res,0,'inibir_lista_basica'));
        $_RESULT["capacidade"]               = trim(pg_fetch_result($res,0,'capacidade'));
        $_RESULT["divisao"]                  = trim(pg_fetch_result($res,0,'divisao'));
        $_RESULT["observacao"]               = trim(pg_fetch_result($res,0,'observacao'));
        $_RESULT["link_img"]                 = trim(pg_fetch_result($res,0,'imagem'));
        $_RESULT["sistema_operacional"]      = trim(pg_fetch_result($res,0,'sistema_operacional'));
        $_RESULT["serie_inicial"]            = trim(pg_fetch_result($res,0,'serie_inicial'));
        $_RESULT["serie_final"]              = trim(pg_fetch_result($res,0,'serie_final'));
        $_RESULT["validar_serie"]            = trim(pg_fetch_result($res,0,'validar_serie'));
        $_RESULT["exigir_selo"]              = trim(pg_fetch_result($res,0,'oem'));

        if (in_array($login_fabrica, [11,172])) {
            $_RESULT["data_cadastro"]         = trim(pg_fetch_result($res,0,'data_cadastro'));
        }

        if ($usaProdutoGenerico || in_array($login_fabrica, array(169,170))) {
            $observacao = $_RESULT["observacao"];
        }

        if ($login_fabrica == 19) {
            $parametros_adicionais = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));

            $param_adicionais = json_decode($parametros_adicionais);
            $_RESULT["data_fabricacao"] = $param_adicionais->data_fabricacao;
            $_RESULT["garantia2"]       = $param_adicionais->garantia2;
            $_RESULT["garantia3"]       = $param_adicionais->garantia3;
        }

        if ($login_fabrica == 195) {
            $parametros_adicionais = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));

            $param_adicionais = json_decode($parametros_adicionais);
            $_RESULT["potencia"] = $param_adicionais->potencia;
            $_RESULT["vazao"]       = $param_adicionais->vazao;
            $_RESULT["pressao"]       = $param_adicionais->pressao;
            $_RESULT["corrente"]       = $param_adicionais->corrente;
        }

        if ($login_fabrica == 148) {
            $_RESULT["pecas_reposicao"] = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $param_adicionais = json_decode($_RESULT["pecas_reposicao"],true);
            $_RESULT["pecas_reposicao"] = $param_adicionais['pecas_reposicao'];
        }

        if ($login_fabrica == 20) {
            $_RESULT["categoria"] = pg_fetch_result($res,0,categoria);
        }

        if($login_fabrica == 153){ //hd_chamado=2717074
            $_RESULT["reparo_na_fabrica"]    = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $param_adicionais = json_decode($_RESULT["reparo_na_fabrica"],true);
            $_RESULT["reparo_na_fabrica"] = $param_adicionais['reparo_na_fabrica'];
        }

        if (in_array($login_fabrica,array(42,142,152,180,181,182))) {
            if(in_array($login_fabrica, array(152,180,181,182))) {
                $_RESULT["entrega_por"] = pg_fetch_result($res, 0, "code_convention");
            }
            $_RESULT["entrega_tecnica"] = pg_fetch_result($res, 0, "entrega_tecnica");
        }

        if($login_fabrica == 138){
           $parametros_adicionais   = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));

           $parametros_adicionais = json_decode($parametros_adicionais, true);
           $multiplas_os = $parametros_adicionais['multiplas_os'];

            $_RESULT["multiplas_os"] = $multiplas_os;
        }

        if(in_array($login_fabrica, [167, 203])){
            $parametros_adicionais = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais = json_decode($parametros_adicionais, true);
            $_RESULT["suprimento"] = $parametros_adicionais['suprimento'];
        }

        if($login_fabrica == 1){
            $parametros_adicionais = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais = json_decode($parametros_adicionais, true);
            $_RESULT["data_descontinuado"] = $parametros_adicionais['data_descontinuado'];
        }

        if($login_fabrica == 161){
            $parametros_adicionais = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais = json_decode($parametros_adicionais, true);
            $_RESULT["insumo"] = $parametros_adicionais['insumo'];
        }

        if(in_array($login_fabrica, array(11,172))){

            $parametros_adicionais = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais = json_decode($parametros_adicionais, true);

            $_RESULT["codigo_interno"]             = $parametros_adicionais['codigo_interno'];
            $_RESULT["codigo_interno_obrigatorio"] = $parametros_adicionais['codigo_interno_obrigatorio'];

            $xreferencia = $_RESULT["referencia"];

            if (!empty($xreferencia)) {
                $sql_fabrica_produto = "SELECT fabrica_i FROM tbl_produto WHERE referencia = '$xreferencia' AND fabrica_i IN (11,172)";
                $res_fabrica_produto = pg_query($con, $sql_fabrica_produto);
                if (pg_num_rows($res_fabrica_produto) > 0) {
                    for ($f=0; $f < pg_num_rows($res_fabrica_produto); $f++) { 
                        if (pg_fetch_result($res_fabrica_produto, $f, 'fabrica_i') == 11) {
                            $_RESULT["fabrica_produto"][0] = 'A';
                        } else if (pg_fetch_result($res_fabrica_produto, $f, 'fabrica_i') == 172) {
                            $_RESULT["fabrica_produto"][1] = 'P';
                        }
                    }
                } 
            } 

        }

        if ( in_array($login_fabrica, [173]) ) {
            $_RESULT["ean"]             = pg_fetch_result($res, 0, 'codigo_barra');

        }

        if(in_array($login_fabrica, array(35))){
            $parametros_adicionais    = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais    = json_decode($parametros_adicionais, true);

            $_RESULT["analise_obrigatoria"] = $parametros_adicionais["analise_obrigatoria"];
        }

        if(in_array($login_fabrica, array(171))){
            $parametros_adicionais    = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais    = json_decode($parametros_adicionais, true);
            $apresentacao             = $parametros_adicionais['apresentacao'];
            $descricao_detalhada      = $parametros_adicionais['descricao_detalhada'];
            $marca_detalhada          = $parametros_adicionais['marca_detalhada'];
            $emb                      = $parametros_adicionais['emb'];
            $categoria                = $parametros_adicionais['categoria'];
            $unidade                  = $parametros_adicionais['unidade'];
            $ncm                      = $parametros_adicionais['ncm'];
            $ii                       = $parametros_adicionais['ii'];
            $alt                      = $parametros_adicionais['alt'];
            $larg                     = $parametros_adicionais['larg'];
            $comp                     = $parametros_adicionais['comp'];
            $peso                     = $parametros_adicionais['peso'];
            $cod_barra                = $parametros_adicionais['cod_barra'];
            $custo_cip                = $parametros_adicionais['custo_cip'];
            $_RESULT['apresentacao']             = $apresentacao;
            $_RESULT['descricao_detalhada']      = $descricao_detalhada;
            $_RESULT['marca_detalhada']          = $marca_detalhada;
            $_RESULT['emb']                      = $emb;
            $_RESULT['categoria']                = $categoria;
            $_RESULT['unidade']                  = $unidade;
            $_RESULT['ncm']                      = $ncm;
            $_RESULT['ii']                       = $ii;
            $_RESULT['alt']                      = $alt;
            $_RESULT['larg']                     = $larg;
            $_RESULT['comp']                     = $comp;
            $_RESULT['peso']                     = $peso;
            $_RESULT['cod_barra']                = $cod_barra;
            $_RESULT['custo_cip']                = $custo_cip;
        }

        if (in_array($login_fabrica, [177])) {

            $parametros_adicionais    = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais    = json_decode($parametros_adicionais, true);

            $lote                     = $parametros_adicionais['lote'];
            $caneca                   = $parametros_adicionais['caneca'];
            $peso                     = $parametros_adicionais['peso'];

            $_RESULT['peso']          = $peso;
            $_RESULT['lote']          = $lote;
            $_RESULT['caneca']        = $caneca;

        }

        if (in_array($login_fabrica, [178])) {
            $parametros_adicionais    = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais    = json_decode($parametros_adicionais, true);

            $marcas = $parametros_adicionais['marcas'];
            $_RESULT["marca"] = explode(',', $marcas); 

            if($parametros_adicionais['fora_linha']){
                $_RESULT["fora_linha"] = true;                
            }

        }


        if($login_fabrica == 42){

            $produto_id = $_RESULT['produto'];

            $sql_classe = "SELECT classe FROM tbl_classe_produto WHERE produto = $produto_id";
            $res_classe = pg_query($con, $sql_classe);
            if(pg_num_rows($res_classe) > 0){
                for($i = 0; $i < pg_num_rows($res_classe); $i++){

                    $classe = pg_fetch_result($res_classe, $i, "classe");

                    $sql_classe_2 = "SELECT * FROM tbl_classe WHERE classe = {$classe} AND entrega_tecnica = 'f'";
                    $res_classe_2 = pg_query($con, $sql_classe_2);

                    if(pg_num_rows($res_classe_2) > 0){
                        $_RESULT["classe_produto"] = trim($classe);
                    }else{
                        $_RESULT["classe_produto_entrega_tecnica"] = trim($classe);
                    }

                }
            }
        }

        if(in_array($login_fabrica, array(142))){
            $valores_adicionais = trim(pg_fetch_result($res, 0, "valores_adicionais"));

            if(!empty($valores_adicionais)){
                $valores_adicionais = json_decode($valores_adicionais);
                foreach ($valores_adicionais as $key => $value) {
                    $_RESULT["deslocamento_km"] =  $value;
                }
            }else{
                $_RESULT["deslocamento_km"] = "";
            }
        }

        if (in_array($login_fabrica, [193])) {
            $parametros_adicionais = trim(pg_fetch_result($res, 0, "parametros_adicionais"));

            if(!empty($parametros_adicionais)){
                $parametros_adicionais = json_decode($parametros_adicionais, true);
                $_RESULT['lancamento'] = (!empty($parametros_adicionais['lancamento'])) ? $parametros_adicionais['lancamento'] : '';
            }   
        }

        if (in_array($login_fabrica, array(169,170))) {
            $parametros_adicionais = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais = json_decode($parametros_adicionais, true);
            $_RESULT["e_ticket"] = ($parametros_adicionais['eticket'] == 'true') ? 't' : '';
            $_RESULT["garantia_estendida"] = $parametros_adicionais['garantia_estendida'];
        }

        if ($login_fabrica == 3) {
            $parametros_adicionais = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais = json_decode($parametros_adicionais, true);
            $_RESULT["ativacao_automatica"] =  ($parametros_adicionais['ativacao_automatica'] == 't') ? 't' : 'f';  
        }

		if (in_array($login_fabrica, [151,187,188,194])) {
            $parametros_adicionais = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
            $parametros_adicionais = json_decode($parametros_adicionais, true);
			if($login_fabrica == 151) {
				$_RESULT['troca_direta'] = $parametros_adicionais['troca_direta'];
			}else if ($login_fabrica == 194){
                $_RESULT["percentual_tolerante"] = $parametros_adicionais["percentual_tolerante"];
            }else{
				$_RESULT['ressarcimento_obrigatoria'] = $parametros_adicionais['ressarcimento_obrigatoria'];
			}
        }

        // $serie_in                 = trim(pg_fetch_result($res,0,'serie_in'));
        // $serie_out                = trim(pg_fetch_result($res,0,'serie_out'));
        $admin                    = trim(pg_fetch_result($res,0,'login'));
        $data_atualizacao         = trim(pg_fetch_result($res,0,'data_atualizacao'));

    if (in_array($login_fabrica, array(169,170))) {
        $_RESULT["referencia"] = str_replace("YY", "-", $_RESULT["referencia"]);
    }

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

<style type="text/css">
    input[type=number]::-webkit-inner-spin-button { 
        -webkit-appearance: none;
        cursor:pointer;
        display:block;
        width:8px;
        color: #333;
        text-align:center;
        position:relative;
    }
       input[type=number] { 
       -moz-appearance: textfield;
       appearance: textfield;
       margin: 0; 
    }

</style>
<script language="JavaScript">

    $(function(){
        
        <?php
        if (!in_array($login_fabrica, [141, 176])) {
        ?>
            $("#voltagem").select2({
                tags: true,
                tokenSeparators: [',', ' '],
                createTag: function (params) {
                    var term = $.trim(params.term);
                    if (term === '') return null;

                    return {
                        id: term,
                        text: term,
                        newTag: true
                    }            
                }
            });
        <?php
        }
        ?>

            setupZoom();
            $('input[name=garantia]').numeric();
            $('input[name=ean]').numeric();
            $('input[name=garantia_horas]').numeric();
            $('input[name=mao_de_obra_admin]').numeric({ allow : ',.' });
            $('input[name=mao_de_obra]').numeric({ allow : ',.' });
            $('input[name=mao_de_obra_troca]').numeric({ allow : ',.' });
            $('input[name=qtd_etiqueta_os]').numeric();
            $("#qtde_disparos").numeric();
            $('input[name=ipi]').numeric({ allow : ',.' });
            $("#percentual_tolerante").numeric();
            $(".bd_sel").multiselect();

            <?php
            if (in_array($login_fabrica, array(169, 170))) {
            ?>
                $('input[name=garantia_estendida]').numeric();
            <?php
            }
            ?>

            if ($("#data_descontinuado").length) {
                $.datepickerLoad(Array("data_descontinuado"));
            }

            if ($("#validacao_cadastro").length) {
                $.datepickerLoad(Array("validacao_cadastro"));
            }

            $("input[name=data_fabricacao]").mask("99/9999");

            $('input[name=preco_pais]').numeric({ allow: ',.' });

            $('.versao').alpha();

            $("input[name=valor]").maskMoney({showSymbol:"", symbol:"", decimal:".", precision:2, thousands:"",maxlength:10});

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

        var retorno_autocomplete_produto = {
            "produto": {
                retorno: function (item) {
                    if (item["id"] != undefined && item["id"].length > 0) {
                        window.location = "<?=$_SERVER['PHP_SELF']?>?produto="+item["id"];
                    }
                }
            }
        }

        <?php 
            if (!in_array($login_fabrica, [11,172])) {
        ?>    
                $.autocompleteLoad(["produto"], null, retorno_autocomplete_produto);
        <?php 
            } 
        ?>

        <?php 
            if (in_array($login_fabrica, [11,172])) {
        ?>    
                let produto_get = "<?=$_GET['produto']?>"
                
                $(".fab_prod").click(function(){
                    if (produto_get != "" && produto_get != undefined) {
                        return false;
                    }
            });  
        <?php 
            } 
        ?>        

        $.ajax({
		url: "<?php echo $_SERVER['PHP_SELF']; ?>?ajax_voltagem=sim&produto=<?=$produto?>&voltagem=<?=$voltagem?>",
            cache: false,
            success: function(data) {
                $("#voltagem").html(data);

                let options = $("#voltagem").find("option");

                let mainOpts = ['110V', '220V', 'BIVOLT'];
                let addedOpts = [];

                $.each(options, function (index, element) {
                    if ($(element).val().length > 0) {
                        addedOpts.push($(element).text().split(" ").join(""));
                    }
                });

                newOpts = mainOpts.diff(addedOpts);
                $.each(newOpts, function (index, element) {
                    $("#voltagem").append("<option value='" + element + "'>" + element + "</option>");
                })

		$("#voltagem").trigger("change");
            }
        });

        <?php if (in_array($login_fabrica, [193])) { ?>

        $("#valor_hora_tecnica").maskMoney({showSymbol:"", symbol:"", decimal:",", precision:2, thousands:"",maxlength:10});
        var a = <?= (!empty($_RESULT['garantia_horas'])) ? $_RESULT['garantia_horas'] : 0 ?> ;
       
        if (a > 0) {
            $("#valor_hora_tecnica").val(a); 
            $("#valor_hora_tecnica").show(); 
            $("label[for='valor_hora_tecnica']").show(); 
            $("input[name='hora_tecnica[]']").prop('checked', true);
        } else {
            $("label[for='valor_hora_tecnica']").hide(); 
            $("#valor_hora_tecnica").hide();     
        }  

        $("input[name='hora_tecnica[]']").on('click', function(){
            if ($(this).is(':checked')) {
                $("label[for='valor_hora_tecnica']").show(); 
                $("#valor_hora_tecnica").show(); 
            } else {
                $("label[for='valor_hora_tecnica']").hide(); 
                $("#valor_hora_tecnica").hide(); 
            }
        });

        <?php } ?>
    });

    Array.prototype.diff = function (a) {
        return this.filter(function (i) {
            return a.indexOf(i) < 0;
        })
    }

    function gravaDados(){
        var servico         = $("input[name=servico]").val();
        var servico_id      = $("input[name=servico_id]").val();
        var produto_id      = $("input[name=produto_id]").val();
        var valor           = $("input[name=valor]").val();
        if(produto_id){
            if(servico) {
                $.ajax({
                    url: "<?php echo $_SERVER['PHP_SELF']; ?>?ajax=sim&servico_id="+servico_id+"&produto="+produto_id+"&servico="+servico+"&valor="+valor,
                    cache: false,
                    success: function(data) {

                    retorno = data.split('|');

                    if (retorno[0]=="OK") {
                        $("input[name=servico]").val("").html("");
                        $("input[name=valor]").val("").html("");
                        $("#resultado > tbody").find("tr").first().nextAll("tr").remove();
                        $("#resultado > tbody").append(retorno[1]);
                        // a.after(retorno[1]);
                        // $("#resultado > tbody > tr").html(retorno[1]);

                    } else {
                        alert(retorno[0]);
                    }
                    }
                });
            }else{
                alert('<?=traduz("Serviço não preenchido")?>');
            }
        }else{
            alert('<?=traduz("Por favor , escolha um Produto!")?>');
        }
    }

    function excluiRegistro(servico,produto){
        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?ajax_exclui=sim&servico="+servico+"&produto="+produto,
            cache: false,
            success: function(data) {

                retorno = data.split('|');

                if (retorno[0]=="OK") {
                    $("#resultado > tbody").find("tr").first().nextAll("tr").remove();
                    $("#resultado > tbody").append(retorno[1]);

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
        var num = campo.value.replace(".","");
        num = num.replace(",",".");
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
        alert('<?=traduz("Informe toda ou parte da informação para realizar a pesquisa!")?>');
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
            alert('<?=traduz("Informe toda ou parte da informação para realizar a pesquisa!")?>');
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
                    $('#msg_erro').html(('<td colspan="6">Número de série inválido</td>'));
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
                    $('#msg_erro').html(('<td colspan="6">Número de série inválido</td>'));
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
        var td3    = document.createElement('td');
        var tr     = document.createElement('tr');
        var input1 = document.createElement('input');
        var input2 = document.createElement('input');
        <? if ($usa_versao_produto) { ?>
        var input3 = document.createElement('input');
        <? } ?>

        input1.setAttribute('type', 'text');
        input1.setAttribute('name', 'mascara_'+total);
        input1.setAttribute('id', 'mascara_'+total);
        input1.setAttribute('size', '60');

        input2.setAttribute('type', 'button');
        input2.setAttribute('class', 'btn btn-danger');
        input2.setAttribute('name', 'btn_excluir_'+total);
        input2.setAttribute('id', 'btn_excluir_'+total);
        input2.setAttribute('value', 'Excluir');

        <? if ($usa_versao_produto) { ?>
        input3.setAttribute('type', 'text');
        input3.setAttribute('name', 'versao_'+total);
        input3.setAttribute('id', 'versao_'+total);
        input3.setAttribute('maxlength', '3');
        <? } ?>

        // tr.setAttribute('bgColor', cor);
        tr.setAttribute('align', 'center');
        tr.setAttribute('id', 'mascara_serie_' + total);

        td1.appendChild(input1);
        <? if ($usa_versao_produto) { ?>
        td3.appendChild(input3);
        <? } ?>
        td2.appendChild(input2);

        tr.appendChild(td1);
        <? if ($usa_versao_produto) { ?>
        tr.appendChild(td3);
        <? } ?>
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

        if (confirm(('Deseja realmente Excluir esta Máscara?'))) {

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
                        alert(('Aconteceu um erro ao excluir o registro!'));
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
<script type='text/javascript'>
$(function (){
    var login_fabrica = <?=$login_fabrica?>;

    $("#btnPopover").popover();
    $("#btnPopover2").popover();
    $("#btnPopover3").popover();
    $("#descIdioma").popover();

    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this), Array("produtoId", "posicao", "voltagemForm") );
    });

    // if(login_fabrica == 1){
    //     var troca_b_garantia = $("input[name^=troca_b_garantia]").is(":checked");
    //     var troca_b_faturada = $("input[name^=troca_b_faturada]").is(":checked");

    //     if(troca_b_garantia === false && troca_b_faturada === false){
    //         $("input[name^=inibir_lista_basica]").prop("disabled",true);
    //     }else{
    //         $("input[name^=inibir_lista_basica]").prop("disabled",false);
    //     }

    //     $("input[name^=troca_b_]").click(function(){
    //         if($("input[name^=troca_b_garantia]").is(":checked") || $("input[name^=troca_b_faturada]").is(":checked")){
    //             $("input[name^=inibir_lista_basica]").prop("disabled",false);
    //         }else{
    //             $("input[name^=inibir_lista_basica]").prop("disabled",true);
    //         }
    //     });
    // }

    $("#gerar_excel_kit").click(function () { /*HD - 4357126*/
        if (ajaxAction()) {
            data = {gerar_excel_kit:true}

            $.ajax({
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "POST",
                data: data,
                beforeSend: function () {
                    loading("show");
                },
                complete: function (data) {
                    window.open(data.responseText, "_blank");

                    loading("hide");
                }
            });
        }
    });
});

function retorna_produto(json){

    if(json.posicao !== undefined){
        $("#referencia_opcao_"+json.posicao).val(json.referencia);
        $("#descricao_opcao_"+json.posicao).val(json.descricao);
        $("#voltagem_opcao_"+json.posicao).val(json.voltagem);

    }else if(json.produto !== undefined){
        window.location = "produto_cadastro.php?produto="+json.produto;
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

<? if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) {
    unset($_POST);
    ?>
    <div class="alert alert-success">
        <h4><? echo $msg; ?></h4>
    </div>
<? } ?>
<?
// Campos do Formulario
// labels que mudam conforme a fábrica
if ($login_fabrica == 14 or $login_fabrica == 66){
   $lbl_mao_de_obra = traduz("ASTEC");
}else{
   $lbl_mao_de_obra = traduz("Posto");
}
$label_mao_de_obra = traduz("M. Obra ").$lbl_mao_de_obra;

if ($login_fabrica == 14 or $login_fabrica == 66){
    $lbl_mao_de_obra_admin = traduz("LAI");
}else{
    $lbl_mao_de_obra_admin = traduz("Admin");
}

if($login_fabrica == 101) {
    $lbl_mao_de_obra_admin = traduz("Orientação");
}

if($login_fabrica == 190) {
    $lbl_mao_de_obra_admin = traduz("Treinamento ");
}

$label_mao_de_obra_admin = traduz("M.Obra ").$lbl_mao_de_obra_admin;


// Combos que são montados a partir do BD ou seus options se diferenciam por fábrica
/*
* LINHA
*/

$cond_linha = (in_array($login_fabrica, array(59))) ? " AND (tbl_linha.ativo IS TRUE OR tbl_linha.nome = 'ACESSÉRIOS') " : " AND tbl_linha.ativo IS TRUE ";

if (!empty($produto) && $login_fabrica <> 59) {
    $cond_linha = ' AND (tbl_linha.ativo IS TRUE OR tbl_linha.linha = (SELECT linha from tbl_produto WHERE produto = '.$produto.' AND fabrica_i = '.$login_fabrica.'))';    
}

$cond_linha_fabrica = " tbl_linha.fabrica = $login_fabrica ";
$cond_familia_fabrica = " tbl_familia.fabrica = $login_fabrica ";

if (in_array($login_fabrica, [11,172])) {
    if (isset($_POST['produto']) && $_POST['produto'] != "") {
        $fab_produto = fabrica_produto_i($_POST['produto']);
    } else if (isset($_GET['produto']) && $_GET['produto'] != "") {
        $fab_produto = fabrica_produto_i($_GET['produto']);
    } else {
        $fab_produto = $login_fabrica;
    }

    if ($fab_produto != $login_fabrica) {
        $cond_linha_fabrica = " tbl_linha.fabrica = $fab_produto ";
        $cond_familia_fabrica = " tbl_familia.fabrica = $fab_produto ";
    }
}

if ($login_fabrica == 117) {
    $sql = "SELECT DISTINCT
                tbl_macro_linha.macro_linha AS linha,
                tbl_macro_linha.descricao AS nome
            FROM tbl_macro_linha
                JOIN tbl_macro_linha_fabrica USING(macro_linha)
            WHERE tbl_macro_linha_fabrica.fabrica = {$login_fabrica} ORDER BY tbl_macro_linha.descricao;";
    $res = pg_query ($con,$sql);
}else{
    $sql = "SELECT  *
            FROM    tbl_linha
            WHERE   $cond_linha_fabrica
            {$cond_linha}
            ORDER BY tbl_linha.nome;";
    $res = pg_query ($con,$sql);
}
if (pg_num_rows($res) > 0) {
    $options_linha = array();
    for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
        $aux_linha = trim(pg_fetch_result($res,$x,linha));
        $aux_nome  = trim(pg_fetch_result($res,$x,nome));
        $options_linha[$aux_linha] = $aux_nome;
    }
}

/*
* Famália
*/

$sql = "SELECT  *
        FROM    tbl_familia
        WHERE   $cond_familia_fabrica
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
$options_voltagem['12 V'] = '12 V';
if ($login_fabrica <> 1) {
    $options_voltagem['110 V'] = '110 V';
}
$options_voltagem['127 V'] = '127 V';
$options_voltagem['220 V'] = '220 V';
$options_voltagem['230 V'] = '230 V';
$options_voltagem['BIVOLT'] = 'Bivolt';
$options_voltagem['BIVOLT AUT'] = 'Bivolt Aut';
$options_voltagem['BATERIA'] = traduz('Bateria');
$options_voltagem['PILHA'] = traduz('Pilha');
if($login_fabrica == 15) { // HD 75711
    $options_voltagem['Full Range'] = 'Full Range';
}
if($login_fabrica == 1) { // HD 75711
    $options_voltagem['SEM'] = traduz('SEM');
}
$hiddens = array(
   'produto'
);
if($login_fabrica == 20){
    $hiddens['idioma_novo'] = array('value' => 'ES');//fabrica 20
}
if($login_fabrica == 3){
    $hiddens['idioma_novo'] = array('value' => 'EN');
    $hiddens['idioma']      = array('value' => 'EN');

}
if($login_fabrica == 3 || $login_fabrica > 80){
    $hiddens['MAX_FILE_SIZE'] = array('value' => 1048576);
}
if($login_fabrica == 1){
    $hiddens[] = 'qtde_itens';
}
if($inf_valores_adicionais){
    $hiddens[] = 'servico_id';
    $hiddens['produto_id'] = array('value' => $produto);
}

if($login_fabrica == 117){
    if (!empty($produto)) {
        $sql_elgin = "SELECT
                        tbl_produto.linha,
                        tbl_produto.familia,
                        tbl_macro_linha_fabrica.macro_linha
                    FROM tbl_produto
                        LEFT JOIN tbl_macro_linha_fabrica ON(tbl_produto.linha = tbl_macro_linha_fabrica.linha AND tbl_macro_linha_fabrica.fabrica = {$login_fabrica})
                    WHERE tbl_produto.produto = {$produto} AND
                        tbl_produto.fabrica_i = {$login_fabrica}";

        $res_elgin = pg_query($con, $sql_elgin);
        if (pg_num_rows($res_elgin) > 0) {
            $hiddens['linha_aux'] = array('value' => pg_fetch_result($res_elgin, 0, "linha"));
            $hiddens['macro_linha_aux'] = array('value' => pg_fetch_result($res_elgin, 0, "macro_linha"));
            $hiddens['familia_aux'] = array('value' => pg_fetch_result($res_elgin, 0, "familia"));
        }
    }else{
        $hiddens['linha_aux'] = array('value' => (isset($_POST['linha']) && !empty($_POST['linha'])) ? $_POST['linha'] : '' );
        $hiddens['familia_aux'] = array('value' => (isset($_POST['familia']) && !empty($_POST['familia'])) ? $_POST['familia'] : '' );
    }
}

if($login_fabrica == 94){
    $spanRef = 8;
    $spanDesc = 8;
    $spanCampo = " span8";
    $widthRef = 6;
}elseif($login_fabrica == 171){
    $spanRefFab = 2;
    $spanRef = 2;
    $spanDesc = 4;
    $widthRef = "";
    $spanCampo = "span10";
}else{
    $spanRef = 4;
    $spanDesc = 4;
    $widthRef = 6;
    $spanCampo = "span12";
}

if ($login_fabrica == 171) {
    $inputs["referencia_fabrica"] = array(
        'id' => 'referencia_fabrica',
        'type' => 'input/text',
        'label' => 'Referência FN',
        'span' => $spanRefFab,
        'maxlength' => 20,
        'class' => $spanRefFab,
        'class' => $spanCampo,
        'lupa' => array(
            'name' => 'lupa',
            'tipo' => 'produto',
            'parametro' => 'referencia'
        ),
    );

    $labelReferencia = traduz('Referência Grohe');
} else {
    $labelReferencia = traduz('Referência');
}

$inputs["referencia"] = array(
        'id' => 'produto_referencia',
        'type' => 'input/text',
        'label' => $labelReferencia,
        'span' => $spanRef,
        'width' => $widthRef,
    'maxlength' => 20,
    'class' => $spanCampo,
        'lupa' => array(
            'name' => 'lupa',
            'tipo' => 'produto',
            'parametro' => 'referencia',
            'extra' => array(
                'produtoId' => 'true'
            )
        ),
        'required' => true
);

$inputs['descricao'] = array(
        'id' => 'produto_descricao',
        'type' => 'input/text',
        'label' => traduz('Descrição'),
        'span' => $spanDesc,
        'maxlength' => 80,
        'lupa' => array(
            'name' => 'lupa',
            'tipo' => 'produto',
            'parametro' => 'descricao',
            'extra' => array(
                'produtoId' => 'true'
            )
        ),
        'required' => true
    );

if (in_array($login_fabrica, array(186)) && strlen($produto) > 0) {

$inputs['descricao']["readonly"] = true;
$inputs['referencia']["readonly"] = true;

}
$inputs['garantia'] = array(
        'type' => 'input/text',
        'label' => traduz('Garantia'),
        'span' => 2,
        'inptc' => 6,
        'maxlength' => (in_array($login_fabrica, array(171,178)) ) ? 3 : 2,
        'required' => ($login_fabrica == 189) ? false : true
    );

if (in_array($login_fabrica, [19])) {

    $inputs['garantia2'] = array(
        'type' => 'input/text',
        'label' => 'Garantia 2',
        'span' => 2,
        'inptc' => 6,
        'maxlength' => 2,
        'required' => false
    );

    $inputs['garantia3'] = array(
        'type' => 'input/text',
        'label' => 'Garantia 3',
        'span' => 2,
        'inptc' => 6,
        'maxlength' => 2,
        'required' => false
    );

}

if (in_array($login_fabrica, array(169, 170))) {
    $inputs['garantia_estendida'] = array(
        'type' => 'input/text',
        'label' => traduz('Garantia Estendida'),
        'span' => 2,
        'inptc' => 6,
        'maxlength' => 2
    );
}

if (!in_array($login_fabrica, [151, 178, 183, 189,191,195,203])) {
    $inputs["mao_de_obra"] = array(
        "type" => "input/text",
        "label" => $label_mao_de_obra,
        "span" => 2,
        "inptc" => 8,
        "maxlength" => 14,
        "required" => (in_array($login_fabrica, array(164,203))) ? false : true,
        "extra" => array(
            "price" => 'true'
        )
    );
}

if ($login_fabrica == 162) {
    $inputs["mao_de_obra"] = array(
        "type" => "input/text",
        "label" => $label_mao_de_obra,
        "span" => 2,
        "inptc" => 8,
        "maxlength" => 14,
        "extra" => array(
            "price" => 'true'
        )
    );
}


if (!in_array($login_fabrica,array(86,115,116,117,120,201,121,122,81,114,124,123,128,129,134,136,137,151,177, 178, 183,191))) {//HD 387824
    $inputs['mao_de_obra_admin'] = array(
        'type' => 'input/text',
        'label' => $label_mao_de_obra_admin,
        'span' => 2,
        'inptc' => 8,
        'maxlength' => 14,
        'extra' => array(
            'price' => 'true'
        )
    );
}
$inputs['classificacao_fiscal'] = array(
        'type' => 'input/text',
        'label' => traduz('Classificação Fiscal'),
        'span' => 2,
        'width' => 12,
        'maxlength' => 20
    );
$inputs['ipi'] = array(
        'type' => 'input/text',
        'label' => traduz('I.P.I.'),
        'span' => 1,
        'inptc' => 6,
        'maxlength' => 3
    );
if ($login_fabrica == 117) {
    $inputs['macro_linha'] = array(
        'type' => 'select',
        'span' => 4,
        'width' => 10,
        'label' => traduz('Linha'),
        'options' => $options_linha,
        'required' =>true
    );
    $inputs['linha'] = array(
            'type' => 'select',
            'span' => 4,
            'width' => 10,
            'label' => traduz('Macro Família'),
            'options' => array(),
            'required' =>true
        );
}else{
$inputs['linha'] = array(
        'type' => 'select',
        'span' => 4,
        'width' => 10,
        'label' => traduz('Linha'),
        'options' => $options_linha,
        'required' =>true
    );

}

if ($login_fabrica == 19) {
    $inputs['data_fabricacao'] = array(
            'type' => 'input/text',
            'label' => traduz('Data de fabricação'),
            'span' => 2,
            'width' => 10
        );
}

if(in_array($login_fabrica, array(11,172))){
    $inputs['codigo_interno'] = array(
        'type' => 'input/text',
        'label' => traduz('Código Interno'),
        'span' => 3,
        'width' => 12,
        'maxlength' => 8,
        'extra' => array(
            'numeric' => 'true'
        )
    );
}

$inputs['familia'] = array(
        'type' => 'select',
        'label' => traduz('Família'),
        'span' => 4,
        'width' => 10,
        'extra' =>$familia_extra,
        'options' => $options_familia,
        'required' => ($login_fabrica == 189) ? false : true
    );

if(in_array($login_fabrica, array(138))){
    $inputs['origem'] = array(
        'type' => 'select',
        'label' => traduz('Origem'),
        'span' => 2,
        'width' => 12,
        'options' => array(
            'NAC' => traduz('Nacional'),
            'IMP' => traduz('Importado')
        )
    );
}else{
    $inputs['origem'] = array(
        'type' => 'select',
        'label' => traduz('Origem'),
        'span' => 2,
        'width' => 12,
        'options' => array(
            'NAC' => traduz('Nacional'),
            'IMP' => traduz('Importado'),
            'USA' => traduz('Importado USA'),
            'ASI' => traduz('Importado Asia')
        )
    );

    if (in_array($login_fabrica, array(169,170))) {
        $inputs['origem']["options"] = array();

        $sqlOrigem = "SELECT DISTINCT origem FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE ORDER BY origem ASC";
        $resOrigem = pg_query($con, $sqlOrigem);

        if (pg_num_rows($resOrigem) > 0) {
            while ($origem = pg_fetch_object($resOrigem)) {
                if (!empty($origem->origem)) {
                    $inputs['origem']["options"][$origem->origem] = $origem->origem;
                }
            }
        }
    }
}

if (in_array($login_fabrica, [195])){
    $inputs['corrente'] = array(
        'type' => 'input/text',
        'label' => traduz('corrente'),
        'span' => 2,
        'width' => 12,
        'required' => true,
    );
    $inputs['potencia'] = array(
        'type' => 'input/text',
        'label' => traduz('Potência'),
        'span' => 2,
        'width' => 12,
        'required' => true,
    );
    $inputs['pressao'] = array(
        'type' => 'input/text',
        'label' => traduz('Pressão'),
        'span' => 2,
        'width' => 12,
        'required' => true,
    );
    $inputs['vazao'] = array(
        'type' => 'input/text',
        'label' => traduz('Vazão'),
        'span' => 2,
        'width' => 12,
        'required' => true,
    );
}


if (in_array($login_fabrica, [141,176])){
    $inputs['voltagem'] = array(
        'type' => 'input/text',
        'label' => traduz('Voltagem'),
        'span' => 2,
        'width' => 12
    );
}else{
    $inputs['voltagem'] = array(
        'type' => 'select',
        'label' => traduz('Voltagem'),
        'span' => 2,
        'width' => 12,
        'options' => $options_voltagem
    );
}

$inputs['ativo'] = array(
    'type' => 'select',
    'label' => traduz('Status Rede'),
    'span' => 2,
    'width' => 12,
    'options' => array(
        't' => traduz('Ativo'),
        'f' => traduz('Inativo')
    ),
    'required' => true
);
$inputs['uso_interno_ativo'] = array(
    'type' => 'select',
    'label' => traduz('Status Uso Interno'),
    'span' => 3,
    'width' => 6,
    'options' => array(
        't' => traduz('Ativo'),
        'f' => traduz('Inativo')
    ),
    'popover' => array(
        'id' => 'btnPopover',
        'msg'   => traduz('Produto visível somente pelo Posto Interno, apesar de inativo para a rede!')
    ),
    'required' => true
);
$inputs['nome_comercial'] = array(
    'type' => 'input/text',
    'label' => traduz('Nome Comercial'),
    'span' => 3,
    'maxlength' => 20
);


if($login_fabrica == 96){
    $inputs['modelo'] = array(
        'type'      => 'input/text',
        'label'     => traduz('Modelo'),
        'span'      => 4,
        'width'     => 7,
        'maxlength' => 20,
        'lupa'      => array(
            'name'      => 'lupa',
            'tipo'      => 'produto',
            'parametro' => 'referencia',
            'extra'     => array(
                'modelo' => 'true'
            )
        )
    );
}


if(in_array($login_fabrica, array(19,35,96,117,178,194))) {
    $inputs['preco'] = array(
        'type' => 'input/text',
        'label' => traduz('Preço'),
        'span' => 4,
        'inptc' => 7,
        'maxlength' => 14,
        'extra' => array(
            'price' => 'true'
        )
    );
}

if($login_fabrica == 194){
    $inputs["percentual_tolerante"] = array(
        "type" => "input/text",
        "label" => "Percentual tolerante",
        "span" => 2,
        'inptc' => 12
    );
}

if($login_fabrica == 87){
   $inputs['garantia_horas'] = array(
        'type'      => 'input/text',
        'label'     => traduz('Garantia Horas'),
        'span'      => 4,
        'inptc'     => 3,
        'maxlength' => 14
    );
}

if($login_fabrica == 3){
    $inputs['valor_troca_gas'] = array(
        'type'      => 'input/text',
        'label'     => traduz('Valor Recarga de Gás'),
        'span'      => 4,
        'width'     => 6,
        'maxlength' => 14,
        'extra'     => array(
            'price' => 'true'
        )
    );

    $inputs['produto_fornecedor'] = array(
        'type'    => 'select',
        'label'   => traduz('Fornecedor do Produto'),
        'span'    => 4,
        'width'   => 6,
        'options' => array()
    );
    $sql = "SELECT  *
            FROM    tbl_produto_fornecedor
            WHERE   tbl_produto_fornecedor.fabrica = $login_fabrica
            ORDER BY tbl_produto_fornecedor.nome;";
    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
            $aux_produto_fornecedor = trim(pg_fetch_result($res,$x,'produto_fornecedor'));
            $aux_nome               = trim(pg_fetch_result($res,$x,'nome'));
            $inputs["produto_fornecedor"]["options"][$aux_produto_fornecedor] = $aux_nome;
        }
    }

    if(strlen($produto)>0){
        $msgDescFornecedor = "";
        $sql = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto";
        $res2 = pg_query ($con,$sql);

        if (pg_num_rows($res2) > 0) {
            $produto          = trim(pg_fetch_result($res2,0,'produto'));
            $idioma           = trim(pg_fetch_result($res2,0,'idioma'));
            $descricao_idioma = trim(pg_fetch_result($res2,0,'descricao'));
            $_RESULT["descricao_idioma"] = $descricao_idioma;
        }else{
            $msgDescFornecedor = traduz("Não existe descrição para esse produto do fornecedor, preencha o campo abaixo para inserir uma.");
        }

        $inputs["descricao_idioma"] = array(
            "type" => "input/text",
            "label" => traduz("Descrição do Fornecedor:"),
            "inptc" => 12,
            "span" => 4,
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
            "label" => traduz("Radical N. Série 1"),
            "span" => 2,
            "width" => 12,
            "maxlength" => 10

        );
    $inputs["radical_serie2"] = array(
            "type" => "input/text",
            "label" => traduz("Radical N. Série 2"),
            "span" => 2,
            "width" => 12,
            "maxlength" => 10

        );
    $inputs["radical_serie3"] = array(
            "type" => "input/text",
            "label" => traduz("Radical N. Série 3"),
            "span" => 2,
            "width" => 12,
            "maxlength" => 10

        );
    $inputs["radical_serie4"] = array(
            "type" => "input/text",
            "label" => traduz("Radical N. Série 4"),
            "span" => 2,
            "width" => 12,
            "maxlength" => 10

        );
    $inputs["radical_serie5"] = array(
            "type" => "input/text",
            "label" => traduz("Radical N. Série 5"),
            "span" => 2,
            "width" => 12,
            "maxlength" => 10

        );
    $inputs["radical_serie6"] = array(
            "type" => "input/text",
            "label" => traduz("Radical N. Série 6"),
            "span" => 2,
            "width" => 12,
            "maxlength" => 10

        );

}

if($login_fabrica == 5){
    $inputs["link_img"] = array(
            "type" => "input/text",
            "label" => traduz("Link para imagem"),
            "title" => traduz("O caminho deve ser digitado por completo"),
            "span" => 4,
            "inptc" => 7,
            "maxlength" => 255

        );
}
if($login_fabrica == 20){
    $inputs["observacao"] = array(
            "type" => "textarea",
            "label" => traduz("Comentário"),
            "span" => 4,
            "cols" => 90,
            "cols" => 4

        );
}
if($login_fabrica == 45){
    $inputs["codigo_validacao_serie"] = array(
            "type" => "input/text",
            "label" => traduz("Código Família"),
            "span" => 4,
            "width" => 6,
            "maxlength" => 20,
            "readonly" =>true

        );
}

if($login_fabrica == 7){
    $inputs["capacidade"] = array(
            "type" => "input/text",
            "label" => traduz("Capacidade"),
            "span" => 4,
            "inptc" => 6,
            "maxlength" => 9
        );
    $inputs["divisao"] = array(
            "type" => "input/text",
            "label" => traduz("Divisão"),
            "span" => 4,
            "inptc" => 6,
            "maxlength" => 20
        );
}

if ($login_fabrica == 175){
    $inputs["capacidade"] = array(
            "type" => "input/text",
            "label" => traduz("Qtd de disparos"),
            "span" => 4,
            "id" => 'qtde_disparos',
            "inptc" => 7,
            "maxlength" => 10,
            'extra' => array(
                'numeric' => 'true'
            ),
            "popover" => array(
                "id" => "btnPopover3",
                "msg" => traduz("Quantidade de disparos realizados.")
            )
    );
}

if(in_array($login_fabrica,array(1,165))){
    if ($login_fabrica == 1) {
        $inputs["code_convention"] = array(
                "type" => "input/text",
                "label" => traduz("Code Convention"),
                "span" => 4,
                "width" => 5,
                "maxlength" => 20
            );
    }
    $inputs["valor_troca"] = array(
            "type" => "input/text",
            "label" => ($login_fabrica == 1) ? traduz("Troca Faturada (valor)") : traduz("Base de Troca"),
            "span" => 4,
            "width" => 6,
            "maxlength" => 14,
            "extra" => array(
                'onblur' => "checarNumero(this)",
                "price" => "true"
            )
        );


}

if ($login_fabrica == '131') {
     $inputs["mao_de_obra_troca"] = array(
            "type" => "input/text",
            "label" => traduz("Valor Troca"),
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
        "label" => traduz("Sistema Operacional"),
        "options" => array(
            "1" => traduz("Windows"),
            "2" => traduz("Linux"),
            "3" => traduz("Apple")
        )
    );
}
if($login_fabrica == 42){
    $inputs["classe_produto"] = array(
        "type" => "select",
        "label" => traduz("Classe"),
        "span" => 4,
        "width" => 7,
        "options" => array()
    );

    $sql = "SELECT classe, nome FROM tbl_classe WHERE fabrica = $login_fabrica AND entrega_tecnica = 'f' ORDER BY nome";
    $res = pg_query($con,$sql);
    if(pg_numrows($res) > 0){
        for($x = 0; $x < pg_numrows($res); $x++){
            $classe      = pg_fetch_result($res,$x,'classe');
            $nome_classe = pg_fetch_result($res,$x,'nome');

            $selected = ($_RESULT['classe_produto'] == $classe) ? 'selected="selected"' : null;

            $inputs["classe_produto"]["options"][$classe] = $nome_classe;
        }
    }

    $inputs["classe_produto_entrega_tecnica"] = array(
        "type" => "select",
        "label" => traduz("Classe Entrega Técnica"),
        "span" => 4,
        "width" => 7,
        "options" => array()
    );

    if(strlen($_RESULT["entrega_tecnica"]) > 0){
        $selected = ($_RESULT["entrega_tecnica"] == "t") ? $inputs["classe_produto_entrega_tecnica"]["options"] = array("checked") : "";
    }

    $sql = "SELECT classe, nome FROM tbl_classe WHERE fabrica = $login_fabrica AND entrega_tecnica = 't' ORDER BY nome";
    $res = pg_query($con,$sql);
    if(pg_numrows($res) > 0){
        for($x = 0; $x < pg_numrows($res); $x++){
            $classe      = pg_fetch_result($res,$x,'classe');
            $nome_classe = pg_fetch_result($res,$x,'nome');

            $selected = ($_RESULT['classe_produto_entrega_tecnica'] == $classe) ? 'selected="selected"' : null;

            $inputs["classe_produto_entrega_tecnica"]["options"][$classe] = $nome_classe;
        }
    }
}

if($login_fabrica == 15){
    $inputs['serie_inicial'] = array(
        'type'      => 'input/text',
        'label'     => traduz('Versão Inicial'),
        'class'     => 'versao',
        'span'      => 4,
        'width'     => 6,
        'maxlength' => 20
    );

    $inputs['serie_final'] = array(
        'type'      => 'input/text',
        'label'     => traduz('Versão Final'),
        'class'     => 'versao',
        'span'      => 4,
        'width'     => 6,
        'maxlength' => 20
    );

    $inputs['radical_serie2'] = array(
        'type'      => 'input/text',
        'label'     => traduz('Ano Inicial'),
        'class'     => 'versao',
        'span'      => 4,
        'width'     => 6,
        'maxlength' => 1
        );

    $inputs['radical_serie3'] = array(
        'type'      => 'input/text',
        'label'     => traduz('Ano Final'),
        'class'     => 'versao',
        'span'      => 4,
        'width'     => 6,
        'maxlength' => 1
        );

}

if (!in_array($login_fabrica,array(3,86)) and in_array($login_fabrica, array(45,46,99,10,80,117,59,146,6,40,3,72,35,101,20,24))) {
    $maxlength = $login_fabrica != 6 ? 10 : 3;
    $required = $login_fabrica == 6 ? true : false;
    $inputs["radical_serie"] = array(
            "type" => "input/text",
            "label" => traduz("Radical Série"),
            "span" => 4,
            "width" => 10,
            "maxlength" => $maxlength,
            "required" => $required
        );
}
if($login_fabrica == 35 || $login_fabrica == 72){
    if ($login_fabrica == 14 or $login_fabrica == 66){
        $lbl = traduz("LAI");
    }else{
        $lbl = traduz("Admin");
    }
       $inputs["mao_de_obra_troca"] = array(
            "type" => "input/text",
            "label" => traduz("M. Obra Troca ").$lbl,
            "span" => 4,
            "width" => 6,
            "maxlength" => 14,
            "extra" => array(
                "price" => "true"
            )
        );
}

if($login_fabrica == 178){

    $inputs["marca[]"] = array(
        "type" => "select",
        "label" => "Marca",
        "class" => "bd_sel",
        "span" => 4,
        "width" => 5,
        "extra" => array(
            "multiple" => "true"
        ),
        'required' =>true,
        "options" => array()
    );


    $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica and ativo is true order by nome";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        for($i=0;pg_num_rows($res)>$i;$i++){
            $xmarca = pg_fetch_result($res,$i,'marca');
            $xnome  = pg_fetch_result($res,$i,'nome');

            $inputs["marca[]"]["options"][$xmarca] = $xnome;

            if($xmarca == 603){
                $selected= " selected ";
            }

            //$inputs["marca[]"]["options"] = array("checked");
        }
    }

}

//if(in_array($login_fabrica, array(,,,146,,,,114)) or $multimarca =='t'){
if(in_array($login_fabrica, array(1,3,30,52,81,86,104,114,131,144,146,169,170,176)) or $multimarca =='t'){
   $inputs["marca"] = array(
        "type" => "select",
        "label" => traduz("Marca"),
        "span" => 4,
        "width" => 5,
        "options" => array()
    );

   if (in_array($login_fabrica, array(131,146))) {
       $inputs["marca"]["required"] = true;
   }

    if (in_array($login_fabrica, array(176)))
    {
        $where = "and ativo is true";
    }else
    {
        //$where = "and visivel is true";
        $where = "and ativo is true";
    }

    $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica {$where} order by nome";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        for($i=0;pg_num_rows($res)>$i;$i++){
            $xmarca = pg_fetch_result($res,$i,'marca');
            $xnome  = pg_fetch_result($res,$i,'nome');

            $inputs["marca"]["options"][$xmarca] = $xnome;
        }
    }
}

if(in_array($login_fabrica,array(1,20,24,80,96))){
    if ($login_fabrica == 96) {
        $lbl = traduz("Nome Comercial");

     } else if($login_fabrica == 1 || $login_fabrica == 80){
        $lbl = traduz("Referência Interna");

     } else if($login_fabrica == 20){
        $lbl = traduz("Bar Tool");

     }else if($login_fabrica == 24){
        $lbl = traduz("Referência Única");

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
            "label" => traduz("Qtd Etiqueta"),
            "span" => 4,
            "inptc" => 7,
            "maxlength" => 3,
            "popover" => array(
                "id" => "btnPopover2",
                "msg" => traduz("Quantidade de etiquetas a serem impressas na Ordem de Serviço caso seja maior que 5 etiquetas.")
            )
        );
}

if (in_array($login_fabrica, [193])) {
    $inputs["valor_hora_tecnica"] = array(
        "type"  => "input/text",
        "label" => "Valor Hora Téc.",
        "span"  => 4,
        "inptc" => 8
    );   
}

if(in_array($login_fabrica,array(19))){
    $inputs["validacao_cadastro"] = array(
            "type" => "input/text",
            "label" => "Validação Cadastro",
            "span" => 4,            
            "width" => 5,
            "maxlength" => 10
        );
}

if( in_array($login_fabrica, [173]) ){
    $inputs["ean"] = array(
            "type" => "input/text",
            "label" => traduz("EAN"),
            "span" => 4,
            "inptc" => 10,
            "maxlength" => 13
        );
}

if ($login_fabrica == 1) {
    $inputs["data_descontinuado"] = array(
            "type" => "input/text",
            "label" => traduz("Data Descontinuado"),
            "span" => 4,
            "inptc" => 7,
    );
}

if($login_fabrica == 14 or $login_fabrica == 66){
    $inputs["abre_os"] = array(
        "label"    => traduz("Permitido Abrir OS"),
        "span" =>4,
        "type"     => "radio",
        "radios"  => array(
            "t" => traduz("Sim"),
            "f" => traduz("Não")
        ),
        "required" =>true
    );
}
if (in_array($login_fabrica, [11,172])) {
    $inputs["abre_os"] = array(
        "label"    => traduz("Permitido Abrir OS"),
        "span" =>2,
        "type"     => "radio",
        "radios"  => array(
            "t" => traduz("Sim"),
            "f" => traduz("Não")
        ),
        "required" =>true
    );
    if (empty($_RESULT['data_cadastro'])) {
        $data_cadastro = date('d/m/Y');
    } else {
        $data_cadastro = $_RESULT['data_cadastro'];
    }
    $inputs["data_cadastro"] = array(
        "label"    => traduz("Data Cadastro \n {$data_cadastro}"),
        "span" =>2
    );
}
if(in_array($login_fabrica,array(152,180,181,182))) {
    $inputs["entrega_por"] = array(
        "label"    => traduz("Valor da Entrega técnica por:"),
        "span" =>8,
        "type"     => "radio",
        "radios"  => array(
            "os" => traduz("Entrega Técnica"),
            "equip" => traduz("Equipamento"),
            "hora" => traduz("Hora")
        ),
    );
}
if($login_fabrica == 14 or $login_fabrica == 66){
    $inputs["aviso_email"] = array(
        "label"    => traduz("Receber E-mail"),
        "span" => 4,
        "type"     => "radio",
        "radios"  => array(
            "t" => traduz("Sim"),
            "f" => traduz("Não")
        )
    );
}

if ($login_fabrica == 20) {
    $inputs["categoria"] = array(
        "type" => "select",
        "label" => traduz("Categoria Mão-de-obra"),
        "span" => 4,
        "width" => 5,
        "required" => true,
        "options" => array()
    );

    $sql = "
        SELECT  categoria,
                descricao
        FROM    tbl_categoria
        WHERE   fabrica = $login_fabrica
        AND     ativo IS TRUE
  ORDER BY      descricao
    ";
    $res = pg_query($con,$sql);
    $categorias = pg_fetch_all($res);

    foreach ($categorias as $categoria) {
        $inputs["categoria"]["options"][$categoria['categoria']] = $categoria['descricao'];
    }
}

// #LUCAS-25042018
if(in_array($login_fabrica,array(171))){
    $inputs["apresentacao"] = array(
            "type" => "input/text",
            "label" => traduz("Apresentação"),
            "span" => 4,
    );
    $inputs["marca_detalhada"] = array(
            "type" => "input/text",
            "label" => traduz("Marca Detalhada"),
            "span" => 4,
    );
    $inputs["descricao_detalhada"] = array(
            "type" => "input/text",
            "label" => traduz("Descrição Detalhada"),
            "span" => 4,
    );
    $inputs["unidade"] = array(
            "type" => "input/text",
            "label" => traduz("Unidade"),
            "span" => 1,
    );
    $inputs["emb"] = array(
            "type" => "input/text",
            "label" => traduz("Emb. 01"),
            "span" => 2,
    );
    $inputs["categoria"] = array(
            "type" => "input/text",
            "label" => traduz("Categoria"),
            "span" => 3,
    );
    $inputs["ncm"] = array(
            "type" => "input/text",
            "label" => traduz("NCM"),
            "span" => 1,
    );
    $inputs["ii"] = array(
            "type" => "input/text",
            "label" => traduz("II%"),
            "span" => 1,
    );
    $inputs["alt"] = array(
            "type" => "input/text",
            "label" => traduz("Alt (CM)"),
            "span" => 1,
    );
    $inputs["larg"] = array(
            "type" => "input/text",
            "label" => traduz("Larg (CM)"),
            "span" => 1,
    );
    $inputs["comp"] = array(
            "type" => "input/text",
            "label" => traduz("Comp (CM)"),
            "span" => 2,
    );
    $inputs["peso"] = array(
            "type" => "input/text",
            "label" => traduz("Peso(KG)"),
            "span" => 1,
    );
    $inputs["cod_barra"] = array(
            "type" => "input/text",
            "label" => traduz("Cód. Barras"),
            "span" => 3,
    );
    $inputs["custo_cip"] = array(
            "type" => "input/text",
            "label" => traduz("Custo Cip. Porto"),
            "span" => 2,
    );

}

if (in_array($login_fabrica, [177])) {
    $inputs["peso"] = array(
        "type" => "input/text",
        "label" => traduz("Peso(KG)"),
        "span" => 1,
    );
}

//checkbox
$arrCheckbox = array("lista_troca" => array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Lista Troca")
        )
    ),
    "numero_serie_obrigatorio" => array(
        "type" => "checkbox",
        "span" => 2,
        "title" =>traduz("Nº Série Obrigatório"),
        "checks" => array(
            "t" => traduz("Nº Série Obg.")
        )
    ),
    "produto_principal" => array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Produto Principal")
        )
    ),
);


if($login_fabrica != 138){
    $arrCheckbox["troca_obrigatoria"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Troca Obrigatória")
        )
    );
}

if (in_array($login_fabrica, [151])) {
    $arrCheckbox["troca_direta"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Troca Direta"
        )
    );
}

if($login_fabrica == 178){
    $arrCheckbox["fora_linha"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Fora de Linha"
        )
    );
}

if (in_array($login_fabrica, [193])) {
    $arrCheckbox["hora_tecnica"] = array(
        "id"   => "hora_tecnica",
        "type" => "checkbox",
        "span" => 2,
        "title" =>"Hora Técnica",
        "checks" => array(
            "t" => "Hora Técnica"
        )
    );

    $arrCheckbox["lancamento"] = array(
        "id"   => "lancamento",
        "type" => "checkbox",
        "span" => 2,
        "title" =>"Lançamento?",
        "checks" => array(
            "t" => "Lançamento?"
        )
    );
}

if (in_array($login_fabrica, array(169,170))) {
    $arrCheckbox["e_ticket"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("E-Ticket")
        )
    );
}

if ($login_fabrica == 148) {
    $arrCheckbox["pecas_reposicao"] = array(
        "type" => "checkbox",
        "span" => 3,
        "checks" => array(
            "t" => traduz("Peças de Reposição")
        )
    );
}

if(in_array($login_fabrica, [167, 203])){
    $arrCheckbox["suprimento"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Suprimento")
        )
    );
}

if($login_fabrica == 138){
    $arrCheckbox["multiplas_os"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "TRUE" => traduz("Multiplas OS")
        )
    );
}

if($login_fabrica == 153){ //hd_chamado=2717074
    $arrCheckbox["reparo_na_fabrica"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Reparo na fábrica")
        )
    );
}

if($login_fabrica == 161){ //hd_chamado=2717074
    $arrCheckbox["insumo"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Insumo")
        )
    );
}


if($login_fabrica == 114){
    $arrCheckbox["exigir_selo"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Exigir Selo")
        )
    );
}

if($login_fabrica == 35 || $login_fabrica == 91){
    $arrCheckbox["produto_critico"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Produto Crítico")
        )
    );
}

if($login_fabrica == 35){
    $arrCheckbox["analise_obrigatoria"] = array(
        "type" => "checkbox",
        "span" => 6,
        "checks" => array(
            "t" => "Análise Obrigatória no Posto Central"
        )
    );
}

if($login_fabrica == 104){ //HD-2303024
    $arrCheckbox["produto_critico"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Produto Auditado")
        )
    );
}

if($login_fabrica == 1){

    $arrCheckbox["locador"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Locador")
            )
        );

    $arrCheckbox["troca_b_garantia"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Troca Garantia")
        )
    );
    $arrCheckbox["troca_b_faturada"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Troca Faturada")
        )
    );

    // $arrCheckbox["inibir_lista_basica"] = array(
    //     "type" => "checkbox",
    //     "span" => 2,
    //     "checks" => array(
    //         "t" => "Inibir Lista Básica"
    //     )
    // );
}

if (in_array($login_fabrica,array(42,142,152,180,181,182))) {
    $arrCheckbox["entrega_tecnica"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => ($login_fabrica == 142) ? traduz("Visita Técnica") : traduz("Entrega Técnica")
        )
    );
}

if(in_array($login_fabrica, array(142))){
    $arrCheckbox["deslocamento_km"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Deslocamento KM")
        )
    );
}

if (in_array($login_fabrica, array(3,11,14,86,172))) {
    $arrCheckbox["intervencao_tecnica"] = array(
        "type" => "checkbox",
        "span" => 3,
        "checks" => array(
            "t" => traduz("Intervenção Técnica")
        )
    );
}

if (in_array($login_fabrica, array(11,172))) {
    $arrCheckbox["codigo_interno_obrigatorio"] = array(
        "type" => "checkbox",
        "span" => 3,
        "checks" => array(
            "t" => traduz("Código Interno Obrigatório")
        )
    );
}

if (in_array($login_fabrica, array(11,172))) {
    $arrCheckbox["fabrica_produto"] = array(
        "type" => "checkbox",
        'label' => traduz('Ref. Pertence à:'),
        "class" => "fab_prod",
        "span" => 2,
        "checks" => array(
            "A" => traduz("Aulik"),
            "P" => traduz("Pacific")
        ),
        "required" => true
    );
}

if(in_array($login_fabrica, array(3,45,146))) {
    $arrCheckbox["validar_serie"] =  array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Validar Série")
        )
    );
}

if(in_array($login_fabrica, array(3))) {
    $arrCheckbox["ativacao_automatica"] =  array(
        "type" => "checkbox",
        "span" => 3,
        "checks" => array(
            "t" => traduz("Ativação Automática")
        )
    );
}

if (in_array($login_fabrica, [177])) {
    $arrCheckbox["lote"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => traduz("Lote")
        )
    );
}

if (in_array($login_fabrica, [187,188])) {
    $arrCheckbox["ressarcimento_obrigatoria"] = array(
        "type" => "checkbox",
        "span" => 2,
        "checks" => array(
            "t" => "Ressarc. Obrigatória"
        )
    );
}
$fab_nome = "";
if (in_array($login_fabrica, [11,172])) {
    $fab_nome = ($login_fabrica == 11) ? traduz("- AULIK") : traduz("- PACIFIC");
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<form name="frm_produto" method="post" enctype='multipart/form-data' action="<?= $PHP_SELF ?>" <?= $onsubmit;?> class='form-search form-inline tc_formulario'>
<?if(strlen($produto) > 0){
    ?><div class="titulo_tabela"><?=traduz('Alterando cadastro')?> <?=$fab_nome?></div><?
}else{
    ?><div class="titulo_tabela"><?=traduz('Cadastro')?> <?=$fab_nome?></div><?
}?>

        <br/>
<?
    echo montaForm($inputs, $hiddens); ?>


     <? echo montaForm($arrCheckbox, null);?>

     <?//hd 21461
    if (in_array($login_fabrica,array(1,165))) {
        $qtde_item = 100;
        $qtde_item_visiveis = 5;
        $qtde_linhas = 0;

        if (strlen($produto) > 0) {
            $sql = "
                SELECT
                    tbl_produto.referencia,
                    tbl_produto.descricao,
                    tbl_produto.produto,
                    tbl_produto.voltagem,
                    tbl_produto_troca_opcao.produto_troca_opcao,
                    tbl_produto_troca_opcao.kit
                FROM tbl_produto
                JOIN tbl_produto_troca_opcao ON tbl_produto.produto = tbl_produto_troca_opcao.produto_opcao
                AND tbl_produto_troca_opcao.produto = {$produto}
                ORDER BY tbl_produto_troca_opcao.kit,tbl_produto.descricao,tbl_produto.voltagem;
            ";

            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $qtde_linhas = pg_num_rows($res);
            }
        }
?>

        <input type='hidden' name='qtde_item' value="<? echo $qtde_item; ?>">

        <br><br>
        <table class='tabela_item table table-striped table-bordered table-hover table-fixed'>
            <thead>
                <tr class='titulo_tabela'>
                    <td colspan='5' class="tac">
                    <b><?=traduz('Pode ser trocado por:')?></b>
                    </td>
                </tr>
            </thead>
            <!-- //HD #145639 - INSTRUÇÕES -->
<?php
        if ($login_fabrica == 1) {
?>
            <tr>
                <td colspan='5'>
                <b>KITs:</b> <?=traduz('podem ser criados KITs para trocar um produto por vários. Para isto, selecione na coluna KIT o mesmo número para agrupar vários produtos. No momento da troca os produtos com o mesmo número de KIT serão agrupados para seleção.')?>
                </td>
            </tr>
<?php
        }
?>
            <tr>

                <td colspan='5' >
                    <div class="control-group pull-right ">
                        <label><?=traduz('Qtde linhas')?></label>
                            <div class="controls controls-row ">
                                <select onChange='qtdeLinhas(this)' class='span2'>
                                    <option value='5'><?=traduz('5 Linhas')?></option>
                                    <option value='10'><?=traduz('10 Linhas')?></option>
                                    <option value='15'><?=traduz('15 Linhas')?></option>
                                    <option value='30'><?=traduz('30 Linhas')?></option>
                                    <option value='50'><?=traduz('50 Linhas')?></option>
                                    <option value='100'><?=traduz('100 Linhas')?></option>
                                </select>
                            </div>
                    </div>
                </td>
            </tr>
            <thead>
            <!-- //HD #145639 - NOME DAS COLUNAS -->
            <tr class='titulo_coluna'>
                <th style="width:2px;"></th>
                <th><?=traduz('Código Produto')?></th>
                <th><?=traduz('Nome Produto')?></th>
                <th><?=traduz('Voltagem')?></th>
                <? if ($login_fabrica == 1) { ?>
                    <th><?=traduz('KIT')?></th>
                <? } else if ($login_fabrica == 165) { ?>
                    <th><?=traduz('Ordem')?></th>
                <? } ?>
            </tr>
            </thead>

<?php
        for ($i = 0; $i < $qtde_item; $i++) {
            $referencia_opcao_   = "";
            $descricao_opcao     = "";
            $produto_opcao       = "";
            $produto_troca_opcao = "";
            $voltagem_opcao = "";
            $kit_opcao = "";
            $ocultar_linha = "";

            $referencia_opcao    = pg_fetch_result($res,$i,'referencia');
            $descricao_opcao     = pg_fetch_result($res,$i,'descricao');
            $produto_opcao       = pg_fetch_result($res,$i,'produto');
            $produto_troca_opcao = pg_fetch_result($res,$i,'produto_troca_opcao');
            $voltagem_opcao      = pg_fetch_result($res,$i,'voltagem');
            $kit_opcao           = pg_fetch_result($res,$i,'kit');

            $erro_linha = "erro_linha" . $i;
            $erro_linha = $$erro_linha;
            $cor_erro = "#FFFFFF";
            if ($erro_linha == 1) $cor_erro = "#FF9999";
            
            
            if ($i + 1 > $qtde_item_visiveis && $i + 1 > $qtde_linhas) {
                $ocultar_linha = " style='display:none' ";
            }
?>

            <tr <?=$ocultar_linha?> rel="<?=$i?>" bgcolor="<?=$cor_erro?>">
                <td style="text-align:center;"><input type='hidden' name="produto_opcao_<?=$i?>" rel='produtos' value="<?=$produto_opcao?>">
                    <input type='hidden' name="produto_troca_opcao_<?=$i?>"    rel='produtos' value="<?=$produto_troca_opcao?>">
                    <input type='hidden' name="voltagem_troca_opcao_<?=$i?>"   rel='produtos' value=''> <?=$i+1?></td>
                    <td style="text-align:center;">
                        <div class="input-append">
                            <input class='span2' type='text' id="referencia_opcao_<?=$i?>" name="referencia_opcao_<?=$i?>" rel='produtos' maxlength='30' value="<?=$referencia_opcao?>" onchange="javascript: document.frm_produto.voltagem_opcao_<?=$i?>.value = '' ">
                            <span class="add-on" rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" posicao="<?=$i?>" voltagemForm="true" tipo="produto" parametro="referencia" />
                        </div>
                    </td>
                    <td style="text-align:center;">

                        <div class="input-append">
                            <input type='text' class="span4"  id="descricao_opcao_<?=$i?>" name="descricao_opcao_<?=$i?>" rel='produtos' maxlength='50' value="<?=$descricao_opcao?>" onchange="javascript: document.frm_produto.voltagem_opcao_<?=$i?>.value = '' ">
                            <span class="add-on" rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" posicao="<?=$i?>" voltagemForm="true" tipo="produto" parametro="descricao" />
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <input class='inptc6' type='text' id='voltagem_opcao_<?=$i?>' name='voltagem_opcao_<?=$i?>' rel='produtos' value="<?=$voltagem_opcao?>" readonly>
                    </td>

                    <!-- // HD #145639 - COLUNA DE KITS -->
                    <? if ($login_fabrica == 1) {
                        $n_kit_opcoes = 20; ?>
                        <td style="text-align:center;">
                            <select class='inptc9' name="kit_opcao_<?= $i;?>" >
                                <option value='0'>--</option>
                                <? for($k = 1; $k <= $n_kit_opcoes; $k++) {
                                    if ($kit_opcao == $k) {
                                        $selected = "selected";
                                    } else {
                                        $selected = "";
                                    } ?>
                                    <option <?=$selected?>  value="<?= $k; ?>" ><?= $k?></option>
                                <? } ?>
                            </select>
                        </td>
                    <? // HD3231391 - Prioridade produtos para troca
                    } else if ($login_fabrica == 165) { ?>
                        <td style="text-align:center;">
                            <input class="inptc9 tac" type="text" name="kit_opcao_<?= $i; ?>" id="kit_opcao_<?= $i; ?>" maxlength="3" value="<?= $kit_opcao; ?>" />
                        </td>
                    <? } ?>
                </tr>
            <? } ?>
        </table>
    <? }
    // HD 96953 - Adicionar imagem do produto para a Britânia
    if ($login_fabrica == 3 || ($login_fabrica > 80 && $login_fabrica < 138) && !empty($produto)) {
        
    $imagem_produto = $produto.'.jpg';
        if (strlen($imagem_produto)>0) {
            $imagem     = "imagens_produtos/$login_fabrica/media/$imagem_produto";
            $msg_imagem = traduz("Anexar imagem do produto:");
        if (file_exists("../$imagem")) {
                $tag_imagem = "<a href='../".str_replace("pequena", "media", $imagem)."' ><img valign='top' src='../$imagem?bypass=" . md5(mt_rand(100,999)) . "' title='".traduz("Clique para ver a imagem")."' valign='middle' class='thickbox' width='160'></a>\n<img src='../imagens/excluir_loja.gif' class='excluir' id='prod$produto?>' style='cursor:pointer' alt='".traduz("Excluir Imagem")."' title='".traduz("Excluir imagem")."' />";

                $msg_imagem = traduz("Mudar imagem:");
            }

    ?>

        <div class="row-fluid">
            <div class="span2"></div>
                <div class="span8">
                    <div class="control-group">
                        <label><?=$msg_imagem?></label>
                        <div class="controls controls-row">
                            <input type='hidden' name='MAX_FILE_SIZE' value='1048576'>
                            <input title='<?=traduz("Selecione o arquivo com a foto do produto")?>'
                        type='file' name='arquivo' size='18'
                        class="multi {accept:'jpg|gif|png', max:'1', STRING: {remove:'<?=traduz("Remover")?>',selected:'<?=traduz("Selecionado")?>: <?=$file?>', denied:'<?=traduz("Tipo de arquivo inválido:")?> <?=$ext?>!'}}">
                        <?=$tag_imagem?>
                        </div>
                    </div>
                </div>
            <div class="span2"></div>
        </div>
      <?
        }

    }else if($login_fabrica >= 138 and !in_array($login_fabrica, array(172,176))){

        include_once 'class/aws/s3_config.php';

        include_once S3CLASS;

        $s3 = new AmazonTC("produto", $login_fabrica);
        $imagem_produto = $s3->getObjectList($produto);
        $imagem_produto = basename($imagem_produto[0]);
        $imagem_produto = $s3->getLink($imagem_produto);

        ?>

        <div class="row-fluid">
            <div class="span2"></div>
            <?php
            if ($usaProdutoGenerico || in_array($login_fabrica, array(169,170))) {
                $observacao = ($observacao == 'null') ? '' : $observacao;
                echo '<div class="span4">
                        <div class="control-group">
                            <label>'.traduz("Características do Produto").'</label>
                            <textarea id="descricao_produto" name="descricao_produto" class="span12">'.$observacao.'</textarea>
                        </div>
                    </div>';
            }
            ?>
            <?php 
            if (!in_array($login_fabrica, array(173))){ ?>
                <div class="span6">
                    <div class="control-group">
                        <label>Imagem do Produto</label>
                        <div class="controls controls-row">
                            <input type='hidden' name='MAX_FILE_SIZE' value='1048576'>
                            <input title='<?=traduz("Selecione o arquivo com a foto do produto")?>'
                        type='file' name='arquivo' size='18'
                        class="multi {accept:'jpg|gif|png', max:'1', STRING: {remove:'<?=traduz("Remover")?>',selected:'<?=traduz("Selecionado")?>: <?=$file?>', denied:'<?=traduz("Tipo de arquivo inválido:")?> <?=$ext?>!'}}">
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="span2"></div>
        </div>

        <?php
        if(strlen($imagem_produto) > 0){
            ?>
                <div class="row-fluid">
                    <div class="span2"></div>
                    <div class="span8" style="text-align: center; min-height: 155px;">
                        <?php
                           echo "<a href='{$imagem_produto}' target='_blank'><img src='{$imagem_produto}' style='max-width: 150px; max-height: 150px;_height:150px;*height:150px;' /></a>";
                        ?>

                    </div>
                    <div class="span2"></div>
                </div>

                <div style="clear: both"></div>

                <div class="row-fluid">
                    <div class="span2"></div>
                    <div class="span8" style="text-align: center;">
                        <a href="produto_cadastro.php?produto=<?php echo $produto; ?>&excluir_imagem=true">
                            <button type="button" class="btn btn-danger" style="width: 145px; text-align: center;"><?=traduz('Excluir Anexo')?></button>
                        </a>
                    </div>
                    <div class="span2"></div>
                </div>

            <?php
        }

    }

    if (in_array($login_fabrica , array(3))  && !empty($produto)) {//HD 325481?>
    <br/>

            <table class="table table-striped table-bordered table-hover table-fixed">
            <thead>
                <tr>
                    <th class="titulo_tabela" colspan="3"><?=traduz('Máscara')?></th>
                </tr>
                <tr>
                    <th class="titulo_tabela"><?=traduz('Máscara')?></th>
                    <? if($usa_versao_produto){ ?>
                    <th class="titulo_tabela"><?=traduz('Posicao da Versão ')?></th>
                    <? } ?>
                    <th class="titulo_tabela"><?=traduz('Ação')?></th>
                </tr>
            </thead>
            <tbody id="tabela_mascara">
            <?php

                $sql_mask = "SELECT * FROM tbl_produto_valida_serie WHERE fabrica = $login_fabrica AND produto = $produto;";
                $res_mask = pg_query($sql_mask);
                $tot_mask = pg_num_rows($res_mask);

                for ($i = 0; $i < $tot_mask; $i++) {?>

                    <tr id="mascara_serie_<?echo $i; ?>">
                        <td><input type="text" name="mascara_<?echo $i; ?>" id="mascara_<? echo $i; ?>" value="<? echo pg_fetch_result($res_mask, $i, 'mascara')?>" readonly="readonly" /></td>
                           <? if($usa_versao_produto){ ?>
                                <td><input type="text" name="versao_<?echo $i; ?>" id="mascara_<? echo $i; ?>" value="<? echo pg_fetch_result($res_mask, $i, 'posicao_versao')?>" readonly="readonly" /></td>
                           <? } ?>
                        <td><input type="button" class="btn btn-danger" name="btn_excluir_<? echo $i; ?>" value='<?=traduz("Excluir")?>' onclick="excluirMascara(<? echo $i; ?>)" /></td>
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
                        <? if($usa_versao_produto){ ?>
                            <input type="hidden" name="versao_mascara" id="versao_mascara" value="<?=$i?>" />
                        <? } ?>
                        <input type="button" class="btn" name="btn_inserir" value='<?=traduz("Inserir")?>' onclick="inserirLinhaSerie()" />

                    </div>
                </div>
            </div>
            <!-- margem -->
            <div class="span2"></div>
        </div>
    <? }
    if($login_fabrica == 20){ ?>

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
                            $produto          = trim(pg_fetch_result($res2,0,'produto'));
                            $idioma           = trim(pg_fetch_result($res2,0,'idioma'));
                            $descricao_idioma = trim(pg_fetch_result($res2,0,'descricao'));
                        }else{?>
                            <tr>
                                <td colspan='3'>
                                    <b>Atenção</b>: <?=traduz('Não existe descrição para esse produto em outro idioma, preencha o campo abaixo para inserir uma.')?><br>
                                    <input type='hidden' name='idioma_novo' value='ES'>
                                </td>
                            </tr>
                        <?}?>
                        <tr>
                            <td width="100px">
                                <div class="control-group">
                                    <label class="control-label"><b><?=traduz('Espanhol')?></b> <br/> <?=traduz('Descrição')?>:</label>
                                    <div class="controls controls-row">
                                        <input type='hidden' name='idioma' value='ES'>
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
                                                <label class="label label-info"><?=traduz('Máquina liberada para')?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row-fluid">

                                    <div class="span3">
                                        <div class="control-group tac">
                                            <label class="control-label"> <b><?=traduz('País')?> </b> </label>
                                        </div>
                                    </div>

                                    <div class="span3">
                                        <div class="control-group tac">
                                            <label class="control-label"><b><?=traduz('Garantia')?></b> </label>
                                        </div>
                                    </div>

                                    <div class="span3">
                                        <div class="control-group tac">
                                            <label class="control-label"><strong><?=traduz('Preço')?></strong> </label>
                                        </div>
                                    </div>

                                    <?if ($prod_libera_br or $prod_libera_outros) {?>
                                        <div class="span3">
                                            <div class="control-group tac">
                                                <label class="control-label"><strong><b><?=traduz('Ação')?></b></strong> </label>
                                            </div>
                                        </div>
                                     <?}?>
                                </div>


                             <? for($i = 0 ; $i < pg_num_rows($res2) ; $i++ ){
                                    $pais          = trim(pg_fetch_result($res2,$i,'pais'));
                                    $garantia_pais = trim(pg_fetch_result($res2,$i,'garantia'));
                                    $preco_pais    = number_format(pg_fetch_result($res2, $i, 'valor'), 2, ',', '');
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
                                                <label class="control-label"> <a href="javascript: if (confirm('<?=traduz("Deseja excluir?")?>')) {window.location='<?=$PHP_SELF."?produto=$produto&acao=a&pais=$pais"?>'} "><?=traduz('Excluir')?></a> </label>
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
                            $msg_libera = ($prod_libera_br) ? traduz('Liberar o produto para o') : traduz('Escolha o país');
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
                                        <label class="control-label"><b><?=traduz('Garantia do País')?></b></label>
                                        <div class="controls controls-row">
                                            <input  type='text' class='frm' name='garantia_pais' value='' size='15' id='garantia_pais' maxlength='50'>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row-fluid">
                                <div class="span12">
                                    <div class="control-group ">
                                        <label class="control-label"><b><?=traduz('Preço')?></b></label>
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
                                            <input type='button'  name='btn_produto_pais' value = '<?=traduz("Atribui Pais")?>'onclick="javascript:window.location='<?="$PHP_SELF?produto=$produto&acao=atribui&pais='+document.getElementById('produto_pais').value+'&garantia_pais='+document.getElementById('garantia_pais').value+'&preco_pais='+document.getElementById('preco_pais').value"?>">
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
    <?php
        if(($inf_valores_adicionais || $fabrica_usa_valor_adicional) && strlen($produto) > 0){
        ?>
        <br/>

            <table id="resultado" align='center' border='0' class='table table-striped table-bordered table-hover table-fixed'>
                <thead>
                    <tr class="titulo_tabela">
                        <th  colspan="3" > <?=traduz('Valores Adicionais ')?></th>
                    </tr>
                    <tr class="titulo_tabela">
                        <th><?=traduz('Serviço')?></th>
                        <th><?=traduz('Valor')?></th>
                        <th><?=traduz('Ação')?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td width="270px">
                            <div class="control-group">
                                <label class="control-label"><?=traduz('Serviço')?></label>
                                <div class="controls controls-row">
                                    <input type='text' name='servico' >
                                </div>
                            </div>
                        </td>


                        <td width="130px">
                            <div class="control-group ">
                                <label class="control-label"><?=traduz('Valor')?></label>
                                <div class="controls controls-row">
                                    <input class="inptc7" type='text' name='valor'>
                                </div>
                            </div>

                        </td>
                        <td>
                            <div class="control-group">
                                <label class="control-label"></label>
                                <div class="controls controls-row tac">
                                    <input type='hidden' name='servico_id' value=''>
                                    <input type='hidden' name='produto_id' value='<?=$produto?>'>
                                    <input type='button' class="btn" value='<?=traduz("Gravar Valores Adicionais")?>' onclick='javascript: gravaDados();'>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php echo mostraDados($produto);?></div>

                </tbody>
            </table>
     <?php } ?>
     <!-- HD 335150 INICIO -->
    <?php

    if (in_array($login_fabrica,$fabricaIntervaloSerie)){ ?>

        <table  id="table_serie_in_out" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr>
                    <th colspan="3" class='titulo_tabela tac'><?=traduz('Intervalo de Série')?></th>
                </tr>
                <tr class="titulo_coluna">
                    <th><?=traduz('Série Inicial')?></th>
                    <th><?=traduz('Série Final')?></th>
                    <th><?=traduz('Ações')?></th>
                </tr>
            </thead>
           <tbody>
            <?
            $linha = '0';
            if (strlen($produto) > 0){
                $sql = "
                    SELECT
                        produto_serie,
                        serie_inicial,
                        serie_final

                    FROM
                        tbl_produto_serie
                    where
                        tbl_produto_serie.produto = $produto
                    order by produto_serie
                ";

                $res = pg_query ($con,$sql);
                $linha = pg_num_rows($res);

                if ( $_POST['qtde_itens'] >0 ){
                    $linha = $_POST['qtde_itens'];
                }

                for ($i = 0; $i < $linha; $i++){

                    if ( $_POST['qtde_itens'] > 0 and $msg_erro){

                        $produto_serie  = $_POST['produto_serie_in_out_'.$i];
                        $serie_in       = $_POST['serie_in_'.$i];
                        $serie_out      = $_POST['serie_out_'.$i];

                    }else{

                        $produto_serie = pg_fetch_result($res,$i,'produto_serie');
                        $serie_in      = pg_fetch_result($res,$i,'serie_inicial');
                        $serie_out     = pg_fetch_result($res,$i,'serie_final');
                    }


                    $length_serie_in = ($login_fabrica == 15) ? " maxlength=\"10\" " : null;
                    $length_serie_out = ($login_fabrica == 15) ? " maxlength=\"10\" " : null;

                    $on_keyUp = ($login_fabrica == 15 || $login_fabrica == 45) ? " onKeyUp=\"somenteMaiusculaSemAcento(this);\" " : null;

                    $maxLengthLatina = ($login_fabrica == 15) ? '10' : '30' ;

                    ?>
                    <tr>

                        <td class="tac">
                            <input type="hidden" name="produto_serie_in_out_<?=$i?>" value="<? echo $produto_serie;?>">

                            <input  type="text" class='frm serie' <?echo $length_serie_in; echo $on_keyUp;?> rel="<?php echo $i;?>" id="serie_in_<?echo $i?>" name="serie_in_<?=$i?>" maxlength="<?=$maxLengthLatina?>" value="<? echo $serie_in;?>">
                        </td>

                        <td class="tac">
                            <input type="text"  class='frm serie' <?echo $length_serie_out; echo $on_keyUp;?> rel="<?php echo $i;?>" id="serie_out_<?echo $i?>" name="serie_out_<?=$i?>" size="15" maxlength="<?=$maxLengthLatina?>" value="<?echo $serie_out;?>">
                        </td>

                        <td class="tac">
                            <input type="checkbox" id="apagar_serie_<?echo $i?>" name="apagar_serie_<?echo $i?>" value='<?=traduz("excluir")?>'>
                            <label style='cursor:pointer' for="apagar_serie_<?echo $i?>"><?=traduz('Excluir')?></label>
                        </td>

                    </tr>
                    <?php
                }
            }?>
            </tbody>
        <input type="hidden" name="qtde_itens" value="<?=$linha?>" id="qtde_itens">
        </table>
        <table class="formulario" align="center" width="700px" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    &nbsp;
                </td>
            </tr>
            <tr>
                <td align="center">
                    <input type='button' class="btn" id='add_line' value='<?=traduz("Acrescentar Intervalo de Série")?>' onclick='adiciona()'>
                </td>
            </tr>
        </table>

        <?php } ?>
    <!-- HD 335150 FIM -->
    <input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
        <div class="row-fluid">
            <!-- margem -->
            <div class="span4"></div>

            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <? if (strlen($produto) > 0){
                            $onclick = "onclick=\"if (confirm('".traduz("Você irá atualizar um produto! Confirma esta ação? Caso deseje apenas inserir um novo, cancele a operação, limpe as informações da tela e insira o produto!")."')) { if(validaForm()){ submitForm($(this).parents('form'),'gravar');} }\" ";
                        }else{
                            $onclick = "onclick=\"if (document.frm_produto.btn_acao.value == '' ) { if(validaForm()){ submitForm($(this).parents('form'),'gravar');} } else { alert ('".traduz("Aguarde submissão")."'); } return false;\" ";
                        }?>

                        <button type="button" class="btn" value='<?=traduz("Gravar")?>' alt='<?=traduz("Gravar formulário")?>' <?php echo $onclick;?> ><?=traduz('Gravar')?></button>

                        <? if (strlen($produto) > 0){?>
                            <button type="button" class="btn btn-warning" value='<?=traduz("Limpar")?>'' onclick="javascript:  window.location='<? echo $PHP_SELF ?>'; return false;" ALT='<?=traduz("Limpar campos")?>'><?=traduz('Limpar')?></button>
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
            <strong><?=traduz('ÚLTIMA ATUALIZAÇÃO')?>: </strong><?echo $admin ." - ". $data_atualizacao;?>
             - <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_produto&id=<?php echo $produto; ?>' name="btnAuditorLog"><?=traduz('Visualizar Log Auditor')?></a>
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

    $res0 = pg_query ($con,$sql);

    if (pg_num_rows($res0) > 0) {?>
        <div class="container">
            <table class="tabela_item table table-striped table-bordered table-hover">
                <thead>
                    <tr align='center'>
                        <th colspan="3" class="titulo_tabela"><?=traduz('PRODUTOS / SUBPRODUTOS RELACIONADOS')?></th>
                    </tr>
                    <tr class="titulo_coluna" align='center'>
                        <th><?=traduz('REFERÊNCIA')?></th>
                        <th><?=traduz('DESCRIÇÃO')?></th>
                        <th><?=traduz('LISTA BÁSICA')?></th>
                    </tr>
                </thead>
                <tbody>
                <? for ($y = 0; $y < pg_num_rows($res0); $y++) {

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
        <a href='preco_cadastro_produto_lbm.php?produto=<?=$produto?>&btn_acao=listar'><?=traduz('CLIQUE AQUI PARA LISTAR A TABELA DE PREÇOS')?> </a><br><br>
    </div>
<?}?>

<? if ($login_fabrica == 3) {?>

    <div class="container">
        <div class="row-fluid">
            <div class="span12">
                <div class="control-group ">
                    <div class="controls controls-row tac">
                        <a href='produto_consulta_parametro.php' target='_blank'><?=traduz('CLIQUE AQUI PARA CONSULTAR FILTRANDO DE ACORDO COM O TIPO (EX.: TROCA OBRIGATÓRIA)')?></a>
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
                    <button type='button' class="btn" onclick="window.location='<?echo $PHP_SELF;?>?listartudo=1'"><?=traduz('Listar Todos os Produtos')?></button>
                <? if ($login_fabrica == 1) {?>
                        <br><br>
                    <div id="gerar_excel_gerencial" class="btn_excel">
                        <span><img src='imagens/excel.png' /></span>
                        <span class="txt"><?=traduz('Excel Gerencial')?></span>
                    </div>
                    <br>
                    <div id="gerar_excel_kit" class="btn_excel">
                        <span><img src='imagens/excel.png' /></span>
                        <span class="txt"><?=traduz('Excel KIT')?></span>
                    </div>
                <?php }
		if (in_array($login_fabrica, array(169,170))) { ?>
                    <button type='button' class="btn btn-info" onclick="window.location='<?echo $PHP_SELF;?>?listarprodutolf=1'">Listar Produtos sem Linha e Familia</button>
                <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div><br/>
<?php

$listartudo = $_GET['listartudo'];
$listarprodutolf = $_GET['listarprodutolf'];

if ($listartudo == 1 && !$listarprodutolf) {

    $sql = "SELECT  tbl_produto.referencia,
                    tbl_produto.voltagem,
                    tbl_produto.produto,
                    tbl_produto.descricao,
                    tbl_produto.mao_de_obra,
                    tbl_produto.ativo,
                    tbl_produto.produto_critico,
                    tbl_produto.uso_interno_ativo,
                    tbl_produto.locador,
                    (SELECT CASE WHEN COUNT(*) > 0
                                 THEN TRUE ELSE FALSE
                            END AS pecas
                       FROM tbl_lista_basica AS lbm
                      WHERE lbm.produto = tbl_produto.produto
                        AND lbm.fabrica = $login_fabrica) AS tem_lbm,
                    (SELECT CASE WHEN COUNT(comunicado) > 0
                                 THEN TRUE ELSE FALSE
                            END AS comunicados
                       FROM tbl_comunicado
                       LEFT JOIN tbl_comunicado_produto using(comunicado)
                      WHERE tipo = 'Vista Explodida'
                        AND (tbl_comunicado.produto = tbl_produto.produto OR tbl_comunicado_produto.produto = tbl_produto.produto)
                        AND fabrica = $login_fabrica)     AS tem_vista,
                    tbl_produto.referencia_fabrica,
                    tbl_produto.garantia,
                    tbl_familia.descricao AS familia,
                    tbl_linha.nome        AS linha
            FROM    tbl_produto
            JOIN    tbl_linha     USING (linha)
            LEFT JOIN tbl_familia USING (familia)
            WHERE   tbl_linha.fabrica = $login_fabrica
        AND tbl_produto.fabrica_i = $login_fabrica";

    if (in_array($login_fabrica, array(35, 81, 114))) {
                $sql .= " ORDER BY
                    tbl_produto.descricao ASC
                ";
    } else {
        $sql .=    " ORDER BY    tbl_linha.nome ASC,
                                 tbl_produto.descricao ASC
                      ";
    }
    // $sql .= " limit 500";
    $res = pg_query ($con,$sql);
    $count = pg_num_rows($res);
    if ($login_fabrica <> 1){
        if($count >= 500){
       ?>
            <div id='registro_max'>
                    <h6><?=traduz('Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.')?></h6>
            </div>
        <? }
    }

    // ícones 'SIM' e 'NÃO'
    $img_ativo   = "<span style='display: none'>".traduz("Ativo")."</span><img src='imagens/status_verde.png'    border='0'  alt='".traduz("Ativo")."'>";
    $img_inativo = "<span style='display: none'>".traduz("Inativo")."</span><img src='imagens/status_vermelho.png' border='0' alt='".traduz("Inativo")."'>";
if ($login_fabrica != 52){
 ?>
     <table id="listagemProduto" style="margin: 0 auto;" class="tabela_item table table-striped table-bordered table-hover table-large">
                    <thead>
                        <tr class='titulo_coluna'>
                            <th nowrap><?=traduz('Status Rede')?></th>
                            <th nowrap><?=traduz('Status Interno')?></th>
                            <?php if ($login_fabrica == 171) { ?>
                            <th><?=traduz('Referência Fábrica')?></th>
                            <?php } ?>
                            <th><?=traduz('Referência')?></th>
                            <th><?=traduz('Descrição')?></th>
                            <?php if ($login_fabrica == 104) { ?>
                                <th class='tac' ><?=traduz('Garantia(meses)')?></th>
                                <th class='tac' ><?=traduz('Mão de Obra')?></th>
                            <?}?>

                        <?if ($login_fabrica == 1 or $login_fabrica == 80) { // HD  90109 ?>

                            <th class='tac' ><?=traduz('Voltagem')?></th>
                            <th class='tac' nowrap ><?=traduz('Referência Interna')?></th>

                        <?}?>
                        <?php if ($login_fabrica == 74) { ?>
                            <th class='tac' ><?=traduz('Voltagem')?></th>
                        <?}?>
                        <?php if ($login_fabrica != 189) { ?>
                        <th class='tac' ><?=traduz('Família')?></th>
                        <?}?>
                        <th class='tac' ><?=traduz('Linha')?></th>

                        <?if ($login_fabrica == 104) { // HD 90109?>
                            <th title='Possui Lista Básica de Materiais (peças)'><?=traduz('LBM')?></th>
                            <th title='Tem Vista Explodida?'><?=traduz('Vista Explodida')?></th>
                            <th title=''><?=traduz('Produto Auditado');?></th>
                        <?}?>

                        <?if ($login_fabrica == 1) { // HD 90109?>
                            <th class='tac' ><?=traduz('Locador')?></th>
                        <?}?>

                        </tr>
                    </thead>

                <tbody>
    <?php
    for ($i = 0; $i < $count; $i++) {
        $tem_lbm = pg_fetch_result($res, $i, 'tem_lbm');
        $tem_ve  = pg_fetch_result($res, $i, 'tem_vista');
        ?>

                    <tr>
                        <td class='tac'>
                            <?echo (pg_fetch_result($res,$i,'ativo') == 't') ? $img_ativo : $img_inativo; ?>
                        </td>
                        <td class='tac'>
                            <?echo (pg_fetch_result($res,$i,'uso_interno_ativo') == 't') ? $img_ativo : $img_inativo; ?>
                        </td>
                        <?php if ($login_fabrica == 171) { ?>
                        <td align='left' nowrap>
                            <?php echo pg_fetch_result($res,$i,'referencia_fabrica');?>
                        </td>
                        <?php } ?>
                        <td align='left' nowrap>
                        <? echo pg_fetch_result($res,$i,'referencia');

                        if (!in_array($login_fabrica, array(1, 74, 80, 81, 114))) {
                             if (strlen($volt = pg_fetch_result($res,$i,'voltagem')) > 0)
                                echo " / $volt";
                        }?>
                        </td>

                        <td align='left' nowrap>
                            <a href='<?="$PHP_SELF?produto=" . pg_fetch_result($res,$i,'produto')?>'>
                                <?=pg_fetch_result($res,$i,'descricao')?>
                            </a>
                        </td>
                        <? if ($login_fabrica == 104) { //HD 2303405 ?>
                            <td class='tac'>
                               <? echo pg_fetch_result($res,$i,'garantia'); ?>
                            </td>
                            <td class='tac'>
                                <? echo number_format(pg_fetch_result($res,$i,'mao_de_obra'),0) ; ?>
                            </td>
                         <? } ?>
                        <?if ($login_fabrica == 1 or $login_fabrica == 74 or $login_fabrica == 80 ) { // HD 90109

                            if ($login_fabrica == 74 ){ ?>
                                <td align='left' nowrap>
                                    <? echo pg_fetch_result($res,$i,'voltagem');?>
                                </td>
                        <?php
                            }else{ ?>
                                <td align='left' nowrap>
                                    <? echo pg_fetch_result($res,$i,'voltagem');?>
                                </td>

                                <td align='left' nowrap>
                                    <? echo pg_fetch_result($res,$i,'referencia_fabrica');?>
                                </td>
                            <?}
                        }?>
                        <?php if ($login_fabrica != 189) { ?>        
                        <td align='left' nowrap>
                            <?echo pg_fetch_result($res,$i,'familia');?>
                        </td>
                        <?php } ?>        
                        <td align='left' nowrap>
                            <? echo pg_fetch_result($res,$i,'linha'); ?>
                        </td>

                        <? if ($login_fabrica == 104) { //HD 2303405 ?>

                        <td class='tac'>
                            <? echo ($tem_lbm == 't') ? $img_ativo : $img_inativo; ?>
                        </td>
                        <td class='tac'>
                            <? echo ($tem_ve == 't') ? $img_ativo : $img_inativo; ?>
                        </td>
                        <td class='tac'>
                           <?php $tem_pc = pg_fetch_result($res,$i,'produto_critico');
                             echo ($tem_pc == 't') ? $img_ativo : $img_inativo; ?>
                        </td>

                        <? } ?>

                       <? if ($login_fabrica == 1) { // HD 90109?>
                        <td class='tac'>
                        <?if (pg_fetch_result($res,$i,'locador') <> 't'){
                            echo traduz("Não");
                        }else{
                            echo traduz("Sim");
                        }?>
                        </td>
                    <?}?>
                </tr>
            <?}?>
            </tbody>
        </table>
        <?php
    }else{
        ?>
         <table id="listagemProduto" style="margin: 0 auto;" class="tabela_item table table-striped table-bordered table-hover table-large">
            <thead>
                <tr class='titulo_coluna'>
                    <th><?=traduz('Referência')?></th>
                    <th><?=traduz('Descrição')?></th>
                    <th><?=traduz('Garantia')?></th>
                    <th class='tac' ><?=traduz('Família')?></th>
                    <th class='tac' ><?=traduz('Linha')?></th>
                    <th nowrap><?=traduz('Status Rede')?></th>
                    <th nowrap><?=traduz('Status Interno')?></th>
                </tr>
            </thead>
            <tbody>
            <?php
                for ($i = 0; $i < $count; $i++) {
                    $tem_lbm = pg_fetch_result($res, $i, 'tem_lbm');
                    $tem_ve  = pg_fetch_result($res, $i, 'tem_vista');
                    ?>
                    <tr>
                        <td align='left' nowrap>
                            <? echo pg_fetch_result($res,$i,'referencia');?>
                        </td>
                        <td align='left' nowrap>
                            <a href='<?="$PHP_SELF?produto=" . pg_fetch_result($res,$i,'produto')?>'>
                                <?=pg_fetch_result($res,$i,'descricao')?>
                            </a>
                        </td>
                        <td align='left' nowrap>

                                <?=pg_fetch_result($res,$i,'garantia')?>

                        </td>
                        <td align='left' nowrap>
                            <?echo pg_fetch_result($res,$i,'familia');?>
                        </td>
                        <td align='left' nowrap>
                            <? echo pg_fetch_result($res,$i,'linha'); ?>
                        </td>
                        <td class='tac'>
                            <?echo (pg_fetch_result($res,$i,'ativo') == 't') ? $img_ativo : $img_inativo; ?>
                        </td>
                        <td class='tac'>
                            <?echo (pg_fetch_result($res,$i,'uso_interno_ativo') == 't') ? $img_ativo : $img_inativo; ?>
                        </td>
                    </tr>
                <?
                }
                ?>
                </tbody>
        </table>
    <?php
    }
            if ($count > 50) { ?>
                <script>
                $.dataTableLoad({
                    table : "#listagemProduto"
                });
                </script>
            <?php }?>
<br/>

    <div id='gerar_excel' class="btn_excel">
        <input type="hidden" id="jsonPOST" value='<?=json_encode(array("listarTudo" => "1"))?>' />
        <span><img src='imagens/excel.png' /></span>
        <span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
    </div>

<?php }

if (in_array($login_fabrica, array(169,170)) && !$listartudo && $listarprodutolf == 1) {

    $sqlPd = "
	SELECT
	    replace(tbl_produto.referencia, 'YY', '-') AS referencia,
	    tbl_produto.produto,
	    tbl_produto.descricao
        FROM tbl_produto
        JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica AND tbl_linha.ativo IS FALSE
        WHERE tbl_produto.familia IS NULL 
        AND tbl_linha.codigo_linha = 'INT'
        AND tbl_produto.fabrica_i = $login_fabrica;
    ";
    $resPd = pg_query($con,$sqlPd); ?>
    <div class="container">
<h4 class='titulo_coluna' style="font-size: 18px;padding-bottom: 10px;">Produtos sem Linha e Familia</h4>
    <table id="listagemProdutoSemLinha" style="margin: 0 auto; min-width: 100%" class="tabela_item table table-striped table-bordered table-hover table-large">
        <thead>
            <tr class='titulo_coluna'>
                <th class="tac">Referência</th>
                <th class="tal">Descrição</th>
                <th nowrap>Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php if (pg_num_rows($resPd) > 0) {?>
            <?php while ($rows = pg_fetch_array($resPd)) {?>
                    <tr>
                        <td class='tac' nowrap>
                            <a href='<?="$PHP_SELF?produto=" . $rows['produto'];?>'>
                                <?php echo $rows['referencia'];?>
                            </a>
                        </td>
                        <td align='left' nowrap>
                            <a href='<?="$PHP_SELF?produto=" . $rows['produto'];?>'>
                                <?php echo $rows['descricao'];?>
                            </a>
                        </td>
                        <td align='center' class="tac" nowrap>
                            <a href='<?="$PHP_SELF?produto=" . $rows['produto'];?>' class='btn btn-small btn-info'>
                                <i class="icon-edit icon-white"></i> Editar
                            </a> 

                        </td>
                    </tr>
            <?php }?>
        <?php } else {?>
            <tr><td colspan="100%" class="tac">nenhum registro encontrado.</td></tr>
        <?php }?>
        </tbody>
    </table>
</div>
<script>
$.dataTableLoad({
    table : "#listagemProdutoSemLinha"
});
</script>

<?php }

include "rodape.php"; ?>

