<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\XmlSitemap\Observer;

use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Config\Model\Config\Structure\Element\Group;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ValueInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Config\Model\Config\Loader as ConfigLoader;
use Magento\Framework\DB\TransactionFactory;
use Magento\Config\Model\Config\Structure as ConfigStructure;
use Magento\Config\Model\Config\Reader\Source\Deployed\SettingChecker;
use Magento\Framework\App\Config\ValueFactory as ConfigValueFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\Config as MagentoConfig;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use \Magento\Framework\App\RequestInterface;

/**
 * Backend config model observer
 *
 * Used to save sitemap configuration
 */
class AdminSystemConfigChangedSection implements ObserverInterface
{
    const SITEMAP_RELATION_ARRAY = [
        'limit' => ['max_lines', 'max_file_size'],
        'page' => ['priority', 'changefreq'],
        'category' => ['priority', 'changefreq'],
        'product' => ['priority', 'changefreq', 'image_include'],
        'store' => ['priority', 'changefreq'],
        'generate' => ['enabled', 'error_email', 'error_email_template',
            'error_email_identity', 'time', 'frequency'],
        'search_engines' => ['submission_robots']
    ];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ConfigLoader
     */
    private $configLoader;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var ConfigStructure
     */
    private $configStructure;

    /**
     * @var SettingChecker
     */
    private $settingChecker;

    /**
     * @var ConfigValueFactory
     */
    private $configValueFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var MagentoConfig
     */
    private $magentoConfig;

    /**
     * @var ReinitableConfigInterface
     */
    private $appConfig;

    /**
     * @var null
     */
    private $scope = null;

    /**
     * @var null
     */
    private $store = null;

    /**
     * @var null
     */
    private $website = null;

    /**
     * @var null
     */
    private $scopeId = null;

    /**
     * @var null
     */
    private $scopeCode = null;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigLoader $configLoader
     * @param TransactionFactory $transactionFactory
     * @param ConfigStructure $configStructure
     * @param ConfigValueFactory $configValueFactory
     * @param StoreManagerInterface $storeManager
     * @param MagentoConfig $magentoConfig
     * @param ReinitableConfigInterface $appConfig
     * @param SettingChecker $settingChecker
     * @param RequestInterface $request
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigLoader $configLoader,
        TransactionFactory $transactionFactory,
        ConfigStructure $configStructure,
        ConfigValueFactory $configValueFactory,
        StoreManagerInterface $storeManager,
        MagentoConfig $magentoConfig,
        ReinitableConfigInterface $appConfig,
        SettingChecker $settingChecker,
        RequestInterface $request
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configLoader = $configLoader;
        $this->transactionFactory = $transactionFactory;
        $this->configStructure = $configStructure;
        $this->configValueFactory = $configValueFactory;
        $this->storeManager = $storeManager;
        $this->magentoConfig = $magentoConfig;
        $this->appConfig = $appConfig;
        $this->settingChecker = $settingChecker;
        $this->request = $request;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $sectionId = (string)$this->request->getParam('section');
        if (!$sectionId) {
            return;
        }
        $requestGroups = (array)$this->request->getParam('groups');
        if (!$requestGroups) {
            return;
        }

        $this->store = $observer->getData('store') ?? null;
        $this->website = $observer->getData('website') ?? null;

        $this->initScope();
        $groups = $this->scopeConfig->getValue($sectionId, $this->scope, $this->scopeCode);

        $sitemapGroup = [];
        foreach (self::SITEMAP_RELATION_ARRAY as $key => $fields) {
            $values = [];
            if (isset($groups[$key])) {
                foreach ($groups[$key] as $field => $value) {

                    if (in_array($field, $fields) && empty($requestGroups[$key]['fields'][$field]['inherit'])) {
                        $values[$key . '_' . $field] = $value;
                    }
                }
            }

            foreach ($fields as $field) {
                $sitemapGroup[$key]['fields'][$field] = isset($values[$key . '_' . $field])
                    ? ['value' => $values[$key . '_' . $field]]
                    : ['inherit' => true];
            }
        }

