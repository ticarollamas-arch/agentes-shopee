# Arquitetura e limites desta entrega

```text
Navegador
  └── HTTPS / mesmo domínio
      ├── HTML/CSS/JS (public/)
      └── /api/* → PHP 8.4+
          └── PDO → MySQL/MariaDB
              ├── agentes
              ├── tarefas / fila
              ├── aprovações
              ├── atividades / mensagens
              └── log de importação

Cron cPanel → PHP CLI → worker → fila MySQL
```

Não existe dependência de Node, npm, Vite, Express ou porta 3000.

## Regras operacionais
- ZIP original é backup imutável.
- A loja pública e seu template ficam separados deste painel.
- Agentes não publicam, removem URLs, alteram canonical, robots, sitemap ou páginas indexadas sem aprovação.
- O importador futuro deve deduplicar pelo ID externo estável, persistir cursor/página, respeitar limites e poder retomar.
- Um registro de “produto importado” só é escrito após confirmação da gravação; retries não podem duplicar.
- API sempre responde JSON, inclusive erros; logs vão para error_log, não para o corpo HTTP.
- A interface atual usa polling. WebSocket não é necessário no cPanel para o MVP.
- O movimento dos avatares é representação visual. O estado vem do banco; nenhuma animação deve ser interpretada como prova de execução.
