<?php
/**
 * The file that defines the plugin class Triibo-Auth-App
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Auth_App
{
    /**
     * Class id
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $id = TRIIBO_AUTH_APP;

	/**
	 * API Gateway.
	 *
	 * @since 1.0.0
     *
	 * @var Triibo_Api_Gateway
	 */
	protected $gate;

	/**
	 * API Node.
	 *
	 * @since 1.0.0
     *
	 * @var Triibo_Api_Node
	 */
	protected $node;

	/**
	 * WC Logger.
	 *
	 * @since 1.0.0
     *
	 * @var WC_Logger
	 */
	protected $log;

    /**
     * Construct
     *
	 * @since 1.0.0
     */
    public function __construct()
    {
		$this->log  = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		$this->gate = new Triibo_Api_Gateway();
		$this->node = new Triibo_Api_Node();

		add_action( hook_name: "wp_footer",                           callback: [ $this, "add_modals"       ] );
		add_action( hook_name: "wp_footer",                           callback: [ $this, "add_script"       ] );
        add_action( hook_name: "wp_ajax_taa_new_account",             callback: [ $this, "new_account"      ] );
        add_action( hook_name: "wp_ajax_nopriv_taa_new_account",      callback: [ $this, "new_account"      ] );
        add_action( hook_name: "wp_ajax_taa_validate_account",        callback: [ $this, "validate_account" ] );
        add_action( hook_name: "wp_ajax_nopriv_taa_validate_account", callback: [ $this, "validate_account" ] );
    }

    /**
     * Returns the Triibo_Auth_App status
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function status() : bool
	{
        $status = get_option( option: TRIIBO_AUTH_ID . "_app_status" );

		return $status == "on" ? true : false;
	}

    /**
     * Required modals
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_modals() : void
    {
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/taa/new-account.php"      );
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/taa/new-password.php"     );
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/taa/validate-account.php" );
		require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/taa/validate-code.php"    );

		wp_localize_script(
            handle: "taa_script",
            object_name: "taa_script_obj",
            l10n: [ "ajaxurl"  => admin_url( path: "admin-ajax.php" ) ]
        );
    }

    /**
     * Required js
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_script()
    {
		$js_path = "assets/js/frontend/taa-script.js";
		$js_ver  = date( format: "ymd-His", timestamp: filemtime( filename: plugin_dir_path( file: TRIIBO_AUTH_PLUGIN_FILE ) . $js_path  ) );
		wp_enqueue_script( handle: "taa_script", src: plugins_url( path: $js_path, plugin: TRIIBO_AUTH_PLUGIN_FILE ), deps: [ "jquery" ], ver: $js_ver,  args: false );
		wp_localize_script( handle: "taa_script", object_name: "taa_script_obj", l10n: [ "ajaxurl" => admin_url( path: "admin-ajax.php" ) ] );
    }

    /**
     * Validate account, if it exists connect, if not create
     *
     * @since 2.2.0 Added call to get_user (Node)
     * @since 1.1.0 New flow
     * @since 1.0.1 Log improvement
     * @since 1.0.0
     *
     * @return never
     */
    public function new_account() : never
    {
        $email     = $_POST[ "email" ];
        $pass      = $_POST[ "pass"  ];
        $terms     = $_POST[ "terms" ];

        $cellphone = get_transient( transient: "_taa_triibo_cellphone" );
        $user      = get_user_by( field: "email", value: $email );

        if ( $user )
        {
            if ( wp_check_password( password: $pass, hash: $user->data->user_pass, user_id: $user->ID ) )
            {
                add_user_meta( user_id: $user->ID, meta_key: "_triibo_phone", meta_value: "+$cellphone" );

                $response = $this->node->get_user( cellphone: $cellphone );

                if ( $response[ "success" ] )
                {
                    $user     = $response[ "data" ][ "userInfo" ][ "user" ];

                    $response = $this->gate->update_user_mkt(
                        user: $user,
                        email: $email,
                        action: "CREATE",
                        name: $user->first_name,
                        nick: $user->nickname,
                        terms: $terms
                    );

                    if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
                    {
                        $response[ "data_sent" ] = [
                            "function"  => "new_account_1",
                            "cellphone" => $cellphone,
                            "email"     => $email
                        ];

                        $this->log( message: json_encode( value: $response ) );
                    }

                    self::make_login( user_id: $user->ID );

                    $result   = [ "success" => true ];
                }
                else
                {
                    $this->log( message: "ERROR - Node /login " . json_encode( value: $response ) );

                    $result = [
                        "success" => false,
                        "error"   => [
                            "code"    => 401,
                            "message" => "Falha na autentição Triibo",
                        ]
                    ];
                }
            }
            else
            {
                $result = [
                    "success" => false,
                    "error"   => [
                        "code"    => 401,
                        "message" => "E-mail e/ou senha incorreta",
                    ]
                ];
            }
        }
        else
        {
            $name    = explode( separator: "@", string: $email )[ 0 ];

            $user_id = wp_insert_user( userdata: [
                "user_login"    => $email,
                "user_pass"     => wp_hash_password( password: "Triibo" . date( format: "Y" ) . "@!#" ),
                "user_nicename" => $name,
                "user_email"    => $email,
                "display_name"  => $name,
                "nickname"      => $name,
                "first_name"    => $name,
                "last_name"     => "",
            ] );

            if ( !is_wp_error( thing: $user_id ) )
            {
                wp_set_password( password: $pass, user_id: $user_id );

                add_user_meta( user_id: $user_id, meta_key: "_triibo_phone", meta_value: "+$cellphone" );

                $user = get_user_by( field: "id", value: $user_id );

                $user->set_role( role: "subscriber" );

                if ( class_exists( class: "WooCommerce" ) )
                    $user->set_role( role: "customer" );

                $response = $this->node->get_user( cellphone: $cellphone );

                if ( $response[ "success" ] )
                {
                    $user_triibo = $response[ "data" ][ "userInfo" ][ "user" ];

                    $response    = $this->gate->update_user_mkt(
                        user: $user_triibo,
                        email: $email,
                        action: "UPDATE",
                        name: $user->first_name,
                        nick: $user->nickname,
                        terms: $terms
                    );

                    if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
                    {
                        $response[ "data_sent" ] = [
                            "function"  => "new_account_2",
                            "cellphone" => $cellphone,
                            "email"     => $email
                        ];

                        $this->log( message: json_encode( value: $response ) );
                    }

                    self::make_login( user_id: $user_id );

                    $result      = [ "success" => true ];
                    delete_transient( transient: "_taa_triibo_uid"       );
                    delete_transient( transient: "_taa_triibo_cellphone" );
                }
                else
                {
                    $this->log( message: "ERROR - Node /login " . json_encode( value: $response ) );

                    $result = [
                        "success" => false,
                        "error"   => [
                            "code"    => 401,
                            "message" => "Falha na autentição Triibo",
                        ]
                    ];
                }
            }
            else
            {
                $result = [
                    "success" => false,
                    "error"   => [
                        "code"    => 500,
                        "message" => "Falha na criação da conta"
                    ]
                ];
            }
        }

        echo json_encode( value: $result );
        die();
    }

    /**
     * validate account, if it exists connect
     *
     * @since 2.2.0 Added call to get_user (Node)
     * @since 1.1.0 New flow
     * @since 1.0.1 Log improvement
     * @since 1.0.0
     *
     * @return never
     */
    public function validate_account() : never
    {
        $email     = $_POST[ "email" ];
        $pass      = $_POST[ "pass"  ];
        $terms     = $_POST[ "terms" ];

        $uid       = get_transient( transient: "_taa_triibo_uid"       );
        $cellphone = get_transient( transient: "_taa_triibo_cellphone" );
        $user      = get_user_by( field: "email", value: $email );

        if ( !$user )
        {
            $result = [
                "success" => false,
                "error"   => [
                    "code"    => 404,
                    "message" => "Usuário não encontrado",
                ]
            ];
        }
        else
        {
            if ( $user && wp_check_password( password: $pass, hash: $user->data->user_pass, user_id: $user->ID ) )
            {
                $response = $this->node->get_user( cellphone: $cellphone );

                if ( $response[ "success" ] )
                {
                    $user_triibo = $response[ "data" ][ "userInfo" ][ "user" ];

                    $response    = $this->gate->update_user_mkt(
                        user: $user_triibo,
                        email: $email,
                        action: "CREATE",
                        name: $user->first_name,
                        nick: $user->nickname,
                        terms: $terms
                    );

                    if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
                    {
                        $response[ "data_sent" ] = [
                            "function"  => "validate_account",
                            "cellphone" => $cellphone,
                            "email"     => $email
                        ];

                        $this->log( message: json_encode( value: $response ) );
                    }

                    self::make_login( user_id: $user->ID );

                    $result = [ "success" => true ];

                    delete_user_meta( user_id: $user->ID, meta_key: "triiboId_phone", meta_value: $cellphone );
                    update_user_meta( user_id: $user->ID, meta_key: "_triibo_phone",  meta_value: $cellphone );
                    update_user_meta( user_id: $user->ID, meta_key: "_triibo_id",     meta_value: $uid       );

                    delete_transient( transient: "_taa_triibo_uid"       );
                    delete_transient( transient: "_taa_triibo_cellphone" );
                }
                else
                {
                    $this->log( message: "ERROR - Node /login " . json_encode( value: $response ) );

                    $result = [
                        "success" => false,
                        "error"   => [
                            "code"    => 401,
                            "message" => "Falha na autentição Triibo",
                        ]
                    ];
                }
            }
            else
            {
                $result = [
                    "success" => false,
                    "error"   => [
                        "code"    => 401,
                        "message" => "E-mail e/ou senha incorreta",
                    ]
                ];
            }
        }

        echo json_encode( value: $result );
        die();
    }

    /**
     * Log in to the account
     *
     * @since 1.0.0
     *
     * @param integer   $user_id
     *
     * @return void
     */
	private static function make_login( int $user_id ) : void
	{
		clean_user_cache( user: $user_id );
		wp_clear_auth_cookie();
		wp_set_current_user( id: $user_id );
		wp_set_auth_cookie( user_id: $user_id, remember: true );
	}

    /**
     * Register log
     *
     * @since 1.0.0
     *
     * @param string    $message
     *
     * @return void
     */
    private function log( string $message ) : void
    {
        $this->log->add( handle: $this->id, message: $message );
    }
}
