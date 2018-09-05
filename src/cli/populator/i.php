<?php
// 2DO 
/*

  - remove ":" from items name + remove the names of the categories like and "Aamazon.com"
  - make an image transparent, without white color
  - если меняется name, то это поле не апдейтится в бд

*/

error_reporting(E_ALL);
ini_set('display_errors', TRUE);

require './vendor/autoload.php';

use Gregwar\Image\Image;

$useproxy = false;


include_once ('./class.proxy.php');

define('DB_NAME','amazon');
define('DB_USER','slim');
define('DB_PASS','slim');
define('DB_CHARSET','utf8'); 
define('DB_PORT', 3306); 
define('DB_HOST', 'localhost'); 

$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=".DB_PORT.";charset=".DB_CHARSET;
$opt = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
  ];

$db = new PDO($dsn, DB_USER, DB_PASS, $opt);

// в дальнейшем просто парсить амазон?

$lines = file('./list.amazon.txt',FILE_IGNORE_NEW_LINES);

$res = $item = $am = array();

$proxy = new Proxy();



foreach ($lines as $key => $value) {

  echo PHP_EOL.$key.' ';

  $item = array();

  //$item['name'] = str_replace('_', ' ', $value);
  $url = $value;

  // NOT TO COLLECT URLS that include offer-listing
  preg_match('#/dp/(.+?)/ref#', $url, $ok);

  if (!isset($ok[1]))
  {
    echo "\033[31mERR. No amazon ID in url. Skipping \033[0m".$url;
    continue;
  }

  $item['id'] = $ok[1];

  echo $item['id'];

  //echo PHP_EOL.substr(str_replace('https://', replace, subject)$url, 0,100);

  // EXPRIMENTAL. Deleting cookies for each new proxy
  file_put_contents('./cookie.txt', '');

  //$html = get($url);

  if ($useproxy ===true)
  {
    $proxy_finder = $proxy -> find_alive($url,'title', 5);

    if ($proxy_finder == false)
    { 
      $html = get($url);

      if ($html === false)
      {
        echo "\033[31m - ERR. Can't connect to url. Skipping \033[0m";
        continue;
      }
    }
    else 
      $html = $proxy_finder['content'];
  } else
    $html = get($url);

  // image
  preg_match('#id="(imgBlkFront|landingImage)" data-a-dynamic-image="{.+?(http.+?)&#', $html, $_ok);

  if (!isset($_ok[2]))
    echo "\033[31m - ERR. No image found. Skipping \033[0m".$url;

  //print_r($_ok); die;
  $item['image'] = $_ok[2];
  
  //name
  preg_match('#<title>(.+?)</title>#', $html, $_ok);

  //$_ok[1] = str_replace(array('Amazon.com:','Amazon.com :','Amazon.com : '), array('','','',''), $_ok[1]);
  $item['name'] = preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, htmlspecialchars_decode($_ok[1]));

  $tmp = array();

  // :
  if (strlen($item['name']) > 50)
  {
    $tmp = explode(':', $item['name']);

    if (count($tmp)>1)
    {
      usort($tmp,'sortarr');
      if (strlen($tmp[0])>=50)
        $item['name'] = trim($tmp[0]);
      elseif (strstr(trim($tmp[1]), ' '))
        $item['name'] = trim($tmp[0]).' - '.trim($tmp[1]);
      else
        $item['name'] = trim($tmp[0]);
    } 
  }

  // -
  if (strlen($item['name']) > 50)
  {
    $tmp = explode('-', $item['name']);

    if (count($tmp)>1)
    {
      usort($tmp,'sortarr');
      if (strlen($tmp[0])>=50)
        $item['name'] = trim($tmp[0]);
      elseif (strstr(trim($tmp[1]), ' '))
        $item['name'] = trim($tmp[0]).' - '.trim($tmp[1]);
      else
        $item['name'] = trim($tmp[0]);
    }
  }
 

  echo " - \033[32m".$item['name']."\033[0m";

  //author
  //preg_match_all('#<a class="authorName" itemprop="url" href=".+?"><span itemprop="name">(.+?)</span></a>#', $html, $_ok);
  //$item['author'] = $_ok[1];

  // categories (tags)
  preg_match_all("#/gp/bestsellers/.+?'>(.+?)</a>#", $html, $_ok);
  $item['cats'] = $_ok[1];

  if (count($item['cats'])>5)
    $item['cats'] = array_slice($item['cats'], -5, 5);



  // ---------- EPIC IMAGE TRANSITION / переписать блин все тут

  if (!isset( $proxy_finder['proxy']))
     $proxy_finder['proxy'] = false;

  $gr_image_url = $item['image'];

  $item['image'] = substr(md5($gr_image_url), 0, 10);

  // getting image WITHOUT PROXY, because proxyrack is returning trash while getting images
  file_put_contents('/home/dmytro/dev/php/amazonsites/public/img/books_covers/'.$item['image'].'.jpg', get($gr_image_url, false, true));

  //transparent_background('/home/dmytro/dev/php/amazonsites/public/img/books_covers/'.$item['image'].'.jpg', '255.255.255', '/home/dmytro/dev/php/amazonsites/public/img/books_covers/'.$item['image'].'.png');

  $gr_image_url = '/home/dmytro/dev/php/amazonsites/public/img/books_covers/'.$item['image'].'.jpg';

  $save_to = '/home/dmytro/dev/php/amazonsites/public/img/books_bg/'.$item['image'].'.jpg';

  $x = rand(1,3);

  if ($x == 1)
  {

    $seamless_image = Image::open('./background_source_images/seamless2.png')
                  ->resize(250, 250);
    $gb_cover_image = Image::open('./background_source_images/png.png');

    $gr_bg_image = Image::open($gr_image_url)->forceresize(500, 1000);

    $gr_image = Image::open($gr_image_url)-> resize(250, 250);        
    $gr_image->contrast(5);

    $img_src = Image::create(1000, 250)
                        ->fill(0xD49A63);
                
    $img_src->merge($gr_bg_image , 250, -250, 500, 0)
        
        ->merge($seamless_image , 0, 0, 250, 250)
        ->merge($seamless_image , 250, 0, 250, 250)
          ->merge($seamless_image , 500, 0, 250, 250)
          ->merge($seamless_image , 750, 0, 250, 250)

        ->merge($gb_cover_image , 0, 0, 1000, 0)

        ->merge($gr_image , 375, 0, 0, 0)

        //->write('./CaviarDreams.ttf', 'Hello '.$username.'!', 150, 150, 20, 0, 'white', 'center')
        ->save($save_to);

  } elseif ($x == 2) {

    $seamless_image = Image::open('./background_source_images/seamless4.png')
                ->resize(0, 250);

    $gb_cover_image = Image::open('./background_source_images/bg1.png');

    $gr_bg_image = Image::open($gr_image_url) -> forceresize(700, 1400);

    $gr_image = Image::open($gr_image_url) -> resize(250, 250);       
     

    $img_src = Image::create(1000, 250)
                        ->fill(0xFFFFFF);


    $img_src->merge($gr_bg_image , 150, -250, 700, 0)
        
        ->merge($seamless_image , 0, 0, 250, 250)
        ->merge($seamless_image , 250, 0, 250, 250)
          ->merge($seamless_image , 500, 0, 250, 250)
          ->merge($seamless_image , 750, 0, 250, 250)

        ->merge($gb_cover_image , 0, 0, 1000, 0)

        ->merge($gr_image , 375, 0, 0, 0)

        //->write('./CaviarDreams.ttf', 'Hello '.$username.'!', 150, 150, 20, 0, 'white', 'center')
        ->save($save_to);

  } else {
    
    $image = imagecreatefromjpeg($gr_image_url);
    $opacity = 0.3;
    imagealphablending($image, false); // imagesavealpha can only be used by doing this for some reason
    imagesavealpha($image, true); // this one helps you keep the alpha. 
    $transparency = 1 - $opacity;
    $transparentimage = imagefilter($image, IMG_FILTER_COLORIZE, 0,0,0,127*$transparency);
    imagepng($image, './background_source_images/i.png');

    $gr_bg_image = Image::open('./background_source_images/i.png')->forceresize(500, 1000);

    $colors = array(0xFF9120,0xC2C016, 0x01395E, 0xA8B6D9, 0x9B59B6, 0x2ECC71, 0x16A085);

    $color = $colors[rand(0, count($colors)-1)];

    $seamless_image = Image::open('./background_source_images/seamless'.rand(2,6).'.png')
                    ->resize(0, 250);

      $gr_image = Image::open($gr_image_url)-> resize(250, 250);        

      $img_src = Image::create(1000, 250)
                        ->fill($color);
                  
      $img_src->merge($seamless_image , 0, 0, 250, 250)
          ->merge($seamless_image , 250, 0, 250, 250)
            ->merge($seamless_image , 500, 0, 250, 250)
            ->merge($seamless_image , 750, 0, 250, 250)

            ->merge($gr_bg_image , 250, -250, 500, 0)

          ->merge($gr_image , 375, 0, 0, 0)

          //->write('./CaviarDreams.ttf', 'Hello '.$username.'!', 150, 150, 20, 0, 'white', 'center')
          ->save($save_to);


  }


  // -----------------------------------------------------------

  // checking if data is already inside
  $q = 'SELECT id from items where name = :name';

  $stmt = $db->prepare($q);
  $stmt -> execute([':name' => $item['name']]);
      
  $res = $stmt -> fetchAll();

  if (count($res)<1)
  {
    $q = "INSERT into items (name, image, am_id) values (:name, :image, :am_id)";

    $stmt = $db->prepare($q);
    $stmt -> execute([':name' => $item['name'], ':image' => $item['image'], ':am_id' => $item['id']]);
    $id = $db->lastInsertId();

  } else {

    $id = $res[0]['id'];

    $q = "UPDATE items set image = :image, am_id = :am_id where id = :id";
    $stmt = $db->prepare($q);
    //print_r($stmt);
    $stmt -> execute([':image' => $item['image'], ':am_id' => $item['id'], ':id' => $id]);
  }

  // inserting categories [ ПРЕДПОЛАГАЕМ, ЧТО КАТЕГОРИИ НЕ МЕНЯЮТСЯ И ПОЭТОМУ ИХ МЕНЯТЬ\УДАЛЯТЬ НЕ НУЖНО. ТОЛЬКО ДОПОЛНЯТЬ/ВНОСИТЬ НОВЫЕ ]

  if (count($item['cats'])>0)
  {
    foreach ($item['cats'] as $key => $value) {

      $q = 'SELECT id from categories_names where name = :name';
      $stmt = $db->prepare($q);
      $stmt -> execute([':name' => $value]);

      $res = $stmt -> fetchAll();

      if (count($res)<1)
      {
        $q = 'INSERT into categories_names (name) values (:name)';
        $stmt = $db->prepare($q);
        $stmt -> execute([':name' => $value]);
      } else {

        $cat_name_id = $res[0]['id'];
        $q = 'SELECT id from categories where categories_names_id = :cat_name_id and items_id = :id';
        $stmt = $db->prepare($q);
        $stmt -> execute([':cat_name_id' => $cat_name_id, ':id' => $id]);

        $res = $stmt -> fetchAll();

        if (count($res)<1)
        {
          $q = 'INSERT into categories (categories_names_id, items_id) values (:categories_names_id, :items_id)';
          $stmt = $db->prepare($q);
          $stmt -> execute([':categories_names_id' => $cat_name_id, ':items_id' => $id]);
        }
      }
    }
  } // if (count($item['categories'])>0)
  
}



