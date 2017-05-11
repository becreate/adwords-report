<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 26.04.2017
 * Project: xreporty
 * File: CampaignManager.php
 */


namespace App\Model;


class CampaignManager extends BaseManager
{
	const
		TABLE_NAME                      = 'campaigns',
		COLUMN_ID                       = 'id',
		COLUMN_CAMPAING_ID              = 'campaign_id',
		COLUMN_CUSTOMER_ID              = 'customer_id',
		COLUMN_CAMPAING_NAME            = 'name',
		COLUMN_CAMPAING_AMOUNT          = 'amount',
		COLUMN_CAMPAING_STATUS          = 'status',
		COLUMN_CAMPAING_IMPRESSIONS     = 'impressions',
		COLUMN_CAMPAING_CLICKS          = 'clicks',
		COLUMN_CAMPAING_CTR             = 'ctr',
		COLUMN_CAMPAING_AVG_CPC         = 'avg_cpc',
		COLUMN_CAMPAING_COST            = 'cost',
		COLUMN_CAMPAING_AVG_POSITION    = 'avg_position',
		COLUMN_CAMPAING_CONVERSION      = 'conversion',
		COLUMN_CAMPAING_CONV_RATE       = 'conv_rate',
		COLUMN_CAMPAING_CONV_VALUE      = 'total_conv_value'
	;

	public function getCampaignsByCustomerId($customerId, $sortData = NULL)
	{
		$selection =  $this->database->table(self::TABLE_NAME);
		$selection->where(self::COLUMN_CUSTOMER_ID, $customerId);
					if ($sortData)
					{
						$selection->order(self::COLUMN_CAMPAING_NAME . ' ' . $sortData[self::COLUMN_CAMPAING_NAME]);
						$selection->order(self::COLUMN_CAMPAING_COST . ' ' . $sortData[self::COLUMN_CAMPAING_COST]);
					}
					else
					{
						$selection->order(self::COLUMN_CAMPAING_NAME . ' ASC');
					}
		return $selection;

	}

	public function getCampaignById($campaignId)
	{
		return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_CAMPAING_ID, $campaignId)->fetch();
	}

	public function saveDataCampaign($data)
	{
		if ($data[self::COLUMN_CAMPAING_ID])
		{
			$campaign = $this->getCampaignById($data[self::COLUMN_CAMPAING_ID]);
			if ($campaign)
			{
				$this->database->table(self::TABLE_NAME)->where(self::COLUMN_CAMPAING_ID, $data[self::COLUMN_CAMPAING_ID])->update($data);
			}
			else
			{
				$this->database->table(self::TABLE_NAME)->insert($data);
			}
		}
	}

	public function getAccountName($customerId)
	{
		return $this->database->table('accounts')
			->alias(':campaigns', 'camp')
			->select('accounts.name')
			->where('camp.customer_id', $customerId)
			->fetch();
	}

	public function getCampaigns()
	{
		return $this->database->table(self::TABLE_NAME)
			->select(self::COLUMN_CAMPAING_ID)
			->select(self::COLUMN_CUSTOMER_ID)
			->select(self::COLUMN_CAMPAING_NAME)
			->select(self::COLUMN_CAMPAING_AMOUNT)
			->select(self::COLUMN_CAMPAING_STATUS)
			->select(self::COLUMN_CAMPAING_IMPRESSIONS)
			->select(self::COLUMN_CAMPAING_CLICKS)
			->select('REPLACE(' . self::COLUMN_CAMPAING_CTR . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_CAMPAING_AVG_CPC . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_CAMPAING_COST . ',".", ",")')
			->select(self::COLUMN_CAMPAING_AVG_POSITION)
			->select('REPLACE(' . self::COLUMN_CAMPAING_CONVERSION . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_CAMPAING_CONV_RATE . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_CAMPAING_CONV_VALUE . ',".", ",")')
			->fetchAll();
	}

	public function getCampaignsByFilterData($customerId, $filterData, $sortData)
	{
		return $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_CUSTOMER_ID, $customerId)
			->where(self::COLUMN_CAMPAING_CLICKS . ' ?', $filterData[self::COLUMN_CAMPAING_CLICKS])
			->order(self::COLUMN_CAMPAING_NAME, $sortData[self::COLUMN_CAMPAING_NAME])
			->order(self::COLUMN_CAMPAING_COST, $sortData[self::COLUMN_CAMPAING_COST]);
	}

}