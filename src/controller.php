<?php

namespace App\Controller;

use \App\Model\Model;
use \App\Helper\Helper;

class Controller
{	

	public $db;
	public $app;
	public $twig;

	public function __construct(Model $db, Helper $app) 
	{
		$this -> db = $db; 
		$this -> app = $app;
		$this -> twig = $app -> loadTwig();

	}

	// 2DO : add pagination

	public function search($s)
	{
		$content = $this -> db -> search($s);

		$this->twig->display('search.html.twig', array('content' => $content, 'search_phrase' => $s ));

		return;
	}

	public function gallery($artist_id)
	{
		$content = $this -> db -> itemPage($artist_id);
		
		/*echo '<pre>';
		print_r($content);
		echo '</pre>';
		die;*/

		if ($this->app->checkFor404($content))
			return;

		if (isset($content['images']) and !is_array($content['images']))
			$content['images'] = array($content['images']);

		$this->twig->display('gallery.html.twig', array('artist' => $content));
		
		return;
	}

	public function staticPage($page_file, $title)
	{
		$content = file_get_contents($page_file);

		$this->twig->display('static_page.html.twig', array('content' => $content, 'title' => $title));

		return;
	}

	public function homePage()
	{		 

		$model = $this -> db -> homePage();

		$model['footer_links'] = array_slice($model['items'], 51, 100);
		$model['items'] = array_slice($model['items'], 0, 50);

		/*echo '<pre>';
		print_r($model);
		echo '</pre>';
		die;*/		

		//shuffle($model['comments']);

		//$model['comments'] = array_slice($model['comments'], 0,10);

		$this->twig->display('home.html.twig', array('comments' => $model['comments'], 'items' => $model['items'], 'footer_links' => $model['footer_links']));
		
		return;
	}

	public function itemPage($id, $artist_name)
	{	

		$content = $this -> db -> itemPage($id);
		
		/*echo '<pre>';
		print_r($content);
		echo '</pre>';
		die;*/

		if ($this->app->checkFor404($content, $artist_name))
			return;

		if (isset($content['schedule_data'])) {
			$content['schedule']['data'] = json_decode($content['schedule_data'],1);
			
			$now = time(); // or your date as well
			$your_date = strtotime($content['schedule_lu']);
			$datediff = $now - $your_date;

			if (1 < round($datediff / (60 * 60 * 24)))
				$content['schedule']['last_update'] = true;
		}

		if (isset($content['images']) and !is_array($content['images']))
			$content['images'] = array($content['images']);

		if (isset($content['cats']) and !is_array($content['cats']))
			$content['cats'] = array($content['cats']);

		if (isset($content['cats_id']) and !is_array($content['cats_id']))
			$content['cats_id'] = array($content['cats_id']);

		$content['author'] = json_decode($content['author'],1);

		if (isset($content['author']))
		{
			$a = '';
			foreach ($content['author'] as $k => $v) {

				$a = $a.$v;
				if ($k < count($content['author'])-1)
					$a = $a.', ';
			}
			$content['author'] = $a;
		}
		
		$this->twig->display('item.html.twig', array('item' => $content));
		
		return;
	}

	public function catPage($id, $page)
	{	
		if ($page == null)
			$page = 0;

		$model = $this -> db -> catPage($id, $page);

		$content = $model['sql_res'];

		if (count($content)<1) {
			$this->app->show404();
			return;
		}

		/*echo '<pre>';
		print_r($content);
		echo '</pre>';
		die;*/

		// first 'next' page starts with #2 
		if ($page == 0)
			$page = 1;

		$this->twig->display('cat.html.twig', array('category' => $content, 'next_page' => $model['next_page'], 'curr_page' => $page, 'cat_id' => $id));

		return;
	}

	public function venuePage($id, $venue_name)
	{	

		$model = $this -> db -> venuePage($id);

		$content = $model['content'];
		$neighbors = $model['neighbors'];

		if ($this->app->checkFor404($content, $venue_name))
			return;

		if (isset($content['details']))
			$content['details'] = json_decode($content['details'],1);

		if (isset($content['schedule_data']))
			$content['schedule_data'] = json_decode($content['schedule_data'],1);
		
		/*echo '<pre>';
		print_r($content);
		echo '</pre>';
		die;*/

		/*if ($this->app->checkFor404($content, $artist_name))
			return;

		if (isset($content['schedule_data'])) {
			$content['schedule']['data'] = json_decode($content['schedule_data'],1);
			
			$now = time(); // or your date as well
			$your_date = strtotime($content['schedule_lu']);
			$datediff = $now - $your_date;

			if (1 < round($datediff / (60 * 60 * 24)))
				$content['schedule']['last_update'] = true;
		}
		*/

		$this->twig->display('venue.html.twig', array('venue' => $content, 'neighbors' => $neighbors));
		
		return;
	}

	public function venueList($page)
	{	
		if ($page == null)
			$page = 0;

		$model = $this -> db -> venueList($page);

		$content = $model['sql_res'];

		if (count($content)<1) {
			$this->app->show404();
			return;
		}

		/*echo '<pre>';
		print_r($content);
		echo '</pre>';
		die;*/

		// first 'next' page starts with #2 
		if ($page == 0)
			$page = 1;

		$this->twig->display('venues_list.html.twig', array('venues' => $content, 'next_page' => $model['next_page'], 'curr_page' => $page));

		return;
	}

}



?>