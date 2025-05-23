<?php

/**
 * The file that defines the Triibo Gateway API
 *
 * A class definition that includes attributes and functions used for access the Triibo Gateway API.
 *
 * @author 	Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 2.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Api_Gateway extends Triibo_Api
{
    /**
     * GROUP/NAME composition of $header.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const GP_NM         = "triibo-webapp";

    /**
     * Default header of all calls. Can be incremented.
     *
     * @since 1.0.0
     *
     * @var array
     */
    const HEADER        = [ "Content-type: application/json" ];

    /**
     * Endpoint for api /getChannelToken_v1.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const CHN_TKN_ID    = "/getChannelToken_v1";

    /**
     * Endpoint for api /creditPoints.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const CREDIT_POINTS = "/creditPoints";

    /**
     * Endpoint for api /login.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const LOGIN         = "/login";

    /**
     * Endpoint for api /setUserInfo_v1.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const SET_USER_INFO = "/setUserInfo_v1";

    /**
     * Endpoint for api /authSMS_v1.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const AUTH_SMS      = "/authSMS_v1";

    /**
     * Endpoint for api /validateSMS_v1.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const VAL_SMS       = "/validateSMS_v1";

    /**
     * Endpoint for api /getUserInfo_v1.
     *
     * @since 1.1.0
     *
     * @var string
     */
    const GET_USER_INFO = "/getUserInfo_v1";

    /**
     * Endpoint for api /insertUserOrgs.
     *
     * @since 1.3.0
     *
     * @var string
     */
    const ADD_USER_ORG  = "/insertUserOrgs";

    /**
     * Endpoint for api /removeUserOrgs.
     *
     * @since 1.3.0
     *
     * @var string
     */
    const DEL_USER_ORG  = "/removeUserOrgs";

    /**
	 * Internal ID for log.
	 *
	 * @since 2.0.0 	Same visibility as the parent
	 * @since 1.11.0 	Updated string format.
	 * @since 1.0.0
     *
	 * @var string
	 */
	protected string $id = "triibo-api-gateway";

	/**
	 * Environment hml/prod.
     *
	 * @since 2.0.0 	Same visibility as the parent
	 * @since 1.0.0
	 *
	 * @var null|string
	 */
	protected ?string $env = null;

	/**
	 * Status of this api.
	 *
	 * @since 2.0.0 	Same visibility as the parent
	 * @since 1.0.0
	 *
	 * @var null|string
	 */
	protected ?string $status = null;

	/**
     * Token.
     *
	 * @since 2.0.0 	Same visibility as the parent
     * @since 1.0.0
	 *
	 * @var null|string
	 */
	protected ?string $token = null;

	/**
	 * Endpoint hml/prod.
	 *
	 * @since 2.0.0
	 *
	 * @var null|string
	 */
	protected ?string $url = null;

	/**
	 * Header for api calls.
	 *
	 * @since 2.0.0 	Same visibility as the parent
	 * @since 1.0.0
	 *
	 * @var null|array
	 */
    protected ?array $header = null;

    /**
     * Channel ID.
     *
     * @since 1.0.0
     *
	 * @var null|string
     */
    private ?string $chn_id = null;

    /**
     * Channel Token.
     *
     * @since 1.0.0
     *
	 * @var null|string
     */
    private ?string $chn_tkn = null;

    /**
     * Construct.
     *
     * @since 1.10.0    Removed $log class property
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->status = get_option( option: "triibo_api_services_gate_status" );
        $this->env    = get_option( option: "triibo_api_services_gate_env"    );

        if ( $this->status() )
        {
            $this->url     = "https://triibo-api-gateway" . ( $this->env() ? "-hml" : "" ) . ".triibo.com.br";

            $opt_token     = "triibo_api_services_gate_hml_tkn";
            $opt_chn_id    = "triibo_api_services_gate_hml_chn_id";
            $opt_chn_tkn   = "triibo_api_services_gate_hml_chn_tkn";

            if ( ! $this->env() )
            {
                $opt_token   = "triibo_api_services_gate_prd_tkn";
                $opt_chn_id  = "triibo_api_services_gate_prd_chn_id";
                $opt_chn_tkn = "triibo_api_services_gate_prd_chn_tkn";
            }

            $this->token   = get_option( option: $opt_token   );
            $this->chn_id  = get_option( option: $opt_chn_id  );
            $this->chn_tkn = get_option( option: $opt_chn_tkn );

            $this->header  = [
                "channelName"   => self::GP_NM,
                "channelGroup"  => self::GP_NM,
                "apiToken"      => $this->token,
                "channelId"     => $this->chn_id,
                "sessionId"     => "001",
                "transactionId" => "002",
            ];
        }
    }

    /**
	 * Make request.
     *
     * @since 2.0.0     Refactored
     * @since 1.0.0
     *
     * @param string    $endpoint
     * @param array     $params
     *
     * @return array
     */
    private function call( string $endpoint, array $params ) : array
    {
		if ( ! $this->status() )
		{
			$message = "API is disabled.";

			$this->log(
				is_error: true,
				level   : "critical",
				message : $message,
			);

			return [
				"code"    => 401, // "500.4",
				"success" => false,
				"message" => "API está desabilitada.",
				"error"   => $message,
				"data"    => null,
			];
		}

		return $this->exec(
			endpoint: $this->url . $endpoint,
			header  : self::HEADER,
			params  : $params
		);
    }

    /**
     * Get hash for channel token.
     *
     * @since   1.0.0
     *
     * @param   null|string     $uid
     *
     * @return  array
     */
    private function hash( ?string $uid = null ) : array
    {
        $passPhrase = floor( num: rand( min: 1000000000, max: 9999999999 ) ) . $uid . floor( num: rand( min: 1000000000, max: 9999999999 ) );

        $challenge1 = md5( string: $passPhrase . $this->token   );
        $challenge2 = md5( string: $challenge1 . $this->chn_tkn );

        return [
            "passPhrase" => $passPhrase,
            "challenge"  => $challenge2,
        ];
    }

    /**
     * Process contact list.
     *
     * @since 1.1.6
     *
     * @param array     $contact
     * @param string    $email
     * @param string    $action
     *
     * @return array
     */
    private function process_contact_list( array $contact, string $email, string $action ) : array
    {
        $key      = array_search( needle: "emailMarketplace", haystack: array_column( array: $contact, column_key: "type" ) );
        $verified = false;

        // email marketplace
        if ( $key !== false )
        {
            $verified        = ( $email == $contact[ $key ][ "value" ] )
                                ? ( $contact[ $key ][ "verified" ]
                                    ? true
                                    : false )
                                : false;

            $contact[ $key ] = [
                "type"     => "emailMarketplace",
                "value"    => $email,
                "verified" => $verified,
            ];
        }
        else
        {
            array_push( array: $contact, values: [
                "type"     => "emailMarketplace",
                "value"    => $email,
                "verified" => false
            ] );
        }

        if ( in_array( needle: $action, haystack: [ "UPDATE", "CREATE" ] ) )
        {
            $key = array_search( needle: "email", haystack: array_column( array: $contact, column_key: "type" ) );

            // email
            if ( $key === false )
            {
                array_push( array: $contact, values: [
                    "type"     => "email",
                    "value"    => $email,
                    "verified" => $verified
                ] );
            }
        }

        return $contact;
    }

    /**
     * Process contact.
     *
     * @since 1.1.6
     *
     * @param array     $terms
     *
     * @return array
     */
    private function process_terms( array $terms ) : array
    {
        $option_id   = ( $this->env() ) ? "triibo_api_services_gate_hml_optin_id"  : "triibo_api_services_gate_prd_optin_id";
        $option_text = ( $this->env() ) ? "triibo_api_services_gate_hml_optin_txt" : "triibo_api_services_gate_prd_optin_txt";

        $term        = [
            "accept"         => true,
            "dateAcceptance" => time() * 1000,
            "optInId"        => get_option( option: $option_id   ),
            "type"           => get_option( option: $option_text ),
            "version"        => 1
        ];

        if ( count( value: $terms ) == 0 )
        {
            $terms = [ $term ];
        }
        else
        {
            $key = array_search( needle: $option_id, haystack: array_column( array: $terms, column_key: "optInId" ) );

            if ( $key !== false )
            {
                $terms[ $key ][ "accept"         ] = true;
                $terms[ $key ][ "dateAcceptance" ] = time() * 1000;
            }
            else
            {
                array_push( array: $terms, values: [ $term ] );
            }
        }

        return $terms;
    }

    /**
     * Process org action.
     *
     * @since 2.0.0     Refactored log
     * @since 1.4.2     Removed parameter $action
     * @since 1.3.0
     *
     * @param string    $uid
     * @param string    $org_id
     *
     * @return array
     */
    private function process_org( string $endpoint, string $uid, string $org_id ) : array
    {
        $opt_sys_uid = ( $this->env() ) ? "triibo_api_services_gate_hml_sys_uid" : "triibo_api_services_gate_prd_sys_uid";
        $sys_uid     = get_option( option: $opt_sys_uid );

        $response    = $this->get_channel_token( uid: $sys_uid );

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $this->log(
                is_error: true,
                level   : "critical",
                message : "Error to get channel token.",
                context :   [
                    "endpoint" => $endpoint,
                    "uid"      => $uid,
                    "org_id"   => $org_id,
                    "response" => $response,
                ]
            );

            return $response;
        }

        $aux         = [
            "channelTokenId" => $response[ "data" ][ "success" ][ "newToken" ][ "channelTokenId" ],
            "uId"            => $sys_uid,
        ];

        $params      = [
            "triiboHeader" => array_merge( $this->get_header(), $aux ),
            "orgID"        => $org_id,
            "uId"          => $uid,
        ];

        $response    = $this->call( endpoint: $endpoint, params: $params );

        $error       = ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) );

        $this->log(
            is_error: $error,
            level   : $error ? "critical" : "info",
            message : $error ? "Error to process org." : "Org processed.",
            context :   [
                "endpoint" => $endpoint,
                "uid"      => $uid,
                "org_id"   => $org_id,
                "response" => $response,
            ]
        );

        return $response;
    }

    /**
     * Get channel token.
     *
     * @since 1.0.0
     *
     * @param null|string   $uid
     *
     * @return array
     */
    private function get_channel_token( ?string $uid = null ) : array
    {
        $hash   = $this->hash( uid: $uid );

        $aux    = [
            "uId"        => $uid,
            "passPhrase" => $hash[ "passPhrase" ],
            "challenge"  => $hash[ "challenge"  ]
        ];

        $header = array_merge( $this->get_header(), $aux );

        return $this->call( endpoint: self::CHN_TKN_ID, params: [ "triiboHeader" => $header ] );
    }

    /**
     * Credits points to the user.
     *
     * @since 2.0.0     Refactored log
     * @since 1.7.0     Added new plans
     * @since 1.1.2     Added param $event
     * @since 1.0.0
     *
     * @param string    $triibo_id
     * @param float     $value
     * @param string    $event
     *
     * @return array
     */
    public function credit_points( string $triibo_id, float $value, string $event ) : array
    {
        $events = apply_filters( hook_name: "triibo_events_valid_events", value: [] );

        if ( !in_array( needle: $event, haystack: $events ) )
        {
            return [
                "success" => false,
                "event"   => $event,
                "message" => "Evento não suportado."
            ];
        }

        $response = $this->get_channel_token();

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $this->log(
                is_error: true,
                level   : "critical",
                message : "Error to get channel token.",
                context :   [
                    "triibo_id" => $triibo_id,
                    "value"     => $value,
                    "event"     => $event,
                    "response"  => $response,
                ]
            );

            return $response;
        }

        $aux    = [ "channelTokenId" => $response[ "data" ][ "success" ][ "newToken" ][ "channelTokenId" ] ];

        $params = [
            "triiboHeader"   => array_merge( $this->get_header(), $aux ),
            "triiboId"       => "+{$triibo_id}@sms.triibo.com.br",
            "eventType"      => $event,
            "recursionValue" => $value,
        ];

        return $this->call( endpoint: self::CREDIT_POINTS, params: $params );
    }

    /**
     * Update marketplace user.
     *
     * @since 2.0.0     Refactored log
     * @since 1.5.0     Change param $cellphone to $user
     * @since 1.1.6     Add param $terms
     * @since 1.1.5     ContactList email/emailMarketplace no longer verified
     * @since 1.1.1     Fix array contactList
     * @since 1.1.0     Add params $name and $nick
     * @since 1.0.0
     *
     * @param array         $user
     * @param string        $email
     * @param string        $action
     * @param string        $name
     * @param string        $nick
     * @param null|bool     $terms
     *
     * @return array
     */
    public function update_user_mkt( array $user, string $email, string $action, string $name = "", string $nick = "", ?bool $terms = null ) : array
    {
        $uid      = $user[ "uId" ];

        $contact  = $this->process_contact_list( contact: $user[ "contactList" ], email: $email, action: $action );

        $response = $this->get_channel_token( uid: $uid );

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $this->log(
                is_error: true,
                level   : "critical",
                message : "Error to get channel token.",
                context :   [
                    "uid"      => $uid,
                    "response" => $response,
                ]
            );

            return $response;
        }

        $aux      = [
            "channelTokenId" => $response[ "data" ][ "success" ][ "newToken" ][ "channelTokenId" ],
            "uId"            => $uid
        ];

        $params   = [
            "triiboHeader"    => array_merge( $this->get_header(), $aux ),
            "queryPartnerAPI" => [ "setUserInfo" ],
            "userInfo"        => [
                "syncType"    => "override",
                "uId"         => $uid,
                "name"        => ( $user[ "name" ] && $user[ "name" ] != "Triiber" ) ? $user[ "name" ] : ( $name ?? "Triiber" ),
                "aliasName"   => ( $user[ "aliasName" ] ) ? $user[ "aliasName" ] : $nick,
                "contactList" => [ ... $contact ],
            ],
        ];

        if ( $terms )
        {
            $user_terms                          = ( isset( $user[ "optInList" ] ) ) ? $user[ "optInList" ] : [];
            $params[ "userInfo" ][ "optInList" ] = [ ... $this->process_terms( terms: $user_terms ) ];
        }

        $response = $this->call( endpoint: self::SET_USER_INFO, params: $params );

        $error    = ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) );

        $response[ "uid" ] = $uid;

        $this->log(
            is_error: $error,
            level   : $error ? "critical" : "info",
            message : $error ? "Error to update user." : "User updated.",
            context :   [
                "uid"      => $uid,
                "response" => $response,
            ]
        );

        return $response;
    }

    /**
     * Send code SMS.
     *
     * @deprecated 2.0.0    Use Triibo_Api_Node::auth() && Triibo_Api_Node::auth_code() instead
     * @see                 Triibo_Api_Node::auth() && Triibo_Api_Node::auth_code()
     *
     * @since 1.1.0
     *
     * @param string    $cellphone
     *
     * @return array
     */
    public function auth_sms( string $cellphone ) : array
    {
        $response = $this->get_channel_token();

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $this->log(
                is_error: true,
                level   : "critical",
                message : "Error to get channel token.",
                context :   [
                    "cellphone" => $cellphone,
                    "response"  => $response,
                ]
            );

            return $response;
        }

        $cellphone = "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $cellphone );

        $aux       = [ "channelTokenId" => $response[ "data" ][ "success" ][ "newToken" ][ "channelTokenId" ] ];

        $params    = [
            "triiboHeader" => array_merge( $this->get_header(), $aux ),
            "platform"     => "web",
            "cellphone"    => $cellphone,
        ];

        $response  = $this->call( endpoint: self::AUTH_SMS, params: $params );

        $error     = ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) );

        $this->log(
            is_error: $error,
            level   : $error ? "critical" : "info",
            message : $error ? "Error to send code." : "Code sent.",
            context :   [
                "cellphone" => $cellphone,
                "response"  => $response,
            ]
        );

        return $response;
    }

    /**
     * Validates code SMS.
     *
     * @deprecated 2.0.0    Use Triibo_Api_Node::validate_sms() instead
     * @see                 Triibo_Api_Node::validate_sms()
     *
     * @since 1.1.0
     *
     * @param string    $code
     * @param string    $cellphone
     * @param string    $trans_id
     *
     * @return array
     */
    public function validate_sms( string $code, string $cellphone, string $trans_id ) : array
    {
        $response = $this->get_channel_token();

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $this->log(
                is_error: true,
                level   : "critical",
                message : "Error to get channel token.",
                context :   [
                    "code"      => $code,
                    "cellphone" => $cellphone,
                    "trans_id"  => $trans_id,
                ]
            );

            return $response;
        }

        $cellphone = "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $cellphone );

        $aux       = [ "channelTokenId" => $response[ "data" ][ "success" ][ "newToken" ][ "channelTokenId" ] ];

        $params    = [
            "triiboHeader"  => array_merge( $this->get_header(), $aux ),
            "code"          => $code,
            "cellphone"     => $cellphone,
            "transactionId" => $trans_id,
        ];

        $response  = $this->call( endpoint: self::VAL_SMS, params: $params );

        $error     = ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) );

        $this->log(
            is_error: $error,
            level   : $error ? "critical" : "info",
            message : $error ? "Error to validate code." : "Code validated.",
            context :   [
                "code"      => $code,
                "cellphone" => $cellphone,
                "trans_id"  => $trans_id,
            ]
        );

        return $response;
    }

    /**
     * Get user info.
     *
     * @since 2.0.0     Refactored log
     * @since 1.1.7     Add $is_uid param, change $cellphone param to $data
     * @since 1.1.0
     *
     * @param string    $data
     *
     * @return array
     */
    public function user_info( string $data, bool $is_uid = false ) : array
    {
        $response = $this->get_channel_token();

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $this->log(
                is_error: true,
                level   : "critical",
                message : "Error to get channel token.",
                context :   [
                    "data"     => $data,
                    "is_uid"   => $is_uid,
                    "response" => $response,
                ]
            );

            return $response;
        }

        if ( $is_uid )
        {
            $user_info = [ "uId" => $data ];
        }
        else
        {
            $user_info = [
                "contactList" => [
                    [
                        "type"  => "cellPhone",
                        "value" => "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $data )
                    ]
                ]
            ];
        }

        $aux      = [ "channelTokenId" => $response[ "data" ][ "success" ][ "newToken" ][ "channelTokenId" ] ];

        $params   = [
            "triiboHeader"    => array_merge( $this->get_header(), $aux ),
            "queryPartnerAPI" => [ "getUserInfo" ],
            "userInfo"        => $user_info,
        ];

        $response = $this->call( endpoint: self::GET_USER_INFO, params: $params );

        $error    = ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) );

        $this->log(
            is_error: $error,
            level   : $error ? "critical" : "info",
            message : $error ? "Error to get user info." : "User info retrieved.",
            context :   [
                "data"     => $data,
                "is_uid"   => $is_uid,
                "response" => $response,
            ]
        );

        return $response;
    }

    /**
     * Add org to user.
     *
     * @since 1.3.0
     *
     * @param string    $uid
     * @param string    $org_id
     *
     * @return array
     */
    public function add_org( string $uid, string $org_id ) : array
    {
        return $this->process_org( endpoint: self::ADD_USER_ORG, uid: $uid, org_id: $org_id );
    }

    /**
     * Remove org from user.
     *
     * @since 1.3.0
     *
     * @param string    $uid
     * @param string    $org_id
     *
     * @return array
     */
    public function del_org( string $uid, string $org_id ) : array
    {
        return $this->process_org( endpoint: self::DEL_USER_ORG, uid: $uid, org_id: $org_id );
    }
}
