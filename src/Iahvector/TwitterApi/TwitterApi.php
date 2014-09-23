<?php namespace Iahvector\TwitterApi;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Exception\ClientException;

class TwitterApi {
 
	public function authenticate($consumerKey, $consumerSecret, $callbackUrl) {
		$client = new Client(['base_url' => 'https://api.twitter.com/',
	                      'defaults' => ['auth' => 'oauth']]);

		$oauth = new Oauth1([
		    'consumer_key'    => $consumerKey,
		    'consumer_secret' => $consumerSecret,
		]);

		$client->getEmitter()->attach($oauth);

		try {
			$res = $client->post('oauth/request_token', [ 'body' => [ 'oauth_callback' => $callbackUrl ]]);
		} catch (ClientException $e) {
    		return array('status' => $e->getResponse()->getStatusCode(), 'message' => $e->getMessage());
    	} catch (Exception $e) {
	        return array('status' => 500, 'message' => $e->getMessage());
    	}

		if ($res->getStatusCode() != 200) {
			$error = "request_token res is not ok";
		    return array('status' => $res->getStatusCode(), 'message' => $error);
		}

		$body = $res->getBody();

		$tokenData = array();
		foreach (explode('&', $body) as $string) {
		    $data = explode('=', $string);
		    $tokenData[$data[0]] = $data[1];
		}

		if (!empty($tokenData['oauth_token']) && !empty($tokenData['oauth_token_secret']) && $tokenData['oauth_callback_confirmed'] == true) {
		    $authenticate_url = "https://api.twitter.com/oauth/authenticate?oauth_token=" . $tokenData['oauth_token'];
		    return array('status' => 200, 'authenticate_url' => $authenticate_url);
		} else {
		    $error = "request_token res body does not contain token data";
		    return array('status' => 500, 'message' => $error);
		}
	}

	public function consumeCallback($consumerKey, $consumerSecret, $oauthToken, $oauthVerifier) {
		$client = new Client(['base_url' => 'https://api.twitter.com/',
	                              'defaults' => ['auth' => 'oauth']]);

		$oauth = new Oauth1([
		    'consumer_key'    => $consumerKey,
		    'consumer_secret' => $consumerSecret,
		    'token' => $oauthToken,
		]);

		$client->getEmitter()->attach($oauth);

		try {
			$res = $client->post('oauth/access_token', [ 'body' => [ 'oauth_verifier' => $oauthVerifier ]]);
		} catch (ClientException $e) {
    		return array('status' => $e->getResponse()->getStatusCode(), 'message' => $e->getMessage());
    	} catch (Exception $e) {
	        return array('status' => 500, 'message' => $e->getMessage());
    	}

		if ($res->getStatusCode() != 200) {
			$error = "request_token res is not ok";
		    return array('status' => $res->getStatusCode(), 'message' => $error);
		}

		$body = $res->getBody();

		$tokenData = array();
		foreach (explode('&', $body) as $string) {
		    $data = explode('=', $string);
		    $tokenData[$data[0]] = $data[1];
		}

		if (empty($tokenData['oauth_token'])
			&& empty($tokenData['oauth_token_secret'])
			&& empty($tokenData['user_id'])
			&& empty($tokenData['screen_name'])) {

			$error = "request_token res body does not contain token data";
			return array('status' => 500, 'message' => $error);
		}

		return array(
			'status' => 200,
			'oauth_token' => $tokenData['oauth_token'],
			'oauth_token_secret' => $tokenData['oauth_token_secret'],
			'user_id' => $tokenData['user_id'],
			'screen_name' => $tokenData['screen_name']);
	}

	public function verifyCredentials($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret) {
		$client = new Client(['base_url' => 'https://api.twitter.com/',
	                              'defaults' => ['auth' => 'oauth']]);

		$oauth = new Oauth1([
	        'consumer_key'    => $consumerKey,
	        'consumer_secret' => $consumerSecret,
            'token' => $accessToken,
            'token_secret' => $accessTokenSecret
        ]);

        $client->getEmitter()->attach($oauth);

        try {
	        $res = $client->get('1.1/account/verify_credentials.json');
        } catch (ClientException $e) {
    		return array('status' => $e->getResponse()->getStatusCode(), 'message' => $e->getMessage());
    	} catch (Exception $e) {
	        return array('status' => 500, 'message' => $e->getMessage());
    	}

        if ($res->getStatusCode() != 200) {
        	return array('status' => 500, 'message' => "verify_credentials res is not ok");
        }
        
        $twitterUser = json_decode($res->getBody(), true);

        $result['status'] = 200;
		$result['twitter_user']['profile_image'] = $twitterUser['profile_image_url'];
        $result['twitter_user']['profile_background_image'] = $twitterUser['profile_background_image_url'];
        $result['twitter_user']['name'] = $twitterUser['name'];
        $result['twitter_user']['screen_name'] = $twitterUser['screen_name'];
        $result['twitter_user']['access_token'] = $twitterUser['oauth_token'];
        $result['twitter_user']['access_token_secret'] = $twitterUser['oauth_token_secret'];
        
        return $result;
	}

