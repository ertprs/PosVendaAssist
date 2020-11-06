DROP   FUNCTION fn_calculo_transacao (int4);
CREATE FUNCTION fn_calculo_transacao (int4) RETURNS float8 AS '

DECLARE
	aux          int4;
	acum         int4;
	
	t_faixa_1    int4;
	m_faixa_1    float;
	
	t_faixa_2    int4;
	m_faixa_2    float;
	
	t_faixa_3    int4;
	m_faixa_3    float;
	
	t_faixa_4    int4;
	m_faixa_4    float;
	
	t_faixa_5    int4;
	m_faixa_5    float;
	
	t_faixa_6    int4;
	m_faixa_6    float;
	
	t_faixa_7    int4;
	m_faixa_7    float;
	
	t_string     int4;
	t_resultado  float;
BEGIN
	aux         := 0;
	acum        := 0;
	
	t_faixa_1   := 100;
	m_faixa_1   := 2;
	
	t_faixa_2   := 200;
	m_faixa_2   := 1;
	
	t_faixa_3   := 500;
	m_faixa_3   := 0.75;
	
	t_faixa_4   := 1000;
	m_faixa_4   := 0.50;
	
	t_faixa_5   := 5000;
	m_faixa_5   := 0.25;
	
	t_faixa_6   := 10000;
	m_faixa_6   := 0.12;
	
	t_faixa_7   := 99999;
	m_faixa_7   := 0.07;
	
	t_string    := $1;
	t_resultado := 0;
	
	/* FAIXA DE 0 A 100 */
	while acum < t_string AND acum < t_faixa_1 loop
		aux  := aux + 1;
		acum := acum + 1;
	end loop;
	t_resultado := (t_resultado + (aux * m_faixa_1));
	raise notice ''     0 \t    100 \t => R$ %'', t_resultado;
	
	/* FAIXA DE 101 A 200 */
	if acum < t_string AND acum < t_faixa_2 then
		aux := 0;
		while acum < t_string AND acum < t_faixa_2 loop
			aux  := aux + 1;
			acum := acum + 1;
		end loop;
		t_resultado := (t_resultado + (aux * m_faixa_2));
		raise notice ''   101 \t    200 \t => R$ %'', t_resultado;
	end if;
	
	/* FAIXA 201 A 500 */
	if acum < t_string AND acum < t_faixa_3 then
		aux := 0;
		while acum < t_string AND acum < t_faixa_3 loop
			aux  := aux + 1;
			acum := acum + 1;
		end loop;
		t_resultado := (t_resultado + (aux * m_faixa_3));
		raise notice ''   201 \t    500 \t => R$ %'', t_resultado;
	end if;
	
	/* FAIXA 501 A 1000 */
	if acum < t_string AND acum < t_faixa_4 then
		aux := 0;
		while acum < t_string AND acum < t_faixa_4 loop
			aux  := aux + 1;
			acum := acum + 1;
		end loop;
		t_resultado := (t_resultado + (aux * m_faixa_4));
		raise notice ''   501 \t  1.000 \t => R$ %'', t_resultado;
	end if;
	
	/* FAIXA 1001 A 5.000 */
	if acum < t_string AND acum < t_faixa_5 then
		aux := 0;
		while acum < t_string AND acum < t_faixa_5 loop
			aux  := aux + 1;
			acum := acum + 1;
		end loop;
		t_resultado := (t_resultado + (aux * m_faixa_5));
		raise notice '' 1.001 \t  5.000 \t => R$ %'', t_resultado;
	end if;
	
	/* FAIXA 5.001 A 10.000 */
	if acum < t_string AND acum < t_faixa_6 then
		aux := 0;
		while acum < t_string AND acum < t_faixa_6 loop
			aux  := aux + 1;
			acum := acum + 1;
		end loop;
		t_resultado := (t_resultado + (aux * m_faixa_6));
		raise notice '' 5.001 \t 10.000 \t => R$ %'', t_resultado;
	end if;
	
	/* FAIXA 10.001 A 99.999 */
	if acum < t_string AND acum < t_faixa_7 then
		aux := 0;
		while acum < t_string AND acum < t_faixa_7 loop
			aux  := aux + 1;
			acum := acum + 1;
		end loop;
		t_resultado := (t_resultado + (aux * m_faixa_7));
		raise notice ''10.001 \t 99.999 \t => R$ %'', t_resultado;
	end if;
	
	RETURN t_resultado;
END;'
LANGUAGE 'plpgsql';