<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 01.05.2017
 * Project: xreporty
 * File: AccountManager.php
 */


namespace App\Model;


class AccountManager extends BaseManager
{
	/**
	 * Konstanty pro práci s daty
	 */
	const
		TABLE_NAME                      = 'accounts',
		COLUMN_CUSTOMER_ID              = 'customer_id',
		COLUMN_ACCOUNT_NAME             = 'name'
	;
	public function getAccounts()
	{
		return $this->database->table(self::TABLE_NAME);
	}

	public function getCampaignById($accountId)
	{
		return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_CUSTOMER_ID, $accountId)->fetch();
	}

	public function saveAccountData($data)
	{
		if ($data[self::COLUMN_CUSTOMER_ID])
		{
			$account = $this->getCampaignById($data[self::COLUMN_CUSTOMER_ID]);
			if ($account)
			{
				$this->database->table(self::TABLE_NAME)->where(self::COLUMN_CUSTOMER_ID, $data[self::COLUMN_CUSTOMER_ID])->update($data);
			}
			else
			{
				$this->database->table(self::TABLE_NAME)->insert($data);
			}
		}
	}
}