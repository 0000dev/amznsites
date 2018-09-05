<?php

namespace App\Model;

use \PDO;

class Model 
{	

	protected $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=".DB_PORT.";charset=".DB_CHARSET;
	protected $opt = [
	    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
	    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	    PDO::ATTR_EMULATE_PREPARES   => false,
	];

	protected $db;

	public function __construct()
	{
		$this->db = new PDO($this->dsn, DB_USER, DB_PASS, $this->opt);
	}

	public function search($s)
	{
		$sql_query = '

			SELECT
				
				items.id,
				items.name,
				items.image

			from items
			
			where
			MATCH (items.name) AGAINST (:s IN NATURAL LANGUAGE MODE)

			limit 50
		';

		$stmt = $this->db->prepare($sql_query); // stmt = statement
		$stmt -> execute([':s' => $s]);
		
		return $sql_res = $stmt -> fetchAll();
		
	}

	public function venuePage($id)
	{
		$sql_query = '

			SELECT
				
				venues.id,
				venues.name,
				venues.image_small,
				venues.image_big,
				venues.address,
				venues.details,

				venues_schedule.data as schedule_data,
				venues_schedule.last_updated as shedule_lu

				from venues

			left join venues_schedule on (venues.id = venues_schedule.venues_id)
		
			where
			venues.id = :id
		';

		$stmt = $this->db->prepare($sql_query); // stmt = statement
		$stmt -> execute([':id' => $id]);
		
		$sql_res = $stmt -> fetchAll();

		$res['content'] = $sql_res[0];

		$sql_query = '

			(SELECT id,name from venues where id > '.$id.' limit 2)
			UNION
			(SELECT id,name from venues where id = (select max(id) from venues where id < '.$id.'))
		';

		$stmt = $this->db->prepare($sql_query);
		$stmt -> execute();
		
		$sql_res = $stmt -> fetchAll();

		$res['neighbors'] = array();

		foreach ($sql_res as $key => $value) {
			$res['neighbors'][] = array('name'=>$value['name'], 'id'=>$value['id']);
		}

		return $res;
		
	}

	public function venueList($page)
	{

		$offset = $page;
		if ($page>0)
			$offset = $page-1;

		$sql_query = '

			SELECT
				
				venues.id,
				venues.name,
				venues.image_small,
				venues.image_big,
				venues.address,
				venues.details

			from venues
			
			limit '.(CATEGORY_ITEMS_PER_PAGE+1).'
			offset '.($offset*CATEGORY_ITEMS_PER_PAGE).'
		';

		$stmt = $this->db->prepare($sql_query); // stmt = statement
		$stmt -> execute();
		
		$sql_res = $stmt -> fetchAll();

		$next_page = false;
 
		if (count($sql_res)>CATEGORY_ITEMS_PER_PAGE)
		{
			$next_page = true;
			array_pop($sql_res);
		}

		return array('sql_res' => $sql_res, 'next_page' => $next_page);
		
	}


	public function homePage() {

		$result = array();

		$sql_query = '

			SELECT

				comments.items_id,
				comments.text as text,
				comments.title as title,
				comments.rating as rating,
				comments.author as author,

				items.id as items_id,
				items.name as items_name,
				items.image as items_image

			from comments 

			join items ON (items.id = comments.items_id)
			
			where
			items.image is not null

			group by comments.items_id

			limit 10
		';

		$stmt = $this->db->prepare($sql_query); // stmt = statement
		$stmt -> execute();

		$result['comments'] = $sql_res = $stmt -> fetchAll();

		$sql_query = '

			SELECT
				
				items.id,
				items.name,
				items.image

			from items

			where items.image is not NULL
			
			limit 100		
		';

		$stmt = $this->db->prepare($sql_query); // stmt = statement
		$stmt -> execute();
		
		$sql_res = $stmt -> fetchAll();

		$result['items'] = $sql_res;

		return $result;
	}

/*	public function glalery($id) {

		$sql_query = '

			SELECT
				
				items.id,
				items.name,
				items.image,
				items.description,

				images.image as images,

				categories_names.name as cats,
				categories_names.id as cats_id

			from items

			left join images on (items.id = images.items_id)

			left join categories ON (items.id = categories.items_id)
			left join categories_names ON (categories_names.id = categories.categories_names_id)
			
			where
			items.id = '.$id.'
		';


	}*/

	public function catPage($id, $page) {

		$offset = $page;
		if ($page>0)
			$offset = $page-1;

		$sql_query = '

			SELECT
				
				items.id,
				items.name,
				items.image,

				categories_names.name as cats,
				categories_names.id as cats_id

			from items

			left join categories ON (items.id = categories.items_id)
			left join categories_names ON (categories_names.id = categories.categories_names_id)
			
			where
			categories.categories_names_id = '.$id.' 

			limit '.(CATEGORY_ITEMS_PER_PAGE+1).'
			offset '.($offset*CATEGORY_ITEMS_PER_PAGE).'
		';

		$stmt = $this->db->prepare($sql_query); // stmt = statement
		$stmt -> execute();
		
		$sql_res = $stmt -> fetchAll();

		$next_page = false;
 
		if (count($sql_res)>CATEGORY_ITEMS_PER_PAGE)
		{
			$next_page = true;
			array_pop($sql_res);
		}

		return array('sql_res' => $sql_res, 'next_page' => $next_page);

	}

