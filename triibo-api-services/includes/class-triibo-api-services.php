<?php

/**
 * The file that defines the core plugin class
 * A class definition that includes attributes and functions used in the admin area.
 *
 * @author 	Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 2.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

/**
 * The core plugin class.
 *
 * This is used to define admin-specific hooks.
 * Also maintains the unique identifier of this plugin as well as the current version of the plugin.
 */
class Triibo_Api_Services
{
	/**
	 * Plugin domain.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected static string $name = "triibo_api_services";

	/**
	 * Plugin loader.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Api_Services_Loader
	 */
	protected Triibo_Api_Services_Loader $loader;

	/**
	 * Plugin domain.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $version;

	/**
	 * Prefix of plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $prefix;

	/**
	 * Sufix of plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $sufix;

	/**
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $page;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->plugin_name = self::$name;
		$this->version     = defined( constant_name: "TRIIBO_API_SERVICES_VERSION" ) ? TRIIBO_API_SERVICES_VERSION : "1.0.0";

		require_once( plugin_dir_path( file: dirname( path: __FILE__ ) ) . "includes/class-triibo-api-services-loader.php" );

		$this->loader      = new Triibo_Api_Services_Loader();
		$this->loader->add_action( hook: "admin_enqueue_scripts", component: $this, callback: "enqueue_styles"  );
		$this->loader->add_action( hook: "admin_enqueue_scripts", component: $this, callback: "enqueue_scripts" );

		$this->add_actions();
		$this->add_filters();

		require_once( plugin_dir_path( file: dirname( path: __FILE__ ) ) . "includes/apis/class-triibo-api.php"         );
		require_once( plugin_dir_path( file: dirname( path: __FILE__ ) ) . "includes/apis/class-triibo-api-gateway.php" );
		require_once( plugin_dir_path( file: dirname( path: __FILE__ ) ) . "includes/apis/class-triibo-api-node.php"    );
		require_once( plugin_dir_path( file: dirname( path: __FILE__ ) ) . "includes/apis/class-triibo-api-php.php"     );
	}

	/**
	 * Return the plugin name.
	 *
	 * @since 2.0.0 	Refactored
	 * @since 1.0.0
	 *
	 * @param bool 	$config 	If true, return the slug for config page
	 *
	 * @return string
	 */
	public static function get_name( bool $config = false ) : string
	{
		if ( ! $config )
			return self::$name;

		return self::$name . "-settings";
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_styles() : void
	{
		wp_enqueue_style(
			handle: $this->plugin_name,
			src   : plugin_dir_url( file: dirname( path: __FILE__ ) ) . "assets/css/admin.css",
			deps  : [],
			ver   : $this->version,
			media : "all"
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() : void
	{
		wp_enqueue_script(
			handle: $this->plugin_name,
			src   : plugin_dir_url( file: dirname( path: __FILE__ ) ) . "assets/js/admin.js",
			deps  : [ "jquery" ],
			ver   : $this->version,
			args  : false
		);
	}

	/**
	 * Actions.
	 *
	 * @since 1.13.0 	Add action 'woocommerce_new_product'
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_actions() : void
	{
		add_action( hook_name: "admin_menu",              callback: [ $this, "add_admin_menu"            ], priority: 9                    );
		add_action( hook_name: "admin_init",              callback: [ $this, "register_and_build_fields" ]                                 );
		add_action( hook_name: "woocommerce_new_product", callback: [ $this, "attach_product_images"     ], priority: 10, accepted_args: 2 );
	}

	/**
	 * Filters.
	 *
	 * @since 1.12.0 	Add filter 'is_protected_meta'
	 * @since 1.2.0 	Add filter 'plugin_row_meta'
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_filters() : void
	{
		$basename = plugin_basename( file: TRIIBO_API_SERVICES_FILE );

		add_filter( hook_name: "plugin_action_links_{$basename}", callback: [ $this, "plugin_action_links"    ]                                 );
		add_filter( hook_name: "plugin_row_meta",                 callback: [ $this, "plugin_row_meta"        ], priority: 10, accepted_args: 3 );
		add_filter( hook_name: "is_protected_meta",               callback: [ $this, "triibo_protected_metas" ], priority: 10, accepted_args: 3 );
	}

	/**
	 * Add submenu on WP Settings menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_admin_menu() : void
	{
		add_menu_page(
			page_title: "Triibo Services",
			menu_title: "Triibo Services",
			capability: "manage_options",
			menu_slug : $this->plugin_name,
			callback  : [ $this, "display_admin_menu" ],
			icon_url  : plugins_url( path: "assets/images/triibo20.png", plugin: TRIIBO_API_SERVICES_FILE ),
			position  : 65
		);

		add_submenu_page(
			parent_slug: $this->plugin_name, // "options-general.php",
			page_title : "Triibo API Services",
			menu_title : "Configurações APIs",
			capability : "manage_options",
			menu_slug  : "{$this->plugin_name}-settings",
			callback   : [ $this, "display_admin_menu_settings" ]
		);
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function display_admin_menu() : void
	{
		if ( !current_user_can( capability: "manage_options" ) )
			wp_die( message: __( text: "Você não tem permissões suficientes para acessar esta página." ) );

		$active_tab = isset( $_GET[ "tab" ] ) ? $_GET[ "tab" ] : "general";

		if ( isset( $_GET[ "error_message" ] ) )
		{
			add_action( hook_name: "admin_notices", callback: [ $this, "triibo_api_services_settings_messages" ] );
			do_action( hook_name: "admin_notices", arg: $_GET[ "error_message" ] );
		}

		require_once ( plugin_dir_path( file: dirname( path: __FILE__ ) ) . "templates/{$this->plugin_name}-admin-display.php" );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function display_admin_menu_settings() : void
	{
		if ( !current_user_can( capability: "manage_options" ) )
			wp_die( message: __( text: "Você não tem permissões suficientes para acessar esta página." ) );

		$active_tab = isset( $_GET[ "tab" ] ) ? $_GET[ "tab" ] : "general";

		if ( isset( $_GET[ "error_message" ] ) )
		{
			add_action( hook_name: "admin_notices", callback: [ $this, "triibo_api_services_settings_messages" ] );
			do_action( hook_name: "admin_notices", arg: $_GET[ "error_message" ] );
		}

		require_once ( plugin_dir_path( file: dirname( path: __FILE__ ) ) . "templates/" . $this->plugin_name . "-admin-settings-display.php" );
	}

	/**
	 * Handles errors and displays messages accordingly.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$error_message
	 *
	 * @return void
	 */
	public function triibo_api_services_settings_messages( string $error_message ) : void
	{
		switch ( $error_message )
		{
			case "1":
				$message       = __( text: "There was an error adding this setting. Please try again. If this persists, shoot us an email.", domain: $this->plugin_name );
				$err_code      = esc_attr( text: "triibo_api_services-example-setting" );
				$setting_field = "triibo_api_services_example_setting";
			break;
		}

		add_settings_error( setting: $setting_field, code: $err_code, message: $message );
	}

	/**
	 * Register and build the plugin fields
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_and_build_fields() : void
	{
		$this->prefix = "triibo_api_services_";
		$this->sufix  = "_section";

		$this->page   = "{$this->prefix}general_settings";

		$gate_fields  = [
			[
				"key"   => "gate_status",
				"title" => "Ativar / Desativar",
				"label" => "Ativar API Gateway",
			],[
				"key"   => "gate_env",
				"title" => "Homologação",
				"label" => "Habilitar API de homologação",
				"value" => true,
			],[
				"key"   => "gate_hml_tkn",
				"title" => "Token (homol)",
			],[
				"key"   => "gate_hml_chn_id",
				"title" => "Channel ID (homol)",
			],[
				"key"   => "gate_hml_chn_tkn",
				"title" => "Channel Token (homol)",
			],[
				"key"   => "gate_hml_sys_uid",
				"title" => "System UID (homol)",
			],[
				"key"   => "gate_hml_optin_id",
				"title" => "OptIn ID (homol)",
			],[
				"key"   => "gate_hml_optin_txt",
				"title" => "OptIn Texto (homol)",
			],[
				"key"   => "gate_prd_tkn",
				"title" => "Token",
			],[
				"key"   => "gate_prd_chn_id",
				"title" => "Channel ID",
			],[
				"key"   => "gate_prd_chn_tkn",
				"title" => "Channel Token",
			],[
				"key"   => "gate_prd_sys_uid",
				"title" => "System UID",
			],[
				"key"   => "gate_prd_optin_id",
				"title" => "OptIn ID",
			],[
				"key"   => "gate_prd_optin_txt",
				"title" => "OptIn Texto",
			],
		];

		$node_fields  = [
			[
				"key"   => "node_status",
				"title" => "Ativar / Desativar",
				"label" => "Ativar API Node",
			],[
				"key"   => "node_env",
				"title" => "Homologação",
				"label" => "Habilitar API de homologação",
				"value" => true,
			],[
				"key"   => "node_hml_user",
				"title" => "Usuário (homol)",
			],[
				"key"   => "node_hml_pass",
				"title" => "Senha (homol)",
			],[
				"key"   => "node_hml_auth_key",
				"title" => "Auth Key (homol)",
			],[
				"key"   => "node_prd_user",
				"title" => "Usuário",
			],[
				"key"   => "node_prd_pass",
				"title" => "Senha",
			],[
				"key"   => "node_prd_auth_key",
				"title" => "Auth Key",
			],
		];

		$php_fields   = [
			[
				"key"   => "php_status",
				"title" => "Ativar / Desativar",
				"label" => "Ativar API PHP",
			],[
				"key"   => "php_env",
				"title" => "Homologação",
				"label" => "Habilitar API de homologação",
				"value" => true,
			],[
				"key"   => "php_hml_key",
				"title" => "Key (homol)",
			],[
				"key"   => "php_hml_tkn",
				"title" => "Token (homol)",
			],[
				"key"   => "php_prd_key",
				"title" => "key",
			],[
				"key"   => "php_prd_tkn",
				"title" => "Token",
			],
		];

		register_setting( option_group: $this->page, option_name: "{$this->prefix}options" );

		$this->add_allowed_options( page: $this->page, data: [ $gate_fields, $node_fields, $php_fields ] );

		add_settings_section(
			id      : $this->prefix . "gateway" . $this->sufix,
			title   : "API Gateway",
			callback: [ $this, "message_section" ],
			page    : $this->page
		);

		add_settings_section(
			id      : $this->prefix . "node"    . $this->sufix,
			title   : "API Node",
			callback: [ $this, "message_section" ],
			page    : $this->page
		);

		add_settings_section(
			id      : $this->prefix . "php"     . $this->sufix,
			title   : "API PHP",
			callback: [ $this, "message_section" ],
			page    : $this->page
		);

		foreach ( $gate_fields as $index => $field )
		{
			$this->add_settings_field( section: "gateway", index: $index, field: $field );
			$this->register_settings( field: $field );
		}

		foreach ( $node_fields as $index => $field )
		{
			$this->add_settings_field( section: "node", index: $index, field: $field );
			$this->register_settings( field: $field );
		}

		foreach ( $php_fields  as $index => $field )
		{
			$this->add_settings_field( section: "php", index: $index, field: $field );
			$this->register_settings( field: $field );
		}
	}

	/**
	 * Adds plugin fields to allowed options.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$page
	 * @param array 	$data
	 *
	 * @return void
	 */
	public function add_allowed_options( string $page, array $data ) : void
	{
		$new_options = [ $page => [] ];

		foreach ( $data as $api )
			foreach ( $api as $field )
				$new_options[ $page ][] = $this->prefix . $field[ "key" ];

		add_allowed_options( new_options: $new_options );
	}

	/**
	 * Configure each plugin field.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$section
	 * @param string 	$index
	 * @param array 	$field
	 *
	 * @return void
	 */
	public function add_settings_field( string $section, string $index, array $field ) : void
	{
		$id      = $this->prefix . $field[ "key" ];
		$title   = $field[ "title" ];
		$section = $this->prefix . $section . $this->sufix;

		add_settings_field(
            id      : $id,
			title   : $title,
			callback: [ $this, "render_settings_field" ],
			page    : $this->page,
			section : $section,
			args    : [
				"type"             => "input",
				"subtype"          => ( in_array( needle: $index, haystack: [ 0, 1 ] ) ) ? "checkbox"        : "text",
				"label"            => ( in_array( needle: $index, haystack: [ 0, 1 ] ) ) ? $field[ "label" ] : "",
				"value"            => ( $index == 1 ) ? $field[ "value" ] : "",
				"id"               => $id,
				"name"             => $id,
				"required"         => true,
				"get_options_list" => "",
				"value_type"       => "normal",
				"wp_data"          => "option"
			]
		);
	}

	/**
	 * Register each plugin field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field
	 *
	 * @return void
	 */
	public function register_settings( array $field ) : void
	{
		$name = $this->prefix . $field[ "key" ];

		register_setting( option_group: $this->page, option_name: $name );
	}

	/**
	 * Echos out any content at the top of the section (between heading and fields).
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function message_section( $args ) : void
	{
		if ( strpos( haystack: $args[ "id" ], needle: "gateway" ) !== false )
		{
			echo "<p>Configurações da API Gateway</p>";
			echo "<p>Plugins que usam essa API:</p>";
			do_action( hook_name: "triibo_api_service_list_plugin_gate" );
		}

		if ( strpos( haystack: $args[ "id" ], needle: "node"    ) !== false )
		{
			echo "<p>Configurações da API Node</p>";
			echo "<p>Plugins que usam essa API:</p>";
			do_action( hook_name: "triibo_api_service_list_plugin_node" );
		}

		if ( strpos( haystack: $args[ "id" ], needle: "php"     ) !== false )
		{
			echo "<p>Configurações da API PHP</p>";
			echo "<p>Plugins que usam essa API:</p>";
			do_action( hook_name: "triibo_api_service_list_plugin_php" );
		}
	}

	/**
	 * Renders each field according to the configured args.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function render_settings_field( $args ) : void
	{
		if ( $args[ "wp_data" ] == "option" )
		{
			$wp_data_value = get_option( option: $args[ "name" ] );
		}
		elseif ( $args[ "wp_data" ] == "post_meta" )
		{
			$wp_data_value = get_post_meta( post_id: $args[ "post_id" ], key: $args[ "name" ], single: true );
		}

		switch ( $args[ "type" ] )
		{
			case "input":
				$status = 0;

				if ( strpos( haystack: $args[ "id" ], needle: "gateway" ) !== false ) $status = get_option( option: "triibo_api_services_gate_status" );
				if ( strpos( haystack: $args[ "id" ], needle: "node"    ) !== false ) $status = get_option( option: "triibo_api_services_node_status" );
				if ( strpos( haystack: $args[ "id" ], needle: "php"     ) !== false ) $status = get_option( option: "triibo_api_services_php_status"  );

				$value = ( $args[ "value_type" ] == "serialized" ) ? serialize( value: $wp_data_value ) : $wp_data_value;

				if ( $args[ "subtype" ] != "checkbox" )
				{
					$prependStart = ( isset( $args[ "prepend_value" ] ) ) ? "<div class='input-prepend'> <span class='add-on'>{$args[ "prepend_value" ]}</span>" : "";
					$prependEnd   = ( isset( $args[ "prepend_value" ] ) ) ? "</div>"                   : "";
					$step         = ( isset( $args[ "step"          ] ) ) ? "step='{$args[ "step" ]}'" : "";
					$min          = ( isset( $args[ "min"           ] ) ) ? "min='{$args[ "min" ]}'"   : "";
					$max          = ( isset( $args[ "max"           ] ) ) ? "max='{$args[ "max" ]}'"   : "";
					$required     = ( $args[ "required" ] && $status    ) ? "required='required'"      : "";

					if ( isset( $args[ "disabled" ] ) )
					{
						// hide the actual input bc if it was just a disabled input the info saved in the database would be wrong - bc it would pass empty values and wipe the actual information
						echo $prependStart . "
							<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}_disabled' {$step} {$max} {$min} name='{$args[ "name" ]}_disabled' disabled value='" . esc_attr( text: $value ) . "' />
							<input type='hidden' id='{$args[ "id" ]}' {$step} {$max} {$min} name='{$args[ "name" ]}' value='" . esc_attr( text: $value ) . "' />" . $prependEnd;
					}
					else
					{
						echo $prependStart . "
							<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}' {$required} {$step} {$max} {$min} name='{$args[ "name" ]}' value='" . esc_attr( text: $value ) . "' />" . $prependEnd;
					}
				}
				else
				{
					$checked = ( $value ) ? "checked" : "";
					echo "<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}' name='{$args[ "name" ]}' {$checked} />";
					echo "<label for='{$args[ "id" ]}' >{$args[ "label" ]}</label>";
				}
			break;

			// default:
			// 	# code...
			// break;
		}
	}

	/**
	 * @since 1.0.0
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function plugin_action_links( array $links ) : array
	{
        $url            = esc_url( url: admin_url( path: "admin.php?page={$this->plugin_name}-settings" ) );
		$plugin_links   = [ "<a href='{$url}' >Configurações</a>" ];

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add link to changelog modal.
	 *
	 * @since 1.2.0
	 *
	 * @param array 	$plugin_meta
	 * @param string 	$plugin_file
	 * @param array 	$plugin_data
	 *
	 * @return array
	 */
	public function plugin_row_meta( array $plugin_meta, string $plugin_file, array $plugin_data ) : array
	{
		$path = path_join( base: WP_PLUGIN_DIR, path: $plugin_file );

		if ( DIRECTORY_SEPARATOR == "\\" )
			$path = str_replace( search: "/", replace: "\\", subject: $path );

        if ( TRIIBO_API_SERVICES_FILE === $path )
		{
            $url = plugins_url( path: "readme.txt", plugin: TRIIBO_API_SERVICES_FILE );

            $plugin_meta[] = sprintf(
                "<a href='%s' class='thickbox open-plugin-details-modal' aria-label='%s' data-title='%s'>%s</a>",
                add_query_arg(  "TB_iframe", "true", $url ),
                esc_attr( text: sprintf( __( text: "More information about %s" ), $plugin_data[ "Name" ] ) ),
                esc_attr( text: $plugin_data[ "Name" ] ),
                __( text: "Histórico de alterações" )
            );
        }

        return $plugin_meta;
	}

	/**
	 * Registering triibo `meta_keys` as not protected.
	 *
	 * @since 1.12.0
	 *
	 * @param bool 		$protected
	 * @param string 	$meta_key
	 * @param string 	$meta_type
	 *
	 * @return bool
	 */
	public function triibo_protected_metas( bool $protected, string $meta_key, string $meta_type ) : bool
	{
		$triibo_metas = [
			"triiboId_phone",

			"_triibo_id",
			"_triibo_phone",
			"_triibo_auth_token",
			"_triibo_auth_validity",
			"_triibo_payments_code",
			"_triibo_security_code",
			"_triibo_assinatura_payment_id",
			"_triibo_assinatura_payment_token",

			"_fastshop_hub",
			"_fastshop_order_id",
			"_fastshop_order_approved",

			"_fs_product_id",
			"_is_integration",
		];

		if ( in_array( needle: $meta_key, haystack: $triibo_metas ) )
			return false;

		return $protected;
	}

	/**
	 * Updates the post_parent of images associated with a product.
	 *
	 * @since 1.13.0
	 *
	 * @param int 		$product_id
	 * @param object 	$product
	 *
	 * @return void
	 */
	public function attach_product_images( $product_id, $product ) : void
	{
		$thumbnail_id = get_post_meta( post_id: $product_id, key: "_thumbnail_id",          single: true );
		$gallery      = get_post_meta( post_id: $product_id, key: "_product_image_gallery", single: true );

		if ( $thumbnail_id )
			wp_update_post( postarr: [ "ID" => $thumbnail_id, "post_parent" => $product_id, ] );

		if ( !empty( $gallery ) )
		{
			$gallery_ids = explode( separator: ",", string: $gallery );

			foreach ( $gallery_ids as $gallery_id )
				wp_update_post( postarr: [ "ID" => $gallery_id, "post_parent" => $product_id, ] );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run() : void
	{
		$this->loader->run();
	}
}
