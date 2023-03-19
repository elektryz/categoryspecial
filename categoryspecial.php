<?php

use Kamil\CategorySpecial\Domain\Reviewer\Command\UpdateCategorySpecialCommand;
use Kamil\CategorySpecial\Domain\Reviewer\Query\GetCategorySpecialSettingsForForm;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotCreateCategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotToggleCategorySpecialStatusException;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Kamil\CategorySpecial\Entity\CategorySpecial as CS;

class Categoryspecial extends Module implements WidgetInterface
{
    private $_html;
    private $templateFile;

    public function __construct()
    {
        $this->name = 'categoryspecial';
        $this->version = '1.0.0';
        $this->author = 'Kamil';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Category special');
        $this->description = $this->displayName;

        $this->ps_versions_compliancy = [
            'min' => '1.7.6.0',
            'max' => '8.99.99',
        ];

        $this->templateFile = 'module:' . $this->name . '/views/templates/hook/widget.tpl';
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionFrontControllerInitAfter') &&
            $this->registerHook('actionCategoryGridDefinitionModifier') &&
            $this->registerHook('actionCategoryGridQueryBuilderModifier') &&
            $this->registerHook('actionCategoryFormBuilderModifier') &&
            $this->registerHook('actionAfterCreateCategoryFormHandler') &&
            $this->registerHook('actionAfterUpdateCategoryFormHandler') &&
            $this->installTables();
    }

    public function renderWidget($hookName, array $params)
    {
        $smartyVars = $this->getWidgetVariables($hookName, $params);

        if (count($smartyVars) == 0) {
            return;
        }

        $this->smarty->assign($smartyVars);

        return $this->fetch($this->templateFile);
    }

    public function getWidgetVariables($hookName, array $params)
    {
        $idCategory = 0;

        if (Tools::getIsset('id_category')) {
            $idCategory = (int)Tools::getValue('id_category');
        }

        if (isset($params['id_category'])) {
            $idCategory = (int)$params['id_category'];
        }

        if ($idCategory == 0) {
            return [];
        }

        $categoryObj = new Category($idCategory, Context::getContext()->language->id);

        if (!Validate::isLoadedObject($categoryObj)) {
            return [];
        }

        return [
            'categoryspecial_name' => $categoryObj->name,
            'categoryspecial_is_special' => CS::isSpecial($idCategory),
        ];
    }

    public function hookActionFrontControllerInitAfter($params)
    {
        $x = $params['controller'];
        $controller = '';

        if (isset($x->php_self)) {
            $controller = $x->php_self;
        }

        if (Tools::getIsset('controller')) {
            $controller = pSQL(Tools::getValue('controller'));
        }

        $idCms = (int)Configuration::get('CATEGORYSPECIAL_CMS');

        if ($controller == 'category' && $idCms > 0) {
            if (!$this->canAccess((int)Tools::getValue('id_category'))) {
                Tools::redirect(
                    Context::getContext()->link->getCMSLink(
                        $idCms,
                        null,
                        true,
                        Context::getContext()->language->id,
                    )
                );
            }
        }
    }

    private function canAccess($idCategory)
    {
        $isSpecial = CS::isSpecial($idCategory);

        // Category is not special - access granted
        if (!$isSpecial) {
            return true;
        }

        $groupBoxConfiguration = Configuration::get('CATEGORYSPECIAL_GROUPS');
        // No groups were selected in configuration - access granted
        if (strlen(trim($groupBoxConfiguration)) == 0) {
            return true;
        }

        $isLogged = Context::getContext()->customer->isLogged();

        // Category is special but you're not logged in - access denied
        if (!$isLogged) {
            return false;
        }

        $groupBoxConfiguration = explode(',', $groupBoxConfiguration);

        $customerGroups = Db::getInstance()->executeS(
            'SELECT id_group FROM '._DB_PREFIX_.'customer_group 
            WHERE id_customer='.(int)Context::getContext()->customer->id.''
        );

        // Category is special but customer groups are empty - access denied
        if (count($customerGroups) == 0) {
            return false;
        }

        // If customer group belongs to any configuration group - access granted
        $canAccess = false;

        foreach ($customerGroups as $item) {
            $customerGroup = (int)$item['id_group'];

            if (in_array($customerGroup, $groupBoxConfiguration)) {
                $canAccess = true;
            }
        }

        return $canAccess;
    }

    public function hookActionCategoryGridDefinitionModifier(array $params)
    {
        $definition = $params['definition'];

        $definition
            ->getColumns()
            ->addAfter(
                'active',
                (new ToggleColumn('is_special'))
                    ->setName($this->l('Is special category?'))
                    ->setOptions([
                        'field' => 'is_special',
                        'primary_field' => 'id_category',
                        'route' => 'categoryspecial_toggle_category_special',
                        'route_param_name' => 'categoryId',
                    ])
            );

        $definition->getFilters()->add(
            (new Filter('is_special', YesAndNoChoiceType::class))
                ->setAssociatedColumn('is_special')
        );
    }

