DROP   FUNCTION so_numeros (TEXT);
CREATE FUNCTION so_numeros (TEXT) RETURNS TEXT AS '
DECLARE
	m_string	TEXT;
	m_retorno	TEXT;
	m_pos		INTEGER;
BEGIN
	m_string	:= $1;
	m_string	:= TRIM (m_string);
	m_retorno	:= '''';
	m_pos		:= 0;
	
	WHILE m_pos < LENGTH (m_string) LOOP
		m_pos := m_pos + 1;
		IF substr (m_string,m_pos,1) = ''0'' OR
			substr (m_string,m_pos,1) = ''1'' OR
			substr (m_string,m_pos,1) = ''2'' OR
			substr (m_string,m_pos,1) = ''3'' OR
			substr (m_string,m_pos,1) = ''4'' OR
			substr (m_string,m_pos,1) = ''5'' OR
			substr (m_string,m_pos,1) = ''6'' OR
			substr (m_string,m_pos,1) = ''7'' OR
			substr (m_string,m_pos,1) = ''8'' OR
			substr (m_string,m_pos,1) = ''9'' THEN
			
			m_retorno := m_retorno || substr (m_string,m_pos,1);
		END IF;
	END LOOP;

	RETURN m_retorno;
END;' LANGUAGE 'plpgsql';
