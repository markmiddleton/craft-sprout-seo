<?php
namespace Craft;

/**
 * Class SproutSeo_CommerceProductUrlEnabledSectionType
 */
class SproutSeo_CommerceProductUrlEnabledSectionType extends SproutSeoBaseUrlEnabledSectionType
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Commerce Products';
	}

	/**
	 * @return string
	 */
	public function getIdColumnName()
	{
		if ($this->typeIdContext == 'matchedElementCheck')
		{
			return 'typeId';
		}

		return 'productTypeId';
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function getById($id)
	{
		return craft()->commerce_productTypes->getProductTypeById($id);
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function getFieldLayoutSettingsObject($id)
	{
		$productType = $this->getById($id);

		return $productType;
	}

	/**
	 * @return string
	 */
	public function getElementTableName()
	{
		return 'commerce_products';
	}

	/**
	 * @return string
	 */
	public function getElementType()
	{
		return 'Commerce_Product';
	}

	/**
	 * @return string
	 */
	public function getMatchedElementVariable()
	{
		return 'product';
	}

	/**
	 * @return mixed
	 */
	public function getAllUrlEnabledSections()
	{
		return craft()->commerce_productTypes->getAllProductTypes();
	}

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'commerce_producttypes_i18n';
	}
}