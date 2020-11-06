<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

function formatDate($date){
    if(strlen($date)>0){
        $date= new DateTime($date);
        return $date->format("d/m/Y");
    }else{
        return '-';
    }
}

function formatNumber($number){
    return number_format($number, 2, ",", "");


}
if($_POST["btn_pesquisar"] == "pesquisar"){
		$os = $_POST["os"];
		$os_int = preg_replace('/\D/','',$os);
    $sql = "SELECT
                     tbl_distrib_lote.distrib_lote,

                     tbl_distrib_lote_posto.nf_mobra,
                     tbl_os.mao_de_obra,
                     tbl_os.valores_adicionais,
                     tbl_os.qtde_km_calculada ,

                     tbl_extrato.total as total_extrato,
                     tbl_extrato_pagamento.data_pagamento as data_baixa,
                     tbl_extrato.extrato,

                     tbl_distrib_lote.fechamento,
                     tbl_distrib_lote.lote,
                     tbl_distrib_lote_posto.data_recebimento_lote,
                     tbl_distrib_lote_posto.identificador_objeto,

                     tbl_os.os,
                     tbl_os.consumidor_nome,
                     tbl_os.nota_fiscal,
                     tbl_os.data_nf,
                     tbl_os.data_abertura,
                     tbl_os.data_fechamento,
                     tbl_posto_fabrica.codigo_posto,
                     tbl_posto.nome as nome_posto,
                     tbl_fabrica.nome,

                     CASE WHEN tbl_status_os.status_os = 14 THEN 'Reprovada'
                          ELSE 'Aprovada'
                     END as aprovada_extrato,

                     tbl_os_status.observacao
                FROM tbl_distrib_lote_os
                JOIN tbl_distrib_lote ON tbl_distrib_lote.distrib_lote = tbl_distrib_lote_os.distrib_lote

                JOIN tbl_os_extra USING(os)
                JOIN tbl_os USING(os)

                LEFT JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_extra.status_os
                LEFT JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os

                JOIN tbl_distrib_lote_posto ON tbl_distrib_lote_posto.distrib_lote = tbl_distrib_lote.distrib_lote AND
                                               tbl_distrib_lote_posto.posto = tbl_os.posto AND tbl_distrib_lote_os.nota_fiscal_mo = tbl_distrib_lote_posto.nf_mobra
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_os.fabrica AND
                                          tbl_posto_fabrica.posto = tbl_os.posto
                JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica
                JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_os_extra.extrato
				WHERE (tbl_distrib_lote_os.os = $os_int or tbl_os.sua_os = '$os')
				AND   tbl_fabrica.parametros_adicionais ~* 'telecontrol_distrib'
                 ";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        $dataObject = pg_fetch_object($res,0);

        $sqlOsExtrato = "SELECT tbl_os.os,
                     tbl_os.consumidor_nome,
                     tbl_os.nota_fiscal,
                     tbl_os.data_nf,
                     tbl_os.data_abertura,
                     tbl_os.data_fechamento,
                     tbl_posto_fabrica.codigo_posto,
                     tbl_posto.nome as nome_posto,
                     tbl_fabrica.nome,

                      CASE WHEN tbl_status_os.status_os = 14 THEN 'Reprovada'
                          ELSE 'Aprovada'
                     END as aprovada_extrato,

                     tbl_os_status.observacao
         FROM tbl_extrato
         INNER JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
         INNER JOIN tbl_os ON tbl_os.os = tbl_os_extra.os

         LEFT JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_extra.status_os
         LEFT JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND
                                    tbl_os_status.status_os = tbl_os_extra.status_os

         JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = tbl_os.fabrica AND
                                   tbl_posto_fabrica.posto = tbl_os.posto
         JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
         JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_os.fabrica
         WHERE tbl_extrato.extrato = {$dataObject->extrato} AND
               tbl_os.os <> $dataObject->os ";

        $resOSExtrato = pg_query($con, $sqlOsExtrato);

    }
}
include 'menu.php'; ?>
<html>
	<head>
		<title>Relatório de OS em Lote</title>
        <style>
             form{ margin:0 auto;}
             table{margin:0 auto;}
             td{text-align:center;}
        </style>
	</head>
	<body>
        <form width="700px" action="<?=$PHP_SELF?>" method="POST" name="frm_relatorio">
             <br/>
            <label>OS</label>
            <input name="os" value="<?=$_POST['os']?>" />
            <button  name="btn_pesquisar" value="pesquisar"> Pesquisar </button>
     <br />
     <br />
    <table border='0' width='500px' cellpadding='3' id="inputs_table">
          <tr bgcolor='#CCCCFF'>
              <td align='center' colspan='7'>
                 INFORMAÇÕES DO EXTRATO
              </td>
          </tr>
          <tr bgcolor='#CCCCFF'>
              <td nowrap>
              	NF PRESTAÇÃO DE SERVIÇO
              </td>
              <td nowrap>
              	Valor Mão de Obra
              </td>
              <td nowrap>
              	VALORES ADICIONAIS
              </td>
              <td nowrap>
              	TOTAL KM
              </td>
              <td nowrap>
              	TOTAL MAO DE OBRA
              </td>
              <td nowrap>
                  VALOR TOTAL EXTRATO
              </td>
              <td nowrap>
              	DATA DE BAIXA
              </td>
          </tr>
          <tr>
              <td nowrap>
                <a onclick="window.open('mostra_extratos_nf.php?nf=<?=$dataObject->nf_mobra?>&distrib_lote=<?=$dataObject->distrib_lote?>&os=<?=$dataObject->os?>', '', 'width=300, height=600');"><?=$dataObject->nf_mobra?> </a>
              </td>
              <td nowrap>
                  <?=formatNumber($dataObject->mao_de_obra)?>
              </td>
              <td nowrap>
                 <?=formatNumber($dataObject->valores_adicionais)?>

              </td>
              <td nowrap>
                <?=empty($dataObject->qtde_km_calculada) ? "0" : $dataObject->qtde_km_calculada ?>

              </td>
              <td nowrap>
                <?=formatNumber($dataObject->mao_de_obra + $dataObject->valores_adicionais + $dataObject->qtde_km_calculada)?>
              </td>
              <td nowrap>
                   <?=formatNumber($dataObject->total_extrato)?>
              </td>
              <td nowrap>
                  <?=formatDate($dataObject->data_baixa) ?>
              </td>
          </tr>
          <tr bgcolor='#CCCCFF'>

              <td nowrap>
              	EXTRATO
              </td>
              <td nowrap>
              	DATA FECHAMENTO LOTE
              </td>
              <td nowrap>
              	NÚMERO LOTE
              </td>
              <td nowrap>
              	DATA RECEBIMENTO
              </td>
              <td nowrap>
                  CODIGO DE RASTREIO
              </td>
         </tr>
         <tr>
              <td nowrap>
              	  <?=$dataObject->extrato?>
              </td>
              <td nowrap>
                 <?=formatDate($dataObject->fechamento) ?>
              </td>
              <td nowrap>
                    <?=$dataObject->lote?>
              </td>
              <td nowrap>
                  <?=formatDate($dataObject->data_recebimento_lote) ?>
              </td>
              <td nowrap>
                   <?=$dataObject->identificador_objeto ? $dataObject->identificador_objeto : "-";?>
              </td>
          </tr>
       </table>

    <br />
       <table width="700px" border='0' width='500' cellpadding='3' id="os_data">
          <thead>
              <tr bgcolor="#CCCCFF">
                 <td colspan="10" align="center"> INFORMAÇÕES DA OS - OS(s) do Extrato <?=$dataObject->extrato?></td>
              </tr>
              <tr bgcolor='#CCCCFF'>
                  <td nowrap> OS </td>
                  <td nowrap> CONSUMIDOR</td>
                  <td nowrap> NOTA FISCAL</td>
                  <td nowrap> DATA NF</td>
                  <td nowrap> ABERTURA </td>
                  <td nowrap> FECHAMENTO</td>
                  <td nowrap> POSTO</td>
                  <td nowrap> FABRICANTE</td>
                  <td nowrap> APROV./REPROV.</td>
                  <td nowrap> MOTIVO</td>
              </tr>
          </thead>
          <tbody>
             <tr bgcolor="#CCCCA0">
                 <td nowrap> <?=$dataObject->os?></td>
                 <td nowrap> <?=$dataObject->consumidor_nome?></td>
                 <td nowrap> <?=$dataObject->nota_fiscal?></td>
                 <td nowrap> <?=formatDate($dataObject->data_nf) ?></td>
                 <td nowrap> <?=formatDate($dataObject->data_abertura) ?></td>
                 <td nowrap> <?=formatDate($dataObject->data_fechamento) ?></td>
                 <td nowrap> <?=$dataObject->codigo_posto . " - " . $dataObject->nome_posto?></td>
                 <td nowrap> <?=$dataObject->nome?></td>
                 <td nowrap> <?=$dataObject->aprovada_extrato?></td>
                 <td nowrap> <?=($dataObject->aprovada_extrato == "Reprovada") ? $dataObject->observacao : "-"?></td>
             </tr>
<?          while($row = pg_fetch_assoc($resOSExtrato)){ ?>
                   <tr>
                      <td nowrap> <?=$row["os"]?></td>
                      <td nowrap> <?=$row["consumidor_nome"]?></td>
                      <td nowrap> <?=$row["nota_fiscal"]?></td>
                      <td nowrap> <?=formatDate($row["data_nf"]) ;?></td>
                      <td nowrap> <?=formatDate($row["data_abertura"]) ?></td>
                      <td nowrap> <?=formatDate($row["data_fechamento"]) ?></td>
                      <td nowrap> <?=$row["codigo_posto"] . " - " . $row["nome_posto"]?></td>
                      <td nowrap> <?=$row["nome"]?></td>
                      <td nowrap> <?=$row["aprovada_extrato"]?></td>
                      <td nowrap> <?=($row["aprovada_extrato"] == "Reprovada") ? $row["observacao"] : "-"?></td>
                  </tr>

         <? } ?>
          </tbody>
         </table>
        </form>

	</body>
</html>
