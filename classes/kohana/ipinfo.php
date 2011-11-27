<?php defined('SYSPATH') or die('No direct script access.');

/*
 * Main Ip to location class
 * 
 * @author Oleh Burkhay <atma@atmaworks.com>
 */

abstract class Kohana_IpInfo
{
    protected $data;
    
    public static function factory($ip = null)
    {
        return new IpInfo($ip);
    }
    
    protected function _set($ip = null)
    {
        $this->config = Kohana::$config->load('ipinfo');
        // do quick IPv4 check
        if ($ip === null OR !preg_match("/\b(?:\d{1,3}\.){3}\d{1,3}\b/", $ip))
            $ip = Request::$client_ip;
        if (strpos($ip, '127') === 0 OR strpos($ip, '192') === 0 OR strpos($ip, '10.') === 0)
            $ip = $this->config['default_ip'];
        
        // Remove old entries
        if (rand(1, 1000) === 1)
        {
            $query = DB::query(Database::DELETE, 'DELETE FROM ipinfo WHERE lastCheck = :timestamp');
            $query->param(':timestamp', time() - $this->config['cache_time'])->execute();
        }
        
        $query = DB::query(Database::SELECT, 'SELECT * FROM ipinfo WHERE ipAddress = :ip');
        $query->param(':ip', $ip);
        $ip_data = $query->as_assoc()->execute();
        if ($ip_data->count() === 0)
        {
            $response = Request::factory(
                'http://api.ipinfodb.com/v3/ip-city/?key='
                .$this->config['key'].'&ip='.$ip.'&format=json'
            )->execute();
            if ($response->status() != 200)
                return false;
            $data = json_decode($response->body(), true);
            $this->data = $data;
            
            if ($data['statusCode'] !== 'OK')
                return false;
            
            $data['lastCheck'] = time();
            $query = DB::insert('ipinfo')
                ->columns(array('ipAddress', 'countryCode', 'countryName', 'regionName', 'cityName', 'zipCode', 'latitude', 'longitude', 'timeZone', 'lastCheck'))
                ->values(array($data['ipAddress'], $data['countryCode'], $data['countryName'], $data['regionName'], $data['cityName'], $data['zipCode'], $data['latitude'], $data['longitude'], $data['timeZone'], $data['lastCheck']));
            
            list($insert_id, $affected_rows) = $query->execute();
        }
        else
        {
            $this->data = $ip_data[0];
        }
        if ($this->config['auto_localize'])
            $this->_localize();
    }
    
    public function __construct($ip = null) {
        $this->_set($ip);
    }
    
    public function country_code()
    {
        return isset($this->data['countryCode']) ? $this->data['countryCode'] : null;
    }
    public function country_name()
    {
        return isset($this->data['countryName']) ? $this->data['countryName'] : null;
    }
    public function region()
    {
        return isset($this->data['regionName']) ? $this->data['regionName'] : null;
    }
    public function city()
    {
        return isset($this->data['cityName']) ? $this->data['cityName'] : null;
    }
    public function zip()
    {
        return (isset($this->data['zipCode']) AND $this->data['zipCode'] !== '-') ? $this->data['zipCode'] : null;
    }
    public function latitude()
    {
        return isset($this->data['latitude']) ? $this->data['latitude'] : null;
    }
    public function longitude()
    {
        return isset($this->data['longitude']) ? $this->data['longitude'] : null;
    }
    public function time_zone()
    {
        return isset($this->data['timeZone']) ? $this->data['timeZone'] : null;
    }
    public function data()
    {
        return $this->data;
    }
    
    // set or get the IP address
    public function ip($ip = null)
    {
        if ($ip === null)
            return isset($this->data['ipAddress']) ? $this->data['ipAddress'] : null;
        else
        {
            $this->_set($ip);
            return $this->ip();
        }
    }
    
