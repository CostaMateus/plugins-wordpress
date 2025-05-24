<?php
/**
 * Plugin's main class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

/**
 * Triibo_Auth bootstrap class
 */
class Triibo_Auth
{
	/**
	 * Admin config prefix
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $prefix = "";

	/**
	 * Admin config sufix
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $sufix  = "";

	/**
	 * Admin config page
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $page   = "";

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Construct
	 *
	 * @since 2.2.1 	Added action admin_enqueue scripts
	 * @since 1.0.0
	 */
	private function __construct()
    {
		if ( class_exists( class: "Triibo_Api_Services" ) )
		{
			add_action( hook_name: "admin_enqueue_scripts", callback: [ $this, "enqueue_admin_files" ] );

			$this->add_actions();
			$this->add_filters();

			$this->includes();
			$this->init_classes();

			$this->add_common();
        }
		else
		{
			add_action( hook_name: "admin_notices", callback: [ $this, "triibo_api_services_missing_notice" ] );
		}
    }

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return self 	A single instance of this class.
	 */
	public static function get_instance() : self
    {
		if ( null === self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Enqueue admin js and css files
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_admin_files() : void
	{
		$js_path  = "assets/js/admin/admin.js";
		$css_path = "assets/css/admin/admin.css";

		$js_ver   = date( format: "ymd-His", timestamp: filemtime( filename: plugin_dir_path( file: TRIIBO_AUTH_PLUGIN_FILE ) . $js_path  ) );
		$css_ver  = date( format: "ymd-His", timestamp: filemtime( filename: plugin_dir_path( file: TRIIBO_AUTH_PLUGIN_FILE ) . $css_path ) );

		wp_enqueue_script( handle: TRIIBO_AUTH_ID . "_js",  src: plugins_url( path: $js_path,  plugin: TRIIBO_AUTH_PLUGIN_FILE ), deps: [ "jquery" ], ver: $js_ver,  args: false );
		wp_enqueue_style(  handle: TRIIBO_AUTH_ID . "_css", src: plugins_url( path: $css_path, plugin: TRIIBO_AUTH_PLUGIN_FILE ), deps: [],           ver: $css_ver, media: "all" );
	}

	/**
	 * Add plugin actions
	 *
	 * @since 2.0.0 	Add dependecy of Node API
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function add_actions() : void
	{
		add_action( hook_name: "admin_init",                          callback: [ $this, "register_and_build_fields" ] );
		add_action( hook_name: "admin_menu",                          callback: [ $this, "add_submenu_page"          ], priority: 11 );
		add_action( hook_name: "triibo_api_service_add_button",       callback: [ $this, "add_btn"                   ], priority: 11 );
		add_action( hook_name: "triibo_api_service_list_plugin_gate", callback: [ $this, "add_info_dependency"       ] );
		add_action( hook_name: "triibo_api_service_list_plugin_node", callback: [ $this, "add_info_dependency"       ] );
		add_action( hook_name: "wp_footer",                           callback: [ $this, "add_global_css"            ] );
	}

	/**
	 * Add plugin filters
	 *
	 * @since 2.0.0 	Add plugin_row_meta
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function add_filters() : void
	{
		add_filter( hook_name: "plugin_action_links_" . plugin_basename( file: TRIIBO_AUTH_PLUGIN_FILE ), callback: [ $this, "plugin_action_links" ] );
		add_filter( hook_name: "plugin_row_meta", callback: [ $this, "plugin_row_meta" ], accepted_args: 3 );
	}

	/**
	 * Includes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function includes() : void
    {
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/rest-api/class-triibo-auth-rest-api.php" );
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/class-triibo-auth-ajax.php"              );
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/class-triibo-auth-app.php"               );
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/class-triibo-auth-login.php"             );
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/class-triibo-auth-checkout.php"          );
    }

	/**
	 * Init classes
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function init_classes() : void
	{
		new Triibo_Auth_Rest_Api();

		if ( Triibo_Auth_App::status()      )
			new Triibo_Auth_App();

		if ( Triibo_Auth_Login::status()    )
			new Triibo_Auth_Login();

		if ( Triibo_Auth_Checkout::status() )
			new Triibo_Auth_Checkout();
	}

	/**
	 * Includes common code for Triibo-Auth-Login and Triibo-Auth-Checkout
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function add_common() : void
	{
		if ( Triibo_Auth_Login::status() || Triibo_Auth_Checkout::status() )
		{
			add_action( hook_name: "wp_footer", callback: [ $this, "add_modals"  ] );
			add_action( hook_name: "wp_footer", callback: [ $this, "add_scripts" ] );
		}

		if ( Triibo_Auth_Checkout::status() )
			add_action( hook_name: "wp_footer", callback: [ $this, "add_modal_checkout" ] );
	}

	/**
	 * Include required modals Triibo-Auth-Login and Triibo-Auth-Checkout
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
    public function add_modals() : void
    {
        require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/load.php"           );
        require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/register.php"       );
        require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/validate.php"       );
        require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/validate-email.php" );
    }

	/**
	 * Include required modals Triibo-Auth-Checkout
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_modal_checkout() : void
	{
        require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/checkout.php"        );
        require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/has_query_param.php" );
	}

    /**
     * Required js
     *
     * @since 1.0.0
	 *
     * @return void
     */
    public function add_scripts() : void
    {
		$mk_path = "assets/js/frontend/mask.min.js";
		$js_path = "assets/js/frontend/tal-script.js";

		$mk_ver  = date( format: "ymd-His", timestamp: filemtime( filename: plugin_dir_path( file: TRIIBO_AUTH_PLUGIN_FILE ) . $mk_path  ) );
		$js_ver  = date( format: "ymd-His", timestamp: filemtime( filename: plugin_dir_path( file: TRIIBO_AUTH_PLUGIN_FILE ) . $js_path  ) );

		wp_enqueue_script( handle: "tal_mask",   src: plugins_url( path: $mk_path,  plugin: TRIIBO_AUTH_PLUGIN_FILE ), deps: [ "jquery" ], ver: $mk_ver,  args: false );
		wp_enqueue_script( handle: "tal_script", src: plugins_url( path: $js_path,  plugin: TRIIBO_AUTH_PLUGIN_FILE ), deps: [ "jquery" ], ver: $js_ver,  args: true  );

		$urls[ "ajaxurl" ] = admin_url( path: "admin-ajax.php" );

		if ( Triibo_Auth_Checkout::status() )
			$urls[ "redirect" ] = Triibo_Auth_Checkout::get_checkout_url();

		wp_localize_script( handle: "tal_script", object_name: "tal_script_obj", l10n: $urls );
    }

	/**
	 * Triibo-Api-Service missing notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function triibo_api_services_missing_notice() : void
	{
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/templates/notices/missing-triibo_api_services.php" );
	}

	/**
	 * Action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$links
	 *
	 * @return array
	 */
	public function plugin_action_links( array $links ) : array
    {
        $url            = esc_url( url: admin_url( path: "admin.php?page=" . TRIIBO_AUTH_ID . "-settings" ) );
        $text           = __( text: "Configurações", domain: TRIIBO_AUTH_ID );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add link to changelog modal
	 *
	 * @since 2.0.0
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

        if ( TRIIBO_AUTH_PLUGIN_FILE === $path )
		{
            $url = plugins_url( path: "readme.txt", plugin: TRIIBO_AUTH_PLUGIN_FILE );

            $plugin_meta[] = sprintf(
                "<a href='%s' class='thickbox open-plugin-details-modal' aria-label='%s' data-title='%s'>%s</a>",
                add_query_arg( "TB_iframe", "true", $url ),
                esc_attr( text: sprintf( __( text: "More information about %s" ), $plugin_data[ "Name" ] ) ),
                esc_attr( text: $plugin_data[ "Name" ] ),
                __( text: "Histórico de alterações" )
            );
        }

        return $plugin_meta;
	}

	/**
	 * Add submenu
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_submenu_page() : void
	{
		add_submenu_page(
			parent_slug: Triibo_Api_Services::get_name(),
			page_title: "Triibo Auth",
			menu_title: "Auth",
			capability: "manage_options",
			menu_slug: TRIIBO_AUTH_ID . "-settings",
			callback: [ $this, "display_admin_menu_settings" ]
		);
	}

	/**
	 * Add config page
	 *
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
			add_action( hook_name: "admin_notices", callback: [ $this, TRIIBO_AUTH_ID . "_settings_messages" ] );
			do_action( hook_name: "admin_notices", arg: $_GET[ "error_message" ] );
		}

		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/templates/admin-page.php" );
	}

	/**
	 * Add config buttom
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_btn() : void
	{
        $url  = esc_url( url: admin_url( path: "admin.php?page=" . TRIIBO_AUTH_ID . "-settings" ) );
		$text = __( text: "Configurações", domain: TRIIBO_AUTH_ID );
		$link = "<a href='{$url}' >{$text}</a>";

		echo "<p>Triibo Auth | {$link}</p>";
	}

	/**
	 * Add information about dependenc
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_info_dependency() : void
	{
		echo "<p> - Triibo Auth</p>";
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
		$this->prefix = TRIIBO_AUTH_ID . "_";
		$this->sufix  = "_section";

		$this->page   = $this->prefix . "settings";

		$resources    = [
			"app"      => [
				[
					"key"   => "app_status",
					"title" => "Ativar / Desativar",
					"label" => "Ativar recurso",
					"value" => true,
				],
			],
			"checkout" => [
				[
					"key"   => "checkout_status",
					"title" => "Ativar / Desativar",
					"label" => "Ativar recurso",
					"value" => true,
				],
			],
			"login"    => [
				[
					"key"   => "login_status",
					"title" => "Ativar / Desativar",
					"label" => "Ativar recurso",
					"value" => true,
				],
			],

			/**
			 * @since 2.0.0 Add config whatsapp
			 */
			"wpp"      => [
				[
					"key"   => "wpp_status",
					"title" => "Ativar / Desativar",
					"label" => "Ativar opção de envio (Checkout e Login)",
					"value" => true,
				],
			],
		];

		register_setting( option_group: $this->page, option_name: $this->prefix . "options" );

		$this->add_allowed_options( page: $this->page, data: $resources );

		add_settings_section( id: $this->prefix . "app"      . $this->sufix, title: "App",      callback: [ $this, "message_section" ], page: $this->page );
		add_settings_section( id: $this->prefix . "checkout" . $this->sufix, title: "Checkout", callback: [ $this, "message_section" ], page: $this->page );
		add_settings_section( id: $this->prefix . "login"    . $this->sufix, title: "Login",    callback: [ $this, "message_section" ], page: $this->page );

		/**
		 * @since 2.0.0 Add config whatsapp
		 */
		add_settings_section( id: $this->prefix . "wpp"      . $this->sufix, title: "Código via Whatsapp", callback: [ $this, "message_section" ], page: $this->page );

		foreach ( $resources as $res => $fields )
		{
			foreach ( $fields as $field )
			{
				$this->add_settings_field( section: $res, field: $field );
				$this->register_settings( field: $field );
			}
		}
	}

