<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 11.05.2017
 * Project: xreporty
 * File: SortFormFactory.php
 */


namespace App\Forms;

use App\Model\CampaignManager;
use Nette;
use Nette\Application\UI\Form;


class SortFormFactory
{
	use Nette\SmartObject;

	/** @var FormFactory */
	private $factory;

	/** @var array */
	private $sortData;

	/** @var  CampaignManager */
	public $campaignManager;

	/** @persistent */
	public $customerId;

	public function __construct(FormFactory $factory, CampaignManager $campaignManager)
	{
		$this->factory = $factory;
		$this->campaignManager = $campaignManager;
	}

	public function setSortData(array $sortData)
	{
		$this->sortData = $sortData;
	}

	/**
	 * @return Form
	 */
	public function create()
	{
		$form = $this->factory->create();
		$form->getElementPrototype()->class = 'ajax';

		$form->addSelect('name', 'Název', $this->sortData)
			->setAttribute('class', 'ajax');
		$form->addSelect('cost', 'Cena', $this->sortData);

		$form->onSuccess[] = [$this, 'sortFormSuccess'];

		return $form;
	}

	public function sortFormSuccess(Form $form, $values)
	{
		if ($form->getPresenter()->isAjax())
		{
			$campaign = $this->campaignManager->getCampaignsByCustomerId($this->customerId, $values);
			$form->getPresenter()->getTemplate()->campaigns = $campaign;
			$form->getPresenter()->redrawControl('campaignList');
		}
		else
		{
			$form->getPresenter()->redirect('this');
		}
	}
}