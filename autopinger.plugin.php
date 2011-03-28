<?php
class Autopinger extends Plugin
{

	/**
	 * When a post is published, add a cron entry to do pinging
	 *
	 * @param Post $post A post object whose status has been set to published
	 */
	public function action_post_status_published( $post)
	{
		if ( $post->status == Post::status( 'published' ) && $post->pubdate <= HabariDateTime::date_create() ) {
			CronTab::add_single_cron( 'ping update sites', array( 'Autopinger', 'ping_sites' ), HabariDateTime::date_create()->int, 'Ping update sites.' );
			EventLog::log( 'Crontab added', 'info', 'default', null, null );
		}
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
	* Respond to the user selecting an action on the plugin page
	*
	* @param string $plugin_id The string id of the acted-upon plugin
	* @param string $action The action string supplied via the filter_plugin_config hook
	*/
	public function configure()
	{
		$ui = new FormUI(strtolower(get_class($this)));
		$ping_services = $ui->append( 'textmulti', 'ping_services', 'option:autopinger__pingservices', _t( 'Ping Service URLs:' ) );
		$ui->append( 'submit', 'save', 'Save' );
		$ui->out();
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
		EventLog::log("Received ping via XMLRPC: {$params[0]} {$params[1]}", 'info', 'default' );
		return true;
	}

}
?>