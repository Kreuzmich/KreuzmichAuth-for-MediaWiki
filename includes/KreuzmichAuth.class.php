<?php 
/*
 * Copyright (c) 2018 Raphael Menke
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
 
use MediaWiki\Session\SessionManager;
use MediaWiki\Auth\AuthManager;
use MediaWiki\User\UserIdentity;
use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\PluggableAuth\PluggableAuth;
use MediaWiki\Extension\PluggableAuth\PluggableAuthLogin;
use User;


class KreuzmichAuth extends PluggableAuth {
	
	public $logindata; // Loginfelder aus Loginbereich
	
	public $http_user; // HTTP Username für cURL
	
	public $http_pwd; // HTTP Passwort für cURL
	
	private $groups; // Benutzergruppen aus externer Quelle
	
	/*
	Konstruktor
	*/
	
	public function __construct()
	{
		// definiere die drei public variables
		
		$this->logindata = $this->getFieldsValue();
		
		if ( isset( $GLOBALS['wgKreuzmichAuth_HttpPwd'] ) )
		{
			$this->http_pwd = $GLOBALS['wgKreuzmichAuth_HttpPwd'];
		} 
		else 
		{
			$this->http_pwd = '';
		}
	
		if ( isset( $GLOBALS['wgKreuzmichAuth_HttpUser'] ) )
		{
			$this->http_user = $GLOBALS['wgKreuzmichAuth_HttpUser'];
		} 
		else 
		{
			$this->http_user = '';
		}
	}
	
	
	/*
	* Funktion authenticate, die von PluggableAuth aufgerufen wird
	* Parameter: 
	* &$id					Die id des zu autentifizierenden Benutzers, null legt neuen Benutzer an
	* &$username			Benutzername des zu authentifizierenden Benutzers
	* &$realname			Voller Name des zu authentifizierenden Benutzers
	* &$email				E-Mail des zu authentifizierenden Benutzers
	* &$errorMessage		Fehlermeldung (optional) bei Return FALSE
	*
	* return	boolean			Wird Benutzer authentifiziert?
	*/
	public function authenticate( &$id, &$username, &$realname, &$email, &$errorMessage ): bool 
	{
	
		// Prüfe auf Umlaute vor Abfrage an Kreuzmich
		if  (preg_match('/[äÄöÖüÜß]/', $this->logindata['kmuser'])) 
		{
			$errorMessage = wfMessage( 'kreuzmichauth-error-umlaut' )->parse();
			return false;
		}	
		
		// Prüfe Login und PW gegen Kreuzmich ExtAuth API
		$ext_auth = $this->getKreuzmichInfo();
		
		// bringe MediaWiki bei, wer wann eingeloggt werden darf
		
		// Antwort entspricht nicht der Norm, Server nicht erreichbar (technische Probleme oder keine Stadt in LocalSettings definiert)
		if (!isset($ext_auth['success']))
		{
			$errorMessage = wfMessage( 'kreuzmichauth-error-noserver' )->parse();
			return false;
		}
		
		$user_data = $ext_auth['user'];
		
		if ( (isset($ext_auth['success'])) && ( $ext_auth['success'] == true ) )
		{
			// gültiger Benutzer von Kreuzmich erkannt
			
			// Sind abgelaufene Benutzer erlaubt? Falls nein & Benutzer abgelaufen, Fehler
			// auch abgelaufene Admins können sich nicht einloggen
			if ( isset( $GLOBALS['wgKreuzmichAuth_EnableExpired'] )  &&
				( $GLOBALS['wgKreuzmichAuth_EnableExpired'] == false ) && // Ist Config Variable gesetzt?
				( $user_data['expired'] == true )	) // Ist dieser Benutzer abgelaufen? 
			{
				$errorMessage = wfMessage( 'kreuzmichauth-error-expired' )->parse();
				return false;
			}
			
			$this->getExternalGroups( $this->logindata['kmuser'] ); // füllt $this->groups
			
			// Ist dieser Benutzer kein Admin oder sind Admins nicht definiert?
			// Dann weiter in der Schleife
			// Ist die Admingroup definiert, nicht leer und Benutzer ist Mitglied, ueberspringe Schleife & Login
			if ( !isset( $GLOBALS['wgKreuzmichAuth_AdminGroup'] ) || 
					( empty($GLOBALS['wgKreuzmichAuth_AdminGroup']) ) ||  
					(  (!in_array($GLOBALS['wgKreuzmichAuth_AdminGroup'], $this->groups, true))  )   )
			{
				// Ist dieser Benutzer für das Wiki blockiert, sofern diese Gruppe definiert ist?
				// Diese Abfrage kommt vor der AccessGroup, da es um personenbezogene Sperrungen geht. 
				// Admins koennen nicht gesperrt werden
				if ( isset( $GLOBALS['wgKreuzmichAuth_BlockedGroup'] ) && 
					( !empty($GLOBALS['wgKreuzmichAuth_BlockedGroup']) ) &&  
					(  (in_array($GLOBALS['wgKreuzmichAuth_BlockedGroup'], $this->groups, true)))   ) 
				{
					$errorMessage = wfMessage( 'kreuzmichauth-error-blocked' )->parse();
					return false;
				}
				
				// Falls dieser Benutzer kein Mitglied der Wiki-Gruppe, z.B. Fachschaftler, Fehler
				if ( isset( $GLOBALS['wgKreuzmichAuth_AccessGroup'] ) && 
					( !empty($GLOBALS['wgKreuzmichAuth_AccessGroup']) ) &&  // Ist Config Variable gesetzt?
					(  (!in_array($GLOBALS['wgKreuzmichAuth_AccessGroup'], $this->groups, true)))   )   // Ist dieser Benutzer nicht in Gruppe? 
				{
					$errorMessage = wfMessage( 'kreuzmichauth-error-membersonly' )->parse() . $GLOBALS['wgKreuzmichAuth_AccessGroup'];
					return false;
				}
			}
			// Wenn du es bis hierhin schaffst, darfst du dich einloggen.
			
			// Gibt es diesen Benutzer schon? Suche in MediaWiki Datenbank via Name
			// return id oder null, bei null wird PluggableAuth neuen User erstellen, sonst wird diese ID geladen
			$id = $this->getUserByName ( $this->logindata['kmuser'] );
			
			// lade Benutzerinfo, die ggf. Info des Benutzers mit $id überschreibt oder zur Neuanlage genutzt wird
			$username = $user_data['username']; // Username wird neuerdings von Kreuzmich mitgeschickt
			$realname = $user_data['firstname'] ." ". $user_data['lastname'];
			$email = $user_data['email'];

			return true;
		} 
		else {
			$errorMessage = wfMessage( 'kreuzmichauth-error-auth' )->parse();
			return false;
		}
	}
	 
	/*
	* Funktion um Kreuzmich bei Deauthentifizierung/Logout zu informieren, hier nicht verwendet
	*/
	public function deauthenticate( User &$user ): void
	{
		// nicht benutzt
	}
	
	
	/**
	* Provide a getter for the AuthManager to abstract out version checking.
	* Copied from LDAPAuthentication2 extension
	*
	* @return AuthManager
	*/
	protected function getAuthManager() {
		if ( method_exists( MediaWikiServices::class, 'getAuthManager' ) ) {
			// MediaWiki 1.35+
			$authManager = MediaWikiServices::getInstance()->getAuthManager();
		} else {
			$authManager = AuthManager::singleton();
		}
		return $authManager;
	}
	
	/*
	* Diese Funktion kann verwendet werden, um zusätzlich zu den Kreuzmich-Daten zwischen Studis und Moderatoren zu unterscheiden
	* Gibt eindimensionales Array mit Gruppennamen zurück
	* Bestimmte Gruppen können in LocalSettings definiert werden
	*/
	private function getExternalGroups ( $username )
	{
		// hier dein eigener Code, der ein eindimensionales Array $ext_groups erstellen sollte
		
		$this->groups = (is_array($ext_groups)) ? $ext_groups : [];
	}
	
	
	/*
	* hole die Extra Login Felder, die in LocalSettings definiert sind
	*/
	private static function getFieldsValue() : array
	{	
		$authManager = $this->getAuthManager();
		$extraLoginFields = $authManager->getAuthenticationSessionData(
			PluggableAuthLogin::EXTRALOGINFIELDS_SESSION_KEY
		);	
		
		return $extraLoginFields;
	}
	
	/**
	* Populates the login page for this plugin.
	* [used by PluggableAuth]
	*/
	public static function getExtraLoginFields(): array
	{
		return (array)( new ExtraLoginFields() );
	}

	/*
	* cURL Abgleich mit Kreuzmich
	*/
	private function getKreuzmichInfo () 
	{
		// Lade Kreuzmich Stadt
		if ( !isset( $GLOBALS['wgKreuzmichAuth_City'] ) ) return null;
		
		$ch = curl_init("https://". $GLOBALS['wgKreuzmichAuth_City'] . ".kreuzmich.de/extAuth/json");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $this->http_user . ':' . $this->http_pwd); // HTTP Benutzer und Passwort aus den Einstellungen
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('username' => $this->logindata['kmuser'], 'password' => $this->logindata['kmpw'] ))); //Benutzername und Passwort aus Loginfeld
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$ext_auth = json_decode($result, true);
		curl_close($ch);
		return $ext_auth; 
	}

	
	/*
	* Suche nach vorhandenem Benutzer in mediaWIKI DB
	*/
	private static function getUserByName ( $name ) 
	{
		// protected MySql Query: select user id where name
		$dbr = wfGetDB( DB_SLAVE );
		$row = $dbr->selectRow( 
			'user',
			[ //SELECT
				'user_id',
				'user_name'
			],
			[ //WHERE
				'user_name' => $name
			],
			__METHOD__,
			[
				// if multiple matching accounts, use the oldest one
				'ORDER BY' => 'user_registration',
				'LIMIT' => 1
			]
		);
		if ( $row === false ) {
			return null;
		} else {
			return  $row->user_id;
		}
	}
	
	
	/*
	* Speichere weitere Benutzerdaten, hier nicht verwendet
	*/
	public function saveExtraAttributes( $id ): void  
	{
		// nicht benutzt
	}	
	
}
