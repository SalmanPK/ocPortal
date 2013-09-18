<?php

class Hook_upload_syndication_photobucket
{
	var $_api=NULL;
	var $_logged_in=false;

	function get_label()
	{
		return 'Photobucket';
	}

	function get_file_handling_types()
	{
		return OCP_UPLOAD_IMAGE | OCP_UPLOAD_VIDEO;
	}

	function is_enabled()
	{
		return get_option('photobucket_client_id')!='' && get_option('photobucket_client_secret')!='';
	}

	function happens_always()
	{
		return false;
	}

	function get_reference_precedence()
	{
		return UPLOAD_PRECEDENCE_HIGH;
	}

	function _get_api()
	{
		if (is_null($this->_api))
		{
			@ini_set('ocproducts.type_strictness','0');
			require_code('photobucket/PBAPI');
			$api=new PBAPI(get_option('photobucket_client_id'),get_option('photobucket_client_secret'));
			$api->setResponseParser('phpserialize');
			$this->_api=$api;
		}
		return $this->_api;
	}

	function is_authorised()
	{
		return $this->_login();
	}

	function _login()
	{
		if (get_long_value('photobucket_oauth_key__'.strval(get_member()))===NULL) return false; // No receive_authorisation() started yet

		if ($this->_logged_in) return true;

		$api=$this->_get_api();
		$api->setOAuthToken(
			get_long_value('photobucket_oauth_key__'.strval(get_member())),
			get_long_value('photobucket_oauth_secret__'.strval(get_member()))
		);
		if (get_long_value('photobucket_oauth_subdomain__'.strval(get_member()))!==NULL)
			$api->setSubdomain(get_long_value('photobucket_oauth_subdomain__'.strval(get_member())));

		// Did it work (requires valid 'access' token, not a temporary 'request' token)
		$api->user();
		$api->get();
		try
		{
			$user=$api->getParsedResponse(true);
		}
		catch (PBAPI_Exception $e)
		{
			// Okay, so we don't have an 'access' token yet, but we may have an authorised 'access' token to get one right away...

			// Request the 'access' token, that may have been authorised for us already against a stored 'request' token
			if (get_long_value('photobucket_oauth_key__'.strval(get_member()))!==NULL)
			{
				$api=$this->_get_api();
				$api->setOAuthToken(
					get_long_value('photobucket_oauth_key__'.strval(get_member())),
					get_long_value('photobucket_oauth_secret__'.strval(get_member()))
				);
				$api->login('access');
				$api->post();

				try
				{
					$api->loadTokenFromResponse();
				}
				catch (PBAPI_Exception $e)
				{
					return false; // Maybe our 'request' token had a stale authorisation, or was never authorised -- so we fail and receive_authorisation() would need calling
				}
				$token=$api->getOAuthToken();
				set_long_value('photobucket_oauth_key__'.strval(get_member()),$token->getKey()); // Replace request token with access token
				set_long_value('photobucket_oauth_secret__'.strval(get_member()),$token->getSecret());
				set_long_value('photobucket_oauth_username__'.strval(get_member()),$api->getUsername());
				set_long_value('photobucket_oauth_subdomain__'.strval(get_member()),$api->getSubdomain());
				$this->_logged_in=true;
				return true;
			}

			return false;
		}
		$this->_logged_in=true;
		return true;
	}

	function receive_authorisation()
	{
		if ($this->is_authorised()) return true;

		$success=$this->_login();
		if ($success) return true;

		// Generate a 'request' token and go do oAuth with it
		$api=$this->_get_api();
		$api->reset(true,true,true,true); // Don't let a previous stale 'request' token we've set in _login() block us from getting a new one
		$api->login('request');
		$api->post();
		$api->loadTokenFromResponse();
		$token=$api->getOAuthToken();
		set_long_value('photobucket_oauth_key__'.strval(get_member()),$token->getKey()); // Request token for now
		set_long_value('photobucket_oauth_secret__'.strval(get_member()),$token->getSecret());
		$api->goRedirect('login','&oauth_callback='.urlencode(get_self_url(true))); // Request an 'access' token to be generated for us. Unfortunately the callback doesn't seem to work, but it's not truly needed anyway.
		exit();

		return false;
	}

	function syndicate($url,$filepath,$filename,$title,$description)
	{
		require_lang('video_syndication_photobucket');

		// Fix to correct file extensions
		$filename=preg_replace('#\.f4v#','.flv',$filename);
		$filename=preg_replace('#\.m2v#','.mpg',$filename);
		$filename=preg_replace('#\.mpv2#','.mpg',$filename);
		$filename=preg_replace('#\.mp2#','.mpg',$filename);
		$filename=preg_replace('#\.m4v#','.mp4',$filename);
		$filename=preg_replace('#\.ram#','.rm',$filename);
		require_code('files');
		$filetype=get_file_extension($filename);
		if (in_array($filetype,array('ogg','ogv','webm','pdf')))
		{
			if (!$this->happens_always())
				attach_message(do_lang_tempcode('PHOTOBUCKET_BAD_FILETYPE',escape_html($filetype)),'warn');
			return NULL;
		}

		try
		{
			$api=$this->_get_api();
			$success=$this->_login();

			$remote_gallery_name=preg_replace('#[^A-Za-z\d\_\- ]#','',get_site_name());
			$call_params=array(
				'name'=>$remote_gallery_name,
			);
			$username=get_long_value('photobucket_oauth_username__'.strval(get_member()));
			$api->album($username,$call_params);
			$api->post();
			try
			{
				$response=$api->getParsedResponse(true);
			}
			catch (PBAPI_Exception $e1)
			{
				if ($e1->getCode()!=116) // If it's not an expected possible "Album already exists: <albumname>"
					throw $e1;
			}

			require_code('images');
			$api->reset(true,true,true);
			$call_params=array(
				'type'=>is_image($filename)?'image':'video',
				'uploadfile'=>'@'.$filepath, // We can't do by URL unfortunately; that requires a business deal to be arranged, it's not allowed to the general public (for abuse reasons) http://web.archive.org/web/20120119180111/http://photobucket.com/developer/forum?read,2,319
				'filename'=>$filename,
				'title'=>$title,
				'description'=>$description,
			);
			$api->album($username.'/'.$remote_gallery_name);
			$api->upload($call_params);
			$api->post();
			$response=$api->getParsedResponse(true);
			return $response['url'];
		}
		catch (PBAPI_Exception $e2)
		{
			attach_message(do_lang_tempcode('PHOTOBUCKET_ERROR',escape_html($e2->getCode()),escape_html($e2->getMessage()),escape_html(get_site_name())));
		}
		
		return NULL;
	}
}