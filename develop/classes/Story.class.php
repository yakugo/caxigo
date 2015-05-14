<?php

namespace SocialKit;

class Story {
	use \SocialTrait\Extension;

	private $id;
	private $conn;
	private $timelineObj;
	private $recipientObj;
	public $data;
	public $template;
	public $view_all_comments = false;
	private $comment_mentions;

	function __construct()
	{
		global $conn;
		$this->conn = $conn;
		return $this;
	}

	public function setConnection(\mysqli $conn)
	{
		$this->conn = $conn;
		return $this;
	}

	protected function getConnection()
	{
		return $this->conn;
	}

	public function getRows()
	{
		$query1 = $this->getConnection()->query("SELECT * FROM posts WHERE id=" . $this->id . " AND active=1");

		if ($query1->num_rows == 1) {
			$post = $query1->fetch_array(MYSQLI_ASSOC);
			$userObj = new \SocialKit\User($this->getConnection());
			$post['timeline'] = $userObj->getById($post['timeline_id']);
			unset($post['timeline_id']);

			if ($post['id'] == $post['post_id'])
			{
				$this->data = $post;
			}
			else
			{
				$query2 = $this->getConnection()->query("SELECT * FROM posts WHERE id=" . $post['post_id'] . " AND active=1");

				if ($query2->num_rows == 1) {
					$this->data = $query2->fetch_array(MYSQLI_ASSOC);
					$userObj = new \SocialKit\User($this->getConnection());
					$this->data['timeline'] = $userObj->getById($this->data['timeline_id']);
					unset($this->data['timeline_id']);
				}
			}

			/* Timeline Object */
			$this->timelineObj = $userObj;
			
			// Get Type
			$this->data['type'] = $this->getType();


			// See if it's reported
			$this->data['isReported'] = $this->isReported();


			// Get recipient, if available
			$this->data['recipient'] = $this->getRecipient();


			// Get activity text (sub-text)
	        $this->data['activity_text'] = $this->getActivity();


	        // Emoticons
	        $escapeObj = new \SocialKit\Escape();

	        $this->data['text'] = $escapeObj->getEmoticons($this->data['text']);
	        $this->data['text'] = $escapeObj->getLinks($this->data['text']);
	        $this->data['text'] = $escapeObj->getHashtags($this->data['text']);
	        $this->data['text'] = $escapeObj->getMentions($this->data['text']);


	        // Media, if available
			$this->data['media'] = $this->getMedia();


			// Location
	        $this->data['location'] = $this->getLocation();


	        // Via
	        $this->data['via'] = $this->getVia($post);


			// Admin Rights
	        $this->data['admin'] = $this->isAdmin();
	        

	        // Invoke plugins
	        //$this->data = $this->invoke('post_content_editor', $this->data);


	        // Basic Template Data
	        $this->getBasicTemplateData();

	        // Caching
			/*$_SESSION['tempche']['story'][$this->id] = $this->data;
			$_SESSION['tempche']['story'][$this->id]['expire_time'] = time() + (60 * 5);*/

	        return $this->data;
		}
	}

	public function isLiked($timeline_id=0) {
	    global $user;

	    $timeline_id = (int) $timeline_id;

	    if ($timeline_id == 0) {
	        $timeline_id = $user['id'];
	    }
	    
	    $query = $this->getConnection()->query("SELECT id FROM " . DB_POSTLIKES . " WHERE post_id=" . $this->id . " AND timeline_id=$timeline_id AND active=1");
	    
	    if ($query->num_rows == 1) {
	        return true;
	    }
	}

	public function isShared($timeline_id=0) {
	    global $user;

	    $timeline_id = (int) $timeline_id;

	    if ($timeline_id == 0) {
	        $timeline_id = $user['id'];
	    }
	    
	    $query = $this->getConnection()->query("SELECT id FROM " . DB_POSTSHARES . " WHERE post_id=" . $this->id . " AND timeline_id=$timeline_id AND active=1");
	    
	    if ($query->num_rows == 1) {
	        return true;
	    }
	}

	public function isFollowed($timeline_id=0) {
	    global $user;

	    $timeline_id = (int) $timeline_id;

	    if ($timeline_id == 0) {
	        $timeline_id = $user['id'];
	    }
	    
	    $query = $this->getConnection()->query("SELECT id FROM " . DB_POSTFOLLOWS . " WHERE post_id=" . $this->id . " AND timeline_id=$timeline_id AND active=1");
	    
	    if ($query->num_rows == 1) {
	        return true;
	    }
	}

