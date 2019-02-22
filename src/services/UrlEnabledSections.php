<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutseo\services;

use barrelstrength\sproutseo\base\UrlEnabledSectionType;
use barrelstrength\sproutseo\events\RegisterUrlEnabledSectionTypesEvent;
use barrelstrength\sproutseo\sectiontypes\Category;
use barrelstrength\sproutseo\sectiontypes\Entry;
use barrelstrength\sproutseo\sectiontypes\NoSection;
use barrelstrength\sproutseo\sectiontypes\Product;
use Craft;
use yii\base\Component;

/**
 *
 * @property mixed                                                  $matchedElementVariables
 * @property \barrelstrength\sproutseo\base\UrlEnabledSectionType[] $registeredUrlEnabledSectionsEvent
 */
class UrlEnabledSections extends Component
{
    const EVENT_REGISTER_URL_ENABLED_SECTION_TYPES = 'registerUrlEnabledSectionTypesEvent';

    /**
     * @var
     */
    public $urlEnabledSectionTypes;

    /**
     * Returns all registered Url-Enabled Section Types
     *
     * @return UrlEnabledSectionType[]
     */
    public function getRegisteredUrlEnabledSectionsEvent()
    {
        $urlEnabledSectionTypes = [
            Entry::class,
            Category::class,
            NoSection::class
        ];

         if (Craft::$app->getPlugins()->getPlugin('commerce')) {
             $urlEnabledSectionTypes[] = Product::class;
         }

        $event = new RegisterUrlEnabledSectionTypesEvent([
            'urlEnabledSectionTypes' => $urlEnabledSectionTypes
        ]);

        $this->trigger(self::EVENT_REGISTER_URL_ENABLED_SECTION_TYPES, $event);

        return $event->urlEnabledSectionTypes;
    }

    public function getUrlEnabledSectionTypes()
    {
        $urlEnabledSectionTypes = $this->getRegisteredUrlEnabledSectionsEvent();

        $urlEnabledSections = [];

        foreach ($urlEnabledSectionTypes as $urlEnabledSectionType) {
            $urlEnabledSections[] = new $urlEnabledSectionType();
        }

        return $urlEnabledSections;
    }

    public function getMatchedElementVariables()
    {
        $urlEnabledSections = $this->getUrlEnabledSectionTypes();

        $matchedElementVariables = [];

        foreach ($urlEnabledSections as $urlEnabledSection) {
            $matchedElementVariables[] = $urlEnabledSection->getMatchedElementVariable();
        }

        return array_filter($matchedElementVariables);
    }
}