    public function hookActionCategoryGridQueryBuilderModifier(array $params)
    {
        $searchQueryBuilder = $params['search_query_builder'];
        $searchCriteria = $params['search_criteria'];

        $searchQueryBuilder->addSelect(
            'IF(css.`is_special` IS NULL,0,css.`is_special`) AS `is_special`'
        );

        $searchQueryBuilder->leftJoin(
            'c',
            '`' . pSQL(_DB_PREFIX_) . 'category_special`',
            'css',
            'css.`id_category` = c.`id_category`'
        );

        if ('is_special' === $searchCriteria->getOrderBy()) {
            $searchQueryBuilder->orderBy('css.`is_special`', $searchCriteria->getOrderWay());
        }

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('is_special' === $filterName) {
                $searchQueryBuilder->andWhere('css.`is_special` = :is_special');
                $searchQueryBuilder->setParameter('is_special', $filterValue);

                if (!$filterValue) {
                    $searchQueryBuilder->orWhere('css.`is_special` IS NULL');
                }
            }
        }
    }

    public function hookActionCategoryFormBuilderModifier(array $params)
    {
        $formBuilder = $params['form_builder'];
        $formBuilder->add('is_special', SwitchType::class, [
            'label' => $this->l('Is special category?'),
            'required' => false,
        ]);

        $queryBus = $this->get('prestashop.core.query_bus');

        $settings = $queryBus->handle(new GetCategorySpecialSettingsForForm($params['id']));

        $params['data']['is_special'] = $settings->isSpecial();

        $formBuilder->setData($params['data']);
    }

    public function isUsingNewTranslationSystem()
    {
        return false;
    }

    public function hookActionAfterUpdateCategoryFormHandler(array $params)
    {
        $this->updateCategorySpecialStatus($params);
    }

    public function hookActionAfterCreateCategoryFormHandler(array $params)
    {
        $this->updateCategorySpecialStatus($params);
    }

    private function updateCategorySpecialStatus(array $params)
    {
        $categoryId = $params['id'];
        $categoryData = $params['form_data'];
        $isSpecial = (bool)$categoryData['is_special'];

        $commandBus = $this->get('prestashop.core.command_bus');

        try {
            $commandBus->handle(new UpdateCategorySpecialCommand(
                $categoryId,
                $isSpecial
            ));
        } catch (CategorySpecialException $exception) {
            $this->handleException($exception);
        }
    }

    private function installTables()
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS `' . pSQL(_DB_PREFIX_) . 'category_special` (
                `id_reviewer` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_category` INT(10) UNSIGNED NOT NULL,
                `is_special` TINYINT(1) NOT NULL,
                PRIMARY KEY (`id_reviewer`)
            ) ENGINE=' . pSQL(_MYSQL_ENGINE_) . ' COLLATE=utf8_unicode_ci;
        ';

        return Db::getInstance()->execute($sql);
    }

    private function handleException(CategorySpecialException $exception)
    {
        $exceptionDictionary = [
            CannotCreateCategorySpecialException::class => 'Failed to create a record for category',
            CannotToggleCategorySpecialStatusException::class => 'Failed to toggle is special category status',
        ];

        $exceptionType = get_class($exception);

        if (isset($exceptionDictionary[$exceptionType])) {
            $message = $exceptionDictionary[$exceptionType];
        } else {
            $message = 'An unexpected error occurred';
        }

        throw new \PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException($message);
    }

    public function getMessage($text)
    {
        $translation = [
            'The special category status had been updated.' => $this->l('The special category status had been updated.'),
            'Something bad happened when trying to get category id.' => $this->l('Something bad happened when trying to get category id.'),
            'Failed to create special category.' => $this->l('Failed to create special category.'),
            'An error occurred while updating the status.' => $this->l('An error occurred while updating the status.'),
        ];

        return $translation[$text] ?? $text;
    }

    public function renderForm()
    {
        $cmsPages = CMS::getCMSPages(Context::getContext()->language->id);
        $cmsPages = array_merge(
            [
                [
                    'id_cms' => 0,
                    'meta_title' => '---',
                ]
            ],
            $cmsPages
        );

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'name' => 'CATEGORYSPECIAL_CMS',
                        'label' => $this->l('CMS page for redirection'),
                        'options' => [
                            'query' => $cmsPages,
                            'id' => 'id_cms',
                            'name' => 'meta_title'
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => $this->l('Groups allowed to access special categories'),
                        'name' => 'CATEGORYSPECIAL_GROUPS',
                        'values' => Group::getGroups(Context::getContext()->language->id),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ]
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . ucfirst($this->name);
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $groupBox = [];
        $groupBoxConfiguration = Configuration::get('CATEGORYSPECIAL_GROUPS');
        $groupsConfig = [];

        if (strlen(trim($groupBoxConfiguration)) > 0) {
            $groupsConfig = explode(',', $groupBoxConfiguration);
        }

        foreach (Group::getGroups(Context::getContext()->language->id) as $group) {
            $idGroup = (int)$group['id_group'];
            $groupBox['groupBox_' . $idGroup] = in_array($idGroup, $groupsConfig) ? 1 : 0;
        }

        return array_merge(
            [
                'CATEGORYSPECIAL_CMS' => Configuration::get('CATEGORYSPECIAL_CMS'),
            ],
            $groupBox
        );
    }

    protected function postProcess()
    {
        Configuration::updateValue('CATEGORYSPECIAL_CMS', Tools::getValue('CATEGORYSPECIAL_CMS'));

        $toSave = [];

        foreach (Group::getGroups(Context::getContext()->language->id) as $group) {
            if (Tools::getIsset('groupBox')) {
                $toSave = Tools::getValue('groupBox');
            }
        }

        Configuration::updateValue('CATEGORYSPECIAL_GROUPS', implode(',', $toSave));
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit' . ucfirst($this->name))) {
            $this->postProcess();
            $this->_html .= $this->displayConfirmation($this->l('Settings updated.'));
        }

        $this->_html .= $this->renderForm();

        return $this->_html;
    }
}
