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

class GlobalsIdentity extends Model
{
    public $name;
    public $description;
    public $keywords;

    // etc..
}
