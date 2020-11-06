DROP   FUNCTION limpa_string (TEXT);
CREATE FUNCTION limpa_string (TEXT) RETURNS TEXT AS '
DECLARE
	m_string	TEXT;
	m_retorno	TEXT;
	m_pos		INTEGER;
	m_letra		CHAR (1);
BEGIN
	m_string := $1;
	m_string := trim (m_string);
	m_retorno := '''';
	m_pos  := 0;
	
	while m_pos < length (m_string) loop
		m_pos := m_pos + 1;
		m_letra := substr (m_string,m_pos,1);
		
		if strpos (''�����'' , m_letra) > 0 then
			m_letra := ''A'';
		end if;
		if strpos (''�����'' , m_letra) > 0 then
			m_letra := ''a'';
		end if;
		if strpos (''����'' , m_letra) > 0 then
			m_letra := ''E'';
		end if;
		if strpos (''����'' , m_letra) > 0 then
			m_letra := ''e'';
		end if;
		if strpos (''����'' , m_letra) > 0 then
			m_letra := ''I'';
		end if;
		if strpos (''����'' , m_letra) > 0 then
			m_letra := ''i'';
		end if;
		if strpos (''�����'' , m_letra) > 0 then
			m_letra := ''O'';
		end if;
		if strpos (''�����'' , m_letra) > 0 then
			m_letra := ''o'';
		end if;
		if strpos (''����'' , m_letra) > 0 then
			m_letra := ''U'';
		end if;
		if strpos (''����'' , m_letra) > 0 then
			m_letra := ''u'';
		end if;
		if strpos (''�'' , m_letra) > 0 then
			m_letra := ''C'';
		end if;
		if strpos (''�'' , m_letra) > 0 then
			m_letra := ''c'';
		end if;
		if strpos (''�'' , m_letra) > 0 then
			m_letra := ''n'';
		end if;
		if strpos (''�'' , m_letra) > 0 then
			m_letra := ''N'';
		end if;
		
		m_retorno := m_retorno || m_letra;
	end loop;
	
	return UPPER(m_retorno);
END;' LANGUAGE 'plpgsql';