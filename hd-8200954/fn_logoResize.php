<?php
if (!function_exists('is_url')) {
	define('RE_URL',         '/^(?P<protocol>https?:\/\/)?(?P<server>[-\w]+\.[-\w\.]+)(:?\w\d+)?(?P<path>\/([-~\w\/_\.]+(\?\S+)?)?)*$/');
	function is_url($url=""){   // False se não bate...
		return (preg_match(RE_URL, $url));
	}
}

if( !function_exists("logoSetSize")){
	function logoSetSize($logoImg, $maxX=220, $maxY=64, $type='html') {
		/*********************************************************
		 * HD 746876 - Acertar no possível a medida das logos... *
		 * Atualizando medida em função do "aspecto" da imagem.  *
		 *********************************************************/
		if (is_readable($logoImg) or is_url($logoImg)) {
			list($logo_w, $logo_h) = getimagesize($logoImg);

			if ($type == 'css') {
				$ratio = ($logo_w >= $logo_h) ? (100 * $maxX) / $logo_w : (100 * $maxY) / $logo_h;
				$new_w = intval($logo_w * ($ratio / 100));
				$new_h = intval($logo_h * ($ratio / 100));

				return "height:{$new_h}px; width:{$new_w}px;";
			}

			$ratio = $logo_h / $logo_w; //Proporção da imagem, altura entre largura. 1 seria quadrada, > 1 seria mais alto que largo...

			$max_h = ($logo_h > $maxY*0.9) ? $maxY : $logo_h;
			$max_w = ($logo_w > $maxX*0.9) ? $maxX : $logo_w;

			return ($ratio >= 0.25) ? " height='$max_h'" : " width='$max_w'";
		}
	}
}