	public function isReported() {
	    if (! isLogged()) {
			return false;
		}
		
		global $user;
		$query = $this->getConnection()->query("SELECT id FROM " . DB_REPORTS . " WHERE reporter_id=" . $user['id'] . " AND post_id=" . $this->data['id'] . " AND type='story'");

		if ($query->num_rows == 1) {
			return true;
		}
	}

	public function isAdmin() {
		if (! isLogged())
		{
			return false;
		}

		$admin = false;
        
        if ($this->timelineObj->isAdmin())
        {
			$admin = true;
		}

		if (is_array($this->data['recipient']))
		{
			if ($this->recipientObj->isAdmin())
			{
				$admin = true;
			}
		}

        return $admin;
	}

	function numLikes()
	{
	    $query = $this->getConnection()->query("SELECT COUNT(id) AS count FROM " . DB_POSTLIKES . " WHERE post_id=" . $this->id . " AND active=1");
	    $fetch = $query->fetch_array(MYSQLI_ASSOC);
	    
	    return $fetch['count'];
	}

	function numComments()
	{
	    $query = $this->getConnection()->query("SELECT COUNT(id) AS count FROM " . DB_COMMENTS . " WHERE post_id=" . $this->id . " AND active=1");
	    $fetch = $query->fetch_array(MYSQLI_ASSOC);
	    
	    return $fetch['count'];
	}

	function numShares()
	{
	    $query = $this->getConnection()->query("SELECT COUNT(id) AS count FROM " . DB_POSTSHARES . " WHERE post_id=" . $this->id . " AND active=1");
	    $fetch = $query->fetch_array(MYSQLI_ASSOC);
	    
	    return $fetch['count'];
	}

	function numFollowers()
	{
	    $query = $this->getConnection()->query("SELECT COUNT(id) AS count FROM " . DB_POSTFOLLOWS . " WHERE post_id=" . $this->id . " AND active=1");
	    $fetch = $query->fetch_array(MYSQLI_ASSOC);
	    
	    return $fetch['count'];
	}

	public function getType()
	{
		return "story";
	}

	public function getMedia() {
		$get = false;

		if ($this->data['media_id'] > 0)
		{
			$get = array();
			$get['type'] = 'photos';
			$mediaObj = new \SocialKit\Media();
			$media = $mediaObj->getById($this->data['media_id']);

			if ($media['type'] == "photo") {
				$get = $media;
				$get['type'] = 'photos';
				$get['each'][0]['url'] = SITE_URL . '/' . $get['each'][0]['url'] . '.' . $get['each'][0]['extension'];
				$get['each'][0]['post_id'] = $this->data['id'];
				$get['each'][0]['post_url'] = smoothLink('index.php?tab1=story&id=' . $this->data['id']);
				
			} elseif ($media['type'] == "album") {
				$get = $media;
				$get['type'] = 'photos';

				foreach ($get['each'] as $each_i => $each_v) {
					$get['each'][$each_i]['url'] = SITE_URL . '/' . $each_v['url'] . '_100x100.' . $each_v['extension'];
				}
			}
			
			unset($this->data['media_id']);

		} elseif (! empty($this->data['soundcloud_uri'])) {
			$get = array();
			$get['type'] = 'soundcloud';
			$get['each'][]['url'] = $this->data['soundcloud_uri'];
			unset($this->data['soundcloud_uri']);

		} elseif (! empty($this->data['youtube_video_id'])) {
			$get = array();
			$get['type'] = 'youtube';
			$get['each'][]['id'] = $this->data['youtube_video_id'];
			unset($this->data['youtube_video_id']);
		}

		return $get;
	}

	public function getLocation() {
		$location = false;

		if (! empty($this->data['google_map_name'])) {
			$location = array(
				'name' => $this->data['google_map_name']
			);
		}

		return $location;
	}

	public function getVia($post) {
		$via = false;

		if ($this->data['id'] !== $post['id'] && $this->data['timeline']['id'] !== $post['timeline']['id']) {
            $via_type = $post['type2'];
            
            if ($post['type2'] === "with") {
                $via_type = 'tag';
            }
            
            $via = array(
            	'type' => $via_type,
            	'timeline' => $post['timeline']
            );
        }

        return $via;
	}

	public function getActivity() {
		$activity = false;

		if (! empty($this->data['activity_text'])) {
	            
            preg_match(
            	'/\[album\]([0-9]+)\[\/album\]/i',
            	$this->data['activity_text'],
            	$matches
            );

            $activity_query1 = $this->getConnection()->query("SELECT id,name FROM " . DB_MEDIA . " WHERE id=" . $matches[1]);
            $activity_fetch1 = $activity_query1->fetch_object();

            $activity_text_replace = '<a href="' . smoothLink('index.php?tab1=album&tab2=' . $activity_fetch1->id) . '" data-href="?tab1=album&tab2=' . $activity_fetch1->id . '">' . $activity_fetch1->name . '</a>';

            $activity = str_replace(
            	$matches[0],
            	'<a href="' . smoothLink('index.php?tab1=album&tab2=' . $activity_fetch1->id) . '" data-href="?tab1=album&tab2=' . $activity_fetch1->id . '">' . $activity_fetch1->name . '</a>',
            	$this->data['activity_text']
            );
        }

        return $activity;
	}

