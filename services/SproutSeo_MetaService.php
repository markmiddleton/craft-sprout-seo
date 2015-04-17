<?php
namespace Craft;

class SproutSeo_MetaService extends BaseApplicationComponent
{
	protected $metaRecord;
	protected $seoOverrideRecord;
	protected $sitemapRecord;
	protected $siteInfo;
	protected $divider;

	protected $currentUrl;

	protected $fallbackMeta = array();
	protected $sproutmeta = array();

	public function __construct($metaRecord = null, $seoOverrideRecord = null, $sitemapRecord = null)
	{
		$this->metaRecord = $metaRecord;
		if (is_null($this->metaRecord)) {
			$this->metaRecord = SproutSeo_DefaultsRecord::model();
		}

		$this->seoOverrideRecord = $seoOverrideRecord;
		if (is_null($this->seoOverrideRecord)) {
			$this->seoOverrideRecord = SproutSeo_OverridesRecord::model();
		}

		$this->sitemapRecord = $sitemapRecord;
		if (is_null($this->sitemapRecord)) {
			$this->sitemapRecord = SproutSeo_SitemapRecord::model();
		}
	}

	/**
	 * Get a new blank item
	 *
	 * @param  array               $attributes
	 * @return SproutSeo_MetaModel
	 */
	public function newMetaModel($attributes = array())
	{
		$model = new SproutSeo_MetaModel();
		$model->setAttributes($attributes);

		return $model;
	}

	/**
	 * Get all Defaults from the database.
	 *
	 * @return array
	 */
	public function getAllDefaults()
	{
		$records = $this->metaRecord->findAll(array(
			'order' => 'name'
			)
		);

		return SproutSeo_MetaModel::populateModels($records, 'id');
	}

	/**
	 * Get a specific Defaults from the database based on ID. If no Defaults
	 * exists, null is returned.
	 *
	 * @param  int   $id
	 * @return mixed
	 */
	public function getDefaultById($id)
	{
		if ($record = $this->metaRecord->findByPk($id))
		{
			return SproutSeo_MetaModel::populateModel($record);
		}
		else
		{
			return new SproutSeo_MetaModel();
		}
	}

	public function getDefaultByDefaultHandle($handle)
	{

		$query = craft()->db->createCommand()
			->select('*')
			->from('sproutseo_defaults')
			->where('handle=:handle', array(':handle'=> $handle))
			->queryRow();

		if (isset($query)) 
		{
			$model = SproutSeo_MetaModel::populateModel($query);
		}
		else
		{			
			return new SproutSeo_MetaModel();
		}

		$model->robots = ($model->robots) ? $this->prepRobotsForSettings($model->robots) : null;

		if ($model->latitude && $model->longitude)
		{
			$model->position = $model->latitude . ";" . $model->longitude;
		}

		return $model;
	}

	public function saveDefaultInfo(SproutSeo_MetaModel $model)
	{
		if ($id = $model->getAttribute('id'))
		{
			if (null === ($record = $this->metaRecord->findByPk($id)))
			{
				// @todo - Review whether this is causing a bug
				// this is being thrown on NEW default save event...
				// NEW is being passed as the id from the _edit template
				throw new Exception(Craft::t('Can\'t find default with ID "{id}"', array(
					'id' => $id
				)));
			}
		}
		else
		{
			$record = $this->metaRecord->create();
		}

		// @todo - Can we improve how validation is handled here?
		// Setting the second argument to 'false' allows us to save unsafe attributes
		$record->setAttributes($model->getAttributes(), false);

		if ($record->save())
		{
			// update id on model (for new records)
			$model->setAttribute('id', $record->getAttribute('id'));

			return true;
		}
		else
		{
			$model->addErrors($record->getErrors());

			return false;
		}
	}

	public function getOverrideById($id)
	{
		if ($record = $this->seoOverrideRecord->findByPk($id))
		{
			return SproutSeo_OverridesModel::populateModel($record);
		}
		else
		{
			return false;
		}
	}

	public function getOverrideByEntryId($entryId)
	{
		$query = craft()->db->createCommand()
			->select('*')
			->from('sproutseo_overrides')
			->where('entryId = :entryId', array(':entryId' => $entryId))
			->queryRow();

		$model = SproutSeo_OverridesModel::populateModel($query);

		// Ensure both latitude and longitude are present
		// @todo - Refactor how longitude and latitude are handled through the whole process
		if ($model->latitude && $model->longitude)
		{
			$model->position = $model->latitude . ";" . $model->longitude;
		}
		
		return $model;

	}

