/*	Galería de funciones varias, sobre todo para que JavaScript sea algo más
	parecido al VisualBasic ('InStr(,n, str, char), p.e., en vez de un método
	de la variable string...)
*/

function InStr(n, s1, s2){
	// Devuelve la posición de la primera ocurrencia de s2 en s1
	// Si se especifica n, se empezará a comprobar desde esa posición
	// Sino se especifica, los dos parámetros serán las cadenas
	var numargs=InStr.arguments.length;	
	if(numargs<3)
		return n.indexOf(s1)+1;
	else
		return s1.indexOf(s2, n)+1;
}

function iif(condition, TrueVal, FalseVal) {
	//	Função iif (boolean condição, var valor1, var valor2)
	//	Devolve o valor 1 se a condição for 'true' e o valor2 se a condição for 'false'

	return (condition) ? TrueVal : FalseVal ;
}