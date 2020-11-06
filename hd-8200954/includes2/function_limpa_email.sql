DROP   FUNCTION limpa_email (TEXT);
CREATE FUNCTION limpa_email (TEXT) RETURNS TEXT AS '
DECLARE
	m_letra			text;
	m_string		text;
	m_string_verifica	text;
	m_string_final		text;
	m_string_conta		integer;
BEGIN
	m_string_conta		:= 1;
	m_string_final		:= '''';
	m_string		:= trim($1);
	
	WHILE m_string_conta <= LENGTH(m_string) LOOP
		m_string_verifica := substr(m_string,m_string_conta,1)::text;
		m_letra := m_string_verifica;
		
		IF m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' THEN
			m_letra := ''a'';
		END IF;
		
		IF m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' THEN
			m_letra := ''e'';
		END IF;
		
		IF m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' THEN
			m_letra := ''i'';
		END IF;
		
		IF m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' THEN
			m_letra := ''o'';
		END IF;
		
		IF m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' THEN
			m_letra := ''u'';
		END IF;
		
		IF m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' THEN
			m_letra := ''c'';
		END IF;
		
		IF m_string_verifica = ''�'' OR
			m_string_verifica = ''�'' THEN
			m_letra := ''n'';
		END IF;
		
		IF m_string_verifica = ''/'' THEN
			m_letra := '''';
		END IF;
		
		IF m_string_verifica = ''-'' THEN
			m_letra := '''';
		END IF;
		
		m_string_final := m_string_final || m_letra;
		m_string_conta := m_string_conta + 1;
	END LOOP;
	m_string_final := LOWER(m_string_final);
	
	RETURN m_string_final;
END;' LANGUAGE 'plpgsql';