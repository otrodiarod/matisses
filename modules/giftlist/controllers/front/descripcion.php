<?php

include_once __DIR__ . '/../../classes/GiftList.php';
include_once __DIR__ . '/../../classes/ListProductBond.php';
include_once __DIR__ . '/../../classes/Bond.php';
include_once _PS_MODULE_DIR_ . "matisses/matisses.php";
include_once _PS_OVERRIDE_DIR_ ."controllers/front/CartController.php";
define("_ERROR_","Ha ocurrido un error, vuelve a intentarlo más tarde");
define("_DELETED_","Eliminado correctamente");
define("_EDITED_","Se ha editado la información correctamente");

class giftlistdescripcionModuleFrontController extends ModuleFrontController {
	public $uploadDir;
	public $module;
	/**
	* Select all event types
	* Select firstname and lastnamen from creator and cocreator
	* Set template by condicion
	*/
    
    private function getCreadotr(){
        $list = new GiftListModel();
        $res = $list->getListBySlug(Tools::getValue('url'));
        return $res;
    }
	public function initContent() {
        global $cookie;
		parent::initContent ();
        $this->display_column_left = false;
        $this->display_column_right = false;
		$list = new GiftListModel();
		$lpd = new ListProductBondModel();
		if(!$res = $list->getListBySlug(Tools::getValue('url')))
		{
			Tools::redirect($this->context->link->getModuleLink('giftlist', 'listas'));
		}
        $this->list = $res;
		$ev = "SELECT name FROM "._DB_PREFIX_."event_type WHERE id =".$res['event_type'];
		$sql = "SELECT id_customer,firstname,lastname FROM "._DB_PREFIX_.
		"customer WHERE id_customer = ". $res['id_creator'];
		$sql2 = "SELECT id_customer,firstname,lastname,email FROM "._DB_PREFIX_.
		"customer WHERE id_customer = ". $res['id_cocreator'];
		$creator = Db::getInstance()->getRow($sql);
		$cocreator = Db::getInstance()->getRow($sql2);
        $months = Tools::dateMonths();
		$days = Tools::dateDays();
		$this->context->smarty->assign ( array (
			'list_desc' => $res,
            'ev_date' => explode("-",substr($res['event_date'],0,-9)),
            'address' => Tools::jsonDecode($res['info_creator']),
            'address_before' => $this->getAddressBefore($res['address_before']),
            'address_after' => $this->getAddressAfter($res['address_after']),
			'all_link' => $this->context->link->getModuleLink('giftlist', 'listas'),
			'admin_link' => $this->context->link->getModuleLink('giftlist', 'administrar',array("url" => Tools::getValue('url'))),
			'form' => _MODULE_DIR_ ."giftlist/views/templates/front/partials/form_save_list.php",
			'form_edit' => _MODULE_DIR_ ."giftlist/views/templates/front/partials/form_edit_list.php",
			'form_cocreator' => _MODULE_DIR_ ."giftlist/views/templates/front/partials/cocreator_info.php",
			'bond_form' => _MODULE_DIR_ ."giftlist/views/templates/front/partials/bond_form.php",
			'creator' => $res['firstname'] . " " . $res['lastname'],
			'cocreator' => ($cocreator ? $cocreator['firstname'] . " " . $cocreator['lastname'] : false),
			'products' => $lpd->getProductsByList($res['id']),
			'event_type' => Db::getInstance()->getValue($ev),
            'bond' => $lpd->getBondsByList($res['id']),
            'days' => $list->getMissingDays($res['event_date']),
            'numberProducts' => $list->getNumberProductsByList($res['id']),
			'share_list' => _MODULE_DIR_ ."giftlist/views/templates/front/partials/share_email.php",
            'countries' => CountryCore::getCountries($this->context->language->id),
            'cats' => Category::getCategories( (int)($cookie->id_lang), true, false  ),
            'items_per_page' => 8,
            'months' => $months,
            'days_d' => $days,
            'year' => date('Y'),
            'limit' => date('Y') + 20,
            'email_co' => $cocreator['email'],
            'events' => Db::getInstance ()->executeS ( "SELECT * FROM `" . _DB_PREFIX_ . "event_type`" ),
		) );

		if($this->context->customer->isLogged()){
			if($res['id_creator'] == $this->context->customer->id || $res['id_cocreator'] == $this->context->customer->id)
				$this->setTemplate ( 'listOwnerDesc.tpl' );
			else
				$this->setTemplate ( 'listDesc.tpl' );
		}
		else{
			$this->setTemplate ( 'listDesc.tpl' );
		}
	}
    
