<?php

/**

2DO

- скачивать картинки коментов и отображать с своего сервака
- на venue.html.twig отображать info по исполнителям (часть schedule_data с бд)

- на страницах artist - обозначить, что фотки от посетителей конценрта
	- на странице gallery - листалку в бок. чтоб и с мобильного работало

*/


namespace App;

use \Dice\Dice;
use \Router;

if (!file_exists(__DIR__.'/../src/config.php'))
	die('No config file. Rename and edit /src/config-sample.php');

require_once __DIR__.'/../vendor/autoload.php';

// Dice = autowiring 
$dice 		= new Dice;
$controller = $dice->create('\App\Controller\Controller');
$helper 	= $dice->create('\App\Helper\Helper');

/**
* Router
*/

Router::route('/', function() use($controller){

	$controller -> homePage();

});

// artist page
Router::route('/([0-9]+)/([^/]+)', function($id, $artist_name) use($controller){

	$controller -> itemPage($id, $artist_name);

});

// for posting comments. not active
Router::route('/feedbackpost', function() use($controller){

	$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
	print_r($_POST);
});

// artist gallery page. previous regex: ([a-fA-F0-9-]{36}
Router::route('/gallery/([0-9]+)', function($artist_id) use($controller){

	$controller -> gallery($artist_id);
	//echo '<center><img src="http://photos-eu.bazaarvoice.com/photo/2/cGhvdG86dGlja2V0bWFzdGVy/'.$image_id.'"></center>';

});

// venue page
Router::route('/venue/([0-9]+)/([^/]+)', function($id, $venue_name) use($controller){

	//print_r($venue_name);
	$controller -> venuePage($id, $venue_name);

});

Router::route('/venues(|/[0-9]+)', function($page) use($controller){

	if ($page != null)
		$page = str_replace('/', '', $page);
	
	//print_r($venue_name);
	$controller -> venueList($page);

});

Router::route('/category/([0-9]+)(|/[0-9]+)', function($cat_id, $page) use($controller){

	if ($page != null)
		$page = str_replace('/', '', $page);

	$controller -> catPage($cat_id, $page);

});


// static pages
Router::route('/page/([-a-z0-9]+)', function($page_file) use($controller){

	$page = STATIC_PAGES_CONTENT_FOLDER.'/'.$page_file.'.txt';

	if (file_exists($page))
		$controller -> staticPage($page, $page_file);

});

Router::route('/search', function() use($controller){

	$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
	//print_r($_POST);
	//echo 'search page';
	$controller -> search($_POST['search']);

});

if (false === Router::execute($url)) 
{
	header("Location: /",true,301);
	exit;
}


if (ENV == 'dev')
	$helper->devPanel();
else 
	$helper->showExecutionTime();


/*

план по наполнению

	before live:

		- сходу наполнить бд на 30-50к исполнителей. 
		- выставить дану FIRST_ARTISTS_LATEST_DATE = дате, когда эти 30-50к будут добавлены. 

		Для этих артистов сходу будет выводится MINIMUM_COMMENTS_FIRST_ARTISTS коментов. Для остальных - сразу MINIMUM_COMMENTS + будет расти каждые PUBLISH_ONE_COMMENT_EACH_DAYS дней

		Собирать не больше 200 коментов. Для следующего сайта уже попробовать собирать 400+ и потом удалять первые 200 (перед тем, как ложить в бд)
			- а парсер умеет собирать так, чтоб клалось в бд не больше заданного числа коментов?

		veues:
			: собираются по апи. первая часть собирает данные по venue, на входе принимает ссылку с TM, вторая - на основе данных из бд решает для каких venues нужно обновить schedule. 
			- крон. каждую минуту или x секунд дергает сбор общей инфы и сразу за этим сбор schedule. 

		- скрипт, который постепенно будет скачивать картинки (на крон)
		
	after live: 
	
		- добавлять данные небольшими партиями? крон? ...кот. будет поочередно запускать добавление исполнителей и дергать апи


	what to consider:

		 - прикинуть какие ситуации могут потребовать обновление данных исполнителя. 

*/