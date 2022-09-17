<?
namespace MediaWiki\Extension\KreuzmichAuth; 

use MediaWiki\Extension\PluggableAuth\Hook\PluggableAuthPopulateGroups;

class HookHandler implements PluggableAuthPopulateGroups {
	/**
	 */

	/**
	 */
	public function __construct( ) {
	}

	/**
	 * Will add or remove admins according to external source
	 *
	 * @param User $user
	 */
	public static function onPluggableAuthPopulateGroups(User $user) {

		$authManager = $this->getAuthManager();
		$groups_array = $this->groups;
		// Check 'sysop' in LocalSettings.php
		$sysop = $GLOBALS['wgKreuzmichAuth_AdminGroup'];
		
		if ((!empty($groups_array)) && (!empty($sysop))) {
			if (in_array($sysop, $groups_array)) {
				if (method_exists(MediaWikiServices::class, 'getUserGroupManager')) {
					// MW 1.35+
					MediaWikiServices::getInstance()->getUserGroupManager()->addUserToGroup($user, 'sysop');
				} else {
					$user->addGroup('sysop');
				}
			} else {
				if (method_exists(MediaWikiServices::class, 'getUserGroupManager')) {
					// MW 1.35+
					MediaWikiServices::getInstance()->getUserGroupManager()->removeUserFromGroup($user, 'sysop');
				} else {
					$user->removeGroup('sysop');
				}
			}
		}
	}

}