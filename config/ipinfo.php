<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Configuration for ipinfo module.
 * 
 * @author Oleh Burkhay <atma@atmaworks.com>
 */
return array(
	/**
     * ipinfodb key for remote API access
	 * Can be registered at http://ipinfodb.com/account.php
	 */
	'key' => 'your ipinfodb.com API key',
	'cache_time' => 3600*24*30,
    'default_ip' => '80.247.32.206',
    'auto_localize' => true,
);