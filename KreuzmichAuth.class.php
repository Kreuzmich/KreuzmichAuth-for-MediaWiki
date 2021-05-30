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
 
use \MediaWiki\Session\SessionManager;
use \MediaWiki\Auth\AuthManager;


class KreuzmichAuth extends PluggableAuth {
	
	public $logindata; // Loginfelder aus Loginbereich
	
	public $http_user; // HTTP Username für cURL
	
	public $http_pwd; // HTTP Passwort für cURL
	
	/*
	Konstruktor
	*/
	
	public function __construct()
	{
		// definiere die drei public variables
		
		$this->logindata = $this->getFields();
		
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
	public function authenticate( &$id, &$username, &$realname, &$email, &$errorMessage ) 
	{
	
		// Prüfe auf Umlaute
		if  (preg_match('/[äÄöÖüÜß]/', $this->logindata['kmuser'])) 
		{
			$errorMessage = wfMessage( 'kreuzmichauth-error-umlaut' )->parse();
			return false;
		}	
		
		// Prüfe Login und PW gegen Kreuzmich ExtAuth API
		$ext_auth = $this->getKreuzmichInfo();
		$user_data = $ext_auth['user'];
		
		
		// bringe MediaWiki bei, wer wann eingeloggt werden darf
		
		// Antwort entspricht nicht der Norm, Server nicht erreichbar (technische Probleme oder keine Stadt in LocalSettings definiert)
		if (!isset($ext_auth['success'])) 
		{
			$errorMessage = wfMessage( 'kreuzmichauth-error-noserver' )->parse();
			return false;
		}	
			
		if ( (isset($ext_auth['success'])) && ( $ext_auth['success'] == true ) ) 
		{
			// gültiger Benutzer von Kreuzmich erkannt
			
			// Sind abgelaufene Benutzer erlaubt? Falls nein & Benutzer abgelaufen, Fehler
			if ( isset( $GLOBALS['wgKreuzmichAuth_EnableExpired'] )  &&
				( $GLOBALS['wgKreuzmichAuth_EnableExpired'] == false ) && // Ist Config Variable gesetzt?
				( $user_data['expired'] == true )	) // Ist dieser Benutzer abgelaufen? 
				{
				$errorMessage = wfMessage( 'kreuzmichauth-error-expired' )->parse();
				return false;
				}
			
			if ( is_null($this->getUserStatus( $this->logindata['kmuser'] )) )   // Ist dieser Benutzer für das Wiki blockiert? 
				{
				$errorMessage = wfMessage( 'kreuzmichauth-error-blocked' )->parse();
				return false;
				}
			
			
			// Falls dieser Benutzer kein Fachschaftler ist & wir keine Fachschaftler erlauben, Fehler
			if ( isset( $GLOBALS['wgKreuzmichAuth_OnlyFachschaft'] ) && 
				( $GLOBALS['wgKreuzmichAuth_OnlyFachschaft'] == true ) &&  // Ist Config Variable gesetzt?
				( $this->getUserStatus( $this->logindata['kmuser'] ) === false )   )   // Ist dieser Benutzer kein Fachschaftler? 
				{
				$errorMessage = wfMessage( 'kreuzmichauth-error-fsonly' )->parse();
				return false;
				}
				
			// Wenn du es bis hierhin schaffst, darf der Benutzer sich einloggen
			
			// Gibt es diesen Benutzer schon? Suche in MediaWiki Datenbank via Name
			// return id oder null
			$id = $this->getUserByName ( $this->logindata['kmuser'] );
			
			//falls null wird PluggableAuth neuen User erstellen, sonst wird diese ID geladen
			
			// lade Benutzerinfo, die ggf. Info des Benutzers mit $id überschreibt
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
	public function deauthenticate( User &$user ) 
	{
	}
	
	
	/*
	* Diese Funktion kann verwendet werden, um zusätzlich zu den Kreuzmich-Daten nur bestimmte Studis ins Wiki zuzulassen
	*
	* return boolean (darf sich einloggen oder nicht)
	*/
	private function getUserStatus ( $username ) 
	{
		return true; // Standardmäßig alle erlaubt
	}
	
	
	/*
	* hole die Extra Login Felder, die in LocalSettings definiert sind
	*/
	private static function getFields() 
	{	
		$authManager = AuthManager::singleton();
		$extraLoginFields = $authManager->getAuthenticationSessionData(
			PluggableAuthLogin::EXTRALOGINFIELDS_SESSION_KEY
		);	
	
	return $extraLoginFields;
	}
	 

	/*
	* cURL Abgleich mit Kreuzmich
	*/
	private function getKreuzmichInfo () 
	{
	
	// Lade Kreuzmich Stadt
	if ( isset( $GLOBALS['wgKreuzmichAuth_City'] ) ) {
		
		$city = $GLOBALS['wgKreuzmichAuth_City'];
		
		// Verbinde mit Kreuzmich
		$ch = curl_init("https://". $city . ".kreuzmich.de/extAuth/json");
		// Session Optionen
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $this->http_user . ':' . $this->http_pwd); // HTTP Benutzer und Passwort aus den Einstellungen
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('username' => $this->logindata['kmuser'], 'password' => $this->logindata['kmpw'] ))); //Benutzername und Passwort aus Loginfeld
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// cURL ausfuehren
		$result=curl_exec($ch);
		// Liest eine huebsche JSON in einen Array
		$ext_auth = json_decode($result, true);
		// Session Ende
		curl_close($ch);

		return $ext_auth; 
	
	}
	else return null;	
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
	public function saveExtraAttributes( $id )  
	{
	}	
	
}