<?
/*
 * Copyright (c) 2022 Raphael Menke
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */
namespace MediaWiki\Extension\KreuzmichAuth; 

/**
 * Class Auth_phpBBHooks
 * @author  Casey Peel
 * @package MediaWiki
 * @subpackage Auth_PHPBB
 */
class KreuzmichAuthHooks {

    /**
     * Extension registration callback
     */
    public static function onRegister()
    {
        $GLOBALS['wgPluggableAuth_Config'] = [
            "login" => [
                'plugin' => 'KreuzmichAuth',
                'buttonLabelMessage' => 'Login mit Kreuzmich',
            ]
        ];

		// BenutzerInnen muesen sich einloggen 
       $GLOBALS['wgGroupPermissions']['*']['edit'] = false;

        // Voraussetzung fuer PluggableAuth
        $GLOBALS['wgGroupPermissions']['*']['createaccount'] = false;
        $GLOBALS['wgGroupPermissions']['*']['autocreateaccount'] = true;
    }
}