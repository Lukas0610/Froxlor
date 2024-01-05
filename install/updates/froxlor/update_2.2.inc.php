<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, you can also view it online at
 * https://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  the authors
 * @author     Froxlor team <team@froxlor.org>
 * @license    https://files.froxlor.org/misc/COPYING.txt GPLv2
 */

use Froxlor\Database\Database;
use Froxlor\FileDir;
use Froxlor\Froxlor;
use Froxlor\Install\Update;
use Froxlor\Settings;

if (!defined('_CRON_UPDATE')) {
	if (!defined('AREA') || (defined('AREA') && AREA != 'admin') || !isset($userinfo['loginname']) || (isset($userinfo['loginname']) && $userinfo['loginname'] == '')) {
		header('Location: ../../../../index.php');
		exit();
	}
}

if (Froxlor::isFroxlorVersion('2.1.x')) {
	Update::showUpdateStep("Enhancing virtual email table");
	Database::query("ALTER TABLE `" . TABLE_MAIL_VIRTUAL . "` ADD `spam_tag_level` float(4,1) NOT NULL DEFAULT 7.0;");
	Database::query("ALTER TABLE `" . TABLE_MAIL_VIRTUAL . "` ADD `spam_kill_level` float(4,1) NOT NULL DEFAULT 14.0;");
	Database::query("ALTER TABLE `" . TABLE_MAIL_VIRTUAL . "` ADD `bypass_spam` tinyint(1) NOT NULL default '0';");
	Database::query("ALTER TABLE `" . TABLE_MAIL_VIRTUAL . "` ADD `policy_greylist` tinyint(1) NOT NULL default '1';");
	Update::lastStepStatus(0);

	Update::showUpdateStep("Adjusting settings");
	Database::query("UPDATE `" . TABLE_PANEL_SETTINGS . "` SET `settinggroup` = 'antispam', `varname` = 'activated' WHERE `settinggroup` = 'dkim' AND `varname` = 'use_dkim';");
	Database::query("UPDATE `" . TABLE_PANEL_SETTINGS . "` SET `settinggroup` = 'antispam', `varname` = 'reload_command' WHERE `settinggroup` = 'dkim' AND `varname` = 'dkimrestart_command';");
	Database::query("UPDATE `" . TABLE_PANEL_SETTINGS . "` SET `settinggroup` = 'antispam', `varname` = 'config_file', `value` = '/etc/rspamd/local.d/froxlor_settings.conf' WHERE `settinggroup` = 'dkim' AND `varname` = 'dkim_prefix';");
	Database::query("UPDATE `" . TABLE_PANEL_SETTINGS . "` SET `settinggroup` = 'antispam' WHERE `settinggroup` = 'dkim' AND `varname` = 'dkim_keylength';");
	Settings::AddNew("dmarc.use_dmarc", "0");
	Settings::AddNew("dmarc.dmarc_entry", "v=DMARC1; p=none;");
	Database::query("DELETE FROM `" . TABLE_PANEL_SETTINGS . "` WHERE `settinggroup` = 'dkim' AND `varname` = 'privkeysuffix';");
	Database::query("DELETE FROM `" . TABLE_PANEL_SETTINGS . "` WHERE `settinggroup` = 'dkim' AND `varname` = 'dkim_domains';");
	Database::query("DELETE FROM `" . TABLE_PANEL_SETTINGS . "` WHERE `settinggroup` = 'dkim' AND `varname` = 'dkim_algorithm';");
	Database::query("DELETE FROM `" . TABLE_PANEL_SETTINGS . "` WHERE `settinggroup` = 'dkim' AND `varname` = 'dkim_notes';");
	Update::lastStepStatus(0);

	$to_clean = [
		'actions/admin/settings/180.dkim.php',
		'actions/admin/settings/185.spf.php',
	];
	Update::cleanOldFiles($to_clean);

	Froxlor::updateToDbVersion('202312230');
	Froxlor::updateToVersion('2.2.0-dev1');
}