# plugins-wordpress

## triibo-api-services

Plugin WordPress que centraliza e disponibiliza os serviços de API da Triibo (Gateway, Node e PHP) em um único plugin. Ele serve como dependência para outros plugins da Triibo, facilitando a integração e a configuração das credenciais de acesso às APIs diretamente pelo painel administrativo do WordPress.
- Permite configurar tokens, usuários e chaves de acesso para ambientes de homologação e produção.
- Compatível com WooCommerce e HPOS.
- Não armazena credenciais no código-fonte, apenas via painel/admin.

## triibo-assinatura

Plugin WordPress para pagamentos recorrentes via WooCommerce, integrando o sistema de assinaturas da Triibo. Depende dos plugins Triibo API Services e WooCommerce Subscriptions para funcionar corretamente.
- Permite gerenciar assinaturas e pagamentos recorrentes de forma automatizada.
- Integração completa com o painel do WooCommerce.
- Suporte a notificações administrativas e configuração de credenciais via painel.

## triibo-usermeta

Plugin WordPress que adiciona todos os meta dados do usuário à resposta da API REST de usuários do WordPress e WooCommerce. Usado principalmente para integrações com sistemas externos (como o Node Triibo) durante o processo de checkout.
- Expõe o meta_data do usuário autenticado na resposta da API, de forma segura e controlada.
- Permite restringir o acesso a determinados e-mails autorizados.
- Facilita integrações avançadas entre WordPress/WooCommerce e sistemas externos.