    private function getAddressAfter($id){
        $add = new AddressCore($id);
        $cName = Db::getInstance()->getValue("SELECT name FROM "._DB_PREFIX_."country_lang WHERE id_country = ". $add->id_country . " AND id_lang = " . $this->context->language->id );
        $ret = array(
            'complete' => $add->address1.  " " . $add->address2 . " - " . ucfirst(strtolower($add->city)) .", " . ucfirst(strtolower($cName)),
            'address' => $add,
        );
        return $ret;
    }
    
    private function getAddressBefore($id){
        $add = new AddressCore($id);
        $cName = Db::getInstance()->getValue("SELECT name FROM "._DB_PREFIX_."country_lang WHERE id_country = ". $add->id_country . " AND id_lang = " . $this->context->language->id );
        $ret = array(
            'complete' => $add->address1.  " " . $add->address2 . " - " . ucfirst(strtolower($add->city)) .", " . ucfirst(strtolower($cName)),
            'address' => $add,
        );
        return $ret;
    }

	public function init(){
		parent::init();
		if($this->ajax){
            $method = Tools::getValue("method");
			if(trim($method)){
				switch(Tools::getValue("method")){
					case "delete-product":
						$this->_deteleProductFromList(Tools::getValue("id_list"),Tools::getValue('id_product'),Tools::getValue('id_att'));
						break;
					case "addBond":
						$this->_addBond(Tools::getValue('id_list'), Tools::getValue('data'));
						break;
                    case "saveMessage":
                        $this->_saveMessaage(Tools::getValue('id_list'), Tools::getValue('message'));
                    case "uploadImage":
                        $this->_uploadImage(Tools::getValue('id_list'), Tools::getValue('prof'));
                    case "deleteImage":
                        $this->_deleteImage(Tools::getValue('id_list'), Tools::getValue('prof'));
                    case "deleteMsg":
                        $this->_deleteMsg(Tools::getValue('id_list'));
                    case "share":
						$this->_shareList();
                    case "saveAddress":
						$this->_saveAddress(Tools::getValue('id_list'), Tools::getValue('form'));
                    case "updateAmount":
						$this->_updateminAmount(Tools::getValue('id_list'), Tools::getValue('value'));
                    case "editInfo":
						$this->_editInfo(Tools::getValue('id_list'), Tools::getValue('data'));
                    case "productDetail":
						$this->_productDetail(Tools::getValue('id_prod'),Tools::getValue('id_list'),Tools::getValue('id_att'));
                    case "changeFavorite":
						$this->_changeFavorite(Tools::getValue('id_prod'),Tools::getValue('id_list'),Tools::getValue('fav'));
                    case "updateQty":
						$this->_updateQty(Tools::getValue('id_prod'),Tools::getValue('id_list'),Tools::getValue('id_attr'),Tools::getValue('cant'));
				}
			}
		}
	}
    
    private function _updateQty($id_prod,$id_list,$id_attr,$cant){
        $response = StockAvailable::getQuantityAvailableByProduct((int)$id_prod,(int)$id_attr);
        if($response < (int)$cant){
            die(Tools::jsonEncode(array(
                'error' => true,
                'msg' => "No hay suficiente producto en inventario"
            )));
        }else{
            $prod = Db::getInstance()->getRow("SELECT cant,missing FROM "._DB_PREFIX_."list_product_bond WHERE id_product = ".$id_prod . " AND id_list = ".$id_list);
            Db::getInstance()->update('list_product_bond',array(
                'cant' => $cant,
                'missing' => ($prod['cant'] == $prod['missing'] ? $cant : $cant - ($prod['cant'] - $prod['missing'])),
            ),"id_product = ".$id_prod . " AND id_list = ".$id_list);
            die(Tools::jsonEncode(array(
                'error' => false,
                'msg' => ""
            )));
        }
    }
    
