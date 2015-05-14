<?php

namespace SocialKit;

class registerStory {
    private $conn;
    private $timelineId;
    private $text;
    private $mediaId = 0;
    private $mediaExists = false;
    private $recipientId;
    private $photos = array();
    private $soundcloudUri;
    private $soundcloudTitle;
    private $youtubeId;
    private $youtubeTitle;
    private $mapName;
    private $escapeObj;
    private $timelineObj;
    private $recipientObj;
    private $timeline;
    private $recipient;
    private $continue = true;
    private $storyId;
    private $mentions = array();

    function __construct()
    {
        global $conn;
        $this->conn = $conn;
        $this->escapeObj = new \SocialKit\Escape();
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

    public function register()
    {
        if (! isLogged())
        {
            return false;
        }

        if (! $this->timeline)
        {
            $this->continue = false;
        }

        if (empty($this->text) && $this->mediaExists != true)
        {
            $this->continue = false;
        }

        if ($this->continue)
        {
            $query = $this->getConnection()->query("INSERT INTO " . DB_POSTS . "
            (active,google_map_name,media_id,soundcloud_title,soundcloud_uri,text,time,timeline_id,recipient_id,youtube_video_id,youtube_title)
            VALUES
            (1,'" . $this->mapName . "'," . $this->mediaId . ",'" . $this->soundcloudTitle . "','" . $this->soundcloudUri . "','" . $this->text . "'," . time() . "," . $this->timelineId . "," . $this->recipientId . ",'" . $this->youtubeId . "','" . $this->youtubeTitle . "')");

            if ($query)
            {
                $this->storyId = $this->getConnection()->insert_id;
                $this->getConnection()->query("UPDATE " . DB_POSTS . " SET post_id=" . $this->storyId . " WHERE id=" . $this->storyId);

                $storyObj = new \SocialKit\Story();
                $storyObj->setId($this->storyId);
                $storyObj->putFollow();

                $this->putNotification();
                return $this->storyId;
            }
        }
    }

    public function putNotification()
    {
        if (! isLogged())
        {
            return false;
        }

        global $lang, $user;
        $text = $lang['mentioned_in_post'];

        /* Notify people mentioned */
        foreach ($this->mentions as $mention)
        {
            $query = $this->getConnection()->query("SELECT id FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $mention . " AND post_id=" . $this->storyId . " AND type='post_mention' AND active=1");
            
            if ($query->num_rows > 0)
            {
                $this->getConnection()->query("DELETE FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $mention . " AND post_id=" . $this->storyId . " AND type='post_mention' AND active=1");
            }

            $this->getConnection()->query("INSERT INTO " . DB_NOTIFICATIONS . " (timeline_id,active,notifier_id,post_id,text,time,type,url) VALUES (" . $mention . ",1," . $user['id'] . "," . $this->storyId . ",'$text'," . time() . ",'post_mention','index.php?tab1=story&id=" . $this->storyId . "')");
        }

        /* Notify timeline owner if posted on someone's timeline */
        if (isset($this->recipient['id']))
        {
            $text = $lang['posted_on_timeline'];
            $query = $this->getConnection()->query("SELECT id FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $this->recipient['id'] . " AND post_id=" . $this->storyId . " AND type='timeline_wall_post' AND active=1");
            
            if ($query->num_rows > 0)
            {
                $this->getConnection()->query("DELETE FROM " . DB_NOTIFICATIONS . " WHERE timeline_id=" . $this->recipient['id'] . " AND post_id=" . $this->storyId . " AND type='timeline_wall_post' AND active=1");
            }

            $this->getConnection()->query("INSERT INTO " . DB_NOTIFICATIONS . " (timeline_id,active,notifier_id,post_id,text,time,type,url) VALUES (" . $this->recipient['id'] . ",1," . $user['id'] . "," . $this->storyId . ",'$text'," . time() . ",'timeline_wall_post','index.php?tab1=story&id=" . $this->storyId . "')");
        }

        return true;
    }

    public function setTimeline($id=0)
    {
        $this->timelineId = (int) $id;
        $this->timelineObj = new \SocialKit\User();
        $this->timelineObj->setId($this->timelineId);
        
        if (! $this->timelineObj->isAdmin())
        {
            $this->timeline = $this->timelineObj->getRows();
        }
    }

    public function setText($t='')
    {
        if (empty($t))
        {
            return false;
        }

        global $config;

        if ($config['story_character_limit'] > 0)
        {
            if (strlen($t) > $config['story_character_limit'])
            {
                return false;
            }
        }

        $t = $this->escapeObj->createLinks($t);
        $t = $this->escapeObj->createHashtags($t);
        $mentions = $this->escapeObj->createMentions($t);
        $t = $mentions['content'];
        $this->text = $this->escapeObj->stringEscape($t);
        $this->mentions = $mentions['mentions'];
    }

    public function setRecipient($id=0)
    {
        $this->recipientId = (int) $id;
        $this->recipientObj = new \SocialKit\User();
        $this->recipientObj->setId($this->recipientId);
        $this->recipient = $this->recipientObj->getRows();

        if (isset($this->recipient['id']))
        {
            if ($this->recipient['type'] == "user")
            {
                if ($this->recipient['timeline_post_privacy'] == "following")
                {
                    if (! $this->recipientObj->isFollowedBy($this->timelineId))
                    {
                        $this->continue = false;
                    }
                }
                elseif ($this->recipient['timeline_post_privacy'] == "none")
                {
                    $this->continue = false;
                }
            }
            elseif ($this->recipient['type'] == "page")
            {
                if ($this->recipient['timeline_post_privacy'] != "everyone")
                {
                    if (! $this->recipientObj->isPageAdmin())
                    {
                        $this->continue = false;
                    }
                }
            }
            elseif ($this->recipient['type'] == "group")
            {
                if ($this->recipient['timeline_post_privacy'] == "members")
                {
                    if (! $this->recipientObj->isFollowedBy())
                    {
                        $this->continue = false;
                    }
                }
                elseif ($this->recipient['timeline_post_privacy'] == "admins")
                {
                    if (! $this->isGroupAdmin())
                    {
                        $this->continue = false;
                    }
                }
            }
        }
    }

    public function setPhotos($a='')
    {
        if (is_array($a))
        {
            $this->photos = $a;
            $count = count($this->photos['name']);

            if ($count == 1)
            {
                $params = array(
                    'tmp_name' => $this->photos['tmp_name'][0],
                    'name' => $this->photos['name'][0],
                    'size' => $this->photos['size'][0]
                );
                $media = registerMedia($params);
                
                if (isset($media['id']))
                {
                    $this->mediaId = $media['id'];
                    $this->mediaExists = true;
                }
            }
            else
            {
                $query = $this->getConnection()->query("INSERT INTO " . DB_MEDIA . " (timeline_id,active,name,type) VALUES (" . $this->timelineId . ",1,'temp_" . generateKey() . "','album')");
                
                if ($query)
                {
                    $albumId = $this->getConnection()->insert_id;
                    $this->mediaId = $albumId;

                    for ($i = 0; $i < $count; $i++)
                    {
                        $params = array(
                            'tmp_name' => $this->photos['tmp_name'][$i],
                            'name' => $this->photos['name'][$i],
                            'size' => $this->photos['size'][$i]
                        );
                        $media = registerMedia($params, $this->mediaId);

                        if (isset($media['id']))
                        {
                            $query2 = $this->getConnection()->query("INSERT INTO " . DB_POSTS . " (active,google_map_name,hidden,media_id,time,timeline_id,recipient_id) VALUES (1,'" . $this->mapName . "',1," . $media['id'] . "," . time() . "," . $this->timelineId . "," . $this->recipientId . ")");

                            if ($query2)
                            {
                                $mediaPostId = $this->getConnection()->insert_id;

                                $this->getConnection()->query("UPDATE " . DB_POSTS . " SET post_id=id WHERE id=$mediaPostId");
                                $this->getConnection()->query("UPDATE " . DB_MEDIA . " SET post_id=$mediaPostId WHERE id=" . $media['id']);

                                $mediaPostObj = new \SocialKit\Story();
                                $mediaPostObj->setId($mediaPostId);
                                $mediaPost = $mediaPostObj->getRows();
                                
                                $mediaPostObj->putFollow();
                            }
                        }
                    }
                }
            }
        }
    }

    public function setSoundcloudUri($uri='')
    {
        if (! empty($uri))
        {
            $this->soundcloudUri = $this->escapeObj->stringEscape($uri);
            $this->mediaExists = true;
        }
    }

    public function setSoundcloudTitle($t='')
    {
        if (! empty($t))
        {
            $this->soundcloudTitle = $this->escapeObj->stringEscape($t);
            $this->mediaExists = true;
        }
    }

    public function setYoutubeId($yid='')
    {
        if (! empty($yid) && preg_match('/^[A-Za-z0-9_\-]+$/', $yid))
        {
            $this->youtubeId = $this->escapeObj->stringEscape($yid);
            $this->mediaExists = true;
            return true;
        }

        $regex_one = '/^(http\:\/\/|https\:\/\/|)(www\.|)youtube\.com\/watch\?v\=([A-Za-z0-9_\-]+)/i';
        $regex_two = '/^(http\:\/\/|https\:\/\/|)(www\.|)youtu\.be\/([A-Za-z0-9_\-]+)/i';
        $regex_three = '/^(http\:\/\/|https\:\/\/|)(www\.|)youtube\.com\/embed\/([A-Za-z0-9_\-]+)/i';
        $regex_four = '/^(http\:\/\/|https\:\/\/|)(www\.|)youtube\.com\/v\/([A-Za-z0-9_\-]+)/i';
        
        if (preg_match($regex_one, $yid, $matches))
        {
            $this->youtubeId = $matches[3];
        }
        elseif (preg_match($regex_two, $yid, $matches))
        {
            $this->youtubeId = $matches[3];
        }
        elseif (preg_match($regex_three, $yid, $matches))
        {
            $this->youtubeId = $matches[3];
        }
        elseif (preg_match($regex_four, $yid, $matches))
        {
            $this->youtubeId = $matches[3];
        }
    }

    public function setYoutubeTitle($t='')
    {
        $this->youtubeTitle = $this->escapeObj->stringEscape($t);
    }

    public function setMapName($n='')
    {
        if (! empty($n))
        {
            $this->mapName = $this->escapeObj->stringEscape($n);
            $this->mediaExists = true;
        }
    }
}