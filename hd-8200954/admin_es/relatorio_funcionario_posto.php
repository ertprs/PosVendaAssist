<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$msg_erro = "";
$msg_debug = "";


// array_funcao
// Estão no include arrays_bosch
include '../admin/array_funcao.php';

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

# Pesquisar
if ($btn_acao == "pesquisar") {
  $funcao = $_POST['funcao'];
  $codigo = $_POST['codigo'];
  $nome   = $_POST['nome'];
  $posto = $_POST['posto'];

  if(strlen($codigo) > 0){
    $sqlPosto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo' AND fabrica = $login_fabrica";
    $resPosto = pg_query($con, $sqlPosto);
    if(pg_num_rows($resPosto) > 0){
      $posto = pg_fetch_result($resPosto, 0, 'posto');
    }
  }

  if ($funcao <> '' or $funcao <> null) {
    $cond_funcao = "AND tbl_tecnico.funcao = '$funcao'";
  }

  if($posto <> '' OR $posto <> null ){
    $cond_posto = "AND tbl_tecnico.posto = $posto";
  }

  $sql_func = "SELECT tbl_tecnico.tecnico,
            tbl_tecnico.posto,
            tbl_tecnico.fabrica,
            tbl_tecnico.nome AS nome_tecnico,
            tbl_tecnico.cpf,
            tbl_tecnico.rg,
            tbl_tecnico.cep,
            tbl_tecnico.estado,
            tbl_tecnico.cidade,
            tbl_tecnico.bairro,
            tbl_tecnico.endereco,
            tbl_tecnico.numero,
            tbl_tecnico.complemento,
            tbl_tecnico.observacao,
            tbl_tecnico.formacao,
            tbl_tecnico.anos_experiencia,
            tbl_tecnico.funcao,
            tbl_tecnico.telefone,
            tbl_tecnico.celular,
            tbl_tecnico.dados_complementares,
            tbl_tecnico.email,
            to_char(tbl_tecnico.data_nascimento, 'DD/MM/YYYY') AS data_nascimento,
            to_char(tbl_tecnico.data_admissao, 'DD/MM/YYYY') AS data_admissao
        FROM tbl_tecnico
        JOIN tbl_posto on tbl_posto.posto = tbl_tecnico.posto
        WHERE tbl_tecnico.fabrica = $login_fabrica
        AND tbl_tecnico.ativo = 't'
        AND tbl_posto.pais = '$login_pais'
        $cond_funcao
        $cond_posto";
  $res_func = pg_query($con,$sql_func);



  ## GERA EXCEL ##
  if(pg_num_rows($res_func) > 0){

    flush();
    echo `rm /tmp/assist/relatorio-de-personal-$login_fabrica.xls`;
    $fp = fopen ("/tmp/assist/relatorio-de-personal-$login_fabrica.html","w");

    fputs ($fp,"<table border='1' align='center' cellspacing='5px' cellpadding='2px' width='950'>
      <tr>
        <th colspan='8' style='color: #373B57; background-color: #F1C913;'>Relatório de Personal</th>
      </tr>
      <tr>
        <th style='color: #FFFFFF; background-color: #373B57;'>POSTO</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>NOMBRE</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>FECHA DE NASCIMENTO</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>FUNCCION</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>NUMERO DE IDENTIFICATION</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>FORMACIÓN ACADEMICA</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>AÑOS DE EXPERIÊNCIA</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>FECHA ADMISIÓN</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>CÓDIGO POSTAL</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>DIRECCIÓN</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>CUIDAD</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>PROVINCIA/DEPARTAMENTO</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>TELEFONO FIJO</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>TELEFONO MOVIL</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>WHATSAPP</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>CALZADO</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>CAMISETA</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>CORREO ELECTRÔNICO</th>
        <th style='color: #FFFFFF; background-color: #373B57;'>OBSERVACIÓN</th>
      </tr>");
    for ($x = 0; $x < pg_num_rows($res_func); $x++) {
      $tecnico              = pg_fetch_result($res_func, $x, 'tecnico');
      $nome_tecnico         = pg_fetch_result($res_func, $x, 'nome_tecnico');
      $cpf                  = pg_fetch_result($res_func, $x, 'cpf');
      $rg                   = pg_fetch_result($res_func, $x, 'rg');
      $cidade               = pg_fetch_result($res_func, $x, 'cidade');
      $bairro               = pg_fetch_result($res_func, $x, 'bairro');
      $endereco             = pg_fetch_result($res_func, $x, 'endereco');
      $numero               = pg_fetch_result($res_func, $x, 'numero');
      $complemento          = pg_fetch_result($res_func, $x, 'complemento');
      $observacao           = pg_fetch_result($res_func, $x, 'observacao');
      $formacao             = pg_fetch_result($res_func, $x, 'formacao');
      $anos_experiencia     = pg_fetch_result($res_func, $x, 'anos_experiencia');
      $funcao               = pg_fetch_result($res_func, $x, 'funcao');
      $telefone             = pg_fetch_result($res_func, $x, 'telefone');
      $celular              = pg_fetch_result($res_func, $x, 'celular');
      $dados_complementares = pg_fetch_result($res_func, $x, 'dados_complementares');
      $email                = pg_fetch_result($res_func, $x, 'email');
      $data_nascimento      = pg_fetch_result($res_func, $x, 'data_nascimento');
      $data_admissao        = pg_fetch_result($res_func, $x, 'data_admissao');

      $dados_complementares = json_decode($dados_complementares);


      $sql_p = "SELECT tbl_posto_fabrica.codigo_posto,
            tbl_posto.nome AS nome_posto
            FROM  tbl_tecnico JOIN tbl_posto_fabrica USING(posto)
                JOIN tbl_posto USING(posto)
            WHERE
              tbl_tecnico.tecnico = $tecnico
              AND tbl_posto_fabrica.fabrica = $login_fabrica";
      $res_p = pg_query($con,$sql_p);

      $cd_posto     = pg_fetch_result($res_p, 0, 'codigo_posto');
      $nome_posto     = pg_fetch_result($res_p, 0, 'nome_posto');


      foreach ($dados_complementares as $key => $value) {
        switch ($key) {
          case 'whatsapp':
            $whatsapp = $value;
            break;
          case 'cep':
            $cep = $value;
          case 'numero_calcado':
            $numero_calcado = $value;
          case 'numero_camiseta':
            $numero_camiseta = $value;
            break;
        }
      }

      switch ($funcao) {
        case 'T':
          $funcao = "Técnico";
          break;
        case 'A':
          $funcao = "Administrativo";
          break;
        case 'G':
          $funcao = "Gerente AT";
          break;
      }
      fputs ($fp,"<tr style='text-align: left;'>
          <td style='background-color: $cor;' nowrap>$cd_posto - $nome_posto</td>
          <td style='background-color: $cor;' nowrap>$nome_tecnico</td>
          <td style='background-color: $cor;' nowrap>$data_nascimento</td>
          <td style='background-color: $cor;' nowrap>$funcao</td>
          <td style='background-color: $cor;' nowrap>$rg</td>
          <td style='background-color: $cor;' nowrap>$formacao</td>
          <td style='background-color: $cor;' nowrap>$anos_experiencia</td>
          <td style='background-color: $cor;' nowrap>$data_admissao</td>
          <td style='background-color: $cor;' nowrap>$cep</td>
          <td style='background-color: $cor;' nowrap>$endereco</td>
          <td style='background-color: $cor;' nowrap>$cidade</td>
          <td style='background-color: $cor;' nowrap>$bairro</td>
          <td style='background-color: $cor;' nowrap>$telefone</td>
          <td style='background-color: $cor;' nowrap>$celular</td>
          <td style='background-color: $cor;' nowrap>$whatsapp</td>
          <td style='background-color: $cor;' nowrap>$numero_calcado</td>
          <td style='background-color: $cor;' nowrap>$numero_camiseta</td>
          <td style='background-color: $cor;' nowrap>$email</td>
          <td style='background-color: $cor;' nowrap>$observacao</td>
        </tr>");
    }
    $relatorio_total = pg_num_rows($res);
    fputs ($fp,"    <tr>
                        <th colspan='8' style='color: #373B57; background-color: #F1C913;'>Total de Funcionarios: $relatorio_total</th>
                    </tr>
                </table>");
    fclose ($fp);

    $data = date("Y-m-d").".".date("H-i-s");
  }

}
# FIm / Pesquisar

if (strlen($posto) > 0 and strlen ($msg_erro) == 0 ) {
  $sql = "SELECT  tbl_posto_fabrica.posto               ,
          tbl_posto_fabrica.credenciamento      ,
          tbl_posto_fabrica.codigo_posto        ,
          tbl_posto_fabrica.tipo_posto          ,
          tbl_posto_fabrica.transportadora_nome ,
          tbl_posto_fabrica.transportadora      ,
          tbl_posto_fabrica.cobranca_endereco   ,
          tbl_posto_fabrica.cobranca_numero     ,
          tbl_posto_fabrica.cobranca_complemento,
          tbl_posto_fabrica.cobranca_bairro     ,
          tbl_posto_fabrica.cobranca_cep        ,
          tbl_posto_fabrica.cobranca_cidade     ,
          tbl_posto_fabrica.cobranca_estado     ,
          tbl_posto_fabrica.obs                 ,
          tbl_posto_fabrica.banco               ,
          tbl_posto_fabrica.agencia             ,
          tbl_posto_fabrica.conta               ,
          tbl_posto_fabrica.nomebanco           ,
          tbl_posto_fabrica.favorecido_conta    ,
          tbl_posto_fabrica.cpf_conta           ,
          tbl_posto_fabrica.tipo_conta          ,
          tbl_posto_fabrica.obs_conta           ,
          tbl_posto.nome AS nome_posto          ,
          tbl_posto.cnpj                        ,
          tbl_posto.ie                          ,
          tbl_posto.endereco                    ,
          tbl_posto.numero                      ,
          tbl_posto.complemento                 ,
          tbl_posto.bairro                      ,
          tbl_posto.cep                         ,
          tbl_posto.cidade                      ,
          tbl_posto.estado                      ,
          tbl_posto.email                       ,
          tbl_posto.fone                        ,
          tbl_posto.fax                         ,
          tbl_posto.suframa                     ,
          tbl_posto.contato                     ,
          tbl_posto.capital_interior            ,
          tbl_posto.nome_fantasia               ,
          tbl_posto.pais                        ,
          tbl_posto_fabrica.item_aparencia      ,
          tbl_posto_fabrica.senha               ,
          tbl_posto_fabrica.desconto            ,
          tbl_posto_fabrica.desconto_acessorio  ,
          tbl_posto_fabrica.custo_administrativo,
          tbl_posto_fabrica.imposto_al          ,
          tbl_posto_fabrica.pedido_em_garantia  ,
          tbl_posto_fabrica.reembolso_peca_estoque,
          tbl_posto_fabrica.coleta_peca         ,
          tbl_posto_fabrica.pedido_faturado     ,
          tbl_posto_fabrica.digita_os           ,
          tbl_posto_fabrica.prestacao_servico   ,
          tbl_posto_fabrica.prestacao_servico_sem_mo ,
          tbl_posto.senha_financeiro            ,
          tbl_posto_fabrica.admin               ,
          to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao,
          tbl_posto_fabrica.pedido_via_distribuidor
      FROM  tbl_posto
      LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
      WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
      AND     tbl_posto_fabrica.posto   = $posto ";
  $res = pg_query($con,$sql);

  if (pg_num_rows($res) > 0) {
    $posto            = trim(pg_fetch_result($res, 0, 'posto'));
    $credenciamento   = trim(pg_fetch_result($res, 0, 'credenciamento'));
    $codigo           = trim(pg_fetch_result($res, 0, 'codigo_posto'));
    $nome             = trim(pg_fetch_result($res, 0, 'nome_posto'));
    $cnpj             = trim(pg_fetch_result($res, 0, 'cnpj'));
    if (strlen($cnpj) == 14) $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    if (strlen($cnpj) == 11) $cnpj = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cnpj);
    $ie               = trim(pg_fetch_result($res, 0, 'ie'));
    $endereco         = trim(pg_fetch_result($res, 0, 'endereco'));
    $endereco         = str_replace("\"","",$endereco);
    $numero           = trim(pg_fetch_result($res, 0, 'numero'));
    $complemento      = trim(pg_fetch_result($res, 0, 'complemento'));
    $bairro           = trim(pg_fetch_result($res, 0, 'bairro'));
    $cep              = trim(pg_fetch_result($res, 0, 'cep'));
    $cidade           = trim(pg_fetch_result($res, 0, 'cidade'));
    $estado           = trim(pg_fetch_result($res, 0, 'estado'));
    $email            = trim(pg_fetch_result($res, 0, 'email'));
    $fone             = trim(pg_fetch_result($res, 0, 'fone'));
    $fax              = trim(pg_fetch_result($res, 0, 'fax'));
    $contato          = trim(pg_fetch_result($res, 0, 'contato'));
    $suframa          = trim(pg_fetch_result($res, 0, 'suframa'));
    $item_aparencia   = trim(pg_fetch_result($res, 0, 'item_aparencia'));
    $obs              = trim(pg_fetch_result($res, 0, 'obs'));
    $capital_interior = trim(pg_fetch_result($res, 0, 'capital_interior'));
    $tipo_posto       = trim(pg_fetch_result($res, 0, 'tipo_posto'));
    $senha            = trim(pg_fetch_result($res, 0, 'senha'));
    $pais            = trim(pg_fetch_result($res, 0, 'pais'));
    $desconto         = trim(pg_fetch_result($res, 0, 'desconto'));
    $desconto_acessorio       = trim(pg_fetch_result($res, 0, 'desconto_acessorio'));
    $custo_administrativo     = trim(pg_fetch_result($res, 0, 'custo_administrativo'));
    $imposto_al               = trim(pg_fetch_result($res, 0, 'imposto_al'));
    $nome_fantasia            = trim(pg_fetch_result($res, 0, 'nome_fantasia'));
    $transportadora           = trim(pg_fetch_result($res, 0, 'transportadora'));

    $cobranca_endereco       = trim(pg_fetch_result($res, 0, 'cobranca_endereco'));
    $cobranca_numero         = trim(pg_fetch_result($res, 0, 'cobranca_numero'));
    $cobranca_complemento    = trim(pg_fetch_result($res, 0, 'cobranca_complemento'));
    $cobranca_bairro         = trim(pg_fetch_result($res, 0, 'cobranca_bairro'));
    $cobranca_cep            = trim(pg_fetch_result($res, 0, 'cobranca_cep'));
    $cobranca_cidade         = trim(pg_fetch_result($res, 0, 'cobranca_cidade'));
    $cobranca_estado         = trim(pg_fetch_result($res, 0, 'cobranca_estado'));
    $pedido_em_garantia      = trim(pg_fetch_result($res, 0, 'pedido_em_garantia'));
    $reembolso_peca_estoque  = trim(pg_fetch_result($res, 0, 'reembolso_peca_estoque'));
    $coleta_peca            = trim(pg_fetch_result($res, 0, 'coleta_peca'));
    $pedido_faturado         = trim(pg_fetch_result($res, 0, 'pedido_faturado'));
    $digita_os               = trim(pg_fetch_result($res, 0, 'digita_os'));
    $prestacao_servico       = trim(pg_fetch_result($res, 0, 'prestacao_servico'));
    $prestacao_servico_sem_mo= trim(pg_fetch_result($res, 0, 'prestacao_servico_sem_mo'));
    $banco                   = trim(pg_fetch_result($res, 0, 'banco'));
    $agencia                 = trim(pg_fetch_result($res, 0, 'agencia'));
    $conta                   = trim(pg_fetch_result($res, 0, 'conta'));
    $nomebanco               = trim(pg_fetch_result($res, 0, 'nomebanco'));
    $favorecido_conta        = trim(pg_fetch_result($res, 0, 'favorecido_conta'));
    $cpf_conta               = trim(pg_fetch_result($res, 0, 'cpf_conta'));
    $tipo_conta              = trim(pg_fetch_result($res, 0, 'tipo_conta'));
    $obs_conta               = trim(pg_fetch_result($res, 0, 'obs_conta'));
    $senha_financeiro        = trim(pg_fetch_result($res, 0, 'senha_financeiro'));
    $pedido_via_distribuidor = trim(pg_fetch_result($res, 0, 'pedido_via_distribuidor'));

    $admin          = trim(pg_fetch_result($res, 0, 'admin'));
    $data_alteracao = trim(pg_fetch_result($res, 0, 'data_alteracao'));

  }else{
    $sql = "SELECT  tbl_posto_fabrica.posto               ,
            tbl_posto_fabrica.credenciamento      ,
            tbl_posto_fabrica.codigo_posto        ,
            tbl_posto_fabrica.tipo_posto          ,
            tbl_posto_fabrica.transportadora_nome ,
            tbl_posto_fabrica.transportadora      ,
            tbl_posto_fabrica.cobranca_endereco   ,
            tbl_posto_fabrica.cobranca_numero     ,
            tbl_posto_fabrica.cobranca_complemento,
            tbl_posto_fabrica.cobranca_bairro     ,
            tbl_posto_fabrica.cobranca_cep        ,
            tbl_posto_fabrica.cobranca_cidade     ,
            tbl_posto_fabrica.cobranca_estado     ,
            tbl_posto_fabrica.obs                 ,
            tbl_posto_fabrica.digita_os           ,
            tbl_posto_fabrica.prestacao_servico   ,
            tbl_posto_fabrica.prestacao_servico_sem_mo ,
            tbl_posto_fabrica.banco               ,
            tbl_posto_fabrica.agencia             ,
            tbl_posto_fabrica.conta               ,
            tbl_posto_fabrica.nomebanco           ,
            tbl_posto_fabrica.favorecido_conta    ,
            tbl_posto_fabrica.cpf_conta           ,
            tbl_posto_fabrica.tipo_conta          ,
            tbl_posto_fabrica.obs_conta           ,
            tbl_posto.nome AS nome_posto          ,
            tbl_posto.cnpj                        ,
            tbl_posto.ie                          ,
            tbl_posto.endereco                    ,
            tbl_posto.numero                      ,
            tbl_posto.complemento                 ,
            tbl_posto.bairro                      ,
            tbl_posto.cep                         ,
            tbl_posto.cidade                      ,
            tbl_posto.estado                      ,
            tbl_posto.email                       ,
            tbl_posto.fone                        ,
            tbl_posto.fax                         ,
            tbl_posto.contato                     ,
            tbl_posto.suframa                     ,
            tbl_posto.pais                        ,
            tbl_posto.capital_interior            ,
            tbl_posto.nome_fantasia               ,
            tbl_posto_fabrica.item_aparencia      ,
            tbl_posto_fabrica.senha               ,
            tbl_posto_fabrica.desconto            ,
            tbl_posto_fabrica.desconto_acessorio  ,
            tbl_posto_fabrica.custo_administrativo,
            tbl_posto_fabrica.imposto_al          ,
            tbl_posto_fabrica.pedido_em_garantia  ,
            tbl_posto_fabrica.reembolso_peca_estoque,
            tbl_posto_fabrica.coleta_peca        ,
            tbl_posto_fabrica.pedido_faturado     ,
            tbl_posto_fabrica.digita_os           ,
            tbl_posto_fabrica.prestacao_servico   ,
            tbl_posto_fabrica.prestacao_servico_sem_mo   ,
            tbl_posto.senha_financeiro            ,
            tbl_posto_fabrica.admin               ,
            to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao,
            tbl_posto_fabrica.pedido_via_distribuidor
        FROM  tbl_posto
        LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
        WHERE   tbl_posto_fabrica.posto   = $posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
      $posto            = trim(pg_fetch_result($res, 0, 'posto'));
      //$codigo         = trim(pg_fetch_result($res, 0, 'codigo_posto'));
      $credenciamento   = trim(pg_fetch_result($res, 0, 'credenciamento'));
      $nome             = trim(pg_fetch_result($res, 0, 'nome_posto'));
      $cnpj             = trim(pg_fetch_result($res, 0, 'cnpj'));
      $ie               = trim(pg_fetch_result($res, 0, 'ie'));
      if (strlen($cnpj) == 14) $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
      if (strlen($cnpj) == 11) $cnpj = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cnpj);
      $endereco         = trim(pg_fetch_result($res, 0, 'endereco'));
      $endereco         = str_replace("\"","",$endereco);
      $numero           = trim(pg_fetch_result($res, 0, 'numero'));
      $complemento      = trim(pg_fetch_result($res, 0, 'complemento'));
      $bairro           = trim(pg_fetch_result($res, 0, 'bairro'));
      $cep              = trim(pg_fetch_result($res, 0, 'cep'));
      $cidade           = trim(pg_fetch_result($res, 0, 'cidade'));
      $estado           = trim(pg_fetch_result($res, 0, 'estado'));
      $email            = trim(pg_fetch_result($res, 0, 'email'));
      $fone             = trim(pg_fetch_result($res, 0, 'fone'));
      $fax              = trim(pg_fetch_result($res, 0, 'fax'));
      $contato          = trim(pg_fetch_result($res, 0, 'contato'));
      $suframa          = trim(pg_fetch_result($res, 0, 'suframa'));
      $item_aparencia   = trim(pg_fetch_result($res, 0, 'item_aparencia'));
      $obs              = trim(pg_fetch_result($res, 0, 'obs'));
      $capital_interior = trim(pg_fetch_result($res, 0, 'capital_interior'));
      $tipo_posto       = trim(pg_fetch_result($res, 0, 'tipo_posto'));
      //$senha            = trim(pg_fetch_result($res, 0, 'senha'));
      $desconto         = trim(pg_fetch_result($res, 0, 'desconto'));
      $desconto_acessorio = trim(pg_fetch_result($res, 0, 'desconto_acessorio'));
      $custo_administrativo = trim(pg_fetch_result($res, 0, 'custo_administrativo'));
      $imposto_al         = trim(pg_fetch_result($res, 0, 'imposto_al'));
      $nome_fantasia    = trim(pg_fetch_result($res, 0, 'nome_fantasia'));
      $transportadora   = trim(pg_fetch_result($res, 0, 'transportadora'));
      $pais             = trim(pg_fetch_result($res, 0, 'pais'));

      $cobranca_endereco    = trim(pg_fetch_result($res, 0, 'cobranca_endereco'));
      $cobranca_numero      = trim(pg_fetch_result($res, 0, 'cobranca_numero'));
      $cobranca_complemento = trim(pg_fetch_result($res, 0, 'cobranca_complemento'));
      $cobranca_bairro      = trim(pg_fetch_result($res, 0, 'cobranca_bairro'));
      $cobranca_cep         = trim(pg_fetch_result($res, 0, 'cobranca_cep'));
      $cobranca_cidade      = trim(pg_fetch_result($res, 0, 'cobranca_cidade'));
      $cobranca_estado      = trim(pg_fetch_result($res, 0, 'cobranca_estado'));
      $pedido_em_garantia   = trim(pg_fetch_result($res, 0, 'pedido_em_garantia'));
      $reembolso_peca_estoque = trim(pg_fetch_result($res, 0, 'reembolso_peca_estoque'));
      $coleta_peca         = trim(pg_fetch_result($res, 0, 'coleta_peca'));
      $pedido_faturado      = trim(pg_fetch_result($res, 0, 'pedido_faturado'));
      $digita_os            = trim(pg_fetch_result($res, 0, 'digita_os'));
      $prestacao_servico    = trim(pg_fetch_result($res, 0, 'prestacao_servico'));
      $prestacao_servico_sem_mo    = trim(pg_fetch_result($res, 0, 'prestacao_servico_sem_mo'));
      $banco                = trim(pg_fetch_result($res, 0, 'banco'));
      $agencia              = trim(pg_fetch_result($res, 0, 'agencia'));
      $conta                = trim(pg_fetch_result($res, 0, 'conta'));
      $nomebanco            = trim(pg_fetch_result($res, 0, 'nomebanco'));
      $favorecido_conta        = trim(pg_fetch_result($res, 0, 'favorecido_conta'));
      $cpf_conta               = trim(pg_fetch_result($res, 0, 'cpf_conta'));
      $tipo_conta              = trim(pg_fetch_result($res, 0, 'tipo_conta'));
      $obs_conta               = trim(pg_fetch_result($res, 0, 'obs_conta'));
      $senha_financeiro        = trim(pg_fetch_result($res, 0, 'senha_financeiro'));
      $pedido_via_distribuidor = trim(pg_fetch_result($res, 0, 'pedido_via_distribuidor'));

      $admin          = trim(pg_fetch_result($res, 0, 'admin'));
      $data_alteracao = trim(pg_fetch_result($res, 0, 'data_alteracao'));

    }else{
      $sql = "SELECT  tbl_posto.nome AS nome_posto  ,
              tbl_posto.cnpj                        ,
              tbl_posto.ie                          ,
              tbl_posto.endereco                    ,
              tbl_posto.numero                      ,
              tbl_posto.complemento                 ,
              tbl_posto.bairro                      ,
              tbl_posto.cep                         ,
              tbl_posto.cidade                      ,
              tbl_posto.estado                      ,
              tbl_posto.email                       ,
              tbl_posto.fone                        ,
              tbl_posto.fax                         ,
              tbl_posto.contato                     ,
              tbl_posto.suframa                     ,
              tbl_posto.capital_interior            ,
              tbl_posto.senha_financeiro            ,
              tbl_posto.pais                        ,
              tbl_posto.nome_fantasia
          FROM  tbl_posto
          WHERE   tbl_posto.posto   = $posto ";
      $res = pg_query($con,$sql);

      if (pg_num_rows($res) > 0) {
        $nome             = trim(pg_fetch_result($res, 0, 'nome_posto'));
        $cnpj             = trim(pg_fetch_result($res, 0, 'cnpj'));
        if (strlen($cnpj) == 14) $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        if (strlen($cnpj) == 11) $cnpj = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cnpj);
        $ie               = trim(pg_fetch_result($res, 0, 'ie'));
        $endereco         = trim(pg_fetch_result($res, 0, 'endereco'));
        $endereco         = str_replace("\"","",$endereco);
        $numero           = trim(pg_fetch_result($res, 0, 'numero'));
        $complemento      = trim(pg_fetch_result($res, 0, 'complemento'));
        $bairro           = trim(pg_fetch_result($res, 0, 'bairro'));
        $cep              = trim(pg_fetch_result($res, 0, 'cep'));
        $cidade           = trim(pg_fetch_result($res, 0, 'cidade'));
        $estado           = trim(pg_fetch_result($res, 0, 'estado'));
        $email            = trim(pg_fetch_result($res, 0, 'email'));
        $fone             = trim(pg_fetch_result($res, 0, 'fone'));
        $fax              = trim(pg_fetch_result($res, 0, 'fax'));
        $contato          = trim(pg_fetch_result($res, 0, 'contato'));
        $suframa          = trim(pg_fetch_result($res, 0, 'suframa'));
        $capital_interior = trim(pg_fetch_result($res, 0, 'capital_interior'));
        $senha_financeiro = trim(pg_fetch_result($res, 0, 'senha_financeiro'));
        $nome_fantasia    = trim(pg_fetch_result($res, 0, 'nome_fantasia'));
        $pais             = trim(pg_fetch_result($res, 0, 'pais'));
      }
    }
  }
}



