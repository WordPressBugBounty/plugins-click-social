<?php

namespace Smashballoon\ClickSocial\App\Controllers;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Enums\WpUserCapabilities;

class SocialAccountController extends BaseController
{
	use SingleTon;

	public function showAccountList()
	{
		$response = AuthHttp::get('social-accounts');
		return $this->render('Settings/Workspace/Members', [
			'socialAccountList' => $response->getBody(true) ?? [],
			'wpUserCaps' => WpUserCapabilities::list(),
		]);
	}
}
