<?php

/**
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

namespace barrelstrength\sproutseo\helpers;

use barrelstrength\sproutseo\models\Metadata;
use barrelstrength\sproutseo\SproutSeo;
use Craft;
use craft\elements\Asset;
use craft\helpers\UrlHelper;
use yii\base\Exception;

class OptimizeHelper
{
    /**
     * Set the default canonical URL to be the current URL
     *
     * @param $metadataModel
     *
     * @return string
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public static function prepareCanonical($metadataModel): string
    {
        $canonical = UrlHelper::siteUrl(Craft::$app->request->getPathInfo());

        if ($metadataModel->canonical) {
            $canonical = $metadataModel->canonical;
        }

        return $canonical;
    }

    /**
     * Set the geo 'position' attribute based on the 'latitude' and 'longitude'
     *
     * @param $model
     *
     * @return string|null
     */
    public static function prepareGeoPosition($model)
    {
        if ($model->latitude && $model->longitude) {
            return $model->latitude.';'.$model->longitude;
        }

        return $model->position;
    }

    /**
     * Return a comma delimited string of robots meta settings
     *
     * @param array|string|null $robots
     *
     * @return string|null
     */
    public static function prepareRobotsMetadataValue($robots = null)
    {
        if ($robots === null) {
            return null;
        }

        if (is_string($robots)) {
            return $robots;
        }

        $robotsMetaValue = '';

        foreach ($robots as $key => $value) {
            if ($value == '') {
                continue;
            }

            if ($robotsMetaValue == '') {
                $robotsMetaValue .= $key;
            } else {
                $robotsMetaValue .= ','.$key;
            }
        }

        return $robotsMetaValue;
    }

    /**
     * Return an array of all robots settings set to their boolean value of on or off
     *
     * @param $robotsValues
     *
     * @return array
     */
    public static function prepareRobotsMetadataForSettings($robotsValues): array
    {
        if (is_string($robotsValues)) {
            $robotsArray = explode(',', $robotsValues);

            $robotsSettings = [];

            foreach ($robotsArray as $key => $value) {
                $robotsSettings[$value] = 1;
            }
        } else {
            // Value from content table
            $robotsSettings = $robotsValues;
        }

        $robots = [
            'noindex' => 0,
            'nofollow' => 0,
            'noarchive' => 0,
            'noimageindex' => 0,
            'noodp' => 0,
            'noydir' => 0,
        ];

        foreach ($robots as $key => $value) {
            if (isset($robotsSettings[$key]) && $robotsSettings[$key]) {
                $robots[$key] = 1;
            }
        }

        return $robots;
    }

    /**
     * Prepare Asset URLs for metadata
     *
     * @param Metadata $model
     *
     * @throws Exception
     */
    public static function prepareAssetUrls(Metadata $model)
    {
        // If a code override for ogImageSecure is provided, make sure it's an absolute URL
        if (!empty($model->ogImageSecure) && 0 !== mb_strpos($model->ogImageSecure, 'https')) {
            throw new Exception('Open Graph Secure Image override value "'.$model->ogImageSecure.'" must be a secure, absolute url.');
        }

        // If the siteUrl is https or the current request is https, use it.
        $scheme = parse_url(UrlHelper::baseSiteUrl(), PHP_URL_SCHEME);

        $ogTransform = null;
        $twitterTransform = null;

        // Modify our Assets to reference their URLs
        if (!empty($model->ogImage)) {
            if ($model->ogTransform) {
                $ogTransform = self::getSelectedTransform($model->ogTransform);
            }

            // If ogImage starts with "http", roll with it
            // If not, then process what we have to try to extract the URL
            if (0 !== mb_strpos($model->ogImage, 'http')) {
                if (!is_numeric($model->ogImage)) {
                    throw new Exception('Open Graph Image override value "'.$model->ogImage.'" must be an absolute url.');
                }

                $ogImage = Craft::$app->assets->getAssetById($model->ogImage);
                // Check all getUrl to validate getHasUrls()
                if ($ogImage !== null && $ogImage->getUrl()) {
                    $imageUrl = (string)$ogImage->getUrl();

                    if ($ogTransform) {
                        $imageUrl = (string)$ogImage->getUrl($ogTransform);
                    }
                    // check to see if Asset already has full Site Url in folder Url
                    if (UrlHelper::isAbsoluteUrl($imageUrl)) {
                        $model->ogImage = $imageUrl;
                    } elseif (UrlHelper::isProtocolRelativeUrl($imageUrl)) {
                        $model->ogImage = $scheme.':'.$imageUrl;
                    } else {
                        $model->ogImage = UrlHelper::siteUrl($imageUrl);
                    }

                    $model->ogImageWidth = $ogImage->width;
                    $model->ogImageHeight = $ogImage->height;
                    $model->ogImageType = $ogImage->mimeType;

                    if ($ogTransform) {
                        $model->ogImageWidth = $ogImage->getWidth($ogTransform);
                        $model->ogImageHeight = $ogImage->getHeight($ogTransform);
                    }

                    if (Craft::$app->request->getIsSecureConnection()) {
                        $secureUrl = preg_replace('/^http:/i', 'https:', $model->ogImage);
                        $model->ogImage = $secureUrl;
                        $model->ogImageSecure = $secureUrl;
                    }
                } else {
                    // If our selected asset was deleted, make sure it is null
                    $model->ogImage = null;
                }
            }
        }

        if (!empty($model->twitterImage)) {
            if ($model->twitterTransform) {
                $twitterTransform = self::getSelectedTransform($model->twitterTransform);
            }

            // If twitterImage starts with "http", roll with it
            // If not, then process what we have to try to extract the URL
            if (0 !== mb_strpos($model->twitterImage, 'http')) {
                if (!is_numeric($model->twitterImage)) {
                    throw new Exception('Twitter Image override value "'.$model->twitterImage.'" must be an	absolute url.');
                }

                $twitterImage = Craft::$app->assets->getAssetById($model->twitterImage);

                if ($twitterImage !== null && $twitterImage->getUrl()) {
                    $imageUrl = (string)$twitterImage->getUrl();

                    if ($twitterTransform) {
                        $imageUrl = (string)$twitterImage->getUrl($twitterTransform);
                    }
                    // check to see if Asset already has full Site Url in folder Url
                    if (UrlHelper::isAbsoluteUrl($imageUrl)) {
                        $model->twitterImage = $imageUrl;
                    } elseif (UrlHelper::isProtocolRelativeUrl($imageUrl)) {
                        $model->twitterImage = $scheme.':'.$imageUrl;
                    } else {
                        $model->twitterImage = UrlHelper::siteUrl($imageUrl);
                    }

                    if (Craft::$app->request->getIsSecureConnection()) {
                        $secureUrl = preg_replace('/^http:/i', 'https:', $model->twitterImage);
                        $model->twitterImage = $secureUrl;
                    }
                } else {
                    // If our selected asset was deleted, make sure it is null
                    $model->twitterImage = null;
                }
            }
        }

        // If twitterImage starts with "http", roll with it
        // If not, then process what we have to try to extract the URL
        if (!empty($model->optimizedImage) && 0 !== mb_strpos($model->optimizedImage, 'http')) {
            if (!is_numeric($model->optimizedImage)) {
                throw new Exception('Meta Image override value "'.$model->optimizedImage.'" must be an	absolute url.');
            }

            /**
             * @var Asset $optimizedImage
             */
            $optimizedImage = Craft::$app->elements->getElementById($model->optimizedImage);

            if ($optimizedImage !== null && $optimizedImage->getUrl()) {
                $imageUrl = (string)$optimizedImage->getUrl();
                // check to se	e if Asset already has full Site Url in folder Url
                if (UrlHelper::isAbsoluteUrl($imageUrl)) {
                    $model->optimizedImage = $optimizedImage->url;
                } elseif (UrlHelper::isProtocolRelativeUrl($imageUrl)) {
                    $model->optimizedImage = $scheme.':'.$imageUrl;
                } else {
                    $model->optimizedImage = UrlHelper::siteUrl($optimizedImage->url);
                }

                if (Craft::$app->request->getIsSecureConnection()) {
                    $secureUrl = preg_replace('/^http:/i', 'https:', $model->optimizedImage);
                    $model->optimizedImage = $secureUrl;
                }
            } else {
                // If our selected asset was deleted, make sure it is null
                $model->optimizedImage = null;
            }
        }
    }

    /**
     * @param $image
     *
     * @return mixed
     */
    public static function getImageId($image)
    {
        $imageId = $image;

        if (is_array($image)) {
            $imageId = $image[0];
        }

        return $imageId;
    }

    /**
     * @param      $id
     * @param null $transform
     *
     * @return null|string
     * @throws \Exception
     */
    public static function getAssetUrl($id, $transform = null)
    {
        $url = null;

        // If not, then process what we have to try to extract the URL
        if (0 !== mb_strpos($id, 'http')) {
            if (!is_numeric($id)) {
                throw new Exception('Meta Image override value "'.$id.'" must be an absolute url.');
            }

            /**
             * @var Asset $asset
             */
            $asset = Craft::$app->elements->getElementById($id);

            if ($asset !== null) {
                $transform = self::getSelectedTransform($transform);

                $imageUrl = Craft::$app->getAssets()->getAssetUrl($asset, $transform);

                // check to see if Asset already has full Site Url in folder Url
                if (strpos($imageUrl, 'http') !== false) {
                    $url = $asset->getUrl();
                } else {
                    $protocol = Craft::$app->request->getIsSecureConnection() ? 'https' : 'http';
                    $url = UrlHelper::urlWithScheme($imageUrl, $protocol);
                }
            } else {
                // If our selected asset was deleted, make sure it is null
                $url = null;
            }
        }

        return $url;
    }

    /**
     * Return pre-defined transform settings or the selected transform handle
     *
     * @param $transformHandle
     *
     * @return mixed
     */
    public static function getSelectedTransform($transformHandle)
    {
        $defaultTransforms = [
            'sproutSeo-socialSquare' => [
                'mode' => 'crop',
                'width' => 400,
                'height' => 400,
                'quality' => 82,
                'position' => 'center-center'
            ],
            'sproutSeo-ogRectangle' => [
                'mode' => 'crop',
                'width' => 1200,
                'height' => 630,
                'quality' => 82,
                'position' => 'center-center'
            ],
            'sproutSeo-twitterRectangle' => [
                'mode' => 'crop',
                'width' => 1024,
                'height' => 512,
                'quality' => 82,
                'position' => 'center-center'
            ]
        ];

        return $defaultTransforms[$transformHandle] ?? ($transformHandle == '' ? null : $transformHandle);
    }

    /**
     * Check our Social Profile settings for a Twitter profile.
     * Return the first Twitter profile as an @profileName
     *
     * @param $socialProfiles
     *
     * @return null|string
     */
    public static function getTwitterProfileName(array $socialProfiles = [])
    {
        if ($socialProfiles === null) {
            return null;
        }

        $twitterProfileName = null;

        foreach ($socialProfiles as $profile) {
            $socialProfileNameFromPost = $profile[0] ?? null;
            $socialProfileNameFromSettings = $profile['profileName'] ?? null;

            // Support syntax for both POST data being saved and previous saved social settings
            if ($socialProfileNameFromPost === 'Twitter' or $socialProfileNameFromSettings === 'Twitter') {
                $twitterUrlFromPost = isset($socialProfileNameFromPost) ? $profile[1] : null;
                $twitterUrl = $socialProfileNameFromSettings !== null ? $profile['url'] : $twitterUrlFromPost;

                $twitterProfileName = '@'.mb_substr($twitterUrl, strrpos($twitterUrl, '/') + 1);

                break;
            }
        }

        return $twitterProfileName;
    }

    /**
     * Returns the first Facebook Page found in the Social Profile settings
     *
     * @param $socialProfiles
     *
     * @return null|string
     */
    public static function getFacebookPage(array $socialProfiles = [])
    {
        if ($socialProfiles === null) {
            return null;
        }

        $facebookUrl = null;

        foreach ($socialProfiles as $profile) {
            $socialProfileNameFromPost = $profile[0] ?? null;
            $socialProfileNameFromSettings = $profile['profileName'] ?? null;

            // Support syntax for both POST data being saved and previous saved social settings
            if ($socialProfileNameFromPost === 'Facebook' || $socialProfileNameFromSettings === 'Facebook') {
                $facebookUrlFromPost = isset($socialProfileNameFromPost) ? $profile[1] : null;
                $facebookUrl = $socialProfileNameFromSettings !== null ? $profile['url'] : $facebookUrlFromPost;

                break;
            }
        }

        return $facebookUrl;
    }

    /**
     * Returns the first Google+ Page found in the Social Profile settings
     *
     * @return null|string
     * @throws Exception
     */
    public static function getGooglePlusPage()
    {
        $googlePlusUrl = null;

        $globals = SproutSeo::$app->globalMetadata->getGlobalMetadata();

        if (empty($globals['social'])) {
            return null;
        }

        foreach ($globals['social'] as $key => $socialProfile) {
            if ($socialProfile['profileName'] === 'Google+') {
                // Get our first Google+ URL and bail
                $googlePlusUrl = $socialProfile['url'];
                break;
            }
        }

        return $googlePlusUrl;
    }

    /**
     * @param $prioritizedMetadataModel
     * @param $globals
     *
     * @return string
     * @throws Exception
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public static function prepareAppendedTitleValue(
        $prioritizedMetadataModel,
        $globals
    ): string {
        $settings = $globals->settings;

        $globalAppendTitleValue = null;
        $appendTitleValueOnHomepage = $settings['appendTitleValueOnHomepage'];
        $seoDivider = $settings['seoDivider'];
        $appendTitleValue = null;

        if ($appendTitleValueOnHomepage || Craft::$app->request->getPathInfo()) {
            $globalAppendTitleValue = $settings['appendTitleValue'];

            // @todo - migrate this to just use the {siteName} global that is supported below
            if ($globalAppendTitleValue === 'sitename') {
                $globalAppendTitleValue = Craft::$app->getSystemName();
            }
        }

        if ($prioritizedMetadataModel->appendTitleValue) {
            $appendTitleValue = $prioritizedMetadataModel->appendTitleValue;
        } else {
            $appendTitleValue = $globalAppendTitleValue;
        }

        if ($appendTitleValue) {
            // Add support for using {divider} and {siteName} in the Sitemap 'Append Meta Title' setting
            $appendTitleValue = Craft::$app->view->renderObjectTemplate($appendTitleValue, [
                'siteName' => Craft::$app->getSystemName(),
                'divider' => $seoDivider
            ]);

            $finalAppendTitleValue = $prioritizedMetadataModel->title.' '.$seoDivider.' '.$appendTitleValue;
            return $finalAppendTitleValue ?? '';
        }

        return $prioritizedMetadataModel->title ?? '';
    }

    /**
     * @param $model
     *
     * @return mixed
     * @throws Exception
     */
    public static function updateOptimizedAndAdvancedMetaValues($model)
    {
        $globals = SproutSeo::$app->globalMetadata->getGlobalMetadata();
        $globalSettings = $globals->settings;

        // Prepare our optimized variables
        // -------------------------------------------------------------
        $optimizedTitle = (!empty($model->optimizedTitle) ? $model->optimizedTitle : null);
        $optimizedDescription = (!empty($model->optimizedDescription) ? $model->optimizedDescription : null);

        // Make our images single IDs instead of an array because when it's called from resaveTask sends an single id
        $optimizedImage = !empty($model->optimizedImage) ? $model->optimizedImage : null;
        $ogImage = !empty($model->ogImage) ? $model->ogImage : $optimizedImage;
        $twitterImage = !empty($model->twitterImage) ? $model->twitterImage : $optimizedImage;

        $model['optimizedImage'] = $optimizedImage;
        $model['ogImage'] = $ogImage;
        $model['twitterImage'] = $twitterImage;

        // Set null values for any Advanced SEO Optimization
        // override fields whose blocks have been disabled

        if (!$model->enableMetaDetailsSearch) {
            foreach ($model['searchMeta'] as $attribute => $value) {
                $model->{$attribute} = null;
            }
        }

        if (!$model->enableMetaDetailsOpenGraph) {
            foreach ($model['openGraphMeta'] as $attribute => $value) {
                $model->{$attribute} = null;
            }
        }

        if (!$model->enableMetaDetailsTwitterCard) {
            foreach ($model['twitterCardsMeta'] as $attribute => $value) {
                $model->{$attribute} = null;
            }
        }

        if (!$model->enableMetaDetailsGeo) {
            foreach ($model['geographicMeta'] as $attribute => $value) {
                $model->{$attribute} = null;
            }
        }

        if (!$model->enableMetaDetailsRobots) {
            foreach ($model['robotsMeta'] as $attribute => $value) {
                $model->{$attribute} = null;
            }
        }

        // Set any values that don't yet exist to the optimized values
        // -------------------------------------------------------------
        $model->title = $model->title ?? $optimizedTitle;
        $model->ogTitle = $model->ogTitle ?? $optimizedTitle;
        $model->twitterTitle = $model->twitterTitle ?? $optimizedTitle;

        $model->description = $model->description ?? $optimizedDescription;
        $model->ogDescription = $model->ogDescription ?? $optimizedDescription;
        $model->twitterDescription = $model->twitterDescription ?? $optimizedDescription;

        $model->ogImage = $model->ogImage ?? $optimizedImage;
        $model->twitterImage = $model->twitterImage ?? $optimizedImage;

        $defaultOgType = $globalSettings['defaultOgType'] ?? null;
        $defaultTwitterCard = $globalSettings['defaultTwitterCard'] ?? null;

        $model->ogType = $model->ogType ?? $defaultOgType;
        $model->twitterCard = $model->twitterCard ?? $defaultTwitterCard;

        return $model;
    }

    /**
     * Prepare the default field type settings for the Sitemap context.
     *
     * Display all of our fields manually for the Sitemaps
     *
     * @return array
     */
    public static function getDefaultFieldTypeSettings(): array
    {
        return [
            'optimizedTitleField' => 'manually',
            'optimizedDescriptionField' => 'manually',
            'optimizedImageField' => 'manually',
            'optimizedKeywordsField' => 'manually',
            'showMainEntity' => true,
            'showSearchMeta' => false,
            'showOpenGraph' => true,
            'showTwitter' => true,
            'showGeo' => true,
            'showRobots' => true,
            'editCanonical' => false
        ];
    }

}
