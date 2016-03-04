<?php
/**
* 2012-2014 PrestaCS, PrestaCenter - Anatoret plus s.r.o.
*
* PrestaCenter XML Export Free
*
* Module PrestaCenter XML Export Free – version for PrestaShop 1.5 and 1.6
* Modul PrestaCenter XML Export Free – verze pro PrestaShop 1.5 a 1.6
*
* FREE FOR PRIVATE USE
*
* PrestaCenter - modules and customization for PrestaShop
* PrestaCS - moduly, česká lokalizace a úpravy pro PrestaShop
* http://www.prestacs.cz
*
* @author    PrestaCenter <info@prestacenter.com>
* @category  others
* @package   prestacenterxmlexportfree
* @copyright 2012-2014 PrestaCenter - Anatoret plus s.r.o.
* @license   see "licence-prestacenter.html"
*/

/**
 * @since 1.5.0
 * @version 1.2.4.1 (2014-07-07)
*/


Dispatcher::getInstance();
class PcXmlDispatcher extends DispatcherCore
{
	public $use_routes = false;
	public function loadRoutes($id_shop = null)
	{
		parent::loadRoutes($id_shop);
	}
	public static function _init()
	{
		$parent = Dispatcher::getInstance();
		$child = new self;
		foreach (get_object_vars($parent) as $property => $value)
		{
			$child->$property = $value;
		}
		self::$instance = $child;
	}
}