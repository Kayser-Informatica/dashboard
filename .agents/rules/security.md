# Regras de Segurança & Prevenção de Vazamento de Segredos

1. **Nunca commitar credenciais reais ou chaves:**
   - Nunca incluir valores de `APP_KEY`, senhas de banco de dados, chaves de API (`reg_sec_...`, `clt_live_...`, `ghp_...`, `AKIA...`) em arquivos rastreados pelo Git como `.env`, `docker-compose*.yml`, classes ou arquivos markdown.
   - Em arquivos `docker-compose*.yml`, `compose*.yaml`, utilize sempre variáveis dinâmicas de ambiente como `${APP_KEY}`, `${DB_PASSWORD}`, `${MYSQL_ROOT_PASSWORD}`.

2. **Arquivo `.env`:**
   - O arquivo `.env` nunca deve ser adicionado ao versionamento do Git.
   - Apenas `.env.example` deve ser commitado, contendo apenas placeholders vazios ou valores genéricos de exemplo.

3. **Validação Pré-Commit:**
   - Antes de realizar qualquer commit, garanta que os arquivos no stage foram inspecionados pelo hook `.githooks/pre-commit` e que nenhuma credencial confidencial está exposta.
