<?php

namespace Smashballoon\ClickSocial\App\Controllers;

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Enums\WpUserCapabilities;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (! defined('ABSPATH')) {
	exit;
}

class QuickShareController extends BaseController
{
	use SingleTon;

	public function show($request)
	{
		$res = AuthHttp::get('social-accounts');
		$social_accounts = $res->getBody(true)['data'] ?? [];
		$quick_share = SettingsManager::getInstance()->get('quick_share', false);

		return $this->render('Settings/Workspace/QuickShare', [
			'quick_share' => $quick_share,
			'social_accounts' => $social_accounts,
		]);
	}

	public function store($request)
	{
		$social_accounts = $request->input('selected_accounts');
		$templates = $request->input('templates');

		$quickshare_data = [
			'accounts'	=> $this->mapAccountsWithTemplates($social_accounts, $templates),
			'action'	=> sanitize_text_field($request->input('action')),
			'status'	=> sanitize_text_field($request->input('status'))
		];
		SettingsManager::getInstance()->update('quick_share', $quickshare_data);

		$res = AuthHttp::get('social-accounts');
		$social_accounts = $res->getBody(true)['data'] ?? [];

		return $this->render('Settings/Workspace/QuickShare', [
			'successMsg'		=> __('Quick share settings updated', 'click-social'),
			'quick_share'		=> $quickshare_data,
			'social_accounts'	=> $social_accounts,
		]);
	}

	protected function mapAccountsWithTemplates($accounts, $templates)
	{
		if (empty($accounts) || empty($templates)) {
			return $accounts;
		}

		foreach ($accounts as $key => $account) {
			foreach ($templates as $template) {
				if ($account['uuid'] === $template['uuid']) {
					$accounts[$key]['templates'] = $template;
				}
			}
		}

		return $accounts;
	}

	public function isQuickShareEnabled()
	{
		$quick_share = SettingsManager::getInstance()->get('quick_share', false);

		if (
			! $quick_share ||
			empty($quick_share['status']) ||
			empty($quick_share['accounts']) ||
			empty($quick_share['action'])
		) {
			return false;
		}

		return true;
	}

	public function memberPermissions($user_id = null)
	{
		$memberData = MemberTransaction::getMemberData($user_id);
		if (empty($memberData['role'])) {
			return false;
		}

		if (
			WpUserRoles::SuperAdmin === $memberData['role'] ||
			WpUserRoles::Admin === $memberData['role']
		) {
			return true;
		}

		$quick_share = SettingsManager::getInstance()->get('quick_share', false);
		$view_only = true;

		if (! empty($memberData['social_accounts']) && is_array($memberData['social_accounts'])) {
			foreach ($memberData['social_accounts'] as $social_account) {
				foreach ($quick_share['accounts'] as $account) {
					if ($account['uuid'] === $social_account['social_account_uuid']) {
						if (WpUserCapabilities::ViewOnly !== $social_account['capability']) {
							$view_only = false;
							break;
						}
					}
				}

				if (! $view_only) {
					break;
				}
			}
		}

		if (! $view_only) {
			return true;
		}

		return false;
	}
}
