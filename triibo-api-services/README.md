# Triibo API Services

### Integração com serviços de API da Triibo:
- CloudFunctions (gateway)
- Node
- PHP

<hr>
### Exemplo de código para adicionar submenu ao menu 'Triibo Services'

```php
add_action( "admin_menu", [ $this, "add_submenu_page" ], 11 );
public function add_submenu_page()
{
    add_submenu_page(
        Triibo_Api_Services::get_name(),
        "Triibo Assinaturas",
        "Triibo Assinaturas",
        "manage_options",
        "triibo_assinaturas-settings",
        [ $this, "display_admin_menu_settings" ]
    );
}
```

<hr>
<h4>Exemplo para adicionar link para configurações nesta página</h4>

```php
add_action( "triibo_api_service_add_button", [ $this, "add_btn" ], 11 );
public function add_btn()
{
    $url  = esc_url( admin_url( "admin.php?page=wc-settings&tab=checkout&section=triibo_assinaturas" ) );
    $text = __( "Configurações", "triibo_assinaturas" );
    $link = "&lt;a href='{$url}' &gt;{$text}&lt;/a&gt;";
    echo "&lt;li&gt;&lt;p&gt;Triibo Assinaturas | {$link}&lt;/p&gt;&lt;/li&gt;";
}
```