	/**
	 * Adds plugin fields to allowed options
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

		foreach ( $data as $fields )
			foreach ( $fields as $field )
				$new_options[ $page ][] = $this->prefix . $field[ "key" ];

		add_allowed_options( new_options: $new_options );
	}

	/**
	 * Configure each plugin field
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$section
	 * @param array 	$field
	 *
	 * @return void
	 */
	public function add_settings_field( string $section, array $field ) : void
	{
		$id      = $this->prefix . $field[ "key" ];
		$title   = $field[ "title" ];
		$section = $this->prefix . $section . $this->sufix;

		add_settings_field(
            id: $id,
			title: $title,
			callback: [ $this, "render_settings_field" ],
			page: $this->page,
			section: $section,
			args: [
				"type"             => "input",
				"subtype"          => "checkbox",
				"label"            => $field[ "label" ],
				"value"            => $field[ "value" ],
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
	 * Register each plugin field
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$field
	 *
	 * @return void
	 */
	public function register_settings( array $field ) : void
	{
		$name = $this->prefix . $field[ "key" ];

		register_setting( option_group: $this->page, option_name: $name );
	}

	/**
	 * Renders each field according to the configured args
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$args
	 *
	 * @return void
	 */
	public function render_settings_field( array $args ) : void
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

				/**
				 * @since 2.0.0 Add config whatsapp
				 */
				if ( strpos( haystack: $args[ "id" ], needle: "wpp"      ) !== false )
					$status = get_option( option: TRIIBO_AUTH_ID . "_wpp_status"      );

				if ( strpos( haystack: $args[ "id" ], needle: "login"    ) !== false )
					$status = get_option( option: TRIIBO_AUTH_ID . "_login_status"    );

				if ( strpos( haystack: $args[ "id" ], needle: "checkout" ) !== false )
					$status = get_option( option: TRIIBO_AUTH_ID . "_checkout_status" );

				if ( strpos( haystack: $args[ "id" ], needle: "app"      ) !== false )
					$status = get_option( option: TRIIBO_AUTH_ID . "_app_status"      );


				$value = ( $args[ "value_type" ] == "serialized" ) ? serialize( value: $wp_data_value ) : $wp_data_value;

				if ( $args[ "subtype" ] != "checkbox" )
				{
					$prependStart = ( isset( $args[ "prepend_value" ] ) ) ? "<div class='input-prepend'> <span class='add-on'>{$args[ "prepend_value" ]}</span>" : "";
					$prependEnd   = ( isset( $args[ "prepend_value" ] ) ) ? "</div>"                   : "";
					$step         = ( isset( $args[ "step"          ] ) ) ? "step='{$args[ "step" ]}'" : "";
					$min          = ( isset( $args[ "min"           ] ) ) ?  "min='{$args[ "min"  ]}'" : "";
					$max          = ( isset( $args[ "max"           ] ) ) ?  "max='{$args[ "max"  ]}'" : "";
					$required     = ( $args[ "required" ] && $status    ) ? "required='required'"      : "";

					if ( isset( $args[ "disabled" ] ) )
					{
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
		}
	}

	/**
	 * Echos out any content at the top of the section (between heading and fields).
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$args
	 *
	 * @return void
	 */
	public function message_section( array $args ) : void
	{
		/**
		 * @since 2.0.0 Add config whatsapp
		 */
		if ( strpos( haystack: $args[ "id" ], needle: "wpp"      ) !== false )
			echo "<p>Envio de código de autenticação via whatsapp</p>";

		if ( strpos( haystack: $args[ "id" ], needle: "login"    ) !== false )
			echo "<p>Login pela Triibo</p>";

		if ( strpos( haystack: $args[ "id" ], needle: "checkout" ) !== false )
			echo "<p>Login Triibo antes do Checkout</p>";

		if ( strpos( haystack: $args[ "id" ], needle: "app"      ) !== false )
			echo "<p>Login pelo App</p>";
	}

	/**
	 * Includes global css
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_global_css() : void
	{
		$css_path = "assets/css/frontend/style.css";
		$css_ver  = date( format: "ymd-His", timestamp: filemtime( filename: plugin_dir_path( file: TRIIBO_AUTH_PLUGIN_FILE ) . $css_path ) );
		wp_enqueue_style ( handle: "ta_global_css", src: plugins_url( path: $css_path, plugin: TRIIBO_AUTH_PLUGIN_FILE ), ver: $css_ver );
	}
}