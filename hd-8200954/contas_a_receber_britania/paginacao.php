<center><?php
    $quant_pg = ceil($quantreg/$numreg);
    $quant_pg++;
    
    // Verifica se esta na primeira p�gina, se nao estiver ele libera o link para anterior
    if ( $pg > 0) { 
        echo "<a href=".$PHP_SELF."?pg=".($pg-1) ." class=pg><b>&laquo; anterior</b></a>";
    } else { 
        echo "<font color=#CCCCCC>&laquo; anterior</font>";
    }
    
    // Faz aparecer os numeros das p�gina entre o ANTERIOR e PROXIMO
    for($i_pg=1;$i_pg<$quant_pg;$i_pg++) { 
        // Verifica se a p�gina que o navegante esta e retira o link do n�mero para identificar visualmente
        if ($pg == ($i_pg-1)) { 
            echo "&nbsp;<span class=pgoff>[$i_pg]</span>&nbsp;";
        } else {
            $i_pg2 = $i_pg-1;
            echo "&nbsp;<a href='".$PHP_SELF."?pg=$i_pg2&tipo=$tipo&busca=$busca&texto=$texto' class=pg><b>$i_pg</b></a>&nbsp;";
        }
			if ($i_pg==30 or $i_pg==60 or $i_pg==90 or $i_pg==120 or $i_pg==150 or $i_pg==180) {
				echo "<br>";
			}
    }
    
    // Verifica se esta na ultima p�gina, se nao estiver ele libera o link para pr�xima
    if (($pg+2) < $quant_pg) { 
        echo "<a href='".$PHP_SELF."?pg=".($pg+1)."&tipo=$tipo&busca=$busca&texto=$texto' class=pg><b>pr�ximo &raquo;</b></a>";
    } else { 
        echo "<font color=#CCCCCC>pr�ximo &raquo;</font>";
    }
?>
</center>