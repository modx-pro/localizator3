<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
{
    private const TABLES = [
        'localizator3_languages',
        'localizator3_content',
        'localizator3_tmplvar_contentvalues',
        'localizator3_option',
        'localizator3_product_option',
    ];

    public function up(): void
    {
        // Phinx TablePrefixAdapter adds MODX table_prefix automatically — do not prepend it here.
        if (!$this->hasTable('localizator3_languages')) {
            $this->table('localizator3_languages', [
                'id' => true,
                'primary_key' => ['id'],
                'engine' => 'MyISAM',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ])
                ->addColumn('name', 'string', ['limit' => 100, 'null' => false, 'default' => ''])
                ->addColumn('key', 'string', ['limit' => 100, 'null' => false, 'default' => ''])
                ->addColumn('cultureKey', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('http_host', 'string', ['limit' => 100, 'null' => false, 'default' => ''])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('active', 'boolean', ['null' => true, 'default' => true])
                ->addColumn('rank', 'integer', ['null' => false, 'default' => 0])
                ->addIndex(['key'], ['name' => 'key'])
                ->addIndex(['cultureKey'], ['name' => 'cultureKey'])
                ->create();
        }

        if (!$this->hasTable('localizator3_content')) {
            $this->table('localizator3_content', [
                'id' => true,
                'primary_key' => ['id'],
                'engine' => 'MyISAM',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ])
                ->addColumn('resource_id', 'integer', ['signed' => false, 'null' => true, 'default' => 0])
                ->addColumn('key', 'string', ['limit' => 100, 'null' => false, 'default' => ''])
                ->addColumn('pagetitle', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('longtitle', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('menutitle', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('seotitle', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('keywords', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('introtext', 'text', ['null' => true])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('content', 'text', ['null' => true])
                ->addColumn('active', 'boolean', ['null' => true, 'default' => true])
                ->addIndex(['resource_id'], ['name' => 'resource_id'])
                ->addIndex(['key'], ['name' => 'key'])
                ->create();
        }

        if (!$this->hasTable('localizator3_tmplvar_contentvalues')) {
            $this->table('localizator3_tmplvar_contentvalues', [
                'id' => true,
                'primary_key' => ['id'],
                'engine' => 'MyISAM',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ])
                ->addColumn('key', 'string', ['limit' => 100, 'null' => false, 'default' => ''])
                ->addColumn('tmplvarid', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('contentid', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('value', 'text', ['null' => false])
                ->addIndex(['key'], ['name' => 'key'])
                ->addIndex(['tmplvarid'], ['name' => 'tmplvarid'])
                ->addIndex(['contentid'], ['name' => 'contentid'])
                ->addIndex(['key', 'tmplvarid', 'contentid'], ['unique' => true, 'name' => 'tv_cnt'])
                ->create();
        }

        if (!$this->hasTable('localizator3_option')) {
            $this->table('localizator3_option', [
                'id' => true,
                'primary_key' => ['id'],
                'engine' => 'MyISAM',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ])
                ->addColumn('option_id', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('key', 'string', ['limit' => 100, 'null' => false, 'default' => ''])
                ->addColumn('caption', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('description', 'text', ['null' => true])
                ->addIndex(['option_id', 'key'], ['unique' => true, 'name' => 'option_lang'])
                ->create();
        }

        if (!$this->hasTable('localizator3_product_option')) {
            $this->table('localizator3_product_option', [
                'id' => true,
                'primary_key' => ['id'],
                'engine' => 'MyISAM',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ])
                ->addColumn('product_option_id', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('key', 'string', ['limit' => 100, 'null' => false, 'default' => ''])
                ->addColumn('value', 'text', ['null' => true])
                ->addIndex(['product_option_id', 'key'], ['unique' => true, 'name' => 'product_option_lang'])
                ->create();
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }
    }
}