    private function _changeFavorite($id_prod,$id_list,$fav){
        $res = Db::getInstance()->update('list_product_bond',array(
            'favorite' => (int)$fav
            ),
            'id_product = '.$id_prod. " AND id_list = ". $id_list
        );
        if($res)
            die(Tools::jsonEncode(array('error'=>false,'msg' => $res)));
        else
            die(Tools::jsonEncode(array('error'=>true,'msg' => Db::getInstance()->getMsgError())));
    }
    
    public function displayProductListReviews($params)
	{
        require_once(_PS_MODULE_DIR_.'productcomments/ProductComment.php');
		$id_product = (int)$params['product']['id_product'];
        $average = ProductComment::getAverageGrade($id_product);
        $this->context->smarty->assign(array(
            'product' => $params['product'],
            'averageTotal' => round($average['grade']),
            'ratings' => ProductComment::getRatings($id_product),
            'nbComments' => (int)ProductComment::getCommentNumber($id_product)
        ));
		
		return $this->context->smarty->fetch(_PS_THEME_DIR_.'/modules/productcomments/productcomments_reviews.tpl');
	}
    
    private function _productDetail($id_prod,$id_list,$id_att){
        $prod = new ProductCore((int)$id_prod);
        $link = new LinkCore();
        if((int)Tools::getValue('group'))
            $infoList = ListProductBondModel::getByProductAndList($id_prod,$id_list,$id_att,Tools::getValue('id_lpd'));
        else
            $infoList = ListProductBondModel::getByProductAndListNotAgroup($id_prod,$id_list,$id_att);
        $image = ProductCore::getCombinationImageById( (int)$infoList['option'][3]->value, Context::getContext()->language->id);
        $params['reference'] = $prod->reference;
        $params['product']['id_product'] = (int)$id_prod;
        $group = $prod->getAttributeCombinationsById((int)$id_att,Context::getContext()->language->id);
        $styleColor = ""; 
        $attr = new AttributeCore($group[0]['id_attribute']);
        $sPrice = Db::getInstance()->getValue("SELECT price FROM `"._DB_PREFIX_."specific_price` WHERE `id_product` = ".$id_prod." AND`id_product_attribute` = ".(int)$id_att);
        
        if(file_exists(_PS_COL_IMG_DIR_.$group[0]['id_attribute']."jpg"))
            $styleColor = 'url('._PS_COL_IMG_DIR_.$group[0]['id_attribute']."jpg)";
        else
            $styleColor = $attr->color;
        
        $price = ($sPrice == 0 ? $prod->price : $sPrice);
        
        die(Tools::jsonEncode(array(
            'image' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$link->getImageLink($prod->link_rewrite[1], (isset($image[0]['id_image']) ? $image[0]['id_image'] : $image['id_image'])),
            'name' => $prod->name[1],
            'reference' => hook::exec('actionMatChangeReference',$params),
            'desc' => $prod->description_short[1],
            'price' => ((int)Tools::getValue('group') ? Tools::displayPrice($price * (int)$infoList['cant']) : Tools::displayPrice($price)),
            'missing' => $infoList['missing'],
            'bought' => $infoList['bought'],
            'total' => $infoList['total'],
            'cantGroup' => $infoList['cant'],
            'reviews' =>$this->displayProductListReviews($params),
            'style'=> $styleColor,
            'colorName' => $attr->name[1],
            'group' => ($infoList['group'] ? true : false),
            'id_product' => $id_prod,
            'id_product_attribute' => (int)$id_att,
        )));
    }
    
