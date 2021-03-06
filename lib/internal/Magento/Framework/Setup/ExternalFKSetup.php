<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Setup;

/**
 * @codeCoverageIgnore
 */
class ExternalFKSetup
{
    /**
     * @var SchemaSetupInterface
     */
    protected $setup;

    /**
     * @var string
     */
    protected $entityTable;

    /**
     * @var string
     */
    protected $entityColumn;

    /**
     * @var string
     */
    protected $externalTable;

    /**
     * @var string
     */
    protected $externalColumn;

    /**
     * Install external foreign key
     *
     * @param SchemaSetupInterface $setup
     * @param string $entityTable
     * @param string $entityColumn
     * @param string $externalTable
     * @param string $externalColumn
     * @return void
     */
    public function install(
        SchemaSetupInterface $setup,
        $entityTable,
        $entityColumn,
        $externalTable,
        $externalColumn
    ) {
        $this->setup = $setup;
        $this->entityTable = $entityTable;
        $this->entityColumn = $entityColumn;
        $this->externalTable = $externalTable;
        $this->externalColumn = $externalColumn;

        $this->execute();
    }

    /**
     * Set external foreign key
     *
     * @return void
     */
    protected function execute()
    {
        $entityTableInfo = $this->setup->getConnection()->describeTable(
            $this->setup->getTable($this->entityTable)
        );
        if (!$entityTableInfo[$this->entityColumn]['PRIMARY']) {
            $this->dropOldForeignKey();
            $this->addForeignKeys();
        } else {
            $this->addDefaultForeignKey();
        }
    }

    /**
     * Get foreign keys for tables and columns
     *
     * @param string $refTable
     * @param string $refColumn
     * @param string $targetTable
     * @param string $targetColumn
     * @return array
     */
    protected function getForeignKeys(
        $targetTable,
        $targetColumn,
        $refTable,
        $refColumn
    ) {
        $foreignKeys = $this->setup->getConnection()->getForeignKeys(
            $this->setup->getTable($targetTable)
        );
        $foreignKeys = array_filter(
            $foreignKeys,
            function ($key) use ($targetColumn, $refTable, $refColumn) {
                return $key['COLUMN_NAME'] == $targetColumn
                && $key['REF_TABLE_NAME'] == $refTable
                && $key['REF_COLUMN_NAME'] == $refColumn;
            }
        );
        return $foreignKeys;
    }

    /**
     * Remove foreign key if exists
     *
     * @param string $targetTable
     * @param string $targetColumn
     * @param string $refTable
     * @param string $refColumn
     * @return void
     */
    protected function clearForeignKey(
        $targetTable,
        $targetColumn,
        $refTable,
        $refColumn
    ) {
        $foreignKeys = $this->getForeignKeys($targetTable, $targetColumn, $refTable, $refColumn);
        foreach ($foreignKeys as $foreignKey) {
            $this->setup->getConnection()->dropForeignKey(
                $foreignKey['TABLE_NAME'],
                $foreignKey['FK_NAME']
            );
        }
    }

    /**
     * Add default foreign key
     *
     * @return void
     */
    protected function addDefaultForeignKey()
    {
        if (!count($this->getForeignKeys(
            $this->externalTable,
            $this->externalColumn,
            $this->entityTable,
            $this->entityColumn
        ))) {
            $this->setup->getConnection()->addForeignKey(
                $this->setup->getFkName(
                    $this->externalTable,
                    $this->externalColumn,
                    $this->entityTable,
                    $this->entityColumn
                ),
                $this->setup->getTable($this->externalTable),
                $this->externalColumn,
                $this->setup->getTable($this->entityTable),
                $this->entityColumn
            );
        }
    }

    /**
     * Add foreign keys to entity table
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $foreignKeys = $this->setup->getConnection()->getForeignKeys(
            $this->setup->getTable($this->entityTable)
        );
        $foreignKeys = array_filter(
            $foreignKeys,
            function ($key) {
                return $key['COLUMN_NAME'] == $this->entityColumn;
            }
        );
        foreach ($foreignKeys as $foreignKeyInfo) {
            if (!count($this->getForeignKeys(
                $this->externalTable,
                $this->externalColumn,
                $this->setup->getTablePlaceholder($foreignKeyInfo['REF_TABLE_NAME']),
                $foreignKeyInfo['REF_COLUMN_NAME']
            ))) {
                $this->setup->getConnection()->addForeignKey(
                    $this->setup->getFkName(
                        $this->externalTable,
                        $this->externalColumn,
                        $this->setup->getTablePlaceholder($foreignKeyInfo['REF_TABLE_NAME']),
                        $foreignKeyInfo['REF_COLUMN_NAME']
                    ),
                    $this->setup->getTable($this->externalTable),
                    $this->externalColumn,
                    $foreignKeyInfo['REF_TABLE_NAME'],
                    $foreignKeyInfo['REF_COLUMN_NAME']
                );
            }
        }
    }

    /**
     * Drop old foreign key
     *
     * @return void
     */
    protected function dropOldForeignKey()
    {
        if (count($this->getForeignKeys(
            $this->externalTable,
            $this->externalColumn,
            $this->entityTable,
            $this->entityColumn
        ))) {
            $this->clearForeignKey(
                $this->externalTable,
                $this->externalColumn,
                $this->entityTable,
                $this->entityColumn
            );
        }
    }
}
