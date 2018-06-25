<?php

namespace barrelstrength\sproutseo\migrations;

use barrelstrength\sproutseo\fields\ElementMetadata;
use craft\db\Migration;
use craft\db\Query;
use Craft;

/**
 * m180625_000000_sections_to_sitemap migration.
 */
class m180625_000000_sections_to_sitemap extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = '{{%sproutseo_sitemaps}}';

        $this->createTable($table, [
            'id' => $this->primaryKey(),
            'siteId' => $this->integer()->notNull(),
            'urlEnabledSectionId' => $this->integer(),
            'enabled' => $this->boolean()->defaultValue(false),
            'type' => $this->string(),
            'uri' => $this->string(),
            'priority' => $this->decimal(11, 1),
            'changeFrequency' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, $table, ['siteId'], false);
        $this->addForeignKey(null, $table, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $primarySite = (new Query())
            ->select(['id'])
            ->from(['{{%sites}}'])
            ->where(['primary' => 1])
            ->one();

        $primarySiteId = $primarySite['id'];

        $sections = (new Query())
            ->select(['*'])
            ->from(['{{%sproutseo_metadata_sections}}'])
            ->all();

        foreach ($sections as $section) {
            $sitemapData = [
                'siteId' => $primarySiteId,
                'urlEnabledSectionId' => $section['urlEnabledSectionId'],
                'enabled' => $section['urlEnabledSectionId'],
                'type' => $section['type'],
                'uri' => $section['uri'],
                'priority' => $section['priority'],
                'changeFrequency' => $section['changeFrequency'],
            ];

            $this->insert($table, $sitemapData);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180625_000000_sections_to_sitemap cannot be reverted.\n";
        return false;
    }
}