    private function _editInfo($id,$data){
        $data = (object)$data;
        $ev_date = date("Y-m-d",strtotime($data->years."-".$data->months."-".$data->days));
        $today = date("Y-m-d");
        $l = new GiftListModel($id);
        if($today >= $ev_date)
            die(
                Tools::jsonEncode(array(
                    'msg' => $this->module->l('La fecha seleccionada debe ser posterior a la fecha actual.'),
                    'error' => 1
                ))
            );    
        if($data->email_co && $data->email_co != "")
            $l->id_cocreator = $l->setCoCreator($l->id,$data->email_co,$data->firstname . " " .$data->lastname,$l->url);
        $l->event_date = $ev_date;
        $ev = "SELECT name FROM "._DB_PREFIX_."event_type WHERE id =".$data->event_type;
        Db::getInstance()->update('gift_list',array(
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'id_cocreator' =>  $l->id_cocreator,
            'event_date' => $l->event_date,
            'event_type' => $data->event_type,
            'real_not' => isset($data->real_not) ? 1 : 0,
            'cons_not' => isset($data->cons_not) ? 1 : 0,
        ),"id = " . $id);
        die(Tools::jsonEncode(array(
            'msg' => $this->module->l('La lista ha sido editada exitosamente.'),
            'name' => $data->firstname . " " . $data->lastname, 
            'date' => date("d/m/Y",strtotime($data->years."-".$data->months."-".$data->days)),
            'event' => Db::getInstance()->getValue($ev),
            'days' => $l->getMissingDays($ev_date),
            'error' => 0
        )));
    }
    
    private function _updateminAmount($id,$val){
        $sql = "UPDATE "._DB_PREFIX_."gift_list SET min_amount = $val  WHERE id = ".$id;
        Db::getInstance()->execute($sql);
    }
    
    private function _deleteMsg($id){
        $sql = "UPDATE "._DB_PREFIX_."gift_list SET message = ''  WHERE id = ".$id;
        Db::getInstance()->execute($sql);
    }
    
    private function _deleteImage($id,$prof){
        $li = new GiftListModel($id);
        $image = ($prof == "1" ? 'avatar.png' : "banner.jpg");
        $sql = "UPDATE "._DB_PREFIX_."gift_list SET ". ($prof == "1" ? "profile_img":"image") .' = "/modules/giftlist/views/img/'.$image.'"  WHERE id = '.$id;
        Db::getInstance()->execute($sql);
        die('/modules/giftlist/views/img/'.$image);
    }
    private function _saveMessaage($id, $message){
        if(Db::getInstance()->update('gift_list', array('message' => $message),"id = ".$id))
            die(Tools::jsonEncode("Se ha actualizado el mensaje correctamente"));
        else
            die(Tools::jsonEncode("Ha ocurrido un error"));
    }

	public function setMedia() {
        $addJs = "";
        $res = $this->getCreadotr();
		parent::setMedia ();
        if($this->context->customer->isLogged()){
			if($res['id_creator'] == $this->context->customer->id || $res['id_cocreator'] == $this->context->customer->id)
				$addJs = _MODULE_DIR_ . '/giftlist/views/js/descripcion.js';
			else
				$addJs = _MODULE_DIR_ . '/giftlist/views/js/descripcion_user.js';
		}
		else{
			$addJs = _MODULE_DIR_ . '/giftlist/views/js/descripcion_user.js';
        }            
        
		$this->addJS ( array (
            _MODULE_DIR_ . '/giftlist/views/js/vendor/jplist/jplist.core.min.js',
			_MODULE_DIR_ . '/giftlist/views/js/vendor/jplist/jplist.pagination-bundle.min.js',
			_MODULE_DIR_ . '/giftlist/views/js/vendor/validation/jquery.validate.min.js',
            _MODULE_DIR_ . '/giftlist/views/js/vendor/validation/messages_es.js',
			_MODULE_DIR_ . '/giftlist/views/js/vendor/owl/owl.carousel.min.js',
			_MODULE_DIR_ . '/giftlist/views/js/vendor/serializeObject/jquery.serializeObject.min.js',
			$addJs
		) );
		$this->addCSS ( array (
            _MODULE_DIR_ . '/giftlist/views/css/vendor/jplist/jplist.core.min.css',
			_MODULE_DIR_ . '/giftlist/views/css/vendor/jplist/jplist.pagination-bundle.min.css',
            _MODULE_DIR_ . '/giftlist/views/css/vendor/owl/owl.carousel.css',
			_MODULE_DIR_ . '/giftlist/views/css/ax-lista-de-regalos.css'
		) );
	}

	public function __construct() {
        $this->uploadDir = _PS_UPLOAD_DIR_."giftlist/";
		$this->module = Module::getInstanceByName ( Tools::getValue ( 'module' ) );
		if (! $this->module->active)
			Tools::redirect ( 'index' );

		$this->page_name = 'module-' . $this->module->name . '-' . Dispatcher::getInstance ()->getController ();
		parent::__construct ();
	}

