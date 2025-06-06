<?php

/**
 * Register all actions and filters for the plugin
 *
 * @author 	Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 2.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout the plugin, and register them with the WordPress API.
 * Call the run function to execute the list of actions and filters.
 */
class Triibo_Api_Services_Loader
{
	/**
	 * WP Actions.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected array $actions;

	/**
	 * WP filters.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected array $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->actions = [];
		$this->filters = [];
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$hook 			The name of the WordPress action that is being registered
	 * @param object 	$component 		A reference to the instance of the object on which the action is defined
	 * @param string 	$callback 		The name of the function definition on the $component
	 * @param int 		$priority 		Optional. The priority at which the function should be fired. Default is 10
	 * @param int 		$accepted_args 	Optional. The number of arguments that should be passed to the $callback. Default is 1
	 *
	 * @return void
	 */
	public function add_action( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ) : void
	{
		$this->actions = $this->add(
			hooks        : $this->actions,
			hook         : $hook,
			component    : $component,
			callback     : $callback,
			priority     : $priority,
			accepted_args: $accepted_args
		);
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$hook 			The name of the WordPress filter that is being registered
	 * @param object 	$component 		A reference to the instance of the object on which the filter is defined
	 * @param string 	$callback 		The name of the function definition on the $component
	 * @param int 		$priority 		Optional. The priority at which the function should be fired. Default is 10
	 * @param int 		$accepted_args 	Optional. The number of arguments that should be passed to the $callback. Default is 1
	 *
	 * @return void
	 */
	public function add_filter( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ) : void
	{
		$this->filters = $this->add(
			hooks        : $this->filters,
			hook         : $hook,
			component    : $component,
			callback     : $callback,
			priority     : $priority,
			accepted_args: $accepted_args
		);
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single collection.
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$hooks 			The collection of hooks that is being registered (that is, actions or filters)
	 * @param string 	$hook 			The name of the WordPress filter that is being registered
	 * @param object 	$component 		A reference to the instance of the object on which the filter is defined
	 * @param string 	$callback 		The name of the function definition on the $component
	 * @param int 		$priority 		The priority at which the function should be fired
	 * @param int 		$accepted_args 	The number of arguments that should be passed to the $callback
	 *
	 * @return array 	The collection of actions and filters registered with WordPress
	 */
	private function add( array $hooks, string $hook, object $component, string $callback, int $priority, int $accepted_args ) : array
	{
		$hooks[] = [
			"hook"          => $hook,
			"component"     => $component,
			"callback"      => $callback,
			"priority"      => $priority,
			"accepted_args" => $accepted_args
		];

		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run() : void
	{
		foreach ( $this->filters as $hook )
		{
			add_filter(
				hook_name    : $hook[ "hook" ],
				callback     : [
					$hook[ "component" ],
					$hook[ "callback"  ]
				],
				priority     : $hook[ "priority" ],
				accepted_args: $hook[ "accepted_args" ]
			);
		}

		foreach ( $this->actions as $hook )
		{
			add_action(
				hook_name    : $hook[ "hook" ],
				callback     : [
					$hook[ "component" ],
					$hook[ "callback"  ]
				],
				priority     : $hook[ "priority" ],
				accepted_args: $hook[ "accepted_args" ]
			);
		}
	}
}
