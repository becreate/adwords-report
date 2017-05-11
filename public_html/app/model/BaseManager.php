<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 16.04.2017
 * Project: xreporty
 * File: BaseManager.php
 */


namespace App\Model;


use Nette\Database\Context;
use Nette\Object;

abstract class BaseManager extends Object
{
	/** @var Context */
	protected $database;

	/**
	 * injektovany konstruktor
	 * @param Context $database
	 */
	public function __construct(Context $database)
	{
		$this->database = $database;
	}
}