	public function getRecipient() {
		$recipient = false;
		
		if ($this->data['recipient_id'] > 0) {
			$recipientObj = new \SocialKit\User($this->getConnection());
			$recipient = $recipientObj->getById($this->data['recipient_id']);
			$this->recipientObj = $recipientObj;
		}

		unset($this->data['recipient_id']);
		return $recipient;
	}

	public function getLikes()
	{
		$get = array();
		$query = $this->getConnection()->query("SELECT id,timeline_id FROM " . DB_POSTLIKES . " WHERE post_id=" . $this->id . " AND active=1");
	    
	    if ($query->num_rows > 0)
	    {
	        while ($fetch = $query->fetch_array(MYSQLI_ASSOC))
	        {
	        	$get[] = $fetch['timeline_id'];
	        }
	    }

	    return $get;
	}

	public function getComments($li=0)
	{
		$comments = '';
		$numComments = $this->numComments();

		if ($li < 1)
		{
			$li = $numComments;
		}

		$commentFeed = new \SocialKit\CommentFeed($this->getConnection());
		$commentFeed->setPostId($this->id);
		$commentFeed->setLimit($li);
		$commentFeed->setTotal($numComments);

        foreach ($commentFeed->getFeed() as $commentId)
        {
        	$comment = new \SocialKit\Comment($this->conn);
        	$comment->setId($commentId);
        	$comments .= $comment->getTemplate();
        }

        return $comments;
	}

	public function getCommentIds($li=0) {
		$get = array();
		$comments = '';
		$numComments = $this->numComments();

		if ($li < 1)
		{
			$li = $numComments;
		}

		$commentFeed = new \SocialKit\CommentFeed($this->getConnection());
		$commentFeed->setPostId($this->id);
		$commentFeed->setLimit($li);
		$commentFeed->setTotal($numComments);

        foreach ($commentFeed->getFeed() as $commentId)
        {
        	$get[] = $commentId;
        }

        return $get;
	}

	public function getShares()
	{
		$get = array();
		$query = $this->getConnection()->query("SELECT id,timeline_id FROM " . DB_POSTSHARES . " WHERE post_id=" . $this->id . " AND active=1");
	    
	    if ($query->num_rows > 0)
	    {
	        while ($fetch = $query->fetch_array(MYSQLI_ASSOC))
	        {
	        	$get[] = $fetch['timeline_id'];
	        }
	    }

	    return $get;
	}

	public function getFollowers()
	{
		$get = array();
		$query = $this->getConnection()->query("SELECT id,timeline_id FROM " . DB_POSTFOLLOWS . " WHERE post_id=" . $this->id . " AND active=1");
	    
	    if ($query->num_rows > 0)
	    {
	        while ($fetch = $query->fetch_array(MYSQLI_ASSOC))
	        {
	        	$sharer = new \SocialKit\User($this->getConnection());
	        	$sharer->setId($fetch['timeline_id']);

	            $get[] = $sharer->getRows();
	        }
	    }

	    return $get;
	}

	public function getCommentBox($timelineId=0)
	{
	    if (! isLogged())
	    {
	        return false;
	    }
	    
	    global $themeData, $user;
	    $continue = true;
	    $timelineId = (int) $timelineId;

	    if ($timelineId < 1)
	    {
	    	$timelineId = $user['id'];
	        $timeline = $user;
	    }
	    else
	    {
	        $timelineObj = new \SocialKit\User();
	        $timelineObj->setId($timelineId);
	        $timeline = $timelineObj->getRows();

	        if (! $timelineObj->isAdmin())
	        {
	        	$continue = false;
	        }
	    }
	    
	    if ($this->data['timeline']['type'] == "user")
	    {
	        if ($this->data['timeline']['id'] != $timelineId && $this->data['timeline']['comment_privacy'] == "following")
	        {
	            if (! $this->timelineObj->isFollowedBy($timelineId))
	            {
	                $continue = false;
	            }
	        }
	    }
	    
	    if ($continue == false)
	    {
	        return false;
	    }

	    //$themeData['story_id'] = $postId;

	    $themeData['publisher_id'] = $timeline['id'];
	    $themeData['publisher_url'] = $timeline['url'];
	    $themeData['publisher_username'] = $timeline['username'];
	    $themeData['publisher_name'] = $timeline['name'];
	    $themeData['publisher_thumbnail_url'] = $timeline['thumbnail_url'];
	    
	    return \SocialKit\UI::view('comment/publisher-box/content');
	}