function gzfile_get_contents($filename, $use_include_path = 0)
{
    //File does not exist
    if( !@file_exists($filename) )
    {    return false;    }
   
    //Read and imploding the array to produce a one line string
   $data = gzfile($filename, $use_include_path);
   $data = implode($data);
   return $data;
}



function get ($url, $proxy = false, $image = false) {
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

  if ($image == true)
    $header = array("Content-Type: image/jpeg");

  //curl_setopt($ch, CURLOPT_VERBOSE, true);
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch,CURLOPT_ENCODING , "gzip");
  curl_setopt($ch,CURLOPT_COOKIEFILE,'cookie.txt');
  curl_setopt($ch,CURLOPT_COOKIEJAR,'cookie.txt');
  curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  

  if ($proxy !== false)
  {
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt( $ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYTYPE, 7);

    /*if ($loginpassw !== false)
      curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);*/
  }
  else
  {
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt( $ch, CURLOPT_TIMEOUT, 10);
  }

  $result=curl_exec($ch);

  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  
  if ($http_code<200 || $http_code>=300)
    return false;

  curl_close($ch);

  return $result;
  
}

function sortarr($a,$b){
    return strlen($b)-strlen($a);
}

function transparent_background($filename, $color, $saveto) 
{
    $img = imagecreatefromjpeg($filename); //or whatever loading function you need
    $colors = explode('.', $color);
    $remove = imagecolorallocate($img, $colors[0], $colors[1], $colors[2]);
    imagecolortransparent($img, $remove);
    imagepng($img, $saveto);
}