	public function hydrateTweets($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, $IDsList) {
		$client = new Client(['base_url' => 'https://api.twitter.com/',
	                              'defaults' => ['auth' => 'oauth']]);

		$oauth = new Oauth1([
	        'consumer_key'    => $consumerKey,
	        'consumer_secret' => $consumerSecret,
            'token' => $accessToken,
            'token_secret' => $accessTokenSecret
        ]);

        $client->getEmitter()->attach($oauth);

		try {
			$res = $client->post('1.1/statuses/lookup.json', ['body' => ['id' => implode(',', $IDsList)]]);
		} catch (ClientException $e) {
    		return array('status' => $e->getResponse()->getStatusCode(), 'message' => $e->getMessage());
    	} catch (Exception $e) {
	        return array('status' => 500, 'message' => $e->getMessage());
    	}

		if ($res->getStatusCode() != 200) {
        	return array('status' => 500, 'message' => "Hydrate tweets res is not ok");
		}
	
		$hydratedTweets = json_decode($res->getBody(), true);

		$result['status'] = 200;
		$result['hydrated_tweets'] = $hydratedTweets;

		return $result;
	}

	public function hydrateUsers($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, $IDsList, $includeEntities = false) {
		$client = new Client(['base_url' => 'https://api.twitter.com/',
	                              'defaults' => ['auth' => 'oauth']]);

		$oauth = new Oauth1([
	        'consumer_key'    => $consumerKey,
	        'consumer_secret' => $consumerSecret,
            'token' => $accessToken,
            'token_secret' => $accessTokenSecret
        ]);

        $client->getEmitter()->attach($oauth);

        try {
    		$res = $client->post('1.1/users/lookup.json', ['body' => ['screen_name' => implode(',', $IDsList), 'include_entities' => $includeEntities]]);
    	} catch (ClientException $e) {
    		return array('status' => $e->getResponse()->getStatusCode(), 'message' => $e->getMessage());
    	} catch (Exception $e) {
	        return array('status' => 500, 'message' => $e->getMessage());
    	}

    	if ($res->getStatusCode() != 200) {
        	return array('status' => 500, 'message' => "Hydrate tweets res is not ok");
		}

    	$hydratedUsers = json_decode($res->getBody(), true);

    	$result['status'] = 200;
    	$result['hydrated_users'] = $hydratedUsers;

    	return $result;
    }

    public function search($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, $query, $resultType, $count, $maxID) {
    	$client = new Client(['base_url' => 'https://api.twitter.com/',
                              'defaults' => ['auth' => 'oauth']]);

		$oauth = new Oauth1([
	        'consumer_key'    => $consumerKey,
	        'consumer_secret' => $consumerSecret,
            'token' => $accessToken,
            'token_secret' => $accessTokenSecret
        ]);

        $client->getEmitter()->attach($oauth);

        $searchParams['q'] = $query;
        if ($resultType == 'recent' || $resultType == 'popular' || $resultType == 'mixed') {
        	$searchParams['result_type'] = $resultType;
        }
        if (is_numeric($count) && $count > 0) {
        	$searchParams['count'] = $count;
        }
        if (!empty($maxID)) {
        	$searchParams['max_id'] = $maxID;
        }

        try {
        	$res = $client->get('1.1/search/tweets.json', ['query' => $searchParams]);
        } catch (ClientException $e) {
    		return array('status' => $e->getResponse()->getStatusCode(), 'message' => $e->getMessage());
    	} catch (Exception $e) {
	        return array('status' => 500, 'message' => $e->getMessage());
    	}

    	if ($res->getStatusCode() != 200) {
        	return array('status' => 500, 'message' => "Hydrate tweets res is not ok");
		}

    	$result['status'] = 200;
    	$result['tweets'] = json_decode($res->getBody(), true);

    	return $result;
    }

    public function updateStatus($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, $text) {
    	$client = new Client(['base_url' => 'https://api.twitter.com/',
    						  'defaults' => ['auth' => 'oauth']]);

		$oauth = new Oauth1([
	        'consumer_key'    => $consumerKey,
	        'consumer_secret' => $consumerSecret,
            'token' => $accessToken,
            'token_secret' => $accessTokenSecret,
        ]);

        $client->getEmitter()->attach($oauth);

	    try {
	        $res = $client->post('1.1/statuses/update.json', [ 'body' => [ 'status' => $text]]);
	    } catch (ClientException $e) {
    		return array('status' => $e->getResponse()->getStatusCode(), 'message' => $e->getMessage());
    	} catch (Exception $e) {
	        return array('status' => 500, 'message' => $e->getMessage());
    	}

    	$tweet = json_decode($res->getBody(), true);

    	$result['status'] = 200;
    	$result['tweet'] = $tweet;

    	return $result;
    }
}