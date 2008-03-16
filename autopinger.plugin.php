<?php
class Autopinger extends Plugin
{
  function info()
  {
    return array(
      'name'=>'Autopinger',
      'version'=>'0.1',
      'url' => 'http://habariproject.org/',
      'author' => 'Habari Community',
      'authorurl' => 'http://habariproject.org/',
      'license' => 'Apache License 2.0',
      'description' => 'Adds support Pingback 1.0 methods to the XML-RPC server.',
      'copyright' => '2008'
    );
  }

	function action_update_check() {
  			Update::add( 'Autopinger', 'c515ef39-b387-33e4-f14f-40628f11415b',  $this->info->version ); 
  }

   public function action_post_status_published($post)
    {
            if ( $post->status == Post::status( 'published' ) ) {
            CronTab::add_single_cron( 'ping update sites', 'ping_sites', time(), 'Ping update sites.' );
            EventLog::log('Crontab added', 'info', 'default', null, null );
        }
    }
 
    public function filter_ping_sites($result)
    {
        	$rpc = new XMLRPCClient('http://technorati.com/rpc/ping', 'weblogUpdates');
        	$ping = $rpc->ping('Habari Sandbox', 'http://sandbox.p0ggel.org/habari');
        	EventLog::log('Ping sent via XMLRPC.', 'info', 'default', null, $result );
        	return $result;
    }
}
?>