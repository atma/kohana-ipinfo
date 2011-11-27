[ipinfodb.com](http://ipinfodb.com/) module for the kohana framework
====================================================================

Kohana module for doing IP to Location lookups using the free IP Info DB service.

Configuration
-------------
Before the begin obtain the API key from and put it to `config/ipinfo.php`.
    'key' => 'your ipinfodb.com API key',

By default IP information storing in a database and refreshes after `cache_time` in seconds
	'cache_time' => 3600*24*30,

Default fallback IP aka default city
    'default_ip' => '80.247.32.206',

Enable automatic location translation (uses Google geocoding) for the current `I18n::$lang`
    'auto_localize' => true,

Usage
-----

Get info for remote addres and store its in $info var
    $info = IpInfo::factory();

Get info for the custom IP
    $info = IpInfo::factory('xxx.xx.x.y');

