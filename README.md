# plugins-wordpress

## Plugins presentes neste repositório

- triibo-api-services
- triibo-assinatura
- triibo-events
- triibo-fastshop
- triibo-payments
- triibo-usermeta

---

## triibo-api-services

Plugin WordPress que centraliza e disponibiliza os serviços de API da Triibo (Gateway, Node e PHP) em um único plugin. Ele serve como dependência para outros plugins da Triibo, facilitando a integração e a configuração das credenciais de acesso às APIs diretamente pelo painel administrativo do WordPress.
- Permite configurar tokens, usuários e chaves de acesso para ambientes de homologação e produção.
- Compatível com WooCommerce e HPOS.
- Não armazena credenciais no código-fonte, apenas via painel/admin.

---

## triibo-assinatura

Plugin WordPress para pagamentos recorrentes via WooCommerce, integrando o sistema de assinaturas da Triibo. Depende dos plugins `triibo-api-services`, `WooCommerce` e `WooCommerce Subscriptions` para funcionar corretamente.
- Permite gerenciar assinaturas e pagamentos recorrentes de forma automatizada.
- Integração completa com o painel do WooCommerce.
- Suporte a notificações administrativas e configuração de credenciais via painel.

---

## triibo-events

Plugin WordPress para registro e integração de eventos de compra que geram créditos e pontos Triibo. Utiliza o `triibo-api-services` para processar eventos como compras normais e assinaturas Triibo VIP, permitindo automação de benefícios e pontuação para clientes. Também depente do `WooCommerce` e `WooCommerce Subscriptions` para funcionar corretamente.
- Gera eventos de compra e assinatura vinculados ao sistema Triibo.
- Integração com APIs externas para processamento automático de pontos e créditos.

---

## triibo-fastshop

Plugin WordPress para integração entre WooCommerce e a plataforma Fast Shop, permitindo sincronização de pedidos, status e informações de clientes entre as duas plataformas. Depende dos plugins `triibo-api-services` e `WooCommerce` para funcionar corretamente.
- Automatiza o envio e atualização de pedidos do WooCommerce para o Fast Shop.
- Sincroniza status de entrega e informações relevantes de forma transparente.

---

## triibo-payments

Plugin WordPress para integração de pagamentos únicos com a plataforma Triibo, voltado para lojas WooCommerce. Permite processar cobranças avulsas, consultar status de transações e gerenciar pagamentos diretamente pelo painel do WordPress. Depende dos plugins `triibo-api-services` e `WooCommerce` para funcionar corretamente.
- Suporte a múltiplos métodos de pagamento e gateways Triibo.
- Integração transparente com WooCommerce.
- Configuração de credenciais e parâmetros via painel administrativo.

---

## triibo-usermeta

Plugin WordPress que adiciona todos os meta dados do usuário à resposta da API REST de usuários do WordPress e WooCommerce. Usado principalmente para integrações com sistemas externos (como o Node Triibo) durante o processo de checkout. Depende do plugin `WooCommerce` para funcionar corretamente.
- Expõe o meta_data do usuário autenticado na resposta da API, de forma segura e controlada.
- Permite restringir o acesso a determinados e-mails autorizados.
- Facilita integrações avançadas entre WordPress/WooCommerce e sistemas externos.
