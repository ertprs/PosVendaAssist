<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (strlen($_GET['fabrica']) > 0) $fabrica = $_GET['fabrica'];

$data = date("d/m/Y");

$sql = "SELECT      trim(tbl_fabrica.fabrica)                         AS fabrica   ,
					trim(tbl_produto.produto)                         AS produto   ,
					trim(tbl_produto.linha)                           AS linha     ,
					trim(tbl_produto.familia)                         AS familia   ,
					trim(tbl_produto.referencia)                      AS referencia,
					trim(replace(tbl_produto.descricao,'\"','\'\''))  AS descricao ,
					trim(tbl_produto.voltagem)                        AS voltagem  ,
					tbl_produto.garantia                                           ,
					tbl_produto.numero_serie_obrigatorio
		FROM        tbl_produto
		JOIN        tbl_linha    ON tbl_linha.linha     = tbl_produto.linha
		JOIN        tbl_fabrica  ON tbl_fabrica.fabrica = tbl_linha.fabrica
		LEFT JOIN   tbl_familia  ON tbl_familia.familia = tbl_produto.familia
		WHERE   tbl_produto.ativo IS TRUE ";
if (strlen($fabrica) > 0) $sql .= "AND tbl_linha.fabrica in ($fabrica) OR tbl_familia.fabrica in ($fabrica)";
$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_produto.referencia,20,0);";
$res = pg_exec($con,$sql);


// print o cabeçalho do xml
header("Content-type: application/xml");

// cabeçalho
echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>";
?>

<DATAPACKET Version="2.0">
	<METADATA>
		<FIELDS>
			<FIELD attrname="fabrica"                  fieldtype="integer"             />
			<FIELD attrname="produto"                  fieldtype="integer"             />
			<FIELD attrname="linha"                    fieldtype="integer"             />
			<FIELD attrname="familia"                  fieldtype="integer"             />
			<FIELD attrname="referencia"               fieldtype="string"   WIDTH="20" />
			<FIELD attrname="descricao"                fieldtype="string"   WIDTH="50" />
			<FIELD attrname="voltagem"                 fieldtype="string"   WIDTH="20" />
			<FIELD attrname="garantia"                 fieldtype="integer"             />
			<FIELD attrname="numero_serie_obrigatorio" fieldtype="boolean"             />
		</FIELDS>
	</METADATA>
	<ROWDATA>
		<?
		for ($i=0; $i < pg_numrows($res); $i++) {
			$fabrica    = trim(pg_result($res,$i,fabrica));
			$produto    = trim(pg_result($res,$i,produto));
			$linha      = trim(pg_result($res,$i,linha));
			$familia    = trim(pg_result($res,$i,familia));
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao  = trim(pg_result($res,$i,descricao));
			$voltagem   = trim(pg_result($res,$i,voltagem));
			$garantia   = trim(pg_result($res,$i,garantia));
			$serie_ob   = trim(pg_result($res,$i,numero_serie_obrigatorio));
			if (strlen($serie_ob) == 0) $serie_ob = "f";
		?>
			<ROW RowState="<?=$i+1;?>" fabrica="<?=$fabrica;?>" produto="<?=$produto;?>" linha="<?=$linha;?>" familia="<?=$familia;?>" referencia="<?=$referencia;?>" descricao="<?=$descricao;?>" voltagem="<?=$voltagem;?>" garantia="<?=$garantia;?>" numero_serie_obrigatorio="<?=$serie_ob;?>"/>
		<? } ?>
	</ROWDATA>
</DATAPACKET>