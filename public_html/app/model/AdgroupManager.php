<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 01.05.2017
 * Project: xreporty
 * File: AdgroupManager.php
 */


namespace App\Model;


class AdgroupManager extends BaseManager
{
	const
		TABLE_NAME                      = 'adgroups',
		COLUMN_ID                       = 'id',
		COLUMN_CAMPAIGN_ID              = 'campaign_id',
		COLUMN_ADGROUP_ID               = 'adgroup_id',
		COLUMN_ADGROUP_NAME             = 'adgroup_name',
		COLUMN_ADGROUP_STATUS          = 'status',
		COLUMN_ADGROUP_IMPRESSIONS     = 'impressions',
		COLUMN_ADGROUP_CLICKS          = 'clicks',
		COLUMN_ADGROUP_CTR             = 'ctr',
		COLUMN_ADGROUP_AVG_CPC         = 'avg_cpc',
		COLUMN_ADGROUP_COST            = 'cost',
		COLUMN_ADGROUP_AVG_POSITION    = 'avg_position',
		COLUMN_ADGROUP_CONVERSION      = 'conversion',
		COLUMN_ADGROUP_CONV_RATE       = 'conv_rate',
		COLUMN_ADGROUP_CONV_VALUE      = 'total_conv_value'
	;

	public function getAdgroupsByCampaignId($campaignId)
	{
		return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_CAMPAIGN_ID, $campaignId)->fetchAll();
	}

	public function getAdgroupById($adgroupId)
	{
		return $this->database->table(self::TABLE_NAME)
			->select(self::COLUMN_ADGROUP_NAME)
			->select(self::COLUMN_ADGROUP_STATUS)
			->select(self::COLUMN_ADGROUP_CLICKS)
			->select(self::COLUMN_ADGROUP_IMPRESSIONS)
			->select(self::COLUMN_ADGROUP_CTR)
			->select(self::COLUMN_ADGROUP_AVG_CPC)
			->select(self::COLUMN_ADGROUP_COST)
			->select(self::COLUMN_ADGROUP_AVG_POSITION)
			->select(self::COLUMN_ADGROUP_CONVERSION)
			->select(self::COLUMN_ADGROUP_CONV_RATE)
			->select(self::COLUMN_ADGROUP_CONV_VALUE)
			->where(self::COLUMN_ADGROUP_ID, $adgroupId)->fetch();
	}

	public function getCampaignName($campaignId)
	{
		return $this->database->table('campaigns')
			->alias(':adgroups', 'adg')
			->select('campaigns.name')
			->where('adg.campaign_id', $campaignId)
			->fetch();
	}

	public function saveAdgroupData($data)
	{
		$adgroup = $this->getAdgroupById($data[self::COLUMN_ADGROUP_ID]);
		if ($adgroup)
		{
			$this->database->table(self::TABLE_NAME)->where(self::COLUMN_ADGROUP_ID, $data[self::COLUMN_ADGROUP_ID])->update([
				self::COLUMN_ADGROUP_NAME           => $data['adgroup_name'],
				self::COLUMN_ADGROUP_STATUS         => $data['status'],
				self::COLUMN_ADGROUP_CLICKS         => $data['clicks'],
				self::COLUMN_ADGROUP_IMPRESSIONS    => $data['impressions'],
				self::COLUMN_ADGROUP_CTR            => $data['ctr'],
				self::COLUMN_ADGROUP_AVG_CPC        => $data['avg_cpc'],
				self::COLUMN_ADGROUP_COST           => $data['cost'],
				self::COLUMN_ADGROUP_AVG_POSITION   => $data['avg_position'],
				self::COLUMN_ADGROUP_CONVERSION     => $data['conversion'],
				self::COLUMN_ADGROUP_CONV_RATE      => $data['conv_rate'],
				self::COLUMN_ADGROUP_CONV_VALUE     => $data['total_conv_value']
			]);

		}
		else
		{
			$this->database->table(self::TABLE_NAME)->insert($data);
		}
	}

	public function getAdgroups()
	{
		return $this->database->table(self::TABLE_NAME)
			->select(self::COLUMN_CAMPAING_ID)
			->select(self::COLUMN_ADGROUP_ID)
			->select(self::COLUMN_ADGROUP_NAME)
			->select(self::COLUMN_ADGROUP_STATUS)
			->select(self::COLUMN_ADGROUP_IMPRESSIONS)
			->select(self::COLUMN_ADGROUP_CLICKS)
			->select('REPLACE(' . self::COLUMN_ADGROUP_CTR . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_ADGROUP_AVG_CPC . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_ADGROUP_COST . ',".", ",")')
			->select(self::COLUMN_ADGROUP_AVG_POSITION)
			->select('REPLACE(' . self::COLUMN_ADGROUP_CONVERSION . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_ADGROUP_CONV_RATE . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_ADGROUP_CONV_VALUE . ',".", ",")')
			->fetchAll();
	}
}