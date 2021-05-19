<?php

class AdminHelperTestController extends ModuleAdminController
{
    private $date_interval = null;

    public function __construct()
    {
        $this->bootstrap = true;
        
        parent::__construct();
        parent::setMedia();

        $this->shopShareDatas = Shop::SHARE_CUSTOMER;

        $this->processFilter();

        $this->table      = 'test123';

        if (Tools::isSubmit('submitReset'.$this->table)) {
            $this->processResetFilters();
        }

        if(Tools::getValue('id_customer') && isset($_GET['statusdoctor'])){

            $SQLUpdtCust = 'SELECT if(active=1,0,1) AS active FROM `'._DB_PREFIX_.'customer` WHERE id_customer = '.(int)Tools::getValue('id_customer');

            $UpdtCust = Db::getInstance()->getValue($SQLUpdtCust);

            Db::getInstance()->update('customer', array(
                'active' => $UpdtCust,
            ), 'id_customer = '.(int)Tools::getValue('id_customer'));                

        }

        $fields_list = array(
            'id_customer' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'remove_onclick' => true
            ),  
            'active' => array(
                'title' => $this->l('Enabled'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'active',
            ),                 
            'firstname' => array(
                'title' => $this->l('First Name'),
                'align' => 'center',
                'remove_onclick' => true
            ),      
            'lastname' => array(
                'title' => $this->l('Last Name'),
                'align' => 'center',
                'remove_onclick' => true
            ),
            'lastname' => array(
                'title' => $this->l('Last name'),
                'width' => 'auto',
                'remove_onclick' => true
            ),
            'firstname' => array(
                'title' => $this->l('First Name'),
                'width' => 'auto',
                'remove_onclick' => true
            ),
            'email_customer' => array(
                'title' => $this->l('Email address'),
                'remove_onclick' => true,
                'orderby' => false,
                'remove_onclick' => true                    
            )                                                              
        );    
        
        $fields = array('id_customer', 'id_gender', 'firstname', 'lastname', 'email_customer');

        $OrderBy = Tools::getValue($this->table.'Orderby');
        $OrderWay = strtoupper(Tools::getValue($this->table.'Orderway', 'ASC'));   

        $page = ($page = Tools::getValue('submitFilter'.$this->table)) ? $page : 1;
        $limit = ($pagination = Tools::getValue($this->table . '_pagination')) ? $pagination : 20;
        $start = ((int)$page - 1) * $limit;            

        $sql1 = '
            SELECT
                id_customer, 
                shop_name,
                firstname, 
                lastname,
                `active`, 
                email_customer
            FROM
            (   ';

        $sql2 = 'SELECT
                    a.id_customer, 
                    a.id_shop as shop_name,
                    a.firstname, 
                    a.lastname,
                    a.`active` AS `active`, 
                    a.email AS email_customer
                    
                FROM `'._DB_PREFIX_.'customer` AS a

                WHERE 1=1
                AND a.`deleted` = 0     
        ';

        if(Shop::getContextListShopID()){
            $sql2 .= ' AND a.id_shop IN ('.implode(', ', Shop::getContextListShopID()).')';
        }

        $sql1 .= $sql2 .' ) as customer ';
        $sql1 .= ' WHERE 1=1';
        
        $sqlFilter = '';
        
        foreach ($fields AS $key => $values){
            if (Tools::getValue($this->table.'Filter_'.$values)){
                $sqlFilter = ' AND '.$values.' like "%'.Tools::getValue($this->table.'Filter_'.$values).'%"';
            }            
        }  
        
        $sql1 .= $sqlFilter;

        if($OrderBy){
            $sql1 .= ' ORDER BY '.$OrderBy.' '.$OrderWay.'';
        }

        $sql1 .= ' LIMIT '.$start.', '.$limit.'';
        

        $sqltotal = '
        SELECT 
            count(id_customer),
            firstname, 
            lastname,
            `active`, 
            email_customer                 
        FROM
        (
            SELECT a.id_customer,
            a.firstname as firstname, 
            a.lastname as lastname,
            a.`active` AS `active`, 
            a.email AS email_customer                
            FROM `'._DB_PREFIX_.'customer` AS a
            LEFT JOIN `'._DB_PREFIX_.'doctor_clinic` dc ON dc.`id_doctor` = a.`id_customer`
            WHERE 1=1
            AND EXISTS(
                SELECT cg.id_customer
                FROM `'._DB_PREFIX_.'customer_group` cg
                WHERE cg.`id_customer`=a.`id_customer` AND cg.`id_group` IN ('.implode(',', EurodietModuleClass::getDoctorsGroups()).')
            ) 
            AND a.`deleted` = 0 
            AND a.id_shop IN ('.implode(', ', Shop::getContextListShopID()).')
        )as total
        WHERE 1=1
        '.$sqlFilter.'
        ';
        
        $listTotal = Db::getInstance()->getValue($sqltotal);

        $patients = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($sql1);

        $currentIndex = "index.php?controller=AdminTest";
        $token = Tools::getAdminTokenLite('AdminTest'); 

        $helper2 = new HelperList();
        $helper2->title = "Doctors";
        $helper2->bootstrap = true;
        $helper2->show_toolbar = true;
        $helper2->toolbar_btn['export']  = array(
            'href' => $currentIndex.'&export'.$this->table.'=1&token='.$token,
            'desc' => $this->l('Export')
        );
        $helper2->simple_header = false;
        $helper2->table = $this->table;
        $helper2->className = 'Customer';
        $helper2->identifier = 'id_customer';
        $helper2->list_id = 'test123';
        $helper2->sql = $sql1;
        $helper2->lang = false;
        $helper2->explicitSelect = true;
        $helper2->allow_export = true;
        $helper2->context = Context::getContext();
        $helper2->default_form_language = $this->context->language->id;
        $helper2->token = $token;
        $helper2->currentIndex = $currentIndex;
        $helper2->shopLinkType = 'shop';
        $helper2->list_no_link = true;
        $helper2->actions = array('view');
        $helper2->listTotal = $listTotal;
        $helper2->_default_pagination = 20;
        $helper2->_pagination = array(10, 20, 50, 100, 200);

        $helper2->orderBy = $OrderBy;
        $helper2->orderWay = $OrderWay;
                
        $helper2->tpl_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );        
        
                
        $this->context->smarty->assign(array(
            'order_way' => $OrderWay,
            'fields_list'     => $helper2->generateList($patients,$fields_list)
        ));            

        if(Tools::getValue('export'.$this->table)==1){
            $patientsN = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS($sql2);                
            $this->processExportTest(';',$fields_list, $patientsN, $this->table);
        }            


        $this->context->smarty->assign(array('date_interval0' => date("d/m/Y", strtotime($this->date_interval[0]))));
        $this->context->smarty->assign(array('date_interval1' => date("d/m/Y", strtotime($this->date_interval[1]))));

        $this->setTemplate('test/test_front.tpl');  
        
    }
}