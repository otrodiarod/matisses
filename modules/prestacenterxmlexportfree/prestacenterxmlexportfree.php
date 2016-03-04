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


if (!defined('_PS_VERSION_'))
	exit;
class PcXmlFreeExportException extends PrestaShopModuleException
{ }
class PrestaCenterXmlExportFree extends Module
{
	const CONTEXT_ALL = 1;
	const CONTEXT_FILE = 2;
	const CONTEXT_ITEM = 3;
	const CONTEXT_SELF = 5;
	const CFG_PREFIX = 'PC_XMLFREE_';
	const XMLTPL_BLOCK = 'ps_block';
	protected $controllerClass;
	protected $exportFiles = array();
	protected $exportInfo = array();
	protected $languages = array();
	protected $currencies = array();
	protected $categories = array();
	protected $allowedProperties = array(
		'id'				=> array(),
		'name'				=> array('key' => 'id_lang', 'helper' => 'notEmpty|clean|escape'),
		'ean'				=> array(),
		'upc'				=> array(),
		'description'		=> array('key' => 'id_lang', 'helper' => 'clean|escape'),
		'description_short' => array('key' => 'id_lang', 'helper' => 'clean|escape'),
		'manufacturer'		=> array('helper' => 'escape|strip'),
		'categories'		=> array('key' => 'id_lang', 'helper' => '?categories|clean|escape'),
		'price_vat'			=> array('key' => 'id_currency', ),
		'price_vat_local'	=> array('key' => 'id_currency', ),
		'price_vat_iso'		=> array('key' => 'id_currency', ),
		'condition'			=> array('helper' => '?condition|clean|escape'),
		'online_only'		=> array(),
		'url'				=> array('key' => 'id_lang', 'helper' => 'escape|strip', ),
		'img_url'			=> array('key' => 'id_lang', 'helper' => 'escape|strip', ),
		'days'				=> array('key' => 'id_lang'),
		'availability'		=> array('helper' => 'availability|clean|escape'),
		'reference'			=> array('helper' => 'clean|escape', ),
		'supplier_reference'=> array('helper' => 'clean|escape', ),
		'date_feed'			=> array('context' => self::CONTEXT_ALL, 'helper' => 'ftime', 'modifier' => 'LOCAL'),
		'date_add'			=> array('helper' => 'ftime', 'modifier' => 'LOCAL'),
		'date_upd'			=> array('helper' => 'ftime', 'modifier' => 'LOCAL'),
		'update_feed'		=> array('context' => self::CONTEXT_ALL, 'helper' => 'ftime', 'modifier' => 'GMT'),
		'update_item'		=> array('helper' => 'ftime', 'modifier' => 'GMT'),
		'shop_name'			=> array('context' => self::CONTEXT_ALL, 'helper' => 'escape|strip', ),
		'shop_url'			=> array('context' => self::CONTEXT_ALL, 'helper' => 'escape|strip', ),
		'lang_code'			=> array('context' => self::CONTEXT_ALL, 'key' => 'id_lang'),
		'lang_code_iso'		=> array('context' => self::CONTEXT_ALL, 'key' => 'id_lang'),
	);
	protected $tplDir = '';
	protected $exportDir = '';
	protected $moduleUrl = '';
	protected $commonTplData = array();
	protected $tempExt = '.tmp';
	protected $template;
	protected $generator;
	protected $validationErrors = array();
	protected $installerData = array(
		'rollback' => array(),
		'sqlReplacements' => array(
			'@engine@' => _MYSQL_ENGINE_,
			'@prefix@' => _DB_PREFIX_,
			'@database@' => _DB_NAME_,
			'@xml_feed@' => 'pc_xmlfree_feed',
			'@xml_service@' => 'pc_xmlfree_service'
		),
		'xpath' => null,
		'tablesExist' => false,
		'tablesEmpty' => true,
	);
	protected $tmp = array();
	public function __construct()
	{
		$this->name = 'prestacenterxmlexportfree';
		$this->tab = 'administration';
		$this->controllerClass = 'PcXmlFree'; 
		$this->version = '1.2.4.1';
		$this->author = 'PrestaCenter';
		$this->need_instance = 1;
		parent::__construct();
		$this->displayName = $this->l('PrestaCenter XML Export Free');
		$this->description = $this->l('Universal XML feed export for shopping comparison websites.');
		$this->tplDir = $this->getLocalPath().'templates'.DS;
		$this->exportDir = rtrim(_PS_ROOT_DIR_, DS).DS.'xml'.DS;
		$this->moduleUrl = rtrim($this->_path, '/').'/';
	}
	protected function initInstall()
	{
		clearstatcache();
		$doc = new DOMDocument;
		$doc->load($this->getLocalPath().'sql.xml');
		$this->installerData['xpath'] = new DOMXPath($doc);
	}
	public function install()
	{
		$this->initInstall();
		try
		{
			$this->checkInstallFolders();
			$this->createTables();
			$this->insertDefaultData();
			if (method_exists($this, 'updateTranslationsAfterInstall'))
				$this->updateTranslationsAfterInstall(false);
			if (!parent::install())
				throw new RuntimeException; 
			$this->addTab();
			return true;
		} catch (RuntimeException $e)
		{
			$this->_errors[] = $e->getMessage();
			foreach (array_reverse($this->installerData['rollback']) as $method)
			{
				try
				{
					$this->$method();
				} catch (RuntimeException $re)
				{
				}
			}
			return false;
		}
	}
	public function uninstall()
	{
		$this->initInstall();
		try
		{
			$this->checkInstallFolders();
			$this->deleteTables();
			if (!parent::uninstall())
				throw new RuntimeException; 
			$this->removeTab();
			return true;
		} catch (RuntimeException $e)
		{
			$this->_errors[] = $e->getMessage();
			return false;
		}
	}
	public function installOverrides()
	{
		if (!@copy($this->getLocalPath().'getfeed.php', $this->exportDir.'getfeed.php')
			|| !@copy($this->getLocalPath().'download.php', $this->exportDir.'download.php')
			|| !@copy($this->getLocalPath().'index.php', $this->exportDir.'index.php'))
			throw new RuntimeException($this->l('Failed to copy scripts for this module.'));
		return true;
	}
	public function uninstallOverrides()
	{
		if ((file_exists($this->exportDir.'getfeed.php') && !@unlink($this->exportDir.'getfeed.php'))
			|| (file_exists($this->exportDir.'download.php') && !@unlink($this->exportDir.'download.php'))
			|| (file_exists($this->exportDir.'index.php') && !@unlink($this->exportDir.'index.php')))
			return false;
		return true;
	}
	protected function insertDefaultData()
	{
		$db = Db::getInstance();
		$inserted = 0;
		foreach ($this->installerData['xpath']->query('//sql/defaultData/query') as $node)
		{
			$sql = strtr($node->nodeValue, $this->installerData['sqlReplacements']);
			if (!$db->execute($sql))
				throw new RuntimeException(sprintf($this->l('Saving the source data into the database failed.')).' : '.$db->getMsgError());
			$inserted += $db->Affected_Rows();
		}
	}
	protected function tablesExist()
	{
		foreach ($this->installerData['xpath']->query('//sql/check/query') as $node)
		{
			$sql = strtr($node->nodeValue, $this->installerData['sqlReplacements']);
			$tmp = Db::getInstance()->executeS($sql);
			if ($tmp === false)
				continue;
			if (count($tmp) > 0)
			{
				$this->installerData['tablesExist'] = true;
				foreach ($tmp as $tableInfo)
				{
					if ($tableInfo['Rows'] > 0)
					{
						$this->installerData['tablesEmpty'] = false;
						return true;
					}
				}
			}
			return true;
		}
		return false;
	}
	protected function createTables()
	{
		$this->installerData['rollback'][] = 'deleteTables';
		$db = Db::getInstance();
		foreach ($this->installerData['xpath']->query('//sql/install/query') as $node)
		{
			$sql = strtr($node->nodeValue, $this->installerData['sqlReplacements']);
			if (!$db->execute($sql))
				throw new RuntimeException(sprintf($this->l('Failed to create database tables for this module.')).' : '.$db->getMsgError());
		}
	}
	protected function deleteTables()
	{
		$db = DB::getInstance();
		foreach ($this->installerData['xpath']->query('//sql/uninstall/query') as $node)
		{
			$sql = strtr($node->nodeValue, $this->installerData['sqlReplacements']);
			if (!$db->execute($sql))
				throw new RuntimeException(sprintf($this->l('Failed to remove database tables of this module.')).' : '.$db->getMsgError());
		}
	}
	protected function addTab()
	{
		$this->installerData['rollback'][] = 'removeTab';
		$id_parent = Tab::getIdFromClassName('AdminCatalog');
		if (!$id_parent)
			throw new RuntimeException(sprintf($this->l('Failed to add the module into the main BO menu.')).' : '.Db::getInstance()->getMsgError());
		$tabNames = array();
		foreach (Language::getLanguages(false) as $lang)
			$tabNames[$lang['id_lang']] = $this->displayName;
		$tab = new Tab(); 
		$tab->class_name = $this->controllerClass;
		$tab->name = $tabNames;
		$tab->module = $this->name;
		$tab->id_parent = $id_parent;
		if (!$tab->save())
			throw new RuntimeException($this->l('Failed to add the module into the main BO menu.'));
	}
	protected function removeTab()
	{
		if (!Tab::getInstanceFromClassName($this->controllerClass)->delete())
			throw new RuntimeException($this->l('Failed to remove the module from the main BO menu.'));
	}
	public function getContent()
	{
		$id = Tab::getIdFromClassName($this->controllerClass);
		$token = Tools::getAdminToken($this->controllerClass.$id.(int)$this->context->employee->id);
		Tools::redirectAdmin('index.php?controller='.$this->controllerClass.'&token='.$token);
		die;
	}
	protected function checkInstallFolders()
	{
		$errors = '';
		$writableDirs = array(
			$this->exportDir,
		);
		foreach ($writableDirs as $dir)
		{
			if (!file_exists($dir) && !@mkdir($dir))
			{
				$errors .= sprintf($this->l('Directory (%s) cannot be created. Please create it and set the write permission.'), $dir);
			}
			elseif (!is_dir($dir))
			{
				$errors .= sprintf($this->l('Error: name (%s) is not a directory but a file.'), $dir);
			}
			elseif (!is_writable($dir) && !chmod($dir, 0775))
			{
				$errors .= sprintf($this->l('Directory (%s) is not writable. Please set the write permission.'), $dir);
			}
		}
		if ($errors)
		{
			throw new RuntimeException($errors);
		}
	}
	public function checkExportFolder()
	{
		$errors = '';
		clearstatcache();
		if (!is_writable($this->exportDir) && !chmod($this->exportDir, 0775))
			$errors .= ' '.sprintf($this->l('Directory (%s) is not writable. Please set the write permission.'), '/xml');
		if (!is_writable($this->tplDir) && !chmod($this->tplDir, 0755))
			$errors .= ' '.sprintf($this->l('Directory (%s) is not writable. Please set the write permission.
'), '/modules/'.$this->name.'/templates');
		if ($errors)
			throw new RuntimeException($errors);
	}
	public function DOMErrorHandler($errno, $errstr, $errfile, $errline)
	{
		static $found = array();
		if ($errno == E_WARNING && strpos($errstr, 'DOMDocument::loadXML()') !== false)
		{
			$line = 0;
			$error = '';
			if (preg_match('~, line: (\d+)$~', $errstr, $m))
			{
				$line = $m[1];
			}
			if (preg_match('~Unsupported encoding (\S+) ~', $errstr, $m))
			{
				$error = sprintf($this->l('Unsupported encoding %s.'), $m[1]);
			}
			elseif (strpos($errstr, "parsing XML declaration: '?>' expected"))
			{
				if (isset($found['declar']))
				{
					return true;
				}
				$error = sprintf($this->l('An unspecified error has occurred in the template XML on line %s.'), $line);
			}
			elseif (strpos($errstr, 'Malformed declaration expecting version')
					|| strpos($errstr, 'Unsupported version'))
			{
				$error = $this->l('Unsupported XML version.');
				$found['declar'] = true;
			}
			elseif (strpos($errstr, 'xmlParsePITarget: invalid name prefix')
					|| strpos($errstr, 'xmlParsePI : no target name'))
			{
				$error = sprintf($this->l('XML template must start with: %s.'), '<?xml ');
			}
			elseif (strpos($errstr, 'EntityRef: expecting \';\''))
			{
				$error = sprintf($this->l('Unclosed entity (missing semicolon) found on line %s.'), $line);
			}
			elseif (preg_match('~Namespace prefix (\w+) on (\w+) is not defined~', $errstr, $m))
			{
				$error = sprintf($this->l('Namespace "%1$s" on element "%2$s" is not defined.'), $m[1], $m[2]);
			}
			elseif (preg_match('~Opening and ending tag mismatch: (\S+) line (\d+) and (\S+)~', $errstr, $m))
			{
				if (isset($found['attrib']))
				{
					return true;
				}
				$error = sprintf($this->l('Opening and ending tag mismatch: %1$s on line %2$s vs. %3$s on line %4$s.'), "<$m[1]>", $m[2], "</$m[3]>", $line);
			}
			elseif (preg_match('~Premature end of data in tag (\S+) line (\d+)~', $errstr, $m))
			{
				if (isset($found['cdata']))
				{
					if ($found['cdata'] === 2)
					{
						return true;
					}
					$found['cdata'] = 2;
					$error = sprintf($this->l('CDATA section in tag %1$s, starting on line %2$s, is not closed.'), $m[1], $m[2]);
				}
				else
				{
					$error = sprintf($this->l('Element %1$s, starting on line %2$s, is not closed.'), $m[1], $m[2]);
				}
			}
			elseif (strpos($errstr, ': expected \'>\''))
			{
				$error = sprintf($this->l('Ending tag cannot contain any additional data (check line %s).'), $line);
			}
			elseif (strpos($errstr, 'AttValue: " or \' expected'))
			{
				$error = sprintf($this->l('Attribute values must be enclosed in single or double quotes, check line %s.'), $line);
				$found['attrib'] = true;
			}
			elseif (strpos($errstr, 'Extra content at the end of the document'))
			{
				if (isset($found['attrib']))
				{
					return true;
				}
				$error = $this->l('Extra content at the end of the XML document.');
			}
			elseif (strpos($errstr, 'Unescaped \'<\' not allowed in attributes'))
			{
				$found['attrib'] = true;
				$error = sprintf($this->l('Attribute value cannot contain raw special characters (such as %s), use entity instead.'), '&lt;');
			}
			elseif (strpos($errstr, 'StartTag: invalid element name'))
			{
				if (isset($found['attrib']))
				{
					return true;
				}
				$error = sprintf($this->l('XML element cannot contain raw special characters (such as %s). Use entity instead, or wrap the whole element contents in a CDATA section.'), '&lt;');
			}
			elseif (preg_match('~Specification mandate value for attribute (\S+)~', $errstr, $m))
			{
				$error = sprintf($this->l('All XML attributes must have a value (even if empty string), check attribute %1$s on line %2$s.'), $m[1], $line);
			}
			elseif (strpos($errstr, 'CData section not finished'))
			{
				$found['cdata'] = 1;
				return true;
			}
			if (strpos($errstr, 'XML declaration allowed only at the start of the document')
					|| strpos($errstr, 'String not closed expecting &quot; or ')
					|| strpos($errstr, 'Blank needed here')
					|| strpos($errstr, "Start tag expected, '<' not found")
					|| preg_match('~Entity (.+) not defined~', $errstr, $m)
					|| strpos($errstr, 'attributes construct error')
					|| strpos($errstr, 'Couldn\'t find end of Start Tag ')
					)
			{
				return true;
			}
			if (empty($error))
			{
				$error = $errstr;
			}
			$this->validationErrors[] = $error;
			return true; 
		}
		return false;
	}
	public function validateXml($xml, $checkDtd = false)
	{
		set_error_handler(array($this, 'DOMErrorHandler'));
		$xml = Tools::stripslashes($xml); 
		$doc = new DOMDocument;
		$doc->validateOnParse = (bool)$checkDtd;
		$doc->loadXML($xml);
		unset($doc);
		restore_error_handler();
		$re = '~'.self::XMLTPL_BLOCK.'=([\'"])([^\'"]+\b)?product(\b[^\'"]+)?\\1~isu';
		if (!preg_match($re, $xml, $matches))
		{
			$this->context->controller->warnings[] = $this->l('XML template does not contain any element identified as product.');
		}
		if (empty($this->validationErrors))
		{
			return true;
		}
		else
		{
			throw new InvalidArgumentException($this->l('XML template is not valid.').' '.implode(' ', $this->validationErrors));
		}
	}
	public function updateExportTemplate()
	{
		$this->checkExportFolder();
		$primaryKey = PcXmlFreeFeed::$definition['primary'];
		$query = new DbQuery;
		$query->select('f.`'.$primaryKey.'` id, f.`xml_source`, f.`allow_empty_tags`, f.`filename`')
			->from(PcXmlFreeFeed::$definition['table'], 'f');
		$data = Db::getInstance()->executeS($query, true);
		if ($data === false)
			throw new RuntimeException($this->l('Error reading from the database.').' : '.Db::getInstance()->getMsgError());
		$phpTemplate = $this->tplDir.'PcXmlFreeTemplate.tpl.php';
		if (!is_file($phpTemplate) || !is_readable($phpTemplate))
		{
			throw new InvalidArgumentException(sprintf($this->l('File (%s) does not exist or is not readable.'), basename($phpTemplate)));
		}
		require $this->tplDir.'PcXmlFreeTplGenerator.php';
		$this->generator = new PcXmlFreeTplGenerator($this->allowedProperties);
		$this->generator->setNamePrefix('feed')->setSource(Tools::file_get_contents($phpTemplate));
		foreach ($data as $file)
		{
			try
			{
				$this->validateXml($file['xml_source']);
				$this->generator->allowEmptyTags($file['allow_empty_tags'])
					->addBlock($file['id'], $file['xml_source']);
			} catch (Exception $e)
			{
				throw new InvalidArgumentException($e->getMessage().' ('.$file['filename'].')');
			}
		}
		if (!file_put_contents($this->tplDir.'PcXmlFreeTemplate.php', $this->generator->getTemplate()))
			throw new RuntimeException(sprintf($this->l('Directory (%s) is not writable. Please set the write permission.'), $this->tplDir));
		$this->generator->reset();
		return true;
	}
	protected function initExport(array $settings)
	{
		@set_time_limit(0);
		$this->exportInfo = $settings;
		$this->checkExportFolder();
		$this->context->shop = new Shop(1);  
		$this->context->link->allow = $this->exportInfo['rewrite'] = (int)Configuration::get('PS_REWRITING_SETTINGS', null, null, Configuration::get('PS_SHOP_DEFAULT'));
		Dispatcher::getInstance()->use_routes = $this->exportInfo['rewrite'];
		if ($this->exportInfo['rewrite'])
		{
			Dispatcher::getInstance()->loadRoutes();
		}
		$this->commonTplData = array(
			'date_feed'		=> time(),
			'shop_name'		=> $this->context->shop->name,
			'shop_url'		=> $this->context->shop->getBaseURL(),
			'lang_code'		=> array(),
			'lang_code_iso' => array(),
		);
		$this->commonTplData['update_feed'] = $this->commonTplData['date_feed'];
		$this->getFileInfo($this->exportInfo['feedIds']);
		$this->exportInfo['numWritten'] = 0;
		$this->updateExportTemplate();
		$this->getAllCategories();
		$this->openFiles();
	}
	protected function getFileInfo(array $feedIds)
	{
		$primaryKey = PcXmlFreeFeed::$definition['primary'];
		$query = new DbQuery;
		$query->select('f.`'.$primaryKey.'`, f.`'.$primaryKey.'` id, f.`xml_source`, f.`allow_empty_tags`, f.`filename`')
			->select('f.`id_lang`, f.`id_currency`, l.`iso_code`, l.`language_code`')
			->from(PcXmlFreeFeed::$definition['table'], 'f')
			->innerJoin('lang', 'l', 'l.`id_lang` = f.`id_lang` AND l.`active` = 1')
			->innerJoin('currency', 'c', 'c.`id_currency` = f.`id_currency`')
			->where('f.`'.$primaryKey.'` IN ('.implode(',', $feedIds).')')
			->orderBy('`id_currency`');
		$data = Db::getInstance()->executeS($query, true);
		if ($data === false)
			throw new RuntimeException($this->l('Error reading from the database.').' '.Db::getInstance()->getMsgError());
		elseif (empty($data))
			throw new RuntimeException($this->l('Output XML files are not defined.'));
		$this->exportInfo['feedIds'] = array();
		$this->tmp = array(
			'language_id_in' => '0',
			'multilang_array' => array(),
			'multilang_string' => array(),
		);
		foreach ($data as $feed)
		{
			$this->exportInfo['feedIds'][] = $feed['id'];
			if (!isset($this->languages[$feed['id_lang']]))
			{
				$this->languages[$feed['id_lang']] = new Language($feed['id_lang']);
				$this->commonTplData['lang_code'][$feed['id_lang']] = $feed['language_code'];
				$this->commonTplData['lang_code_iso'][$feed['id_lang']] = $feed['iso_code'];
				$this->tmp['language_id_in'] .= ', '.$feed['id_lang'];
				$this->tmp['multilang_string'][$feed['id_lang']] = '';
				$this->tmp['multilang_array'][$feed['id_lang']] = array();
			}
			if (!isset($this->currencies[$feed['id_currency']]))
			{
				$this->currencies[$feed['id_currency']] = new Currency($feed['id_currency']);
			}
		}
		$this->exportFiles = $data;
	}
	protected function getAlternativeCategories()
	{
		$sql = 'SELECT DISTINCT cp.id_category_default as `default`, alt.`id_category` as `alternative`
				FROM `'._DB_PREFIX_.'category_product` alt
				INNER JOIN (
					SELECT p3.`id_product`, p3.`id_category_default`, MIN(tmp2.`position`) position
					FROM `'._DB_PREFIX_.'product` p3
					INNER JOIN (
						SELECT DISTINCT p.`id_category_default`
						FROM `'._DB_PREFIX_.'product` p
						WHERE NOT EXISTS (
							SELECT *
							FROM `'._DB_PREFIX_.'category` c
							WHERE c.`id_category` = p.`id_category_default`
						)
					) p4 ON p4.`id_category_default` = p3.`id_category_default`
					LEFT JOIN `'._DB_PREFIX_.'category_product` tmp2 ON p3.`id_product` = tmp2.`id_product`
					WHERE tmp2.`position` > 0
					GROUP BY p3.`id_product`
					ORDER BY NULL
				) cp ON alt.`id_product` = cp.`id_product` AND alt.`position` = cp.`position`
				ORDER BY NULL';
		return (array)Db::getInstance()->ExecuteS($sql);
	}
	protected function getAllCategories()
	{
		$db = Db::getInstance();
		$rootCategory = (int)Configuration::get('PS_HOME_CATEGORY');
		if (!$rootCategory)
			$rootCategory = $this->context->shop->getCategory();
		$sql = 'SELECT c.`id_category` id, c.`id_parent` parent, c.`active`,
			cl.`name`, cl.`id_lang`, cl.`link_rewrite`
			FROM `'._DB_PREFIX_.'category` c
			INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON c.`id_category` = cl.`id_category`
				AND cl.`id_lang` IN ('.$this->tmp['language_id_in'].')
			WHERE c.`nleft` >
				(SELECT `nleft`
				FROM `'._DB_PREFIX_.'category`
				WHERE `id_category` = '.(int)$rootCategory.'
				LIMIT 1)
			ORDER BY c.`nleft`';
		$result = $db->query($sql);
		if ($result === false)
			throw new RuntimeException($this->l('Error reading from the database.').' '.$db->getMsgError());
		elseif (!$db->numRows())
			return;
		$this->categories = array('breadcrumb' => array(), 'rewriteLink' => array());
		while ($row = $db->nextRow($result))
		{
			$tmp = '';
			if (isset($this->categories['breadcrumb'][$row['parent']][$row['id_lang']]))
			{
				$tmp = $this->categories['breadcrumb'][$row['parent']][$row['id_lang']];
			}
			if ($row['active'])
			{
				$tmp .= (!empty($tmp) ? ' | ' : '').$row['name'];
			}
			$this->categories['breadcrumb'][$row['id']][$row['id_lang']] = $tmp;
			$this->categories['rewriteLink'][$row['id']][$row['id_lang']] = $row['link_rewrite'];
		}
		foreach ($this->getAlternativeCategories() as $cat)
		{
			if (isset($this->categories['breadcrumb'][$cat['alternative']]))
				$this->categories['breadcrumb'][$cat['default']] = $this->categories['breadcrumb'][$cat['alternative']];
			if (isset($this->categories['rewriteLink'][$cat['alternative']]))
				$this->categories['rewriteLink'][$cat['default']] = $this->categories['rewriteLink'][$cat['alternative']];
		}
	}
	public function createExportFiles($settings)
	{
		$oldCurrency = $this->context->currency;
		$oldLanguage = $this->context->language;
		$oldLinkRewriting = $this->context->link->allow;
		$oldShop = $this->context->shop;
		Shop::setContext(Shop::CONTEXT_SHOP, $this->context->shop->id);
		try
		{
			$this->initExport($settings);
			$this->exportProducts();
			$this->finishExport();
			$this->context->shop = $oldShop;
			$this->context->currency = $oldCurrency;
			$this->context->language = $oldLanguage;
			$this->context->link->allow = $oldLinkRewriting;
			Dispatcher::getInstance()->use_routes = $oldLinkRewriting;
			Shop::setContext(Shop::CONTEXT_ALL);
		} catch (Exception $e)
		{
			$this->closeFiles();
			$this->removeTempFiles();
			$this->context->shop = $oldShop;
			$this->context->currency = $oldCurrency;
			$this->context->language = $oldLanguage;
			$this->context->link->allow = $oldLinkRewriting;
			Dispatcher::getInstance()->use_routes = $oldLinkRewriting;
			Shop::setContext(Shop::CONTEXT_ALL);
			throw $e;
		}
		return $this->exportFiles;
	}
	protected function exportProducts()
	{
		$db = Db::getInstance();
		$sql = 'SELECT ps.`id_product`, ps.`id_category_default`, ps.`online_only`, ps.`date_upd`, ps.`date_add`,
			ps.`condition`, ps.`available_for_order`,
			p.`ean13`, IFNULL(p.`upc`, "") upc, p.`supplier_reference`, p.`reference`,
			pl.`id_lang`, pl.`name`, pl.`description`, pl.`description_short`, pl.`link_rewrite`,
			IFNULL(i.`id_image`, 0) id_image, IFNULL(m.`name`, "") manufacturer, pl.`available_later`, pl.`available_now`';
		$sql .= ' FROM `'._DB_PREFIX_.'product_shop` ps
			INNER JOIN `'._DB_PREFIX_.'product` p ON p.`id_product` = ps.`id_product`
			INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON ps.`id_product` = pl.`id_product`
				AND pl.`id_shop` = ps.`id_shop`
				AND EXISTS (
					SELECT `id_lang` FROM `'._DB_PREFIX_.'lang` l
					WHERE `id_lang` IN ('.$this->tmp['language_id_in'].') AND pl.`id_lang` = l.`id_lang`)
			LEFT JOIN `'._DB_PREFIX_.'image` i ON i.`id_product` = ps.`id_product` AND i.`cover` = 1
			LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON p.`id_manufacturer` = m.`id_manufacturer` AND m.`active` = 1
			WHERE ps.`active` = 1 ';
		$sql .= ' AND ps.`id_shop` = 1
			ORDER BY ps.`id_product`, pl.`id_lang`';
		$result = $db->query($sql);
		if ($result === false)
			throw new RuntimeException($this->l('Error reading from the database.').' '.$db->getMsgError());
		$lastProductId =  0;
		$tmp =  array();
		$product = new Product;
		$properties = array_flip(array_keys(get_object_vars($product))); 
		while ($row = $db->nextRow($result))
		{
			if ($row['id_product'] != $lastProductId)
			{
				if ($lastProductId > 0)
				{
					$this->addProductPrices($tmp, $product); 
					$this->writeProduct($tmp, $product);
				}
				$lastProductId = $row['id_product'];
				$tmp						= $row + $this->tmp['multilang_array'];
				$tmp['id']					= $row['id_product'];
				$tmp['ean']					= $row['ean13'];
				$tmp['date_add']			= strtotime($row['date_add']);
				$tmp['date_upd']			= strtotime($row['date_upd']);
				$tmp['update_item']			= $tmp['date_upd'];
				$tmp['availability']		= ''; 
				list($tmp['quantity'], $tmp['out_of_stock'])	= $this->getProductAvailability($row['id_product']);
				$tmp['days']			= $this->tmp['multilang_string'];
				$tmp['available_now']	= $this->tmp['multilang_string'];
				$tmp['available_later'] = $this->tmp['multilang_string'];
				$tmp['url']				= $this->tmp['multilang_string'];
				$tmp['name']			= $this->tmp['multilang_string'];
				$tmp['categories']		= $this->tmp['multilang_string'];
				$tmp['link_rewrite']	= $this->tmp['multilang_string'];
				$tmp['img_url']			= $this->tmp['multilang_string'];
				$tmp['description']		= $this->tmp['multilang_string'];
				$tmp['description_short']	= $this->tmp['multilang_string'];
				$tmp['price_vat']		= array();
				$tmp['price_vat_local'] = array();
				$tmp['price_vat_iso']	= array();
			}
			$this->context->language = $this->languages[$row['id_lang']];
			$product->id = $row['id_product'];
			$product->category = null;
			$tmp['categories'][$row['id_lang']] = null;
			foreach ($row as $key => $value)
			{
				if (isset($properties[$key]))
				{
					$product->$key = $value;
				}
			}
			if (!empty($this->categories['rewriteLink'][$row['id_category_default']][$row['id_lang']]))
			{
				$product->category = $this->categories['rewriteLink'][$row['id_category_default']][$row['id_lang']];
			}
			if (!empty($this->categories['breadcrumb'][$row['id_category_default']][$row['id_lang']]))
			{
				$tmp['categories'][$row['id_lang']] = $this->categories['breadcrumb'][$row['id_category_default']][$row['id_lang']];
			}
			$tmp['name'][$row['id_lang']] = $row['name'];
			$tmp['description'][$row['id_lang']] = $row['description'];
			$tmp['description_short'][$row['id_lang']] = $row['description_short'];
			$tmp['available_now'][$row['id_lang']] = $row['available_now'];
			$tmp['available_later'][$row['id_lang']] = $row['available_later'];
			$tmp['link_rewrite'][$row['id_lang']] = $row['link_rewrite'];
			if ($this->exportInfo['rewrite'] == 1)
			{
				$tmp['url'][$row['id_lang']] = $this->context->link->getProductLink($product, null, null, $row['ean13'], $row['id_lang'], null, 0, true);
				$tmp['img_url'][$row['id_lang']] = !empty($row['id_image']) ? $this->context->link->getImageLink($row['link_rewrite'], $row['id_image'], $this->exportInfo['imgType']) : '';
			}
			else
			{
				$tmp['url'][$row['id_lang']] = $this->context->link->getProductLink($product, null, null, $row['ean13'], $row['id_lang'], null, 0, false);
				$tmp['img_url'][$row['id_lang']] = !empty($row['id_image']) ? $this->context->link->getImageLink('', $row['id_image'], $this->exportInfo['imgType']) : '';
			}
			if ($tmp['quantity'] > 0)
				$tmp['days'][$row['id_lang']] = preg_match('~(\d+)~', $row['available_now'], $m) ? $m[1] : 0;
			else
				$tmp['days'][$row['id_lang']] = preg_match('~(\d+)~', $row['available_later'], $m) ? $m[1] : '';
		}
		if ($tmp)
		{
			$this->addProductPrices($tmp, $product); 
			$this->writeProduct($tmp, $product);
		}
		unset($tmp, $product);
	}
	protected function addProductPrices(&$productData, Product $product)
	{
		static  $usePriceVat = null, $defaultCurrencyId;
		if (is_null($usePriceVat))
		{
			if (!$this->generator)
			{
				 $usePriceVat = true;
			}
			else
			{
				 $usePriceVat = false;
				foreach ($this->exportInfo['feedIds'] as $feedId)
				{
					$usePriceVat |= ($this->generator->isUsed($feedId, PcXmlFreeTplGenerator::VARIABLE, 'price_vat')
						| $this->generator->isUsed($feedId, PcXmlFreeTplGenerator::VARIABLE, 'price_vat_iso')
						| $this->generator->isUsed($feedId, PcXmlFreeTplGenerator::VARIABLE, 'price_vat_local')
					);
				}
			}
			$defaultCurrencyId = Configuration::get('PS_CURRENCY_DEFAULT');
		}
		$this->context->currency = $this->currencies[$defaultCurrencyId];
		if ($usePriceVat)
			$priceVat = $product->getPrice(true);
		foreach ($this->currencies as $currency)
		{
			$this->context->currency = $currency;
			if ($usePriceVat)
			{
				$tmp = Tools::convertPrice($priceVat, $currency, true, $this->context);
				$productData['price_vat'][$currency->id] = number_format(Tools::ps_round($tmp, ((int)$currency->decimals * _PS_PRICE_DISPLAY_PRECISION_)), 2, '.', '');
				$productData['price_vat_local'][$currency->id] = Tools::displayPrice($tmp, $currency, false, $this->context);
				$productData['price_vat_iso'][$currency->id] = $productData['price_vat'][$currency->id].' '.$currency->iso_code;
			}
		}
	}
	protected function getProductAvailability($productId)
	{
		$sql = 'SELECT COALESCE(SUM(`quantity`), 0), COALESCE(MIN(`out_of_stock`), 2)
			FROM `'._DB_PREFIX_.'stock_available`
			WHERE `id_product` = '.(int)$productId;
		$sql .= ' AND `id_product_attribute` = 0 AND `id_shop` = 1';
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true);
		return !$result ? array(0, 2) : array_values($result[0]);
	}
	protected function openFiles()
	{
		require $this->tplDir.'PcXmlFreeTemplate.php';
		$this->template = new PcXmlFreeTemplate;
		$this->template->setCommonData($this->commonTplData);
		foreach ($this->exportFiles as $key => &$file)
		{
			$file['filename'] = $this->exportDir.$file['filename'];
				$this->removeTempFiles($file['filename']);
			$file['pointer'] = @fopen($file['filename'].$this->tempExt, 'w');
			if (!$file['pointer'])
			{
				$this->_errors[] = sprintf($this->l('XML file %s is not writable.'), basename($file['filename']));
				$this->removeTempFiles($file['filename']);
				unset($this->exportFiles[$key]);
			}
				$this->template
					->set('id_lang', $file['id_lang'])
					->set('id_currency', $file['id_currency']);
				@fwrite($file['pointer'], $this->template->
			{'feed'.$file['id'].'header'}());
		}
		unset($file);
		if (empty($this->exportFiles))
			throw new RuntimeException($this->l('XML files cannot be created, please check the access rights.'));
	}
	protected function writeProduct(array $productData, Product $product)
	{
		foreach ($this->exportFiles as $file)
		{
			$this->template
				->set('product', $productData)
				->set('id_lang', $file['id_lang'])
				->set('id_currency', $file['id_currency']);
			try
			{
				@fwrite($file['pointer'], $this->template->
				{'feed'.$file['id'].'product'}($productData));
			} catch (PcXmlFreeExportException $e)
			{ }
		}
		$this->exportInfo['numWritten']++;
		$product->flushPriceCache();
	}
	protected function finishExport()
	{
		foreach ($this->exportFiles as &$file)
		{
				$this->template
					->set('id_lang', $file['id_lang'])
					->set('id_currency', $file['id_currency']);
				@fwrite($file['pointer'], $this->template->
			{'feed'.$file['id'].'footer'}());
				@fclose($file['pointer']);
				$this->updateExportFiles($file['filename']);
			$file['filename'] = basename($file['filename']);
			unset($file['pointer']);
		}
		unset($file, $this->template, $this->languages, $this->currencies,
			$this->categories );
	}
	protected function closeFiles()
	{
		foreach ($this->exportFiles as $file)
		{
			@fclose($file['pointer']);
		}
		unset($file, $this->template, $this->languages, $this->currencies,
			$this->categories );
	}
	protected function updateExportFiles($filename = null)
	{
		if (!$filename)
		{
			foreach ($this->exportFiles as $file)
				$this->updateExportFiles($file['filename']);
			return;
		}
		if (file_exists($filename.$this->tempExt))
		{
			if (file_exists($filename) && ! @unlink($filename))
				throw new RuntimeException(sprintf($this->l('Unable to update the feed %s.'), basename($filename)));
			if (! @rename($filename.$this->tempExt, $filename))
				throw new RuntimeException(sprintf($this->l('Unable to update the feed %s.'), basename($filename)));
		}
	}
	protected function removeTempFiles($filename = null)
	{
		if (!$filename)
		{
			foreach ($this->exportFiles as $file)
				$this->removeTempFiles($file['filename']);
			return;
		}
		if (file_exists($filename.$this->tempExt))
		{
			if (! @unlink($filename.$this->tempExt))
				throw new RuntimeException(sprintf($this->l('Incorrect feed %s cannot be deleted.'), basename($filename)));
		}
	}
	public function getTplDir()
	{
		return $this->tplDir;
	}
	public function getExportDir()
	{
		return $this->exportDir;
	}
	public function getExportInfo()
	{
		return $this->exportInfo;
	}
	public function getModuleUrl()
	{
		return $this->moduleUrl;
	}
	public function readableFileSize($bytes, $precision = 2)
	{
		$bytes = round($bytes);
		if ($bytes <= 0)
			return '0 B';
		$units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
		foreach ($units as $unit)
		{
			if ($bytes < 1024 || $unit === end($units))
				break;
			$bytes /= 1024;
		}
		return round($bytes, $precision).' '.$unit;
	}
}