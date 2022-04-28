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

const AREA = 'admin';
require __DIR__ . '/lib/init.php';

use Froxlor\Froxlor;
use Froxlor\FroxlorLogger;
use Froxlor\Http\HttpClient;
use Froxlor\Settings;
use Froxlor\UI\Panel\UI;
use Froxlor\UI\Response;

// define update-uri
define('UPDATE_URI', "https://version.froxlor.org/Froxlor/api/" . Froxlor::VERSION);
define('RELEASE_URI', "https://autoupdate.froxlor.org/froxlor-{version}.zip");
define('CHECKSUM_URI', "https://autoupdate.froxlor.org/froxlor-{version}.zip.sha256");

if ($page != 'error') {
	// check for archive-stuff
	if (!extension_loaded('zip')) {
		Response::redirectTo($filename, [
			'page' => 'error',
			'errno' => 2
		]);
	}

	// 0.11.x requires 7.4 at least
	if (version_compare("7.4.0", PHP_VERSION, ">=")) {
		Response::redirectTo($filename, [
			'page' => 'error',
			'errno' => 10
		]);
	}

	// check for webupdate to be enabled
	if (Settings::Config('enable_webupdate') != true) {
		Response::redirectTo($filename, [
			'page' => 'error',
			'errno' => 11
		]);
	}
}

