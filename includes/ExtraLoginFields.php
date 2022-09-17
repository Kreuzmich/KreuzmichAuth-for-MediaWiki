<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @package MediaWiki
 * @subpackage Auth_PHPBB
 * @author Nicholas Dunnaway
 * @author Steve Gilvarry
 * @author Jonathan W. Platt
 * @author C4K3
 * @author Joel Haasnoot
 * @author Casey Peel
 * @copyright 2007-2021 Digitalroot Technologies
 * @license http://www.gnu.org/copyleft/gpl.html
 * @link https://github.com/Digitalroot/MediaWiki_PHPBB_Auth
 * @link http://digitalroot.net/
 */

namespace MediaWiki\Extension\KreuzmichAuth;

/**
 * Class ExtraLoginFields
 * Inspired by LDAPAuthentication2 extension
 * @author  Casey Peel, Raphael Menke
 * @package MediaWiki
 * @subpackage KreuzmichAuth
 */
class ExtraLoginFields extends \ArrayObject {

    const LABEL = 'kmlabel';
    const USERNAME = 'kmuser';
    const PASSWORD = 'kmpw';

    public function __construct() {
        parent::__construct( [
            static::LABEL => [
                'type' => 'null',
                'label' => wfMessage( 'kreuzmichauth-msg' ),
                'help' => wfMessage( 'kreuzmichauth-msg-help' ),
            ],
			static::USERNAME => [
                'type' => 'string',
                'label' => wfMessage( 'kreuzmichauth-user' ),
                'help' => wfMessage( 'kreuzmichauth-user-help' ),
            ],
            static::PASSWORD => [
                'type' => 'password',
                'label' => wfMessage( 'kreuzmichauth-pwd' ),
                'help' => wfMessage( 'kreuzmichauth-pwd-help' ),
                'sensitive' => true,
            ]
        ] );
    }
}