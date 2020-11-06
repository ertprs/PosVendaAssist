<?
 include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';  ?>
<html>
  <body>
    <? if($_GET["nf"]){
      $totalExtratos = 0;
      $sqlExtratosNaNF = " SELECT DISTINCT tbl_extrato.extrato, 
                                 total
                          FROM tbl_distrib_lote_os 
                          INNER JOIN tbl_distrib_lote ON tbl_distrib_lote.distrib_lote = tbl_distrib_lote_os.distrib_lote
                          INNER JOIN tbl_os ON tbl_os.os = tbl_distrib_lote_os.os AND
                                     tbl_os.fabrica = tbl_distrib_lote.fabrica
                          inner join tbl_os_extra on tbl_os_extra.os = tbl_distrib_lote_os.os 
                          inner join tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato  AND
                                                    tbl_extrato.fabrica = tbl_distrib_lote.fabrica
                          WHERE nota_fiscal_mo = '".$_GET["nf"]."' AND
                                tbl_distrib_lote_os.os = ".$_GET["os"];
	  
      $resExtratosNaNF = pg_query($con, $sqlExtratosNaNF); ?>

    <table width="300px" border='0' width='500' cellpadding='3' id="os_data">
      <thead>
        <tr bgcolor="#CCCCFF">
          <td colspan="10" align="center"> VALORES DO EXTRATO <?=$dataObject->extrato?></td>
        </tr>
        <tr bgcolor='#CCCCFF'>
          <td nowrap> EXTRATO </td>
          <td nowrap> VALOR</td>
        </tr>
      </thead>
      <tbody>
	<?          while($row = pg_fetch_assoc($resExtratosNaNF)){ 
          $totalExtratos += $row["total"];
	?>
        <tr>
          <td nowrap> <?=$row["extrato"]?></td>
          <td nowrap> <?=number_format($row["total"], 2, ",", "")?></td>
        </tr>

         <? } ?>
          </tbody>
          <tfoot>
             <tr bgcolor="#CCCCAA">
                <td nowrap> TOTAL</td>
                <td nowrap> <?=number_format($totalExtratos, 2, ",", "")?></td>
              </tr>
          </tfoot>
         </table>


<?} ?>
