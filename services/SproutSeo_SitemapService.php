<?php
namespace Craft;

/**
 * Class SproutSeo_SitemapService
 *
 * @package Craft
 */
class SproutSeo_SitemapService extends BaseApplicationComponent
{
	/**
	 * @var SproutSeo_SitemapRecord
	 */
	protected $sitemapRecord;

	/**
	 * @param null|SproutSeo_SitemapRecord $sitemapRecord
	 */
	public function __construct($sitemapRecord = null)
	{
		$this->sitemapRecord = $sitemapRecord;

		if (is_null($this->sitemapRecord))
		{
			$this->sitemapRecord = SproutSeo_SitemapRecord::model();
		}
	}

	/**
	 * @param SproutSeo_SitemapModel $attributes
	 *
	 * @return mixed|null|string
	 */
	public function saveSitemap(SproutSeo_SitemapModel $attributes)
	{
		$row   = array();
		$isNew = false;

		// ther first two letters allows to "s-" => section, "c-" => category
		if (isset($attributes->id) && (substr($attributes->id, 2, 3) === "new"))
		{
			$isNew = true;
		}

		// Check if the id is section or category
		$sitemapId = $attributes->id;

		if (!ctype_digit($sitemapId))
		{
			// remove "s-" or "c-"
			$sitemapId = substr($sitemapId, 2);
		}

		if (!$isNew)
		{
			$row = craft()->db->createCommand()
				->select('*')
				->from('sproutseo_sitemap')
				->where('id=:id', array(':id' => $sitemapId))
				->queryRow();
		}

		$model = SproutSeo_SitemapModel::populateModel($row);

		$model->id              = (!$isNew) ? $sitemapId : null;
		$model->sectionId       = (isset($attributes->sectionId)) ? $attributes->sectionId : null;
		$model->categoryGroupId = (isset($attributes->categoryGroupId)) ? $attributes->categoryGroupId : null;
		$model->url             = (isset($attributes->url)) ? $attributes->url : null;
		$model->priority        = $attributes->priority;
		$model->changeFrequency = $attributes->changeFrequency;
		$model->enabled         = ($attributes->enabled == 'true') ? 1 : 0;
		$model->ping            = ($attributes->ping == 'true') ? 1 : 0;
		$model->dateUpdated     = DateTimeHelper::currentTimeForDb();
		$model->uid             = StringHelper::UUID();

		if ($isNew)
		{
			$model->dateCreated = DateTimeHelper::currentTimeForDb();

			craft()->db->createCommand()->insert('sproutseo_sitemap', $model->getAttributes());

			return craft()->db->lastInsertID;
		}
		else
		{
			$result = craft()->db->createCommand()
				->update(
					'sproutseo_sitemap',
					$model->getAttributes(),
					'id=:id', array(
						':id' => $model->id
					)
				);

			return $model->id;
		}
	}

	/**
	 * @param SproutSeo_SitemapModel $customPage
	 *
	 * @return int
	 */
	public function saveCustomPage(SproutSeo_SitemapModel $customPage)
	{
		$result = craft()->db->createCommand()->insert('sproutseo_sitemap', $customPage->getAttributes());

		return $result;
	}