    protected function _localize($lang = null)
    {
        if ($lang === null)
            $lang = I18n::$lang;
        if (strpos($lang, '-') === false)
        {
            $language = $lang;
            $region = strtoupper($lang);
        }
        else
        {
            $lang = explode('-', $lang);
            $language = $lang[0];
            $region = strtoupper($lang[1]);
        }
        
        // supported languages @link https://spreadsheets.google.com/pub?key=p9pdwsai2hDMsLkXsoM05KQ&gid=1
        $supported_langs = array(
            'ar', 'eu', 'bg', 'bn', 'ca', 'ss', 'da', 'de', 'el', 'en', 'es', 'eu', 'fa', 'fi', 'fil', 'fr', 'gl', 'gu', 'hi', 'hr', 'hu', 'id', 'id', 'iw', 'ja', 'kn', 'ko', 'lt', 'lv', 'mr', 'nl', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'tl', 'ta', 'te', 'th', 'tr', 'uk', 'vi', 'zh'
        );
        // TODO resolve zh-TW, zh-CH, etc
        if (!in_array($language, $supported_langs))
            return false;
        $query = DB::query(Database::SELECT, 'SELECT * FROM ipinfo_localization WHERE lang = :lang AND canonical_country = :country AND canonical_region = :region AND canonical_city = :city');
        $query->parameters(array(
            ':lang' => $language,
            ':country' => $this->data['countryName'],
            ':region' => $this->data['regionName'],
            ':city' => $this->data['cityName']
        ));
        $result = $query->as_assoc()->execute();
        $total = $result->count();
        $data_trans = array();
        
        if ($total === 0 AND $this->data['longitude'] AND $this->data['latitude'])
        {
            $response = Request::factory('http://maps.googleapis.com/maps/api/geocode/json?latlng='.$this->data['latitude'].','.$this->data['longitude'].'&sensor=false&language='.$language)->execute();
            if ($response->status() != 200)
                return false;
            $data = json_decode($response->body(), true);
            if ($data['status'] != 'OK' OR !isset($data['results']))
                return false;
            $data = $data['results'];
            
            foreach ($data as $r)
            {
                if (isset($r['address_components']))
                {
                    foreach ($r['address_components'] as $c) {
                        if (count($r['types']) === 2 AND in_array('political', $c['types']))
                        {
                            if (in_array('country', $c['types']))
                            {
                                $data_trans['country'] = $c['long_name'];
                            }
                            elseif (in_array('administrative_area_level_1', $c['types']))
                            {
                                $data_trans['region'] = $c['long_name'];
                            }
                            elseif (in_array('locality', $c['types']))
                            {
                                // russian city names fix
                                if ($language == 'ru' AND mb_strpos($c['long_name'], 'город') === 0)
                                    $data_trans['city'] = mb_substr ($c['long_name'], 6);
                                else
                                    $data_trans['city'] = $c['long_name'];
                            }
                        }
                        
                    }
                }
                if (count($data_trans) === 3)
                    break;
            }
            $data_trans['country'] = isset($data_trans['country']) ? $data_trans['country'] : '';
            $data_trans['region'] = isset($data_trans['region']) ? $data_trans['region'] : '';
            $data_trans['city'] = isset($data_trans['city']) ? $data_trans['city'] : '';
            
            $query = DB::insert('ipinfo_localization')
                ->columns(array('lang', 'canonical_country', 'canonical_region', 'canonical_city', 'translated_country', 'translated_region', 'translated_city'))
                ->values(array($language, $this->data['countryName'], $this->data['regionName'], $this->data['cityName'], $data_trans['country'], $data_trans['region'], $data_trans['city']));
            $query->execute();
            
            $this->data['countryName'] = $data_trans['country'];
            $this->data['regionName'] = $data_trans['region'];
            $this->data['cityName'] = $data_trans['city'];
            
            return true;
        }
        elseif ($total > 0)
        {
            $data = $result[0];
            $this->data['countryName'] = $data['translated_country'];
            $this->data['regionName'] = $data['translated_region'];
            $this->data['cityName'] = $data['translated_city'];
            
            return true;
        }
        else
        {
            return false;
        }
    }
}
