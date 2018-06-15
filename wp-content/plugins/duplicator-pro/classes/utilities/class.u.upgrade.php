<?php
defined("ABSPATH") or die("");
/**
 * Utility class working with strings
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package DUP_PRO
 * @subpackage classes/utilities
 * @copyright (c) 2017, Snapcreek LLC
 * @license	https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since 3.0.0
 *
 */
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.global.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.secure.global.entity.php');

class DUP_PRO_Upgrade_U
{
    static function PerformUpgrade($currentVersion, $newVersion)
    {
		error_log("Performing upgrade from {$currentVersion} to {$newVersion}");
		
        self::MoveDataToSecureGlobal();
		self::InitializeScheduleArchiveEngine();
        self::InitializeGift();
    }

    static function InitializeGift()
	{
		/* @var $global DUP_PRO_Global_Entity */
		$global = DUP_PRO_Global_Entity::get_instance();

        $global->dupHidePackagesGiftFeatures = !DUPLICATOR_PRO_GIFT_THIS_RELEASE;
        
        $global->save();
    }
         
	// Used during the time when DupArchive isn't available for backup
	static function InitializeScheduleArchiveEngine()
	{
		/* @var $global DUP_PRO_Global_Entity */
		$global = DUP_PRO_Global_Entity::get_instance();

        // Display gift flag on update
     //   $global->dupHidePackagesGiftFeatures = false;

		if($global->archive_build_mode_schedule === DUP_PRO_Archive_Build_Mode::Unconfigured) {

			error_log("schedule build mode unconfigured on upgrade");
			if(($global->archive_build_mode === DUP_PRO_Archive_Build_Mode::Shell_Exec) || ($global->archive_build_mode === DUP_PRO_Archive_Build_Mode::ZipArchive)) {

				$global->archive_build_mode_schedule = $global->archive_build_mode;
				$global->archive_compression_schedule = $global->archive_compression;

				$global->save();

				error_log("existing build mode is a zip mode so set build mode schedule to {$global->archive_build_mode_schedule} and schedule compression to {$global->archive_compression_schedule}");
			}
		} else {
			error_log("Build mode schedule already set to {$global->archive_build_mode_schedule} so not setting");
		}

	}

    static function MoveDataToSecureGlobal()
    {
        /* @var $global DUP_PRO_Global_Entity */
        /* @var $sglobal DUP_PRO_Secure_Global_Entity */
        $global = DUP_PRO_Global_Entity::get_instance();

        if($global->lkp !== '' || $global->basic_auth_user !== '')
        {
            error_log('setting sglobal');
            $sglobal = DUP_PRO_Secure_Global_Entity::getInstance();

            $sglobal->lkp = $global->lkp;
            $sglobal->basic_auth_password = $global->basic_auth_password;

            $global->lkp = '';
            $global->basic_auth_password = '';

            $sglobal->save();
            $global->save();
        }
    }
}

