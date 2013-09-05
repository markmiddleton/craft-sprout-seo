<?php
namespace Craft;

class OneSeoService extends BaseApplicationComponent
{
    protected $seoDataRecord;
    protected $seoOverrideRecord;

    public function __construct($seoDataRecord = null, $seoOverrideRecord = null)
    {
        $this->seoDataRecord = $seoDataRecord;
        if (is_null($this->seoDataRecord)) {
            $this->seoDataRecord = OneSeo_OneSeoFallbacksRecord::model();
        }

        $this->seoOverrideRecord = $seoOverrideRecord;
        if (is_null($this->seoOverrideRecord)) {
            $this->seoOverrideRecord = OneSeo_OneSeoOverridesRecord::model();
        }

    }

    /**
     * Get a new blank item
     *
     * @param  array               $attributes
     * @return OneSeo_SeoDataModel
     */
    public function newModel($attributes = array())
    {
        $model = new OneSeo_SeoDataModel();
        $model->setAttributes($attributes);

        return $model;
    }

    /**
     * Get all Fallbacks from the database.
     *
     * @return array
     */
    public function getAllFallbacks()
    {
        $records = $this->seoDataRecord->findAll(array('order'=>'name'));

        return OneSeo_SeoDataModel::populateModels($records, 'id');
    }

    /**
     * Get a specific Fallbacks from the database based on ID. If no Fallbacks exists, null is returned.
     *
     * @param  int   $id
     * @return mixed
     */
    public function getFallbackById($id)
    {
        if ($record = $this->seoDataRecord->findByPk($id)) {
            return OneSeo_SeoDataModel::populateModel($record);
        }
    }

    public function getFallbackByTemplateHandle($handle)
    {

        $query = craft()->db->createCommand()
                    ->select('*')
                    ->from('oneseo_fallbacks')
                    ->where('handle=:handle', array(':handle'=> $handle))
                    ->queryRow();

        $model = OneSeo_SeoDataModel::populateModel($query);

        $model->robots = ($model->robots) ? $this->prepRobots($model->robots) : null;


        if ($model->latitude && $model->longitude)
        {
            $model->position = $model->latitude . ";" . $model->longitude;
        }

        if ($model->id) {
            return $model;
        }
    }

    public function saveFallbackInfo(OneSeo_SeoDataModel &$model)
    {

       if ($id = $model->getAttribute('id')) {
            if (null === ($record = $this->seoDataRecord->findByPk($id))) {
                throw new Exception(Craft::t('Can\'t find fallback with ID "{id}"', array('id' => $id)));
            }
        } else {
            $record = $this->seoDataRecord->create();
        }

        // @TODO passing 'false' here allows us to save unsafe attributes
        // we should really update this to address validation better.
        $record->setAttributes($model->getAttributes(), false);

        if ($record->save()) {

            // update id on model (for new records)
            $model->setAttribute('id', $record->getAttribute('id'));

            return true;

        } else {

            $model->addErrors($record->getErrors());

            return false;
        }

    }

    public function getOverrideById($id)
    {
        if ($record = $this->seoOverrideRecord->findByPk($id)) {
            return OneSeo_OverridesModel::populateModel($record);
        }
    }

    public function getOverrideByEntryId($entryId)
    {
        $query = craft()->db->createCommand()
                   ->select('*')
                   ->from('oneseo_overrides')
                   ->where('entryId = :entryId', array(':entryId' => $entryId))
                   ->queryRow();

        return OneSeo_OverridesModel::populateModel($query);

    }

    public function getBasicSeoFeildsByEntryId($entryId)
    {
        $query = craft()->db->createCommand()
                   ->select('id, title, description, keywords')
                   ->from('oneseo_overrides')
                   ->where('entryId = :entryId', array(':entryId' => $entryId))
                   ->queryRow();

       if (isset($query)) 
       {
            return OneSeo_BasicSeoFieldModel::populateModel($query);
        }
        else
        {
            return new OneSeo_BasicSeoFieldModel;
        }

    }

    public function getGeographicSeoFeildsByEntryId($entryId)
    {
        $query = craft()->db->createCommand()
                   ->select('region, placename, longitude, latitude')
                   ->from('oneseo_overrides')
                   ->where('entryId = :entryId', array(':entryId' => $entryId))
                   ->queryRow();

       if (isset($query)) 
       {
            return OneSeo_GeographicSeoFieldModel::populateModel($query);
        }
        else
        {
            return new OneSeo_GeographicSeoFieldModel;
        }

    }

    public function getRobotsSeoFeildsByEntryId($entryId)
    {
        $query = craft()->db->createCommand()
                   ->select('canonical, robots')
                   ->from('oneseo_overrides')
                   ->where('entryId = :entryId', array(':entryId' => $entryId))
                   ->queryRow();

       if (isset($query)) 
       {
            return OneSeo_RobotsSeoFieldModel::populateModel($query);
        }
        else
        {
            return new OneSeo_RobotsSeoFieldModel;
        }

    }

    public function createOverride($attributes)
    {
        craft()->db->createCommand()
                       ->insert('oneseo_overrides', $attributes);
    }

    public function updateOverride($id, $attributes)
    {
        craft()->db->createCommand()
        ->update('oneseo_overrides',
            $attributes,
            'id = :id', array(':id' => $id)
        );

    }

    public function deleteOverrideById($id = null)
    {
        $record = new OneSeo_OneSeoOverridesRecord;
            
        // @TODO is this the right way to do this?  Would this actually return
        // true or false?
        // Returns the number of rows deleted
        // ref: http://www.yiiframework.com/doc/api/1.1/CActiveRecord#deleteByPk-detail
        return $record->deleteByPk($id);


    }
    
    /**
     * Deletes a fallback
     *
     * @param int 
     * @return bool
     */
    public function deleteFallback($id = null)
    {
        $record = new OneSeo_OneSeoFallbacksRecord;
        return $record->deleteByPk($id);
    }

    public function prepRobots($robotsArray)
    {
        $robotsArray = json_decode($robotsArray);
        
        if(empty($robotsArray))
        {
        	return '';
        }
        
        foreach ($robotsArray as $key => $value) 
        {
            if ($key == 0)
            {
                $robots = $value;
            }                
            else
           {
                $robots .= "," . $value;
            }
        }

        return $robots;
    }

    /**
     * Prepare our robots array from the code overrides
     * on the page.
     *
     * robots: {
     *  noindex: true,
     *  nofollow: true
     * }
     * 
     * @param  [type] $robotsArray [description]
     * @return [type]              [description]
     */
    public function prepRobotsArray($robotsArray)
    {
        if (!$robotsArray OR !is_array($robotsArray)) return;
        
        $i = 0;
        foreach ($robotsArray as $key => $value) {

            if ($value)
            {
                if ($i == 0)
                {
                    $robots = $value;
                }                
                else
                {
                    $robots .= "," . $value;
                }
            }

            $i++;
        }

        return $robots;
    }

}