$title       = "INFORME OFICIAL DE ESCRITORIO";
$layout_menu = "cadastro";
include 'cabecalho.php';

?>
<script src="../js/jquery-1.8.3.min.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script language="JavaScript">

$(document).ready(function() {
  Shadowbox.init();
});

function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {
  if (tipo == "nome" ) {
    var xcampo = campo;
  }

  if (tipo == "cnpj" ) {
    var xcampo = campo2;
  }

  if (tipo == "codigo" ) {
    var xcampo = campo3;
  }

  if (xcampo.value != "") {
    var url = "";
    url = "posto_pesquisa<?=$suffix?>.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
    janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");
    janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
    janela.nome = campo;
    janela.cnpj = campo2;
    janela.focus();
  }
}

function pesquisaTecnico(tecnico){
  Shadowbox.open({
    content:    "pesquisa_tecnico.php?tecnico="+tecnico,
    player: "iframe",
    width:  800,
    height: 500
  });
}
</script>

<style type="text/css">

.menu_top {
  text-align: center;
  font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
  font-size: 10px;
  font-weight: bold;
  border: 1px solid;
  color:#596d9b;
  background-color: #d9e2ef
}

.border {
  border: 1px solid #ced7e7;
}

.table_line {
  text-align: center;
  font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
  font-size: 10px;
  font-weight: normal;
  border: 0px solid;
  background-color: #ffffff
}