	public function itemPage($id) {

		// artist data

		$sql_query = '

			SELECT
				
				items.id,
				items.name,
				items.author,
				items.image,
				items.am_id,
				items.date_created,

				categories_names.name as cats,
				categories_names.id as cats_id

			from items

			left join categories ON (items.id = categories.items_id)
			left join categories_names ON (categories_names.id = categories.categories_names_id)
			
			where
			items.id = '.$id.'
		';

		$stmt = $this->db->prepare($sql_query); 
		$stmt -> execute();
		
		$sql_res = $stmt -> fetchAll();

		// flattering results
		foreach ($sql_res as $tmp) {
			foreach ($tmp as $tmp_key => $tmp_value) {
				if (!isset($res[$tmp_key]))
					$res[$tmp_key] = $tmp_value;
				else {
					if (is_array($res[$tmp_key]))
					{
						if (!in_array($tmp_value, $res[$tmp_key]))
							$res[$tmp_key][] = $tmp_value;
					} else {
						if ($res[$tmp_key] != $tmp_value)
						{
							$res[$tmp_key] = array($res[$tmp_key]);
							$res[$tmp_key][] = $tmp_value;
						}
					}
				}
			}
		}

		// neighboring items by id

		$sql_query = '

			(SELECT id,name from items where id > '.$id.' limit 4)
			UNION
			(SELECT id,name from items where id = (select max(id) from items where id < '.$id.'))
		';

		$stmt = $this->db->prepare($sql_query);
		$stmt -> execute();
		
		$sql_res = $stmt -> fetchAll();

		$res['neighbors'] = array();

		foreach ($sql_res as $key => $value) {
			$res['neighbors'][] = array('name'=>$value['name'], 'id'=>$value['id']);
		}

		// comments
		$max_comments = MAX_COMMENTS_PER_PAGE;

		if (defined('POSTPONED_COMMENT_PUBLISH') and POSTPONED_COMMENT_PUBLISH === true) {

			$now = time(); 
			$start_date = strtotime($res['date_created']);
			$datediff = $now - $start_date;
			$datediff =  round($datediff / (60 * 60 * 24));

			$x = PUBLISH_ONE_COMMENT_EACH_DAYS;

			$max_comments = round($datediff / $x); // 1 comment each $x days

			if ($max_comments<1)
				$max_comments = MINIMUM_COMMENTS;

			// making early added items have MINIMUM_COMMENTS_FIRST_items comments
			if (strtotime($res['date_created']) < strtotime(FIRST_ITEMS_LATEST_DATE) and $max_comments < MINIMUM_COMMENTS_FIRST_ITEMS)
				$max_comments = MINIMUM_COMMENTS_FIRST_ITEMS;

			// no more than MAX_COMMENTS_PER_PAGE will be displayed
			if ($max_comments > MAX_COMMENTS_PER_PAGE)
				$max_comments = MAX_COMMENTS_PER_PAGE;
		}


		$sql_query = '

			SELECT

				comments.text as text,
				comments.title as title,
				comments.rating as rating,
				comments.author as author

			
			from comments 

		 
			where
			comments.items_id = '.$id.' limit '.$max_comments.'
		';

		$stmt = $this->db->prepare($sql_query); // stmt = statement
		$stmt -> execute();
		
		$res['comments'] = $stmt -> fetchAll();

		if (count($res['comments'])<1)
		{
			$sql_query = '

			SELECT

				comments.items_id,
				comments.text as text,
				comments.title as title,
				comments.rating as rating,
				comments.author as author,

				items.id as items_id,
				items.name as items_name,
				items.image as items_image

			from comments 

			join items ON (items.id = comments.items_id)
			
			where
				items.image is not null
			and 
				items_id > '.$id.'

			group by comments.items_id

			limit 10
			';

			$stmt = $this->db->prepare($sql_query); // stmt = statement
			$stmt -> execute();

			$res['other_items_comments'] = $sql_res = $stmt -> fetchAll();
		}

		return $res;

		/*if (count($res['comment_text'])>0) {
			$comments = array();
			foreach ($res['comment_text'] as $key => $value) {
				$comments[$key]['text'] = $value;
				$comments[$key]['title'] = isset($res['comment_title'][$key])? $res['comment_title'][$key] : NULL;
				$comments[$key]['details'] = isset($res['comment_details'][$key])? $res['comment_details'][$key] : NULL;
				$comments[$key]['rating'] = isset($res['comment_rating'][$key])? $res['comment_rating'][$key] : NULL;
			}

			unset($res['comment_text'], $res['comment_title'], $res['comment_details'], $res['comment_rating']);

			$res['comments'] = $comments;
		}*/

	}

}

  
