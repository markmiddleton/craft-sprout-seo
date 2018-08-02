<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutseo\models;

use barrelstrength\sproutbase\app\fields\models\Address;
use barrelstrength\sproutbase\SproutBase;
use craft\base\Model;
use craft\helpers\Json;

class Globals extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $siteId;

    /**
     * @var string
     */
    public $everything;

    /**
     * @var \DateTime
     */
    public $dateCreated;

    /**
     * @var \DateTime
     */
    public $dateUpdated;

    /**
     * @var int
     */
    public $uid;

    /**
     * @var Address|null
     */
    public $addressModel = null;

    public function init()
    {
//        if (isset($this->identity['addressId']) && $this->addressModel === null) {
//            $this->addressModel = SproutBase::$app->addressField->getAddressById($this->identity['addressId']);
//        }
    }

    /**
     * @return array
     */
    protected function getMetadata()
    {
        // Mass assign
        $metadata = new Metadata();
        $metadata->siteId = $this->siteId;
        $metadata->setAttributes($this->everything, false);

        return new Metadata($this);
    }

    /**
     * Get the values associated with the Identity column in the database
     *
     * @return array
     */
    protected function getIdentity()
    {
        $globalsIdentity = new GlobalsIdentity();
        $globalsIdentity->setAttributes($this->everything, false);

        return $globalsIdentity;
    }

    /**
     * Get the values associated with the Contacts column in the database
     *
     * @return array
     */
    protected function getContacts()
    {
        $contacts = $this->{$this->globalKey};

        if (!count($contacts)) {
            return [];
        }

        $contactPoints = [];

        /** @noinspection ForeachSourceInspection */
        foreach ($contacts as $contact) {
            $contactPoints[] = [
                '@type' => 'ContactPoint',
                'contactType' => $contact['contactType'] ?? $contact[0],
                'telephone' => $contact['telephone'] ?? $contact[1]
            ];
        }

        return $contactPoints;
    }

    /**
     * Get the values associated with the Social column in the database
     *
     * @return array
     */
    protected function getSocial()
    {
        $profiles = $this->{$this->globalKey};

        if (!count($profiles)) {
            return [];
        }

        $profileLinks = [];

        /** @noinspection ForeachSourceInspection */
        foreach ($profiles as $profile) {
            $profileLinks[] = [
                'profileName' => $profile['profileName'] ?? $profile[0],
                'url' => $profile['url'] ?? $profile[1]
            ];
        }

        return $profileLinks;
    }

    /**
     * Get the values associated with the Ownership column in the database
     *
     * @return array
     */
    protected function getOwnership()
    {
        return $this->{$this->globalKey};
    }

    /**
     * Get the values associated with the Robots column in the database
     *
     * @return array
     */
    protected function getRobots()
    {
        return $this->{$this->globalKey};
    }

    /**
     * Get the values associated with the Settings column in the database
     *
     * @return array
     */
    protected function getSettings()
    {
        return $this->{$this->globalKey};
    }




    /**
     * @return null|string
     */
    public function getWebsiteIdentityType()
    {
        $this->getGlobalByKey('identity');
        $identityType = 'Organization';

        if (isset($this->identity['@type']) && $this->identity['@type'] != '') {
            $identityType = $this->identity['@type'];
        }

        return $identityType;
    }

    /**
     * Determine if the selected Website Identity Schema Type is a Local Business
     *
     * @return null|string
     */
    public function isLocalBusiness()
    {
        $this->getGlobalByKey('identity');

        if (isset($this->identity['organizationSubTypes'][0]) && $this->identity['organizationSubTypes'][0] === 'LocalBusiness') {
            return true;
        }

        return false;
    }
}