input {
  font-size: 10px;
}

.top_list {
  text-align: center;
  font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
  font-size: 10px;
  font-weight: bold;
  color:#596d9b;
  background-color: #d9e2ef
}

.line_list {
  text-align: left;
  font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
  font-size: x-small;
  font-weight: normal;
  color:#596d9b;
  background-color: #ffffff
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
</style>


<? if(strlen($msg_erro) > 0){ ?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
  <td class='error'>
    <? echo $msg_erro; ?>
  </td>
</tr>
</table>
<? } ?>
<p>

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
<!-- <input type="hidden" name="posto" value="<? echo $posto ?>">
 -->
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
  <tr>
    <td colspan="5"class="menu_top">
      <font color='#36425C'>Información de la estación Autorizado
    </td>
  </tr>
  <tr class="menu_top">
    <td colspan="2">CÓDIGO</td>
    <td colspan="5">RAZÓN SOCIAL</td>
  </tr>
  <tr class="table_line">
    <td colspan="2"><input type="text" name="codigo" size="14" maxlength="14" value="<? echo $codigo ?>" style="width:150px">&nbsp;<a href="#"><img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'codigo')"></a></td>
    <td colspan="3"><input type="text" name="nome" size="50" maxlength="60" value="<? echo $nome?>" style="width:300px" >&nbsp;<a href="#"><img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'nome')"></a></td>
  </tr>
  <tr class="menu_top">
    <td colspan="2">FUNCCIÓN</td>
    <td colspan="5"></td>
  </tr>
  <tr class="table_line">
    <td>
      <select name='funcao' size='1'>
        <option value=''>Selecione</option>
        <option value='A' <? if ($funcao == 'A') echo ' selected ' ?> >Administrativo</option>
        <option value='G' <? if ($capital_interior == 'G') echo ' selected ' ?> >Gerente AT</option>
       <option value='T' <? if ($capital_interior == 'T') echo ' selected ' ?> >Técnico</option>
      </select>
    </td>

