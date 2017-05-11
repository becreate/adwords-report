<?php

/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 04.05.2017
 * Project: xreporty
 * File: Breadcrumb.php
 */

namespace App\Breadcrumb;

use Nette\Application\UI\Control;
use Nette\Neon\Exception;
use Tester\Environment;

class Breadcrumb extends Control
{
	/** @var array links */
	public $links = array();

	/** @var null pokud není deklarovaný */
	private $templateFile = NULL;

	public function customTemplate($template)
	{
		$this->templateFile = $template ? $template : __DIR__ . DIRECTORY_SEPARATOR .'breadcrumb.latte';
	}

	/**
	 * Vykreslení funkce
	 */
	public function render()
	{
		$this->customTemplate($this->templateFile);

		//$this->template->setFile($this->templateFile);

		$this->template->links = $this->links;
		$this->template->render($this->templateFile);
	}

	/**
	 * Přidání odkazu
	 *
	 * @param string                        $title
	 * @param \Nette\Application\UI\Link    $link
	 * @param null                          $icon
	 */
	public function addLink($title, $link = NULL, $icon = NULL)
	{
		$this->links[md5($title)] = array(
			'title' => $title,
			'link'  => $link,
			'icon'  => $icon
		);
	}

	/**
	 * Úprava odkazu
	 *
	 * @param string                        $title
	 * @param \Nette\Application\UI\Link    $link
	 * @param null                          $icon
	 */
	public function editLink($title, $link = NULL, $icon = NULL)
	{
		if (array_key_exists(md5($title), $this->links))
		{
			$this->addLink($title, $link, $icon);
		}
	}

	/**
	 * Odstranění odkazu
	 *
	 * @param $key
	 *
	 * @throws Exception
	 */
	public function removeLink($key)
	{
		$key = md5($key);
		if (array_key_exists($key, $this->links))
		{
			unset($this->links[$key]);
		}
		else
		{
			throw new Exception('Klíč neexistuje.');
		}
	}
}