// display initial version check
if ($page == 'overview') {
	// log our actions
	$log->logAction(FroxlorLogger::ADM_ACTION, LOG_NOTICE, "checking auto-update");

	// check for new version
	try {
		$latestversion = HttpClient::urlGet(UPDATE_URI, true, 3);
	} catch (Exception $e) {
		Response::dynamicError("Version-check currently unavailable, please try again later");
	}
	$latestversion = explode('|', $latestversion);

	if (is_array($latestversion) && count($latestversion) >= 1) {
		$_version = $latestversion[0];
		$_message = isset($latestversion[1]) ? $latestversion[1] : '';
		$_link = isset($latestversion[2]) ? $latestversion[2] : htmlspecialchars($filename . '?page=' . urlencode($page) . '&lookfornewversion=yes');

		// add the branding so debian guys are not gettings confused
		// about their version-number
		$version_label = $_version . Froxlor::BRANDING;
		$version_link = $_link;
		$message_addinfo = $_message;

		// not numeric -> error-message
		if (!preg_match('/^((\d+\\.)(\d+\\.)(\d+\\.)?(\d+)?(\-(svn|dev|rc)(\d+))?)$/', $_version)) {
			// check for customized version to not output
			// "There is a newer version of froxlor" besides the error-message
			Response::redirectTo($filename, [
				'page' => 'error',
				'errno' => 3
			]);
		} elseif (Froxlor::versionCompare2(Froxlor::VERSION, $_version) == -1) {
			// there is a newer version - yay
			$isnewerversion = 1;
		} else {
			// nothing new
			$isnewerversion = 0;
		}

		// anzeige über version-status mit ggfls. formular
		// zum update schritt #1 -> download
		if ($isnewerversion == 1) {
			$text = 'There is a newer version available. Update to version <b>' . $_version . '</b> now?<br/>(Your current version is: ' . Froxlor::VERSION . ')';

			$upd_formfield = [
				'updates' => [
					'title' => lng('update.update'),
					'image' => 'fa-solid fa-download',
					'sections' => [
						'section_autoupd' => [
							'fields' => [
								'newversion' => ['type' => 'hidden', 'value' => $_version]
							]
						]
					],
					'buttons' => [
						[
							'class' => 'btn-outline-secondary',
							'label' => lng('panel.cancel'),
							'type' => 'reset'
						],
						[
							'label' => lng('update.proceed')
						]
					]
				]
			];

			UI::view('user/form-note.html.twig', [
				'formaction' => $linker->getLink(['section' => 'autoupdate', 'page' => 'getdownload']),
				'formdata' => $upd_formfield['updates'],
				// alert
				'type' => 'warning',
				'alert_msg' => $text
			]);
		} elseif ($isnewerversion == 0) {
			// all good
			Response::standardSuccess('noupdatesavail');
		} else {
			Response::standardError('customized_version');
		}
	}
} // download the new archive
elseif ($page == 'getdownload') {
	// retrieve the new version from the form
	$newversion = isset($_POST['newversion']) ? $_POST['newversion'] : null;

	// valid?
	if ($newversion !== null) {
		// define files to get
		$toLoad = str_replace('{version}', $newversion, RELEASE_URI);
		$toCheck = str_replace('{version}', $newversion, CHECKSUM_URI);

		// check for local destination folder
		if (!is_dir(Froxlor::getInstallDir() . '/updates/')) {
			mkdir(Froxlor::getInstallDir() . '/updates/');
		}

		// name archive
		$localArchive = Froxlor::getInstallDir() . '/updates/' . basename($toLoad);

		$log->logAction(FroxlorLogger::ADM_ACTION, LOG_NOTICE, "Downloading " . $toLoad . " to " . $localArchive);

		// remove old archive
		if (file_exists($localArchive)) {
			@unlink($localArchive);
		}

		// get archive data
		try {
			HttpClient::fileGet($toLoad, $localArchive);
		} catch (Exception $e) {
			Response::redirectTo($filename, [
				'page' => 'error',
				'errno' => 4
			]);
		}

		// validate the integrity of the downloaded file
		$_shouldsum = HttpClient::urlGet($toCheck);
		if (!empty($_shouldsum)) {
			$_t = explode(" ", $_shouldsum);
			$shouldsum = $_t[0];
		} else {
			$shouldsum = null;
		}
		$filesum = hash_file('sha256', $localArchive);

		if ($filesum != $shouldsum) {
			Response::redirectTo($filename, [
				'page' => 'error',
				'errno' => 9
			]);
		}

		// to the next step
		Response::redirectTo($filename, [
			'page' => 'extract',
			'archive' => basename($localArchive)
		]);
	}
	Response::redirectTo($filename, [
		'page' => 'error',
		'errno' => 6
	]);
} // extract and install new version
elseif ($page == 'extract') {
	if (isset($_POST['send']) && $_POST['send'] == 'send') {
		$toExtract = isset($_POST['archive']) ? $_POST['archive'] : null;
		$localArchive = Froxlor::getInstallDir() . '/updates/' . $toExtract;
		// decompress from zip
		$zip = new ZipArchive();
		$res = $zip->open($localArchive);
		if ($res === true) {
			$log->logAction(FroxlorLogger::ADM_ACTION, LOG_NOTICE, "Extracting " . $localArchive . " to " . Froxlor::getInstallDir());
			$zip->extractTo(Froxlor::getInstallDir());
			$zip->close();
			// success - remove unused archive
			@unlink($localArchive);
			// wait a bit before we redirect to be sure
			sleep(2);
		} else {
			// error
			Response::redirectTo($filename, [
				'page' => 'error',
				'errno' => 8
			]);
		}

		// redirect to update-page?
		Response::redirectTo('admin_updates.php');
	} else {
		$toExtract = isset($_GET['archive']) ? $_GET['archive'] : null;
		$localArchive = Froxlor::getInstallDir() . '/updates/' . $toExtract;
	}

	if (!file_exists($localArchive)) {
		Response::redirectTo($filename, [
			'page' => 'error',
			'errno' => 7
		]);
	}

	$text = 'Extract downloaded archive "' . $toExtract . '"?';

	$upd_formfield = [
		'updates' => [
			'title' => lng('update.update'),
			'image' => 'fa-solid fa-download',
			'sections' => [
				'section_autoupd' => [
					'fields' => [
						'archive' => ['type' => 'hidden', 'value' => $toExtract]
					]
				]
			],
			'buttons' => [
				[
					'class' => 'btn-outline-secondary',
					'label' => lng('panel.cancel'),
					'type' => 'reset'
				],
				[
					'label' => lng('update.proceed')
				]
			]
		]
	];

	UI::view('user/form-note.html.twig', [
		'formaction' => $linker->getLink(['section' => 'autoupdate', 'page' => 'extract']),
		'formdata' => $upd_formfield['updates'],
		// alert
		'type' => 'warning',
		'alert_msg' => $text
	]);
} // display error
elseif ($page == 'error') {
	// retrieve error-number via url-parameter
	$errno = isset($_GET['errno']) ? (int)$_GET['errno'] : 0;

	// 2 = no Zlib
	// 3 = custom version detected
	// 4 = could not store archive to local hdd
	// 5 = some weird value came from version.froxlor.org
	// 6 = download without valid version
	// 7 = local archive does not exist
	// 8 = could not extract archive
	// 9 = checksum mismatch
	// 10 = <php-7.4
	// 11 = enable_webupdate = false
	Response::standardError('autoupdate_' . $errno);
}
