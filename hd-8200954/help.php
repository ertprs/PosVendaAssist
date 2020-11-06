<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$local = $PHP_SELF;

		$sql = "SELECT * from tbl_help";
		$res = pg_exec ($con,$sql);

		if (@pg_numrows($res) >= 0) {
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$programa           = pg_result($res,$i,programa);
				$help           = pg_result($res,$i,help);
			
				$pos = strpos($local, $programa);
				if ($pos == true) {
				echo"$programa<BR>";
		//		echo"$help";
				}
		
			}
			echo"entrou";
		

		}

?>