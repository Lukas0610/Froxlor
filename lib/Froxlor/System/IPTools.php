<?php

namespace Froxlor\System;

class IPTools
{

	/**
	 * Converts CIDR to a netmask address
	 *
	 * @thx to https://stackoverflow.com/a/5711080/3020926
	 * @param string $cidr
	 *
	 * @return string
	 */
	public static function cidr2NetmaskAddr($cidr)
	{
		$ta = substr($cidr, strpos($cidr, '/') + 1) * 1;
		$netmask = str_split(str_pad(str_pad('', $ta, '1'), 32, '0'), 8);

		foreach ($netmask as &$element) {
			$element = bindec($element);
		}

		return implode('.', $netmask);
	}

	/**
	 * Checks if an $address (IP) is IPv6
	 *
	 * @param string $address
	 *
	 * @return string|bool ip address on success, false on failure
	 */
	public static function is_ipv6($address)
	{
		return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}

	/**
	 * Checks whether the given $ip is in range of given ip/cidr range
	 *
	 * @param array $ip_cidr 0 => ip, 1 => netmask in decimal, e.g. [0 => '123.123.123.123', 1 => 24]
	 * @param string $ip ip-address to check
	 *
	 * @return bool
	 */
	public static function ip_in_range(array $ip_cidr, string $ip): bool
	{
		$netip = $ip_cidr[0];
		if (self::is_ipv6($netip)) {
			return self::ipv6_in_range($ip_cidr, $ip);
		}
		$netmask = $ip_cidr[1];
		$range_decimal = ip2long($netip);
		$ip_decimal = ip2long($ip);
		$wildcard_decimal = pow(2, (32 - $netmask)) - 1;
		$netmask_decimal = ~$wildcard_decimal;
		return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
	}

	/**
	 * Checks whether the given ipv6 $ip is in range of given ip/cidr range
	 *
	 * @param array $ip_cidr 0 => ip, 1 => netmask in decimal, e.g. [0 => '123:123::1', 1 => 64]
	 * @param string $ip ip-address to check
	 *
	 * @return bool
	 */
	private static function ipv6_in_range(array $ip_cidr, string $ip): bool
	{
		$in_range = false;

		$size = 128 - $ip_cidr[1];
		if ($size == 0) {
			return inet_ntop(inet_pton($ip_cidr[0])) == inet_ntop(inet_pton($ip));
		}
		$addr = gmp_init('0x' . str_replace(':', '', self::inet6_expand($ip_cidr[0])));
		$mask = gmp_init('0x' . str_replace(':', '', self::inet6_expand(self::inet6_prefix_to_mask($ip_cidr[1]))));
		$prefix = gmp_and($addr, $mask);
		$start = gmp_strval(gmp_add($prefix, '0x1'), 16);
		$end = '0b';
		for ($i = 0; $i < $size; $i++) {
			$end .= '1';
		}
		$end = gmp_strval(gmp_add($prefix, gmp_init($end)), 16);
		$start_result = '';
		for ($i = 0; $i < 8; $i++) {
			$start_result .= substr($start, $i * 4, 4);
			if ($i != 7) $start_result .= ':';
		}
		$end_result = '';
		for ($i = 0; $i < 8; $i++) {
			$end_result .= substr($end, $i * 4, 4);
			if ($i != 7) $end_result .= ':';
		}

		$first = self::ip2long6($start_result);
		$last = self::ip2long6($end_result);
		$ip = self::ip2long6($ip);

		$in_range = ($ip >= $first && $ip <= $last);
		return $in_range;
	}

	private static function ip2long6($ip)
	{
		$ip_n = inet_pton($ip);
		$bits = 15; // 16 x 8 bit = 128bit
		$ipv6long = '';
		while ($bits >= 0) {
			$bin = sprintf("%08b", (ord($ip_n[$bits])));
			$ipv6long = $bin . $ipv6long;
			$bits--;
		}
		return gmp_strval(gmp_init($ipv6long, 2), 10);
	}

	private static function inet6_expand(string $addr)
	{
		// Check if there are segments missing, insert if necessary
		if (strpos($addr, '::') !== false) {
			$part = explode('::', $addr);
			$part[0] = explode(':', $part[0]);
			$part[1] = explode(':', $part[1]);
			$missing = array();
			for ($i = 0; $i < (8 - (count($part[0]) + count($part[1]))); $i++)
				array_push($missing, '0000');
			$missing = array_merge($part[0], $missing);
			$part = array_merge($missing, $part[1]);
		} else {
			$part = explode(":", $addr);
		}
		// Pad each segment until it has 4 digits
		foreach ($part as &$p) {
			while (strlen($p) < 4) $p = '0' . $p;
		}
		unset($p);
		// Join segments
		$result = implode(':', $part);
		// Quick check to make sure the length is as expected
		if (strlen($result) == 39) {
			return $result;
		} else {
			return false;
		}
	}

	private static function inet6_prefix_to_mask($prefix)
	{
		/* Make sure the prefix is a number between 1 and 127 (inclusive) */
		$prefix = intval($prefix);
		if ($prefix < 0 || $prefix > 128) return false;
		$mask = '0b';
		for ($i = 0; $i < $prefix; $i++) $mask .= '1';
		for ($i = strlen($mask) - 2; $i < 128; $i++) $mask .= '0';
		$mask = gmp_strval(gmp_init($mask), 16);
		$result = '';
		for ($i = 0; $i < 8; $i++) {
			$result .= substr($mask, $i * 4, 4);
			if ($i != 7) $result .= ':';
		} // for
		return inet_ntop(inet_pton($result));
	}
}
