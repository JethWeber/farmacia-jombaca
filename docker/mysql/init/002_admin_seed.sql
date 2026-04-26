USE farmacia_jombaca;

INSERT INTO usuarios (nome_completo, email, senha_hash, telefone, role, lgpd_consent)
VALUES (
  'Weber Admin',
  'weber@admin.com',
  '$2y$10$w2bVMAQqA/z2zQvecZPOP.2kV9Qi4Z6C/H.jGdmiNbgiYG/xKtElG',
  '900000000',
  'admin',
  1
)
ON DUPLICATE KEY UPDATE
  nome_completo = VALUES(nome_completo),
  email = VALUES(email),
  telefone = VALUES(telefone),
  senha_hash = VALUES(senha_hash),
  role = 'admin',
  lgpd_consent = 1;
