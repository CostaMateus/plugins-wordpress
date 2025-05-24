# plugins-wordpress

## Plugins presentes neste repositório

- triibo-api-services
- triibo-assinatura
- triibo-auth
- triibo-events
- triibo-fastshop
- triibo-payments
- triibo-usermeta
- woo-packet
- woo-suprempay

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

## triibo-auth

Plugin WordPress para autenticação de usuários integrada ao ecossistema Triibo, com suporte a login via celular, validação de código SMS, criação e validação de contas marketplace. Permite customizar o fluxo de autenticação e registro, integrando com APIs externas e facilitando o onboarding de novos usuários. Depende dos plugins `triibo-api-services` e `WooCommerce` para funcionar corretamente.
- Login e cadastro simplificados via e-mail ou celular.
- Envio e validação de códigos por SMS.
- Integração com outros plugins Triibo para unificação de contas e experiência do usuário.

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

---

## woo-packet

Plugin WordPress que integra o WooCommerce com os Correios para cálculo e gestão de envios no Brasil. Permite oferecer opções de frete nacionais diretamente no checkout da loja, utilizando as tabelas e serviços dos Correios.
- Calcula automaticamente o valor do frete via Correios no WooCommerce.
- Suporte a diferentes modalidades de envio nacionais.
- Ideal para lojas virtuais que vendem e entregam produtos em todo o Brasil.

---

## woo-suprempay

Plugin WordPress que integra o método de pagamento SupremCash ao WooCommerce, permitindo que clientes realizem pagamentos utilizando a plataforma SupremPay diretamente na loja virtual.
- Adiciona SupremPay como opção de pagamento no checkout do WooCommerce.
- Estrutura compatível com versões recentes do WooCommerce e WordPress.
- Ideal para lojas que desejam oferecer SupremCash como alternativa de pagamento.