	public function postProcess(){
        //echo "<pre>";echo print_r($_POST);die("</pre>");
		if(Tools::isSubmit ('saveList'))
		$this->_saveList(Tools::getValue("id_list"));
	}

	/**
	* @param int $id_list
	* @param int $id_product
	*/
	private function _deteleProductFromList($id_list,$id_product,$id_attr){
		$lpd = new ListProductBondModel();
		$lpd->deleteProduct($id_list, $id_product, $id_attr);
        die(_DELETED_);
	}

	private function _addBond($id_list, $data){
		if ($this->context->cart->id)
		{
			$cart = new Cart($this->context->cookie->id_cart);
		}
		else
		{
			$cart = new Cart();
			$cart->id_lang = $this->context->language->id;
			$cart->id_currency = $this->context->currency->id;
			$cart->save();
		}
        
        $products = $cart->getProducts();
        foreach($products as $product){

            if($product['id_giftlist'] != 0 && $product['id_giftlist'] != $id_list){

                die(Tools::jsonEncode(array(
				    'msg' => 'Recuerda que solo puedes agregar productos de una misma Lista de regalos a un solo carrito de compras',
                    'error' => true
				)));

            }elseif($product['id_giftlist'] == 0){
                die(Tools::jsonEncode(array(
                    'msg' => 'Recuerda que no puedes agregar productos del Ecommerce y de una Lista de regalos en un mismo carrito',
                    'error' => true
                )));

            }

        }
        
        $mat = new Matisses();
        $res = $mat->wsmatissess_getVIPGift($data['mount']);
        $FreeVipBond = $res["return"]['detail'];
		$bond = new BondModel();
		$list = new GiftListModel($id_list);
		$bond->id_list = $id_list;
        if($list->min_amount > $data['mount'])
            die(Tools::jsonEncode(array(
				'msg' =>  'Por favor, escribe un valor mayor o igual a '.$data['mount']
				)));
		$bond->value = $data['mount'];
		$bond->message = $data['message'];
		$bond->luxury_bond = ($FreeVipBond ? 1 : (isset($data['luxury_bond']) ? 1 : 0));
		$bond->created_at = date( "Y-m-d H:i:s" );
		$sql = "SELECT id_product FROM "._DB_PREFIX_."product WHERE reference = 'BOND-LIST'";
		$id_product = Db::getInstance()->getValue($sql);
		if(!$bond->save())
			die(Tools::jsonEncode(array(
				'msg' =>  'No se ha podido guardar el bono de regalo'
				)));
		else{
			Db::getInstance()->insert('cart_product', array(
				'id_cart' => $cart->id,
				'id_product' => $id_product,
				'id_address_delivery' => 0,
				'id_shop' => $this->context->shop->id,
				'id_bond' => $bond->id,
				'id_giftlist' => $id_list,
				'id_product_attribute' => 0,
				'quantity' => 1,
				'date_add' => date( "Y-m-d H:i:s" )
			));
			$this->context->cookie->id_cart = ( int )$cart->id;
			$this->ajax_refresh = true;
			CartRule::autoAddToCart($this->context);
			die(Tools::jsonEncode(array(
				'msg' =>  'Se ha agregado un bono de $'.$bond->value. ' a la lista "'. $list->name.'"'
				)));
            $cartController = new CartController();
            $_GET['summary'] = true;
            $this->context->cookie->ajax_blockcart_display = 1;
            $cartController->displayAjax();
		}
    }

