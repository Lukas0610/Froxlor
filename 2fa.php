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

if (!defined('AREA')) {
	header("Location: index.php");
	exit();
}

use Froxlor\Database\Database;
use Froxlor\FroxlorLogger;
use Froxlor\FroxlorTwoFactorAuth;
use Froxlor\Settings;
use Froxlor\UI\Panel\UI;
use Froxlor\UI\Response;

if (Settings::Get('2fa.enabled') != '1') {
	Response::dynamicError('2fa.2fa_not_activated');
}

// This file is being included in admin_index and customer_index
// and therefore does not need to require lib/init.php
if (AREA == 'admin') {
	$upd_stmt = Database::prepare("UPDATE `" . TABLE_PANEL_ADMINS . "` SET `type_2fa` = :t2fa, `data_2fa` = :d2fa WHERE adminid = :id");
	$uid = $userinfo['adminid'];
} elseif (AREA == 'customer') {
	$upd_stmt = Database::prepare("UPDATE `" . TABLE_PANEL_CUSTOMERS . "` SET `type_2fa` = :t2fa, `data_2fa` = :d2fa WHERE customerid = :id");
	$uid = $userinfo['customerid'];
}
$success_message = "";

$tfa = new FroxlorTwoFactorAuth('Froxlor ' . Settings::Get('system.hostname'));

// do the delete and then just show a success-message
if ($action == 'delete') {
	Database::pexecute($upd_stmt, [
		't2fa' => 0,
		'd2fa' => "",
		'id' => $uid
	]);
	Response::standardSuccess('2fa.2fa_removed');
} elseif ($action == 'add') {
	$type = isset($_POST['type_2fa']) ? $_POST['type_2fa'] : '0';

	if ($type == 0 || $type == 1) {
		$data = "";
	}
	if ($type == 2) {
		// generate secret for TOTP
		$data = $tfa->createSecret();
	}
	Database::pexecute($upd_stmt, [
		't2fa' => $type,
		'd2fa' => $data,
		'id' => $uid
	]);
	Response::standardSuccess('2fa.2fa_added', [$filename]);
}

$log->logAction(FroxlorLogger::USR_ACTION, LOG_NOTICE, "viewed 2fa::overview");

$type_select_values = [];
$ga_qrcode = '';
if ($userinfo['type_2fa'] == '0') {
	// available types
	$type_select_values = [
		0 => '-',
		1 => 'E-Mail',
		2 => 'Authenticator'
	];
	asort($type_select_values);
} elseif ($userinfo['type_2fa'] == '1') {
	// email 2fa enabled
} elseif ($userinfo['type_2fa'] == '2') {
	// authenticator 2fa enabled
	$ga_qrcode = $tfa->getQRCodeImageAsDataUri($userinfo['loginname'], $userinfo['data_2fa']);
}

UI::view('user/2fa.html.twig', [
	'themes' => $themes_avail,
	'type_select_values' => $type_select_values,
	'ga_qrcode' => $ga_qrcode
]);
