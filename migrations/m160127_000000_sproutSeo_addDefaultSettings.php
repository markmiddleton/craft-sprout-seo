<?php
namespace Craft;
/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160127_000000_sproutSeo_addDefaultSettings extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{
		$plugin = craft()->plugins->getPlugin('sproutseo');
		$settings = $plugin->getSettings();

		if(is_null($settings->structureId))
		{
			$structureId = sproutSeo()->redirects->installDefaultSettings($settings->pluginNameOverride);
			SproutSeoPlugin::log('Successfully added structure', LogLevel::Info, true);
			// Set structure to currents redirects
			$redirects = SproutSeo_RedirectRecord::model()->findAll();
			foreach ($redirects as $key => $redirect)
			{
				$redirectModel = SproutSeo_RedirectModel::populateModel($redirect);
				craft()->structures->appendToRoot($structureId, $redirectModel);
			}
		}

		return true;
	}
}