<?php

/**
*
Uses proxy lists to find a good proxy by fetching given url
*/
class Proxy
{

	function __construct()
	{
		# code...
	}

	// http://api.best-proxies.ru/feeds/proxylist.txt?key=cWK3jNePG8750dIuKSAEkAKd&type=socks5&cex=1&response=300&limit=0

	// LATEST: 

	protected $best_prxoxy_url = 'http://proxyrack.net/rotating/megaproxy/';

	public function find_alive ($url, $validation_str, $find_good_proxy_tries = 30)
	{

		$proxy_list_url = $this->best_prxoxy_url;

	    $local_proxy_file = realpath(dirname(__FILE__)).'/lists/tmp.proxy.txt';
	    $proxy_array = $this->renew_proxylist($proxy_list_url, $local_proxy_file);

	    $proxy = $proxy_array[rand(0,count($proxy_array)-1)];

	    $good_proxy_indicator = 0;
	    //$ip = trim(get('http://www.ipmango.com/api/myip'));
	    //echo $ip; die;
	    $repeats_counter = 0;

	    while ($good_proxy_indicator == 0)
	    {	
	    	//echo 'here';
	    	//echo $proxy;
	        $curl = $this->get($url,false ,$proxy);

	        if ($curl === false)
	        {
	            $proxy_array = $this->remove_proxy($proxy, $local_proxy_file);

	            if (count($proxy_array)<20)
	                $proxy_array = $this->renew_proxylist($proxy_list_url, $local_proxy_file, $proxy_array);

	            $proxy = $proxy_array[rand(0,count($proxy_array)-1)];
	        }
	        elseif ($curl == 'site_error')
	        {
	        	return false;
	        }
	        elseif ($curl !== false and strstr($curl['content'], $validation_str))
	        	$good_proxy_indicator++;

	        $repeats_counter++;
	        if ($repeats_counter >= $find_good_proxy_tries) return false; //No good proxy found after [$find_good_proxy_tries] rounds of repeat
	    }

	    $result['curl_info'] = $curl['curl_info'];
	    $result['content'] = $curl['content'];
	    $result['proxy'] = $proxy;

	    return $result;
	}


	protected function remove_proxy($proxy, $local_proxy_file = './proxy.txt')
	{
	    //echo 'removing proxy '.$proxy."<br/>";
	    $proxy_array = file($local_proxy_file, FILE_IGNORE_NEW_LINES);
	    $proxy_array = array_diff($proxy_array, array($proxy));

	    if (count($proxy_array)>1)
	        file_put_contents($local_proxy_file, implode("\n", $proxy_array), LOCK_EX);

	    return array_values($proxy_array);
	}


	protected function renew_proxylist($proxy_url, $local_proxy_file = './proxy.txt', $proxy_array = false)
	{
		// assuming some other process\thread may be updating the file
		// and when update is finished there will be no need to
		if (file_exists($local_proxy_file))
		{
			if (($fp = fopen($local_proxy_file, 'r')) !== FALSE)
			{
			    if (time()-filemtime($local_proxy_file) > 60*5)
		        {
		        	if (($fp = fopen($local_proxy_file, 'w')) !== FALSE)
		            {
		                if (flock($fp, LOCK_EX) === TRUE)
		                {
		                    fwrite($fp, file_get_contents($proxy_url));
		                    flock($fp, LOCK_UN);
		                }

		                fclose($fp);
		            }
		        }
			  	else
			  	{
				    if ($proxy_array === false)
				    	$proxy_array = file($local_proxy_file, FILE_IGNORE_NEW_LINES);

				    if (is_array($proxy_array) and count($proxy_array)<10)
					{
			        	if (($fp = fopen($local_proxy_file, 'w')) !== FALSE)
			            {
			            	// if the file was updated less than 15 seconds ago
			            	if (time()-filemtime($local_proxy_file) < 10) sleep(10 - (time()-filemtime($local_proxy_file)));

			                if (flock($fp, LOCK_EX) === TRUE and time()-filemtime($local_proxy_file >= 10))
			                {
			                    fwrite($fp, file_get_contents($proxy_url));
			                    flock($fp, LOCK_UN);
			                }

			                fclose($fp);
			            }
					}
				}
			}
		}
		else // creating file
		{
            if (($fp = fopen($local_proxy_file, 'w')) !== FALSE)
            {
                if (flock($fp, LOCK_EX) === TRUE)
                {
                    fwrite($fp, file_get_contents($proxy_url));
                    flock($fp, LOCK_UN);
                }

                fclose($fp);
            }
		}

		if (($fp = fopen($local_proxy_file, 'r')) !== FALSE)
		{
			if (flock($fp, LOCK_EX) === TRUE)
            {
                return $proxy_array = file($local_proxy_file, FILE_IGNORE_NEW_LINES);
                flock($fp, LOCK_UN);
            }

            fclose($fp);
		}


	}


	protected function get($url, $referer = false, $proxy = false, $loginpassw = false)
	{

	    $ch = curl_init();

	    $header=array(
			'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Language: en-us,en;q=0.5',
			'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
			'Accept-Encoding: gzip,deflate',
			'Keep-Alive: 115',
			'Connection: keep-alive',
		);

	    //curl_setopt($ch, CURLOPT_VERBOSE, true);
	    //curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
	    curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
	    if ($referer !== false) curl_setopt($ch, CURLOPT_REFERER, $referer);
	    curl_setopt( $ch, CURLOPT_URL, $url );
	    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	    //curl_setopt( $ch, CURLOPT_ENCODING, "" );
	    curl_setopt($ch,CURLOPT_ENCODING , "gzip");
	    curl_setopt($ch,CURLOPT_COOKIEFILE,'cookie_feedback_filler.txt');
		curl_setopt($ch,CURLOPT_COOKIEJAR,'cookie_feedback_filler.txt');
	    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	    curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
	    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

	    if ($proxy !== false)
	    {
	    	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 20);
	    	curl_setopt( $ch, CURLOPT_TIMEOUT, 30);
	    	curl_setopt($ch, CURLOPT_PROXY, $proxy);
	        curl_setopt($ch, CURLOPT_PROXYTYPE, 7);

	        if ($loginpassw !== false)
	        	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
	    }
	    else
	    {
	    	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
	   		curl_setopt( $ch, CURLOPT_TIMEOUT, 10);
	    }

	    $content = curl_exec( $ch );
	    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $curl_info = curl_getinfo($ch);
	    $error = curl_error($ch);

	    if ($error) // ошибка curl, например таймаут
	    {
	        //echo 'CURL error occurred during the request: ' . $error;
	        //echo "\n";
	        return false;
	    } 
	    elseif ($http_code<200 || $http_code>=300) // код возврата не 200
	    {
	        //echo 'HTTP error ' . $http_code. ' occurred during the request';
	        //echo "\n";
	        //var_dump(curl_getinfo( $ch )); // там все заголовки и другая отладочная информация
	        return 'site_error';
	    } else
	    {
	        $result['content'] = $content;
	        $result['curl_info'] = $curl_info;

	        return ($result);
	    }

	}


}


?>