		/**
        *only for cocreator who cannot edit the list
		* @param int $id
		*/
	private function _saveAddress($id,$data){
        $c = CountryCore::getCountries($this->context->language->id);
		$li = new GiftListModel ($id);
        $li->firstname = $data['firstname'];
        $li->lastname = $data['lastname'];
		$li->info_creator = Tools::jsonEncode(array(
            'country' => 'Colombia',
            'city' => ucfirst(strtolower($c[$data['city']]['name'])),
            'town' => ucfirst(strtolower($data['town'])),
            'address' => $data['address'],
            'address_2' => $data['address_2'],
            'tel' => $data['tel'],
        ));
        $state = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_."state WHERE id_state = ".$data['before-town']);
        Db::getInstance()->update(
            'address',
            array(
                'firstname' => $data['before-firstname'],
                'lastname' => $data['before-lastname'],
                'phone' => $data['before-tel'],
                'id_country' => $data['before-city'],
                'id_state' => $data['before-town'],
                'city' => $state['name'],
                'postcode' => $state['isocode'],
                'address1' => $data['before-address'],
                'address2' => $data['before-address_2'],
            ),"id_address = " . $li->address_before
        );
        $state = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_."state WHERE id_state = ".$data['after-town']);
        Db::getInstance()->update(
            'address',
            array(
                'firstname' => $data['after-firstname'],
                'lastname' => $data['after-lastname'],
                'phone' => $data['after-tel'],
                'id_country' => $data['after-city'],
                'id_state' => $data['after-town'],
                'city' => $state['name'],
                'postcode' => $state['isocode'],
                'address1' => $data['after-address'],
                'address2' => $data['after-address_2'],
            ),"id_address = " . $li->address_after
        );
		try {
			if ($li->updateInfo()){
                $ab = $this->getAddressBefore($li->address_before);
                $aa = $this->getAddressAfter($li->address_after);
				die( Tools::jsonEncode(array (
					'response' => _EDITED_,
                    'name' => $data['firstname'] . " " . $data['lastname'],
                    'a_b' => $ab['complete'],
                    'a_a' => $aa['complete'],
					'error' => false
				)));
			}
			else
				die(Tools::jsonEncode(array (
					'response' => _ERROR_,
					'error' => true
				)));
		} catch ( Exception $e ) {
			die(Tools::jsonEncode(array (
				'response' => $e->getMessage(),
				'error' => true
			)));
		}
	}

	/**
	* upload image from list
	* @return boolean|string|NULL
	*/
	private function _uploadImage($id, $prof){
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir);         
        }
        $prof = ($prof == "true" ? true : false);
		if ($_FILES['file-0']['name'] != '') {
			$file = Tools::fileAttachment('file-0');
			$sqlExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
			$mimeType = array('image/png', 'image/x-png','image/jpeg','image/gif');
			if(!$file || empty($file) || !in_array($file['mime'], $mimeType))
				return false;
			else {
				move_uploaded_file($file['tmp_name'], $this->uploadDir . ($prof ? "prof_" : "") . $id. ".". $sqlExtension);
                $image_name = ($prof ? "prof_" : "") . $id. ".". $sqlExtension;
                $sql = "UPDATE "._DB_PREFIX_."gift_list SET ". ($prof ? "profile_img":"image") .' = "/upload/giftlist/'.$image_name.'" WHERE id = '.$id;
                Db::getInstance()->execute($sql);
			}
			@unlink($file);
			die(isset($image_name) ? "/upload/giftlist/" . $image_name : false);
		}
		return false;
	}
    
    private function _shareList(){
		$id_shop = (int)Context::getContext()->shop->id;
		$id_lang = $this->context->language->id;
		$list = new GiftListModel (Tools::getValue('id_list'));
		$currency = $this->context->currency;
		$customer = new CustomerCore($list->id_creator);
		$params = array(
			'{lastname}' => $customer->lastname,
			'{firstname}' => $customer->firstname,
			'{code}' => $list->code,
			'{description_link}' => $this->context->link->getModuleLink('giftlist', 'descripcion',array('url' => $list->url))
		);

		if(!empty($list->id_cocreator)){
			$customer = new CustomerCore($list->id_cocreator);
			$params['firstname_co'] = $customer->firstname;
			$params['lastname_co'] = $customer->lastname;

			MailCore::Send($id_lang, 'share-list', sprintf(
			MailCore::l('Te han compartido una lista'), 1),
			$params, Tools::getValue('email'), $customer->firstname.' '.$customer->lastname,
			null, null, null,null, _MODULE_DIR_."giftlist/mails/", true, $id_shop);
			die("Se ha compartido la lista correctamente");
		}
		MailCore::Send($id_lang, 'share-list-no-cocreator', sprintf(
		MailCore::l('Te han compartido una lista'), 1),
		$params, Tools::getValue('email'), $customer->firstname.' '.$customer->lastname,
		null, null, null,null, _MODULE_DIR_."giftlist/mails/", true, $id_shop);
		die("Se ha compartido la lista correctamente");
	}
}