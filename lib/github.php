<?php
/** 
 * Required configuration 
 * GITHUB_APP_ID (application id)
 * GITHUB_APP_SECRET (application secret)
 * @global $HTTP_CONFIG (http configuration to use)
 * @see http://pear.php.net/manual/en/package.http.http-request2.config.php
 */
class Github extends Module{
	const name='github';
	function __construct()
	{
	}
	const ACCESS_TOKEN_URL='https://github.com/login/oauth/access_token';
	const USER_URL="https://api.github.com/user";
	public static function login()
	{
		header("Location: https://github.com/login/oauth/authorize?client_id=".GITHUB_APP_ID);
	}
	function update(){}
	public static function callback()
	{
		global $HTTP_CONFIG;
		//exchange the code you get for a access_token
		$code=$_GET['code'];
		require "HTTP/Request2.php";
		$request = new HTTP_Request2(self::ACCESS_TOKEN_URL);
		$request->setMethod(HTTP_Request2::METHOD_POST);
		$request->setConfig($HTTP_CONFIG);
		$request->addPostParameter(array(
			'client_id'=>GITHUB_APP_ID,
			'client_secret'=>GITHUB_APP_SECRET,
			'code'=>$code
		));
		$request->setHeader('Accept','application/json');
		$response = $request->send();
		$response = json_decode($response->getBody());
		$access_token=$response->access_token;
		
		//Use this access token to get user details
		$request = new HTTP_Request2(self::USER_URL.'?access_token='.$access_token,
			HTTP_Request2::METHOD_GET,
			$HTTP_CONFIG
		);
		$response = $request->send()->getBody();
		//Get the userid, and perform the organization check
		$userid=json_decode($response)->login;
		$request = new HTTP_Request2(json_decode($response)->organizations_url.'?access_token='.$access_token,
			HTTP_Request2::METHOD_GET,
			$HTTP_CONFIG
		);
		$response = $request->send()->getBody();
		$organizations_list=array_map(
			function($repo){
				return $repo->login;
			},
			json_decode($response)
		);
		if(in_array(GITHUB_ORGANIZATION,$organizations_list))
		{
			Token::add('github',$userid,$access_token);
			$_SESSION['userid']=$userid;
			redirect_to('/');
		}
		else
		{
			throw new Exception("You are not in the listed members.");
		}
	}
}
