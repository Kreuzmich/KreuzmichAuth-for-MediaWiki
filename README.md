# KreuzmichAuth-for-MediaWiki
Establishes a authentication via cURL against Kreuzmich Server for MediaWiki. Requires Pluggable Auth for MediaWiki

# Installation
Download files and rename folder to "KreuzmichAuth", copy it into your /extensions folder.

# Prerequisites
https://www.mediawiki.org/wiki/Extension:PluggableAuth needs to be installed. See this page for configuration for PluggableAuth. 
Important configuration settings for PluggableAuth are listed below.

# Configuration 
### Configuration PluggableAuth - necessary
```
$wgGroupPermissions['*']['createaccount'] = true;
$wgGroupPermissions['*']['autocreateaccount'] = true;
```

### Configuration PluggableAuth - recommended
```$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['*']['read'] = false;
$wgGroupPermissions['user']['read'] = true;
```

### Configuration KreuzmichAuth - necessary
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
```

## Further configuration
If needed you can specify HTTP User and HTTP Password here (not needed in normal mode)
```
$wgKreuzmichAuth_HttpUser = '';
$wgKreuzmichAuth_HttpPwd = '';
```
You can also specify if expired Kreuzmich Users are allowed
```
$wgKreuzmichAuth_EnableExpired = true;
```
If you set this setting, only special users are allowed. The PHP function getFachschaftStatus() can be further specified for this reason. 
```
$wgKreuzmichAuth_OnlyFachschaft = true;
```