</table>
<br>

<input type='hidden' name='btn_acao' value=''>
<button onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='pesquisar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Guardar formulário" border='0'>Buscar</button>
</center>
<br>
</form>
<br>
<?php
if (pg_num_rows($res_func) > 0) {
    echo "<table align='center' border='1' cellpadding='3' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
    for ($i = 0 ; $i < pg_num_rows($res_func) ; $i++) {
      if ($i % 20 == 0) {
        flush();
        echo "<tr class='Titulo'>";
        echo "<td nowrap>POSTO</td>";
        echo "<td nowrap>NOMBRE</td>";
        echo "<td nowrap>NUMERO DE IDENTIFICACION</td>";
        echo "<td nowrap>FECHA DE NASCIMENTO</td>";
        echo "<td nowrap>TELEFONO</td>";
        echo "<td nowrap>TELEFONO MOVIL</td>";
        echo "<td nowrap>WHATSAPP</td>";
        echo "<td nowrap>CALZADO</td>";
        echo "<td nowrap>CAMISETA</td>";
        echo "<td nowrap>EMAIL</td>";
        echo "<td nowrap>FUNCCIÓN</td>";
        echo "</tr>";
      }

      $tecnico              = pg_fetch_result($res_func, $i, 'tecnico');
      $posto                = pg_fetch_result($res_func, $i, 'posto');
      $nome_tecnico         = pg_fetch_result($res_func, $i, 'nome_tecnico');
      $rg                   = pg_fetch_result($res_func, $i, 'rg');
      $funcao               = pg_fetch_result($res_func, $i, 'funcao');
      $telefone             = pg_fetch_result($res_func, $i, 'telefone');
      $celular              = pg_fetch_result($res_func, $i, 'celular');
      $data_nascimento      = pg_fetch_result($res_func, $i, 'data_nascimento');
      $telefone             = pg_fetch_result($res_func, $i, 'telefone');
      $celular              = pg_fetch_result($res_func, $i, 'celular');
      $dados_complementares = pg_fetch_result($res_func, $i, 'dados_complementares');
      $email                = pg_fetch_result($res_func, $i, 'email');

      $dados_complementares = json_decode($dados_complementares);

      foreach ($dados_complementares as $key => $value) {
        switch ($key) {
          case 'whatsapp':
            $whatsapp = $value;
            break;
          case 'cep':
            $cep = $value;
          case 'numero_calcado':
            $numero_calcado = $value;
          case 'numero_camiseta':
            $numero_camiseta = $value;
            break;
        }
      }

      if(strlen($funcao) > 0){
        switch ($funcao) {
          case 'A':
            $funcao = "Administrativo";
            break;
          case 'G':
            $funcao = "Gerente AT";
            break;
          case 'T':
            $funcao = "Técnico";
            break;
        }
      }

      $cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
      $sql_p = "SELECT tbl_posto_fabrica.codigo_posto,
            tbl_posto.nome AS nome_posto
            FROM  tbl_tecnico JOIN tbl_posto_fabrica USING(posto)
                JOIN tbl_posto USING(posto)
            WHERE
              tbl_tecnico.tecnico = $tecnico
              AND tbl_posto_fabrica.fabrica = $login_fabrica";
      $res_p = pg_query($con,$sql_p);

      $cd_posto     = pg_fetch_result($res_p, 0, 'codigo_posto');
      $nome_posto     = pg_fetch_result($res_p, 0, 'nome_posto');

      echo "<tr class='Conteudo' bgcolor='$cor'>";
      echo "<td nowrap align='left'>".$cd_posto."-".$nome_posto."</td>";
      echo "<td nowrap align='left'><a href=\"javascript: pesquisaTecnico($tecnico)\">" . $nome_tecnico . "</a></td>";
      echo "<td nowrap align='center'>" . $rg . "</td>";
      echo "<td nowrap>" . $data_nascimento . "</td>";
      echo "<td nowrap>" . $telefone . "</td>";
      echo "<td nowrap>" . $celular . "</td>";
      echo "<td nowrap>" . $whatsapp . "</td>";
      echo "<td nowrap>" . $numero_calcado . "</td>";
      echo "<td nowrap>" . $numero_camiseta . "</td>";
      echo "<td nowrap>" . $email . "</td>";
      echo "<td nowrap>" . $funcao . "</td>";
      echo "</tr>";
    }
    echo "</table>";


  rename("/tmp/assist/relatorio-de-personal-$login_fabrica.html", "../admin/xls/relatorio-de-personal-$login_fabrica.$data.xls");
  echo "<br /> <a href='../admin/xls/relatorio-de-personal-$login_fabrica.$data.xls' target='_blank'><img src='../admin/imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;Descargar Excel</a> <br />";

}


?>


<p>
<? include "rodape.php"; ?>
