<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
function pecho ($str) {echo "<p>$str</p>\n";}
function checaCPF ($cpf,$return_str = true) {
	global $con;	// Para conectar com o banco...
	$cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
	if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

	$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
	if ($res_cpf === false) {
		return ($return_str) ? pg_last_error($con) : false;
	}
	return $cpf;
}

$num = "03519207000165";
pecho ("Original: $num");
echo (is_numeric($num)) ? "numérico":"não numérico";
echo "<br>";
$codigo = highlight_string('$num = strtr($num,array("."=>"",","=>"."));
$n_num = floatval($num);
$f_num = (float) $num;
$e_num = number_format($num, 2, ",", ".");
$x_num = str_replace(",",".",$num);
$p_num = preg_replace("/\D/", "", $num);
', true);
$num = strtr($num,array("."=>"",","=>"."));
$n_num = floatval($num);
$f_num = (float) $num;
$e_num = number_format($num, 2, ",", ".");
$x_num = str_replace(",",".",$num);
$p_num = preg_replace("/\D/", "", $num);
pecho ("strtr: $num");
pecho ("FloatVal(): ".$n_num);
pecho ("(float) ".$f_num);
pecho ("formatNum: ".$e_num);
pecho ("replace: $x_num");
pecho ("preg: $p_num");
pecho ("CPF OK: ".checaCPF($num));
?>
<p>O programa:</p>
<p>
<?=$codigo?>
</p>