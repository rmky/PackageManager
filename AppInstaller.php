<?php namespace axenox\PackageManager;

use Composer\Installer\PackageEvent;
use exface\Core\CommonLogic\Workbench;
use exface\Core\CommonLogic\NameResolver;
use Composer\Installer\InstallerEvent;

require_once dirname(__FILE__) . 
	DIRECTORY_SEPARATOR . '..' . 
	DIRECTORY_SEPARATOR . '..' . 
	DIRECTORY_SEPARATOR . 'exface' . 
	DIRECTORY_SEPARATOR . 'Core' . 
	DIRECTORY_SEPARATOR . 'CommonLogic' .
	DIRECTORY_SEPARATOR . 'Workbench.php';

/**
 * The app installer is a simplified wrapper for the package manager actions, which simplifies installing apps from outside of
 * ExFace - in particular AppInstaller::finish_composer_update() can be used as a script in composer to perform the app specific
 * installatiom automatically once composer is done installing or updating all the files. 
 * 
 * @author Andrej Kabachnik
 *
 */
class AppInstaller {
	const PACKAGE_MANAGER_APP_ALIAS = 'axenox.PackageManager';
	const PACKAGE_MANAGER_INSTALL_ACTION_ALIAS = 'InstallApp';
	const PACKAGE_MANAGER_UNINSTALL_ACTION_ALIAS = 'UninstallApp';
	
	private $workbench = null;
	
	public static function composer_finish_package_install(PackageEvent $composer_event){
		$app_alias = self::composer_get_app_alias_from_extras($composer_event->getOperation()->getPackage()->getExtra());
		if ($app_alias){
			$result = self::install($app_alias);
			fwrite(STDOUT,  'Installing app "' . $app_alias . '" from ' . $composer_event->getOperation()->getPackage()->getName() . ': ' . ($result ? $result : 'Nothing to do') . ".\n");
			return $result;
		} else {
			return false;
		}
	}
	
	public static function composer_finish_package_update(PackageEvent $composer_event){
		$app_alias = self::composer_get_app_alias_from_extras($composer_event->getOperation()->getTargetPackage()->getExtra());
		if ($app_alias){
			$result = self::install($app_alias);
			fwrite(STDOUT,  'Updating app "' . $app_alias . '" from ' . $composer_event->getOperation()->getTargetPackage()->getName() . ': ' . ($result ? $result : 'Nothing to do') . ".\n");
			return $result;
		} else {
			fwrite(STDOUT, 'No app to install in package "' . $composer_event->getOperation()->getTargetPackage()->getName() . '".'  . "\n");
			return false;
		}
	}
	
	public static function composer_finish_install(InstallerEvent $composer_event){
		
	}
	
	public static function composer_finish_update(InstallerEvent $composer_event){
		/* @var $operation \Composer\DependencyResolver\Operation\UpdateOperation */
		foreach ($composer_event->getOperations() as $operation){
			var_dump($operation->getTargetPackage()->getName(), self::composer_get_app_alias_from_extras($operation->getTargetPackage()->getExtra()));
		}
	}
	
	public static function composer_prepare_uninstall(PackageEvent $composer_event){
		return self::uninstall($composer_event->getOperation()->getPackage()->getName());
	}
	
	protected static function composer_get_app_alias_from_extras($extras_array){
		if (is_array($extras_array) && array_key_exists('app', $extras_array) && is_array($extras_array['app']) && array_key_exists('app_alias', $extras_array['app'])){
			return $extras_array['app']['app_alias'];
		}
		return false;
	}
	
	public static function install($app_alias){
		$installer = new self();
		return $installer->install_app($app_alias);
	}
	
	public static function uninstall($app_alias){
		// TODO
	}
	
	public function install_app($app_alias){
		$result = '';
		try {
			$exface = $this->get_workbench();
			$app_name_resolver = NameResolver::create_from_string($app_alias, NameResolver::OBJECT_TYPE_APP, $exface);
			$result = $exface->get_app(self::PACKAGE_MANAGER_APP_ALIAS)->get_action(self::PACKAGE_MANAGER_INSTALL_ACTION_ALIAS)->install($app_name_resolver);
			$exface->stop();
		} catch (\Exception $e){
			$result = $e->getMessage();
		}
		return $result;
	}
	
	public function uninstall_app($app_alias){
		$result = '';
		try {
			$exface = $this->get_workbench();
			$app_name_resolver = NameResolver::create_from_string($app_alias, NameResolver::OBJECT_TYPE_APP, $exface);
			$result = $exface->get_app(self::PACKAGE_MANAGER_APP_ALIAS)->get_action(self::PACKAGE_MANAGER_UNINSTALL_ACTION_ALIAS)->uninstall($app_name_resolver);
			$exface->stop();
		} catch (\Exception $e){
			$result = $e->getMessage();
		}
		return $result;
	}
	
	protected function get_workbench(){
		if (is_null($this->workbench)){
			error_reporting(E_ALL ^  E_NOTICE);
			$this->workbench = Workbench::start_new_instance();
		}
		return $this->workbench;
	}
	
	protected function get_temp_file(){
		return self::PACKAGE_MANAGER_APP_ALIAS . '.temp.json';
	}
		
}
?>