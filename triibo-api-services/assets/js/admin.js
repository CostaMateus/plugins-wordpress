( function ( $ )
{
	"use strict";

	function toggleFields( prefix, checked, env )
	{
		const fields = [
			// gateway
			"tkn", // php too
			"chn_id",
			"chn_tkn",
			"sys_uid",
			"optin_id",
			"optin_txt",

			// node
			"user",
			"pass",
			"auth_key",

			// php
			"key"
		];

		const envs = [ "hml", "prd" ];

		envs.forEach( currentEnv =>
		{
			const isCurrentEnvEnabled = checked === ( currentEnv === "hml" ? 1 : 2 );
			const shouldShow          = isCurrentEnvEnabled || checked === 3 && env === ( currentEnv === "hml" ? 1 : 2 );
			const shouldRequire       = isCurrentEnvEnabled && env;

			fields.forEach( field =>
			{
				const elementId = `#triibo_api_services_${prefix}_${currentEnv}_${field}`;
				const $element  = $( elementId );

				if ( $element.length )
				{
					$element.closest( "tr" ).toggle( shouldShow );
					$element.prop( "required", shouldRequire );
				}
			} );
		} );
	}

	function handler( service )
	{
		const statusId     = `#triibo_api_services_${service}_status`;
		const envId        = `#triibo_api_services_${service}_env`;
		const isEnabled    = $( statusId ).is( ":checked" );
		const isHomol      = $( envId    ).is( ":checked" );
		const checkedValue = isEnabled ? (isHomol ? 1 : 2) : 3;
		const envValue     = isHomol ? 1 : 2;

		toggleFields( service, checkedValue, ( isEnabled ? isHomol : envValue ) );
	}

	$( document ).on( "change", "#triibo_api_services_gate_env", function ()
	{
		const checked   = $( this ).is( ":checked" ) ? 1 : 2;
		const isEnabled = $( "#triibo_api_services_gate_status" ).is( ":checked" );

		toggleFields( "gate", checked, isEnabled );
	} );

	$( document ).on( "change", "#triibo_api_services_node_env", function ()
	{
		const checked   = $( this ).is( ":checked" ) ? 1 : 2;
		const isEnabled = $( "#triibo_api_services_node_status" ).is( ":checked" );

		toggleFields( "node", checked, isEnabled );
	} );

	$( document ).on( "change", "#triibo_api_services_php_env", function ()
	{
		const checked   = $( this ).is( ":checked" ) ? 1 : 2;
		const isEnabled = $( "#triibo_api_services_php_status" ).is( ":checked" );

		toggleFields( "php", checked, isEnabled );
	} );

	$( document ).on( "change", "#triibo_api_services_gate_status", function () { handler( "gate" ); } );
	$( document ).on( "change", "#triibo_api_services_node_status", function () { handler( "node" ); } );
	$( document ).on( "change", "#triibo_api_services_php_status",  function () { handler( "php"  ); } );

	$( document ).ready( function ()
	{
		handler( "gate" );
		handler( "node" );
		handler( "php"  );

		const els = $( "#triibo_api_services_gate_status" ).closest( "table" ).siblings( "h2" );

		els.each( ( i, e ) =>
		{
			if ( i > 0 )
				$( e ).css( {
					"border-top": "1px solid #CCC",
					"padding-top": "2rem"
				} );
		} );
	} );
} )( jQuery );
