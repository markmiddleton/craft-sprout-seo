<?php
namespace Craft;

class SproutSeo_SchemaController extends BaseController
{
	/**
	 * Load the Structured Data Edit Page
	 *
	 * @throws HttpException
	 */
	public function actionSchemaEditTemplate()
	{
		$segment = craft()->request->getSegment(3);
		$this->renderTemplate('sproutseo/schema/' . $segment, array());
	}

	/**
	 * Save Structured Data to the database
	 *
	 * @throws HttpException
	 */
	public function actionSaveSchema()
	{
		$this->requirePostRequest();

		$postData   = craft()->request->getPost('sproutseo.schema');
		$schemaType = craft()->request->getPost('schemaType');

		$schemaTypes = explode(',', $schemaType);

		$schema = SproutSeo_SchemaModel::populateModel($postData);

		$globalMetadata = $this->populateGlobalMetadata($postData);

		$schema->meta = JsonHelper::encode($globalMetadata);

		if (sproutSeo()->schema->saveSchema($schemaTypes, $schema))
		{
			craft()->userSession->setNotice(Craft::t('Schema saved.'));

			$this->redirectToPostedUrl($schema);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to save schema.'));
			craft()->urlManager->setRouteVariables(array(
				'schema' => $schema
			));
		}
	}

	/**
	 * Save the Verify Ownership Structured Data to the database
	 *
	 * @todo - consider refactoring this into the standard saveSchema method
	 *
	 * @throws HttpException
	 */
	public function actionSaveVerifyOwnership()
	{
		$this->requirePostRequest();

		$ownershipMeta = craft()->request->getPost('sproutseo.meta.ownership');
		$schemaType    = 'ownership';

		// Remove empty items from multi-dimensional array
		$ownershipMeta = array_filter(array_map('array_filter', $ownershipMeta));

		$ownershipMetaWithKeys = array();

		foreach ($ownershipMeta as $key => $meta)
		{
			// @todo - add proper validation and return errors to template
			if (count($meta) == 3)
			{
				$ownershipMetaWithKeys[$key]['service']          = $meta[0];
				$ownershipMetaWithKeys[$key]['metaTag']          = $meta[1];
				$ownershipMetaWithKeys[$key]['verificationCode'] = $meta[2];
			}
		}

		$schema = SproutSeo_SchemaModel::populateModel(array(
				$schemaType => $ownershipMetaWithKeys
			)
		);

		if (sproutSeo()->schema->saveSchema(array($schemaType), $schema))
		{
			craft()->userSession->setNotice(Craft::t('Schema saved.'));

			$this->redirectToPostedUrl($schema);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to save schema.'));

			craft()->urlManager->setRouteVariables(array(
				'schema' => $schema
			));
		}
	}

	public function populateGlobalMetadata($postData)
	{
		$settings = craft()->plugins->getPlugin('sproutseo')->getSettings();
		$locale   = craft()->i18n->getLocaleById(craft()->language);
		$localeId = $locale->id;

		$oldGlobals        = sproutSeo()->schema->getGlobals();
		$oldIdentity       = isset($oldGlobals) ? $oldGlobals->identity : null;
		$identity          = isset($postData['identity']) ? $postData['identity'] : $oldIdentity;
		$oldSocialProfiles = isset($oldGlobals) ? $oldGlobals->social : array();

		$globalMetadata = new SproutSeo_MetadataModel();
		$siteName               = craft()->getSiteName();

		$urlSetting = isset($postData['identity']['url']) ? $postData['identity']['url'] : null;
		$siteUrl    = SproutSeoOptimizeHelper::getGlobalSiteUrl($urlSetting);

		$socialProfiles     = isset($postData['social']) ? $postData['social'] : $oldSocialProfiles;
		$twitterProfileName = SproutSeoOptimizeHelper::getTwitterProfileName($socialProfiles);

		$robots          = isset($postData['robots']) ? $postData['robots'] : $oldGlobals->robots;
		$robotsMetaValue = SproutSeoOptimizeHelper::getRobotsMetaValue($robots);

		if ($settings->localeIdOverride)
		{
			$localeId = $settings->localeIdOverride;
		}

		if ($identity)
		{
			$identityName         = $identity['name'];
			$optimizedTitle       = $identityName;
			$optimizedDescription = $identity['description'];
			$optimizedImage       = isset($identity['logo'][0]) ? $identity['logo'][0] : null;

			$globalMetadata->optimizedTitle       = $optimizedTitle;
			$globalMetadata->optimizedDescription = $optimizedDescription;
			$globalMetadata->optimizedImage       = $optimizedImage;

			$globalMetadata->title       = $optimizedTitle;
			$globalMetadata->description = $optimizedDescription;
			$globalMetadata->keywords    = $identity['keywords'];

			$globalMetadata->robots    = $robotsMetaValue;
			$globalMetadata->canonical = $siteUrl;

			$globalMetadata->region    = ""; // @todo - add location info
			$globalMetadata->placename = "";
			$globalMetadata->position  = "";
			$globalMetadata->latitude  = "";
			$globalMetadata->longitude = "";

			$globalMetadata->ogType        = 'website';
			$globalMetadata->ogSiteName    = $siteName;
			$globalMetadata->ogUrl         = $siteUrl;
			$globalMetadata->ogAuthor      = $identityName;
			$globalMetadata->ogPublisher   = $identityName;
			$globalMetadata->ogTitle       = $optimizedTitle;
			$globalMetadata->ogDescription = $optimizedDescription;
			$globalMetadata->ogImage       = $optimizedImage;
			$globalMetadata->ogLocale      = $localeId;

			$globalMetadata->twitterCard        = 'summary';
			$globalMetadata->twitterSite        = $twitterProfileName;
			$globalMetadata->twitterCreator     = $twitterProfileName;
			$globalMetadata->twitterUrl         = $siteUrl;
			$globalMetadata->twitterTitle       = $optimizedTitle;
			$globalMetadata->twitterDescription = $optimizedDescription;
			$globalMetadata->twitterImage       = $optimizedImage;
		}

		return $globalMetadata;
	}
}
