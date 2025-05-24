<?php
/**
 * The file that defines the plugin class Triibo-Auth-Ajax
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Auth_Ajax
{
    /**
     * Class id
     *
     * @since 2.2.1     Changed so that the other classes define its value
     * @since 1.0.0
     *
     * @var string
     */
    protected $id;

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
	 * @since 2.0.0
     *
	 * @var Triibo_Api_Node
	 */
	protected $node;

    /**
     * Send types
     *
	 * @since 2.0.0
     *
     * @var array
     */
    protected $send_types = [ "sms", "wpp" ];

	/**
	 * WC Logger.
	 *
	 * @since 1.0.0
     *
	 * @var WC_Logger
	 */
	protected $log;

    /**
     * Send code by sms
     *
     * @since 2.1.0     New Node API securityCode
     * @since 2.0.0     New Node API for send code by sms/wpp
     * @since 1.0.1     Log improvement
     * @since 1.0.0
     *
     * @return never
     */
    public function auth_sms() : never
    {
        /**
         * @since 2.1.0 Added api security code
         */
        $response = $this->node->get_security_code();

        if ( $response[ "success" ] )
        {
            $code     = $response[ "data" ][ "securityCode" ];

            $response = $this->node->auth( token: "", code: $code );

            if ( $response[ "success" ] )
            {
                $_token    = $response[ "data"  ][ "token" ];

                $type      = ( !in_array( needle: $_POST[ "type" ], haystack: $this->send_types ) ) ? "sms" : $_POST[ "type" ];
                $type      = get_option( option: TRIIBO_AUTH_ID . "_wpp_status" ) ? $type : "sms";

                $cellphone = $_POST[ "cellphone" ];

                $response  = $this->node->auth_code( token: $_token, data: [
                    "cellphone" => $cellphone,
                    "type"      => $type
                ] );

                if ( $response[ "success" ] )
                {
                    $this->log( message: "INFO - auth_code : " . json_encode( value: $response ) );

                    $return = [
                        "error"   => false,
                        "success" => [
                            "transactionId" => $response[ "data" ][ "transactionId" ],
                        ]
                    ];

                    echo json_encode( value: $return );
                    die();
                }
                else
                {
                    $this->log( message: "ERROR - auth_code : " . json_encode( value: $response ) );

                    $err   = $response[ "data" ][ "error" ];
                    $code  = ( $err == "Usuário excedeu o limite de requisições" ) ? 429 : 400;
                    $error = [
                        "success" => false,
                        "error"   => [
                            "errorCode" => $code,
                        ]
                    ];
                }
            }
            else
            {
                $this->log( message: "ERROR - auth : " . json_encode( value: $response ) );

                $error = [
                    "success" => false,
                    "error"   => [
                        "errorCode" => 500,
                    ]
                ];
            }
        }
        else
        {
            $this->log( message: "ERROR - auth_security : " . json_encode( value: $response ) );

            $error = [
                "success" => false,
                "error"   => [
                    "errorCode" => 401,
                ]
            ];
        }

        echo json_encode( value: $error );
        die();
    }

    /**
     * Validates code sent by sms
     *
     * @since 2.2.0     Change validation to Node API
     * @since 1.0.1     Log improvement
     * @since 1.0.0
     *
     * @return never
     */
    public function validate_sms() : never
    {
        $data     = [
            "code"      => $_POST[ "code"          ],
            "trans_id"  => $_POST[ "transactionId" ],
            "cellphone" => $_POST[ "cellphone"     ],
        ];

        $response = $this->node->validate_sms( data: $data );

        if ( $response[ "success" ] )
        {
            $success = [
                "success" => true,
                "data"    => [
                    "logged_in" => is_user_logged_in(),
                ],
                "error"   => null,
            ];

            echo json_encode( value: $success );
            exit();
        }

        $this->log( message: "ERROR - validate_sms : " . json_encode( value: $response ) );

        $error = [
            "success" => false,
            "error"   => [
                "errorCode" => $this->get_error_code( error: $response[ "data" ][ "error" ] ),
                "message"   => $response[ "data" ][ "error" ],
            ],
            "data"    => [
                "logged_in" => is_user_logged_in(),
            ]
        ];

        echo json_encode( value: $error );
        exit();
    }

    /**
     * Get user info triibo
     *
     * @since 1.1.0     New flow
     * @since 1.0.1     Log improvement
     * @since 1.0.0
     *
     * @return never
     */
    public function user_info() : never
    {
        $error     = true;
        $cellphone = $_POST[ "cellphone" ];

        $response  = $this->gate->user_info( data: $cellphone );

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $response[ "data_sent" ] = [
                "function" => "user_info_1",
                "cellphone" => $cellphone
            ];

            $this->log( message: json_encode( value: $response ) );
        }

        $data      = $response[ "data" ];

        if ( empty( $data[ "error" ] ) && empty( $data[ "success" ][ "userInfo" ] ) )
        {
            $alert  = reset( array: $data[ "alert" ] );
            $result = [
                "success" => false,
                "error"   => [
                    "message" => $alert[ "errorMsg" ],
                    "code"    => 404,
                ],
            ];
        }
        elseif ( !empty( $data[ "error" ] ) )
        {
            $result = [
                "success" => false,
                "error"   => [
                    "message" => "Server error",
                    "code"    => 500,
                ],
            ];
        }
        else
        {
            $uid       = $response[ "data" ][ "success" ][ "userInfo" ][ "uId" ];
            $cellphone = "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $cellphone );

            $user      = get_users( args: [
                "meta_key"   => "_triibo_id",
                "meta_value" => $uid
            ] );

            if ( !empty( $user ) )
            {
                $user     = reset( array: $user );
                $email    = $user->user_email;

                delete_user_meta( user_id: $user->ID, meta_key: "triiboId_phone", meta_value: $cellphone );
                update_user_meta( user_id: $user->ID, meta_key: "_triibo_phone",  meta_value: $cellphone );

                $result   = $this->login_by_triibo( field: "id", value: $user->ID );
            }
            else
            {
                $user = get_users( args: [
                    "meta_key"   => "triiboId_phone",
                    "meta_value" => $cellphone
                ] );

                if ( !empty( $user ) )
                {
                    $user     = reset( array: $user );
                    $email    = $user->user_email;

                    $response = $this->create_or_update_user_triibo( cellphone: $cellphone, email: $email, name: $user->first_name, nick: $user->nickname );

                    if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
                    {
                        $response[ "data_sent" ] = [
                            "function"  => "user_info_3",
                            "cellphone" => $cellphone,
                            "email"     => $email,
                        ];

                        $this->log( message: json_encode( value: $response ) );
                    }

                    $uid      = ( $uid == $response[ "data" ][ "success" ][ "newUserInfo" ][ "uId" ] ) ? $uid : $response[ "data" ][ "success" ][ "userInfo" ][ "uId" ];

                    delete_user_meta( user_id: $user->ID, meta_key: "triiboId_phone", meta_value: $cellphone );
                    update_user_meta( user_id: $user->ID, meta_key: "_triibo_phone",  meta_value: $cellphone );
                    update_user_meta( user_id: $user->ID, meta_key: "_triibo_id",     meta_value: $uid       );

                    $result   = $this->login_by_triibo( field: "id", value: $user->ID );
                }
                else
                {
                    $result   = [
                        "success" => false,
                        "error"   => [
                            "message" => "Celular não encontrado",
                            "code"    => 401,
                        ],
                    ];
                }
            }
        }

        if ( $error )
        {
            $log                = $result;
            $log[ "data_sent" ] = [
                "function"  => "user_info_2",
                "cellphone" => $cellphone
            ];

            $this->log( message: json_encode( value: $log ) );
        }

        echo json_encode( value: $result );
        die();
    }

    /**
     * Create user triibo
     *
     * @since 1.1.0     New flow. Non-existent user no longer validates email
     * @since 1.0.1     Log improvement
     * @since 1.0.0
     *
     * @return never
     */
    public function find_user() : never
    {
        $cellphone = $_POST[ "cellphone" ];
        $email     = $_POST[ "email"     ];
        $passw     = $_POST[ "password"  ];
        $terms     = $_POST[ "terms"     ];

        $user      = get_user_by( field: "email", value: $email );

        if ( !empty( $user ) )
        {
            if ( $user && wp_check_password( password: $passw, hash: $user->data->user_pass, user_id: $user->ID ) )
            {
                $response  = $this->create_or_update_user_triibo( cellphone: $cellphone, email: $email, name: $user->first_name, nick: $user->nickname, terms: $terms );

                if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
                {
                    $response[ "data_sent" ] = [
                        "function"  => "find_user",
                        "cellphone" => $cellphone,
                        "email"     => $email,
                    ];

                    $this->log( message: json_encode( value: $response ) );
                }

                $cellphone = "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $cellphone );

                delete_user_meta( user_id: $user->ID, meta_key: "triiboId_phone", meta_value: $cellphone );
                update_user_meta( user_id: $user->ID, meta_key: "_triibo_phone",  meta_value: $cellphone );

                if ( isset( $response[ "uid" ] ) )
                    update_user_meta( user_id: $user->ID, meta_key: "_triibo_id", meta_value: $response[ "uid" ] );

                $result    = $this->login_by_triibo( field: "id", value: $user->ID );
            }
            else
            {
                $result = [
                    "success" => false,
                    "error"   => [
                        "message" => "E-mail e/ou senha incorreta",
                        "code"    => 401,
                    ],
                ];
            }
        }
        else
        {
            /**
             * @since 1.1.0
             * Create marketplace account directly
             */
            $response = $this->create_or_update_user_triibo( cellphone: $cellphone, email: $email, name: "", nick: "", terms: $terms );

            if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
            {
                $response[ "data_sent" ] = [
                    "function"  => "find_user",
                    "cellphone" => $cellphone,
                    "email"     => $email,
                ];

                $this->log( message: json_encode( value: $response ) );
            }

            $uid      = isset( $response[ "uid" ] ) ? $response[ "uid" ] : null;
            $result   = $this->create_user_marketplace( uid: $uid, cellphone: $cellphone, email: $email, passw: $passw );
        }

        echo json_encode( value: $result );
        die();
    }

    /**
     * Create user triibo
     *
     * @since 1.0.1     Log improvement
     * @since 1.0.0
     * @return never
     */
    public function add_triibo_id() : never
    {
        $cellphone = $_POST[ "cellphone" ];
        $user      = wp_get_current_user();

        $response  = $this->create_or_update_user_triibo( cellphone: $cellphone, email: $user->user_email, name: $user->first_name, nick: $user->nickname );

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $response[ "data_sent" ] = [
                "function"  => "add_triibo_id",
                "cellphone" => $cellphone
            ];

            $this->log( message: json_encode( value: $response ) );
        }

        $cellphone = "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $cellphone );

        delete_user_meta( user_id: $user->ID, meta_key: "triiboId_phone", meta_value: $cellphone );
        update_user_meta( user_id: $user->ID, meta_key: "_triibo_phone",  meta_value: $cellphone );

        if ( isset( $response[ "uid" ] ) )
            update_user_meta( user_id: $user->ID, meta_key: "_triibo_id", meta_value: $response[ "uid" ] );

        echo json_encode( value: [ "success"  => true ] );
        die();
    }

    /**
     * Create or update user triibo
     *
     * @since 2.2.0     Add get_user(Node) after change of update_user_mkt (Gateway)
     * @since 1.1.0     Change 'Null Coalescing Operator' to 'Elvis Operator'
     * @since 1.0.0
     *
     * @param string    $cellphone
     * @param string    $email
     * @param string    $name
     * @param string    $nick
     * @param null|bool $terms
     *
     * @return array
     */
    private function create_or_update_user_triibo( string $cellphone, string $email, string $name = "", string $nick = "", ?bool $terms = null ) : array
    {
        $name     = $name ?: reset( array: explode( separator: "@", string: $email ) );
        $nick     = $nick ?: $name;

        $response = $this->node->get_user( cellphone: $cellphone );

        if ( !$response[ "success" ] )
            return $response;

        $user     = $response[ "data" ][ "userInfo" ][ "user" ];

        return $this->gate->update_user_mkt( user: $user, email: $email, action: "UPDATE", name: $name, nick: $nick, terms: $terms );
    }

    /**
     * Create marketplace user
     *
     * @since 1.0.0
     *
     * @param string|null   $uid
     * @param string        $cellphone
     * @param string        $email
     * @param string        $passw
     *
     * @return array
     */
    private function create_user_marketplace( string $uid, string $cellphone, string $email, string $passw ) : array
    {
        $cellphone = "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $cellphone );
        $name      = reset( array: explode( separator: "@", string: $email ) );

        $user_id   = wp_insert_user( [
            "user_login"    => $uid ?? $email,
            "user_pass"     => wp_hash_password( password: "Triibo@" . date( format: "Y" ) . "!" ),
            "user_nicename" => $name,
            "user_email"    => $email,
            "display_name"  => $name,
            "nickname"      => $name,
            "first_name"    => $name,
            "last_name"     => "",
        ] );

        if ( !is_wp_error( thing: $user_id ) )
        {
            wp_set_password( password: $passw, user_id: $user_id );

            add_user_meta( user_id: $user_id, meta_key: "_triibo_phone", meta_value: $cellphone );

            if ( $uid )
                add_user_meta( user_id: $user_id, meta_key: "_triibo_id", meta_value: $uid );

            $user = get_user_by( field: "id", value: $user_id );

            $user->set_role( role: "subscriber" );

            if ( class_exists( class: "WooCommerce" ) )
                $user->set_role( role: "customer" );
        }

        return $this->login_by_triibo( field: "id", value: $user_id );
    }

    /**
     * Log in to Mkt, according to $field passed
     *
     * @since 1.0.0
     *
     * @param string        $field
     * @param string|int    $value
     *
     * @return array
     */
    private function login_by_triibo( string $field, string|int $value ) : array
    {
        $user = get_user_by( field: $field, value: $value );

        if ( !is_wp_error( thing: $user ) )
        {
            wp_clear_auth_cookie();
            wp_set_current_user( id: $user->ID );
            wp_set_auth_cookie( user_id: $user->ID, remember: true );

            $result = [
                "success"  => true,
                "redirect" => home_url( path: "/my-account" ),
            ];
        }
        else
        {
            $result = [
                "success" => false,
                "error"   => [
                    "message" => "Error login",
                    "code"    => 500,
                ],
            ];
        }

        return $result;
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

    /**
     * Get erro code by message
     *
     * @since 2.2.0
     *
     * @param string  $error
     *
     * @return int
     */
    private function get_error_code( string $error ) : int
    {
        return match ( $error )
        {
            "Invalid code!"                        => 1006,
            "Code expired! - errorCode: 1007"      => 1007,
            "Invalid cellphone! - errorCode: 1009" => 1009,
            default                                => 5000,
        };
    }

    /**
     * Set the id
     *
     * @since 2.2.1
     *
     * @param string    $id
     *
     * @return void
     */
    public function set_id( string $id = TRIIBO_AUTH_ID ) : void
    {
        $this->id = $id;
    }
}