	public function setId($id) {
		$this->id = (int) $id;
	}

	public function putLike()
	{
	    if (! isLogged())
	    {
	        return false;
	    }
	    
	    global $user;
	    
	    if ($this->isLiked())
	    {
	        $this->getConnection()->query("DELETE FROM " . DB_POSTLIKES . " WHERE post_id=" . $this->id . " AND timeline_id=" . $user['id'] . " AND active=1");
	    }
	    else
	    {
	        $this->getConnection()->query("INSERT INTO " . DB_POSTLIKES . " (timeline_id,active,post_id,time) VALUES (" . $user['id'] . ",1," . $this->id . "," . time() . ")");
	        $this->putNotification('like');
	    }

	    return true;
	}

	public function putShare() {
	    if (! isLogged())
	    {
	        return false;
	    }
	    
	    global $user;
	    
	    if ($this->isShared())
	    {
	        $this->getConnection()->query("DELETE FROM " . DB_POSTSHARES . " WHERE post_id=" . $this->id . " AND timeline_id=" . $user['id'] . " AND active=1");
	    }
	    else
	    {
	        $this->getConnection()->query("INSERT INTO " . DB_POSTSHARES . " (timeline_id,active,post_id,time) VALUES (" . $user['id'] . ",1," . $this->id . "," . time() . ")");
	        $this->putNotification('share');
	    }

	    return true;
	}

	public function putFollow()
	{
	    if (! isLogged())
	    {
	        return false;
	    }
	    
	    global $user;
	    
	    if ($this->isFollowed())
	    {
	        $this->getConnection()->query("DELETE FROM " . DB_POSTFOLLOWS . " WHERE post_id=" . $this->id . " AND timeline_id=" . $user['id'] . " AND active=1");
	        $this->putNotification('follow');
	    }
	    else
	    {
	        $this->getConnection()->query("INSERT INTO " . DB_POSTFOLLOWS . " (timeline_id,active,post_id,time) VALUES (" . $user['id'] . ",1," . $this->id . "," . time() . ")");
	    }

	    return true;
	}

	public function putComment($text='', $timelineId=0)
	{
		if (! isLogged())
	    {
	        return false;
	    }
	    
	    global $user, $config;
	    
	    if (empty($text))
	    {
	        return false;
	    }

	    if ($config['comment_character_limit'] > 0)
	    {
	        if (strlen($text) > $config['comment_character_limit'])
	        {
	            return false;
	        }
	    }
	    
	    $timelineId = (int) $timelineId;

	    if ($timelineId < 1)
	    {
	        $timelineId = $user['id'];
	    }
	    
	    $timelineObj = new \SocialKit\User($this->getConnection());
	    $timelineObj->setId($timelineId);
	    $timeline = $timelineObj->getRows();
	    $continue = true;
	    
	    if (! $timelineObj->isAdmin())
	    {
	    	return false;
	    }
	    
	    if ($this->data['timeline']['type'] == "user" && $this->data['timeline']['id'] != $timelineId)
	    {
	        
	        if ($this->data['timeline']['comment_privacy'] == "following")
	        {
	            
	            if (! $this->timelineObj->isFollowing($timelineId))
	            {
	                $continue = false;
	            }
	        }
	    }
	    elseif ($this->data['timeline']['type'] == "group")
	    {
	        
	        if (! $this->timelineObj->isFollowing($timelineId))
	        {
	            $continue = false;
	        }
	    }

	    if (!$continue)
	    {
	        return false;
	    }


	    $escapeObj = new \SocialKit\Escape($this->conn);

	    /* Links */
	    $text = $escapeObj->createLinks($text);

	    /* Hashtags */
	    $text = $escapeObj->createHashtags($text);

	    /* Mentions */
	    $mentions = $escapeObj->createMentions($text);
	    $text = $mentions['content'];
	    $this->comment_mentions = $mentions['mentions'];

	    /* Text */
	    $text = $escapeObj->postEscape($text);

	    /* Query */
	    $query = $this->getConnection()->query("INSERT INTO " . DB_COMMENTS . " (timeline_id,active,post_id,text,time) VALUES ($timelineId,1," . $this->id . ",'$text'," . time() . ")");
	    
	    if ($query)
	    {
	        $commentId = $this->getConnection()->insert_id;
	        
	        /* Put follow */
	        if (! $this->isFollowed())
	        {
	            $this->putFollow();
	        }
	        
	        /* Notify followers */
	        $this->putNotification('comment', $commentId);
	        
	        /* Return results */
	        return $commentId;
	    }
	}