        $this->proceedTransaction($sitemapGroup, ('mfxmlsitemap' === $sectionId) ? 'sitemap' : 'mfxmlsitemap');
    }

    /**
     * @param $groups
     * @param $sectionId
     * @throws \Exception
     */
    private function proceedTransaction($groups, $sectionId)
    {
        $oldConfig = $this->configLoader->getConfigByPath(
            $sectionId,
            $this->scope,
            $this->scopeId,
            true
        );

        /** @var Transaction $deleteTransaction */
        $deleteTransaction = $this->transactionFactory->create();
        /** @var Transaction $saveTransaction */
        $saveTransaction = $this->transactionFactory->create();

        $extraOldGroups = [];

        foreach ($groups as $groupId => $groupData) {
            $this->processGroup(
                $groupId,
                $groupData,
                $groups,
                $sectionId,
                $extraOldGroups,
                $oldConfig,
                $saveTransaction,
                $deleteTransaction
            );
        }

        try {
            $deleteTransaction->delete();
            $saveTransaction->save();
            $this->appConfig->reinit();
        } catch (\Exception $e) {
            $this->appConfig->reinit();
            throw $e;
        }
    }

    /**
     * @param $groupId
     * @param array $groupData
     * @param array $groups
     * @param $sectionPath
     * @param array $extraOldGroups
     * @param array $oldConfig
     * @param Transaction $saveTransaction
     * @param Transaction $deleteTransaction
     * @return void
     */
    private function processGroup(
        $groupId,
        array $groupData,
        array $groups,
        $sectionPath,
        array &$extraOldGroups,
        array &$oldConfig,
        Transaction $saveTransaction,
        Transaction $deleteTransaction
    ) {
        $groupPath = $sectionPath . '/' . $groupId;


        if (isset($groupData['fields'])) {
            /** @var Group $group */
            $group = $this->configStructure->getElement($groupPath);

            // set value for group field entry by fieldname
            // use extra memory
            $fieldsetData = [];
            foreach ($groupData['fields'] as $fieldId => $fieldData) {
                $fieldsetData[$fieldId] = $fieldData['value'] ?? null;
            }

            foreach ($groupData['fields'] as $fieldId => $fieldData) {
                $isReadOnly = $this->settingChecker->isReadOnly(
                    $groupPath . '/' . $fieldId,
                    $this->scope,
                    $this->scopeCode
                );

                if ($isReadOnly) {
                    continue;
                }

                $field = $this->getField($sectionPath, $groupId, $fieldId);
                /** @var ValueInterface $backendModel */
                $backendModel = $field->hasBackendModel()
                    ? $field->getBackendModel()
                    : $this->configValueFactory->create();

                if (!isset($fieldData['value'])) {
                    $fieldData['value'] = null;
                }

                if ($field->getType() == 'multiline' && is_array($fieldData['value'])) {
                    $fieldData['value'] = trim(implode(PHP_EOL, $fieldData['value']));
                }

                $data = [
                    'field' => $fieldId,
                    'groups' => $groups,
                    'group_id' => $group->getId(),
                    'scope' => $this->scope,
                    'scope_id' => $this->scopeId,
                    'scope_code' => $this->scopeCode,
                    'field_config' => $field->getData(),
                    'fieldset_data' => $fieldsetData
                ];
                $backendModel->addData($data);
                $this->checkSingleStoreMode($field, $backendModel);

                $path = $this->getFieldPath($field, $fieldId, $oldConfig, $extraOldGroups);
                $backendModel->setPath($path)->setValue($fieldData['value']);

                $inherit = !empty($fieldData['inherit']);

                if (isset($oldConfig[$path])) {
                    $backendModel->setConfigId($oldConfig[$path]['config_id']);

                    /**
                     * Delete config data if inherit
                     */
                    if (!$inherit) {
                        $saveTransaction->addObject($backendModel);
                    } else {
                        $deleteTransaction->addObject($backendModel);
                    }
                } elseif (!$inherit) {
                    $backendModel->unsConfigId();
                    $saveTransaction->addObject($backendModel);
                }
            }
        }
    }
    /**
     * Get field object
     *
     * @param string $sectionId
     * @param string $groupId
     * @param string $fieldId
     * @return Field
     */
    private function getField(string $sectionId, string $groupId, string $fieldId): Field
    {
        /** @var Group $group */
        $group = $this->configStructure->getElement($sectionId . '/' . $groupId);
        $fieldPath = $group->getPath() . '/' . $this->getOriginalFieldId($group, $fieldId);
        $field = $this->configStructure->getElement($fieldPath);

        return $field;
    }

    /**
     * Map field name if they were cloned
     *
     * @param Group $group
     * @param string $fieldId
     * @return string
     */
    private function getOriginalFieldId(Group $group, string $fieldId): string
    {
        if ($group->shouldCloneFields()) {
            $cloneModel = $group->getCloneModel();

            /** @var Field $field */
            foreach ($group->getChildren() as $field) {
                foreach ($cloneModel->getPrefixes() as $prefix) {
                    if ($prefix['field'] . $field->getId() === $fieldId) {
                        $fieldId = $field->getId();
                        break(2);
                    }
                }
            }
        }

        return $fieldId;
    }

    /**
     * Set correct scope if isSingleStoreMode = true
     *
     * @param Field $fieldConfig
     * @param ValueInterface $dataObject
     * @return void
     */
    protected function checkSingleStoreMode(Field $fieldConfig, $dataObject)
    {
        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();
        if (!$isSingleStoreMode) {
            return;
        }
        if (!$fieldConfig->showInDefault()) {
            $websites = $this->storeManager->getWebsites();
            $singleStoreWebsite = array_shift($websites);
            $dataObject->setScope('websites');
            $dataObject->setWebsiteCode($singleStoreWebsite->getCode());
            $dataObject->setScopeCode($singleStoreWebsite->getCode());
            $dataObject->setScopeId($singleStoreWebsite->getId());
        }
    }

    /**
     * Get field path
     *
     * @param Field $field
     * @param string $fieldId Need for support of clone_field feature
     * @param array $oldConfig Need for compatibility with _processGroup()
     * @param array $extraOldGroups Need for compatibility with _processGroup()
     * @return string
     */
    private function getFieldPath(Field $field, string $fieldId, array &$oldConfig, array &$extraOldGroups): string
    {
        $path = $field->getGroupPath() . '/' . $fieldId;

        /**
         * Look for custom defined field path
         */
        $configPath = $field->getConfigPath();
        if ($configPath && strrpos($configPath, '/') > 0) {
            // Extend old data with specified section group
            $configGroupPath = substr($configPath, 0, strrpos($configPath, '/'));
            if (!isset($extraOldGroups[$configGroupPath])) {
                $oldConfig = $this->magentoConfig->extendConfig($configGroupPath, true, $oldConfig);
                $extraOldGroups[$configGroupPath] = true;
            }
            $path = $configPath;
        }

        return $path;
    }

    /**
     * Get scope name and scopeId
     *
     * @todo refactor to scope resolver
     * @return void
     */
    private function initScope()
    {
        if ($this->website === null) {
            $this->website = '';
        }
        if ($this->store === null) {
            $this->store = '';
        }

        if ($this->store) {
            $scope = 'stores';
            $store = $this->storeManager->getStore($this->store);
            $scopeId = (int)$store->getId();
            $scopeCode = $store->getCode();
        } elseif ($this->website) {
            $scope = 'websites';
            $website = $this->storeManager->getWebsite($this->website);
            $scopeId = (int)$website->getId();
            $scopeCode = $website->getCode();
        } else {
            $scope = 'default';
            $scopeId = 0;
            $scopeCode = '';
        }
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $this->scopeCode = $scopeCode;
    }
}
