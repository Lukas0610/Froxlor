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

return [
	'install' => [
		'title' => lng('admin.admin_add'),
		'image' => 'fa-solid fa-user-plus',
		'self_overview' => ['section' => 'admins', 'page' => 'admins'],
		'sections' => [
			'step1' => [
				'title' => lng('install.dabatase'),
				'fields' => [
					'sql_hostname' => [
						'label' => lng('sql_hostname'),
						'type' => 'text',
						'mandatory' => true,
						'value' => 'localhost'
					],
					'sql_root_username' => [
						'label' => lng('sql_root_username'),
						'type' => 'password',
						'mandatory' => true,
						'next_to' => [
							'sql_root_password' => [
								'label' => lng('sql_root_password'),
								'type' => 'password',
								'mandatory' => true
							],
						]
					],
					'sql_username' => [
						'label' => lng('sql_username'),
						'type' => 'password',
						'mandatory' => true,
						'next_to' => [
							'sql_password' => [
								'label' => lng('sql_password'),
								'type' => 'password',
								'mandatory' => true
							],
						]
					],
				]
			],
			'step2' => [
				'title' => lng('admin.contactdata'),
				'image' => 'icons/user_add.png',
				'fields' => [
					'name' => [
						'label' => lng('name'),
						'type' => 'text',
						'mandatory' => true
					],
					'username' => [
						'label' => lng('username'),
						'type' => 'text',
						'mandatory' => true
					],
					'password' => [
						'label' => lng('password'),
						'type' => 'password',
						'mandatory' => true
					],
					'email' => [
						'label' => lng('email'),
						'type' => 'text',
						'mandatory' => true
					],
				]
			],
			'step3' => [
				'title' => lng('admin.servicedata'),
				'image' => 'icons/user_add.png',
				'fields' => [
					'ipaddress' => [
						'label' => lng('serversettings.ipaddress.title'),
						'type' => 'select'
					],
					'change_serversettings' => [
						'label' => lng('admin.change_serversettings'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => false
					],
					'customers' => [
						'label' => lng('admin.customers'),
						'type' => 'textul',
						'value' => 0,
						'maxlength' => 9,
						'mandatory' => true
					],
					'customers_see_all' => [
						'label' => lng('admin.customers_see_all'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => false
					],
					'domains' => [
						'label' => lng('admin.domains'),
						'type' => 'textul',
						'value' => 0,
						'maxlength' => 9,
						'mandatory' => true
					],
					'domains_see_all' => [
						'label' => lng('admin.domains_see_all'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => false
					],
					'caneditphpsettings' => [
						'label' => lng('admin.caneditphpsettings'),
						'type' => 'checkbox',
						'value' => '1',
						'checked' => false
					],
					'subdomains' => [
						'label' => lng('customer.subdomains'),
						'type' => 'textul',
						'value' => 0,
						'maxlength' => 9,
						'mandatory' => true
					],
					'emails' => [
						'label' => lng('customer.emails'),
						'type' => 'textul',
						'value' => 0,
						'maxlength' => 9,
						'mandatory' => true
					],
					'email_accounts' => [
						'label' => lng('customer.accounts'),
						'type' => 'textul',
						'value' => 0,
						'maxlength' => 9,
						'mandatory' => true
					],
					'email_forwarders' => [
						'label' => lng('customer.forwarders'),
						'type' => 'textul',
						'value' => 0,
						'maxlength' => 9,
						'mandatory' => true
					],
					'ftps' => [
						'label' => lng('customer.ftps'),
						'type' => 'textul',
						'value' => 0,
						'maxlength' => 9
					],
					'mysqls' => [
						'label' => lng('customer.mysqls'),
						'type' => 'textul',
						'value' => 0,
						'maxlength' => 9,
						'mandatory' => true
					]
				]
			]
		]
	]
];
