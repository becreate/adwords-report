<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 25.04.2017
 * Project: xreporty
 * File: AccountPresenter.php
 */


namespace App\Presenters;

use App\Model\AccountManager;

use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201702\mcm\ManagedCustomerService;
use Google\AdsApi\AdWords\v201702\cm\Selector;
use Google\AdsApi\AdWords\v201702\cm\OrderBy;
use Google\AdsApi\AdWords\v201702\cm\SortOrder;
use Google\AdsApi\AdWords\v201702\cm\Paging;

class AccountPresenter extends BasePresenter
{
	/** @var  AccountManager @inject */
	public $accountManager;

	public function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn())
		{
			$this->redirect('Sign:in');
		}
	}

	public function actionDefault()
	{
		$this['breadcrumb']->addLink('Účty');
	}

	public function actionUpdate()
	{
		$this->processAccountData(new AdWordsServices(), $this->adwords_session);
		$this->flashMessage('Účty byly aktualizovány.', 'success');
		$this->redirect('Account:default');
	}

	public function renderDefault()
	{
		$accounts = $this->accountManager->getAccounts();

		// získání vp komponenty
		$visualPaginator = $this['visualPaginator'];
		// získání stránkovacího formuláře z vp
		$paginator = $visualPaginator->getPaginator();
		// počet položek na stránku
		$paginator->itemsPerPage = self::ITEMS_PER_PAGE;
		// celkový počet položek
		$paginator->itemCount = $accounts->count();
		// aplikace limitu
		$accounts->limit($paginator->itemsPerPage, $paginator->offset);

		$this->template->accounts = $accounts;
	}

	/**
	 * Získání AdWords účtu podle customer ID, postup podle Google AdWords API
	 *
	 * @param AdWordsServices $adWordsServices
	 * @param AdWordsSession  $adWordsSession
	 *
	 * @return array
	 */
	private function processAccountData(AdWordsServices $adWordsServices, AdWordsSession $adWordsSession)
	{
		$managedCustomerService = $adWordsServices->get(
			$adWordsSession, ManagedCustomerService::class);

		// Create selector.
		$selector = new Selector();
		$selector->setFields(['CustomerId', 'Name']);
		$selector->setOrdering([new OrderBy('CustomerId', SortOrder::ASCENDING)]);
		$selector->setPaging(new Paging(0, self::PAGE_LIMIT));

		// Maps from customer IDs to accounts and links.
		$customerIdsToAccounts = [];
		$customerIdsToChildLinks = [];
		$customerIdsToParentLinks = [];
		$totalNumEntries = 0;
		do {
			// Make the get request.
			$page = $managedCustomerService->get($selector);

			// Create links between manager and clients.
			if ($page->getEntries() !== null) {
				$totalNumEntries = $page->getTotalNumEntries();
				if ($page->getLinks() !== null) {
					foreach ($page->getLinks() as $link) {
						$managerId = $link->getManagerCustomerId();
						$managerId = preg_replace('/^[.*]/', '', $managerId);
						$clientCustomerId = $link->getClientCustomerId();
						$clientCustomerId = preg_replace('/^[.*]/', '', $clientCustomerId);

						$customerIdsToChildLinks[$managerId][] = $link;

						if ($link->getIsHidden() === false)
						{
							$customerIdsToParentLinks[$clientCustomerId] = $link;
						}
					}
				}

				foreach ($page->getEntries() as $account) {
					$customerId = $account->getCustomerId();
					$customerId = preg_replace('/^[.*]/', '', $customerId);
					if (array_key_exists($customerId,
						$customerIdsToParentLinks)) {
						$customerIdsToAccounts[$customerId]['customer_id'] = $customerId;
						$customerIdsToAccounts[$customerId]['name'] = $account->getName();

						// uložení dat do databáze
						$this->accountManager->saveAccountData($customerIdsToAccounts[$customerId]);
					}
				}
			}

			// Advance the paging index.
			$selector->getPaging()->setStartIndex(
				$selector->getPaging()->getStartIndex() + self::PAGE_LIMIT);
		} while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

		// třeba se někdy hodí
		// Find the root account.
		/*$rootAccount = null;
		foreach ($customerIdsToAccounts as $account) {
			$account_id = preg_replace('/^[.*]/', '', $account->getCustomerId());
			if (!array_key_exists($account_id,
				$customerIdsToParentLinks)) {
				$rootAccount = $account;
				//break;
			}
			else{
				$customerIdsToAccounts[$account_id] = $account;
			}
		}*/

	}
}