	public function putReport()
	{
		if (! isLogged()) {
			return false;
		}

		if ($this->isReported())
		{
			return false;
		}

		global $user;
		$query = $this->getConnection()->query("INSERT INTO " . DB_REPORTS . " (active,post_id,reporter_id,type) VALUES (1," . $this->id ."," . $user['id'] . ",'story')");

		if (! $query)
		{
			return false;
		}

		return true;
	}

	public function putRemove()
	{
		if (! isLogged()) {
			return false;
		}

		$continue = false;
        
        if ($this->timelineObj->isAdmin())
        {
            $continue = true;
        }
        elseif (is_array($this->data['recipient']))
        {
            if ($this->recipientObj->isAdmin())
            {
                $continue = true;
            }
        }
        
        if ($continue)
        {
        	if ($this->data['media']['type'] == "photos")
        	{
        		$continue = true;

        		if (isset ($this->data['media']['temp']))
	        	{
	        		$continue = false;

	        		if ($this->data['media']['temp'] == 1)
	        		{
	        			$continue = true;
	        		}
	        	}

        		if ($continue)
        		{
        			foreach ($this->data['media']['each'] as $key => $value)
        			{
	        			$this->getConnection()->query("DELETE FROM " . DB_MEDIA . " WHERE id=" . $value['id'] . " AND type='photo'");
	        			$this->getConnection()->query("DELETE FROM " . DB_POSTS . " WHERE media_id=" . $value['id']);

	        			$dirImages = glob(str_replace(SITE_URL . "/", "", $value['url']) . "*");
	        			
	        			foreach ($dirImages as $k => $img)
	        			{
	                        unlink($img);
	                    }
	        		}
        		}
        	}

        	$this->getConnection()->query("DELETE FROM " . DB_POSTS . " WHERE post_id=" . $this->id);
        	return true;
        }
	}

