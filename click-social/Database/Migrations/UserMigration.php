<?php

namespace Smashballoon\ClickSocial\Database\Migrations;

if (!defined('ABSPATH')) exit;

use CodesVault\Howdyqb\DB;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;

class UserMigration
{
	use SingleTon;

	public function __construct()
	{
		// DB::create('sbcs_user')
		// 	->column('ID')->bigInt()->unsigned()->autoIncrement()->primary()->required()
		// ->column('name')->string(255)->required()
		// 	->column('email')->string(320)->required()
		// ->column('password')->string(500)->required()
		// ->column('token')->string(500)->required()
		// ->column('created_at')->dateTime()->required()
		// 	->execute();
	}
}
