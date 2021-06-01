# KreuzmichAuth-for-MediaWiki
Establishes a authentication via cURL against Kreuzmich Server for MediaWiki. Requires Pluggable Auth for MediaWiki

# Installation
Download files and rename folder to "KreuzmichAuth", copy it into your /extensions folder.

# Prerequisites
https://www.mediawiki.org/wiki/Extension:PluggableAuth needs to be installed. See this page for configuration for PluggableAuth. 
Important configuration settings for PluggableAuth are listed below.

# Configuration PluggableAuth - necessary
```
$wgGroupPermissions['*']['createaccount'] = true;
$wgGroupPermissions['*']['autocreateaccount'] = true;
```

# Configuration PluggableAuth - recommended
```$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['*']['read'] = false;
$wgGroupPermissions['user']['read'] = true;
```

# Configuration KreuzmichAuth
the following lines need to be added to your LocalSettings.php
```
wfLoadExtension( 'KreuzmichAuth' );

# KreuzmichAuth für PluggableAuth 
$wgPluggableAuth_ExtraLoginFields = [
	'kmlabel' => [
		'type' => 'null',
		'label' => 'kreuzmichauth-msg',
	],
	'kmuser' => [
		'type' => 'string',
		'label' => 'kreuzmichauth-user',
	],
	'kmpw' => [
		'type' => 'password',
		'label' => 'kreuzmichauth-pwd',
		'sensitive' => true,
	]];

# add your Kreuzmich subdomain here, e.g. 'duesseldorf', 'koeln', 'regensburg'
$wgKreuzmichAuth_City = 'duesseldorf';

# you can specify HTTP Username and HTTP Password, if needed. If not, leave this commented out
# $wgKreuzmichAuth_HttpUser = '';
# $wgKreuzmichAuth_HttpPwd = '';
# $wgKreuzmichAuth_EnableExpired = true;
# $wgKreuzmichAuth_OnlyFachschaft = true;
```

## Further configuration
If wanted, an aditional PHP function can be specified to further specify if users are allowed to log in, e.g. for a wiki intended only for a group of users.