	public function putNotification($action)
	{
		if (! isLogged())
		{
			return false;
		}

		global $lang, $user;
		$text = '';

		if ($this->data['timeline']['id'] == $user['id']) {
			return false;
		}

		if ($action == "like")
		{
			$count = $this->numLikes();
	        
	        if ($this->isLiked())
	        {
	            $count = $count - 1;
	        }
	        
	        if ($count > 1)
	        {
	            $text .= str_replace('{count}', ($count-1), $lang['notif_other_people']) . ' ';
	        }
	        
	        $text .= str_replace('{post}', substr($this->data['text'], 0, 45), $lang['likes_your_post']);
	        $query = $this->getConnection()->query("SELECT id FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $this->data['timeline']['id'] . " AND post_id=" . $this->id . " AND type='like' AND active=1");
			
		    if ($query->num_rows > 0)
		    {
		        $this->getConnection()->query("DELETE FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $this->data['timeline']['id'] . " AND post_id=" . $this->id . " AND type='like' AND active=1");
		    }
		    else
		    {
		    	$this->getConnection()->query("INSERT INTO " . DB_NOTIFICATIONS . " (timeline_id,active,notifier_id,post_id,text,time,type,url) VALUES (" . $this->data['timeline']['id'] . ",1," . $user['id'] . "," . $this->id . ",'$text'," . time() . ",'like','index.php?tab1=story&id=" . $this->id . "')");
		    }

		    return true;
		}
		elseif ($action == "share")
		{
			$count = $this->numShares();
	        
	        if ($this->isShared())
	        {
	            $count = $count - 1;
	        }
	        
	        if ($count > 1)
	        {
	            $text .= str_replace('{count}', ($count-1), $lang['notif_other_people']) . ' ';
	        }
	        
	        $text .= str_replace('{post}', substr($this->data['text'], 0, 45), $lang['shared_your_post']);
	        $query = $this->getConnection()->query("SELECT id FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $this->data['timeline']['id'] . " AND post_id=" . $this->id . " AND type='share' AND active=1");
			
		    if ($query->num_rows > 0)
		    {
		        $this->getConnection()->query("DELETE FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $this->data['timeline']['id'] . " AND post_id=" . $this->id . " AND type='share' AND active=1");
		    }
		    else
		    {
		    	$this->getConnection()->query("INSERT INTO " . DB_NOTIFICATIONS . " (timeline_id,active,notifier_id,post_id,text,time,type,url) VALUES (" . $this->data['timeline']['id'] . ",1," . $user['id'] . "," . $this->id . ",'$text'," . time() . ",'share','index.php?tab1=story&id=" . $this->id . "')");
		    }

		    return true;
		}
		elseif ($action == "comment")
		{
			$count = $this->numComments();
			
			if ($count > 1)
            {
                $text .= str_replace('{count}', ($count-1), $lang['notif_other_people']) . ' ';
            }

            /* Notify story followers */
            foreach ($this->getFollowers() as $follower)
	        {
	            if ($follower['id'] == $this->data['timeline']['id'])
	            {
	                $text .= str_replace('{post}', substr($this->data['text'], 0, 45), $lang['commented_on_post']);
	            }
	            else
	            {
	            	$text .= str_replace(
	                    array(
	                        '{user}',
	                        '{post}'
	                    ),

	                    array(
	                        $follower['name'],
	                        substr($this->data['text'], 0, 45)
	                    ),

	                    $lang['commented_on_user_post']
                    );
	            }

            	$query = $this->getConnection()->query("SELECT id FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $follower['id'] . " AND post_id=" . $this->id . " AND type='comment' AND active=1");
				
			    if ($query->num_rows > 0)
			    {
			        $this->getConnection()->query("DELETE FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $follower['id'] . " AND post_id=" . $this->id . " AND type='comment' AND active=1");
			    }
			    else
			    {
			    	$this->getConnection()->query("INSERT INTO " . DB_NOTIFICATIONS . " (timeline_id,active,notifier_id,post_id,text,time,type,url) VALUES (" . $follower['id'] . ",1," . $user['id'] . "," . $this->id . ",'$text'," . time() . ",'comment','index.php?tab1=story&id=" . $this->id . "')");
			    }
	        }

	        /* Notify people mentioned */
	        if (func_num_args() > 1)
	        {
	        	$commentId = (int) func_get_arg(1);
	        	$text = $lang['mentioned_in_comment'];

		        foreach ($this->comment_mentions as $mention)
		        {
	            	$query = $this->getConnection()->query("SELECT id FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $mention . " AND post_id=" . $this->id . " AND type='post_mention' AND active=1");
					
				    if ($query->num_rows > 0)
				    {
				        $this->getConnection()->query("DELETE FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $mention . " AND post_id=" . $this->id . " AND type='post_mention' AND active=1");
				    }
				    else
				    {
				    	$this->getConnection()->query("INSERT INTO " . DB_NOTIFICATIONS . " (timeline_id,active,notifier_id,post_id,text,time,type,url) VALUES (" . $mention . ",1," . $user['id'] . "," . $this->id . ",'$text'," . time() . ",'post_mention','index.php?tab1=story&id=" . $this->id . "#comment_$commentId')");
				    }
		        }
	        }

            return true;
		}
	}

	/* Template Methods */

	public function getTemplate() {

		if (! is_array($this->data))
		{
			$this->getRows();
		}

		if (! isset($this->data['id']))
		{
			return false;
		}

		global $themeData, $user;
		
		// Basic Template Data
		$this->getBasicTemplateData();

		// Recipient Template Data
        $this->getRecipientTemplate();

        /* Control buttons */
        $themeData['story_control_buttons'] = $this->getControlButtonTemplate();

        /* Text */
        $themeData['story_text_html'] = $this->getTextTemplate();

        /* Media */
        $themeData['media_html'] = $this->getMediaTemplate();

        /* Location */
        $themeData['story_location_html'] = $this->getLocationTemplate();

        // Like Activity
        $themeData['story_like_activity'] = $this->getLikeActivityTemplate();

        // Comment Activity
        $themeData['story_comment_activity'] = $this->getCommentActivityTemplate();

        // Share Activity
        $themeData['story_share_activity'] = $this->getShareActivityTemplate();

        // Follow Activity
        $themeData['story_follow_activity'] = $this->getFollowActivityTemplate();

        // Via
        $themeData['via'] = $this->getViaTemplate();

        // View all comments
        $themeData['view_all_comments_html'] = '';
        $commentsNum = $themeData['story_comments_num'];
        
        if ($this->view_all_comments == false) {
            
            if ($commentsNum > 3) {
            	$themeData['view_all_comments_html'] = \SocialKit\UI::view('story/view-all-comments-html');
            }

            $commentsNum = 3;
        }

        // Comments
        $themeData['comments'] = $this->getComments($commentsNum);

        // Comment Publisher Box
        $show_comment_publisher_box = true;
        $commentPublisherBox = '';
        
        if ($this->data['timeline']['type'] == "user")
        {
            if ($this->data['timeline']['comment_privacy'] == "following" && $this->data['timeline']['id'] != $user['id'])
            {
                if (! $this->timelineObj->isFollowing())
                {
                    $show_comment_publisher_box = false;
                }
            }

        } elseif ($this->data['timeline']['type'] == "group")
        {
            if (! $this->timelineObj->isFollowedBy())
            {
                $show_comment_publisher_box = false;
            }
        }
        
        if ($show_comment_publisher_box == true)
        {
        	if ($this->timelineObj->isAdmin())
        	{
        		$commentPublisherBox = $this->getCommentBox($this->data['timeline']['id']);
        	}
        	else
        	{
        		$commentPublisherBox = $this->getCommentBox();
        	}
        }

        $themeData['comment_publisher_box'] = $commentPublisherBox;

        $this->template = \SocialKit\UI::view('story/content');
        return $this->template;
	}

	public function getBasicTemplateData() {
		global $themeData;

		$themeData['story_id'] = $this->data['id'];
        $themeData['story_activity_text'] = $this->data['activity_text'];
        $themeData['story_time'] = date('c', $this->data['time']);

        $themeData['story_timeline_id'] = $this->data['timeline']['id'];
        $themeData['story_timeline_url'] = $this->data['timeline']['url'];
        $themeData['story_timeline_username'] = $this->data['timeline']['username'];
        $themeData['story_timeline_name'] = $this->data['timeline']['name'];
        $themeData['story_timeline_thumbnail_url'] = $this->data['timeline']['thumbnail_url'];        
	}

	public function getRecipientTemplate() {
		global $themeData;

		if (isset($this->data['recipient']['id']))
		{
            $themeData['story_recipient_id'] = $this->data['recipient']['id'];
            $themeData['story_recipient_url'] = $this->data['recipient']['url'];
            $themeData['story_recipient_username'] = $this->data['recipient']['username'];
            $themeData['story_recipient_name'] = $this->data['recipient']['name'];
            $themeData['story_recipient_thumbnail_url'] = $this->data['recipient']['thumbnail_url'];
            $themeData['story_recipient_html'] = \SocialKit\UI::view('story/recipient-html');
        }
	}

	public function getRemoveButtonTemplate() {
		if ($this->data['admin'] == true) {
            return \SocialKit\UI::view('story/remove-button');
        }
	}

	public function getReportButtonTemplate() {
		if ($this->data['admin'] != true && ! $this->isReported()) {
            return \SocialKit\UI::view('story/report-button');
        }
	}

	public function getLikeButtonTemplate() {
		if ($this->isLiked()) {
            return \SocialKit\UI::view('story/unlike-button');
        } else {
            return \SocialKit\UI::view('story/like-button');
        }
	}

	public function getShareButtonTemplate() {
		if ($this->isShared()) {
	        return \SocialKit\UI::view('story/unshare-button');
	    } else {
	        return \SocialKit\UI::view('story/share-button');
	    }
	}

	public function getFollowButtonTemplate() {
		if ($this->isFollowed()) {
	        return \SocialKit\UI::view('story/unfollow-button');
	    } else {
	        return \SocialKit\UI::view('story/follow-button');
	    }
	}

	public function getControlButtonTemplate() {
		
		if (isLogged()) {
			global $themeData;

        	// Remove Button
        	$themeData['story_remove_button'] = $this->getRemoveButtonTemplate();

        	// Report Button
        	$themeData['story_report_button'] = $this->getReportButtonTemplate();

		    // Like Button
        	$themeData['story_like_button'] = $this->getLikeButtonTemplate();

	        // Share Button
	        $themeData['story_share_button'] = $this->getShareButtonTemplate();

		    // Notification Button
		    $themeData['story_notification_button'] = $this->getFollowButtonTemplate();

		    return \SocialKit\UI::view('story/control-buttons');
        }
	}

	public function getTextTemplate() {
		global $themeData;

		if (! empty($this->data['text'])) {
        	$themeData['story_text'] = $this->data['text'];
        	return \SocialKit\UI::view('story/story-text');
        }
	}

	public function getMediaTemplate()
	{
		global $themeData;

		if ($this->data['media'] != false)
		{
        	if ($this->data['media']['type'] == "photos")
        	{
        		$photo_class = 'width-' . $this->data['media']['num'];
	            
	            if ($this->data['media']['num'] >= 3)
	            {
	                $photo_class = 'width-3';
	            }
	            
	            $listPhotos = '';

	            if (is_array($this->data['media']['each']))
	            {
	            	foreach ($this->data['media']['each'] as $photo)
	            	{
		                $themeData['list_photo_class'] = $photo_class;
		                $themeData['list_photo_url'] = $photo['url'];
		                $themeData['list_photo_story_id'] = $photo['post_id'];

		                $listPhotos .= \SocialKit\UI::view('story/list-photo-each');
		            }
	            }

	            $themeData['list_photos'] = $listPhotos;
	            return \SocialKit\UI::view('story/photos-html');

        	} elseif ($this->data['media']['type'] == "soundcloud") {
        		$themeData['media_url'] = $this->data['media']['each'][0]['url'];
        		return \SocialKit\UI::view('story/soundcloud-html');

        	} elseif ($this->data['media']['type'] == "youtube") {

        		$themeData['media_id'] = $this->data['media']['each'][0]['id'];
        		return \SocialKit\UI::view('story/youtube-html');

        	}
        } elseif ($this->data['location'] != false) {
        	$themeData['story_location_name'] = $this->data['location']['name'];
        	return \SocialKit\UI::view('story/map-html');
        }
	}

	public function getLocationTemplate() {
		if (! empty ($this->data['location'])) {
			$themeData['story_location_name'] = $this->data['location']['name'];
        	return \SocialKit\UI::view('story/location-html');
        }
	}

	public function getLikeActivityTemplate() {
		global $themeData;

		$themeData['story_likes_num'] = $this->numLikes();
        return \SocialKit\UI::view('story/like-activity');
	}

	public function getCommentActivityTemplate() {
		global $themeData;

		$themeData['story_comments_num'] = $this->numComments();
        return \SocialKit\UI::view('story/comment-activity');
	}

	public function getShareActivityTemplate() {
		global $themeData;

		$themeData['story_shares_num'] = $this->numShares();
        return \SocialKit\UI::view('story/share-activity');
	}

	public function getFollowActivityTemplate() {
		global $themeData;

		$themeData['story_followers_num'] = $this->numFollowers();
        return \SocialKit\UI::view('story/follow-activity');
	}

	public function getViaTemplate() {
		global $themeData;

		if (! empty ($this->via)) {
        	$themeData['story_via_id'] = $this->via['id'];
        	$themeData['story_via_url'] = $this->via['url'];
        	$themeData['story_via_username'] = $this->via['username'];
        	$themeData['story_via_name'] = $this->via['name'];
        	
        	if ($this->via['type'] == "like") {
        		$themeData['via_html'] = \SocialKit\UI::view('story/via-like-html');

        	} elseif ($this->via['type'] == "share") {
        		$themeData['via_html'] = \SocialKit\UI::view('story/via-share-html');

        	} elseif ($sk['story']['via_type'] == "tag") {
        		$themeData['via_html'] = \SocialKit\UI::view('story/via-tag-html');
        	}

        	return \SocialKit\UI::view('story/via-html');
        }
	}

	public function getLikesTemplate() {
		global $themeData;
		$i = 0;
		$listLikes = '';

        foreach ($this->getLikes() as $likerId)
        {
        	$likerObj = new \SocialKit\User();
        	$likerObj->setId($likerId);
        	$liker = $likerObj->getRows();

            $themeData['list_liker_id'] = $liker['id'];
            $themeData['list_liker_url'] = $liker['url'];
            $themeData['list_liker_username'] = $liker['username'];
            $themeData['list_liker_name'] = $liker['name'];
            $themeData['list_liker_thumbnail_url'] = $liker['thumbnail_url'];

            $themeData['list_liker_button'] = $likerObj->getFollowButton();

            $listLikes .= \SocialKit\UI::view('story/list-view-likes-each');
            $i++;
        }

        if ($i < 1) {
            $listLikes .= \SocialKit\UI::view('story/view-likes-none');
        }

        $themeData['list_likes'] = $listLikes;
        return \SocialKit\UI::view('story/view-likes');
	}

	public function getSharesTemplate() {
		global $themeData;
		$i = 0;
		$listShares = '';

        foreach ($this->getShares() as $sharerId)
        {
            $sharerObj = new \SocialKit\User();
        	$sharerObj->setId($sharerId);
        	$sharer = $sharerObj->getRows();

            $themeData['list_sharer_id'] = $sharer['id'];
            $themeData['list_sharer_url'] = $sharer['url'];
            $themeData['list_sharer_username'] = $sharer['username'];
            $themeData['list_sharer_name'] = $sharer['name'];
            $themeData['list_sharer_thumbnail_url'] = $sharer['thumbnail_url'];

            $themeData['list_sharer_button'] = $sharerObj->getFollowButton();

            $listShares .= \SocialKit\UI::view('story/list-view-shares-each');
            $i++;
        }

        if ($i < 1) {
            $listShares .= \SocialKit\UI::view('story/view-shares-none');
        }

        $themeData['list_shares'] = $listShares;
        return \SocialKit\UI::view('story/view-shares');
	}

	public function getRemoveTemplate() {
		return \SocialKit\UI::view('story/view-remove');
	}
}