	/**
	 * Returns all URLs for a given sitemap or the rendered sitemap itself
	 *
	 * @param array|null $options
	 *
	 * @throws Exception
	 * @return array|string
	 */
	public function getSitemap(array $options = null)
	{
		$urls            = array();
		$enabledSections = craft()->db->createCommand()
			->select('*')
			->from('sproutseo_sitemap')
			->where('enabled = 1 and (sectionId is not null or categoryGroupId is not null)')
			->queryAll();

		// Fetching settings for each enabled section in Sprout SEO
		foreach ($enabledSections as $key => $sitemapSettings)
		{
			// Fetching all enabled locales
			foreach (craft()->i18n->getSiteLocales() as $locale)
			{
				$criteria = new \CDbCriteria();

				if (isset($sitemapSettings['sectionId']))
				{
					$criteria  = craft()->elements->getCriteria(ElementType::Entry);
					$criteria->sectionId = $sitemapSettings['sectionId'];
				}
				else if (isset($sitemapSettings['categoryGroupId']))
				{
					$criteria  = craft()->elements->getCriteria(ElementType::Category);
					$criteria->groupId = $sitemapSettings['categoryGroupId'];
				}

				$criteria->limit  = null;
				$criteria->status = 'live';
				$criteria->locale = $locale->id;

				/**
				 * @var $entries EntryModel[]
				 *
				 * Fetching all entries enabled for the current locale
				 */
				$entries = $criteria->find();

				foreach ($entries as $entry)
				{
					// @todo ensure that this check/logging is absolutely necessary
					// Catch null URLs, log them, and prevent them from being output to the sitemap
					if (is_null($entry->getUrl()))
					{
						SproutSeoPlugin::log('Entry ID ' . $entry->id . " does not have a URL.", LogLevel::Warning, true);
						continue;
					}

					// Adding each location indexed by its id
					$urls[$entry->id][] = array(
						'id'        => $entry->id,
						'url'       => $entry->getUrl(),
						'locale'    => $locale->id,
						'modified'  => $entry->dateUpdated->format('Y-m-d\Th:m:s\Z'),
						'priority'  => $sitemapSettings['priority'],
						'frequency' => $sitemapSettings['changeFrequency'],
					);
				}
			}
		}

		// Fetching all custom pages define in Sprout SEO
		$customUrls = craft()->db->createCommand()
			->select('url, priority, changeFrequency as frequency, dateUpdated')
			->from('sproutseo_sitemap')
			->where('enabled = 1')
			->andWhere('url is not null')
			->queryAll();

		foreach ($customUrls as $customEntry)
		{
			// Adding each custom location indexed by its URL
			$modified                  = new DateTime($customEntry['dateUpdated']);
			$customEntry['modified']   = $modified->format('Y-m-d\Th:m:s\Z');
			$urls[$customEntry['url']] = craft()->config->parseEnvironmentString($customEntry);
		}

		$urls = $this->getLocalizedSitemapStructure($urls);

		// Rendering the template and passing in received options
		$path = craft()->path->getTemplatesPath();

		craft()->path->setTemplatesPath(dirname(__FILE__) . '/../templates/');

		$source = craft()->templates->render(
			'_special/sitemap', array(
				'entries' => $urls,
				'options' => is_array($options) ? $options : array(),
			)
		);

		craft()->path->setTemplatesPath($path);

		return TemplateHelper::getRaw($source);
	}

	/**
	 * Get all sitemap sections with URLs
	 */
	public function getAllSitemapSections()
	{
		$sitemaps = craft()->plugins->call('sproutSeoRegisterSitemap');

		$sitemapGroupSettings = array();

		foreach ($sitemaps as $sitemap)
		{
			foreach ($sitemap as $key => $settings)
			{
				$service = $settings['service'];
				$method = $settings['method'];

				$sitemapGroups = craft()->{$service}->{$method}();
				$sitemapGroupSettings[$key] = $sitemapGroups;
			}
		}

		$sectionData = array();

		// Prepare a list of all Sitemap Groups we can link to
		foreach ($sitemapGroupSettings as $key => $sitemapGroups)
		{
			foreach ($sitemapGroups as $sitemapGroup)
			{
				if ($sitemapGroup->hasUrls == 1)
				{
					$sectionData[$key][$sitemapGroup->id] = $sitemapGroup->getAttributes();
				}
				else
				{
					// Remove Sections without URLs. They don't have links!
					unset($sitemapGroupSettings[$key]);
				}
			}
		}

		// @todo - needs updated to get all things from the sproutseo_sitemap table
		// Need to add two new columns to sproutseo_sitemap table:
		// - elementGroupId (the ID of the section, categoryGroup, email campaign, commerce product type, etc.
		// - type (The thing that is the elementGroupId: 'section', 'categoryGroup', etc.)
		$sitemapSettings = $this->getAllSiteMaps("section");

		// Prepare the data for our Sitemap Settings page
		foreach ($sitemapSettings as $key => $settings)
		{
			foreach ($sectionData as $key2 => $data)
			{
				// Add Sitemap data to any sectionIds that match
				if (array_key_exists($settings['sectionId'], $data))
				{
					$sectionData[$key2][$settings['elementGroupId']]['settings'] = $settings;
				}
			}
		}


		// @TODO - This doesn't work yet! Needs additional code updated.
		Craft::dd($sectionData);

		return $sitemapGroups;
	}

