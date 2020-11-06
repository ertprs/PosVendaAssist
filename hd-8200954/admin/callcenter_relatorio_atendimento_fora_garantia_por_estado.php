<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';
$layout_menu = "callcenter";
$title = "ATENDIMENTOS FORA DE GARANTIA";

include "cabecalho.php";
?>

<style type="text/css">

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<?php
if($_GET['estado']){
	$estado = $_GET['estado'];
	$data_inicial = $_GET['data_inicial'];
	$data_final = $_GET['data_final'];

	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
	}

	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
	}

		if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
	}
	if(strlen($msg_erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
	}

	if($login_fabrica == 74){
        $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
    }

	$sql = "SELECT tbl_hd_chamado.hd_chamado,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					tbl_cidade.nome as cidade,
					tbl_cidade.estado,
					tbl_hd_chamado_extra.nome AS cliente,
					tbl_hd_chamado_extra.endereco,
					tbl_hd_chamado_extra.numero,
					tbl_hd_chamado_extra.complemento,
					tbl_hd_chamado_extra.bairro,
					tbl_hd_chamado_extra.cep,
					tbl_hd_chamado_extra.fone,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade  = tbl_cidade.cidade
			JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i = 74
			WHERE tbl_hd_chamado.fabrica = $login_fabrica
			AND tbl_hd_chamado.data BETWEEN '$data_inicial' and '$data_final'
			and tbl_cidade.estado = '$estado'
			AND tbl_hd_chamado_extra.garantia IS NOT TRUE 
			$cond_admin_fale_conosco 
			ORDER BY tbl_cidade.nome";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
	?>
		<table align='center' class='tabela'>
			<caption class='titulo_tabela'>Atendimentos fora de garantia</caption>
			<tr class='titulo_coluna'>
				<th>Atendimento</th>
				<th>Data</th>
				<th>Cliente</th>
				<th>UF</th>
				<th>Cidade</th>
				<th>Endereço</th>
				<th>Bairro</th>
				<th>CEP</th>
				<th>Fone</th>
				<th>Produto</th>
			</tr>
	<?php
		for($i = 0; $i< pg_num_rows($res); $i++){
			$hd_chamado = pg_fetch_result($res,$i,'hd_chamado');
			$data = pg_fetch_result($res,$i,'data');
			$cliente = pg_fetch_result($res,$i,'cliente');
			$uf = pg_fetch_result($res,$i,'estado');
			$cidade = pg_fetch_result($res,$i,'cidade');
			$endereco = pg_fetch_result($res,$i,'endereco').", ".pg_fetch_result($res,$i,'numero');
			$complemento = pg_fetch_result($res,$i,'complemento');
			if(!empty($complemento)){
				$endereco .= " - ".$complemento;
			}
			$bairro = pg_fetch_result($res,$i,'bairro');
			$cep = pg_fetch_result($res,$i,'cep');
			$fone = pg_fetch_result($res,$i,'fone');
			$produto = pg_fetch_result($res,$i,'referencia')." - ".pg_fetch_result($res,$i,'descricao');

			$cor = ($i % 2 == 0)? '#F1F4FA'	:"#F7F5F0";
	?>
			<tr bgcolor='<?=$cor?>'>
				<td><a href='callcenter_interativo_new.php?callcenter=<?=$hd_chamado?>' target='_blank'><?=$hd_chamado?></a></td>
				<td><?=$data?></td>
				<td><?=$cliente?></td>
				<td><?=$uf?></td>
				<td><?=$cidade?></td>
				<td><?=$endereco?></td>
				<td><?=$bairro?></td>
				<td><?=$cep?></td>
				<td><?=$fone?></td>
				<td><?=$produto?></td>
			</tr>
	<?php
		}
	?>
		</table>
	<?php
	}
	
}

include "rodape.php";
