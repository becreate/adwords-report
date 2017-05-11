<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 10.05.2017
 * Project: xreporty
 * File: SortFormControl.php
 */


namespace App\Controls;


use Nette\Application\UI\Control;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Utils\Callback;
use Tomaj\Form\Renderer\BootstrapRenderer;

class SortFormControl extends Control
{
	const FILTER_IN = 1;
	const FILTER_OUT = 2;

	/** @persistent  */
	public $data;
	/** @var  array */
	protected $dataSort;
	/** @var array */
	public $onSort;
	/** @var  string */
	private $templateFile;
	/** @var array */
	private $defaultValues = array();
	/** @var  bool */
	private $ajaxRequest;

	public function __construct()
	{
		parent::__construct();
		$this->templateFile = __DIR__ . DIRECTORY_SEPARATOR . 'sort-form.latte';

	}

	public function setAjaxRequest($value = TRUE)
	{
		$this->ajaxRequest = $value;
		return $this;
	}

	public function getData()
	{
		$data = array();
		if ($this->data != NULL) {
			parse_str($this->data, $data);
		}
		// add null values
		foreach ($this->getComponent('form')->getComponents() as $key => $value) {
			if (!isset($data[$key]) || $data[$key] === '') {
				$data[$key] = NULL;
			}
		}

		// Filter out callback
		if ($this->dataSort) {
			if (!empty($data)) {
				$dataSort = Callback::invokeArgs($this->dataSort, array($data, self::FILTER_OUT));
				if ($dataSort && is_array($dataSort)) {
					$data = $dataSort;
				}
			}
		}

		return $data;
	}

	/**
	 * @param $data
	 *
	 * @return SortFormControl
	 */
	private function saveData($data)
	{
		$sort = array();
		foreach ($data as $key => $value) {
			if ($value !== '') {
				$sort[$key] = $value;
			}
		}

		// Filter in callback
		if (!empty($sort)) {
			$this->data = http_build_query($sort, '', '&');
		} else {
			$this->data = NULL;
		}
		return $this;
	}

	protected function createComponentForm()
	{
		$form = new Form();
		$form->setRenderer(new BootstrapRenderer());
		$elementPrototype = $form->getElementPrototype();

		$elementPrototype->class[] = lcfirst(self::getReflection()->getShortName());
		$elementPrototype->class[] = lcfirst($this->name);
		!$this->ajaxRequest ? : $elementPrototype->class[] = 'ajax';

		$sorting = ['ASC' => 'Vzestupně', 'DESC' => 'Sestupně'];

		$form->addSelect('name', 'Název', $sorting);
		$form->addSelect('cost', 'Cena', $sorting);

		$form->addSubmit('send', 'Seřadit')
			->onClick[] = Callback::closure($this, 'processSubmit');


		return $form;

	}

	public function processSubmit(SubmitButton $button)
	{
		$values = $button->getForm()->getValues(TRUE);
		dump($values);
		$this->saveData($values);
		$this->onSort($this, $values);

		if (!$this->presenter->isAjax()) {
			$this->presenter->redirect('this', ['visualPaginator-page' => NULL]);
		}
	}

	/**
	 * @return string
	 */
	public function getTemplateFile()
	{
		return $this->templateFile;
	}


	/**
	 * @param string $file
	 * @return void
	 */
	public function setTemplateFile($file)
	{
		if ($file) {
			$this->templateFile = $file;
		}
	}

	private function loadData()
	{
		$data = $this->getData();

		/** @var Form $form */
		$form = $this['form'];

		foreach ($data as $key => $value) {
			if ($value !== '' && isset($form[$key])) {
				$form[$key]->setValue($value);
			}
		}
	}


	public function render()
	{
		$this->loadData();

		$template = $this->template;
		$template->_form = $template->form = $this->getComponent('form');
		$template->setFile($this->getTemplateFile());
		$template->render();
	}
}