	/**
	 * @return array
	 */
	public function getAllSectionsWithUrls()
	{
		$sections = craft()->sections->getAllSections();

		// Get all of the Sitemap Settings regarding our Sections
		$sitemapSettings = $this->getAllSiteMaps("section");

		// Prepare a list of all Sections we can link to
		foreach ($sections as $key => $section)
		{
			if ($section->hasUrls == 1)
			{
				$sectionData[$section->id] = $section->getAttributes();
			}
			else
			{
				// Remove Sections without URLs. They don't have links!
				unset($sections[$key]);
			}
		}

		// Prepare the data for our Sitemap Settings page
		foreach ($sitemapSettings as $key => $settings)
		{
			// Add Sitemap data to any sectionIds that match
			if (array_key_exists($settings['sectionId'], $sectionData))
			{
				$sectionData[$settings['sectionId']]['settings'] = $settings;
			}
		}

		return $sectionData;
	}

	/**
	 * @return array
	 */
	public function getAllCategoriesWithUrls()
	{
		$categories   = craft()->categories->getAllGroups();
		$categoryData = array();

		// Get all of the Sitemap Settings regarding our Sections
		$sitemapSettings = $this->getAllSiteMaps("category");

		// Prepare a list of all categories we can link to
		foreach ($categories as $key => $category)
		{
			if ($category->hasUrls == 1)
			{
					$categoryData[$category->id] = $category->getAttributes();
			}
			else
			{
				// Remove Sections without URLs. They don't have links!
				unset($categories[$key]);
			}
		}

		// Prepare the data for our Sitemap Settings page
		foreach ($sitemapSettings as $key => $settings)
		{
			// Add Sitemap data to any sectionIds that match
			if (array_key_exists($settings['categoryGroupId'], $categoryData))
			{
				$categoryData[$settings['categoryGroupId']]['settings'] = $settings;
			}
		}

		return $categoryData;
	}

	/**
	 * @param string type allowed: section|category
	 * @return array
	 */
	public function getAllSiteMaps($type)
	{
		$sitemaps = craft()->db->createCommand()
			->select('*')
			->from('sproutseo_sitemap');

		switch ($type) {
			case 'section':
				$sitemaps->where('sectionId IS NOT NULL');
				break;

			case 'category':
				$sitemaps->where('categoryGroupId IS NOT NULL');
				break;
		}

		return $sitemaps->queryAll();
	}

	/**
	 * @return array|\CDbDataReader
	 */
	public function getAllCustomPages()
	{
		$customPages = craft()->db->createCommand()
			->select('*')
			->from('sproutseo_sitemap')
			->where('url IS NOT NULL')
			->queryAll();

		return $customPages;
	}

	/**
	 * @param $id
	 *
	 * @return int
	 */
	public function deleteCustomPageById($id)
	{
		$record = new SproutSeo_SitemapRecord;

		return $record->deleteByPk($id);
	}

	/**
	 * Returns an array of localized entries for a sitemap from a set of URLs indexed by id
	 *
	 * The returned structure is compliant with multiple locale google sitemap spec
	 *
	 * @param array $stack
	 *
	 * @return array
	 */
	protected function getLocalizedSitemapStructure(array $stack)
	{
		// Defining the containing structure
		$structure = array();

		// Looping through all entries indexed by id
		foreach ($stack as $id => $locations)
		{
			if (is_string($id))
			{
				// Adding a custom location indexed by its URL
				$structure[] = $locations;
			}
			else
			{
				// Looping through each entry and adding it as primary and creating its alternates
				foreach ($locations as $index => $location)
				{
					// Add secondary locations as alternatives to primary
					if (count($locations) > 1)
					{
						$structure[] = array_merge($location, array('alternates' => $locations));
					}
					else
					{
						$structure[] = $location;
					}
				}
			}
		}

		return $structure;
	}
}