	public function getBasicMetaFieldsByEntryId($entryId)
	{
		$query = craft()->db->createCommand()
			->select('id, title, description, keywords')
			->from('sproutseo_overrides')
			->where('entryId = :entryId', array(
				':entryId' => $entryId
			))
			->queryRow();

		 if (isset($query))
		 {
			return SproutSeo_BasicMetaFieldModel::populateModel($query);
		}
		else
		{
			return new SproutSeo_BasicMetaFieldModel;
		}

	}

	public function getTwitterCardFieldsByEntryId($entryId)
	{
		$query = craft()->db->createCommand()
			->select('id, twitterCard, twitterSite, twitterTitle, twitterCreator,
			twitterDescription, twitterImage, twitterPlayerStream,
			twitterPlayerStreamContentType, twitterPlayerWidth,
			twitterPlayerHeight')
			->from('sproutseo_overrides')
			->where('entryId = :entryId', array(
				':entryId' => $entryId
			)
			)
			->queryRow();

	if (isset($query))
	{
			return SproutSeo_TwitterCardFieldModel::populateModel($query);
		}
		else
		{
			return new SproutSeo_TwitterCardFieldModel;
		}

	}

	public function getOpenGraphFieldsByEntryId($entryId)
	{
		$query = craft()->db->createCommand()
			->select('id, ogTitle, ogType, ogUrl, ogImage, ogAuthor, ogPublisher, ogSiteName, ogDescription, ogAudio, ogVideo, ogLocale')
			->from('sproutseo_overrides')
			->where('entryId = :entryId', array(
			':entryId' => $entryId
			)
			)
			->queryRow();

	if (isset($query))
	{
			return SproutSeo_OpenGraphFieldModel::populateModel($query);
		}
		else
		{
			return new SproutSeo_OpenGraphFieldModel;
		}

	}

	public function getGeographicMetaFieldsByEntryId($entryId)
	{
		$query = craft()->db->createCommand()
			->select('region, placename, longitude, latitude')
			->from('sproutseo_overrides')
			->where('entryId = :entryId', array(':entryId' => $entryId))
			->queryRow();

		 if (isset($query))
		 {
			return SproutSeo_GeographicMetaFieldModel::populateModel($query);
		}
		else
		{
			return new SproutSeo_GeographicMetaFieldModel;
		}

	}

	public function getRobotsMetaFieldsByEntryId($entryId)
	{
		$query = craft()->db->createCommand()
			->select('canonical, robots')
			->from('sproutseo_overrides')
			->where('entryId = :entryId', array(':entryId' => $entryId))
			->queryRow();

		 if (isset($query))
		 {
			return SproutSeo_RobotsMetaFieldModel::populateModel($query);
		}
		else
		{
			return new SproutSeo_RobotsMetaFieldModel;
		}

	}

	public function createOverride($attributes)
	{
		craft()->db->createCommand()
			->insert('sproutseo_overrides', $attributes);
	}

	public function updateOverride($id, $attributes)
	{
		craft()->db->createCommand()
			->update('sproutseo_overrides',
				$attributes,
				'id = :id', array(':id' => $id)
			);
	}

	public function deleteOverrideById($id = null)
	{
		$record = new SproutSeo_OverridesRecord;

		// @todo - Review how this code works
		// Will this actually return true or false?
		// Returns the number of rows deleted
		// ref: http://www.yiiframework.com/doc/api/1.1/CActiveRecord#deleteByPk-detail
		return $record->deleteByPk($id);
	}

	/**
	 * Deletes a default
	 *
	 * @param int
	 * @return bool
	 */
	public function deleteDefault($id = null)
	{
		$record = new SproutSeo_DefaultsRecord;
		return $record->deleteByPk($id);
	}

	public function optimize($overrideInfo)
	{
		// by default don't append anything to the end of our title
		$this->siteInfo = "";

		// Divider from settings
		$this->divider = craft()->plugins->getPlugin('sproutseo')->getSettings()->seoDivider;

		// If no divider exists, use a dash
		$this->divider = ($this->divider) ? $this->divider : '-';

		// Setup all of our SEO Metadata Arrays
		$entryOverrides = new SproutSeo_MetaModel; // Top Priority
		$codeOverrides  = new SproutSeo_MetaModel; // Second Priority
		$defaults       = new SproutSeo_MetaModel; // Lowest Priority

		// PREPARE Defaults
		// ------------------------------------------------------------

		// Create our 'default' array or fallback to an empty array
		if (isset($overrideInfo['default']))
		{
			if (isset($overrideInfo['default']))
			{
				$defaultHandle = $overrideInfo['default'];
				$defaults = $this->getDefaultByDefaultHandle($defaultHandle);				
			}

			// Remove any values that don't need to be matched with the 'codeOverrides` array
			unset($overrideInfo['default']);
		}
		else
		{
			// Get the handle of our SEO Default
			$query = craft()->db->createCommand()
				->select('*')
				->from('sproutseo_defaults')
				->queryRow();

			if (isset($query)) 
			{
				$model = SproutSeo_MetaModel::populateModel($query);
			}

			// Ensure both latitude and longitude are present
			if ($model->latitude && $model->longitude)
			{
				$defaults->position = $model->latitude . ";" . $model->longitude;
			}
			
		}

		// Set the default canonical URL to be the current URL
		$this->currentUrl = UrlHelper::getSiteUrl(craft()->request->url);
		$defaults->canonical = $this->currentUrl;


		// ------------------------------------------------------------
		// PREPARE ENTRY OVERRIDES
		// ------------------------------------------------------------

		// If our code overrides include an ID, let's query the database and
		// see if this entry has any Entry Overrides.
		if (isset($overrideInfo['id']))
		{
			$entryOverrides = $this->getOverrideByEntryId($overrideInfo['id']);

			unset($overrideInfo['id']);
		}


		// ------------------------------------------------------------
		// PREPARE CODE OVERRIDES
		// ------------------------------------------------------------

		// If we have any more values that were set in our template
		// let's store them as code overrides.
		if ( ! empty($overrideInfo))
		{
			$codeOverrides = SproutSeo_MetaModel::populateModel($overrideInfo);
		}

		// @todo - Refactor how we handle robots in all situations
		$codeOverrides->robots = ($codeOverrides->robots)
			? $codeOverrides->robots
			: null;


		// ------------------------------------------------------------
		// PRIORITIZE OUR METADATA
		// ------------------------------------------------------------

		// For each item in our SEO DATA model, loop through
		// and select the highest ranking item to output.
		//
		// 1) Entry Override
		// 2) On-Page Override
		// 3) Default
		// 4) Blank

		// Once we have added all the content we need to be outputting
		// to our array we will loop through that array and create the
		// HTML we will output to our page.
		//
		// While we don't define HTML in our PHP as much as possible, the
		// goal here is to be as easy to use as possible on the front end
		// so we want to simplify the front end code to a single function
		// and wrangle what we need to here.

		$metaValues = $this->_prioritizeMetaValues($entryOverrides, $codeOverrides, $defaults);


		$output = "\n";
		$openGraphPattern = '/^og:/';
		$twitterPattern = '/^twitter:/';

		foreach ($metaValues as $name => $value)
		{
			if ($value)
			{
				$value = craft()->config->parseEnvironmentString($value);

				switch ($name)
				{
					// Title tag
					case 'title':
					$output .= "\t<title>$value".$this->siteInfo."</title>\n";
					break;

					// Author tag
					case 'author':
					$output .= "\t<link href=\"$value\" rel=\"author\" />\n";
					break;

					case 'publisher':
					$output .= "\t<link href=\"$value\" rel=\"publisher\" />\n";
					break;

					// Open Graph Tags
					case (preg_match($openGraphPattern, $name) ? true : false):
					$output .= "\t<meta property='$name' content='$value' />\n";
					break;

					// Twitter Cards
					case (preg_match($twitterPattern, $name) ? true : false):
					$output .= "\t<meta name='$name' content='$value'>\n";
					break;

					// Canonical URLs
					case 'canonical':
					$output .= "\t<link rel='canonical' href='$value' />\n";
					break;

					// Robots
					case 'robots':
					$value = $this->_determineRobotsOutput($value);					
					$output .= "\t<meta name='robots' content='" . $value . "' />\n";
					break;

					// Standard Meta Tags
					default:
					$output .= "\t<meta name='$name' content='$value' />\n";
					break;
				}
			}

		}

		return $output;
	}

	private function _prioritizeMetaValues($entryOverrides, $codeOverrides, $defaults)
	{

		$metaValues     = array();
		$globalFallback = $this->getGlobalFallback();
		$secureUrl      = ( isset($_SERVER['HTTPS']) ) ? true : false;

		// Allow defaults to override append site setting
		$appendSiteName = is_null($defaults->appendSiteName) ? $globalFallback->appendSiteName : $defaults->appendSiteName;

		if ($appendSiteName)
		{
			$this->siteInfo = " " . $this->divider . " " . craft()->getInfo('siteName');
		}

		// Loop through all of our SEO meta values using the
		// $entryOverrides model because it will always exist
		// and is the highest priority in our SEO meta values waterfall
		foreach ($entryOverrides->getAttributes() as $key => $value)
		{
			// Highest Priority
			if ($entryOverrides->getAttribute($key))
			{
				$metaValues[$key] = $value;
			}
			// Second Highest Priority
			elseif ($codeOverrides->getAttribute($key))
			{	
				$metaValues[$key] = $codeOverrides[$key];
			}
			// Third Highest Priority
			elseif ($defaults->getAttribute($key))
			{	
				$metaValues[$key] = $defaults->getAttribute($key);
			}
			// Lowest Priority
			elseif (!empty($globalFallback))
			{
				$metaValues[$key] = $globalFallback->getAttribute($key);
			}
			else
			{
				// We got nuthin'
				$metaValues[$key] = '';
			}
		}

		if (count($metaValues['robots']) == 0) 
		{
			// If no values are set, we set this to empty which triggers
			// all positive values to be output.  Kinda lame.
			$metaValues['robots'] = array('empty');
		}

		// Modify our Assets to reference their URLs
		if (!empty($metaValues['ogImage']))
		{	
			// If ogImage starts with "http", roll with it
			// If not, then process what we have to try to extract the URL
			if ( substr($metaValues['ogImage'], 0, 4) !== "http" )
			{
				$ogImage = craft()->elements->getElementById($metaValues['ogImage']);

				$imageUrl = (string)($ogImage->url);

				if (!empty($ogImage)) 
				{			
					// check to see if Asset already has full Site Url in folder Url
					if (strpos($imageUrl, "http") !== false) 
					{
						$metaValues['ogImage'] = $ogImage->url;
					}
					else
					{
						$metaValues['ogImage'] = UrlHelper::getSiteUrl($ogImage->url);
					}
					
					$metaValues['ogImageWidth']  = $ogImage->width;
					$metaValues['ogImageHeight'] = $ogImage->height;
					$metaValues['ogImageType']   = $ogImage->mimeType;

					if ($secureUrl) 
					{
						$metaValues['ogImageSecure'] = UrlHelper::getSiteUrl($ogImage->url, null, "https");
					}
				}
			}
		}

		$metaValues['twitterUrl'] = $this->currentUrl;

		if (!empty($metaValues['twitterImage']))
		{
			// If twitterImage starts with "http", roll with it
			// If not, then process what we have to try to extract the URL
			if ( substr($metaValues['twitterImage'], 0, 4) !== "http" )
			{
				$twitterImage = craft()->elements->getElementById($metaValues['twitterImage']);

				$imageUrl = (string)($twitterImage->url);

				if (!empty($twitterImage)) 
				{
					// check to see if Asset already has full Site Url in folder Url
					if (strpos($imageUrl, "http") !== false) 
					{
						$metaValues['twitterImage'] = $twitterImage->url;
					}
					else
					{
						$metaValues['twitterImage'] = UrlHelper::getSiteUrl($twitterImage->url);
					}
				}
			}
		}

		// Unset general default info
		unset($metaValues['id']);
		unset($metaValues['entryId']);
		unset($metaValues['name']);
		unset($metaValues['handle']);
		unset($metaValues['appendSiteName']);
		unset($metaValues['globalFallback']);

		// These values get combined and become: geo.position
		unset($metaValues['longitude']);
		unset($metaValues['latitude']);

		$metaNames = array(
			'title'          => 'title',
			'description'    => 'description',
			'keywords'       => 'keywords',
			'author'         => 'author',
			'publisher'      => 'publisher',

			'robots'         => 'robots',
			'canonical'      => 'canonical',
			'region'         => 'geo.region',
			'placename'      => 'geo.placename',
			'position'       => 'geo.position',

			// Open Graph
			'ogTitle'        => 'og:title',
			'ogType'         => 'og:type',
			'ogUrl'          => 'og:url',

			'ogImage'        => 'og:image',
			'ogImageSecure'  => 'og:image:secure_url',
			'ogImageWidth'   => 'og:image:width',
			'ogImageHeight'  => 'og:image:height',
			'ogImageType'    => 'og:image:type',

			'ogAuthor'       => 'og:author',
			'ogPublisher'    => 'og:publisher',

			'ogSiteName'     => 'og:site_name',
			'ogDescription'  => 'og:description',
			'ogAudio'        => 'og:audio',
			'ogVideo'        => 'og:video',
			'ogLocale'       => 'og:locale',

			// Twitter
			'twitterCard'    => 'twitter:card',
			'twitterSite'    => 'twitter:site',
			'twitterCreator' => 'twitter:creator',
			'twitterTitle'   => 'twitter:title',
			'twitterDescription' => 'twitter:description',

			'twitterUrl'     => 'twitter:url',
			'twitterImage'   => 'twitter:image',

			// Fields for Twitter Player Card
			'twitterPlayer' => 'twitter:player',
			'twitterPlayerStream' => 'twitter:player:stream',
			'twitterPlayerStreamContentType' => 'twitter:player:stream:content_type',
			'twitterPlayerWidth' => 'twitter:player:width',
			'twitterPlayerHeight' => 'twitter:player:height',
		);

		// update our array to use the actual meta name="" parameter values
		// as our index
		$meta = array();
		foreach ($metaValues as $name => $value)
		{
			if (is_string($value)) 
			{
				// Escape the values that might contain quotes
				$meta[$metaNames[$name]] = htmlspecialchars($value, ENT_QUOTES, craft()->templates->getTwig()->getCharset());
			}
			else
			{
				$meta[$metaNames[$name]] = $value;
			}
		}

		return $meta;
	}

	public function getMeta()
	{
		return $this->sproutmeta;
	}

	public function updateMeta($meta)
	{
		// Add values to the $meta array
		if (count($meta))
		{
			foreach ($meta as $key => $value)
			{
				$this->sproutmeta[$key] = $value;
			}
		}
	}

	public function getGlobalFallback()
	{
		$fallback = craft()->db->createCommand()
									->select('*')
									->from('sproutseo_defaults')
									->where('globalFallback=:globalFallback', array(
										':globalFallback' => 1
									))
									->queryRow();

		return SproutSeo_MetaModel::populateModel($fallback);
	}

	public function displayGlobalFallback($defaultId = null)
	{
		$fallback = $this->getGlobalFallback();

		$isGlobalFallback = ( $fallback->id && ($defaultId == $fallback->id) );
		$fallbackExists = !is_null($fallback->id);

		if ($isGlobalFallback OR !$fallbackExists)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function prepRobotsForDb($robotsArray)
	{
		return StringHelper::arrayToString($robotsArray);
	}

	public function prepRobotsForSettings($robotsString)
	{
		return ArrayHelper::stringToArray($robotsString);
	}

	private function _determineRobotsOutput($robotsArray)
	{	
		$robotsMap = array(
			"noindex"      => "index",
			"nofollow"     => "follow",
			"noarchive"    => "archive",
			"noimageindex" => "imageindex",
			"nosnippet"    => "snippet",
			"noodp"        => "odp",
			"noydir"       => "ydir"
		);

		$robotOutputValues = "";

		foreach ($robotsMap as $negativeValue => $positiveValue)
		{
			$robotString = StringHelper::arrayToString($robotsArray);

			if (stristr($robotString, $negativeValue) === FALSE) 
			{
				$robotOutputValues .= $robotsMap[$negativeValue] . ",";				
			}
			else
			{
				$robotOutputValues .= $negativeValue . ",";
			}
		}
		
		// Remove the trailing comma from our string
		$robotOutputValues = rtrim($robotOutputValues, ",");

		return $robotOutputValues;
	}
}