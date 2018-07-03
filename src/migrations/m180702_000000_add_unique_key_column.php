<?php

namespace barrelstrength\sproutseo\migrations;

use barrelstrength\sproutseo\SproutSeo;
use craft\db\Query;
use craft\db\Migration;

/**
 * m180702_000000_add_unique_key_column migration.
 */
class m180702_000000_add_unique_key_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = '{{%sproutseo_sitemaps}}';

        if (!$this->db->columnExists($table, 'uniqueKey')) {
            $this->addColumn($table, 'uniqueKey', $this->text()->after('siteId'));
        }

        $sitemaps = (new Query())
            ->select('*')
            ->from(['{{%sproutseo_sitemaps}}'])
            ->all();

        foreach ($sitemaps as $sitemap) {
            $uniqueKey = SproutSeo::$app->sitemaps->generateUniqueKey();
            $this->update($table, ['uniqueKey' => $uniqueKey], ['id' => $sitemap['id']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180702_000000_add_unique_key_column cannot be reverted.\n";

        return false;
    }
}