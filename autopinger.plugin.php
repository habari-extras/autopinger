<?php
class Autopinger extends Plugin
{
  /**
   * Respond to plugin info request
   *
   * @return array Info about plugin
   */
  function info()
  {
    return array(
      'name'=>'Autopinger',
      'version'=>'0.3',
      'url' => 'http://habariproject.org/',
      'author' => 'Habari Community',
      'authorurl' => 'http://habariproject.org/',
      'license' => 'Apache License 2.0',
      'description' => 'Adds XML-RPC ping support.',
      'copyright' => '2008'
    );
  }

	/**
	 * Configure beacon information for the plugin
	 */
	function action_update_check() {
		Update::add( 'Autopinger', 'c515ef39-b387-33e4-f14f-40628f11415b',  $this->info->version );
  }

	/**
	 * When a post is published, add a cron entry to do pinging
	 *
	 * @param Post $post A post object whose status has been set to published
	 */
	public function action_post_publish_after( $post )
	{
		CronTab::add_single_cron( 'ping update sites', array( 'Autopinger', 'ping_sites' ), time(), 'Ping update sites.' );
		EventLog::log( 'Crontab added', 'info', 'default', null, null );
	}

	/**
	 * Do the ping on the cron filter "ping_sites"
	 *
	 * @param boolean $result The result of the cron job, false if failed
	 * @return boolean The result of the cron job, false if failed to get rescheduled
	 */
	public static function ping_sites($result)
	{
		$services = Options::get( 'autopinger__pingservices' );
		if(!is_array($services)) {
			EventLog::log('No pings sent - no services configured.');
			return false;
		}
		else {
			$count = 0;
			foreach($services as $service) {
				$rpc = new XMLRPCClient($service, 'weblogUpdates');
				$ping = $rpc->ping(Options::get('title'), Site::get_url('habari'));
				$count++;
			}
			EventLog::log("Ping sent via XMLRPC - pinged {$count} sites.", 'info', 'default', null, $result );
			return true;
		}
	}

	/**
	* Add actions to the plugin page for this plugin
	*
	* @param array $actions An array of actions that apply to this plugin
	* @param string $plugin_id The string id of a plugin, generated by the system
	* @return array The array of actions to attach to the specified $plugin_id
	*/
	public function filter_plugin_config($actions, $plugin_id)
	{
		if ($plugin_id == $this->plugin_id()){
			$actions[] = _t( 'Configure' );
		}

		return $actions;
	}

	/**
	* Respond to the user selecting an action on the plugin page
	*
	* @param string $plugin_id The string id of the acted-upon plugin
	* @param string $action The action string supplied via the filter_plugin_config hook
	*/
	public function action_plugin_ui($plugin_id, $action)
	{
		if ($plugin_id == $this->plugin_id()){
			switch ($action){
				case _t( 'Configure' ):
					$ui = new FormUI(strtolower(get_class($this)));
					$ping_services = $ui->append( 'textmulti', 'ping_services', 'option:autopinger__pingservices', _t( 'Ping Service URLs:' ) );
					$ui->append( 'submit', 'save', 'Save' );
					$ui->out();
					break;
			}
		}
	}


	/**
	 * Log ping requests to this site as a server
	 *
	 * @param array $params An array of incoming parameters
	 * @param XMLRPCServer $rpcserver The server object that received the request
	 * @return mixed The result of the request
	 */
	public function xmlrpc_weblogUpdates__ping($params, $rpcserver)
	{
		EventLog::log("Recieved ping via XMLRPC: {$params[0]} {$params[1]}", 'info', 'default' );
		return true;
	}

}
?>