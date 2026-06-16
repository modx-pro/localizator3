<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
{
    public function up(): void
    {
        $prefix = $this->adapter->getOption('table_prefix') ?? '';

        // localizator3_languages
        if (!$this->hasTable($prefix . 'localizator3_languages')) {
            $this->table($prefix . 'localizator3_languages', [
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

        // localizator3_content
        if (!$this->hasTable($prefix . 'localizator3_content')) {
            $this->table($prefix . 'localizator3_content', [
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

        // localizator3_tmplvar_contentvalues
        if (!$this->hasTable($prefix . 'localizator3_tmplvar_contentvalues')) {
            $this->table($prefix . 'localizator3_tmplvar_contentvalues', [
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

        // localizator3_option
        if (!$this->hasTable($prefix . 'localizator3_option')) {
            $this->table($prefix . 'localizator3_option', [
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

        // localizator3_product_option
        if (!$this->hasTable($prefix . 'localizator3_product_option')) {
            $this->table($prefix . 'localizator3_product_option', [
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
        $prefix = $this->adapter->getOption('table_prefix') ?? '';

        $tables = [
            'localizator3_product_option',
            'localizator3_option',
            'localizator3_tmplvar_contentvalues',
            'localizator3_content',
            'localizator3_languages',
        ];

        foreach ($tables as $table) {
            $fullName = $prefix . $table;
            if ($this->hasTable($fullName)) {
                $this->table($fullName)->drop()->save();
            }
        }
    }
}
