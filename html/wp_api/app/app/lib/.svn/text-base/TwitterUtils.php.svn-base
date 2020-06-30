<?php

class TwitterUtils {
    
    public function getTweets() {
        $twitter_user = "stockupfood_com";
        $twitter_url = 'https://api.twitter.com/1/statuses/user_timeline.json?include_entities=true&include_rts=true&screen_name='.$twitter_user.'&count=2';
        try {
            $feed = file_get_contents($twitter_url);
            $tweets = json_decode($feed);
        } catch (Exception $e) {
            return null;
        }
        
        
        $array = array();
        if (!is_null($tweets)) {
            foreach ($tweets as $tweet) {
                $title = $tweet->text;
                $link = $tweet->entities->urls;
                if (count($link) == 0) {
                    $link = "https://twitter.com/stockupfood_com";
                } else {
                    $link = $link[0]->url;
                }
                $date = $tweet->created_at;

                //Turn all urls, hastags, and @mentions into links              
                $title = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\">\\2</a>", $title);
                // $title = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\">\\2</a>", $title);
                $title = preg_replace("/@(\w+)/", "<a href=\"http://twitter.com/\\1\">@\\1</a>", $title);
                $title = preg_replace("/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\">#\\1</a>", $title);

                $formatted_date = date("M jS g:i a", strtotime($date));                                         
                $formattedTweet = array(
                    'title' => $title,
                    'link' => $link,
                    'date' => $formatted_date,
                );
                array_push($array, $formattedTweet);
            }
        }
        return $array;
    }
}    