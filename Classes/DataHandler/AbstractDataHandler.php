<?php
namespace GridElementsTeam\Gridelements\DataHandler;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use GridElementsTeam\Gridelements\Backend\LayoutSetup;
use GridElementsTeam\Gridelements\Helper\Helper;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
abstract class AbstractDataHandler
{

    protected $table;

    protected $pageUid;

    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var LayoutSetup
     */
    protected $layoutSetup;

    /**
     * inject layout setup
     *
     * @param LayoutSetup $layoutSetup
     *
     * @return void
     */
    public function injectLayoutSetup(LayoutSetup $layoutSetup)
    {
        $this->layoutSetup = $layoutSetup;
    }

    /**
     * initializes this class
     *
     * @param string $table : The name of the table the data should be saved to
     * @param integer $pageUid : The uid of the page we are currently working on
     * @param DataHandler $dataHandler
     *
     * @return void
     */
    public function init($table, $pageUid, DataHandler $dataHandler)
    {
        $this->setTable($table);
        $this->setPageUid($pageUid);
        $this->setTceMain($dataHandler);
        $this->setDatabaseConnection($GLOBALS['TYPO3_DB']);
        if (!$this->layoutSetup instanceof LayoutSetup) {
            if ($pageUid < 0) {
                $pageUid = Helper::getInstance()->getPidFromNegativeUid($pageUid);
            }
            $this->injectLayoutSetup(GeneralUtility::makeInstance(LayoutSetup::class)->init($pageUid));
        }
    }

    /**
     * setter for table
     *
     * @param string $table
     *
     * @return void
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * getter for table
     *
     * @return string table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * setter for pageUid
     *
     * @param integer $pageUid
     *
     * @return void
     */
    public function setPageUid($pageUid)
    {
        $this->pageUid = $pageUid;
    }

    /**
     * getter for pageUid
     *
     * @return integer pageUid
     */
    public function getPageUid()
    {
        return $this->pageUid;
    }

    /**
     * setter for dataHandler object
     *
     * @param DataHandler $dataHandler
     *
     * @return void
     */
    public function setTceMain(DataHandler $dataHandler)
    {
        $this->dataHandler = $dataHandler;
    }

    /**
     * getter for dataHandler
     *
     * @return DataHandler dataHandler
     */
    public function getTceMain()
    {
        return $this->dataHandler;
    }

    /**
     * setter for databaseConnection object
     *
     * @param DatabaseConnection $databaseConnection
     *
     * @return void
     */
    public function setDatabaseConnection(DatabaseConnection $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * getter for databaseConnection
     *
     * @return DatabaseConnection databaseConnection
     */
    public function getDatabaseConnection()
    {
        return $this->databaseConnection;
    }

    /**
     * Function to handle record actions between different grid containers
     *
     * @param array $containerUpdateArray
     *
     * @return void
     */
    public function doGridContainerUpdate($containerUpdateArray = array())
    {
        if (is_array($containerUpdateArray) && !empty($containerUpdateArray)) {
            foreach ($containerUpdateArray as $containerUid => $newElement) {
                $fieldArray = array('tx_gridelements_children' => 'tx_gridelements_children + ' . (int)$newElement);
                $this->databaseConnection->exec_UPDATEquery('tt_content', 'uid=' . (int)$containerUid, $fieldArray,
                    'tx_gridelements_children');
                $this->getTceMain()->updateRefIndex('tt_content', (int)$containerUid);
            }
        }
    }

    /**
     * Function to handle record actions for current or former children of translated grid containers
     *
     * @param int $uid
     */
    public function checkAndUpdateTranslatedElements($uid)
    {
        if ($uid > 0) {
            $translatedElements = $this->databaseConnection->exec_SELECTgetRows(
                'uid,tx_gridelements_container,tx_gridelements_columns,sys_language_uid,colPos',
                'tt_content', 'l18n_parent=' . (int)$uid , '', '', '', 'uid'
            );
            if (empty($translatedElements)) {
                return;
            }
            $currentValues = $this->databaseConnection->exec_SELECTgetSingleRow(
                'tx_gridelements_container,tx_gridelements_columns,colPos',
                'tt_content', 'uid=' . (int)$uid
            );
            if ($currentValues['tx_gridelements_container'] > 0) {
                $translatedContainers = $this->databaseConnection->exec_SELECTgetRows(
                    'uid,sys_language_uid',
                    'tt_content', 'l18n_parent=' . (int)$currentValues['tx_gridelements_container'], '', '', '', 'sys_language_uid'
                );
            }
            $containerUpdateArray = array();
            foreach ($translatedElements as $translatedUid => $translatedElement) {
                $updateArray = array();
                $updateArray['tx_gridelements_container'] = isset($translatedContainers[$translatedElement['sys_language_uid']]) ?
                    (int)$translatedContainers[$translatedElement['sys_language_uid']]['uid'] : 0;
                $updateArray['tx_gridelements_columns'] = isset($translatedContainers[$translatedElement['sys_language_uid']]) ?
                    (int)$currentValues['tx_gridelements_columns'] : 0;
                $updateArray['colPos'] = (int)$currentValues['colPos'];

                $this->databaseConnection->exec_UPDATEquery('tt_content', 'uid=' . (int)$translatedUid, $updateArray,
                    'tx_gridelements_container,tx_gridelements_columns.colPos');

                if ($translatedElement['tx_gridelements_container'] !== $updateArray['tx_gridelements_container']) {
                    $containerUpdateArray[$translatedElement['tx_gridelements_container']] -= 1;
                    $containerUpdateArray[$updateArray['tx_gridelements_container']] += 1;
                    $this->getTceMain()->updateRefIndex('tt_content', $translatedElement['tx_gridelements_container']);
                    $this->getTceMain()->updateRefIndex('tt_content', $updateArray['tx_gridelements_container']);
                }
            }
            if (!empty($containerUpdateArray)) {
                $this->doGridContainerUpdate($containerUpdateArray);
            }
        }
    }
}
