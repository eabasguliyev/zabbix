<?php declare(strict_types = 1);
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * Class is used to parse <sec|#num>:<time_shift> trigger parameter.
 */
class CPeriodParser extends CParser {

	/**
	 * User macro parser.
	 *
	 * @var CUserMacroParser
	 */
	private $user_macro_parser;

	/**
	 * LLD macro parser.
	 *
	 * @var CLLDMacroParser
	 */
	private $lld_macro_parser;

	/**
	 * Parsed data.
	 *
	 * @var CPeriodParserResult
	 */
	public $result;

	/**
	 * @param array $options
	 */
	public function __construct() {
		$this->user_macro_parser = new CUserMacroParser();
		$this->lld_macro_parser = new CLLDMacroParser();
	}

	/**
	 * Parse period.
	 *
	 * @param string $source
	 * @param int    $pos
	 *
	 * @return int
	 */
	public function parse($source, $pos = 0): int {
		$start_pos = $pos;
		$break_chars = [',', ')'];
		$parts = [
			0 => ''
		];
		$contains_macros = [
			0 => false
		];
		$num = 0;

		while (isset($source[$pos])) {
			if (in_array($source[$pos], $break_chars)) {
				break;
			}
			elseif ($source[$pos] === ':') {
				$parts[++$num] = '';
				$contains_macros[$num] = false;
				$pos++;
			}
			elseif ($this->user_macro_parser->parse($source, $pos) !== CParser::PARSE_FAIL) {
				$pos += $this->user_macro_parser->length;
				$parts[$num] .= $this->user_macro_parser->match;
				$contains_macros[$num] = true;
			}
			elseif ($this->lld_macro_parser->parse($source, $pos) !== CParser::PARSE_FAIL) {
				$pos += $this->lld_macro_parser->length;
				$parts[$num] .= $this->lld_macro_parser->match;
				$contains_macros[$num] = true;
			}
			else {
				$parts[$num] .= $source[$pos];
				$pos++;
			}
		}

		if (count($parts) > 2) {
			return CParser::PARSE_FAIL;
		}

		$this->result = new CPeriodParserResult();
		$this->length = $pos - $start_pos;
		$this->result->match = substr($source, $start_pos, $this->length);
		$this->result->sec_num = $parts[0];
		$this->result->time_shift = (array_key_exists(1, $parts) && $parts[1] !== '') ? $parts[1] : null;
		$this->result->sec_num_contains_macros = $contains_macros[0];
		$this->result->time_shift_contains_macros = array_key_exists(1, $contains_macros) ? $contains_macros[1] : false;
		$this->result->length = $this->length;
		$this->result->pos = $start_pos;

		return isset($source[$pos]) ? self::PARSE_SUCCESS_CONT : self::PARSE_SUCCESS;
	}
}
