<?php
$isFabrica = function($arr) use ($login_fabrica) {
    if (!is_array($arr))
        $arr = explode(',', $arr);
    return in_array($login_fabrica, $arr);
};

$comunicado_options = '';

if ($isFabrica('42')) {
    $comunicado_options = include('admin/menus/comunicado_option_array.php');
    $comunicado_options['Altera��es T�cnicas'] = traduz('Altera��es.T�cnicas',$con,$cook_idioma);
    $comunicado_options['Esquema El�trico'] = traduz('Esquema.El�trico',$con,$cook_idioma);
    $comunicado_options['Manual T�cnico'] = traduz('Manual.T�cnico',$con,$cook_idioma);
    $comunicado_options['Vista Explodida'] = traduz('Vista.Explodida',$con,$cook_idioma);
}

if($isFabrica('117')){
	$comunicado_options = include('admin/menus/comunicado_option_array.php');
	unset($comunicado_options['Apresenta��o do Produto']);
	unset($comunicado_options['Com. Unico Posto']);
	unset($comunicado_options['Estrutura do Produto']);
	unset($comunicado_options['Foto']);
	unset($comunicado_options['Manual']);
	unset($comunicado_options['Orienta��o de Servi�o']);
	unset($comunicado_options['Procedimentos']);
	unset($comunicado_options['Promocao']);
	unset($comunicado_options['Comunicado']);
}

ksort($comunicado_options);
return $comunicado_options;
